<?php
/** @var app $app */
/** @var auth $auth */
$auth = $app->auth;
//****  Menu Definition ****

// Make sure that the items array is empty

$items = array();
$items[] = array(
    'title' => "Tokens",
    'target' => 'content',
    'link' => 'ddns/ddns_token_list.php',
    'html_id' => 'dns_wizard');
$module['nav'][] = array(
    'title' => 'Dynamic DNS',
    'open' => 1,
    'items' => $items
);

?>

