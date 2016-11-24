<?php
//
// Windows SNMP Config Wizard
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: windowssnmp.inc.php 1676 2015-09-10 19:40:51Z lgroschen $

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

windowssnmp_configwizard_init();

function windowssnmp_configwizard_init()
{
    $name = "windowssnmp";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.8",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Microsoft&reg; Windows workstation or server using SNMP."),
        CONFIGWIZARD_DISPLAYTITLE => _("Windows SNMP"),
        CONFIGWIZARD_FUNCTION => "windowssnmp_configwizard_func",
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
function windowssnmp_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "windowssnmp";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $osversion = grab_array_var($inargs, "osversion", "");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "public");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");

            $address = nagiosccm_replace_user_macros($address);
            $snmpcommunity = nagiosccm_replace_user_macros($snmpcommunity);

            $snmpopts = "";
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial != "")
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            if (!is_array($snmpopts)) {
                $snmpopts_default = array(
                    "v3_security_level" => "authPriv",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_privacy_password" => "",
                    "v3_auth_proto" => "md5",
                    "v3_priv_proto" => "des"
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
            }

            $output = '
<script type="text/javascript">
$(document).ready(function() {
    // Initial check
    update_security_display();
    select_default_proto();

    // Detection
    $("#snmpversion").change(function() {
        update_security_display();
    });

    $("[name=\'snmpopts[v3_security_level]\']").change(function() {
        select_default_proto();
    });

    function update_security_display(version_val) {
        var version_val = $("#snmpversion").val();

        if (version_val == "3") {
            $("#auth").show();
        } else {
            $("#auth").hide();
        }
    }

    function select_default_proto(level_val) {
        var level_val = $("[name=\'snmpopts[v3_security_level]\']").val();

        if (level_val == "authNoPriv") {
            $("[name=\'snmpopts[v3_auth_proto]\']").val("md5");
            $("[name=\'snmpopts[v3_priv_proto]\']").val("");
        } else if (level_val == "authPriv") {
            $("[name=\'snmpopts[v3_auth_proto]\']").val("md5");
            $("[name=\'snmpopts[v3_priv_proto]\']").val("des");
        }
    }
});
</script>

<h5 class="ul">' . _('Windows Machine Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('IP Address').':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address of the Windows machine you\'d like to monitor.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Operating System:') . '</label>
        </td>
        <td>
            <select name="osversion" class="form-control">
                <option value="winxp" ' . is_selected($osversion, "winxp") . '>' . _('Windows XP') . '</option>
                <option value="winvista" ' . is_selected($osversion, "winvista") . '>' . _('Windows Vista') . '</option>
                <option value="win7" ' . is_selected($osversion, "win7") . '>' . _('Windows 7') . '</option>
                <option value="win2k" ' . is_selected($osversion, "win2k") . '>' . _('Windows 2000') . '</option>
                <option value="win2k3" ' . is_selected($osversion, "win2k3") . '>' . _('Windows Server 2003') . '</option>
                <option value="win2k8" ' . is_selected($osversion, "win2k8") . '>' . _('Windows Server 2008') . '</option>
                <option value="other" ' . is_selected($osversion, "other") . '>' . _('Other') . '</option>
            </select>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('SNMP Settings') . '</h5>
