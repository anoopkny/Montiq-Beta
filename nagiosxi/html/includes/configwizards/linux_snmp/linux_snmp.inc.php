<?php
//
// Linux SNMP Config Wizard
// Copyright (c) 2011-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id: linux_snmp.inc.php 1531 2015-05-21 19:49:29Z lgroschen $

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

linuxsnmp_configwizard_init();

function linuxsnmp_configwizard_init()
{
    $name = "linuxsnmp";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.8",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Linux workstation or server using SNMP."),
        CONFIGWIZARD_DISPLAYTITLE => _("Linux SNMP"),
        CONFIGWIZARD_FUNCTION => "linuxsnmp_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "snmppenguin.png",
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
function linuxsnmp_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "linuxsnmp";
    $process_count = 10;

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "public");
            $snmpcommunity = nagiosccm_replace_user_macros($snmpcommunity);

            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");

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
                    "v3_priv_proto" => "des",
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

<h5 class="ul">' . _('Linux Machine Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address of the Linux machine you\'d like to monitor') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('SNMP Settings') . '</h5>
<p>' . _('Specify the settings used to monitor the Linux machine via SNMP') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('SNMP Community') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="snmpcommunity" id="snmpcommunity" value="' . htmlentities($snmpcommunity) . '" class="textfield form-control">
            <div class="subtext">' . _('The SNMP community string required used to query the Windows machine') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('SNMP Version') . ':</label>
        </td>
        <td>
            <select name="snmpversion" id="snmpversion" class="form-control">
                <option value="2c" ' . is_selected($snmpversion, "2c") . '>2c</option>
                <option value="3" ' . is_selected($snmpversion, "3") . '>3</option>
            </select>
            <div class="subtext">' . _('The SNMP protocol version used to commicate with the machine') . '.</div>
        </td>
    </tr>
</table>

