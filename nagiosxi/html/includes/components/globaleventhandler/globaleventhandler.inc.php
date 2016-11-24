<?php
//
// Global Event Handler Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: isms.inc.php 4 2010-04-07 15:49:08Z mmestnik $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$globaleventhandler_component_name = "globaleventhandler";
globaleventhandler_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function globaleventhandler_component_init()
{
    global $globaleventhandler_component_name;

    $args = array(
        COMPONENT_NAME => $globaleventhandler_component_name,
        COMPONENT_VERSION => '1.2.0',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Provides the ability to execute external scripts on host and service notifications and state changes."),
        COMPONENT_TITLE => _("Global Event Handlers"),
        COMPONENT_CONFIGFUNCTION => "globaleventhandler_component_config_func"
    );

    register_component($globaleventhandler_component_name, $args);

}


///////////////////////////////////////////////////////////////////////////////////////////
//CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function globaleventhandler_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    global $globaleventhandler_component_name;

    $result = 0;
    $output = "";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $settings_raw = get_option("globaleventhandler_component_options");
            if ($settings_raw == "")
                $settings = array();
            else
                $settings = unserialize($settings_raw);
            
            // initial values
            $host_event_handler_commands = array();
            $service_event_handler_commands = array();
            $host_notification_handler_commands = array();
            $service_notification_handler_commands = array();
            for ($x = 0; $x <= 2; $x++) {
                $host_event_handler_commands[$x] = array(
                    "enabled" => "",
                    "command" => "",
                    "downtime" => 0,
                );
                $service_event_handler_commands[$x] = array(
                    "enabled" => "",
                    "command" => "",
                    "downtime" => 0,
                );
                $host_notification_handler_commands[$x] = array(
                    "enabled" => "",
                    "command" => "",
                );
                $service_notification_handler_commands[$x] = array(
                    "enabled" => "",
                    "command" => "",
                );
            }

            // sample definitions
            $host_event_handler_commands[0]["command"] = '/tmp/host_change_handler.sh "%host%" %hoststate% %hoststateid% %lasthoststate% %lasthoststateid% %hoststatetype% %currentattempt% %maxattempts% %hosteventid% %hostproblemid% "%hostoutput%" %hostdowntime%';
            $service_event_handler_commands[0]["command"] = '/tmp/service_change_handler.sh "%host%" "%service%" %hoststate% %servicestate% %servicestateid% %lastservicestate%  %lastservicestateid% %servicestatetype% %currentattempt% %maxattempts% %serviceeventid% %serviceproblemid% "%serviceoutput%" %servicedowntime%';
            $host_notification_handler_commands[0]["command"] = '/tmp/host_notification_handler.sh "%contact%" "%type%" "%author%" "%comments%" "%host%" "%hostaddress%" %hoststate% %hoststateid% %lasthoststate% %lasthoststateid% %hoststatetype% %currentattempt% %maxattempts% %hosteventid% %hostproblemid% "%hostoutput%"';
            $service_notification_handler_commands[0]["command"] = '/tmp/service_notification_handler.sh "%contact%" "%type%" "%author%" "%comments%" "%host%" "%hostaddress%" %hoststate% %hoststateid% %servicestate% %servicestateid% %lastservicestate% %lastservicestateid% %servicestatetype% %currentattempt% %maxattempts% %serviceeventid% %serviceproblemid% "%serviceoutput%"';

            // saved values
            $host_event_handler_commands = grab_array_var($settings, "host_event_handler_commands", $host_event_handler_commands);
            $service_event_handler_commands = grab_array_var($settings, "service_event_handler_commands", $service_event_handler_commands);
            $host_notification_handler_commands = grab_array_var($settings, "host_notification_handler_commands", $host_notification_handler_commands);
            $service_notification_handler_commands = grab_array_var($settings, "service_notification_handler_commands", $service_notification_handler_commands);

            // values passed to us
            $host_event_handler_commands = grab_array_var($inargs, "host_event_handler_commands", $host_event_handler_commands);
            $service_event_handler_commands = grab_array_var($inargs, "service_event_handler_commands", $service_event_handler_commands);
            $host_notification_handler_commands = grab_array_var($inargs, "host_notification_handler_commands", $host_notification_handler_commands);
            $service_notification_handler_commands = grab_array_var($inargs, "service_notification_handler_commands", $service_notification_handler_commands);

            // fix missing values
            for ($x = 0; $x <= 2; $x++) {

                if (!array_key_exists("enabled", $host_event_handler_commands[$x]))
                    $host_event_handler_commands[$x]["enabled"] = "";
                if (!array_key_exists("enabled", $service_event_handler_commands[$x]))
                    $service_event_handler_commands[$x]["enabled"] = "";
                if (!array_key_exists("enabled", $host_notification_handler_commands[$x]))
                    $host_notification_handler_commands[$x]["enabled"] = "";
                if (!array_key_exists("enabled", $service_notification_handler_commands[$x]))
                    $service_notification_handler_commands[$x]["enabled"] = "";

                if (!array_key_exists("downtime", $host_event_handler_commands[$x]))
                    $host_event_handler_commands[$x]["downtime"] = "";
                if (!array_key_exists("downtime", $service_event_handler_commands[$x]))
                    $service_event_handler_commands[$x]["downtime"] = "";
                if (!array_key_exists("downtime", $host_notification_handler_commands[$x]))
                    $host_notification_handler_commands[$x]["downtime"] = "";
                if (!array_key_exists("downtime", $service_notification_handler_commands[$x]))
                    $service_notification_handler_commands[$x]["downtime"] = "";
            }

            $component_url = get_component_url_base($globaleventhandler_component_name);

            $output = "";

            $eventhandlersok = globaleventhandler_component_checkeventhandlers();
            if (!$eventhandlersok)
                $output .= "<font color='red'><b>" . _("WARNING") . ":</b> " . _("Event handlers are currently disabled.  This will prevent event handler commands from working!") . "</font>";

            $output .= '

