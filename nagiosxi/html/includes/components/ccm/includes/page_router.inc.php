<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: page_router.inc.php
//  Desc: Functions for directing the user to certain pages inside the CCM.
//

/**
 * Main page content routing function. Handles ALL requests for action in the CCM to build appropriate page.
 *
 * @global object $Menu menu object for navigation menu 
 * @global object $myDataClass NagiosQL object to work with copy and delete commands 
 * @global bool $AUTH main authorization boolean
 * @return string $html html page output
 */
function page_router()
{
    global $Menu;
    global $myDataClass;
    global $AUTH;

    // Debug output
    if ($debug = ccm_grab_array_var($_SESSION, 'debug', false)) {
        ccm_array_dump($_REQUEST);
    }

    // Process input variables   
    $cmd = ccm_grab_request_var('cmd', "");
    $type = ccm_grab_request_var('type', "");
    $id = ccm_grab_request_var('id', false);
    $objectName = ccm_grab_request_var('objectName', '');
    $token = ccm_grab_request_var('token', grab_array_var($_SESSION, 'token', ''));

    // Do a quick authorization check and verify that the command was submitted from the
    // form, route to login page if it's an illegal operation
    if ($AUTH !== true) { $cmd = 'login'; }
    verify_token($cmd, $token);
    verify_type($cmd, $type); // XSS check

    switch ($cmd)
    {
        case 'login':
            include_once(TPLDIR.'login.inc.php');
            $html = build_login_form();
            return $html;

        // Kill the session on log out
        case 'logout':
            $username = $_SESSION['ccm_username'];
            unset($_SESSION['ccm_username']);
            unset($_SESSION['ccm_login']);
            unset($_SESSION['token']);
            unset($_SESSION['loginMessage']);
            unset($_SESSION['startsite']);
            unset($_SESSION['keystring']);
            unset($_SESSION['strLoginMessage']);
            audit_log(AUDITLOGTYPE_SECURITY, $username._(" successfully logged out of Nagios CCM"));
            include_once(TPLDIR.'login.inc.php');
            $html = build_login_form();
            return $html;

        // Creating views (all tables in the CCM are views)
        case 'view':
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            $args = route_view($type);
            $html = ccm_table($args);
            return $html;

        // Admin only functions
        case 'admin':
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            $html = route_admin_view($type);
            return $html;

        // Redirect users from delete page or do the deletion
        case 'delete':
            if ($type == 'user') {
                $html = route_admin_view($type);
            }
            else {
                if (!empty($id)) {
                    include_once(INCDIR.'delete_object.inc.php');
                    $returnContent = delete_object($type, $id);    
                    if (ENVIRONMENT == "nagiosxi" && $returnContent[0] == 0) {
                        set_option("ccm_apply_config_needed", 1);
                    }
                }
                $args = route_view($type);
                if (ENVIRONMENT == "nagioscore") {
                    $Menu->print_menu_html();
                }
                $msgtype = FLASH_MSG_INFO;
                if ($returnContent[0] == 1) {
                    $msgtype = FLASH_MSG_ERROR;
                }
                flash_message($returnContent[1], $msgtype);
                $html = ccm_table($args);
            }
            return $html;

        case 'delete_multi':
            include_once(INCDIR.'delete_object.inc.php');
            $returnContent = delete_multi($type);
            $args = route_view($type);
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            if (ENVIRONMENT == "nagiosxi" && $returnContent[0] == 0) {
                set_option("ccm_apply_config_needed", 1);
            }
            $msgtype = FLASH_MSG_INFO;
            if ($returnContent[0] == 1) {
                $msgtype = FLASH_MSG_ERROR;
            }
            flash_message($returnContent[1], $msgtype);
            $html = ccm_table($args);
            return $html;

        case 'deactivate':
        case 'deactivate_multi':
        case 'activate':
        case 'activate_multi':
            include_once(INCDIR.'activate.inc.php');
            $returnContent = route_activate($cmd, $type, $id);
            $args = route_view($type);
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            $msgtype = FLASH_MSG_INFO;
            if ($returnContent[0] == 1) {
                $msgtype = FLASH_MSG_ERROR;
            }
            flash_message($returnContent[1], $msgtype);
            return ccm_table($args);

        // Creates the form for modify or insert actions
        case 'modify':
        case 'insert':
            $ccmDB = new Db;
            $FIELDS = array();
            if ($type == '') { return; }

            // Build form to display based on type and cmd (modify or insert)
            $Form = new Form($type, $cmd);
            $Form->prepare_data();
            $Form->build_form();
            break;

        // Copy a single object and return to the table
        case 'copy':
            $keyField = $myDataClass->getKeyField($type);
            $bool = $myDataClass->dataCopyEasy('tbl_'.$type, $keyField,$id);
            $returnContent = array($bool, $myDataClass->strDBMessage);
            $args = route_view($type);
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            $msgtype = FLASH_MSG_INFO;
            if ($returnContent[0] == 1) {
                $msgtype = FLASH_MSG_ERROR;
            }
            flash_message($returnContent[1], $msgtype);
            return ccm_table($args);

        // Copy multiple objects and return to the table
        case 'copy_multi':
            $checks = ccm_grab_request_var('checked', array());
            $keyField = $myDataClass->getKeyField($type);
            $copyCount = 0;
            $failCount = 0;
            $returnMessage = '';
            foreach ($checks as $id) {
                $bool = $myDataClass->dataCopyEasy('tbl_'.$type, $keyField, $id);
                if ($bool == 0) {
                    $copyCount++;
                } else {
                    $failCount++;
                }
                $returnMessage .= $myDataClass->strDBMessage."<br />";
            }

            // Determine return status and message 
            if ($copyCount == 0) {
                $returnContent = array(1, "<strong>"._("No objects copied.")."</strong><br />".$returnMessage);
            } else if ($failCount > 0) {
                $returnContent = array(1, "$copyCount "._("objects copied").".<strong>$failCount "._("objects failed to copy.")."</strong><br />".$returnMessage);
            } else {
                $returnContent = array(0, "$copyCount "._("objects copied succesfully!")."<br />".$returnMessage);
            }

            // Display actual table page
            $args = route_view($type);
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            $html = ccm_table($args,$returnContent);
            return $html;

        case 'info':
            $table = 'tbl_'.$type;
            $myDataClass->fullTableRelations($table, $arrRelations);

            // If service dependency then service only
            $so = false;
            if ($type == 'servicedependency') {
                $so = true;
            }

            $bool = $myDataClass->infoRelation($table, $id, "id", 1, $so);

            switch ($type) {
                case 'hostescalation':
                    $hr_type = _('Host Escalation');
                    break;
                case 'serviceescalation':
                    $hr_type = _('Service Escalation');
                    break;
                default:
                    $hr_type = $type;
                    break;
            }

            $deps = '';
            if ($myDataClass->hasDepRels) {
                $deps = '<span class="label label-danger label-10">'._('Dependent relationships denoted by').' <i class="fa fa-link"></i></span>';
            }

            $returnMessage = '
<div>
    <div class="close"><i class="fa fa-times"></i></div>
    <h1 style="padding: 0; margin: 0;">'.$objectName.'</h1>
    <p style="padding: 5px 0 10px 0; margin: 0;">'._('Object relationships').' '.$deps.'</p>
    <div id="rel-tabs" style="border: 0;">
        <ul>';

            foreach ($myDataClass->arrRR as $tab => $data) {
                $dep = false;
                foreach ($data as $d) {
                    if (is_array($d)) {
                        if (array_key_exists('dependent', $d)) {
                            $dep = true;
                            break;
                        }
                    }
                }

                $depicon = '';
                if ($dep) {
                    $depicon = '<i class="fa fa-link l rt-tt-bind" title="'._('Dependent relationships').'"></i> ';
                }

                $returnMessage .= '<li><a href="#tab-'.$tab.'">'.$depicon.ucfirst($tab).'s <span class="badge">'.count($data).'</span></a></li>';
            }

            $returnMessage .= '</ul>';

            foreach ($myDataClass->arrRR as $tab => $data) {
                $returnMessage .= '<div id="tab-'.$tab.'"><div class="bounding-box">
                    <table class="table table-condensed table-striped">';

                if ($tab == 'service' && $type != 'servicedependency') {
                    $returnMessage .= '<thead><tr>
                                <th>'._('Config Name').'</th>
                                <th>'._('Service Description').'</th>
                            </tr></thead>';
                }

                foreach ($data as $oid => $obj) {

                    $returnMessage .= '<tr>';
                    $dep = '';
                    $name = _('Unknown');

                    if (is_array($obj)) {
                        if (!empty($obj['cfg'])) { $returnMessage .= '<td>'.$obj['cfg'].'</td>'; }
                        if (!empty($obj['dependent'])) { $dep = '<i title="'._('Dependent relationship').'" class="rt-tt-bind fa fa-13 fa-link fa-fw"></i> '; }
                        if (array_key_exists('service', $obj)) {
                            $name = $obj['service'];
                        } else if (array_key_exists('name', $obj)) {
                            $name = $obj['name'];
                        }
                    } else {
                        $name = $obj;
                    }

                    if ($type == 'servicedependency' && $tab == 'service') {
                        $r = $name;
                    } else {
                        $r = $dep.'<a href="index.php?cmd=modify&type='.$tab.'&id='.$oid.'">'.$name.'</a>';
                    }
                    $returnMessage .= '<td>'.$r.'</td></tr>';

                }

                $returnMessage .= '</table></div></div>';
            }

            $returnMessage .= '</div>
    <script type="text/javascript">
    $("#rel-tabs").tabs().show();
    $(".rt-tt-bind").tooltip();
    </script>
</div>
            ';

            return $returnMessage;

        // Submit results to the ccm table page
        case 'submit':
            route_submission($type);

            // If the user came from a page (which should be every time) then redirect them back
            // the the page that they were already on... this should make sense!
            $page = ccm_grab_request_var('page');

            header("Location: index.php?cmd=view&type=".$type."&page=".$page);
            exit;

        case 'apply':
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            require_once(INCDIR.'applyconfig.inc.php');
            $html = apply_config($type);
            return $html;

        case 'default':
        default:
            if (ENVIRONMENT == "nagioscore") {
                $Menu->print_menu_html();
            }
            $html = default_page();
            return $html;
    }
}