<div id="auth" class="hide">
    <h5 class="ul">'._('SNMP Authentication').'</h5>
    <p>' . _('When using SNMP v3 you must specify authentication information') . '.</p>
    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td>
                <label>' . _('Security Level') . ':</label>
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
                <label>' . _('Username') . ':</label>
            </td>
            <td>
                <input type="text" size="20" name="snmpopts[v3_username]" value="' . htmlentities($snmpopts["v3_username"]) . '" class="textfield form-control">
            </td>
        </tr>
        <tr>
            <td valign="top">
                <label>' . _('Authentication Password') . ':</label>
            </td>
            <td>
                <input type="texs" size="20" name="snmpopts[v3_auth_password]" value="' . htmlentities($snmpopts["v3_auth_password"]) . '" class="textfield form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Privileged Password') . ':</label>
            </td>
            <td>
                <input type="text" size="20" name="snmpopts[v3_privacy_password]" value="' . htmlentities($snmpopts["v3_privacy_password"]) . '" class="textfield form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Authentication Protocol') . ':</label>
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
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = "No address specified.";
            else if (!valid_ip($address))
                $errmsg[$errors++] = "Invalid IP address.";;
            if (have_value($snmpcommunity) == false && $snmpversion != "3")
                $errmsg[$errors++] = "No SNMP community specified.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");

            $hostname = grab_array_var($inargs, "hostname", @gethostbyaddr($address));
            $hostname = nagiosccm_replace_user_macros($hostname);

            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpcommunity = nagiosccm_replace_user_macros($snmpcommunity);

            $snmpversion = grab_array_var($inargs, "snmpversion", "");

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial == "")
                $snmpopts = grab_array_var($inargs, "snmpopts");
            else
                $snmpopts = unserialize(base64_decode($snmpopts_serial));

            $walksuccess = 1;
            $process = array();
            $disk = array();
            $process_oid = "HOST-RESOURCES-MIB::hrSWRunName";
            $disk_oid = "HOST-RESOURCES-MIB::hrFSMountPoint";

            ////////////////////////////////////////////////////////////////////////
            // Walk the process and disk OIDs to display on stage 2 select inputs //
            if ($snmpversion == "3") {

                $securitylevel = grab_array_var($snmpopts, "v3_security_level");
                $username = grab_array_var($snmpopts, "v3_username");
                $authproto = grab_array_var($snmpopts, "v3_auth_proto");
                $privproto = grab_array_var($snmpopts, "v3_priv_proto");
                $authpassword = grab_array_var($snmpopts, "v3_auth_password");
                $privacypassword = grab_array_var($snmpopts, "v3_privacy_password");
                // Run through user macros to account for illegal characters
                $username_replaced = nagiosccm_replace_user_macros($username);
                $authpassword_replaced = nagiosccm_replace_user_macros($authpassword);
                $privacypassword_replaced = nagiosccm_replace_user_macros($privacypassword);

                if ($username != "")
                    $snmpargs .= " --login=" . $username_replaced;
                if ($authpassword != "")
                    $snmpargs .= " --passwd=" . $authpassword_replaced;
                if ($privacypassword != "")
                    $snmpargs .= " --privpass=" . $privacypassword_replaced;

                if ($authproto != "" && $privproto != "") {
                    $snmpargs .= " --protocols=" . $authproto . "," . $privproto;
                } else if ($authproto != "" ) {
                    $snmpargs .= " --protocols=" . $authproto;
                }

                $process_name_table = snmp3_real_walk($address, $username_replaced, $securitylevel, $authproto, $authpassword_replaced, $privproto, $privacypassword_replaced, $process_oid, 10000000);
                $disk_name_table = snmp3_walk($address, $username_replaced, $securitylevel, $authproto, $authpassword_replaced, $privproto, $privacypassword_replaced, $disk_oid, 10000000);
            } else {
                $process_name_table = snmprealwalk($address, $snmpcommunity, $process_oid, 10000000);
                $disk_name_table = snmpwalk($address, $snmpcommunity, $disk_oid, 10000000);
            }

            $process = array();
            $disk = array();

            // If any walks fail, let user know
            if ($disk_name_table == false || $process_name_table == false) {
                $walksuccess = 0;
            }

            // Processes
            if(!empty($process_name_table)) {
                foreach ($process_name_table as $key => $val) {
                    preg_match('/"([^"]+)"/', $val, $name);
                    $name = $name[1];

                    // Create array of names
                    array_push($process, '"' . $name . '"');
                }

                // Trim repeated process names
                $process = array_unique($process);
                // get count
                $process_count = count($process);
                $process = implode(",", $process);
            }

            // Disks
            if(!empty($disk_name_table)) {
                foreach ($disk_name_table as $key => $val) {
                    
                    preg_match('/"([^"]+)"/', $val, $name);
                    $name = $name[1];

                    // Create array of names
                    array_push($disk, '"' . $name . '"');
                }

                // List of disks
                $disk = implode(",", $disk);
            }
            // End of walk section //
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
                    "nobuffers" => 1,
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
                    "disk_warning" => array(),
                    "disk_critical" => array(),
                    "disk" => array(),
                    "processstate" => array(),
                );
                for ($x = 0; $x < 5; $x++) {
                    if(!empty($disk)) { // we have snmpwalk data
                        $serviceargs_default["disk_warning"][$x] = 80;
                        $serviceargs_default["disk_critical"][$x] = 95;
                        $serviceargs_default["disk"][$x] = "";
                    } else { // if we dont have walk data return to default
                        $serviceargs_default["disk_warning"][$x] = 80;
                        $serviceargs_default["disk_critical"][$x] = 95;
                        $serviceargs_default["disk"][$x] = ($x == 0) ? "/" : "";
                    }
                }
                for ($x = 0; $x < 5; $x++) {
                    if(!empty($process)) { // we have snmpwalk data
                        $serviceargs_default['processstate'][$x]['process'] = '';
                        $serviceargs_default['processstate'][$x]['name'] = '';
                        $serviceargs_default['processstate'][$x]['warn'] = '';
                        $serviceargs_default['processstate'][$x]['crit'] = '';
                        $services['processstate'][$x] = '';
                    } else { // if we dont have walk data return to default
                        if ($x == 0) {
                            $serviceargs_default['processstate'][$x]['process'] = 'httpd';
                            $serviceargs_default['processstate'][$x]['name'] = 'Apache';
                        } else if ($x == 1) {
                            $serviceargs_default['processstate'][$x]['process'] = 'mysqld';
                            $serviceargs_default['processstate'][$x]['name'] = 'MySQL';
                        } else if ($x == 2) {
                            $serviceargs_default['processstate'][$x]['process'] = 'sshd';
                            $serviceargs_default['processstate'][$x]['name'] = 'SSH';
                        } else {
                            $serviceargs_default['processstate'][$x]['process'] = '';
                            $serviceargs_default['processstate'][$x]['name'] = '';
                        }

                        $serviceargs_default['processstate'][$x]['warn'] = '';
                        $serviceargs_default['processstate'][$x]['crit'] = '';
                    }
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="snmpcommunity" value="' . htmlentities($snmpcommunity) . '">
<input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
<input type="hidden" name="snmpopts_serial" value="' . base64_encode(serialize($snmpopts)) . '">

<h5 class="ul">' . _('Linux Machine Details') . '</h5>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this Linux machine') . '.</div>
        </td>
    </tr>

    </table>';

    // Error if any of the snmpwalks failed
    if ($walksuccess == 0) {
        $output .= '<div class="message" style="width: 500px; margin-top: -20px;"><ul class="errorMessage">' . _('The wizard detected that this server does not have snmpwalk permission on the target host.  This will prevent auto population of processes and prevent them from running successfully, but you can continue with the wizard manually.  To troubleshoot this ensure that these OIDs are available on the target host: "HOST-RESOURCES-MIB::hrSWRunName" and "HOST-RESOURCES-MIB::hrFSMountPoint".') . '<li></li></ul></div>';
    }

    $output .= '
<h5 class="ul">' . _('Server Metrics') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the Linux machine') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[ping]" ' . is_checked($services["ping"], "1") . '>
        </td>
        <td>
            <b>' . _('Ping') . '</b><br>
            ' . _('Monitors the machine with an ICMP ping.  Useful for watching network latency and general uptime') . '.
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[cpu]" ' . is_checked($services["cpu"], "1") . '>
        </td>
        <td>
            <b>' . _('CPU') . '</b><br>
            ' . _('Monitors the CPU (processor usage) on the machine') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_warning]" value="' . htmlentities($serviceargs["cpu_warning"]) . '" class="textfield form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[cpu_critical]" value="' . htmlentities($serviceargs["cpu_critical"]) . '" class="textfield form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[memory]" ' . is_checked($services["memory"], "1") . '>
        </td>
        <td>
            <b>' . _('Physical Memory Usage') . '</b><br>
            ' . _('Monitors the physical (real) memory usage on the machine.  To run with memory buffers unselect the checkbox') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_warning]" value="' . htmlentities($serviceargs["memory_warning"]) . '" class="textfield form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[memory_critical]" value="' . htmlentities($serviceargs["memory_critical"]) . '" class="textfield form-control condensed"> % &nbsp; <span class="checkbox" style="display: inline-block; margin: 0;"><label><input type="checkbox" name="services[nobuffers]" ' . is_checked($services["nobuffers"], "1") . '> ' . _('Run without buffers') . '</label></span>
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[pagefile]" ' . is_checked($services["pagefile"], "1") . '>
        </td>
        <td>
            <b>' . _('Swap Usage') . '</b><br>
            ' . _('Monitors the swap usage on the machine') . '.
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagefile_warning]" value="' . htmlentities($serviceargs["pagefile_warning"]) . '" class="textfield form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagefile_critical]" value="' . htmlentities($serviceargs["pagefile_critical"]) . '" class="textfield form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[disk]" ' . is_checked($services["disk"], "1") . '>
        </td>
        <td>
            <b>' . _('Disk Usage') . '</b>
            ' . _('Monitors disk usage on the machine') . '.<br>';
            // show message if process data available
            if (!empty($disk)) {
                $output .= '
                    <div class="message"><ul class="actionMessage">' . _('The SNMP wizard detected Disks on ') . $hostname . '<li></li></ul></div><br>';
            }

            $output .= '
            <div class="pad-t5">
                <div style="display: inline-block; vertical-align: top; height: auto;">
                    <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">';
                        for ($x = 0; $x < count($serviceargs["disk"]); $x++) {
                            $checkedstr = "";
                            if ($x == 0)
                                $checkedstr = "checked";
                            $output .= '<tr>';
                            $output .= '<td><label>Drive:</label>';
                            $output .= '<td><input type="text" size="15" name="serviceargs[disk][' . $x . ']" value="' . htmlentities($serviceargs["disk"][$x]) . '" class="form-control condensed"></td>';
                            $output .= '<td><label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_warning][' . $x . ']" value="' . htmlentities($serviceargs["disk_warning"][$x]) . '" class="form-control condensed"> % &nbsp; <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[disk_critical][' . $x . ']" value="' . htmlentities($serviceargs["disk_critical"][$x]) . '" class="form-control condensed">%</td>';
                            $output .= '</tr>';
                        }
                        $output .= '
                    </table>
                </div>';

                if (!empty($disk)) {
                    $output .= '
                    <div style="display: inline-block; vertical-align: top; height: auto; margin-left: 10px;">
                        <b>' . _("Scanned Disk List &nbsp;(double click to add)") . '</b><br><select multiple id="diskList" style="width: 450px; margin: 5px 5px 5px 15px;" size="8"></select><br>
                    </div>';
                }

            $output .= '
            </div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Processes') . '</h5>    
