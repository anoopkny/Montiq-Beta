<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//
// $Id$

//require_once(dirname(__FILE__).'/common.inc.php');


////////////////////////////////////////////////////////////////////////////////
// PROGRAM STATUS 
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_program_status_xml_output($request_args, $json=false)
{

    global $sqlquery;
    global $db_tables;

    $output = "";

    // generate query
    $fieldmap = array(
        "instance_id" => $db_tables[DB_NDOUTILS]["instances"] . ".instance_id",
        "instance_name" => $db_tables[DB_NDOUTILS]["instances"] . ".instance_name",
        "status_update_time" => $db_tables[DB_NDOUTILS]["programstatus"] . ".status_update_time",
        "program_start_time" => $db_tables[DB_NDOUTILS]["programstatus"] . ".program_start_time",
        "program_run_time" => "program_run_time",
        "program_end_time" => $db_tables[DB_NDOUTILS]["programstatus"] . ".program_end_time",
        "is_currently_running" => $db_tables[DB_NDOUTILS]["programstatus"] . ".is_currently_running",
        "process_id" => $db_tables[DB_NDOUTILS]["programstatus"] . ".process_id",
        "daemon_mode" => $db_tables[DB_NDOUTILS]["programstatus"] . ".daemon_mode",
        "last_command_check" => $db_tables[DB_NDOUTILS]["programstatus"] . ".last_command_check",
        "last_log_rotation" => $db_tables[DB_NDOUTILS]["programstatus"] . ".last_log_rotation",
        "notifications_enabled" => $db_tables[DB_NDOUTILS]["programstatus"] . ".notifications_enabled",
        "active_service_checks_enabled" => $db_tables[DB_NDOUTILS]["programstatus"] . ".active_service_checks_enabled",
        "passive_service_checks_enabled" => $db_tables[DB_NDOUTILS]["programstatus"] . ".passive_service_checks_enabled",
        "event_handlers_enabled" => $db_tables[DB_NDOUTILS]["programstatus"] . ".event_handlers_enabled",
        "flap_detection_enabled" => $db_tables[DB_NDOUTILS]["programstatus"] . ".flap_detection_enabled",
        "process_performance_data" => $db_tables[DB_NDOUTILS]["programstatus"] . ".process_performance_data",
        "obsess_over_hosts" => $db_tables[DB_NDOUTILS]["programstatus"] . ".obsess_over_hosts",
        "obsess_over_services" => $db_tables[DB_NDOUTILS]["programstatus"] . ".obsess_over_services"
    );
    $instanceauthfields = array(
        "instance_id"
    );
    $args = array(
        "sql" => $sqlquery['GetProgramStatus'],
        "fieldmap" => $fieldmap,
        "instanceauthfields" => $instanceauthfields,
        "instanceauthperms" => P_LIST,
        "useropts" => $request_args, // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql, false))) {
        //handle_backend_db_error(DB_NDOUTILS);
    } else {

        if (!$json) {

            $output .= "<programstatuslist>\n";
            $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";

            if (!isset($request_args["totals"])) {
                while (!$rs->EOF) {

                    $output .= "  <programstatus>\n";
                    $output .= get_xml_db_field(2, $rs, 'instance_id', 'instance_id');
                    $output .= get_xml_db_field(2, $rs, 'instance_name');
                    $output .= get_xml_db_field(2, $rs, 'status_update_time');
                    $output .= get_xml_db_field(2, $rs, 'program_start_time');
                    $output .= get_xml_db_field(2, $rs, 'program_run_time');
                    $output .= get_xml_db_field(2, $rs, 'program_end_time');
                    $output .= get_xml_db_field(2, $rs, 'is_currently_running');
                    $output .= get_xml_db_field(2, $rs, 'process_id');
                    $output .= get_xml_db_field(2, $rs, 'daemon_mode');
                    $output .= get_xml_db_field(2, $rs, 'last_command_check');
                    $output .= get_xml_db_field(2, $rs, 'last_log_rotation');
                    $output .= get_xml_db_field(2, $rs, 'notifications_enabled');
                    $output .= get_xml_db_field(2, $rs, 'active_service_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'passive_service_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'active_host_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'passive_host_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'event_handlers_enabled');
                    $output .= get_xml_db_field(2, $rs, 'flap_detection_enabled');
                    $output .= get_xml_db_field(2, $rs, 'process_performance_data');
                    $output .= get_xml_db_field(2, $rs, 'obsess_over_hosts');
                    $output .= get_xml_db_field(2, $rs, 'obsess_over_services');
                    $output .= get_xml_db_field(2, $rs, 'modified_host_attributes');
                    $output .= get_xml_db_field(2, $rs, 'modified_service_attributes');
                    $output .= get_xml_db_field(2, $rs, 'global_host_event_handler');
                    $output .= get_xml_db_field(2, $rs, 'global_service_event_handler');
                    $output .= "  </programstatus>\n";

                    $rs->MoveNext();
                }
            }
            $output .= "</programstatuslist>\n";

            return $output;

        } else {

            $arr = $rs->GetArray();

            $json = array(
                'instance_id' => $arr[0]['instance_id'],
                'instancne_name' => $arr[0]['instance_name'],
                'status_update_time' => $arr[0]['status_update_time'],
                'program_start_time' => $arr[0]['program_start_time'],
                'program_run_time' => $arr[0]['program_run_time'],
                'program_end_time' => $arr[0]['program_end_time'],
                'is_currently_running' => $arr[0]['is_currently_running'],
                'process_id' => $arr[0]['process_id'],
                'daemon_mode' => $arr[0]['daemon_mode'],
                'last_command_check' => $arr[0]['last_command_check'],
                'last_log_rotation' => $arr[0]['last_log_rotation'],
                'notifications_enabled' => $arr[0]['notifications_enabled'],
                'active_service_checks_enabled' => $arr[0]['active_service_checks_enabled'],
                'passive_service_checks_enabled' => $arr[0]['passive_service_checks_enabled'],
                'active_host_checks_enabled' => $arr[0]['active_host_checks_enabled'],
                'passive_host_checks_enabled' => $arr[0]['passive_host_checks_enabled'],
                'event_handlers_enabled' => $arr[0]['event_handlers_enabled'],
                'flap_detection_enabled' => $arr[0]['flap_detection_enabled'],
                'process_performance_data' => $arr[0]['process_performance_data'],
                'obsess_over_hosts' => $arr[0]['obsess_over_hosts'],
                'obsess_over_services' => $arr[0]['obsess_over_services'],
                'modified_host_attributes' => $arr[0]['modified_host_attributes'],
                'modified_service_attributes' => $arr[0]['modified_service_attributes'],
                'global_host_event_handler' => $arr[0]['global_host_event_handler'],
                'global_service_event_handler' => $arr[0]['global_service_event_handler']
            );

            return $json;

        }
    }

    return $output;
}

///////////////////////////////////////////////////////////////////////////////
//	TACTICAL OVERVIEW
///////////////////////////////////////////////////////////////////////////////	

