<?php
//
// Alert Cloud
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
// 

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

$alertcloud_component_name = "alertcloud";
alertcloud_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function alertcloud_component_init()
{
    global $alertcloud_component_name;
    $versionok = alertcloud_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.4B or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $alertcloud_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays a cloud of recent alerts. ") . $desc,
        COMPONENT_TITLE => _("Alert Cloud"),
        COMPONENT_VERSION => '1.2.0'
    );

    register_component($alertcloud_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'alertcloud_component_addmenu');

        // Register a dashlet
        $args = array();
        $args[DASHLET_NAME] = "alertcloud";
        $args[DASHLET_TITLE] = "Alert Cloud";
        $args[DASHLET_FUNCTION] = "alertcloud_dashlet_func";
        $args[DASHLET_DESCRIPTION] = "Displays a dynamic cloud of alerts.";
        $args[DASHLET_WIDTH] = "350";
        $args[DASHLET_HEIGHT] = "350";
        $args[DASHLET_INBOARD_CLASS] = "alertcloud_map_inboard";
        $args[DASHLET_OUTBOARD_CLASS] = "alertcloud_map_outboard";
        $args[DASHLET_CLASS] = "alertcloud_map";
        $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
        $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2011-2016 Nagios Enterprises. All rights reserved.";
        $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
        $args[DASHLET_FUNCTION] = "alertcloud_dashlet_func";
        $args[DASHLET_SHOWASAVAILABLE] = true;
        register_dashlet($args[DASHLET_NAME], $args);
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function alertcloud_component_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }
    if (get_product_release() < 126) {
        return false;
    }
    return true;
}


function alertcloud_component_addmenu($arg = null)
{
    global $alertcloud_component_name;

    $mi = find_menu_item(MENU_REPORTS, "menu-reports-sectionend-visualization", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - .1;

    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Alert Cloud"),
        "id" => "menu-reports-alertcloud",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/alertcloud/',
        )
    ));
}


///////////////////////////////////////////////////////////////////////////////////////////
// AJAX FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function xicore_ajax_get_alertcloud_dashlet_html($args = null)
{
    $width = grab_array_var($args, "width", 500);
    $height = grab_array_var($args, "height", 500);
    $bgcolor = grab_array_var($args, "bgcolor", "FFFFFF");
    $tcolor = grab_array_var($args, "tcolor", "0BA000");
    $tcolor2 = grab_array_var($args, "tcolor2", "0BA000");
    $speed = grab_array_var($args, "speed", 50);
    $distr = grab_array_var($args, "distr", 1);
    $hicolor = grab_array_var($args, "hicolor", 1);
    $trans = grab_array_var($args, "trans", "true");
    $data = grab_array_var($args, "data", "alerts");

    $id = "alertcloud_" . random_string(6);

    $output = "";

    $component_url = get_base_url() . "includes/components/alertcloud/";
    $movie = $component_url . "alertcloud.swf";
    if ($data == "alerts") {
        $xmlurl = $component_url . "xmldata.php?data=" . $data;
    } else {
        $xmlurl = $component_url . "alertcloud.xml";
    }

    $options = array(
        "width" => $width,
        "height" => $height,
        "bgcolor" => $bgcolor,
        "trans" => $trans, // transparency
        "tcolor" => $tcolor,
        "tcolor2" => $tcolor2,
        "hicolor" => $hicolor,
        "speed" => $speed,
        "distr" => $distr
    );
    $options["xmlpath"] = $xmlurl;

    $flashtag = '<object id="alertCloudObject" type="application/x-shockwave-flash" data="' . $movie . '" width="' . $options['width'] . '" height="' . $options['height'] . '">';
    $flashtag .= '<param name="movie" value="' . $movie . '" />';
    $flashtag .= '<param name="bgcolor" value="#' . $options['bgcolor'] . '" />';
    $flashtag .= '<param name="AllowScriptAccess" value="sameDomain" />';
    if ($options['trans'] == 'true') {
        $flashtag .= '<param name="wmode" value="transparent" />';
    }
    $flashtag .= '<param name="flashvars" value="';
    $flashtag .= 'tcolor=0x' . $options['tcolor'];
    $flashtag .= '&amp;tcolor2=0x' . $options['tcolor2'];
    $flashtag .= '&amp;hicolor=0x' . $options['hicolor'];
    $flashtag .= '&amp;tspeed=' . $options['speed'];
    $flashtag .= '&amp;distr=' . $options['distr'];
    $flashtag .= '&amp;xmlpath=' . $options['xmlpath'];

    $flashtag .= '" />';

    $flashtag .= "</object>";

    $output .= '<div class="well" style="margin-bottom: 5px;"><div style="background-color: #FFF;">'.$flashtag.'</div></div>';

    $output .= '
	<div class="ajax_date">'._('Last Updated').': ' . get_datetime_string(time()) . '</div>
	';

    return $output;
}