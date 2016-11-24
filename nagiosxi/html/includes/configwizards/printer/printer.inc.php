<?php
//
// Printer Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

printer_configwizard_init();

function printer_configwizard_init()
{
    $name = "printer";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor an HP JetDirect&reg; compatible network printer."),
        CONFIGWIZARD_DISPLAYTITLE => _("Printer"),
        CONFIGWIZARD_FUNCTION => "printer_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "printer2.png",
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
function printer_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{

    $wizard_name = "printer";

    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");

            $output = '
<div class="sectionTitle">' . _('Printer Information') . '</div>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Printer Address:') . '</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address of the printer you would like to monitor.') . '</div>
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

            $hostname = @gethostbyaddr($address);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $snmpcommunity = "public";
            $snmpcommunity = nagiosccm_replace_user_macros($snmpcommunity);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Printer Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Printer Address:') . '</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this printer.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Printer Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the device.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="p" class="checkbox" id="ping" name="services[ping]" checked>
        </td>
        <td>
            <label class="normal" for="p">
                <b>' . _('Ping') . '</b><br>
                ' . _('Monitors the printer with an ICMP ping.  Useful for watching network latency and general uptime of your printer.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ps" class="checkbox" id="printerstatus" name="services[printerstatus]" checked>
        </td>
        <td>
            <label class="normal" for="ps">
                <b>' . _('Printer Status') . '</b><br>
                ' . _('Monitors the status of the printer through it\'s JetDirect card.  Useful for watching for offline and paper jam issues, etc.') . '
            </label>
            <div class="pad-t5">
                <label for="snmpcommunity">' . _('SNMP Community:') . '</label> <input type="text" size="20" name="serviceargs[snmpcommunity]" id="snmpcommunity" value="' . htmlentities($snmpcommunity) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");

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

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

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
                    "use" => "xiwizard_printer_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "printer2.png",
                    "statusmap_image" => "printer2.png",
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
                            "use" => "xiwizard_printer_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "printerstatus":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Printer Status",
                            "use" => "xiwizard_printer_hpjd_service",
                            "check_command" => "check_xi_service_hpjd!" . $serviceargs["snmpcommunity"],
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    default:
                        break;
                }
            }

            //echo "OBJECTS:<BR>";
            //print_r($objs);

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}