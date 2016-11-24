<?php
// AUDIT LOG FUNCTIONS
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//


////////////////////////////////////////////////////////////////////////
// REPORTING FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param $args
 *
 * @return SimpleXMLElement
 */
function get_xml_auditlog($args)
{
    $x = simplexml_load_string(get_auditlog_xml_output($args));
    //print_r($x);
    return $x;
}

////////////////////////////////////////////////////////////////////////
// AUDIT LOG FUNCTIONS
////////////////////////////////////////////////////////////////////////

// easier-to-use function with most defaultts
/**
 * @param        $message
 * @param int    $type
 * @param string $source
 * @param string $user
 * @param string $ipaddress
 *
 * @return bool
 */
function send_to_audit_log($message, $type = AUDITLOGTYPE_NONE, $source = "", $user = "", $ipaddress = "", $details = "")
{

    $logtime = time();
    if ($user == "")
        $user = get_user_attr(0, "username");
    if ($source == "")
        $source = AUDITLOGSOURCE_NAGIOSXI;
    if ($ipaddress == "") {
        if (isset($_SERVER["REMOTE_ADDR"]))
            $ipaddress = $_SERVER["REMOTE_ADDR"];
        else
            $ipaddress = "localhost";
    }

    $auditlogfile = get_option("auditlogfile");

    $args = array(
        "time" => $logtime,
        "source" => $source,
        "user" => $user,
        "type" => $type,
        "ipaddress" => $ipaddress,
        "message" => $message,
        "details" => $details,
        "auditlogfile" => $auditlogfile,
    );

    return send_to_audit_log2($args);
}


/**
 * @param null $arr
 *
 * @return bool
 */
function send_to_audit_log2($arr = null)
{
    global $cfg;

    if (!is_array($arr))
        return false;

    $logtime = grab_array_var($arr, "time", time());
    $source = grab_array_var($arr, "source", "Nagios XI");
    $user = grab_array_var($arr, "user", get_user_attr(0, "username"));
    $type = grab_array_var($arr, "type", AUDITLOGTYPE_NONE);
    $message = grab_array_var($arr, "message", "");
    $details = grab_array_var($arr, "details", "");
    $auditlogfile = grab_array_var($arr, "auditlogfile");

    if (isset($_SERVER["REMOTE_ADDR"]))
        $ip = $_SERVER["REMOTE_ADDR"];
    else
        $ip = "localhost";
    $ipaddress = grab_array_var($arr, "ip_address", $ip);

    $t = date("Y-m-d H:i:s", $logtime);

    if ($cfg['db_info']['nagiosxi']['dbtype'] == "mysql") {
        $u = 'user';
    } else {
        $u = '"user"';
    }

    $sql = "INSERT INTO xi_auditlog (log_time,source,$u,type,message,ip_address,details) VALUES ('" . escape_sql_param($t, DB_NAGIOSXI) . "','" . escape_sql_param($source, DB_NAGIOSXI) . "','" . escape_sql_param($user, DB_NAGIOSXI) . "'," . escape_sql_param($type, DB_NAGIOSXI) . ",'" . escape_sql_param($message, DB_NAGIOSXI) . "','" . escape_sql_param($ipaddress, DB_NAGIOSXI) . "','" . escape_sql_param($details, DB_NAGIOSXI) . "')";

    // write audit log to file - set in system settings (bool)
    if ($auditlogfile) {
        if ($user == "")
            $user = 'system';

        $log = $t . " - " . $source . " [" . $type . "] " . $user . ":" . $ipaddress . " - " . $message . PHP_EOL;

        file_put_contents('/usr/local/nagiosxi/var/components/auditlog.log', $log, FILE_APPEND);
    }

    //echo "SQL: $sql<BR>";
    //exit();

    if (!exec_sql_query(DB_NAGIOSXI, $sql))
        return false;

    return true;
}


// clear last X days from audit log 
/**
 * @param int $days
 */
function trim_audit_log($days = -1)
{

    // use saved value in database
    if ($days == -1) {
        $days = get_option("audit_log_retention_days");
        if ($days == "")
            $days = 30;
    }

    $ts = time() - ($days * 60 * 60 * 24);

    $sql = "DELETE FROM xi_auditlog WHERE log_time < '" . $ts . "'";
    exec_sql_query(DB_NAGIOSXI, $sql);
}


// delete everything
function clear_audit_log()
{
    $sql = "TRUNCATE xi_auditlog";
    exec_sql_query(DB_NAGIOSXI, $sql);
}

