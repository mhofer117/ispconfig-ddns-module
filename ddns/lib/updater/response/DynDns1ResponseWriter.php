<?php
require_once(dirname(__FILE__) . '/DdnsResponseWriter.php');

class DynDns1ResponseWriter implements DdnsResponseWriter
{
    /** @var app $_ispconfig */
    protected $_ispconfig;

    public function __construct(app $ispconfig)
    {
        $this->_ispconfig = $ispconfig;
    }

    private function dynDns1Error(string $message) {
        echo "<TITLE>$message</TITLE>\n";
        echo "return code: ERROR\n";
        echo "error code: ERROR\n";
        exit;
    }

    private function dynDns1Success(string $message) {
        echo "<TITLE>$message</TITLE>\n";
        echo "return code: NOERROR\n";
        echo "error code: NOERROR\n";
        exit;
    }

    public function invalidOrMissingToken(): void
    {
        $this->dynDns1Error("Missing or invalid token");
    }

    public function maintenance(): void
    {
        $this->dynDns1Error("This ISPConfig installation is currently under maintenance. We should be back shortly. Thank you for your patience.");
    }

    public function tooManyLoginAttempts(): void
    {
        $this->dynDns1Error($this->_ispconfig->lng('error_user_too_many_logins'));
    }

    public function forbidden(string $entity): void
    {
        $this->dynDns1Error("Permission denied for $entity");
    }

    public function missingInput(DdnsRequest $request): void
    {
        $this->dynDns1Error("Missing input data, zone={$request->getZone()}, record={$request->getRecord()}, type={$request->getRecordType()}, data={$request->getData()}");
    }

    public function invalidIpAddress($ip): void
    {
        $this->dynDns1Error("Invalid IP address: $ip\n");
    }

    public function dnsNotFound(string $dns): void
    {
        $this->dynDns1Error("Could not find $dns");
    }

    public function internalError(string $message): void
    {
        $this->dynDns1Error($message);
    }

    public function noUpdateRequired(string $dnsData): void
    {
        $this->dynDns1Success("$dnsData is already set");
    }

    public function successfulUpdate(DdnsRequest $request, $record_ttl, $cron_eta): void
    {
        $this->dynDns1Success("Scheduled update. Schedule runs in $cron_eta seconds. Record TTL: $record_ttl");
    }
}
