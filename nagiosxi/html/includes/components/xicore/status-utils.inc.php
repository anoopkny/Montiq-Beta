<?php
//
// XI Status Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');

include_once(dirname(__FILE__) . '/status-object-detail.inc.php');


////////////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param        $host
 * @param string $service
 * @param bool   $usehost
 */
function show_object_icon($host, $service = "", $usehost = false)
{
    echo get_object_icon($host, $service, $usehost);
}

/**
 * @param        $host
 * @param string $service
 * @param bool   $usehost
 *
 * @return string
 */
function get_object_icon($host, $service = "", $usehost = false)
{

    $html = "";

    $tryhost = false;
    $iconimage = "";

    // we are showing a host icon
    if ($service == "")
        $tryhost = true;

    // we are showing a service icon
    else {
        $sid = get_service_id($host, $service);
        //echo "SID=$sid\n";
        $xml = get_xml_service_objects(array("service_id" => $sid));
        //print_r($xml);
        if ($xml != null && $xml->recordcount > 0) {
            //echo "USING SVC\n";
            $iconimage = strval($xml->service->icon_image);
            $iconimagealt = strval($xml->service->icon_image_alt);
            if ($iconimage == "" && $usehost == true)
                $tryhost = true;
        } else {
            //echo "NULL SVC\n";
            if ($usehost == true)
                $tryhost = true;
        }
    }

    if ($tryhost == true) {
        $hid = get_host_id($host);
        //echo "HID=$hid\n";
        $xml = get_xml_host_objects(array("host_id" => $hid));
        if ($xml != null && $xml->recordcount > 0) {
            //echo "USING HOST\n";
            $iconimage = strval($xml->host->icon_image);
            $iconimagealt = strval($xml->host->icon_image_alt);
        }
        //else
        //echo "NULL HOST\n";
    }

    //echo "IMG='".$iconimage."'";

    if ($iconimage != "")
        $html = get_object_icon_html($iconimage, $iconimagealt);
    //else
    //$html="HOST=".$host."=".$hid.",SVC=".$service."=".$sid;

    return $html;
}

/**
 * @param        $host
 * @param string $service
 * @param bool   $usehost
 *
 * @return string
 */
function get_object_icon_image($host, $service = "", $usehost = false)
{

    $iconimage = "";

    $tryhost = false;

    // we are showing a host icon
    if ($service == "")
        $tryhost = true;

    // we are showing a service icon
    else {
        $sid = get_service_id($host, $service);
        //echo "SID=$sid\n";
        $xml = get_xml_service_objects(array("service_id" => $sid));
        //print_r($xml);
        if ($xml != null && $xml->recordcount > 0) {
            //echo "USING SVC\n";
            $iconimage = strval($xml->service->icon_image);
            if ($iconimage == "" && $usehost == true)
                $tryhost = true;
        } else {
            //echo "NULL SVC\n";
            if ($usehost == true)
                $tryhost = true;
        }
    }

    if ($tryhost == true) {
        $hid = get_host_id($host);
        //echo "HID=$hid\n";
        $xml = get_xml_host_objects(array("host_id" => $hid));
        if ($xml != null && $xml->recordcount > 0) {
            //echo "USING HOST\n";
            $iconimage = strval($xml->host->icon_image);
        }
        //else
        //echo "NULL HOST\n";
    }

    return $iconimage;
}

/**
 * @param $img
 * @param $imgalt
 *
 * @return string
 */
function get_object_icon_html($img, $imgalt)
{

    $html = "";

    if ($img != "")
        $html = "<img class='objecticon' src='" . get_object_icon_url($img) . "' title='" . encode_form_val($imgalt, ENT_COMPAT, 'UTF-8') . "' alt='" . encode_form_val($imgalt, ENT_COMPAT, 'UTF-8') . "'>";

    return $html;
}

/**
 * @param $img
 * @param $imgalt
 *
 * @return string
 */
function get_service_status_note_image($img, $imgalt)
{
    $html = "<img src='" . theme_image($img) . "' title='" . encode_form_val($imgalt, ENT_COMPAT, 'UTF-8') . "' alt='" . encode_form_val($imgalt, ENT_COMPAT, 'UTF-8') . "'>";
    return $html;
}

/**
 * @param $img
 * @param $imgalt
 *
 * @return string
 */
