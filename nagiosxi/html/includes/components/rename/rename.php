<?php
//
// Renaming Tool Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
init_session(true);
grab_request_vars();
check_prereqs();

// Verify authentication
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}

$html = route_request();

do_page_start(array("page_title" => _("Renaming Tool"), "enterprise" => true), true);
?>

<script type="text/javascript">
function remove(id) {
    $('#' + id).remove();
}

function goBack(type) {
    var stage = parseInt($('#stage').val()) - 2;
    window.location = 'rename.php?stage=' + stage + '&objecttype=' + type;
}

function obj_search(type) {
    var search = $('#search').val();
    window.location = 'rename.php?stage=2&objecttype=' + type + '&search=' + search;
}

function clear_search(type) {
    window.location = 'rename.php?stage=2&objecttype=' + type;
}

$(document).ready(function () {
    $('#back').click(function () {
        var type = $('#objecttype').val();
        goBack(type);
    });

    // Bind search event to enter key
    $('#search').keyup(function (ev) {
        if (ev.which == 13 && $('#search').val() != '') {
            var type = $('#objecttype').val();
            obj_search(type);
        }
    });

});
</script>

<?php
if (get_theme() != 'xi5') {
    echo enterprise_message();
}
?>

<h1><?php echo _("Bulk Renaming Tool"); ?></h1>
<div>
    <?php echo $html; ?>
</div>

<?php
do_page_end(true); 

function route_request()
{
    $stage = grab_request_var('stage', 1);
    $type = escape_sql_param(grab_request_var('objecttype', 'host'), DB_NDOUTILS);

    switch ($stage) {
        case 2:
            $output = rename_stage_two($type);
            break;

        case 3:
            $output = rename_stage_three($type);
            break;

        case 4:
            $output = rename_stage_four($type);
            break;

        case 1:
        default:
            $output = rename_stage_one();
            break;
    }

    return $output;
}

function rename_stage_one()
{
    $output = "
    <div id='rename_stage_1'>
        <div class='info' style='margin-bottom: 10px;'>
            <p>" . _("The Renaming Tool allows for host and service names to be updated in bulk, while retaining all historical
            status information and performance data. The renaming tool updates configurations based on what is
            defined in the Core Config Manager.") . "</p>
             
            <div class='alert alert-info' style='line-height: 22px; padding: 10px; margin: 0;'>
                <i class='fa fa-info-circle fa-14 fa-fw' style='vertical-align: text-top;'></i>
                " ._("Hosts and services that are renamed will show as <strong>Pending</strong> until the first check result is received with the new name.") . "
            </div>
        </div>
        <h2>" . _("Stage 1") . "</h2>
        <div id='prompt'>" . _("What would you like to rename?") . "</div>
        <div>
            <form id='mainform' method='post' action='rename.php'>
                <div style='margin: 15px 0 25px 0;'>
                    <div class='radio'>
                        <label>
                            <input type='radio' id='rad1' value='host' checked='checked' name='objecttype'>" . _("Hosts") . "
                        </label>
                    </div>
                    <div class='radio'>
                        <label>
                            <input type='radio' id='rad1' value='service'  name='objecttype'>" . _("Services") . "
                        </label>
                    </div>
                </div>
                <button type='submit' class='btn btn-sm btn-primary' id='submit' name='submit'>" . _("Next") . " <i class='fa fa-chevron-right'></i></button>
                <input type='hidden' name='stage' id='stage' value='2'>
            </form> 
        </div>
    </div>  
    ";
    return $output;
}

