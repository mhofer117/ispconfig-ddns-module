<?php
require_once(dirname(__FILE__) . '/DdnsRequest.php');

class DefaultDdnsRequest extends DdnsRequest
{
    function __construct()
    {
        $this->setToken($this->getTokenFromRequest());
        $this->setZone($_GET['zone']);
        $this->setRecord($_GET['record']);
        $this->setRecordType($_GET['type']);
        $this->setData($_GET['data']);
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

    public function autoSetMissingInput(DdnsToken $token): void
    {
        // auto-set zone if possible
        if ($this->getZone() == null && count($token->getAllowedZones()) == 1) {
            $this->setZone($token->getAllowedZones()[0]);
        }
        // auto-set record if possible
        if ($this->getRecord() == null && count($token->getLimitRecords()) == 1) {
            $this->setRecord($token->getLimitRecords()[0]);
        }

        // auto-set data if possible
        if ($this->getData() == null && ($this->getRecordType() == null || $this->getRecordType() == 'A' || $this->getRecordType() == 'AAAA')) {
            $this->setData($_SERVER['REMOTE_ADDR']);
        }

        // auto-set type if possible
        if ($this->getRecordType() == null && $this->getData() != null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->setRecordType('A');
        } else if ($this->getRecordType() == null && $this->getData() != null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->setRecordType('AAAA');
        }
    }
}
