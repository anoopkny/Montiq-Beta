<?php
//
// Auto-Discovery Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

autodiscovery_configwizard_init();

function autodiscovery_configwizard_init()
{
    $name = "autodiscovery";

    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.0",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _('Monitor servers, devices, and services found by auto-discovery jobs.'),
        CONFIGWIZARD_DISPLAYTITLE => _('Auto-Discovery'),
        CONFIGWIZARD_FUNCTION => 'autodiscovery_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => 'autodiscovery.png',
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
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
function autodiscovery_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "autodiscovery";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $job = grab_array_var($inargs, "job", "");
            $show = grab_array_var($inargs, "show", "");
            $addresstype = grab_array_var($inargs, "addresstype", "ip");
            $defaultservices = grab_array_var($inargs, "defaultservices", "common");

            $output = '
    <h5 class="ul">' . _('Auto-Discovery Job') . '</h5>
    <table class="table table-condensed table-no-border table-auto-width table-padded">
        <tr>
            <td class="vt">
                <label>' . _('Job') . ':</label>
            </td>
            <td>
                <select name="job" class="form-control">';

            // Get jobs
            $jobs = autodiscovery_component_getjobs();

            // sort jobs by start time
            foreach ($jobs as $jobid => $row) {
                $search[$jobid] = $row['start_date'];
            }
            array_multisort($search, SORT_DESC, $jobs);

            $visible_jobs = 0;
            foreach ($jobs as $jobid => $jobarr) {

                $output_file = get_component_dir_base("autodiscovery") . "/jobs/" . $jobid . ".xml";

                // job is still running - skip it
                if (!file_exists($output_file))
                    continue;

                $visible_jobs++;

                $total_hosts = 0;
                $new_hosts = 0;
                $xml = @simplexml_load_file($output_file);
                if ($xml) {
                    foreach ($xml->device as $d) {
                        $status = strval($d->status);
                        if ($status == "new")
                            $new_hosts++;
                        $total_hosts++;
                    }
                }

                $jobdesc = 'Scan of ' . $jobarr["address"] . ' @ ' . get_datetime_string($jobarr["start_date"]) . " - " . _('Found') . " " . $new_hosts . ' ' . _('New') . ' / ' . $total_hosts . ' ' . _('Total Hosts');

                $output .= '<option value="' . htmlentities($jobid) . '" ' . is_selected($job, $jobid) . '>' . $jobdesc . '</option>';
            }

            if ($visible_jobs == 0) {
                $output .= '<option value="">' . _('No completed auto-discovery jobs found') . '.</option>';
            }

            $output .= '
                </select>
                <div class="subtext">' . _('Select the auto-discovery job you wish to use for choosing new hosts and services to monitor.') . '</div>
                <div class="subtext">' . _('If you wish, you can also') . ' <a href="' . get_base_url() . '/includes/components/autodiscovery/">' . _('launch a new discovery job') . '</a>.</div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>' . _('Show') . ':</label>
            </td>
            <td>
                <select name="show" class="form-control">
                    <option value="new" ' . is_selected($show, "new") . '>' . _('New Hosts') . '</option>
                    <option value="all" ' . is_selected($show, "all") . '>' . _('All Hosts') . '</option>
                </select>
                <div class="subtext">' . _('Choose whether you\'d like to see results from all hosts that were found during the scan, or only new hosts that aren\'t currently being monitored') . '.</div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>' . _('Default Services') . ':</label>
            </td>
            <td>
                <select name="defaultservices" class="form-control">
                    <option value="common" ' . is_selected($defaultservices, "common") . '>' . _('Common') . '</option>
                    <option value="none" ' . is_selected($defaultservices, "none") . '>' . _('None') . '</option>
                    <option value="all" ' . is_selected($defaultservices, "all") . '>' . _('All') . '</option>
                </select>
                <div class="subtext">' . _('Select the types of services that you would like to be selected for monitoring by default.  You can override individual services on the next page') . '.</div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>' . _('Host Addresses') . ':</label>
            </td>
            <td>
                <select name="addresstype" class="form-control">
                    <option value="ip" ' . is_selected($addresstype, "ip") . '>' . _('IP Addresses') . '</option>
                    <option value="dns" ' . is_selected($addresstype, "dns") . '>' . _('DNS Names') . '</option>
                </select>
                <div class="subtext">' . _('Select the type of addresses that you would prefer to use for newly configured hosts') . '.</div>
            </td>
        </tr>
    </table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $job = grab_array_var($inargs, "job", "");
            $defaultservices = grab_array_var($inargs, "defaultservices", "common");


            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($job) == false) {
                $errmsg[$errors++] = _("No job specified.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $job = grab_array_var($inargs, "job");
            $show = grab_array_var($inargs, "show", "");
            $addresstype = grab_array_var($inargs, "addresstype", "ip");
            $defaultservices = grab_array_var($inargs, "defaultservices", "common");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", array());

            $services_serial = base64_encode(serialize($services));

            $output = '
            
        <input type="hidden" name="services_serial" value="' . $services_serial . '" />
        <input type="hidden" name="job" value="' . htmlentities($job) . '">
        <input type="hidden" name="show" value="' . htmlentities($show) . '">
        <input type="hidden" name="addresstype" value="' . htmlentities($addresstype) . '">
        <input type="hidden" name="defaultservices" value="' . htmlentities($defaultservices) . '">

        <h5 class="ul">' . _('Scan Results') . '</h5>
        <p>' . _('The hosts and services below were discovered during the auto-discovery scan. Select the hosts and services you\'d like to monitor') . '.</p>';

            if (count($services) == 0) {

                $jobid = $job;
                $output_file = get_component_dir_base("autodiscovery") . "/jobs/" . $jobid . ".xml";

                $total_hosts = 0;
                $new_hosts = 0;
                $xml = @simplexml_load_file($output_file);
                if ($xml) {

                    foreach ($xml->device as $d) {

                        $status = strval($d->status);
                        //if($status!="new")
                        //continue;

                        $address = strval($d->address);
                        //$fqdns=strval($d->fqdns);
                        $fqdns = @gethostbyaddr($address);

                        $os = "";
                        $type = "";


                        $services[$address] = array(
                            "address" => $address,
                            "fqdns" => $fqdns,
                            "os" => $os,
                            "type" => $type,
                            "status" => $status,
                            "selected" => 0,
                            "ports" => array(),

                            "addressisdns" => 0,
                            "hostname" => $fqdns,
                        );
                        if ($addresstype == "ip")
                            $services[$address]["hostaddress"] = $address;
                        else {
                            $dnsname = @gethostbyaddr($address);
                            if ($dnsname != $address)
                                $services[$address]["addressisdns"] = 1;
                            $services[$address]["hostaddress"] = $dnsname;
                            $services[$address]["hostname"] = $dnsname;
                        }

                        // get ports
                        foreach ($d->ports->port as $p) {

                            $protocol = strval($p->protocol);
                            $port = strval($p->port);
                            $state = strval($p->state);

                            if ($state != "open")
                                continue;

                            $service = getservbyport($port, strtolower($protocol));
                            $servicename = autodiscovery_configwizard_get_friendly_service_name($service, $port, $protocol);

                            // should the service be selected/shown?
                            $is_selected = 0;
                            $display_service = 1;
                            if ($defaultservices == "all")
                                $is_selected = 1;
                            else if ($defaultservices == "common") {
                                if (autodiscovery_configwizard_is_common_service($service, $port, $protocol) == true)
                                    $is_selected = 1;
                            }
                            //echo "SELECTED: $is_selected<BR>";

                            if ($display_service == 1) {
                                $protocol = strtoupper($protocol);

                                $services[$address]["ports"][$protocol . "" . $port] = array(
                                    "protocol" => $protocol,
                                    "port" => $port,
                                    "service" => $service,
                                    "servicename" => $servicename,
                                    "selected" => $is_selected
                                );
                            }
                        }

                        // Get operating system (first one in list)
                        $services[$address]["os"] = strval($d->operatingsystems->osinfo->osname);
                        $services[$address]["osaccuracy"] = intval($d->operatingsystems->osinfo->osaccuracy);

                        // Get device type
                        $services[$address]["type"] = get_autodiscovery_type($services[$address]["os"]);

                    }
                } else {
                    $output .= '<p><b>' . _('Error') . ':</b> ' . _('No results were found in the selected auto-discovery scan') . '.</p>';
                }
            }

            $output .= '
            <script type="text/javascript">
            $(document).ready(function(){
                $("#cb_selectallhosts").click(function(){
                    $(".ad_host_checkbox").attr("checked",this.checked);
                });
                $("#cb_selectallservices").click(function(){
                    $(".ad_service_checkbox").attr("checked",this.checked);
                });
            });
            </script>
                ';

            $output .= '
                <table class="table table-condensed table-bordered table-striped table-auto-width">
                <thead>
                <tr><th rowspan="2"><input type="checkbox" id="cb_selectallhosts" name="selectallhosts"></th><th rowspan="2">' . _('Address') . '</th><th rowspan="2">' . _('Type') . '</th><th rowspan="2">' . _('OS') . '</th><th rowspan="2">' . _('Status') . '</th><th rowspan="2">' . _('Host Name') . '</th><th colspan="5">' . _('Services') . '</th></tr>
                <tr><th><input type="checkbox" id="cb_selectallservices" name="selectallservices"></th><th>' . _('Service Name') . '</th><th>' . _('Service') . '</th><th>Port</th><th>' . _('Protocol') . '</th></tr>
                </thead>
                <tbody>
                ';


            foreach ($services as $address => $arr) {

                $status = "";
                if ($arr["status"] == "new") {
                    $status = "New";
                } else {
                    // skip old hosts
                    if ($show == "new")
                        continue;
                    $status = "Old";
                }

                $output .= '<tr>';

                $output .= '<td><input type="checkbox" name="services[' . htmlentities($address) . '][selected]" class="ad_host_checkbox" ' . is_checked($services[$address]["selected"]) . '></td>';

                $output .= '<td>' . $address;
                if ($services[$address]["addressisdns"] == 1)
                    $output .= '<br> (' . $services[$address]["address"] . ')';
                $output .= '</td>';

                $output .= '<td>' . $arr["type"] . '</td>';
                $output .= '<td>' . $arr["os"] . '</td>';

                $output .= '<td>' . $status . '</td>';

                $output .= '<td colspan="6"><input type="text" name="services[' . htmlentities($address) . '][hostname]" value="' . htmlentities($arr["hostname"]) . '" class="form-control condensed" size="25"></td>';

                $output .= '</tr>';

                foreach ($arr["ports"] as $pid => $parr) {
                    $protocol = $parr["protocol"];
                    $port = $parr["port"];
                    $service = $parr["service"];
                    $servicename = $parr["servicename"];

                    $output .= '<tr>';
                    $output .= '<td colspan="6"></td>';

                    $output .= '<input type="hidden" name="services[' . htmlentities($address) . '][ports][' . htmlentities($protocol) . '' . htmlentities($port) . '][service]" value="' . htmlentities($service) . '">';
                    $output .= '<input type="hidden" name="services[' . htmlentities($address) . '][ports][' . htmlentities($protocol) . '' . htmlentities($port) . '][port]" value="' . htmlentities($port) . '">';
                    $output .= '<input type="hidden" name="services[' . htmlentities($address) . '][ports][' . htmlentities($protocol) . '' . htmlentities($port) . '][protocol]" value="' . htmlentities($parr["protocol"]) . '">';

                    $output .= '<td><input type="checkbox" name="services[' . htmlentities($address) . '][ports][' . htmlentities($protocol) . '' . htmlentities($port) . '][selected]" class="ad_service_checkbox" ' . is_checked($services[$address]["ports"][$protocol . "" . $port]["selected"], 1) . '></td>';

                    $output .= '<td><input type="text" name="services[' . htmlentities($address) . '][ports][' . htmlentities($protocol) . '' . htmlentities($port) . '][servicename]" class="form-control condensed" value="' . htmlentities($servicename) . '" size="25"></td>';

                    $output .= '<td>' . htmlentities($service) . '</td>';
                    $output .= '<td>' . htmlentities($port) . '</td>';
                    $output .= '<td>' . htmlentities($protocol) . '</td>';
                    $output .= '</tr>';
                }
                if (count($arr["ports"]) == 0) {
                    $output .= '<tr><td colspan="6"></td><td colspan="5">' . _('No services were detected on this host') . '.</td></tr>';
                }

                $output .= '<tr></tr>';
            }

            $output .= '
                </tbody>
                </table>
                ';

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $job = grab_array_var($inargs, "job");
            $show = grab_array_var($inargs, "show", "");
            $addresstype = grab_array_var($inargs, "addresstype", "ip");
            $defaultservices = grab_array_var($inargs, "defaultservices", "common");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services_u = unserialize(base64_decode($services_serial));
            $services_r = grab_array_var($inargs, "services", array());

            $services = array_merge($services_u, $services_r);
            // check for errors
            $errors = 0;
            $errmsg = array();
            $havehost = false;
            /*
            if(is_valid_host_name($hostname)==false)
                $errmsg[$errors++]="Invalid host name.";
            */
            if (!is_array($services))
                $errmsg[$errors++] = _("Service array is empty.");
            else {
                $havesvc = false;
                $hosthost = false;

                //print_r($services);

                foreach ($services as $address => $harr) {

                    $checkthishost = false;

                    if (array_key_exists("selected", $harr)) {
                        $havesvc = true;
                        $havehost = true;
                        $checkthishost = true;
                    }

                    // validate host name
                    if ($checkthishost == true) {
                        $hostname = $harr["hostname"];
                        if (is_valid_host_name($hostname) == false)
                            $errmsg[$errors++] = "Invalid host name '<b>$hostname</b>'";
                    }

                    // check all of the host's services
                    if (array_key_exists("ports", $harr)) {
                        foreach ($harr["ports"] as $pid => $parr) {
                            $checkthissvc = false;
                            if (array_key_exists("selected", $parr)) {
                                $havesvc = true;
                                $checkthissvc = true;
                            }
                            // validate service name
                            if ($checkthishost == true && $checkthissvc == true) {
                                $servicename = $parr["servicename"];
                                if (is_valid_service_name($servicename) == false)
                                    $errmsg[$errors++] = _("Invalid service name") . " '<b>$servicename</b>' " . _("on host") . " <b>$hostname</b>";
                            }
                        }
                    }
                }
                if ($havehost == false)
                    $errmsg[$errors++] = _("No hosts were selected.");
                //$errmsg[$errors++]="Looks okay.";
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $job = grab_array_var($inargs, "job");
            $show = grab_array_var($inargs, "show", "");
            $addresstype = grab_array_var($inargs, "addresstype", "ip");
            $defaultservices = grab_array_var($inargs, "defaultservices", "common");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services_u = unserialize(base64_decode($services_serial));
            $services_r = grab_array_var($inargs, "services", array());

            $services = array_merge($services_u, $services_r);


            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '

        <input type="hidden" name="job" value="' . htmlentities($job) . '">
        <input type="hidden" name="show" value="' . htmlentities($show) . '">
        <input type="hidden" name="addresstype" value="' . htmlentities($addresstype) . '">
        <input type="hidden" name="defaultservices" value="' . htmlentities($defaultservices) . '">

        <input type="hidden" name="services_serial" value="' .
                base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' .
                base64_encode(serialize($serviceargs)) . '">

        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->

            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE4OPTS:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $serviceargs_serial = grab_array_var($inargs,
                "serviceargs_serial", "");

            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services_u = unserialize(base64_decode($services_serial));
            $services_r = grab_array_var($inargs, "services", array());

            $services = array_merge($services_u, $services_r);

            $job = grab_array_var($inargs, "job");
            $show = grab_array_var($inargs, "show", "");
            $addresstype = grab_array_var($inargs, "addresstype", "ip");
            $defaultservices = grab_array_var($inargs, "defaultservices", "common");

            /*
            echo "SERVICES<BR>";
            print_r($services);
            echo "<BR>";
            echo "SERVICEARGS<BR>";
            print_r($serviceargs);
            echo "<BR>";
            */

            $objs = array();

            // process each host
            foreach ($services as $address => $arr) {
                // the host should be monitored...
                if (array_key_exists("selected", $arr)) {

                    $hostname = $arr["hostname"];

                    // add the host if necessary
                    if (!host_exists($hostname)) {

                        $hostaddress = $address;
                        if ($addresstype == "dns")
                            $hostaddress = @gethostbyaddr($address);

                        // add the host
                        $objs[] = array(
                            "type" => OBJECTTYPE_HOST,
                            "use" => "xiwizard_generic_host",
                            "host_name" => $hostname,
                            "address" => $hostaddress,
                            "_xiwizard" => $wizard_name,
                        );

                        // add a "Ping" service
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Ping",
                            "use" => "xiwizard_genericnetdevice_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                    }

                    // process each port/service
                    foreach ($arr["ports"] as $pid => $parr) {

                        // skip this service if it wasn't selected
                        if (!array_key_exists("selected", $parr))
                            continue;

                        $servicename = $parr["servicename"];
                        $port = $parr["port"];
                        $protocol = strtolower($parr["protocol"]);

                        autodiscovery_configwizard_get_object_vars($port, $protocol, $use, $cmdline);

                        $newsvc = array(
                            'type' => OBJECTTYPE_SERVICE,
                            'host_name' => $hostname,
                            'service_description' => $servicename,
                            '_xiwizard' => $wizard_name,
                        );
                        if ($use != "")
                            $newsvc['use'] = $use;
                        if ($cmdline != "")
                            $newsvc['check_command'] = $cmdline;

                        $objs[] = $newsvc;
                    }
                }
            }

            //echo "OBJECTS:<BR>";

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;


        // THE FOLLOWING MODES ARE POST-CONFIGURATION CALLBACKS
        // THEY CAN BE USED TO DO CONFIGURATION TASKS, ETC AFTER A NEW
        //      CONFIGURATION HAS BEEN SUBMITTED

        case CONFIGWIZARD_MODE_COMMITERROR:
            break;

        case CONFIGWIZARD_MODE_COMMITCONFIGERROR:
            break;

        case CONFIGWIZARD_MODE_COMMITPERMSERROR:
            break;

        case CONFIGWIZARD_MODE_COMMITOK:

            break;

        default:
            break;
    }

    return $output;
}

/**
 * @param $port
 * @param $protocol
 * @param $use
 * @param $cmdline
 */
function autodiscovery_configwizard_get_object_vars($port, $protocol, &$use, &$cmdline)
{

    $use = "xiwizard_generic_service";
    $cmdline = "";

    $arr = array(
        "tcp" => array(
            21 => array("use" => "xiwizard_ftp_service"),
            22 => array("use" => "xiwizard_ssh_service"),
            25 => array("use" => "xiwizard_smtp_service"),
            80 => array("use" => "xiwizard_website_http_service"),
            110 => array("use" => "xiwizard_pop_service"),
            143 => array("use" => "xiwizard_imap_service"),
            443 => array("use" => "xiwizard_website_http_service", "cmdline" => "check_xi_service_http!-S"),
        ),
        "udp" => array(),
    );

    if (array_key_exists($port, $arr[$protocol])) {
        $match = $arr[$protocol][$port];
        $use = grab_array_var($match, "use");
        $cmdline = grab_array_var($match, "cmdline");
    } else {
        // use either xiwizard_tcp_service OR xiwizard_udp_service templates
        $use = "xiwizard_" . $protocol . "_service";
        // check_xi_service_tcp OR check_xi_service_udp
        $cmdline = "check_xi_service_" . $protocol . "!-p " . $port;
    }
}


/**
 * @param $service
 * @param $port
 * @param $protocol
 *
 * @return bool
 */
function autodiscovery_configwizard_is_common_service($service, $port, $protocol)
{

    $protoname = strtoupper($protocol);

    $name = $service;

    $common_services = array(
        "tcp" => array(
            21 => "FTP",
            22 => "SSH",
            23 => "Telnet",
            25 => "SMTP",
            80 => "HTTP",
            110 => "POP3",
            143 => "IMAP",
            389 => "LDAP",
            443 => "HTTPS",
            139 => "NetBIOS",
            631 => "IPP",
            993 => "IMAP SSL",
            3389 => "RDP",
            5666 => "NRPE",
            5667 => "NSCA",
        ),
        "udp" => array(),
    );

    if (array_key_exists($port, $common_services[$protocol]))
        return true;
    return false;
}


/**
 * @param $service
 * @param $port
 * @param $protocol
 *
 * @return string
 */
function autodiscovery_configwizard_get_friendly_service_name($service, $port, $protocol)
{

    $protoname = strtoupper($protocol);

    $name = $service;

    $friendly_names = array(
        "tcp" => array(
            21 => "FTP",
            22 => "SSH",
            23 => "Telnet",
            25 => "SMTP",
            80 => "HTTP",
            110 => "POP3",
            143 => "IMAP",
            389 => "LDAP",
            443 => "HTTPS",
            139 => "NetBIOS",
            631 => "IPP",
            993 => "IMAP SSL",
            3389 => "RDP",
            5666 => "NRPE",
            5667 => "NSCA",
        ),
        "udp" => array(),
    );

    if (array_key_exists($port, $friendly_names[$protocol]))
        $name = $friendly_names[$protocol][$port];
    else if ($service == "")
        $name = $protoname . " Port " . $port;
    else {
        // remome illegal chars in portnames -SW
        // `~!$%^&*|'"<>?,()=/\
        $badchars = explode(" ", "; ` ~ ! $ % ^ & * | ' \" < > ? , ( ) = / \\ { }");
        str_replace($badchars, " ", $service);
        $name = $protoname . " Port " . $port . " - " . $service . "";
    }
    return $name;
}