<?php

interface DdnsResponseWriter
{
    public function invalidOrMissingToken(): void;

    public function maintenance(): void;

    public function tooManyLoginAttempts(): void;

    public function forbidden(string $entity): void;

    public function missingInput(DdnsRequest $request): void;

    public function invalidIpAddress(?string $ip): void;

    public function invalidData(string $reason): void;

    public function dnsNotFound(string $dns): void;

    public function internalError(string $message): void;

    public function noUpdateRequired(string $dnsData): void;

    public function successfulUpdate(string $data, int $record_ttl, int $cron_eta): void;
}
