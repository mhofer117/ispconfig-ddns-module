<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/** @var app $app */
/** @var auth $auth */
$auth = $app->auth;

// From and List definition files
$list_def_file = 'list/ddns_token.list.php';
$tform_def_file = 'form/ddns_token.tform.php';

//* Check permissions for module
$auth->check_module_permissions('dns');

// Load the form
$app->uses('tform_actions');
$app->tform_actions->onDelete();

?>
