<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: ajax.php
//  Desc: Defines all functions that get called from various pages to do ajax requests for
//        most of the misc. actions that are available in the CCM.
//

// Define environment
if (file_exists(dirname(__FILE__).'/../../../config.inc.php')) {
    define('ENVIRONMENT', 'nagiosxi');
} else {
    define('ENVIRONMENT', 'nagioscore');
}

// Start the session and initialization based on environment
if (ENVIRONMENT == "nagiosxi") {

    // Include the Nagios XI helper functions through the component helper file and initialize
    // anything we will need to authenticate ourselves to the CCM
    require_once(dirname(__FILE__).'/../componenthelper.inc.php');
    pre_init();
    init_session();

} else {
    ob_start();
    session_start();
}

// Set the location of the CCM root directory
define('BASEDIR', dirname(__FILE__).'/');
require_once('includes/session.inc.php');

// Check authentication and grab the token and command (there shouldn't be one
// but we check this anyway...)
$cmd = ccm_grab_request_var('cmd', '');
$token = ccm_grab_request_var('token', '');
if ($AUTH !== true) { die("You must be authorized to use these commands."); }

// Verify that the command was submitted from the form and if it's not then the user will be
// routed to the login page if it's an illegal operation otherwise route the request for download
verify_token($cmd, $token);
route_request($cmd);

/**
* function route_request()
* Directs page navigation and input requests for config downloads
* @param string $cmd Requires a valid command to do anything
*/
/**
 * @param string $cmd
 */
function route_request($cmd='')
{
    $opt = ccm_grab_request_var('opt', false);
    switch ($cmd) {

        case 'getcontacts':
            print get_ajax_relationship_table($opt);
            break;

        case 'getcontactgroups':
            print get_cg_ajax_relationship_table($opt);
            break;

        case 'getinfo':
            $type = ccm_grab_request_var('type', '');
            print get_ajax_documentation($type, $opt);
            break;

        case 'getfile':
            print get_ajax_file($opt);
            break;

        case 'info':
            print page_router();
            break;

        case 'removesession':
            print ccm_remove_session();
            break;

        case 'updatesession':
            print ccm_update_session();
            break;

        case 'takelock':
            print ccm_takeover_lock();
            break;

        default:
            echo _('Error: Command not found.');
            //echo $cmd;
            //echo $opt;
            break;
    }
}

/**
* function get_ajax_file()
* Loads a static config file into a text area
* @param string $file
*/
function get_ajax_file($file)
{
    global $ccmDB;

    // We need to make sure that the file they are looking for IS in the static directory and not some random directory
    $dir = $ccmDB->query("SELECT `value` FROM tbl_settings WHERE `name`='staticdir'");
    $static_dir = isset($dir[0]['value']) ? $dir[0]['value'] : '/usr/local/nagios/etc/static';

    $p = explode("/", $file);
    $file = end($p);
    $file = $static_dir."/".$file;

    if (is_readable($file) && is_file($file)) {
        $contents = file_get_contents($file);
        return $contents;
    }

    return "File is not readable.";
}

/**
* function get_ajax_documentation
* Fetch config documentation for selected option
* @param $type
* @param $opt
*/
function get_ajax_documentation($type, $opt)
{
    $infolang = get_documentation_array($type, $opt);
    if (!empty($infolang)) {
        print $infolang;
        print "<div class='closeOverlay'>
                   <a class='linkBox' href='javascript:killOverlay(\"documentation\")' title='Close'>Close</a>
               </div><!-- end closeOverlay -->";
    }

}