/**
 *    Runs multiple backend queries to fetch tactical overview totals into one result set
 *    This function runs many queries becuase large installs perform much better with SQL doing the counting than PHP
 *
 * @param bool  $asxml        : should we return data as an XML string or as an array?
 * @param mixed $request_args : input array, currently unused
 * @param bool  $timer        : print run time data for debugging
 *
 * @return string $output: an XML formatted *string
 */
function get_host_status_tac_summary_data($asxml = true, $request_args = array(), $timer = false)
{

    if ($timer)
        $start = timer_start();

    //storage array
    $info = array(
        "up" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
        ),
        "down" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
        ),
        "unreachable" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
        ),
        "pending" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
        ),
    );

    // HOST DOWN
    get_host_tac_data_by_state($info, 1, 'down');

    // UNREACHABLE HOSTS
    get_host_tac_data_by_state($info, 2, 'unreachable');

    // UP HOSTS
    get_host_tac_data_by_state($info, 0, 'up');

    // PENDING HOSTS
    get_host_tac_data_by_state($info, 0, 'pending');


    if (!$asxml)
        return $info;

    //build the xml string
    $output = "<hoststatussummary>\n";

    foreach ($info as $key => $arr) {
        $output .= "<{$key}>\n";
        foreach ($arr as $subkey => $val) {
            $output .= "<{$subkey}>{$val}</subkey>\n";
        }
        //close the subsection
        $output .= "<{$key}/>\n";
    }

    if ($timer) {
        $time_total = timer_stop($start);
        $output .= "<time>{$time_total}</time>\n";
        //echo $time_total;
    }
    //close the xml
    $output .= "</hoststatussummary>\n";

    //echo $output;
    return $output;


}

/**
 *    updates tac data storage with totals from backend
 *
 * @param mixed  $info       : REFERENCE variable to storage array
 * @param int    $statecode  : code corresponding to current_state
 * @param string $stateindex : the named array index to store the data in
 *
 * @return null
 */
function get_host_tac_data_by_state(&$info, $statecode, $stateindex)
{

    //start with generic host count query
    $args = array(
        'cmd' => 'gethoststatus',
        'totals' => 1,
        'is_active' => 1,
    );

    $args["current_state"] = $statecode;
    if ($stateindex == 'pending')
        $args["has_been_checked"] = 0;
    else
        $args["has_been_checked"] = 1;

    //total
    $xml = get_xml_host_status($args);
    if ($xml)
        $info[$stateindex]['total'] = intval($xml->recordcount);

    //scheduled dt
    $args["scheduled_downtime_depth"] = "gt:0";
    $xml = get_xml_host_status($args);
    if ($xml)
        $info[$stateindex]['scheduled'] = intval($xml->recordcount);

    unset($args["scheduled_downtime_depth"]);

    //active
    $args["active_checks_enabled"] = 1;
    $xml = get_xml_host_status($args);
    if ($xml)
        $info[$stateindex]['active'] = intval($xml->recordcount);
    unset($args["active_checks_enabled"]);

    //passive
    $args["active_checks_enabled"] = 0;
    $args["passive_checks_enabled"] = 1;
    $xml = get_xml_host_status($args);
    if ($xml)
        $info[$stateindex]['disabled'] = intval($xml->recordcount);
    unset($args["active_checks_enabled"]);
    unset($args["passive_checks_enabled"]);


    //soft
    $args["state_type"] = 0;
    $xml = get_xml_host_status($args);
    if ($xml)
        $info[$stateindex]['soft'] = intval($xml->recordcount);
    unset($args["state_type"]);

    if ($statecode != 0) {
        //acked
        $args["problem_acknowledged"] = 1;
        $xml = get_xml_host_status($args);
        if ($xml)
            $info[$stateindex]['acknowledged'] = intval($xml->recordcount);
        unset($args["problem_acknowledged"]);

        //unhandled
        $args["problem_acknowledged"] = 0;
        $args["scheduled_downtime_depth"] = 0;
        $xml = get_xml_host_status($args);
        if ($xml)
            $info[$stateindex]['unhandled'] = intval($xml->recordcount);
        unset($args["scheduled_downtime_depth"]);
        unset($args["problem_acknowledged"]);
    }

}


/**
 *    Runs multiple backend queries to fetch tactical overview totals into one result set
 *    This function runs many queries becuase large installs perform much better with SQL doing the counting than PHP
 *
 * @param bool  $asxml        : should we return data as an XML string or as an array?
 * @param mixed $request_args : input array, currently unused
 * @param bool  $timer        : print run time data for debugging
 *
 * @return string $output: an XML formatted *string
 */
function get_service_status_tac_summary_data($asxml = true, $request_args = array(), $timer = false)
{

    if ($timer)
        $start = timer_start();

    //storage array
    $info = array(
        "ok" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
            "hostproblem" => 0,
        ),
        "warning" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
            "hostproblem" => 0,
        ),
        "critical" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
            "hostproblem" => 0,
        ),
        "unknown" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
            "hostproblem" => 0,
        ),
        "pending" => array(
            "total" => 0,
            "soft" => 0,
            "acknowledged" => 0,
            "active" => 0,
            "disabled" => 0,
            "scheduled" => 0,
            "unhandled" => 0,
            "hostproblem" => 0,
        ),
    );

    // SERVICE OK
    get_service_tac_data_by_state($info, 0, 'ok');

    // SERVICE WARNING
    get_service_tac_data_by_state($info, 1, 'warning');

    // SERVICE CRITICAL
    get_service_tac_data_by_state($info, 2, 'critical');

    // SERVICE UNKNOWN
    get_service_tac_data_by_state($info, 3, 'unknown');

    // SERVICE PENDING
    get_service_tac_data_by_state($info, 0, 'pending');

    //array_dump($info);

    if (!$asxml)
        return $info;

    //build the xml string
    $output = "<servicestatussummary>\n";

    foreach ($info as $key => $arr) {
        $output .= "<{$key}>\n";
        foreach ($arr as $subkey => $val) {
            $output .= "<{$subkey}>{$val}</subkey>\n";
        }
        //close the subsection
        $output .= "<{$key}/>\n";
    }

    if ($timer) {
        $time_total = timer_stop($start);
        $output .= "<time>{$time_total}</time>\n";
        //echo $time_total;
    }
    //close the xml
    $output .= "</servicestatussummary>\n";

    //echo $output;
    return $output;


}

/**
 *    updates tac data storage with totals from backend
 *
 * @param mixed  $info       : REFERENCE variable to storage array
 * @param int    $statecode  : code corresponding to current_state
 * @param string $stateindex : the named array index to store the data in
 *
 * @return null
 */
