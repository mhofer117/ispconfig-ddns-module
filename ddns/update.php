<?php
require_once(dirname(__FILE__).'/../../lib/config.inc.php');
require_once(dirname(__FILE__).'/../../lib/app.inc.php');

/** @var app $app */

// Maintenance mode
if ($app->is_under_maintenance()) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "This ISPConfig installation is currently under maintenance. We should be back shortly. Thank you for your patience.\n";
    exit;
}

if (!isset($_GET['token'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo "Missing token";
    exit;
}

//* Check if there are already wrong logins
$request_ip = md5($_SERVER['REMOTE_ADDR']);
$sql = "SELECT * FROM `attempts_login` WHERE `ip`= ? AND  `login_time` > (NOW() - INTERVAL 1 MINUTE) LIMIT 1";
$alreadyfailed = $app->db->queryOneRecord($sql, $request_ip);
if ($alreadyfailed['times'] > 5) {
    header("HTTP/1.1 429 Too Many Requests");
    echo $app->lng('error_user_too_many_logins');
    exit;
}

// only hex characters allowed in token
$search_token = preg_replace("/[^0-9^a-f]/", "", $_GET['token']);
$zone = $_GET['zone'];
$record = $_GET['record'];
$type = $_GET['type'];
$data = $_GET['data'];

$token = $app->db->queryOneRecord("SELECT * FROM ddns_token WHERE active = 'Y' AND token=?", $search_token);
if ($token == null) {
    if (!$alreadyfailed['times']) {
        //* user login the first time wrong
        $sql = "INSERT INTO `attempts_login` (`ip`, `times`, `login_time`) VALUES (?, 1, NOW())";
        $app->db->query($sql, $request_ip);
    } elseif ($alreadyfailed['times'] >= 1) {
        //* update times wrong
        $sql = "UPDATE `attempts_login` SET `times`=`times`+1, `login_time`=NOW() WHERE `ip` = ? AND `login_time` < NOW() ORDER BY `login_time` DESC LIMIT 1";
        $app->db->query($sql, $request_ip);
    }

    header("HTTP/1.1 401 Unauthorized");
    echo "Invalid token";
    exit;
} else {
    // User login right, so attempts can be deleted
    $sql = "DELETE FROM `attempts_login` WHERE `ip`=?";
    $app->db->query($sql, $request_ip);
}

$userid = $token['sys_userid'];
$allowed_zones = array_filter(explode(',', $token['allowed_zones']));
$allowed_record_types = array_filter(explode(',', $token['allowed_record_types']));
$limit_records = array_filter(explode(',', $token['limit_records']));

// auto-set zone if possible
if ($zone == null && count($allowed_zones) == 1) {
    $zone = $allowed_zones[0];
}
// auto-set record if possible
if ($record == null && count($limit_records) == 1) {
    $record = $limit_records[0];
}

// auto-set data if possible
if ($data == null && ($type == null || $type == 'A' || $type == 'AAAA')) {
    $data = $_SERVER['REMOTE_ADDR'];
}

// auto-set type if possible
if ($type == null && $data != null && filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $type = 'A';
} else if ($type == null && $data != null && filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $type = 'AAAA';
}

// check if requested zone is allowed (allowed_zones must be set)
if ($zone != null && !in_array($zone, $allowed_zones, true)) {
    header("HTTP/1.1 403 Forbidden");
    echo "Permission denied for zone $zone\n";
    exit;
}

// check if record restriction is set and requested zone is allowed
if ($record != null && count($limit_records) != 0 && !in_array($record, $limit_records, true)) {
    header("HTTP/1.1 403 Forbidden");
    echo "Permission denied for record $record\n";
    exit;
}

// check if requested type is allowed (allowed_record_types must be set)
if ($type != null && !in_array($type, $allowed_record_types, true)) {
    header("HTTP/1.1 403 Forbidden");
    echo "Permission denied for type $type\n";
    exit;
}

// check if all required data is available
if ($zone == null || $record == null || $type == null || $data == null) {
    header("HTTP/1.1 400 Bad Request");
    echo "Missing input data, zone=$zone, record=$record, type=$type, data=$data\n";
    exit;
}

// validate data for given type
if ($type == 'A') {
    $ip = filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    if (!$ip) {
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid IPv4 address: $data\n";
        exit;
    }
    $data = $ip;
} else if ($type == 'AAAA') {
    $ip = filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    if (!$ip) {
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid IPv6 address: $data\n";
        exit;
    }
    $data = $ip;
}

// try to load zone
$soa = $app->db->queryOneRecord("SELECT id,origin,serial FROM dns_soa WHERE origin=?", $zone);
if ($soa == null || $soa['id'] == null) {
    header("HTTP/1.1 404 Not Found");
    echo "Zone '$zone' not found\n";
    exit;
}

// try to load record
$rr = null;
$rrResult = $app->db->query("SELECT id,data,ttl,serial FROM dns_rr WHERE type=? AND name=? AND zone=?", $type, $record, $soa['id']);
if ($rrResult && $rrResult->rows() > 0) {
    if ($rrResult->rows() > 1) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Found more than one record to update, unable to proceed\n";
        exit;
    }
    $rr = $rrResult->get();
    $rrResult->free();
}
if ($rr == null) {
    header("HTTP/1.1 404 Not Found");
    echo "Record '$record' of type '$type' not found in zone '$zone'\n";
    exit;
}

// check if update is required
if ($rr['data'] == $data) {
    echo "ERROR: Address $data has not changed\n";
    exit;
}

//* Update the RR record
$rr_update = array(
    "data" => $data,
    "serial" => $app->validate_dns->increase_serial($rr["serial"]),
    "stamp" => date('Y-m-d H:i:s')
);
$app->db->datalogUpdate('dns_rr', $rr_update, 'id', $rr['id']);

//* Update the serial number of the SOA record
$soa_update = array(
    "serial" => $app->validate_dns->increase_serial($soa["serial"])
);
$app->db->datalogUpdate('dns_soa', $soa_update, 'id', $soa['id']);

// cron runs every full minute, calculate seconds left
$cron_eta = 60 - date('s');
echo "Scheduled update of zone: $zone, record: $record, type: $type, data: $data, TTL: ${rr['ttl']}\n";
echo "Schedule runs in $cron_eta seconds\n";

?>
