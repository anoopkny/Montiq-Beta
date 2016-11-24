<?php
//
// Renaming Tool Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

// Include the helper file
require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$rename_component_name = "rename";

// Run the initialization function
rename_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function rename_component_init()
{
    global $rename_component_name;

    // Boolean to check for latest version
    $versionok = rename_component_checkversion();

    // Component description
    $desc = _("This component allows administrators to manage renaming of hosts and services in bulk.");

    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2012R1.0 or later with Enterprise Features enabled.") . "</b>";
    }

    // All components require a few arguments to be initialized correctly.  
    $args = array(
        COMPONENT_NAME => $rename_component_name,
        COMPONENT_VERSION => '1.5.1',
        COMPONENT_DATE => '07/12/2016',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => _("Bulk Renaming Tool")
    );

    // Register this component with XI 
    register_component($rename_component_name, $args);

    // Only add this menu if the user is an admin / register the addmenu function
    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'rename_component_addmenu');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function rename_component_checkversion()
{
    // Needs permission fix in reconfigure_nagios.sh script in 2011R2.4
    if (!function_exists('get_product_release') || get_product_release() < 300) {
        return false;
    }
    return true;
}

function rename_component_addmenu($arg = null)
{
    global $rename_component_name;
    global $menus;

    // Retrieve the URL for this component
    $urlbase = get_component_url_base($rename_component_name);

    // Add this to the core config manager menu 
    add_menu_item(MENU_CORECONFIGMANAGER, array(
        "type" => "link",
        "title" => _("Bulk Renaming Tool"),
        "id" => "menu-coreconfigmanager-rename",
        "order" => 801.75,
        "opts" => array(
            "href" => $urlbase . "/rename.php",
            "icon" => "fa fa-tags"
        )
    ));

    // Add to the new ccm if it is installed 
    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Bulk Renaming Tool"),
        "id" => "menu-ccm-rename",
        "order" => 802.75,
        "opts" => array(
            "href" => $urlbase . "/rename.php",
            "icon" => "fa fa-tags"
        )
    ));
}