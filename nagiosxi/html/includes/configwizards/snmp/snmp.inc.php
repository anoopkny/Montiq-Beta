<?php
//
// SNMP Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

snmp_configwizard_init();

function snmp_configwizard_init()
{
    $name = "snmp";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.5",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a device, service, or application using SNMP."),
        CONFIGWIZARD_DISPLAYTITLE => "SNMP",
        CONFIGWIZARD_FUNCTION => "snmp_configwizard_func",
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
function snmp_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "snmp";

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
<h5 class="ul">' . _('SNMP Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Device Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or fully qualified DNS name of the server or device you\'d like to monitor.') . '</div>
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
            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "public");
            $snmpcommunity = nagiosccm_replace_user_macros($snmpcommunity);

            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");
            $services = "";
            $serviceargs = "";

            // Use encoded data (if user came back from future screen)
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            // Use current request data if available
            if ($services == "")
                $services = grab_array_var($inargs, "services", array());
            if ($serviceargs == "")
                $serviceargs = grab_array_var($inargs, "serviceargs", array());
            if (!array_key_exists("v3_security_level", $serviceargs)) $serviceargs["v3_security_level"] = "authPriv";
            if (!array_key_exists("v3_username", $serviceargs)) $serviceargs["v3_username"] = "";
            if (!array_key_exists("v3_privacy_password", $serviceargs)) $serviceargs["v3_privacy_password"] = "";
            if (!array_key_exists("v3_auth_password", $serviceargs)) $serviceargs["v3_auth_password"] = "";
            if (!array_key_exists("v3_auth_proto", $serviceargs)) $serviceargs["v3_auth_proto"] = "md5";
            if (!array_key_exists("v3_priv_proto", $serviceargs)) $serviceargs["v3_priv_proto"] = "des";

            // Initialize or fill in missing array variables
            if (!array_key_exists("oid", $services))
                $services["oid"] = array();
            if (!array_key_exists("oid", $serviceargs))
                $serviceargs["oid"] = array();
            for ($x = 0; $x < 6; $x++) {
                if (!array_key_exists($x, $services["oid"]))
                    $services["oid"][$x] = "";
                if (!array_key_exists($x, $serviceargs["oid"])) {

                    $oid = "";
                    $name = "";
                    $label = "";
                    $units = "";
                    $matchtype = "";
                    $warning = "";
                    $critical = "";
                    $string = "";
                    $mib = "";

                    if ($x == 0) {
                        $oid = "sysUpTime.0";
                        $name = "Uptime";
                        $matchtype = "none";
                    }
                    if ($x == 1) {
                        $oid = "ifOperStatus.1";
                        $name = "Port 1 Status";
                        $string = "1";
                        $matchtype = "string";
                        $mib = "RFC1213-MIB";
                    }
                    if ($x == 2) {
                        $oid = ".1.3.6.1.4.1.2.3.51.1.2.1.5.1.0";
                        $name = "IBM RSA II Adapter Temperature";
                        $label = "Ambient Temp";
                        $units = "Deg. Celsius";
                        $matchtype = "numeric";
                        $warning = "29";
                        $critical = "35";
                    }
                    if ($x == 3) {
                        $oid = "1.3.6.1.4.1.3076.2.1.2.17.1.7.0,1.3.6.1.4.1.3076.2.1.2.17.1.9.0";
                        $name = "Cisco VPN Sessions";
                        $label = "Active Sessions";
                        $matchtype = "numeric";
                        $warning = ":70,:8";
                        $critical = ":75,:10";
                    }

                    $serviceargs["oid"][$x] = array(
                        "oid" => $oid,
                        "name" => $name,
                        "label" => $label,
                        "units" => $units,
                        "matchtype" => $matchtype,
                        "warning" => $warning,
                        "critical" => $critical,
                        "string" => $string,
                        "mib" => $mib,
                    );
                }
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

    $("[name=\'serviceargs[v3_security_level]\']").change(function() {
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
        var level_val = $("[name=\'serviceargs[v3_security_level]\']").val();

        if (level_val == "noAuthNoPriv") {
            $("[name=\'serviceargs[v3_auth_proto]\']").val("");
            $("[name=\'serviceargs[v3_priv_proto]\']").val("");
        } else if (level_val == "authNoPriv") {
            $("[name=\'serviceargs[v3_auth_proto]\']").val("md5");
            $("[name=\'serviceargs[v3_priv_proto]\']").val("");
        } else if (level_val == "authPriv") {
            $("[name=\'serviceargs[v3_auth_proto]\']").val("md5");
            $("[name=\'serviceargs[v3_priv_proto]\']").val("des");
        }
    }
});
</script>

