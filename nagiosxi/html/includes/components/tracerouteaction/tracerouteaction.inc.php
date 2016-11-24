<?php
//
// Traceroute Action Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$tracerouteaction_component_name = "tracerouteaction";
tracerouteaction_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function tracerouteaction_component_init()
{
    global $tracerouteaction_component_name;
    $versionok = tracerouteaction_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.4 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME =>           $tracerouteaction_component_name,
        COMPONENT_AUTHOR =>         "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION =>    _("Provides a fast method of checking host connectivity using traceroute. ") . $desc,
        COMPONENT_TITLE =>          _("Traceroute Action"),
        COMPONENT_VERSION =>        '1.1.1',
        COMPONENT_DATE =>           '04/28/2016',
        COMPONENT_CONFIGFUNCTION => "tracerouteaction_component_config_func"
    );

    register_component($tracerouteaction_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_HOST_DETAIL_ACTION_LINK, 'tracerouteaction_component_host_detail_action');
        register_callback(CALLBACK_SERVICE_DETAIL_ACTION_LINK, 'tracerouteaction_component_service_detail_action');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function tracerouteaction_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 125)
        return false;
    return true;
}


///////////////////////////////////////////////////////////////////////////////////////////
// CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function tracerouteaction_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    $result = 0;
    $output = "";

    $component_name = "tracerouteaction";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $settings_raw = get_option("tracerouteaction_component_options");
            if ($settings_raw == "") {
                $settings = array(
                    "enabled" => 1,
                );
            } else
                $settings = unserialize($settings_raw);

            // initial values
            $enabled = grab_array_var($settings, "enabled", "");

            // values passed to us
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", $enabled));

            $component_url = get_component_url_base($component_name);

            $output = '
            
<h5 class="ul">' . _('Traceroute Settings') . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
    <td class="checkbox">
        <label>
            <input type="checkbox" id="enabled" name="enabled" ' . is_checked($enabled, 1) . '>
            ' . _('Enable traceroute action') . '
        </label>
    </td>
    </tr>
</table>';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // get variables
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", ""));

            // validate variables
            $errors = 0;
            $errmsg = array();
            if ($enabled == 1) {
                /*
                if(have_value($user_dn)==false){
                    $errmsg[$errors++]="No user DN specified.";
                    }
                */
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
            );
            set_option("tracerouteaction_component_options", serialize($settings));

            break;

        default:
            break;

    }

    return $output;
}


///////////////////////////////////////////////////////////////////////////////////////////
// ACTION FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function tracerouteaction_component_host_detail_action($cbtype, &$cbargs)
{

    // get our settings
    $settings_raw = get_option("tracerouteaction_component_options");
    if ($settings_raw == "") {
        $settings = array(
            "enabled" => 1,
        );
    } else
        $settings = unserialize($settings_raw);

    // initial values
    $enabled = grab_array_var($settings, "enabled");

    // bail out if we're not enabled...
    if ($enabled != 1) {
        return;
    }


    // add an action link...

    $hostname = grab_array_var($cbargs, "hostname");
    $host_id = grab_array_var($cbargs, "host_id");
    $hostaddress = $hostname;

    // find the host's address
    $args = array(
        "cmd" => "gethosts",
        "host_id" => $host_id,
    );
    $xml = get_xml_host_objects($args);
    if ($xml)
        $hostaddress = $xml->host->address;

    $component_url = get_component_url_base("tracerouteaction");
    $url = $component_url . "/traceroute.php?host=" . $hostaddress . "&cmd=go";

    $img = $component_url . "/images/traceroute.png";
    $clickcmd = "onClick='window.open(\"" . $url . "\",\"tracerouteaction\",\"status=0,toolbar=0,height=300,width=700,scrollbars=yes\")'";
    $text = "Traceroute to this host";

    $cbargs["actions"][] = '<li><div class="commandimage"><a href="#" ' . $clickcmd . '><img src="' . $img . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"  ' . $clickcmd . '>' . $text . '</a></div></li>';

}

function tracerouteaction_component_service_detail_action($cbtype, &$cbargs)
{

    // get our settings
    $settings_raw = get_option("tracerouteaction_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);

    // initial values
    $enabled = grab_array_var($settings, "enabled");

    // bail out if we're not enabled...
    if ($enabled != 1) {
        return;
    }

    // add an action link...

    $hostname = grab_array_var($cbargs, "hostname");
    $service_id = grab_array_var($cbargs, "service_id");
    $hostaddress = $hostname;

    // find the host's address
    $args = array(
        "cmd" => "gethosts",
        "host_name" => $hostname,
    );
    $xml = get_xml_host_objects($args);
    if ($xml)
        $hostaddress = $xml->host->address;

    $component_url = get_component_url_base("tracerouteaction");
    $url = $component_url . "/traceroute.php?host=" . $hostaddress . "&cmd=go";

    $img = $component_url . "/images/traceroute.png";
    $clickcmd = "onClick='window.open(\"" . $url . "\",\"tracerouteaction\",\"status=0,toolbar=0,height=300,width=700,scrollbars=yes\")'";
    $text = "Traceroute to this host";

    $cbargs["actions"][] = '<li><div class="commandimage"><a href="#" ' . $clickcmd . '><img src="' . $img . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"  ' . $clickcmd . '>' . $text . '</a></div></li>';

}


?>