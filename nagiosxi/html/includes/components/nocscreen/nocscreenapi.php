<?php //nocscreenapi.php
//
// Nagios XI Operations Center API
//
// Copyright (c) 2011-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// 

require_once(dirname(__FILE__) . '/../../common.inc.php');

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
///////////////////MAIN////////////////////////

$summary = grab_request_var('summary', false);

if ($summary)
    noc_summary_table();
else
    show_all_tables();


////////////////FUNCTIONS//////////////////////////
function show_all_tables()
{
    noc_host_table();
    //noc_summary_table();
    noc_service_table();

}

function noc_get_host_problems()
{
    global $request;
    
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["current_state"] = "in:1,2";
    $backendargs["has_been_checked"] = 1;
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $backendargs["orderby"] = 'last_state_change:d';
    $backendargs['is_active'] = 1;
    // Hide/Show soft state
    $backendargs["current_check_attempt"] = "gt:0";
    $backendargs["max_check_attempts"] = "gt:0";
    
    if ($host != "")
        $backendargs["name"] = $host;
    else if ($hostgroup != "" || $servicegroup != "") {
        if ($hostgroup != "") {
            $host_ids = get_hostgroup_member_ids($hostgroup);
            
        } //  limit by hostgroup hosts
        else if ($servicegroup != "") {
            $host_ids = get_servicegroup_host_member_ids($servicegroup);
            
        } //  limit by host
        else if ($host != "") {
            $host_ids[] = get_host_id($host);
        }
        $y = 0;
        foreach ($host_ids as $hid) {
            if ($y > 0)
                $host_ids_str .= ",";
            $host_ids_str .= $hid;
            $y++;
        }
        
        if ($host_ids_str != "")
            $backendargs["host_id"] = "in:" . $host_ids_str;
    }
    
    $hosts_xml = get_xml_host_status($backendargs);
    if ($hosts_xml)
        return $hosts_xml;

}


function noc_get_service_problems()
{
    global $request;
    
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $state = grab_request_var("state", "");
    
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = 1;
    $backendargs["current_state"] = "in:1,2,3";
    $backendargs["has_been_checked"] = 1;
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $backendargs['is_active'] = 1;
    $backendargs["orderby"] = 'last_state_change:d';
    // Hide/Show soft state
    $backendargs["current_check_attempt"] = "gt:0";
    $backendargs["max_check_attempts"] = "gt:0";
    
    if (!empty($state))
        $backendargs["current_state"] = "in:".$state;
    
    if ($service != "") {
        $backendargs["host"] = $host;
        $backendargs["service"] = $service;
    }
    else if ($host != "")
        $backendargs["host_name"] = $host;
    else if ($hostgroup != "" || $servicegroup != "") {
        if ($hostgroup != "") {
            $host_ids = get_hostgroup_member_ids($hostgroup);
        } //  limit by servicegroup hosts
        else if ($servicegroup != "") {
            //  limit services by servicegroup
            $service_ids_str = "";
            //  limit by servicegroup
            $service_ids = get_servicegroup_member_ids($servicegroup);
        } //  limit by host
        else if ($host != "") {
            $host_ids[] = get_host_id($host);
        }
        $y = 0;
        foreach ($service_ids as $sid) {
            if ($y > 0)
                $service_ids_str .= ",";
            $service_ids_str .= $sid;
            $y++;
        }
        
        $y = 0;
            foreach ($host_ids as $hid) {
                if ($y > 0)
                    $host_ids_str .= ",";
                $host_ids_str .= $hid;
                $y++;
            }
        
        if ($service_ids_str != "")
            $backendargs["service_id"] = "in:" . $service_ids_str;
        
        if ($host_ids_str != "")
            $backendargs["host_id"] = "in:" . $host_ids_str;
    }
    $xml = get_xml_service_status($backendargs);
    return $xml;

} //end function 


function host_class($code)
{
    switch ($code) {
        case 0:
            return "hostup";
        case 1:
            return 'hostdown';
        default:
            return 'hostunreachable';
    }
}

function service_class($code)
{
    switch ($code) {
        case 0:
            return "serviceok";
        case 1:
            return 'servicewarning';
        case 2:
            return 'servicecritical';
        default:
            return 'serviceunknown';
    }
}

