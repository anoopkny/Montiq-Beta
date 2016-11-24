<?php
//
// Alert Stream Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__).'/../componenthelper.inc.php');

$alertstream_component_name = "alertstream";

alertstream_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function alertstream_component_init()
{
    global $alertstream_component_name;
    $versionok = alertstream_component_checkversion();
    
    $desc = "";
    if (!$versionok) {
        $desc = " <br><b>" . _("Error: This component requires Nagios XI 2009R1.4B or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $alertstream_component_name,
        COMPONENT_VERSION => '2.0.6',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays a streamgraph report of alerts.") . $desc,
        COMPONENT_TITLE => _("Alert Stream"),
        COMPONENT_REQUIRES_VERSION => 500
    );

    register_component($alertstream_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'alertstream_component_addmenu');
    }
}
    

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function alertstream_component_checkversion()
{
    if(!function_exists('get_product_release')) {
        return false;
    }
    if (get_product_release() < 500) {
        return false;
    }
    return true;
}


function alertstream_component_addmenu($arg = null)
{
    global $alertstream_component_name;

    $mi = find_menu_item(MENU_REPORTS, "menu-reports-sectionend-visualization", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if (empty($order)) {
        return;
    }

    $neworder = $order - 0.1;

    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Alert Stream"),
        "id" => "menu-reports-alertstream",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url().'includes/components/alertstream/'
        )
    ));
}