<?php
require_once(dirname(__FILE__) . '/token/DdnsToken.php');

class DdnsUpdater
{
    /** @var app $_ispconfig */
    protected $_ispconfig;
    /** @var DdnsToken $_token */
    protected $_token;
    /** @var DdnsRequest[] $_requests */
    protected $_requests = [];
    /** @var DdnsResponseWriter $_response_writer */
    protected $_response_writer;

    public function __construct(app $ispconfig)
    {
        $this->_ispconfig = $ispconfig;
        switch ($_SERVER['REQUEST_URI']) {
            case '/nic/update':
                require_once(dirname(__FILE__) . '/request/DynDnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DynDns1ResponseWriter.php');
                $this->_response_writer = new DynDns1ResponseWriter($ispconfig);
                $this->_requests[] = new DynDnsRequest($_GET['host_id']);
                break;
            case '/v3/update':
                require_once(dirname(__FILE__) . '/request/DynDnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DynDns2ResponseWriter.php');
                $this->_response_writer = new DynDns2ResponseWriter();
                $hostnames = explode(',', $_GET['hostname']);
                if (sizeof($hostnames) == 0) {
                    $this->_response_writer->missingInput(new DynDnsRequest(null));
                    exit;
                }
                foreach ($hostnames as &$hostname) {
                    $this->_requests[] = new DynDnsRequest($hostname);
                }
                break;
            default:
                require_once(dirname(__FILE__) . '/request/DefaultDdnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DefaultDdnsResponseWriter.php');
                $this->_response_writer = new DefaultDdnsResponseWriter($ispconfig);
                $this->_requests[] = new DefaultDdnsRequest();
        }
        if ($this->_ispconfig->is_under_maintenance()) {
            $this->_response_writer->maintenance();
            exit;
        }
        $this->_token = new DdnsToken($ispconfig, $this->getTokenFromRequest(), $this->_response_writer);
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
        foreach ($this->_requests as $request) {
            $request->autoSetMissingInput($this->_token);
            $request->validate($this->_token, $this->_response_writer);
            $this->updateDnsRecords($request);
        }
    }

    protected function updateDnsRecords(DdnsRequest $request): void
    {
        // try to load zone
        $soa = $this->_ispconfig->db->queryOneRecord("SELECT id,origin,serial FROM dns_soa WHERE origin=?", $request->getZone());
        if ($soa == null || $soa['id'] == null) {
            $this->_response_writer->dnsNotFound("zone '{$request->getZone()}'");
            exit;
        }

        // try to load record
        $rr = null;
        $rrResult = $this->_ispconfig->db->query("SELECT id,data,ttl,serial FROM dns_rr WHERE type=? AND name=? AND zone=?", $request->getRecordType(), $request->getRecord(), $soa['id']);
        if ($rrResult && $rrResult->rows() > 0) {
            if ($rrResult->rows() > 1) {
                $this->_response_writer->internalError("Found more than one record to update, unable to proceed");
                exit;
            }
            $rr = $rrResult->get();
            $rrResult->free();
        }
        if ($rr == null) {
            $this->_response_writer->dnsNotFound("record '{$request->getRecord()}' of type '{$request->getRecordType()}' in zone '{$request->getZone()}'");
            exit;
        }

        // check if update is required
        if ($rr['data'] == $request->getData()) {
            $this->_response_writer->noUpdateRequired($request->getData());
            exit;
        }

        //* Update the RR record
        $rr_update = array(
            "data" => $request->getData(),
            "serial" => $this->_ispconfig->validate_dns->increase_serial($rr["serial"]),
            "stamp" => date('Y-m-d H:i:s')
        );
        $this->_ispconfig->db->datalogUpdate('dns_rr', $rr_update, 'id', $rr['id']);

        //* Update the serial number of the SOA record
        $soa_update = array(
            "serial" => $this->_ispconfig->validate_dns->increase_serial($soa["serial"])
        );
        $this->_ispconfig->db->datalogUpdate('dns_soa', $soa_update, 'id', $soa['id']);

        // cron runs every full minute, calculate seconds left
        $cron_eta = 60 - date('s');
        $this->_response_writer->successfulUpdate($request, $rr['ttl'], $cron_eta);
    }
}