function noc_host_table()
{
    $hide_soft = grab_request_var("hide_soft", "");
    $hosts = noc_get_host_problems();
    $count = 0;
    $table = "
      <div id='hostdiv'>
        <table id='hosttable' class='standardtable hoststatustable table table-condensed table-striped table-bordered'>
          <tr>
            <th>" . _("Host Name") . "</th>
            <th>" . _("Duration") . "</th>
            <th>" . _("Status Information") . "</th>
          </tr>";
    //build table rows
    foreach ($hosts as $x) {
        $curattempt = intval($x->current_check_attempt);
        $maxattempt = intval($x->max_check_attempts);
        if ($hide_soft && ($curattempt != $maxattempt)) { continue; }
        if (trim("$x->name") != '') //ghost entries in ndoutils????
            noc_host_row($table, $x, $count++);
    }
    //close table
    $table .= "</table></div>";

    print $table;
}

function noc_service_table()
{
    $hide_soft = grab_request_var("hide_soft", "");
    $hosts = noc_get_service_problems();
    $count = 0;
    $table = "
      <div id='servicediv'>
        <table id='servicetable' class='standardtable servicestatustable table table-condensed table-striped table-bordered'>
          <tr>
            <th>" . _("Host Name") . "</th>
            <th>" . _("Service") . "</th>
            <th><div class='duration'>" . _("Duration") . "</div></th>
            <th>" . _("Status Information") . "</th>
          </tr>";
    //build table rows
    foreach ($hosts as $x) {
        $curattempt = intval($x->current_check_attempt);
        $maxattempt = intval($x->max_check_attempts);
        if ($hide_soft && ($curattempt != $maxattempt)) { continue; }
        if (trim("$x->name") != '') //ghost entries in ndoutils????
            noc_service_row($table, $x, $count++);
    }
    //close table
    $table .= "</table></div>";

    print $table;
}

function noc_host_row(&$table, $x, $count)
{
    //builds onto main table variable
    $class = ($count % 2 == 1) ? 'even' : 'odd';
    $stateduration = get_duration_string(time() - strtotime("$x->last_state_change"));

    //allow html tags?
    $allow_html = is_null(get_option('allow_status_html')) ? false : get_option('allow_status_html');
    $status_info = ($allow_html == true) ? html_entity_decode(strval($x->status_text)) : strval($x->status_text);

    $table .=
        "<tr class='hostrow {$class}'>
        <td class='host_name " . host_class("$x->current_state") . "'><a style='color:black' href='../xicore/status.php?show=hostdetail&host={$x->name}'>{$x->name}</a></td>
        <td><div class='duration'>{$stateduration}</div></td>
        <td class='statustext'>{$status_info}</td>
    </tr>";

}

function noc_service_row(&$table, $x, $count)
{
    //builds onto main table variable
    $class = ($count % 2 == 1) ? 'even' : 'odd';
    $stateduration = get_duration_string(time() - strtotime("$x->last_state_change"));

    //allow html tags?
    $allow_html = is_null(get_option('allow_status_html')) ? false : get_option('allow_status_html');
    $status_info = ($allow_html == true) ? html_entity_decode(strval($x->status_text)) : strval($x->status_text);

    if (host_class("$x->host_current_state") == "hostup") {
        $table .=
            "<tr class='servicerow {$class}'>
            <td class='host_name " . host_class("$x->host_current_state") . "'><a style='color:black' href='../xicore/status.php?show=hostdetail&host={$x->host_name}'>{$x->host_name}</a></td>
            <td class='description " . service_class("$x->current_state") . "'><a style='color:black' href='../xicore/status.php?show=servicedetail&host={$x->host_name}&service={$x->name}'>{$x->name}</a></td>
            <td><div class='duration'>{$stateduration}</div></td>
            <td class='statustext'>{$status_info}</td>
        </tr>";
    }
}


