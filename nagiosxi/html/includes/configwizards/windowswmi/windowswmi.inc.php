<?php
//
// Windows WMI Config Wizard
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

windowswmi_configwizard_init();

function windowswmi_configwizard_init()
{
    $name = "windowswmi";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "2.0.7",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Microsoft&reg; Windows workstation or server using WMI."),
        CONFIGWIZARD_DISPLAYTITLE => _("Windows WMI"),
        CONFIGWIZARD_FUNCTION => "windowswmi_configwizard_func",
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
function windowswmi_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    global $cfg;
    $wizard_name = "windowswmi";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $plugin_output_len = grab_array_var($inargs, "plugin_output_len", "");

            $address = nagiosccm_replace_user_macros($address);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            // Bail now if wmic is not installed
            if (!file_exists('/usr/bin/wmic')) {
                $output = '<div>
    <span style="color:red;">
    ' . _('WARNING: wmic binary has not been installed.') . '</span> ' . _('See documentation on') . '
    <a href="https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_WMI_Client_For_XI.pdf" title="WMI Documentation" target="_blank">' . _('Installing WMI') . '</a></div>';
                break;
            }

            $plugins = grab_array_var($cfg['component_info']['nagioscore'], 'plugin_dir', '/usr/local/nagios/libexec');
            // get the version of check_wmic_plus.pl
            $tmp_check_wmic_plus_ver= exec( $plugins."/check_wmi_plus.pl --version", $shell_output, $shell_return);

            if ( $shell_return != 0 )
              // something went wrong when we tried to run it, assume it isn't a version less that 1.59 (ie not -A option)
              $check_wmic_plus_ver=0.0;
            else
              // grab the verion number
              $check_wmic_plus_ver=substr($tmp_check_wmic_plus_ver,-4);

            $output = '<input type="hidden" name="check_wmic_plus_ver" value="' . htmlentities($check_wmic_plus_ver) . '">
                       <input type="hidden" name="plugin_output_len" value="' . htmlentities($plugin_output_len) . '">

<h5 class="ul">' . _('Windows Machine Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address:') . '</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address of the Windows machine you\'d like to monitor.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Auth Info') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Username:') . '</label>
        </td>
        <td>
            <input type="text" size="25" name="username" id="username" value="' . htmlentities($username) . '" class="form-control">
            <div class="subtext">' . _('The username used to connect to the Windows machine.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password:') . '</label>
        </td>
        <td>
            <input type="password" size="15" name="password" id="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _('The password used to authenticate to the Windows machine. ') . '</div>
        </td>
    </tr>';

        if ($check_wmic_plus_ver >= 1.50 ) {
    $output .= '<tr>
        <td colspan=2>' . _('Or') . '</td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Auth File:') . '</label>
        </td>
        <td>
            <input type="text" size="50" name="auth_file" id="auth_file" value="' . htmlentities($auth_file) . '" class="form-control">
            <div class="subtext">' . _('File to use with username and password for authentication.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Plugin Details') . '</h5>
<div class="subtext" style="max-width: 515px;">' . _('The check_wmi_plus.pl plugin truncates plugin output to a maximum of 8192 bytes.  Use this field to increase the plugin output length in bytes.  Leave Blank to use the default.') . '</div><br>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Truncate Output Length:') . '</label>
        </td>
        <td>
            <input type="text" size="5" name="plugin_output_len" id="plugin_output_len" value="' . htmlentities($plugin_output_len) . '" class="form-control" placeholder="8192">' . _(' bytes') . '
        </td>
    </tr>
</table>
';
        }

        $output .= '</table> ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            $no_username = 0;
            $no_password = 0;
            $no_auth_file = 0;
            $check_wmic_plus_ver = 0;

            // Try to avoid yelling at them twice for not having a username and password.
            $auth_err=0;
            $auth_good=0;

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $check_wmic_plus_ver = grab_array_var($inargs, "check_wmic_plus_ver", "");
            $plugin_output_len = grab_array_var($inargs, "plugin_output_len", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            else if (!valid_ip($address))
                $errmsg[$errors++] = _("Invalid IP address.");


            if (have_value($username) == false)
                //$errmsg[$errors++] = _("No username specified.");
              $no_username++;

            if (have_value($password) == false)
                //$errmsg[$errors++] = _("No password specified.");
              $no_password++;

            // if we can use an auth file...
            if ($check_wmic_plus_ver >= 1.50 ) {
                // see if they gave a auth file
                if (have_value($auth_file) == false)
                            //$errmsg[$errors++] = _("No Auth File specified.");
                            $no_auth_file++;
                else
                            // have file, make sure it is there and we can read it.
                            if (!is_readable($auth_file)) {
                    $errmsg[$errors++] = _("Auth File is not readable or does not exist.");
                    $auth_err=1;
                    } else
                                $auth_good=1;
                // check the auth stuff
                if (($no_auth_file>0) and (($no_password>0) or ($no_username>0))) {
                    $auth_err=1;
                    $errmsg[$errors++] = _("Please specify a username and password OR an Auth File");
                }
            }

            // If auth isn't good && we didn't have an auth error, and we have no password or username
            // error that we have no username or password.
            if (($auth_good < 1) && (($no_password>0) or ($no_username>0)) && ($auth_err < 1) )
            $errmsg[$errors++] = _("Please specify a username and password");

            // Verify truncate plugin output length is higher than default
            if ($plugin_output_len !== "") {
                if ($plugin_output_len < 8192 || $plugin_output_len == 8192)
                    $errmsg[$errors++] = _("The default value for truncate output length is 8192 bytes. Please select a value that is higher than the default.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $username_replaced = nagiosccm_replace_user_macros($username);
            $password_replaced = nagiosccm_replace_user_macros($password);
            $auth_file_replaced = nagiosccm_replace_user_macros($auth_file);
            $check_wmic_plus_ver = grab_array_var($inargs, "check_wmic_plus_ver", "");
            $plugin_output_len = grab_array_var($inargs, "plugin_output_len", "");

            // smart scan to populate wizard options
            $scansuccess = 1;
            $diskdata = array();
            $servicedata = array();
            $servicedata_tmp = array();
            $processdata = array();

            // generate commands
            if (!empty($auth_file)) {
                $disk_wmi_command = "/usr/local/nagios/libexec/check_wmi_plus.pl -H " . $address . " -A " . $auth_file_replaced . " -m checkdrivesize -a .";
                $service_wmi_command = "/usr/local/nagios/libexec/check_wmi_plus.pl -H " . $address . " -A " . $auth_file_replaced . " -m checkservice -a .";
                $process_wmi_command = "/usr/local/nagios/libexec/check_wmi_plus.pl -H " . $address . " -A " . $auth_file_replaced . " -m checkprocess -a .";
            } else {
                $disk_wmi_command = "/usr/local/nagios/libexec/check_wmi_plus.pl -H " . $address . " -u " . $username_replaced . " -p " . $password_replaced . " -m checkdrivesize -a .";
                $service_wmi_command = "/usr/local/nagios/libexec/check_wmi_plus.pl -H " . $address . " -u " . $username_replaced . " -p " . $password_replaced . " -m checkservice -a .";
                $process_wmi_command = "/usr/local/nagios/libexec/check_wmi_plus.pl -H " . $address . " -u " . $username_replaced . " -p " . $password_replaced . " -m checkprocess -a .";
            }

            // Add truncate length to the command before running
            if (!empty($plugin_output_len)) {
                $disk_wmi_command .= " --forcetruncateoutput " . $plugin_output_len;
                $service_wmi_command .= " --forcetruncateoutput " . $plugin_output_len;
                $process_wmi_command .= " --forcetruncateoutput " . $plugin_output_len;
            }

            // Run the WMI plugin to get realtime info
            exec($disk_wmi_command, $disk_output, $disk_return_var);
            exec($service_wmi_command, $service_output, $service_return_var);
            exec($process_wmi_command, $process_output, $process_return_var);

            // check if any of the plugins did not return successfully
            if ($disk_return_var !== 0 || $service_return_var !== 0 || $process_return_var !== 0) {
                // if one scan failed then use defaults
                $scansuccess = 0;

                // Create multi-level array of all errors
                $scan_errors = array();
                // remove OK output
                $d_check = (substr($disk_output[0], 0, 2) === 'OK');
                if ($d_check === false) {
                    array_push($scan_errors, $disk_output);
                }

                $s_check = (substr($service_output[0], 0, 2) === 'OK');
                if ($s_check === false) {
                    array_push($scan_errors, $service_output);
                }

                $p_check = (substr($process_output[0], 0, 2) === 'OK');
                if ($p_check === false) {
                    array_push($scan_errors, $process_output);
                }

                // Remove multiple of the same error
                $scan_errors_unique = array_map("unserialize", array_unique(array_map("serialize", $scan_errors[0])));
                $scan_errors_unique = implode("<br><br>", $scan_errors_unique);
            }

            if ($scansuccess && !empty($disk_output)) {
                // parse plugin output
                $disk_data = $disk_output[0];
                $disk_data = substr($disk_data, 0, strpos($disk_data, "|"));
                $disk_data = preg_split("/\s{5}/", $disk_data);

                foreach ($disk_data as $key => $val) {
                    if ($val != "") {
                        array_push($diskdata, '"' .  $val . '"');
                    }
                }

                // prepare for Javascript array
                $diskdata = implode(",", $diskdata);
            }

            if ($scansuccess && !empty($service_output)) {
                // parse plugin output
                $service_data = $service_output[0];
                // retrieve number of services
                preg_match("/\W\w+(\d+)/", $service_data, $service_container);
                $service_count = $service_container[0];

                // match service name , display and status
                $matcher = "/\\'([a-z0-9._()+&\\-\\\\\\/\\s]*\\'[a-z0-9._()+&\\-\\\\\\/\\s]*\\([a-z0-9._()+&\\-\\\\\\/\\s]*\\)[a-z0-9._()+&\\-\\\\\\/\\s]*)\\,\\s/i";
                preg_match_all($matcher, $service_data, $service_data);

                $service_data = str_replace("'", "", $service_data[1]);

                // push into array
                foreach ($service_data as $key => $val) {
                    // remove generic return value(s)
                    if ($val == "DisplayName (Name) is State") {
                        unset($service_data[$key]);
                        continue;
                    }

                    array_push($servicedata, $val);
                }

                foreach ($servicedata as $key => $value) {
                    $open = strrpos($value, '(') + 1;
                    $close = strrpos($value, ')');
                    $length = $close - $open;
                    $servicename_key = substr($value, $open, $length);
                    $servicedata_tmp[$servicename_key] = $value;
                }

                // encode
                $servicedata = json_encode($servicedata_tmp);
            }

            if ($scansuccess && !empty($process_output)) {
                // parse plugin output
                $process_count = $process_output[0];
                preg_match("/\W\w+(\d+)/", $process_count, $process_container);
                $process_count = $process_container[0];
                $process_data = $process_output[1];
                $process_data = preg_replace("/\d+\w/", "", $process_data);

                // remove general output message that the plugin returns - this may change if the plugin output does
                $process_data = str_replace("The process(es) found are", "", $process_data);
                $process_data = explode(",", $process_data);

                // push into array
                foreach ($process_data as $key => $val) {
                    $val = ltrim($val, " ");
                    array_push($processdata, '"' .  $val . '"');
                }

                // prepare for Javascript array
                $processdata = implode(",", $processdata);
            }
            // end scan section //
            //////////////////////

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services)) {
                $services_default = array(
                    "ping" => 1,
                    "cpu" => 1,
                    "memory" => 1,
                    "pagefile" => 1,
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
                    "cpu_warning" => 80,
                    "cpu_critical" => 90,
                    "memory_warning" => 80,
                    "memory_critical" => 90,
                    "pagefile_warning" => 80,
                    "pagefile_critical" => 90,
                );
                for ($x = 0; $x < 5; $x++) {
                    if (!empty($diskdata)) {
                        $serviceargs_default["disk_warning"][$x] = 80;
                        $serviceargs_default["disk_critical"][$x] = 95;
                        $serviceargs_default["disk"][$x] = "";
                    } else {
                        $serviceargs_default["disk_warning"][$x] = 80;
                        $serviceargs_default["disk_critical"][$x] = 95;
                        $serviceargs_default["disk"][$x] = ($x == 0) ? "C" : "";
                    }
                }

                // Set scanned drives as default select option - after they are created
                if (!empty($diskdata)) {
                    $x = 0;

                    // Set disk as a seperate array and choose each disk
                    preg_match_all("/\s-\s(\w):/", $diskdata, $disk);

                    foreach ($disk[1] as $key => $val) {
                        $serviceargs_default["disk"][$x] = ($x == $key) ? $val : "";
                        $x++;
                    }
                }

                for ($x = 0; $x < 4; $x++) {
                    // If we have WMI scan data set blank defaults
                    if (!empty($processdata)) {
                        $serviceargs_default['processstate'][$x]['process'] = '';
                        $serviceargs_default['processstate'][$x]['name'] = '';
                        $services["processstate"][$x] = "";
                    } else {
                        if ($x == 0) {
                            $serviceargs_default['processstate'][$x]['process'] = 'Explorer.exe';
                            $serviceargs_default['processstate'][$x]['name'] = 'Explorer';
                            $services["processstate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        } else {
                            $serviceargs_default['processstate'][$x]['process'] = '';
                            $serviceargs_default['processstate'][$x]['name'] = '';
                            $services["processstate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        }
                    }
                }

                for ($x = 0; $x < 4; $x++) {
                    // If we have WMI scan data set blank defaults
                    if (!empty($servicedata)) {
                        $serviceargs_default['servicestate'][$x]['service'] = "";
                        $serviceargs_default['servicestate'][$x]['name'] = "";
                        $services["servicestate"][$x] = "";
                    } else {
                        if ($x == 0) {
                            $serviceargs_default['servicestate'][$x]['service'] = "W3SVC";
                            $serviceargs_default['servicestate'][$x]['name'] = "IIS Web Server";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        } elseif ($x == 1) {
                            $serviceargs_default['servicestate'][$x]['service'] = "MSSQLSERVER";
                            $serviceargs_default['servicestate'][$x]['name'] = "SQL Server";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        } else {
                            $serviceargs_default['servicestate'][$x]['service'] = "";
                            $serviceargs_default['servicestate'][$x]['name'] = "";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        }
                    }
                }

                for ($x = 0; $x < 5; $x++) {
                    if ($x == 0) {
                        $serviceargs_default['eventlog'][$x]['log'] = 'System';
                        $serviceargs_default['eventlog'][$x]['name'] = 'System Log Critical Errors';
                        $serviceargs_default['eventlog'][$x]['severity'] = 1;
                        $serviceargs_default['eventlog'][$x]['hours'] = 1;
                        $serviceargs_default['eventlog'][$x]['warning'] = '';
                        $serviceargs_default['eventlog'][$x]['critical'] = '';
                        $services["eventlog"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else if ($x == 1) {
                        $serviceargs_default['eventlog'][$x]['log'] = 'Application';
                        $serviceargs_default['eventlog'][$x]['name'] = 'Application Log Warnings';
                        $serviceargs_default['eventlog'][$x]['severity'] = 2;
                        $serviceargs_default['eventlog'][$x]['hours'] = 1;
                        $serviceargs_default['eventlog'][$x]['warning'] = '';
                        $serviceargs_default['eventlog'][$x]['critical'] = '';
                        $services["eventlog"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    } else {
                        $serviceargs_default['eventlog'][$x]['log'] = '';
                        $serviceargs_default['eventlog'][$x]['name'] = '';
                        $serviceargs_default['eventlog'][$x]['severity'] = '';
                        $serviceargs_default['eventlog'][$x]['hours'] = '';
                        $serviceargs_default['eventlog'][$x]['warning'] = '';
                        $serviceargs_default['eventlog'][$x]['critical'] = '';
                        $services["eventlog"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                    }
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $hostname = grab_array_var($inargs, "hostname", @gethostbyaddr($address));


            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="auth_file" value="' . htmlentities($auth_file) . '">
<input type="hidden" name="check_wmic_plus_ver" value="' . htmlentities($check_wmic_plus_ver) . '">
<input type="hidden" name="plugin_output_len" value="' . htmlentities($plugin_output_len) . '">

<h5 class="ul">' . _('Windows Machine Details') . '</h5>
    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td>
                <label>' . _('IP Address:') . '</label>
            </td>
            <td>
                <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>' . _('Host Name:') . '</label>
            </td>
            <td>
                <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
                <div class="subtext">' . _('The name you\'d like to have associated with this Windows machine.') . '</div>
            </td>
        </tr>
    </table>';

    // Error if any of the WMI plugin scans failed
    if ($scansuccess == 0) {
        $output .= '
        <div class="message" style="width: 500px; margin-top: -20px;">
            <ul class="errorMessage">' . _('The wizard detected that the WMI plugin returned an unsuccessful output code. This will prevent the automatic scan of services and processes and prevent services from running successfully. Below is the given error output') . ':<br><br>';

        // Error message from plugin exec output
        $output .= '<div class="message" style="font-size: 9px; width: auto; margin-top: 10px; border: 1px solid #CCC;"><ul><b>' . _('WMI Error Output:  ') . '</b><br><br>' . $scan_errors_unique . '</ul></div>';

        $output .= '
            <li></li>
            </ul>
        </div>';
    }

    $output .= '
<h5 class="ul">' . _('Server Metrics') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the Windows machine.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="p" class="checkbox" name="services[ping]" ' . is_checked($services["ping"], "1") . '>
        </td>
        <td>
            <label class="normal" for="p">
                <b>' . _('Ping') . '</b><br>
                ' . _('Monitors the machine with an ICMP "ping".  Useful for watching network latency and general uptime.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="cpu" class="checkbox" name="services[cpu]" ' . is_checked($services["cpu"], "1") . '>
        </td>
        <td>
            <label class="normal" for="cpu">
                <b>' . _('CPU') . '</b><br>
                ' . _('Monitors the CPU (processor usage) on the machine.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_warning]" value="' . htmlentities($serviceargs["cpu_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_critical]" value="' . htmlentities($serviceargs["cpu_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="mu" class="checkbox" name="services[memory]" ' . is_checked($services["memory"], "1") . '>
        </td>
        <td>
            <label class="normal" for="mu">
                <b>' . _('Memory Usage') . '</b><br>
                ' . _('Monitors the memory usage on the machine.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . htmlentities($serviceargs["memory_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . htmlentities($serviceargs["memory_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[pagefile]" ' . is_checked($services["pagefile"], "1") . '>
        </td>
        <td>
                <b>' . _('Page File Usage') . '</b><br>
                ' . _('Monitors the page file usage on the machine.') . '<br>
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagefile_warning]" value="' . htmlentities($serviceargs["pagefile_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagefile_critical]" value="' . htmlentities($serviceargs["pagefile_critical"]) . '" class="form-control condensed"> %
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="du" class="checkbox" name="services[disk]" ' . is_checked($services["disk"], "1") . '>
        </td>
        <td>
            <label class="normal" for="du">
                <b>' . _('Disk Usage') . '</b><br>
                ' . _('Monitors disk usage on the machine.') . '
            </label><br>';

            // show message if process data available
            if (!empty($diskdata)) {
                $output .= '
                    <div class="message" style="display: inline-block;"><ul class="actionMessage">' . _('WMI plugin detected disks on ') . $hostname . '<li></li></ul></div><br>';
            }
            $output .= '
            <div style="display: inline-block; vertical-align: top; height: auto;">
                <div class="pad-t5">
                    <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">';

                for ($x = 0; $x < count($serviceargs["disk"]); $x++) {
                    $checkedstr = "";
                    if ($x == 0)
                        $checkedstr = "checked";
                    $output .= '<tr>';
                    $output .= '<td><label>' . _('Drive') . ':</label> <select name="serviceargs[disk][' . $x . ']" class="form-control condensed">';
                    $output .= '<option value=""></option>';
                    for ($y = 0; $y < 26; $y++) {
                        $selected = "";
                        $diskname = chr(ord('A') + $y);
                        $selected = is_selected($serviceargs["disk"][$x], $diskname);
                        $output .= '<option value="' . $diskname . '" ' . $selected . '>' . $diskname . ':</option>';
                    }
                    $output .= '</select></td>';
                    $output .= '<td><label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_warning][' . $x . ']" value="' . htmlentities($serviceargs["disk_warning"][$x]) . '" class="form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_critical][' . $x . ']" value="' . htmlentities($serviceargs["disk_critical"][$x]) . '" class="form-control condensed"> %</td>';
                    $output .= '</tr>';
                }
                $output .= '
                    </table>
                </div>
            </div>';

            // only display select box if we have services to display
            if (!empty($diskdata)) {
                $output .= '
                <div style="display: inline-block; vertical-align: top; height: auto; margin: 5px 0 0 10px;">
                    <b>' . _("Scanned Disk List &nbsp; (Status - Drive: Statistics)") . '</b><br><select multiple id="diskList" class="form-control condensed" style="width: 450px; margin: 5px 5px 5px 15px;" size="8"></select><br>
                </div>';
            }

            $output .= '
            </td>
        </div>
    </tr>
</table>

<h5 class="ul">' . _('Services') . '</h5>
<p>' . _('Specify any services that should be monitored to ensure they\'re in a running state.') . '</p>';

    // show message if service data available
    if (!empty($servicedata)) {
        $output .= '
            <div class="message" style="margin-left: 20px;"><ul class="actionMessage" style="margin-top: 0;">' . _('WMI plugin detected ') . $service_count . _(' services on ') . $hostname . '<li></li></ul></div><br>';
    }

    $output .= '
    <div style="display: inline-block; vertical-align: top; height: auto;">
        <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
            <tr>
                <th></th>
                <th>' . _('Windows Service') . '</th>
                <th>' . _('Display Name') . '</th>
            </tr>';

                    for ($x = 0; $x < count($serviceargs['servicestate']); $x++) {

                        $servicestring = htmlentities($serviceargs['servicestate'][$x]['service']);
                        $servicename = htmlentities($serviceargs['servicestate'][$x]['name']);
                        $is_checked = isset($services['servicestate'][$x])
                            ? is_checked($services['servicestate'][$x]) : '';

                        $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[servicestate][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="25" name="serviceargs[servicestate][' . $x . '][service]" value="' . $servicestring . '" class="form-control"></td><td><input type="text" size="25" name="serviceargs[servicestate][' . $x . '][name]" value="' . $servicename . '" class="form-control"></td></tr>';
                    }
                    $output .= '
        </table>
    </div>';

    // only display select box if we have services to display
    if (!empty($servicedata)) {
        $output .= '
        <div style="display: inline-block; vertical-align: top; height: auto; margin: 5px 0 0 10px;">
            <b>' . _("Scanned Service List &nbsp; (Service Name (Display Name) Status)") . '</b><br><select multiple id="serviceList" class="form-control condensed" style="width: 500px; margin: 5px 5px 5px 15px;" size="8"></select><br><a href="#" onClick="return false;" id="addServ">Add Selected</a>&nbsp;|&nbsp;<a href="#" onClick="return false;" name="selectAll">Select All</a>
        </div>';
    }

    $output .= '
<div style="height: 20px;"></div>

<h5 class="ul">' . _('Processes') . '</h5>
<p>' . _('Specify any processes that should be monitored to ensure they\'re running.') . '</p>';

    // show message if process data available
    if (!empty($processdata)) {
        $output .= '
            <div class="message" style="margin-left: 20px;"><ul class="actionMessage" style="margin-top: 0;">' . _('WMI plugin detected ') . $process_count . _(' processes on ') . $hostname . '<li></li></ul></div><br>';
    }

    $output .= '
    <div style="display: inline-block; vertical-align: top; height: auto;">
        <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
            <tr>
                <th></th>
                <th>' . _('Windows Process') . '</th>
                <th>' . _('Display Name') . '</th>
            </tr>';

                    for ($x = 0; $x < count($serviceargs['processstate']); $x++) {

                        $processstring = htmlentities($serviceargs['processstate'][$x]['process']);
                        $processname = htmlentities($serviceargs['processstate'][$x]['name']);
                        $is_checked = isset($services['processstate'][$x])
                            ? is_checked($services['processstate'][$x]) : '';

                        $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="15" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="form-control"></td><td><input type="text" size="20" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="form-control"></td></tr>';
                    }
                    $output .= '
        </table>
    </div>';

    // only display select box if we have processes to display
    if (!empty($processdata)) {
        $output .= '
        <div style="display: inline-block; vertical-align: top; height: auto; margin: 5px 0 0 10px;">
            <b>' . _("Scanned Process List") . '</b><br><select multiple class="form-control condensed" id="processList" style="width: 500px; margin: 5px 5px 5px 15px;" size="8"></select><br><a href="#" onClick="return false;" id="addProc">Add Selected</a>&nbsp;|&nbsp;<a href="#" onClick="return false;" name="selectAll">Select All</a>
        </div>';
    }

    $output .= '
<div style="height: 20px;"></div>

<h5 class="ul">' . _('Event Logs') . '</h5>
<p>' . _('Specify what type(s) of event log data you\'d like to monitor.') . '</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('Event Log') . '</th>
        <th>' . _('Display Name') . '</th>
        <th>' . _('Severity') . '</th>
        <th>' . _('Hours') . '</th>
        <th>' . _('Warning') . '<br>' . _('Count') . '</th>
        <th>' . _('Critical') . '<br>' . _('Count') . '</th>
    </tr>';

            for ($x = 0; $x < count($serviceargs['eventlog']); $x++) {

                $eventlog = htmlentities($serviceargs['eventlog'][$x]['log']);
                $eventname = htmlentities($serviceargs['eventlog'][$x]['name']);
                $severity = $serviceargs['eventlog'][$x]['severity'];
                $hours = htmlentities($serviceargs['eventlog'][$x]['hours']);
                $warning = htmlentities($serviceargs['eventlog'][$x]['warning']);
                $critical = htmlentities($serviceargs['eventlog'][$x]['critical']);
                $is_checked = isset($services['eventlog'][$x])
                    ? is_checked($services['eventlog'][$x]) : '';

                $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[eventlog][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="15" name="serviceargs[eventlog][' . $x . '][log]" value="' . $eventlog . '" class="form-control"></td><td><input type="text" size="25" name="serviceargs[eventlog][' . $x . '][name]" value="' . $eventname . '" class="form-control"></td>';
                $output .= '<td><select name="serviceargs[eventlog][' . $x . '][severity]" class="form-control"><option value="2" ' . is_selected($severity, 2) . '>Warnings</option><option value="1" ' . is_selected($severity, 1) . '>Errors</option></select></td>';
                $output .= '<td><input type="text" size="2" name="serviceargs[eventlog][' . $x . '][hours]" value="' . $hours . '" class="form-control"></td>';
                $output .= '<td><input type="text" size="2" name="serviceargs[eventlog][' . $x . '][warning]" value="' . $warning . '" class="form-control"></td>';
                $output .= '<td><input type="text" size="2" name="serviceargs[eventlog][' . $x . '][critical]" value="' . $critical . '" class="form-control"></td>';
                $output .= '</tr>';
            }
            $output .= '
</table>
<div style="height: 20px;"></div>

    <script type="text/javascript">
        $(document).ready(function () {
            wizard_populate();

            var proccount = 0;
            var servcount = 0;

            // smart process selecter
            $("#addProc").click( function() {
                var element = "";
                var element = $("#processList option:selected");
                var selected = element.length;
                var value = element.text();

                row_count = get_empty_field_count("process");

                if (selected > row_count) {
                    row_count = get_empty_field_count("process");

                    // count how many rows we need to trigger
                    var create_inputs = selected - row_count;

                    for (i = 0; i < create_inputs; i++) {
                        $(this).parent().prev().find("a.wizard-add-row").trigger("click");
                    }
                }

                if (selected > 1) {
                    $.each(element, function() {
                        value = $(this).html();
                        $(this).remove();

                        // find empty input
                        target = $("[name^=\'serviceargs[processstate][" + proccount + "][process]\']").filter(function() { return $(this).val() == ""; });
                        target.val(value);
                        target.closest("td").next("td").children("input").val(value);

                        proccount++;
                    });

                    check_box_with_value();
                } else {
                    element.remove();

                    target = target[0];
                    target = target["name"];

                    $("[name=" + "\'" + target + "\'" + "]").val(value);
                    $("[name=" + "\'" + target + "\'" + "]").closest("td").next("td").children("input").val(value);

                    proccount++;
                    check_box_with_value();
                }
            });

            // allow single double-click selector as well
            $("#processList").on("dblclick", "option", function() {
                var element = "";
                var element = $("#processList option:selected");
                var selected = element.length;
                var value = element.text();
                element.remove();

                row_count = get_empty_field_count("process");

                // add row if needed
                if (row_count < 1) {
                    $("#processList").parent().prev().find("a.wizard-add-row").trigger("click");
                }

                // find empty input
                var target = $("[name^=\'serviceargs[processstate]\']").filter(":even").filter(function() { return $(this).val() == ""; });
                target = target[0];
                target = target["name"];

                $("[name=" + "\'" + target + "\'" + "]").val(value);
                $("[name=" + "\'" + target + "\'" + "]").closest("td").next("td").children("input").val(value);

                proccount++;
                check_box_with_value();
            });

            // smart service selecter
            $("#addServ").click( function() {
                var element = "";
                var element = $("#serviceList option:selected");
                var selected = element.length;
                var value = element.text();

                var count = 0;
                row_count = get_empty_field_count("service");

                if (selected > row_count) {
                    row_count = get_empty_field_count("service");

                    // count how many rows we need to trigger
                    var create_inputs = selected - row_count;

                    for (i = 0; i < create_inputs; i++) {
                        $(this).parent().prev().find("a.wizard-add-row").trigger("click");
                    }
                }

                if (selected > 1) {
                    $.each(element, function() {
                        var servicename = "";
                        var displayname = "";
                        value = $(this).html();
                        $(this).remove();

                        servicename = value.split(" (", 1);
                        var displayname = $(this).val();

                        // find empty input
                        targetservice = $("[name^=\'serviceargs[servicestate][" + servcount + "][name]\']").filter(function() { return $(this).val() == ""; });
                        targetservice.val(servicename);
                        targetdisplay = $("[name^=\'serviceargs[servicestate][" + servcount + "][service]\']").filter(function() { return $(this).val() == ""; });
                        targetdisplay.val(displayname);

                        servcount++;
                    });

                    check_box_with_value();
                } else {
                    element.remove();
                    var servicename = value.split(" (", 1);
                    var displayname = element.val();

                    var targetservice = $("[name^=\'serviceargs[servicestate]\']").filter(":odd").filter(function() { return $(this).val() == ""; });
                    targetservice = targetservice[0];
                    targetservice = targetservice["name"];
                    var targetdisplay = $("[name^=\'serviceargs[servicestate]\']").filter(":even").filter(function() { return $(this).val() == ""; });
                    targetdisplay = targetdisplay[0];
                    targetdisplay = targetdisplay["name"];

                    $("[name=" + "\'" + targetservice + "\'" + "]").val(servicename);
                    $("[name=" + "\'" + targetdisplay + "\'" + "]").val(displayname);

                    servcount++;
                    check_box_with_value();
                }
            });

            // allow single double-click selector
            $("#serviceList").on("dblclick", "option", function() {
                var element = "";
                var element = $("#serviceList option:selected");
                var selected = element.length;
                var value = element.text();
                var servicename = value.split(" (", 1);
                var displayname = element.val();
                element.remove();

                row_count = get_empty_field_count("service");

                // add row if needed
                if (row_count < 1) {
                    $("#serviceList").parent().prev().find("a.wizard-add-row").trigger("click");
                }

                // find empty input
                var targetdisplay = $("[name^=\'serviceargs[servicestate]\']").filter(":even").filter(function() { return $(this).val() == ""; });
                targetdisplay = targetdisplay[0];
                targetdisplay = targetdisplay["name"];
                var targetservice = $("[name^=\'serviceargs[servicestate]\']").filter(":odd").filter(function() { return $(this).val() == ""; });
                targetservice = targetservice[0];
                targetservice = targetservice["name"];

                $("[name=" + "\'" + targetservice + "\'" + "]").val(servicename);
                $("[name=" + "\'" + targetdisplay + "\'" + "]").val(displayname);             

                servcount++;
                check_box_with_value();
            });
        });

        // Select all button
        $("[name=selectAll]").click(function() {
            $(this).parent().find("select option").prop(\'selected\', true);
        });

        function wizard_populate() {
            // populate scanned data
            var disklist = [' . $diskdata . '];
            disklist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var servicelist = [' . $servicedata . '];
            servicelist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var proclist = [' . $processdata . '];
            proclist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var disk_select = $("#diskList");
            $.each(disklist, function(key, value) {
                disk_select.append($("<option></option>").attr("value", value).text(value)); 
            });

            var service_select = $("#serviceList");
            $.each(servicelist[0], function(key, value) {
                service_select.append($("<option></option>").attr("value", key).text(value)); 
            });

            var process_select = $("#processList");
            $.each(proclist, function(key, value) {
                process_select.append($("<option></option>").attr("value", value).text(value)); 
            });
        }

        function get_empty_field_count(type) {
            var input = "";
            target = "";

            // target the correct servicetype name
            if (type == "service") {
                input = "servicestate";
            } else if (type == "process") {
                input = "processstate";
            } else {
                input = "eventlog";
            }

            // find empty input fields
            target = $("[name^=\'serviceargs[" + input + "]\']").filter(":even").filter(function() { return $(this).val() == ""; });
            var row_count = target.length;

            return row_count;
        }

        // make sure checkboxes are checked
        function check_box_with_value() {
            var servicetargets = $("input[name^=\'serviceargs[servicestate]\']").filter(":even").filter(function() { return $(this).val() !== ""; });
            var processtargets = $("input[name^=\'serviceargs[processstate]\']").filter(":even").filter(function() { return $(this).val() !== ""; });

            $.each(servicetargets, function() {
                $(this).parent().prev("td").find("input").attr("checked", true);
            });

            $.each(processtargets, function() {
                $(this).parent().prev("td").find("input").attr("checked", true);
            });
        }
    </script>
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $password = grab_array_var($inargs, "password");
            $auth_file = grab_array_var($inargs, "auth_file");
            $check_wmic_plus_ver = grab_array_var($inargs, "check_wmic_plus_ver");
            $plugin_output_len = grab_array_var($inargs, "plugin_output_len", "");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial == "")
                $services = grab_array_var($inargs, "services");
            else
                $services = unserialize(base64_decode($services_serial));

            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");

            // Make sure at least one service is chosen
            if ($services == "")
                $errmsg[$errors++] = _("You must select at least one service.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $auth_file = grab_array_var($inargs, "auth_file");
            $check_wmic_plus_ver = grab_array_var($inargs, "check_wmic_plus_ver");
            $plugin_output_len = grab_array_var($inargs, "plugin_output_len", "");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial == "")
                $services = grab_array_var($inargs, "services");
            else
                $services = unserialize(base64_decode($services_serial));

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($serviceargs_serial == "")
                $serviceargs = grab_array_var($inargs, "serviceargs");
            else
                $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $output = '
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="auth_file" value="' . htmlentities($auth_file) . '">
        <input type="hidden" name="check_wmic_plus_ver" value="' . htmlentities($check_wmic_plus_ver) . '">
        <input type="hidden" name="plugin_output_len" value="' . htmlentities($plugin_output_len) . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>--> ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $check_wmic_plus_ver = grab_array_var($inargs, "check_wmic_plus_ver");
            $plugin_output_len = grab_array_var($inargs, "plugin_output_len", "");
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $hostaddress = $address;

            // Determine the auth type
            if ( have_value($auth_file) && ($check_wmic_plus_ver > 1.49))
                // if we have an auth file use it (ignore username password)
                $CMD_ARGS="check_xi_service_wmiplus_authfile!" . $auth_file;
            else
                $CMD_ARGS="check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password);

            #replace backslash with forward slash from domain\username to domain/username -SW
            $username = str_replace("\\", "/", $username);

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["password"] = $password;
            $meta_arr["auth_file"] = $auth_file;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_windowswmi_host",
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
                            "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                            "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkcpu!-w " . escapeshellarg($serviceargs["cpu_warning"]) . " -c " . escapeshellarg($serviceargs["cpu_critical"]),
                            "check_command" => $CMD_ARGS . "!checkcpu!-w " . escapeshellarg($serviceargs["cpu_warning"]) . " -c " . escapeshellarg($serviceargs["cpu_critical"]),
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                            "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkmem!-s physical -w " . escapeshellarg($serviceargs["memory_warning"]) . " -c " . escapeshellarg($serviceargs["memory_critical"]),
                            "check_command" => $CMD_ARGS . "!checkmem!-s physical -w " . escapeshellarg($serviceargs["memory_warning"]) . " -c " . escapeshellarg($serviceargs["memory_critical"]),
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagefile":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Page File Usage",
                            "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                            "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkpage!-w " . escapeshellarg($serviceargs["pagefile_warning"]) . " -c " . escapeshellarg($serviceargs["pagefile_critical"]),
                            "check_command" => $CMD_ARGS . "!checkpage!-w " . escapeshellarg($serviceargs["pagefile_warning"]) . " -c " . escapeshellarg($serviceargs["pagefile_critical"]),

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
                                "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                                "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkdrivesize!-a " . escapeshellarg($diskname) . ": -w " . escapeshellarg($serviceargs["disk_warning"][$diskid]) . " -c " . escapeshellarg($serviceargs["disk_critical"][$diskid]),
                            "check_command" => $CMD_ARGS . "!checkdrivesize!-a " . escapeshellarg($diskname) . ": -w " . escapeshellarg($serviceargs["disk_warning"][$diskid]) . " -c " . escapeshellarg($serviceargs["disk_critical"][$diskid]),
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
                                "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                                "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkservice!-a " . escapeshellarg($sname) . " ",
                            "check_command" => $CMD_ARGS . "!checkservice!-a " . escapeshellarg($sname) . " -c _Total=1: -c 0",
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
                                "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                                "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkprocess!-s Commandline -a " . escapeshellarg($pname) . " ",
                            "check_command" => $CMD_ARGS . "!checkprocess!-s Commandline -a " . escapeshellarg($pname) . " -c _ItemCount=1:",
                                "_xiwizard" => $wizard_name,
                            );
                        }
                        break;

                    case "eventlog":

                        $enabledlogs = $svcstate;
                        foreach ($enabledlogs as $lid => $pstate) {

                            $log = $serviceargs["eventlog"][$lid]["log"];
                            $lname = $serviceargs["eventlog"][$lid]["name"];
                            $lseverity = $serviceargs["eventlog"][$lid]["severity"];
                            $lhours = $serviceargs["eventlog"][$lid]["hours"];
                            $lwarning = $serviceargs["eventlog"][$lid]["warning"];
                            $lcritical = $serviceargs["eventlog"][$lid]["critical"];

                            $lstr = escapeshellarg($log) . " -o " . $lseverity . " -3 " . $lhours;

                            $lextra = "";
                            if ($lwarning != "")
                                $lextra .= " -w " . escapeshellarg($lwarning);
                            if ($lcritical != "")
                                $lextra .= " -c " . escapeshellarg($lcritical);

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $lname,
                                "use" => "xiwizard_windowswmi_service",
// change this for auth file
//                                "check_command" => "check_xi_service_wmiplus!" . escapeshellarg($username) . "!" . escapeshellarg($password) . "!checkeventlog!-a " . $lstr . " " . $lextra,
                            "check_command" => $CMD_ARGS . "!checkeventlog!-a " . $lstr . " " . $lextra,
                                "_xiwizard" => $wizard_name,
                            );
                        }
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