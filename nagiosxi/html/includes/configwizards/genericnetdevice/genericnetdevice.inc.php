<?php
//
// Generic Network Device Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

genericnetdevice_configwizard_init();

function genericnetdevice_configwizard_init()
{
    $name = "genericnetdevice";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a generic IP network device."),
        CONFIGWIZARD_DISPLAYTITLE => _("Generic Network Device"),
        CONFIGWIZARD_FUNCTION => "genericnetdevice_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "network_node.png",
        CONFIGWIZARD_VERSION => "1.0.2",
        CONFIGWIZARD_COPYRIGHT => "Copyright &copy; 2008-2015 Nagios Enterprises, LLC.",
        CONFIGWIZARD_AUTHOR => "Nagios Enterprises, LLC",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function genericnetdevice_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "genericnetdevice";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $output = '
    <h5 class="ul">' . _('Network Device Information') . '</h5>
    
    <table class="table table-condensed table-no-border table-auto-width table-padded">
        <tbody>
            <tr>
                <td class="vt">
                    <label>' . _('Device Address') . ':</label>
                </td>
                <td>
                    <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
                    <div class="subtext">' . _('The IP address of the device you\'d like to monitor') . '.</div>
                </td>
            </tr>
        </tbody>
    </table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");

            // check for errors
            $errors = 0;
            $errmsg = array();
            //$errmsg[$errors++]="Address: '$address'";
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            else if (!valid_ip($address))
                $errmsg[$errors++] = _("Invalid IP address.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");

            $hostname = @gethostbyaddr($address);
            $hostname = nagiosccm_replace_user_macros($hostname);

            if (empty($hostname)) {
                $hostname = $address;
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Device Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Device Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this device.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Device Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the device.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="ping" name="services[ping]" checked>
        </td>
        <td>
            <b>' . _('Ping') . '</b><br>
            ' . _('Monitors the device with an ICMP ping.  Useful for watching network latency and general uptime of your device.') . '
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="services_serial" value="' . $services_serial . '">
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $output = '
            
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $hostaddress = $address;

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_genericnetdevice_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "network_node.png",
                    "statusmap_image" => "network_node.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // See which services we should monitor
            foreach ($services as $svc => $svcstate) {
                switch ($svc) {

                    case "ping":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Ping",
                            "use" => "xiwizard_genericnetdevice_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    default:
                        break;
                }
            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}