<?php
//
// Custom Login Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: customlogin.inc.php 902 2012-10-26 21:25:46Z mguthrie $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$customlogin_component_name = "customlogin";
customlogin_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function customlogin_component_init()
{
    global $customlogin_component_name;

    $versionok = customlogin_component_checkversion();

    $desc = "";
    if (!$versionok)
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2012R1.3 or later.") . "</b>";

    $args = array(
        COMPONENT_NAME => $customlogin_component_name,
        COMPONENT_VERSION => '1.0.0',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Allows a custom login splash page to be defined. ") . $desc,
        COMPONENT_TITLE => _("Custom Login"),
        COMPONENT_CONFIGFUNCTION => "customlogin_component_config_func",
    );

    register_component($customlogin_component_name, $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function customlogin_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 301)
        return false;
    return true;
}

////////////////////////////////////////////////////////////////////////
// CONFIG FUNCTIONS
////////////////////////////////////////////////////////////////////////

function customlogin_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    $result = 0;
    $output = "";

    $component_name = "customlogin";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $enabled = get_option("custom_login_splash_enabled");
            $enabled = empty($enabled) ? false : checkbox_binary($enabled, true);
            $file = get_option("custom_login_splash_include");
            $file = empty($file) ? get_base_dir() . '/loginsplash.inc.php' : $file;

            $output = '
            
    <h5 class="ul">' . _('Custom Login Settings') . '</h5>

    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td></td>
            <td class="checkbox">
                <label>
                    <input type="checkbox" class="checkbox" id="enabled" name="enabled" ' . is_checked($enabled, true) . '>
                    '._('Enable Custom Login Splash').'
                </label>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>' . _('Include File') . ':</label>
            </td>
            <td>
                <input type="text" size="40" name="file" id="file" value="' . htmlentities($file) . '" class="form-control">
                <div class="subtext">' . _('The include file to be used on the login page') . '</div>
            </td>
        </tr>
    </table>

            ';
            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            $file = grab_array_var($inargs, "file", "");
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", ""));

            $errors = 0;
            $errmsg = array();
            if ($enabled == 1) {
                if (have_value($file) == false)
                    $errmsg[$errors++] = "No include file specified.";
            }

            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            set_option("custom_login_splash_enabled", $enabled);
            set_option("custom_login_splash_include", $file);

            break;

        default:
            break;

    }

    return $output;
}