function rename_stage_two($type)
{
    $ntype = $type . 's';
    $search = escape_sql_param(grab_request_var('search', ''), DB_NDOUTILS);
    $optionString = get_option_list($type, $search);
    $info = ($type == 'service') ? "<div id='listInfo'><strong>" . _("Config Name") . "</strong> :: <strong>" . _("Service Description") . "</strong></div>\n" : '';

    $hide_sc = '';
    if (empty($search)) { $hide_sc = 'hide'; }

    $output = "
    <div id='rename_stage_2'>
        <h2>" . _("Stage 2") . "</h2>
        <p>" . _("Which") . " {$ntype} " . _("would you like to rename?") . "</p>
        <div>
            <form id='mainform' method='post' action='rename.php'>
                <div style='margin-bottom: 5px; font-weight: bold;'>{$info}</div>

                <div style='margin-bottom: 5px;'>
                    <input type='text' class='form-control' name='search' id='search' placeholder='"._('Search')."...' value='{$search}'>
                    <button type='button' class='btn btn-sm btn-default' style='vertical-align: top;' id='actionSearch' onclick='javascript:obj_search(\"{$type}\");'><i class='fa fa-search'></i></button>
                    <button type='button' class='btn btn-sm btn-default {$hide_sc}' style='vertical-align: top;' id='clearSearch' onclick='javascript:clear_search(\"{$type}\");'><i class='fa fa-times'></i></button>
                </div>
                
                <select name='selected[]' id='selected' multiple='multiple' class='required form-control' style='height: 300px; min-width: 200px; width: 25%;'>
                    {$optionString}
                </select>

                <div style='margin-top: 20px;'>
                    <button type='button' class='btn btn-sm btn-default' id='back' name='back'><i class='fa fa-chevron-left'></i> " . _("Back") . "</button>
                    <button type='submit' class='btn btn-sm btn-primary' id='proceed'>" . _('Next') . " <i class='fa fa-chevron-right'></i></button>
                    <input type='hidden' name='stage' id='stage' value='3' /> 
                    <input type='hidden' name='objecttype' id='objecttype' value='{$type}' />
                </div>
            </form>
        </div>
    </div>
    ";

    return $output;
}

function rename_stage_three($type, $errors = array(0, ''), $ids = null, $rows = array())
{
    $uctype = ucfirst($type) . 's';
    if ($ids === null) {
        $ids = grab_request_var('selected', array());
    }

    // Fetch more info on the selected objects 
    $tr_data = '';
    if (!empty($ids)) {
        $tr_data = get_object_details($type, $ids, $rows);
    }

    // Check for errors if we're coming back from stage 4
    $feedback = '';
    if ($errors[0] > 0) {
        $feedback = display_message($errors[0], true, $errors[1], false);
    }

    $output = "
    <div id='rename_stage_3'>
        {$feedback}
        <h2>"._('Stage 3')."</h2>
        <div id='prompt'>" . _("Update names for the selected objects") . ".</div>
        <div id='formdiv'>
            <form id='mainform' method='post' action='rename.php'>

                <div style='margin-top: 20px;'>
                    <table class='table table-condensed table-striped table-auto-width table-bordered' id='listings_table'>
                    {$tr_data}
                    </table>
                </div>

                <div class='checkbox'>
                    <label><input type='checkbox' name='no_apply' value='1'> "._("Do not apply config at the end of the wizard")."</label>
                </div>

                <div style='margin-top: 20px;'>
                    <button type='button' class='btn btn-sm btn-default' id='back' name='back'><i class='fa fa-chevron-left'></i> " . _("Back") . "</button>
                    <button type='submit' class='btn btn-sm btn-primary' id='submit' name='submit'>" . _("Next") . " <i class='fa fa-chevron-right r'></i></button>
                    <input type='hidden' name='stage' id='stage' value='4'>
                    <input type='hidden' name='objecttype' id='objecttype' value='{$type}'>
                </div>
            </form>
        </div>
    </div>
    ";

    return $output;
}

/**
 *   Stage 4 of wizard, verifies input and returns success | fail message
 */
