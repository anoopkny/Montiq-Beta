<?php
//
// RDP Component
// Copyright (c) 2012-2015 Nagios Enterprises, LLC. All rights reserved.
//  

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$rdp_component_name = "rdp";
rdp_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function rdp_component_init()
{
    global $rdp_component_name;

    $versionok = rdp_component_checkversion();

    $desc = "";
    if (!$versionok)
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.8 or later.") . "</b>";

    $args = array(
        COMPONENT_NAME => $rdp_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Adds RDP and VNC connection links to hosts and services.") . $desc,
        COMPONENT_TITLE => _("RDP and VNC Connection"),
        COMPONENT_VERSION => '1.0.1',
        COMPONENT_CONFIGFUNCTION => "rdp_component_config_func"
    );

    register_component($rdp_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_HOST_DETAIL_ACTION_LINK, 'rdp_component_host_detail_action');
        register_callback(CALLBACK_SERVICE_DETAIL_ACTION_LINK, 'rdp_component_service_detail_action');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function rdp_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 207)
        return false;

    return true;
}


///////////////////////////////////////////////////////////////////////////////////////////
// CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function rdp_component_config_func($mode = "", $inargs, &$outargs, &$result)
{

    // initialize return code and output
    $result = 0;
    $output = "";

    $component_name = "rdp";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // default settings
            $settings_default = array(
                "enabled" => 1,
                "actions" => array(),
            );

            // saved settings
            $settings_raw = get_option("rdp_component_options");
            if ($settings_raw != "") {
                $settings_default = unserialize($settings_raw);
            }

            // settings passed to us
            $settings = grab_array_var($inargs, "settings", $settings_default);

            // checkboxes
            $enabled = checkbox_binary(grab_array_var($settings, "enabled", ""));


            $component_url = get_component_url_base($component_name);

            $output = '
            
<h5 class="ul">' . _("RDP / VNC Connection Settings") . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="enabled" name="settings[enabled]" ' . is_checked($enabled, 1) . '>
                ' . _("Enable Component") . '
            </label>
        </td>
    </tr>
</table>';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // get variables
            $settings = grab_array_var($inargs, "settings", array("settings" => array()));

            // fix checkboxes
            $settings["enabled"] = checkbox_binary(grab_array_var($settings, "enabled", ""));
            /*
            foreach($settings["actions"] as $x => $sa){
                $settings["actions"][$x]["enabled"]=checkbox_binary(grab_array_var($sa,"enabled",""));
                }
            */

            //print_r($settings);
            //exit();

            // checkboxes
            $enabled = checkbox_binary(grab_array_var($settings, "enabled", ""));

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
            set_option("rdp_component_options", serialize($settings));

            break;

        default:
            break;

    }

    return $output;
}


///////////////////////////////////////////////////////////////////////////////////////////
// ACTION FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function rdp_component_host_detail_action($cbtype, &$cbargs)
{

    // get our settings
    $settings_raw = get_option("rdp_component_options");
    if ($settings_raw == "") {
        $settings = array(
            "enabled" => 1,
        );
    } else
        $settings = unserialize($settings_raw);

    // initial values
    $enabled = grab_array_var($settings, "enabled");

    //print_r($settings);

    // bail out if we're not enabled...
    if ($enabled != 1) {
        return;
    }


    // add an action link...

    $hostname = grab_array_var($cbargs, "hostname");
    $host_id = grab_array_var($cbargs, "host_id");
    $hoststatus_xml = grab_array_var($cbargs, "hoststatus_xml");

    // get variables
    $objectvars = rdp_component_get_host_vars($hostname, $host_id, $hoststatus_xml);
    //print_r($objectvars);

    $component_url = get_component_url_base("rdp");
    $img = $component_url . "/images/rdp.png";


    /*
    // find matching actions
    foreach($settings["actions"] as $x => $sa){
        if($sa["enabled"]!=1)
            continue;
        // must be host type
        if($sa["type"]!="host" && $sa["type"]!="any")
            continue;
        // must match host name
        if($sa["host"]!="" && preg_match($sa["host"],$hostname)==0)
            continue;
        // must match hostgroup if specified
        if($sa["hostgroup"]!="" && is_host_member_of_hostgroup($hostname,$sa["hostgroup"])==false)
            continue;
        // must match servicegroup if specified
        if($sa["servicegroup"]!="" && is_host_member_of_servicegroup($hostname,$sa["servicegroup"])==false)
            continue;

        // good to go...

        // URL
        if($sa["action_type"]=="url"){

            $url=$sa["url"];
            $target=$sa["target"];
            $hrefopts="";

            // process vars in url
            foreach($objectvars as $var => $val){
                $tvar="%".$var."%";
                $url=str_replace($tvar,urlencode($val),$url);
                }
            }

        // COMMAND
        else{

            $url=$component_url."/runcmd.php?action=".urlencode($x)."&uid=".urlencode($sa["uid"])."&host=".urlencode($hostname);
            $target="_blank";
            $hrefopts="";
            }

        // action text
        $text=$sa["text"];

        // get optional code to run
        $code=$sa["code"];

        // process vars in text, and php code
        foreach($objectvars as $var => $val){
            $tvar="%".$var."%";
            $text=str_replace($tvar,$val,$text);
            $code=str_replace($tvar,$val,$code);
            }

        $showlink=true;

        // execute PHP code
        if($code!=""){
            eval($code);
            }
        // code indicated we shouldn't show this link
        if($showlink==false)
            return;

        $cbargs["actions"][]='<li><div class="commandimage"><a href="'.$url.'" target="'.$target.'" '.$hrefopts.'><img src="'.$img.'" alt="'.htmlentities($text).'" title="'.htmlentities($text).'"></a></div><div class="commandtext"><a href="'.$url.'" target="'.$target.'" '.$hrefopts.'>'.htmlentities($text).'</a></div></li>';
        }
    */

    $url = $component_url . "/gordp.php?confirm=1&hostid=" . $objectvars["hostid"] . "&address=" . $objectvars["hostaddress"];
    $onclick = "window.open('" . $url . "','rdp','width=540,height=400,menubar=no,status=no,toolbar=no,scrollbars=no,resizable=yes');";
    //$target="_blank";
    $hrefopts = "";
    $text = "Connect to " . $hostname;
    $cbargs["actions"][] = '<li><div class="commandimage"><a href="#" onclick="' . $onclick . '"><img src="' . $img . '" alt="' . htmlentities($text) . '" title="' . htmlentities($text) . '"></a></div><div class="commandtext"><a href="#" onclick="' . $onclick . '">' . htmlentities($text) . '</a></div></li>';

}

