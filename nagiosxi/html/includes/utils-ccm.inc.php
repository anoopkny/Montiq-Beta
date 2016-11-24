<?php
//
// Copyright (c) 2009-2014 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 03/22/2008
// $Id$

////////////////////////////////////////////////////////////////////////
// DELETION FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param      $hostname
 * @param      $servicename
 * @param bool $cascade
 *
 * @return bool
 */
function nagiosccm_can_service_be_deleted($hostname, $servicename, $cascade = false)
{
    // Make sure the host is in NagiosQL
    if (($serviceid = nagiosccm_get_service_id($hostname, $servicename)) <= 0) {
        return false;
    }

    // Make sure service is unique, and not an advanced setup (using wildcards, multiple hosts, etc)
    if (nagiosccm_is_service_unique($serviceid) == false) {
        return false;
    }

    if ($cascade == false) {
        // Make sure service is not in a dependency
        if (nagiosccm_service_is_in_dependency($serviceid) == true) {
            return false;
        }
    }

    return true;
}

/**
 * @param      $hostname
 * @param bool $cascade
 *
 * @return bool
 */
function nagiosccm_can_host_be_deleted($hostname, $cascade = false)
{
    // Make sure the host is in NagiosQL
    if (($hostid = nagiosql_get_host_id($hostname)) <= 0) {
        return false;
    }

    // See if associated services can be deleted too
    if ($cascade == true) {
        // ??
    } else {
        // Make sure host doesn't have any services
        if (nagiosql_host_has_services($hostname) == true) {
            return false;
        }
        // Make sure host is not in a dependency
        if (nagiosql_host_is_in_dependency($hostname) == true) {
            return false;
        }
        // Make sure host is not related to other hosts (e.g. parent host)
        if (nagiosql_host_is_related_to_other_hosts($hostname) == true) {
            return false;
        }
    }

    return true;
}

///////////////////////////////////////////////////////////////////////////////////////////
// HOST FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

/**
 * @param $hostname
 *
 * @return int
 */
function nagiosccm_get_host_id($hostname)
{
    global $db_tables;

    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSQL]["host"] . " WHERE host_name='" . escape_sql_param($hostname, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if (!$rs->EOF) {
            return intval($rs->fields["id"]);
        }
    }

    return -1;
}

/**
 * @param $hostid
 *
 * @return bool
 */
function nagiosccm_host_is_in_dependency($hostid)
{
    global $db_tables;

    // See if host is a master host in a dependency
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkHostdependencyToHost_H"] . " WHERE idSlave='" . escape_sql_param($hostid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 0) {
            return true;
        }
    }

    // See if host is a dependent host in a dependency
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkHostdependencyToHost_DH"] . " WHERE idSlave='" . escape_sql_param($hostid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 0) {
            return true;
        }
    }

    return false;
}

/**
 * @param $hostid
 *
 * @return bool
 */
function nagiosccm_host_has_services($hostid)
{
    global $db_tables;

    // See if host has services associated with it
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkServiceToHost"] . " WHERE idSlave='" . escape_sql_param($hostid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 0) {
            return true;
        }
    }

    return false;
}

/**
 * @param $hostid
 *
 * @return bool
 */
function nagiosccm_host_is_related_to_other_hosts($hostid)
{
    global $db_tables;

    // See if host is related to other hosts
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkHostToHost"] . " WHERE idMaster='" . escape_sql_param($hostid, DB_NAGIOSQL) . "' OR  idSlave='" . escape_sql_param($hostid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 0) {
            return true;
        }
    }

    return false;
}

///////////////////////////////////////////////////////////////////////////////////////////
// SERVICE FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

/**
 * @param $hostname
 * @param $servicename
 *
 * @return int
 */
