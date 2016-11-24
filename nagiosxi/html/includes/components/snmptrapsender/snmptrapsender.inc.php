<?php
//
// SNMP Trap Sender Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$snmptrapsender_component_name = "snmptrapsender";

snmptrapsender_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function snmptrapsender_component_init()
{
    global $snmptrapsender_component_name;
    $desc = "";

    // Check XI version
    $versionok = snmptrapsender_component_checkversion();
    if (!$versionok)
        $desc .= "<b>" . _("Error: This component requires Nagios XI 2009R1.2B or later.") . "</b>  ";

    // Check required component installation
    $installok = snmptrapsender_component_checkinstallation();
    if (!$installok) {
        $desc .= "<b>" . _("Installation Required!") . "</b>
        " . _("You must login to the server as the root user and run the following commands to complete the installation of this component") . ":<br>
        <i>cd /usr/local/nagiosxi/html/includes/components/" . $snmptrapsender_component_name . "/</i><br>
        <i>chmod +x install.sh</i><br>
        <i>./install.sh</i><br>";
    }

    $args = array(
        COMPONENT_NAME => $snmptrapsender_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Allows Nagios XI to send SNMP traps to other network management systems when host and service alerts occur.  ") . $desc,
        COMPONENT_TITLE => "SNMP Trap Sender",
        COMPONENT_VERSION => '1.5.3',
        COMPONENT_CONFIGFUNCTION => "snmptrapsender_component_config_func",
    );

    register_component($snmptrapsender_component_name, $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
//CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function snmptrapsender_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    global $snmptrapsender_component_name;

    // Initialize return code and output
    $result = 0;
    $output = "";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // defaults
            $trap_hosts = array();
            for ($x = 0; $x <= 4; $x++) {
                $trap_hosts[$x] = array(
                    "address" => "",
                    //set port to empty
                    "port" => "",
                    "tcp" => 0,
                    "community" => "public",
                    "downtime" => 0,
                );
            }

            $settings_raw = get_option("snmptrapsender_component_options");
            if ($settings_raw == "") {
                $settings = array(
                    "enabled" => 0
                );
            } else
                $settings = unserialize($settings_raw);


            // initial values
            $enabled = grab_array_var($settings, "enabled", "");
            $trap_hosts = grab_array_var($settings, "trap_hosts", $trap_hosts);
            
            // trim empty lines
            foreach ($trap_hosts as $x => $sa) {
                if ($sa["address"] == "")
                    unset($trap_hosts[$x]);
            }
            
            // Add an empty row at the end ...
            $trap_hosts[] = array(
                    "address" => "",
                    "port" => "",
                    "tcp" => 0,
                    "community" => "public",
                    "downtime" => 0,
            );
            
            $trap_hosts_count = count($trap_hosts);

            // fix missing values
            
            for ($x = 0; $x < $trap_hosts_count; $x++) {
                if (!array_key_exists("hoststateid", $trap_hosts[$x]))
                    $trap_hosts[$x]["hoststateid"] = "0";
                if (!array_key_exists("servicestateid", $trap_hosts[$x]))
                    $trap_hosts[$x]["servicestateid"] = "0";
                if (!array_key_exists("statetype", $trap_hosts[$x]))
                    $trap_hosts[$x]["statetype"] = "BOTH";
                if (!array_key_exists("port", $trap_hosts[$x]))
                    $trap_hosts[$x]["port"] = "";
                if (!array_key_exists("tcp", $trap_hosts[$x]))
                    $trap_hosts[$x]["tcp"] = "";
                if (!array_key_exists("downtime", $trap_hosts[$x]))
                    $trap_hosts[$x]["downtime"] = "";
            }
            
            
            $trap_hosts_count = count($trap_hosts);
            
            $component_url = get_component_url_base($snmptrapsender_component_name);

            $output = '';

            $eventhandlersok = snmptrapsender_component_checkeventhandlers();
            if (!$eventhandlersok)
                $output .= "<font color='red'><b>WARNING:</b> " . _("Event handlers are currently disabled.  This will prevent the SNMP trap sender from working!") . "</font>";

            $output .= '
            
<h5 class="ul">' . _('Integration Settings') . '</h5>
    
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td></td>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="enabled" name="enabled" ' . is_checked($enabled, 1) . '>
                ' . _('Enable SNMP trap sender integration') . '
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Trap Hosts') . '</h5>
    
<p>
    ' . _('Specify the addresses of the hosts that SNMP traps should be sent to.  If you want to prevent traps from being sent during downtime check the checkbox for each host.') . '<br>
    ' . _('If you leave the Port field blank it will use the default port 162 and UDP protocol.  Select the checkbox to use the TCP protocol.') . '
</p>

<table class="table table-condensed table-bordered table-striped table-auto-width">
    <thead>
        <tr>
            <th>' . _('Host Address') . '</th>
            <th>' . _('Port') . '</th>
            <th>' . _('Use TCP') . '</th>
            <th>' . _('SNMP Community') . '</th>
            <th>' . _('Hosts') . '</th>
            <th>' . _('Services') . '</th>
            <th>' . _('State Type') . '</th>
            <th>' . _('Don\'t Send During Downtime') . '</th>
        </tr>
    </thead>
    <tbody>';

            for ($x = 0; $x < $trap_hosts_count; $x++) {

                $output .= '
        <tr>
            <td>
                <input type="text" size="25" name="trap_hosts[' . $x . '][address]" value="' . htmlentities($trap_hosts[$x]["address"]) . '" class="form-control">
            </td>
            <td>
                <input type="text" size="5" name="trap_hosts[' . $x . '][port]" value="' . htmlentities($trap_hosts[$x]["port"]) . '" class="form-control">
            </td>
            <td class="center">
                <input type="checkbox" id="tcp" name="trap_hosts[' . $x . '][tcp]" value="1"' . is_checked($trap_hosts[$x]['tcp'], 1) . '>
            </td>
            <td>
                <input type="text" size="15" name="trap_hosts[' . $x . '][community]" value="' . htmlentities($trap_hosts[$x]["community"]) . '" class="form-control">
            </td>
            <td>
                <select name="trap_hosts[' . $x . '][hoststateid]" class="form-control">
                    <option value="0" ' . is_selected($trap_hosts[$x]['hoststateid'], "0") . '>ALL</option>
                    <option value="1" ' . is_selected($trap_hosts[$x]['hoststateid'], "1") . '>DOWN</option>
                    <option value="-1" ' . is_selected($trap_hosts[$x]['hoststateid'], "-1") . '>NONE</option>
                </select>
            </td>
            <td>
                <select name="trap_hosts[' . $x . '][servicestateid]" class="form-control">
                    <option value="0" ' . is_selected($trap_hosts[$x]['servicestateid'], "0") . '>ALL</option>
                    <option value="2" ' . is_selected($trap_hosts[$x]['servicestateid'], "2") . '>CRITICAL</option>
                    <option value="1" ' . is_selected($trap_hosts[$x]['servicestateid'], "1") . '>WARNING</option>
                    <option value="-1" ' . is_selected($trap_hosts[$x]['servicestateid'], "-1") . '>NONE</option>
                </select>
            </td>
            <td>
                <select name="trap_hosts[' . $x . '][statetype]" class="form-control">
                    <option value="BOTH" ' . is_selected($trap_hosts[$x]['statetype'], "BOTH") . '>BOTH</option>
                    <option value="HARD" ' . is_selected($trap_hosts[$x]['statetype'], "HARD") . '>HARD</option>
                    <option value="SOFT" ' . is_selected($trap_hosts[$x]['statetype'], "SOFT") . '>SOFT</option>
                </select>
            </td>
            <td class="center">
                <input type="checkbox" id="downtime" name="trap_hosts[' . $x . '][downtime]" value="1"' . is_checked($trap_hosts[$x]['downtime'], 1) . '>
            </td>
        </tr>';
            }

            $output .= '
    </tbody>
</table>

<h5 class="ul">MIBs</h5>
    
<p>' . _('You should install the following MIBs on the trap management hosts') . ':</p>
<p>
    <a href="' . $component_url . '/mibs/NAGIOS-NOTIFY-MIB.txt">NAGIOS-NOTIFY-MIB.txt</a><br>
    <a href="' . $component_url . '/mibs/NAGIOS-ROOT-MIB.txt">NAGIOS-ROOT-MIB.txt</a><br>
</p>';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // get variables
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", ""));
            $trap_hosts = grab_array_var($inargs, "trap_hosts", "");

            // Renumber items & add a UID for each item
            $settings_new = array();
            $y = 0;
            foreach ($trap_hosts as $x => $sa) {
                if(!empty($sa["address"]))
                        $settings_new[$y++] = $sa;
            }
            $trap_hosts = $settings_new;
            
            // validate variables
            $errors = 0;
            $errmsg = array();
            if ($enabled == 1) {
            }

            // handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // save settings
            $settings = array(
                "enabled" => $enabled,
                "trap_hosts" => $trap_hosts,
            );
            set_option("snmptrapsender_component_options", serialize($settings));

            // info messages
            $okmsg = array();
            $okmsg[] = "Settings updated.";
            $outargs[COMPONENT_INFO_MESSAGES] = $okmsg;

            break;

        default:
            break;

    }

    return $output;
}


