<?php
/** @var app $app */

// Name of the list
$liste['name'] = 'ddns_token';

// Database table
$liste['table'] = 'ddns_token';

// Index index field of the database table
$liste['table_idx'] = 'id';

// Search Field Prefix
$liste['search_prefix'] = 'search_';

// Records per page
$liste['records_per_page'] = 15;

// Script File of the list
$liste['file'] = 'ddns_token_list.php';

// Script file of the edit form
$liste['edit_file'] = 'ddns_token_edit.php';

// Script File of the delete script
$liste['delete_file'] = 'ddns_token_del.php';

// Paging Template
// TODO: paging does not work... maybe because the page is rendered under the DNS module
$liste['paging_tpl'] = 'templates/paging.tpl.htm';

// Enable auth
$liste['auth'] = 'yes';

//****** Search fields

$liste["item"][] = array(
    'field' => "active",
    'datatype' => "VARCHAR",
    'formtype' => "SELECT",
    'op' => "=",
    'prefix' => "",
    'suffix' => "",
    'width' => "",
    'value' => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt'))
);
if($_SESSION['s']['user']['typ'] == 'admin') {
    $liste["item"][] = array(
        'field'  => "sys_groupid",
        'datatype' => "INTEGER",
        'formtype' => "SELECT",
        'op'  => "=",
        'prefix' => "",
        'suffix' => "",
        'datasource' => array (  'type' => 'SQL',
            'querystring' => "SELECT sys_group.groupid,CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), IF(client.contact_firstname != '', CONCAT(client.contact_firstname, ' '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as name FROM sys_group, client WHERE sys_group.groupid != 1 AND sys_group.client_id = client.client_id ORDER BY client.company_name, client.contact_name",
            'keyfield'=> 'groupid',
            'valuefield'=> 'name'
        ),
        'width'  => "",
        'value'  => ""
    );
}

$liste["item"][] = array(
    'field' => "token",
    'datatype' => "VARCHAR",
    'filters' => array(
        0 => array('event' => 'SHOW', 'type' => 'IDNTOUTF8')
    ),
    'formtype' => "TEXT",
    'op' => "like",
    'prefix' => "%",
    'suffix' => "%",
    'width' => "",
    'value' => ""
);

$liste['item'][] = array(
    'field' => 'allowed_zones',
    'datatype' => "VARCHAR",
    'filters' => array(
        0 => array('event' => 'SHOW', 'type' => 'IDNTOUTF8')
    ),
    'formtype' => "TEXT",
    'op' => "like",
    'prefix' => "%",
    'suffix' => "%",
    'width' => "",
    'value' => ""
);

$liste["item"][] = array(
    'field' => "allowed_record_types",
    'datatype' => "VARCHAR",
    'formtype' => "TEXT",
    'op' => "like",
    'prefix' => "%",
    'suffix' => "%",
    'width' => "",
    'value' => ""
);

$liste["item"][] = array(
    'field' => "limit_records",
    'datatype' => "VARCHAR",
    'filters' => array(
        0 => array('event' => 'SHOW', 'type' => 'IDNTOUTF8')
    ),
    'formtype' => "TEXT",
    'op' => "like",
    'prefix' => "%",
    'suffix' => "%",
    'width' => "",
    'value' => ""
);

?>
