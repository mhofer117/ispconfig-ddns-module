<?php
require_once(dirname(__FILE__) . '/DdnsResponseWriter.php');

class DynDns2ResponseWriter implements DdnsResponseWriter
{
    private function exit(): void {
        // current ddclient implementation does not handle chunked encoding, setting content-length fixes this
        // see: https://github.com/ddclient/ddclient/issues/499#issuecomment-1447465250
        header('Content-Length: ' . ob_get_length());
        exit;
    }

    public function invalidOrMissingToken(): void
    {
        echo "badauth";
        $this->exit();
    }

    public function maintenance(): void
    {
        echo "maintenance"; // not a documented dyndns2 return value
        $this->exit();
    }

    public function tooManyLoginAttempts(): void
    {
        echo "abuse";
        $this->exit();
    }

    public function forbidden(string $entity): void
    {
        echo "!yours";
        $this->exit();
    }

    public function missingInput(DdnsRequest $request): void
    {
        echo "notfqdn";
        $this->exit();
    }

    public function invalidIpAddress(?string $ip): void
    {
        echo "notip"; // not a documented dyndns2 return value
        $this->exit();
    }

    public function invalidData(string $reason): void
    {
        $this->invalidIpAddress($reason);
    }

    public function dnsNotFound(string $dns): void
    {
        echo "nohost";
        $this->exit();
    }

    public function internalError(string $message): void
    {
        echo "dnserr";
        $this->exit();
    }

    public function noUpdateRequired(DdnsRequest $request, string $action): void
    {
        echo "nochg";
        $this->exit();
    }

    public function successfulUpdate(DdnsRequest $request, string $action, int $record_ttl, int $cron_eta): void
    {
        echo "good {$request->getData()}";
        $this->exit();
    }
}
