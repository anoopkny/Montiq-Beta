<?php
//
// SNMP Walk Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

snmpwalk_configwizard_init();

function snmpwalk_configwizard_init()
{
    $name = "snmpwalk";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Scan an SNMP-enabled device for elements to monitor."),
        CONFIGWIZARD_DISPLAYTITLE => _("SNMP Walk"),
        CONFIGWIZARD_FUNCTION => "snmpwalk_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "snmp.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
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
function snmpwalk_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "snmpwalk";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "161");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "public");

            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $timeout = grab_array_var($inargs, "timeout", 15);
            $maxresults = grab_array_var($inargs, "maxresults", 100);
            $oid = grab_array_var($inargs, "oid", "mib-2.interfaces");
            $forcescan = grab_array_var($inargs, "forcescan", 0);

            $snmpopts = "";
            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial != "")
                $snmpopts = unserialize(base64_decode($snmpopts_serial));
            if (!is_array($snmpopts)) {
                $snmpopts_default = array(
                    "v3_security_level" => "",
                    "v3_username" => "",
                    "v3_auth_password" => "",
                    "v3_privacy_password" => "",
                    "v3_auth_proto" => "",
                    "v3_priv_proto" => "",
                );
                $snmpopts = grab_array_var($inargs, "snmpopts", $snmpopts_default);
            }

            $output = '
<h5 class="ul">'._('SNMP Information').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('Device Address').':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">'._('The IP address or fully qualified DNS name of the server or device you\'d like to monitor.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Device Port').':</label>
        </td>
        <td>
            <input type="text" size="40" name="port" id="port" value="' . htmlentities($port) . '" class="form-control">
            <div class="subtext">'._('The port on which the SNMP device is listening.').'</div>
        </td>
    </tr>
</table>

<h5 class="ul">'._('SNMP Scan Settings').'</h5>
<p>'._('Specify the settings used to scan the server or device via SNMP.').'</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('SNMP Community').':</label>
        </td>
        <td>
            <input type="text" size="20" name="snmpcommunity" id="snmpcommunity" value="' . htmlentities($snmpcommunity) . '" class="form-control">
            <div class="subtext">'._('The SNMP community string used to to query the device.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('SNMP Version').':</label>
        </td>
        <td>
            <select name="snmpversion" id="snmpversion" class="form-control">
                <option value="1" ' . is_selected($snmpversion, "1") . '>1</option>
                <option value="2c" ' . is_selected($snmpversion, "2c") . '>2c</option>
                <option value="3" ' . is_selected($snmpversion, "3") . '>3</option>
            </select>
            <div class="subtext">'._('The SNMP protocol version used to commicate with the device.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>OID:</label>
        </td>
        <td>
            <input type="text" size="20" name="oid" id="oid" value="' . htmlentities($oid) . '" class="form-control">
            <div class="subtext">'._('The top-level OID to use for scanning.  Clear this field to scan for all OIDs on the device.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Timeout').':</label>
        </td>
        <td>
            <input type="text" size="2" name="timeout" id="timeout" value="' . htmlentities($timeout) . '" class="form-control">
            <div class="subtext">'._('The maximum number of seconds to wait for the SNMP scan to complete.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Max Results').':</label>
        </td>
        <td>
            <input type="text" size="3" name="maxresults" id="maxresults" value="' . htmlentities($maxresults) . '" class="form-control">
            <div class="subtext">'._('The maximum number of results (OIDs) to process from the SNMP scan.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Force Scan').':</label>
        </td>
        <td>
            <select name="forcescan" id="forcescan" class="form-control">
                <option value="0" ' . is_selected($forcescan, "0") . '>No</option>
                <option value="1" ' . is_selected($forcescan, "1") . '>Yes</option>
            </select>
            <div class="subtext">'._('By default, a scan is only performed if a previous scan result is not found. Override default scan behavior with this option.').'</div>
        </td>
    </tr>
</table>

<div id="snmpv3" class="hide">
    <h5 class="ul">'._('SNMP Authentication').'</h5>
    <p>'._('When using SNMP v3 you must specify authentication information.').'</p>
    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td>
                <label>'._('Security Level').':</label>
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
                <label>'._('Username').':</label>
            </td>
            <td>
                <input type="text" size="20" name="snmpopts[v3_username]" value="' . htmlentities($snmpopts["v3_username"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>'._('Privacy Password').':</label>
            </td>
            <td>
                <input type="text" size="20" name="snmpopts[v3_privacy_password]" value="' . htmlentities($snmpopts["v3_privacy_password"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>'._('Authentication Password').':</label>
            </td>
            <td>
                <input type="texs" size="20" name="snmpopts[v3_auth_password]" value="' . htmlentities($snmpopts["v3_auth_password"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>'._('Authentication Protocol').':</label>
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
                <label>' . _('Privileged Protocol') . ':</label>
            </td>
            <td>
                <select name="snmpopts[v3_priv_proto]" class="form-control">
                    <option value="des" ' . is_selected($snmpopts["v3_priv_proto"], "des") . '>DES</option>
                    <option value="aes" ' . is_selected($snmpopts["v3_priv_proto"], "aes") . '>AES</option>
                </select>
            </td>
        </tr>
    </table>