function nagiosccm_get_service_id($hostname, $servicename)
{
    global $db_tables;

    $sql = "SELECT 
" . $db_tables[DB_NAGIOSQL]["lnkServiceToHost"] . ".idMaster as service_id,
" . $db_tables[DB_NAGIOSQL]["host"] . ".id as host_id,
" . $db_tables[DB_NAGIOSQL]["host"] . ".host_name as host_name,
" . $db_tables[DB_NAGIOSQL]["service"] . ".service_description
FROM " . $db_tables[DB_NAGIOSQL]["service"] . "
LEFT JOIN " . $db_tables[DB_NAGIOSQL]["lnkServiceToHost"] . " ON " . $db_tables[DB_NAGIOSQL]["service"] . ".id=" . $db_tables[DB_NAGIOSQL]["lnkServiceToHost"] . ".idMaster
LEFT JOIN " . $db_tables[DB_NAGIOSQL]["host"] . " ON " . $db_tables[DB_NAGIOSQL]["lnkServiceToHost"] . ".idSlave=" . $db_tables[DB_NAGIOSQL]["host"] . ".id
 WHERE " . $db_tables[DB_NAGIOSQL]["host"] . ".host_name='" . escape_sql_param($hostname, DB_NAGIOSQL) . "' AND " . $db_tables[DB_NAGIOSQL]["service"] . ".service_description='" . escape_sql_param($servicename, DB_NAGIOSQL) . "'";

    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if (!$rs->EOF) {
            return intval($rs->fields["service_id"]);
        }
    }

    return -1;
}

/**
 * @param $serviceid
 *
 * @return bool
 */
function nagiosccm_is_service_unique($serviceid)
{
    global $db_tables;

    if ($serviceid <= 0) {
        return false;
    }

    // Check flags in service definition to see if there are wildcards used for host or hostgroup
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkServiceToHostgroup"] . " WHERE idMaster='" . escape_sql_param($serviceid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if (!$rs->EOF) {

            $host_flag = intval($rs->fields["host_name"]);
            $hostgroup_flag = intval($rs->fields["hostgroup_name"]);

            // Service is associated with one or more ( or wildcard) hostgroups, so its not unique
            if ($hostgroup_flag != 0) {
                return false;
            }

            // Service is associated with no( or wildcard) hosts, so its probably not unique
            if ($host_flag != 1) {
                return false;
            }
        }
    }

    // See if service is associated with multiple hosts (or no hosts)
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkServiceToHost"] . " WHERE idMaster='" . escape_sql_param($serviceid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 1) {
            return false;
        }
    }

    // next see if service is associated with one or more hostgroups
    // NOTE - already taken care of by checking hostgroup_flag above...
    /*
    $sql="SELECT  * FROM ".$db_tables[DB_NAGIOSQL]["lnkServiceToHostgroup"]." WHERE idMaster='".escape_sql_param($serviceid,DB_NAGIOSQL)."'";
    if(($rs=exec_sql_query(DB_NAGIOSQL,$sql))){
        if($rs->RecordCount()>0)
            return false;
        }
    */

    return true;
}

/**
 * @param $serviceid
 *
 * @return bool
 */
function nagiosccm_service_is_in_dependency($serviceid)
{
    global $db_tables;

    // See if service is a master service in a dependency
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkServicedependencyToService_S"] . " WHERE idSlave='" . escape_sql_param($serviceid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 0) {
            return true;
        }
    }

    // See if service is a dependent service in a dependency
    $sql = "SELECT  * FROM " . $db_tables[DB_NAGIOSQL]["lnkServicedependencyToService_DS"] . " WHERE idSlave='" . escape_sql_param($serviceid, DB_NAGIOSQL) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSQL, $sql))) {
        if ($rs->RecordCount() != 0) {
            return true;
        }
    }

    return false;
}

// Replace user macros with Nagios macros in resource.cfg
// Input: string
// Output: string (with replaced macros)
/**
 * @param string $str
 *
 * @return mixed
 */
function nagiosccm_replace_user_macros($str = "")
{
    if (empty($str)) {
        return "";
    }

    $cfg_file_data = nagiosccm_get_resource_cfg(false, true);

    // Replace macros in the string given
    $newstr = str_replace($cfg_file_data["user_macros"], $cfg_file_data["user_macro_values"], $str);
    return $newstr;
}

