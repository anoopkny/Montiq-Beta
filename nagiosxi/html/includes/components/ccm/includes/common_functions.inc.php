<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: common_functions.inc.php
//  Desc: Bunch of functions in the CCM that need to be cleaned up and possibly moved
//        into different functions based on usage.
//

/**
 * Displays the default home page message for the CCM
 *
 * @return string $html HTML content for default page that is displayed
 */
function default_page()
{
    if (ENVIRONMENT == "nagiosxi") {
        require_once("ccm-splash.inc.php");
    } else {
        $html = "
<div id='contentWrapper'>
    <h3>Nagios CCM ".CCMVERSION."</h3>
    <ul>
        <li>"._("The Nagios CCM (Core Config Manager) is a front-end interface for managing Nagios object configuration files.")."</li> 
        <li>"._("Changes made in the CCM are written to a database, and when configurations are applied, they are written to physical configuration files.")."</li>
        <li>"._("All configuration changes need to be verified for logic and syntax errors before Nagios can utilize them and restart successfully.")."</li>
        <li>"._("No changes will take place until the configurations are written to file and Nagios is restarted with a valid configuration.")."</li>
        <li>"._("Configurations from a Nagios Core install can be imported via the 'Import Config Files' page in the Tools section.")."</li>
    </ul>
    <p style='color: #AAA; position: fixed; bottom: 10px;'>"._("Nagios CCM was based on NagiosQL 3.0.3")."</p>
</div>";
    }

    return $html;
}


/**
 * Security measure to protect against form spoofing for commands that modify configs 
 *
 * @param string $cmd REFERENCE variable to string command 
 * @param string $token token submitted from the ccm form
 *
 * @return null modifies referenced $cmd variable if token is bad 
 */
function verify_token(&$cmd, $token)
{
    //commands that require a token check 
    $required = array('delete','copy','delete_multi','copy_multi','add','modify','insert','info','download'); 
    if(in_array($cmd,$required)) {
        //echo "SESSION: ".$_SESSION['token']." TOKEN: ".$token ;
        if($token != $_SESSION['token']){
             $cmd='login'; 
             $_SESSION['loginMessage'] = _("Unauthorized action! Login required."); 
             unset($_SESSION['ccm_username']); 
             unset($_SESSION['ccm_login']); 
        }
    }
}


/**
 * Security measure to protect against form spoofing for commands that modify configs 
 *
 * @param string $cmd REFERENCE variable to string command 
 * @param string $type nagios object type (host,service,contact, etc) 
 *
 * @return null modifies referenced $cmd variable if type is bad 
 */
function verify_type(&$cmd, $type)
{
    // Valid $types 
    $matches = array('','host','service','hosttemplate','servicetemplate',
                        'contact','contactgroup','contacttemplate','hostgroup',
                        'servicegroup', 'contactgroup','command','timeperiod',
                        'hosttemplate','servicetemplate','serviceescalation','hostescalation',
                        'servicedependency','hostdependency',
                        'writeConfig','verify','restart','applyfull','delete',
                        'import','corecfg','cgicfg','user',
                        'password','log','settings','bulk','static',
                        );

    // Bail to home page if a bad type is entered (hacks)         
    if(!in_array(trim($type),$matches)) $cmd = 'default';
}


/**
 * Used for preloading checkboxes on CCM forms, returns or prints checked="checked" based on $return  
 *
 * @param string $tbl_val checks against a few special form/table values that have multiple values in single field
 * @param string $form_val checks to see if the $FIELDS[$form_val] array item is set
 * @param bool $return output will be printed unless this is set to true 
 *
 * @return string checked='checked' 
 */