<p>' . _('Define commands to be locally executed on this Nagios XI server when host and service state changes or notifications occur.  Recommended only for advanced users.') . '</p>

<script type="text/javascript">
$(document).ready(function() {
    $("#tabs").tabs();
});
</script>
    
<div id="tabs">
    <ul>
        <li><a href="#statechanges-tab">State Changes</a></li>
        <li><a href="#notifications-tab">Notifications</a></li>
    </ul>
    
    <div id="statechanges-tab">

    <h5 class="ul">' . _('Host State Change Handler Commands') . '</h5>
    
    <p>' . _('Commands to be executed when host state changes occur.') . ' </p>
        
    <table class="table table-condensed table-bordered table-striped table-auto-width">
        <thead>
            <tr>
                <th>' . _('Sequence') . '</th>
                <th>' . _('Enabled') . '</th>
                <th>' . _('Command') . '</th>
                <th>' . _('Don&#39t Run in Downtime') . '</th>
            </tr>
        </thead>
        <tbody>';

            for ($x = 0; $x <= 2; $x++) {

                $output .= '
            <tr>
                <td class="center">' . ($x + 1) . '</td>
                <td class="center">
                    <input type="checkbox" id="host_event_handler_commands[' . $x . '][enabled]" name="host_event_handler_commands[' . $x . '][enabled]" ' . is_checked($host_event_handler_commands[$x]["enabled"], 1) . '>
                </td>
                <td>
                    <input type="text" size="80" name="host_event_handler_commands[' . $x . '][command]" value="' . htmlspecialchars($host_event_handler_commands[$x]["command"]) . '" class="form-control">
                </td>
                <td class="center">
                    <input type="checkbox" id="downtime" name="host_event_handler_commands[' . $x . '][downtime]" value="1"' . is_checked($host_event_handler_commands[$x]['downtime'], 1) . '>
                </td>
            </tr>';
            }

            $output .= '
        </tbody>
    </table>
    
    <h5 class="ul">' . _('Service State Change Handler Commands') . '</h5>

    <p>' . _('Commands to be executed when service state changes occur.') . ' </p>

    <table class="table table-condensed table-bordered table-striped table-auto-width">
        <thead>
            <tr>
                <th>' . _('Sequence') . '</th>
                <th>' . _('Enabled') . '</th>
                <th>' . _('Command') . '</th>
                <th>' . _('Don&#39t Run in Downtime') . '</th>
            </tr>
        </thead>
        <tbody>';

            for ($x = 0; $x <= 2; $x++) {

                $output .= '
            <tr>
                <td class="center">' . ($x + 1) . '</td>
                <td class="center">
                    <input type="checkbox" id="service_event_handler_commands[' . $x . '][enabled]" name="service_event_handler_commands[' . $x . '][enabled]" ' . is_checked($service_event_handler_commands[$x]["enabled"], 1) . '>
                </td>
                <td>
                    <input type="text" size="80" name="service_event_handler_commands[' . $x . '][command]" value="' . htmlspecialchars($service_event_handler_commands[$x]["command"]) . '" class="form-control">
                </td>
                <td class="center">
                    <input type="checkbox"id="downtime" name="service_event_handler_commands[' . $x . '][downtime]" value="1" ' . is_checked($service_event_handler_commands[$x]['downtime'], 1) . '>
                </td>
            </tr>';
            }

            $output .= '
        </tbody>
    </table>
    
    </div>

    <div id="notifications-tab">
    
    <h5 class="ul">' . _('Host Notification Handler Commands') . '</h5>
    
    <p>' . _('Commands to be executed when host notifications occur.') . '</p>

    <table class="table table-condensed table-bordered table-striped table-auto-width">
        <thead>
            <tr>
                <th>' . _('Sequence') . '</th>
                <th>' . _('Enabled') . '</th>
                <th>' . _('Command') . '</th>
            </tr>
        </thead>
        <tbody>';

            for ($x = 0; $x <= 2; $x++) {

                $output .= '
            <tr>
                <td class="center">' . ($x + 1) . '</td>
                <td class="center">
                    <input type="checkbox" id="host_notification_handler_commands[' . $x . '][enabled]" name="host_notification_handler_commands[' . $x . '][enabled]" ' . is_checked($host_notification_handler_commands[$x]["enabled"], 1) . '>
                </td>
                <td>
                    <input type="text" size="80" name="host_notification_handler_commands[' . $x . '][command]" value="' . htmlspecialchars($host_notification_handler_commands[$x]["command"]) . '" class="form-control">
                </td>
            </tr>
        ';
            }

            $output .= '
        </tbody>
    </table>

    <h5 class="ul">' . _('Service Notification Handler Commands') . '</h5>
    
    <p>' . _('Commands to be executed when service notifications occur.') . '</p>

    <table class="table table-condensed table-bordered table-striped table-auto-width">
        <thead>
            <tr>
                <th>' . _('Sequence') . '</th>
                <th>' . _('Enabled') . '</th>
                <th>' . _('Command') . '</th>
            </tr>
        </thead>
        <tbody>';

            for ($x = 0; $x <= 2; $x++) {

                $output .= '
            <tr>
                <td class="center">' . ($x + 1) . '</td>
                <td class="center">
                    <input type="checkbox" id="service_notification_handler_commands[' . $x . '][enabled]" name="service_notification_handler_commands[' . $x . '][enabled]" ' . is_checked($service_notification_handler_commands[$x]["enabled"], 1) . '>
                </td>
                <td>
                    <input type="text" size="80" name="service_notification_handler_commands[' . $x . '][command]" value="' . htmlspecialchars($service_notification_handler_commands[$x]["command"]) . '" class="form-control">
                </td>
            </tr>';
            }

            $output .= '
        </tbody>
    </table>
    
    </div>

