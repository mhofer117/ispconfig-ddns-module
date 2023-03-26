<?php
require_once(dirname(__FILE__) . '/token/DdnsToken.php');

class DdnsUpdater
{
    /** @var app $_ispconfig */
    protected $_ispconfig;
    /** @var string $_remote_ip */
    protected $_remote_ip;
    /** @var DdnsToken $_token */
    protected $_token;
    /** @var DdnsRequest[] $_requests */
    protected $_requests = [];
    /** @var DdnsResponseWriter $_response_writer */
    protected $_response_writer;

    public function __construct(app $ispconfig, array $config)
    {
        $this->_ispconfig = $ispconfig;
        if ($this->_ispconfig->is_under_maintenance()) {
            $this->_response_writer->maintenance();
            exit;
        }
        if (isset($_SERVER['HTTP_X_ORIGINAL_REQUEST_URI'])) {
            $request_uri = parse_url($_SERVER['HTTP_X_ORIGINAL_REQUEST_URI'], PHP_URL_PATH);
        } else {
            $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        }
        $this->_remote_ip = $this->getRequestIp($config);
        switch ($request_uri) {
            case '/nic/dyndns':
            case '/nic/statdns':
                # DynDns 1 endpoints
                require_once(dirname(__FILE__) . '/request/DynDnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DynDns1ResponseWriter.php');
                $this->_response_writer = new DynDns1ResponseWriter($ispconfig);
                $this->_requests[] = new DynDnsRequest($_GET['host_id']);
                break;
            case '/nic/update':
                # DynDns 2 endpoint
                require_once(dirname(__FILE__) . '/request/DynDnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DynDns2ResponseWriter.php');
                $this->_response_writer = new DynDns2ResponseWriter();
                $hostnames = explode(',', $_GET['hostname']);
                if (sizeof($hostnames) == 0) {
                    $this->_response_writer->missingInput(new DynDnsRequest(null));
                    exit;
                }
                foreach ($hostnames as $hostname) {
                    $this->_requests[] = new DynDnsRequest($hostname);
                }
                break;
            default:
                require_once(dirname(__FILE__) . '/request/DefaultDdnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DefaultDdnsResponseWriter.php');
                $this->_response_writer = new DefaultDdnsResponseWriter($ispconfig);
                $this->_requests[] = new DefaultDdnsRequest();
        }
        $this->_token = new DdnsToken($ispconfig, $this->_remote_ip, $this->getTokenFromRequest(), $this->_response_writer);
    }

