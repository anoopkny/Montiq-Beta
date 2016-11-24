<?php
//
// Microsoft Exchange Config Wizard
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

exchange_configwizard_init();

function exchange_configwizard_init()
{
    $name = "exchange";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.0",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Microsoft&reg; Exchange server."),
        CONFIGWIZARD_DISPLAYTITLE => "Exchange Server",
        CONFIGWIZARD_FUNCTION => "exchange_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "exchange2010.png",
        CONFIGWIZARD_FILTER_GROUPS => array('windows','email'),
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
function exchange_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "exchange";
    $agent_url = "http://www.nsclient.org/download/";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $version = grab_array_var($inargs, "version", "2016");

            $output = '
<h5 class="ul">' . _("Exchange Server Information") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address of the Exchange server you\'d like to monitor') . '.</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Server Version') . ':</label>
        </td>
        <td>
            <select name="version" class="form-control">
                <option value="5.5" ' . is_selected($version, "5.5") . '>Exchange 5.5</option>
                <option value="2000" ' . is_selected($version, "2000") . '>Exchange 2000</option>
                <option value="2003" ' . is_selected($version, "2003") . '>Exchange 2003</option>
                <option value="2007" ' . is_selected($version, "2007") . '>Exchange 2007</option>
                <option value="2010" ' . is_selected($version, "2010") . '>Exchange 2010</option>
                <option value="2013" ' . is_selected($version, "2013") . '>Exchange 2013</option>
                <option value="2016" ' . is_selected($version, "2016") . '>Exchange 2016</option>
            </select>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $version = grab_array_var($inargs, "version", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _('No address specified.');
            else if (!valid_ip($address))
                $errmsg[$errors++] = _('Invalid IP address.');
            if (have_value($version) == false)
                $errmsg[$errors++] = _('No server version specified.');

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $version = grab_array_var($inargs, "version", "");

            $hn = @gethostbyaddr($address);
            if ($hn == "")
                $hn = $address;
            $hostname = grab_array_var($inargs, "hostname", $hn);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $password = grab_array_var($inargs, "password", "");
            $password = nagiosccm_replace_user_macros($password);

            $url = grab_array_var($inargs, "url", "http://" . $hostname . "/exchange/");
            $url = nagiosccm_replace_user_macros($url);

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
                    "owa_http" => 1,
                    "owa_https" => 0,
                    "core_services" => 1,
                    "web_services" => 1,
                    "pending_routing" => 1,
                    "remote_queue_length" => 1,
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
                    "core_service_names" => "",
                    "web_service_names" => "W3SVC",
                    "pending_routing_warning" => 25,
                    "pending_routing_critical" => 100,
                    "remote_queue_length_warning" => 25,
                    "remote_queue_length_critical" => 50,
                );
                switch ($version) {
                    case "2016":
                        // assumed for 2016
                        $serviceargs_default["core_service_names"] = "MSExchangeADTopology,MSExchangeAntispamUpdate,MSComplianceAudit,MSExchangeCompliance,MSExchangeDagMgmt,MSExchangeDiagnostics,MSExchangeEdgeSync,MSExchangeFastSearch,MSExchangeFrontEndTransport,MSExchangeHM,MSExchangeIS,MSExchangeMailboxAssistants,MSExchangeMailboxReplication,MSExchangeDelivery,MSExchangeRepl,MSExchangeRPC,MSExchangeServiceHost,MSExchangeThrottling,MSExchangeTransport,MSExchangeTransportLogSearch,MSExchangeUM,MSExchangeUMCR";
                            // These are manual services in 2016 and cause a CRITICAL return if included: MSExchangeImap4,MSExchangeIMAP4BE,MSExchangePop3,MSExchangePOP3BE
                        break;
                    case "2013":
                        // assumed for 2013 - nagioswiki.com
                        $serviceargs_default["core_service_names"] = "MSExchangeADTopology,MSExchangeAntispamUpdate,MSExchangeDagMgmt,MSExchangeDelivery,MSExchangeDiagnostics,MSExchangeEdgeSync,MSExchangeFastSearch,MSExchangeFrontEndTransport,MSExchangeHM,MSExchangeImap4,MSExchangeIMAP4BE,MSExchangeIS,MSExchangeMailboxAssistants,MSExchangeMailboxReplication,MSExchangePop3,MSExchangePOP3BE,MSExchangeRepl,MSExchangeRPC,MSExchangeServiceHost,MSExchangeSubmission,MSExchangeThrottling,MSExchangeTransport,MSExchangeTransportLogSearch,MSExchangeUM,MSExchangeUMCR";
                        break;
                    case "2010":
                        // assumed for 2010 - nagioswiki.com
                        $serviceargs_default["core_service_names"] = "MSExchangeADTopology,MSExchangeAntispamUpdate,MSExchangeEdgeSync,MSExchangeFDS,MSExchangeImap4,MSExchangeIS,MSExchangeMailboxAssistants,MSExchangeMailSubmission,MSExchangeMonangePop3,MSExchangeRepl,MSExchangeSA,MSExchangeSearch,MSExchangeServiceHost,MSExchangeTransport,MSExchangeTransportLogSearch,msftesql-Exchange";
                        break;
                    case "2007":
                        // known for 2007 - nagioswiki.com
                        $serviceargs_default["core_service_names"] = "MSExchangeADTopology,MSExchangeAntispamUpdate,MSExchangeEdgeSync,MSExchangeFDS,MSExchangeImap4,MSExchangeIS,MSExchangeMailboxAssistants,MSExchangeMailSubmission,MSExchangeMonangePop3,MSExchangeRepl,MSExchangeSA,MSExchangeSearch,MSExchangeServiceHost,MSExchangeTransport,MSExchangeTransportLogSearch,msftesql-Exchange";
                        break;
                    case "2003":
                        // assumed for 2003 - nagioswiki.com
                        $serviceargs_default["core_service_names"] = "MSExchangeIS,MSExchangeMTA,SMTPSVC,RESvc";
                    case "2000":
                        // known for 2000 - nagioswiki.com
                        $serviceargs_default["core_service_names"] = "MSExchangeIS,MSExchangeMTA,SMTPSVC,RESvc";
                        break;
                    case "5.5":
                    default:
                        // known for 5.5 - Nagios Exchange (http://exchange.nagios.org/directory/Plugins/Email-and-Groupware/Microsoft-Exchange/Check-MS-Exchange-Server-Health/details)
                        $serviceargs_default["core_service_names"] = "MSExchangeDS,MSExchangeES,MSExchangeIMC,MSExchangeIS,MSExchangeMTA,MSExchangeSA ";
                        break;
                }

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }


            $output = '     
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="version" value="' . htmlentities($version) . '">

