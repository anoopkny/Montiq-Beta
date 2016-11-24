<?php
//
// Copyright (c) 2011 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 03/22/2008
// $Id: utils-metrics.inc.php 75 2015-06-18 19:40:08Z lgroschen $

//require_once(dirname(__FILE__).'/common.inc.php');


////////////////////////////////////////////////////////////////////////
//  MAIN FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param null $args
 *
 * @return array
 */
function get_service_metrics($args = null)
{

    $host = grab_array_var($args, "host", "");
    $service = grab_array_var($args, "service", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $metric = grab_array_var($args, "metric", "disk");
    $sortorder = grab_array_var($args, "sortorder", "desc");

    // special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    //  limiters
    $host_ids = array();
    $service_ids = array();
    //  limit by hostgroup
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } //  limit service by servicegroup
    else if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    } //  limit by service
    else if ($service != "" && $host != "") {
        $service_ids[] = get_service_id($host, $service);
    } //  limit by host
    else if ($host != "") {
        $host_ids[] = get_host_id($host);
    }

    // get host/service id string
    $y = 0;
    $host_ids_str = "";
    foreach ($host_ids as $hid) {
        if ($y > 0)
            $host_ids_str .= ",";
        $host_ids_str .= $hid;
        $y++;
    }
    $y = 0;
    $service_ids_str = "";
    foreach ($service_ids as $sid) {
        if ($y > 0)
            $service_ids_str .= ",";
        $service_ids_str .= $sid;
        $y++;
    }


    $metricdata = array();

    // get service status from backend
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["combinedhost"] = true; // get host status too
    // host id limiters
    if ($host_ids_str != "")
        $backendargs["host_id"] = "in:" . $host_ids_str;
    // service id limiters
    if ($service_ids_str != "")
        $backendargs["service_id"] = "in:" . $service_ids_str;

    $xml = get_xml_service_status($backendargs);

    if ($xml) {
        foreach ($xml->servicestatus as $ss) {
            $hostname = strval($ss->host_name);
            $servicename = strval($ss->name);
            $output = strval($ss->status_text);
            $perfdata = strval($ss->performance_data);
            $command = strval($ss->check_command);

            // make sure we can find metric pattern...
            if (service_matches_metric($metric, $hostname, $servicename, $output, $perfdata, $command) == false)
                continue;

            // get metric values
            $sortval = null;
            $displayval = "";
            if (get_service_metric_value($metric, $hostname, $servicename, $output, $perfdata, $command, $sortval, $displayval, $current, $uom, $warn, $crit, $min, $max) == false)
                continue;

            $metricdata[] = array(
                "host_name" => $hostname,
                "service_name" => $servicename,
                "output" => $output,
                "perfdata" => $perfdata,
                "sortval" => $sortval,
                "displayval" => $displayval,
                "current" => $current,
                "uom" => $uom,
                "warn" => $warn,
                "crit" => $crit,
                "min" => $min,
                "max" => $max,
            );
        }
    }

    // sort data
    $metricdata = array_sort_by_subval($metricdata, "sortval", ($sortorder == "desc") ? true : false);

    return $metricdata;
}


////////////////////////////////////////////////////////////////////////
//  MATCHING FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param $metric
 * @param $hostname
 * @param $servicename
 * @param $output
 * @param $perfdata
 *
 * @return bool
 */
