<?php
//
// Better Bullet Map (BBMap) Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

$bbmap_component_name = "bbmap";
bbmap_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function bbmap_component_init()
{
    global $bbmap_component_name;

    $versionok = bbmap_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<b>Error: " . _("This component requires Nagios XI 2009R1.4 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $bbmap_component_name,
        COMPONENT_VERSION => '1.1.8',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays a grid-like view of host and service status. ") . $desc,
        COMPONENT_TITLE => "BBMap"
    );

    register_component($bbmap_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'bbmap_component_addmenu');
    }

    $args = array();
    $args[DASHLET_NAME] = "bbmap";
    $args[DASHLET_TITLE] = "BBMap";
    $args[DASHLET_FUNCTION] = "bbmap_dashlet_func";
    $args[DASHLET_DESCRIPTION] = _("Displays a grid-like view of host and service status.");
    $args[DASHLET_WIDTH] = "350";
    $args[DASHLET_HEIGHT] = "250";
    $args[DASHLET_INBOARD_CLASS] = "bbmap_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS] = "bbmap_map_outboard";
    $args[DASHLET_CLASS] = "bbmap_map";
    $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2011 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE] = true;
    register_dashlet($args[DASHLET_NAME], $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function bbmap_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 114)
        return false;
    return true;
}

function bbmap_component_addmenu($arg = null)
{
    global $bbmap_component_name;

    $mi = find_menu_item(MENU_HOME, "menu-home-networkstatusmap", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => "BBmap",
        "id" => "menu-home-bbmap",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/bbmap/'
        )
    ));
}


///////////////////////////////////////////////////////////////////////////////////////////
// AJAX FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function xicore_ajax_get_bbmap_dashlet_html($args = null)
{
    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");

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

    //  limit hosts by hostgroup or host
    $host_ids = array();
    $host_ids_str = "";
    //  limit by hostgroup
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup);
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
    //  limit service by servicegroup
    $service_ids = array();
    $service_ids_str = "";
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }
    $y = 0;
    foreach ($service_ids as $sid) {
        if ($y > 0)
            $service_ids_str .= ",";
        $service_ids_str .= $sid;
        $y++;
    }


    // get service status from backend
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["combinedhost"] = true; // get host status too
    $backendargs["brevity"] = 1; // we don't everything
    // host id limiters
    if ($host_ids_str != "")
        $backendargs["host_id"] = "in:" . $host_ids_str;
    // service id limiters
    if ($service_ids_str != "")
        $backendargs["service_id"] = "in:" . $service_ids_str;
    // order by host name, service description
    $backendargs["orderby"] = "host_name:a,service_description:a";

    $xml = get_xml_service_status($backendargs);


    // get service names
    $servicenames = array();
    foreach ($xml->servicestatus as $ss) {
        $sn = strval($ss->name);
        if (!in_array($sn, $servicenames))
            $servicenames[] = $sn;
    }

    sort($servicenames);
    $cols = count($servicenames);

    $output = "";

    $pretitle = "";
    if ($host != "")
        $pretitle = _("Host") . " '$host' ";
    else if ($hostgroup != "")
        $pretitle = _(Hostgroup) . " '$hostgroup' ";
    else if ($servicegroup != "")
        $pretitle = _(Servicegroup) . " '$servicegroup' ";

    $output .= '<div class="infotable_title">' . $pretitle . _('Status Grid') . '</div>';

    $output .= '
