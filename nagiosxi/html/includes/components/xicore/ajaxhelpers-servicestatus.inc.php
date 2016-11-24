<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');

////////////////////////////////////////////////////////////////////////
// STATUS AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_servicestatus_table($args = null)
{
    global $lstr;

    //BENCHMARKING
    //$start = timer_start(); 

    if ($args == null)
        $args = array();

    $url = get_base_url() . "includes/components/xicore/status.php";

    // defaults
    //$sortby="host_name:a,service_description";
    $sortby = "";
    $sortorder = "asc";
    $page = 1;
    $records = 25;
    $search = "";

    //allow html tags?
    $allow_html = is_null(get_option('allow_status_html')) ? false : get_option('allow_status_html');

    // grab request variables
    $show = grab_array_var($args, "show", "services");
    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $hostattr = grab_array_var($args, "hostattr", 0);
    $serviceattr = grab_array_var($args, "serviceattr", 0);
    $hoststatustypes = grab_array_var($args, "hoststatustypes", 0);
    $servicestatustypes = grab_array_var($args, "servicestatustypes", 0);

    $sortby = grab_array_var($args, "sortby", $sortby);
    $sortorder = grab_array_var($args, "sortorder", $sortorder);
    $records = grab_array_var($args, "records", $records);
    $page = grab_array_var($args, "page", $page);
    $search = trim(grab_array_var($args, "search", $search));
    if ($search == _("Search..."))
        $search = "";

    // strip out "all" hosts
    if ($host == "all")
        $host = "";


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

    $output = "";

    // PREP TO GET DATA FROM BACKEND...
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = true; // get host status too
    // order criteria
    if ($sortby != "") {
        $backendargs["orderby"] = $sortby;
        if (isset($sortorder) && $sortorder == "desc")
            $backendargs["orderby"] .= ":d";
        else
            $backendargs["orderby"] .= ":a";
    } else {
        if ($sortorder == "desc")
            $backendargs["orderby"] = "host_name:d,service_description:a";
        else
            $backendargs["orderby"] = "host_name:a,service_description:a";
    }
    // host id limiters
    if ($host_ids_str != "")
        $backendargs["host_id"] = "in:" . $host_ids_str;
    // service id limiters
    if ($service_ids_str != "")
        $backendargs["service_id"] = "in:" . $service_ids_str;
    // search criteria
    if ($search != "") {
        $backendargs["host_name"] = "lk:" . $search . ";name=lk:" . $search . ';host_address=lk:' . $search;
    }
    // host status limiters
    if ($hoststatustypes != 0 && $hoststatustypes != HOSTSTATE_ANY) {
        $hoststatus_str = "";
        $hoststatus_ids = array();
        if (($hoststatustypes & HOSTSTATE_UP)) {
            $hoststatus_ids[] = 0;
            $backendargs["host_has_been_checked"] = 1;
        } else if (($hoststatustypes & HOSTSTATE_PENDING)) {
            $hoststatus_ids[] = 0;
            $backendargs["host_has_been_checked"] = 0;
        }
        if (($hoststatustypes & HOSTSTATE_DOWN))
            $hoststatus_ids[] = 1;
        if (($hoststatustypes & HOSTSTATE_UNREACHABLE))
            $hoststatus_ids[] = 2;
        $x = 0;
        foreach ($hoststatus_ids as $hid) {
            if ($x > 0)
                $hoststatus_str .= ",";
            $hoststatus_str .= $hid;
            $x++;
        }
        $backendargs["host_current_state"] = "in:" . $hoststatus_str;
    }
    // service status limiters
    if ($servicestatustypes != 0 && $servicestatustypes != SERVICESTATE_ANY) {
        $servicestatus_str = "";
        $servicestatus_ids = array();
        if (($servicestatustypes & SERVICESTATE_OK)) {
            $servicestatus_ids[] = 0;
            $backendargs["has_been_checked"] = 1;
        } else if (($servicestatustypes & SERVICESTATE_PENDING)) {
            $servicestatus_ids[] = 0;
            $backendargs["has_been_checked"] = 0;
        }
        if (($servicestatustypes & SERVICESTATE_WARNING))
            $servicestatus_ids[] = 1;
        if (($servicestatustypes & SERVICESTATE_UNKNOWN))
            $servicestatus_ids[] = 3;
        if (($servicestatustypes & SERVICESTATE_CRITICAL))
            $servicestatus_ids[] = 2;
        $x = 0;
        foreach ($servicestatus_ids as $sid) {
            if ($x > 0)
                $servicestatus_str .= ",";
            $servicestatus_str .= $sid;
            $x++;
        }
        $backendargs["current_state"] = "in:" . $servicestatus_str;
    }
    // host attribute limiters
    if ($hostattr != 0) {
        if (($hostattr & HOSTSTATUSATTR_ACKNOWLEDGED))
            $backendargs["host_problem_acknowledged"] = 1;
        if (($hostattr & HOSTSTATUSATTR_NOTACKNOWLEDGED))
            $backendargs["host_problem_acknowledged"] = 0;
        if (($hostattr & HOSTSTATUSATTR_INDOWNTIME))
            $backendargs["host_scheduled_downtime_depth"] = "gte:1";
        if (($hostattr & HOSTSTATUSATTR_NOTINDOWNTIME))
            $backendargs["host_scheduled_downtime_depth"] = 0;
        if (($hostattr & HOSTSTATUSATTR_ISFLAPPING))
            $backendargs["host_is_flapping"] = 1;
        if (($hostattr & HOSTSTATUSATTR_ISNOTFLAPPING))
            $backendargs["host_is_flapping"] = 0;
        if (($hostattr & HOSTSTATUSATTR_CHECKSDISABLED))
            $backendargs["host_active_checks_enabled"] = 0;
        if (($hostattr & HOSTSTATUSATTR_CHECKSENABLED))
            $backendargs["host_active_checks_enabled"] = 1;
        if (($hostattr & HOSTSTATUSATTR_NOTIFICATIONSDISABLED))
            $backendargs["host_notifications_enabled"] = 0;
        if (($hostattr & HOSTSTATUSATTR_NOTIFICATIONSENABLED))
            $backendargs["host_notifications_enabled"] = 1;
        if (($hostattr & HOSTSTATUSATTR_HARDSTATE))
            $backendargs["host_state_type"] = 1;
        if (($hostattr & HOSTSTATUSATTR_SOFTSTATE))
            $backendargs["host_state_type"] = 0;

        // these may not all be implemented by the backend yet...
        if (($hostattr & HOSTSTATUSATTR_EVENTHANDLERDISABLED))
            $backendargs["host_event_handler_enabled"] = 0;
        if (($hostattr & HOSTSTATUSATTR_EVENTHANDLERENABLED))
            $backendargs["host_event_handler_enabled"] = 1;
        if (($hostattr & HOSTSTATUSATTR_FLAPDETECTIONDISABLED))
            $backendargs["host_flap_detection_enabled"] = 0;
        if (($hostattr & HOSTSTATUSATTR_FLAPDETECTIONENABLED))
            $backendargs["host_flap_detection_enabled"] = 1;
        if (($hostattr & HOSTSTATUSATTR_PASSIVECHECKSDISABLED))
            $backendargs["host_passive_checks_enabled"] = 0;
        if (($hostattr & HOSTSTATUSATTR_PASSIVECHECKSENABLED))
            $backendargs["host_passive_checks_enabled"] = 1;
        if (($hostattr & HOSTSTATUSATTR_PASSIVECHECK))
            $backendargs["host_check_type"] = 0;
        if (($hostattr & HOSTSTATUSATTR_ACTIVECHECK))
            $backendargs["host_check_type"] = 1;
    }
    // service attribute limiters
    if ($serviceattr != 0) {
        if (($serviceattr & SERVICESTATUSATTR_ACKNOWLEDGED))
            $backendargs["problem_acknowledged"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_NOTACKNOWLEDGED))
            $backendargs["problem_acknowledged"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_INDOWNTIME))
            $backendargs["scheduled_downtime_depth"] = "gte:1";
        if (($serviceattr & SERVICESTATUSATTR_NOTINDOWNTIME))
            $backendargs["scheduled_downtime_depth"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_ISFLAPPING))
            $backendargs["is_flapping"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_ISNOTFLAPPING))
            $backendargs["is_flapping"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_CHECKSDISABLED))
            $backendargs["active_checks_enabled"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_CHECKSENABLED))
            $backendargs["active_checks_enabled"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_NOTIFICATIONSDISABLED))
            $backendargs["notifications_enabled"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_NOTIFICATIONSENABLED))
            $backendargs["notifications_enabled"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_HARDSTATE))
            $backendargs["state_type"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_SOFTSTATE))
            $backendargs["state_type"] = 0;

        // these may not all be implemented by the backend yet...
        if (($serviceattr & SERVICESTATUSATTR_EVENTHANDLERDISABLED))
            $backendargs["event_handler_enabled"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_EVENTHANDLERENABLED))
            $backendargs["event_handler_enabled"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_FLAPDETECTIONDISABLED))
            $backendargs["flap_detection_enabled"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_FLAPDETECTIONENABLED))
            $backendargs["flap_detection_enabled"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_PASSIVECHECKSDISABLED))
            $backendargs["passive_checks_enabled"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_PASSIVECHECKSENABLED))
            $backendargs["passive_checks_enabled"] = 1;
        if (($serviceattr & SERVICESTATUSATTR_PASSIVECHECK))
            $backendargs["check_type"] = 0;
        if (($serviceattr & SERVICESTATUSATTR_ACTIVECHECK))
            $backendargs["check_type"] = 1;
    }


    // FIRST GET TOTAL RECORD COUNT FROM BACKEND...
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount     
    // get result from backend
    //echo "BACKEND1: ".serialize($backendargs)."<BR>";
    //$xml=get_backend_xml_data($backendargs);

    $xml = get_xml_service_status($backendargs);
    // how many total services do we have?
    $total_records = 0;
    if ($xml)
        $total_records = intval($xml->recordcount);

    // GET RECORDS FROM BACKEND...
    unset($backendargs["limitrecords"]);
    unset($backendargs["totals"]);
    // record-limiters
    $backendargs["records"] = $records . ":" . (($page - 1) * $records);
    // get result from backend
    //echo "BACKEND2: ".serialize($backendargs)."<BR>";
    //print_r($backendargs);
    //$xml=get_backend_xml_data($backendargs);
    $xml = get_xml_service_status($backendargs);

    // get comments - we need this later...
    $backendargs = array();
    $backendargs["cmd"] = "getcomments";
    $backendargs["brevity"] = 1;
    $backendargs["orderby"] = 'comment_id:a';
    //$commentsxml=get_backend_xml_data($backendargs);
    $commentsxml = get_xml_comments($backendargs);
    $comments = array();
    if ($commentsxml != null) {
        foreach ($commentsxml->comment as $c) {
            $objid = intval($c->object_id);
            $comments[$objid] = $c->comment_data;
        }
    }
    //print_r($comments);


    /////////////////////////////////////// NEW

    // get table paging info - reset page number if necessary
    $pager_args = array(
        "sortby" => $sortby,
        "sortorder" => $sortorder,
        "search" => $search,
        "show" => $show,
        "hoststatustypes" => $hoststatustypes,
        "servicestatustypes" => $servicestatustypes,
        "hostattr" => $hostattr,
        "serviceattr" => $serviceattr,
        "host" => $host,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup,
    );

    $pager_results = get_table_pager_info($url, $total_records, $page, $records, $pager_args);

    $output .= "<form action='" . $url . "'>";
    $output .= "<input type='hidden' name='show' value=\"" . encode_form_val($show) . "\">\n";
    $output .= "<input type='hidden' name='sortby' value=\"" . encode_form_val($sortby) . "\">\n";
    $output .= "<input type='hidden' name='sortorder' value=\"" . encode_form_val($sortorder) . "\">\n";
    $output .= "<input type='hidden' name='host' value=\"" . encode_form_val($host) . "\">\n";
    $output .= "<input type='hidden' name='hostgroup' value=\"" . encode_form_val($hostgroup) . "\">\n";
    $output .= "<input type='hidden' name='servicegroup' value=\"" . encode_form_val($servicegroup) . "\">\n";
    $output .= "<input type='hidden' name='hoststatustypes' value=\"" . encode_form_val($hoststatustypes) . "\">\n";
    $output .= "<input type='hidden' name='servicestatustypes' value=\"" . encode_form_val($servicestatustypes) . "\">\n";
    $output .= "<input type='hidden' name='hostattr' value=\"" . encode_form_val($hostattr) . "\">\n";
    $output .= "<input type='hidden' name='serviceattr' value=\"" . encode_form_val($serviceattr) . "\">\n";
    $output .= "<input type='hidden' name='search' value=\"" . encode_form_val($search) . "\">\n";

    $output .= '<div id="statusTableContainer" class="tableContainer">';

    $output .= '<div class="tableHeader">
    <div class="tableTopText">';

    $filterargs = array(
        "host" => $host,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup,
        "search" => $search,
        "show" => $show,
    );
    $output .= get_status_view_filters_html($show, $filterargs, $hostattr, $serviceattr, $hoststatustypes, $servicestatustypes, $url);

    $output .= '</div></div>';

    $id = "servicestatustable_" . random_string(6);

    $clear_args = array(
        "sortby" => $sortby,
        "search" => "",
        "show" => $show,
    );
    $output .= '<div class="xi-table-recordcount">'.table_record_count_text($pager_results, $search, true, $clear_args, $url).'</div>';

    $records_options = array("5", "10", "15", "25", "50", "100", "250", "500", "1000");
    $output .= '<div class="xi-table-box">'.get_table_record_pager($pager_results, $records_options).'</div>';

    // Get custom column headers
    $cbdata = array(
        "objecttype" => OBJECTTYPE_SERVICE,
        "table_headers" => array(),
        "allow_html" => $allow_html,
    );
    do_callbacks(CALLBACK_CUSTOM_TABLE_HEADERS, $cbdata);
    $customheaders = grab_array_var($cbdata, "table_headers", array());
    
    $headercols = count($customheaders) + 7;
    
    $output .= "<div class='clear'></div>";
    $output .= "<table class='tablesorter servicestatustable table table-condensed table-striped table-bordered' id='" . $id . "'>";
    $output .= "<thead><tr>";

    // Extra arts for sorted table header
    $extra_args = array();

    // Add extra args that were passed to us
    foreach ($args as $var => $val) {
        if ($var == "sortby" || $var == "sortorder") {
            continue;
        }
        $extra_args[$var] = $val;
    }
    $extra_args["show"] = "services";

    // Sorted table header
    $output .= sorted_table_header($sortby, $sortorder, "", _("Host"), $extra_args, "", $url);
    $output .= sorted_table_header($sortby, $sortorder, "service_description", _("Service"), $extra_args, "", $url);
    $output .= sorted_table_header($sortby, $sortorder, "current_state", _("Status"), $extra_args, "", $url);
    $output .= sorted_table_header($sortby, $sortorder, "last_state_change", _("Duration"), $extra_args, "", $url);
    $output .= sorted_table_header($sortby, $sortorder, "current_check_attempt", _("Attempt"), $extra_args, "", $url);
    $output .= sorted_table_header($sortby, $sortorder, "last_check", _("Last Check"), $extra_args, "", $url);
    $output .= sorted_table_header($sortby, $sortorder, "status_text", _("Status Information"), $extra_args, "", $url);

    //add custom headers if they exist
    foreach ($customheaders as $header) {
        if (!empty($header))
            $output .= $header;
    }

    //close table header    
    $output .= "</tr>\n";
    $output .= "</thead>\n";
    $output .= "<tbody>\n";

    $last_host_name = "";
    $current_service = 0;
    $display_host_alias = get_user_meta(0,'display_host_alias');
    $display_service_alias = get_user_meta(0,'display_service_alias');

    foreach ($xml->servicestatus as $x) {

        $current_service++;

        if (($current_service % 2) == 0)
            $rowclass = "even";
        else
            $rowclass = "odd";

        // get the host name
        $host_name = strval($x->host_name);

        $host_address = strval($x->host_address);

        if ($last_host_name != $host_name)
            $display_host_name = $host_name;
        else
            $display_host_name = "";
        $last_host_name = $host_name;

        // host status 
        $host_current_state = intval($x->host_current_state);
        switch ($host_current_state) {
            case 0:
                $host_has_been_checked = intval($x->host_has_been_checked);
                if ($host_has_been_checked == 1)
                    $host_status_class = "hostup";
                else
                    $host_status_class = "hostpending";
                break;
            case 1:
                $host_status_class = "hostdown";
                break;
            case 2:
                $host_status_class = "hostunreachable";
                break;
            default:
                $host_status_class = "";
                break;
        }

        // service name
        $service_name = strval($x->name);

        // service status 
        $current_state = intval($x->current_state);
        switch ($current_state) {
            case 0:
                $status_string = _("Ok");
                $service_status_class = "serviceok";
                break;
            case 1:
                $status_string = _("Warning");
                $service_status_class = "servicewarning";
                break;
            case 2:
                $status_string = _("Critical");
                $service_status_class = "servicecritical";
                break;
            case 3:
                $status_string = _("Unknown");
                $service_status_class = "serviceunknown";
                break;
            default:
                $status_string = "";
                $service_status_class = "";
                break;
        }
        $has_been_checked = intval($x->has_been_checked);
        if ($has_been_checked == 0) {
            $status_string = _("Pending");
            $service_status_class = "servicepending";
        }

        // host name cell
        $host_name_cell = "";
        if ($display_host_name != "") {
            $host_icons = "";
            // host comments
            if (array_key_exists(intval($x->host_id), $comments)) {
                $host_icons .= get_host_status_note_image("hascomments.png", $comments[intval($x->host_id)]);
            }
            // flapping
            if (intval($x->host_is_flapping) == 1) {
                $host_icons .= get_host_status_note_image("flapping.png", _("This host is flapping"));
            }
            // acknowledged
            if (intval($x->host_problem_acknowledged) == 1) {
                $host_icons .= get_host_status_note_image("ack.png", _("This host problem has been acknowledged"));
            }
            $passive_checks_enabled = intval($x->host_passive_checks_enabled);
            $active_checks_enabled = intval($x->host_active_checks_enabled);
            // passive only
            if ($active_checks_enabled == 0 && $passive_checks_enabled == 1) {
                $host_icons .= get_host_status_note_image("passiveonly.png", _("Passive Only Check"));
            }
            // notifications
            if (intval($x->host_notifications_enabled) == 0) {
                $host_icons .= get_host_status_note_image("nonotifications.png", _("Notifications are disabled for this host"));
            }
            // downtime
            if (intval($x->host_scheduled_downtime_depth) > 0) {
                $host_icons .= get_host_status_note_image("downtime.png", _("This host is in scheduled downtime"));
            }
            // host icon
            $host_icons .= get_object_icon_html($x->host_icon_image, $x->host_icon_image_alt);

            $show_host_name = ($display_host_alias && !empty($x->host_alias)) ? $host_name . " (". $x->host_alias . ")" : $host_name;
            
            $host_name_cell .= "<div class='hostname'><a href='" . get_host_status_detail_link($host_name) . "' title='" . $host_address . "'>" . $show_host_name . "</a></div>";
            //custom icons
            // get custom host icons
            $extra_icons = '';
            $cbdata = array(
                "objecttype" => OBJECTTYPE_HOST,
                "host_name" => $host_name,
                "object_id" => intval($x->host_id),
                "object_data" => $x, //xml host object
                "allow_html" => $allow_html,
                "icons" => array(),
            );
            do_callbacks(CALLBACK_CUSTOM_TABLE_ICONS, $cbdata);
            $custom_icons = grab_array_var($cbdata, "icons", array());

            //add custom icons if they exist
            foreach ($custom_icons as $icon)
                if (!empty($icon)) $extra_icons .= strip_non_img_from_table_icons($allow_html, $icon);

            //add custom icons to cell
            $host_name_cell .= "<div class='extraicons'>" . $extra_icons . "</div>";
            
            $host_name_cell .= "<div class='hosticons'>" . $host_icons;
            // service details link
            $url = get_base_url() . "includes/components/xicore/status.php?show=services&host=".urlencode($host_name);
            $alttext = _("View service status details for this host");
            $host_name_cell .= "<a href='" . $url . "'><img src='" . theme_image("statusdetailmulti.png") . "' alt='" . $alttext . "' title='" . $alttext . "'></a>";
            
            $host_name_cell .= "</div>";

        } else {
            // we're not displaying the host name...
            $host_status_class = "empty";
        }

        // service name cell
        $service_name_cell = "";
        $service_icons = "";
        // service comments
        if (array_key_exists(intval($x->service_id), $comments)) {
            $service_icons .= get_service_status_note_image("hascomments.png", encode_form_val(strip_tags($comments[intval($x->service_id)])));
        }
        // flapping
        if (intval($x->is_flapping) == 1) {
            $service_icons .= get_service_status_note_image("flapping.png", _("This service is flapping"));
        }
        // acknowledged
        if (intval($x->problem_acknowledged) == 1) {
            $service_icons .= get_service_status_note_image("ack.png", _("This service problem has been acknowledged"));
        }
        $passive_checks_enabled = intval($x->passive_checks_enabled);
        $active_checks_enabled = intval($x->active_checks_enabled);
        // passive only
        if ($active_checks_enabled == 0 && $passive_checks_enabled == 1) {
            $service_icons .= get_service_status_note_image("passiveonly.png", _("Passive Only Check"));
        }
        // notifications
        if (intval($x->notifications_enabled) == 0) {
            $service_icons .= get_service_status_note_image("nonotifications.png", _("Notifications are disabled for this service"));
        }
        // downtime
        if (intval($x->scheduled_downtime_depth) > 0) {
            $service_icons .= get_service_status_note_image("downtime.png", _("This service is in scheduled downtime"));
        }
        // service icons
        $service_icons .= get_object_icon_html($x->icon_image, $x->icon_image_alt);

        $show_service_name = ($display_service_alias && !empty($x->display_name)) ? $service_name . " (". strval($x->display_name) . ")" : $service_name;
        $service_name_cell .= "<div class='servicename'><a href='" . get_service_status_detail_link($host_name, $service_name) . "'>" . $show_service_name . "</a></div>";
        
        // get custom service icons
        $extra_icons = '';
        $cbdata = array(
            "objecttype" => OBJECTTYPE_SERVICE,
            "host_name" => $host_name,
            "service_description" => $service_name,
            "object_id" => intval($x->service_id),
            "object_data" => $x, //xml host object
            "allow_html" => $allow_html,
            "icons" => array(),
        );

        do_callbacks(CALLBACK_CUSTOM_TABLE_ICONS, $cbdata);
        $custom_icons = grab_array_var($cbdata, "icons", array());

        //add custom icons if they exist
        foreach ($custom_icons as $icon)
            if (!empty($icon)) $extra_icons .= strip_non_img_from_table_icons($allow_html, $icon);

        //add icons to cell
        $service_name_cell .= "<div class='extraicons'>" . $extra_icons . "</div>";
        
        $service_name_cell .= "<div class='serviceicons'><a href='" . get_service_status_detail_link($host_name, $service_name) . "'>" . $service_icons . "</a>";
        $service_name_cell .= "</div>";

        

        // status cell
        $status_cell = "";
        //$status_cell.="<div class='".$service_status_class."'>";
        $status_cell .= $status_string;
        //$status_cell.="</div>";

        // last check
        $last_check_time = strval($x->last_check);
        $last_check = get_datetime_string_from_datetime($last_check_time, "", DT_SHORT_DATE_TIME, DF_AUTO, "N/A");

        // current attempt
        $current_attempt = intval($x->current_check_attempt);
        $max_attempts = intval($x->max_check_attempts);

        // last state change / duration
        //$last_state_change_time=strval($x->last_state_change);
        //$last_state_change=get_datetime_string_from_datetime($last_state_change_time,"",DT_SHORT_DATE_TIME,DF_AUTO,"N/A");
        $last_state_change_time = strtotime($x->last_state_change);
        if ($last_state_change_time == 0)
            $statedurationstr = "N/A";
        else
            $statedurationstr = get_duration_string(time() - $last_state_change_time);
        //$statedurationstr.=" ".get_datetime_string_from_datetime($x->last_state_change,"",DT_SHORT_DATE_TIME,DF_AUTO,"Never");

        // status information       //added option to allow html tags - MG 12/29/2011
        $status_info = ($allow_html == true) ? html_entity_decode(strval($x->status_text)) : strval($x->status_text);

        if ($has_been_checked == 0) {
            $should_be_scheduled = intval($x->should_be_scheduled);
            $next_check_time = strval($x->next_check);
            $next_check = get_datetime_string_from_datetime($next_check_time, "", DT_SHORT_DATE_TIME, DF_AUTO, "N/A");
            if ($should_be_scheduled == 1) {
                $status_info = _("Service check is pending...");
                if (strtotime($next_check_time) != 0)
                    $status_info .= _(" Check is scheduled for ") . $next_check;
            } else
                $status_info = _("No check results for service yet...");
        }

        //table row output
        $output .= "<tr class='" . $rowclass . "'>
            <td style='white-space: nowrap' class='" . $host_status_class . "'>" . $host_name_cell . "</td>
            <td  style='white-space: nowrap'>" . $service_name_cell . "</td><td class='" . $service_status_class . "'>" . $status_cell . "</td>
            <td nowrap>" . $statedurationstr . "</td>
            <td>" . $current_attempt . "/" . $max_attempts . "</td>
            <td nowrap>" . $last_check . "</td>
            <td>" . $status_info . "</td>";

        //add any custom columns data       
        //update array from earlier
        unset($cbdata["icons"]);
        $cbdata["table_data"] = array();
        $cbdata["allow_html"] = $allow_html;

        do_callbacks(CALLBACK_CUSTOM_TABLE_DATA, $cbdata);
        $custom_data = grab_array_var($cbdata, "table_data", array());

        // add custom data if it exists
        foreach ($custom_data as $data)
            if (!empty($data))
                $output .= strip_html_from_table_data($allow_html, $data);

        //close table row
        $output .= "</tr>";
    }
    if ($current_service == 0)
        $output .= "<tr><td colspan='".$headercols."'>" . _("No matching services found") . "</td></tr>";

    $output .= "</tbody>";
    $output .= "</table>";

    $records_options = array("5", "10", "15", "25", "50", "100", "250", "500", "1000");
    $output .= '<div class="xi-table-box">'.get_table_record_pager($pager_results, $records_options).'</div>';

    $output .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    $output .= "</form>\n";

    $output .= "</div><script>init_sorted_tables();</script><!-- tableContainer -->";

    //echo "TOTAL: ".timer_stop($start)."<br />"; 

    return $output;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_service_status_quick_actions_html($args = null)
{

    $hostname = grab_array_var($args, "hostname", "");
    $servicename = urldecode(grab_array_var($args, "servicename", ""));
    $service_id = grab_array_var($args, "service_id", -1);

    $auth = is_authorized_for_service(0, $hostname, $servicename);

    if ($auth == false) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    // Save this for later
    $auth_command = is_authorized_for_service_command(0, $hostname, $servicename);

    // Get service status
    $args = array(
        "cmd" => "getservicestatus",
        "service_id" => $service_id,
        "combinedhost" => 1,
    );

    $xml = get_xml_service_status($args);

    $output = '';
    if ($xml == null) {
        //$output.="No data";
    } else {

        if ($auth_command) {

            // Initialze some stuff we'll use a few times...
            $cmd = array(
                "command" => COMMAND_NAGIOSCORE_SUBMITCOMMAND,
            );
            $cmd["command_args"] = array(
                "host_name" => $hostname,
                "service_name" => $servicename,
            );

            // ACKNOWLEDGE PROBLEM
            if (intval($xml->servicestatus->current_state) != 0 && intval($xml->servicestatus->problem_acknowledged) == 0) {
                $urlbase = get_base_url() . "includes/components/nagioscore/ui/cmd.php?cmd_typ=";
                $urlmod = "&host=" . urlencode($hostname) . "&service=" . urlencode($servicename) . "&com_data=Problem has been acknowledged";
                //$output .= '<li>' . get_object_command_link($urlbase . NAGIOSCORE_CMD_ACKNOWLEDGE_SVC_PROBLEM . $urlmod, "ack.gif", _("Acknowledge this problem")) . '</li>';
                $output .= '<li><a class="cmdlink" data-modal="add-ack" data-cmd-type="'.NAGIOSCORE_CMD_ACKNOWLEDGE_SVC_PROBLEM.'"><img src="'.theme_image('ack_add.png').'">'._('Acknowledge this problem').'</a></li>';
            }

            // NOTIFICATIONS
            if (intval($xml->servicestatus->notifications_enabled) == 1) {
                $cmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DISABLE_SVC_NOTIFICATIONS;
                $output .= '<li>' . get_service_detail_command_link($cmd, "nonotifications.png", _("Disable notifications")) . '</li>';
            } else {
                $cmd["command_args"]["cmd"] = NAGIOSCORE_CMD_ENABLE_SVC_NOTIFICATIONS;
                $output .= '<li>' . get_service_detail_command_link($cmd, "enablenotifications.png", _("Enable notifications")) . '</li>';
            }

            // SCHEDULE CHECK
            // Changed the scheduled check to be a forced check (default in Nagios Core)
            $cmd["command_args"]["cmd"] = NAGIOSCORE_CMD_SCHEDULE_FORCED_SVC_CHECK;
            $cmd["command_args"]["start_time"] = time();
            $output .= '<li>' . get_service_detail_command_link($cmd, "arrow_refresh.png", _("Force an immediate check")) . '</li>';
        }

        // Additional actions...
        $cbdata = array(
            "hostname" => $hostname,
            "servicename" => $servicename,
            "service_id" => $service_id,
            "servicestatus_xml" => $xml,
            "actions" => array(),
        );
        do_callbacks(CALLBACK_SERVICE_DETAIL_ACTION_LINK, $cbdata);
        $customactions = grab_array_var($cbdata, "actions", array());
        foreach ($customactions as $ca) {
            $output .= $ca;
        }
    }

    if ($output == "") {
        $output = "<li>" . _("No actions are available") . "</li>";
    }

    return $output;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_service_status_detailed_info_html($args = null)
{

    $hostname = grab_array_var($args, "hostname", "");
    $servicename = urldecode(grab_array_var($args, "servicename", ""));
    $service_id = grab_array_var($args, "service_id", -1);
    $display = grab_array_var($args, "display", "simple");

    $auth = is_authorized_for_service(0, $hostname, $servicename);

    if ($auth == false) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    // get service status
    $args = array(
        "cmd" => "getservicestatus",
        "service_id" => $service_id,
    );
    //$xml=get_backend_xml_data($args);
    $xml = get_xml_service_status($args);

    $output = '';
    if ($xml == null) {
        $output .= "No data";
    } else {

        $has_been_checked = intval($xml->servicestatus->has_been_checked);
        $current_state = intval($xml->servicestatus->current_state);

        switch ($current_state) {
            case 0:
                $statestr = _("Ok");
                $statecolor = 'fa-ok';
                break;
            case 1:
                $statestr = _("Warning");
                $statecolor = 'fa-warning';
                break;
            case 2:
                $statestr = _("Critical");
                $statecolor = 'fa-critical';
                break;
            case 3:
                $statestr = _("Unknown");
                $statecolor = 'fa-unknown';
                break;
            default:
                break;
        }
        if ($has_been_checked == 0) {
            $statestr = _("Pending");
            $statecolor = 'fa-pending';
        }

        if ($display == "advanced")
            $title = _("Advanced Status Details");
        else
            $title = _("Status Details");

        $output .= '
        <div style="float: left; margin-right: 25px;">
        <div class="infotable_title">' . $title . '</div>
        <table class="table table-condensed table-striped table-bordered table-no-margin" style="width: 400px;">
        <thead>
        </thead>
        <tbody>
        ';

        $output .= '<tr><td style="width: 140px;">' . _('Service State') . ':</td><td><i class="fa fa-circle ' . $statecolor . '"></i> ' . $statestr . '</td></tr>';

        $last_state_change_time = strtotime($xml->servicestatus->last_state_change);
        if ($last_state_change_time == 0)
            $statedurationstr = "N/A";
        else
            $statedurationstr = get_duration_string(time() - $last_state_change_time);
        $output .= '<tr><td>' . _('Duration') . ':</td><td>' . $statedurationstr . '</td></tr>';

        $state_type = intval($xml->servicestatus->state_type);
        if ($display == "advanced") {
            if ($state_type == STATETYPE_HARD)
                $statetypestr = _("Hard");
            else
                $statetypestr = _("Soft");
            $output .= '<tr><td>' . _('State Type') . ':</td><td>' . $statetypestr . '</td></tr>';
            $output .= '<tr><td>' . _('Current Check') . ':</td><td>' . $xml->servicestatus->current_check_attempt . ' of ' . $xml->servicestatus->max_check_attempts . '</td></tr>';
        } else {
            if ($state_type == STATETYPE_SOFT) {
                $output .= '<tr><td>' . _('Service Stability') . ':</td><td>Changing</td></tr>';
                $output .= '<tr><td>' . _('Current Check') . ':</td><td>' . $xml->servicestatus->current_check_attempt . ' of ' . $xml->servicestatus->max_check_attempts . '</td></tr>';
            } else {
                $output .= '<tr><td>' . _('Service Stability') . ':</td><td>' . _('Unchanging (stable)') . '</td></tr>';
            }
        }

        $lastcheck = get_datetime_string_from_datetime($xml->servicestatus->last_check, "", DT_SHORT_DATE_TIME, DF_AUTO, _("Never"));
        $output .= '<tr><td>' . _('Last Check') . ':</td><td>' . $lastcheck . '</td></tr>';

        $nextcheck = get_datetime_string_from_datetime($xml->servicestatus->next_check, "", DT_SHORT_DATE_TIME, DF_AUTO, _("Not scheduled"));
        $output .= '<tr><td>' . _('Next Check') . ':</td><td>' . $nextcheck . '</td></tr>';

        if ($display == "advanced") {

            $laststatechange = get_datetime_string_from_datetime($xml->servicestatus->last_state_change, "", DT_SHORT_DATE_TIME, DF_AUTO, _("Never"));
            $output .= '<tr><td nowrap>' . _('Last State Change') . ':</td><td>' . $laststatechange . '</td></tr>';

            $lastnotification = get_datetime_string_from_datetime($xml->servicestatus->last_notification, "", DT_SHORT_DATE_TIME, DF_AUTO, _("Never"));
            $output .= '<tr><td>' . _('Last Notification') . ':</td><td>' . $lastnotification . '</td></tr>';

            if ($xml->servicestatus->check_type == ACTIVE_CHECK)
                $checktype = _("Active");
            else
                $checktype = _("Passive");
            $output .= '<tr><td valign="top" nowrap>' . _('Check Type') . ':</td><td>' . $checktype . '</td></tr>';

            $output .= '<tr><td valign="top" nowrap>' . _('Check Latency') . ':</td><td>' . $xml->servicestatus->latency . ' seconds</td></tr>';

            $output .= '<tr><td valign="top" nowrap>' . _('Execution Time') . ':</td><td>' . $xml->servicestatus->execution_time . ' seconds</td></tr>';

            $output .= '<tr><td valign="top" nowrap>' . _('State Change') . ':</td><td>' . $xml->servicestatus->percent_state_change . '%</td></tr>';

            $output .= '<tr><td valign="top" nowrap>' . _('Performance Data') . ':</td><td>' . $xml->servicestatus->performance_data . '</td></tr>';
        }

        $notesoutput = "";

        if (intval($xml->servicestatus->problem_acknowledged) == 1) {
            $attr_text = _("Service problem has been acknowledged");
            $attr_icon = theme_image("ack.png");
            $attr_icon_alt = $attr_text;
            $notesoutput .= '<li><div class="servicestatusdetailattrimg"><img src="' . $attr_icon . '" alt="' . $attr_icon_alt . '" title="' . $attr_icon_alt . '"></div><div class="servicestatusdetailattrtext">' . $attr_text . '</div></li>';
        }
        if (intval($xml->servicestatus->scheduled_downtime_depth) > 0) {
            $attr_text = _("Service is in scheduled downtime");
            $attr_icon = theme_image("downtime.png");
            $attr_icon_alt = $attr_text;
            $notesoutput .= '<li><div class="servicestatusdetailattrimg"><img src="' . $attr_icon . '" alt="' . $attr_icon_alt . '" title="' . $attr_icon_alt . '"></div><div class="servicestatusdetailattrtext">' . $attr_text . '</div></li>';
        }
        if (intval($xml->servicestatus->is_flapping) == 1) {
            $attr_text = _("Service is flapping between states");
            $attr_icon = theme_image("flapping.png");
            $attr_icon_alt = $attr_text;
            $notesoutput .= '<li><div class="servicestatusdetailattrimg"><img src="' . $attr_icon . '" alt="' . $attr_icon_alt . '" title="' . $attr_icon_alt . '"></div><div class="servicestatusdetailattrtext">' . $attr_text . '</div></li>';
        }
        if (intval($xml->servicestatus->notifications_enabled) == 0) {
            $attr_text = _("Service notifications are disabled");
            $attr_icon = theme_image("nonotifications.png");
            $attr_icon_alt = $attr_text;
            $notesoutput .= '<li><div class="servicestatusdetailattrimg"><img src="' . $attr_icon . '" alt="' . $attr_icon_alt . '" title="' . $attr_icon_alt . '"></div><div class="servicestatusdetailattrtext">' . $attr_text . '</div></li>';
        }

        if ($notesoutput != "") {
            $output .= '<tr><td valign="top">Service Notes:</td><td><ul class="servicestatusdetailnotes">';
            $output .= $notesoutput;
            $output .= '</ul></td></tr>';
        }

        $output .= '
        </tbody>
        </table>
        </div>
        ';
    }
    
    return $output;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_service_status_state_summary_html($args = null)
{

    $hostname = grab_array_var($args, "hostname", "");
    $servicename = urldecode(grab_array_var($args, "servicename", ""));
    $service_id = grab_array_var($args, "service_id", -1);

    //if($service_id>0)
    //$auth=is_authorized_for_object_id(0,$service_id);
    $auth = is_authorized_for_service(0, $hostname, $servicename);

    if ($auth == false) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    // get service status
    $args = array(
        "cmd" => "getservicestatus",
        "service_id" => $service_id,
    );
    //$xml=get_backend_xml_data($args);
    $xml = get_xml_service_status($args);

    $output = '';
    if ($xml == null) {
        $output .= "No data";
    } else {

        $img = theme_image("nagios_unknown_large.png");
        $imgalt = _("Unknown");

        $current_state = intval($xml->servicestatus->current_state);
        $has_been_checked = intval($xml->servicestatus->has_been_checked);

        $status_text = strval($xml->servicestatus->status_text);
        $status_text_long = strval($xml->servicestatus->status_text_long);
        $status_text_long = str_replace("\\n", "<br />", $status_text_long);
        $status_text_long = str_replace("\n", "<br />", $status_text_long);
//      $status_text_long = nl2br($status_text_long); //switch to php's built-in - MG
        //allow html tags?
        if (get_option('allow_status_html') == true) {
            $status_text = html_entity_decode($status_text);
            $status_text_long = html_entity_decode($status_text_long);
        }
        switch ($current_state) {
            case 0:
                $img = theme_image("nagios_ok_large.png");
                $statestr = _("Ok");
                $imgalt = $statestr;
                break;
            case 1:
                $img = theme_image("nagios_warning_large.png");
                $statestr = _("Warning");
                $imgalt = $statestr;
                break;
            case 2:
                $img = theme_image("nagios_critical_large.png");
                $statestr = _("Critical");
                $imgalt = $statestr;
                break;
            default:
                break;
        }
        if ($has_been_checked == 0) {
            $img = theme_image("nagios_pending_large.png");
            $statestr = _("Pending");
            $imgalt = $statestr;
            $status_text = _("Service check is pending...");
        }

        $output .= '<div class="well"><div class="servicestatusdetailinfo">';
        $imgwidth = "24";
        $state_icon = "<img src='" . $img . "' alt='" . $imgalt . "' title='" . $imgalt . "'  width='" . $imgwidth . "'>";
        $output .= '<div class="servicestatusdetailinfoimg">' . $state_icon . '</div><div class="servicestatusdetailinfotext">' . $status_text . '</div>';
        
        if (!empty($status_text_long)) {
            $output .= '<div class="servicestatusdetailinfotextlong">' . $status_text_long . '</div>';
        }
        
        $output .= '</div></div>';
    }

    return $output;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_service_comments_html($args = null)
{

    $hostname = grab_array_var($args, "hostname", "");
    $servicename = urldecode(grab_array_var($args, "servicename", ""));
    $service_id = grab_array_var($args, "service_id", -1);

    //if($service_id>0)
    //$auth=is_authorized_for_object_id(0,$service_id);
    $auth = is_authorized_for_service(0, $hostname, $servicename);

    if ($auth == false) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    // save this for later
    $auth_command = is_authorized_for_service_command(0, $hostname, $servicename);

    // get service comments
    $args = array(
        "cmd" => "getcomments",
        "object_id" => $service_id,
    );
    //$xml=get_backend_xml_data($args);
    $xml = get_xml_comments($args);

    $output = '';

    $output .= '<div class="infotable_title">' . _('Acknowledgements and Comments') . '</div>';

    if ($xml == null || intval($xml->recordcount) == 0) {
        $output .= _('No comments or acknowledgements.');
    } else {

        $output .= '
        <table class="infotable table table-condensed table-striped table-bordered">
        <tbody>
        ';

        foreach ($xml->comment as $c) {
            switch (intval($c->entry_type)) {
                case COMMENTTYPE_ACKNOWLEDGEMENT:
                    $typeimg = theme_image("ack.png");
                    break;
                default:
                    $typeimg = theme_image("comment.png");
                    break;
            }
            $type = "<img src='" . $typeimg . "'>";
            $timestr = get_datetime_string_from_datetime($c->comment_time);
            $author = strval($c->author_name);
            $comment = (get_option('allow_status_html') == true) ? html_entity_decode($c->comment_data) : strval($c->comment_data);

            $output .= '<tr><td valign="top">' . $type . '</td><td>By <b>' . $author . '</b> at ' . $timestr . '<br>' . $comment . '</td>';
            if ($auth_command) {
                $cmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DEL_SVC_COMMENT;
                $cmd["command_args"]["comment_id"] = intval($c->internal_id);
                $action = "<a href='#' " . get_nagioscore_command_ajax_code($cmd) . "><img src='" . theme_image("cross.png") . "' alt='" . _("Delete") . "' title='" . _("Delete") . "'></a>";
                $output .= '<td>' . $action . '</td>';
            }
            $output .= '</tr>';
        }

        $output .= '
        </tbody>
        </table>
        ';
    }

    return $output;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_service_status_attributes_html($args = null)
{

    $hostname = grab_array_var($args, "hostname", "");
    $servicename = urldecode(grab_array_var($args, "servicename", ""));
    $service_id = grab_array_var($args, "service_id", -1);

    $display = grab_array_var($args, "display", "simple");

    //if($service_id>0)
    //$auth=is_authorized_for_object_id(0,$service_id);
    $auth = is_authorized_for_service(0, $hostname, $servicename);

    if ($auth == false) {
        return _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    }

    // save this for later
    $auth_command = is_authorized_for_service_command(0, $hostname, $servicename);

    // get service status
    $args = array(
        "cmd" => "getservicestatus",
        "service_id" => $service_id,
    );
    //$xml=get_backend_xml_data($args);
    $xml = get_xml_service_status($args);

    if ($display == "advanced")
        $title = _("Advanced Service Attributes");
    else
        $title = _("Service Attributes");

    $output = '';
    $output .= '<div class="infotable_title">' . $title . '</div>';
    if ($xml == null) {
        $output .= "No data";
    } else {
        $output .= '
        <table class="infotable table table-condensed table-striped table-bordered">
        <thead>
        <tr><th><div style="width: 50px;">' . _('Attribute') . '</div></th><th><div style="width: 50px;">' . _('State') . '</div></th>';
        if ($auth_command)
            $output .= '<th><div style="width: 50px;">' . _('Action') . '</div></th>';
        $output .= '</tr>
        </thead>
        <tbody>
        ';

        if (1) {

            // initialze some stuff we'll use a few times...
            $okcmd = array(
                "command" => COMMAND_NAGIOSCORE_SUBMITCOMMAND,
            );
            $errcmd = array(
                "command" => COMMAND_NAGIOSCORE_SUBMITCOMMAND,
            );
            $okcmd["command_args"] = array(
                "host_name" => $hostname,
                "service_name" => $servicename,
            );
            $errcmd["command_args"] = array(
                "host_name" => $hostname,
                "service_name" => $servicename,
            );


            if ($display == "simple" || $display == "all") {

                // ACTIVE CHECKS
                $v = intval($xml->servicestatus->active_checks_enabled);
                $okcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DISABLE_SVC_CHECK;
                $errcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_ENABLE_SVC_CHECK;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Active  Checks') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v) . '</td>';
                if ($auth_command)
                    $output .= '<td class="text-center">' . xicore_ajax_get_setting_action_html($v, $okcmd, $errcmd) . '</td>';
                $output .= '</tr>';
            }

            if ($display == "advanced" || $display == "all") {

                // PASSIVE CHECKS
                $v = intval($xml->servicestatus->passive_checks_enabled);
                $okcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DISABLE_PASSIVE_SVC_CHECKS;
                $errcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_ENABLE_PASSIVE_SVC_CHECKS;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Passive Checks') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v) . '</td>';
                if ($auth_command)
                    $output .= '<td class="text-center">' . xicore_ajax_get_setting_action_html($v, $okcmd, $errcmd) . '</td>';
                $output .= '</tr>';
            }

            if ($display == "simple" || $display == "all") {

                // NOTIFICATIONS
                $v = intval($xml->servicestatus->notifications_enabled);
                $okcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DISABLE_SVC_NOTIFICATIONS;
                $errcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_ENABLE_SVC_NOTIFICATIONS;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Notifications') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v) . '</td>';
                if ($auth_command)
                    $output .= '<td class="text-center">' . xicore_ajax_get_setting_action_html($v, $okcmd, $errcmd) . '</td>';
                $output .= '</tr>';

                // FLAP DETECTION
                $v = intval($xml->servicestatus->flap_detection_enabled);
                $okcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DISABLE_SVC_FLAP_DETECTION;
                $errcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_ENABLE_SVC_FLAP_DETECTION;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Flap Detection') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v, false) . '</td>';
                if ($auth_command)
                    $output .= '<td class="text-center">' . xicore_ajax_get_setting_action_html($v, $okcmd, $errcmd) . '</td>';
                $output .= '</tr>';
            }

            if ($display == "advanced" || $display == "all") {

                // EVENT HANDLER
                $v = intval($xml->servicestatus->event_handler_enabled);
                $okcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_DISABLE_SVC_EVENT_HANDLER;
                $errcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_ENABLE_SVC_EVENT_HANDLER;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Event Handler') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v, false) . '</td>';
                if ($auth_command)
                    $output .= '<td class="text-center">' . xicore_ajax_get_setting_action_html($v, $okcmd, $errcmd) . '</td>';
                $output .= '</tr>';

                // PERFORMANCE DATA
                $v = intval($xml->servicestatus->process_performance_data);
                //$okcmd["command_args"]["cmd"]=NAGIOSCORE_CMD_DISABLE_PERFORMANCE_DATA;
                //$errcmd["command_args"]["cmd"]=NAGIOSCORE_CMD_ENABLE_PERFORMANCE_DATA;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Performance Data') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v) . '</td><td></td></tr>';

                // OBSESS
                $v = intval($xml->servicestatus->obsess_over_service);
                $okcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_STOP_OBSESSING_OVER_SVC;
                $errcmd["command_args"]["cmd"] = NAGIOSCORE_CMD_START_OBSESSING_OVER_SVC;
                $output .= '<tr><td><span class="sysstat_stat_subtitle">' . _('Obsession') . '</span></td><td class="text-center">' . xicore_ajax_get_setting_status_html($v, false) . '</td>';
                if ($auth_command)
                    $output .= '<td class="text-center">' . xicore_ajax_get_setting_action_html($v, $okcmd, $errcmd) . '</td>';
                $output .= '</tr>';
            }
        }

        $output .= '
        </tbody>
        </table>';
    }

    return $output;
}