function rdp_component_service_detail_action($cbtype, &$cbargs)
{


    // get our settings
    $settings_raw = get_option("rdp_component_options");
    if ($settings_raw == "") {
        $settings = array(
            "enabled" => 1,
        );
    } else
        $settings = unserialize($settings_raw);

    // initial values
    $enabled = grab_array_var($settings, "enabled");

    //print_r($settings);

    // bail out if we're not enabled...
    if ($enabled != 1) {
        return;
    }


    // add an action link...

    $hostname = grab_array_var($cbargs, "hostname");
    $servicename = grab_array_var($cbargs, "servicename");
    $service_id = grab_array_var($cbargs, "service_id");
    $servicestatus_xml = grab_array_var($cbargs, "servicestatus_xml");

    // get variables
    $objectvars = rdp_component_get_service_vars($hostname, $servicename, $service_id, $servicestatus_xml);
    //print_r($objectvars);

    $component_url = get_component_url_base("actions");
    $img = $component_url . "/images/action.png";

    /*
    // find matching actions
    foreach($settings["actions"] as $x => $sa){
        if($sa["enabled"]!=1)
            continue;
        // must be service type
        if($sa["type"]!="service" && $sa["type"]!="any")
            continue;
        // must match host name
        if($sa["host"]!="" && preg_match($sa["host"],$hostname)==0)
            continue;
        // must match service name
        if($sa["service"]!="" && preg_match($sa["service"],$servicename)==0)
            continue;
        // must match hostgroup if specified
        if($sa["hostgroup"]!="" && is_service_member_of_hostgroup($hostname,$servicename,$sa["hostgroup"])==false)
            continue;
        // must match servicegroup if specified
        if($sa["servicegroup"]!="" && is_service_member_of_servicegroup($hostname,$servicename,$sa["servicegroup"])==false)
            continue;

        // good to go...

        // URL
        if($sa["action_type"]=="url"){

            $url=$sa["url"];
            $target=$sa["target"];
            $hrefopts="";

            // process vars in url
            foreach($objectvars as $var => $val){
                $tvar="%".$var."%";
                $url=str_replace($tvar,urlencode($val),$url);
                }
            }

        // COMMAND
        else{

            $url=$component_url."/runcmd.php?action=".urlencode($x)."&uid=".urlencode($sa["uid"])."&host=".urlencode($hostname)."&service=".urlencode($servicename);
            $target="_blank";
            $hrefopts="";
            }

        // action text
        $text=$sa["text"];

        // get optional code to run
        $code=$sa["code"];


        // process vars in text, and php code
        foreach($objectvars as $var => $val){
            $tvar="%".$var."%";
            $text=str_replace($tvar,$val,$text);
            $code=str_replace($tvar,$val,$code);
            }

        $showlink=true;

        // execute PHP code
        if($code!=""){
            eval($code);
            }
        // code indicated we shouldn't show this link
        if($showlink==false)
            return;

        $cbargs["actions"][]='<li><div class="commandimage"><a href="'.$url.'" target="'.$target.'" '.$hrefopts.'><img src="'.$img.'" alt="'.htmlentities($text).'" title="'.htmlentities($text).'"></a></div><div class="commandtext"><a href="'.$url.'" target="'.$target.'" '.$hrefopts.'>'.htmlentities($text).'</a></div></li>';
        }
    */
}