<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Device Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Device Address') . ':</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this server or device.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('SNMP Settings') . '</h5>
<p>' . _('Specify the settings used to monitor the server or device via SNMP.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('SNMP Community') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="snmpcommunity" id="snmpcommunity" value="' . htmlentities($snmpcommunity) . '" class="form-control">
            <div class="subtext">' . _('The SNMP community string required used to to query the device.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('SNMP Version') . ':</label>
        </td>
        <td>
            <select name="snmpversion" id="snmpversion" class="form-control">
                <option value="1" ' . is_selected($snmpversion, "1") . '>1</option>
                <option value="2c" ' . is_selected($snmpversion, "2c") . '>2c</option>
                <option value="3" ' . is_selected($snmpversion, "3") . '>3</option>
            </select>
            <div class="subtext">' . _('The SNMP protocol version used to commicate with the device.') . '</div>
        </td>
    </tr>
</table>

<div id="auth" class="hide">
    <h5 class="ul">' . _('SNMP Authentication') . '</h5>
    <p>' . _('When using SNMP v3 you must specify authentication information.') . '</p>
    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td>
                <label>' . _('Security Level') . ':</label>
            </td>
            <td>
                <select name="serviceargs[v3_security_level]" class="form-control">
                    <option value="noAuthNoPriv" ' . is_selected($serviceargs["v3_security_level"], "noAuthNoPriv") . '>noAuthNoPriv</option>
                    <option value="authNoPriv" ' . is_selected($serviceargs["v3_security_level"], "authNoPriv") . '>authNoPriv</option>
                    <option value="authPriv" ' . is_selected($serviceargs["v3_security_level"], "authPriv") . '>authPriv</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Username') . ':</label>
            </td>
            <td>
                <input type="text" size="20" name="serviceargs[v3_username]" value="' . htmlentities($serviceargs["v3_username"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Privacy Password') . ':</label>
            </td>
            <td>
                <input type="text" size="20" name="serviceargs[v3_privacy_password]" value="' . htmlentities($serviceargs["v3_privacy_password"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Authentication Password') . ':</label>
            </td>
            <td>
                <input type="texs" size="20" name="serviceargs[v3_auth_password]" value="' . htmlentities($serviceargs["v3_auth_password"]) . '" class="form-control">
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Authentication Protocol') . ':</label>
            </td>
            <td>
                <select name="serviceargs[v3_auth_proto]" class="form-control">
                    <option value="" ' . is_selected($serviceargs["v3_auth_proto"], "") . '>None</option>
                    <option value="md5" ' . is_selected($serviceargs["v3_auth_proto"], "md5") . '>MD5</option>
                    <option value="sha" ' . is_selected($serviceargs["v3_auth_proto"], "sha") . '>SHA</option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label>' . _('Privileged Protocol') . ':</label>
            </td>
            <td>
                <select name="serviceargs[v3_priv_proto]" class="form-control">
                    <option value="" ' . is_selected($serviceargs["v3_priv_proto"], "") . '>None</option>
                    <option value="des" ' . is_selected($serviceargs["v3_priv_proto"], "des") . '>DES</option>
                    <option value="aes" ' . is_selected($serviceargs["v3_priv_proto"], "aes") . '>AES</option>
                </select>
            </td>
        </tr>
    </table>
</div>
    
