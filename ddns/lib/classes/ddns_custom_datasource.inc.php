<?php

class ddns_custom_datasource {

    function dns_zones($field, $record) {
        global $app, $conf;
        $zones = $app->db->queryAllRecords("SELECT id,origin FROM dns_soa WHERE ".$app->tform->getAuthSQL('r')." ORDER BY origin");
        $zones_new = array();
        foreach($zones as $zone) {
            $zones_new[$zone['origin']] = $zone['origin'];
        }
        return $zones_new;
    }

}

?>