function get_host_status_note_image($img, $imgalt)
{
    $html = "<img src='" . theme_image($img) . "' title='" . encode_form_val($imgalt, ENT_COMPAT, 'UTF-8') . "' alt='" . encode_form_val($imgalt, ENT_COMPAT, 'UTF-8') . "'>";
    return $html;
}

/**
 * @param $img
 *
 * @return string
 */
function get_object_icon_url($img)
{
    $url = get_base_url() . "includes/components/nagioscore/ui/images/logos/" . $img;
    return $url;
}


/**
 * @param $img
 * @param $alt
 *
 * @return string
 */
function get_object_command_icon($img, $alt)
{
    return "<img src='" . get_object_command_icon_url($img) . "' title='" . encode_form_val($alt, ENT_COMPAT, 'UTF-8') . "' alt='" . encode_form_val($alt, ENT_COMPAT, 'UTF-8') . "'>";
}

/**
 * @param $img
 *
 * @return string
 */
function get_object_command_icon_url($img)
{
    $url = get_base_url() . "includes/components/nagioscore/ui/images/" . $img;
    return $url;
}

/**
 * @param $url
 * @param $img
 * @param $title
 *
 * @return string
 */
function get_object_command_link($url, $img, $title)
{
    return '<div class="commandimage"><a href="' . $url . '">' . get_object_command_icon($img, $title) . '</a></div><div class="commandtext"><a href="' . $url . '">' . $title . '</a></div>';
}

/**
 * @param $url
 * @param $img
 * @param $title
 */
function show_object_command_link($url, $img, $title)
{
    echo get_object_command_link($url, $img, $title);
}

/**
 * @param $cmdarr
 *
 * @return string
 */
function get_nagioscore_command_ajax_code($cmdarr)
{
    $args = array();
    if (!empty($cmdarr["multi_cmd"]))
        foreach ($cmdarr["multi_cmd"] as $k => $command_args)
            foreach ($command_args["command_args"] as $var => $val)
                $args['multi_cmd'][$k][$var] = $val;
            
    if (!empty($cmdarr["command_args"])) {
        foreach ($cmdarr["command_args"] as $var => $val) {
            $args[$var] = $val;
        }
    }
    $cmddata = json_encode($args);
    $clickcmd = "onClick='submit_command(" . COMMAND_NAGIOSCORE_SUBMITCOMMAND . "," . $cmddata . ")'";
    return $clickcmd;
}

/**
 * @param $cmdarr
 * @param $img
 * @param $text
 *
 * @return string
 */
function get_service_detail_command_link($cmdarr, $img, $text)
{

    $clickcmd = get_nagioscore_command_ajax_code($cmdarr);

    return '<div class="commandimage"><a href="#" ' . $clickcmd . '><img src="' . theme_image($img) . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"  ' . $clickcmd . '>' . $text . '</a></div>';
}


/**
 * @param $cmdarr
 * @param $img
 * @param $text
 *
 * @return string
 */
function get_host_detail_command_link($cmdarr, $img, $text)
{

    $clickcmd = get_nagioscore_command_ajax_code($cmdarr);

    return '<div class="commandimage"><a href="#" ' . $clickcmd . '><img src="' . theme_image($img) . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"  ' . $clickcmd . '>' . $text . '</a></div>';
}


/**
 * @param $clickcmd
 * @param $img
 * @param $text
 *
 * @return string
 */
function get_service_detail_inplace_action_link($clickcmd, $img, $text)
{

    return '<div class="commandimage"><a href="#" onClick="' . $clickcmd . '"><img src="' . theme_image($img) . '" alt="' . $text . '" title="' . $text . '"></a></div><div class="commandtext"><a href="#"   onClick="' . $clickcmd . '">' . $text . '</a></div>';
}


/**
 * @param $hostname
 * @param $servicename
 */
function draw_service_detail_links($hostname, $servicename)
{

    echo "<div class='statusdetaillinks'>";

    echo "<div class='statusdetaillink'><a href='" . get_host_status_link($hostname) . "'><img src='" . theme_image("statusdetailmulti.png") . "' class='tt-bind' alt='" . _("View Current Status of Host Services") . "' title='" . _("View Current Status For Host Services") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_service_notifications_link($hostname, $servicename) . "'><img src='" . theme_image("notifications.png") . "' class='tt-bind' alt='" . _("View Service Notifications") . "' title='" . _("View Service Notifications") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_service_history_link($hostname, $servicename) . "'><img src='" . theme_image("history.png") . "' class='tt-bind' alt='" . _("View Service History") . "' title='" . _("View Service History") . "'></a></div>";
    //echo "<div class='statusdetaillink'><a href='" . get_service_trends_link($hostname, $servicename) . "'><img src='" . theme_image("trends.png") . "' class='tt-bind' alt='" . _("View Service Trends") . "' title='" . _("View Service Trends") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_service_availability_link($hostname, $servicename) . "'><img src='" . theme_image("availability.png") . "' class='tt-bind' alt='" . _("View Service Availability") . "' title='" . _("View Service Availability") . "'></a></div>";

    echo "</div>";
}