/**
* function get_ajax_relationship_table()
* Gets relationship table for contacts
* @param $opt
*/
function get_ajax_relationship_table($opt='host')
{
    global $ccmDB;
    $contact = ccm_grab_request_var('contact', false);
    $id = ccm_grab_request_var('id', false);
    $html = "<div class='bulk_wizard'>";

    $query = "SELECT `id`,`host_name` FROM `tbl_lnkHostToContact` LEFT JOIN `tbl_host` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'"; 
    $results = $ccmDB->query($query);

    $html .= "<div class='leftBox'>
                <h4>"._("Hosts directly assigned to contact").": {$contact}</h4>
                <p class='ccm-label'>"._("Check any relationships you wish to")." <strong>"._("remove")."</strong></p>
                <table class='standardtable' style='text-align: center;'>
                    <tr>
                        <th>"._("Host")."</th>
                        <th>
                            "._("Assigned as Contact")."<br />
                            <a id='checkAllhost' style='float:none;' title='Check All' href='javascript:checkAllType(\"host\");'>"._("Check All")."</a>               
                        </th>
                    </tr>"; 

    if (empty($results)) {
        $html .= "<tr><td colspan='2'>"._("No relationships for this contact")."</td></tr>";
    }
                
    foreach($results as $r) {
        $html .= "<tr>
                    <td>".$r['host_name']."</td>
                    <td style='text-align: center;'>
                        <input class='host' type='checkbox' name='hostschecked[]' value='".$r['id']."' />
                    </td>
                </tr>";
    }

    $html .= "</table></div>";
    $html .= "<div class='rightBox'>
                <h4>"._("Service directly assigned to contact").": {$contact}</h4>
                <p class='ccm-label'>"._("Check any relationships you wish to")." <strong>"._("remove")."</strong></p>
                <table class='standardtable' style='text-align: center;'>
                    <tr>
                        <th>"._("Config Name")."</th>
                        <th>"._("Service Description")."</th>                          
                        <th>    
                            "._("Assigned as Contact")."<br />
                            <a id='checkAllservice' style='float:none;' title='Check All' href='javascript:checkAllType(\"service\");'>"._("Check All")."</a>
                        </th>
                    </tr>"; 
    
    // Get option list           
    $query = "SELECT `id`,`config_name`,`service_description` FROM `tbl_lnkServiceToContact` LEFT JOIN `tbl_service` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'"; 
    $results = $ccmDB->query($query);          
                
    if (empty($results)) { 
        $html .= "<tr><td colspan='3'>"._("No relationships for this contact")."</td></tr>";
    }     
    
    // Display list 
    foreach ($results as $r) {
        $html .= "<tr>
                    <td>".$r['config_name']."</td>
                    <td>".$r['service_description']."</td>
                    <td style='text-align: center;'>
                        <input class='service' type='checkbox' name='serviceschecked[]' value='".$r['id']."' />
                    </td>
                </tr>";
    }
    
    $html .= "</table></div>";
    return $html;
}

/**
* function get_cg_ajax_relationship_table
* Gets relationship table for contact groups  
* @TODO: this can be rolled into the same function as contact
*/
function get_cg_ajax_relationship_table($opt='host') 
{
    global $ccmDB;
    $contactgroup = ccm_grab_request_var('contactgroup', false);
    $id = ccm_grab_request_var('id', false);
    
    $query = "SELECT `id`,`host_name` FROM `tbl_lnkHostToContactgroup` LEFT JOIN `tbl_host` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'"; 
    $results = $ccmDB->query($query); 

    $html = "<div class='bulk_wizard'>"; 
    $html .= "<div class='leftBox'>
                <h4>"._("Hosts directly assigned to contact").": {$contactgroup}</h4>
                <p class='ccm-label'>"._("Check any relationships you wish to")." <strong>"._("remove")."</strong></p>
                <table class='standardtable' style='text-align:center;'>
                    <tr>
                        <th>"._("Host")."</th>
                        <th>
                            "._("Assigned as Contact Group")."<br />
                            <a id='checkAllhost' style='float:none;' title='Check All' href='javascript:checkAllType(\"host\");'>Check All</a>              
                        </th>
                    </tr>"; 

    if (empty($results)) {
        $html .= "<tr><td colspan='2'>"._("No relationships for this contact group")."</td></tr>";
    }
                
    foreach($results as $r) {
        $html .= "<tr>
                    <td>".$r['host_name']."</td>
                    <td style='text-align: center;'>
                        <input class='host' type='checkbox' name='hostschecked[]' value='".$r['id']."' />
                    </td>
                </tr>";
    } 
    
    $html .= "</table></div>";
    $html .= "<div class='rightBox'>
                <h4>"._("Service directly assigned to contact").": {$contactgroup}</h4>
                <p class='ccm-label'>"._("Check any relationships you wish to")." <strong>"._("remove")."</strong></p>
                <table class='standardtable' style='text-align: center;'>
                    <tr>
                        <th>"._("Config Name")."</th>
                        <th>"._("Service Description")."</th>                          
                        <th>
                            "._("Assigned as Contact")."<br />
                            <a id='checkAllservice' style='float:none;' title='Check All' href='javascript:checkAllType(\"service\");'>"._("Check All")."</a>
                        </th>
                    </tr>"; 
    
    // Get option list           
    $query = "SELECT `id`,`config_name`,`service_description` FROM `tbl_lnkServiceToContactgroup` LEFT JOIN `tbl_service` ON `idMaster` = `id` WHERE `idSlave` = '{$id}'"; 
    $results = $ccmDB->query($query);          
    
    if (empty($results)) {
        $html .= "<tr><td colspan='3'>"._("No relationships for this contact group")."</td></tr>";       
    }

    // Display list 
    foreach($results as $r) {
        $html .= "<tr>
                    <td>".$r['config_name']."</td>
                    <td>".$r['service_description']."</td>
                    <td style='text-align: center;'>
                        <input class='service' type='checkbox' name='serviceschecked[]' value='".$r['id']."' />
                    </td>
                </tr>";
    }
    
    $html .= "</table></div>";
    return $html;
}