function get_service_tac_data_by_state(&$info, $statecode, $stateindex)
{

    //start with generic host count query
    $args = array(
        'cmd' => 'getservicestatus',
        'totals' => 1,
        'is_active' => 1,
        'combinedhost' => 1,
    );

    $args["current_state"] = $statecode;
    if ($stateindex == 'pending')
        $args["has_been_checked"] = 0;
    else
        $args["has_been_checked"] = 1;

    //total
    $xml = get_xml_service_status($args);
    if ($xml)
        $info[$stateindex]['total'] = intval($xml->recordcount);

    //scheduled dt
    $args["scheduled_downtime_depth"] = "gt:0";
    $xml = get_xml_service_status($args);
    if ($xml)
        $info[$stateindex]['scheduled'] = intval($xml->recordcount);

    unset($args["scheduled_downtime_depth"]);

    //active
    $args["active_checks_enabled"] = 1;
    $xml = get_xml_service_status($args);
    if ($xml)
        $info[$stateindex]['active'] = intval($xml->recordcount);
    unset($args["active_checks_enabled"]);

    //passive
    $args["active_checks_enabled"] = 0;
    $args["passive_checks_enabled"] = 1;
    $xml = get_xml_service_status($args);
    if ($xml)
        $info[$stateindex]['disabled'] = intval($xml->recordcount);
    unset($args["active_checks_enabled"]);
    unset($args["passive_checks_enabled"]);


    //soft
    $args["state_type"] = 0;
    $xml = get_xml_service_status($args);
    if ($xml)
        $info[$stateindex]['soft'] = intval($xml->recordcount);
    unset($args["state_type"]);

    //host problems
    $args["host_current_state"] = "gt:0";
    $xml = get_xml_service_status($args);
    if ($xml)
        $info[$stateindex]['hostproblem'] = intval($xml->recordcount);
    unset($args["host_current_state"]);

    if ($statecode != 0) {
        //acked
        $args["problem_acknowledged"] = 1;
        $xml = get_xml_service_status($args);
        if ($xml)
            $info[$stateindex]['acknowledged'] = intval($xml->recordcount);
        unset($args["problem_acknowledged"]);

        //unhandled
        $args["problem_acknowledged"] = 0;
        $args["scheduled_downtime_depth"] = 0;
        $args["host_current_state"] = 0;

        $xml = get_xml_service_status($args);
        if ($xml)
            $info[$stateindex]['unhandled'] = intval($xml->recordcount);
        unset($args["scheduled_downtime_depth"]);
        unset($args["problem_acknowledged"]);
        unset($args["host_current_state"]);
    }

}