/**
 * Determines and fetches information to be presented in in the main CCM display tables based on object $type
 *
 * @param string $type Nagios object type (host,service,contact, etc)
 * @return array $return_args['data'] array of filtered DB results
 *                           ['keyName'] string used for table header
 *                           ['keyDesc'] string used for table description 
 *                           ['config_names'] array of config_names for services table | empty array                     
 */
function route_view($type, $returnData=array())
{
    global $ccmDB;
    global $request;
    $txt_search = ccm_grab_request_var('search', '');
    $name_filter = ccm_grab_request_var('name_filter', '');
    $orderby = ccm_grab_request_var('orderby', ccm_grab_array_var($_SESSION, $type.'_orderby', ''));
    $sort = ccm_grab_request_var('sort', ccm_grab_array_var($_SESSION, $type.'_sort', 'ASC'));
    $sortlist = ccm_grab_request_var('sortlist', false);
    $session_search = ccm_grab_array_var($_SESSION, $type.'_search', '');
    $query = '';

    if($orderby != '') {
        $_SESSION[$type.'_orderby'] = $orderby;
        $_SESSION[$type.'_sort']= $sort;
        $sortlist = 'true';
    }
    // Get relevant entries  
    list($table, $typeName, $typeDesc) = get_table_and_fields($type);

    // Required params for standard views
    if (isset($typeName, $typeDesc)) {

        // we need some additional data to determine if we have mrtg data for this service
        $mrtg_check = "";
        if ($type == "service")
            $mrtg_check = ",check_command";

        // Build SQL query based on filters and type
        $query = "SELECT id,{$typeName},{$typeDesc},last_modified,active{$mrtg_check} FROM `{$table}` ";
        if ($type != "user") // no config_id column in tbl_users tps#7540 -bh
            $query .= "WHERE `config_id`={$_SESSION['domain']} ";

        // Search filters 
        $searchQuery = '';
        $config_names = array();

        // If clear has been pressed, clear search values
        if ($txt_search == 'false' || (isset($_POST['search']) && $_POST['search'] == '')) {
            $txt_search = '';
            $session_search = '';
            unset($_SESSION[$type.'_search']);
            unset($request['search']);
        }

        // If we are searching use text search first, else use what is in the session
        if ($txt_search != "" || $session_search != '') {
            $search = (($txt_search != '') ? $txt_search : $session_search); 
            $searchQuery = "AND (`$typeName` LIKE '%".$search."%' OR `$typeDesc` LIKE '%".$search."%'";
            if ($type == 'host') {
                $searchQuery.=" OR `display_name` LIKE '%".$search."%' OR `address` LIKE '%".$search."%'";
            }
            $searchQuery .=')';
        }

        // "config_name" filter only used on services page
        if ($name_filter != '' && $name_filter != 'null') {
            $_SESSION['name_filter'] = $name_filter;
        }

        if (isset($_SESSION['name_filter'])) {
            // Verify named filter exists and remove it if it doesn't
            $result = $ccmDB->query("SELECT DISTINCT config_name FROM tbl_service WHERE config_name = '".$ccmDB->escape_string($_SESSION['name_filter'])."';");
            if (empty($result)) {
                unset($_SESSION['name_filter']);
            }
        }

        // Clear name filter is empty has been selected OR if clear button has been pressed
        if ($name_filter == 'null' || $txt_search == 'false') {
            unset($_SESSION['name_filter']);
        }

        // Add to query if relevant
        if ($type == 'service' && isset($_SESSION['name_filter']) && $_SESSION['name_filter'] != '' && $_SESSION['name_filter'] != 'null') {
            $query .= "AND `config_name`='{$_SESSION['name_filter']}' ";
        }

        if ($sortlist != 'false' && $sortlist != false) {
            $query .= "$searchQuery ORDER BY `$orderby` ";
            if ($orderby == "config_name" && $type == "service") {
                $query .= $sort.", `service_description` ASC";
            }
        } else {
            $query .= "$searchQuery ORDER BY `$typeName`";
            if ($typeName == "config_name" && $type == "service") {
                $query .= ", `service_description`";
            }
        }

        // Finally, sort by either ASC or DESC
        if ($orderby != "config_name") {
            $query .= " {$sort} ";
        }

        // Grab config names for services page if needed
        if ($typeName == 'config_name') {
            $config_names = $ccmDB->query("SELECT DISTINCT config_name FROM tbl_service;");
        }

        // Return the query
        $return_args = array('data'         => $ccmDB->query($query),
                             'keyName'      => $typeName,
                             'keyDesc'      => $typeDesc,
                             'config_names' => $config_names);

        return $return_args;
    } else {
        // Can't route the request so we can just exit...
        exit();
    }
}


