<?php
//
// SSH Proxy Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

sshproxy_configwizard_init();

function sshproxy_configwizard_init()
{
    $name = "sshproxy";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.5",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a remote Linux, Unix, or Mac OS/X machine using SSH."),
        CONFIGWIZARD_DISPLAYTITLE => _("SSH Proxy"),
        CONFIGWIZARD_FUNCTION => "sshproxy_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "ssh.png",
        CONFIGWIZARD_FILTER_GROUPS => array('linux','otheros'),
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
function sshproxy_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "sshproxy";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $osdistro = grab_array_var($inargs, "osdistro", "");

            $output = '
<h5 class="ul">' . _('Server Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the server you\'d like to monitor.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Operating System') . ':</label>
        </td>
        <td>
            <select name="osdistro" id="osdistro" class="form-control">
                <option value="AIX" ' . is_selected($osdistro, "AIX") . '>AIX</option>
                <option value="FreeBSD" ' . is_selected($osdistro, "FreeBSD") . '>FreeBSD</option>
                <option value="HP-UX" ' . is_selected($osdistro, "HP-UX") . '>HP-UX</option>
                <option value="RHEL" ' . is_selected($osdistro, "RHEL") . '>Linux - RedHat Enterprise</option>
                <option value="Fedora" ' . is_selected($osdistro, "Fedora") . '>Linux - Fedora</option>
                <option value="CentOS" ' . is_selected($osdistro, "CentOS") . '>Linux - CentOS</option>
                <option value="Ubuntu" ' . is_selected($osdistro, "Ubuntu") . '>Linux - Ubuntu</option>
                <option value="Debian" ' . is_selected($osdistro, "Debian") . '>Linux - Debian</option>
                <option value="SUSE" ' . is_selected($osdistro, "SUSE") . '>Linux - SUSE Enterprise</option>
                <option value="OpenSUSE" ' . is_selected($osdistro, "OpenSUSE") . '>Linux - OpenSUSE</option>
                <option value="Linux" ' . is_selected($osdistro, "Linux") . '>Linux - Other</option>
                <option value="NetBSD" ' . is_selected($osdistro, "NetBSD") . '>NetBSD</option>
                <option value="OpenBSD" ' . is_selected($osdistro, "OpenBSD") . '>OpenBSD</option>
                <option value="Solaris" ' . is_selected($osdistro, "Solaris") . '>Solaris</option>
                <option value="Windows" ' . is_selected($osdistro, "Windows") . '>Windows</option>
                <option value="Mac" ' . is_selected($osdistro, "Mac") . '>Mac OS/X</option>
            </select>
            <div class="subtext">' . _('The operating system running on the server you\'d like to monitor.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $osdistro = grab_array_var($inargs, "osdistro", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $osdistro = grab_array_var($inargs, "osdistro", "");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $password = "";

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services)) {
                $services_default = array(
                    "ping" => 1,
                    "commands" => array(),
                );
                $services_default["commands"][0] = "on";
                $services_default["commands"][1] = "on";
                $services_default["commands"][2] = "on";
                $services = grab_array_var($inargs, "services", $services_default);
            }
            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            if (!is_array($serviceargs)) {
                $serviceargs_default = array(
                    "commands" => array(),
                );
                for ($x = 0; $x < 4; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['commands'][$x]['command'] = '/usr/local/nagios/libexec/check_disk /';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = 'Root Disk Space';
                    } else if ($x == 1) {
                        $serviceargs_default['commands'][$x]['command'] = '/usr/local/nagios/libexec/check_users -w 5 -c 10';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = 'Current Users';
                    } else if ($x == 2) {
                        $serviceargs_default['commands'][$x]['command'] = '/usr/local/nagios/libexec/check_procs -w 150 -c 170';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = 'Total Processes';
                    } else {
                        $serviceargs_default['commands'][$x]['command'] = '';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = '';
                        $services['commands'][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    }
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="osdistro" value="' . htmlentities($osdistro) . '">

<h5 class="ul">' . _('Server Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Operating System') . ':</label>
        </td>
            <td>
                <img src="' . nagioscore_get_ui_url() . "images/logos/" . sshproxy_configwizard_get_distro_icon($osdistro) . '">
                <div class="subtext">' . $osdistro . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this server.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Server Metrics') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the server.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="p" class="checkbox" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="p">
                <b>'._('Ping').'</b><br>
                ' . _('Monitors the server with an ICMP ping.  Useful for watching network latency and general uptime.') . '
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">'._('SSH Commands').'</h5>
<p>' . _('Specify any remote commands that should be executed/monitored on the server using SSH.') . ' </p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('Remote Command') . '</th>
        <th>' . _('Display Name') . '</th>
    </tr>';

            for ($x = 0; $x < count($serviceargs["commands"]); $x++) {

                $commandstring = htmlentities($serviceargs['commands'][$x]['command']);
                $commandname = htmlentities($serviceargs['commands'][$x]['name']);
                $is_checked = isset($services['commands'][$x])
                    ? is_checked($services['commands'][$x]) : '';

                $output .= '<tr>
        <td><input type="checkbox" class="checkbox" name="services[commands][' . $x . ']" ' . $is_checked . '></td>
        <td><input type="text" size="50" name="serviceargs[commands][' . $x . '][command]" value="' . $commandstring . '" class="form-control"></td>
        <td><input type="text" size="25" name="serviceargs[commands][' . $x . '][name]" value="' . $commandname . '" class="form-control"></td>
        </tr>';
            }

            $output .= '
    </table>
    <div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $osdistro = grab_array_var($inargs, "osdistro", "");

            // check for errors
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

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $osdistro = grab_array_var($inargs, "osdistro", "");

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            else
                $services = grab_array_var($inargs, "services");

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            else
                $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . $address . '">
        <input type="hidden" name="hostname" value="' . $hostname . '">
        <input type="hidden" name="osdistro" value="' . $osdistro . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $osdistro = grab_array_var($inargs, "osdistro", "");
            $hostaddress = $address;

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
            $meta_arr["osdistro"] = $osdistro;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            $icon = sshproxy_configwizard_get_distro_icon($osdistro);

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_linuxserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => $icon,
                    "statusmap_image" => $icon,
                    "_xiwizard" => $wizard_name,
                );
            }

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "ping":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Ping",
                            "use" => "xiwizard_linuxserver_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    case "commands":

                        $enabledcmds = $svcstate;
                        foreach ($enabledcmds as $pid => $pstate) {

                            $pname = $serviceargs["commands"][$pid]["command"];
                            $pdesc = $serviceargs["commands"][$pid]["name"];

                            $checkcommand = "check_xi_by_ssh!-C \"" . $pname . "\"";

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $pdesc,
                                "use" => "generic-service",
                                "check_command" => $checkcommand,
                                "_xiwizard" => $wizard_name,
                            );
                        }
                        break;

                    default:
                        break;
                }
            }

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


/**
 * @param $osdistro
 *
 * @return string
 */
function sshproxy_configwizard_get_distro_icon($osdistro)
{

    $icon = "linux.png";

    switch ($osdistro) {
        case "Solaris":
            $icon = "solaris.png";
            break;
        case "AIX":
            $icon = "aix.png";
            break;
        case "HP-UX":
            $icon = "hp-ux.png";
            break;

        case "FreeBSD":
            $icon = "freebsd2.png";
            break;
        case "NetBSD":
            $icon = "netbsd.png";
            break;
        case "OpenBSD":
            $icon = "openbsd.png";
            break;
        case "Windows":
            $icon = "windowsxp.png";
            break;
        case "RHEL":
            $icon = "redhat.png";
            break;
        case "Fedora":
            $icon = "fedora.png";
            break;
        case "CentOS":
            $icon = "centos.png";
            break;
        case "Ubuntu":
            $icon = "ubuntu.png";
            break;
        case "Debian":
            $icon = "debian.png";
            break;
        case "OpenSUSE":
            $icon = "opensuse.png";
            break;
        case "SUSE":
            $icon = "suse_enterprise.png";
            break;
        default:
            break;
    }

    return $icon;
}
