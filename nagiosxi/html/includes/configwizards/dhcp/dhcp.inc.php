<?php
//
// DHCP Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

dhcp_configwizard_init();

function dhcp_configwizard_init()
{
    $name = "dhcp";

    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a DHCP server."),
        CONFIGWIZARD_DISPLAYTITLE => "DHCP",
        CONFIGWIZARD_FUNCTION => "dhcp_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "dhcp.png",
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
function dhcp_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{

    $wizard_name = "dhcp";

    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");

            $output = '
    <h5 class="ul">DHCP Host</h5>
    <table class="table table-condensed table-no-border table-auto-width table-padded">
        <tbody>
            <tr>
                <td class="vt">
                    <label>Address:</label>
                </td>
                <td>
                    <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
                    <div class="subtext">' . _('The IP address or FQDNS name of the device or server associated with the DHCP check') . '.</div>
                </td>
            </tr>
        </tbody>
    </table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false) {
                $errmsg[$errors++] = _("No address specified.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");

            $ha = @gethostbyaddr($address);
            if (empty($ha)) {
                $ha = $address;
            }
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $server_ip = grab_array_var($inargs, "server_ip");
            $requested_address = grab_array_var($inargs, "requested_address");
            $interface = grab_array_var($inargs, "interface");
            $mac_address = grab_array_var($inargs, "mac_address");
            $unicast = grab_array_var($inargs, "unicast");

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('DHCP Host') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this DHCP client or server') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Basic Settings') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Server Address') . ':</label>
        </td>
        <td>
            <input type="text" size="16" name="server_ip" value="' . htmlentities($server_ip) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address of the DHCP server a response is expected from. Leave blank for any server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Requested IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="16" name="requested_address" value="' . htmlentities($requested_address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address expected to be given by the DHCP server. Leave blank for any valid address') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('MAC Address') . ':</label>
        </td>
        <td>
            <input type="text" size="26" name="mac_address" value="' . htmlentities($mac_address) . '" class="textfield form-control">
            <div class="subtext">' . _('An optional MAC address to use in the DHCP request') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Interface') . ':</label>
        </td>
        <td>
            <input type="text" size="16" name="interface" value="' . htmlentities($interface) . '" class="textfield form-control">
            <div class="subtext">' . _('The network interface to use for listening for DHCP responses (e.g eth0).  Optional.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Advanced Settings') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="uni" class="checkbox" name="unicast" ' . is_checked($unicast, "on") . '>
        </td>
        <td>
            <label for="uni" style="font-weight: normal;">
                <b>' . _('Unicast Mode') . '</b><br>
                ' . _('Use unicast mode to mimic a DHCP relay.  Requires a server address to be specified above') . '.
            </label>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $server_ip = grab_array_var($inargs, "server_ip");
            $unicast = grab_array_var($inargs, "unicast");


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if ($unicast != "" && $server_ip == "")
                $errmsg[$errors++] = _("Unicast mode requires a server IP address to be specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $server_ip = grab_array_var($inargs, "server_ip");
            $requested_address = grab_array_var($inargs, "requested_address");
            $mac_address = grab_array_var($inargs, "mac_address");
            $interface = grab_array_var($inargs, "interface");
            $unicast = grab_array_var($inargs, "unicast");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="server_ip" value="' . htmlentities($server_ip) . '">
        <input type="hidden" name="requested_address" value="' . htmlentities($requested_address) . '">
        <input type="hidden" name="mac_address" value="' . htmlentities($mac_address) . '">
        <input type="hidden" name="interface" value="' . htmlentities($interface) . '">
        <input type="hidden" name="unicast" value="' . htmlentities($unicast) . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '<p>' . _('You will need to verify that iptables is allowing access to the dhcp ports (it doesn\'t by default) before this check will work.
            You can run the following command from the command prompt to enable the ports') . '</p>
            <p>iptables -I INPUT -i eth0 -p udp --dport 67:68 --sport 67:68 -j ACCEPT</p>';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $hostaddress = $address;

            $server_ip = grab_array_var($inargs, "server_ip");
            $requested_address = grab_array_var($inargs, "requested_address");
            $mac_address = grab_array_var($inargs, "mac_address");
            $interface = grab_array_var($inargs, "interface");
            $unicast = grab_array_var($inargs, "unicast");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            /*
            echo "SERVICES<BR>";
            print_r($services);
            echo "<BR>";
            echo "SERVICEARGS<BR>";
            print_r($serviceargs);
            echo "<BR>";
            */

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["server_ip"] = $server_ip;
            $meta_arr["requested_address"] = $requested_address;
            $meta_arr["mac_address"] = $mac_address;
            $meta_arr["interface"] = $interface;
            $meta_arr["unicast"] = $unicast;
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

            // DHCP service
            $dhcpargs = "";
            if ($server_ip != "")
                $dhcpargs .= " -s " . $server_ip;
            if ($requested_address != "")
                $dhcpargs .= " -r " . $requested_address;
            if ($interface != "")
                $dhcpargs .= " -i " . $interface;
            if ($mac_address != "")
                $dhcpargs .= " -m " . $mac_address;
            if ($unicast != "")
                $dhcpargs .= " -u";

            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => "DHCP",
                "use" => "xiwizard_generic_service",
                "check_command" => "check_dhcp!" . $dhcpargs,
                "_xiwizard" => $wizard_name,
            );

            //echo "OBJECTS:<BR>";
            //print_r($objs);
            //exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


?>