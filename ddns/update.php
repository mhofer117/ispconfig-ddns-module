<?php
require_once(dirname(__FILE__) . '/../../lib/config.inc.php');
require_once(dirname(__FILE__) . '/../../lib/app.inc.php');
require_once(dirname(__FILE__) . '/lib/updater/DdnsUpdater.php');

$ddns_updater = new DdnsUpdater();
$ddns_updater->process();