/**
 * @param $hostname
 */
function draw_host_detail_links($hostname)
{
    
    // FIRST GET TOTAL RECORD COUNT FROM BACKEND...
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount
    $backendargs["host_name"] = $hostname;
    $xml = get_xml_service_status($backendargs);
    // how many total services do we have?
    $total_records = 0;
    if ($xml)
        $total_records = intval($xml->recordcount);

    echo "<div class='statusdetaillinks'>";
    if ($total_records > 0)
        echo "<div class='statusdetaillink'><a href='" . get_host_status_link($hostname) . "'><img src='" . theme_image("statusdetailmulti.png") . "' class='tt-bind' alt='" . _("View Current Status of Host Services") . "' title='" . _("View Current Status For Host Services") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_host_notifications_link($hostname) . "'><img src='" . theme_image("notifications.png") . "' class='tt-bind' alt='" . _("View Host Notifications") . "' title='" . _("View Host Notifications") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_host_history_link($hostname) . "'><img src='" . theme_image("history.png") . "' class='tt-bind' alt='" . _("View Host History") . "' title='" . _("View Host History") . "'></a></div>";
    //echo "<div class='statusdetaillink'><a href='" . get_host_trends_link($hostname) . "'><img src='" . theme_image("trends.png") . "' alt='" . _("View Host Trends") . "' title='" . _("View Host Trends") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_host_availability_link($hostname) . "'><img src='" . theme_image("availability.png") . "' class='tt-bind' alt='" . _("View Host Availability") . "' title='" . _("View Host Availability") . "'></a></div>";

    echo "</div>";
}


/**
 * @param $hostgroupname
 */
function draw_hostgroup_viewstyle_links($hostgroupname)
{

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";

    echo "<div class='statusdetaillinks'>";

    echo "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&hostgroup=" . urlencode($hostgroupname) . "'><img src='" . theme_image("statusdetailmulti.png") . "' alt='" . _("View Hostgroup Service Details") . "' title='" . _("View Hostgroup Service Details") . "'></a></div>";
    //echo "<div class='statusdetaillink'><a href='".get_hostgroup_status_link($hostgroupname,"detail")."'><img src='".theme_image("statusdetailmulti.png")."' alt='"._("View Hostgroup Service Details")."' title='"._("View Hostgroup Service Details")."'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_hostgroup_status_link($hostgroupname, "summary") . "'><img src='" . theme_image("vssummary.png") . "' alt='" . _("View Hostgroup Summary") . "' title='" . _("View Hostgroup Summary") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_hostgroup_status_link($hostgroupname, "overview") . "'><img src='" . theme_image("vsoverview.png") . "' alt='" . _("View Hostgroup Overview") . "' title='" . _("View Hostgroup Overview") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_hostgroup_status_link($hostgroupname, "grid") . "'><img src='" . theme_image("vsgrid.png") . "' alt='" . _("View Hostgroup Grid") . "' title='" . _("View Hostgroup Grid") . "'></a></div>";

    echo "</div>";
}

/**
 * @param $servicegroupname
 */
function draw_servicegroup_viewstyle_links($servicegroupname)
{

    $xistatus_url = get_base_url() . "includes/components/xicore/status.php";

    echo "<div class='statusdetaillinks'>";

    echo "<div class='statusdetaillink'><a href='" . $xistatus_url . "?show=services&servicegroup=" . urlencode($servicegroupname) . "'><img src='" . theme_image("statusdetailmulti.png") . "' alt='" . _("View Servicegroup Service Details") . "' title='" . _("View Servicegroup Service Details") . "'></a></div>";
    //echo "<div class='statusdetaillink'><a href='".get_servicegroup_status_link($servicegroupname,"detail")."'><img src='".theme_image("statusdetailmulti.png")."' alt='"._("View Servicegroup Service Details")."' title='"._("View Servicegroup Service Details")."'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_servicegroup_status_link($servicegroupname, "summary") . "'><img src='" . theme_image("vssummary.png") . "' alt='" . _("View Servicegroup Summary") . "' title='" . _("View Servicegroup Summary") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_servicegroup_status_link($servicegroupname, "overview") . "'><img src='" . theme_image("vsoverview.png") . "' alt='" . _("View Servicegroup Overview") . "' title='" . _("View Servicegroup Overview") . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_servicegroup_status_link($servicegroupname, "grid") . "'><img src='" . theme_image("vsgrid.png") . "' alt='" . _("View Servicegroup Grid") . "' title='" . _("View Servicegroup Grid") . "'></a></div>";

    echo "</div>";
}