////////////////////////////////////////////////////////////////////////////////
// SERVICE STATUS 
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_service_status_xml_output($request_args)
{
    global $sqlquery;
    global $db_tables;

    //$start = timer_start();
    //echo "Start: $start<br />";

    $output = "";

    // how brief should we be?
    $brevity = 0;
    if (isset($request_args['brevity']))
        $brevity = $request_args['brevity'];

    $totals = grab_array_var($request_args, 'totals', false);

    // generate query
    $fieldmap = array(
        "host_name" => "obj1.name1",
        "service_description" => "obj1.name2",
        "name" => "obj1.name2",
        "display_name" => $db_tables[DB_NDOUTILS]["services"] . ".display_name",
        "host_display_name" => $db_tables[DB_NDOUTILS]["hosts"] . ".display_name",
        "host_alias" => $db_tables[DB_NDOUTILS]["hosts"] . ".alias",
        "host_address" => $db_tables[DB_NDOUTILS]["hosts"] . ".address",
        "instance_id" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".instance_id",
        //"instance_id" => $db_tables[DB_NDOUTILS]["services"].".instance_id",
        "servicestatus_id" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".servicestatus_id",
        "service_id" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".service_object_id",
        "host_id" => $db_tables[DB_NDOUTILS]["hosts"] . ".host_object_id",
        "status_update_time" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".status_update_time",
        "current_state" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".current_state",
        "status_text" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".output",
        "current_check_attempt" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".current_check_attempt",
        "check_command" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".check_command",

        //brevity < 1
        "modified_attributes" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".modified_service_attributes", //
        "should_be_scheduled" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".should_be_scheduled", //
        "event_handler_enabled" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".event_handler_enabled", //
        "flap_detection_enabled" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".flap_detection_enabled", //
        "acknowledgement_type" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".acknowledgement_type", //
        "current_notification_number" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".current_notification_number", //

        //brevity < 2
        "last_state_change" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".last_state_change", //
        "max_check_attempts" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".max_check_attempts", //1
        "state_type" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".state_type", //1
        "is_flapping" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".is_flapping", //1
        "percent_state_change" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".percent_state_change", //1
        "latency" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".latency", //1
        "execution_time" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".execution_time", //1
        "scheduled_downtime_depth" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".scheduled_downtime_depth", //1
        "problem_acknowledged" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".problem_has_been_acknowledged", //1
        "active_checks_enabled" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".active_checks_enabled", //1
        "passive_checks_enabled" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".passive_checks_enabled", //1
        "notifications_enabled" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".notifications_enabled", //1
        "has_been_checked" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".has_been_checked", //1
        "last_check" => $db_tables[DB_NDOUTILS]["servicestatus"] . ".last_check", //1

        // host attributes (only valid in combined view)
        "host_status_update_time" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".status_update_time",
        "host_current_state" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".current_state",
        "host_last_check" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".last_check", //1
        "host_last_state_change" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".last_state_change",
        "host_current_check_attempt" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".current_check_attempt", //1
        "host_status_text" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".output",
        "host_has_been_checked" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".has_been_checked", //1
        "host_should_be_scheduled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".should_be_scheduled",
        "host_active_checks_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".active_checks_enabled", //1
        "host_passive_checks_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".passive_checks_enabled", //1
        "host_notifications_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".notifications_enabled", //1
        "host_event_handler_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".event_handler_enabled",
        "host_flap_detection_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".flap_detection_enabled",
        "host_is_flapping" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".is_flapping",
        "host_percent_state_change" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".percent_state_change",
        "host_latency" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".latency",
        "host_execution_time" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".execution_time",
        "host_scheduled_downtime_depth" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".scheduled_downtime_depth",
        "host_problem_acknowledged" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".problem_has_been_acknowledged", //1
        "host_acknowledgement_type" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".acknowledgement_type",
        "host_current_notification_number" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".current_notification_number",
        "host_max_check_attempts" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".max_check_attempts", //1
        "host_state_type" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".state_type", //1
    );

    $objectauthfields = array(
        "service_id"
    );
    $instanceauthfields = array(
        "instance_id"
    );

    // combined host status?
    $combined = false;
    if (isset($request_args['combinedhost']))
        $combined = true;

    // what query should we run?
    if ($combined)
        $q = ($totals == 1) ? $sqlquery['GetServiceStatusWithHostStatusCount'] : $sqlquery['GetServiceStatusWithHostStatus'];
    else
        $q = ($totals == 1) ? $sqlquery['GetServiceStatusCount'] : $sqlquery['GetServiceStatus'];

    $args = array(
        "sql" => $q,
        "fieldmap" => $fieldmap,
        "objectauthfields" => $objectauthfields,
        "objectauthperms" => P_READ,
        "instanceauthfields" => $instanceauthfields,
        "useropts" => $request_args, // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    //echo "SQL: $sql <br />";
    //array_dump($request_args);

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql, true))) {
        //echo mysql_error();
    } else {
        
        //echo '<pre>';
        //var_dump($sql);
        //die();

        //output_backend_header();
        $output .= "<servicestatuslist>\n";
        //$output.="  <sql>".$sql."</sql>\n";
        if ($totals == 1) {
            $count = 0;
            foreach ($rs as $row)
                $count = empty($row['total']) ? 0 : $row['total'];

            $output .= "  <recordcount>" . $count . "</recordcount>\n";
        } else
            $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";


        if (!isset($request_args["totals"])) {
            while (!$rs->EOF) {

                $output .= "  <servicestatus id='" . get_xml_db_field_val($rs, 'servicestatus_id') . "'>\n";
                $output .= get_xml_db_field(2, $rs, 'instance_id');
                $output .= get_xml_db_field(2, $rs, 'service_object_id', 'service_id');
                $output .= get_xml_db_field(2, $rs, 'host_object_id', 'host_id');
                $output .= get_xml_db_field(2, $rs, 'host_name');
                $output .= get_xml_db_field(2, $rs, 'host_alias');
                $output .= get_xml_db_field(2, $rs, 'service_description', 'name');
                $output .= get_xml_db_field(2, $rs, 'host_display_name');
                $output .= get_xml_db_field(2, $rs, 'host_address');
                $output .= get_xml_db_field(2, $rs, 'display_name');
                $output .= get_xml_db_field(2, $rs, 'status_update_time');
                $output .= get_xml_db_field(2, $rs, 'output', 'status_text');
                $output .= get_xml_db_field(2, $rs, 'long_output', 'status_text_long');
                $output .= get_xml_db_field(2, $rs, 'current_state');

                if ($brevity < 1) {
                    $output .= get_xml_db_field(2, $rs, 'perfdata', 'performance_data');
                    $output .= get_xml_db_field(2, $rs, 'should_be_scheduled');
                    $output .= get_xml_db_field(2, $rs, 'check_type');
                    $output .= get_xml_db_field(2, $rs, 'last_state_change');
                    $output .= get_xml_db_field(2, $rs, 'last_hard_state_change');
                    $output .= get_xml_db_field(2, $rs, 'last_hard_state');
                    $output .= get_xml_db_field(2, $rs, 'last_time_ok');
                    $output .= get_xml_db_field(2, $rs, 'last_time_warning');
                    $output .= get_xml_db_field(2, $rs, 'last_time_critical');
                    $output .= get_xml_db_field(2, $rs, 'last_time_unknown');
                    $output .= get_xml_db_field(2, $rs, 'last_notification');
                    $output .= get_xml_db_field(2, $rs, 'next_notification');
                    $output .= get_xml_db_field(2, $rs, 'no_more_notifications');
                    $output .= get_xml_db_field(2, $rs, 'acknowledgement_type');
                    $output .= get_xml_db_field(2, $rs, 'current_notification_number');
                    $output .= get_xml_db_field(2, $rs, 'process_performance_data');
                    $output .= get_xml_db_field(2, $rs, 'obsess_over_service');
                    $output .= get_xml_db_field(2, $rs, 'event_handler_enabled');
                    $output .= get_xml_db_field(2, $rs, 'modified_service_attributes');
                    $output .= get_xml_db_field(2, $rs, 'event_handler');
                    $output .= get_xml_db_field(2, $rs, 'check_command');
                    $output .= get_xml_db_field(2, $rs, 'normal_check_interval');
                    $output .= get_xml_db_field(2, $rs, 'retry_check_interval');
                    $output .= get_xml_db_field(2, $rs, 'check_timeperiod_object_id', 'check_timeperiod_id');
                    $output .= get_xml_db_field(2, $rs, 'icon_image');
                    $output .= get_xml_db_field(2, $rs, 'icon_image_alt');
                }

                if ($brevity < 2) {
                    $output .= get_xml_db_field(2, $rs, 'has_been_checked');
                    $output .= get_xml_db_field(2, $rs, 'current_check_attempt');
                    $output .= get_xml_db_field(2, $rs, 'max_check_attempts');
                    $output .= get_xml_db_field(2, $rs, 'last_check');
                    $output .= get_xml_db_field(2, $rs, 'next_check');
                    $output .= get_xml_db_field(2, $rs, 'state_type');
                    $output .= get_xml_db_field(2, $rs, 'notifications_enabled');
                    $output .= get_xml_db_field(2, $rs, 'problem_has_been_acknowledged', 'problem_acknowledged');
                    $output .= get_xml_db_field(2, $rs, 'flap_detection_enabled');
                    $output .= get_xml_db_field(2, $rs, 'is_flapping');
                    $output .= get_xml_db_field(2, $rs, 'percent_state_change');
                    $output .= get_xml_db_field(2, $rs, 'latency');
                    $output .= get_xml_db_field(2, $rs, 'execution_time');
                    $output .= get_xml_db_field(2, $rs, 'scheduled_downtime_depth');
                    $output .= get_xml_db_field(2, $rs, 'passive_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'active_checks_enabled');
                }

                if ($combined) {
                    $output .= get_xml_db_field(2, $rs, 'host_status_update_time');
                    $output .= get_xml_db_field(2, $rs, 'host_output', 'host_status_text');
                    $output .= get_xml_db_field(2, $rs, 'host_current_state');

                    if ($brevity < 1) {
                        $output .= get_xml_db_field(2, $rs, 'host_icon_image');
                        $output .= get_xml_db_field(2, $rs, 'host_icon_image_alt');
                        $output .= get_xml_db_field(2, $rs, 'host_perfdata', 'host_performance_data');
                        $output .= get_xml_db_field(2, $rs, 'host_should_be_scheduled');
                        $output .= get_xml_db_field(2, $rs, 'host_check_type');
                        $output .= get_xml_db_field(2, $rs, 'host_last_state_change');
                        $output .= get_xml_db_field(2, $rs, 'host_last_hard_state_change');
                        $output .= get_xml_db_field(2, $rs, 'host_last_hard_state');
                        $output .= get_xml_db_field(2, $rs, 'host_last_time_up');
                        $output .= get_xml_db_field(2, $rs, 'host_last_time_down');
                        $output .= get_xml_db_field(2, $rs, 'host_last_time_unreachable');
                        $output .= get_xml_db_field(2, $rs, 'host_last_notification');
                        $output .= get_xml_db_field(2, $rs, 'host_next_notification');
                        $output .= get_xml_db_field(2, $rs, 'host_no_more_notifications');
                        $output .= get_xml_db_field(2, $rs, 'host_acknowledgement_type');
                        $output .= get_xml_db_field(2, $rs, 'host_current_notification_number');
                        $output .= get_xml_db_field(2, $rs, 'host_event_handler_enabled');
                        $output .= get_xml_db_field(2, $rs, 'host_process_performance_data');
                        $output .= get_xml_db_field(2, $rs, 'obsess_over_host');
                        $output .= get_xml_db_field(2, $rs, 'modified_host_attributes');
                        $output .= get_xml_db_field(2, $rs, 'host_event_handler');
                        $output .= get_xml_db_field(2, $rs, 'host_check_command');
                        $output .= get_xml_db_field(2, $rs, 'host_normal_check_interval');
                        $output .= get_xml_db_field(2, $rs, 'host_retry_check_interval');
                        $output .= get_xml_db_field(2, $rs, 'host_check_timeperiod_object_id', 'check_timeperiod_id');
                    }

                    if ($brevity < 2) {
                        $output .= get_xml_db_field(2, $rs, 'host_current_check_attempt');
                        $output .= get_xml_db_field(2, $rs, 'host_max_check_attempts');
                        $output .= get_xml_db_field(2, $rs, 'host_last_check');
                        $output .= get_xml_db_field(2, $rs, 'host_next_check');
                        $output .= get_xml_db_field(2, $rs, 'host_has_been_checked');
                        $output .= get_xml_db_field(2, $rs, 'host_state_type');
                        $output .= get_xml_db_field(2, $rs, 'host_notifications_enabled');
                        $output .= get_xml_db_field(2, $rs, 'host_problem_has_been_acknowledged', 'host_problem_acknowledged');
                        $output .= get_xml_db_field(2, $rs, 'host_passive_checks_enabled');
                        $output .= get_xml_db_field(2, $rs, 'host_active_checks_enabled');
                        $output .= get_xml_db_field(2, $rs, 'host_flap_detection_enabled');
                        $output .= get_xml_db_field(2, $rs, 'host_is_flapping');
                        $output .= get_xml_db_field(2, $rs, 'host_percent_state_change');
                        $output .= get_xml_db_field(2, $rs, 'host_latency');
                        $output .= get_xml_db_field(2, $rs, 'host_execution_time');
                        $output .= get_xml_db_field(2, $rs, 'host_scheduled_downtime_depth');
                    }

                }

                $output .= "  </servicestatus>\n";

                $rs->MoveNext();
            }
        }
        $output .= "</servicestatuslist>\n";
    }

    //BENCHMARKING
    //$total = timer_stop($start);
    //echo "Fetch time: {$total}<br />";

    return $output;
}


