<?php

abstract class DdnsRequest
{

    /** @var string $_zone */
    protected $_zone;
    /** @var string $_record */
    protected $_record;
    /** @var string $_record_type */
    protected $_record_type;
    /** @var string $_data */
    protected $_data;
    /** @var string $_action */
    protected $_action; // 'add', 'update' or 'delete'

    public function setZone($zone): void
    {
        $this->_zone = $zone;
    }

    public function getZone(): ?string
    {
        return $this->_zone;
    }

    public function setRecord($record): void
    {
        $this->_record = $record;
    }

    public function getRecord(): ?string
    {
        return $this->_record;
    }

    public function setRecordType($record_type): void
    {
        $this->_record_type = $record_type;
    }

    public function getRecordType(): ?string
    {
        return $this->_record_type;
    }

    public function setData($data): void
    {
        $this->_data = $data;
    }

    public function getData(): ?string
    {
        return $this->_data;
    }

    public function setAction($action): void
    {
        // ignore invalid actions...
        if (in_array($action, array('add', 'delete', 'update'), true)) {
            $this->_action = $action;
        }
    }

    public function getAction(): ?string
    {
        return $this->_action;
    }

    abstract public function autoSetMissingInput(DdnsToken $token, string $remote_ip): void;

    // match zone and record from a hostname
    protected function match_from_hostname(string $hostname, DdnsToken $token): void
    {
        $hostname = rtrim($hostname, '.');

        // match hostname with allowed dns zones
        $matching_zones = [];
        foreach ($token->getAllowedZones() as $allowed_zone) {
            if(strpos($hostname, rtrim($allowed_zone, '.')) !== false) {
                $matching_zones[] = $allowed_zone;
            }
        }
        if (empty($matching_zones)) {
            return;
        } else if (sizeof($matching_zones) === 1) {
            $this->setZone($matching_zones[0]);
        } else {
            $closest_match = '';
            foreach ($matching_zones as $matching_zone) {
                if(sizeof($matching_zone) > sizeof($closest_match)) {
                    $closest_match = $matching_zone;
                }
            }
            $this->setZone($closest_match);
        }

        // match records with allowed records
        $zone_length = strlen(rtrim($this->getZone(), '.'));
        if ($zone_length < strlen($hostname)) {
            $record = substr($hostname, 0, - $zone_length - 1);
            $this->setRecord($record);
        } else if (count($token->getLimitRecords()) == 0) {
            $this->setRecord('');
        }
    }

    public function validate(DdnsToken $token, DdnsResponseWriter $response_writer, app $app): void
    {
        // check if requested zone is allowed (allowed_zones must be set)
        if ($this->getZone() !== null && !in_array($this->getZone(), $token->getAllowedZones(), true)) {
            $response_writer->forbidden("zone {$this->getZone()}");
            exit;
        }

        // check if record restriction is set and requested zone is allowed
        if ($this->getRecord() !== null && count($token->getLimitRecords()) !== 0 && !in_array($this->getRecord(), $token->getLimitRecords(), true)) {
            $response_writer->forbidden("record {$this->getRecord()}");
            exit;
        }

        // check if requested type is allowed (allowed_record_types must be set)
        if ($this->getRecordType() !== null && !in_array($this->getRecordType(), $token->getAllowedRecordTypes(), true)) {
            $response_writer->forbidden("record type {$this->getRecordType()}");
            exit;
        }

        // check if all required data is available
        if ($this->getZone() === null || $this->getRecord() === null || $this->getRecordType() === null || $this->getData() === null) {
            $response_writer->missingInput($this);
            exit;
        }

        // validate data for given type
        if ($this->getRecordType() === 'A') {
            $ip = filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            if (!$ip) {
                $response_writer->invalidIpAddress($this->getData());
                exit;
            }
            // write back filtered ip
            $this->setData($ip);
        } else if ($this->getRecordType() === 'AAAA') {
            $ip = filter_var($this->getData(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
            if (!$ip) {
                $response_writer->invalidIpAddress($this->getData());
                exit;
            }
            // write back filtered ip
            $this->setData($ip);
        } else if ($this->getRecordType() === 'TXT') {
            // IDNTOASCII and TOLOWER transformations for record name
            $record = $app->functions->idn_encode($this->getRecord());
            $record = strtolower($record);
            $this->setRecord($record);

            // validation for data
            if ($this->getData() === '') {
                $response_writer->missingInput($this);
                exit;
            } else if (strlen($this->getData()) > 255) {
                $response_writer->invalidData("maximum 255 characters");
                exit;
            }

            if ($this->getAction() === 'update') {
                $response_writer->forbidden("TXT update");
            }
        } else {
            $response_writer->forbidden('record type ' . $this->getRecordType());
        }
    }
}
