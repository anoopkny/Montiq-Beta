<?php
//
// Switch/Router Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$
//
include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

switch_configwizard_init();
function switch_configwizard_init()
{
    $name = "switch";
    $args = array(
        CONFIGWIZARD_NAME =>               $name,
        CONFIGWIZARD_VERSION =>            "2.3.5",
        CONFIGWIZARD_TYPE =>               CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION =>        _("Monitor a network switch or router."),
        CONFIGWIZARD_DISPLAYTITLE =>       _("Network Switch / Router"),
        CONFIGWIZARD_FUNCTION =>           "switch_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE =>       "switch.png",
        CONFIGWIZARD_FILTER_GROUPS =>      array('network'),
        CONFIGWIZARD_REQUIRES_VERSION =>   530
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
 * @return String - String to be executed
 */
function switch_configwizard_get_cfgmaker_cmd($snmpopts, $address, $port, $snmpversion = "1", $defaultspeed = "100000000")
{
    $snmpversion = (in_array($snmpversion, array("1", "2c", "3")) ? $snmpversion : "1");

    $cmd = "/usr/bin/cfgmaker ";
    $args[] = "--show-op-down";
    $args[] = "--noreversedns";
    $args[] = "--zero-speed";
    $args[] = escapeshellarg($defaultspeed);

    if (empty($snmpopts['v3_username'])) {

        // Run SNMPv1, SNMPv2, SNMPv2c code here
        $username = $snmpopts['snmpcommunity'];
        $delimitors = ":::::";
        if (!empty($port))
            $delimitors = ":".intval($port)."::::";

        $args[] = escapeshellarg("{$username}@{$address}{$delimitors}" . (int) $snmpversion);

        file_put_contents("/tmp/bs", escapeshellarg("{$username}@{$address}{$delimitors}" . (int) $snmpversion));

    } else {

        // Run SNMPv3 code here
        $args[] = "--enablesnmpv3";
        $args[] = "--snmp-options=:::::3";

        if (!empty($snmpopts['v3_username']))
            $args[] = "--username=" . escapeshellarg($snmpopts['v3_username']);
        
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
 *
 * Main configwizard driver function
 *
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function switch_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "switch";

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

<h5 class="ul">' . _("Router/Switch Information") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _("IP Address") . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _("The IP address of the network device you'd like to monitor") . '</div>
        </td>
    </tr>
    <tr>
        <td valign="top">
            <label>'._("Port").':</label>
        </td>
        <td>
            <input type="text" size="6" name="port" id="port" value="'.encode_form_val($address_port).'" class="form-control">
            <div class="subtext">'._("The port of the network device").'</div>
        </td>
    </tr>
</table>
<input type="hidden" name="snmpversion" id="snmpversion" value="' . htmlentities($snmpversion) . '">

<div id="tabs" style="width: 600px;">
    <ul>
        <li><a href="#snmpv1" id="selectv1">SNMPv1</a></li>
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
                    <div class="subtext">' . _("The SNMP community string required used to to query the network device") . '</div>
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
                    <label>' . _("Authentication Password") . ':</label>
                </td>
                <td>
                    <input type="text" size="20" name="snmpopts[v3_auth_password]" value="' . $snmpopts["v3_auth_password"] . '" class="form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Privileged Password") . ':</label>
                </td>
                <td>
                    <input type="text" size="20" name="snmpopts[v3_priv_password]" value="' . $snmpopts["v3_priv_password"] . '" class="form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Authentication Protocol") . ':</label>
                </td>
                <td>
                    <select name="snmpopts[v3_auth_proto]" class="form-control">
                        <option value="MD5" ' . is_selected($snmpopts["v3_auth_proto"], "MD5") . '>MD5</option>
                        <option value="SHA" ' . is_selected($snmpopts["v3_auth_proto"], "SHA") . '>SHA</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>' . _("Privileged Protocol") . ':</label>
                </td>
                <td>
                    <select name="snmpopts[v3_priv_proto]" class="form-control">
                        <option value="DES" ' . is_selected($snmpopts["v3_priv_proto"], "DES") . '>DES</option>
                        <option value="AES" ' . is_selected($snmpopts["v3_priv_proto"], "AES128") . '>AES</option>
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
            <label><input name="scaninterfaces" type="checkbox" ' . is_checked($scaninterfaces) . '> ' . _("Scan the switch or router to auto-detect interfaces that can be monitored for link up/down status and bandwidth usage.
The scanning process may take several seconds to complete.") . '</label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _("Default Values") . '</h5>
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
            
            // check passwords for bad characters (! and ;)
            if (!empty($snmpopts["v3_auth_password"])) {
                if (strpos($snmpopts["v3_auth_password"], '!') || strpos($snmpopts["v3_auth_password"], ';')) {
                    $errmsg[$errors++] = _("You cannot use '!' or ';' characters in authentification password field.");
                }
            }

            if (!empty($snmpopts["v3_priv_password"])) {
                if (strpos($snmpopts["v3_priv_password"], '!') || strpos($snmpopts["v3_priv_password"], ';')) {
                    $errmsg[$errors++] = _("You cannot use '!' or ';' characters in priveleged password field.");
                }
            }                 

            // Error results
            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            // If user wants to scan interfaces, immediately launch the command and start working on it....
            if ($scaninterfaces == "on") {

                $outfile = switch_configwizard_get_walk_file($address);
                $donefile = $outfile . ".done";

                // Get rid of old "done" file if it exists
                if (file_exists($donefile)) {
                    unlink($donefile);
                }

                // launch cfgmaker command to scan for ports
                $cfgmaker_cmd = switch_configwizard_get_cfgmaker_cmd($snmpopts, $address, $address_port, $snmpversion, $default_port_speed);
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
            $hostname = nagiosccm_replace_user_macros($hostname);

            $output = '
<input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($address_port) . '">
<input type="hidden" name="vendor" value="' . htmlentities($vendor) . '">
<input type="hidden" name="snmpopts_serial" value="' . $snmpopts_serial . '">
<input type="hidden" name="portnames" value="' . htmlentities($portnames) . '">
<input type="hidden" name="scaninterfaces" value="' . htmlentities($scaninterfaces) . '">
<input type="hidden" name="warn_speed_in_percent" value="' . htmlentities($warn_speed_in_percent) . '">
<input type="hidden" name="crit_speed_in_percent" value="' . htmlentities($crit_speed_in_percent) . '">
<input type="hidden" name="warn_speed_out_percent" value="' . htmlentities($warn_speed_out_percent) . '">
<input type="hidden" name="crit_speed_out_percent" value="' . htmlentities($crit_speed_out_percent) . '">

<h5 class="ul">' . _('Switch Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Switch/Router Address') . ':</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this switch or router.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the switch or router.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="p" class="checkbox" id="ping" name="services[ping]" checked>
        </td>
        <td>
            <label class="normal" for="p">
                <b>' . _('Ping') . '</b><br>
                ' . _('Monitors the switch/router with an ICMP ping.  Useful for watching network latency and general uptime.') . '
            </label>
        </td>
    </tr>
</table>';

                $ports = switch_configwizard_read_walk_file($address);
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
                                <th>' . _('Port') . '<br><a href="javascript:void(0);" onclick="switchCheckAll()" title="' . _('Check All Ports') . '">' . _('Check') . '</a> /
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
                                    ' . _('Bandwidth') . '<br><a href="javascript:void(0);" onclick="bandwidthCheckAll()" title="' . _('Check All Ports') . '">' . _('Check') . '</a> /
                                    <a href="javascript:void(0);" onclick="bandwidthUncheckAll()" title="' . _('Uncheck All Ports') . '">' . _('Uncheck') . '</a>
                                </th>
                                <th>
                                    ' . _('Port Status') . '<br><a href="javascript:void(0);" onclick="statusCheckAll()" title="' . _('Check All Ports') . '">' . _('Check') . '</a> /
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

                        $max_speed = switch_configwizard_get_readable_port_line_speed($port_bytes, $speed, $label);
                        $warn_in_speed = ($speed * ($warn_speed_in_percent / 100));
                        $warn_out_speed = ($speed * ($warn_speed_out_percent / 100));
                        $crit_in_speed = ($speed * ($crit_speed_in_percent / 100));
                        $crit_out_speed = ($speed * ($crit_speed_out_percent / 100));

                        // Possible refomat speed values/labels
                        switch_configwizard_recalculate_speeds($warn_in_speed, $warn_out_speed, $crit_in_speed, $crit_out_speed, $label);

                        $output .= '
                        <tr>
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
                        <input type="text" size="3" name="serviceargs[bandwidth_warning_input_value][' . $port_num . ']" value="' . number_format($warn_in_speed, 2) . '" class="form-control condensed">
                        </td>
                        <td>
                        <input type="text" size="3" name="serviceargs[bandwidth_warning_output_value][' . $port_num . ']" value="' . number_format($warn_out_speed, 2) . '" class="form-control condensed">
                        </td>
        
                        <td>
                        <label>' . _('Critical') . ':</label>
                        </td>
                        <td>
                        <input type="text" size="3" name="serviceargs[bandwidth_critical_input_value][' . $port_num . ']" value="' . number_format($crit_in_speed, 2) . '" class="form-control condensed">
                        </td>
                        <td>
                        <input type="text" size="3" name="serviceargs[bandwidth_critical_output_value][' . $port_num . ']" value="' . number_format($crit_out_speed, 2) . '" class="form-control condensed">
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
                        </tr>';
                    }

                    $output .= '
                        </tbody>
                    </table>
                    ';
                } else {
                    $output .= '
                    <img src="' . theme_image("critical_small.png") . '">
                    <b>' . _('No ports were detected on the switch.') . '</b>  ' . _('Possible reasons for this include') . ':
                    <ul>
                    <li>' . _('The switch is currently down') . '</li>
                    <li>' . _('The switch does not exist at the address you specified') . '</li>
                    <li>' . _('SNMP support on the switch is disabled') . '</li>
                    </ul>
                    ';

                    if (is_admin() == true) {
                        $cfgmaker_cmd = switch_configwizard_get_cfgmaker_cmd($snmpopts, $address, $address_port, $snmpversion);
                        $output .= '
                        <br>
                        <img src="' . theme_image("ack.png") . '">
                        <b>' . _('Troubleshooting Tip') . ':</b>
                        <p>
                        ' . _('If you keep experiencing problems with the switch wizard scan, login to the Nagios XI server as the root user and execute the following command') . ':
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
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname");
            $address = grab_array_var($inargs, "address");
            $address_port = grab_array_var($inargs, "port", 161);
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
            $address_port = grab_array_var($inargs, "port", 161);
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

            global $request;
            debug($request);
            debug($services);
            debug($serviceargs);

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '" />
        <input type="hidden" name="port" value="' . htmlentities($address_port) . '" />
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

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $address_port = grab_array_var($inargs, "port", 161);
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

            debug($services);            
            debug($serviceargs);

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

            $objs = array();

            //if (!host_exists($hostname)) {

                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_switch_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "switch.png",
                    "statusmap_image" => "switch.png",
                    "_xiwizard" => $wizard_name,
                );
            //}

            $have_bandwidth = false;

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                debug("PROCESSING: $svc -> $svcstate");

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

                    case "port":

                        foreach ($svcstate as $portnum => $portstate) {

                            debug("HAVE PORT $portnum");

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

                                    debug("MONITOR PORT STATUS ON $portnum");

                                    if ($snmpversion != 3) {

                                        $objs[] = array(
                                            "type" => OBJECTTYPE_SERVICE,
                                            "host_name" => $hostname,
                                            "service_description" => $portname . " Status",
                                            "use" => "xiwizard_switch_port_status_service",
                                            "check_command" => "check_xi_service_ifoperstatus!" . $snmpopts['snmpcommunity'] . "!" . $portnum . "!-v " . (int)$snmpversion . " -p " . $address_port,
                                            "_xiwizard" => $wizard_name,
                                        );

                                    } else {

                                        // If privilege password
                                        $priv_password_and_proto = "";
                                        if (!empty($snmpopts['v3_priv_password'])) {
                                            $priv_password_and_proto = "-x {$snmpopts['v3_priv_proto']}";
                                            $priv_password_and_proto .= " -X \"{$snmpopts['v3_priv_password']}\"";
                                        }

                                        $objs[] = array(
                                            "type" => OBJECTTYPE_SERVICE,
                                            "host_name" => $hostname,
                                            "service_description" => $portname . " Status",
                                            "use" => "xiwizard_switch_port_status_service",
                                            "check_command" => "check_xi_service_ifoperstatusnag!{$portnum}!-v{$snmpversion} -u {$snmpopts['v3_username']} -A \"{$snmpopts['v3_auth_password']}\" {$priv_password_and_proto} -a {$snmpopts["v3_auth_proto"]} -l {$snmpopts['v3_security_level']}",
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

            debug($objs);

            if ($have_bandwidth) {

                // attempt to add the file to mrtg
                if (switch_configwizard_add_cfg_to_mrtg($address) == false) {
                    debug("Adding the MRTG configuration failed.");
                    echo _("Adding the MRTG configuration failed!");
                }

                // update mrtg (force creation of rrd files so nagios doesn't freak out)
                switch_configwizard_update_mrtg();
            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;
            break;

        default:
            break;
    }

    return $output;
}


/**
 *
 * Return a standardardized location for walk/cfgmaker files
 *
 * @param $address string the address to use to build the cfgmaker file location
 *
 * @return string - absolute path to file for cfgmaker walk
 *
 */
function switch_configwizard_get_walk_file($address) {

    return get_tmp_dir() . "/mrtg-{$address}";
}


 /**
*
* Read a cfgmaker file to determine port information
*
* @param $address string - the address used to locate the cfgmaker file
* @param $mrtg_cfg boolean - if true, then look for legacy file instead of cfgmaker file
*
* @return array - port information scanned from file
*/
function switch_configwizard_read_walk_file($address, $mrtg_cfg = false)
{

    $cfgmaker_file = switch_configwizard_get_walk_file($address);
    if ($mrtg_cfg)
        $cfgmaker_file = switch_configwizard_get_legacy_mrtg_cfg_file($address);

    debug($cfgmaker_file);

    $output = array();

    // open the walk file for reading
    $cfgmaker_file_handle = fopen($cfgmaker_file, "r");
    if ($cfgmaker_file_handle) {

        while (!feof($cfgmaker_file_handle)) {

            $buf = fgets($cfgmaker_file_handle);

            // skip comments
            $pos = strpos($buf, "#");
            if ($pos === 0)
                continue;

            // we only care about lines that contain a few keywords, so lets check for those first
            // and if we're sure our lines don't contain them, skip so as not to perform a lot of unecessary regex -bh

            if (strpos($buf, "Target[") !== false) {

                // found the target line (contains port number)
                if (preg_match('/Target\[' . $address . '_([0-9\.]+)\]/', $buf, $matches)) {

                    $port_number = $matches[1];

                    if (!array_key_exists($port_number, $output)) {
                        $output[$port_number] = array(
                            "port_number" => $port_number,
                            "port_description" => _("Port ") . $port_number,
                            "max_bytes" => 0,
                            "port_long_description" => _("Port ") . $port_number,
                        );
                    }

                    continue;
                }
            }

            if (strpos($buf, "MaxBytes[") !== false) {

                // we have the port speed
                if (preg_match('/MaxBytes\[' . $address . '_([0-9\.]+)\]: ([0-9\.]+)/', $buf, $matches)) {

                    $port_number = $matches[1];
                    $max_bytes = $matches[2];

                    if (!array_key_exists($port_number, $output)) {
                        $output[$port_number] = array(
                            "port_number" => $port_number,
                            "port_description" => _("Port ") . $port_number,
                            "max_bytes" => $max_bytes,
                            "port_long_description" => _("Port ") . $port_number,
                        );
                    } else {
                        $output[$port_number]["max_bytes"] = $max_bytes;
                    }

                    continue;
                }
            }

            $key = 'MRTG_INT_DESCR="';
            $pos = strpos($buf, $key);
            if ($pos !== false) {

                // we found the description
                // modified so that the short description will replace port number if found

                // find position of value and grab substring
                $position = $pos + strlen($key);
                $short_descrip = substr($buf, $position, (strlen($buf)));

                // strip quotes and spaces
                $short_descrip = trim(str_replace('"', NULL, $short_descrip));

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

        fclose($cfgmaker_file_handle);
    }

    debug($output);
    return $output;
}


/**
*
* Add a specific cfgmaker file to mrtg config
* NOTE: we use copy+exec(sed) because it's a bit faster than all of the calls to fopen/fwrite/etc.
*       this is a pretty big deal on larger systems!
*
* @param $address string - address to search in 
*
* @return bool
*/
function switch_configwizard_add_cfg_to_mrtg($address)
{
    // get the data that we need
    $mrtg_confd_dir = "/etc/mrtg/conf.d";
    $mrtg_cfg_file = "{$address}.cfg";
    $absolute_mrtg_cfg_file = "{$mrtg_confd_dir}/{$mrtg_cfg_file}";
    $cfgmaker_file = switch_configwizard_get_walk_file($address);

    // check if the file already exists for useful debugging
    $mrtg_confd_contents = scandir($mrtg_confd_dir);
    if (in_array($mrtg_cfg_file, $mrtg_confd_contents)) {
        debug("{$mrtg_cfg_file} exists in {$mrtg_confd_dir}, overwriting");
    } else {
        debug("{$mrtg_cfg_file} does not exist in {$mrtg_confd_dir}, creating");
    }

    // copy the cfgmaker file to the mrtg cfg destination
    if (!copy($cfgmaker_file, $absolute_mrtg_cfg_file)) {
        debug("Unable to copy from {$cfgmaker_file} to {$absolute_mrtg_cfg_file}");
        return false;
    }

    // add some meta info to the file
    $infoline = "#### ADDED BY NAGIOSXI (User: ". get_user_attr(0, 'username') .", DATE: ". get_datetime_string(time()) .") ####";
    exec("sed -i '1s|.*|{$infoline}&|' $absolute_mrtg_cfg_file");

    return true;
}


/**
*
* Updates the mrtg config to stop nagios from alerting that rrd files don't exist
*
*/
function switch_configwizard_update_mrtg() {

    if (@is_executable("/usr/bin/mrtg")) {

        // for whatever reason this won't return immediately without the redirectors
        $cmd = "env LANG=C /usr/bin/mrtg /etc/mrtg/mrtg.cfg >/dev/null 2>&1 &";
        exec($cmd);
    }
}


/**
 *
 * Append some human readable information to maxbytes/portspeed
 *
 * @param $max_bytes
 * @param $speed
 * @param $label
 *
 * @return string
 */
function switch_configwizard_get_readable_port_line_speed($max_bytes, &$speed, &$label)
{
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
 *
 * Recalculate warn/crit in/out speeds based on label/readability
 *
 * @param $warn_in_speed
 * @param $warn_out_speed
 * @param $crit_in_speed
 * @param $crit_out_speed
 * @param $label
 */
function switch_configwizard_recalculate_speeds(&$warn_in_speed, &$warn_out_speed, &$crit_in_speed, &$crit_out_speed, &$label)
{

    while (1) {

        if ($label == "bps")
            break;

        $maxval = max($warn_in_speed, $warn_out_speed, $crit_in_speed, $crit_out_speed);

        if ($maxval < 1) {

            switch ($label) {

                case "Gbps":
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
 *
 * Debug logging - dummy function override for xiver < 5.3.0
 *
 */
if (!function_exists('debug')) {
    function debug() { }
}

?>
