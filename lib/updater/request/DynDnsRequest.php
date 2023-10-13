<?php
require_once(dirname(__FILE__) . '/DdnsRequest.php');

class DynDnsRequest extends DdnsRequest
{
    /** @var string $_hostname */
    protected $_hostname;

    function __construct($hostname)
    {
        $this->_hostname = $hostname;
        $this->setData($_GET['myip']);
        // action is always update (for DynDNS requests)
        $this->setAction('update');
        // zone, record and type cannot be determined from http request only
    }

    public function autoSetMissingInput(DdnsToken $token, string $remote_ip): void
    {
        if ($this->_hostname !== null) {
            parent::match_from_hostname($this->_hostname, $token);
        }

        if ($this->getData() === null) {
            $this->setData($remote_ip);
        }

        // auto-set type
        if ($this->getData() !== null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->setRecordType('A');
        } else if ($this->getData() !== null && filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->setRecordType('AAAA');
        }
    }

    public function validate(DdnsToken $token, DdnsResponseWriter $response_writer, app $app): void
    {
        if ($this->_hostname === null) {
            $response_writer->missingInput($this);
            exit;
        }
        // check if all required data is available
        if ($this->getZone() === null || $this->getRecord() === null) {
            $response_writer->dnsNotFound($this->_hostname);
            exit;
        } else if ($this->getRecordType() !== 'A' && $this->getRecordType() !== 'AAAA') {
            $response_writer->invalidIpAddress($this->getData());
        }
        parent::validate($token, $response_writer, $app);
    }
}