////////////////////////////////////////////////////////////////////////
// EVENT HANDLER AND NOTIFICATION FUNCTIONS
////////////////////////////////////////////////////////////////////////

register_callback(CALLBACK_EVENT_PROCESSED, 'snmptrapsender_component_eventhandler');


function snmptrapsender_component_eventhandler($cbtype, $args)
{

    echo "*** GLOBAL HANDLER (snmptrapsender)...\n";
    print_r($args);

    switch ($args["event_type"]) {
        case EVENTTYPE_STATECHANGE:
            snmptrapsender_component_handle_statechange_event($args);
            break;
        default:
            break;
    }
}


function snmptrapsender_component_handle_statechange_event($args)
{

    // the commands we run
    $service_trap_command = "/usr/bin/snmptrap -v 2c -c public 192.168.5.4 '' NAGIOS-NOTIFY-MIB::nSvcEvent nSvcHostname s \"%host%\" nSvcDesc s \"%service%\" nSvcStateID i %servicestateid% nSvcOutput s \"%serviceoutput%\"";
    $host_trap_command = "/usr/bin/snmptrap -v 2c -c public 192.168.5.4 '' NAGIOS-NOTIFY-MIB::nHostEvent nHostname s \"%host%\" nHostStateID i %hoststateid% nHostOutput s \"%hostoutput%\"";
    $meta = grab_array_var($args, "event_meta", array());
    $handler_type = grab_array_var($meta, "handler-type", "");

    // load settings
    $settings_raw = get_option("snmptrapsender_component_options");
    if ($settings_raw == "") {
        //$settings=array();
        // settings have not been configured yet...
        echo "SNMP TRAP SENDER NOT CONFIGURED!\n";
        return;
    } else
        $settings = unserialize($settings_raw);

    // are we enabled?
    $enabled = grab_array_var($settings, "enabled", "");
    if ($enabled != 1) {
        echo "SNMP TRAP SENDER NOT ENABLED! VALUE='$enabled'\n";
        return;
    }
    // print_r($meta);
    switch ($handler_type) {
        case "host":
            if (array_key_exists("trap_hosts", $settings)) {

                // loop through all trap hosts
                foreach ($settings["trap_hosts"] as $th) {
                    echo "PROCESSING:\n";

                    // get address, community and port
                    $address = grab_array_var($th, "address");
                    $community = grab_array_var($th, "community");
                    $port = grab_array_var($th, "port");

                    // only send to hosts that have address and community defined
                    if ($address != "" && $community != "") {
                        echo "PROCESSING:\n";
                        print_r($th);

                        // filters
                        if (isset($th['hoststateid']) && $th['hoststateid'] != 0) {
                            if ($meta['hoststateid'] < $th['hoststateid'] || $th['hoststateid'] == -1) {
                                echo "Host matched state filter, skipping... TRAPHANDLER STATE SETTING=" . $th['hoststateid'] . " EVENT STATE=" . $meta['hoststateid'] . "\n";
                                continue;
                            }
                        }
                        if (isset($th['statetype']) && $th['statetype'] != "BOTH") {
                            if ($th['statetype'] != $meta['hoststatetype']) {
                                echo "Host matched type filter, skipping... TRAPHANDLER STATETYPE SETTING=" . $th['statetype'] . " EVENT STATETYPE=" . $meta['hoststatetype'] . "\n";
                                continue;
                            }
                        }
                        if (snmptrapsender_component_retrievedowntime($meta) && grab_array_var($th, 'downtime', 0) == 1) {
                            echo "Host is in scheduled downtime... EVENT DOWNTIME=1 \n";
                            continue;
                        }
                        if ($th['port'] != "") {
                            $port = ":" . $port;
                        } else {
                            $port = '';
                        }                        
                        if (grab_array_var($th, 'tcp', 0) == 1) {
                            $tcp = 'tcp:';
                            echo "Using TCP protocol..\n";
                        } else {
                            $tcp = '';
                        }

                        $trap_command = "/usr/bin/snmptrap -v 2c -c $community $tcp$address$port '' NAGIOS-NOTIFY-MIB::nHostEvent nHostname s \"%host%\" nHostStateID i %hoststateid% nHostOutput s \"%hostoutput%\"";

                        snmptrapsender_component_sendtrap($address, $community, $trap_command, $meta);
                    }
                }
            }
            break;
        case "service":
            if (array_key_exists("trap_hosts", $settings)) {

                // loop through all trap hosts
                foreach ($settings["trap_hosts"] as $th) {

                    // get address, community and port
                    $address = grab_array_var($th, "address");
                    $community = grab_array_var($th, "community");
                    $port = grab_array_var($th, "port");

                    // only send to hosts that have address and community defined
                    if ($address != "" && $community != "") {
                        echo "PROCESSING:\n";
                        print_r($th);

                        // filters
                        if (isset($th['servicestateid']) && $th['servicestateid'] != 0) {
                            if ($meta['servicestateid'] < $th['servicestateid'] || $th['servicestateid'] == -1) {
                                echo "Service matched state filter, skipping... TRAPHANDLER STATE SETTING=" . $th['servicestateid'] . " EVENT STATE=" . $meta['servicestateid'] . "\n";
                                continue;
                            }
                        }
                        if (isset($th['statetype']) && $th['statetype'] != "BOTH") {
                            if ($th['statetype'] != $meta['servicestatetype']) {
                                echo "Service matched type filter, skipping... TRAPHANDLER STATETYPE SETTING=" . $th['statetype'] . " EVENT STATETYPE=" . $meta['servicestatetype'] . "\n";
                                continue;
                            }
                        }
                        if (snmptrapsender_component_retrievedowntime($meta) && grab_array_var($th, 'downtime', 0) == 1) {
                            echo "Service is in scheduled downtime... EVENT DOWNTIME=1 \n";
                            continue;
                        }

                        if ($th['port'] != "") {
                            $port = ":" . $port;
                        } else {
                            $port = '';
                        }

                        if (grab_array_var($th, 'tcp', 0) == 1) {
                            $tcp = 'tcp:';
                            echo "Using TCP protocol..\n";
                        } else {
                            $tcp = '';
                        }

                        $trap_command = "/usr/bin/snmptrap -v 2c -c $community $tcp$address$port '' NAGIOS-NOTIFY-MIB::nSvcEvent nSvcHostname s \"%host%\" nSvcDesc s \"%service%\" nSvcStateID i %servicestateid% nSvcOutput s \"%serviceoutput%\"";

                        snmptrapsender_component_sendtrap($address, $community, $trap_command, $meta);

                    }
                }
            }
            break;
        default;
            break;
    }

}