/**
 * Switch that handles submissions for adding and modifying config objects
 *
 * @param string $type nagios object type (host,service,contact, etc) 
 * @return array $returnData (int exitCode, string exitMessage)
 */
function route_submission($type)
{
    $returnData = array(0, '');

    switch ($type)
    {
        case 'host':
        case 'service':
        case 'hosttemplate';
        case 'servicetemplate': 
            require_once('hostservice.inc.php');
            $returnData = process_ccm_submission();
            break;

        case 'hostgroup':
        case 'servicegroup':
        case 'contactgroup':
            require_once(INCDIR.'group.inc.php');
            $returnData = process_ccm_group();
            break;

        case 'timeperiod':
            require_once(INCDIR.'objects.inc.php');
            $returnData = process_timeperiod_submission();
            break;

        case 'command':
            require_once(INCDIR.'objects.inc.php');
            $returnData = process_command_submission();
            break;

        case 'contact':
        case 'contacttemplate':
            require_once(INCDIR.'contact.inc.php');
            $returnData = process_contact_submission();
            break;

        case 'serviceescalation':
        case 'hostescalation':
            require_once(INCDIR.'objects.inc.php');
            $returnData = process_escalation_submission();
            break;

        case 'servicedependency':
        case 'hostdependency':
            require_once(INCDIR.'objects.inc.php');
            $returnData = process_dependency_submission();
            break;

        default:
            $returnData = array(1, "Missing arguments! No type specified for route.");
            break;

    }
    
    flash_message($returnData[1]);
}


