<?php
//
// WatchGuard Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: watchguard.inc.php 1378 2014-08-29 16:33:04Z lgroschen $
//
// TODOS:
// * Smarter MRTG file update
//     Current implementation is naive in that it only looks for a single existing address/port match
//     Make it smarter by determining missing ports in the MRTG file and only adding those...

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

watchguard_configwizard_init();

function watchguard_configwizard_init()
{
    $name = "watchguard";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.4",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a WatchGuard device."),
        CONFIGWIZARD_DISPLAYTITLE => _("WatchGuard"),
        CONFIGWIZARD_FUNCTION => "watchguard_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "watchguard.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 *
 * Generates the instructions to be passed to the command line to generate an MRTG configuration.
 *
 * @param $snmpopts     Array - Given by the getstage1html
 * @param $address      String - Address of the network device
 * @param $snmpversion  String - Must be either 1, 2, 3
 * @param $defaultspeed String - If nothing is returned by ifSpeed, use this value.
 *
 * @return $cmd String - String to be executed
 */
function watchguard_configwizard_get_cfgmaker_cmd($snmpopts, $address, $port, $snmpversion = "1", $defaultspeed = "100000000")
{
    $cmd = "/usr/bin/cfgmaker ";
    $args[] = "--show-op-down";
    $args[] = "--noreversedns";
    $args[] = "--zero-speed";
    $args[] = escapeshellarg($defaultspeed);
    if (empty($snmpopts['v3_username'])) {
        // Run SNMPv1, SNMPv2, SNMPv2c code here
        $username = $snmpopts['snmpcommunity'];
        $delimitors = ":::::";
        if (!empty($port)) { $delimitors = ":".intval($port)."::::"; }
        $args[] = escapeshellarg("{$username}@{$address}{$delimitors}".(int)$snmpversion);
    } else {
        // Run SNMPv3 code here
        $args[] = "--enablesnmpv3";
        $args[] = "--snmp-options=:::::3";
        if (!empty($snmpopts['v3_username'])) {
            $args[] = "--username=" . escapeshellarg($snmpopts['v3_username']);
        }
        if (!empty($snmpopts['v3_auth_password'])) {
            $args[] = "--authprotocol=" . escapeshellarg(strtolower($snmpopts['v3_auth_proto']));
            $args[] = "--authpassword=" . escapeshellarg($snmpopts['v3_auth_password']);
        }
        if (!empty($snmpopts['v3_priv_password'])) {
            $args[] = "--privprotocol=" . escapeshellarg(strtolower($snmpopts['v3_priv_proto']));
            $args[] = "--privpassword=" . escapeshellarg($snmpopts['v3_priv_password']);
        }
        $args[] = "--contextengineid=0";
        $args[] = escapeshellarg($address);
    }
    $cmd .= implode(' ', $args);

    // Run the cfgmaker through the user macros
    $cmd = nagiosccm_replace_user_macros($cmd);
    return $cmd;
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function watchguard_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "watchguard";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {

        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            $address = grab_array_var($inargs, "address", "");
            $address_port = grab_array_var($inargs, "port", 161);

            $address = nagiosccm_replace_user_macros($address);
            $address_port = nagiosccm_replace_user_macros($address_port);

            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if (!empty($snmpopts_serial)) {
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            } else {
                // Set the defaults if nothing is set yet
                $snmpopts_default = array(
                    "snmpcommunity" => "public",
                    "v3_security_level" => "",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_priv_password" => "",
                    "v3_auth_proto" => "MD5",
                    "v3_priv_proto" => "DES"
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
            }
            $portnames = grab_array_var($inargs, "portnames", "number");
            $scaninterfaces = grab_array_var($inargs, "scaninterfaces", "on");
            $default_port_speed = grab_array_var($inargs, "default_port_speed", 100000000);
            $warn_speed_in_percent = grab_array_var($inargs, "warn_speed_in_percent", 50);
            $warn_speed_out_percent = grab_array_var($inargs, "warn_speed_out_percent", 50);
            $crit_speed_in_percent = grab_array_var($inargs, "crit_speed_in_percent", 80);
            $crit_speed_out_percent = grab_array_var($inargs, "crit_speed_out_percent", 80);
            if (!watchguard_configwizard_checkversion()) {
                $output = "<br/><strong>" . _("Error: This wizard requires Nagios XI 2014 or later.") . "</strong>";
            } else {

                $output = '
<script type="text/javascript">
    $(document).ready(function() {
        $("#tabs").tabs();
        // Upon clicking the tabs, set the invisible radio selector
        // to be true, based on the tab clicked
        $("#selectv1").click(function() {
            $("#snmpversion").val("1");
        });
        $("#selectv2c").click(function() {
            $("#snmpversion").val("2c");
        });
        $("#selectv3").click(function() {
            $("#snmpversion").val("3");
        });
        $("#selectv' . $snmpversion . '").click();
        // End of above commented section
    });
</script>

<h5 class="ul">' . _("WatchGuard Information") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _("IP Address") . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _("The IP address of the WatchGuard device you'd like to monitor") . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._("Port").':</label>
        </td>
        <td>
            <input type="text" size="6" name="port" id="port" value="'.encode_form_val($address_port).'" class="form-control">
            <div class="subtext">'._("The access port of the WatchGuard device").'</div>
        </td>
    </tr>
</table>
<input type="hidden" name="snmpversion" id="snmpversion" value="$snmpversion">

<div id="tabs">
    <ul>
        <li><a href="#snmpv2" id="selectv1">SNMPv1</a></li>
        <li><a href="#snmpv2" id="selectv2c">SNMPv2c</a></li>
        <li><a href="#snmpv3" id="selectv3">SNMPv3</a></li>
    </ul>
    <div id="snmpv2">
        <table class="table table-condensed table-no-border table-auto-width" style="margin: 0;">
            <tr>
                <td class="vt">
                    <label>' . _("SNMP Community") . ':</label>
                </td>
                <td>
                    <input type="text" size="20" name="snmpopts[snmpcommunity]" value="' . htmlentities($snmpopts['snmpcommunity']) . '" class="form-control">
                    <div class="subtext">' . _("The SNMP community string required used to to query the WatchGuard device") . '.</div>
                </td>
            </tr>
        </table>
    </div> <!-- Closes #snmpv2 -->

    <div id="snmpv3">
        <p>' . _("When using SNMP v3 you must specify authentication information") . '.</p>
        <table class="table table-condensed table-no-border table-auto-width" style="margin: 0;">
            <tr>
                <td>
                    <label>' . _("Security Level") . ':</label>
                </td>
                <td>
                    <select name="snmpopts[v3_security_level]" class="form-control">
                        <option value="noAuthNoPriv" ' . is_selected($snmpopts["v3_security_level"], "noAuthNoPriv") . '>noAuthNoPriv</option>
                        <option value="authNoPriv" ' . is_selected($snmpopts["v3_security_level"], "authNoPriv") . '>authNoPriv</option>
                        <option value="authPriv" ' . is_selected($snmpopts["v3_security_level"], "authPriv") . '>authPriv</option>
                    </select>
                    
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Username") . ':</label>
                </td>
                <td>
                    <input type="text" size="20" name="snmpopts[v3_username]" value="' . htmlentities($snmpopts["v3_username"]) . '" class="form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Authentification Password") . ':</label>
                </td>
                <td>
                    <input type="texs" size="20" name="snmpopts[v3_auth_password]" value="' . htmlentities($snmpopts["v3_auth_password"]) . '" class="form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Privileged Password") . ':</label>
                </td>
                <td>
                    <input type="text" size="20" name="snmpopts[v3_priv_password]" value="' . htmlentities($snmpopts["v3_priv_password"]) . '" class="form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Authentification Protocol") . ':</label>
                </td>
                <td>
                    <select name="snmpopts[v3_auth_proto]" class="form-control">
                        <option value="MD5" ' . is_selected($snmpopts["v3_auth_proto"], "MD5") . '>MD5</option>
                        <option value="SHA" ' . is_selected($snmpopts["v3_auth_proto"], "SHA") . '>SHA</option>
                    </select>
                    
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <label>' . _("Privileged Protocol") . ':</label>
                </td>
                <td>
                    <select name="snmpopts[v3_priv_proto]" class="form-control">
                        <option value="DES" ' . is_selected($snmpopts["v3_priv_proto"], "DES") . '>DES</option>
                        <option value="AES" ' . is_selected($snmpopts["v3_priv_proto"], "AES") . '>AES</option>
                    </select>
                    
                </td>
            </tr>
        </table>
    </div> <!-- Closes #snmpv3 -->
</div> <!-- Closes #tabs -->

<h5 class="ul">' . _("Monitoring Options") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label for="portnames">' . _("Monitor Using:") . '</label>
        </td>
        <td>
            <select name="portnames" class="form-control">
                <option value="number" ' . is_selected($portnames, "number") . '>' . _("Port's Number") . '</option>
                <option value="name" ' . is_selected($portnames, "name") . '>' . _("Port's Name") . '</option>
            </select>
            <div class="subtext">' . _("Select the port naming scheme that should be used. ") . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label for="scaninterfaces">' . _("Scan Interfaces") . '</label>
        </td>
        <td class="checkbox">
            <label>
                <input name="scaninterfaces" type="checkbox" ' . is_checked($scaninterfaces) . '> ' . _("Scan the WatchGuard device to auto-detect interfaces that can be monitored for link up/down status and bandwidth usage. The scanning process may take several seconds to complete.") . '
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _("Value Defaults") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label for="warn_speed_in_percent"><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> ' . _("Input Rate") . ':</label>
        </td>
        <td>
            <input class="form-control" type="text" value="' . $warn_speed_in_percent . '" name="warn_speed_in_percent" size="2"> %
        </td>
        <td>
            <label for="crit_speed_in_percent"><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> ' . _("Input Rate") . ':</label>
        </td>
        <td>
            <input class="form-control" type="text" value="' . $crit_speed_in_percent . '" name="crit_speed_in_percent" size="2"> %
        </td>
    </tr>
    <tr>
        <td>
            <label for="warn_speed_in_percent"><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> ' . _("Output Rate") . ':</label>
        </td>
        <td>
            <input class="form-control" type="text" value="' . $warn_speed_out_percent . '" name="warn_speed_out_percent" size="2"> %
        </td>
        <td>
            <label for="crit_speed_in_percent"><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> ' . _("Output Rate") . ':</label>
        </td>
        <td>
            <input class="form-control" type="text" value="' . $crit_speed_out_percent . '" name="crit_speed_out_percent" size="2"> %
        </td>
    </tr>
    <tr>
        <td>
            <label for="default_port_speed">' . _("Default Port Speed") . ':</label>
        </td>
        <td>
            <input type="text" value="' . $default_port_speed . '" name="default_port_speed" size="10" class="form-control"> bytes/second
        </td>
    </tr>
</table>';
            } // end else
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $address_port = grab_array_var($inargs, "port", 161);
            $snmpopts = grab_array_var($inargs, "snmpopts", array());
            $scaninterfaces = grab_array_var($inargs, "scaninterfaces");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $default_port_speed = grab_array_var($inargs, "default_port_speed", 100000000);
            $errors = 0;
            $errmsg = array();

            // Do error checking
            if (have_value($address) == false) {
                $errmsg[$errors++] = _("No address specified.");
            } else if (!valid_ip($address)) {
                $errmsg[$errors++] = _("Invalid IP address.");
            } else if (empty($snmpopts['snmpcommunity']) && empty($snmpopts['v3_username'])) {
                $errmsg[$errors++] = _("Must give either community or username.");
            }

            // Error results
            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            // If user wants to scan interfaces, immediately launch the command and start working on it....
            if ($scaninterfaces == "on") {

                // Set temp scan directory... normally "/usr/local/nagioxi/tmp"
                $tmp_dir = get_tmp_dir();
                $outfile = $tmp_dir . "/mrtgscan-" . $address;
                $donefile = $outfile . ".done";

                // Get rid of old "done" file if it exists
                if (file_exists($donefile)) {
                    unlink($donefile);
                }

                // Run MRTG's cfgmaker command in the background
                // TODO - see if data already exists in mrtg.cfg and skip this step....
                $cfgmaker_cmd = watchguard_configwizard_get_cfgmaker_cmd($snmpopts, $address, $address_port, $snmpversion, $default_port_speed);
                $cmd = $cfgmaker_cmd . " > " . $outfile . " ; touch " . $donefile . " > /dev/null &";
                exec($cmd);
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $address_port = grab_array_var($inargs, "port", 161);
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if (!empty($snmpopts_serial)) {
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            } else {
                $snmpopts_default = array(
                    "snmpcommunity" => "public",
                    "v3_security_level" => "",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_priv_password" => "",
                    "v3_auth_proto" => "md5",
                    "v3_priv_proto" => "des"
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
                $snmpopts_serial = base64_encode(serialize($snmpopts));
            }
            $vendor = grab_array_var($inargs, "vendor", "");
            $portnames = grab_array_var($inargs, "portnames");
            $scaninterfaces = grab_array_var($inargs, "scaninterfaces");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $default_port_speed = grab_array_var($inargs, "default_port_speed", 100000000);
            $warn_speed_in_percent = grab_array_var($inargs, "warn_speed_in_percent", 50);
            $warn_speed_out_percent = grab_array_var($inargs, "warn_speed_out_percent", 50);
            $crit_speed_in_percent = grab_array_var($inargs, "crit_speed_in_percent", 80);
            $crit_speed_out_percent = grab_array_var($inargs, "crit_speed_out_percent", 80);
            $hostname = @gethostbyaddr($address);

            $services = "";
            $services_default = array(
                "cpu_usage" => "on",
                "ping" => "on",
                "active_connections" => "on",
                "total_sent_packets" => "on",
                "total_received_packets" => "on",
                "stream_requests_total" => "on",
                "stream_requests_drop" => "on",
                "total_sent_bytes" => "on",
                "total_received_bytes" => "on",
            );
            /* Check to see if there is any information in the $services_serial array. This is in case someone used
            a back form button. We use this data to populate the displayed forms. */
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", $services_default);

            $serviceargs = "";
            $serviceargs_default = array(
                "cpu_usage_warning" => "20",
                "cpu_usage_critical" => "40",
                "ping_warning" => "20",
                "ping_critical" => "40",
                "active_connections_warning" => "300",
                "active_connections_critical" => "500",
                "total_sent_bytes_warning" => "1000",
                "total_sent_bytes_critical" => "2000",
                "total_received_bytes_warning" => "1000",
                "total_received_bytes_critical" => "2000",
                "total_sent_packets_warning" => "1000",
                "total_sent_packets_critical" => "2000",
                "total_received_packets_warning" => "1000",
                "total_received_packets_critical" => "2000",
                "stream_requests_total_warning" => "300",
                "stream_requests_total_critical" => "500",
                "stream_requests_drop_warning" => "300",
                "stream_requests_drop_critical" => "500",
            );

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                //echo "ARGSSERIAL: $serviceargs_serial<BR>\n";
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
            if (!is_array($serviceargs))
                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);            

            $output = '
<input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="vendor" value="' . htmlentities($vendor) . '">
<input type="hidden" name="snmpopts_serial" value="' . $snmpopts_serial . '">
<input type="hidden" name="portnames" value="' . htmlentities($portnames) . '">
<input type="hidden" name="scaninterfaces" value="' . htmlentities($scaninterfaces) . '">
<input type="hidden" name="warn_speed_in_percent" value="' . htmlentities($warn_speed_in_percent) . '">
<input type="hidden" name="crit_speed_in_percent" value="' . htmlentities($crit_speed_in_percent) . '">
<input type="hidden" name="warn_speed_out_percent" value="' . htmlentities($warn_speed_out_percent) . '">
<input type="hidden" name="crit_speed_out_percent" value="' . htmlentities($crit_speed_out_percent) . '">

<h5 class="ul">' . _('WatchGuard device Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('WatchGuard Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this WatchGuard device.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the WatchGuard device.') . '</p>
<table class="table table-no-border table-auto-width">';
            // Create variable here to avoid typos, which will hold the name of the service we are editing
            // Create Ping service HTML
            $cs = array(0 => "ping");
            $output .= "
    <tr>
        <td>
            <input type='checkbox' class='checkbox' id='{$cs[0]}' name='services[{$cs[0]}]' " . is_checked(grab_array_var($services, $cs[0]), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs[0]}'>
                <b>" . _('Ping') . "</b><br>
                " . _('Monitors the WatchGuard device with an ICMP ping.  Useful for watching network latency') . ".
            </label>
        </td>
    </tr>";
            // Create CPU Usage service HTML
            $cs = array('name' => "cpu_usage",
                'title' => _("CPU Usage"),
                'desc' => _("Monitors the Watchguards CPU usage."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> % &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> %
            </div>
        </td>
    </tr>";
            // Create Active Connections service HTML
            $cs = array('name' => "active_connections",
                'title' => _("Active Connections"),
                'desc' => _("Checks the active connections that the WatchGuard device is servicing."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'>
            </div>
        </td>
    </tr>";
            // Create Total Sent Packets service HTML
            $cs = array('name' => "total_sent_packets",
                'title' => _("Total Sent Packets"),
                'desc' => _("Checks the total number of packets sent by the WatchGuard device."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> " . _("packets/sec") . " &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> " . _("packets/sec") . "
            </div>
        </td>
    </tr>";
            // Create Total Received Packets service HTML
            $cs = array('name' => "total_received_packets",
                'title' => _("Total Received Packets"),
                'desc' => _("Checks the total number of packets received by the WatchGuard device."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> " . _("packets/sec") . " &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> " . _("packets/sec") . "
            </div>
        </td>
    </tr>";
            // Create Stream Requests Total service HTML
            $cs = array('name' => "stream_requests_total",
                'title' => _("Stream Requests"),
                'desc' => _("Checks the total number of stream requests received by the WatchGuard device."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> " . _("requests/sec") . " &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> " . _("requests/sec") . "
            </div>
        </td>
    </tr>";
            // Create Stream Requests Drop service HTML
            $cs = array('name' => "stream_requests_drop",
                'title' => _("Stream Requests Dropped"),
                'desc' => _("Checks the total number of stream requests dropped by the WatchGuard device."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> " . _("drops/sec") . " &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> " . _("drops/sec") . "
            </div>
        </td>
    </tr>";
            // Create Total Bytes Sent service HTML
            $cs = array('name' => "total_sent_bytes",
                'title' => _("Total Bytes Sent"),
                'desc' => _("Checks the total number of bytes sent by the WatchGuard device."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> " . _("Kbps") . " &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> " . _("Kbps") . "
            </div>
        </td>
    </tr>";
            // Create Total Received Bytes service HTML
            $cs = array('name' => "total_received_bytes",
                'title' => _("Total Received Bytes"),
                'desc' => _("Checks the total number of bytes received by the WatchGuard device."),);
            $output .= "
    <tr>
        <td class='vt'>
            <input type='checkbox' class='checkbox' id='{$cs['name']}' name='services[{$cs['name']}]' " . is_checked(grab_array_var($services, $cs['name']), "on") . ">
        </td>
        <td>
            <label class='normal' for='{$cs['name']}'>
                <b>{$cs['title']}</b><br>
                {$cs['desc']}
            </label>
            <div class='pad-t5'>
                <label><img src='".theme_image('error.png')."' class='tt-bind' title='"._('Warning Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_warning]' value='" . htmlentities($serviceargs["{$cs['name']}_warning"]) . "' class='form-control condensed'> " . _("Kbps") . " &nbsp;<label><img src='".theme_image('critical_small.png')."' class='tt-bind' title='"._('Critical Threshold')."'></label> <input type='text' size='3' name='serviceargs[{$cs['name']}_critical]' value='" . htmlentities($serviceargs["{$cs['name']}_critical"]) . "' class='form-control condensed'> " . _("Kbps") . "
            </div>
        </td>
    </tr>
    </table>";

            if ($scaninterfaces == "on") {

                // Read results of MRTG's scan
                // TODO - if WatchGuard device is already in mrtg.cfg, read that instead...
                $tmp_dir = get_tmp_dir();
                $outfile = $tmp_dir . "/mrtgscan-" . $address;
                $ports = watchguard_configwizard_read_walk_file($outfile, $address);

                $output .= '
<h5 class="ul">' . _('Bandwidth and Port Status') . '</h5>

<script type="text/javascript">
//check all ports 
var allChecked=false;
function switchCheckAll()
{
    $(".portbox:checkbox").each(function() {
      this.checked = "checked";
    });
}   
function switchUncheckAll()
{
    $(".portbox:checkbox").each(function() {
      this.checked = "";
    });
}
function bandwidthCheckAll()
{
    $(".bandwidthbox:checkbox").each(function() {
      this.checked = "checked";
    });
}   
function bandwidthUncheckAll()
{
    $(".bandwidthbox:checkbox").each(function() {
      this.checked = "";
    });
}
function statusCheckAll()
{
    $(".statusbox:checkbox").each(function() {
      this.checked = "checked";
    });
}   
function statusUncheckAll()
{
    $(".statusbox:checkbox").each(function() {
      this.checked = "";
    });
}
</script>';

                if (count($ports) > 0) {

                    $output .= '
                    <p>' . _("Select the ports for which you'd like to monitor bandwidth and port status.  You may specify an optional port name to be associated with specific ports.") . '</p>

                    <table class="table table-condensed table-hover table-striped table-bordered table-auto-width">
                        <thead>
                            <tr>
                                <th>' . _('Port') . '<br/><a href="javascript:void(0);" onclick="switchCheckAll()" title="' . _('Check All Ports') . '">' . _('Check') . '</a> /
                                    <a href="javascript:void(0);" onclick="switchUncheckAll()" title="' . _('Uncheck All Ports') . '">' . _('Uncheck') . '</a>
                                </th>
                                <th>
                                    ' . _('Port Name') . '
                                </th>
                                <th>
                                    ' . _('Max Speed') . '
                                </th>
                                <th>
                                    ' . _('Service Description') . '
                                </th>
                                <th>
                                    ' . _('Bandwidth') . '<br/><a href="javascript:void(0);" onclick="bandwidthCheckAll()" title="' . _('Check All Ports') . '">' . _('Check') . '</a> /
                                    <a href="javascript:void(0);" onclick="bandwidthUncheckAll()" title="' . _('Uncheck All Ports') . '">' . _('Uncheck') . '</a>
                                </th>
                                <th>
                                    ' . _('Port Status') . '<br/><a href="javascript:void(0);" onclick="statusCheckAll()" title="' . _('Check All Ports') . '">' . _('Check') . '</a> /
                                    <a href="javascript:void(0);" onclick="statusUncheckAll()" title="' . _('Uncheck All Ports') . '">' . _('Uncheck') . '</a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>';

                    $x = 0;
                    foreach ($ports as $port_num => $parr) {

                        $port_bytes = grab_array_var($parr, "max_bytes", 0);

                        // We'll use either description or number as the name later
                        $port_description = grab_array_var($parr, "port_description", $port_num);
                        $port_number = grab_array_var($parr, "port_number", $port_num);
                        $port_long_desc = grab_array_var($parr, "port_long_description", $port_num);

                        // Remome illegal chars in portnames -SW
                        // `~!$%^&*|'"<>?,()=\
                        $badchars = explode(" ", "; ` ~ ! $ % ^ & * | ' \" < > ? , ( ) = \\ { } [ ]");
                        $port_number = str_replace($badchars, " ", $port_number);
                        $port_description = str_replace($badchars, " ", $port_description);
                        $port_long_desc = str_replace($badchars, " ", $port_long_desc);

                        // Default to using port number for service name
                        $port_name = "Port " . $port_number;
                        if ($portnames == "name") {
                            $port_name = $port_long_desc; //changed to long description -MG 
                        }

                        $x++;

                        $max_speed = watchguard_configwizard_get_readable_port_line_speed($port_bytes, $speed, $label);
                        $warn_in_speed = ($speed * ($warn_speed_in_percent / 100));
                        $warn_out_speed = ($speed * ($warn_speed_out_percent / 100));
                        $crit_in_speed = ($speed * ($crit_speed_in_percent / 100));
                        $crit_out_speed = ($speed * ($crit_speed_out_percent / 100));

                        // Possible refomat speed values/labels
                        watchguard_configwizard_recalculate_speeds($warn_in_speed, $warn_out_speed, $crit_in_speed, $crit_out_speed, $label);

                        $rowclass = "";
                        if (($x % 2) != 0) {
                            $rowclass .= " odd";
                        } else {
                            $rowclass .= " even";
                        }

                        $output .= '
                        <tr class=' . $rowclass . '>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" class="portbox" id="port_' . $port_num . '" name="services[port][' . $port_num . ']" checked>
                                    ' . _('Port ') . $port_num . '
                                </label>
                            </td>
                            <td>
                                ' . encode_form_val($port_description). '
                            </td>
                            <td>
                                ' . $max_speed . '
                            </td>
                            <td>
                                <input type="text" size="16" name="serviceargs[portname][' . $port_num . ']" value="' . $port_name . '" class="form-control">
                            </td>

                        <td>
                        <table>
                        <tr>
                        <td>
                        <input type="checkbox" class="checkbox bandwidthbox" id="bandwidth_' . $port_num . '" name="serviceargs[bandwidth][' . $port_num . ']" checked>
                        </td>
                        <td>' . _('Rate In') . ':</td>
                        <td>' . _('Rate Out') . ':</td>
                        <td></td>
                        <td>' . _('Rate In') . ':</td>
                        <td>' . _('Rate Out') . ':</td>
                        </tr>

                        <tr>
                        <td>
                        <label>' . _('Warning') . ':</label>
                        </td>
                        <td>
                        <input type="text" size="2" name="serviceargs[bandwidth_warning_input_value][' . $port_num . ']" value="' . number_format($warn_in_speed) . '" class="form-control condensed">
                        </td>
                        <td>
                        <input type="text" size="2" name="serviceargs[bandwidth_warning_output_value][' . $port_num . ']" value="' . number_format($warn_out_speed) . '" class="form-control condensed">
                        </td>
        
                        <td>
                        <label>' . _('Critical') . ':</label>
                        </td>
                        <td>
                        <input type="text" size="2" name="serviceargs[bandwidth_critical_input_value][' . $port_num . ']" value="' . number_format($crit_in_speed) . '" class="form-control condensed">
                        </td>
                        <td>
                        <input type="text" size="2" name="serviceargs[bandwidth_critical_output_value][' . $port_num . ']" value="' . number_format($crit_out_speed) . '" class="form-control condensed">
                        </td>
                        <td>
                        <select name="serviceargs[bandwidth_speed_label][' . $port_num . ']" class="form-control condensed">
                        <option value="Gbps" ' . is_selected("Gbps", $label) . '>' . _('Gbps') . '</option>
                        <option value="Mbps" ' . is_selected("Mbps", $label) . '>' . _('Mbps') . '</option>
                        <option value="Kbps" ' . is_selected("Kbps", $label) . '>' . _('Kbps') . '</option>
                        <option value="bps" ' . is_selected("bps", $label) . '>' . _('bps') . '</option>
                        </select>
                        </td>
                        </tr>
                        </table>
                
                        
                        </td>
                            <td style="text-align: center;">
                                <input type="checkbox" class="statusbox" id="portstatus_' . $port_num . '" name="serviceargs[portstatus][' . $port_num . ']" checked>
                            </td>
                        </tr>
                        ';
                    }

                    $output .= '
                        </tbody>
                    </table>';
                } else {
                    $output .= '
                    <img src="' . theme_image("critical_small.png") . '">
                    <b>' . _('No ports were detected on the WatchGuard device.') . '</b>  ' . _('Possible reasons for this include') . ':
                    <ul>
                    <li>' . _('The WatchGuard device is currently down') . '</li>
                    <li>' . _('The WatchGuard device does not exist at the address you specified') . '</li>
                    <li>' . _('SNMP support on the WatchGuard device is disabled') . '</li>
                    </ul>
                    ';

                    if (is_admin() == true) {
                        $cfgmaker_cmd = watchguard_configwizard_get_cfgmaker_cmd($snmpopts, $address, $address_port, $snmpversion);
                        $output .= '
                        <br>
                        <img src="' . theme_image("ack.png") . '">
                        <b>' . _('Troubleshooting Tip') . ':</b>
                        <p>
                        ' . _('If you keep experiencing problems with the WatchGuard device wizard scan, login to the Nagios XI server as the root user and execute the following command') . ':
                        </p>
<pre>
' . $cfgmaker_cmd . '
</pre>
<p>
' . _('Send the output of the command and a description of your problem to the Nagios support team by posting to our online ') . '<a href="http://support.nagios.com/forum/" target="_blank">' . _('support forum') . '</a>.
</p>
                        ';
                    }
                }

            }

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname");
            $address = grab_array_var($inargs, "address");
            $portnames = grab_array_var($inargs, "portnames");
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if (!empty($snmpopts_serial)) {
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            } else {
                $snmpopts_default = array(
                    "snmpcommunity" => "public",
                    "v3_security_level" => "",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_priv_password" => "",
                    "v3_auth_proto" => "md5",
                    "v3_priv_proto" => "des"
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
                $snmpopts_serial = base64_encode(serialize($snmpopts));
            }
            $snmpversion = grab_array_var($inargs, "snmpversion", "1");
            $vendor = grab_array_var($inargs, "vendor");
            $scaninterfaces = grab_array_var($inargs, "scaninterfaces");
            $warn_speed_in_percent = grab_array_var($inargs, "warn_speed_in_percent", 50);
            $warn_speed_out_percent = grab_array_var($inargs, "warn_speed_out_percent", 50);
            $crit_speed_in_percent = grab_array_var($inargs, "crit_speed_in_percent", 80);
            $crit_speed_out_percent = grab_array_var($inargs, "crit_speed_out_percent", 80);

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false) {
                $errmsg[$errors++] = _("Invalid host name.");
            }

            // TODO - check rate in/out warning and critical thresholds

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $vendor = grab_array_var($inargs, "vendor");
            $portnames = grab_array_var($inargs, "portnames");
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial != "") {
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            } else {
                $snmpopts_default = array(
                    "snmpcommunity" => "public",
                    "v3_security_level" => "",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_priv_password" => "",
                    "v3_auth_proto" => "md5",
                    "v3_priv_proto" => "des",
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
                $snmpopts_serial = base64_encode(serialize($snmpopts));
            }
            $snmpversion = grab_array_var($inargs, "snmpversion", "1");
            $scaninterfaces = grab_array_var($inargs, "scaninterfaces");
            $warn_speed_in_percent = grab_array_var($inargs, "warn_speed_in_percent", 50);
            $warn_speed_out_percent = grab_array_var($inargs, "warn_speed_out_percent", 50);
            $crit_speed_in_percent = grab_array_var($inargs, "crit_speed_in_percent", 80);
            $crit_speed_out_percent = grab_array_var($inargs, "crit_speed_out_percent", 80);

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            /*
            echo "REQUEST:<BR>";
            global $request;
            print_r($request);
            
            echo "SERVICES:<BR>";
            print_r($services);
            echo "SERVICEARGS:<BR>";
            print_r($serviceargs);
            */


            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '" />
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
        <input type="hidden" name="snmpopts_serial" value="' . $snmpopts_serial . '" />
        <input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '" />
        <input type="hidden" name="vendor" value="' . htmlentities($vendor) . '" />
        <input type="hidden" name="portnames" value="' . htmlentities($portnames) . '" />
        <input type="hidden" name="scaninterfaces" value="' . htmlentities($scaninterfaces) . '" />
        <input type="hidden" name="warn_speed_in_percent" value="' . htmlentities($warn_speed_in_percent) . '" />
        <input type="hidden" name="crit_speed_in_percent" value="' . htmlentities($crit_speed_in_percent) . '" />
        <input type="hidden" name="warn_speed_out_percent" value="' . htmlentities($warn_speed_out_percent) . '" />
        <input type="hidden" name="crit_speed_out_percent" value="' . htmlentities($crit_speed_out_percent) . '" />
        <input type="hidden" name="services_serial" value="' . $services_serial . '" />
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '" />
        
        <!-- SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR> -->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $output = '
            
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial != "") {
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            } else {
                $snmpopts_default = array(
                    "snmpcommunity" => "public",
                    "v3_security_level" => "",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_priv_password" => "",
                    "v3_auth_proto" => "md5",
                    "v3_priv_proto" => "des",
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
                $snmpopts_serial = base64_encode(serialize($snmpopts));
            }
            $snmpversion = grab_array_var($inargs, "snmpversion", "1");
            $portnames = grab_array_var($inargs, "portnames");
            $scaninterfaces = grab_array_var($inargs, "scaninterfaces");
            $vendor = grab_array_var($inargs, "vendor");

            $hostaddress = $address;

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            //echo "SERVICES:<BR>";
            //print_r($services);
            //echo "SERVICEARGS:<BR>";
            //print_r($serviceargs);

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["snmpopts_serial"] = $snmpopts_serial;
            $meta_arr["snmpversion"] = $snmpversion;
            $meta_arr["portnames"] = $portnames;
            $meta_arr["scaninterfaces"] = $scaninterfaces;
            $meta_arr["vendor"] = $vendor;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            // ABSTRACTION FOR OIDS
            $CPU_oid = "1.3.6.1.4.1.3097.6.3.78.0";
            $AConns_oid = "1.3.6.1.4.1.3097.6.3.80.0";
            $Sent_Packets_oid = "1.3.6.1.4.1.3097.6.3.10.0";
            $Recv_Packets_oid = "1.3.6.1.4.1.3097.6.3.11.0";
            $Strm_Requests_oid = "1.3.6.1.4.1.3097.6.3.30.0";
            $Strm_Dropped_oid = "1.3.6.1.4.1.3097.6.3.34.0";
            $Sent_Bytes_oid = "1.3.6.1.4.1.3097.6.3.8.0";
            $Recv_Bytes_oid = "1.3.6.1.4.1.3097.6.3.9.0";

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_watchguard_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "watchguard.png",
                    "statusmap_image" => "watchguard.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $have_bandwidth = false;

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";
                if ($svc != "ping" && $svc != "port") {
                    $warn = $serviceargs["{$svc}_warning"];
                    $crit = $serviceargs["{$svc}_critical"];
                }

                //check for SNMP v3 and if true add credentials to service command(s)
                $snmpv3_credentials = "";
                if ($snmpversion == 3) {
                    $priv_password_and_proto = "";
                    if (!empty($snmpopts['v3_priv_password'])) {
                        $priv_password_and_proto = "-x {$snmpopts['v3_priv_proto']}";
                        $priv_password_and_proto .= " -X {$snmpopts['v3_priv_password']}";
                    }

                    $snmpv3_credentials = "-u {$snmpopts['v3_username']} -A {$snmpopts['v3_auth_password']} {$priv_password_and_proto} -a {$snmpopts["v3_auth_proto"]} -l {$snmpopts['v3_security_level']}";
                }

                switch ($svc) {

                    case "ping":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Ping",
                            "use" => "xiwizard_switch_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "cpu_usage":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "CPU Usage",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$CPU_oid} -l CPU_Usage -u '%' -d 'CPU Usage' -w $warn -c $crit -D 100",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "active_connections":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Active Connections",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$AConns_oid} -l Active_Connections -d 'Active Connections' -w $warn -c $crit",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "total_sent_packets":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Sent Packets",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$Sent_Packets_oid} -l Total_Sent_Packets -d 'Total Sent Packets/Sec' -w $warn -c $crit -e",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "total_received_packets":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Received Packets",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$Recv_Packets_oid} -l Total_Received_Packets -d 'Total Received Packets/Sec' -w $warn -c $crit -e",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "stream_requests_total":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Stream Requests",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$Strm_Requests_oid} -l Total_Stream_Requests -d 'Total Stream Requests/Sec' -w $warn -c $crit -e",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "stream_requests_drop":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Stream Drops",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$Strm_Dropped_oid} -l Total_Stream_Requests_Dropped -d 'Total Stream Requests Dropped/Sec' -w $warn -c $crit -e",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "total_sent_bytes":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Total Sent Bytes",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$Sent_Bytes_oid} -l Sent_Bytes -u KB -d 'Total Sent Bytes/Sec' -w $warn -c $crit -e -D 1024",
                            "_xiwizard" => $wizard_name,
                        );

                    case "total_received_bytes":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Total Received Bytes",
                            "use" => "xiwizard_watchguard_service",
                            "check_command" => "check_xi_service_snmp_watchguard!-N '-c " . $snmpopts['snmpcommunity'] . " -v " . $snmpversion . " " . $snmpv3_credentials . "' {$Recv_Bytes_oid} -l Received_Bytes -u KB -d 'Total Received Bytes/Sec' -w $warn -c $crit -e -D 1024",
                            "_xiwizard" => $wizard_name,
                        );
                        break;                    

                    case "port":

                        foreach ($svcstate as $portnum => $portstate) {
                            //echo "HAVE PORT $portnum<BR>\n";

                            $portname = _("Port ") . $portnum;
                            if (array_key_exists("portname", $serviceargs)) {
                                if (array_key_exists($portnum, $serviceargs["portname"])) {
                                    $portname = $serviceargs["portname"][$portnum];
                                    $badchars = explode(" ", "` ~ ! $ % ^ & * | ' \" < > ? , ( ) = \\ { } [ ]");
                                    $portname = str_replace($badchars, " ", $portname);
                                }
                            }

                            // monitor bandwidth
                            if (array_key_exists("bandwidth", $serviceargs)) {
                                if (array_key_exists($portnum, $serviceargs["bandwidth"])) {
                                    //echo "MONITOR BANDWIDTH ON $portnum<BR>\n";

                                    $have_bandwidth = true;

                                    $warn_pair = $serviceargs["bandwidth_warning_input_value"][$portnum] . "," . $serviceargs["bandwidth_warning_output_value"][$portnum];
                                    $crit_pair = $serviceargs["bandwidth_critical_input_value"][$portnum] . "," . $serviceargs["bandwidth_critical_output_value"][$portnum];

                                    switch ($serviceargs["bandwidth_speed_label"][$portnum]) {
                                        case "Gbps":
                                            $label = "G";
                                            break;
                                        case "Mbps":
                                            $label = "M";
                                            break;
                                        case "Kbps":
                                            $label = "K";
                                            break;
                                        default:
                                            $label = "B";
                                            break;
                                    }

                                    $objs[] = array(
                                        "type" => OBJECTTYPE_SERVICE,
                                        "host_name" => $hostname,
                                        "service_description" => $portname . " Bandwidth",
                                        "use" => "xiwizard_switch_port_bandwidth_service",
                                        "check_command" => "check_xi_service_mrtgtraf!" . $hostaddress . "_" . $portnum . ".rrd!" . $warn_pair . "!" . $crit_pair . "!" . $label,
                                        "_xiwizard" => $wizard_name,
                                    );
                                }
                            }

                            // monitor port status
                            if (array_key_exists("portstatus", $serviceargs)) {
                                if (array_key_exists($portnum, $serviceargs["portstatus"])) {
                                    //echo "MONITOR PORT STATUS ON $portnum<BR>\n";
                                    if ($snmpversion != 3) {
                                        $objs[] = array(
                                            "type" => OBJECTTYPE_SERVICE,
                                            "host_name" => $hostname,
                                            "service_description" => $portname . " Status",
                                            "use" => "xiwizard_switch_port_status_service",
                                            "check_command" => "check_xi_service_ifoperstatus!" . $snmpopts['snmpcommunity'] . "!" . $portnum . "!-v " . (int)$snmpversion,
                                            "_xiwizard" => $wizard_name,
                                        );
                                    } else {

                                        // If privlidge password
                                        $priv_password_and_proto = "";
                                        if (!empty($snmpopts['v3_priv_password'])) {
                                            $priv_password_and_proto = "-x {$snmpopts['v3_priv_proto']}";
                                            $priv_password_and_proto .= " -X {$snmpopts['v3_priv_password']}";
                                        }

                                        $objs[] = array(
                                            "type" => OBJECTTYPE_SERVICE,
                                            "host_name" => $hostname,
                                            "service_description" => $portname . " Status",
                                            "use" => "xiwizard_switch_port_status_service",
                                            "check_command" => "check_xi_service_ifoperstatusnag!{$portnum}!-v{$snmpversion} -u {$snmpopts['v3_username']} -A {$snmpopts['v3_auth_password']} {$priv_password_and_proto} -a {$snmpopts["v3_auth_proto"]} -l {$snmpopts['v3_security_level']}",
                                            "_xiwizard" => $wizard_name,
                                        );
                                    }
                                }
                            }
                        }
                        break;

                    default:
                        break;
                }
            }

            //echo "OBJECTS:<BR>";
            //print_r($objs);
            //exit();

            // tell MRTG to start monitoring the watchguard device
            if ($have_bandwidth == true) {
                $tmp_dir = get_tmp_dir();
                $outfile = $tmp_dir . "/mrtgscan-" . $address;
                watchguard_configwizard_add_walk_file_to_mrtg($outfile, $address);
                //echo "ADDED WALK FILE TO MRTG...";
            }
            //else
            //  echo "WE DON'T HAVE BANDWIDTH...";

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}

/**
 * @param $f
 * @param $address
 *
 * @return array
 */
function watchguard_configwizard_read_walk_file($f, $address)
{

    $output = array();

    // open the walk file for reading
    $fi = fopen($f, "r");
    if ($fi) {

        while (!feof($fi)) {

            $buf = fgets($fi);

            // skip comments
            $pos = strpos($buf, "#");
            if ($pos !== false)
                continue;

            // found the target line (contains port number)
            if (preg_match('/Target\[' . $address . '_([0-9\.]+)\]/', $buf, $matches)) {
                $port_number = $matches[1];
                //echo "FOUND PORT $port_number<BR>\n";
                if (!array_key_exists($port_number, $output)) {
                    $output[$port_number] = array(
                        "port_number" => $port_number,
                        "port_description" => _("Port ") . $port_number,
                        "max_bytes" => 0,
                        "port_long_description" => _("Port ") . $port_number,
                    );
                }
            }
            // we have the port speed
            if (preg_match('/MaxBytes\[' . $address . '_([0-9\.]+)\]: ([0-9\.]+)/', $buf, $matches)) {
                $port_number = $matches[1];
                $max_bytes = $matches[2];
                //echo "PORT $port_number SPEED IS $max_bytes<BR>\n";
                //$output[$port_number]=$max_bytes;

                if (!array_key_exists($port_number, $output)) {
                    $output[$port_number] = array(
                        "port_number" => $port_number,
                        "port_description" => _("Port ") . $port_number,
                        "max_bytes" => $max_bytes,
                        "port_long_description" => _("Port ") . $port_number,
                    );
                } else
                    $output[$port_number]["max_bytes"] = $max_bytes;

            }
            // we found the description
            //modified so that the short description will replace port number if found
            if (preg_match('/MRTG_INT_DESCR=/', $buf, $matches)) {
                //key string
                $key = 'MRTG_INT_DESCR="';
                //find position of value and grab substring
                $position = strpos($buf, $key) + strlen($key);
                $short_descrip = substr($buf, $position, (strlen($buf)));
                //strip quotes and spaces
                $short_descrip = trim(str_replace('"', NULL, $short_descrip));
                //echo $short_descrip.'<br />';
                // save the description
                $output[$port_number]["port_description"] = $short_descrip;
                $output[$port_number]["port_long_description"] = $short_descrip;
                $longKey = "<td>" . $short_descrip;
            }

            //check for user defined description
            if (isset($longKey) && strpos($buf, $longKey)) {
                $position = strpos($buf, $longKey) + strlen($longKey);
                $long_descrip = substr($buf, $position, (strlen($buf)));
                //strip td tag and spaces
                $long_descrip = trim(str_replace("</td>", NULL, $long_descrip));
                // save the description
                if ($long_descrip != '') $output[$port_number]["port_long_description"] = $long_descrip;

            }

        }
        //end IF FILE is open


        fclose($fi);
    }

    //print_r($output);

    return $output;
}

/**
 * @param $f
 * @param $address
 *
 * @return bool
 */
function watchguard_configwizard_add_walk_file_to_mrtg($f, $address)
{
    $debug = false;

    $mrtg_cfg = "/etc/mrtg/mrtg.cfg";
    $mrtg_conf_dir = "/etc/mrtg/conf.d";
    $address_config = "{$address}.cfg";

    if ($debug) {
        echo "Checking for existing config for $address at {$address}.cfg.";

        $dir_contents = scandir($mrtg_conf_dir);
        if (in_array($address_config, $dir_contents) === true) {
            echo "Config exists, it will be overwritten.";
        } else {
            echo "Config does not exist, it will be created.";
        }
    }

    // Open the devices config file for writing.
    $absolute_address_config = "{$mrtg_conf_dir}/{$address_config}";
    $fo = fopen($absolute_address_config, "w");

    if (!$fo) {

        if ($debug) {
            echo "UNABLE TO OPEN {$mrtg_cfg} FOR APPENDING!<br>";
        }
        return false;
    }

    // open the walk file for reading.
    $fi = fopen($f, "r");
    if ($fi) {

        fprintf($fo, "\n\n#### ADDED BY NAGIOSXI (USER: %s, DATE: %s) ####\n", get_user_attr(0, "username"), get_datetime_string(time()));

        while (!feof($fi)) {
            $buf = fgets($fi);
            fputs($fo, $buf);
        }
    }

    fclose($fo);

    // immediately tell mrtg to generate data from the new walk file 
    // if we didn't do this, nagios might send alerts about missing rrd files!
    $cmd = "mrtg {$f} &";
    exec($cmd);

    return true;
}

/**
 * @param $max_bytes
 * @param $speed
 * @param $label
 *
 * @return string
 */
function watchguard_configwizard_get_readable_port_line_speed($max_bytes, &$speed, &$label)
{

    //$bps=$max_bytes/8;
    $bps = $max_bytes * 8;

    $kbps = $bps / 1e3;
    $mbps = $bps / 1e6;
    $gbps = $bps / 1e9;

    if ($gbps >= 1) {
        $speed = $gbps;
        $label = "Gbps";
    } else if ($mbps >= 1) {
        $speed = $mbps;
        $label = "Mbps";
    } else if ($kbps >= 1) {
        $speed = $kbps;
        $label = "Kbps";
    } else {
        $speed = $bps . " bps";
        $label = "bps";
    }

    $output = number_format($speed, 2) . " " . $label;

    return $output;
}


/**
 * @param $warn_in_speed
 * @param $warn_out_speed
 * @param $crit_in_speed
 * @param $crit_out_speed
 * @param $label
 */
function watchguard_configwizard_recalculate_speeds(&$warn_in_speed, &$warn_out_speed, &$crit_in_speed, &$crit_out_speed, &$label)
{

    while (1) {

        if ($label == "bps")
            break;

        $maxval = max($warn_in_speed, $warn_out_speed, $crit_in_speed, $crit_out_speed);

        if ($maxval < 1) {

            switch ($label) {
                case "Gbps":
                    //echo "GBPS=$warn_in_speed<BR>";
                    $label = "Mbps";
                    break;
                case "Mbps":
                    $label = "Kbps";
                    break;
                case "Kbps":
                    $label = "bps";
                    break;
                default:
                    break;
            }

            // bump down a level
            $warn_in_speed *= 1000;
            $warn_out_speed *= 1000;
            $crit_in_speed *= 1000;
            $crit_out_speed *= 1000;

        } else
            break;
    }

}

/**
 * @return bool
 */
function watchguard_configwizard_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 399)
        return false;
    if (!function_exists('use_2014_features') || !use_2014_features())
        return false;

    return true;
}

// Check if we should define nagiosccm functionality
// THIS SHOULD BE PHASED OUT ONCE NEW RELEASES OF XI COME OUT!
if (!function_exists('nagiosccm_replace_user_macros')) {
    /**
     * @param $str
     *
     * @return mixed
     */
    function nagiosccm_replace_user_macros($str)
    {
        if (empty($str)) {
            return "";
        }

        // Grab the resource.cfg and read it
        $lines = file('/usr/local/nagios/etc/resource.cfg', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $user_macros = array();
        $user_macro_values = array();

        foreach ($lines as $k => $line) {
            if ($line[0] != "#") {
                list($macro, $value) = explode("=", $line);
                $user_macros[] = trim($macro);
                $user_macro_values[] = trim($value);
            }
        }

        // Replace macros in the string given
        $newstr = str_replace($user_macros, $user_macro_values, $str);
        return $newstr;
    }
}