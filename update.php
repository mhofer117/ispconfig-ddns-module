<?php
require_once(dirname(__FILE__) . '/../../lib/config.inc.php');
require_once(dirname(__FILE__) . '/../../lib/app.inc.php');
require_once(dirname(__FILE__) . '/lib/updater/DdnsUpdater.php');

$default_config = include dirname(__FILE__) . '/update.config.php';
if (file_exists(dirname(__FILE__) . '/update.config.local.php')) {
    $config_local = include dirname(__FILE__) . '/update.config.local.php';
    $config = array_merge($default_config, $config_local);
} else {
    $config = $default_config;
}

/** @var app $app */
$ddns_updater = new DdnsUpdater($app, $config);
$ddns_updater->process();