<p>' . _('Specify the settings used to monitor the Windows machine via SNMP.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('SNMP Community:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="snmpcommunity" id="snmpcommunity" value="' . htmlentities($snmpcommunity) . '" class="form-control">
            <div class="subtext">' . _('The SNMP community string required used to to query the Windows machine.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('SNMP Version:') . '</label>
        </td>
        <td>
            <select name="snmpversion" id="snmpversion" class="form-control">
                <!--<option value="1" ' . is_selected($snmpversion, "1") . '>1</option>-->
                <option value="2c" ' . is_selected($snmpversion, "2c") . '>2c</option>
                <option value="3" ' . is_selected($snmpversion, "3") . '>3</option>
            </select>
            <div class="subtext">' . _('The SNMP protocol version used to commicate with the machine.') . '</div>
        </td>
    </tr>
</table>

<div id="auth" class="hide">
    <h5 class="ul">' . _('SNMP Authentication') . '</h5>
    <p>' . _('When using SNMP v3 you must specify authentication information.') . '</p>
    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td>
                <label>' . _('Security Level:') . '</label>
            </td>
            <td>
                <select name="snmpopts[v3_security_level]" class="form-control">
                    <option value="authNoPriv" ' . is_selected($snmpopts["v3_security_level"], "authNoPriv") . '>authNoPriv</option>
                    <option value="authPriv" ' . is_selected($snmpopts["v3_security_level"], "authPriv") . '>authPriv</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Username:') . '</label>
            </td>
            <td>
                <input type="text" size="20" name="snmpopts[v3_username]" value="' . htmlentities($snmpopts["v3_username"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Authentication Password') . ':</label>
            </td>
            <td>
                <input type="texs" size="20" name="snmpopts[v3_auth_password]" value="' . htmlentities($snmpopts["v3_auth_password"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Privileged Password:') . '</label>
            </td>
            <td>
                <input type="text" size="20" name="snmpopts[v3_privacy_password]" value="' . htmlentities($snmpopts["v3_privacy_password"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Authentication Protocol:') . '</label>
            </td>
            <td>
                <select name="snmpopts[v3_auth_proto]" class="form-control">
                    <option value="md5" ' . is_selected($snmpopts["v3_auth_proto"], "md5") . '>MD5</option>
                    <option value="sha" ' . is_selected($snmpopts["v3_auth_proto"], "sha") . '>SHA</option>
                </select>
            </td>
        </tr>
        <tr>
            <td valign="top">
                <label>' . _('Privileged Protocol') . ':</label>
            </td>
            <td>
                <select name="snmpopts[v3_priv_proto]" class="form-control">
                    <option value="" ' . is_selected($snmpopts["v3_priv_proto"], "") . '>None</option>
                    <option value="des" ' . is_selected($snmpopts["v3_priv_proto"], "des") . '>DES</option>
                    <option value="aes" ' . is_selected($snmpopts["v3_priv_proto"], "aes") . '>AES</option>
                </select>
            </td>
        </tr>
    </table>
</div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $osversion = grab_array_var($inargs, "osversion", "");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            else if (!valid_ip($address))
                $errmsg[$errors++] = _("Invalid IP address.");
            if (have_value($osversion) == false)
                $errmsg[$errors++] = _("No operating system specified.");
            if (have_value($snmpcommunity) == false && $snmpversion != "3")
                $errmsg[$errors++] = _("No SNMP community specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname", @gethostbyaddr($address));
            $osversion = grab_array_var($inargs, "osversion", "");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            // Run snmpcommunity through user macros to account for illegal characters
            $snmpcommunity_replaced = nagiosccm_replace_user_macros($snmpcommunity);
            $snmpversion = grab_array_var($inargs, "snmpversion", "");

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial == "")
                $snmpopts = grab_array_var($inargs, "snmpopts");
            else
                $snmpopts = unserialize(base64_decode($snmpopts_serial));

            $walksuccess = 1;
            $disk = array();
            $w_services = array();
            $process = array();

            // Populate disks that were walked then offer dropdowns A-Z for new fields
            $disk_oid = "HOST-RESOURCES-MIB::hrStorageDescr";
            $w_service_oid = "SNMPv2-SMI::enterprises.77.1.2.3.1.1";
            $process_oid = "HOST-RESOURCES-MIB::hrSWRunName";

            ////////////////////////////////////////////////////////////////////////
            // Walk the process and disk OIDs to display on stage 2 select inputs //

            if ($snmpversion == "3") {

                $securitylevel = grab_array_var($snmpopts, "v3_security_level", "");
                $username = grab_array_var($snmpopts, "v3_username", "");
                $authproto = grab_array_var($snmpopts, "v3_auth_proto", "");
                $privproto = grab_array_var($snmpopts, "v3_priv_proto", "");
                $authpassword = grab_array_var($snmpopts, "v3_auth_password", "");
                $privacypassword = grab_array_var($snmpopts, "v3_privacy_password", "");
                // Run through user macros to account for illegal characters
                $username_replaced = nagiosccm_replace_user_macros($username);
                $authpassword_replaced = nagiosccm_replace_user_macros($authpassword);
                $privacypassword_replaced = nagiosccm_replace_user_macros($privacypassword);

                $disk_table = snmp3_real_walk($address, $username_replaced, $securitylevel, $authproto, $authpassword_replaced, $privproto, $privacypassword_replaced, $disk_oid, 10000000);
                $w_service_table = snmp3_real_walk($address, $username_replaced, $securitylevel, $authproto, $authpassword_replaced, $privproto, $privacypassword_replaced, $w_service_oid, 10000000);
                $process_name_table = snmp3_real_walk($address, $username_replaced, $securitylevel, $authproto, $authpassword_replaced, $privproto, $privacypassword_replaced, $process_oid, 10000000);
            } else {
                $disk_table = snmprealwalk($address, $snmpcommunity_replaced, $disk_oid, 10000000);
                $w_service_table = snmprealwalk($address, $snmpcommunity_replaced, $w_service_oid, 10000000);
                $process_name_table = snmprealwalk($address, $snmpcommunity_replaced, $process_oid, 10000000);
            }

            // If any walks fail, let user know
            if ($disk_table == false || $w_service_table == false || $process_name_table == false) {
                $walksuccess = 0;
            }

            if(!empty($disk_table)) {
                // disks
                foreach ($disk_table as $key => $val) {
                    preg_match("/(\w):\\\\/", $val, $name);

                    // Remove any without label/serial numbers (virtual, peripheral drives, etc.)
                    preg_match("/Label/", $val, $dcheck);
                    if ($dcheck[0] == "")
                        continue;
                    
                    if (isset($name[1])) {
                        $name = $name[1];
                        // use drive letter as name
                        array_push($disk, $name);
                    }
                }
                // trim repeated process names
                $disk = array_unique($disk);
            }

            if(!empty($process_name_table)) {
                // processes
                foreach ($process_name_table as $key => $val) {
                    preg_match('/"([^"]+)"/', $val, $name);
                    $name = $name[1];

                    // create array of names
                    array_push($process, '"' . $name . '"');
                }
                // trim repeated process names
                $process = array_unique($process);
                // get count
                $process_count = count($process);
                // list of processes
                $process = implode(",", $process);
            }

            // services
            if(!empty($w_service_table)) {
                foreach ($w_service_table as $key => $val) {
                    preg_match('/"([^"]+)"/', $val, $name);
                    $name = $name[1];

                    // create array of names
                    array_push($w_services, '"' .  $name . '"');
                }
                // trim repeated service names
                $w_services = array_unique($w_services);
                // get count
                $service_count = count($w_services);
                // list of services
                $w_services = implode(",", $w_services);
            }
            // end of walk section //
            /////////////////////////

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
                    if (!empty($disk)) { // we have snmpwalk data
                        $serviceargs_default["disk_warning"][$x] = 80;
                        $serviceargs_default["disk_critical"][$x] = 95;
                        $serviceargs_default["disk"][$x] = "";
                    } else { // we don't have snmpwalk data
                        $serviceargs_default["disk_warning"][$x] = 80;
                        $serviceargs_default["disk_critical"][$x] = 95;
                        $serviceargs_default["disk"][$x] = ($x == 0) ? "C" : "";
                    }
                }

                // Set scanned drives as default select option - after they are created
                if (!empty($disk)) {
                    $x = 0;
                    foreach ($disk as $key => $val) {
                        $serviceargs_default["disk"][$x] = ($x == $key) ? $val : "";
                        $x++;
                    }
                }

                for ($x = 0; $x < 4; $x++) {
                    if (!empty($process)) { // we have snmpwalk data
                        $serviceargs_default['processstate'][$x]['process'] = '';
                        $serviceargs_default['processstate'][$x]['name'] = '';
                        $services["processstate"][$x] = "";
                    } else { // we don't have snmpwalk data
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
                }

                for ($x = 0; $x < 4; $x++) {
                    if (!empty($w_services)) { // we have snmpwalk data
                        $serviceargs_default['servicestate'][$x]['service'] = "";
                        $serviceargs_default['servicestate'][$x]['name'] = "";
                        $services["servicestate"][$x] = "";
                    } else {
                        if ($x == 0) { // we don't have snmpwalk data
                            $serviceargs_default['servicestate'][$x]['service'] = "World Wide Web Publishing";
                            $serviceargs_default['servicestate'][$x]['name'] = "IIS Web Server";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        } elseif ($x == 1) {
                            $serviceargs_default['servicestate'][$x]['service'] = "Task Scheduler";
                            $serviceargs_default['servicestate'][$x]['name'] = "Task Scheduler";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        } elseif ($x == 2) {
                            $serviceargs_default['servicestate'][$x]['service'] = "Terminal Services";
                            $serviceargs_default['servicestate'][$x]['name'] = "Terminal Services";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        } else {
                            $serviceargs_default['servicestate'][$x]['service'] = "";
                            $serviceargs_default['servicestate'][$x]['name'] = "";
                            $services["servicestate"][$x] = ""; // defaults for checkboxes, enter on to be checked by default
                        }
                    }
                }


                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            // List of disks
            $disk = implode(",", $disk);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="osversion" value="' . htmlentities($osversion) . '">
<input type="hidden" name="snmpcommunity" value="' . htmlentities($snmpcommunity) . '">
<input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
<input type="hidden" name="snmpopts_serial" value="' . base64_encode(serialize($snmpopts)) . '">

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

    // error if any of the snmpwalks failed
    if ($walksuccess == 0) {
        $output .= '<div class="message" style="width: 500px; margin-top: -20px;"><ul class="errorMessage">' . _('The wizard detected that this server does not have snmpwalk permission on the target host.  This will prevent the automatic scan of services and processes and prevent services from running successfully, but you can continue with the wizard manually.  To troubleshoot this ensure that these OIDs are available on the target host: "HOST-RESOURCES-MIB::hrStorageDescr", "SNMPv2-SMI::enterprises.77.1.2.3.1.1" and "HOST-RESOURCES-MIB::hrSWRunName"') . ' <br><li></li></ul></div>';
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
                <b>Ping</b><br>
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
                <b>CPU</b><br>
                ' . _('Monitors the CPU (processor usage) on the machine.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_warning]" value="' . htmlentities($serviceargs["cpu_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_critical]" value="' . htmlentities($serviceargs["cpu_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="pmu" class="checkbox" name="services[memory]" ' . is_checked($services["memory"], "1") . '>
        </td>
        <td>
            <label class="normal" for="pmu">
                <b>' . _('Physical Memory Usage') . '</b><br>
                ' . _('Monitors the physical (real) memory usage on the machine.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . htmlentities($serviceargs["memory_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . htmlentities($serviceargs["memory_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="vmu" class="checkbox" name="services[pagefile]" ' . is_checked($services["pagefile"], "1") . '>
        </td>
        <td>
            <label class="normal" for="vmu">
                <b>' . _('Virtual Memory Usage') . '</b><br>
                ' . _('Monitors the virtual memory usage on the machine.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagefile_warning]" value="' . htmlentities($serviceargs["pagefile_warning"]) . '" class="form-control condensed"> % &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagefile_critical]" value="' . htmlentities($serviceargs["pagefile_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="du" class="checkbox" name="services[disk]" ' . is_checked($services["disk"], "1") . '>
        </td>
        <td>
            <label class="normal" for="du">
                <b>'._('Disk Usage').'</b><br>
                ' . _('Monitors disk usage on the machine.') . '
            </label>
    ';

    if (!empty($disk)) {
        $output .= '<br><div class="message" style="width:400px;"><ul class="actionMessage">' . _('The wizard will populate detected drives automatically. To add more drives select a new drive from the dropdown list.') . '<li></li></ul></div><br>';
    }

    $output .= '
            <div style="display: inline-block; vertical-align: top; height: auto;">
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
<p>' . _('Specify any services that should be monitored to ensure they\'re in a running state.') . '<br><strong>Note:</strong> ' . _('The Windows Service name must match the full name of the service you want to monitor.') . '</p>';

    // show message if service data available
    if (!empty($w_services)) {
        $output .= '
            <div class="message" style="margin-left: 20px;"><ul class="actionMessage" style="margin-top: 0;">' . _('The SNMP wizard detected ') . $service_count . _(' services on ') . $hostname . '<li></li></ul></div><br>';
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

                    $output .= '<tr><td><input type="checkbox" class="checkbox" name="services[servicestate][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="35" name="serviceargs[servicestate][' . $x . '][service]" value="' . $servicestring . '" class="form-control"></td>
                        <td><input type="text" size="20" name="serviceargs[servicestate][' . $x . '][name]" value="' . $servicename . '" class="form-control"></td></tr>';
                }
                $output .= '
    </table>
</div>';

    // only display select box if we have services to display
    if (!empty($w_services)) {
        $output .= '
        <div style="display: inline-block; vertical-align: top; height: auto; margin: 5px 0 0 10px;">
            <b>' . _("Scanned Service List") . '</b><br><select multiple id="serviceList" class="form-control condensed" style="width: 500px; margin: 5px 5px 5px 15px;" size="8"></select><br><a href="#" onClick="return false;" id="addServ">Add Selected</a>&nbsp;|&nbsp;<a href="#" onClick="return false;" name="selectAll">Select All</a>
        </div>';
    }

    $output .= '
<div style="height: 20px;"></div>

<h5 class="ul">' . _('Processes') . '</h5>
<p>' . _('Specify any processes that should be monitored to ensure they\'re running.') . '<br><strong>Note:</strong> ' . _('Process names are case-sensitive.') . '</p>';

    if (!empty($process)) {
        $output .= '
            <div class="message" style="margin-left: 20px;"><ul class="actionMessage" style="margin-top: 0;">' . _('The SNMP wizard detected ') . $process_count . _(' processes on ') . $hostname . '<li></li></ul></div><br>';
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

                    $output .= '
                    <tr><td><input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']"  ' . $is_checked . '></td><td><input type="text" size="35" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="form-control"></td><td><input type="text" size="20" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="form-control"></td></tr>';
                }
                $output .= '
    </table>
</div>';

    // only display select box if we have processes to display
    if (!empty($process)) {
        $output .= '
        <div style="display: inline-block; vertical-align: top; height: auto; margin: 5px 0 0 10px;">
            <b>' . _("Scanned Process List") . '</b><br><select multiple class="form-control condensed" id="processList" style="width: 500px; margin: 5px 5px 5px 15px;" size="8"></select><br><a href="#" onClick="return false;" id="addProc">Add Selected</a>&nbsp;|&nbsp;<a href="#" onClick="return false;" name="selectAll">Select All</a>
        </div>';
    }

    $output .= '
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

                    target = $("[name^=\'serviceargs[processstate][" + proccount + "][process]\']").filter(function() { return $(this).val() == ""; });
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
                        value = $(this).html();
                        $(this).remove();

                        // find empty input
                        target = $("[name^=\'serviceargs[servicestate][" + servcount + "][service]\']").filter(function() { return $(this).val() == ""; });
                        target.val(value);
                        target.closest("td").next("td").children("input").val(value);

                        servcount++;
                    });

                    check_box_with_value();
                } else {
                    element.remove();

                    target = $("[name^=\'serviceargs[servicestate][" + servcount + "][service]\']").filter(function() { return $(this).val() == ""; });
                    target = target[0];
                    target = target["name"];

                    $("[name=" + "\'" + target + "\'" + "]").val(value);
                    $("[name=" + "\'" + target + "\'" + "]").closest("td").next("td").children("input").val(value);

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
                element.remove();

                row_count = get_empty_field_count("service");

                // add row if needed
                if (row_count < 1) {
                    $("#serviceList").parent().prev().find("a.wizard-add-row").trigger("click");
                }

                // find empty input
                var target = $("[name^=\'serviceargs[servicestate]\']").filter(":even").filter(function() { return $(this).val() == ""; });
                target = target[0];
                target = target["name"];

                $("[name=" + "\'" + target + "\'" + "]").val(value);
                $("[name=" + "\'" + target + "\'" + "]").closest("td").next("td").children("input").val(value);

                servcount++;
                check_box_with_value();
            });
        });

        // Select all button
        $("[name=selectAll]").click(function() {
            $(this).parent().find("select option").prop(\'selected\', true);
        });

        function wizard_populate() {
            var servicelist = [' . $w_services . '];
            servicelist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var proclist = [' . $process . '];
            proclist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var service_select = $("#serviceList");
            $.each(servicelist, function(key, value) {
                service_select.append($("<option></option>").attr("value", key).text(value)); 
            });

            var process_select = $("#processList");
            $.each(proclist, function(key, value) {
                process_select.append($("<option></option>").attr("value", key).text(value)); 
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
</script>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $osversion = grab_array_var($inargs, "osversion", "");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = "Invalid host name.";

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
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial == "")
                $snmpopts = grab_array_var($inargs, "snmpopts");
            else
                $snmpopts = unserialize(base64_decode($snmpopts_serial));

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
        <input type="hidden" name="osversion" value="' . htmlentities($osversion) . '">
        <input type="hidden" name="snmpcommunity" value="' . htmlentities($snmpcommunity) . '">
        <input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
        <input type="hidden" name="snmpopts_serial" value="' . base64_encode(serialize($snmpopts)) . '">
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
            $osversion = grab_array_var($inargs, "osversion", "");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");
            $hostaddress = $address;

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            $snmpopts = unserialize(base64_decode($snmpopts_serial));

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
            $meta_arr["snmpcommunity"] = $snmpcommunity;
            $meta_arr["snmpversion"] = $snmpversion;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_windowssnmp_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "win_server.png",
                    "statusmap_image" => "win_server.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // determine SNMP args
            $snmpargs = "";
            if ($snmpcommunity != "" && $snmpversion != "3")
                $snmpargs .= " -C " . $snmpcommunity;
            if ($snmpversion == "2c")
                $snmpargs .= " --v2c";
            // snmpv3 stuff
            else if ($snmpversion == "3") {

                $securitylevel = grab_array_var($snmpopts, "v3_security_level");
                $username = grab_array_var($snmpopts, "v3_username");
                $authproto = grab_array_var($snmpopts, "v3_auth_proto");
                $privproto = grab_array_var($snmpopts, "v3_priv_proto");
                $authpassword = grab_array_var($snmpopts, "v3_auth_password");
                $privacypassword = grab_array_var($snmpopts, "v3_privacy_password");

                if ($username != "")
                    $snmpargs .= " --login=" . $username;
                if ($authpassword != "")
                    $snmpargs .= " --passwd=" . $authpassword;
                if ($privacypassword != "")
                    $snmpargs .= " --privpass=" . $privacypassword;

                if ($authproto != "" && $privproto != "") {
                    $snmpargs .= " --protocols=" . $authproto . "," . $privproto;
                } else if ($authproto != "" ) {
                    $snmpargs .= " --protocols=" . $authproto;
                }
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
                            "use" => "xiwizard_windowssnmp_load",
                            "check_command" => "check_xi_service_snmp_win_load!" . $snmpargs . " -w " . $serviceargs["cpu_warning"] . " -c " . $serviceargs["cpu_critical"] . " -f",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Physical Memory Usage",
                            "use" => "xiwizard_windowssnmp_storage",
                            "check_command" => "check_xi_service_snmp_win_storage!" . $snmpargs . " -m 'Physical Memory' -w " . $serviceargs["memory_warning"] . " -c " . $serviceargs["memory_critical"] . " -f",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagefile":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Virtual Memory Usage",
                            "use" => "xiwizard_windowssnmp_storage",
                            "check_command" => "check_xi_service_snmp_win_storage!" . $snmpargs . " -m 'Virtual Memory' -w " . $serviceargs["pagefile_warning"] . " -c " . $serviceargs["pagefile_critical"] . " -f",
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
                                "use" => "xiwizard_windowssnmp_storage",
                                "check_command" => "check_xi_service_snmp_win_storage!" . $snmpargs . " -m ^" . $diskname . ": -w " . $serviceargs["disk_warning"][$diskid] . " -c " . $serviceargs["disk_critical"][$diskid] . " -f",
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
                                "use" => "xiwizard_windowssnmp_service",
                                "check_command" => "check_xi_service_snmp_win_service!" . $snmpargs . " -r -n '" . $sname . "'",
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
                                "use" => "xiwizard_windowssnmp_process",
                                "check_command" => "check_xi_service_snmp_win_process!" . $snmpargs . " -r -n '" . $pname . "'",
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