function check($tbl_val, $form_val, $return=false)
{
    global $FIELDS;
    if ($tbl_val == '') { return; }

    // Check for multiple values in single field 
    $exceptions = array('notification_options','stalking_options',
                        'escalation_options','flap_detection_options',
                        'host_notification_options','service_notification_options',
                        'execution_failure_criteria','notification_failure_criteria');
                        
    if (in_array($tbl_val, $exceptions)) {
        $parts = explode(',', $FIELDS[$tbl_val]);
        foreach ($parts as $part) {
            if (trim($part == $form_val)) {
                if ($return == false ) {
                    echo 'checked="checked"';
                } else {
                    return 'checked="checked"';
                }
            }    
        }
    }

    if (trim($FIELDS[$tbl_val]) == trim($form_val)) {         
        if ($return == false) {
            echo 'checked="checked"';
        } else {
            return 'checked="checked"';
        }
    }    
}


/**
 * Checks to see if a select list item should be preselected, prints selected='selected' upon true 
 *
 * @param string $tbl_val the DB table's field name
 * @param string/int $form_val the value of the select option 
 *
 * @return null
 */
function ccm_is_selected($tbl_val, $form_val)
{
    global $FIELDS;
    if (!isset($FIELDS[$tbl_val])) { return; } 
    
    if (is_array($FIELDS[$tbl_val])) {
        if (in_array($form_val, $FIELDS[$tbl_val])) {
            echo " selected='selected'";
        }
    } else { 
        if (trim($FIELDS[$tbl_val]) == trim($form_val)) {
            echo " selected='selected'";
        }
    }
}


/**
 * Handles null value from checkboxes for insertion into DB 
 *
 * @param string $strKey input from form. 
 *
 * @return string $strKey potentially modifed string value 
 */
function checkNull($strKey) {
    // If the transfer is null
    if ($strKey == '' || $strKey == 'NULL') {
        return 'NULL';
    }
    if (strtoupper($strKey) == "NULL") {
        return("-1");
    }
    return($strKey);
}


/**
 * Simple wrapper function for main ccm_table function, turns a bool into a string for table display 
 *
 * @param int $int (0 | 1) active or inactive?
 *
 * @return string 'Yes' | 'No' 
 */
function is_active($int, $id, $name)
{
    if ($int == 1) {
        return "<a href='javascript:actionPic(\"deactivate\",\"{$id}\",\"{$name}\");' title='Deactivate'>"._('Yes')."</a>";
    } else {
        return "<strong><a class='urgent' href='javascript:actionPic(\"activate\",\"{$id}\",\"{$name}\");' title='Activate'>"._('No')."</a></strong>";
    }
}


/**
 * Debugging function to dump sql results into a human readable formar into the browser 
 */ 
function sql_output($query='')
{
    global $myDataClass;
    global $myConfigClass;
    global $myDBClass;
    
    if ($query == '') {
        echo "<p>QUERY INFO: ".mysql_info()."</p>";
    } else {
        echo "<p>QUERY RUN: $query</p>"; 
    }
    echo "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";  
}

/**
 * Function to dump a formatted array into the webbrowser
 *
 * @param mixed $array 
 */
function ccm_array_dump($array) 
{   
    echo "<pre>".print_r($array,true)."</pre>";
}

/**
 * Debugging function to print $_REQUEST variables in a readable format in the browser 
 */
function dump_request()
{
    foreach ($_REQUEST as $type => $value) {
        echo "$type => $value <br />";
    }
}

/**
 * Displays a login div with the $_SESSION['loginMessage'] variable in it. 
 */ 
function do_login_div()
{
    if (isset($_SESSION['loginMessage'])) {
        echo "<div id='loginDiv'><span class='deselect'>".$_SESSION['loginMessage']."</span></div>";  
    }
}


/**
 * Wrapper function that only prints a value if it exists
 *
 * @param mixed $value the form value to check for
 * @param bool $print defaults to printing output, else returns it 
 *
 * @return null | string $output 
 */ 
function val($value, $print=true)
{
    $output = (isset($value) ? $value : "");
    if ($print == false) {
        return $output;
    } else {
        print $output;
    }
}


/**
 * Get names for table, name, description based on object $type 
 *
 * @param string $type nagios object type
 *
 * @return mixed $array($table,$name,$description) 
 */
