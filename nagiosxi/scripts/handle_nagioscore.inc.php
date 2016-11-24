<?php
// NAGIOS CORE GLOBAL EVENT HANDLER
// Include file
//
// This file replaces the old global event handlers functionality
// We use a PDO call to the database instead of loading the ADO
// library to save on a lot of memory, cpu, disk consumption during
// outages.
//
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//  

define('CFG_ONLY', 1);
require_once(dirname(__FILE__) . '/../html/config.inc.php');
require_once(dirname(__FILE__) . '/../html/includes/constants.inc.php');


// execute a sql statement via a PDO connection to the database
function execute_sql($sql) {

    global $cfg;

    $dbtype = $cfg['db_info']['nagiosxi']['dbtype'];
    $dbserver = $cfg['db_info']['nagiosxi']['dbserver'];
    if (empty($dbserver)) {
        $dbserver = "localhost";
    }
    $user = $cfg['db_info']['nagiosxi']['user'];
    $pwd = $cfg['db_info']['nagiosxi']['pwd'];
    $db = $cfg['db_info']['nagiosxi']['db'];

    $port = null;
    if (!empty($cfg['nagiosxi']['port']))
        $port = $cfg['nagiosxi']['port'];

    $opts = array(
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
            );

    $dsn = "{$dbtype}:host={$dbserver};dbname={$db};";
    if (!empty($port))
        $dsn .= "port={$port};";

    try {

        $pdo = new PDO($dsn, $user, $pwd, $opts);
    } catch (PDOException $e) {

        echo "UNABLE TO CONNECT TO DB - EXITING!\n";
        exit(1);
    }

    try {

        $statement = $pdo->query($sql);
    } catch (PDOException $e) {

        echo "UNABLE TO EXECUTE QUERY - EXITING!\n";
        $pdo = null;
        exit(1);
    }

    $pdo = null;

    return $statement;
}


// return a sql statement for inserting data into the eventqueue
function handle_event_sql($source, $type, $meta) {

    $time = time();
    $source = intval($source);
    $type = intval($type);
    $meta = base64_encode(serialize($meta));

    return "INSERT INTO xi_eventqueue (event_time, event_source, event_type, event_meta) VALUES ({$time}, {$source}, {$type}, '{$meta}')";
}


// parse arguments, build insert statement and execute sql
function handle_event($source, $type) {

	global $argv;

	$meta = parse_argv($argv);
    execute_sql(handle_event_sql($source, $type, $meta));
}


// parse the arguments, pulled from utilsx.inc.php 7/18/2016
function parse_argv($argv)
{
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg) {

        if (substr($arg, 0, 2) == '--') {
            $eq = strpos($arg, '=');
            if ($eq === false) {
                $key = substr($arg, 2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg, 2, $eq - 2);
                $out[$key] = substr($arg, $eq + 1);
            }
        } else if (substr($arg, 0, 1) == '-') {
            if (substr($arg, 2, 1) == '=') {
                $key = substr($arg, 1, 1);
                $out[$key] = substr($arg, 3);
            } else {
                $chars = str_split(substr($arg, 1));
                foreach ($chars as $char) {
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }

    return $out;
}