function ccm_remove_session()
{
    global $ccmDB;
    $id = intval(ccm_grab_request_var('ccm_session_id', 0));

    if (empty($id)) {
        return;
    }

    // Remove the session from the db and clear any locks
    $ccmDB->delete_entry('session_locks', 'sid', $id);
    $ccmDB->delete_entry('session', 'id', $id);
}

function ccm_update_session()
{
    global $ccmDB;
    $id = intval(ccm_grab_request_var('ccm_session_id', 0));
    $active = intval(ccm_grab_request_var('active', 1));
    $obj_id = intval(ccm_grab_request_var('obj_id', 0));
    $lock_id = intval(ccm_grab_request_var('lock_id', 0));

    // Check if locking is enabled
    $enable_locking = get_option('ccm_enable_locking', 1);

    if (empty($id)) {
        return;
    }

    // Update the session value
    $sql = "UPDATE tbl_session SET last_updated = '".time()."', active = ".$active." WHERE id = ".$id;
    $ccmDB->query($sql);

    $data = array('has_new_lock' => 0);

    // Check if new lock exists
    if ($enable_locking) {
        $sql = "SELECT *, l.id AS id FROM `tbl_session_locks` AS l LEFT JOIN `tbl_session` AS s ON l.sid = s.id WHERE l.obj_id = ".$obj_id." AND l.sid != ".$id." AND l.id != ".$lock_id." LIMIT 1";
        $result = $ccmDB->query($sql);
        if (!empty($result)) {
            $lock = $result[0];
            $username = get_user_attr($lock['user_id'], 'username');
            $lock['username'] = $username;
            $data = array('has_new_lock' => 1,
                          'lock' => $lock,
                          'locktext' => '<b>'.$username.'</b> '._('removed lock and started editing at').' '.get_datetime_string($lock['started'], DT_SHORT_DATE_TIME, DF_AUTO, "null"));
        }
    }

    print json_encode($data);
}

function ccm_takeover_lock()
{
    global $ccmDB;
    $id = intval(ccm_grab_request_var('ccm_session_id', 0));
    $lock_id = intval(ccm_grab_request_var('lock_id', 0));

    if (empty($id) || empty($lock_id)) {
        return;
    }

    $sql = "UPDATE tbl_session_locks SET sid = ".$id." WHERE id = ".$lock_id.";";
    $x = $ccmDB->query($sql);

    if (!empty($x)) {
        return json_encode(array('success' => 1));
    }

    return json_encode(array('success' => 0));;
}