function noc_service_summary_data()
{
    global $request;

    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $hide_soft = grab_request_var("hide_soft", "");

    // PREP TO GET TOTAL RECORD COUNTS FROM BACKEND...
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount
    $backendargs["combinedhost"] = true; // get host status too

    if ($service != "") {
        $backendargs["host"] = $host;
        $backendargs["service"] = $service;
    }
    else if ($host != "")
        $backendargs["host_name"] = $host;
    else if ($hostgroup != "" || $servicegroup != "") {
        if ($hostgroup != "") {
            $host_ids = get_hostgroup_member_ids($hostgroup);
        } //  limit by servicegroup hosts
        else if ($servicegroup != "") {
            //  limit services by servicegroup
            $service_ids_str = "";
            //  limit by servicegroup
            $service_ids = get_servicegroup_member_ids($servicegroup);
        } //  limit by host
        else if ($host != "") {
            $host_ids[] = get_host_id($host);
        }

        $y = 0;
        foreach ($service_ids as $sid) {
            if ($y > 0)
                $service_ids_str .= ",";
            $service_ids_str .= $sid;
            $y++;
        }

        $y = 0;
        foreach ($host_ids as $hid) {
            if ($y > 0)
                $host_ids_str .= ",";
            $host_ids_str .= $hid;
            $y++;
        }

        if ($service_ids_str != "")
            $backendargs["service_id"] = "in:" . $service_ids_str;

        if ($host_ids_str != "")
            $backendargs["host_id"] = "in:" . $host_ids_str;
    }

    $xml = get_xml_service_status($backendargs);
    $total_records = 0;
    if ($xml)
        $total_records = intval($xml->recordcount);

    // get state totals (ok/pending checked later)
    $state_totals = array();
    for ($x = 1; $x <= 3; $x++) {
        $backendargs["current_state"] = $x;

        // remove from count if not hard state and hiding soft
        if ($hide_soft) {
            $backendargs["state_type"] = 1;
        }

        $xml = get_xml_service_status($backendargs);
        $state_totals[$x] = 0;
        if ($xml)
            $state_totals[$x] = intval($xml->recordcount);
    }

    // get ok (non-pending)
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 1;
    $xml = get_xml_service_status($backendargs);
    $state_totals[0] = 0;
    if ($xml)
        $state_totals[0] = intval($xml->recordcount);

    // get pending
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 0;
    $xml = get_xml_service_status($backendargs);
    $state_totals[4] = 0;
    if ($xml)
        $state_totals[4] = intval($xml->recordcount);

    // total problems
    $total_problems = $state_totals[1] + $state_totals[2] + $state_totals[3];

    // unhandled problems
    $backendargs["current_state"] = "in:1,2,3";
    unset($backendargs["has_been_checked"]);
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $backendargs["host_current_state"] = 0; // up state
    $xml = get_xml_service_status($backendargs);
    $unhandled_problems = 0;
    if ($xml)
        $unhandled_problems = intval($xml->recordcount);

    $all = array_sum($state_totals);
    return array($state_totals[0], $state_totals[1], $state_totals[2], $state_totals[3], $state_totals[4], $total_problems, $unhandled_problems, $all);

}

