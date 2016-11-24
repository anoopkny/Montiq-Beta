<?php
// 
// Proxy Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: proxy.inc.php 115 2010-08-16 16:15:26Z mguthrie $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$proxy_component_name = "proxy";

proxy_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function proxy_component_init()
{
    global $proxy_component_name;
    $versionok = proxy_component_checkversion();

    // Component description
    $desc = _("This component creates a proxy configuration menu in the Admin panel
	and is used to configure proxy settings for update checks. 
	<strong>Requires v2011R1.8rc or later.") . "</strong>";

    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 20011R1.8rc or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $proxy_component_name,
        COMPONENT_VERSION => '1.1.3',
        COMPONENT_DATE => '02/18/2016',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => "Proxy Configuration",
    );

    // Register this component with XI
    register_component($proxy_component_name, $args);

    // Register the addmenu function
    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'proxy_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function proxy_component_checkversion()
{

    if (!function_exists('get_product_release')) {
        return false;
    }
    // Requires greater than 2009R1.2
    if (get_product_release() < 208) {
        return false;
    }

    return true;
}

function proxy_component_addmenu($arg = null)
{
    global $proxy_component_name;
    
    // Retrieve the URL for this component
    $urlbase = get_component_url_base($proxy_component_name);
    
    // Figure out where I'm going on the menu
    $mi = find_menu_item(MENU_ADMIN, "menu-admin-managesystemconfig", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    $neworder = $order + 0.1;

    // Add this to the main home menu
    add_menu_item(MENU_ADMIN, array(
        "type" => "link",
        "title" => _("Proxy Configuration"),
        "id" => "menu-admin-proxy",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/proxyconfig.php",
        )
    ));
}