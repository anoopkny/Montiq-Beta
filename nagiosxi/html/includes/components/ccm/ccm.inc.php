<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  Authors:
//      Jacob Omann - Nagios Enterprises <jomann@nagios.com>
//      Scott Wilkerson - Nagios Enterprises <swilkerson@nagios.com>
//      Luke Groshen - Nagios Enterprises <lgroshen@nagios.com>
//
//  Past Authors:
//      Mike Guthrie - Nagios Enterprises
//
//  Based on NagiosQL 3.0.3
//  Original Authors:
//      Martin Willisegger
//

// Include the component helper file
require_once(dirname(__FILE__).'/../componenthelper.inc.php');

// Run the initialization function
$ccm_component_name = "ccm";
ccm_component_init();


////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////


function ccm_component_init() {
    global $ccm_component_name;
    global $cfg;
    $versionok = ccm_component_checkversion();
    
    // Component description / version ok check
    $desc = _("Integration with Nagios Core Config Manager used to manage object configuration files for Nagios XI.");
    if (!$versionok) {
        $desc = "<b>"._('Error: This component requires Nagios XI 2011R3.4 or later.0')."</b>";
    }
    
    // All components require a few arguments to be initialized correctly.  
    $args = array(
        COMPONENT_NAME => $ccm_component_name,
        COMPONENT_VERSION => '2.6.2',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => _("Nagios Core Config Manager (CCM)"),
        COMPONENT_PROTECTED => true,
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );
    
    // Register this component with XI 
    register_component($ccm_component_name, $args);
    
    // Register the addmenu function
    define('MENU_CCM', 'ccm');
    
    // Register all callbacks for Nagios XI
    if ($versionok) {
        if (use_2012_features() == true) {
            register_callback(CALLBACK_MENUS_DEFINED, 'add_ccm_menu');
            register_callback(CALLBACK_MENUS_INITIALIZED, 'ccm_component_addmenu');
            register_callback(CALLBACK_PAGE_HEAD, 'ccm_component_head_include'); 
            ccm_component_update_ccm_config();
        }
    }   
}   

/**
 * Callback function for the do_page_head funtion that creates the required includes for
 * the CCM styles and javascript while inside of Nagios XI.
 */
function ccm_component_head_include($cbtype='', $args=null) {
    global $components;
    $component_base = get_base_url().'includes/components/ccm/';

    if (strpos($_SERVER['PHP_SELF'], 'includes/components/ccm/') !== false || strpos($_SERVER['PHP_SELF'], 'includes/components/bulkmodifications/') !== false) {
        echo "<link rel='stylesheet' type='text/css' href='".$component_base."css/style.css?".$components['ccm']['args']['version']."' />";
        echo '<script type="text/javascript" src="'.$component_base.'javascript/main_js.js?'.$components['ccm']['args']['version'].'"></script>
              <script type="text/javascript">
              var NAGIOSXI=true
              </script>';
    }
}

// Requires Nagios XI version greater than 2011R2.1
function ccm_component_checkversion() {
    if (!function_exists('get_product_release')) {
        return false;
    }
    if (get_product_release() < 217) {
        return false;
    }
    return true;
}


