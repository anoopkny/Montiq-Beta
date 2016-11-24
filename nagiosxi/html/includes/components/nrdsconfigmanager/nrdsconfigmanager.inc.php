<?php
//
// NRDS Config Manager
// Written by: Scott Wilkerson (nagios@nagios.org)
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$nrdsconfigmanager_component_name = "nrdsconfigmanager";

nrdsconfigmanager_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function nrdsconfigmanager_component_init()
{
    global $nrdsconfigmanager_component_name;
    $versionok = nrdsconfigmanager_component_checkversion();

    // Component description
    $desc = "This component allows administrators to manage their NRDS config files to be distributed to remote clients. ";
    if (!file_exists(dirname(__FILE__) . "/installed.nrds")) {
        $desc .= " <br><b>IMPORTANT: Run the following as root to install.</b><br/><pre>
cd " . dirname(__FILE__) . "
chmod +x install.sh
./install.sh
</pre>";
    }

    if (!$versionok) {
        $desc = "<b>Error: This component requires Nagios XI 2009R1.2B or later.</b>";
    }

    $args = array(
        COMPONENT_NAME => $nrdsconfigmanager_component_name,
        COMPONENT_VERSION => '1.5.4',
        COMPONENT_DATE => '05/06/2016',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => "NRDS Config Manager",
    );

    // Register this component with XI
    register_component($nrdsconfigmanager_component_name, $args);

    // Register the addmenu function
    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'nrdsconfigmanager_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function nrdsconfigmanager_component_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }

    // Requires greater than 2009R1.2
    if (get_product_release() < 114) {
        return false;
    }

    return true;
}

function nrdsconfigmanager_component_addmenu($arg = null)
{
    global $nrdsconfigmanager_component_name;

    // Retrieve the URL for this component
    $urlbase = get_component_url_base($nrdsconfigmanager_component_name);
    
    //Figure out where I'm going on the menu
    $mi = find_menu_item(MENU_ADMIN, "menu-admin-missingobjects", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    $neworder = $order - 0.1;

    // Add this to the main home menu
    add_menu_item(MENU_ADMIN, array(
        "type" => "link",
        "title" => "NRDS Config Manager",
        "id" => "menu-admin-nrdsconfigmanager",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/nrdsconfigmanager.php",
        )
    ));
}