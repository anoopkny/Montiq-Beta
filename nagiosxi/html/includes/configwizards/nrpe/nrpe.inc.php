<?php
//
// NRPE Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

nrpe_configwizard_init();

function nrpe_configwizard_init()
{
    $name = "nrpe";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a remote Linux/Unix server using NRPE."),
        CONFIGWIZARD_DISPLAYTITLE => "NRPE",
        CONFIGWIZARD_FUNCTION => "nrpe_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "remote.png",
        CONFIGWIZARD_FILTER_GROUPS => array('linux','windows','otheros'),
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
function nrpe_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "nrpe";

    // Check if we were sent here for some reason... set OS distro to Linux
    $sent = grab_request_var("sent", 0);
    $address = grab_request_var("sentaddress", "");
    $osdistro = "";
    if ($sent) {
        $osdistro = "Linux";
    }

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", $address);
            $address = nagiosccm_replace_user_macros($address);

            $osdistro = grab_array_var($inargs, "osdistro", $osdistro);
            $output = '';

            if ($sent) {
                $output .= '<div class="message">
                <ul class="actionMessage">
                    <li>'._("You have been sent here from the").' <strong>'._("Linux Server Wizard").'</strong> '._("because you selected").' <strong>'._("Other").'</strong> '._("as your linux type").'.</li>
                    <li>'._("The NRPE Wizard is a similar wizard with more customizability").'.</li>
                    <li><strong><em>'._("Don't forget! You will need to install NRPE just like you would in the Linux Server Wizard").'.</em></strong></li>
                </ul>
            </div>';
            }

            $output .= '
            <h5 class="ul">'._('Server Information').'</h5>
            <table class="table table-condensed table-no-border table-auto-width">
                <tr>
                    <td class="vt">
                        <label for="address">'._('IP Address').':</label>
                    </td>
                    <td>
                        <input type="text" size="40" name="address" id="address" value="'.htmlentities($address).'" class="form-control">
                        <div class="subtext">'._("The IP address or FQDNS name of the server you'd like to monitor").'.</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <label for="osdistro">' . _('Operating System') . ':</label>
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
                        <div class="subtext">'._("The operating system running on the server you'd like to monitor").'.</div>
                    </td>
                </tr>
            </table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $osdistro = grab_array_var($inargs, "osdistro", "");

            // Check if someone is selecting Mac OS/X let's send them there...
            if ($osdistro == "Mac") {
                header("Location: monitoringwizard.php?update=1&nextstep=2&nsp=".get_nagios_session_protector_id()."&wizard=macosx&address=".$address);
            }

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (empty($address)) {
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
            $osdistro = grab_array_var($inargs, "osdistro", "");
            $ssl = grab_array_var($inargs, "ssl", "");

            $ha = @gethostbyaddr($address);
            if (empty($ha)) {
                $ha = $address;
            }
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);
            $password = "";

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }
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
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
            if (!is_array($serviceargs)) {
                $serviceargs_default = array(
                    "commands" => array(),
                );
                for ($x = 0; $x < 5; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['commands'][$x]['command'] = 'check_users';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = 'Current Users';
                    } else if ($x == 1) {
                        $serviceargs_default['commands'][$x]['command'] = 'check_load';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = 'Current Load';
                    } else if ($x == 2) {
                        $serviceargs_default['commands'][$x]['command'] = 'check_total_procs';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = 'Total Processes';
                    } else {
                        $serviceargs_default['commands'][$x]['command'] = '';
                        $serviceargs_default['commands'][$x]['args'] = '';
                        $serviceargs_default['commands'][$x]['name'] = '';
                        $services['commands'][$x] = '';
                    }
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            // Create a list of all the distros there is an NRPE installation for and get icon
            $install_distros = array("RHEL", "CentOS", "Fedora", "OpenSUSE", "SUSE", "Ubuntu", "Debian");
            $icon = nagioscore_get_ui_url() . "images/logos/" . nrpe_configwizard_get_distro_icon($osdistro);

            $agent_url = "";
            $install_doc_url = "";
            if (in_array($osdistro, $install_distros)) {
                $agent_url = "https://assets.nagios.com/downloads/nagiosxi/agents/linux-nrpe-agent.tar.gz";
                $install_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_Linux_Agent.pdf";
            } else if ($osdistro == "AIX") {
                $agent_url = "https://assets.nagios.com/downloads/nagiosxi/agents/aix-nrpe-agent.tar.gz";
                $install_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_AIX_Agent.pdf";
            } else if ($osdistro == "Solaris") {
                $agent_url = "https://assets.nagios.com/downloads/nagiosxi/agents/solaris-nrpe-agent.tar.gz";
                $install_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_Solaris_Agent.pdf";
            } else if ($osdistro == "Windows") {
                $agent32_stable_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSClient++-Stable-32.msi";
                $agent32_v043_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSCP-0.4.3-Win32.msi";
                $agent64_stable_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSClient++-Stable-64.msi";
                $agent64_v043_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSCP-0.4.3-x64.msi";
                $install_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_Windows_Agent.pdf";
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="osdistro" value="' . htmlentities($osdistro) . '">

<h5 class="ul">' . _('Server Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label for="address">' . _('IP Address') . ':</label>
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
            <img src="' . $icon . '" style="">
            <div class="subtext">' . $osdistro . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label for="hostname">' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _("The name you'd like to have associated with this host") . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('NRPE Agent') . '</h5>
<p>' . _('Specify options that should be used to communicate with the remote NRPE agent') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">';

            if (!empty($agent_url) && !empty($install_doc_url)) {
                $output .= '
                <tr>
                    <td>
                        <label>' . _('Agent Download') . ':</label>
                    </td>
                    <td>
                        <a href="'.$agent_url.'"><img src="' . theme_image("download.png") . '" style="vertical-align: middle;"></a>
                        <a href="'.$agent_url.'" style="vertical-align: middle;"><b>'._("Download Agent").'<b></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>' . _('Agent Install Instructions:') . '</label>
                    </td>
                    <td>
                        <a href="'.$install_doc_url.'"><img src="' . theme_image("page_go.png") . '"></a>
                        <a href="'.$install_doc_url.'"><b>' . _('Agent Installation Instructions') . '<b></a>
                    </td>
                </tr>';
            } elseif (!empty($agent32_stable_url) && !empty($agent32_v043_url) && !empty($agent64_stable_url) && !empty($agent64_v043_url) && !empty($install_doc_url)) {
                $output .= '<tr>
                    <td>
                        <label>' . _('Agent Download') . ':</label>
                    <td>
                        <div class="pad-t5">
                            <table class="table table-condensed table-no-border table-auto-width">
                                <thead>
                                    <tr>
                                        <th>' . _('32-Bit Agent') . '</th>
                                        <th>' . _('64-Bit Agent') . '</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <a href="' . $agent32_stable_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent32_stable_url . '"><b>' . _('Download') . ' v0.3.9 (32bit)</b></a>
                                        </td>
                                        <td>
                                            <a href="' . $agent64_stable_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent64_stable_url . '"><b>' . _('Download') . ' v0.3.9 (64bit)</b></a>
                                        </td>
                                        <td><b>('._('Recommended').')</b></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="' . $agent32_v043_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent32_v043_url . '">' . _('Download') . ' v0.4.3 (32bit)</a>
                                        </td>
                                        <td>
                                            <a href="' . $agent64_v043_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent64_v043_url . '">' . _('Download') . ' v0.4.3 (64bit)</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p>' . _('Note: Additional agent versions are available from the') . ' <a href="http://nsclient.org/download/" target="_blank">' . _('NSClient++ downloads page') . '</a>.</p>
                    </td>
                </tr>';
            }

            $output .= '
                <tr>
                    <td class="vt">
                        <label for="ssl">'._("SSL Encryption").':</label>
                    </td>
                    <td>
                        <select name="ssl" id="ssl" class="form-control">
                            <option value="on" ' . is_selected($ssl, "on") . '>' . _('Enabled (Default)') . '</option>
                            <option value="off" ' . is_selected($ssl, "off") . '>' . _('Disabled') . '</option>
                        </select>
                        <div class="subtext">' . _('Determines whether or not data between the Nagios XI server and NRPE agent is encrypted') . '.<br><b>' . _('Note') . '</b>: ' . _('Legacy NRPE installations may require that SSL support be disabled') . '.</div>
                    </td>
                </tr> 
            </table>

<h5 class="ul">' . _('Server Metrics') . '</h5>
<p>' . _("Specify which services you'd like to monitor for the server") . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="ping" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="ping">
                <b>' . _('Ping') . '</b><br>
                ' . _('Monitors the server with an ICMP Ping.  Useful for watching network latency and general uptime') . '.
            </label>
        </td>
    </tr>
</table>
    
<h5 class="ul">' . _('NRPE Commands') . '</h5>
<p>' . _('Specify any remote NRPE commands that should be monitored on the server. Multiple command arguments should be separated with a space') . '.</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin: 0;">
    <tr>
        <th></th>
        <th>' . _('Display Name') . '</th>
        <th>' . _('Remote NRPE Command') . '</th>
        <th>' . _('Command Args') . '</th>
    </tr>';

            for ($x = 0; $x < count($serviceargs['commands']); $x++) {

                $commandstring = htmlentities($serviceargs['commands'][$x]['command']);
                $commandargs = htmlentities($serviceargs['commands'][$x]['args']);
                $commandname = htmlentities($serviceargs['commands'][$x]['name']);
                $is_checked = (isset($services['commands'][$x]) ? is_checked($services['commands'][$x]) : '');

                $output .= '
                <tr>
                    <td><input type="checkbox" class="checkbox" name="services[commands][' . $x . ']" ' . $is_checked . '></td>
                    <td><input type="text" size="25" name="serviceargs[commands][' . $x . '][name]" value="' . $commandname . '" class="form-control"></td>
                    <td><input type="text" size="35" name="serviceargs[commands][' . $x . '][command]" value="' . $commandstring . '" class="form-control"></td>
                    <td><input type="text" size="40" name="serviceargs[commands][' . $x . '][args]" value="' . $commandargs . '" class="form-control"></td>
                </tr>';
            }

            $output .= '
            </table>
            <div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $osdistro = grab_array_var($inargs, "osdistro", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false) {
                $errmsg[$errors++] = "Invalid host name.";
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $osdistro = grab_array_var($inargs, "osdistro", "");
            $ssl = grab_array_var($inargs, "ssl", "on");

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            } else {
                $services = grab_array_var($inargs, "services");
            }

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            } else {
                $serviceargs = grab_array_var($inargs, "serviceargs");
            }

            $output = '
            <input type="hidden" name="address" value="' . $address . '">
            <input type="hidden" name="hostname" value="' . $hostname . '">
            <input type="hidden" name="osdistro" value="' . $osdistro . '">
            <input type="hidden" name="ssl" value="' . $ssl . '">
            <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
            <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $osdistro = grab_array_var($inargs, "osdistro", "");
            $ssl = grab_array_var($inargs, "ssl", "on");
            $hostaddress = $address;

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // Save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["osdistro"] = $osdistro;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();
            $icon = nrpe_configwizard_get_distro_icon($osdistro);

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

            // Optional non-SSL args to add
            $sslargs = "";
            if ($ssl == "off") {
                $sslargs .= " -n";
            }

            // See which services we should monitor
            foreach ($services as $svc => $svcstate) {

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
                            $pargs = $serviceargs["commands"][$pid]["args"];
                            $pdesc = $serviceargs["commands"][$pid]["name"];

                            $checkcommand = "check_nrpe!" . $pname . "!" . $sslargs;
                            if ($pargs != "")
                                $checkcommand .= " -a " . $pargs . "";

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

            // Return the object definitions to the wizard
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
function nrpe_configwizard_get_distro_icon($osdistro)
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