function rename_stage_four($type)
{
    $output = '';
    $uctype = ucfirst($type) . 's';
    $rows = grab_request_var('rows', array());
    $no_apply = grab_request_var('no_apply', 0);
    $msg = '';
    $ids = array();

    // Validate input 
    $errors = 0;
    $errmsg = '';

    if (!enterprise_features_enabled()) {
        $errors++;
        $errmsg .= _('This feature requires a Nagios XI Enterprise Edition license.') . ' <br />';
    }

    foreach ($rows as $r) {
        if ($type == 'host') {
            if (!isset($r['host_name']) || trim($r['host_name']) == '' || preg_match('/[`~!$%^&*"|\'<>?,()=\\\@]/', $r['host_name'])) {
                $errors++;
                $errmsg .= _("Missing use a valid Host Name for Host") . ": " . $r['host_name'] . "<br />";
            }
        }
        if ($type == 'service') {
            if (!isset($r['service_description']) || trim($r['service_description'] == '') || preg_match('/[`~!$%^&*"|\'<>?,()=\\\@]/', $r['service_description'])) {
                $errors++;
                $errmsg .= _("Must use a valid Service Description for Service") . ": {$r['service_description']}<br />";
            }
        }
        $ids[] = $r['id']; // Add to ids array for error handling        
    }

    // Go back if there are problems
    if ($errors > 0) {
        return rename_stage_three($type, array($errors, $errmsg), $ids, $rows);
    }

    unset($ids); // No longer needed     

    // Process input results 
    $rename_function = "process_{$type}_rename";

    foreach ($rows as $r) {
        $error = $rename_function($r, $msg);
        $errors += $error;
    }

    // Apply configuration if all is well 
    if ($errors == 0 && $no_apply == 0) {
        submit_command(COMMAND_NAGIOSCORE_APPLYCONFIG);
    }

    if ($no_apply) {
        set_option("ccm_apply_config_needed", 1);
        $applied_config = "<p>"._('The new config has not yet been applied.')." <a href='".get_base_url()."includes/components/nagioscorecfg/applyconfig.php?cmd=confirm'>"._("Apply Configuration")."</a> "._("now").".</p>";
    } else {
        $applied_config = "<p>"._('The new config is being applied in the background and on large systems can take some time.')."</p>";
    }

    // Build html for stage 4 
    $output = "
    <div id='rename_stage_4'>
        <h2>" . _("Complete") . "</h2>
        ".$applied_config."
        <div id='prompt'>" . get_message_text($errors, false, $msg) . "</div>
        <div id='formdiv' style='margin-top: 20px;'>
            <a href='rename.php?stage=1' class='btn btn-sm btn-default'>" . _("Run This This Wizard Again") . "</a>
        </div>
    </div>  
    ";

    return $output;
}

/**
 *   get html option list based on object type
 *
 * @param string $type   'host' | 'service'
 * @param string $search form input search
 *
 * @return string $output html option string
 */
function get_option_list($type, $search)
{
    $output = "";
    if ($type == 'host') {
        $opts = get_all_ccm_hosts($search);
        $name = 'host_name';
    } else {
        $opts = get_all_ccm_services($search);
        $name = 'name';
    }

    foreach ($opts as $row) {
        $output .= "<option value='{$row['id']}'>" . encode_form_val($row[$name]) . "</option>\n";
    }

    return $output;
}

/**
 *   gets more detailed info fields for selected $ids
 *
 * @param string $type 'host' | 'service'
 * @param mixed  $ids  array of selected object ids
 *
 * @return string $rows html string of table rows
 */
function get_object_details($type, $ids, $rows = array())
{
    $selected = implode(',', $ids);

    if ($type == 'host') {
        $query = "SELECT `id`,`host_name`,`alias`,`display_name`
                  FROM tbl_host WHERE `id` IN ({$selected}) ORDER BY `host_name`";
    } else {
        $query = "SELECT `id`,`config_name`,`service_description`
                  FROM tbl_service WHERE id IN ({$selected}) ORDER BY `service_description`";
    }

    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    $x = rs_to_table_row($rs, $type, $rows);

    return $x;
}