function get_table_and_fields($type) {

    // Determine SQL fields that we need to grab for the main table      
    switch($type)
    {
        case 'host':
        case 'hostgroup':
        case 'servicegroup':
        case 'contact':
        case 'contactgroup':
        case 'timeperiod':
            $table = 'tbl_'.$type;
            $typeName = $type.'_name';
            $typeDesc = 'alias';
            break;
            
        case 'service':
            $table = 'tbl_service';
            $typeName = 'config_name';
            $typeDesc = 'service_description';
            break;
        
        case 'command':
            $table = 'tbl_command';
            $typeName = 'command_name';
            $typeDesc = 'command_line';
            break;
            
        case 'servicetemplate':
        case 'hosttemplate';
        case 'contacttemplate';
            $table = 'tbl_'.$type;
            $typeName = 'template_name';
            $typeDesc = 'alias';
            if ($type == 'servicetemplate') { $typeDesc = 'display_name'; } 
            break;

        case 'serviceescalation':
            $table = 'tbl_'.$type;
            $typeName = 'config_name';
            $typeDesc = 'service_description';
            break;
            
        case 'hostescalation':
            $table = 'tbl_'.$type;
            $typeName = 'config_name';
            $typeDesc = 'host_name';
            break;
            
        case 'hostdependency':
            $table = 'tbl_'.$type;
            $typeName = 'config_name';
            $typeDesc = 'host_name';
            break;
            
        case 'servicedependency':
            $table = 'tbl_'.$type; 
            $typeName = 'config_name';
            $typeDesc = 'service_description';
            break;
            
        case 'user':
            $table = 'tbl_'.$type;
            $typeName = 'username';
            $typeDesc = 'alias';
            break;
        
        default:
            echo '<div class="error" style="width:300px; margin:10px auto; text-align:center;">'._('ROUTE VIEW').': '._('Invalid object type').': '.$type.' '._('specified').'</div>'; 
            echo default_page();
            die();           
    }
            
    return array($table, $typeName, $typeDesc);   
}


/////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////the following functions are written by Ethan Galstad, Nagios Enterprises 
///////////////////////////////////////////////////////////////////////////////////////////////////
/**
 *
 * Cleaner function that is run on every REQUEST variable.
 *
 * Designed to work with array_walk_recursive. http://php.net/manual/en/function.array-walk-recursive.php
 *
 * Takes an input parameter and changes that parameter in place.
 *
 * @param reference item - String to be cleaned and sanitized for general application
 * @param reference key - Unused, required for use with array_walk_recursive.
 *
 * @return None. Returns item by reference. 
  *
 */
function request_var_cleaner(&$item) {
    $item = mysql_real_escape_string($item);
}

/**
 * grabs and preprocesses all GET and POST request variables
 * @author Ethan Galstad
 *
 * Escapes and sanitizes all strings coming in through $_GET and $_POST. Uses
 * the above `request_var_cleaner` on each individual leaf on the $_REQUEST
 * tree.
 *
 * @param bool   $preprocess
 * @param string $type
 * @return None. Returns $request via reference.
*/
function ccm_grab_request_vars($preprocess=true,$type=""){
    global $escape_request_vars;
    global $request;
    // do we need to strip slashes?
    $strip=false;
    if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!= "off"))) {
        $strip=true;
    }
    
    $request=array();
    
    if($escape_request_vars == true) {
        $request = $_REQUEST;
        if(!array_walk_recursive($request, 'request_var_cleaner')) {
            trigger_error('Array walk failed in common_functions.inc.php, in function ccm_grab_request_vars.');
        }
    }
    else {
        trigger_error('Not cleaning any of these arguments because $escape_request_vars is not set to false in the session.inc.php');
    }

        
    // strip slashes - we escape them later in sql queries
    if($strip==true){
        foreach($request as $var => $val)
            $request[$var]=stripslashes($val);
        }
    
}


/**
 * @param $item
 */
function decode_input_var(&$item) {
    $item = htmlentities($item, ENT_NOQUOTES);
}