// Read resource.cfg file and return raw text or array of contents
// Input: bool
// Output: array or raw text
/**
 * @param bool $raw
 *
 * @return mixed
 */
function nagiosccm_get_resource_cfg($raw = 0, $time = 1) {
    $usermacro_disable = get_option("usermacro_disable", 0);
    $usermacro_redacted = get_option("usermacro_redacted", 1);
    $usermacro_user_redacted = get_option("usermacro_user_redacted", 0);

    if ($usermacro_disable == 1) {
        return "The User Macro Component has been disabled. Contact your Administrator.";
    }

    // trigger new timestamp by default
    if ($time)
        $time = time();

    // Check if raw file was requested
    if ($raw) {
        $lines = file('/usr/local/nagios/etc/resource.cfg', FILE_IGNORE_NEW_LINES);
    } else {
        $lines = file('/usr/local/nagios/etc/resource.cfg', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    // Grab the resource.cfg and read it
    $user_macros = array();
    $user_macro_values = array();
    $lines_redacted = array();

    foreach ($lines as $k => $line) {
        if ($raw) {
            if ($usermacro_redacted || ($usermacro_user_redacted == 1 && is_admin() == false)) {
                //$lines = explode("\", $line);
                $pos = strpos($line, "=");

                if ($pos !== false) {
                    $line = substr_replace($line, "*****", $pos + 1);
                }

                $lines_redacted[] = $line;
            } else {
                // return raw file contents - Not redacted
                return $lines;
            }
        } else {
            if ($line[0] != "#") {
                list($macro, $value) = explode("=", $line);
                $user_macros[] = trim($macro);

                if ($usermacro_redacted == 1) {
                    $user_macro_values[] = "*****";
                } else if ($usermacro_user_redacted == 1 && is_admin() == false) {
                    $user_macro_values[] = "*****";
                } else {
                    $user_macro_values[] = trim($value);
                }
            }
        }
    }

    // Return array
    if (!$raw) {
        return array("user_macros" => $user_macros, "user_macro_values" => $user_macro_values, "last_read" => $time);
    } else if ($raw && ($usermacro_redacted || $usermacro_user_redacted)) {
        return $lines_redacted;
    }
}

// Verify current USER macros to existing ones and if no matches found write the new macro to resource.cfg
// Input: string, string, time
// Output: array containing return code and reponse string
/**
 * @param $macro
 * @param $new_value
 * @param $write_time
 * @return mixed
 */
function nagiosccm_add_new_macro($macro, $new_value, $write_time) {
    $current = nagiosccm_get_resource_cfg(false, false);
    $return = array();
    $datetime = get_datetime_string(time(), DT_SHORT_DATE_TIME, DF_AUTO, "null");
    $user = $_SESSION["username"];

    // Double check macro key doesn't exist
    if (in_array($macro, $current['user_macros'])) {
        $return["response"] = _("This User Macro Key already exists.  Please check the value and try again or choose another User Macro Key.");
        $return["return_code"] = 1;

        return $return;
    }

    // If macro value already exists exit
    if (in_array($new_value, $current['user_macro_values'])) {
        $return["response"] = _("This User Macro Value already exists in the resource.cfg file and can be used by using selected key.");
        $return["return_code"] = 1;

        return $return;
    }

    // Construct Macro config line with added by info
    $new_line = "\n# Created by " . $user . " - [" . $datetime . "] \n" . $macro . "=" . $new_value . "\n";

    // Write to file
    $write = file_put_contents('/usr/local/nagios/etc/resource.cfg', $new_line, FILE_APPEND);

    // Check for success
    if ($write) {
        $return["response"] = _("Successfuly added new user macro.");
        $return["return_code"] = 0;
    } else {
        $return["response"] = _("Writing to /usr/local/nagios/etc/resource.cfg failed.  Verify the file is set as user apache and group nagios with read, write and execute permissions.");
        $return["return_code"] = 1;
    }

    return json_encode($return);
}