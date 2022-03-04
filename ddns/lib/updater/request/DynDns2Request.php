<?php
require_once(dirname(__FILE__) . '/DdnsRequest.php');

class DynDns2Request extends DdnsRequest
{
    function __construct()
    {
        $this->setToken($this->getTokenFromRequest());
        // TODO: parse correct data
        $this->setZone($_GET['zone']);
        $this->setRecord($_GET['record']);
        $this->setRecordType($_GET['type']);
        $this->setData($_GET['data']);
    }

    protected function getTokenFromRequest(): ?string
    {
        // only hex characters allowed in token
        return preg_replace("/[^0-9^a-f]/", "", $_SERVER['PHP_AUTH_PW']);
    }

    public function autoSetMissingInput(DdnsToken $token): void
    {
        // all parameters must be set implicitly
    }
}
