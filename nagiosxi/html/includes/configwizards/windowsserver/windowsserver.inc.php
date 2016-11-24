<?php
//
// Windows Server Config Wizard
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
// 

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

windowsserver_configwizard_init();


// Check if user has old windowsdesktop
if (!function_exists('windowsdesktop_configwizard_init')) {
    function windowsdesktop_configwizard_init()
    {
        $name = "windowsdesktop";
        $args = array(
            CONFIGWIZARD_NAME => $name,
            CONFIGWIZARD_VERSION => "1.4.6",
            CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
            CONFIGWIZARD_DESCRIPTION => _("Monitor a Microsoft&reg; Windows XP, Vista, 7, 8 and 10 desktop."),
            CONFIGWIZARD_DISPLAYTITLE => _("Windows Desktop"),
            CONFIGWIZARD_FUNCTION => "windowsserver_configwizard_func",
            CONFIGWIZARD_PREVIEWIMAGE => "windowsxp.png",
            CONFIGWIZARD_FILTER_GROUPS => array('windows'),
            CONFIGWIZARD_REQUIRES_VERSION => 500
        );
        register_configwizard($name, $args);
    }

    windowsdesktop_configwizard_init();
}

function windowsserver_configwizard_init()
{
    $name = "windowsserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.6",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Microsoft&reg; Windows 2000, 2003, 2008 or 2012 server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Windows Server"),
        CONFIGWIZARD_FUNCTION => "windowsserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "win_server.png",
        CONFIGWIZARD_FILTER_GROUPS => array('windows'),
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
function windowsserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = $inargs['wizard'];
    $wizard_full_name = _('Windows Server');

    if ($inargs['wizard'] == 'windowsdesktop') {
        $wizard_full_name = _('Windows Desktop');
    }

    $agents_url = "http://nsclient.org/download/";
    $agent32_stable_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSClient++-Stable-32.msi";
    $agent32_v043_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSCP-0.4.3-Win32.msi";
    $agent64_stable_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSClient++-Stable-64.msi";
    $agent64_v043_url = "https://assets.nagios.com/downloads/nagiosxi/agents/NSClient++/NSCP-0.4.3-x64.msi";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");

            $output = '
<h5 class="ul">' . $wizard_full_name . _(' Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address of the ') . $wizard_full_name . _(' you\'d like to monitor.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            // Check for errors
            $errors = 0;
            $errmsg = array();

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
            $port = grab_array_var($inargs, "port", "12489");
            $hostname = grab_array_var($inargs, "hostname", @gethostbyaddr($address));
            $password = grab_array_var($inargs, "password", "");

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services)) {
                $services_default = array(
                    "ping" => 1,
                    "cpu" => 1,
                    "memory" => 1,
                    "uptime" => 1,
                    "disk" => 1,
                );
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

                    "cpu_warning" => 80,
                    "cpu_critical" => 90,

                    "processstate" => array(),
                    "servicestate" => array(),
                    "counter" => array(),
                );
                for ($x = 0; $x < 5; $x++) {
                    $serviceargs_default["disk_warning"][$x] = 80;
                    $serviceargs_default["disk_critical"][$x] = 95;
                    $serviceargs_default["disk"][$x] = ($x == 0) ? "C" : "";
                }
                for ($x = 0; $x < 4; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['processstate'][$x]['process'] = 'explorer.exe';
                        $serviceargs_default['processstate'][$x]['name'] = 'Explorer';
                        $services["processstate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else {
                        $serviceargs_default['processstate'][$x]['process'] = '';
                        $serviceargs_default['processstate'][$x]['name'] = '';
                        $services["processstate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    }
                }

                for ($x = 0; $x < 4; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['servicestate'][$x]['service'] = "W3SVC";
                        $serviceargs_default['servicestate'][$x]['name'] = "IIS Web Server";
                        $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else if ($x == 1) {
                        $serviceargs_default['servicestate'][$x]['service'] = "MSSQLSERVER";
                        $serviceargs_default['servicestate'][$x]['name'] = "SQL Server";
                        $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else {
                        $serviceargs_default['servicestate'][$x]['service'] = "";
                        $serviceargs_default['servicestate'][$x]['name'] = "";
                        $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    }
                }
                for ($x = 0; $x < 6; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['counter'][$x]['counter'] = "\\\\Paging File(_Total)\\\\% Usage";
                        $serviceargs_default['counter'][$x]['name'] = "Page File Usage";
                        $serviceargs_default['counter'][$x]['format'] = "Paging File usage is %.2f %%";
                        $serviceargs_default['counter'][$x]['warning'] = "70";
                        $serviceargs_default['counter'][$x]['critical'] = "90";
                        $services["counter"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else if ($x == 1) {
                        $serviceargs_default['counter'][$x]['counter'] = "\\\\Server\\\\Errors Logon";
                        $serviceargs_default['counter'][$x]['name'] = "Logon Errors";
                        $serviceargs_default['counter'][$x]['format'] = "Login Errors since last reboot is %.f";
                        $serviceargs_default['counter'][$x]['warning'] = "2";
                        $serviceargs_default['counter'][$x]['critical'] = "20";
                        $services["counter"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else if ($x == 2) {
                        $serviceargs_default['counter'][$x]['counter'] = "\\\\Server Work Queues(0)\\\\Queue Length";
                        $serviceargs_default['counter'][$x]['name'] = "Server Work Queues";
                        $serviceargs_default['counter'][$x]['format'] = "Current work queue (an indication of processing load) is %.f ";
                        $serviceargs_default['counter'][$x]['warning'] = "4";
                        $serviceargs_default['counter'][$x]['critical'] = "7";
                        $services["counter"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else {
                        $serviceargs_default['counter'][$x]['counter'] = "";
                        $serviceargs_default['counter'][$x]['name'] = "";
                        $serviceargs_default['counter'][$x]['format'] = "";
                        $serviceargs_default['counter'][$x]['warning'] = "";
                        $serviceargs_default['counter'][$x]['critical'] = "";
                        $services["counter"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    }
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . $wizard_full_name . _(' Details') . '</h5>
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
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this ') . $wizard_full_name . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Windows Agent') . '</h5>
<p>' . _('You\'ll need to install an agent on the ') . $wizard_full_name . _(' in order to monitor it.  For security purposes, it is recommended to use a password with the agent.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
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
    </tr>
    <tr>
        <td valign="top">
            <label>' . _('Agent Password') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _('Valid characters include') . ': <b>a-zA-Z0-9 .\:_-</b></div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Server Metrics') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the ') . $wizard_full_name . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="p" class="checkbox" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="p">
                <b>Ping</b><br>
                ' . _('Monitors the server with an ICMP ping.  Useful for watching network latency and general uptime.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="cpu" class="checkbox" name="services[cpu]" ' . is_checked(checkbox_binary($services["cpu"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="cpu">
                <b>' . _('CPU') . '</b><br>
                ' . _('Monitors the CPU (processor usage) on the server.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_warning]" value="' . htmlentities($serviceargs["cpu_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_critical]" value="' . htmlentities($serviceargs["cpu_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="mu" class="checkbox" name="services[memory]" ' . is_checked(checkbox_binary($services["memory"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="mu">
                <b>' . _('Memory Usage') . '</b><br>
                ' . _('Monitors the memory usage on the server.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . htmlentities($serviceargs["memory_warning"]) . '" class="form-control condensed">% &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . htmlentities($serviceargs["memory_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" id="up" class="checkbox" name="services[uptime]" ' . is_checked(checkbox_binary($services["uptime"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="up">
                <b>'._('Uptime').'</b><br>
                ' . _('Monitors the uptime on the server.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="du" class="checkbox" name="services[disk]" ' . is_checked(checkbox_binary($services["disk"]), "1") . '>
        </td>
        <td>
            <label class="normal" for="du">
                <b>'._('Disk Usage').'</b><br>
                ' . _('Monitors disk usage on the server.') . '
            </label>
            <div class="pad-t5">
                <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">';

            for ($x = 0; $x < count($serviceargs["disk"]); $x++) {
                $checkedstr = "";
                if ($x == 0)
                    $checkedstr = "checked";
                $output .= '<tr>';
                $output .= '<td><label>'._('Drive').':</label> <select name="serviceargs[disk][' . $x . ']" class="form-control condensed">';
                $output .= '<option value=""></option>';
                for ($y = 0; $y < 26; $y++) {
                    $selected = "";
                    $diskname = chr(ord('A') + $y);
                    $selected = is_selected($serviceargs["disk"][$x], $diskname);
                    $output .= '<option value="' . $diskname . '" ' . $selected . '>' . $diskname . ':</option>';
                }
                $output .= '</select></td>';
                $output .= '<td><label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_warning][' . $x . ']" value="' . htmlentities($serviceargs["disk_warning"][$x]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_critical][' . $x . ']" value="' . htmlentities($serviceargs["disk_critical"][$x]) . '" class="form-control condensed"> %</td>';
                $output .= '</tr>';
            }
            $output .= '
                </table>
            </div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Services') . '</h5>
<p>' . _('Specify any services that should be monitored to ensure they\'re in a running state.') . '</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('Windows Service') . '</th>
        <th>' . _('Display Name') . '</th>
    </tr>
    ';
            for ($x = 0; $x < count($serviceargs['servicestate']); $x++) {

                $servicestring = htmlentities($serviceargs['servicestate'][$x]['service']);
                $servicename = htmlentities($serviceargs['servicestate'][$x]['name']);
                $is_checked = isset($services['servicestate'][$x])
                    ? is_checked($services['servicestate'][$x]) : '';

                $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[servicestate][' . $x . ']" ' . $is_checked . '></td><td><input type="text" size="15" name="serviceargs[servicestate][' . $x . '][service]" value="' . $servicestring . '" class="form-control"></td><td><input type="text" size="20" name="serviceargs[servicestate][' . $x . '][name]" value="' . $servicename . '" class="form-control"></td></tr>';
            }
            $output .= '
</table>
<div style="height: 20px;"></div>

<h5 class="ul">' . _('Processes') . '</h5>
<p>' . _('Specify any processes that should be monitored to ensure they\'re running.') . '</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('Windows Process') . '</th>
        <th>' . _('Display Name') . '</th>
    </tr>
    ';
            for ($x = 0; $x < count($serviceargs['processstate']); $x++) {

                $processstring = htmlentities($serviceargs['processstate'][$x]['process']);
                $processname = htmlentities($serviceargs['processstate'][$x]['name']);
                $is_checked = isset($services['processstate'][$x])
                    ? is_checked($services['processstate'][$x]) : '';

                $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="15" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="form-control"></td><td><input type="text" size="20" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="form-control"></td></tr>';
            }
            $output .= '
</table>
<div style="height: 20px;"></div>

<h5 class="ul">' . _('Performance Counters') . '</h5>
<p>' . _('Specify any performance counters that should be monitored.') . '</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('Performance Counter') . '</th>
        <th>' . _('Display Name') . '</th>
        <th>' . _('Counter Output Format') . '</th>
        <th>' . _('Warning') . '</th>
        <th>' . _('Critical') . '</th>
    </tr>
    ';

            for ($x = 0; $x < count($serviceargs['counter']); $x++) {

                $counterstring = htmlentities($serviceargs['counter'][$x]['counter']);
                $countername = htmlentities($serviceargs['counter'][$x]['name']);
                $counterformat = htmlentities($serviceargs['counter'][$x]['format']);
                $warnlevel = htmlentities($serviceargs['counter'][$x]['warning']);
                $critlevel = htmlentities($serviceargs['counter'][$x]['critical']);
                $is_checked = isset($services['counter'][$x])
                    ? is_checked($services['counter'][$x]) : '';

                $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[counter][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="25" name="serviceargs[counter][' . $x . '][counter]" value="' . $counterstring . '" class="form-control"></td><td><input type="text" size="20" name="serviceargs[counter][' . $x . '][name]" value="' . $countername . '" class="form-control"></td><td><input type="text" size="25" name="serviceargs[counter][' . $x . '][format]" value="' . $counterformat . '" class="form-control"></td><td><input type="text" size="2" name="serviceargs[counter][' . $x . '][warning]" value="' . $warnlevel . '" class="form-control"></td><td><input type="text" size="2" name="serviceargs[counter][' . $x . '][critical]" value="' . $critlevel . '" class="form-control"></td></tr>';
            }

            $output .= '
</table>
<div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $password = grab_array_var($inargs, "password");
            $password_t = "";

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");

            if (preg_match("/[^a-zA-Z0-9 \.\\\\:_-]/", $password)) {
                if (!preg_match('/\$USER[0-9]+\$/', $password) && !preg_match('/\$[a-zA-Z]+\$/', $password)) { // Check for user macros
                    $errmsg[$errors++] = _("Password contains invalid characters.");
                }
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $password = grab_array_var($inargs, "password");

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
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
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
            <p>' . _('Dont forget to download and install the Windows Agent on the target server!') . '</p>
            <table class="standardtable">
        <thead>
        <tr>
        <th>' . _('32-Bit Agent') . '</th><th>' . _('64-Bit Agent') . '</th>
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
            <p>' . _('Newer versions of the Windows agent may be available from the') . ' <a href="http://nsclient.org/download/" target="_blank">' . _('NSClient++ downloads page') . '</a>.</p>';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $password = grab_array_var($inargs, "password", "");
            $hostaddress = $address;

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["password"] = $password;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_windowsserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "win_server.png",
                    "statusmap_image" => "win_server.png",
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
                            "use" => "xiwizard_windowsserver_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "cpu":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "CPU Usage",
                            "use" => "xiwizard_windowsserver_nsclient_service",
                            "check_command" => "check_xi_service_nsclient!" . $password . "!CPULOAD!-l 5," . $serviceargs["cpu_warning"] . "," . $serviceargs["cpu_critical"],
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_windowsserver_nsclient_service",
                            "check_command" => "check_xi_service_nsclient!" . $password . "!MEMUSE!-w " . $serviceargs["memory_warning"] . " -c " . $serviceargs["memory_critical"],
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "uptime":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Uptime",
                            "use" => "xiwizard_windowsserver_nsclient_service",
                            "check_command" => "check_xi_service_nsclient!" . $password . "!UPTIME",
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
                                "service_description" => "Drive " . $diskname . ": Disk Usage",
                                "use" => "xiwizard_windowsserver_nsclient_service",
                                "check_command" => "check_xi_service_nsclient!" . $password . "!USEDDISKSPACE!-l " . $diskname . " -w " . $serviceargs["disk_warning"][$diskid] . " -c " . $serviceargs["disk_critical"][$diskid],
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
                                "use" => "xiwizard_windowsserver_nsclient_service",
                                "check_command" => "check_xi_service_nsclient!" . $password . "!SERVICESTATE!-l " . $sname . " -d SHOWALL",
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
                                "use" => "xiwizard_windowsserver_nsclient_service",
                                "check_command" => "check_xi_service_nsclient!" . $password . "!PROCSTATE!-l " . $pname . " -d SHOWALL",
                                "_xiwizard" => $wizard_name,
                            );
                        }
                        break;

                    case "counter":

                        $enabledcounters = $svcstate;
                        foreach ($enabledcounters as $cid => $cstate) {

                            $cname = $serviceargs["counter"][$cid]["counter"];
                            $cdesc = $serviceargs["counter"][$cid]["name"];
                            $cformat = $serviceargs["counter"][$cid]["format"];
                            $cwarn = $serviceargs["counter"][$cid]["warning"];
                            $ccrit = $serviceargs["counter"][$cid]["critical"];

                            $checkcommand = "check_xi_service_nsclient!" . $password . "!COUNTER!-l \"" . $cname . "\"";
                            if ($cformat != "")
                                $checkcommand .= ",\"" . $cformat . "\"";
                            if ($cwarn != "")
                                $checkcommand .= " -w " . $cwarn;
                            if ($ccrit != "")
                                $checkcommand .= " -c " . $ccrit;

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $cdesc,
                                "use" => "xiwizard_windowsserver_nsclient_service",
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