    public function getRequestIp($config): string
    {
        $remote_ip = $_SERVER['REMOTE_ADDR'];
        if (!isset($_SERVER["HTTP_{$config['PROXY_KEY_HEADER']}"]) || !isset($_SERVER["HTTP_{$config['PROXY_IP_HEADER']}"])) {
            return $remote_ip;
        }
        if ($remote_ip !== $config['TRUSTED_PROXY_IP']) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "Untrusted proxy: '$remote_ip' does not match config TRUSTED_PROXY_IP.\n";
            exit;
        }
        if (empty($config['TRUSTED_PROXY_KEY']) || $_SERVER["HTTP_{$config['PROXY_KEY_HEADER']}"] !== $config['TRUSTED_PROXY_KEY']) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "Proxy key is invalid.\n";
            exit;
        }
        $forwarded_ip = $_SERVER["HTTP_{$config['PROXY_IP_HEADER']}"];
        if (filter_var($forwarded_ip, FILTER_VALIDATE_IP) === false) {
            header("HTTP/1.1 500 Internal Server Error");
            echo "The proxy has forwarded an invalid IP: '$forwarded_ip'.\n";
            exit;
        }
        return $forwarded_ip;
    }

    protected function getTokenFromRequest(): ?string
    {
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
        } else if (isset($_SERVER['PHP_AUTH_PW'])) {
            $token = $_SERVER['PHP_AUTH_PW'];
        } else {
            return null;
        }
        // only hex characters allowed in token
        return preg_replace("/[^0-9^a-f]/", "", $token);
    }

    public function process(): void
    {
        $records = [];
        foreach ($this->_requests as $request) {
            $request->autoSetMissingInput($this->_token, $this->_remote_ip);
            $request->validate($this->_token, $this->_response_writer, $this->_ispconfig);
            $records[] = $this->loadDnsRecord($request);
        }
        $this->updateDnsRecords($records);
    }

    protected function loadDnsRecord(DdnsRequest $request): array
    {
        // try to load zone
        $soa = $this->_ispconfig->db->queryOneRecord(
            "SELECT id,server_id,sys_userid,sys_groupid,origin,ttl,serial FROM dns_soa WHERE origin=?",
            $request->getZone()
        );
        if ($soa == null || $soa['id'] == null) {
            $this->_response_writer->dnsNotFound("zone '{$request->getZone()}'");
            exit;
        }

        // try to load record (for update/delete)
        $rr = null;
        if ($request->getAction() !== 'add') {
            $rrResult = $this->_ispconfig->db->query(
                "SELECT id,data,ttl,serial FROM dns_rr WHERE type=? AND name=? AND zone=?",
                $request->getRecordType(),
                $request->getRecord(),
                $soa['id']
            );
            if ($rrResult && $rrResult->rows() > 0) {
                if ($request->getAction() === 'update') {
                    // update requests are only possible with DDNS because we do not work with IDs or 'oldData' (yet)
                    // there should not be more than one A / AAAA entry for this...
                    if ($rrResult->rows() > 1) {
                        $rrResult->free();
                        $this->_response_writer->internalError("Found more than one record to update, unable to proceed");
                        exit;
                    }
                    $rr = $rrResult->get();
                    $rrResult->free();
                } else {
                    // for delete, check matching record by data
                    while($record = $rrResult->get()) {
                        if ($record['data'] === $request->getData()) {
                            $rr = $record;
                            break;
                        }
                    }
                    $rrResult->free();
                }
            }
            if ($rr === null) {
                $this->_response_writer->dnsNotFound(
                    "record '{$request->getRecord()}' of type '{$request->getRecordType()}' in zone '{$request->getZone()}'"
                );
                exit;
            }
        }

        return [
            'request' => $request,
            'soa' => $soa,
            'rr' => $rr
        ];
    }

    protected function updateDnsRecords(array $records): void
    {
        $update_performed = false;
        $unique_soa = [];
        $longest_ttl = 0;
        // update DNS records
        foreach ($records as $record) {
            $request = $record['request'];
            $soa = $record['soa'];
            $rr = $record['rr'];
            if ($request->getAction() === 'delete') {
                // delete record
                if ($rr === null) {
                    // cannot delete non-existing record
                    continue;
                }
                $this->_ispconfig->db->datalogDelete('dns_rr', 'id', $rr['id']);
                $update_performed = true;
                if ($longest_ttl < (int)$rr['ttl']) {
                    $longest_ttl = (int)$rr['ttl'];
                }
            } else if ($request->getAction() === 'update') {
                // update record
                if ($rr === null) {
                    $this->_response_writer->internalError("Record is missing for action update, unable to proceed");
                    exit;
                }
                // check if update is required
                if ($rr['data'] == $request->getData()) {
                    continue;
                }
                // Update the RR
                $rr_update = array(
                    "data" => $request->getData(),
                    "serial" => $this->_ispconfig->validate_dns->increase_serial($rr["serial"]),
                    "stamp" => date('Y-m-d H:i:s')
                );
                $this->_ispconfig->db->datalogUpdate('dns_rr', $rr_update, 'id', $rr['id']);
                $update_performed = true;
                if ($longest_ttl < (int)$rr['ttl']) {
                    $longest_ttl = (int)$rr['ttl'];
                }
            } else if ($request->getAction() === 'add') {
                // create record
                // Get the limits of the client
                $client_group_id = intval($soa["sys_groupid"]);
                $client = $this->_ispconfig->db->queryOneRecord(
                    "SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?",
                    $client_group_id
                );
                // Check if the user may add another record.
                if($client["limit_dns_record"] >= 0) {
                    $tmp = $this->_ispconfig->db->queryOneRecord(
                        "SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?",
                        $client_group_id
                    );
                    if($tmp["number"] >= $client["limit_dns_record"]) {
                        $this->_response_writer->forbidden("new record. dns record limit reached.");
                        exit;
                    }
                }
                $rr_insert = array(
                    // "id" auto-generated
                    "server_id" => $soa["server_id"],
                    "sys_userid" => $soa["sys_userid"],
                    "sys_groupid" => $soa["sys_groupid"],
                    "sys_perm_user" => 'riud',
                    "sys_perm_group" => 'riud',
                    "zone" => $soa['id'],
                    "type" => $request->getRecordType(),
                    "ttl" => '3600',
                    "name" => $request->getRecord(),
                    "data" => $request->getData(),
                    "serial" => $this->_ispconfig->validate_dns->increase_serial($rr["serial"]),
                    "active" => 'Y',
                    "stamp" => date('Y-m-d H:i:s')
                );
                // insert the RR
                $this->_ispconfig->db->datalogInsert('dns_rr', $rr_insert, 'id');
                $update_performed = true;
                if ($longest_ttl < (int)$soa['ttl']) {
                    $longest_ttl = (int)$soa['ttl'];
                }
            }
            if (!array_key_exists($soa['id'], $unique_soa)) {
                $unique_soa[$soa['id']] = $soa;
            }
        }

        if (!$update_performed) {
            $this->_response_writer->noUpdateRequired($records[0]['request']);
            exit;
        }

        // Update the serial number of the affected SOA records
        foreach ($unique_soa as $soa) {
            //* Update the serial number of the SOA record
            $soa_update = array(
                "serial" => $this->_ispconfig->validate_dns->increase_serial($soa["serial"])
            );
            $this->_ispconfig->db->datalogUpdate('dns_soa', $soa_update, 'id', $soa['id']);

            // cron runs every full minute, calculate seconds left
            $cron_eta = 60 - date('s');
        }
        $this->_response_writer->successfulUpdate($records[0]['request'], $longest_ttl, $cron_eta);
    }
}
