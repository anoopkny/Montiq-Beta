<?php
//
// Mass Acknowledge Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$massacknowledge_component_name = "massacknowledge";
massacknowledge_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function massacknowledge_component_init()
{
    global $massacknowledge_component_name;
    $versionok = massacknowledge_component_checkversion();
    $desc = _("This component allows administrators to submit mass acknowledgements or downtime for
			a list of problem hosts and services. ");

    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2009R1.2B or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $massacknowledge_component_name,
        COMPONENT_VERSION => '2.1.11',
        COMPONENT_DATE => '10/24/2016',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => _("Mass Acknowledge"),
        COMPONENT_REQUIRES_VERSION => 500
    );

    // Register this component with XI
    register_component($massacknowledge_component_name, $args);

    // Register the addmenu function
    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'massacknowledge_component_addmenu');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function massacknowledge_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 114)
        return false;
    return true;
}

function massacknowledge_component_addmenu($arg = null)
{
    if (is_readonly_user(0)) {
        return;
    }

    global $massacknowledge_component_name;
    $urlbase = get_component_url_base($massacknowledge_component_name);

    $mi = find_menu_item(MENU_HOME, "menu-home-acknowledgements", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    $neworder = $order + 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Mass Acknowledge"),
        "id" => "menu-home-massacknowledge",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/index.php"
        )
    ));
}