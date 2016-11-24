<?php
//
// Hypermap Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');


$hypermap_component_name = "hypermap";
hypermap_component_init();


////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////


function hypermap_component_init()
{
    global $hypermap_component_name;
    $versionok = hypermap_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2011R1 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $hypermap_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("This component adds a network hypermap to Nagios XI. ") . $desc,
        COMPONENT_TITLE => _("Hypermap"),
        COMPONENT_VERSION => '1.1.5',
        COMPONENT_DATE => "02/18/2015"
    );

    register_component($hypermap_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'hypermap_component_addmenu');
    }

    // Register a dashlet
    $args = array();
    $args[DASHLET_NAME] = "hypermap";
    $args[DASHLET_TITLE] = _("Hypermap");
    $args[DASHLET_FUNCTION] = "hypermap_dashlet";
    $args[DASHLET_DESCRIPTION] = _("Displays a dynamic network status map.");
    $args[DASHLET_WIDTH] = "350";
    $args[DASHLET_HEIGHT] = "250";
    $args[DASHLET_INBOARD_CLASS] = "hypermap_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS] = "hypermap_map_outboard";
    $args[DASHLET_CLASS] = "hypermap_map";
    $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2010-2015 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE] = true;
    register_dashlet($args[DASHLET_NAME], $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function hypermap_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 200)
        return false;
    return true;
}


function hypermap_component_addmenu($arg = null)
{
    global $hypermap_component_name;

    $mi = find_menu_item(MENU_HOME, "menu-home-networkstatusmap", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Hypermap"),
        "id" => "menu-home-hypermap",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/hypermap/',
        )
    ));
}