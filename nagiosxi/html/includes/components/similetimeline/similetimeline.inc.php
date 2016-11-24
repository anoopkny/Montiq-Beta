<?php
//
// Alert Timeline
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$similetimeline_component_name = "similetimeline";
similetimeline_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function similetimeline_component_init()
{
    global $similetimeline_component_name;

    $versionok = similetimeline_component_checkversion();

    $desc = "";
    if (!$versionok)
        $desc = "<br><b>Error: This component requires Nagios XI 2009R1.4B or later.</b>";

    $args = array(
        COMPONENT_NAME => $similetimeline_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => "Provides a timeline of events. " . $desc,
        COMPONENT_TITLE => _("Simile Timeline"),
        COMPONENT_VERSION => '1.4.5'
    );

    register_component($similetimeline_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'similetimeline_component_addmenu');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function similetimeline_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 126)
        return false;
    return true;
}

function similetimeline_component_addmenu($arg = null)
{
    global $similetimeline_component_name;

    $mi = find_menu_item(MENU_REPORTS, "menu-reports-sectionend-visualization", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - .1;

    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Alert Timeline"),
        "id" => "menu-reports-similetimeline",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/similetimeline/',
        )
    ));
}