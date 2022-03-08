<?php
/** @var app $app */

// Title of the form.
$form['title'] = 'dynamic_dns_tokens_title_txt';

// Optional description of the form.
$form['description'] = '';

// Name of the form which cannot contain spaces or foreign characters.
$form['name'] = 'ddns_token';

// The file that is used to call the form in the browser.
$form['action'] = 'ddns_token_edit.php';

// The name of the database table used to store the data
$form['db_table'] = 'ddns_token';

// The name of the database table index field.
// This field must be a numeric auto increment column.
$form['db_table_idx'] = 'id';

// Should changes to this table be stored in the database history (sys_datalog) table.
// This should be set to 'yes' for all tables that store configuration information.
$form['db_history'] = 'yes';

// The name of the tab that is shown when the form is opened
$form['tab_default'] = 'token';

// The name of the default list file of this form
$form['list_default'] = 'ddns_token_list.php';

// Use the internal authentication system for this table. This should
// be set to 'yes' in most cases, otherwise 'no'.
$form['auth'] = 'yes';

//** Authentication presets. The defaults below does not need to be changed in most cases.

// 0 = id of the user, > 0 id must match with id of current user
$form['auth_preset']['userid'] = 0;

// 0 = default groupid of the user, > 0 id must match with groupid of current
$form['auth_preset']['groupid'] = 0;

// Permissions with the following codes: r = read, i = insert, u = update, d = delete
$form['auth_preset']['perm_user'] = 'riud';
$form['auth_preset']['perm_group'] = 'riud';
$form['auth_preset']['perm_other'] = '';

// The form definition of the first tab. The name of the tab is called 'message'. We refer
// to this name in the $form['tab_default'] setting above.
$form['tabs']['token'] = array(
    'title' => 'token_txt', // Title of the Tab
    'width' => 150,       // Tab width
    'template' => 'templates/ddns_token_edit.htm', // Template file name
    'fields' => array(

        //*** BEGIN Datatable columns **********************************

        'token' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => '',
            'value' => '',
            'validators' => array(
                0 => array(
                    'type' => 'REGEX',
                    'regex' => '/^[0-9a-f]{48}$/',
                    'errmsg' => 'token_error_regex'
                ),
            ),
            'width' => '48',
            'maxlength' => '48'
        ),
        'allowed_zones' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'MULTIPLE',
            'separator' => ',',
            'default' => '',
            'validators' => array(
                0 => array(
                    'type' => 'NOTEMPTY',
                    'errmsg' => 'allowed_zones_notempty_txt'
                )
            ),
            'datasource' => array(
                'type' => 'CUSTOM',
                'class' => 'ddns_custom_datasource',
                'function' => 'dns_zones'
            ),
            'value' => '',
            'name' => 'zones',
            'maxlength' => '500'
        ),
        'allowed_record_types' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOXARRAY',
            'separator' => ',',
            'default' => 'A,AAAA',
            'validators' => array(
                0 => array(
                    'type' => 'NOTEMPTY',
                    'errmsg' => 'allowed_record_types_notempty_txt'
                )
            ),
            'value' => array('A' => 'A (IPv4)', 'AAAA' => 'AAAA (IPv6)'),
            'name' => 'record_types',
            'maxlength' => '255'
        ),
        'limit_records' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'validators' => array(
                0 => array(
                    'type' => 'REGEX',
                    'regex' => '/^([a-zA-Z0-9\.\-\*],?)*$/',
                    'errmsg' => 'limit_records_error_regex_txt'
                )
            ),
            'filters' => array(
                0 => array('event' => 'SAVE', 'type' => 'IDNTOASCII'),
                1 => array('event' => 'SHOW', 'type' => 'IDNTOUTF8'),
                2 => array('event' => 'SAVE', 'type' => 'TOLOWER')
            ),
            'default' => '',
            'value' => '',
            'width' => '30',
            'maxlength' => '255'
        ),
        'active' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'Y',
            'value' => array(0 => 'N', 1 => 'Y')
        ),

        //*** END Datatable columns **********************************
    )
);
?>