</div>';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // get variables
            $host_event_handler_commands = grab_array_var($inargs, "host_event_handler_commands", "");
            $service_event_handler_commands = grab_array_var($inargs, "service_event_handler_commands", "");
            $host_notification_handler_commands = grab_array_var($inargs, "host_notification_handler_commands", "");
            $service_notification_handler_commands = grab_array_var($inargs, "service_notification_handler_commands", "");

            // validate variables
            $errors = 0;
            $errmsg = array();

            // handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // save settings
            $settings = array(
                "host_event_handler_commands" => $host_event_handler_commands,
                "service_event_handler_commands" => $service_event_handler_commands,
                "host_notification_handler_commands" => $host_notification_handler_commands,
                "service_notification_handler_commands" => $service_notification_handler_commands,
            );
            set_option("globaleventhandler_component_options", serialize($settings));

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


register_callback(CALLBACK_EVENT_PROCESSED, 'globaleventhandler_component_eventhandler');

function globaleventhandler_component_eventhandler($cbtype, $args)
{

    //enable logging?
    $logging = is_null(get_option('enable_subsystem_logging')) ? true : get_option("enable_subsystem_logging");
    if ($logging) {
        echo "*** GLOBAL HANDLER...\n";
        print_r($args);
    }

    switch ($args["event_type"]) {
        case EVENTTYPE_STATECHANGE:
            globaleventhandler_component_handle_statechange_event($args);
            break;
        case EVENTTYPE_NOTIFICATION:
            globaleventhandler_component_handle_notification_event($args);
            break;
        default:
            break;
    }
}


