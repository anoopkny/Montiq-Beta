<?php
//
// Custom Logo Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$customlogo_component_name = "customlogo";
customlogo_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function customlogo_component_init()
{
    global $customlogo_component_name;
    $versionok = customlogo_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2009R1.3F or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $customlogo_component_name,
        COMPONENT_VERSION => '1.2.0',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays a custom logo in the header of the XI interface. ") . $desc,
        COMPONENT_TITLE => _("Custom Logo"),
        COMPONENT_CONFIGFUNCTION => "customlogo_component_config_func"
    );

    register_component($customlogo_component_name, $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function customlogo_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 123)
        return false;
    return true;
}

////////////////////////////////////////////////////////////////////////
// CONFIG FUNCTIONS
////////////////////////////////////////////////////////////////////////

function customlogo_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    $result = 0;
    $output = "";

    $component_name = "customlogo";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $settings_raw = get_option("custom_logo_options");
            if ($settings_raw == "")
                $settings = array();
            else
                $settings = unserialize($settings_raw);

            // initial values
            $logo = grab_array_var($settings, "logo", "nagiosxi-logo-small.png");
            $logo_url = grab_array_var($settings, "logo_url", get_base_url());
            $logo_alt = grab_array_var($settings, "logo_alt", "Nagios XI");
            $logo_target = grab_array_var($settings, "logo_target", "_blank");
            $enabled = grab_array_var($settings, "enabled", "");

            // values passed to us
            $logo = grab_array_var($inargs, "logo", $logo);
            $logo_url = grab_array_var($inargs, "logo_url", $logo_url);
            $logo_alt = grab_array_var($inargs, "logo_alt", $logo_alt);
            $logo_target = grab_array_var($inargs, "logo_target", $logo_target);
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", $enabled));

            $component_url = get_component_url_base($component_name);

            $output = '
<p>'._('Use the custom logo component to change the logo in the upper left-hand corner of the web interface.').'</p>
            
<h5 class="ul">' . _('Custom Logo Settings') . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td></td>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="enabled" name="enabled" ' . is_checked($enabled, 1) . '>
                '._('Enable Custom Logo').'
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Logo Image') . ':</label>
        </td>
        <td>
            <input type="text" size="25" name="logo" id="logo" value="' . htmlentities($logo) . '" class="form-control">
            <div class="subtext">' . _('The filename of the image to use as the logo.  The image must already be installed in') . ' <br /><i>/usr/local/nagiosxi/html/images/</i> (100px X 42px).</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Logo Text') . ':</label>
        </td>
        <td>
            <input type="logo_alt" size="40" name="logo_alt" id="logo_alt" value="' . htmlentities($logo_alt) . '" class="form-control">
            <div class="subtext">' . _('Optional text to use for the ALT and TITLE attributes of the logo.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Logo Target') . ':</label>
        </td>
        <td>
            <input type="logo_target" size="40" name="logo_target" id="logo_target" value="' . htmlentities($logo_target) . '" style="width: 100px;" class="form-control">
            <div class="subtext">' . _('Optional target when clicking on logo. (ie. _blank = new tab, _top = same frame)') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Target URL') . ':</label>
        </td>
        <td>
            <input type="logo_url" size="40" name="logo_url" id="logo_url" value="' . htmlentities($logo_url) . '" class="form-control">
            <div class="subtext">' . _('The URL that the logo should link to.') . '</div>
        </td>
    </tr>
</table>';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // get variables
            $logo = grab_array_var($inargs, "logo", "");
            $logo_url = grab_array_var($inargs, "logo_url", "");
            $logo_alt = grab_array_var($inargs, "logo_alt", "");
            $logo_target = grab_array_var($inargs, "logo_target", "_blank");
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", ""));

            // validate variables
            $errors = 0;
            $errmsg = array();
            if ($enabled == 1) {
                if (have_value($logo) == false) {
                    $errmsg[$errors++] = "No logo image specified.";
                }
                if (have_value($logo_url) == false) {
                    $errmsg[$errors++] = "No target URL specified.";
                }
            }

            // handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // save settings
            $settings = array(
                "logo" => $logo,
                "logo_alt" => $logo_alt,
                "logo_url" => $logo_url,
                "logo_target" => $logo_target,
                "enabled" => $enabled,
            );
            set_option("custom_logo_options", serialize($settings));

            break;

        default:
            break;

    }

    return $output;
}