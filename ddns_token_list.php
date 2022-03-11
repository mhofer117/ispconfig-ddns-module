<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/** @var app $app */
/** @var auth $auth */
$auth = $app->auth;

/******************************************
 * Begin Form configuration
 ******************************************/

$list_def_file = 'list/ddns_token.list.php';

/******************************************
 * End Form configuration
 ******************************************/

//* Check permissions for module
$auth->check_module_permissions('dns');

$app->uses('listform_actions');

// load custom config for PROXY_HOST variable
$default_config = array('PROXY_HOST' => '');
if (file_exists(dirname(__FILE__) . '/update.config.local.php')) {
    $config_local = include dirname(__FILE__) . '/update.config.local.php';
    $config = array_merge($default_config, $config_local);
} else {
    $config = $default_config;
}
$app->tpl->setVar('PROXY_HOST', $config['PROXY_HOST']);

// $app->listform_actions->SQLExtWhere = "dns_soa.access = 'REJECT'";
// $app->listform_actions->SQLOrderBy = 'ORDER BY dns_soa.origin';

$app->listform_actions->onLoad();


?>