function rdp_component_get_host_vars($hostname, $host_id = -1, $hoststatus_xml = null)
{

    $hostaddress = $hostname;

    $objectvars = array();

    // find the host's address (and possibly id)
    $args = array(
        "cmd" => "gethosts",
    );
    if ($host_id == -1)
        $args["name"] = $hostname;
    else
        $args["host_id"] = $host_id;

    $xml = get_xml_host_objects($args);
    if ($xml) {
        $hostaddress = strval($xml->host->address);
    }

    // fetch host status if needed
    if ($hoststatus_xml == null) {
        $args = array(
            "cmd" => "gethoststatus",
            "name" => $hostname,
        );
        $hoststatus_xml = get_xml_host_status($args);
    }

    // variables
    $objectvars = array(
        "objecttype" => "host",
        "host" => $hostname,
        "hostname" => $hostname,
        "hostaddress" => $hostaddress,
        "hostid" => strval($hoststatus_xml->hoststatus->host_id),
        "hostdisplayname" => strval($hoststatus_xml->hoststatus->display_name),
        "hostalias" => strval($hoststatus_xml->hoststatus->alias),
        "hoststateid" => intval($hoststatus_xml->hoststatus->current_state),
        "hoststatetype" => strval($hoststatus_xml->hoststatus->state_type),
        "hoststatustext" => strval($hoststatus_xml->hoststatus->status_text),
        "hoststatustextlong" => strval($hoststatus_xml->hoststatus->status_text_long),
        "hostperfdata" => strval($hoststatus_xml->hoststatus->performance_data),
        "hostchecktype" => strval($hoststatus_xml->hoststatus->check_type),
        "hostactivechecks" => strval($hoststatus_xml->hoststatus->active_checks_enabled),
        "hostpassivechecks" => strval($hoststatus_xml->hoststatus->passive_checks_enabled),
        "hostnotifications" => strval($hoststatus_xml->hoststatus->notifications_enabled),
        "hostacknowledged" => strval($hoststatus_xml->hoststatus->problem_acknowledged),
        "hosteventhandler" => strval($hoststatus_xml->hoststatus->event_handler_enabled),
        "hostflapdetection" => strval($hoststatus_xml->hoststatus->flap_detection_enabled),
        "hostisflapping" => strval($hoststatus_xml->hoststatus->is_flapping),
        "hostpercentstatechange" => strval($hoststatus_xml->hoststatus->percent_state_change),
        "hostdowntime" => strval($hoststatus_xml->hoststatus->scheduled_downtime_depth),
        "hostlatency" => strval($hoststatus_xml->hoststatus->latency),
        "hostexectime" => strval($hoststatus_xml->hoststatus->execution_time),
        "hostlastcheck" => strval($hoststatus_xml->hoststatus->last_check),
        "hostnextcheck" => strval($hoststatus_xml->hoststatus->next_check),
        "hosthasbeenchecked" => strval($hoststatus_xml->hoststatus->has_been_checked),
        "hostshouldbescheduled" => strval($hoststatus_xml->hoststatus->should_be_scheduled),
        "hostcurrentattempt" => strval($hoststatus_xml->hoststatus->current_check_attempt),
        "hostmaxattempts" => strval($hoststatus_xml->hoststatus->max_check_attempts),
    );

    return $objectvars;
}