/**
 * Routes the views for admin pages such as the CCM Log, User Management, and  CCM Settings
 * @param $type
 * @return string|void
 */
function route_admin_view($type)
{
    global $ccmDB;
    global $request;
    require_once(INCDIR.'admin_views.inc.php');
    $txt_search = ccm_grab_request_var('search', '');
    $query = '';
    $session_search = ccm_grab_array_var($_SESSION, $type.'_search', '');

    switch ($type)
    {
        case 'user':
            $mode = ccm_grab_request_var('mode', false);
            $id = ccm_grab_request_var('id', false);
            $cmd = ccm_grab_request_var('cmd', "");
            $returnData = array(0, '');

            // Handle submissions on the Users page
            if (($mode == 'insert') || ($mode == 'modify') || ($cmd == 'delete')) {
                $returnData = process_user_submission();
            }

            // Query all users
            $query = "SELECT `id`,`username`,`alias`,`active` FROM `tbl_user` WHERE 1 ";
            list($table, $typeName, $typeDesc) = get_table_and_fields($type);

            // Required params for standard views 
            if (isset($typeName, $typeDesc)) {
                $config_names = array();
                // If clear has been pressed, clear search values
                if ($txt_search == 'false' || (isset($_POST['search']) && $_POST['search']=='') ) {
                    $txt_search='';
                    $session_search='';
                    unset($_SESSION[$type.'_search']);
                    unset($request['search']);
                }
                if ($txt_search != "" || $session_search != '') {
                    // Use text search first, else use what is in the session
                    $search = ($txt_search!='') ? $txt_search : $session_search;
                    $query .= "AND (`$typeName` LIKE '%".$search."%' OR `$typeDesc` LIKE '%".$search."%'";
                    $query .=')';
                }
            }

            $return_args = array('data' => $ccmDB->query($query),
                                 'keyName' => 'username',
                                 'keyDesc' => 'alias',
                                 'config_names' => array());
            return ccm_table($return_args, $returnData);

        case 'import':
            return ccm_import_page();

        case 'corecfg':
            return ccm_corecfg();

        case 'cgicfg': 
            return ccm_cgicfg();

        case 'log':
            require_once(INCDIR.'ccm_log.inc.php');
            return ccm_log();

        case 'settings':
            return ccm_settings();

        case 'static':
            return ccm_static_editor();

        default:
            return default_page();
    }
}

// for backwards compatibility with xi < 5.3.0
if (!function_exists("sensitive_field_autocomplete")) {
    function sensitive_field_autocomplete() {
        return "";
    }
}