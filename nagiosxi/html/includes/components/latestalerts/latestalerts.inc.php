<?php
//
// Latest Alerts Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: latestalerts.inc.php 155 2010-11-06 02:36:00Z egalstad $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

$latestalerts_component_name = "latestalerts";
latestalerts_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function latestalerts_component_init()
{
    global $latestalerts_component_name;
    $versionok = latestalerts_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2009R1.4B or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $latestalerts_component_name,
        COMPONENT_VERSION => '1.2.5',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays the latest alerts. ") . $desc,
        COMPONENT_TITLE => _("Latest Alerts")
    );

    register_component($latestalerts_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'latestalerts_component_addmenu');
    }

    $args = array();
    $args[DASHLET_NAME] = "latestalerts";
    $args[DASHLET_TITLE] = "Latest Alerts";
    $args[DASHLET_FUNCTION] = "latestalerts_dashlet_func";
    $args[DASHLET_DESCRIPTION] = _("Displays the latest alerts.");
    $args[DASHLET_WIDTH] = "350";
    $args[DASHLET_HEIGHT] = "250";
    $args[DASHLET_INBOARD_CLASS] = "latestalerts_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS] = "latestalerts_map_outboard";
    $args[DASHLET_CLASS] = "latestalerts_map";
    $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2011-2015 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE] = true;
    register_dashlet($args[DASHLET_NAME], $args);
}

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function latestalerts_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 114)
        return false;
    return true;
}

function latestalerts_component_addmenu($arg = null)
{
    global $latestalerts_component_name;

    $mi = find_menu_item(MENU_HOME, "menu-home-acknowledgements", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Latest Alerts"),
        "id" => "menu-home-latestalerts",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/latestalerts/'
        )
    ));
}


