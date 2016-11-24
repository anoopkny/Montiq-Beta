<?php
//
// Nagvis Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$nagvis_component_name = "nagvis";
nagvis_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function nagvis_component_init()
{
    global $nagvis_component_name;
    $versionok = nagvis_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2009R1.2B or later.") . "</b>";
    }

    $installok = nagvis_component_checkinstallation();
    if (!$installok) {
        $desc .= "<b>" . _("Installation Required!") . "</b>  " .
            _("You must login to the server as the root user and run the following commands to complete the installation of this component") . ":<br>
		<i>cd /usr/local/nagiosxi/html/includes/components/" . $nagvis_component_name . "/</i><br>
		<i>chmod +x install.sh</i><br>		
		<i>./install.sh</i><br>";
    }

    $args = array(
        COMPONENT_NAME => $nagvis_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("This component brings Nagvis into the maps menu inside of XI. ") . $desc,
        COMPONENT_TITLE => "Nagvis",
        COMPONENT_VERSION => '1.1.3'
    );

    register_component($nagvis_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'nagvis_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function nagvis_component_checkversion()
{

    if (!function_exists('get_product_release'))
        return false;
    //requires greater than 2009R1.2
    if (get_product_release() < 114)
        return false;

    return true;
}

function nagvis_component_addmenu($arg = null)
{
    global $nagvis_component_name;

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
        "title" => "Nagvis",
        "id" => "menu-home-nagvis",
        "order" => $neworder,
        "opts" => array(
            "href" => '/nagvis/',
        )
    ));
}

function nagvis_component_checkinstallation()
{
    global $snmptrapsender_component_name;

    $f = "/usr/local/nagvis/share/index.php";

    // install file doesn't exist
    if (!file_exists($f)) {
        //echo "FILE $f DOES NOT EXIST<BR>";
        return false;
    }

    return true;
}


?>