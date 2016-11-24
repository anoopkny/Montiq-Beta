<?php
//
// Birdseye Component
// Copyright (c) 2013-2014 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// respect the name (unique pls)
$birdseye_component_name = "birdseye";

// run the initialization function
birdseye_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function birdseye_component_init()
{
    global $birdseye_component_name;

    // Check version ok - add error if not
    $versionok = birdseye_component_checkversion();
    $desc = "";
    if (!$versionok)
        $desc = "<br><b>Error: This component requires Nagios XI 2009R1.8 or later.</b>";

    $args = array(
        // name and information
        COMPONENT_NAME => $birdseye_component_name,
        COMPONENT_AUTHOR => "Jake Omann, Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => "A dark-themed NOC-type interactive overview. " . $desc,
        COMPONENT_TITLE => "Birdseye",
        COMPONENT_VERSION => '3.1.2',
        COMPONENT_DATE => '10/24/2016',
        // configuration function (optional)
        //COMPONENT_CONFIGFUNCTION => "birdseye_component_config_func",
    );

    register_component($birdseye_component_name, $args);

    if ($versionok) {
        // configure action callbacks
        register_callback(CALLBACK_MENUS_INITIALIZED, 'birdseye_component_addmenu');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK & CALLBACK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function birdseye_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 207)
        return false;

    return true;
}

function birdseye_component_addmenu($arg = null)
{
    global $birdseye_component_name;

    // retrieve the URL for this component
    $urlbase = get_component_url_base($birdseye_component_name);

    // Get menu order
    $mi = find_menu_item(MENU_HOME, "menu-home-tacticaloverview", "id");
    if ($mi == null) {
        return;
    }
    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    // Add menu spacer
    /*
    $neworder = $order + 0.2;
    add_menu_item(MENU_HOME, array(
            "type" => "linkspacer",
            "title" => "",
            "id" => "menu-home-birdseye_spacer",
            "order" => $neworder,
            "opts" => array()));

    */

    // Add to the menu at "order" location
    $neworder = $order + 0.1;
    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => "Birdseye",
        "id" => "menu-home-birdseye",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/birdseye.php")));
}

function birdseye_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    // Initialize return outputs
    $result = 0;
    $output = "";

    switch ($mode) {
        // Dispaly settings
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // Initial values
            $default_lighting = get_option("default_lighting");
            if ($default_lighting == "on") {
                $dl_on_chk = ' selected="selected"';
            } else if ($default_lighting == "off") {
                $dl_off_chk = ' selected="selected"';
            }

            // Actual output
            $output = '
<p>Birdseye is an interesting graphical NOC-style display. The idea behind BirdsEye 3 is to be able to clearly see issues as they happen.</p>
<div class="sectionTitle">' . _("Global Settings") . '</div>
<br />
<table class="standardtable">
<tr>
    <td><strong>' . _("Default Lighting Option") . '</strong><br />' . _("Sets the default lighting option when you load the page.") . '</td>
    <td style="vertical-align: middle;">
        <select name="default_lighting">
            <option' . $dl_on_chk . '>' . _("On") . '</option>
            <option' . $dl_off_chk . '>' . _("Off") . '</option>
        </select>
    </td>
</tr>
</table>';
            break;

        // Save settings
        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // Get new variables
            $default_lighting = grab_array_var($inargs, "default_lighting", "on");

            // Validate?
            $errors = 0;
            $errmsg = array();

            // Display & handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // Save new variables
            set_option("default_lighting", $default_lighting);

            break;

        default:
            break;
    }

    return $output;
}
