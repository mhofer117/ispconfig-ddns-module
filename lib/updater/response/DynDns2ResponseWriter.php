<?php
require_once(dirname(__FILE__) . '/DdnsResponseWriter.php');

class DynDns2ResponseWriter implements DdnsResponseWriter
{

    public function invalidOrMissingToken(): void
    {
        echo "badauth";
        exit;
    }

    public function maintenance(): void
    {
        echo "maintenance"; // not a documented dyndns2 return value
        exit;
    }

    public function tooManyLoginAttempts(): void
    {
        echo "abuse";
        exit;
    }

    public function forbidden(string $entity): void
    {
        echo "!yours";
        exit;
    }

    public function missingInput(DdnsRequest $request): void
    {
        echo "notfqdn";
        exit;
    }

    public function invalidIpAddress(?string $ip): void
    {
        echo "notip"; // not a documented dyndns2 return value
        exit;
    }

    public function invalidData(string $reason): void
    {
        $this->invalidIpAddress($reason);
    }

    public function dnsNotFound(string $dns): void
    {
        echo "nohost";
        exit;
    }

    public function internalError(string $message): void
    {
        echo "dnserr";
        exit;
    }

    public function noUpdateRequired(string $dnsData): void
    {
        echo "nochg";
        exit;
    }

    public function successfulUpdate(string $data, int $record_ttl, int $cron_eta): void
    {
        echo "good $data";
        exit;
    }
}
