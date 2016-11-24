<?php
//
// DNS Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

dnsquery_configwizard_init();

function dnsquery_configwizard_init()
{
    $name = "dnsquery";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a host or domain lookup/query via DNS."),
        CONFIGWIZARD_DISPLAYTITLE => "DNS Query",
        CONFIGWIZARD_FUNCTION => "dnsquery_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "community2.png",
        CONFIGWIZARD_FILTER_GROUPS => array('website','network'),
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
function dnsquery_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "dnsquery";

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
<h5 class="ul">' . _('Domain Name') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('FQDN') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The fully qualified domain name you\'d like to monitor') . '.</div>
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
            if (have_value($address) == false) {
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
            $dnsserver = grab_array_var($inargs, "dnsserver");
            $dnsauthority = grab_array_var($inargs, "dnsauthority");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $ip = gethostbyname($hostname);

            $services_serial = grab_array_var($inargs, "services_serial");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            } else {
                $services = grab_array_var($inargs, "services", array(
                    "dns" => "on",
                    "dnsip" => "on",
                ));
            }
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            } else {
                $serviceargs = grab_array_var($inargs, "serviceargs", array());
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Query Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('FQDN') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="ip" id="ip" value="' . htmlentities($ip) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address associated with the FQDN') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this FQDN') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('DNS Query Options') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('DNS Server') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="dnsserver" id="dnsserver" value="' . htmlentities($dnsserver) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address of the DNS server you\'d like to use for the query (optional)') . '.</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Authoritative Response') . ':</label>
        </td>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="dnsauthority" name="dnsauthority" ' . is_checked($dnsauthority, "on") . '>
                ' . _('Require the DNS server specified above to be authoritative for the query') . '.
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('DNS Query Services') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="dns" name="services[dns]" checked>
        </td>
        <td>
            <label for="dns" style="font-weight: normal;">
                <b>' . _('DNS Resolution') . '</b><br>
                ' . _('Monitors the FQDN to ensure it resolves to a valid IP address') . '.
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="dnsip" name="services[dnsip]" checked>
        </td>
        <td>
            <label for="dnsip" style="font-weight: normal;">
                <b>' . _('DNS IP Match') . '</b><br>
                ' . _('Monitors the FQDN to ensure it resolves to the current known IP address.  Helps ensure your DNS doesn\'t change unexpectedly, which may mean a security breach has occurred') . '.
            </label>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $ip = grab_array_var($inargs, "ip");
            $hostname = grab_array_var($inargs, "hostname");

            $dnsserver = grab_array_var($inargs, "dnsserver");
            $dnsauthority = grab_array_var($inargs, "dnsauthority");

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            /*
            if(array_key_exists("transfer",$services)){
                if($username=="" || $password=="")
                    $errmsg[$errors++]="Username or password is blank.";
                if($port=="")
                    $errmsg[$errors++]="Invalid port number.";
                }
            */

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $ip = grab_array_var($inargs, "ip");
            $hostname = grab_array_var($inargs, "hostname");

            $dnsserver = grab_array_var($inargs, "dnsserver");
            $dnsauthority = grab_array_var($inargs, "dnsauthority");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="ip" value="' . htmlentities($ip) . '">
        <input type="hidden" name="dnsserver" value="' . htmlentities($dnsserver) . '">
        <input type="hidden" name="dnsauthority" value="' . htmlentities($dnsauthority) . '">
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
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $ip = grab_array_var($inargs, "ip", "");
            $address = grab_array_var($inargs, "address", "");

            // this is different than normal
            $hostaddress = $ip;

            $dnsserver = grab_array_var($inargs, "dnsserver");
            $dnsauthority = grab_array_var($inargs, "dnsauthority");

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
            $meta_arr["ip"] = $ip;
            $meta_arr["dnsserver"] = $dnsserver;
            $meta_arr["dnsauthority"] = $dnsauthority;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_dnsquery_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "server.png",
                    "statusmap_image" => "server.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $pluginopts = "";
            if ($dnsserver != "") {
                $pluginopts .= " -s " . $dnsserver;
                if ($dnsauthority != "")
                    $pluginopts .= " -A";
            }

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "dns":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "DNS Resolution - " . $address,
                            "use" => "xiwizard_dnsquery_service",
                            "check_command" => "check_xi_service_dnsquery!-H " . $address . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "dnsip":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "DNS IP Match - " . $address,
                            "use" => "xiwizard_dnsquery_service",
                            "check_command" => "check_xi_service_dnsquery!-H " . $address . " -a " . $ip . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
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


?>