function rdp_component_get_service_vars($hostname, $servicename, $service_id, $servicestatus_xml)
{

    $objectvars = array();

    $hostaddress = $hostname;

    if ($servicestatus_xml == null) {
        $args = array(
            "cmd" => "getservicestatus",
            "host_name" => $hostname,
            "service_description" => $servicename,
        );
        $servicestatus_xml = get_xml_service_status($args);
    }

    // find the host's address
    $args = array(
        "cmd" => "gethosts",
        "host_id" => intval($servicestatus_xml->servicestatus->host_id),
    );
    $xml = get_xml_host_objects($args);
    if ($xml) {
        $hostaddress = strval($xml->host->address);
    }

    // variables
    $objectvars = array(
        "objecttype" => "service",
        "service" => $servicename,
        "servicename" => $servicename,
        "serviceid" => strval($servicestatus_xml->servicestatus->service_id),
        "servicedisplayname" => strval($servicestatus_xml->servicestatus->display_name),
        "servicestateid" => intval($servicestatus_xml->servicestatus->current_state),
        "servicestatetype" => strval($servicestatus_xml->servicestatus->state_type),
        "servicestatustext" => strval($servicestatus_xml->servicestatus->status_text),
        "servicestatustextlong" => strval($servicestatus_xml->servicestatus->status_text_long),
        "serviceperfdata" => strval($servicestatus_xml->servicestatus->performance_data),
        "hostchecktype" => strval($servicestatus_xml->servicestatus->check_type),
        "serviceactivechecks" => strval($servicestatus_xml->servicestatus->active_checks_enabled),
        "servicepassivechecks" => strval($servicestatus_xml->servicestatus->passive_checks_enabled),
        "servicenotifications" => strval($servicestatus_xml->servicestatus->notifications_enabled),
        "serviceacknowledged" => strval($servicestatus_xml->servicestatus->problem_acknowledged),
        "serviceeventhandler" => strval($servicestatus_xml->servicestatus->event_handler_enabled),
        "serviceflapdetection" => strval($servicestatus_xml->servicestatus->flap_detection_enabled),
        "serviceisflapping" => strval($servicestatus_xml->servicestatus->is_flapping),
        "servicepercentstatechange" => strval($servicestatus_xml->servicestatus->percent_state_change),
        "servicedowntime" => strval($servicestatus_xml->servicestatus->scheduled_downtime_depth),
        "servicelatency" => strval($servicestatus_xml->servicestatus->latency),
        "serviceexectime" => strval($servicestatus_xml->servicestatus->execution_time),
        "servicelastcheck" => strval($servicestatus_xml->servicestatus->last_check),
        "servicenextcheck" => strval($servicestatus_xml->servicestatus->next_check),
        "servicehasbeenchecked" => strval($servicestatus_xml->servicestatus->has_been_checked),
        "serviceshouldbescheduled" => strval($servicestatus_xml->servicestatus->should_be_scheduled),
        "servicecurrentattempt" => strval($servicestatus_xml->servicestatus->current_check_attempt),
        "servicemaxattempts" => strval($servicestatus_xml->servicestatus->max_check_attempts),


        "host" => $hostname,
        "hostname" => $hostname,
        "hostaddress" => $hostaddress,
        "hostid" => strval($servicestatus_xml->servicestatus->host_id),
        "hostdisplayname" => strval($servicestatus_xml->servicestatus->host_display_name),
        //"hostalias"=>strval($servicestatus_xml->servicestatus->host_alias),
        "hoststateid" => intval($servicestatus_xml->servicestatus->host_current_state),
        "hoststatetype" => strval($servicestatus_xml->servicestatus->host_state_type),
        "servicestatustext" => strval($servicestatus_xml->servicestatus->host_status_text),
        "servicestatustextlong" => strval($servicestatus_xml->servicestatus->host_status_text_long),
        "hostperfdata" => strval($servicestatus_xml->servicestatus->host_performance_data),
        "hostchecktype" => strval($servicestatus_xml->servicestatus->host_check_type),
        "hostactivechecks" => strval($servicestatus_xml->servicestatus->host_active_checks_enabled),
        "hostpassivechecks" => strval($servicestatus_xml->servicestatus->host_passive_checks_enabled),
        "hostnotifications" => strval($servicestatus_xml->servicestatus->host_notifications_enabled),
        "hostacknowledged" => strval($servicestatus_xml->servicestatus->host_problem_acknowledged),
        "hosteventhandler" => strval($servicestatus_xml->servicestatus->host_event_handler_enabled),
        "hostflapdetection" => strval($servicestatus_xml->servicestatus->host_flap_detection_enabled),
        "hostisflapping" => strval($servicestatus_xml->servicestatus->host_is_flapping),
        "hostpercentstatechange" => strval($servicestatus_xml->servicestatus->host_percent_state_change),
        "hostdowntime" => strval($servicestatus_xml->servicestatus->host_scheduled_downtime_depth),
        "hostlatency" => strval($servicestatus_xml->servicestatus->host_latency),
        "hostexectime" => strval($servicestatus_xml->servicestatus->host_execution_time),
        "hostlastcheck" => strval($servicestatus_xml->servicestatus->host_last_check),
        "hostnextcheck" => strval($servicestatus_xml->servicestatus->host_next_check),
        "hosthasbeenchecked" => strval($servicestatus_xml->servicestatus->host_has_been_checked),
        "hostshouldbescheduled" => strval($servicestatus_xml->servicestatus->host_should_be_scheduled),
        "hostcurrentattempt" => strval($servicestatus_xml->servicestatus->host_current_check_attempt),
        "hostmaxattempts" => strval($servicestatus_xml->servicestatus->host_max_check_attempts),
    );

    return $objectvars;
}

?>