/**
*   grabs and preprocess a single GET and POST request variable
*   @author Ethan Galstad 
*   @param string $varname the GET or POST variable to grab
*   @param mixed $default set an optional default value for the request var 
*   @return mixed returns either the value found or the default value 
*/
function ccm_grab_request_var($varname, $default="", $decode=false){
    global $request;

    $v=$default;
    if(isset($request[$varname]))
        $v=$request[$varname];

    if($decode) {
        if(is_array($v)) {
            array_walk_recursive($v, 'decode_input_var');
        }
        else {
            decode_input_var($v);
        }
    }
    
    //echo "VAR $varname = $v<BR>";

    return $v;
}

/**
*   grabs and preprocess array variable
*   @author Ethan Galstad 
*   @param string $varname the array index to grab 
*   @param mixed $default set an optional default value for the request var 
*   @return mixed returns either the value found or the default value 
*/
// gets value from array using default
function ccm_grab_array_var($arr,$varname,$default=""){
    global $request;
    
    $v=$default;
    if(is_array($arr)){
        if(array_key_exists($varname,$arr))
            $v=$arr[$varname];
        }
    return $v;
    }
    
    
function ccm_decode_request_vars(){
    global $request;
    global $request_vars_decoded;
    
    $newarr=array();
    foreach($request as $var => $val){
        $newarr[$var]=ccm_grab_request_var($var);
        }
        
    $request_vars_decoded=true;
        
    $request=$newarr;
}

//These function are for compatibility when moving out of Nagios XI
if (!function_exists('encode_form_val')){
    /**
     * @param $rawval
     *
     * @return string
     */
    function encode_form_val($rawval){
        return htmlentities($rawval, ENT_COMPAT, 'UTF-8');
    }
}
if (!function_exists('grab_array_var')){
    /**
     * @param        $arr
     * @param        $varname
     * @param string $default
     *
     * @return string
     */function grab_array_var($arr,$varname,$default=""){
        global $request;
        
        $v=$default;
        if(is_array($arr)){
            if(array_key_exists($varname,$arr))
                $v=$arr[$varname];
            }
        return $v;
        }
}
    
if (!function_exists('decode_request_vars')){   
    function decode_request_vars(){
        global $request;
        global $request_vars_decoded;
        
        $newarr=array();
        foreach($request as $var => $val){
            $newarr[$var]=ccm_grab_request_var($var);
            }
            
        $request_vars_decoded=true;
            
        $request=$newarr;
        }
}

if (!function_exists('grab_request_var')){
    /**
     * @param        $varname
     * @param string $default
     * @param bool   $decode
     *
     * @return array|string
     */function grab_request_var($varname, $default="", $decode=false){
        global $request;

        $v=$default;
        if(isset($request[$varname]))
            $v=$request[$varname];

        if($decode) {
            if(is_array($v)) {
                array_walk_recursive($v, 'decode_input_var');
            }
            else {
                decode_input_var($v);
            }
        }
        
        //echo "VAR $varname = $v<BR>";

        return $v;
    }
}

// generates a random alpha-numeric string (password or backend ticket)
// @author Ethan Galstad 
/**
 * @param int $len
 *
 * @return string
 */
function ccm_random_string($len=6){
    $chars="023456789abcdefghijklmnopqrstuv";
    $rnd="";
    $charlen=strlen($chars);

    srand((double)microtime()*1000000);
    
    for($x=0;$x<$len;$x++){
        $num=rand()%$charlen;
        $ch=substr($chars,$num,1);
        $rnd.=$ch;
        }
        
    return $rnd;
    }

/**
 * @param $type
 * @param $msg
 *
 * @return int
 */