////////////////////////////////////////////////////////////////////////////////
// CUSTOM SERVICE VARIABLE STATUS 
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_custom_service_variable_status_xml_output($request_args)
{
    global $sqlquery;
    global $db_tables;

    $output = "";

    // generate query
    $fieldmap = array(
        "host_name" => "obj1.name1",
        "service_description" => "obj1.name2",
        "display_name" => $db_tables[DB_NDOUTILS]["services"] . ".display_name",
        "instance_id" => $db_tables[DB_NDOUTILS]["customvariablestatus"] . ".instance_id",
        "service_id" => "obj1.object_id",
        "var_name" => $db_tables[DB_NDOUTILS]["customvariablestatus"] . ".varname",
        "var_value" => $db_tables[DB_NDOUTILS]["customvariablestatus"] . ".varvalue"
    );
    $objectauthfields = array(
        "service_id"
    );
    $instanceauthfields = array(
        "instance_id"
    );
    $default_order = "host_name ASC, service_description ASC";
    $args = array(
        "sql" => $sqlquery['GetCustomServiceVariableStatus'],
        "fieldmap" => $fieldmap,
        "objectauthfields" => $objectauthfields,
        "objectauthperms" => P_READ,
        "instanceauthfields" => $instanceauthfields,
        "default_order" => $default_order,
        "useropts" => $request_args, // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql, false))) {
        //handle_backend_db_error(DB_NDOUTILS);
    } else {
        //output_backend_header();
        $output .= "<customservicevarstatuslist>\n";
        $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";

        if (!isset($request["totals"])) {
            $last_id = -1;
            while (!$rs->EOF) {

                $this_id = $rs->fields['object_id'];
                if ($last_id != $this_id) {
                    if ($last_id != -1) {
                        $output .= "    </customvars>\n";
                        $output .= "  </customservicevarstatus>\n";
                    }
                    $last_id = $this_id;
                    $output .= "  <customservicevarstatus>\n";
                    $output .= get_xml_db_field(2, $rs, 'instance_id');
                    $output .= get_xml_db_field(2, $rs, 'object_id', 'contact_id');
                    $output .= get_xml_db_field(2, $rs, 'host_name');
                    $output .= get_xml_db_field(2, $rs, 'service_description');
                    $output .= get_xml_db_field(2, $rs, 'display_name');
                    $output .= "    <customvars>\n";
                }

                $output .= "      <customvar>\n";
                $output .= get_xml_db_field(4, $rs, 'varname', 'name');
                $output .= get_xml_db_field(4, $rs, 'varvalue', 'value');
                $output .= get_xml_db_field(4, $rs, 'has_been_modified', 'modified');
                $output .= get_xml_db_field(4, $rs, 'status_update_time', 'last_update');
                $output .= "      </customvar>\n";

                $rs->MoveNext();
            }
            if ($last_id != -1) {
                $output .= "    </customvars>\n";
                $output .= "  </customservicevarstatus>\n";
            }
        }
        $output .= "</customservicevarstatuslist>\n";
    }

    return $output;
}


