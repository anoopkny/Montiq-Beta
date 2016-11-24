<?php
//
// Linux Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

linux_server_configwizard_init();

function linux_server_configwizard_init()
{
    $name = "linux-server";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a remote Linux server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Linux Server"),
        CONFIGWIZARD_FUNCTION => "linux_server_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "linux-server.png",
        CONFIGWIZARD_FILTER_GROUPS => array('linux'),
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
function linux_server_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{

    $wizard_name = "linux-server";
    $linuxdistro = grab_array_var($inargs, "linuxdistro", "");

    $agent_url = "https://assets.nagios.com/downloads/nagiosxi/agents/linux-nrpe-agent.tar.gz";
    $agent_doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_Linux_Agent.pdf";

    if ($linuxdistro == "RHEL" || $linuxdistro == "CentOS" || $linuxdistro == "Fedora" || $linuxdistro == "Oracle") {
        $cron_daemon = "crond";
        $ssh_daemon = "sshd";
        $syslog_daemon = "syslog";
    } else if ($linuxdistro == "Ubuntu" || $linuxdistro == "Debian") {
        $cron_daemon = "cron";
        $ssh_daemon = "ssh";
        $syslog_daemon = "rsyslog";
    } else if ($linuxdistro == "SUSE" || $linuxdistro == "OpenSUSE") {
        $cron_daemon = "cron";
        $ssh_daemon = "sshd";
        $syslog_daemon = "rsyslog";
    } else {
        // "Other" was selected... we need to send them to the 
        // standard NRPE wizard so they can manage what they are going to monitor
        $send_to_nrpe = true;
    }

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {

        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");

            $output = '
            <h5 class="ul">'._('Linux Server Information').'</h5>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt">
                        <label for="address">'._('IP Address').':</label>
                    </td>
                    <td>
                        <input type="text" size="40" name="address" id="address" value="'.htmlentities($address).'" class="textfield form-control">
                        <div class="subtext">'._("The IP address or FQDNS name of the Linux server you'd like to monitor").'.</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <label for="linuxdistro">'._('Linux Distribution').':</label>
                    </td>
                    <td>
                        <select name="linuxdistro" id="linuxdistro" class="form-control">
                            <option value="RHEL" '.is_selected($linuxdistro, "RHEL").'>'._('RedHat Enterprise').'</option>
                            <option value="CentOS" '.is_selected($linuxdistro, "CentOS").'>'._('CentOS').'</option>
                            <option value="Fedora" '.is_selected($linuxdistro, "Fedora").'>'._('Fedora').'</option>
                            <option value="Oracle" '.is_selected($linuxdistro, "Oracle").'>'._('Oracle').'</option>
                            <option value="Ubuntu" '.is_selected($linuxdistro, "Ubuntu").'>'._('Ubuntu').'</option>
                            <option value="Debian" '.is_selected($linuxdistro, "Debian").'>'._('Debian').'</option>
                            <option value="SUSE" '.is_selected($linuxdistro, "SUSE Enterprise").'>'._('SUSE Enterprise').'</option>
                            <option value="OpenSUSE" '.is_selected($linuxdistro, "OpenSUSE").'>'._('OpenSUSE').'</option>
                            <option value="Other" '.is_selected($linuxdistro, "Other").'>'._('Other').'</option>
                        </select>
                        <div class="subtext">'._("The Linux distribution running on the server you'd like to monitor").'.</div>
                    </td>
                </tr>
            </table>
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");

            // Send to NRPE if the user selected "Other"
            if ($send_to_nrpe) {
                header("Location: monitoringwizard.php?update=1&nextstep=2&nsp=".get_nagios_session_protector_id()."&wizard=nrpe&sentaddress=".$address."&sent=1");
            }

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (empty($address)) {
                $errmsg[$errors++] = "No address specified.";
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");
            $ssl = grab_array_var($inargs, "ssl", "on");

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
                    "yum" => 1,
                    "apt" => 1,
                    "load" => 1,
                    "cpustats" => 1,
                    "memory" => 1,
                    "swap" => 1,
                    "openfiles" => 1,
                    "users" => 1,
                    "procs" => 1,
                    "disk" => 1,
                    "servicestate" => array(),
                    "processstate" => array()
                );
                $services_default["servicestate"][0] = "on";
                $services_default["servicestate"][1] = "on";
                $services = grab_array_var($inargs, "services", $services_default);
            }

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
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
                    "counter" => array()
                );
                for ($x = 0; $x < 5; $x++) {
                    $serviceargs_default["disk_warning"][$x] = 20;
                    $serviceargs_default["disk_critical"][$x] = 10;
                    $serviceargs_default["disk"][$x] = ($x == 0) ? "/" : "";
                }
                for ($x = 0; $x < 5; $x++) {
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
                        if ($linuxdistro == "Ubuntu" || $linuxdistro == "Debian") {
                            $serviceargs_default['servicestate'][$x]['service'] = "apache2";
                        } else {
                            $serviceargs_default['servicestate'][$x]['service'] = "httpd";
                        }

                        $serviceargs_default['servicestate'][$x]['name'] = "Apache Web Server";
                    } else if ($x == 4) {
                        if ($linuxdistro == "Ubuntu" || $linuxdistro == "Debian") {
                            $serviceargs_default['servicestate'][$x]['service'] = "mysql";
                        } else {
                            $serviceargs_default['servicestate'][$x]['service'] = "mysqld";
                        }

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

            $icon = nagioscore_get_ui_url() . "images/logos/" . linux_server_configwizard_get_distro_icon($linuxdistro);

            $output = '
            <input type="hidden" name="address" value="' . htmlentities($address) . '">
            <input type="hidden" name="linuxdistro" value="' . htmlentities($linuxdistro) . '">

            <h5 class="ul">' . _('Linux Server Details') . '</h5>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td>
                        <label for="address">' . _('IP Address') . ':</label>
                    </td>
                    <td>
                        <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" readonly>
                    </td>
                </tr>
                <tr>
                    <td style="vt">
                        <label>' . _('Operating System') . ':</label>
                    </td>
                    <td>
                        <img src="' . $icon . '" style="">
                        <div class="subtext">' . $linuxdistro . '</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <label for="hostname">' . _('Host Name') . ':</label>
                    </td>
                    <td>
                        <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
                        <div class="subtext">' . _("The name you'd like to have associated with this Linux server") . '.</div>
                    </td>
                </tr>
            </table>

            <h5 class="ul">' . _('Linux Agent') . '</h5>
            <p>' . _('You will need to install an agent on the Linux server in order to monitor its metrics') . '.</p>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td>
                        <label>' . _('Agent Download') . ':</label>
                    </td>
                    <td>
                        <a href="' . $agent_url . '"><img src="' . theme_image("download.png") . '" style="vertical-align: middle;"></a>
                        <a href="' . $agent_url . '" style="vertical-align: middle;"><b>'._("Download Agent").'<b></a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>' . _('Agent Install Instructions:') . '</label>
                    </td>
                    <td>
                        <a href="' . $agent_doc_url . '"><img src="' . theme_image("page_go.png") . '"></a>
                        <a href="' . $agent_doc_url . '"><b>' . _('Agent Installation Instructions') . '<b></a>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <label style="font-weight: bold;" for="ssl">'._("SSL Encryption").':</label>
                    </td>
                    <td>
                        <select name="ssl" id="ssl" class="form-control">
                            <option value="on" ' . is_selected($ssl, "on") . '>' . _('Enabled (Default)') . '</option>
                            <option value="off" ' . is_selected($ssl, "off") . '>' . _('Disabled') . '</option>
                        </select>
                        <div class="subtext">' . _('Determines whether or not data between the Nagios XI server and Linux agent is encrypted') . '.<br><b>' . _('Note') . '</b>: ' . _('Legacy NRPE installations may require that SSL support be disabled') . '.</div>
                    </td>
                </tr>
            </table>

            <h5 class="ul">' . _('Server Metrics') . '</h5>
            <p>' . _("Specify which services you'd like to monitor for the Linux server") . '.</p>
            <table class="table table-no-border table-auto-width table-padded">
                <tr>
                    <td>
                        <input type="checkbox" class="checkbox" id="ping" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
                    </td>
                    <td>
                        <label for="ping" class="select-cf-option">' . _('Ping') . '</label><br>
                        ' . _('Monitors the server with an ICMP ping.  Useful for watching network latency and general uptime') . '.
                    </td>
                </tr>';

            $RHEL = ($linuxdistro == "RHEL" || $linuxdistro == "Fedora" || $linuxdistro == "CentOS" || $linuxdistro == "Oracle");
            if ($RHEL) {
                $output .= '
                <tr>
                    <td>
                        <input type="checkbox" class="checkbox" id="yum" name="services[yum]"  ' . is_checked(checkbox_binary($services["yum"]), "1") . '>
                    </td>
                    <td>
                        <label for="yum" class="select-cf-option">' . _('Yum Update Status') . '</label><br>
                        ' . _("Monitors the server to ensure it's up to date with the latest RPM packages") . '.
                    </td>
                </tr>';
            }

            $DEB = ($linuxdistro == "Debian" || $linuxdistro == "Ubuntu");
            if ($DEB) {
                $output .= '
                <tr>
                    <td>
                        <input type="checkbox" class="checkbox" id="apt" name="services[apt]"  ' . is_checked(checkbox_binary($services["apt"]), "1") . '>
                    </td>
                    <td>
                        <label for="apt" class="select-cf-option">' . _('APT Update Status') . '</label><br>
                        ' . _("Monitors the server to ensure it's up to date with the latest DEB packages") . '.
                    </td>
                </tr>';
            }

            $SUSE = ($linuxdistro == "SUSE" || $linuxdistro == "OpenSUSE");
            $output .= '
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" id="load" name="services[load]"  ' . is_checked(checkbox_binary($services["load"]), "1") . '>
                    </td>
                    <td>
                        <label for="load" class="select-cf-option">' . _('Load') . '</label><br>
                        ' . _('Monitors the load on the server (1,5,15 minute values)') . '.
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_warning]" value="' . $serviceargs["load_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_critical]" value="' . $serviceargs["load_critical"] . '" class="textfield form-control condensed">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" id="cpu" name="services[cpustats]"  ' . is_checked(checkbox_binary($services["cpustats"]), "1") . '>
                    </td>
                    <td>
                        <label for="cpu" class="select-cf-option">' . _('CPU Statistics') . '</label><br>
                        ' . _('Monitors the server CPU statistics') . ' (% user, system, iowait, and idle)
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpustats_warning]" value="' . $serviceargs["cpustats_warning"] . '" class="textfield form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpustats_critical]" value="' . $serviceargs["cpustats_critical"] . '" class="textfield form-control condensed"> %
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" id="mem" name="services[memory]"  ' . is_checked(checkbox_binary($services["memory"]), "1") . '>
                    </td>
                    <td>
                        <label for="mem" class="select-cf-option">' . _('Memory Usage') . '</label><br>
                        ' . _('Monitors the memory usage on the server') . '.
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . $serviceargs["memory_warning"] . '" class="textfield form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . $serviceargs["memory_critical"] . '" class="textfield form-control condensed"> %
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" id="swap" name="services[swap]"  ' . is_checked(checkbox_binary($services["swap"]), "1") . '>
                    </td>
                    <td>
                        <label for="swap" class="select-cf-option">' . _('Swap Usage') . '</label><br>
                        ' . _('Monitors the swap usage on the server') . '.
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[swap_warning]" value="' . $serviceargs["swap_warning"] . '" class="textfield form-control condensed" > % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[swap_critical]" value="' . $serviceargs["swap_critical"] . '" class="textfield form-control condensed"> %
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" id="opf" name="services[openfiles]"  ' . is_checked(checkbox_binary($services["openfiles"]), "1") . '>
                    </td>
                    <td>
                        <label for="opf" class="select-cf-option">' . _('Open Files') . '</label><br>
                        ' . _('Monitors the number of open files on the server') . '.
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[openfiles_warning]" value="' . $serviceargs["openfiles_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[openfiles_critical]" value="' . $serviceargs["openfiles_critical"] . '" class="textfield form-control condensed">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" id="users" name="services[users]"  ' . is_checked(checkbox_binary($services["users"]), "1") . '>
                    </td>
                    <td>
                        <label for="users" class="select-cf-option">' . _('Users') . '</label><br>
                        ' . _('Monitors the number of users currently logged in to the server') . '.
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[users_warning]" value="' . $serviceargs["users_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[users_critical]" value="' . $serviceargs["users_critical"] . '" class="textfield form-control condensed">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" id="procs" class="checkbox" name="services[procs]"  ' . is_checked(checkbox_binary($services["procs"]), "1") . '>
                    </td>
                    <td>
                        <label for="procs" class="select-cf-option">' . _('Total Processes') . '</label><br>
                        ' . _('Monitors the total number of processes running on the server') . '.
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[procs_warning]" value="' . $serviceargs["procs_warning"] . '" class="textfield form-control condensed"> &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[procs_critical]" value="' . $serviceargs["procs_critical"] . '" class="textfield form-control condensed">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" id="disk" class="checkbox" name="services[disk]"  ' . is_checked(checkbox_binary($services["disk"]), "1") . '>
                    </td>
                    <td>
                        <label for="disk" class="select-cf-option">' . _('Disk Usage') . '</label><br>
                        ' . _('Monitors disk usage on the server.  Paths can be mount points or partition names') . '.
                        <div class="pad-t5">
                            <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
                            ';
                            for ($x = 0; $x < count($serviceargs["disk"]); $x++) {
                                $output .= '<tr>';
                                $output .= '<td><label>'._("Path").': <input type="text" size="10" class="form-control condensed" name="serviceargs[disk][' . $x . ']" value="' . $serviceargs["disk"][$x] . '"></label> &nbsp; ';
                                $output .= ' <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" class="form-control condensed" name="serviceargs[disk_warning][' . $x . ']" value="' . htmlentities($serviceargs["disk_warning"][$x]) . '"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_critical][' . $x . ']" value="' . htmlentities($serviceargs["disk_critical"][$x]) . '" class="form-control condensed"> %</td>';
                                $output .= '</tr>';
                            }
                            $output .= '
                            </table>
                        </div>
                    </td>
                </tr>
            </table>';

            if ($RHEL || $DEB || $SUSE) {

            $output .= '
            <h5 class="ul">' . _('Services') . '</h5>
            <p>' . _('Specify any services normally started by the init process that should be monitored to ensure they\'re in a running state') . '.</p>
            <table class="table table-condensed table-no-border table-auto-width adddeleterow" style="margin: 0 0 10px 0;">
                <tr>
                    <th></th>
                    <th>init.d '._("Service").'</th>
                    <th>' . _('Display Name') . '</th>
                </tr>';
                for ($x = 0; $x < count($serviceargs["servicestate"]); $x++) {

                    $servicestring = htmlentities($serviceargs['servicestate'][$x]['service']);
                    $servicename = htmlentities($serviceargs['servicestate'][$x]['name']);
                    $is_checked = (isset($services["servicestate"][$x]) ? is_checked($services["servicestate"][$x]) : '');

                    $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" class="checkbox" name="services[servicestate][' . $x . ']" ' . $is_checked . '>
                        </td>
                        <td>
                            <input type="text" size="15" name="serviceargs[servicestate][' . $x . '][service]" value="' . $servicestring . '" class="textfield form-control">
                        </td>
                        <td>
                            <input type="text" size="30" name="serviceargs[servicestate][' . $x . '][name]" value="' . $servicename . '" class="textfield form-control">
                        </td>
                    </tr>';
                }
            }
            $output .= '
            </table>

            <h5 class="ul" style="margin-top: 20px;">' . _('Processes') . '</h5>
            <p>' . _("Specify any process names that should be monitored to ensure they're running") . '.</p>
            <table class="table table-condensed table-no-border table-auto-width adddeleterow" style="margin: 0 0 10px 0;">
                <tr>
                    <th></th>
                    <th>' . _('Linux Process') . '</th>
                    <th>' . _('Display Name') . '</th>
                </tr>';
                for ($x = 0; $x < count($serviceargs["processstate"]); $x++) {

                $processstring = htmlentities($serviceargs['processstate'][$x]['process']);
                $processname = htmlentities($serviceargs['processstate'][$x]['name']);
                $is_checked = (isset($services["processstate"][$x]) ? is_checked($services["processstate"][$x]) : '');

                $output .= '
                <tr>
                    <td>
                        <input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']" ' . $is_checked . '>
                    </td>
                    <td>
                        <input type="text" size="15" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="textfield form-control">
                    </td>
                    <td>
                        <input type="text" size="30" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="textfield form-control">
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
            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");

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
            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");
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
            <input type="hidden" name="linuxdistro" value="' . $linuxdistro . '">
            <input type="hidden" name="ssl" value="' . $ssl . '">
            <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
            <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $output = '
            <p>' . _('Dont forget to download and install the Linux Agent on the target server') . '!</p>
            <table class="section">
                <tr>
                    <td style="vertical-align: top;">
                        <label>' . _('Agent Download') . ':</label>
                    </td>
                    <td>
                        <a href="' . $agent_url . '"><img src="' . theme_image("download.png") . '" style="vertical-align: middle;"></a>
                        <a href="' . $agent_url . '" style="vertical-align: middle;"><b>'._("Download Agent").'<b></a>
                        <div class="subtext"></div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top;">
                        <label>' . _('Agent Install Instructions:') . '</label>
                    </td>
                    <td>
                        <a href="' . $agent_doc_url . '"><img src="' . theme_image("download.png") . '"></a>
                        <a href="' . $agent_doc_url . '"><b>' . _('Download Agent Installation Instructions') . '<b></a>
                        <div class="subtext"></div>
                    </td>
                </tr>
                <tr>
                    <td class="category">
                        <label style="font-weight: bold;" for="ssl">'._("SSL Encryption").':</label>
                    </td>
                    <td>
                        <select name="ssl" id="ssl">
                            <option value="on" ' . is_selected($ssl, "on") . '>' . _('Enabled (Default)') . '</option>
                            <option value="off" ' . is_selected($ssl, "off") . '>' . _('Disabled') . '</option>
                        </select>
                        <div class="subtext">' . _('Determines whether or not data between the Nagios XI server and Linux agent is encrypted') . '.<br><b>' . _('Note') . '</b>: ' . _('Legacy NRPE installations may require that SSL support be disabled') . '.</div>
                    </td>
                </tr>
            </table>';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $linuxdistro = grab_array_var($inargs, "linuxdistro", "");
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
            $meta_arr["linuxdistro"] = $linuxdistro;
            $meta_arr["ssl"] = $ssl;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();
            $icon = linux_server_configwizard_get_distro_icon($linuxdistro);

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

            $RHEL = ($linuxdistro == "RHEL" || $linuxdistro == "Fedora" || $linuxdistro == "CentOS" || $linuxdistro == "Oracle");
            $DEB = ($linuxdistro == "Debian" || $linuxdistro == "Ubuntu");
            $SUSE = ($linuxdistro == "SUSE" || $linuxdistro == "OpenSUSE");

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

                    case "yum":
                        if (!$RHEL) break;
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Yum Updates",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_yum!" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "apt":
                        if (!$DEB) break;
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "APT Updates",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_apt!-a '-U'" . $sslargs,
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
                            "check_command" => "check_nrpe!check_cpu_stats!-a '-w " . $serviceargs["cpustats_warning"] . " -c " . $serviceargs["cpustats_critical"] . "'" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_mem!-a '-w " . (100 - intval($serviceargs["memory_warning"])) . " -c " . (100 - intval($serviceargs["memory_critical"])) . "'" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "swap":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Swap Usage",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_swap!-a '-w " . (100 - intval($serviceargs["swap_warning"])) . " -c " . (100 - intval($serviceargs["swap_critical"])) . "'" . $sslargs,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "openfiles":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Open Files",
                            "use" => "xiwizard_nrpe_service",
                            "check_command" => "check_nrpe!check_open_files!-a '-w " . $serviceargs["openfiles_warning"] . " -c " . $serviceargs["openfiles_critical"] . "'" . $sslargs,
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

                            if ($diskname == "") {
                                continue;
                            }

                            // We already configured this disk
                            if (in_array($diskname, $donedisks)) {
                                continue;
                            }
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
                        $enabledservices = $svcstate;
                        foreach ($enabledservices as $sid => $sstate) {

                            $sname = $serviceargs["servicestate"][$sid]["service"];
                            $sdesc = $serviceargs["servicestate"][$sid]["name"];

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $sdesc,
                                "use" => "xiwizard_nrpe_service",
                                "check_command" => "check_nrpe!check_init_service!-a '" . $sname . "'" . $sslargs,
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

            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


/**
 * @param $linuxdistro
 *
 * @return string
 */
function linux_server_configwizard_get_distro_icon($linuxdistro)
{

    $icon = "linux-server.png";

    switch ($linuxdistro) {
        case "RHEL":
            $icon = "redhat.png";
            break;
        case "Fedora":
            $icon = "fedora.png";
            break;
        case "CentOS":
            $icon = "centos.png";
            break;
        case "Oracle":
            $icon = "oracle-linux.gif";
            break;
        case "Ubuntu":
            $icon = "ubuntu.png";
            break;
        case "Debian":
            $icon = "debian.png";
            break;
        case "SUSE":
            $icon = "suse_enterprise.png";
            break;
        case "OpenSUSE":
            $icon = "opensuse.png";
            break;
        case "Arch":
            break;
        default:
            break;
    }

    return $icon;
}
