<?php
require_once(dirname(__FILE__) . '/DdnsResponseWriter.php');

class DefaultDdnsResponseWriter implements DdnsResponseWriter
{
    /** @var app $_ispconfig */
    protected $_ispconfig;

    public function __construct(app $ispconfig)
    {
        $this->_ispconfig = $ispconfig;
    }

    public function invalidOrMissingToken(): void
    {
        header("HTTP/1.1 401 Unauthorized");
        echo "Missing or invalid token.\n";
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
        echo $this->_ispconfig->lng('error_user_too_many_logins')."\n";
        exit;
    }

    public function forbidden(string $entity): void
    {
        header("HTTP/1.1 403 Forbidden");
        echo "Permission denied for $entity\n";
        exit;
    }

    public function missingInput(DdnsRequest $request): void
    {
        header("HTTP/1.1 400 Bad Request");
        echo "Missing input data, zone={$request->getZone()}, record={$request->getRecord()}, type={$request->getRecordType()}, data={$request->getData()}.\n";
        exit;
    }

    public function invalidIpAddress(?string $ip): void
    {
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid IP address: $ip\n";
        exit;
    }

    public function invalidData(string $reason): void
    {
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid Data: $reason\n";
        exit;
    }

    public function dnsNotFound(string $dns): void
    {
        header("HTTP/1.1 404 Not Found");
        echo "Could not find $dns\n";
        exit;
    }

    public function internalError(string $message): void
    {
        header("HTTP/1.1 500 Internal Server Error");
        echo "$message.\n";
        exit;
    }

    public function noUpdateRequired(DdnsRequest $request, string $action): void
    {
        // return normal 200, no http error code
        if ($action === 'delete') {
            echo "ERROR: {$request->getRecord()} does not exit.\n";
        } else {
            echo "ERROR: {$request->getData()} is already set in {$request->getRecord()}.\n";
        }
        exit;
    }

    public function successfulUpdate(DdnsRequest $request, string $action, int $record_ttl, int $cron_eta): void
    {
        if ($action === 'delete') {
            echo "Scheduled delete of record {$request->getRecord()}. Schedule runs in $cron_eta seconds. Record TTL: $record_ttl.\n";
        } else {
            echo "Scheduled update to '{$request->getData()}' of record {$request->getRecord()}. Schedule runs in $cron_eta seconds. Record TTL: $record_ttl.\n";
        }
        exit;
    }

}