<p>' . _('Specify any processes that should be monitored to ensure they\'re running') . '.  <strong>Note:</strong> ' . _('Process names are case-sensitive') . '.<br>
<b>' . _('Tip') . ':</b> ' . _('The') . ' <i>' . _('Warning') . '</i> ' . _('and') . ' <i>' . _('Critical') . '</i> ' . _('fields can contain two numbers separated by a comma that represent thresholds for the number of processes that should be running.') . '<br>&nbsp; ' . _('A field value of') . ' <i>5,10</i> ' . _('would generate a warning or critical alert if there were less than 5 or more than 10 processes found') . '.</p>';

    if (!empty($process)) { 
        $output .= '<div class="message" style="margin-left: 20px;"><ul class="actionMessage" style="margin-top: 0;">' . _('The SNMP wizard detected ') . $process_count . _(' processes on ') . $hostname . '<li></li></ul></div><br>';
    }

    $output .= '
    <div style="display: inline-block; vertical-align: top; height: auto;">
        <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin: 0 0 10px 0;">
            <tr>
                <th></th>
                <th>' . _('Linux Process') . '</th>
                <th>' . _('Display Name') . '</th>
                <th>' . _('Warning') . '</th>
                <th>' . _('Critical') . '</th>
            </tr>';

                for ($x = 0; $x < count($serviceargs['processstate']); $x++) {

                    $processstring = htmlentities($serviceargs['processstate'][$x]['process']);
                    $processname = htmlentities($serviceargs['processstate'][$x]['name']);
                    $processwarn = htmlentities($serviceargs['processstate'][$x]['warn']);
                    $processcrit = htmlentities($serviceargs['processstate'][$x]['crit']);
                    $is_checked = isset($services["processstate"][$x])
                        ? is_checked($services["processstate"][$x]) : '';

                    $output .= '
                    <tr>
                        <td><input type="checkbox" class="checkbox" name="services[processstate][' . $x . ']" ' . $is_checked . '></td>';
                            if (!empty($process)) { // snmpwalk has data- create process select list
                                $output .= '
                                <td><input type="text" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="processList form-control">';
                            } else { // snmpwalk did not return data
                                $output .= '<td><input type="text" size="15" name="serviceargs[processstate][' . $x . '][process]" value="' . $processstring . '" class="form-control"></td>';
                            }
                        $output .= '
                        </td>
                        <td><input type="text" size="20" name="serviceargs[processstate][' . $x . '][name]" value="' . $processname . '" class="form-control"></td>
                        <td><label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="5" name="serviceargs[processstate][' . $x . '][warn] value="' . $processwarn . '" class="form-control"></td>
                        <td><label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="5" name="serviceargs[processstate][' . $x . '][crit] value="' . $processcrit . '" class="form-control"></td>
                    </tr>';
                }

                $output .= '
        </table>
    </div>';

    // only display select box if we have processes to display
    if (!empty($process)) {
        $output .= '
        <div style="display: inline-block; vertical-align: top; height: auto; margin-left: 10px; margin-top: 5px;">
            <b>' . _("Scanned Process List") . '</b><br><select multiple id="processList" style="width: 500px; margin: 5px 5px 5px 15px;" size="10"></select><br><a href="#" onClick="return false;" id="addProc">Add Selected</a>&nbsp;|&nbsp;<a href="#" onClick="return false;" name="selectAll">Select All</a>
        </div>';
    }

    $output .= '