///////////////////////////////////////////////////////////////////////////////////////////
// AJAX FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function xicore_ajax_get_latestalerts_dashlet_html($args = null)
{

    $host = grab_array_var($args, "host", "");
    $service = grab_array_var($args, "service", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $maxitems = grab_array_var($args, "maxitems", 20);

    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    //  limit hosts by hostgroup or host
    $host_ids = array();
    //  limit by hostgroup
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup);
    } //  limit by host
    else if ($host != "") {
        $host_ids[] = get_host_id($host);
        
        if ($service == "")
            $service_ids = get_host_service_member_ids($host);
        else
            $service_ids[] =get_service_id($host,$service);
        
    }

    //  Limit service by servicegroup
    if ($servicegroup != "") {
        $host_ids = get_servicegroup_host_member_ids($servicegroup);
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    // Get host/service id string
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
    if (!empty($service_ids)) {
        foreach ($service_ids as $sid) {
            if ($y > 0)
                $service_ids_str .= ",";
            $service_ids_str .= $sid;
            $y++;
        }
    }

    $latestalerts = array();

    // get service status from backend
    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["limitrecords"] = false; // don't limit records
    // host id limiters
    if ($host_ids_str != "")
        $backendargs["host_id"] = "in:" . $host_ids_str;
    // only down and unreachable hosts
    $backendargs["current_state"] = "in:1,2";
    // order by last state change
    $backendargs["orderby"] = "last_state_change:a";

    $xml = get_xml_host_status($backendargs);
    if ($xml) {
        foreach ($xml->hoststatus as $hs) {

            $hostname = strval($hs->name);
            $currentstate = intval($hs->current_state);
            $statustext = strval($hs->status_text);
            $ts = strtotime(strval($hs->last_state_change));

            $latestalerts[$hostname] = array(
                "host_name" => $hostname,
                "latest_alert" => $ts,
                "host_statustext" => $statustext,
                "host_state" => $currentstate,
                "services_critical" => 0,
                "services_warning" => 0,
                "services_unknown" => 0,
                "services_downtime" => 0,
                "services_acknowledged" => 0,
                "services_critical_arr" => array(),
                "services_warning_arr" => array(),
                "services_unknown_arr" => array(),
                "services_downtime_arr" => array(),
                "services_acknowledged_arr" => array(),
            );
        }
    }

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
    // only non-ok services
    $backendargs["current_state"] = "in:1,2,3";
    // order by last state change
    $backendargs["orderby"] = "last_state_change:a";

    $xml = get_xml_service_status($backendargs);
    if ($xml) {
        foreach ($xml->servicestatus as $ss) {

            $hostname = strval($ss->host_name);
            $servicename = strval($ss->name);
            $currentstate = intval($ss->current_state);
            $statustext = strval($ss->status_text);
            $ts = strtotime(strval($ss->last_state_change));
            $acknowledged = intval($ss->problem_acknowledged);
            $downtime = intval($ss->scheduled_downtime_depth);

            if (!array_key_exists($hostname, $latestalerts)) {
                $latestalerts[$hostname] = array(
                    "host_name" => $hostname,
                    "latest_alert" => $ts,
                    "host_state" => 0,
                    "services_critical" => 0,
                    "services_warning" => 0,
                    "services_unknown" => 0,
                    "services_downtime" => 0,
                    "services_acknowledged" => 0,
                    "services_critical_arr" => array(),
                    "services_warning_arr" => array(),
                    "services_unknown_arr" => array(),
                    "services_downtime_arr" => array(),
                    "services_acknowledged_arr" => array(),
                );
            }
            $newarr = $latestalerts[$hostname];

            // Update latest alert if necessary
            if ($ts > $newarr["latest_alert"]) {
                $newarr["latest_alert"] = $ts;
            }

            // Increment state counters
            $svcindex = "";
            if ($acknowledged == 1) {
                $newarr["services_acknowledged"] = $newarr["services_acknowledged"] + 1;
                $svcindex = "services_acknowledged_arr";
            } else if ($downtime == 1) {
                $newarr["services_downtime"] = $newarr["services_downtime"] + 1;
                $svcindex = "services_downtime_arr";
            } else if ($currentstate == STATE_CRITICAL) {
                $newarr["services_critical"] = $newarr["services_critical"] + 1;
                $svcindex = "services_critical_arr";
            } else if ($currentstate == STATE_WARNING) {
                $newarr["services_warning"] = $newarr["services_warning"] + 1;
                $svcindex = "services_warning_arr";
            } else if ($currentstate == STATE_UNKNOWN) {
                $newarr["services_unknown"] = $newarr["services_unknown"] + 1;
                $svcindex = "services_unknown_arr";
            }

            $newarr[$svcindex][] = $servicename;

            $latestalerts[$hostname] = $newarr;
        }
    }

    $latestalerts = subval_sort($latestalerts, "latest_alert", true);

    $output = "";

    $pretitle = "";
    if ($host != "")
        $pretitle = "For Host '$host' ";
    else if ($hostgroup != "")
        $pretitle = "For Hostgroup '$hostgroup' ";
    else if ($servicegroup != "")
        $pretitle = "For Servicegroup '$servicegroup' ";

    $output .= '<div class="infotable_title">' . _('Latest Alerts'). ' ' . $pretitle . '</div>';

    $output .= '
    <table class="table table-condensed table-striped table-bordered" style="margin-bottom: 5px;">
        <thead>
            <tr>
                <th>' . _('Source') . '</th>
                <th>' . _('Latest Alert') . '</th>
                <th>' . _('Alerts') . '</th>
            </tr>
        </thead>
        <tbody>';

    if (count($latestalerts) > 0) {
        $base_url = get_base_url();
        $status_url = $base_url . "includes/components/xicore/status.php";
        $image_url = $base_url . "includes/components/latestalerts/images/";

        $currentalert = 0;
        foreach ($latestalerts as $ts => $la) {

            $currentalert++;
            if ($currentalert > $maxitems)
                break;

            // what host image should we use?
            $hoststate = $la["host_state"];
            $img = theme_image("ok_small.png");
            $output_pretext = "";
            if ($hoststate == STATE_DOWN) {
                $img = theme_image("critical_small.png");
                $output_pretext = "<img src=".$img."> " . _("Host Down").": ";
            } else if ($hoststate == STATE_UNREACHABLE) {
                $img = theme_image("critical_small.png");
                $output_pretext = "<img src=".$img."> " . _("Host Unreachable").": ";
            } else if ($la["services_critical"] > 0) {
                $img = theme_image("critical_small.png");
            } else if ($la["services_warning"] > 0) {
                $img = theme_image("warning_small.png");
            } else if ($la["services_unknown"] > 0) {
                $img = theme_image("unknown_small.png");
            } else if ($la["services_downtime"] > 0) {
                $img = theme_image("ack.png");
            } else if ($la["services_acknowledged"] > 0) {
                $img = theme_image("ack.png");
            }

            $output .= '<tr>';
            $output .= '<td valign="top" nowrap>';
            $output .= '<img src="' . $img . '"> ';
            $output .= '<a href="' . $status_url . '?show=hostdetail&host=' . urlencode($la["host_name"]) . '">';
            $output .= $la["host_name"];
            $output .= '</a>';
            //$output.=' ('.$hoststate.')';
            $output .= '&nbsp;</td>';

            $output .= '<td valign="top" nowrap>';
            $output .= get_datetime_string($la["latest_alert"]);
            $output .= '&nbsp;</td>';

            $output .= '<td valign="top">';
            // host is up, but there are service problems
            if ($hoststate == STATE_UP) {

                // critical services
                $x = 0;
                foreach ($la["services_critical_arr"] as $svc) {
                    if ($x > 0)
                        $output .= ', ';
                    else
                        $output .= '<img src="' . theme_image("critical_small.png") . '"> ';
                    $output .= '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($la["host_name"]) . '&service=' . urlencode($svc) . '">';
                    $output .= $svc;
                    $output .= '</a>';
                    $x++;
                }
                if ($x > 0)
                    $output .= " " . (($x == 1) ? _("is") : _("are")) . " " . _("Critical") . "<br>";

                // warning services
                $x = 0;
                foreach ($la["services_warning_arr"] as $svc) {
                    if ($x > 0)
                        $output .= ', ';
                    else
                        $output .= '<img src="' . theme_image("warning_small.png") . '"> ';

                    $output .= '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($la["host_name"]) . '&service=' . urlencode($svc) . '">';
                    $output .= $svc;
                    $output .= '</a>';
                    $x++;
                }
                if ($x > 0)
                    $output .= " " . (($x == 1) ? _("is") : _("are")) . " " . _("Warning") . "<br>";

                // unknown services
                $x = 0;
                foreach ($la["services_unknown_arr"] as $svc) {
                    if ($x > 0)
                        $output .= ', ';
                    else
                        $output .= '<img src="' . theme_image("unknown_small.png") . '"> ';
                    $output .= '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($la["host_name"]) . '&service=' . urlencode($svc) . '">';
                    $output .= $svc;
                    $output .= '</a>';
                    $x++;
                }
                if ($x > 0)
                    $output .= " " . (($x == 1) ? _("is") : _("are")) . " " . _("Unknown"). "<br>";


                // acknowledged/downtime
                $x = 0;
                foreach ($la["services_downtime_arr"] as $svc) {
                    if ($x > 0)
                        $output .= ', ';
                    else
                        $output .= '<img src="' . theme_image("ack.png") . '"> ';
                    $output .= '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($la["host_name"]) . '&service=' . urlencode($svc) . '">';
                    $output .= $svc;
                    $output .= '</a>';
                    $x++;
                }
                foreach ($la["services_acknowledged_arr"] as $svc) {
                    if ($x > 0)
                        $output .= ', ';
                    else
                        $output .= '<img src="' . theme_image("ack.png") . '"> ';
                    $output .= '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($la["host_name"]) . '&service=' . urlencode($svc) . '">';
                    $output .= $svc;
                    $output .= '</a>';
                    $x++;
                }
                if ($x > 0)
                    $output .= " " . (($x == 1) ? "Is" : "Are") . " Being Handled<br>";
            } // host is down or unreachable
            else {

                $output .= $output_pretext . $la["host_statustext"];
            }
            $output .= '</td>';

            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="3">' . _('No recent alerts.') . '</td></tr>';
    }

    $output .= '
    </tbody>
    </table>';

    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    return $output;
}

function latestalerts_get_service_title($s)
{
    $title = "<div class='latestalertsverticaltext'>$s</div>";
    return $title;
}

function subval_sort($a, $subkey, $reverse = false)
{
    $c = array();
    $b = array();
    foreach ($a as $k => $v) {
        $b[$k] = strtolower($v[$subkey]);
    }
    if ($reverse == false)
        asort($b);
    else
        arsort($b);
    foreach ($b as $key => $val) {
        $c[] = $a[$key];
    }
    return $c;
}