function globaleventhandler_component_handle_statechange_event($args)
{

    $meta = grab_array_var($args, "event_meta", array());
    $handler_type = grab_array_var($meta, "handler-type", "");

    // load settings
    $settings_raw = get_option("globaleventhandler_component_options");
    if ($settings_raw == "") {
        //$settings=array();
        // settings have not been configured yet...
        return;
    } else
        $settings = unserialize($settings_raw);

    switch ($handler_type) {
        case "host":
            if (array_key_exists("host_event_handler_commands", $settings)) {
                // loop through all event handler commands
                foreach ($settings["host_event_handler_commands"] as $seq => $vals) {
                    // only process commands that are enabled
                    if (array_key_exists("enabled", $vals)) {
                        if (globaleventhandler_component_retrievedowntime($meta) && grab_array_var($vals, 'downtime', 0) == 1) {
                            echo "Host is in scheduled downtime... EVENT DOWNTIME=1 \n";
                            continue;
                        }
                        // only process non-null commands
                        if ($vals["command"] != "") {
                            globaleventhandler_component_run_command($vals["command"], $meta);
                        }
                    }
                }
            }
            break;

        case "service":
            if (array_key_exists("service_event_handler_commands", $settings)) {
                // loop through all event handler commands
                foreach ($settings["service_event_handler_commands"] as $seq => $vals) {
                    // only process commands that are enabled
                    if (array_key_exists("enabled", $vals)) {
                        if (globaleventhandler_component_retrievedowntime($meta) && grab_array_var($vals, 'downtime', 0) == 1) {
                            echo "Host is in scheduled downtime... EVENT DOWNTIME=1 \n";
                            continue;
                        }
                        // only process non-null commands
                        if ($vals["command"] != "") {
                            globaleventhandler_component_run_command($vals["command"], $meta);
                        }
                    }
                }
            }
            break;

        default;
            break;
    }

}

function globaleventhandler_component_handle_notification_event($args)
{

    $meta = grab_array_var($args, "event_meta", array());
    $notification_type = grab_array_var($meta, "notification-type", "");
    $contact = grab_array_var($meta, "contact", "");

    // load settings
    $settings_raw = get_option("globaleventhandler_component_options");
    if ($settings_raw == "") {
        //$settings=array();
        // settings have not been configured yet...
        return;
    } else
        $settings = unserialize($settings_raw);

    switch ($notification_type) {

        case "host":
            if (array_key_exists("host_notification_handler_commands", $settings)) {
                // loop through all notification commands
                foreach ($settings["host_notification_handler_commands"] as $seq => $vals) {
                    // only process commands that are enabled
                    if (array_key_exists("enabled", $vals)) {
                        // only process non-null commands
                        if ($vals["command"] != "") {
                            globaleventhandler_component_run_command($vals["command"], $meta);
                        }
                    }
                }
            }
            break;

        case "service":
            if (array_key_exists("service_notification_handler_commands", $settings)) {
                // loop through all notification commands
                foreach ($settings["service_notification_handler_commands"] as $seq => $vals) {
                    // only process commands that are enabled
                    if (array_key_exists("enabled", $vals)) {
                        // only process non-null commands
                        if ($vals["command"] != "") {
                            globaleventhandler_component_run_command($vals["command"], $meta);
                        }
                    }
                }
            }
            break;

        default:
            break;
    }
}


////////////////////////////////////////////////////////////////////////
// COMMAND FUNCTIONS
////////////////////////////////////////////////////////////////////////

function globaleventhandler_component_run_command($cmd, $meta)
{

    //enable logging?
    $logging = is_null(get_option('enable_subsystem_logging')) ? true : get_option("enable_subsystem_logging");
    if ($logging) {
        echo "RUNNING GLOBAL EVENT HANDLER...\n";
        echo "CMD=$cmd\n";
        echo "META:\n";
        print_r($meta);
    }
    // process variables present in the command line
    $cmdline = process_notification_text($cmd, $meta);

    // execute the command
    exec($cmdline);

    return 0;
}


////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
////////////////////////////////////////////////////////////////////////

function globaleventhandler_component_checkeventhandlers()
{

    // get process status
    $args = array(
        "cmd" => "getprogramstatus",
    );
    $xml = get_backend_xml_data($args);
    if ($xml) {
        $v = intval($xml->programstatus->event_handlers_enabled);
        if ($v == 1)
            return true; // event handlers are enabled
    }

    return false;
}

function globaleventhandler_component_retrievedowntime($meta)
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