<h5 class="ul">' . _('Exchange Server Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . encode_form_val($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Server Version') . ':</label>
        </td>
            <td><b>Exchange ' . encode_form_val($version) . '</b><br>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . encode_form_val($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this Exchange server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('URL') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . encode_form_val($url) . '" class="textfield form-control">
            <div class="subtext">' . _('The URL used to access OWA on the Exchange server') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Windows Agent') . '</h5>
<p>' . _("You'll need to install the Nagios Windows agent on the Exchange server in order to monitor anything other than basic services.  For security purposes, it is recommended to use a password with the agent") . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Instructions') . ':</label>
        </td>
        <td>
            <a href="https://assets.nagios.com/downloads/nagiosxi/docs/Installing_The_XI_Windows_Agent.pdf" target="_blank"><b>' . _('Agent Installation Instructions') . '</b></a>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Downloads') . ':</label>
        </td>
        <td>
            <a href="' . $agent_url . '"></a> <a href="' . $agent_url . '" target="_blank"><b>' . _('Agent Downloads Site') . '<b></a>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Agent Password') . ':</label>
        </td>
        <td>
            <input type="text" size="10" name="password" id="password" value="' . encode_form_val($password) . '" class="textfield form-control">
            <div class="subtext">' . _("Valid characters include:") . ' <b>a-zA-Z0-9 .\:_-</b></div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Basic Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the Exchange server') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[ping]" ' . is_checked(checkbox_binary($services["ping"]), "1") . '>
        </td>
        <td>
            <b>' . _('Ping') . '</b><br>
            ' . _('Monitors the server with an ICMP ping.  Useful for watching network latency and general uptime') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[smtp]" ' . is_checked(checkbox_binary($services["smtp"]), "1") . '>
        </td>
        <td>
            <b>' . _('SMTP') . '</b><br>
            ' . _('Monitors SMTP service availability') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[imap]" ' . is_checked(checkbox_binary($services["imap"]), "1") . '>
        </td>
        <td>
            <b>' . _('IMAP') . '</b><br>
            ' . _('Monitors IMAP service availability') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[pop]" ' . is_checked(checkbox_binary($services["pop"]), "1") . '>
        </td>
        <td>
            <b>' . _('POP') . '</b><br>
            ' . _('Monitors POP service availability') . '.
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[rbl]" ' . is_checked(checkbox_binary($services["rbl"]), "1") . '>
        </td>
        <td>
            <b>' . _('RBL Blacklist Check') . '</b><br>
            ' . _('Checks to see if your mail server is listed on any public RBLs (real time blackhole lists)') . '.<br>
            <div class="pad-t5">
                <label>' . _('Blacklist Servers') . ':</label>
                <input type="text" size="60" name="serviceargs[rbl_servers]" value="' . encode_form_val($serviceargs["rbl_servers"]) . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[owa_http]" ' . is_checked(checkbox_binary($services["owa_http"]), "1") . '>
        </td>
        <td>
            <b>' . _('OWA HTTP') . '</b><br>
            ' . _('Monitors the availability of Outlook Web Access over HTTP') . '.
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[owa_https]" ' . is_checked(checkbox_binary($services["owa_https"]), "1") . '>
        </td>
        <td>
            <b>' . _('OWA HTTPS') . '</b><br>
            ' . _('Monitors the availability of Outlook Web Access over HTTPS (secured with SSL)') . '.
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Exchange Services') . '</h5>
<p>' . _('Specify which Exchange services you\'d like to monitor (these require installation of the Windows agent)') . '.<br><b>' . _('You must have the listed core service classes available for the wizard services to run correctly.') . '</b></p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[core_services]" ' . is_checked(checkbox_binary($services["core_services"]), "1") . '>
        </td>
        <td>
            <b>' . _('Core Services') . '</b><br>
            ' . _('Checks to make sure core services (specified below) that are essential to Exchange are running') . '.
            <div class="pad-t5">
                <label>' . _('Services') . ':</label>
                <input type="text" size="40" name="serviceargs[core_service_names]" value="' . encode_form_val($serviceargs["core_service_names"]) . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[web_services]" '.is_checked(checkbox_binary($services["web_services"]), "1").'>
        </td>
        <td>
            <b>' . _('Web Services') . '</b><br>
            ' . _('Checks to make sure web services (specified below) that are essential to Exchange are running') . '.
            <div class="pad-t5">
                <label>' . _('Services') . ':</label>
                <input type="text" size="10" name="serviceargs[web_service_names]" value="'.encode_form_val($serviceargs["web_service_names"]).'" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Exchange Metrics') . '</h5>
