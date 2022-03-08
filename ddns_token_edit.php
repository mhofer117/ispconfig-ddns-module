<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/** @var app $app */
/** @var auth $auth */
$auth = $app->auth;

/******************************************
 * Begin Form configuration
 ******************************************/

$tform_def_file = 'form/ddns_token.tform.php';

/******************************************
 * End Form configuration
 ******************************************/

//* Check permissions for module
$auth->check_module_permissions('dns');

$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

// Create a class page_action that extends the tform_actions base class
class page_action extends tform_actions {

    function onBeforeInsert()
    {
        global $app, $conf;

        if($this->id <= 0) {
            try {
                // generate 48 character hex string (192 bits of entropy)
                $this->dataRecord['token'] = bin2hex(random_bytes(24));
            } catch (Exception $e) {
                $app->tform->errorMessage = "Unable to generate random token: " . $e->getMessage();
            }
        }

        parent::onBeforeInsert();
    }

	function onShowEnd() {
        global $app, $conf;
        // If user is admin, we will allow him to select to whom this record belongs
        if($_SESSION["s"]["user"]["typ"] == 'admin') {
            // Getting all users
            $sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";
            $clients = $app->db->queryAllRecords($sql);
            $clients = $app->functions->htmlentities($clients);
            $client_select = '';
            if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
            if(is_array($clients)) {
                foreach( $clients as $client) {
                    $selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
                    $client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
                }
            }
            $app->tpl->setVar("client_group_id", $client_select);
        } else if($app->auth->has_clients($_SESSION['s']['user']['userid'])) {

            // Get the limits of the client
            $client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
            $client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
            $client = $app->functions->htmlentities($client);

            // Fill the client select field
            $sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
            $clients = $app->db->queryAllRecords($sql, $client['client_id']);
            $clients = $app->functions->htmlentities($clients);
            $tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client['client_id']);
            $client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
            //$tmp_data_record = $app->tform->getDataRecord($this->id);
            if(is_array($clients)) {
                foreach( $clients as $client) {
                    $selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
                    $client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
                }
            }
            $app->tpl->setVar("client_group_id", $client_select);

        }
        parent::onShowEnd();
    }

    function onSubmit() {
        global $app;

        if($_SESSION['s']['user']['typ'] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);

        parent::onSubmit();
    }

    function onAfterInsert() {
        global $app, $conf;

        if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
            $client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
            $app->db->query("UPDATE ddns_token SET sys_groupid = ?, sys_perm_group = 'riud' WHERE id = ?", $client_group_id, $this->id);
        }
        if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
            $client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
            $client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
            $client = $app->functions->htmlentities($client);
            $sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
            $clients = $app->db->queryAllRecords($sql, $client['client_id']);
            $clients = $app->functions->htmlentities($clients);
            $valid_group_ids = array();
            if(is_array($clients)) {
                foreach( $clients as $client) {
                    array_push($valid_group_ids, $client['groupid']);
                }
            }
            if (array_search($this->dataRecord["client_group_id"], $valid_group_ids)) {
                $set_client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
                $app->db->query("UPDATE ddns_token SET sys_groupid = ?, sys_perm_group = 'riud' WHERE id = ?", $set_client_group_id, $this->id);
            }
        }
    }

    function onAfterUpdate() {
        global $app, $conf;

        if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
            $client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
            $app->db->query("UPDATE ddns_token SET sys_groupid = ?, sys_perm_group = 'riud' WHERE id = ?", $client_group_id, $this->id);
        }
        if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
            $client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
            $client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
            $client = $app->functions->htmlentities($client);
            $sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
            $clients = $app->db->queryAllRecords($sql, $client['client_id']);
            $clients = $app->functions->htmlentities($clients);
            $valid_group_ids = array();
            if(is_array($clients)) {
                foreach( $clients as $client) {
                    array_push($valid_group_ids, $client['groupid']);
                }
            }
            if (array_search($this->dataRecord["client_group_id"], $valid_group_ids)) {
                $set_client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
                $app->db->query("UPDATE ddns_token SET sys_groupid = ?, sys_perm_group = 'riud' WHERE id = ?", $set_client_group_id, $this->id);
            }
        }
    }

}

$page = new page_action();
$page->onLoad();

?>