/**
 *   Builds the html set of table rows based on result set from $rs
 *
 * @param mixed  $rs   ADODB database object
 * @param string $type 'host' | 'service'
 *
 * @return string $rows html string of table rows
 */
function rs_to_table_row($rs, $type, $inv_rows = array())
{
    $rows = '';
    $count = 0;
    $img = '<img border="0" title="Close" alt="Close" src="' . get_base_url() . '/images/b_close.png">';
    if ($type == 'host') {
        $rows .= "<thead>
                    <tr>
                        <th>" . _("Host Name") . "</th>
                        <th>" . _("New Name") . "</th>
                        <th>" . _("Alias") . "</th>
                        <th>" . _("Display Name") . "</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($rs as $r) {
            $count++;

            // Check if we have the row from when someone tried to put a value in already
            $hostname = $r['host_name'];
            if (!empty($inv_rows)) {
                foreach ($inv_rows as $r2) {
                    if ($r2['id'] == $r['id']) {
                        $hostname = $r2['host_name'];
                    }
                }
            }

            $rows .= "
            <tr id='tr_{$count}'>
                <td><input type='hidden' name='rows[{$count}][id]' value='{$r['id']}'>
                    <input type='hidden' name='rows[{$count}][old_name]' value='{$r['host_name']}'>
                    {$r['host_name']}
                </td>
                <td>
                    <input type='text' class='form-control condensed' name='rows[{$count}][host_name]' value='{$hostname}'>
                </td>               
                <td>
                    <input type='text' class='form-control condensed' name='rows[{$count}][alias]' value='{$r['alias']}'>
                </td>
                <td>
                    <input type='text' class='form-control condensed' name='rows[{$count}][display_name]' value='{$r['display_name']}'>
                </td>
                <td><a class='remove tt-bind' title='"._('Remove')."' href='javascript:remove(\"tr_{$count}\")'><i class='fa fa-14 fa-trash'></i></a></td>
            </tr>\n";
        }
        $rows .= "</tbody>";
    } else {
        $rows .= "<thead>
                    <tr>
                        <th>" . _("Config Name") . "</th>
                        <th>" . _("Old Service Description") . "</th>
                        <th>" . _("New Service Description") . "</th>
                        <th>" . _("Affected Hosts") . "</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($rs as $r) {
            $count++;

            // Check if we have the row from when someone tried to put a value in already
            $service_description = $r['service_description'];
            if (!empty($inv_rows)) {
                foreach ($inv_rows as $r2) {
                    if ($r2['id'] == $r['id']) {
                        $service_description = $r2['service_description'];
                    }
                }
            }

            $rows .= "
            <tr class='{$class}' id='tr_{$count}'>
                <td>
                    <input type='hidden' name='rows[{$count}][id]' value='{$r['id']}'>
                    <input type='hidden' name='rows[{$count}][old_name]' value='{$r['service_description']}'>
                    <input type='text' class='form-control condensed' name='rows[{$count}][config_name]' value='{$r['config_name']}'>
                </td>
                <td>
                    {$r['service_description']}
                </td>
                <td>
                    <input type='text' class='form-control condensed' style='width: 360px;' name='rows[{$count}][service_description]' value='{$service_description}'>
                </td>
                <td class='td_hostlist'>" . get_service_to_host_list($r['id']) . "</td>
                <td><a class='remove tt-bind' title='"._('Remove')."' href='javascript:remove(\"tr_{$count}\")'><i class='fa fa-14 fa-trash'></i></a></td>
            </tr>\n";
        }
        $rows .= "</tbody>";
    }

    return $rows;
}

/**
 *   retrieves a full list of id->host_names from CCM DB
 *
 * @param string $search optional search string
 *
 * @return mixed $rs ADODB OBJECT type
 */
function get_all_ccm_hosts($search)
{
    $query = "SELECT `id`,`host_name` FROM tbl_host";
    if (!empty($search)) {
        $query .= " WHERE `host_name` LIKE '%{$search}%'";
    }
    $query .= " ORDER BY `host_name`";
    exec_sql_query(DB_NAGIOSQL, "SET NAMES 'utf8'");
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    return $rs;
}

