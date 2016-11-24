<?php
// BirdsEye 3 Component
//
// Ajax Requests file - allows polling for new data, creates json, returns to the component
// so it can display it out every 1-5 mins depending on users settings
//
// Copyright (c) 2013-2015 Nagios Enterprises, LLC.  All rights reserved.
// 

require_once(dirname(__FILE__) . '/../../common.inc.php');
//require_once(dirname(__FILE__).'/includes/xml2json.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication(false);

// =====================================================
// Handle Ajax Requests
// =====================================================

// Route the request sent to this php file
function route_request()
{
    $mode = grab_request_var('mode');

    switch ($mode) {
        case 'get_all_down':
            get_all_down();
            break;
        case 'get_state_history':
            get_state_history();
            break;
        default:
            break;
    }
}

route_request();

// =====================================================
// Functions
// =====================================================

function get_all_down()
{
    $down = array();
    
    $show_handled = grab_request_var('show_handled');
    $show_soft = grab_request_var('show_soft');
    
    // Get down host status
    $backendargs = array();

    if (!$show_handled) {
        $backendargs["limitrecords"] = false; // don't limit records
        $backendargs["current_state"] = "in:1,2"; // down or unreachable
        $backendargs["problem_acknowledged"] = 0; // not acknowledged
        $backendargs["scheduled_downtime_depth"] = 0; // not in downtime
        $backendargs["notifications_enabled"] = 1; // is allowed to alert
        // hard states
        $backendargs["current_check_attempt"] = "gt:0"; // used for Hard State determination
        $backendargs["max_check_attempts"] = "gt:0"; // used for Hard State determination
    } else {
        $backendargs["limitrecords"] = false; // don't limit records
        $backendargs["current_state"] = "in:1,2"; // down or unreachable
        // hard states
        $backendargs["current_check_attempt"] = "gt:0"; // used for Hard State determination
        $backendargs["max_check_attempts"] = "gt:0"; // used for Hard State determination
    }

    $hosts_xml = get_xml_host_status($backendargs);

    foreach ($hosts_xml->hoststatus as $host) {
        $cs = intval($host->current_state);
        $curattempt = intval($host->current_check_attempt);
        $maxattempt = intval($host->max_check_attempts);
        if ($cs > 0) {
            // check for hard soft state
            if (!$show_soft && $curattempt != $maxattempt) { continue; }
            if ($cs == 1) {
                $type = "critical";
            } else if ($cs == 2) {
                $type = "warning";
            } else {
                $type = "online";
            }
            $host_name = strval($host->host_id);
            $down[$host_name] = array("name" => strval($host->name),
                "type" => $type,
                "host_id" => intval($host->host_id),
                "host_name" => $host_name,
                "icon" => strval($host->icon_image),
                "down_services" => array());
        }
    }

    // Get down service status
    $backendargs = array();

    if(!$show_handled){
        $backendargs["combinedhost"] = 1; // combined host status
        $backendargs["host_current_state"] = "0"; // host up
        $backendargs["current_state"] = "in:1,2,3"; // service non-ok state
        $backendargs["host_problem_acknowledged"] = 0; // host not acknowledged
        $backendargs["host_scheduled_downtime_depth"] = 0; // host not in downtime
        $backendargs["problem_acknowledged"] = 0; // service not acknowledged
        $backendargs["scheduled_downtime_depth"] = 0; // service not in downtime
        $backendargs["notifications_enabled"] = 1; // service allowed to alert
        // hard states
        $backendargs["current_check_attempt"] = "gt:0"; // used for Hard State determination
        $backendargs["max_check_attempts"] = "gt:0"; // used for Hard State determination
    } else {
        $backendargs["combinedhost"] = 1; // combined host status
        $backendargs["host_current_state"] = "0"; // host up
        $backendargs["current_state"] = "in:1,2,3"; // service non-ok state
        // hard states
        $backendargs["current_check_attempt"] = "gt:0"; // used for Hard State determination
        $backendargs["max_check_attempts"] = "gt:0"; // used for Hard State determination
    }

    $services_xml = get_xml_service_status($backendargs);
    foreach ($services_xml->servicestatus as $service) {
        // Next line removes service names starting with _ as they are non alertable items
        if (0 === strpos(strval($service->name), '_')) { continue; }
        $cs = intval($service->current_state);
        $curattempt = intval($service->current_check_attempt);
        $maxattempt = intval($service->max_check_attempts);
        if ($cs > 0) {
            // check for hard soft state
            if (!$show_soft && $curattempt != $maxattempt) { continue; }
            $host_name = strval($service->host_name);
            if (!array_key_exists($host_name, $down)) {
                // Host is up but a service is down
                $xml = get_xml_host_status(array("host_name" => $host_name));
                //echo "<pre>";
                //print_r($xml);
                //echo "</pre>";
                $down[$host_name] = array("name" => strval($xml->hoststatus->name),
                    "type" => "online",
                    "host_id" => intval($xml->hoststatus->host_id),
                    "host_name" => $host_name,
                    "icon" => strval($xml->host->icon_image),
                    "down_services" => array());
            }

            // Add down service to array
            if ($down[$host_name]['type'] != "critical") {
                $down[$host_name]['down_services'][] = array("service_name" => strval($service->name));
            }
        }
    }

    // Loop through and create a new array (making the critical hosts be on top)
    $to_json = array();
    foreach ($down as $host) {
        $to_json[] = $host;
    }

    //print_r($down);

    print json_encode($to_json);
}

function get_state_history()
{
    $state_history = array();

    $args = array();
    // Get the state history
    $test = get_xml_statehistory($args);
    $test = $test->stateentry;

    if (!empty($test)) {
        for ($i = 0; $i < 12; $i++) {
            if (!empty($test[$i])) {
                if ($test[$i]->state == "0") {
                    $state_history[$i]['state_image'] = 'tick';
                } else if ($test[$i]->state == "1" || $test[$i]->state == "2") {
                    $state_history[$i]['state_image'] = 'cross';
                } else {
                    $state_history[$i]['state_image'] = 'bullet_error';
                }

                $state_history[$i]['host_name'] = strval($test[$i]->host_name);
                $state_history[$i]['state_time'] = strval($test[$i]->state_time);
                $state_history[$i]['output'] = strval($test[$i]->output);

                if (!empty($test[$i]->service_description)) {
                    $state_history[$i]['service_description'] = strval($test[$i]->service_description);
                }
            }
        }
    } else {
        $state_history = array("msg" => "No State History");
    }

    print json_encode($state_history);
}

?>
