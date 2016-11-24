<?php
//
// Ping Action Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: pingaction.inc.php 182 2010-11-18 18:30:46Z egalstad $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$pingaction_component_name = "pingaction";
pingaction_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function pingaction_component_init()
{
    global $pingaction_component_name;
    $versionok = pingaction_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.4 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME =>           $pingaction_component_name,
        COMPONENT_AUTHOR =>         "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION =>    _("Provides a fast method of checking host connectivity using ICMP ping. ") . $desc,
        COMPONENT_TITLE =>          "Ping Action",
        COMPONENT_DATE =>           '04/28/2016',
        COMPONENT_VERSION =>        '1.1.1',
        COMPONENT_CONFIGFUNCTION => "pingaction_component_config_func"
    );

    register_component($pingaction_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_HOST_DETAIL_ACTION_LINK, 'pingaction_component_host_detail_action');
        register_callback(CALLBACK_SERVICE_DETAIL_ACTION_LINK, 'pingaction_component_service_detail_action');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function pingaction_component_checkversion()
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

function pingaction_component_config_func($mode = "", $inargs, &$outargs, &$result)
{

    // initialize return code and output
    $result = 0;
    $output = "";

    $component_name = "pingaction";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $settings_raw = get_option("pingaction_component_options");
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
            
<h5 class="ul">' . _('Ping Settings') . '</h5>

<table>
    <tr>
        <td class="checkbox">
            <label>
                <input type="checkbox" id="enabled" name="enabled" ' . is_checked($enabled, 1) . '>
                ' . _('Enable ping action') . '
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
            set_option("pingaction_component_options", serialize($settings));

            break;

        default:
            break;

    }

    return $output;
}


///////////////////////////////////////////////////////////////////////////////////////////
// ACTION FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function pingaction_component_host_detail_action($cbtype, &$cbargs)
{

    // get our settings
    $settings_raw = get_option("pingaction_component_options");
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

    $component_url = get_component_url_base("pingaction");
    $url = $component_url . "/ping.php?host=" . $hostaddress . "&cmd=go";

    $img = $component_url . "/images/ping.png";
    $clickcmd = "onClick='window.open(\"" . $url . "\",\"pingaction\",\"status=0,toolbar=0,height=300,width=600\")'";
    $text = "Ping this host";

    $cbargs["actions"][] = '<li><div class="commandimage"><a href="#" ' . $clickcmd . '><img src="' . $img . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"  ' . $clickcmd . '>' . $text . '</a></div></li>';

}

function pingaction_component_service_detail_action($cbtype, &$cbargs)
{

    // get our settings
    $settings_raw = get_option("pingaction_component_options");
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

    $component_url = get_component_url_base("pingaction");
    $url = $component_url . "/ping.php?host=" . $hostaddress . "&cmd=go";

    $img = $component_url . "/images/ping.png";
    $clickcmd = "onClick='window.open(\"" . $url . "\",\"pingaction\",\"status=0,toolbar=0,height=300,width=600\")'";
    $text = "Ping this host";

    $cbargs["actions"][] = '<li><div class="commandimage"><a href="#" ' . $clickcmd . '><img src="' . $img . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"  ' . $clickcmd . '>' . $text . '</a></div></li>';

}


?>