/**
 *   retrieves a full list of id->host_names from CCM DB
 *
 * @param string $search optional search string
 *
 * @return mixed $services array of services with id and host::service
 */
function get_all_ccm_services($search)
{
    $query = "SELECT id,config_name,service_description FROM tbl_service";
    if (!empty($search)) {
        $query .= " WHERE `config_name` LIKE '%{$search}%' OR `service_description` LIKE '%{$search}%' ";
    }
    $query .= " ORDER BY `config_name`,`service_description`";
    exec_sql_query(DB_NAGIOSQL, "SET NAMES 'utf8'");
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    $services = array();
    foreach ($rs as $row) {
        $services[] = array(
            'name' => $row['config_name'] . ' :: ' . $row['service_description'],
            'id' => $row['id']);
    }
    return $services;
}

/**
 *   manages all subfunctions for renaming a single host, takes in input array
 *
 * @param mixed $array
 *            ["id"] => 86   //NAGIOSQL id, not ndoutils
 *            ["old_name"] => _LOC_host_1
 *            ["host_name"] => MASS_host_1
 *            ["alias"] => MASS
 *            ["display_name"] => MASS
 *
 *   $param string $msg REFERENCE variables for output message
 *
 * @return int $error error code for success or failuer ( 0 | 1 )
 */
function process_host_rename($array, &$msg)
{
    $errors = 0;

    // Rename host in three places: ndoutils, nagiosql, and perfdata
    $errors += rename_host_ndoutils($array, $msg);
    $errors += rename_host_nagiosql($array, $msg);
    $errors += rename_host_perfdata($array, $msg);

    // Rename the host in any additional areas that are necessary
    if (function_exists('objectnotes_component_init')) {
        rename_host_objectnotes($array, $msg);
    }

    if ($errors == 0) {
        $msg .= " <strong>" . $array['old_name'] . '</strong> updated to <strong>' . $array['host_name'] . "</strong> successfully!<br />";
    }

    return $errors;
}

/**
 *   manages all subfunctions for renaming a single service, takes in input array
 *
 * @param mixed $array
 *            ["id"] => 86   //NAGIOSQL id, not ndoutils
 *            ["old_name"] => <old service description>
 *            ["config_name"] => <physical config file it's stored on>
 *            ["service_description"] => <new service desc>
 *            $param string $msg REFERENCE variables for output message
 *
 * @return int $error error code for success or failuer ( 0 | 1 )
 */
function process_service_rename($array, &$msg)
{
    $errors = 0;

    // Rename services in three different places: ndoutils, nagiosql, and perfdata
    $errors += rename_service_ndoutils($array, $msg, $hosts);
    $array['hosts'] = $hosts; // Push related hosts into array
    $errors += rename_service_nagiosql($array, $msg);
    $errors += rename_service_perfdata($array, $msg);

    // Rename the service in any additional areas that are necessary
    if (function_exists('objectnotes_component_init')) {
        rename_service_objectnotes($array, $msg);
    }

    if ($errors == 0) {
        $msg .= "Service <strong>" . $array['old_name'] . "</strong> updated to <strong>" . $array['service_description'] . "</strong> for the following hosts: <br />" . implode("<br />", $array['hosts']) . "<br />";
    }

    return $errors;
}

