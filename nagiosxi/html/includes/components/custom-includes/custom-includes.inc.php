<?php
//
// Custom Includes Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$customincludes_component_name = "custom-includes";
customincludes_component_init();

//////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function customincludes_component_init()
{
    global $customincludes_component_name;
    $versionok = customincludes_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 5.3.0 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $customincludes_component_name,
        COMPONENT_VERSION => '1.0.2',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Allows the upload and inclusion of files to Nagios XI that will not be overwritten during upgrades.") . $desc,
        COMPONENT_TITLE => _("Custom Includes (CSS, Javascript, and images)"),
        COMPONENT_CONFIGFUNCTION => array("location" => "manage.php"),
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );

    register_component($customincludes_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'customincludes_component_addmenu');
        register_callback(CALLBACK_PAGE_HEAD, 'customincludes_component_addincludes');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function customincludes_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 530)
        return false;
    return true;
}

function customincludes_component_addmenu($args = null)
{
    $mi = find_menu_item(MENU_ADMIN, "menu-admin-managemibs", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    $neworder = $order + 0.1;
    add_menu_item(MENU_ADMIN, array(
        "type" => "link",
        "title" => _("Custom Includes"),
        "id" => "menu-admin-custom-includes",
        "order" => $neworder,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/custom-includes/manage.php',
            "icon" => "fa-plus-square"
        )
    ));
}

function customincludes_component_addincludes()
{
    $comp_url = get_component_url_base('custom-includes');
    $build_id = get_build_id();

    // Do CSS includes first
    $css = get_array_option('custom_includes_files_css');
    foreach ($css as $c) {
        if ($c['inc']) {
            echo '<link type="text/css" href="'.$comp_url.'/css/'.$c['name'].'?'.$build_id.'" rel="stylesheet">';
        }
    }

    // Javascript includes
    $js = get_array_option('custom_includes_files_javascript');
    foreach ($js as $j) {
        if ($j['inc']) {
            echo '<script type="text/javascript" src="'.$comp_url.'/javascript/'.$j['name'].'?'.$build_id.'"></script>';
        }
    }
}