<h5 class="ul">' . _('SNMP Services') . '</h5>
<p>' . _('Specify any OIDs you\'d like to monitor via SNMP.  Sample entries have been provided as examples.') . '</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th></th>
        <th>' . _('OID') . '</th>
        <th>' . _('Display Name') . '</th>
        <th>' . _('Data Label') . '</th>
        <th>' . _('Data Units') . '</th>
        <th>' . _('Match Type') . '</th>
        <th>' . _('Warning') . '<br>' . _('Range') . '</th>
        <th>Critical<br>' . _('Range') . '</th>
        <th>' . _('String') . '<br>' . _('To Match') . '</th>
        <th>' . _('MIB To Use') . '</th>
    </tr>';

            for ($x = 0; $x < count($serviceargs["oid"]); $x++) {

                $output .= '<tr>
        <td><input type="checkbox" class="checkbox" name="services[oid][' . $x . ']" ' . is_checked($services["oid"][$x], "on") . '></td>

        <td><input type="text" size="20" name="serviceargs[oid][' . $x . '][oid]" value="' . htmlentities($serviceargs["oid"][$x]["oid"]) . '" class="form-control"></td>
        <td><input type="text" size="20" name="serviceargs[oid][' . $x . '][name]" value="' . htmlentities($serviceargs["oid"][$x]["name"]) . '" class="form-control"></td>
        <td><input type="text" size="10" name="serviceargs[oid][' . $x . '][label]" value="' . htmlentities($serviceargs["oid"][$x]["label"]) . '" class="form-control"></td>
        <td><input type="text" size="10" name="serviceargs[oid][' . $x . '][units]" value="' . htmlentities($serviceargs["oid"][$x]["units"]) . '" class="form-control"></td>
        
        <td>
            <select name="serviceargs[oid][' . $x . '][matchtype]" class="form-control">
                <option value="none" ' . is_selected($serviceargs["oid"][$x]["matchtype"], "none") . '>' . _('None') . '</option>
                <option value="numeric" ' . is_selected($serviceargs["oid"][$x]["matchtype"], "numeric") . '>' . _('Numeric') . '</option>
                <option value="string" ' . is_selected($serviceargs["oid"][$x]["matchtype"], "string") . '>' . _('String') . '</option>
            </select>
        </td>
        
        <td><input type="text" size="4" name="serviceargs[oid][' . $x . '][warning]" value="' . htmlentities($serviceargs["oid"][$x]["warning"]) . '" class="form-control"></td>
        <td><input type="text" size="4" name="serviceargs[oid][' . $x . '][critical]" value="' . htmlentities($serviceargs["oid"][$x]["critical"]) . '" class="form-control"></td>
        <td><input type="text" size="8" name="serviceargs[oid][' . $x . '][string]" value="' . htmlentities($serviceargs["oid"][$x]["string"]) . '" class="form-control"></td>
        <td><input type="text" size="12" name="serviceargs[oid][' . $x . '][mib]" value="' . htmlentities($serviceargs["oid"][$x]["mib"]) . '" class="form-control"></td>
        </tr>';
            }

            $output .= '
    </table>
    <div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            //print_r($inargs);

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $services = "";
            $serviceargs = "";

            // use encoded data (if user came back from future screen)
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
            // use current request data if available
            if ($services == "")
                $services = grab_array_var($inargs, "services", array());
            if ($serviceargs == "")
                $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (!array_key_exists("oid", $services) || count($services["oid"]) == 0)
                $errmsg[$errors++] = _("You have not selected any OIDs to monitor.");
            else foreach ($services["oid"] as $index => $indexval) {
                // get oid
                $oid = $serviceargs["oid"][$index]["oid"];
                // skip empty oids
                if ($oid == "")
                    continue;
                // test match arguments
                switch ($serviceargs["oid"][$index]["matchtype"]) {
                    case "numeric":
                        if ($serviceargs["oid"][$index]["warning"] == "")
                            $errmsg[$errors++] = _("Invalid warning numeric range for OID ") . htmlentities($oid);
                        if ($serviceargs["oid"][$index]["critical"] == "")
                            $errmsg[$errors++] = _("Invalid critical numeric range for OID ") . htmlentities($oid);
                        break;
                    case "string":
                        if ($serviceargs["oid"][$index]["string"] == "")
                            $errmsg[$errors++] = _("Invalid string match for OID ") . htmlentities($oid);
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
            $hostname = grab_array_var($inargs, "hostname");
            $snmpcommunity = grab_array_var($inargs, "snmpcommunity");
            $snmpversion = grab_array_var($inargs, "snmpversion");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="snmpcommunity" value="' . htmlentities($snmpcommunity) . '">
        <input type="hidden" name="snmpversion" value="' . htmlentities($snmpversion) . '">
        <input type="hidden" name="services_serial" value="' . $services_serial . '">
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">
        
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

            $snmpcommunity = grab_array_var($inargs, "snmpcommunity", "");
            $snmpversion = grab_array_var($inargs, "snmpversion", "2c");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

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

                                $securitylevel = grab_array_var($serviceargs, "v3_security_level");
                                $username = grab_array_var($serviceargs, "v3_username");
                                $authproto = grab_array_var($serviceargs, "v3_auth_proto");
                                $privproto = grab_array_var($serviceargs, "v3_priv_proto");
                                $authpassword = grab_array_var($serviceargs, "v3_auth_password");
                                $privacypassword = grab_array_var($serviceargs, "v3_privacy_password");

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
                                    $cmdargs .= " --privproto=" . $privproto;
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