////////////////////////////////////////////////////////////////////////////////
// HOST STATUS 
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_host_status_xml_output($request_args)
{
    global $sqlquery;
    global $db_tables;

    $output = "";

    $totals = grab_array_var($request_args, 'totals', false);

    // generate query
    $fieldmap = array(
        "name" => "obj1.name1",
        "host_name" => "obj1.name1",
        "display_name" => $db_tables[DB_NDOUTILS]["hosts"] . ".display_name",
        "address" => $db_tables[DB_NDOUTILS]["hosts"] . ".address",
        "alias" => $db_tables[DB_NDOUTILS]["hosts"] . ".alias",
        "instance_id" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".instance_id",
        "hoststatus_id" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".hoststatus_id",
        "host_id" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".host_object_id",
        "status_update_time" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".status_update_time",
        "current_state" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".current_state",
        "state_type" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".state_type",
        "last_check" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".last_check",
        "last_state_change" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".last_state_change",
        "modified_attributes" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".modified_host_attributes",
        "has_been_checked" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".has_been_checked",
        "active_checks_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".active_checks_enabled",
        "passive_checks_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".passive_checks_enabled",
        "notifications_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".notifications_enabled",
        "event_handler_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".event_handler_enabled",
        "is_flapping" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".is_flapping",
        "flap_detection_enabled" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".flap_detection_enabled",
        "scheduled_downtime_depth" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".scheduled_downtime_depth",
        "problem_acknowledged" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".problem_has_been_acknowledged",
        "check_command" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".check_command",
        "current_check_attempt" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".current_check_attempt",
        "max_check_attempts" => $db_tables[DB_NDOUTILS]["hoststatus"] . ".max_check_attempts",
    );
    $objectauthfields = array(
        "host_id"
    );
    $instanceauthfields = array(
        "instance_id"
    );

    //Full data or just totals?
    $q = ($totals == 1) ? $sqlquery['GetHostStatusCount'] : $sqlquery['GetHostStatus'];

    $args = array(
        "sql" => $q,
        "fieldmap" => $fieldmap,
        "objectauthfields" => $objectauthfields,
        "objectauthperms" => P_READ,
        "instanceauthfields" => $instanceauthfields,
        "useropts" => $request_args, // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    if (!isset($request_args['brevity']))
        $brevity = 0;
    else
        $brevity = $request_args['brevity'];

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql, false))) {
        //handle_backend_db_error(DB_NDOUTILS);
    } else {
        //output_backend_header();
        $output .= "<hoststatuslist>\n";

        if ($totals == 1) { //return SQL count()
            foreach ($rs as $row)
                $count = empty($row['total']) ? 0 : $row['total'];

            $output .= "  <recordcount>" . $count . "</recordcount>\n";
        } else //count full data set
            $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";


        if (!isset($request_args["totals"])) {
            while (!$rs->EOF) {

                $output .= "  <hoststatus id='" . get_xml_db_field_val($rs, 'hoststatus_id') . "'>\n";
                $output .= get_xml_db_field(2, $rs, 'instance_id');
                $output .= get_xml_db_field(2, $rs, 'host_object_id', 'host_id');
                $output .= get_xml_db_field(2, $rs, 'host_name', 'name');
                $output .= get_xml_db_field(2, $rs, 'display_name');
                $output .= get_xml_db_field(2, $rs, 'address', 'address');
                $output .= get_xml_db_field(2, $rs, 'host_alias', 'alias');
                $output .= get_xml_db_field(2, $rs, 'status_update_time');
                $output .= get_xml_db_field(2, $rs, 'output', 'status_text');
                $output .= get_xml_db_field(2, $rs, 'long_output', 'status_text_long');
                $output .= get_xml_db_field(2, $rs, 'current_state');

                if ($brevity < 1) {
                    $output .= get_xml_db_field(2, $rs, 'icon_image');
                    $output .= get_xml_db_field(2, $rs, 'icon_image_alt');
                    $output .= get_xml_db_field(2, $rs, 'perfdata', 'performance_data');
                    $output .= get_xml_db_field(2, $rs, 'should_be_scheduled');
                    $output .= get_xml_db_field(2, $rs, 'check_type');
                    $output .= get_xml_db_field(2, $rs, 'last_state_change');
                    $output .= get_xml_db_field(2, $rs, 'last_hard_state_change');
                    $output .= get_xml_db_field(2, $rs, 'last_hard_state');
                    $output .= get_xml_db_field(2, $rs, 'last_time_up');
                    $output .= get_xml_db_field(2, $rs, 'last_time_down');
                    $output .= get_xml_db_field(2, $rs, 'last_time_unreachable');
                    $output .= get_xml_db_field(2, $rs, 'last_notification');
                    $output .= get_xml_db_field(2, $rs, 'next_notification');
                    $output .= get_xml_db_field(2, $rs, 'no_more_notifications');
                    $output .= get_xml_db_field(2, $rs, 'acknowledgement_type');
                    $output .= get_xml_db_field(2, $rs, 'current_notification_number');
                    $output .= get_xml_db_field(2, $rs, 'event_handler_enabled');
                    $output .= get_xml_db_field(2, $rs, 'process_performance_data');
                    $output .= get_xml_db_field(2, $rs, 'obsess_over_host');
                    $output .= get_xml_db_field(2, $rs, 'modified_host_attributes');
                    $output .= get_xml_db_field(2, $rs, 'event_handler');
                    $output .= get_xml_db_field(2, $rs, 'check_command');
                    $output .= get_xml_db_field(2, $rs, 'normal_check_interval');
                    $output .= get_xml_db_field(2, $rs, 'retry_check_interval');
                    $output .= get_xml_db_field(2, $rs, 'check_timeperiod_object_id', 'check_timeperiod_id');
                }

                if ($brevity < 2) {
                    $output .= get_xml_db_field(2, $rs, 'has_been_checked');
                    $output .= get_xml_db_field(2, $rs, 'current_check_attempt');
                    $output .= get_xml_db_field(2, $rs, 'max_check_attempts');
                    $output .= get_xml_db_field(2, $rs, 'last_check');
                    $output .= get_xml_db_field(2, $rs, 'next_check');
                    $output .= get_xml_db_field(2, $rs, 'state_type');
                    $output .= get_xml_db_field(2, $rs, 'notifications_enabled');
                    $output .= get_xml_db_field(2, $rs, 'problem_has_been_acknowledged', 'problem_acknowledged');
                    $output .= get_xml_db_field(2, $rs, 'passive_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'active_checks_enabled');
                    $output .= get_xml_db_field(2, $rs, 'flap_detection_enabled');
                    $output .= get_xml_db_field(2, $rs, 'is_flapping');
                    $output .= get_xml_db_field(2, $rs, 'percent_state_change');
                    $output .= get_xml_db_field(2, $rs, 'latency');
                    $output .= get_xml_db_field(2, $rs, 'execution_time');
                    $output .= get_xml_db_field(2, $rs, 'scheduled_downtime_depth');
                }


                $output .= "  </hoststatus>\n";

                $rs->MoveNext();
            }
        }
        $output .= "</hoststatuslist>\n";
    }

    return $output;
}


////////////////////////////////////////////////////////////////////////////////
// CUSTOM HOST VARIABLE STATUS 
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_custom_host_variable_status_xml_output($request_args)
{
    global $sqlquery;
    global $db_tables;

    $output = "";

    // generate query
    $fieldmap = array(
        "host_name" => "obj1.name1",
        "display_name" => $db_tables[DB_NDOUTILS]["hosts"] . ".display_name",
        "host_alias" => $db_tables[DB_NDOUTILS]["hosts"] . ".alias",
        "instance_id" => $db_tables[DB_NDOUTILS]["customvariablestatus"] . ".instance_id",
        "host_id" => "obj1.object_id",
        "var_name" => $db_tables[DB_NDOUTILS]["customvariablestatus"] . ".varname",
        "var_value" => $db_tables[DB_NDOUTILS]["customvariablestatus"] . ".varvalue"
    );
    $objectauthfields = array(
        "host_id"
    );
    $instanceauthfields = array(
        "instance_id"
    );
    $args = array(
        "sql" => $sqlquery['GetCustomHostVariableStatus'],
        "fieldmap" => $fieldmap,
        "objectauthfields" => $objectauthfields,
        "objectauthperms" => P_READ,
        "instanceauthfields" => $instanceauthfields,
        "useropts" => $request_args, // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql, false))) {
        //handle_backend_db_error(DB_NDOUTILS);
    } else {
        //output_backend_header();
        $output .= "<customhostvarstatuslist>\n";
        $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";

        if (!isset($request["totals"])) {
            $last_id = -1;
            while (!$rs->EOF) {

                $this_id = $rs->fields['object_id'];
                if ($last_id != $this_id) {
                    if ($last_id != -1) {
                        $output .= "    </customvars>\n";
                        $output .= "  </customhostvarstatus>\n";
                    }
                    $last_id = $this_id;
                    $output .= "  <customhostvarstatus>\n";
                    $output .= get_xml_db_field(2, $rs, 'instance_id');
                    $output .= get_xml_db_field(2, $rs, 'object_id', 'contact_id');
                    $output .= get_xml_db_field(2, $rs, 'host_name');
                    $output .= get_xml_db_field(2, $rs, 'display_name');
                    $output .= get_xml_db_field(2, $rs, 'host_alias');
                    $output .= "    <customvars>\n";
                }

                $output .= "      <customvar>\n";
                $output .= get_xml_db_field(4, $rs, 'varname', 'name');
                $output .= get_xml_db_field(4, $rs, 'varvalue', 'value');
                $output .= get_xml_db_field(4, $rs, 'has_been_modified', 'modified');
                $output .= get_xml_db_field(4, $rs, 'status_update_time', 'last_update');
                $output .= "      </customvar>\n";

                $rs->MoveNext();
            }
            if ($last_id != -1) {
                $output .= "    </customvars>\n";
                $output .= "  </customhostvarstatus>\n";
            }
        }
        $output .= "</customhostvarstatuslist>\n";
    }

    return $output;
}