</div>

<div id="snmpwalk-throbber" class="hide" style="padding-bottom: 120px; z-index: 10000;">
    <div class="message" style="width: 450px;">
        <ul class="infoMessage">
            <li><b>'._('Scanning device. Please wait...').'</b></li>
        </ul>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $("#configWizardForm").submit(function(e) {
        whiteout();
        $("#snmpwalk-throbber").center().show();
    });
    $("#snmpversion").change(function() {
        if ($(this).val() != "3") {
            $("#snmpv3").hide();
        } else {
            $("#snmpv3").show();
        }
    });
});
</script>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "161");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "public");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $oid = grab_array_var($inargs, "oid", "");
            $timeout = grab_array_var($inargs, "timeout", 15);
            $maxresults = grab_array_var($inargs, "maxresults", 100);

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = "No address specified.";
            if (!is_numeric($port))
                $errmsg[$errors++] = "Port number must be numeric.";
            if (have_value($snmpcommunity) == false && $snmpversion != "3")
                $errmsg[$errors++] = "No SNMP community specified.";
            if (have_value($snmpversion) == false)
                $errmsg[$errors++] = "No SNMP version specified.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);

            $port = grab_array_var($inargs, "port", "161");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "public");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $oid = grab_array_var($inargs, "oid", "");
            $timeout = grab_array_var($inargs, "timeout", 15);
            $maxresults = grab_array_var($inargs, "maxresults", 200);
            $forcescan = grab_array_var($inargs, "forcescan", 0);

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial == "")
                $snmpopts = grab_array_var($inargs, "snmpopts");
            else
                $snmpopts = unserialize(base64_decode($snmpopts_serial));

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            else
                $serviceargs = grab_array_var($inargs, "serviceargs");

            // START THE SCAN!
            $resultfile = get_tmp_dir() . "/snmpwalk-" . escapeshellcmd($oid) . "-" . escapeshellcmd($address);

            // START A NEW SCAN IF WE CAN'T FIND A PRIOR RESULT
            if ((!file_exists($resultfile) || $forcescan == 1) && !(is_array($serviceargs) && count($serviceargs["oid"]) > 0)) {

                // snmp v3 stuff
                $cmdargs = "";
                if ($snmpversion == "3") {

                    $securitylevel = grab_array_var($snmpopts, "v3_security_level");
                    $username = grab_array_var($snmpopts, "v3_username");
                    $authproto = grab_array_var($snmpopts, "v3_auth_proto");
                    $authpassword = grab_array_var($snmpopts, "v3_auth_password");
                    $privacypassword = grab_array_var($snmpopts, "v3_privacy_password");
                    $privproto = grab_array_var($snmpopts, "v3_priv_proto");

                    if ($securitylevel != "")
                        $cmdargs .= " -l " . $securitylevel;
                    if ($username != "")
                        $cmdargs .= " -u " . nagiosccm_replace_user_macros($username);
                    if ($authproto != "")
                        $cmdargs .= " -a " . $authproto;
                    if ($authpassword != "")
                        $cmdargs .= " -A " . nagiosccm_replace_user_macros($authpassword);
                    if ($privacypassword != "")
                        $cmdargs .= " -X " . nagiosccm_replace_user_macros($privacypassword);
                    if ($privproto != "")
                        $cmdargs .= " -x " . $privproto;

                }

                if ($oid == "")
                    $useoid = "";
                else
                    $useoid = escapeshellcmd($oid);

                // Replace user macros for fields we can use them in
                $cstring =nagiosccm_replace_user_macros($snmpcommunity);


                if ($snmpversion == "3") {
                    $cmdline = "/usr/bin/snmpwalk -v " . escapeshellcmd($snmpversion) . " " . escapeshellcmd($cmdargs) . " " . escapeshellcmd(nagiosccm_replace_user_macros($address)) . ":" . escapeshellcmd(nagiosccm_replace_user_macros($port)) . " " . $useoid . " > " . $resultfile . " 2>&1 & echo $!";
                } else
                    $cmdline = "/usr/bin/snmpwalk -v " . escapeshellcmd($snmpversion) . " -c " . escapeshellcmd(nagiosccm_replace_user_macros($snmpcommunity)) . escapeshellcmd($cmdargs) . " " . escapeshellcmd(nagiosccm_replace_user_macros($address)) . ":" . escapeshellcmd(nagiosccm_replace_user_macros($port)) . " " . $useoid . " > " . $resultfile . " 2>&1 & echo $!";

                exec($cmdline, $op);
                $pid = (int)$op[0];

                // Wait until earlier of timeout or completion
                sleep(2);
                for ($x = 0; $x < $timeout; $x++) {

                    // See if process if still running...
                    exec("ps ax | grep $pid 2>&1", $output);
                    $check_pid = "";
                    while (list(, $row) = each($output)) {
                        $row_array = explode(" ", $row);
                        $check_pid = $row_array[0];
                        if ($pid == $check_pid) {
                            break;
                        }
                    }

                    // Process is gone - it must be done!
                    if ($check_pid != $pid)
                        break;

                    // Else process is still running
                    sleep(1);
                }

            } else {
                //echo "USING EXISTING SCAN FILE!<BR>";
            }

            // read the results (up to 100,000 bytes)
            $fcontents = file_get_contents($resultfile, false, NULL, -1, 100000);

            //echo "CONTENTS:<BR>$fcontents<BR>";

            $rows = explode("\n", $fcontents);
            $x = 0;
            foreach ($rows as $row) {

                // get mib
                $parts = explode("::", $row);
                $mib = $parts[0];

                array_shift($parts);
                $newrow = implode("::", $parts);

                // get oid
                $parts = explode(" = ", $newrow);
                $theoid = $parts[0];

                array_shift($parts);
                $newrow = implode(" = ", $parts);

                // get type
                $parts = explode(":", $newrow);
                $type = $parts[0];

                array_shift($parts);
                $newrow = implode(":", $parts);

                // get value
                $val = $newrow;

                // make sure we have all the data
                if ($mib == "" || $theoid == "" || $type == "")
                    continue;

                $x++;
                if ($x > $maxresults)
                    break;

                $serviceargs["oid"][] = array(
                    "oid" => $theoid,
                    "type" => $type,
                    "val" => $val,
                    "name" => "",
                    "label" => "",
                    "units" => "",
                    "matchtype" => "",
                    "warning" => "",
                    "critical" => "",
                    "string" => "",
                    "mib" => $mib,
                );
                $services["oid"][] = "";
            }


            $output = '
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="snmpcommunity" value="' . htmlentities($snmpcommunity) . '">
<input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
<input type="hidden" name="oid" value="' . htmlentities($oid) . '">
<input type="hidden" name="timeout" value="' . htmlentities($timeout) . '">
<input type="hidden" name="maxresults" value="' . htmlentities($maxresults) . '">
<input type="hidden" name="forcescan" value="' . htmlentities($forcescan) . '">
<input type="hidden" name="snmpopts_serial" value="' . base64_encode(serialize($snmpopts)) . '">';

            if (!array_key_exists("oid", $serviceargs) || count($serviceargs["oid"]) == 0) {

                $output .= '

<p>'._('No results were returned from a scan of the device. This may be due to the fact that SNMP is not enabled, or you specified incorrect scan settings.').'</p>
<p>'._('You may either').' <a href="javascript: history.go(-1)">'._('change your settings').'</a> '._('or').' <a href="javascript:location.reload(true)" id="retry">'._('try the same scan again').'</a>.</p>

<div id="snmpwalk-throbber" class="hide" style="padding-bottom: 120px; z-index: 10000;">
    <div class="message" style="width: 450px;">
        <ul class="infoMessage">
            <li><b>'._('Scanning device. Please wait...').'</b></li>
        </ul>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $("#configWizardForm").submit(function(e) {
        whiteout();
        $("#snmpwalk-throbber").center().show();
    });
});
</script>';

            } else {

                $output .= '
<h5 class="ul">'._('Device Details').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>'._('Device Address').':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Host Name').':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">'._('The name you would like to have associated with this server or device.').'</div>
        </td>
    </tr>
