<?php
//
// Solaris Config Wizard
// Copyright (c) 2011-2015 Nagios Enterprises, LLC. All rights reserved.
// 

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

solaris_configwizard_init();

function solaris_configwizard_init()
{
    $name = "solaris";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.4",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Solaris server."),
        CONFIGWIZARD_DISPLAYTITLE => "Solaris",
        CONFIGWIZARD_FUNCTION => "solaris_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "solaris.png",
        CONFIGWIZARD_FILTER_GROUPS => array('otheros'),
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
function solaris_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "solaris";
    $agent_url = "https://assets.nagios.com/downloads/nagiosxi/agents/solaris-nrpe-agent.tar.gz";
    $agent_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_Solaris_Agent.pdf ";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $osversion = grab_array_var($inargs, "osversion", "");

            $output = '
<h5 class="ul">' . _('Solaris Server Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the Solaris server you\'d like to monitor') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('OS Version') . ':</label>
        </td>
        <td>
            <select name="osversion" id="osversion" class="form-control">
                <option value="10" ' . is_selected($osversion, "10") . '>Solaris 10</option>
            </select>
            <div class="subtext">' . _('The version of Solaris running on the server you\'d like to monitor') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $osversion = grab_array_var($inargs, "osversion", "");

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
            $osversion = grab_array_var($inargs, "osversion", "");

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
                    "load" => 1,
                    "cpustats" => 1,
                    "memory" => 1,
                    "swap" => 1,
                    "openfiles" => 1,
                    "users" => 1,
                    "procs" => 1,
                    "disk" => 1,
                    "servicestate" => array(),
                    "processstate" => array(),
                );
                $services_default["servicestate"][0] = "on";
                $services_default["servicestate"][1] = "on";
                $services = grab_array_var($inargs, "services", $services_default);
            }
            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            if (!is_array($serviceargs)) {
                $serviceargs_default = array(

                    "memory_warning" => 15,
                    "memory_critical" => 5,

                    "load_warning" => "15,10,5",
                    "load_critical" => "30,20,10",

                    "cpustats_warning" => "70,40,30",
                    "cpustats_critical" => "90,60,40",

                    "openfiles_warning" => 3000,
                    "openfiles_critical" => 5000,

                    "swap_warning" => 50,
                    "swap_critical" => 80,

                    "users_warning" => 5,
                    "users_critical" => 10,

                    "procs_warning" => 150,
                    "procs_critical" => 250,

                    "processstate" => array(),
                    "servicestate" => array(),
                    "counter" => array(),
                );
                for ($x = 0; $x < 5; $x++) {
                    $serviceargs_default["disk_warning"][$x] = 20;
                    $serviceargs_default["disk_critical"][$x] = 10;
                    $serviceargs_default["disk"][$x] = ($x == 0) ? "/" : "";
                }
                for ($x = 0; $x < 4; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['processstate'][$x]['process'] = 'sendmail';
                        $serviceargs_default['processstate'][$x]['name'] = 'Sendmail';
                        $services['processstate'][$x] = 'on';
                    } else {
                        $serviceargs_default['processstate'][$x]['process'] = '';
                        $serviceargs_default['processstate'][$x]['name'] = '';
                        $services['processstate'][$x] = 'off';
                    }
                }

                for ($x = 0; $x < 5; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['servicestate'][$x]['service'] = "ssh";
                        $serviceargs_default['servicestate'][$x]['name'] = "SSH Server";
                    } else if ($x == 1) {
                        $serviceargs_default['servicestate'][$x]['service'] = "cron";
                        $serviceargs_default['servicestate'][$x]['name'] = "Cron Scheduling Daemon";
                    } else if ($x == 2) {
                        $serviceargs_default['servicestate'][$x]['service'] = "system-log";
                        $serviceargs_default['servicestate'][$x]['name'] = "System Logging Daemon";
                        $services["servicestate"][$x] = "off";
                    } else if ($x == 3) {
                        $serviceargs_default['servicestate'][$x]['service'] = "httpd";
                        $serviceargs_default['servicestate'][$x]['name'] = "Apache Web Server";
                        $services["servicestate"][$x] = "off";
                    } else if ($x == 4) {
                        $serviceargs_default['servicestate'][$x]['service'] = "smtp";
                        $serviceargs_default['servicestate'][$x]['name'] = "Sendmail Mail Transfer Agent";
                        $services["servicestate"][$x] = "off";
                    } else {
                        $serviceargs_default['servicestate'][$x]['service'] = "";
                        $serviceargs_default['servicestate'][$x]['name'] = "";
                        $services["servicestate"][$x] = "off";
                    }
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="osversion" value="' . htmlentities($osversion) . '">