// Update the CCM config file
function ccm_component_update_ccm_config() {
    global $cfg; 
    
    // We don't want subsystem jobs messing up file ownership
    if (defined('SUBSYSTEM')) {
        return; 
    }
    
    // Make sure we can interact with the db
    db_connect_all(); 

    $base = get_root_dir(); 
    $ccm_cfg = $base.'/etc/components/ccm_config.inc.php'; 
    $ccm_last_update = filemtime($ccm_cfg); 
    $default_language = get_option("default_language");
    $mtime = filemtime($base.'/html/config.inc.php');
    
    // Log to apache if we can't update CCM credentials
    if (file_exists($ccm_cfg) && !is_writable($ccm_cfg)) {
        trigger_error("CCM Config File: {$ccm_cfg} is not writable by apache!", E_USER_NOTICE);
    }   
    
    // Update config hourly or if config.inc.php has been updated
    if (!file_exists($ccm_cfg) || $mtime > $ccm_last_update || filesize($ccm_cfg) == 0) {

        $plugins = grab_array_var($cfg['component_info']['nagioscore'], 'plugin_dir', '/usr/local/nagios/libexec');
        $nagcmd = grab_array_var($cfg['component_info'], 'plugin_dir', '/usr/local/nagios/var/rw/nagios.cmd');
        $server = grab_array_var($cfg['db_info']['nagiosql'], 'dbserver', 'localhost');
        $server = grab_array_var($cfg['db_info']['nagiosql'], 'dbserver', 'localhost');
        $server_parts = explode(':', $server);
        $server = $server_parts[0];
        $port = (!empty($server_parts[1])) ? $server_parts[1] : 3306; 
        $db = grab_array_var($cfg['db_info']['nagiosql'], 'db', 'nagiosql');
        $user = grab_array_var($cfg['db_info']['nagiosql'], 'user', 'nagiosql');
        $password = grab_array_var($cfg['db_info']['nagiosql'], 'pwd', 'n@gweb'); 

        $content = '<?php
        /** DO NOT MANUALLY EDIT THIS FILE
        This file is used internally by Nagios CCM.
        Nagios CCM will override this file automatically with the latest settings. */

        $CFG["plugins_directory"] = "'.$plugins.'";
        $CFG["command_file"] = "'.$nagcmd.'"; 
        $CFG["default_language"] = "'.$default_language.'";
         
        // MySQL database connection info 
        $CFG["db"] = array(
            "server"   => "'.$server.'",
            "port"     => "'.$port.'",
            "database" => "'.$db.'",
            "username" => "'.$user.'",
            "password" => "'.$password.'"
            );';

        file_put_contents($ccm_cfg, $content);
    }
}


///////////////////////////////////////////////////
//  Menu Functions 
//////////////////////////////////////////////////


// Add the CCM link to the Configure page's menu items
function ccm_component_addmenu($arg=null) {
	global $autodiscovery_component_name;

	$mi = find_menu_item(MENU_CONFIGURE, "menu-configure-section-advanced", "id");
	if ($mi == null) {
		return;
	}
		
	$order = grab_array_var($mi, "order", "");
	if ($order == "") {
		return;
	}
	$neworder = $order + 1;

    add_menu_item(MENU_CONFIGURE, array(
        "type" => "link",
        "title" => _("Core Config Manager"),
        "id" => "menu-configure-ccm",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url().'includes/components/ccm/xi-index.php',
            "icon" => "fa-cog",
            "target" => "_top"
            ),
        "function" => "is_advanced_user"
    ));
}


