<?php
//
// Escalation Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

//include the helper file
require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// respect the name
$escalationwizard_component_name = "escalationwizard";

// run the initialization function
escalationwizard_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function escalationwizard_component_init()
{
    global $escalationwizard_component_name;
    $versionok = escalationwizard_component_checkversion();

    // Component description
    $desc = _("This component host and service escalations to be easily created through a wizard");

    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2011R2.0 or later.") . "</b>";
    }

    // All components require a few arguments to be initialized correctly.
    $args = array(
        COMPONENT_NAME => $escalationwizard_component_name,
        COMPONENT_VERSION => '1.4.2',
        COMPONENT_DATE => '11/18/2016',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => _("Escalation Wizard")
    );

    // Register this component with XI
    register_component($escalationwizard_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'escalationwizard_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function escalationwizard_component_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }
    // Needs permission fix in reconfigure_nagios.sh script in 2011R2.4
    if (get_product_release() < 300) {
        return false;
    }
    return true;
}

function escalationwizard_component_addmenu($arg = null)
{
    global $escalationwizard_component_name;
    global $menus;

    $urlbase = get_component_url_base($escalationwizard_component_name);

    add_menu_item(MENU_CORECONFIGMANAGER, array(
        "type" => "link",
        "title" => _("Escalation Wizard"),
        "id" => "menu-coreconfigmanager-escalationwizard",
        "order" => 801.6,
        "opts" => array(
            "href" => $urlbase . "/escalationwizard.php",
            "icon" => "fa-chevron-circle-up"
        )
    ));

    add_menu_item(MENU_CCM, array(
        "type" => "link",
        "title" => _("Escalation Wizard"),
        "id" => "menu-ccm-escalationwizard",
        "order" => 802.6,
        "opts" => array(
            "href" => $urlbase . "/escalationwizard.php",
            "icon" => "fa-chevron-circle-up"
        )
    ));
}