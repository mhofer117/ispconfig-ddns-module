<?php
require_once(dirname(__FILE__) . '/DdnsRequest.php');

class DynDns1Request extends DdnsRequest
{
    protected $_hostname;

    function __construct()
    {
        $this->setToken($this->getTokenFromRequest());
        $this->_hostname = $_GET['host_id'];
        $this->setData($_GET['myip']);
        // zone, record and type cannot be determined from http request only
    }

    protected function getTokenFromRequest(): ?string
    {
        // only hex characters allowed in token
        return preg_replace("/[^0-9^a-f]/", "", $_SERVER['PHP_AUTH_PW']);
    }

    public function autoSetMissingInput(DdnsToken $token): void
    {
        if ($this->_hostname == null) {
            return;
        }
        $this->_hostname = rtrim($this->_hostname, '.');

        // match hostname with allowed dns zones
        $matching_zones = [];
        foreach ($token->getAllowedZones() as $allowed_zone) {
            if(strpos($this->_hostname, rtrim($allowed_zone, '.')) !== false) {
                $matching_zones[] = $allowed_zone;
            }
        }
        if (empty($matching_zones)) {
            return;
        } else if (sizeof($matching_zones) == 1) {
            $this->setZone($matching_zones[0]);
        } else {
            $closest_match = '';
            foreach ($matching_zones as $matching_zone) {
                if(sizeof($matching_zone) > sizeof($closest_match)) {
                    $closest_match = $matching_zone;
                }
            }
            $this->setZone($closest_match);
        }

        // match records with allowed records
        $zone_length = strlen(rtrim($this->getZone(), '.'));
        $record = substr($this->_hostname, 0, -$zone_length);
        $this->setRecord($record);

        // auto-set type
        if ($this->getData() != null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->setRecordType('A');
        } else if ($this->getData() != null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->setRecordType('AAAA');
        }
    }

    public function validate(DdnsToken $token, DdnsResponseWriter $response_writer): void
    {
        if ($this->_hostname == null) {
            $response_writer->missingInput($this);
            exit;
        }
        // check if all required data is available
        if ($this->getZone() == null || $this->getRecord() == null) {
            $response_writer->dnsNotFound($this->_hostname);
            exit;
        } else if ($this->getRecordType() == null) {
            $response_writer->invalidIpAddress($this->getData());
        }
        parent::validate($token, $response_writer);
    }
}
