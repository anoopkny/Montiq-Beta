<?php
//
// Minefield Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

$minemap_component_name = "minemap";
minemap_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function minemap_component_init()
{
    global $minemap_component_name;

    $versionok = minemap_component_checkversion();

    $desc = "";
    if (!$versionok)
        $desc = "<b>" . _("Error: This component requires Nagios XI 2009R1.2B or later.") . "</b>";

    $args = array(
        COMPONENT_NAME => $minemap_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays a grid-like view of host and service status. ") . $desc,
        COMPONENT_TITLE => _("Minemap"),
        COMPONENT_VERSION => '1.2.3'
    );

    register_component($minemap_component_name, $args);

    // add a menu link
    if ($versionok)
        register_callback(CALLBACK_MENUS_INITIALIZED, 'minemap_component_addmenu');

    // register a dashlet
    $args = array();
    $args[DASHLET_NAME] = "minemap";
    $args[DASHLET_TITLE] = "Minemap";
    $args[DASHLET_FUNCTION] = "minemap_dashlet_func";
    $args[DASHLET_DESCRIPTION] = _("Displays a grid-like view of host and service status.");
    $args[DASHLET_WIDTH] = "350";
    $args[DASHLET_HEIGHT] = "250";
    $args[DASHLET_INBOARD_CLASS] = "minemap_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS] = "minemap_map_outboard";
    $args[DASHLET_CLASS] = "minemap_map";
    $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2010 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE] = true;
    register_dashlet($args[DASHLET_NAME], $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function minemap_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    //requires greater than 2009R1.2
    if (get_product_release() < 114)
        return false;

    return true;
}

function minemap_component_addmenu($arg = null)
{
    global $minemap_component_name;

    $mi = find_menu_item(MENU_HOME, "menu-home-networkstatusmap", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Minemap"),
        "id" => "menu-home-minemap",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/minemap/',
        )
    ));

}


///////////////////////////////////////////////////////////////////////////////////////////
// AJAX FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function xicore_ajax_get_minemap_dashlet_html($args = null)
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


    $output = "";

    $pretitle = "";
    if ($host != "")
        $pretitle = "Host '$host' ";
    else if ($hostgroup != "")
        $pretitle = "Hostgroup '$hostgroup' ";
    else if ($servicegroup != "")
        $pretitle = "Servicegroup '$servicegroup' ";

    $output .= '<div class="infotable_title">' . $pretitle . _('Status Grid') . '</div>';

    //$output.='ARGS='.serialize($args).'<BR>';

    $output .= '
	<table class="infotable table table-condensed table-striped table-bordered table-hover">
	<thead>
	<tr><th>' . _('Hosts') . '</th><th>' . _('Services') . '</th></tr>
	</thead>
	<tbody>
	';

    if ($xml) {
        //$output.='<tr><td>ARGS</td><td>'.serialize($backendargs).'</td><tr>';
        //$output.='<tr><td colspan="2">'.serialize($xml).'</td><tr>';

        $base_url = get_base_url();
        $status_url = $base_url . "includes/components/xicore/status.php";
        $image_url = $base_url . "includes/components/minemap/images/";

        $lasthost = "";
        foreach ($xml->servicestatus as $ss) {
            //$output.='<tr><td>SVC</td></tr>';

            $thishost = strval($ss->host_name);

            if ($thishost != $lasthost) {

                // end last row
                if ($lasthost != "") {
                    $output .= '</td></tr>';
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
                        $statetext .= _(" (Acknowledged)");
                        $img = $image_url . "handled.png";
                    } else if ($sdd > 0) {
                        $statetext .= _(" (Scheduled Downtime)");
                        $img = $image_url . "handled.png";
                    }
                }

                $imgtitle = "Host " . $statetext . ": " . htmlentities($hosttext);

                // start new row
                $output .= '<tr><td nowrap>';
                $output .= '<a href="' . $status_url . '?show=hostdetail&host=' . urlencode($thishost) . '" target="_blank"><img src="' . $img . '" class="tt-bind-dr" alt="' . $imgtitle . '" title="' . $imgtitle . '">' . $thishost . '</a>';
                $output .= '<td>';
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

            $imgtitle = "" . encode_form_val($service) . " " . $statetext . ": " . encode_form_val($servicetext);

            $output .= '<a href="' . $status_url . '?show=servicedetail&host=' . urlencode($thishost) . '&service=' . urlencode($service) . '" target="_blank"><img src="' . $img . '" class="tt-bind-d" alt="' . $imgtitle . '" title="' . $imgtitle . '"></a>';

        }

        // end last row
        if ($lasthost != "") {
            $output .= '</td></tr>';
        }
    } else {
        $output .= '<tr><td colspan="2">No data to display</td></tr>';
    }


    $output .= '
	</tbody>
	</table>';

    $output .= '
	<div class="ajax_date">'._('Last Updated').': ' . get_datetime_string(time()) . '</div>
	';

    return $output;
}

?>