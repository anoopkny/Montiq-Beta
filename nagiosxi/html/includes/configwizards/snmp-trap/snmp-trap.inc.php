<?php
//
// SNMP Trap Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

snmp_trap_configwizard_init();

function snmp_trap_configwizard_init()
{
    $name = "snmp_trap";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _('Monitor SNMP Traps.'),
        CONFIGWIZARD_DISPLAYTITLE => _('SNMP Trap'),
        CONFIGWIZARD_FUNCTION => 'snmp_trap_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => 'snmptrap.png',
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
function snmp_trap_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "snmp_trap";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $output = '<p style="margin: 10px 0 20px 0;">' . _('This wizard allows you to enable SNMP Traps for existing hosts that are being monitored.') . '</p>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if(!file_exists("/usr/local/bin/snmptraphandling.py")){
                $errmsg[$errors++] = _('It looks like you are missing the trap handling file <b>snmptraphandling.py</b> normally located here: /usr/local/bin/ - To use this wizard you must configure SNMP using the document <a href="https://assets.nagios.com/downloads/nagiosxi/docs/Integrating_SNMP_Traps_With_Nagios_XI.pdf">How to Intergrate SNMP Traps With Nagios XI</a>.');
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            $hosts_per_page = 15;

            $output = '
<script type="text/javascript">
var contentDivs = new Array();
var contentDivsinit = -1;
var lasttabid = 0; // By default show the first one.

function checkall() {
    var checkboxes = $(".checkbox");
    if ($("input[name=\'all_hosts\']").is(":checked")) {
        checkboxes.prop("checked", true);
    } else {
        checkboxes.prop("checked", false);
    }
}

function showNextTab() {
    if (contentDivsinit) {
        contentDivs = document.getElementById("HostPages").childNodes;
        contentDivsinit = 0;
    }
    selectedId = lasttabid;
    selectedId++;
    if (contentDivs.length < selectedId) { return false; }

    contentDivs[selectedId].className = "table";
    contentDivs[lasttabid].className = "table hide";
    lasttabid = selectedId;

    // Stop the browser following the link
    return false;
}

function showPrevTab() {
    if (contentDivsinit) {
        contentDivs = document.getElementById("HostPages").childNodes;
        contentDivsinit = 0;
    }
    selectedId = lasttabid;
    selectedId--;
    if (-1 >= selectedId) { return false; }

    contentDivs[selectedId].className = "table";
    contentDivs[lasttabid].className = "table hide";
    lasttabid = selectedId;

    // Stop the browser following the link
    return false;
}
</script>

<h5 class="ul">' . _('SNMP Trap Details') . '</h5>
<p>' . _('Select the hosts you would like to enable SNMP Traps for.') . '</p>';

            $output .= '<div id="HostPages">';

            $tstart = '<table class="table table-condensed table-striped table-no-border table-auto-width">';

            $output .= $tstart;
            $output .= '<tr><th><input type="checkbox" class="checkbox tt-bind" id="allhosts" name="all_hosts" onclick="checkall()" data-placement="right" title="'._('Toggle all hosts').'"></th><th>' . _('Host Name') . '</th></tr>';

            $mtstart = '<table class="table table-condensed table-striped table-no-border table-auto-width hide">';

            $args = array(
                "orderby" => "host_name:a",
            );
            $xml = get_xml_host_objects($args);
            if ($xml) {
                $x = 0;
                foreach ($xml->host as $h) {
                    $x++;

                    if (($x % 2) != 0)
                        $rowclass = '"odd"';
                    else
                        $rowclass = '"even"';

                    $host_name = htmlentities(strval($h->host_name));

                    $output .= '
                    <tr class=' . $rowclass . '>
                        <td>
                            <input type="checkbox" class="checkbox" id="host_' . $x . '" name="services[host][' . $host_name . ']" >
                        </td>
                        <td>
                            ' . $host_name . '
                        </td>
                    </tr>';

                    if (($x % ($hosts_per_page)) == 0) {
                        $output .= '
            </table>' . $mtstart;
                        $output .= '<tr><th>&nbsp;</th></tr>';
                    }

                }
            }

            $output .= '</table>';
            $output .= '</div>';

            if ($x > $hosts_per_page) {
                $output .= '<table>';
                $output .= '
                        <tr>
                        <td><a href="#"><span onClick="showPrevTab()">&lt; ' . _('Previous') . $hosts_per_page . '</span></a>&nbsp;</td>
                        <td>&nbsp;<a href="#"><span onClick="showNextTab()">' . _('Next') . $hosts_per_page . ' &gt;</span></a></td>
                        </tr>
                        </table>
                        ';
            }

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $services = grab_array_var($inargs, "services");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (!is_array($services))
                $errmsg[$errors++] = _("No hosts selected.");
            else if (!array_key_exists("host", $services))
                $errmsg[$errors++] = _("No hosts selected.");
            else if (count($services["host"]) == 0)
                $errmsg[$errors++] = _("No hosts selected.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            // Hide normal/retry check interval options
            $output = '<div style="margin-bottom: 20px;">' . _('There are no monitoring options to configure with SNMP Traps.  Click Next to continue.') . '</div>';
            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '

        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">

        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->

            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE4OPTS:

            // hide some notification options
            $output = '';
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            $outargs[CONFIGWIZARD_HIDDEN_OPTIONS] = array(
                CONFIGWIZARD_HIDE_NOTIFICATION_DELAY,
                CONFIGWIZARD_HIDE_NOTIFICATION_INTERVAL,
            );

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs,
                "serviceargs_serial", "");

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

            $objs = array();

            $hosts = $services["host"];
            foreach ($hosts as $hostname => $hoststate) {

                //echo "PROCESSING: $hostname -> $hoststate<BR>\n";

                $objs[] = array(
                    'type' => OBJECTTYPE_SERVICE,
                    'host_name' => $hostname,
                    'service_description' => 'SNMP Traps',
                    'use' => 'xiwizard_snmptrap_service',
                    'check_interval' => 1,
                    'retry_interval' => 1,
                    'max_check_attempts' => 1,
                    'notification_interval' => 1,
                    'icon_image' => 'snmptrap.png',
                    '_xiwizard' => $wizard_name,
                );
            }

            //echo "OBJECTS:<BR>";
            //print_r($objs);
            //exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;


        // THE FOLLOWING MODES ARE POST-CONFIGURATION CALLBACKS
        // THEY CAN BE USED TO DO CONFIGURATION TASKS, ETC AFTER A NEW
        //      CONFIGURATION HAS BEEN SUBMITTED

        case CONFIGWIZARD_MODE_COMMITERROR:
            echo "COMMITERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITCONFIGERROR:
            echo "COMMITCONFIGERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITPERMSERROR:
            echo "COMMITPERMSERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITOK:

            //echo "COMMITOK!\n";
            //echo "INARGS:\n";
            //print_r($inargs);

            $services_serial = grab_array_var($inargs, "services_serial");
            $services = unserialize(base64_decode($services_serial));

            //echo "SERVICES:\n";
            //print_r($services);

            // initialize each new service with an OK state
            $servicename = 'SNMP Traps';
            $hosts = grab_array_var($services, "host");
            foreach ($hosts as $hostname => $hoststate) {
                echo "HOST/SVC => $hostname,SNMP Traps\n";
                $output = "";
                $raw_command = "PROCESS_SERVICE_CHECK_RESULT;" . $hostname .
                    ";" . $servicename . ";0;Waiting for trap...\n";
                submit_direct_nagioscore_command($raw_command, $output);
            }

            break;

        default:
            break;
    }

    return $output;
}