////////////////////////////////////////////////////////////////////////////////
// COMMENTS
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_comments_xml_output($request_args)
{
    global $sqlquery;
    global $db_tables;

    $output = "";

    // generate query
    $fieldmap = array(
        "instance_id" => $db_tables[DB_NDOUTILS]["instances"] . ".instance_id",
        "comment_id" => $db_tables[DB_NDOUTILS]["comments"] . ".comment_id",
        "comment_type" => $db_tables[DB_NDOUTILS]["comments"] . ".comment_type",
        "object_id" => "obj1.object_id",
        "objecttype_id" => "obj1.objecttype_id",
        "host_name" => "obj1.name1",
        "service_description" => "obj1.name2",
        "entry_type" => $db_tables[DB_NDOUTILS]["comments"] . ".entry_type",
        "entry_time" => $db_tables[DB_NDOUTILS]["comments"] . ".entry_time",
        "entry_time_usec" => $db_tables[DB_NDOUTILS]["comments"] . ".entry_time_usec",
        "comment_time" => $db_tables[DB_NDOUTILS]["comments"] . ".comment_time",
        "internal_id" => $db_tables[DB_NDOUTILS]["comments"] . ".internal_comment_id",
        "author_name" => $db_tables[DB_NDOUTILS]["comments"] . ".author_name",
        "comment_data" => $db_tables[DB_NDOUTILS]["comments"] . ".comment_data",
        "is_persistent" => $db_tables[DB_NDOUTILS]["comments"] . ".is_persistent",
        "comment_source" => $db_tables[DB_NDOUTILS]["comments"] . ".comment_source",
        "expires" => $db_tables[DB_NDOUTILS]["comments"] . ".expires",
        "expiration_time" => $db_tables[DB_NDOUTILS]["comments"] . ".expiration_time",
    );
    $objectauthfields = array(
        "object_id"
    );
    $instanceauthfields = array(
        "instance_id"
    );
    $default_order = "entry_time DESC, entry_time_usec DESC, comment_id DESC";
    $args = array(
        "sql" => $sqlquery['GetComments'],
        "fieldmap" => $fieldmap,
        "objectauthfields" => $objectauthfields,
        "objectauthperms" => P_READ,
        "instanceauthfields" => $instanceauthfields,
        "default_order" => $default_order,
        "useropts" => $request_args, // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    if (!isset($request['brevity']))
        $brevity = 0;
    else
        $brevity = $request['brevity'];

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql, false))) {
        //handle_backend_db_error(DB_NDOUTILS);
    } else {
        //output_backend_header();
        $output .= "<comments>\n";
        $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";

        if (!isset($request["totals"])) {
            while (!$rs->EOF) {

                $output .= "  <comment id='" . get_xml_db_field_val($rs, 'comment_id') . "'>\n";
                $output .= get_xml_db_field(2, $rs, 'instance_id');
                $output .= get_xml_db_field(2, $rs, 'comment_id');
                $output .= get_xml_db_field(2, $rs, 'comment_type');
                $output .= get_xml_db_field(2, $rs, 'object_id');
                $output .= get_xml_db_field(2, $rs, 'objecttype_id');
                if ($brevity < 1) {
                    $output .= get_xml_db_field(2, $rs, 'host_name');
                    $output .= get_xml_db_field(2, $rs, 'service_description');
                    $output .= get_xml_db_field(2, $rs, 'entry_type');
                    $output .= get_xml_db_field(2, $rs, 'entry_time');
                    $output .= get_xml_db_field(2, $rs, 'entry_time_usec');
                    $output .= get_xml_db_field(2, $rs, 'comment_time');
                    $output .= get_xml_db_field(2, $rs, 'internal_comment_id', 'internal_id');
                    $output .= get_xml_db_field(2, $rs, 'author_name');
                    $output .= get_xml_db_field(2, $rs, 'comment_data');
                    $output .= get_xml_db_field(2, $rs, 'is_persistent');
                    $output .= get_xml_db_field(2, $rs, 'comment_source');
                    $output .= get_xml_db_field(2, $rs, 'expires');
                    $output .= get_xml_db_field(2, $rs, 'expiration_time');
                }
                $output .= "  </comment>\n";

                $rs->MoveNext();
            }
        }
        $output .= "</comments>\n";
    }

    return $output;
}


////////////////////////////////////////////////////////////////////////////////
// TIMED EVENT QUEUE SUMMARY
////////////////////////////////////////////////////////////////////////////////

/**
 * @param $request_args
 *
 * @return string
 */
