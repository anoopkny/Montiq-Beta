<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// PERFDATA AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_perfdata_chart_html($args = null)
{

    //$output="ARGS";
    //$output.=serialize($args);
    //return $output;

    $hostname = grab_array_var($args, "hostname", "");
    $host_id = grab_array_var($args, "host_id", -1);
    $servicename = grab_array_var($args, "servicename", "");
    $service_id = grab_array_var($args, "service_id", -1);
    $source = grab_array_var($args, "source", "");
    $view = grab_array_var($args, "view", "");
    $start = nstrtotime(grab_array_var($args, "startdate", ""));
    $end = nstrtotime(grab_array_var($args, "enddate", ""));


    if ($service_id > 0)
        $auth = is_authorized_for_object_id(0, $service_id);
    else
        $auth = is_authorized_for_object_id(0, $host_id);
    if ($auth == false) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    $output = perfdata_get_graph_image_url($hostname, $servicename, $source, $view, $start, $end, $host_id, $service_id);
    $output .= "&rand=" . time();

    return $output;
}

