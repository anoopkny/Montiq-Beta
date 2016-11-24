<?php
// Nagios XI Operations Center Component
//
// Copyright (c) 2010-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: nocscreen.inc.php 115 2010-08-16 16:15:26Z mguthrie $

//include the helper file
require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// respect the name
$nocscreen_component_name = "nocscreen";

// run the initialization function
nocscreen_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function nocscreen_component_init()
{
    global $nocscreen_component_name;

    //boolean to check for latest version
    $versionok = nocscreen_component_checkversion();

    //component description
    $desc = _("This component adds a read-only NOC screen for all current unhandled problems to the home menu.");

    if (!$versionok)
        $desc = "<b>" . _("Error: This component requires Nagios XI 2011R1 or later.") . "</b>";

    //all components require a few arguments to be initialized correctly.
    $args = array(

        // need a name
        COMPONENT_NAME => $nocscreen_component_name,
        COMPONENT_VERSION => '1.0.5',
        COMPONENT_DATE => '01/24/2011',

        // informative information
        COMPONENT_AUTHOR => "Mike Guthrie. Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => "Operations Center",

        // configuration function (optional)
        //COMPONENT_CONFIGFUNCTION => "nocscreen_component_config_func",
    );

    //register this component with XI
    register_component($nocscreen_component_name, $args);

    // register the addmenu function
    if ($versionok)
        register_callback(CALLBACK_MENUS_INITIALIZED, 'nocscreen_component_addmenu');
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function nocscreen_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    //requires greater than 2009R1.2
    if (get_product_release() < 200)
        return false;

    return true;
}

function nocscreen_component_addmenu($arg = null)
{
    global $nocscreen_component_name;
    //retrieve the URL for this component
    $urlbase = get_component_url_base($nocscreen_component_name);
    //figure out where I'm going on the menu
    $mi = find_menu_item(MENU_HOME, "menu-home-tacticaloverview", "id");
    if ($mi == null) //bail if I didn't find the above menu item
        return;

    $order = grab_array_var($mi, "order", ""); //extract this variable from the $mi array
    if ($order == "")
        return;

    $neworder = $order + 0.1; //determine my menu order

    //add this to the main home menu
    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Operations Center"),
        "id" => "menu-home-nocscreen",
        "order" => $neworder,
        "opts" => array(
            //this is the page the menu will actually point to.
            //all of my actual component workings will happen on this script
            "href" => $urlbase . "/noc.php",
        )
    ));

}


?>