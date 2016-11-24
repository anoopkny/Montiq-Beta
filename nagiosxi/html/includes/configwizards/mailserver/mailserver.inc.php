<?php
//
// Mail Server Config Wizard
// Copyright (c) 2011-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: mailserver.inc.php 663 2011-06-22 23:20:48Z egalstad $

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mailserver_configwizard_init();

function mailserver_configwizard_init()
{
    $name = "mailserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor an email server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Mail Server"),
        CONFIGWIZARD_FUNCTION => "mailserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mailserver.png",
        CONFIGWIZARD_FILTER_GROUPS => array('email'),
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
function mailserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mailserver";

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
<h5 class="ul">' . _('Mail Server Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address of the mail server you\'d like to monitor') . '.</div>
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
            $hn = @gethostbyaddr($address);
            if ($hn == "")
                $hn = $address;
            $hostname = grab_array_var($inargs, "hostname", $hn);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services)) {
                $services_default = array(
                    "ping" => 1,
                    "smtp" => 1,
                    "imap" => 1,
                    "pop" => 1,
                    "rbl" => 1,
                );
                $services = grab_array_var($inargs, "services", $services_default);
            }

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            if (!is_array($serviceargs)) {
                $serviceargs_default = array(
                    "rbl_servers" => "zen.spamhaus.org bl.spamcop.net dnsbl.ahbl.org dnsbl.njabl.org dnsbl.sorbs.net virbl.dnsbl.bit.nl rbl.efnet.org phishing.rbl.msrbl.net 0spam.fusionzero.com list.dsbl.org multihop.dsbl.org unconfirmed.dsbl.org will-spam-for-food.eu.org blacklist.spambag.org blackholes.brainerd.net blackholes.uceb.org spamsources.dnsbl.info map.spam-rbl.com ns1.unsubscore.com psbl.surriel.com l2.spews.dnsbl.sorbs.net bl.csma.biz sbl.csma.biz dynablock.njabl.org no-more-funn.moensted.dk  ubl.unsubscore.com dnsbl-1.uceprotect.net dnsbl-2.uceprotect.net dnsbl-3.uceprotect.net spamguard.leadmon.net opm.blitzed.org bl.spamcannibal.org rbl.schulte.org dnsbl.ahbl.org virbl.dnsbl.bit.nl combined.rbl.msrbl.net",
                );

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Mail Server Details') . '</h5>
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
        <td valign="top">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this mail server') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the mail server') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[ping]"  ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <b>' . _('Ping') . '</b><br>
            ' . _('Monitors the server with an ICMP ping.  Useful for watching network latency and general uptime') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[smtp]"  ' . is_checked(checkbox_binary($services["smtp"]), "1") . '>
        </td>
        <td>
            <b>' . _('SMTP') . '</b><br>
            ' . _('Monitors SMTP service availability') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[imap]"  ' . is_checked(checkbox_binary($services["imap"]), "1") . '>
        </td>
        <td>
            <b>' . _('IMAP') . '</b><br>
            ' . _('Monitors IMAP service availability') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[pop]"  ' . is_checked(checkbox_binary($services["pop"]), "1") . '>
        </td>
        <td>
            <b>' . _('POP') . '</b><br>
            ' . _('Monitors POP service availability') . '.
        </td>
    </tr>

    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[rbl]"  ' . is_checked(checkbox_binary($services["rbl"]), "1") . '><br>
        </td>
        <td>
            <b>' . _('RBL Blacklist Check') . '</b><br>
            ' . _('Checks to see if your mail server is listed on any public RBLs (real time blackhole lists)') . '.
            <div class="pad-t5">
                <label>' . _('Blacklist Servers') . ':</label>
                <input type="text" size="60" name="serviceargs[rbl_servers]" value="' . htmlentities($serviceargs["rbl_servers"]) . '" class="textfield form-control condensed">
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

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            else
                $services = grab_array_var($inargs, "services");

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            else
                $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
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
            $hostaddress = $address;

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
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mailserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "mailserver.png",
                    "statusmap_image" => "mailserver.png",
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
                            "use" => "xiwizard_mailserver_ping_service",
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

                    case "rbl":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Blacklist Status",
                            "use" => "xiwizard_mailserver_service",
                            "check_command" => "check_mailserver_rbl!-B " . $serviceargs["rbl_servers"],
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