<h5 class="ul">' . _('Solaris Server Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    ';
            $icon = nagioscore_get_ui_url() . "images/logos/" . solaris_configwizard_get_os_icon($osversion);

            $output .= '
    <tr>
        <td class="vt">
            <label>' . _('Operating System') . ':</label>
        </td>
        <td>
            <img src="' . $icon . '">
            <div class="subtext">Solaris ' . $osversion . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this Solaris server') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Solaris Agent') . '</h5>
<p>' . _('You\'ll need to install an agent on the Solaris server in order to monitor its metrics') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Agent Download') . ':</label>
        </td>
        <td>
            <a href="' . $agent_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent_url . '"><b>' . _('Download Agent') . '<b></a> (both SPARC and x86)
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Agent Install Instructions') . ':</label>
        </td>
        <td>
            <a href="' . $agent_doc_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent_doc_url . '"><b>' . _('Download Agent Installation Instructions') . '<b></a>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Server Metrics') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the Solaris server') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="p" class="checkbox" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="p">
                <b>' . _('Ping') . '</b><br>
                ' . _('Monitors the server with an ICMP "ping".  Useful for watching network latency and general uptime') . '.
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="l" class="checkbox" name="services[load]"  ' . is_checked(checkbox_binary($services["load"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="l">
                <b>' . _('Load') . '</b><br>
                ' . _('Monitors the load on the server (1,5,15 minute values)') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_warning]" value="' . $serviceargs["load_warning"] . '" class="form-control condensed"> &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_critical]" value="' . $serviceargs["load_critical"] . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="cs" class="checkbox" name="services[cpustats]"  ' . is_checked(checkbox_binary($services["cpustats"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="cs">
                <b>' . _('CPU Statistics') . '</b><br>
                '._('Monitors the server CPU statistics (user, system, iowait %s).').'
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="7" name="serviceargs[cpustats_warning]" value="' . $serviceargs["cpustats_warning"] . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="7" name="serviceargs[cpustats_critical]" value="' . $serviceargs["cpustats_critical"] . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="mu" class="checkbox" name="services[memory]"  ' . is_checked(checkbox_binary($services["memory"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="mu">
                <b>' . _('Memory Usage') . '</b><br>
                ' . _('Monitors the free memory on the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . $serviceargs["memory_warning"] . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . $serviceargs["memory_critical"] . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[swap]"  ' . is_checked(checkbox_binary($services["swap"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="su">
                <b>' . _('Swap Usage') . '</b><br>
                ' . _('Monitors the swap usage on the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[swap_warning]" value="' . $serviceargs["swap_warning"] . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[swap_critical]" value="' . $serviceargs["swap_critical"] . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="of" class="checkbox" name="services[openfiles]"  ' . is_checked(checkbox_binary($services["openfiles"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="of">
                <b>' . _('Open Files') . '</b><br>
                ' . _('Monitors the number of open files on the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="4" name="serviceargs[openfiles_warning]" value="' . $serviceargs["openfiles_warning"] . '" class="form-control condensed"> &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="4" name="serviceargs[openfiles_critical]" value="' . $serviceargs["openfiles_critical"] . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="usr" class="checkbox" name="services[users]"  ' . is_checked(checkbox_binary($services["users"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="usr">
                <b>' . _('Users') . '</b><br>
                ' . _('Monitors the number of users currently logged in to the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[users_warning]" value="' . $serviceargs["users_warning"] . '" class="form-control condensed"> &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[users_critical]" value="' . $serviceargs["users_critical"] . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tp" class="checkbox" name="services[procs]"  ' . is_checked(checkbox_binary($services["procs"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="tp">
                <b>' . _('Total Processes') . '</b><br>
                ' . _('Monitors the total number of processes running on the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[procs_warning]" value="' . $serviceargs["procs_warning"] . '" class="form-control condensed"> &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[procs_critical]" value="' . $serviceargs["procs_critical"] . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="du" class="checkbox" name="services[disk]"  ' . is_checked(checkbox_binary($services["disk"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="du">
                <b>' . _('Disk Usage') . '</b><br>
                ' . _('Monitors disk usage on the server.  Paths can be mount points or partition names') . '.
            </label>
            <div class="pad-t5">
                <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
                ';
                        for ($x = 0; $x < count($serviceargs["disk"]); $x++) {
                            $output .= '<tr>';
                            $output .= '<td><label>' . _('Path') . ':</label> <input type="text" size="10" name="serviceargs[disk][' . $x . ']" value="' . $serviceargs["disk"][$x] . '" class="form-control condensed"></td>';
                            $output .= '<td><label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_warning][' . $x . ']" value="' . htmlentities($serviceargs["disk_warning"][$x]) . '" class="form-control condensed"> % &nbsp;
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_critical][' . $x . ']" value="' . htmlentities($serviceargs["disk_critical"][$x]) . '" class="form-control condensed"> %</td>';
                            $output .= '</tr>';
                        }
                        $output .= '
                </table>
            </div>
        </td>
    </tr>
</table>';

            if (true) {

                $output .= '
<h5 class="ul">' . _('Services') . '</h5>
<p>' . _('Specify any services normally started by the SMF that should be monitored to ensure they\'re in a running state') . '.</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
        <tr>
            <th></th>
            <th>' . _('SMF Service') . '</th>
            <th>' . _('Display Name') . '</th>
        </tr>';

                for ($x = 0; $x < count($serviceargs['servicestate']); $x++) {

                    $servicestring = htmlentities($serviceargs['servicestate'][$x]['service']);
                    $servicename = htmlentities($serviceargs['servicestate'][$x]['name']);
                    $is_checked = isset($services["servicestate"][$x])
                        ? is_checked($services["servicestate"][$x]) : '';

                    $output .= '<tr>
                        <td>
                            <input type="checkbox" class="checkbox" name="services[servicestate][' . $x . ']" ' . $is_checked . '>
                        </td>
                        <td>
                            <input type="text" size="15" name="serviceargs[servicestate][' . $x . '][service]" value="' . $servicestring . '" class="form-control">
                        </td>
                        <td>
                            <input type="text" size="30" name="serviceargs[servicestate][' . $x . '][name]" value="' . $servicename . '" class="form-control">
                        </td>
                    </tr>';
                }
            }
            $output .= '
</table>
<div style="height: 20px;"></div>

<h5 class="ul">' . _('Processes') . '</h5>
<p>' . _('Specify any process names that should be monitored to ensure they\'re running') . '.</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('Solaris Process') . '</th>
        <th>' . _('Display Name') . '</th>
    </tr>';

            for ($x = 0; $x < count($serviceargs['processstate']); $x++) {

                $processstring = htmlentities($serviceargs['processstate'][$x]['process']);
                $processname = htmlentities($serviceargs['processstate'][$x]['name']);
                $is_checked = isset($services["processstate"][$x])
                    ? is_checked($services["processstate"][$x]) : '';

                $output .= '<tr>
                    <td>
                        <input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']" ' . $is_checked . '>
                    </td>
                    <td>
                        <input type="text" size="15" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="form-control">
                    </td>
                    <td>
                        <input type="text" size="30" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="form-control">
                    </td>
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
            $osversion = grab_array_var($inargs, "osversion", "");

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

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $osversion = grab_array_var($inargs, "osversion", "");

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
        <input type="hidden" name="osversion" value="' . $osversion . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            <!--
            <p>' . _('Dont forget to download and install the Solaris Agent on the target server') . '!</p>
            <p><a href="' . $agent_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent_url . '"><b>' . _('Download Agent') . '<b></a></p>
            //-->
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $osversion = grab_array_var($inargs, "osversion", "");
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
            $meta_arr["osversion"] = $osversion;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            $icon = solaris_configwizard_get_os_icon($osversion);

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


                    case "load":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Load",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_load!-a '-w " . $serviceargs["load_warning"] . " -c " . $serviceargs["load_critical"] . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "cpustats":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "CPU Stats",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_cpu_stats!-a '-w " . $serviceargs["cpustats_warning"] . " -c " . $serviceargs["cpustats_critical"] . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_mem!-a '-w " . (100 - intval($serviceargs["cpustats_warning"])) . " -c " . (100 - intval($serviceargs["cpustats_critical"])) . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "swap":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Swap Usage",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_swap!-a '-w " . (100 - intval($serviceargs["swap_warning"])) . " -c " . (100 - intval($serviceargs["swap_critical"])) . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "openfiles":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Open Files",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_open_files!-a '-w " . $serviceargs["openfiles_warning"] . " -c " . $serviceargs["openfiles_critical"] . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "users":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Users",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_users!-a '-w " . $serviceargs["users_warning"] . " -c " . $serviceargs["users_critical"] . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "procs":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Total Processes",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_all_procs!-a '-w " . $serviceargs["procs_warning"] . " -c " . $serviceargs["procs_critical"] . "'",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "disk":
                        $donedisks = array();
                        $diskid = 0;
                        foreach ($serviceargs["disk"] as $diskname) {

                            if ($diskname == "")
                                continue;

                            //echo "HANDLING DISK: $diskname<BR>";

                            // we already configured this disk
                            if (in_array($diskname, $donedisks))
                                continue;
                            $donedisks[] = $diskname;

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $diskname . " Disk Usage",
                                "use" => "xiwizard_nrpe_service",
                                "check_command" => "check_nrpe!check_disk!-a '-w " . $serviceargs["disk_warning"][$diskid] . "% -c " . $serviceargs["disk_critical"][$diskid] . "% -p " . $diskname . "'",
                                "_xiwizard" => $wizard_name,
                            );

                            $diskid++;
                        }
                        break;

                    case "servicestate":
                        //if(!($RHEL || $DEB)) break;

                        $enabledservices = $svcstate;
                        foreach ($enabledservices as $sid => $sstate) {

                            $sname = $serviceargs["servicestate"][$sid]["service"];
                            $sdesc = $serviceargs["servicestate"][$sid]["name"];

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $sdesc,
                                "use" => "xiwizard_nrpe_service",
                                "check_command" => "check_nrpe!check_init_service!-a '" . $sname . "'",
                                "_xiwizard" => $wizard_name,
                            );
                        }
                        break;

                    case "processstate":

                        $enabledprocs = $svcstate;
                        foreach ($enabledprocs as $pid => $pstate) {

                            $pname = $serviceargs["processstate"][$pid]["process"];
                            $pdesc = $serviceargs["processstate"][$pid]["name"];

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $pdesc,
                                "use" => "xiwizard_nrpe_service",
                                "check_command" => "check_nrpe!check_services!-a '" . $pname . "'",
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
 * @param $osversion
 *
 * @return string
 */
function solaris_configwizard_get_os_icon($osversion)
{

    $icon = "solaris.png";

    return $icon;
}
