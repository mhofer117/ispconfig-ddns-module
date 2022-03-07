<?php
require_once(dirname(__FILE__) . '/../../lib/config.inc.php');
require_once(dirname(__FILE__) . '/../../lib/app.inc.php');
require_once(dirname(__FILE__) . '/lib/updater/DdnsUpdater.php');

/** @var app $app */
$ddns_updater = new DdnsUpdater($app);
$ddns_updater->process();