</table>

<h5 class="ul">'._('SNMP Services').'</h5>
<p>'._('Select the OIDs you\'d like to monitor via SNMP.').'</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <th>'._('Select').'</th>
        <th>OID</th>
        <th>'._('Type').'</th>
        <th>'._('Current Value').'</th>
        <th>'._('Display Name').'</th>
        <th>'._('Data Label').'</th>
        <th>'._('Data Units').'</th>
        <th>'._('Match Type').'</th>
        <th>'._('Warning').'<br>'._('Range').'</th>
        <th>'._('Critical').'<br>'._('Range').'</th>
        <th>'._('String').'<br>'._('To Match').'</th>
        <th>MIB '._('To Use').'</th>
    </tr>';

                $total = count($serviceargs["oid"]);
                for ($x = 0; $x < $total; $x++) {

                    $output .= '<tr>
            <td><input type="checkbox" class="checkbox" name="services[oid][' . $x . ']" ' . is_checked($services["oid"][$x]) . '></td>
            
            <td><input type="text" size="45" name="serviceargs[oid][' . $x . '][oid]" value="' . htmlentities($serviceargs["oid"][$x]["oid"]) . '" class="form-control"></td>

            <td>' . htmlentities($serviceargs["oid"][$x]["type"]) . '<input type="hidden" name="serviceargs[oid][' . $x . '][type]" value="' . htmlentities($serviceargs["oid"][$x]["type"]) . '" /></td>
            <td>' . htmlentities($serviceargs["oid"][$x]["val"]) . '<input type="hidden" name="serviceargs[oid][' . $x . '][val]" value="' . htmlentities($serviceargs["oid"][$x]["val"]) . '" /></td>
            

            <td><input type="text" size="15" name="serviceargs[oid][' . $x . '][name]" value="' . htmlentities($serviceargs["oid"][$x]["name"]) . '" class="form-control"></td>
            <td><input type="text" size="10" name="serviceargs[oid][' . $x . '][label]" value="' . htmlentities($serviceargs["oid"][$x]["label"]) . '" class="form-control"></td>
            <td><input type="text" size="10" name="serviceargs[oid][' . $x . '][units]" value="' . htmlentities($serviceargs["oid"][$x]["units"]) . '" class="form-control"></td>
            
            <td>
                <select name="serviceargs[oid][' . $x . '][matchtype]" class="form-control">
                    <option value="none" ' . is_selected($serviceargs["oid"][$x]["matchtype"], "none") . '>'._('None').'</option>
                    <option value="numeric" ' . is_selected($serviceargs["oid"][$x]["matchtype"], "numeric") . '>'._('Numeric').'</option>
                    <option value="string" ' . is_selected($serviceargs["oid"][$x]["matchtype"], "string") . '>'._('String').'</option>
                </select>
            </td>
            
            <td><input type="text" size="4" name="serviceargs[oid][' . $x . '][warning]" value="' . htmlentities($serviceargs["oid"][$x]["warning"]) . '" class="form-control"></td>
            <td><input type="text" size="4" name="serviceargs[oid][' . $x . '][critical]" value="' . htmlentities($serviceargs["oid"][$x]["critical"]) . '" class="form-control"></td>
            <td><input type="text" size="8" name="serviceargs[oid][' . $x . '][string]" value="' . htmlentities($serviceargs["oid"][$x]["string"]) . '" class="form-control"></td>
            <td><input type="text" size="12" name="serviceargs[oid][' . $x . '][mib]" value="' . htmlentities($serviceargs["oid"][$x]["mib"]) . '" class="form-control"></td>
            </tr>';
                }

                $output .= '
        </table>';
            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = "Invalid host name.";
            if (!array_key_exists("oid", $services) || count($services["oid"]) == 0)
                $errmsg[$errors++] = "You have not selected any OIDs to monitor.";
            else foreach ($services["oid"] as $index => $indexval) {
                // get oid
                $oid = $index;
                // skip empty oids
                if ($oid == "")
                    continue;
                // test match arguments
                switch ($serviceargs["oid"][$index]["matchtype"]) {
                    case "numeric":
                        if ($serviceargs["oid"][$index]["warning"] == "")
                            $errmsg[$errors++] = "Invalid warning numeric range for OID " . htmlentities($oid);
                        if ($serviceargs["oid"][$index]["critical"] == "")
                            $errmsg[$errors++] = "Invalid critical numeric range for OID " . htmlentities($oid);
                        break;
                    case "string":
                        if ($serviceargs["oid"][$index]["string"] == "")
                            $errmsg[$errors++] = "Invalid string match for OID " . htmlentities($oid);
                        break;
                    default:
                        break;
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
            $port = grab_array_var($inargs, "port");
            $hostname = grab_array_var($inargs, "hostname");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity");
            $snmpversion = grab_array_var($inargs, "snmpversion");
            $oid = grab_array_var($inargs, "oid", "");
            $timeout = grab_array_var($inargs, "timeout", 15);
            $maxresults = grab_array_var($inargs, "maxresults", 100);
            $forcescan = grab_array_var($inargs, "forcescan", 0);

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            if ($snmpopts_serial == "")
                $snmpopts = grab_array_var($inargs, "snmpopts");
            else
                $snmpopts = unserialize(base64_decode($snmpopts_serial));

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="snmpcommunity" value="' . htmlentities($snmpcommunity) . '">
        <input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
        <input type="hidden" name="oid" value="' . htmlentities($oid) . '">
        <input type="hidden" name="timeout" value="' . htmlentities($timeout) . '">
        <input type="hidden" name="maxresults" value="' . htmlentities($maxresults) . '">
        <input type="hidden" name="forcescan" value="' . htmlentities($forcescan) . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        <input type="hidden" name="snmpopts_serial" value="' . base64_encode(serialize($snmpopts)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
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
            $hostaddress = $address;

            $port = grab_array_var($inargs, "port", "");

            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");

            $snmpopts_serial = grab_array_var($inargs, "snmpopts_serial", "");
            $snmpopts = unserialize(base64_decode($snmpopts_serial));

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["port"] = $port;
            $meta_arr["address"] = $address;
            $meta_arr["snmpcommunity"] = $snmpcommunity;
            $meta_arr["snmpversion"] = $snmpversion;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            $meta_arr["snmpopts"] = $snmpopts;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_genericnetdevice_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "snmp.png",
                    "statusmap_image" => "snmp.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "oid":

                        $enabledservices = $svcstate;

                        foreach ($enabledservices as $sid => $sstate) {

                            $oid = $serviceargs["oid"][$sid]["oid"];
                            $name = $serviceargs["oid"][$sid]["name"];
                            $label = $serviceargs["oid"][$sid]["label"];
                            $units = $serviceargs["oid"][$sid]["units"];
                            $matchtype = $serviceargs["oid"][$sid]["matchtype"];
                            $warning = $serviceargs["oid"][$sid]["warning"];
                            $critical = $serviceargs["oid"][$sid]["critical"];
                            $string = $serviceargs["oid"][$sid]["string"];
                            $mib = $serviceargs["oid"][$sid]["mib"];

                            $sdesc = $name;

                            $cmdargs = "";
                            // port
                            $cmdargs .= " -p " . $port;
                            // oid
                            if ($oid != "")
                                $cmdargs .= " -o " . $oid;
                            // snmp community
                            if ($snmpcommunity != "" && $snmpversion != "3")
                                $cmdargs .= " -C " . $snmpcommunity;
                            // snmp version
                            if ($snmpversion != "")
                                $cmdargs .= " -P " . $snmpversion;
                            // snmp v3 stuff
                            if ($snmpversion == "3") {

                                $securitylevel = grab_array_var($snmpopts, "v3_security_level");
                                $username = grab_array_var($snmpopts, "v3_username");
                                $authproto = grab_array_var($snmpopts, "v3_auth_proto");
                                $authpassword = grab_array_var($snmpopts, "v3_auth_password");
                                $privacypassword = grab_array_var($snmpopts, "v3_privacy_password");
                                $privproto = grab_array_var($snmpopts, "v3_priv_proto");

                                if ($securitylevel != "")
                                    $cmdargs .= " --seclevel=" . $securitylevel;
                                if ($username != "")
                                    $cmdargs .= " --secname=" . $username;
                                if ($authproto != "")
                                    $cmdargs .= " --authproto=" . $authproto;
                                if ($authpassword != "")
                                    $cmdargs .= " --authpasswd='" . $authpassword . "'";
                                if ($privacypassword != "")
                                    $cmdargs .= " --privpasswd='" . $privacypassword . "'";
                                if ($privproto != "")
                                    $cmdargs .= " -x " . $privproto;
                            }
                            // label
                            if ($label != "")
                                $cmdargs .= " -l \"" . $label . "\"";
                            // units
                            if ($units != "")
                                $cmdargs .= " -u \"" . $units . "\"";
                            // mib
                            if ($mib != "")
                                $cmdargs .= " -m " . $mib;
                            // match type...
                            switch ($matchtype) {
                                case "numeric":
                                    if ($warning != "")
                                        $cmdargs .= " -w " . $warning;
                                    if ($critical != "")
                                        $cmdargs .= " -c " . $critical;
                                    break;
                                case "string":
                                    if ($string != "")
                                        $cmdargs .= " -r \"" . $string . "\"";
                                    break;
                                default:
                                    break;
                            }

                            // make sure we have a service name
                            if ($sdesc == "")
                                $sdesc = $oid;

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $sdesc,
                                "use" => "xiwizard_snmp_service",
                                "check_command" => "check_xi_service_snmp!" . $cmdargs,
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