function audit_log($type,$msg) {
    global $CFG;
    // AUDIT LOG TYPES
    /*
    define("AUDITLOGTYPE_NONE",0);
    define("AUDITLOGTYPE_ADD",1); // adding objects /users
    define("AUDITLOGTYPE_DELETE",2); // deleting objects / users
    define("AUDITLOGTYPE_MODIFY",4); // modifying objects / users
    define("AUDITLOGTYPE_MODIFICATION",4); // modifying objects / users
    define("AUDITLOGTYPE_CHANGE",8); // changes (reconfiguring system settings)
    define("AUDITLOGTYPE_SYSTEMCHANGE",8); // changes (reconfiguring system settings)
    define("AUDITLOGTYPE_SECURITY",16);  // security-related events
    define("AUDITLOGTYPE_INFO",32); // informational messages
    define("AUDITLOGTYPE_OTHER",64); // everything else 
    */
    $send = $CFG['audit_send']; 
    $user = ccm_grab_array_var($_SESSION,'ccm_username','none'); 
    $source = "Nagios CCM"; 
    $msg = escapeshellcmd(strip_tags($msg)); 
    $type = escapeshellcmd($type);
    
    if(file_exists($send) && is_executable($send)){
        $cmd = $send." --message='{$msg}' --type={$type} --user='{$user}' --source='{$source}' ";
        $output = exec($cmd,$fulloutput,$code);
        //echo "CMD: $cmd <br />OUT:".$output."<br />";
        return $code; 
    }   
    else
        return 1; 
    

}

///////////////////////////////////////////////////////////////
//      LANGUAGE FUNCTIONS
//////////////////////////////////////////////////////////////
function ccm_init_language()
{
    global $CFG;
    global $AUTH; 
    global $ccmDB; 
    
    $loginSubmitted = ccm_grab_request_var('loginSubmitted', false);

    ccm_get_languages(); 

    // Read language file (always read English first in case translators missed something)
    $default_language = ccm_grab_array_var($CFG, 'default_language', 'en_US');
    if ($default_language == 'en') {
        $default_language = 'en_US'; 
    }
    $session_language = $default_language;

    // Authenticated logins will set the locale to the specific user
    if ($loginSubmitted && $AUTH) {
        $sql = "SELECT locale from tbl_user WHERE username='".$_SESSION['ccm_username']."'";
        $res = $ccmDB->query($sql); 
        foreach ($res as $r) {
            if (isset($r['locale']) && in_array($r['locale'], $CFG['languages'])) {
                $session_language = $r['locale']; 
            }   
        }   
        
        if ($session_language == 'en') {
            $session_language = 'en_US'; 
        }
        
        if (ccm_set_language($session_language) == 1) {
            trigger_error('Unable to set CCM language: '.$session_language, E_USER_NOTICE); 
        }
        
        // Update the login message with the new language string
        $_SESSION['loginMessage'] = _('Logged in as: ').$_SESSION['ccm_username']." <a href='index.php?cmd=logout'>"._('Logout')."</a>";
        return;
    }

    if (ENVIRONMENT == "nagiosxi") {
        $separate_ccm_login = get_option("separate_ccm_login", 0);
        if ($separate_ccm_login == 0) {
            if (isset($_SESSION['language'])) {
                if (isset($_SESSION['ccm_language'])) {
                    if ($_SESSION['ccm_language'] != $_SESSION['language']) {
                        $_SESSION['ccm_language'] = $_SESSION['language'];
                    }
                } else {
                    $_SESSION['ccm_language'] = $_SESSION['language'];
                }
            }
        }
    }

    if ($AUTH && isset($_SESSION['ccm_language'])) {
        ccm_set_language($_SESSION['ccm_language']);
    }
}

function ccm_get_languages()
{
    global $CFG;
    
    $dirs = scandir(dirname(__FILE__).'/../../../lang/locale');
    $base = dirname(__FILE__).'/../../../lang/locale/'; 

    // Add directories to language options 
    foreach ($dirs as $dir) {
        if (is_dir($base.$dir) && strpos($dir, '.') === false) {
            $newlang = htmlentities(utf8_encode($dir), ENT_QUOTES, 'UTF-8'); 
            $CFG['languages'][$newlang] = $newlang;
        }
    }

    return $CFG['languages'];
}

