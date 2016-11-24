<?php
//
// User Macros Component
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$statusmap_component_name = "statusmap";
statusmap_component_init();


////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function statusmap_component_init()
{
    global $statusmap_component_name;
    $versionok = statusmap_component_checkversion();

    $args = array(
        COMPONENT_NAME => $statusmap_component_name,
        COMPONENT_VERSION => '1.0.1',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Resources for the Javascript D3 network status map."),
        COMPONENT_TITLE => _("Network Status Map"),
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );

    register_component($statusmap_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'statusmap_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function statusmap_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;

    // XI 5.3.0 or newer
    if (get_product_release() < 530)
        return false;
    return true;
}


function statusmap_component_addmenu($arg = null)
{
    global $statusmap_component_name;

    $base_url = get_base_url();
    $base = substr($base_url, 0, (strlen($base_url) - 3)); //chop down url

    $mi = find_menu_item(MENU_HOME, "menu-home-networkstatusmap", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Network Status Map"),
        "id" => "menu-home-new-nsm",
        "order" => $neworder,
        "opts" => array(
            "icon" => "fa-share-alt",
            "href" => get_base_url() . 'includes/components/statusmap/index.php',
        )
    ));
}