<div class="outer">    
    <div class="inner">
        <table class="infotable table table-condensed table-striped table-bordered table-hover table-auto-width" border="1">
            <thead>
                <tr>
                    <th style="border:none" class="infotable_hosts"></th>';
    for ($x = 0; $x < $cols; $x++) {
        $output .= '<td>' . bbmap_get_service_title($servicenames[$x]) . '</td>';
    }
    if ($cols < 1)
        $output .= '<td>&nbsp;</td>';
    $output .= '
                </tr>
            </thead>
            <tbody>';

    if ($xml) {
        $base_url = get_base_url();
        $status_url = $base_url . "includes/components/xicore/status.php";
        $image_url = $base_url . "includes/components/bbmap/images/";

        $lasthost = "";
        $coloutput = array();

        if (count($xml->servicestatus) == 0) {
                $output .= '<tr><th>No Hosts</th><td>No Services</td></tr>';
        }

        foreach ($xml->servicestatus as $ss) {

            $thishost = strval($ss->host_name);

            if ($thishost != $lasthost) {

                // end last row
                if ($lasthost != "") {

                    for ($x = 0; $x < $cols; $x++) {
                        $co = "";
                        if (array_key_exists($x, $coloutput))
                            $co = $coloutput[$x];
                        $output .= '<td>' . $co . '</td>';
                    }

                    $output .= '</tr>';

                    // clear columnn output
                    unset($coloutput);
                    $coloutput = array();
                }

                $hosttext = strval($ss->host_status_text);
                $hoststate = intval($ss->host_current_state);
                $hosthbc = intval($ss->host_has_been_checked);

                $statetext = "";

                switch ($hoststate) {
                    case 0:
                        if ($hosthbc == 1) {
                            $img = $image_url . "up.png";
                            $statetext = "Up";
                        } else {
                            $img = $image_url . "pending.png";
                            $statetext = "Pending";
                        }
                        break;
                    case 1:
                        $img = $image_url . "down.png";
                        $statetext = "Down";
                        break;
                    case 2:
                        $img = $image_url . "unreachable.png";
                        $statetext = "Unreachable";
                        break;
                    default:
                        $img = "";
                        break;
                }

                // check for acknowledgements, scheduled downtime
                if ($hoststate != 0) {
                    $ack = intval($ss->host_problem_acknowledged);
                    $sdd = intval($ss->host_scheduled_downtime_depth);
                    if ($ack == 1) {
                        $statetext .= " ("._('Acknowledged').")";
                        $img = $image_url . "handled.png";
                    } else if ($sdd > 0) {
                        $statetext .= " ("._('Scheduled Downtime').")";
                        $img = $image_url . "handled.png";
                    }
                }

                $imgtitle = "Host " . $statetext . ": " . encode_form_val($hosttext);

                // start new row
                $output .= '<tr><th class="infotable_hosts">';
                $output .= '<a style="font-weight: normal;" class="tt-bind" title="'.$thishost.'" href="' . $status_url . '?show=hostdetail&host=' . urlencode($thishost) . '" target="_blank"><img src="' . $img . '" class="tt-bind-dr" alt="' . $imgtitle . '" title="' . $imgtitle . '"> ' . $thishost . '</a>';
            }

            // remember last host
            $lasthost = $thishost;

            // service status
            $service = strval($ss->name);

            $servicetext = strval($ss->status_text);
            $servicestate = intval($ss->current_state);
            $servicehbc = intval($ss->has_been_checked);

            $statetext = "";

            switch ($servicestate) {
                case 0:
                    if ($servicehbc == 1) {
                        $img = $image_url . "ok.png";
                        $statetext = "Ok";
                    } else {
                        $img = $image_url . "pending.png";
                        $statetext = "Pending";
                    }
                    break;
                case 1:
                    $img = $image_url . "warning.png";
                    $statetext = "Warning";
                    break;
                case 2:
                    $img = $image_url . "critical.png";
                    $statetext = "Critical";
                    break;
                case 3:
                    $img = $image_url . "unknown.png";
                    $statetext = "Unknown";
                    break;
                default:
                    $img = "";
                    break;
            }

            // check for acknowledgements, scheduled downtime
            if ($servicestate != 0) {
                $ack = intval($ss->problem_acknowledged);
                $sdd = intval($ss->scheduled_downtime_depth);
                if ($ack == 1) {
                    $statetext .= _(" (Acknowledged)");
                    $img = $image_url . "handled.png";
                } else if ($sdd > 0) {
                    $statetext .= _(" (Scheduled Downtime)");
                    $img = $image_url . "handled.png";
                }
            }

            // check for host acknowledgements, scheduled downtime
            if ($hoststate != 0) {
                $ack = intval($ss->host_problem_acknowledged);
                $sdd = intval($ss->host_scheduled_downtime_depth);
                if ($ack == 1) {
                    $statetext .= _(" (Host Problem Acknowledged)");
                    $img = $image_url . "handled.png";
                } else if ($sdd > 0) {
                    $statetext .= _(" (Host In Scheduled Downtime)");
                    $img = $image_url . "handled.png";
                }
            }

            $imgtitle = "" . htmlentities($service, ENT_COMPAT, 'UTF-8') . " " . $statetext . ": " . htmlentities($servicetext, ENT_COMPAT, 'UTF-8');

            // what column does this go in?
            $col = array_search($service, $servicenames);

            $coloutput[$col] = '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($thishost) . '&service=' . urlencode($service) . '" target="_blank"><img src="' . $img . '" alt="' . $imgtitle . '" title="' . $imgtitle . '" class="tt-bind-d"></a>';

        }

        // end last row
        if ($lasthost != "") {

            for ($x = 0; $x < $cols; $x++) {
                $co = "";
                if (array_key_exists($x, $coloutput))
                    $co = $coloutput[$x];
                $output .= '<td>' . $co . '</td>';
            }

            $output .= '</tr>';

            // clear columnn output
            unset($coloutput);
            $coloutput = array();
        }
    } else {
        $output .= '<tr><td  colspan="' . ($cols + 1) . '">' . _('No data to display') . '</td></tr>';
    }


    $output .= '
    </tbody>
    </table></div></div>';

    $output .= '
    <div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
    ';

    return $output;
}

function bbmap_get_service_title($s)
{
    $title = "<div class='bbmapverticaltext tt-bind-d' title='$s'>$s</div>";
    return $title;
}