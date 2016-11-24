<?php
//
// Bulk Modifications Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();
grab_request_vars();

// Check prereqs and authentication
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}

// Route the request to the porper area
$cmd = grab_request_var('cmd', false);
switch ($cmd) {

    case 'getcontacts':
        print get_ajax_relationship_table();
        break;

    case 'getcontactgroups':
        print get_cg_ajax_relationship_table();
        break;

    case 'gethostgroups':
        print get_hg_ajax_relationship_table();
        break;

    case 'getparentshosts':
        print get_ph_ajax_relationship_table();
        break;

    case 'gethostsinhostgroup':
        // NOTE: These MEMBERS are not TRUE to the DB. They are from Nagios Core.
        //       This means that you may have to apply configuration to see member objects appear in host/service groups.
        $hostgroup = grab_request_var('hostgroup', '');
        $args = array('id' => $hostgroup);
        $xml = get_xml_hostgroup_member_objects($args);
        $hosts = array();
        if ($xml->recordcount > 0) {
            foreach ($xml->hostgroup->members->host as $obj) {
                $hosts[intval($obj->attributes()->id)] = strval($obj->host_name);
            }
        }
        print json_encode($hosts);
        break;

    case 'getservicesinservicegroup':
        // NOTE: These MEMBERS are not TRUE to the DB. They are from Nagios Core.
        //       This means that you may have to apply configuration to see member objects appear in host/service groups.
        $servicegroup = grab_request_var('servicegroup', '');
        $args = array('id' => $servicegroup);
        $xml = get_xml_servicegroup_member_objects($args);
        $services = array();
        if ($xml->recordcount > 0) {
            foreach ($xml->servicegroup->members->service as $obj) {
                $services[intval($obj->attributes()->id)] = array('host_name' => strval($obj->host_name), 'service_description' => strval($obj->service_description));
            }
        }
        print json_encode($services);
        break;

    default:
        break;
}

// Gets relationship table for contacts
function get_ajax_relationship_table($opt = 'host')
{
    $contact = grab_request_var('contact', false);
    $id = grab_request_var('id', false);
    $html = '<div class="relation-tables">';

    $query = "SELECT `id`,`host_name` FROM `tbl_lnkHostToContact` LEFT JOIN `tbl_host` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'";
    $results = exec_sql_query(DB_NAGIOSQL, $query, true);

    $html .= "<div class='leftBox'>
            <h5>" . _("Hosts directly assigned to contact") . ": {$contact}</h5>
            <p class='label'>" . _("Check any relationships you wish to") . " <strong>" . _("remove") . "</strong></p>
            <table class='table table-condensed table-bordered table-striped table-auto-width table-no-margin'>
                <thead>
                <tr>
                    <th>" . _("Host") . "</th>
                    <th>" . _("Assigned as Contact") . "
                        (<a id='checkAllhost' style='float:none;' title='Check All' href='javascript:checkAllType(\"host\");'>" . _("Check All") . "</a>)
                    </th>
                </tr>
                </thead>
                <tbody>";

    if ($results->recordCount() == 0) {
        $html .= "<tr style='width:300px;'><td colspan='2'>" . _("No relationships for this contact") . "</td></tr>";
    }

    foreach ($results as $r) {
        $html .= "<tr><td>" . $r['host_name'] . "</td><td style='text-align:center;'>
        <input class='host' type='checkbox' name='hostschecked[]' value='" . $r['id'] . "' /></td></tr>";
    }

    $html .= "</tbody></table></div>";
    $html .= "<div class='rightBox'>
            <h5>" . _("Service directly assigned to contact") . ": {$contact}</h5>
            <p class='label'>" . _("Check any relationships you wish to") . " <strong>" . _("remove") . "</strong></p>
            <table class='table table-condensed table-bordered table-striped table-auto-width table-no-margin'>
                <thead>
                    <tr>
                        <th>" . _("Config Name") . "</th>
                        <th>" . _("Service Description") . "</th>
                        <th>" . _("Assigned as Contact") . "
                            (<a id='checkAllservice' style='float:none;' title='Check All' href='javascript:checkAllType(\"service\");'>" . _("Check All") . "</a>)
                        </th>
                    </tr>
                </thead><tbody>";

    //get option list
    $query = "SELECT `id`,`config_name`,`service_description` FROM `tbl_lnkServiceToContact` LEFT JOIN `tbl_service` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'";
    $results = exec_sql_query(DB_NAGIOSQL, $query, true);

    if ($results->recordCount() == 0) {
        $html .= "<tr style='width:300px;'><td colspan='3'>" . _("No relationships for this contact") . "</td></tr>";
    }
    
    // Display list
    foreach ($results as $r) {
        $html .= "<tr><td>" . $r['config_name'] . "</td><td>" . $r['service_description'] . "</td><td style='text-align:center;'>
        <input class='service' type='checkbox' name='serviceschecked[]' value='" . $r['id'] . "' /></td></tr>";
    }

    $html .= '</tbody></table>
    </div>
    <div class="clear"></div>
    </div>';

    return $html;
}