/**
 *   Updates objectnotes to save the notes for the current host
 *
 * @param mixed  $host_array host information returned from form
 * @param string $msg        REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_host_objectnotes($host_array, &$msg)
{
    $errors = 0;
    $host_name = $host_array['host_name'];
    $old_host_name = $host_array['old_name'];

    $query = "UPDATE xi_options SET name = 'objectnotes_" . objectnotes_component_transform_object_name($host_name) . "' WHERE name = 'objectnotes_" . objectnotes_component_transform_object_name($old_host_name) . "'";
    $rs = exec_sql_query(DB_NAGIOSXI, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating service object notes for {$old_service} to {$service}.<br />";
        $error = 1;
    }

    return $errors;
}

/**
 *   Updates name fields in ndoutils
 *
 * @param mixed  $host_array host information returned from form
 * @param string $msg        REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_host_ndoutils($host_array, &$msg)
{
    $errors = 0;
    $host_name = $host_array['host_name'];
    $old_name = $host_array['old_name'];

    // Needs to rename name1 for host entry but also all service entries with name1=host_name
    // abstract this a bit more
    $query = "UPDATE nagios_objects SET `name1`='{$host_name}' WHERE `name1`='$old_name'";
    $rs = exec_sql_query(DB_NDOUTILS, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating {$old_name} to {$host_name}.<br />";
        $error++;
    }

    return $errors;
}

/**
 *   Updates name2 field in ndoutils
 *
 * @param mixed  $array REFERENCE variable to service information returned from form
 * @param string $msg   REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_service_ndoutils($array, &$msg, &$hosts)
{
    $errors = 0;
    $hosts = array();
    $old_name = $array['old_name'];
    $service = $array['service_description'];
    $id = $array['id'];
    $hoststring = '';

    // Get related hosts from nagiosql and build hoststring for next query
    $hosts = get_service_to_host_relationships($id, $hoststring);

    // Abstract this a bit more?
    $query = "UPDATE nagios_objects SET `name2`='{$service}' WHERE `name1` IN({$hoststring}) AND `name2`='{$old_name}'";
    $rs = exec_sql_query(DB_NDOUTILS, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating {$old_name} to {$service}.<br />";
        $errors++;
    }

    return $errors;
}

/**
 *   Renames a host in the nagiosql table, remove config file it's stored in
 *
 * @param mixed  $host_array host information returned from form
 * @param string $msg        REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_host_nagiosql($host_array, &$msg)
{
    $error = 0;
    $host_name = $host_array['host_name'];
    $old_name = $host_array['old_name'];
    $id = intval($host_array['id']);

    $alias = grab_array_var($host_array, 'alias', '');
    $display_name = grab_array_var($host_array, 'display_name', '');

    // Needs to rename name1 for host entry but also all service entries with name1=host_name
    // abstract this a bit more
    $query = "UPDATE tbl_host SET `host_name`='{$host_name}',`alias`='{$alias}',`display_name`='{$display_name}',`last_modified`=NOW() WHERE `id`={$id}";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating {$host_array['old_name']} to {$host_name}.<br />";
        $error = 1;
    }

    $query = "UPDATE tbl_service SET `config_name`='{$host_name}',`last_modified`=NOW() WHERE `config_name`='{$old_name}'";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating service config name from {$host_array['old_name']} to {$host_name}.<br />";
        $error = 1;
    }

    // Remove old host config file 
    $hostdir = '/usr/local/nagios/etc/hosts/';
    $servicedir = '/usr/local/nagios/etc/services/';
    exec("rm -rf ".$hostdir."*");
    exec("rm -rf ".$servicedir."*");

    return 0;
}

/**
 *   Updates objectnotes to save the notes for the current service
 *
 * @param mixed  $array host/service information returned from form
 * @param string $msg   REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_service_objectnotes($array, &$msg)
{
    $errors = 0;
    $config_name = $array['config_name'];
    $service = $array['service_description'];
    $old_service = $array['old_name'];

    $query = "UPDATE xi_options SET name = 'objectnotes_" . objectnotes_component_transform_object_name($config_name, $service) . "' WHERE name = 'objectnotes_" . objectnotes_component_transform_object_name($config_name, $old_service) . "'";
    $rs = exec_sql_query(DB_NAGIOSXI, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating service object notes for {$old_service} to {$service}.<br />";
        $error = 1;
    }

    return $errors;
}

/**
 *   Renames a service in the nagiosql table, remove config file it's stored in
 *
 * @param mixed  $array host/service information returned from form
 * @param string $msg   REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_service_nagiosql($array, &$msg)
{
    $error = 0;
    $old_name = $array['old_name'];
    $config_name = $array['config_name'];
    $id = intval($array['id']);
    $service = $array['service_description'];

    // Abstract this a bit more
    $query = "UPDATE tbl_service SET `service_description`='{$service}',`config_name`='{$config_name}',`last_modified`=NOW() WHERE `id`={$id}";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);

    // Handle errors 
    if (!$rs) {
        $msg .= "Error updating service name {$old_name} to {$service}.<br />";
        $error = 1;
    }

    // Remove old service config file 
    $servicedir = '/usr/local/nagios/etc/services/';
    $file = $servicedir . $config_name . '.cfg';
    if (file_exists($file)) {
        @unlink($file);
    }

    return $error;
}

/**
 *   creates a directory with new host name, moves perfdata files there
 *   This function creates a new directory with apache.apache ownership, so the reconfigure_nagios.sh
 *   script has to update permissions to nagios.nagios to PNP can write to it
 *
 * @param mixed  $host_array host information returned from form
 * @param string $msg        REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_host_perfdata($host_array, &$msg)
{
    global $cfg;
    $errors = 0;

    // Directories 
    $perfdir = grab_array_var($cfg, 'perfdata_dir', '/usr/local/nagios/share/perfdata');
    $hostdir = pnp_convert_object_name($host_array['old_name']); //cleaned variable
    $destdir = $perfdir . '/' . pnp_convert_object_name($host_array['host_name']); //cleaned variable
    $xmlfile = $destdir . '/' . '_HOST_.xml';

    // Does this host have performance data??
    if (!file_exists($perfdir . '/' . $hostdir)) {
        return $errors;
    }

    // Make new directory
    $cmd = "mkdir -p " . $destdir;
    $output = system($cmd, $code);
    if ($code > 0) {
        $errors++;
        $msg .= _("Unable to create directory") . ": $destdir $output<br />";
        return $errors;
    }

    // Update xml files
    $files = scandir($perfdir . '/' . $hostdir);
    foreach ($files as $file) {
        if (!strpos($file, '.xml')) continue;

        $f = file_get_contents($perfdir . '/' . $hostdir . '/' . $file);
        if (!$f) {
            $errors++;
            $msg .= _("Unable to update performance data XML file:") . " $file<br />";
        }

        // Replace old host string with new one
        $newf = str_replace($host_array['old_name'], $host_array['host_name'], $f);
        if (!file_put_contents($perfdir . '/' . $hostdir . '/' . $file, $newf)) {
            $errors++;
            $msg .= _("Unable to update performance data XML file") . ": $file<br />";
        }
    }

    // Move files
    $origdir = $perfdir . '/' . $hostdir;
    $cmd = "mv -f " . $perfdir . '/' . $hostdir . '/* ' . $destdir;
    $output = system($cmd, $code);

    if ($code > 0 && $output != false) {
        $errors++;
        if ($output != false) {
            $msg .= $output . "<br />";
        }
    }

    return $errors;
}

/**
 *   Update filenames and XML entries for service performance data
 *   This function creates a new xml / rrd file with apache.apache ownership, so the reconfigure_nagios.sh
 *   script has to update permissions to nagios.nagios to PNP can write to it
 *
 * @param mixed  $array service information returned from form
 * @param string $msg   REFERENCE variable to output string
 *
 * @return int $errors error count from function
 */