function noc_host_summary_data()
{
    global $request;

    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $hide_soft = grab_request_var("hide_soft", "");

    // PREP TO GET TOTAL RECORD COUNTS FROM BACKEND...
    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount

    if ($host != "")
        $backendargs["name"] = $host;
    if ($hostgroup != "" || $servicegroup != "") {
        if ($hostgroup != "") {
            $host_ids = get_hostgroup_member_ids($hostgroup);

        } //  limit by hostgroup hosts
        else if ($servicegroup != "") {
            $host_ids = get_servicegroup_host_member_ids($servicegroup);

        } //  limit by host
        else if ($host != "") {
            $host_ids[] = get_host_id($host);
        }
        $y = 0;
        foreach ($host_ids as $hid) {
            if ($y > 0)
                $host_ids_str .= ",";
            $host_ids_str .= $hid;
            $y++;
        }

        if ($host_ids_str != "")
            $backendargs["host_id"] = "in:" . $host_ids_str;
    }

    // get total hosts
    $xml = get_xml_host_status($backendargs);
    $total_records = 0;
    if ($xml) $total_records = intval($xml->recordcount);

    // get host totals (up/pending checked later)
    $state_totals = array();
    for ($x = 1; $x <= 2; $x++) {
        $backendargs["current_state"] = $x;

        // remove from count if not hard state and hiding soft
        if ($hide_soft) {
            $backendargs["state_type"] = 1;
            unset($backendargs["totals"]);
        }
        // $timerinfo[]=get_timer();
        // $xml=get_backend_xml_data($backendargs);
        $xml = get_xml_host_status($backendargs);
        if ($hide_soft) {
            $curattempt = intval($xml->hoststatus->current_check_attempt);
            $maxattempt = intval($xml->hoststatus->max_check_attempts);
            if (!empty($curattempt) && !empty($maxattempt)) {
                if ($hide_soft && ($curattempt != $maxattempt)) {
                    $backendargs["state_type"] = 0;
                }
            }
        }

        // back into record count
        $backendargs["totals"] = 1;
        $xml = get_xml_host_status($backendargs);
        $state_totals[$x] = 0;
        if ($xml)
            $state_totals[$x] = intval($xml->recordcount);
    }

    // get up (non-pending)
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 1;
    $xml = get_xml_host_status($backendargs);
    $state_totals[0] = 0;
    if ($xml)
        $state_totals[0] = intval($xml->recordcount);
    // get pending
    $backendargs["current_state"] = 0;
    $backendargs["has_been_checked"] = 0;
    $xml = get_xml_host_status($backendargs);
    $state_totals[3] = 0;
    if ($xml) $state_totals[3] = intval($xml->recordcount);

    // total problems
    $total_problems = $state_totals[1] + $state_totals[2];

    // unhandled problems
    $backendargs["current_state"] = "in:1,2";
    unset($backendargs["has_been_checked"]);
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $xml = get_xml_host_status($backendargs);
    $unhandled_problems = 0;
    if ($xml) $unhandled_problems = intval($xml->recordcount);

    $all = array_sum($state_totals);
    return array($state_totals[0], $state_totals[1], $state_totals[2], $state_totals[3], $total_problems, $unhandled_problems, $all);

}


function noc_summary_table()
{
    // Service data
    list($ok, $warning, $critical, $unknown, $spending, $sproblems, $sunhandled, $sall) = noc_service_summary_data();

    // Host data
    list($up, $down, $unreachable, $hpending, $hproblems, $hunhandled, $hall) = noc_host_summary_data();

    $table = "
    <div id='summary' class='summary'>
    <table class='standardtable service_status_summary_dashlet host_status_summary_dashlet table table-condensed table-striped table-bordered' id='summarytable'>
      <tr class='strong'>
        <th></th>
        <th>Up</th>
        <th>D</th>
        <th>UR</th>
        <th>Pe</th>
        <th>UH</th>
        <th>Pr</th>
        <th>All</th>

        <!-- service th -->
        <th>&nbsp;</th>
        <th>Ok</th>
        <th>W</th>
        <th>Cr</th>
        <th>UK</th>
        <th>Pe</th>
        <th>UH</th>
        <th>Pr</th>
        <th>All</th>
      </tr>

      <tr class='hostrow'>
        <td class='strong'> " . _("Hosts") . ": </td>
        <td class='hostup havehostup'>{$up}</td>
        <td class='hostdown havehostdown'>{$down}</td>
        <td class='serviceunknown haveserviceunknown'>{$unreachable}</td>
        <td class='hostpending havehostpending'>{$hpending}</td>
        <td class='unhandledhostproblems haveunhandledhostproblems'>{$hunhandled}</td>
        <td class='hostproblems havehostproblems'>{$hproblems}</td>
        <td class='all'>{$hall}</td>

        <!-- service stuff -->
        <td class='strong'> " . _("Services") . ": </td>
        <td class='serviceok haveserviceok'>{$ok}</td>
        <td class='servicewarning haveservicewarning'>{$warning}</td>
        <td class='servicecritical haveservicecritical'>{$critical}</td>
        <td class='serviceunknown haveserviceunknown'>{$unknown}</td>
        <td class='servicepending haveservicepending'>{$spending}</td>
        <td class='unhandledserviceproblems haveunhandledserviceproblems'>{$sunhandled}</td>
        <td class='serviceproblems haveserviceproblems'>{$sproblems}</td>
        <td class='all'>{$sall}</td>
      </tr> <!-- end services row -->
    </table>  <!-- end summary table -->
    </div> <!-- end summmary div -->";

    print $table;
}


?>
