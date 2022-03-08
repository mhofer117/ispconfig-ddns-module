<?php

class DdnsToken
{
    /** @var app $_ispconfig */
    protected $_ispconfig;
    /** @var string[] $_allowed_zones */
    protected $_allowed_zones;
    /** @var string[] $_allowed_record_types */
    protected $_allowed_record_types;
    /** @var string[] $_limit_records */
    protected $_limit_records;

    function __construct(app $ispconfig, ?string $requestToken, DdnsResponseWriter $response_writer)
    {
        $this->_ispconfig = $ispconfig;
        if ($requestToken == null) {
            $response_writer->invalidOrMissingToken();
            exit;
        }

        //* Check if there are already wrong logins
        $request_ip = md5($_SERVER['REMOTE_ADDR']);
        $sql = "SELECT * FROM `attempts_login` WHERE `ip`= ? AND  `login_time` > (NOW() - INTERVAL 1 MINUTE) LIMIT 1";
        $alreadyfailed = $this->_ispconfig->db->queryOneRecord($sql, $request_ip);
        if ($alreadyfailed['times'] > 5) {
            $response_writer->tooManyLoginAttempts();
            exit;
        }

        $token = $this->_ispconfig->db->queryOneRecord("SELECT * FROM ddns_token WHERE active = 'Y' AND token=?", $requestToken);
        if ($token == null) {
            if (!$alreadyfailed['times']) {
                //* user login the first time wrong
                $sql = "INSERT INTO `attempts_login` (`ip`, `times`, `login_time`) VALUES (?, 1, NOW())";
                $this->_ispconfig->db->query($sql, $request_ip);
            } elseif ($alreadyfailed['times'] >= 1) {
                //* update times wrong
                $sql = "UPDATE `attempts_login` SET `times`=`times`+1, `login_time`=NOW() WHERE `ip` = ? AND `login_time` < NOW() ORDER BY `login_time` DESC LIMIT 1";
                $this->_ispconfig->db->query($sql, $request_ip);
            }

            $response_writer->invalidOrMissingToken();
            exit;
        } else {
            // User login right, so attempts can be deleted
            $sql = "DELETE FROM `attempts_login` WHERE `ip`=?";
            $this->_ispconfig->db->query($sql, $request_ip);
        }
        $this->_allowed_zones = array_filter(explode(',', $token['allowed_zones']));
        $this->_allowed_record_types = array_filter(explode(',', $token['allowed_record_types']));
        $this->_limit_records = array_filter(explode(',', $token['limit_records']));
    }

    public function getAllowedZones(): array
    {
        return $this->_allowed_zones;
    }


    public function getAllowedRecordTypes(): array
    {
        return $this->_allowed_record_types;
    }

    public function getLimitRecords(): array
    {
        return $this->_limit_records;
    }
}
