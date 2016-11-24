<?php
//
// Bandwidth Report Component
// Copyright (c) 2010-2014 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');


// respect the name
$bandwidthreport_component_name = "bandwidthreport";

// run the initialization function
bandwidthreport_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function bandwidthreport_component_init()
{
    global $bandwidthreport_component_name;

    $versionok = bandwidthreport_component_checkversion();

    $desc = _("Requires host authorization to view report. ");
    if (!$versionok)
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.4 or later.") . "</b>";

    $args = array(

        // need a name
        COMPONENT_NAME => $bandwidthreport_component_name,

        // informative information
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Provides a bandwith usage report for network switches and routers that are being monitored.") . $desc,
        COMPONENT_TITLE => "Bandwidth Usage Report",
        // configuration function (optional)
        //	COMPONENT_CONFIGFUNCTION => "bandwidthreport_component_config_func",
        COMPONENT_VERSION => "1.7.4"
    );

    register_component($bandwidthreport_component_name, $args);

    // register the addmenu function
    if ($versionok)
        register_callback(CALLBACK_MENUS_INITIALIZED, 'bandwidthreport_component_addmenu');

}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function bandwidthreport_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 125)
        return false;

    return true;
}


///////////////////////////////////////////////////////////////////////////////////////////
// MENU FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function bandwidthreport_component_addmenu($arg = null)
{

    global $bandwidthreport_component_name;

    //retrieve the URL for this component
    $urlbase = get_component_url_base($bandwidthreport_component_name);
    //figure out where I'm going on the menu
    $mi = find_menu_item(MENU_REPORTS, "menu-reports-nagiosxi-eventlog", "id");
    if ($mi == null) //bail if I didn't find the above menu item
        return;

    $order = grab_array_var($mi, "order", ""); //extract this variable from the $mi array
    if ($order == "")
        return;

    $neworder = $order + 0.05; //determine my menu order

    //add this to the main home menu
    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Bandwidth Usage"),
        "id" => "menu-reports-bandwidthreport",
        "order" => $neworder,
        "opts" => array(
            //this is the page the menu will actually point to.
            "href" => $urlbase . "/index.php",
        )
    ));

}