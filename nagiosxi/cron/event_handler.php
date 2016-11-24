#!/bin/env php -q
<?php
//
// Event handler
// This script runs once a minute, and checks the event_handler queue
// for events to be added to the DB
//
// Run frequency: 1 minute
//
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//

define('SUBSYSTEM', 1);
require_once(dirname(__FILE__).'/../html/config.inc.php');
require_once(dirname(__FILE__).'/../html/includes/utils.inc.php');


$script_start_time = time();
$max_exec_time = 60;
$sleep_time = 5;
$eventhandler_lockfile = get_root_dir() . '/var/event_handler.lock';


init_eventhandler();
do_eventhandler_jobs();


// create a lockfile and connect to db
function init_eventhandler() {

    global $eventhandler_lockfile;
    global $max_exec_time;

    // Check lock file
    if (@file_exists($eventhandler_lockfile)) {

        $ft = filemtime($eventhandler_lockfile);
        $now = time();

        // if this file is older than twice the max_exec_time, we have a problem!
        if (($now - $ft) > ($max_exec_time * 2)) {
            echo "LOCKFILE '$eventhandler_lockfile' IS OLD - REMOVING\n";
            unlink($eventhandler_lockfile);
        } else {
            echo "LOCKFILE '$eventhandler_lockfile' EXISTS - EXITING!\n";
            exit(1);
        }
    }
    
    // Attempt to create lock file
    if (@touch($eventhandler_lockfile) === false) {
        echo "LOCKFILE '$eventhandler_lockfile' NOT CREATED - EXITING!\n";
        exit(1);
    } else {
        echo "LOCKFILE '$eventhandler_lockfile' CREATED\n";
    }
    
    // Make database connections
    $dbok = db_connect_all();
    if ($dbok == false) {
        echo "ERROR CONNECTING TO DATABASES - EXITING!\n";
        exit(1);
    }
}


// cycle through any events to be added from in the queue
function do_eventhandler_jobs() {

    global $script_start_time;
    global $max_exec_time;
    global $eventhandler_queuefile;
    global $eventhandler_queuelockfile;
    global $sleep_time;

    while (true) {

        $clear_ids = array();

        // check to see if the next run is going to cause us to move over our alloted max_exec_time and exit if necessary
        if ((time() - $script_start_time) >= ($max_exec_time - $sleep_time)) {
            eventhandler_safe_exit();
        }

        // obtain event handler queue data and process
        $sql = "SELECT * FROM xi_eventqueue";
        $rows = exec_sql_query(DB_NAGIOSXI, $sql);
        if (count($rows) > 0) {
            foreach($rows as $row) {

                $clear_ids[] = $row['eventqueue_id'];

                $time = $row['event_time'];
                $source = $row['event_source'];
                $type = $row['event_type'];
                $meta = base64_decode($row['event_meta']);

                print_r($row);

                add_event($source, $type, $time, $meta);
            }
        }

        // now see if we need to clear our queue data out
        if (count($clear_ids) > 0) {
            $sql = "DELETE FROM xi_eventqueue WHERE ";
            foreach($clear_ids as $id) {
                $sql .= "eventqueue_id = $id OR ";
            }
            $sql = substr($sql, 0, -4); // get rid of that remaining " OR "
            exec_sql_query(DB_NAGIOSXI, $sql);
        }

        sleep($sleep_time);
    }
}


// delete the lockfile
function eventhandler_safe_exit() {

    global $eventhandler_lockfile;

    if (@file_exists($eventhandler_lockfile)) {
        if (@unlink($eventhandler_lockfile) === false) {
            echo "UNABLE TO DELETE LOCKFILE '$eventhandler_lockfile' - EXITING!\n";
            exit(1);
        } else {
            echo "DELETED LOCKFILE '$eventhandler_lockfile'\n";
        }
    }

    echo "EVENT HANDLER EXITING\n";
    exit();
}