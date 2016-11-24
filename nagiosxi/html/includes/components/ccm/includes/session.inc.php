<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: session.inc.php
//  Desc: Handles all the definitions and includes for the CCM. Also does the authentication
//        using the function in auth.inc.php.
//

// Version information (also used to create fresh CCS/Javascript includes)
define('CCMVERSION', '2.6.2');
define('VERSION', 262);

// Directory location constants 
define('INCDIR', BASEDIR.'includes/'); 
define('TPLDIR', BASEDIR.'page_templates/'); 
define('CLASSDIR', BASEDIR.'classes/'); 

// Define all audit log types for CCM standalone
if(!defined('AUDITLOGTYPE_NONE') || ENVIRONMENT != "nagiosxi") {
    define("AUDITLOGTYPE_NONE", 0);
    define("AUDITLOGTYPE_ADD", 1); // Adding objects / users
    define("AUDITLOGTYPE_DELETE", 2); // Deleting objects / users
    define("AUDITLOGTYPE_MODIFY", 4); // Modifying objects / users
    define("AUDITLOGTYPE_MODIFICATION", 4); // Modifying objects / users
    define("AUDITLOGTYPE_CHANGE", 8); // Changes (reconfiguring system settings)
    define("AUDITLOGTYPE_SYSTEMCHANGE", 8); // Changes (reconfiguring system settings)
    define("AUDITLOGTYPE_SECURITY", 16);  // Security-related events
    define("AUDITLOGTYPE_INFO", 32); // Informational messages
    define("AUDITLOGTYPE_OTHER", 64); // Everything else
}

// Main includes 
require_once(BASEDIR.'config.inc.php'); 
require_once(INCDIR.'common_functions.inc.php'); 
require_once(INCDIR.'language.inc.php'); 
require_once(INCDIR.'auth.inc.php'); 
require_once(INCDIR.'hidden_overlay_functions.inc.php'); 

// Updated NagiosQL classes
require_once(CLASSDIR.'config_class.php'); 
require_once(CLASSDIR.'data_class.php'); 
require_once(CLASSDIR.'mysql_class.php');
require_once(CLASSDIR.'import_class.php'); 

// New CCM classes
require_once(CLASSDIR.'Db.php');
require_once(CLASSDIR.'CCM_Menu.php'); 
require_once(CLASSDIR.'Form_class.php');

// Pear template class //XXX TODO: eventually phase this out so we don't need it anymore 
require_once($CFG['pear_include']);

// CCM main includes 
require_once(TPLDIR.'ccm_table.php');
require_once(INCDIR.'page_router.inc.php');

// Set default result limits 
define('DEFAULT_PAGELIMIT', $CFG['default_pagelimit']); 
$_SESSION['default_limit'] = DEFAULT_PAGELIMIT;

// Global classe instances
$ccmDB = new Db();
$Menu = new Main_Menu();

// Load CCM config settings
// TODO: This should be changed to "ccm_cfg" to not be confused with Nagios XI
$CFG['settings'] = array(); 
$settings = $ccmDB->query("SELECT * FROM tbl_settings;"); 
foreach ($settings as $s) { 
    $CFG[$s['category']][$s['name']] = $s['value']; 
}

// Add data to the session
// =======================
$_SESSION['SETS'] = $CFG;
$_SESSION['domain'] = 1; // Currently we only support single domain configs 
$_SESSION['pagelimit'] = $CFG['common']['pagelines']; 

// Process $_POST an $_GET variables 
$escape_request_vars = true; 
ccm_grab_request_vars(); 

// Show the menu if we are running CCM standalone
$see_menu = ccm_grab_request_var('menu', false);
if ($see_menu) { $_SESSION['menu'] = $see_menu; }
if (!isset($_SESSION['menu'])) { $_SESSION['menu'] = 'visible'; }  

// Always enable the CCM standalone menu while using the CCM with Nagios Core
if (ENVIRONMENT == 'nagioscore') {
    $_SESSION['menu'] = 'visible';
}

// Initialize base global objects
$myDataClass = new nagdata;
$myConfigClass = new nagconfig;
$myDBClass = new mysqldb; 
$myImportClass = new nagimport;

// Reference objects inside of global objects
$myDataClass->myDBClass =& $myDBClass;
$myDataClass->myConfigClass =& $myConfigClass;
$myConfigClass->myDBClass =& $myDBClass;
$myConfigClass->myDataClass =& $myDataClass;
$myImportClass->myDataClass =& $myDataClass;
$myImportClass->myDBClass =& $myDBClass;
$myImportClass->myConfigClass =& $myConfigClass;

// NagiosQL authentication
$AUTH = check_auth();

// Global unique element ID used as a counter 
$unique = 100;

// Nagios XI language settings
if (ENVIRONMENT == "nagiosxi") {
    ccm_init_language(); 
}
    
// Debug mode...
$debug = ccm_grab_request_var('debug', false);
if ($debug == 'enable') { $_SESSION['debug'] = true; }
if ($debug == 'verbose') { $_SESSION['debug'] = 'verbose'; }
if ($debug == 'disable') { unset($_SESSION['debug']); }