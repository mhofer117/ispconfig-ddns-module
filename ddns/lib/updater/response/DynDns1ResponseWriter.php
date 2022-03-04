<?php
require_once(dirname(__FILE__) . '/DdnsResponseWriter.php');

class DynDns1ResponseWriter implements DdnsResponseWriter
{

    public function invalidOrMissingToken(): void
    {
        echo "badauth";
        exit;
    }

    public function maintenance(): void
    {
        // TODO: Implement maintenance() method.
    }

    public function tooManyLoginAttempts(): void
    {
        // TODO: Implement tooManyLoginAttempts() method.
    }

    public function forbidden(string $entity): void
    {
        // TODO: Implement forbidden() method.
    }

    public function missingInput(DdnsRequest $request): void
    {
        // TODO: Implement missingInput() method.
    }

    public function invalidIpAddress(string $ip): void
    {
        // TODO: Implement invalidIpAddress() method.
    }

    public function dnsNotFound(string $dns): void
    {
        // TODO: Implement dnsNotFound() method.
    }

    public function internalError(string $message): void
    {
        // TODO: Implement internalError() method.
    }

    public function noUpdateRequired(string $dnsData): void
    {
        // TODO: Implement noUpdateRequired() method.
    }

    public function successfulUpdate(DdnsRequest $request, $record_ttl, $cron_eta): void
    {
        // TODO: Implement successfulUpdate() method.
    }
}
