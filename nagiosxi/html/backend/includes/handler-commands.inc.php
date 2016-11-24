<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/common.inc.php');

///////////////////////////////////////////////////////////////////////////////////////////
//
// BACKEND COMMAND FUNCTIONS
//
///////////////////////////////////////////////////////////////////////////////////////////

function backend_submit_command()
{
    // Only let admins do this
    if (is_admin() == false) {
        exit;
    }

    // Grab the command to run...
    if (($command = grab_request_var("command", "")) == "") {
        handle_backend_error("You must enter a command (and command data if required) to run a command.");
    }
    $command_data = grab_request_var("command_data", "");
    $event_time = grab_request_var("event_time", "0");

    // Run the command through the backend (don't wait for it to return)
    $command_id = submit_command($command, $command_data, $event_time, 0);
    if ($command_id > 0) {
        $rc = RESULT_OK;
    } else {
        $rc = RESULT_ERROR;
    }

    // Get output type and send out
    $outputtype = strtolower(grab_request_var("outputtype", "xml"));
    if ($outputtype == "json") {
        $data = array("result" => $rc,
            "message" => MSG_OK,
            "command_id" => $command_id);
        print backend_output($data);
    } else {
        output_backend_header();
        begin_backend_result($rc, MSG_OK);
        echo "<command_id>$command_id</command_id>\n";
        end_backend_result();
    }
}

/**
 * @return bool
 */
function backend_get_command_status()
{
    global $db_tables;
    global $sqlquery;
    global $request;

    // Admins can see everything, everyone else sees only their commands
    if (is_admin() == false) {
        // Add submitter id to request variables to limit sql
        $request["submitter_id"] = $_SESSION["user_id"];
    }

    // Generate SQL query
    $fieldmap = array(
        "command_id" => $db_tables[DB_NAGIOSXI]["commands"] . ".command_id",
        "group_id" => $db_tables[DB_NAGIOSXI]["commands"] . ".group_id",
        "submitter_id" => $db_tables[DB_NAGIOSXI]["commands"] . ".submitter_id",
        "beneficiary_id" => $db_tables[DB_NAGIOSXI]["commands"] . ".beneficiary_id",
        "command" => $db_tables[DB_NAGIOSXI]["commands"] . ".command",
        "command_data" => $db_tables[DB_NAGIOSXI]["commands"] . ".command_data",
        "submission_time" => $db_tables[DB_NAGIOSXI]["commands"] . ".submission_time",
        "event_time" => $db_tables[DB_NAGIOSXI]["commands"] . ".event_time",
        "processing_time" => $db_tables[DB_NAGIOSXI]["commands"] . ".processing_time",
        "frequency_type" => $db_tables[DB_NAGIOSXI]["commands"] . ".frequency_type",
        "frequency_units" => $db_tables[DB_NAGIOSXI]["commands"] . ".frequency_units",
        "frequency_interval" => $db_tables[DB_NAGIOSXI]["commands"] . ".frequency_interval",
        "status_code" => $db_tables[DB_NAGIOSXI]["commands"] . ".status_code",
        "result_code" => $db_tables[DB_NAGIOSXI]["commands"] . ".result_code",
        "result" => $db_tables[DB_NAGIOSXI]["commands"] . ".result"
    );

    $query_args = $request;
    $args = array(
        "sql" => $sqlquery['GetCommands'],
        "fieldmap" => $fieldmap,
        "default_order" => "command_id",
        "useropts" => $query_args,
        "limitrecords" => false
    );

    // Returns an ADO object...
    $sql = generate_sql_query(DB_NAGIOSXI, $args);

    // Execute a non-caching query (needed for fastest Ajax results)
    if (!($rs = exec_sql_query(DB_NAGIOSXI, $sql, true, false))) {
        handle_backend_db_error();
    } else {

        // Generate the XML
        $outputtype = grab_request_var("outputtype", "");
        $output = "<commands>\n";
        $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";

        if (!isset($request["totals"])) {
            while (!$rs->EOF) {
                if ($outputtype == "json") {
                } else {
                    $output .= "  <command id='" . db_field($rs, 'command_id') . "'>\n";
                }
                $output .= xml_db_field(2, $rs, 'group_id', '', true);
                $output .= xml_db_field(2, $rs, 'submitter_id', '', true);
                $output .= xml_db_field(2, $rs, 'beneficiary_id', '', true);
                $output .= xml_db_field(2, $rs, 'command', '', true);
                $output .= xml_db_field(2, $rs, 'command_data', '', true);
                $output .= xml_db_field(2, $rs, 'submission_time', '', true);
                $output .= xml_db_field(2, $rs, 'event_time', '', true);
                $output .= xml_db_field(2, $rs, 'processing_time', '', true);
                $output .= xml_db_field(2, $rs, 'frequency_type', '', true);
                $output .= xml_db_field(2, $rs, 'frequency_units', '', true);
                $output .= xml_db_field(2, $rs, 'frequency_interval', '', true);
                $output .= xml_db_field(2, $rs, 'status_code', '', true);
                $output .= xml_db_field(2, $rs, 'result_code', '', true);
                $output .= xml_db_field(2, $rs, 'result', '', true);
                $output .= "  </command>\n";
                $rs->MoveNext();
            }
        }
        $output .= "</commands>\n";

        print backend_output($output);
    }
    return true;
}