function draw_servicestatus_table()
{

    // what meta key do we use to save user prefs?
    $meta_pref_option = 'servicestatus_table_options';

    // defaults
    //$sortby="host_name:a,service_description";
    $sortby = "";
    $sortorder = "asc";
    $page = 1;
    $records = 15;
    $search = "";

    // default to use saved options
    $s = get_user_meta(0, $meta_pref_option);
    $saved_options = unserialize($s);
    if (is_array($saved_options)) {
        if (isset($saved_options["sortby"]))
            $sortby = $saved_options["sortby"];
        if (isset($saved_options["sortorder"]))
            $sortorder = $saved_options["sortorder"];
        if (isset($saved_options["records"]))
            $records = $saved_options["records"];
        //if(array_key_exists("search",$saved_options))
        //$search=$saved_options["search"];
    }
    //echo "SAVED OPTIONS: ";
    //print_r($saved_options);

    // grab request variables
    $show = grab_request_var("show", "services");
    $host = grab_request_var("host", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $hostattr = grab_request_var("hostattr", 0);
    $serviceattr = grab_request_var("serviceattr", 0);
    $hoststatustypes = grab_request_var("hoststatustypes", 0);
    $servicestatustypes = grab_request_var("servicestatustypes", 0);

    // fix for "all" options
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    $sortby = grab_request_var("sortby", $sortby);
    $sortorder = grab_request_var("sortorder", $sortorder);
    $records = grab_request_var("records", $records);
    $page = grab_request_var("page", $page);
    $search = trim(grab_request_var("search", $search));
    if ($search == _("Search..."))
        $search = "";

    // save options for later
    $saved_options = array(
        "sortby" => $sortby,
        "sortorder" => $sortorder,
        "records" => $records,
        //"search" => $search
    );
    $s = serialize($saved_options);
    set_user_meta(0, $meta_pref_option, $s, false);


    $output = '';

    $output .= "<form action='" . get_base_url() . "includes/components/xicore/status.php'>";
    $output .= "<input type='hidden' name='show' value=\"" . encode_form_val($show) . "\">\n";
    $output .= "<input type='hidden' name='sortby' value=\"" . encode_form_val($sortby) . "\">\n";
    $output .= "<input type='hidden' name='sortorder' value=\"" . encode_form_val($sortorder) . "\">\n";
    $output .= "<input type='hidden' name='host' value=\"" . encode_form_val($host) . "\">\n";
    $output .= "<input type='hidden' name='hostgroup' value=\"" . encode_form_val($hostgroup) . "\">\n";
    $output .= "<input type='hidden' name='servicegroup' value=\"" . encode_form_val($servicegroup) . "\">\n";

    $output .= '<div class="servicestatustablesearch">';

    $output .= '
            <input type="text" size="15" name="search" id="hostsearchBox" value="" class="form-control condensed" placeholder="'._('Search').'...">
            <button type="submit" class="btn btn-xs btn-default" name="searchButton" id="searchButton"><i class="fa fa-search"></i></button>
        </div>
    </form>';

    // ajax updater args
    $ajaxargs = array();
    $ajaxargs["host"] = $host;
    $ajaxargs["hostgroup"] = $hostgroup;
    $ajaxargs["servicegroup"] = $servicegroup;
    $ajaxargs["sortby"] = $sortby;
    $ajaxargs["sortorder"] = $sortorder;
    $ajaxargs["records"] = $records;
    $ajaxargs["page"] = $page;
    $ajaxargs["search"] = $search;
    $ajaxargs["hostattr"] = $hostattr;
    $ajaxargs["serviceattr"] = $serviceattr;
    $ajaxargs["hoststatustypes"] = $hoststatustypes;
    $ajaxargs["servicestatustypes"] = $servicestatustypes;

    $id = "servicestatustable_" . random_string(6);

    $output .= "<div class='servicestatustable' id='" . $id . "'>\n";
    $output .= get_throbber_html();
    $output .= "</div>";

    // build args for javascript
    $n = 0;
    $jargs = "{";
    foreach ($ajaxargs as $var => $val) {
        if ($n > 0)
            $jargs .= ", ";
        $jargs .= "\"" . encode_form_val($var) . "\" : \"" . encode_form_val($val) . "\"";
        $n++;
    }
    $jargs .= "}";

    // ajax updater
    $output .= '
    <script type="text/javascript">
    $(document).ready(function(){
    
        get_' . $id . '_content();
            
        $("#' . $id . '").everyTime(30*1000, "timer-' . $id . '", function(i) {
            get_' . $id . '_content();
        });
        
        function get_' . $id . '_content(){
            $("#' . $id . '").each(function(){
                var optsarr = {
                    "func": "get_servicestatus_table",
                    "args": ' . $jargs . '
                    }
                var opts=array2json(optsarr);
                get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
                });
            }

    });
    </script>
    ';

    //return $output;
    echo $output;
}


function draw_hoststatus_table()
{

    // what meta key do we use to save user prefs?
    $meta_pref_option = 'hoststatus_table_options';

    // defaults
    //$sortby="host_name:a,service_description";
    $sortby = "";
    $sortorder = "asc";
    $page = 1;
    $records = 15;
    $search = "";

    // default to use saved options
    $s = get_user_meta(0, $meta_pref_option);
    if ($s) {
        $saved_options = unserialize($s);
        if (is_array($saved_options)) {
            if (isset($saved_options["sortby"]))
                $sortby = $saved_options["sortby"];
            if (isset($saved_options["sortorder"]))
                $sortorder = $saved_options["sortorder"];
            if (isset($saved_options["records"]))
                $records = $saved_options["records"];
            //if(array_key_exists("search",$saved_options))
            //  $search=$saved_options["search"];
        }
        //echo "SAVED OPTIONS: ";
        //print_r($saved_options);
    }

    // grab request variables
    $show = grab_request_var("show", "services");
    $host = grab_request_var("host", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $hostattr = grab_request_var("hostattr", 0);
    $serviceattr = grab_request_var("serviceattr", 0);
    $hoststatustypes = grab_request_var("hoststatustypes", 0);
    $servicestatustypes = grab_request_var("servicestatustypes", 0);

    // fix for "all" options
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    $sortby = grab_request_var("sortby", $sortby);
    $sortorder = grab_request_var("sortorder", $sortorder);
    $records = grab_request_var("records", $records);
    $page = grab_request_var("page", $page);
    $search = trim(grab_request_var("search", $search));
    if ($search == _("Search..."))
        $search = "";

    // save options for later
    $saved_options = array(
        "sortby" => $sortby,
        "sortorder" => $sortorder,
        "records" => $records,
        //"search" => $search
    );
    $s = serialize($saved_options);
    set_user_meta(0, $meta_pref_option, $s, false);


    $output = '';

    $output .= "<form action='" . get_base_url() . "includes/components/xicore/status.php'>";
    $output .= "<input type='hidden' name='show' value='hosts'>\n";

    $output .= "<input type='hidden' name='sortby' value=\"" . encode_form_val($sortby) . "\">\n";
    $output .= "<input type='hidden' name='sortorder' value=\"" . encode_form_val($sortorder) . "\">\n";
    $output .= "<input type='hidden' name='host' value=\"" . encode_form_val($host) . "\">\n";
    $output .= "<input type='hidden' name='hostgroup' value=\"" . encode_form_val($hostgroup) . "\">\n";
    $output .= "<input type='hidden' name='servicegroup' value=\"" . encode_form_val($servicegroup) . "\">\n";

    $output .= '<div class="servicestatustablesearch">';
    $output .= '
            <input type="text" size="15" name="search" id="hostsearchBox" value="" class="form-control condensed" placeholder="'._('Search').'...">
            <button type="submit" class="btn btn-xs btn-default" name="searchButton" id="searchButton"><i class="fa fa-search"></i></button>
        </div>
    </form>';

    // ajax updater args
    $ajaxargs = array();
    $ajaxargs["host"] = $host;
    $ajaxargs["hostgroup"] = $hostgroup;
    $ajaxargs["servicegroup"] = $servicegroup;
    $ajaxargs["sortby"] = $sortby;
    $ajaxargs["sortorder"] = $sortorder;
    $ajaxargs["records"] = $records;
    $ajaxargs["page"] = $page;
    $ajaxargs["search"] = $search;
    $ajaxargs["hostattr"] = $hostattr;
    $ajaxargs["serviceattr"] = $serviceattr;
    $ajaxargs["hoststatustypes"] = $hoststatustypes;
    $ajaxargs["servicestatustypes"] = $servicestatustypes;

    $id = "hoststatustable_" . random_string(6);

    $output .= "<div class='hoststatustable' id='" . $id . "'>\n";
    $output .= get_throbber_html();
    $output .= "</div>";

    // build args for javascript
    $n = 0;
    $jargs = "{";
    foreach ($ajaxargs as $var => $val) {
        if ($n > 0)
            $jargs .= ", ";
        $jargs .= "\"" . htmlentities($var) . "\" : \"" . htmlentities($val) . "\"";
        $n++;
    }
    $jargs .= "}";

    // ajax updater
    $output .= '
    <script type="text/javascript">
    $(document).ready(function(){

        get_' . $id . '_content();
            
        $("#' . $id . '").everyTime(30*1000, "timer-' . $id . '", function(i) {
            get_' . $id . '_content();
        });
        
        function get_' . $id . '_content(){
            $("#' . $id . '").each(function(){
                var optsarr = {
                    "func": "get_hoststatus_table",
                    "args": ' . $jargs . '
                    }
                var opts=array2json(optsarr);
                get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
                });
            }

    });
    </script>
    ';

    //return $output;
    echo $output;
}


/**
 * @param        $show
 * @param        $urlargs
 * @param        $hostattr
 * @param        $serviceattr
 * @param        $hoststatustypes
 * @param        $servicestatustypes
 * @param string $url
 *
 * @return string
 */
function get_status_view_filters_html($show, $urlargs, $hostattr, $serviceattr, $hoststatustypes, $servicestatustypes, $url = "")
{


    if ($url == "")
        $theurl = get_current_page();
    else
        $theurl = $url;

    $output = '';

    // no filter is being used...
    if ($hostattr == 0 && ($hoststatustypes == 0 || $hoststatustypes == HOSTSTATE_ANY) && $serviceattr == 0 && ($servicestatustypes == 0 || $servicestatustypes == SERVICESTATE_ANY)) {
        //$output.='HA='.$hostattr.', HS='.$hoststatustypes.', SA='.$serviceattr.', SS='.$servicestatustypes;
        return '';
    }

    if ($show == "openproblems" || $show == "serviceproblems")
        $show = "services";
    else if ($show == "hostproblems")
        $show = "hosts";

    $theurl .= "?show=" . $show;
    foreach ($urlargs as $var => $val) {
        if ($var == "show" || $var == "hostattr" || $var == "serviceattr" || $var == "hoststatustypes" || $var == "servicestatustypes")
            continue;
        $theurl .= "&" . $var . "=" . $val;
    }

    $output .= '<img src="' . theme_image("filter.png") . '"> ' . _('Filters') . ':';

    $filters = "";

    if ($hostattr != 0 || ($hoststatustypes != 0 && $hoststatustypes != HOSTSTATE_ANY)) {
        $filters .= " <b>" . _('Host') . "</b>=";
        $filterstrs = array();

        if (($hoststatustypes & HOSTSTATE_UP))
            $filterstrs[] = _("Up");
        if (($hoststatustypes & HOSTSTATE_DOWN))
            $filterstrs[] = _("Down");
        if (($hoststatustypes & HOSTSTATE_UNREACHABLE))
            $filterstrs[] = _("Unreachable");
        if (($hostattr & HOSTSTATUSATTR_ACKNOWLEDGED))
            $filterstrs[] = _("Acknowledged");
        if (($hostattr & HOSTSTATUSATTR_NOTACKNOWLEDGED))
            $filterstrs[] = _("Not Acknowledged");
        if (($hostattr & HOSTSTATUSATTR_INDOWNTIME))
            $filterstrs[] = _("In Downtime");
        if (($hostattr & HOSTSTATUSATTR_NOTINDOWNTIME))
            $filterstrs[] = _("Not In Downtime");
        if (($hostattr & HOSTSTATUSATTR_ISFLAPPING))
            $filterstrs[] = _("Flapping");
        if (($hostattr & HOSTSTATUSATTR_ISNOTFLAPPING))
            $filterstrs[] = _("Not Flapping");
        if (($hostattr & HOSTSTATUSATTR_CHECKSENABLED))
            $filterstrs[] = _("Checks Enabled");
        if (($hostattr & HOSTSTATUSATTR_CHECKSDISABLED))
            $filterstrs[] = _("Checks Disabled");
        if (($hostattr & HOSTSTATUSATTR_NOTIFICATIONSENABLED))
            $filterstrs[] = _("Notifications Enabled");
        if (($hostattr & HOSTSTATUSATTR_NOTIFICATIONSDISABLED))
            $filterstrs[] = _("Notifications Disabled");
        if (($hostattr & HOSTSTATUSATTR_HARDSTATE))
            $filterstrs[] = _("Hard State");
        if (($hostattr & HOSTSTATUSATTR_SOFTSTATE))
            $filterstrs[] = _("Soft State");

        if (($hostattr & HOSTSTATUSATTR_EVENTHANDLERDISABLED))
            $filterstrs[] = _("Event Handler Disabled");
        if (($hostattr & HOSTSTATUSATTR_EVENTHANDLERENABLED))
            $filterstrs[] = _("Event Handler Enabled");
        if (($hostattr & HOSTSTATUSATTR_FLAPDETECTIONDISABLED))
            $filterstrs[] = _("Flap Detection Disabled");
        if (($hostattr & HOSTSTATUSATTR_FLAPDETECTIONENABLED))
            $filterstrs[] = _("Flap Detection Enabled");
        if (($hostattr & HOSTSTATUSATTR_PASSIVECHECKSDISABLED))
            $filterstrs[] = _("Passive Checks Disabled");
        if (($hostattr & HOSTSTATUSATTR_PASSIVECHECKSENABLED))
            $filterstrs[] = _("Passive Checks Enabled");
        if (($hostattr & HOSTSTATUSATTR_PASSIVECHECK))
            $filterstrs[] = _("Passive Check");
        if (($hostattr & HOSTSTATUSATTR_ACTIVECHECK))
            $filterstrs[] = _("Active Check");
        if (($hostattr & HOSTSTATUSATTR_HARDSTATE))
            $filterstrs[] = _("Hard State");
        if (($hostattr & HOSTSTATUSATTR_SOFTSTATE))
            $filterstrs[] = _("Soft State");

        $x = 0;
        foreach ($filterstrs as $f) {
            if ($x > 0)
                $filters .= ",";
            $filters .= $f;
            $x++;
        }
    }

    if ($serviceattr != 0 || ($servicestatustypes != 0 && $servicestatustypes != SERVICESTATE_ANY)) {
        //if($filters!="")
        //  $filters.="<BR>";
        $filters .= " <b>" . _('Service') . "</b>=";
        $filterstrs = array();

        if (($servicestatustypes & SERVICESTATE_OK))
            $filterstrs[] = _("Ok");
        if (($servicestatustypes & SERVICESTATE_WARNING))
            $filterstrs[] = _("Warning");
        if (($servicestatustypes & SERVICESTATE_UNKNOWN))
            $filterstrs[] = _("Unknown");
        if (($servicestatustypes & SERVICESTATE_CRITICAL))
            $filterstrs[] = _("Critical");
        if (($serviceattr & SERVICESTATUSATTR_ACKNOWLEDGED))
            $filterstrs[] = _("Acknowledged");
        if (($serviceattr & SERVICESTATUSATTR_NOTACKNOWLEDGED))
            $filterstrs[] = _("Not Acknowledged");
        if (($serviceattr & SERVICESTATUSATTR_INDOWNTIME))
            $filterstrs[] = _("In Downtime");
        if (($serviceattr & SERVICESTATUSATTR_NOTINDOWNTIME))
            $filterstrs[] = _("Not In Downtime");
        if (($serviceattr & SERVICESTATUSATTR_ISFLAPPING))
            $filterstrs[] = _("Flapping");
        if (($serviceattr & SERVICESTATUSATTR_ISNOTFLAPPING))
            $filterstrs[] = _("Not Flapping");
        if (($serviceattr & SERVICESTATUSATTR_CHECKSENABLED))
            $filterstrs[] = _("Checks Enabled");
        if (($serviceattr & SERVICESTATUSATTR_CHECKSDISABLED))
            $filterstrs[] = _("Checks Disabled");
        if (($serviceattr & SERVICESTATUSATTR_NOTIFICATIONSENABLED))
            $filterstrs[] = _("Notifications Enabled");
        if (($serviceattr & SERVICESTATUSATTR_NOTIFICATIONSDISABLED))
            $filterstrs[] = _("Notifications Disabled");
        if (($serviceattr & SERVICESTATUSATTR_HARDSTATE))
            $filterstrs[] = _("Hard State");
        if (($serviceattr & SERVICESTATUSATTR_SOFTSTATE))
            $filterstrs[] = _("Soft State");

        if (($serviceattr & SERVICESTATUSATTR_EVENTHANDLERDISABLED))
            $filterstrs[] = _("Event Handler Disabled");
        if (($serviceattr & SERVICESTATUSATTR_EVENTHANDLERENABLED))
            $filterstrs[] = _("Event Handler Enabled");
        if (($serviceattr & SERVICESTATUSATTR_FLAPDETECTIONDISABLED))
            $filterstrs[] = _("Flap Detection Disabled");
        if (($serviceattr & SERVICESTATUSATTR_FLAPDETECTIONENABLED))
            $filterstrs[] = _("Flap Detection Enabled");
        if (($serviceattr & SERVICESTATUSATTR_PASSIVECHECKSDISABLED))
            $filterstrs[] = _("Passive Checks Disabled");
        if (($serviceattr & SERVICESTATUSATTR_PASSIVECHECKSENABLED))
            $filterstrs[] = _("Passive Checks Enabled");
        if (($serviceattr & SERVICESTATUSATTR_PASSIVECHECK))
            $filterstrs[] = _("Passive Check");
        if (($serviceattr & SERVICESTATUSATTR_ACTIVECHECK))
            $filterstrs[] = _("Active Check");
        if (($serviceattr & SERVICESTATUSATTR_HARDSTATE))
            $filterstrs[] = _("Hard State");
        if (($serviceattr & SERVICESTATUSATTR_SOFTSTATE))
            $filterstrs[] = _("Soft State");

        $x = 0;
        foreach ($filterstrs as $f) {
            if ($x > 0)
                $filters .= ",";
            $filters .= $f;
            $x++;
        }
    }

    $output .= $filters;

    $output .= " <a href='" . $theurl . "'><img src='" . theme_image("clearfilter.png") . "' alt='" . _("Clear Filter") . "' title='" . _("Clear Filter") . "'></a>";


    return $output;
}

// used for stripping html out of custom data sent with table_data/table_header callbacks
function strip_html_from_table_data($allow_html, $data) {

    $output = $data;

    if (!$allow_html) {

        $output = "";

        // if we don't allow html, we need to still allow td and th tags, otherwise whats the point?
        $data_array_split_by_td_tags = preg_split("/(<[tT][dD].*?>|<\/[tT][dD]>|<[tT][hH].*?>|<\/[tT][hH]>)/", $data, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if (count($data_array_split_by_td_tags) > 0) {
            foreach ($data_array_split_by_td_tags as $data_element) {

                if (preg_match("/^<[tT][dD]|<[tT][hH]|<\/[tT][dD]|<\/[tT][hH]/", $data_element) === 1) {
                    $output .= $data_element;
                    continue;
                }

                $output .= htmlentities($data_element);

            }
        } else {
            $output .= $data;
        }
    }

    return $output;
}

// used for stripping non-img tags out of custom data sent with table_icons callbacks
function strip_non_img_from_table_icons($allow_html, $icons) {

    $output = $icons;

    if (!$allow_html) {

        $output = "";

        // ONLY include img tags - don't use php's strip_tags since < 5.3.4 doesn't handle self closing html tags properly
        if (preg_match_all("/<[iI][mM][gG]\s.*?>/", $icons, $img_matches) > 0) {
            debug($img_matches);
            foreach($img_matches[0] as $img) {
                $output .= $img;
            }
        }
    }

    return $output;

}


function draw_statusmap_viewstyle_links()
{
    global $lstr;

    echo "<div class='statusdetaillinks'>";

    echo "<div class='statusdetaillink'><a href='" . get_statusmap_link(6) . "'><img src='" . theme_image("statusmapballoon.png") . "' alt='" . $lstr['ViewStatusMapBalloonAlt'] . "' title='" . $lstr['ViewStatusMapBalloonAlt'] . "'></a></div>";
    echo "<div class='statusdetaillink'><a href='" . get_statusmap_link(3) . "'><img src='" . theme_image("statusmaptree.png") . "' alt='" . $lstr['ViewStatusMapTreeAlt'] . "' title='" . $lstr['ViewStatusMapTreeAlt'] . "'></a></div>";

    echo "</div>";
}