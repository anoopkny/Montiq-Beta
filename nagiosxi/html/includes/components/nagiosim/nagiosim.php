<?php
//
// Nagios IM Integration
// Copyright (c) 2011-2015 Nagios Enterprises, LLC. All rights reserved.
//

/**
 * API token is also the XI token to access this page directly
 *
 * IM will send this page an incident ID, XI will find it, and acknowledge the host or service problem
 * if it still exists and remove the incident.
 */

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

check_authentication(false);

// Grab GET or POST variables and check prereqs
grab_request_vars();
check_prereqs();

route_request();

function route_request()
{
    $mode = grab_request_var('mode', 'default');
    $token = grab_request_var('token', 'badtoken');

    switch ($mode) {

        case 'update':
            $settings = unserialize(get_option('im_component_options'));
            $xitoken = grab_array_var($settings, 'api_key', '');

            // Bypass normal XI authentication if token is ok
            if ($token == $xitoken) {
                handle_incident_update();
            } else {
                $msg = "Nagios IM Component - Unauthorized action. Bad Token.\n";
                send_to_audit_log($msg, AUDITLOGTYPE_SECURITY);
                die($msg);
            }
            break;

        case 'resolve':
            $host = grab_request_var('host', '');
            $service = grab_request_var('service', '');
            $c = 0;

            $incidents = nagiosim_component_find_incidents($host, $service);
            foreach ($incidents as $incident) {
                $bool = nagiosim_component_resolve_incident($incident['incident_id']);
                $c += intval($bool);
            }

            echo "{$c} incidents resolved in Nagios IM\n";
            break;

        default:
            // Debugging
            echo "<pre>\n";
            echo "NAGIOS IM Scratch Pad\n";
            nagiosim_component_flush_incidents(CALLBACK_SUBSYS_CLEANER, array());
            echo "</pre>\n";
            break;

    }
}

function handle_incident_update()
{
    $msg = "Callback 'handle_incident_update()' \n";
    im_log($msg . "\n" . print_r($_REQUEST, true) . "\n\n");

    // Handle post inputs
    $incident_id = grab_request_var('incident_id', false);
    $type_array = grab_request_var('incident_type', array());
    $title = grab_request_var('title', '');
    $status = grab_request_var('status', false);
    $api_url = grab_request_var('api_url', false);
    $cbtype = grab_request_var('callback_type', false);
    $incident_type = grab_array_var($type_array, 'title', '');

    // Preserve sanity
    if ($incident_id == false || intval($incident_id) == 0) {
        return;
    }

    // Grab incident from db if it exists
    $sql = "SELECT * FROM xi_incidents WHERE incident_id='{$incident_id}'";
    $rs = exec_sql_query(DB_NAGIOSXI, $sql, true);
    im_log("SQL: {$sql}\n");

    // Nothing to do if the incident has already been removed by a recovery
    if ($rs->recordCount() == 0) {
        return;
    }

    foreach ($rs as $row)
        $incident = $row;

    $host = grab_array_var($incident, 'host');
    $service = grab_array_var($incident, 'service', '');

    // Do acknowledgment stuff
    if ($status == 'Closed' || $status == 'Resolved' || $status == 'Acknowledged') {

        $cmd = get_core_acknowledgment($host, $service, $title, $status);
        exec($cmd, $output);
        $msg = "CMD: $cmd\n OUTPUT: " . print_r($output, true) . "\n";
        im_log($msg);

        // Log command
        if (function_exists('send_to_audit_log')) {
            send_to_audit_log("Nagios IM Component submitted a command to Nagios Core: ACKNOWLEDGMENT: {$host} {$service}", AUDITLOGTYPE_INFO);
        }

        // Only remove an incident if it's closed or resolved
        if ($status != 'Acknowledged') {
            $msg = "Removing XI Incident...\n";
            im_log($msg);
            $sql = "DELETE FROM xi_incidents WHERE incident_id='{$incident_id}'";
            exec_sql_query(DB_NAGIOSXI, $sql, true);
        }

    // Just post a comment
    } else {

        // Details update
        $cmd = get_core_comment($host, $service, $title, $status);
        exec($cmd, $output);
        $msg = "CMD: '$cmd'\n";
        im_log($msg);

        // Log command
        if (function_exists('send_to_audit_log')) {
            send_to_audit_log("Nagios IM Component submitted a command to Nagios Core: ADD COMMENT: {$host} {$service}", AUDITLOGTYPE_INFO);
        }

    }
}

function get_core_acknowledgment($host, $service, $title, $status)
{
    global $cfg;

    $host = escapeshellarg($host);
    $service = escapeshellarg($service);
    $title = escapeshellarg($title);
    $status = escapeshellarg($status);

    $pipe = grab_array_var($cfg['component_info']['nagioscore'], 'cmd_file', '/usr/local/nagios/var/rw/nagios.cmd');
    $now = time();
    $sticky = 1;
    $notify = 1;
    $persistent = 0;
    $username = 'Nagios IM Component';
    $message = "Incident Update: {$title}. Status is: {$status}";

    if ($service != '') {
        $ackCommand = 'ACKNOWLEDGE_SVC_PROBLEM';
        $cmdstring = "/bin/echo '[$now] $ackCommand;$host;$service;$sticky;$notify;$persistent;$username;$message' > $pipe";
    } else {
        $ackCommand = 'ACKNOWLEDGE_HOST_PROBLEM';
        $cmdstring = "/bin/echo '[$now] $ackCommand;$host;$sticky;$notify;$persistent;$username;$message' > $pipe";
    }

    return $cmdstring;
}

function get_core_comment($host, $service, $title, $status)
{
    global $cfg;

    $pipe = grab_array_var($cfg['component_info']['nagioscore'], 'cmd_file', '/usr/local/nagios/var/rw/nagios.cmd');
    $now = time();
    $persistent = 0;
    $username = 'Nagios IM Component';
    $message = "Incident: {$title} updated. Status is: {$status}";

    if ($service != '') {
        $ackCommand = 'ADD_SVC_COMMENT';
        $cmdstring = "/bin/echo '[$now] $ackCommand;$host;$service;$persistent;$username;$message' > $pipe";
    } else {
        $ackCommand = 'ADD_HOST_COMMENT';
        $cmdstring = "/bin/echo '[$now] $ackCommand;$host;$persistent;$username;$message' > $pipe";
    }

    return $cmdstring;
}
