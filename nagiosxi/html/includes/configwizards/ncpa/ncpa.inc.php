<?php
//
// Nagios Cross-Platform Agent (NCPA) Config Wizard
// Copyright (c) 2014-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');


ncpa_configwizard_init();


function ncpa_configwizard_init()
{
    $name = "ncpa";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.4.1",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a host (Windows, Linux, or OS X) using the Nagios Cross-Plaftorm Agent."),
        CONFIGWIZARD_DISPLAYTITLE => _("NCPA"),
        CONFIGWIZARD_FUNCTION => "ncpa_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "ncpa.png",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios','windows','linux','otheros'),
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
function ncpa_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "ncpa";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {

        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "5693");
            $token = grab_array_var($inargs, "token", "");
            $no_ssl_verify = grab_array_var($inargs, "no_ssl_verify", 1);

            $output = '
            <h5 class="ul">' . _('Connect to NCPA') . '</h5>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt"></td>
                    <td class="checkbox">
                        <label>
                            <input type="checkbox" name="no_ssl_verify" value="1" ' . is_checked($no_ssl_verify, 1) . '>
                            ' . _("Do not verify SSL certificate") . '
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="vt"><label>' . _('Address') . ':</label></td>
                    <td>
                        <input type="text" size="40" name="address" value="' . encode_form_val($address) . '" class="textfield usermacro-detection form-control">
                        <div class="subtext">' . _('The IP address or FQDNS name used to connect to NCPA') . '.</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt"><label>' . _('Port') . ':</label></td>
                    <td>
                        <input type="text" size="5" name="port" value="' . encode_form_val($port) . '" class="textfield usermacro-detection form-control">
                        <div class="subtext">' . _('Port used to connect to NCPA. Defaults to port 5693') . '.</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt"><label>' . _('Token') . ':</label></td>
                    <td>
                        <input type="text" size="20" name="token" id="token" value="' . encode_form_val($token) . '" class="textfield usermacro-detection form-control">
                        <div class="subtext">' . _('Authentication token used to connect to NCPA') . '.</div>
                    </td>
                </tr>
            </table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:
            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "5693");
            $token = grab_array_var($inargs, "token", "");
            $no_ssl_verify = grab_array_var($inargs, "no_ssl_verify", 1);

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($address) == false) {
                $errmsg[$errors++] = _("No address specified.");
            }
            if (have_value($port) == false) {
                $errmsg[$errors++] = _("No port number specified.");
            }

            // Test the connection if no errors
            if (empty($errors)) {

                $address = nagiosccm_replace_user_macros($address);
                $port = nagiosccm_replace_user_macros($port);
                $token = nagiosccm_replace_user_macros($token);

                // The URL we will use to query the NCPA agent, and do a walk
                // of all monitorable items.
                $query_url = "https://{$address}:{$port}/testconnect?token={$token}";

                // Remove SSL verification or not
                $context = array("ssl" => array("verify_peer" => true, "verify_peer_name" => true));
                if ($no_ssl_verify) {
                    $context['ssl']['verify_peer'] = false;
                    $context['ssl']['verify_peer_name'] = false;
                }

                // All we want to do is test if we can hit this URL.
                $raw_json = file_get_contents($query_url, false, stream_context_create($context));
                if (empty($raw_json)) {
                    $errmsg[$errors++] = _("Unable to contact server at") . " {$query_url}.";
                } else {
                    $json = json_decode($raw_json, true);
                    if (!array_key_exists('value', $json)) {
                        $errmsg[$errors++] = _("Bad token for connection.");
                    }
                }

            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:
            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "");
            $token = grab_array_var($inargs, "token", "");
            $no_ssl_verify = grab_array_var($inargs, "no_ssl_verify", 1);
            $hostname = grab_array_var($inargs, 'hostname', gethostbyaddr($address));

            $rp_address = nagiosccm_replace_user_macros($address);
            $rp_port = nagiosccm_replace_user_macros($port);
            $rp_token = nagiosccm_replace_user_macros($token);

            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial) {
                $services = unserialize(base64_decode($services_serial));
            }

            // Remove SSL verification or not
            $context = array("ssl" => array("verify_peer" => true, "verify_peer_name" => true));
            if ($no_ssl_verify) {
                $context['ssl']['verify_peer'] = false;
                $context['ssl']['verify_peer_name'] = false;
            }

            // The URL we will use to query the NCPA agent, and do a walk
            // of all monitorable items. Make three queries, one for disk,
            // one for interfaces, and one for services.
            $iface_url = "https://{$rp_address}:{$rp_port}/api/interface?token={$rp_token}";
            $disks_url = "https://{$rp_address}:{$rp_port}/api/disk?token={$rp_token}";
            $services_api_url = "https://{$rp_address}:{$rp_port}/api/services?token={$rp_token}";

            $iface_json = file_get_contents($iface_url, false, stream_context_create($context));
            $iface_data = json_decode($iface_json, true);
            if (array_key_exists('value', $iface_data)) {
                $iface_root = $iface_data['value']['interface'];
            } else {
                $iface_root = $iface_data['interface'];
            }
            
            $disks_json = file_get_contents($disks_url, false, stream_context_create($context));
            $disks_data = json_decode($disks_json, true);
            if (array_key_exists('value', $disks_data)) {
                $disks_root = $disks_data['value']['disk'];
            } else {
                $disks_root = $disks_data['disk'];
            }

            $services_api_json = file_get_contents($services_api_url, false, stream_context_create($context));
            $services_api_data = json_decode($services_api_json, true);
            if (array_key_exists('value', $services_api_data)) {
                $services_api_root = $services_api_data['value']['services'];
            } else {
                $services_api_root = $services_api_data['services'];
            }

            $categories = array();
            $root = array();

            $root['disk'] = $disks_root;
            $root['interface'] = $iface_root;
            $root['services'] = $services_api_root;

            $output = '
            <input type="hidden" name="address" value="' . encode_form_val($address) . '">
            <input type="hidden" name="port" value="' . encode_form_val($port) . '">
            <input type="hidden" name="token" value="' . encode_form_val($token) . '">
            <input type="hidden" name="no_ssl_verify" value="' . intval($no_ssl_verify) . '">

            <h5 class="ul">' . _("Connection Information") . '</h5>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt"><label>' . _("Address") . ':</label></td>
                    <td>
                        <input type="text" size="20" value="' . encode_form_val($address) . '" class="textfield form-control" disabled>
                    </td>
                </tr>
                <tr>
                    <td class="vt"><label>' . _("Host Name") . ':</label></td>
                    <td>
                        <input type="text" size="20" name="hostname" id="hostname" value="' . encode_form_val($hostname) . '" class="textfield form-control">
                        <div class="subtext">' . _("The hostname you'd like to have associated with this NCPA Agent") . '.</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt"><label>' . _("Port") . ':</label></td>
                    <td>
                        <input type="text" size="5" value="' . encode_form_val($port) . '" class="textfield form-control" disabled>
                    </td>
                </tr>
                <tr>
                    <td><label>' . _("Token") . ':</label></td>
                    <td>
                        <input type="text" size="20" value="' . encode_form_val($token) . '" class="textfield form-control" disabled>
                    </td>
                </tr>
            </table>';

            // Set defaults for services
            $default_services['cpu_usage']['monitor'] = 'on';
            $default_services['cpu_usage']['warning'] = 20;
            $default_services['cpu_usage']['critical'] = 40;
            $default_services['cpu_usage']['average'] = 1;
            $default_services['memory_usage']['monitor'] = 'on';
            $default_services['memory_usage']['warning'] = 50;
            $default_services['memory_usage']['critical'] = 80;
            $default_services['swap_usage']['monitor'] = 'on';
            $default_services['swap_usage']['warning'] = 50;
            $default_services['swap_usage']['critical'] = 80;

            foreach ($root['disk']['logical'] as $title => $value) {
                $default_services['disk'][$title]['monitor'] = 'on';
                $default_services['disk'][$title]['warning'] = 70;
                $default_services['disk'][$title]['critical'] = 90;
                $default_services['disk'][$title]['name'] = $value['device_name'][0];
            }

            ksort($root['interface']);
            foreach ($root['interface'] as $title => $value) {
                if (stripos($title, "Local Area Connection") !== false || stripos($title, "eth") !== false || stripos($title, "Wireless") !== false) {
                    $default_services['interface'][$title]['monitor'] = 'on';
                } else {
                    $default_services['interface'][$title]['monitor'] = 'off';
                }
                $default_services['interface'][$title]['warning'] = 10;
                $default_services['interface'][$title]['critical'] = 100;
                $default_services['interface'][$title]['name'] = $title;
            }

            // Create only one default process to monitor... we will add more via JS if someone wants to add more
            $default_services['process'][0]['monitor'] = 'off';
            $default_services['process'][0]['name'] = '';
            $default_services['process'][0]['display_name'] = '';
            $default_services['process'][0]['count']['warning'] = 60;
            $default_services['process'][0]['count']['critical'] = 100;

            // Create only one service too if there are no services saved
            $default_services['services'][0]['monitor'] = 'off';
            $default_services['services'][0]['name'] = '';
            $default_services['services'][0]['state'] = 'running';

            if (!isset($services)) {
                $services = $default_services;
            }

            $output .= '
            <h5 class="ul">' . _("CPU Metrics") . '</h5>
            <p>' . _("Specify the metrics you'd like to monitor on the NCPA Agent") . '.</p>
            <table class="table table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" name="services[cpu_usage][monitor]" id="cpum" ' . is_checked(grab_array_var($services['cpu_usage'], "monitor"), "on") . '>
                    </td>
                    <td>
                        <div>
                            <label for="cpum" style="line-height: auto;">
                                <b>' . _('CPU Usage') . '</b>
                            </label>
                            <div style="margin-bottom: 6px;">' . _('Check the CPU usage of the system') . '.</div>
                        </div>
                        <div>
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>
                            <input type="text" size="1" name="services[cpu_usage][warning]" value="' . encode_form_val($services['cpu_usage']['warning']) . '" class="form-control condensed"> %&nbsp;&nbsp;
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>
                            <input type="text" size="1" name="services[cpu_usage][critical]" value="' . encode_form_val($services['cpu_usage']['critical']) . '" class="form-control condensed"> %
                        </div>
                        <div class="checkbox" style="margin-top: 5px;">
                            <label>
                                <input type="checkbox" name="services[cpu_usage][average]" value="1" ' . is_checked($services['cpu_usage']['average'], 1) . '> ' . _("Show average CPU usage instead of per cpu core") . '
                            </label>
                        </div>
                    </td>
                </tr>
            </table>
            
            <h5 class="ul">' . _('Memory Metrics') . '</h5>
            <table class="table table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" name="services[memory_usage][monitor]" id="mainm" ' . is_checked(grab_array_var($services['memory_usage'], 'monitor'), 'on') . '>
                    </td>
                    <td>
                        <label class="normal" for="mainm">
                            <b>' . _('Main Memory Usage') . '</b><br>
                            ' . _('Monitor the main memory of the system. This metric is the percentage of main memory used') . '.
                        </label>
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>
                            <input type="text" size="1" name="services[memory_usage][warning]" value="' . encode_form_val($services['memory_usage']['warning']) . '" class="form-control condensed"> %&nbsp;&nbsp;
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>
                            <input type="text" size="1" name="services[memory_usage][critical]" value="' . encode_form_val($services['memory_usage']['critical']) . '" class="form-control condensed"> %
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="vt">
                        <input type="checkbox" class="checkbox" name="services[swap_usage][monitor]" id="swapm" ' . is_checked(grab_array_var($services['swap_usage'], 'monitor'), 'on') . '>
                    </td>
                    <td>
                        <label class="normal" for="swapm">
                            <b>' . _('Swap Usage') . '</b><br>
                            ' . _('Monitor the percentage of allocated swap used by the system') . '.
                        </label>
                        <div class="pad-t5">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>
                            <input type="text" size="1" name="services[swap_usage][warning]" value="' . encode_form_val($services['swap_usage']['warning']) . '" class="form-control condensed"> %&nbsp;&nbsp;
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>
                            <input type="text" size="1" name="services[swap_usage][critical]" value="' . encode_form_val($services['swap_usage']['critical']) . '" class="form-control condensed"> %
                        </div>
                    </td>
                </tr>
            </table>
    
            <h5 class="ul">' . _("Disk Metrics") . '</h5>
            <p>' . _("Specify the disks the the warning and critical percentages for disk capacity") . '.</p>
            <table class="table table-condensed table-no-border table-auto-width table-padded">';

            $id = 0;
            foreach ($services['disk'] as $title => $metrics) {
                $id++;
                $output .= '
                <tr>
                    <td>
                        <input type="checkbox" id="d' . $id . '" class="checkbox" name="services[disk][' . $title . '][monitor]" ' . is_checked(grab_array_var($services['disk'][$title], 'monitor'), 'on') . '>
                    </td>
                    <td>
                        <input type="hidden" name="services[disk][' . $title . '][name]" value="' . $metrics['name'][0] . '">
                        <label for="d' . $id . '"><input type="text" class="form-control" name="services[disk][' . $title . '][name]" value="' . $metrics['name'][0] . '" disabled></label>
                        <span style="margin-left: 6px;">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>
                            <input type="text" size="1" name="services[disk][' . $title . '][warning]" value="' . encode_form_val($services['disk'][$title]['warning']) . '" class="form-control condensed"> %&nbsp;&nbsp;
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>
                            <input type="text" size="1" name="services[disk][' . $title . '][critical]" value="' . encode_form_val($services['disk'][$title]['critical']) . '" class="form-control condensed"> %
                        </span>
                    </td>
                </tr>';
            }

            $output .= '
            </table>

            <h5 class="ul">' . _('Network Interface Metrics') . '</h5>
            <p>' . _("Specify reasonable bandwidths for your network interfaces. Note that these measurements are in megabits") . '.</p>
            <script type="text/javascript">
            var IF_SHOW = 0;
            $(document).ready(function() {
                $(".show-hidden-interfaces").click(function() {
                    if (!IF_SHOW) {
                        IF_SHOW = 1;
                        $(this).html("'._('Hide unselected interfaces').'");
                        $(".hidden-interface").removeClass("hide");
                    } else {
                        IF_SHOW = 0;
                        $(this).html("'._('Show all interfaces').'");
                        $(".hidden-interface").each(function() {
                            if (!$(this).find("input.checkbox").is(":checked")) {
                                $(this).addClass("hide");
                            }
                        });
                    }
                });
            });
            </script>
            <table class="table table-condensed table-no-border table-auto-width table-padded">';

            $id = 0;
            $hidden = false;
            foreach ($services['interface'] as $title => $metrics) {
                $id++;
                $hide = '';
                if (!is_checked(grab_array_var($services['interface'][$title], 'monitor'), 'on')) {
                    $hide = 'class="hidden-interface hide"';
                }
                $output .= '
                <tr '.$hide.'>
                    <td>
                        <input type="checkbox" id="ni' . $id . '" class="checkbox" name="services[interface][' . $title . '][monitor]" ' . is_checked(grab_array_var($services['interface'][$title], 'monitor'), 'on') . '>
                    </td>
                    <td>
                        <label for="ni' . $id . '"><input type="text" class="form-control" style="width: 300px;" name="services[interface][' . $title . '][name]" value="' . encode_form_val(str_replace("|", "/", $title)) . '" disabled></label>
                        <span style="margin-left: 6px;">
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>
                            <input type="text" size="2" name="services[interface][' . $title . '][warning]" value="' . encode_form_val($services['interface'][$title]['warning']) . '" class="form-control condensed"> Mb&nbsp;&nbsp;
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>
                            <input type="text" size="2" name="services[interface][' . $title . '][critical]" value="' . encode_form_val($services['interface'][$title]['critical']) . '" class="form-control condensed"> Mb
                        </span>
                    </td>
                </tr>';
            }

            $output .= '
            </table>
            <p><a class="show-hidden-interfaces">'._("Show all interfaces").'</a></p>

            <h5 class="ul">' . _("Services") . '</h5>
            <p>' . _("Specify which services should be running or stopped. Depending on the selected state you will recieve an OK when the process is in the selected state and a CRITICAL if the process is not in the state selected.") . '</p>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <thead>
                    <tr>
                        <td></td>
                        <td style="font-weight: bold;">' . _("Service Name") . '</td>
                        <td style="font-weight: bold;">' . _("Expected Status") . '</td>
                    </tr>
                </thead>
                <tbody id="services-list">';

            foreach ($services['services'] as $i => $metrics) {
                $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" name="services[services][' . $i . '][monitor]" ' . is_checked($metrics['monitor'], 'on') . '>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control" style="width: 200px;" name="services[services][' . $i . '][name]" value="' . encode_form_val($metrics['name']) . '">
                                <div class="input-group-addon" style="padding-bottom: 5px;"><span style="cursor: pointer;" class="service-selector" data-id="' . $i . '"><img src="' . theme_image('history2.png') . '" title="' . _("Select service from currently running services list") . '"></span></div>
                            </div>
                        </td>
                        <td>
                            <label class="normal" style="margin-right: 6px;"><input name="services[services][' . $i . '][state]" style="vertical-align: text-bottom;" type="radio" value="running" ' . is_checked($metrics['state'], 'running') . '> ' . _("Running") . '</label><label class="normal"><input type="radio" style="vertical-align: text-bottom;" value="stopped" name="services[services][' . $i . '][state]" ' . is_checked($metrics['state'], 'stopped') . '> ' . _("Stopped") . '</label>
                        </td>
                    </tr>';
            }

            // Create a list of services for the JS
            $service_list = '';
            ksort($root['services']);
            foreach ($root['services'] as $service => $status) {
                $service_list .= '<option value="' . $service . '" data-status="' . $status . '">' . $service . ' (' . $status . ')</option>';
            }

            $output .= '
                </tbody>
            </table>
            <div style="margin: 10px 0 20px 0;">
                <a style="cursor: pointer;" id="add-new-service">Add Another Service Check</a>
            </div>
    
            <h5 class="ul">' . _('Running Processes') . '</h5>
            <p>' . _("Specify which processes should be running, and how many should be") . '.</p>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <thead>
                    <tr>
                        <td></td>
                        <td><label>Process Name</label></td>
                        <td><label>Display Name</label></td>
                        <td><label>Warning #</label></td>
                        <td><label>Critical #</label></td>
                    </tr>
                </thead>
                <tbody id="process-list">';

            foreach ($services['process'] as $i => $metrics) {
                $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" class="checkbox deselect" name="services[process][' . $i . '][monitor]" ' . is_checked($metrics['monitor'], 'on') . '>
                        </td>
                        <td style="padding-right: 10px;">
                            <input type="text" class="destext form-control" name="services[process][' . $i . '][name]" value="' . encode_form_val($metrics['name']) . '">
                        </td>
                        <td style="padding-right: 10px;">
                            <input type="text" class="destext form-control" name="services[process][' . $i . '][display_name]" value="' . encode_form_val($metrics['display_name']) . '">
                        </td>
                        <td>
                            <input type="text" class="destext form-control" size="2" name="services[process][' . $i . '][count][warning]" value="' . encode_form_val($metrics['count']['warning']) . '">
                        </td>
                        <td>
                            <input type="text" class="destext form-control" size="2" name="services[process][' . $i . '][count][critical]" value="' . encode_form_val($metrics['count']['critical']) . '">
                        </td>
                    </tr>';
            }

            $output .= '
                </tbody>
            </table>
            <div style="margin: 10px 0 20px 0;">
                <a style="cursor: pointer;" id="add-new-process">Add Another Process Check</a>
            </div>
            <script type="text/javascript">
            var processnum = ' . (count($services['process']) - 1) . ';
            var servicenum = ' . (count($services['services']) - 1) . ';
            $(document).ready(function() {

                $("#add-new-process").click(function() {
                    processnum++;
                    $("#process-list").append(\'<tr><td><input type="checkbox" class="checkbox deselect" name="services[process][\'+processnum+\'][monitor]" /></td><td style="padding-right: 10px;"><input type="text" class="destext form-control" name="services[process][\'+processnum+\'][name]" value=""></td><td style="padding-right: 10px;"><input type="text" class="destext form-control" name="services[process][\'+processnum+\'][display_name]" value=""></td><td><input type="text" class="destext form-control" size="2" name="services[process][\'+processnum+\'][count][warning]" value="60"></td><td><input type="text" class="destext form-control" size="2" name="services[process][\'+processnum+\'][count][critical]" value="100"></td></tr>\');
                });

                $("#add-new-service").click(function() {
                    servicenum++;
                    $("#services-list").append(\'<tr><td><input type="checkbox" name="services[services][\'+servicenum+\'][monitor]"></td><td><div class="input-group"><input type="text" class="form-control" style="width: 200px;" name="services[services][\'+servicenum+\'][name]" value=""><div class="input-group-addon" style="padding-bottom: 5px;"><span style="cursor: pointer;" class="service-selector" data-id="\'+servicenum+\'"><img src="' . theme_image('history2.png') . '" title="' . _("Select service from currently running services list") . '"></span></div></div></td><td><label class="normal" style="margin-right: 6px;"><input name="services[services][\'+servicenum+\'][state]" type="radio" value="running" style="vertical-align: text-bottom;" checked> ' . _("Running") . '</label><label class="normal"><input type="radio" value="stopped" style="vertical-align: text-bottom;" name="services[services][\'+servicenum+\'][state]"> ' . _("Stopped") . '</label></td></tr>\');
                });

                $("#services-list").on("click", ".service-selector", function() {
                    var service_id = $(this).data("id");
                    var content = \'<div><h2>' . _("Services listed by the NCPA Agent") . '</h2><p>' . _("Select a service that is either running or stopped from the NCPA client host to atuomatically fill in the service name and the expected state.") . '</p><div><select id="selected-service" class="form-control">' . $service_list . '</select></div><div style="margin-top: 6px;"><button data-serviceid="\'+service_id+\'" class="btn btn-sm btn-primary" id="add-selected-service">' . _("Select this Service") . '</button></div></div>\';
                    
                    $("#child_popup_container").width(450);
                    $("#child_popup_layer").width(480);
                    $("#child_popup_layer").css("position", "fixed");
                    set_child_popup_content(content);
                    display_child_popup();
                });

                $("#child_popup_container").on("click", "#add-selected-service", function() {
                    var service_id = $(this).data("serviceid");
                    var selected_service = $("#selected-service option:selected").val();
                    var selected_state = $("#selected-service option:selected").data("status");
                    $(\'input[name="services[services][\'+service_id+\'][name]"]\').val(selected_service);
                    $(\'input[name="services[services][\'+service_id+\'][monitor]"]\').prop("checked", true);
                    $(\'input[name="services[services][\'+service_id+\'][state]"][value="\'+selected_state+\'"]\').prop("checked", true);
                    close_child_popup();
                });

            });
            </script>
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:
            // Get variables that were passed to us
            $address = grab_array_var($inargs, 'address');
            $hostname = grab_array_var($inargs, 'hostname');
            $port = grab_array_var($inargs, 'port');
            $token = grab_array_var($inargs, 'token');

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false) {
                $errmsg[$errors++] = "Invalid host name.";
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:
            // Get variables that were passed to us
            $address = grab_array_var($inargs, 'address');
            $hostname = grab_array_var($inargs, 'hostname');
            $port = grab_array_var($inargs, 'port');
            $token = grab_array_var($inargs, 'token');
            $services = grab_array_var($inargs, 'services', array());

            $output = '
            <input type="hidden" name="address" value="' . encode_form_val($address) . '" />
            <input type="hidden" name="hostname" value="' . encode_form_val($hostname) . '" />
            <input type="hidden" name="port" value="' . encode_form_val($port) . '" />
            <input type="hidden" name="token" value="' . encode_form_val($token) . '" />
            <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '" />';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:
            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:
            $output = '';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:
            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $hostaddress = $address;
            $port = grab_array_var($inargs, "port", "");
            $token = grab_array_var($inargs, "token", "");
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));

            // Save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["port"] = $port;
            $meta_arr["token"] = $token;
            $meta_arr["services"] = $services;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();
            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_ncpa_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "ncpa.png",
                    "statusmap_image" => "ncpa.png",
                    "_xiwizard" => $wizard_name);
            }

            // Common plugin opts
            $commonopts = "-t '$token' ";
            if ($port) {
                $commonopts .= "-P $port ";
            }

            foreach ($services as $type => $args) {
                $pluginopts = "";
                $pluginopts .= $commonopts;

                switch ($type) {

                    case "cpu_usage":
                        if (!array_key_exists('monitor', $args)) {
                            break;
                        }
                        $pluginopts .= "-M cpu/percent";

                        if (!empty($args['warning'])) {
                            $pluginopts .= " -w " . $args["warning"];
                        }
                        if (!empty($args['critical'])) {
                            $pluginopts .= " -c " . $args["critical"];
                        }

                        if (!empty($args['average'])) {
                            $pluginopts .= " -q 'aggregate=avg'";
                        }

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "CPU Usage",
                            "use" => "xiwizard_ncpa_service",
                            "check_command" => "check_xi_ncpa!" . $pluginopts,
                            "_xiwizard" => $wizard_name);
                        break;

                    case "memory_usage":
                        if (!array_key_exists('monitor', $args)) {
                            break;
                        }
                        $pluginopts .= "-M memory/virtual -u " . $args['unit'];

                        if (!empty($args['warning'])) {
                            $pluginopts .= " -w " . $args["warning"];
                        }
                        if (!empty($args['critical'])) {
                            $pluginopts .= " -c " . $args["critical"];
                        }

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Memory Usage",
                            "use" => "xiwizard_ncpa_service",
                            "check_command" => "check_xi_ncpa!" . $pluginopts,
                            "_xiwizard" => $wizard_name);
                        break;

                    case "swap_usage":
                        if (!array_key_exists('monitor', $args)) {
                            break;
                        }
                        $pluginopts .= "-M memory/swap -u " . $args['unit'];

                        if (!empty($args['warning'])) {
                            $pluginopts .= " -w " . $args["warning"];
                        }
                        if (!empty($args['critical'])) {
                            $pluginopts .= " -c " . $args["critical"];
                        }

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Swap Usage",
                            "use" => "xiwizard_ncpa_service",
                            "check_command" => "check_xi_ncpa!" . $pluginopts,
                            "_xiwizard" => $wizard_name);
                        break;

                    case "disk":
                        foreach ($args as $title => $metrics) {
                            if (!array_key_exists('monitor', $metrics)) {
                                continue;
                            }
                            $theseopts = "{$pluginopts} -M 'disk/logical/{$title}/used_percent'";

                            if (!empty($metrics["warning"])) {
                                $theseopts .= " -w " . $metrics["warning"];
                            }
                            if (!empty($metrics["critical"])) {
                                $theseopts .= " -c " . $metrics["critical"];
                            }

                            // Make sure back slash doesn't escape service description line
                            $service_name = str_replace('\\', '/', $metrics["name"]);

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => "Disk Usage on " . $service_name,
                                "use" => "xiwizard_ncpa_service",
                                "check_command" => "check_xi_ncpa!" . $theseopts,
                                "_xiwizard" => $wizard_name);
                        }
                        break;

                    case "interface":
                        foreach ($args as $title => $metrics) {
                            if (!array_key_exists('monitor', $metrics)) {
                                continue;
                            }

                            $theseopts = "{$pluginopts} -M 'interface/{$title}/bytes_sent' -d -u M";

                            if (!empty($metrics["warning"])) {
                                $theseopts .= " -w " . $metrics["warning"];
                            }
                            if (!empty($metrics["critical"])) {
                                $theseopts .= " -c " . $metrics["critical"];
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => "{$title} Bandwidth - Outbound",
                                "use" => "xiwizard_ncpa_service",
                                "check_command" => "check_xi_ncpa!" . $theseopts,
                                "_xiwizard" => $wizard_name);

                            $theseopts = "{$pluginopts} -M 'interface/{$title}/bytes_recv' -d -u M";

                            if (!empty($metrics["warning"])) {
                                $theseopts .= " -w " . $metrics["warning"];
                            }
                            if (!empty($metrics["critical"])) {
                                $theseopts .= " -c " . $metrics["critical"];
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => "{$title} Bandwidth - Inbound",
                                "use" => "xiwizard_ncpa_service",
                                "check_command" => "check_xi_ncpa!" . $theseopts,
                                "_xiwizard" => $wizard_name);
                        }
                        break;

                    case "services":
                        foreach ($args as $i => $service) {
                            if (!array_key_exists('monitor', $service)) {
                                continue;
                            }
                            $theseopts = "{$pluginopts} -M 'service/" . $service["name"] . "/" . $service["state"] . "'";
                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => "Service Status: " . $service["name"],
                                "use" => "xiwizard_ncpa_service",
                                "check_command" => "check_xi_ncpa!" . $theseopts,
                                "_xiwizard" => $wizard_name);
                        }
                        break;

                    case "process":
                        foreach ($args as $i => $metrics) {
                            if (!array_key_exists('monitor', $metrics)) {
                                continue;
                            }
                            $proc_name = $metrics['name'];
                            $display = $metrics['display_name'];
                            $theseopts = "{$pluginopts} -M 'process/{$proc_name}/count'";

                            if (!empty($metrics["count"]["warning"])) {
                                $theseopts .= " -w " . $metrics["count"]["warning"];
                            }
                            if (!empty($metrics["count"]["critical"])) {
                                $theseopts .= " -c " . $metrics["count"]["critical"];
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => "Instances of {$display}",
                                "use" => "xiwizard_ncpa_service",
                                "check_command" => "check_xi_ncpa!" . $theseopts,
                                "_xiwizard" => $wizard_name);
                        }
                        break;

                    default:
                        break;
                }
            }

            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;
            break;

        default:
            break;
    }

    return $output;
}