<div style="height: 20px;"></div>

    <script type="text/javascript">
        $(document).ready(function () {
            wizard_populate();

            var proccount = 0;

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
                var target = $("[name^=\'serviceargs[processstate]\']").filter(":even").filter(":even").filter(function() { return $(this).val() == ""; });
                target = target[0];
                target = target["name"];

                $("[name=" + "\'" + target + "\'" + "]").val(value);
                $("[name=" + "\'" + target + "\'" + "]").closest("td").next("td").children("input").val(value);

                proccount++;
                check_box_with_value();
            });

            $("#diskList").on("dblclick", "option", function() {
                var element = "";
                var element = $("#diskList option:selected");
                var selected = element.length;
                var value = element.text();
                element.remove();

                row_count = get_empty_field_count("disk");

                if (row_count < 1) {
                    $("#diskList").parent().prev().find("a.wizard-add-row").trigger("click");
                }

                // find empty input
                var target = $("[name^=\'serviceargs[disk]\']").filter(function() { return $(this).val() == ""; });
                target = target[0];
                target = target["name"];

                $("[name=" + "\'" + target + "\'" + "]").val(value);
                $("[name=" + "\'" + target + "\'" + "]").closest("td").children("input").val(value);
            });
        });

        // Select all button
        $("[name=selectAll]").click(function() {
            $(this).parent().find("select option").prop(\'selected\', true);
        });

        function wizard_populate() {
            // populate scanned data
            var proclist = [' . $process . ' ];
            proclist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var disklist = [' . $disk . '];
            disklist.sort(function (a, b) {
                return a.toLowerCase().localeCompare(b.toLowerCase());
            });

            var process_select = $("#processList");
            $.each(proclist, function(key, value) {
                process_select.append($("<option></option>").attr("value", key).text(value)); 
            });

            var disk_select = $("#diskList");
            $.each(disklist, function(key, value) {
                disk_select.append($("<option></option>").attr("value", key).text(value)); 
            });
        }

        function get_empty_field_count(type) {
            var input = "";
            target = "";

            // find empty input fields based on type
            if (type == "process") {
                target = $("[name^=\'serviceargs[processstate]\']").filter(":even").filter(":even").filter(function() { return $(this).val() == ""; });
                var row_count = target.length;
            } else if (type == "disk") {
                input = "disk";

                target = $("[name^=\'serviceargs[disk]\']").filter(function() { return $(this).val() == ""; });
                var row_count = target.length;
            }

            return row_count;
        }

        // make sure checkboxes are checked
        function check_box_with_value() {
            var processtargets = $("input[name^=\'serviceargs[processstate]\']").filter(":even").filter(":even").filter(function() { return $(this).val() !== ""; });

            $.each(processtargets, function() {
                $(this).parent().prev("td").find("input").attr("checked", true);
            });
        }
    </script>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");

            // check for errors
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
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial == "")
                $snmpopts = grab_array_var($inargs, "snmpopts");
            else
                $snmpopts = unserialize(base64_decode($snmpopts_serial));

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
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
            $meta_arr["snmpcommunity"] = $snmpcommunity;
            $meta_arr["snmpversion"] = $snmpversion;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_linuxsnmp_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "snmppenguin.png",
                    "statusmap_image" => "snmppenguin.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            if ($services['nobuffers'] == 'on') {
                $nobuffers = 'Physical';
            } else {
                $nobuffers = 'Memory';
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
                            "check_command" => "check-host-alive",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "cpu":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "CPU Usage",
                            "use" => "xiwizard_linuxsnmp_load",
                            "check_command" => "check_xi_service_snmp_linux_load!" . $snmpargs . " -w " . $serviceargs["cpu_warning"] . " -c " . $serviceargs["cpu_critical"] . " -f",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_linuxsnmp_storage",
                            "check_command" => "check_xi_service_snmp_linux_storage!" . $snmpargs . " -m " . $nobuffers . " -w " . $serviceargs["memory_warning"] . " -c " . $serviceargs["memory_critical"] . " -f",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagefile":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Swap Usage",
                            "use" => "xiwizard_linuxsnmp_storage",
                            "check_command" => "check_xi_service_snmp_linux_storage!" . $snmpargs . " -m 'Swap' -w " . $serviceargs["pagefile_warning"] . " -c " . $serviceargs["pagefile_critical"] . " -f",
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
                                "use" => "xiwizard_linuxsnmp_storage",
                                "check_command" => "check_xi_service_snmp_linux_storage!" . $snmpargs . " -m \"^" . $diskname . "$\" -w " . $serviceargs["disk_warning"][$diskid] . " -c " . $serviceargs["disk_critical"][$diskid] . " -f",
                                "_xiwizard" => $wizard_name,
                            );

                            $diskid++;
                        }
                        break;


                    case "processstate":

                        $enabledprocs = $svcstate;
                        foreach ($enabledprocs as $pid => $pstate) {

                            $pname = $serviceargs["processstate"][$pid]["process"];
                            $pdesc = $serviceargs["processstate"][$pid]["name"];
                            $pwarn = $serviceargs["processstate"][$pid]["warn"];
                            $pcrit = $serviceargs["processstate"][$pid]["crit"];

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $pdesc,
                                "use" => "xiwizard_linuxsnmp_process",
                                "check_command" => "check_xi_service_snmp_linux_process!" . $snmpargs . " -r -n '" . $pname . "' -w '" . $pwarn . "' -c '" . $pcrit . "'",
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