function get_timedeventqueuesummary_xml_output($request_args)
{

    $output = "";

    //////////////////Modified Query///////////////////////////
    /**
     *    Updated this query 1/02/2013 to pull data from status tables instead of timedeventqueue
     *    The timedeventqueue table makes up about 60% of all ndoutils traffic, this should save from overhead
     *    Auth checks removed because only users with full access can see the event queue chart - MG
     */
    $newsql = "
	SELECT COUNT(*) AS total_events,next_check, NOW() as time_now,
	TIMESTAMPDIFF(SECOND,NOW(),next_check) AS seconds_from_now,
	(TIMESTAMPDIFF(SECOND,NOW(),next_check) DIV 10) AS bucket
	FROM nagios_hoststatus
	WHERE TRUE 
	AND (TIMESTAMPDIFF(SECOND,NOW(),next_check) < 300)
	AND instance_id = '1'
	AND UNIX_TIMESTAMP(next_check) != 0
	GROUP BY instance_id, bucket
	UNION
	SELECT COUNT(*) AS total_events,next_check, NOW() as time_now,
	TIMESTAMPDIFF(SECOND,NOW(),next_check) AS seconds_from_now,
	(TIMESTAMPDIFF(SECOND,NOW(),next_check) DIV 10) AS bucket
	FROM nagios_servicestatus
	WHERE TRUE 
	AND (TIMESTAMPDIFF(SECOND,NOW(),next_check) < 300)
	AND instance_id = '1'
	AND UNIX_TIMESTAMP(next_check) != 0
	GROUP BY instance_id, bucket	
	ORDER BY bucket ASC LIMIT 10000";


    /*

    // use a custom query
    $raw_sql=" SELECT
".$db_tables['ndoutils']['instances'].".instance_id, ".$db_tables['ndoutils']['instances'].".instance_name,
COUNT(".$db_tables[DB_NDOUTILS]["timedeventqueue"].".event_type) AS total_events,
".$db_tables[DB_NDOUTILS]["timedeventqueue"].".event_type,
".$db_tables[DB_NDOUTILS]["timedeventqueue"].".scheduled_time,
NOW() as time_now,
TIMESTAMPDIFF(SECOND,NOW(),".$db_tables[DB_NDOUTILS]["timedeventqueue"].".scheduled_time) AS seconds_from_now,
(TIMESTAMPDIFF(SECOND,NOW(),".$db_tables[DB_NDOUTILS]["timedeventqueue"].".scheduled_time) DIV %d) AS bucket
FROM ".$db_tables[DB_NDOUTILS]["timedeventqueue"]."
LEFT JOIN ".$db_tables[DB_NDOUTILS]['instances']." ON ".$db_tables[DB_NDOUTILS]["timedeventqueue"].".instance_id=".$db_tables[DB_NDOUTILS]['instances'].".instance_id

 WHERE TRUE AND (TIMESTAMPDIFF(SECOND,NOW(),".$db_tables[DB_NDOUTILS]["timedeventqueue"].".scheduled_time) < %d)
 ";

    // get timeframe and bucket size
    $bucket_size=grab_request_var("bucket_size",15); // 15 second chunks
    $window=grab_request_var("window",300);  // time frame (window) to look at
    $brevity=grab_request_var("brevity",0);

    // update query with bucket size and window
    $bucket_size=escape_sql_param($bucket_size,DB_NDOUTILS);
    $window=escape_sql_param($window,DB_NDOUTILS);
    $sql_query=sprintf($raw_sql,$bucket_size,$window);

    // custom group by
    $group_by="GROUP BY
".$db_tables[DB_NDOUTILS]["timedeventqueue"].".instance_id,
".$db_tables[DB_NDOUTILS]["timedeventqueue"].".event_type,
bucket
";

    // default # of records to return if none specified
    if(!isset($request_args["records"]))
        $request_args["records"]=10000;

    // generate query
    $fieldmap=array(
        "instance_id" => $db_tables[DB_NDOUTILS]["timedeventqueue"].".instance_id",
        "event_type" => $db_tables[DB_NDOUTILS]["timedeventqueue"].".event_type",
        );
    $instanceauthfields=array(
        "instance_id"
        );
    $default_order=$db_tables[DB_NDOUTILS]["timedeventqueue"].".scheduled_time ASC";
    $args=array(
        "sql" => $sql_query,   // <- this is a bit different that most other functions
        "groupby" => $group_by, // <- this is a bit different that most other functions
        "fieldmap" => $fieldmap,
        "instanceauthfields" => $instanceauthfields,
        "instanceauthperms" => P_READ,
        "default_order" => $default_order,
        "useropts" => $request_args,  // ADDED 12/22/09 FOR NEW NON-BACKEND CALLS
        );
    */


    //$sql=generate_sql_query(DB_NDOUTILS,$args);

    //file_put_contents('/tmp/teq.log',$sql);

    if (!($rs = exec_sql_query(DB_NDOUTILS, $newsql, false))) {
        //handle_backend_db_error(DB_NDOUTILS);
    } else {
        // massage data...

        ///////////////////////////Modified - MG////////////////////
        // get timeframe and bucket size
        $bucket_size = grab_request_var("bucket_size", 15); // 15 second chunks
        $window = grab_request_var("window", 300); // time frame (window) to look at
        $brevity = grab_request_var("brevity", 0);

        // update query with bucket size and window
        $bucket_size = escape_sql_param($bucket_size, DB_NDOUTILS);
        $window = escape_sql_param($window, DB_NDOUTILS);

        // how many total buckets will we have?
        $total_buckets = ceil($window / $bucket_size);

        // initialize bucket array
        $bucket_array = array();

        for ($current_bucket = -1; $current_bucket <= ($total_buckets - 1); $current_bucket++) {
            $bucket_array[$current_bucket] = array();

            for ($event_type = 0; $event_type <= 12; $event_type++)
                $bucket_array[$current_bucket][$event_type] = 0;

            $bucket_array[$current_bucket][99] = 0;
        }

        $bucket_array[$current_bucket][0] = array();

        // fill bucket array
        while (!$rs->EOF) {

            $current_bucket = intval($rs->fields["bucket"]);
            //$event_type=intval($rs->fields["event_type"]); //only check data now - MG
            $event_type = 0;
            $total_events = intval($rs->fields["total_events"]);

            // skip invalid (old) buckets
            $bucket_to_use = $current_bucket;
            if ($bucket_to_use < 0)
                $bucket_to_use = -1;
            $bucket_array[$bucket_to_use][$event_type] += $total_events;

            // new max - now calculated below
            //if($bucket_array[$bucket_to_use][$event_type]>$max_bucket_items)
            //	$max_bucket_items=$bucket_array[$bucket_to_use][$event_type];

            $rs->MoveNext();
        }


        //output_backend_header();

        //print_r($bucket_array);

        $output .= "<timedeventqueuesummary>\n";
        $output .= "  <recordcount>" . ($total_buckets + 1) . "</recordcount>\n";
        $output .= "  <bucket_size>" . $bucket_size . "</bucket_size>\n";
        $output .= "  <window>" . $window . "</window>\n";
        $output .= "  <total_buckets>" . ($total_buckets + 1) . "</total_buckets>\n";

        $max_bucket_items = 0;
        foreach ($bucket_array as $bucket => $bucket_contents) {
            $output .= "  <bucket chunk='" . $bucket . "'>\n";
            $bucket_total = 0;
            foreach ($bucket_contents as $event_type => $total_events) {
                if ($brevity < 1)
                    $output .= "    <eventtotals type='" . $event_type . "'>" . $total_events . "</eventtotals>\n";
                $bucket_total += intval($total_events);
                if ($bucket_total > $max_bucket_items)
                    $max_bucket_items = $bucket_total;
            }
            if ($brevity > 0)
                $output .= "    <eventtotals type='-1'>" . $bucket_total . "</eventtotals>\n";
            $output .= "  </bucket>\n";
        }
        $output .= "  <maxbucketitems>" . $max_bucket_items . "</maxbucketitems>\n";
        $output .= "</timedeventqueuesummary>\n";
    }

    return $output;
}

