<?php
require_once(dirname(__FILE__) . '/DdnsResponseWriter.php');

class DefaultDdnsResponseWriter implements DdnsResponseWriter
{

    public function invalidOrMissingToken(): void
    {
        header("HTTP/1.1 401 Unauthorized");
        echo "Missing or invalid token";
        exit;
    }

    public function maintenance(): void
    {
        header("HTTP/1.1 500 Internal Server Error");
        echo "This ISPConfig installation is currently under maintenance. We should be back shortly. Thank you for your patience.\n";
        exit;
    }

    public function tooManyLoginAttempts(): void
    {
        header("HTTP/1.1 429 Too Many Requests");
        /** @var app $app */
        echo $app->lng('error_user_too_many_logins');
        exit;
    }

    public function forbidden(string $entity): void
    {
        header("HTTP/1.1 403 Forbidden");
        echo "Permission denied for $entity";
        exit;
    }

    public function missingInput(DdnsRequest $request): void
    {
        header("HTTP/1.1 400 Bad Request");
        echo "Missing input data, zone={$request->getZone()}, record={$request->getRecord()}, type={$request->getRecordType()}, data={$request->getData()}";
        exit;
    }

    public function invalidIpAddress($ip): void
    {
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid IP address: $ip\n";
        exit;
    }

    public function dnsNotFound(string $dns): void
    {
        header("HTTP/1.1 404 Not Found");
        echo "Could not find $dns";
        exit;
    }

    public function internalError(string $message): void
    {
        header("HTTP/1.1 500 Internal Server Error");
        echo "$message";
        exit;
    }

    public function noUpdateRequired(string $dnsData): void
    {
        // return normal 200, no http error code
        echo "ERROR: $dnsData is already set";
        exit;
    }

    public function successfulUpdate(DdnsRequest $request, $record_ttl, $cron_eta): void
    {
        echo "Scheduled update of zone={$request->getZone()}, record={$request->getRecord()}, type={$request->getRecordType()}, data={$request->getData()}, TTL: $record_ttl\n";
        echo "Schedule runs in $cron_eta seconds";
        exit;
    }

}