/**
 * @param $language
 */
function ccm_set_language($language)
{
    $dirs = scandir(dirname(__FILE__).'/../../../lang/locale');
    $base = dirname(__FILE__).'/../../../lang/locale/'; 
    
    // Only set _() locale if we have a language file
    if(!file_exists($base.$language)) {
        return(1); 
    }

    // Set session language
    $_SESSION["ccm_language"] = $language;
    
    // Gettext support 
    setlocale(LC_MESSAGES, $language, $language.'utf-8', $language.'utf8', "en_GB.utf8");
    putenv("LANG=".$language);

    // Non-English numeric formats will turn decimals to commas and mess up all kinds of stuff
    setlocale(LC_NUMERIC, 'C');
    
    // Bind text domains
    bindtextdomain($language, $base);
    bind_textdomain_codeset($language, 'UTF-8');
    textdomain($language);
}

// Gets the full english-looking title (can be translated)
// p = plural or not (true or false)
function ccm_get_full_title($type, $p = false) {
    if ($p) {
        $titles = array(
            'host' => _('Hosts'),
            'service' => _('Services'),
            'hosttemplate' => _('Host Templates'),
            'servicetemplate' => _('Service Templates'),
            'contact' => _('Contacts'),
            'contactgroup' => _('Contact Groups'),
            'contacttemplate' => _('Contact Templates'),
            'hostgroup' => _('Host Groups'),
            'servicegroup' => _('Service Groups'),
            'command' => _('Commands'),
            'timeperiod' => _('Time Periods'),
            'serviceescalation' => _('Service Escalations'),
            'hostescalation' => _('Host Escalations'),
            'servicedependency' => _('Service Dependencies'),
            'hostdependency' => _('Host Dependencies'),
            'user' => _('Users'),
            'template' => _('Templates'),
            'parent' => _('Parents')
        );
    } else {
        $titles = array(
            'host' => _('Host'),
            'service' => _('Service'),
            'hosttemplate' => _('Host Template'),
            'servicetemplate' => _('Service Template'),
            'contact' => _('Contact'),
            'contactgroup' => _('Contact Group'),
            'contacttemplate' => _('Contact Template'),
            'hostgroup' => _('Host Group'),
            'servicegroup' => _('Service Group'),
            'command' => _('Command'),
            'timeperiod' => _('Time Period'),
            'serviceescalation' => _('Service Escalation'),
            'hostescalation' => _('Host Escalation'),
            'servicedependency' => _('Service Dependency'),
            'hostdependency' => _('Host Dependency'),
            'user' => _('User'),
            'template' => _('Template'),
            'parent' => _('Parent')
        );
    }
    
    if (isset($titles[$type])) {
        return $titles[$type];
    }
}


/**
 * function to return value of a directive in specified configuration file
 *
 * @param $option_name string item to look for in $config_file
 *
 * @return string the value of $option_name, as present in nagios.cfg, or false if not found
 */
function ccm_get_nagioscore_config_option($option_name)
{
    global $myConfigClass;
    $myConfigClass->getConfigData("nagiosbasedir", $nagios_etc_dir);
    $nagios_etc_dir = "/usr/local/nagios/etc/";
    $config_file = $nagios_etc_dir . 'nagios.cfg';

    // if the main xi function for this is present, we use that:
    if (function_exists('get_nagioscore_config_option')) {
        return get_nagioscore_config_option($option_name, $config_file);
    }

    $return_value = false;

    if (@is_readable($config_file)) {

        $config_file_handle = fopen($config_file, "r");
        if ($config_file_handle) {

            while (!feof($config_file_handle)) {
                $line = fgets($config_file_handle);

                if (strpos($line, $option_name) === 0) {
                    if (preg_match("/^$option_name\s*?=\s*(.*)/", $line, $matches) === 1) {
                        if (!empty($matches[1])) {

                            $return_value = $matches[1];
                            break;
                        }
                    }
                }
            }
            fclose($config_file_handle);
        }
    }

    return $return_value;
}