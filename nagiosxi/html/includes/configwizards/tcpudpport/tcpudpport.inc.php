<?php
//
// TCP/UDP Port Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

tcpudpport_configwizard_init();

function tcpudpport_configwizard_init()
{
    $name = "tcpudpport";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor common and custom TCP/UDP ports."),
        CONFIGWIZARD_DISPLAYTITLE => _("TCP/UDP Port"),
        CONFIGWIZARD_FUNCTION => "tcpudpport_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "serverport.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @param string $mode
 * @param        $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function tcpudpport_configwizard_func($mode = "", $inargs, &$outargs, &$result)
{
    $wizard_name = "tcpudpport";

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
<h5 class="ul">' . _('Server Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Server Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or fully qualified DNS name of the server or device you\'d like to monitor TCP/UDP ports on.') . '</div>
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

            $services = grab_array_var($inargs, "services", array());
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }

            // Fill in missing services variables
            if (!array_key_exists("common", $services))
                $services["common"] = array();
            if (!array_key_exists("ftp", $services["common"]))
                $services["common"]["ftp"] = "";
            if (!array_key_exists("http", $services["common"]))
                $services["common"]["http"] = "";
            if (!array_key_exists("imap", $services["common"]))
                $services["common"]["imap"] = "";
            if (!array_key_exists("pop", $services["common"]))
                $services["common"]["pop"] = "";
            if (!array_key_exists("smtp", $services["common"]))
                $services["common"]["smtp"] = "";
            if (!array_key_exists("ssh", $services["common"]))
                $services["common"]["ssh"] = "";

            // Custom ports
            if (!array_key_exists("custom", $services))
                $services["custom"] = array();
            for ($x = 0; $x < 4; $x++) {
                if (!array_key_exists($x, $services["custom"]))
                    $services["custom"][$x] = array();
                if (!array_key_exists("port", $services["custom"][$x]))
                    $services["custom"][$x]["port"] = "";
                if (!array_key_exists("type", $services["custom"][$x]))
                    $services["custom"][$x]["type"] = "";
                if (!array_key_exists("name", $services["custom"][$x]))
                    $services["custom"][$x]["name"] = "";
                if (!array_key_exists("send", $services["custom"][$x]))
                    $services["custom"][$x]["send"] = "";
                if (!array_key_exists("expect", $services["custom"][$x]))
                    $services["custom"][$x]["expect"] = "";
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Server Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Server Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this server or device.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Common Server Ports') . '</h5>
<p>' . _('Specify which ports you\'d like to monitor on the server or device.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="ftp" name="services[common][ftp]" ' . is_checked($services["common"]["ftp"], "on") . '>
        </td>
        <td>
            <label for="ftp">' . _('FTP') . '</label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="http" name="services[common][http]" ' . is_checked($services["common"]["http"], "on") . '>
        </td>
        <td>
            <label for="http">' . _('HTTP') . '</label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="imap" name="services[common][imap]" ' . is_checked($services["common"]["imap"], "on") . '>
        </td>
        <td>
            <label for="imap">' . _('IMAP') . '</label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="pop" name="services[common][pop]" ' . is_checked($services["common"]["pop"], "on") . '>
        </td>
        <td>
            <label for="pop">' . _('POP') . '</label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="smtp" name="services[common][smtp]" ' . is_checked($services["common"]["smtp"], "on") . '>
        </td>
        <td>
            <label for="smtp">' . _('SMTP') . '</label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="ssh" name="services[common][ssh]" ' . is_checked($services["common"]["ssh"], "on") . '>
        </td>
        <td>
            <label for="ssh">' . _('SSH') . '</label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Custom Server Ports') . '</h5>
<p>' . _('Specify any custom TCP/UDP ports you\'d like to monitor on the server or device.') . '</p>
<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <th>' . _('Port Number') . '</th>
        <th>' . _('Port Type') . '</th>
        <th>' . _('Port/Application Name') . '</th>
        <th>' . _('Send String') . '</th>
        <th>' . _('Expect String') . '</th>
    </tr>';

            for ($x = 0; $x < count($services["custom"]); $x++) {

                $output .= '<tr>';

                $output .= '<td><input type="text" size="5" name="services[custom][' . $x . '][port]" id="custom_port_' . $x . '" value="' . htmlentities($services["custom"][$x]["port"]) . '" class="form-control"></td>';

                $output .= '<td><select name="services[custom][' . $x . '][type]" class="form-control"><option value="tcp" ' . is_selected($services["custom"][$x]["type"], "tcp") . '>' . _('TCP') . '</option><option value="udp" ' . is_selected($services["custom"][$x]["type"], "udp") . '>' . _('UDP') . '</option></select></td>';

                $output .= '<td><input type="text" size="20" name="services[custom][' . $x . '][name]" id="custom_name_' . $x . '" value="' . htmlentities($services["custom"][$x]["name"]) . '" class="form-control"></td>';

                $output .= '<td><input type="text" size="20" name="services[custom][' . $x . '][send]" id="custom_send_' . $x . '" value="' . htmlentities($services["custom"][$x]["send"]) . '" class="form-control"></td>';

                $output .= '<td><input type="text" size="20" name="services[custom][' . $x . '][expect]" id="custom_expect_' . $x . '" value="' . htmlentities($services["custom"][$x]["expect"]) . '" class="form-control"></td>';

                $output .= '</tr>';

            }

            $output .= '
    </table>
    <div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $services = grab_array_var($inargs, "services");

            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            foreach ($services["custom"] as $id => $portarr) {

                $port = grab_array_var($portarr, "port", "");

                if ($port == "")
                    continue;

                if (!is_numeric($port))
                    $errmsg[$errors++] = _("Invalid port number: ") . htmlentities($port);

                $name = grab_array_var($portarr, "name", "");
                if ($name != "") {
                    if (!is_valid_service_name($name))
                        $errmsg[$errors++] = _("Invalid port/application name for port ") . htmlentities($port);
                }

                $send = grab_array_var($portarr, "send", "");
                if ($send != "") {
                    if (strstr($send, "\""))
                        $errmsg[$errors++] = _("Send string for port ") . htmlentities($port) . _(" may not contain quotes");
                }

                $expect = grab_array_var($portarr, "expect", "");
                if ($expect != "") {
                    if (strstr($expect, "\""))
                        $errmsg[$errors++] = _("Expect string for port ") . htmlentities($port) . _(" may not contain quotes");
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
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="services_serial" value="' . $services_serial . '">
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">
        
        <!--
        SERVICES2=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>
        //-->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $output = '
            
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname", "");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $hostaddress = $address;

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
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
                    "icon_image" => "server2.png",
                    "statusmap_image" => "server2.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            if (!array_key_exists("common", $services))
                $services["common"] = array();
            if (!array_key_exists("custom", $services))
                $services["custom"] = array();

            // see which common ports we should monitor
            foreach ($services["common"] as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "ftp":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "FTP",
                            "use" => "xiwizard_ftp_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "http":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "HTTP",
                            "use" => "xiwizard_website_http_service",
                            "check_command" => "check_xi_service_http",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "imap":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "IMAP",
                            "use" => "xiwizard_imap_service",
                            "check_command" => "check_xi_service_imap!-j",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pop":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "POP",
                            "use" => "xiwizard_pop_service",
                            "check_command" => "check_xi_service_pop!-j",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "smtp":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "SMTP",
                            "use" => "xiwizard_smtp_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "ssh":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "SSH",
                            "use" => "xiwizard_ssh_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    default:
                        break;
                }
            }

            // see which common ports we should monitor
            foreach ($services["custom"] as $id => $portarr) {

                $port = grab_array_var($portarr, "port", "");
                $type = grab_array_var($portarr, "type", "tcp");
                $name = grab_array_var($portarr, "name", "");
                $send = grab_array_var($portarr, "send", "");
                $expect = grab_array_var($portarr, "expect", "");

                if ($port == "")
                    continue;

                //echo "PROCESSING: $id -> ".serialize($portarr)."<BR>\n";

                $svc_description = $name;
                if ($svc_description == "") {
                    if ($type == "udp")
                        $svc_description .= _("UDP");
                    else
                        $svc_description .= _("TCP");
                    $svc_description .= _(" Port ") . $port;
                }

                if ($type == "udp") {
                    $use = "xiwizard_udp_service";
                    $check_command = "check_xi_service_udp!-p " . $port;
                } else {
                    $use = "xiwizard_tcp_service";
                    $check_command = "check_xi_service_tcp!-p " . $port;
                }
                // optional send/expect strings
                if ($send != "")
                    $check_command .= " -s \"" . $send . "\"";
                if ($expect != "")
                    $check_command .= " -e \"" . $expect . "\"";

                $objs[] = array(
                    "type" => OBJECTTYPE_SERVICE,
                    "host_name" => $hostname,
                    "service_description" => $svc_description,
                    "use" => $use,
                    "check_command" => $check_command,
                    "_xiwizard" => $wizard_name,
                );

            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}