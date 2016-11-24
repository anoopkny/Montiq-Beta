<?php
//
// Operationg Screen Component
// Copyright (c) 2012-2015 Nagios Enterprises, LLC.  All rights reserved.
// 

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$opscreen_component_name = "opscreen";
opscreen_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function opscreen_component_init()
{
    global $opscreen_component_name;

    $versionok = opscreen_component_checkversion();

    $desc = "";
    if (!$versionok)
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.4 or later.") . "</b>";

    $args = array(
        COMPONENT_NAME => $opscreen_component_name,
        COMPONENT_VERSION => "1.7.9",
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Provides an operations screen that can be used to display a status overview on a NOC monitor. ") . $desc,
        COMPONENT_TITLE => _("Operations Screen")
    );

    register_component($opscreen_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'opscreen_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function opscreen_component_checkversion()
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

function opscreen_component_addmenu($arg = null)
{
    global $opscreen_component_name;

    $urlbase = get_component_url_base($opscreen_component_name);


    $mi = find_menu_item(MENU_HOME, "menu-home-tacticaloverview", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order + 0.1;
    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Operations Screen"),
        "id" => "menu-home-opscreen",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/opscreen.php",
        )
    ));

}


?>