function service_matches_metric($metric, $hostname, $servicename, $output, $perfdata, $command)
{

    $count = 0;
    switch ($metric) {
        case "load":
            if (preg_match("/^load1=/", $perfdata) > 0) //NRPE
                return true;
            if (preg_match("/check_xi_service_snmp/", $command) > 0) {//SNMP
                //display by matching the load OID
                if (preg_match("/.1.3.6.1.4.1.2021.10.1.3.1/", $command) > 0)
                    return true;
            }
            break;
        case "disk":
            if (preg_match("/[A-Z]:\\\\ Used Space/", $perfdata) > 0) // NSClient++ 
                return true;
            if (preg_match("/[A-Z]: Space/", $perfdata) > 0) // WMI
                return true;
            if (preg_match("/[0-9]*% inode=[0-9]*%/", $output) > 0) // Linux
                return true;
            if (preg_match("/disk\/logical\/.*\/used_percent/", $command) > 0) // NCPA
                return true;
            if (preg_match("/check_xi_service_snmp_\w+_storage/", $command) > 0) // SNMP
                return true;
            /*
            DISK OK - free space: / 1462 MB (22% inode=92%):
            /=5005MB;5455;6137;0;6819
            */
            break;
        case "cpu":
            if (preg_match("/check_xi_service_nsclient/", $command) > 0) { //NSclient
                if (preg_match("/CPULOAD/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_nrpe/", $command) > 0) { //NRPE
                if (preg_match("/check_cpu_stats/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_xi_ncpa_agent/", $command) > 0) { //NCPA
                if (preg_match("/cpu\/percent/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_xi_service_wmiplus/", $command) > 0) { //WMI
                if (preg_match("/checkcpu/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_xi_service_snmp_\w+_load/", $command) > 0) { //SNMP
                return true;
            }
            break;
        case "swap":
            if (preg_match("/^swap=/", $perfdata) > 0 || preg_match("/Swap_space/", $perfdata) > 0) // Linux
                return true;
            if (preg_match("/check_xi_ncpa_agent/", $command) > 0) {// Linux
                if (preg_match("/memory\/swap\/percent/", $command) > 0) {
                    return true;
                }
            }
            break;
        case "memory":
            if (preg_match("/check_xi_service_nsclient/", $command) > 0) { //NSclient
                if (preg_match("/MEMUSE/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_nrpe/", $command) > 0) { //NRPE
                if (preg_match("/check_mem/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_xi_ncpa_agent/", $command) > 0) { //NCPA
                if (preg_match("/memory\/virtual\/percent/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_xi_service_wmiplus/", $command) > 0) { //WMI
                if (preg_match("/checkmem/", $command) > 0) {
                    return true;
                }
            } else if (preg_match("/check_xi_service_snmp_\w+_storage/", $command) > 0) { //SNMP
                if (preg_match("/Physical\sMemory/", $command) > 0 || preg_match("/Memory/", $command) > 0 || preg_match("/Physical_memory/", $perfdata) > 0) {
                    return true;
                }
            }
            //echo "NO MATCH: $perfdata<BR>";
            break;
        default:
            break;
    }
    return false;
}

////////////////////////////////////////////////////////////////////////
//  METRIC VALUE FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param $metric
 * @param $hostname
 * @param $servicename
 * @param $output
 * @param $perfdata
 * @param $sortval
 * @param $displayval
 * @param $current
 * @param $uom
 * @param $warn
 * @param $crit
 * @param $min
 * @param $max
 *
 * @return bool
 */
function get_service_metric_value($metric, $hostname, $servicename, $output, $perfdata, $command, &$sortval, &$displayval, &$current, &$uom, &$warn, &$crit, &$min, &$max)
{
    // initialize display variable
    $x = "";

    switch ($metric) {
        case "load":
            if (preg_match("/^load1=/", $perfdata) > 0) {
                $perfpartspre = explode(" ", $perfdata);
                // process load1
                metrics_split_perfdata($perfpartspre[0], $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "", $metric);
                return true;
            }
            if (preg_match("/check_xi_service_snmp/", $command) > 0) {//SNMP
                //display by matching the load OID
                if (preg_match("/.1.3.6.1.4.1.2021.10.1.3.1/", $command) > 0) {
                    snmp_split_output($output, $command, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "", $metric);
                    return true;
                }
            }
            break;
        case "disk":
            if (preg_match("/[A-Z]:\\\\ Used Space/", $perfdata) > 0) { // NSClient++
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            }
            if (preg_match("/[A-Z]: Space/", $perfdata) > 0) { // WMI
                $perfpartspre = explode("; ", $perfdata);
                metrics_split_perfdata($perfpartspre[1], $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            }
            if (preg_match("/[0-9]*% inode=[0-9]*%/", $output) > 0) { // Linux
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            }
            if (preg_match("/disk\/logical\/.*\/used_percent/", $command) > 0) { // NCPA
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            }
            if (preg_match("/check_xi_service_snmp_\w+_storage/", $command) > 0) { // SNMP
                // make sure we dont display other storage metrics for SNMP other than disk
                $snmp_not_disk = array("Physical", "Physical\sMemory", "Physical\smemory", "Virtual\sMemory", "Virtual\smemory", "Memory", "Swap");

                if (preg_match('/' . implode('|', $snmp_not_disk) . '/', $command) === 0 )  {
                    metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                    return true;
                }
            }
            break;
        case "cpu":
            if (preg_match("/CPULOAD/", $command) > 0) { // NSClient++;
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            } else if (preg_match("/check_cpu_stats/", $command) > 0) { // NRPE
                $x = nrpe_split_perfdata($command, $perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);

                // check for display
                if ($x === false) {
                    return false;
                } else {
                    return true;
                }
            } else if (preg_match("/cpu\/percent/", $command) > 0) { // NCPA
                $x = ncpa_split_perfdata($command, $perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);

                // check for display
                if ($x === false) {
                    return false;
                } else {
                    return true;
                }
            } else if (preg_match("/checkcpu/", $command) > 0) { // WMI
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            } else if (preg_match("/check_xi_service_snmp_\w+_load/", $command) > 0) { // SNMP
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            }
            break;
        case "swap":
            if (preg_match("/^swap=/", $perfdata) > 0 || preg_match("/Swap_space/", $perfdata) > 0) { // Linux
                metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                return true;
            }
            if (preg_match("/check_xi_ncpa_agent/", $command) > 0) { // NCPA
                if (preg_match("/memory\/swap\/percent/", $command) > 0) {
                    $x = ncpa_split_perfdata($command, $perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);

                    // check for display
                    if ($x === false) {
                        return false;
                    } else {
                        return true;
                    }
                }
            }
            break;
        case "memory":
            if (preg_match("/check_xi_service_nsclient/", $command) > 0) { // NSClient
                if (preg_match("/MEMUSE/", $command) > 0) {
                    metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                    return true;
                }
            } else if (preg_match("/check_nrpe/", $command) > 0) { // NRPE
                if (preg_match("/check_mem/", $command) > 0) {
                    $x = nrpe_split_perfdata($command, $perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);

                    // check for display
                    if ($x === false) {
                        return false;
                    } else {
                        return true;
                    }
                }
            } else if (preg_match("/check_xi_ncpa_agent/", $command) > 0) { // NCPA
                if (preg_match("/memory\/virtual\/percent/", $command) > 0) {
                    $x = ncpa_split_perfdata($command, $perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);

                    // check for display
                    if ($x === false) {
                        return false;
                    } else {
                        return true;
                    }
                }
            } else if (preg_match("/check_xi_service_wmiplus/", $command) > 0) { // WMI
                if (preg_match("/checkmem/", $command) > 0) {
                    $x = wmi_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);

                    // check for display
                    if ($x === false) {
                        return false;
                    } else {
                        return true;
                    }
                }
            } else if (preg_match("/check_xi_service_snmp_\w+_storage/", $command) > 0) { // NSClient
                if (preg_match("/Physical\sMemory/", $command) > 0 || preg_match("/Memory/", $command) > 0 || preg_match("/Physical_memory/", $perfdata) > 0)  {
                    metrics_split_perfdata($perfdata, $current, $uom, $warn, $crit, $min, $max, $sortval, $displayval, "%", $metric);
                    return true;
                }
            }
            break;
        default:
            break;
    }

    return false;
}

/**
 * @param        $perfdata
 * @param        $current
 * @param        $uom
 * @param        $warn
 * @param        $crit
 * @param        $min
 * @param        $max
 * @param        $sortval
 * @param        $displayval
 * @param string $units
 * @param string $metric
 */
function metrics_split_perfdata($perfdata, &$current, &$uom, &$warn, &$crit, &$min, &$max, &$sortval, &$displayval, $units = "%", $metric = "")
{
    $display = 1;

    if (!empty($perfdata)) {
        $perfpartsa = explode("=", $perfdata);
        $perfpartsb = explode(";", $perfpartsa[1]);

        $current = grab_array_var($perfpartsb, 0, 0);
        $current = number_format(floatval($current), 2, '.', '');
        $uom = "";
        $warn = floatval(grab_array_var($perfpartsb, 1, 0));
        $crit = floatval(grab_array_var($perfpartsb, 2, 0));
        $min = floatval(grab_array_var($perfpartsb, 3, 0));
        $max = floatval(grab_array_var($perfpartsb, 4, 0));
    } else {
        $display = 0;
    }

    // echo "CUR=$current,WARN=$warn,CRIT=$crit,MIN=$min,MAX=$max<BR>";

    switch ($metric) {
        case "load":
            $usage = $current;
            // fake max value
            $max = 100;
            break;
        case "swap":
            // swap data is calculated as a mount of swap that is free/unused
            $usage = number_format(($max - $current) / $max * 100, 2);
            break;
        default:
            if ($max > 0)
                $usage = number_format($current / $max * 100, 2);
            else
                $usage = $current;
            break;
    }

    // make sure we have a max value
    if ($max <= 0) {
            $max = 100;
    }

    // get sort value and display value/label
    if ($usage >= 0) {
        switch ($metric) {
            case "load":
                $sortval = $usage * 6;
                if ($sortval > $max)
                    $sortval = $max;
                break;
            default:
                $sortval = $usage;
                break;
        }
        $displayval = $usage . $units;
    } else {
        $sortval = -1;
        $displayval = "N/A";
    }

    if (!$display)
        return false;
    //echo "USAGE=$usage<BR>";
}

/**
 * @param        $command
 * @param        $perfdata
 * @param        $current
 * @param        $uom
 * @param        $warn
 * @param        $crit
 * @param        $min
 * @param        $max
 * @param        $sortval
 * @param        $displayval
 * @param string $units
 * @param string $metric
 */
function nrpe_split_perfdata($command, $perfdata, &$current, &$uom, &$warn, &$crit, &$min, &$max, &$sortval, &$displayval, $units = "%", $metric = "") {

    // set constant nrpe values
    $display = 1;
    $uom = "";
    $units = "%";

    // if no data don't display
    if($perfdata == null)
        $display = 0;

    // parse warning and critical
    $command_parts = explode("!", $command);
    $pattern = '/-w .*/';
    $x = preg_match($pattern, $command_parts[2], $match);

    // check if match had errors
    if ($x == 0 || $x == false) 
        $display = 0;

    $alertstring = explode(" ", $match[0]);

    if ($alertstring[0] == "-w") {
        $warn = $alertstring[1];
    }

    if ($alertstring[2] == "-c")
        $crit = $alertstring[3];

    // Memory or CPU
    if (preg_match("/check_cpu_stats/", $command) > 0) {
        $metric = "cpu";
        $perfpartsa = explode(" ", $perfdata);
        $max = 100;

        $usage = 0;

        foreach ($perfpartsa as $k => $v) {
            $perfpartsb = explode("=", $v);

            if ($perfpartsb[0] == "user") {
                $usage = $usage + str_replace('%', '', $perfpartsb[1]);
            }

            if ($perfpartsb[0] == "system") {
                $usage = $usage + str_replace('%', '', $perfpartsb[1]);
            }
        }

        // actual aggregate cpu value
        $current = $usage;

        $sortval = $current;
        $displayval = $current . $units;

    } else if (preg_match("/check_mem/", $command) > 0) {
        $metric = "memory";
        $perfpartsa = explode(" ", $perfdata);

        foreach ($perfpartsa as $k => $v) {
            $perfpartsb = explode("=", $v);

            foreach ($perfpartsb as $k => $v) {
                // used
                if ($perfpartsb[0] == "used") {
                    $current = str_replace("MB", "", $perfpartsb[1]);
                }

                if ($perfpartsb[0] == "total") {
                    $max = str_replace("MB", "", $perfpartsb[1]);
                }
            }
        }

        // parse mem max
        if ($max > 0) {
            $sortval = number_format($current / $max * 100, 2);
        } else {
            $sortval = $current;
        }

        $displayval = $sortval . $units;
    } else {
        $display = 0;
    }

    if (!$display)
        return false;
}

/**
 * @param        $command
 * @param        $perfdata
 * @param        $current
 * @param        $uom
 * @param        $warn
 * @param        $crit
 * @param        $min
 * @param        $max
 * @param        $sortval
 * @param        $displayval
 * @param string $units
 * @param string $metric
 */
function ncpa_split_perfdata($command, $perfdata, &$current, &$uom, &$warn, &$crit, &$min, &$max, &$sortval, &$displayval, $units = "%", $metric = "") {

    // set constant ncpa values
    $display = 1;
    $uom = "";
    $units = "%";

    // if no data don't display
    if($perfdata == null) 
        $display = 0;

    // Memory or CPU
    if (preg_match("/cpu\/percent/", $command) > 0) {
        $metric = "cpu";
        $max = 100;

        // parse aggregates of supplied cpu cores
        $perfpartsa = explode(" ", $perfdata);
        $count = 0;
        $core_use = 0;

        foreach ($perfpartsa as $k => $v) {
            $perfpartsb = explode(";", $v);

            if(!empty($perfpartsb[3])) {
                $warn = $perfpartsb[3];
            } else {
                $display = 0;
            }

            if(!empty($perfpartsb[4])) {
                $crit = $perfpartsb[4];
            } else {
                $display = 0;
            }

            if(!empty($perfpartsb[2])) {
                $core_use = $core_use + intval(str_replace(str_split('=%'), '', $perfpartsb[2]));
            } else {
                $display = 0;
            }

            $count++;
        }

        // actual aggregate value
        $current = number_format($core_use / $count, 2);

        $sortval = $current;
        $displayval = $current . $units;

    } else if (preg_match("/memory\/virtual\/percent/", $command) > 0) {
        $metric = "memory";

        // parse perfdata
        $perfparts = explode(";", $perfdata);

        if(!empty($perfparts[3])) {
            $warn = $perfparts[3];
        } else {
            $display = 0;
        }

        if(!empty($perfparts[4])) {
            $crit = $perfparts[4];
        } else {
            $display = 0;
        }

        if(!empty($perfparts[2])) {
            $current = str_replace(str_split('=%'), '', $perfparts[2]);
        } else {
                $display = 0;
        }

        // actual aggregate value
        $current = number_format($current, 2);

        $sortval = $current;
        $displayval = $current . $units;
    } else if (preg_match("/memory\/swap\/percent/", $command) > 0) {
        $metric = "swap";

        // parse perfdata
        $perfparts = explode(";", $perfdata);

        if(!empty($perfparts[3])) {
            $warn = $perfparts[3];
        } else {
            $display = 0;
        }

        if(!empty($perfparts[4])) {
            $crit = $perfparts[4];
        } else {
            $display = 0;
        }

        if(!empty($perfparts[2])) {
            $current = str_replace(str_split('=%'), '', $perfparts[2]);
        } else {
                $display = 0;
        }

        // actual aggregate value
        $current = number_format($current, 2);

        $sortval = $current;
        $displayval = $current . $units;
    } else {
        $display = 0;
    }

    if (!$display)
        return false;
}

/**
 * @param        $command
 * @param        $perfdata
 * @param        $current
 * @param        $uom
 * @param        $warn
 * @param        $crit
 * @param        $min
 * @param        $max
 * @param        $sortval
 * @param        $displayval
 * @param string $units
 * @param string $metric
 */
function wmi_split_perfdata($perfdata, &$current, &$uom, &$warn, &$crit, &$min, &$max, &$sortval, &$displayval, $units = "%", $metric = "") {

    // set constant wmi memory values
    $display = 1;
    $uom = "";
    $units = "%";
    $metric = "memory";

    // if no data don't display
    if($perfdata == null)
        $display = 0;

    // parse perfdata
    $perfparts = explode(";", $perfdata);

    foreach ($perfparts as $k => $v) {
        if (preg_match("/Utilisation&apos/U", $v) == 1) {
            $current = str_replace(str_split('=%'), '', $perfparts[$k + 1]);

            if(isset($perfparts[$k + 2])) {
                $warn = $perfparts[$k + 2];
            } else {
                $display = 0;
            }

            if(isset($perfparts[$k + 3])) {
                $crit = $perfparts[$k + 3];
            } else {
                $display = 0;
            }
        }
    }

    $sortval = $current;
    $displayval = $current . $units;

    if (!$display)
        return false;
}

function snmp_split_output($output, $command, &$current, &$uom, &$warn, &$crit, &$min, &$max, &$sortval, &$displayval, $units = "%", $metric = "") {
    $uom = "";
    $units = "%";
    $metric = "load";
    $min = 0;
    $max = 100;

    // parse SNMP output
    $pattern = '/SNMP\s\w+\s-\s(.*)/';
    preg_match($pattern, $output, $snmpload);
    $snmpload = htmlspecialchars(str_replace('&quot;', "", $snmpload[1]));

    $current = $snmpload;

    $sortval = $current;
    $displayval = $current;

    // this will not display graphs as there is no prefdata to display
}


////////////////////////////////////////////////////////////////////////
//  MISC FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @return array
 */
function get_metric_names()
{

    $metrics = array(
        "disk" => _("Disk Usage"),
        "cpu" => _("CPU Usage"),
        "memory" => _("Memory Usage"),
        "load" => _("Load"),
        "swap" => _("Swap")
    );

    return $metrics;
}

/**
 * @param $name
 *
 * @return string
 */
function get_metric_value_description($name)
{

    $metrics = array(
        "disk" => _("% Utilization"),
        "cpu" => _("% Utilization"),
        "memory" => _("% Utilization"),
        "load" => _("Load"),
        "swap" => _("Swap Utilization")
    );

    $desc = grab_array_var($metrics, $name);

    return $desc;
}

/**
 * @param $name
 *
 * @return string
 */
function get_metric_description($name)
{

    $metrics = get_metric_names();
    $desc = grab_array_var($metrics, $name);

    return $desc;
}