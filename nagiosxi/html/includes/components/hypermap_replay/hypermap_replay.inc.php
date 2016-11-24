<?php
//
// Hypermap Replay Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$hypermap_replay_component_name = "hypermap_replay";
hypermap_replay_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function hypermap_replay_component_init()
{
    global $hypermap_replay_component_name;

    $versionok = hypermap_replay_component_checkversion();

    $desc = "";
    if (!$versionok)
        $desc = "<b>Error: This component requires Nagios XI 2011R1 or later.</b>";

    $args = array(
        COMPONENT_NAME => $hypermap_replay_component_name,
        COMPONENT_VERSION => '1.1.5',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("This component adds a network replay report to Nagios XI. ") . $desc,
        COMPONENT_TITLE => _("Network Replay")
    );

    register_component($hypermap_replay_component_name, $args);

    // add a menu link
    if ($versionok)
        register_callback(CALLBACK_MENUS_INITIALIZED, 'hypermap_replay_component_addmenu');

    // register a dashlet
    /*
    $args=array();
    $args[DASHLET_NAME]="hypermap_replay";
    $args[DASHLET_TITLE]="Hypermap Replay";
    $args[DASHLET_FUNCTION]="hypermap_replay_dashlet";
    $args[DASHLET_DESCRIPTION]="Displays a dynamic network status replay.";
    $args[DASHLET_WIDTH]="350";
    $args[DASHLET_HEIGHT]="250";
    $args[DASHLET_INBOARD_CLASS]="hypermap_replay_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS]="hypermap_replay_map_outboard";
    $args[DASHLET_CLASS]="hypermap_replay_map";
    $args[DASHLET_AUTHOR]="Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT]="Dashlet Copyright &copy; 2010 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE]="http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE]=true;
    register_dashlet($args[DASHLET_NAME],$args);
    */
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function hypermap_replay_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    //requires greater than 2011R1
    if (get_product_release() < 200)
        return false;

    return true;
}

function hypermap_replay_component_addmenu($arg = null)
{
    global $hypermap_replay_component_name;

    $mi = find_menu_item(MENU_REPORTS, "menu-reports-sectionend-visualization", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - .1;

    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Network Replay"),
        "id" => "menu-reports-hypermap_replay",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/hypermap_replay/',
        )
    ));

}


?>