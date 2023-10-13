<?php
require_once(dirname(__FILE__) . '/DdnsRequest.php');

class DefaultDdnsRequest extends DdnsRequest
{
    function __construct()
    {
        if (isset($_GET['zone']) || isset($_POST['zone'])) {
            // make trailing dot optional in request params
            $zone = rtrim($_GET['zone'] ?? $_POST['zone'], '.') . '.';
            $this->setZone($zone);
        }
        $this->setRecord($_GET['record'] ?? $_POST['record']);
        $this->setRecordType($_GET['type'] ?? $_POST['type']);
        $this->setData($_GET['data'] ?? $_POST['data']);
        $this->setAction($_GET['action'] ?? $_POST['action']);
    }

    public function autoSetMissingInput(DdnsToken $token, string $remote_ip): void
    {
        // auto-set zone based on record if possible
        if ($this->getZone() === null && $this->getRecord() !== null && $this->getRecord() !== '') {
            parent::match_from_hostname($this->getRecord(), $token);
        }

        // auto-set zone if possible
        if ($this->getZone() === null && count($token->getAllowedZones()) === 1) {
            $this->setZone($token->getAllowedZones()[0]);
        }
        // auto-set record if possible
        if ($this->getRecord() === null && count($token->getLimitRecords()) === 1) {
            $this->setRecord($token->getLimitRecords()[0]);
        } else if ($this->getRecord() === null && count($token->getLimitRecords()) === 0) {
            $this->setRecord('');
        }

        // auto-set type if possible
        if ($this->getRecordType() === null && count($token->getAllowedRecordTypes()) === 1) {
            $this->setRecordType($token->getAllowedRecordTypes()[0]);
        }

        // auto-set data if possible
        if ($this->getData() === null && ($this->getRecordType() === null || $this->getRecordType() === 'A' || $this->getRecordType() === 'AAAA')) {
            $this->setData($remote_ip);
            // auto-set type based on IP
            if ($this->getRecordType() === null && $this->getData() !== null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $this->setRecordType('A');
            } else if ($this->getRecordType() === null && $this->getData() !== null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $this->setRecordType('AAAA');
            }
        }

        // auto-set action if provided
        if ($this->getAction() === null) {
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'DELETE':
                    $this->setAction('delete');
                    break;
                case 'POST':
                    $this->setAction('add');
                    break;
                default:
                    // GET, PUT, PATCH, ...
                    $this->setAction('update');
            }
        }
    }
}
