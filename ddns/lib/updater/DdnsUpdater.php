<?php
require_once(dirname(__FILE__) . '/token/DdnsToken.php');

class DdnsUpdater
{
    /** @var DdnsRequest $_request */
    protected $_request;
    /** @var DdnsResponseWriter $_response_writer */
    protected $_response_writer;

    public function __construct()
    {
        switch ($_SERVER['REQUEST_URI']) {
            case '/nic/update':
                require_once(dirname(__FILE__) . '/request/DynDns1Request.php');
                require_once(dirname(__FILE__) . '/response/DynDns1ResponseWriter.php');
                $this->_request = new DynDns1Request();
                $this->_response_writer = new DynDns1ResponseWriter();
                break;
            case '/v3/update':
                require_once(dirname(__FILE__) . '/request/DynDns2Request.php');
                require_once(dirname(__FILE__) . '/response/DynDns2ResponseWriter.php');
                $this->_request = new DynDns2Request();
                $this->_response_writer = new DynDns2ResponseWriter();
                break;
            default:
                require_once(dirname(__FILE__) . '/request/DefaultDdnsRequest.php');
                require_once(dirname(__FILE__) . '/response/DefaultDdnsResponseWriter.php');
                $this->_request = new DefaultDdnsRequest();
                $this->_response_writer = new DefaultDdnsResponseWriter();
        }
    }

    public function process(): void
    {
        /** @var app $app */
        if ($app->is_under_maintenance()) {
            $this->_response_writer->maintenance();
            exit;
        }

        if ($this->_request->getToken() == null) {
            $this->_response_writer->invalidOrMissingToken();
            exit;
        }

        $token = DdnsToken::initToken($this->_request->getToken(), $this->_response_writer);

        $this->_request->autoSetMissingInput($token);
        $this->_request->validate($token, $this->_response_writer);

        $this->updateDnsRecord();
    }

    protected function updateDnsRecord(): void
    {
        /** @var app $app */
        // try to load zone
        $soa = $app->db->queryOneRecord("SELECT id,origin,serial FROM dns_soa WHERE origin=?", $this->_request->getZone());
        if ($soa == null || $soa['id'] == null) {
            $this->_response_writer->dnsNotFound("zone '{$this->_request->getZone()}'");
            exit;
        }

        // try to load record
        $rr = null;
        $rrResult = $app->db->query("SELECT id,data,ttl,serial FROM dns_rr WHERE type=? AND name=? AND zone=?", $this->_request->getRecordType(), $this->_request->getRecord(), $soa['id']);
        if ($rrResult && $rrResult->rows() > 0) {
            if ($rrResult->rows() > 1) {
                $this->_response_writer->internalError("Found more than one record to update, unable to proceed");
                exit;
            }
            $rr = $rrResult->get();
            $rrResult->free();
        }
        if ($rr == null) {
            $this->_response_writer->dnsNotFound("record '{$this->_request->getRecord()}' of type '{$this->_request->getRecordType()}' in zone '{$this->_request->getZone()}'");
            exit;
        }

        // check if update is required
        if ($rr['data'] == $this->_request->getData()) {
            $this->_response_writer->noUpdateRequired($this->_request->getData());
            exit;
        }

        //* Update the RR record
        $rr_update = array(
            "data" => $this->_request->getData(),
            "serial" => $app->validate_dns->increase_serial($rr["serial"]),
            "stamp" => date('Y-m-d H:i:s')
        );
        $app->db->datalogUpdate('dns_rr', $rr_update, 'id', $rr['id']);

        //* Update the serial number of the SOA record
        $soa_update = array(
            "serial" => $app->validate_dns->increase_serial($soa["serial"])
        );
        $app->db->datalogUpdate('dns_soa', $soa_update, 'id', $soa['id']);

        // cron runs every full minute, calculate seconds left
        $cron_eta = 60 - date('s');
        $this->_response_writer->successfulUpdate($this->_request, $rr['ttl'], $cron_eta);
    }
}
