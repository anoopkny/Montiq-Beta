<?php
//
//  Nagios Network Analyzer - Adding Host/Services Dynamically
//  ---------------------------------
//  This file allows NNA to contact the Nagios XI server and use the NRDP token to authenticate
//  and create a new host and service in the IMPORT directory of /usr/local/nagios/etc which will
//  then be added into the Nagios Core config once the configuration is applied.
//
//  Note: Nagios Core is NOT restarted after the config file has been added from the NNA side
//  and the user must log into Nagios XI and apply configuration manually.
//
//  With Nagios Core 4, should we allow the creation of a new host/service to restart core? -JO
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
require_once(dirname(__FILE__) . '/../ccm/includes/applyconfig.inc.php');
require_once('/usr/local/nrdp/server/config.inc.php');

$REMOTEADDR = $_SERVER['REMOTE_ADDR'];

if (!defined('NAGIOSNA_LOGFILE')) {
    define('NAGIOSNA_LOGFILE', get_root_dir() . '/var/components/nagiosna.log');
}

if (!defined('NAGIOSNA_LOGLEVEL')) {
    define('NAGIOSNA_LOGLEVEL', 0);
}

define("DEBUG", 0);
define("INFO", 1);
define("WARNING", 2);
define("ERROR", 3);

// Create config if it doesn't exist already
//create_config_if_necessary();

// Check ACL
if (!authorize_incoming_connection()) {
    return_as_json(array("error" => "Forbidden. Not allowed.",
        "code" => 1,
        "ip" => $_SERVER['REMOTE_ADDR']));
    die();
}

function nalog($message, $level = DEBUG)
{
    switch ($level) {
        case 0:
            $description = 'DEBUG';
            break;
        case 1:
            $description = 'INFO';
            break;
        case 2:
            $description = 'WARNING';
            break;
        case 3:
            $description = 'ERROR';
            break;
        default:
            $description = 'UNKNOWN';
            break;
    }

    if (LOGLEVEL <= $level) {
        $datestamp = date("m/d/Y H:i:s");
        $entry = "{$datestamp} {$description} - {$message}" . PHP_EOL;
        file_put_contents(NAGIOSNA_LOGFILE, $entry, FILE_APPEND);
    }
}

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// Do the requesting
route_request();

function route_request()
{

    $mode = grab_request_var("mode", "");
    nalog("Received command for {$mode}", DEBUG);

    switch ($mode) {
        case 'getnrdptoken':
            return_nrdp_token();
            break;
        case 'create':
            create_new_service_object();
            break;
        case 'apply':
            reconfigure_nagios();
            break;
        default:
            $json = array("error" => "No request was made.", "code" => 2);
            return_as_json($json);
            break;
    }
}

function authorize_incoming_connection()
{
    $authorized = false;
    global $REMOTEADDR;

    $serialized_instances = get_option("nagiosna_component_instances");

    //~ If the config didn't exist or hasn't been defined, then nobody is getting in
    if (empty($serialized_instances)) {
        $authorized = false;
    } else {
        $instances = unserialize($serialized_instances);

        // Loop through the instances and add accessable addresses
        $ok_addresses = array();
        foreach ($instances as $instance) {
            if ($instance['enabled']) {
                $enabled_addresses[] = $instance['address'];
            }
        }

        // Check if the IP is a known authenticated IP
        $enabled_ip = false;
        foreach ($enabled_addresses as $address) {
            if ($address === $REMOTEADDR) {
                $enabled_ip = true;
                break;
            }
        }

        //~ If the enabled box is check and the IP is in the Allowed Hosts, allow them in
        if ($enabled_ip === true) {
            $authorized = true;
        }
    }
    return $authorized;
}

function valid_nrdp_tokens_available($getit = false)
{
    global $cfg;
    $tokens = grab_array_var($cfg, "authorized_tokens", array());
    foreach ($tokens as $token) {
        if (!empty($token)) {
            if ($getit)
                return $token;
            else
                return true;
        }
    }
    return false;
}

function return_as_json($assoc_array)
{
    $my_json = json_encode($assoc_array);
    header('Content-type: application/json');
    print $my_json;
}

function return_nrdp_token()
{
    nalog("Returning nrdp token: " . valid_nrdp_tokens_available(true));
    $json_array = array('token' => valid_nrdp_tokens_available(true));
    return_as_json($json_array);
}

function create_new_service_object()
{

    $hostname = grab_request_var("hostname", "");
    $servicename = grab_request_var("servicename", "");
    $hosttemplate = grab_request_var("hosttemplate", "generic-host");
    $servicetemplate = grab_request_var("servicetemplate", "generic-service");
    if ($hostname && $servicename) {
        make_host_service_file($hostname, $hosttemplate, $servicename, $servicetemplate);
        return_as_json(array("success" => "successfully wrote config"));
    } else {
        nalog("Not making host and service file because hostname and/or service name was empty.", INFO);
        return_as_json(array("error" => '', "exit" => 0));
    }
}

function make_host_service_file($hostname, $hosttemplate, $servicename, $servicetemplate)
{
    $import_host_file = "/usr/local/nagios/etc/import/{$hostname}.cfg";
    $nc = fopen($import_host_file, "w");
    nalog("Writing to config file {$import_host_file}", DEBUG);
    $host_declaration = <<<EOT
define host {
    host_name   {$hostname}
    alias       {$hostname}
    check_period 24x7
    notification_period 24x7
    notification_interval 60
    address     localhost
    use         {$hosttemplate}
    active_checks_enabled 0
    passive_checks_enabled 1
    check_command check_dummy!0
    max_check_attempts 5
    
}

EOT;

    $service_declaration = <<<EOT
define service {
    host_name           {$hostname}
    use                 {$servicetemplate}
    service_description {$servicename}
    active_checks_enabled 0
    passive_checks_enabled 1
    check_command check_dummy!0
}

EOT;

    if (!host_exists($hostname)) {
        nalog("Writing: {$host_declaration}", DEBUG);
        fwrite($nc, $host_declaration);
    }
    if (!service_exists($hostname, $servicename)) {
        nalog("Writing:" . PHP_EOL . "{$service_declaration}", DEBUG);
        fwrite($nc, $service_declaration);
    }
    fclose($nc);
}

function exec_shell_cmd($shell)
{
    exec($shell, $retcode);
    return $retcode;
}

function esa($cmd)
{
    return escapeshellarg($cmd);
}

?>