<p>' . _('Specify which metrics you\'d like to monitor on the Exchange server (these require installation of the Windows agent)') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[pending_routing]"  ' . is_checked(checkbox_binary($services["pending_routing"]), "1") . '>
        </td>
        <td>
            <b>' . _('Messages Pending Routing') . '</b>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>  <input type="text" size="3" name="serviceargs[pending_routing_warning]" value="' . encode_form_val($serviceargs["pending_routing_warning"]) . '" class="textfield form-control condensed"> &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[pending_routing_critical]" value="' . encode_form_val($serviceargs["pending_routing_critical"]) . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" name="services[remote_queue_length]"  ' . is_checked(checkbox_binary($services["remote_queue_length"]), "1") . '>
        </td>
        <td>
            <b>' . _('Remote Queue Length') . '</b>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>  <input type="text" size="3" name="serviceargs[remote_queue_length_warning]" value="' . encode_form_val($serviceargs["remote_queue_length_warning"]) . '" class="textfield form-control condensed"> &nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[remote_queue_length_critical]" value="' . encode_form_val($serviceargs["remote_queue_length_critical"]) . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $version = grab_array_var($inargs, "version", "");
            $hostname = grab_array_var($inargs, "hostname");
            $password = grab_array_var($inargs, "password");
            $url = grab_array_var($inargs, "url", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = "Invalid host name.";
            if (preg_match('/[^a-zA-Z0-9 .\:_-]/', $password))
                $errmsg[$errors++] = _("Password contains invalid characters.");
            if (valid_url($url) == false)
                $errmsg[$errors++] = _("Invalid URL.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $version = grab_array_var($inargs, "version", "");
            $hostname = grab_array_var($inargs, "hostname");
            $password = grab_array_var($inargs, "password");
            $url = grab_array_var($inargs, "url", "");

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
        <input type="hidden" name="version" value="' . htmlentities($version) . '">
        <input type="hidden" name="url" value="' . htmlentities($url) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
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
            $version = grab_array_var($inargs, "version", "");
            $address = grab_array_var($inargs, "address", "");
            $password = grab_array_var($inargs, "password", "");
            $hostaddress = $address;
            $url = grab_array_var($inargs, "url", "");

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
            $meta_arr["password"] = $password;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_exchange_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "exchange2010.png",
                    "statusmap_image" => "exchange2010.png",
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
                            "use" => "xiwizard_exchange_ping_service",
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
                            "use" => "xiwizard_exchange_service",
                            "check_command" => "check_exchange_rbl!-B " . $serviceargs["rbl_servers"],
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "owa_http":
                    case "owa_https":

                        $pluginopts = "";

                        $urlparts = parse_url($url);
                        $vhost = $urlparts["host"];
                        if ($vhost == "")
                            $vhost = $address;
                        $pluginopts .= " -H " . $vhost; // virtual host name

                        $pluginopts .= " -f ok"; // on redirect, follow (OK status)
                        $pluginopts .= " -I " . $address; // ip address

                        $urlpath = $urlparts["path"];
                        if ($urlpath == "")
                            $urlpath = "/";
                        $pluginopts .= " -u \"" . $urlpath . "\"";

                        if ($svc == "owa_https")
                            $pluginopts .= " -S";
                        if (!empty($port))
                            $pluginopts .= " -p " . $port;
                        if (!empty($username))
                            $pluginopts .= " -a \"" . $username . ":" . $password . "\"";

                        $sname = "OWA HTTP";
                        if ($svc == "owa_https")
                            $sname .= "S";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $sname,
                            "use" => "xiwizard_exchange_service",
                            "check_command" => "check_xi_service_http!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "core_services":

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Exchange Core Services",
                            "use" => "xiwizard_exchange_service",
                            "check_command" => "check_xi_service_nsclient!" . $password . "!SERVICESTATE!-l " . $serviceargs["core_service_names"] . " -d SHOWALL",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "web_services":

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Exchange Web Services",
                            "use" => "xiwizard_exchange_service",
                            "check_command" => "check_xi_service_nsclient!" . $password . "!SERVICESTATE!-l " . $serviceargs["web_service_names"] . " -d SHOWALL",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pending_routing":

                        $checkcommand = "check_xi_service_nsclient!" . $password . "!COUNTER!-l \"\\\\SMTP Server(_Total)\\\\Messages Pending Routing\"";
                        if ($serviceargs["pending_routing_warning"] != "")
                            $checkcommand .= " -w " . $serviceargs["pending_routing_warning"];
                        if ($serviceargs["pending_routing_critical"] != "")
                            $checkcommand .= " -c " . $serviceargs["pending_routing_critical"];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Messages Pending Routing",
                            "use" => "xiwizard_windowsserver_nsclient_service",
                            "check_command" => $checkcommand,
                            "_xiwizard" => $wizard_name,
                        );

                        break;

                    case "remote_queue_length":

                        $checkcommand = "check_xi_service_nsclient!" . $password . "!COUNTER!-l \"\\\\SMTP Server(_Total)\\\\Remote Queue Length\"";
                        if ($serviceargs["remote_queue_length_warning"] != "")
                            $checkcommand .= " -w " . $serviceargs["remote_queue_length_warning"];
                        if ($serviceargs["remote_queue_length_critical"] != "")
                            $checkcommand .= " -c " . $serviceargs["remote_queue_length_critical"];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Remote Queue Length",
                            "use" => "xiwizard_windowsserver_nsclient_service",
                            "check_command" => $checkcommand,
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