// Build the CCM menu and add it to the page
function add_ccm_menu()
{
    add_menu(MENU_CCM); 
    $ccm_home = get_base_url()."includes/components/ccm/";
    $corecfg_path = get_base_url()."includes/components/nagioscorecfg/";
    $separate_ccm_login = get_option("separate_ccm_login", 0);

    add_menu_item(MENU_CCM, array(
        "type" => "html",
        "title" => _("Nagios Core Config Manager"),
        "id" => "menu-ccm-logo",
        "order" => 100,
        "opts" => array(
            "html" => "<a href='index.php' target='maincontentframe'><img style='margin: 9px 0 8px 0; width: 200px; height: 20px;' src='".$ccm_home."images/ccm_side.png' title='"._("Nagios Core Config Manager")."'></a>"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Quick Tools"),
        "id" => "menu-ccm-section-quicktools",
        "order" => 200,
        "opts" => array(
            "id" => "quicktools",
            "expanded" => true
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Core Config Manager"),
        "id" => "menu-ccm-configmanagerhome",
        "order" => 201,
        "opts" => array(
            "icon" => "fa-home",
            "href" => $ccm_home
        )
    ));     

    add_menu_item(MENU_CCM, array(
        "type" => "linkspacer",
        "order" => 202
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Apply Configuration"),
        "id" => "menu-ccm-applyconfiguration",
        "order" => 210,
        "opts" => array(
            "id" => "ccm-apply-menu-link",
            "icon" => "fa-asterisk",
            "href" => $corecfg_path."applyconfig.php?cmd=confirm"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Configuration Snapshots"),
        "id" => "menu-ccm-configsnapshots",
        "order" => 211,
        "opts" => array(
            "icon" => "fa-hdd-o",
            "href" => get_base_url()."admin/coreconfigsnapshots.php",
            "target" => "maincontentframe"
        ),
        "function" => "is_admin"
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "linkspacer",
        "order" => 212
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Monitoring Plugins"),
        "id" => "menu-ccm-monitoringplugins",
        "order" => 221,
        "opts" => array(
            "icon" => "fa-share",
            "href" => get_base_url()."admin/?xiwindow=monitoringplugins.php",
            "target" => "_parent"
            ),
        "function" => "is_admin"
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Configuration Wizards"),
        "id" => "menu-ccm-configwizards",
        "order" => 222,
        "opts" => array(
            "icon" => "fa-share",
            "href" => get_base_url()."config/?xiwindow=monitoringwizard.php",
            "target" => "_parent"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "id" => "menu-ccm-sectionend-quicktools",
        "order" => 223,
        "title" => "",
        "opts" => ""
    ));
    
    //
    // Monitoring
    //

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Monitoring"),
        "id" => "menu-ccm-section-monitoring",
        "order" => 300,
        "opts" => array(
            "id" => "monitoring",
            "expanded" => true
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Hosts"),
        "id" => "menu-ccm-hosts",
        "order" => 301,
        "opts" => array(
            "icon" => "fa-sticky-note-o",
            "href" => $ccm_home.'?cmd=view&type=host'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Services"),
        "id" => "menu-ccm-services",
        "order" => 302,
        "opts" => array(
            "icon" => "fa-sticky-note-o",
            "href" => $ccm_home.'?cmd=view&type=service'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Host Groups"),
        "id" => "menu-ccm-hostgroups",
        "order" => 303,
        "opts" => array(
            "icon" => "fa-folder-open-o",
            "href" => $ccm_home.'?cmd=view&type=hostgroup'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Service Groups"),
        "id" => "menu-ccm-servicegroups",
        "order" => 304,
        "opts" => array(
            "icon" => "fa-folder-open-o",
            "href" => $ccm_home.'?cmd=view&type=servicegroup'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "title" => "",
        "id" => "menu-ccm-sectionend-monitoring",
        "order" => 305,
        "opts" => ""
    ));

    // 
    // Alerting
    //

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Alerting"),
        "id" => "menu-ccm-section-alerting",
        "order" => 400,
        "opts" => array(
            "id" => "alerting",
            "expanded" => true
        )
    ));

    add_menu_item(MENU_CCM,array(
        "type" => "link",
        "title" => _("Contacts"),
        "id" => "menu-ccm-contacts",
        "order" => 401,
        "opts" => array(
            "icon" => "fa-user",
            "href" => $ccm_home.'?cmd=view&type=contact'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Contact Groups"),
        "id" => "menu-ccm-contactgroups",
        "order" => 402,
        "opts" => array(
            "icon" => "fa-users",
            "href" => $ccm_home.'?cmd=view&type=contactgroup'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Time Periods"),
        "id" => "menu-ccm-timeperiods",
        "order" => 403,
        "opts" => array(
            "icon" => "fa-clock-o",
            "href" => $ccm_home.'?cmd=view&type=timeperiod'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Host Escalations"),
        "id" => "menu-ccm-hostescalations",
        "order" => 404,
        "opts" => array(
            "icon" => "fa-flag",
            "href" => $ccm_home."?cmd=view&type=hostescalation"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Service Escalations"),
        "id" => "menu-ccm-serviceescalations",
        "order" => 405,
        "opts" => array(
            "icon" => "fa-flag",
            "href" => $ccm_home."?cmd=view&type=serviceescalation"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "title" => "",
        "id" => "menu-ccm-sectionend-alerting",
        "order" => 406,
        "opts" => ""
    ));

    //
    // Templates
    //

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Templates"),
        "id" => "menu-ccm-section-templates",
        "order" => 500,
        "opts" => array(
            "id" => "templates",
            "expanded" => false
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Host Templates"),
        "id" => "menu-ccm-hosttemplates",
        "order" => 501,
        "opts" => array(
            "icon" => "fa-sticky-note-o",
            "href" => $ccm_home.'?cmd=view&type=hosttemplate'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Service Templates"),
        "id" => "menu-ccm-servicetemplates",
        "order" => 502,
        "opts" => array(
            "icon" => "fa-sticky-note-o",
            "href" => $ccm_home.'?cmd=view&type=servicetemplate'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Contact Templates"),
        "id" => "menu-ccm-contacttemplates",
        "order" => 503,
        "opts" => array(
            "icon" => "fa-sticky-note-o",
            "href" => $ccm_home.'?cmd=view&type=contacttemplate'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "title" => "",
        "id" => "menu-ccm-sectionend-templates",
        "order" => 504,
        "opts" => ""
    ));

    //
    // Commands
    //

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Commands"),
        "id" => "menu-ccm-section-commands",
        "order" => 600,
        "opts" => array(
            "id" => "commands",
            "expanded" => false
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => "Commands",
        "id" => "menu-ccm-commands",
        "order" => 601,
        "opts" => array(
            "icon" => 'fa-terminal',
            "href" => $ccm_home.'?cmd=view&type=command'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "title" => "",
        "id" => "menu-ccm-sectionend-commands",
        "order" => 602,
        "opts" => ""
    ));

    //
    // Advanced
    //

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Advanced"),
        "id" => "menu-ccm-section-advanced",
        "order" => 700,
        "opts" => array(
            "id" => "advanced",
            "expanded" => false
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Host Dependencies"),
        "id" => "menu-ccm-hostdependencies",
        "order" => 701,
        "opts" => array(
            "icon" => "fa-list-ul",
            "href" => $ccm_home."?cmd=view&type=hostdependency"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Service Dependencies"),
        "id" => "menu-ccm-servicedependencies",
        "order" => 702,
        "opts" => array(
            "icon" => "fa-list-ul",
            "href" => $ccm_home."?cmd=view&type=servicedependency"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "title" => "",
        "id" => "menu-ccm-sectionend-advanced",
        "order" => 705,
        "opts" => ""
    ));

    //
    // Tools
    //

    add_menu_item(MENU_CCM, array(
        "type" => "menusection",
        "title" => _("Tools"),
        "id" => "menu-ccm-section-tools",
        "order" => 800,
        "opts" => array(
            "id" => "tools",
            "expanded" => false
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Static Config Editor"),
        "id" => "menu-ccm-staticconfigurations",
        "order" => 801,
        "opts" => array(
            "icon" => 'fa-pencil-square-o',
            "href" => $ccm_home."?cmd=admin&type=static"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Import Config Files"),
        "id" => "menu-ccm-importconfigfiles",
        "order" => 803,
        "opts" => array(
            "icon" => 'fa-upload',
            "href" => $ccm_home."?cmd=admin&type=import"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Config File Management"),
        "id" => "menu-ccm-configfilemanage",
        "order" => 804,
        "opts" => array(
            'icon' => 'fa-file',
            "href" => $ccm_home.'?cmd=apply'
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "menusectionend",
        "title" => "",
        "id" => "menu-ccm-sectionend-tools",
        "order" => 810,
        "opts" => ""
    ));

    //
    // Config Manager Admin
    //

    // Only allow full admins to view the CCM Admin section
    if ($separate_ccm_login == 1 || is_admin()) {

        add_menu_item(MENU_CCM, array(
            "type" => "menusection",
            "title" => _("CCM Admin"),
            "id" => "menu-ccm-section-admin",
            "order" => 900,
            "opts" => array(
                "id" => "nagiosqladmin",
                "expanded" => false
            )
        ));

        add_menu_item(MENU_CCM, array(
            "type" => "link",
            "title" => _("Manage Users"),
            "id" => "menu-ccm-manageconfigaccess",
            "order" => 901,
            "opts" => array(
                "icon" => 'fa-wrench',
                "href" => $ccm_home."?cmd=admin&type=user"
            )
        ));

        add_menu_item(MENU_CCM, array(
            "type" => "link",
            "title" => _("Settings"),
            "id" => "menu-ccm-configmanagersettings",
            "order" => 903,
            "opts" => array(
                "icon" => 'fa-cog',
                "href" => $ccm_home."?cmd=admin&type=settings"
            )
        ));

        add_menu_item(MENU_CCM, array(
            "type" => "link",
            "title" => _("Core Configs"),
            "id" => "menu-ccm-coremainconfig",
            "order" => 904,
            "opts" => array(
                "icon" => 'fa-book',
                "href" => $ccm_home."?cmd=admin&type=corecfg"
            )
        ));

        add_menu_item(MENU_CCM, array(
            "type" => "link",
            "title" => _("Audit Log"),
            "id" => "menu-ccm-configmanagerlog",
            "order" => 905,
            "opts" => array(
                "icon" => 'fa-bars',
                "href" => $ccm_home."?cmd=admin&type=log",
            )
        ));

        add_menu_item(MENU_CCM, array(
            "type" => "menusectionend",
            "title" => "",
            "id" => "menu-ccm-sectionend-admin",
            "order" => 920,
            "opts" => ""
        ));
    }
}

function session_tracking()
{
    global $ccmDB;

    $session_id = hash('sha256', session_id());
    $type = $ccmDB->escape_string(grab_request_var('type', ''));
    $id = intval(grab_request_var('id', 0));
    $ip = $_SERVER['REMOTE_ADDR'];

    if (empty($type) || empty($id)) {
        return;
    }

    $user_id = intval($_SESSION["user_id"]);
    $sql = "SELECT * FROM `tbl_session` WHERE `user_id` = ".$user_id." AND `session_id` = '".$session_id."' AND `obj_id` = ".$id." AND `type` = '".$type."';";
    $sessions = $ccmDB->query($sql);
    
    if (empty($sessions)) {

        // Record current session
        $t = time();
        $sql = "INSERT INTO `tbl_session` (`user_id`, `type`, `obj_id`, `started`, `last_updated`, `session_id`, `ip`, `active`) VALUES (".$user_id.", '".$type."', ".$id.", ".$t.", ".$t.", '".$session_id."', '".$ip."', 1);";
        $ccmDB->query($sql, false);
        $ccm_session_id = $ccmDB->get_last_id();

    } else {

        // Update current session
        $ccm_session_id = $sessions[0]['id'];
        foreach ($sessions as $s) {
            $sql = "UPDATE `tbl_session` SET `last_updated` = '".time()."' WHERE `id` = ".$s['id'].";";
            $ccmDB->query($sql, false);
        }

    }

    // If there are sessions older than 5 min let's delete them
    $timeout = get_option('ccm_page_lock_timeout', 300);
    $del_time = time() - $timeout;
    $sql = "DELETE FROM `tbl_session` WHERE `last_updated` < ".$del_time.";";
    $ccmDB->query($sql, false);

    // Look for any locks that exist or not
    $sql = "SELECT l.id as id FROM `tbl_session_locks` AS l LEFT JOIN `tbl_session` AS s ON s.id = l.sid WHERE s.session_id IS null;";
    $res = $ccmDB->query($sql);
    if (!empty($res)) {
        foreach ($res as $r) {
            $ccmDB->query("DELETE FROM `tbl_session_locks` WHERE id = ".$r['id'], false);
        }
    }

    // Check if there are any valid locks
    // and if there aren't we can create our own
    $sql = "SELECT * FROM `tbl_session_locks` AS l LEFT JOIN `tbl_session` AS s ON s.id = l.sid WHERE l.obj_id = ".$id." AND l.type = '".$type."';";
    $res = $ccmDB->query($sql);
    if (empty($res)) {
        $sql = "INSERT INTO `tbl_session_locks` (`sid`, `obj_id`, `type`) VALUES (".$ccm_session_id.", ".$id.", '".$type."');";
        $ccmDB->query($sql, false);
    }

    return $ccm_session_id;
}

function session_get_lock()
{
    global $ccmDB;

    $type = $ccmDB->escape_string(grab_request_var('type', ''));
    $id = intval(grab_request_var('id', 0));

    // Grab user_id if session exists (backend never has a full session)
    $user_id = 0;
    if (array_key_exists('user_id', $_SESSION)) {
        $user_id = intval($_SESSION["user_id"]);
    }

    if (empty($type) || empty($id)) {
        return false;
    }

    // Check if the current page is locked or not
    $sql = "SELECT *, l.id AS id FROM `tbl_session_locks` AS l LEFT JOIN `tbl_session` AS s ON l.sid = s.id WHERE l.obj_id = ".$id." AND l.type = '".$type."' AND s.user_id != ".$user_id." LIMIT 1;";
    $res = $ccmDB->query($sql);
    if (!empty($res)) {
        $lock = $res[0];

        $username = get_user_attr($lock['user_id'], 'username');
        $lock['username'] = $username;
        return $lock;
    }

    return false;
}