function rename_service_perfdata($array, &$msg)
{
    global $cfg;
    $errors = 0;

    // Directories 
    $perfdir = grab_array_var($cfg, 'perfdata_dir', '/usr/local/nagios/share/perfdata');

    // Will need to fetch related host names from ndoutils function
    $hosts =& $array['hosts'];

    // Each iteration handles a host:service combination
    foreach ($hosts as $host) {
        $destdir = $perfdir . '/' . pnp_convert_object_name($host) . '/';
        $servicefile = $destdir . pnp_convert_object_name($array['service_description']);
        $oldfile = $destdir . pnp_convert_object_name($array['old_name']);
        $newrrd = $servicefile . '.rrd';
        $newxml = $servicefile . '.xml';
        $oldxml = $oldfile . '.xml';
        $oldrrd = $oldfile . '.rrd';

        // Does this service have performance data??
        if (!file_exists($oldxml) && !file_exists($oldrrd)) {
            return $errors;
        }

        // Update xml files
        $f = file_get_contents($oldxml);
        if (!$f) {
            $errors++;
            $msg .= "Unable to update performance data XML file: $newxml<br />";
            return $errors;
        }

        // Replace old host string with new one
        $newf = str_replace($array['old_name'], $array['service_description'], $f);
        if (!file_put_contents($oldxml, $newf)) {
            $errors++;
            $msg .= "Unable to update service description in performance data XML file: $oldxml<br />";
        }

        // Move (rename) files
        $cmd1 = 'mv -f ' . $oldfile . '.xml ' . $newxml;
        $output = system($cmd1, $code);
        if ($code > 0) {
            $errors++;
            $msg .= "Unable to run: $cmd1. $output<br />";
        }
        $cmd2 = 'mv -f ' . $oldfile . '.rrd ' . $newrrd;
        $output = system($cmd2, $code);
        if ($code > 0) {
            $errors++;
            $msg .= "Unable to run: $cmd2. $output <br />";
        }
    }

    return $errors;
}