// Gets relationship table for contact groups
function get_cg_ajax_relationship_table($opt = 'host')
{
    $contactgroup = grab_request_var('contactgroup', false);
    $id = grab_request_var('id', false);

    $query = "SELECT `id`,`host_name` FROM `tbl_lnkHostToContactgroup` LEFT JOIN `tbl_host` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'";
    $results = exec_sql_query(DB_NAGIOSQL, $query, true);

    $html = "<div class='bulk_wizard'>";
    $html .= "<div class='leftBox'>
            <h5>" . _("Hosts directly assigned to contact") . ": {$contactgroup}</h5>
            <p class='label'>" . _("Check any relationships you wish to") . " <strong>" . _("remove") . "</strong></p>
            <table class='table table-condensed table-bordered table-striped table-auto-width table-no-margin'>
                <thead>
                    <tr>
                        <th>" . _("Host") . "</th>
                        <th>" . _("Assigned as Contact Group") . "
                            (<a id='checkAllhost' style='float:none;' title='Check All' href='javascript:checkAllType(\"host\");'>" . _("Check All") . "</a>)
                        </th>
                    </tr>
                </thead>
                <tbody>";

    if ($results->recordCount() == 0) {
        $html .= "<tr style='width:300px;'><td colspan='2'>" . _("No relationships for this contact group") . "</td></tr>";
    }

    foreach ($results as $r) {
        $html .= "<tr><td>" . $r['host_name'] . "</td><td style='text-align:center;'>
        <input class='host' type='checkbox' name='hostschecked[]' value='" . $r['id'] . "' /></td></tr>";
    }

    $html .= "</tbody></table></div>";
    $html .= "<div class='rightBox'>
            <h5>" . _("Service directly assigned to contact") . ": {$contactgroup}</h5>
            <p class='label'>" . _("Check any relationships you wish to") . " <strong>" . _("remove") . "</strong></p>
            <table class='table table-condensed table-bordered table-striped table-auto-width table-no-margin'>
                <thead>
                    <tr>
                        <th>" . _("Config Name") . "</th>
                        <th>" . _("Service Description") . "</th>
                        <th>" . _("Assigned as Contact") . "
                            (<a id='checkAllservice' style='float:none;' title='Check All' href='javascript:checkAllType(\"service\");'>" . _("Check All") . "</a>)
                        </th>
                    </tr>
                </thead>
                <tbody>";

    // Get option list
    $query = "SELECT `id`,`config_name`,`service_description` FROM `tbl_lnkServiceToContactgroup` LEFT JOIN `tbl_service` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'";
    $results = exec_sql_query(DB_NAGIOSQL, $query, true);

    if ($results->recordCount() == 0) {
        $html .= "<tr style='width:300px;'><td colspan='3'>" . _("No relationships for this contact group") . "</td></tr>";
    }

    // Display list
    foreach ($results as $r) {
        $html .= "<tr><td>" . $r['config_name'] . "</td><td>" . $r['service_description'] . "</td><td style='text-align:center;'>
        <input class='service' type='checkbox' name='serviceschecked[]' value='" . $r['id'] . "' /></td></tr>";
    }

    $html .= '</tbody></table>
    </div>
    <div class="clear"></div>
    </div>';

    return $html;
}

function get_hg_ajax_relationship_table()
{
    $hostgroup = grab_request_var('hostgroup', false);
    $id = grab_request_var('id', false);

    $query = "SELECT `id`,`host_name` FROM `tbl_lnkHostToHostgroup` LEFT JOIN `tbl_host` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'";
    $results = exec_sql_query(DB_NAGIOSQL, $query, true);

    $html = "<div class='bulk_wizard'>";
    $html .= "<div>
            <h5>" . _("Hosts directly assigned to Host Group") . ": {$hostgroup}</h5>
            <p class='label'>" . _("Check any relationships you wish to") . " <strong>" . _("remove") . "</strong></p>
            <table class='table table-condensed table-bordered table-striped table-auto-width table-no-margin'>
                <thead>
                    <tr>
                        <th>" . _("Host") . "</th>
                        <th>
                            " . _("Assigned as Host Group") . "
                            (<a id='checkAllhost' style='float:none;' title='Check All' href='javascript:checkAllType(\"host\");'>" . _("Check All") . "</a>)
                        </th>
                    </tr>
                </thead>
                <tbody>";

    if ($results->recordCount() == 0) {
        $html .= "<tr style='width:300px;'><td colspan='2'>" . _("No relationships for this host group") . "</td></tr>";
    }

    foreach ($results as $r) {
        $html .= "<tr><td>" . $r['host_name'] . "</td><td style='text-align:center;'><input class='host' type='checkbox' name='hostschecked[]' value='" . $r['id'] . "'></td></tr>";
    }

    $html .= "</tbody></table></div>";

    return $html;
}

function get_ph_ajax_relationship_table()
{
    $parenthost = grab_request_var('parenthost', false);
    $id = grab_request_var('id', false);

    $query = "SELECT `id`,`host_name` FROM `tbl_lnkHostToHost` LEFT JOIN `tbl_host` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'";
    $results = exec_sql_query(DB_NAGIOSQL, $query, true);

    $html = "<div class='bulk_wizard'>";
    $html .= "<div>
            <h5>" . _("Hosts directly assigned as children of the Parent Host") . ": {$parenthost}</h5>
            <p class='label'>" . _("Check any child hosts you wish to") . " <strong>" . _("remove") . "</strong></p>
            <table class='table table-condensed table-bordered table-striped table-auto-width table-no-margin'>
                <thead>
                    <tr>
                        <th>" . _("Host") . "</th>
                        <th>
                            " . _("Assigned as Child Host") . "
                            (<a id='checkAllhost' style='float:none;' title='Check All' href='javascript:checkAllType(\"host\");'>" . _("Check All") . "</a>)
                        </th>
                    </tr>
                </thead>
                <tbody>";

    if ($results->recordCount() == 0) {
        $html .= "<tr style='width:300px;'><td colspan='2'>" . _("No child hosts") . "</td></tr>";
    }

    foreach ($results as $r) {
        $html .= "<tr><td>" . $r['host_name'] . "</td><td style='text-align:center;'><input class='host' type='checkbox' name='hostschecked[]' value='" . $r['id'] . "'></td></tr>";
    }

    $html .= "</tbody></table></div>";

    return $html;
}