function snmptrapsender_component_sendtrap($host, $community, $command, $meta)
{

    // pre-process command for variables
    $processed_command = process_notification_text($command, $meta);

    echo "RUNNING COMMAND: $processed_command\n";

    // run the command
    exec($processed_command);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function snmptrapsender_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    //requires greater than 2009R1.2
    if (get_product_release() < 114)
        return false;

    return true;
}

function snmptrapsender_component_checkinstallation()
{
    global $snmptrapsender_component_name;

    $f = "/usr/local/nagiosxi/html/includes/components/" . $snmptrapsender_component_name . "/installed.ok";
    $f2 = "/usr/local/nagiosxi/html/includes/components/" . $snmptrapsender_component_name . "/installed";

    // Install file doesn't exist
    if (!file_exists($f) && !file_exists($f2)) {
        return false;
    }

    return true;
}

function snmptrapsender_component_checkeventhandlers()
{
    $args = array(
        "cmd" => "getprogramstatus",
    );
    $xml = get_backend_xml_data($args);
    if ($xml) {
        $v = intval($xml->programstatus->event_handlers_enabled);
        if ($v == 1)
            return true;
    }

    return false;
}

function snmptrapsender_component_retrievedowntime($meta)
{

    $handler_type = grab_array_var($meta, "handler-type", "");

    if (!empty($handler_type)) {
        if ($handler_type == 'host') {
            $req = array("host_name" => $meta['host']);
            $obj = simplexml_load_string(get_host_status_xml_output($req));
            $dt = intval($obj ->hoststatus ->scheduled_downtime_depth);

            if ($dt > 0) {
                return true;
            }
            return false;
        } else if ($handler_type == 'service') {
            $req = array("name" => $meta['service'], "host_name" => $meta['host']);
            $obj = simplexml_load_string(get_service_status_xml_output($req));
            $dt = intval($obj ->servicestatus ->scheduled_downtime_depth);

            if ($dt > 0) {
                return true;
            }
            return false;
        }
    }
}