/**
 *   Runs multiple SQL queries and gets all related hosts for a particular service.
 *   This function does NOT handle service->hostgroup->hostgroup->host relationships, but should do everything else
 *
 * @param int    $id         service ID from nagiosql tbl_service
 * @param string $hoststring REFERENCE variable to hoststring that will be used in SQL IN() search
 *
 * @return mixed $hosts array of all related hosts
 */
function get_service_to_host_relationships($id, &$hoststring)
{
    $hoststring = '';

    // Handle service->host relationships
    $query = "SELECT a.id,a.host_name FROM tbl_lnkServiceToHost b JOIN  tbl_host a ON a.id=b.idSlave WHERE b.idMaster={$id}";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);

    foreach ($rs as $row) {
        $hoststring .= "'" . $row['host_name'] . "',";
        $hosts[] = $row['host_name']; // Push related hosts into array
    }

    // Check for service->hostgroup relationships
    $query = "SELECT b.host_name FROM tbl_lnkHostgroupToHost a 
              JOIN tbl_host b ON a.idSlave=b.id WHERE idMaster 
              IN (SELECT idSlave FROM tbl_lnkServiceToHostgroup WHERE idMaster={$id})";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    if ($rs && $rs->recordCount() > 0) { // There are hostgroup relationships
        foreach ($rs as $row) {
            $hoststring .= "'" . $row['host_name'] . "',";
            $hosts[] = $row['host_name']; // Push related hosts into array           
        }
    }

    // Check relationships host->hostgroup
    $query = "SELECT b.host_name FROM tbl_lnkHostToHostgroup a 
              JOIN tbl_host b ON a.idMaster=b.id WHERE idSlave 
              IN (SELECT idSlave FROM tbl_lnkServiceToHostgroup WHERE idMaster={$id})";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    if ($rs && $rs->recordCount() > 0) { // There are hostgroup relationships
        foreach ($rs as $row) {
            $hoststring .= "'" . $row['host_name'] . "',";
            $hosts[] = $row['host_name']; // Push related hosts into array           
        }
    }

    // Remove last comma
    $hoststring = substr($hoststring, 0, (strlen($hoststring) - 1));

    return $hosts;
}

function get_service_to_host_list($id)
{
    $hosts = get_service_to_host_relationships($id, $hoststring);
    $output = "";
    foreach ($hosts as $host) {
        $output .= "<div>{$host}</div>";
    }
    return $output;
}