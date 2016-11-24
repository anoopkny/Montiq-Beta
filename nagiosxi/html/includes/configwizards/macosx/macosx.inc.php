<?php
//
// Mac OS X Config Wizard
// Copyright (c) 2012-2016 Nagios Enterprises, LLC.  All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

macosx_configwizard_init();

function macosx_configwizard_init()
{
    $name = "macosx";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.5",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Mac OS X machine."),
        CONFIGWIZARD_DISPLAYTITLE => "Mac OS X",
        CONFIGWIZARD_FUNCTION => "macosx_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "osx.png",
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
function macosx_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "macosx";

    $agent_url = "https://assets.nagios.com/downloads/nagiosxi/agents/macosx-nrpe-agent.tar.gz";
    $agent_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_the_XI_Mac_OSX_Agent.pdf";
    $cron_daemon = "cron";
    $ssh_daemon = "ssh";
    $syslog_daemon = "syslog";

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
<h5 class="ul">OS X ' . _('Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the OS/X machine you\'d like to monitor') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");

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
            $ssl = grab_array_var($inargs, "ssl", "on");

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
                    "yum" => 1,
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

                    "memory_warning" => 80,
                    "memory_critical" => 90,

                    "load_warning" => "15,10,5",
                    "load_critical" => "30,20,10",

                    "cpustats_warning" => 85,
                    "cpustats_critical" => 95,

                    "openfiles_warning" => 30,
                    "openfiles_critical" => 50,

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
                    } else {
                        $serviceargs_default['processstate'][$x]['process'] = '';
                        $serviceargs_default['processstate'][$x]['name'] = '';

                    }
                    if (!array_key_exists($x, $services['processstate'])) $services["processstate"][$x] = "";
                }

                for ($x = 0; $x < 7; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['servicestate'][$x]['service'] = $ssh_daemon;
                        $serviceargs_default['servicestate'][$x]['name'] = "SSH Server";
                    } else if ($x == 1) {
                        $serviceargs_default['servicestate'][$x]['service'] = $cron_daemon;
                        $serviceargs_default['servicestate'][$x]['name'] = "Cron Scheduling Daemon";
                    } else if ($x == 2) {
                        $serviceargs_default['servicestate'][$x]['service'] = $syslog_daemon;
                        $serviceargs_default['servicestate'][$x]['name'] = "System Logging Daemon";
                    } else if ($x == 3) {
                        $serviceargs_default['servicestate'][$x]['service'] = "httpd";
                        $serviceargs_default['servicestate'][$x]['name'] = "Apache Web Server";
                    } else if ($x == 4) {
                        $serviceargs_default['servicestate'][$x]['service'] = "mysqld";
                        $serviceargs_default['servicestate'][$x]['name'] = "MySQL Server";
                    } else if ($x == 5) {
                        $serviceargs_default['servicestate'][$x]['service'] = "sendmail";
                        $serviceargs_default['servicestate'][$x]['name'] = "Sendmail Mail Transfer Agent";
                    } else if ($x == 6) {
                        $serviceargs_default['servicestate'][$x]['service'] = "dovecot";
                        $serviceargs_default['servicestate'][$x]['name'] = "Dovecot Mail Server";
                    }
                    if (!array_key_exists($x, $services['servicestate'])) $services["servicestate"][$x] = "";
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">OS X ' . _('Machine Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('IP Address') . ':</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this machine.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">OS X Agent</h5>
<p>' . _('You\'ll need to install an agent on the OS X in order to monitor its metrics') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Agent Download') . ':</label>
        </td>
        <td>
            <a href="' . $agent_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent_url . '"><b>' . _('Download Agent') . '<b></a>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Agent Install Instructions') . ':</label>
        </td>
        <td>
            <a href="' . $agent_doc_url . '" target="_blank"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent_doc_url . '"><b>' . _('Download Agent Installation Instructions') . '<b></a>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <b>' . _('SSL Encryption') . ':</b>
        </td>
        <td>
            <select name="ssl" id="ssl" class="form-control">
                <option value="on" ' . is_selected($ssl, "on") . '>'._('Enabled').' ('._('Default').')</option>
                <option value="off" ' . is_selected($ssl, "off") . '>'._('Disabled').'</option>
            </select>
            <div class="subtext">' . _('Determines whether or not data between the Nagios XI server and OS X agent is encrypted') . '.<br><b>' . _('Note') . '</b>: ' . _('Legacy NRPE installations may require that SSL support be disabled') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Machine Metrics') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the OS X machine') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <b>' . _('Ping') . '</b><br>
            ' . _('Monitors the machine with an ICMP "ping".  Useful for watching network latency and general uptime') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[load]"  ' . is_checked(checkbox_binary($services["load"]), "1") . '>
        </td>
        <td>
            <b>' . _('Load') . '</b><br>
            ' . _('Monitors the load on the server (1/5/15 minute values)') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_warning]" value="' . $serviceargs["load_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_critical]" value="' . $serviceargs["load_critical"] . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[cpustats]"  ' . is_checked(checkbox_binary($services["cpustats"]), "1") . '>
        </td>
        <td>
            <b>' . _('CPU Statistics') . '</b><br>
            '._('Monitors the server CPU statistics (% user, system, and idle).').'
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[memory]"  ' . is_checked(checkbox_binary($services["memory"]), "1") . '>
        </td>
        <td>
            <b>' . _('Memory Usage') . '</b><br>
            ' . _('Monitors the memory usage on the server') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . $serviceargs["memory_warning"] . '" class="textfield form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . $serviceargs["memory_critical"] . '" class="textfield form-control condensed">%
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[users]"  ' . is_checked(checkbox_binary($services["users"]), "1") . '>
        </td>
        <td>
            <b>' . _('Users') . '</b><br>
            ' . _('Monitors the number of users currently logged in to the server') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[users_warning]" value="' . $serviceargs["users_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[users_critical]" value="' . $serviceargs["users_critical"] . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[procs]"  ' . is_checked(checkbox_binary($services["procs"]), "1") . '>
        </td>
        <td>
            <b>' . _('Total Processes') . '</b><br>
            ' . _('Monitors the total number of processes running on the server') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[procs_warning]" value="' . $serviceargs["procs_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[procs_critical]" value="' . $serviceargs["procs_critical"] . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[disk]"  ' . is_checked(checkbox_binary($services["disk"]), "1") . '>
        </td>
        <td>
            <b>' . _('Disk Usage') . '</b><br>
            ' . _('Monitors disk usage on the server.  Paths can be mount points or partition names') . '.
            <div class="pad-t5">
                <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
                ';
                        for ($x = 0; $x < count($serviceargs["disk"]); $x++) {
                            $output .= '<tr>';
                            $output .= '<td><label>' . _('Path') . ':</label> <input type="text" size="10" name="serviceargs[disk][' . $x . ']" value="' . $serviceargs["disk"][$x] . '" class="form-control condensed"></td>';
                            $output .= '<td><label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_warning][' . $x . ']" value="' . htmlentities($serviceargs["disk_warning"][$x]) . '" class="form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_critical][' . $x . ']" value="' . htmlentities($serviceargs["disk_critical"][$x]) . '" class="form-control condensed"> %</td>';
                            $output .= '</tr>';
                        }
                        $output .= '
                </table>
            </div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Processes') . '</h5>
<p>' . _('Specify any process names that should be monitored to ensure they\'re running') . '.</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin: 0 0 10px 0;">
    <tr>
        <th></th>
        <th>'._('Process').'</th>
        <th>' . _('Display Name') . '</th>
    </tr>';
            
            for ($x = 0; $x < count($serviceargs['processstate']); $x++) {

                $processstring = htmlentities($serviceargs['processstate'][$x]['process']);
                $processname = htmlentities($serviceargs['processstate'][$x]['name']);
                $is_checked = isset($services["processstate"][$x])
                    ? is_checked($services["processstate"][$x]) : '';

                $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="15" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="form-control"></td><td><input type="text" size="20" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="form-control"></td></tr>';
            }

            $output .= '
</table>

<div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");

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
            $ssl = grab_array_var($inargs, "ssl", "on");

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
        <input type="hidden" name="ssl" value="' . $ssl . '">
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
            <p>' . _('Dont forget to download and install the OS/X Agent on the target machine') . '!</p>
            <p><a href="' . $agent_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent_url . '"><b>' . _('Download Agent') . '<b></a></p>
            //-->
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $ssl = grab_array_var($inargs, "ssl", "on");
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
            $meta_arr["ssl"] = $ssl;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_linuxserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "osx.png",
                    "statusmap_image" => "osx.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // optional non-SSL args to add
            $sslargs = "";
            if ($ssl == "off")
                $sslargs .= " -n";

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
                            "check_command" => "check_nrpe!check_load!-a '-w " . $serviceargs["load_warning"] . " -c " . $serviceargs["load_critical"] . "'" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "cpustats":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "CPU Stats",
                            "use" => "xiwizard_nrpe_service",
                            //   "check_command" => "check_nrpe!check_cpu_stats!-a '-w " . $serviceargs["cpustats_warning"] . " -c " . $serviceargs["cpustats_critical"] . "'" . $sslargs,
                            "check_command" => "check_nrpe!check_cpu_stats!" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_mem!-a '-w " . intval($serviceargs["memory_warning"]) . " -c " . intval($serviceargs["memory_critical"]) . "'" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;
                    case "users":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Users",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_users!-a '-w " . $serviceargs["users_warning"] . " -c " . $serviceargs["users_critical"] . "'" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "procs":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Total Processes",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_procs!-a '-w " . $serviceargs["procs_warning"] . " -c " . $serviceargs["procs_critical"] . "'" . $sslargs,
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
                                "check_command" => "check_nrpe!check_disk!-a '-w " . $serviceargs["disk_warning"][$diskid] . "% -c " . $serviceargs["disk_critical"][$diskid] . "% -p " . $diskname . "'" . $sslargs,
                                "_xiwizard" => $wizard_name,
                            );

                            $diskid++;
                        }
                        break;

                    case "servicestate":
                        if (!($RHEL || $DEB)) break;

                        $enabledservices = $svcstate;
                        foreach ($enabledservices as $sid => $sstate) {

                            $sname = $serviceargs["servicestate"][$sid]["service"];
                            $sdesc = $serviceargs["servicestate"][$sid]["name"];

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $sdesc,
                                "use" => "xiwizard_nrpe_service",
                                "check_command" => "check_nrpe!check_init_service_osx!-a '" . $sname . "'" . $sslargs,
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
                                "check_command" => "check_nrpe!check_services!-a '" . $pname . "'" . $sslargs,
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