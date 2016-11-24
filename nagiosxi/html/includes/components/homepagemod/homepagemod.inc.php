<?php
//
// Home Page Mod Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$homepagemod_component_name = "homepagemod";
homepagemod_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function homepagemod_component_init()
{
    global $homepagemod_component_name;
    $versionok = homepagemod_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>" . _("Error: This component requires Nagios XI 2011R1.7 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $homepagemod_component_name,
        COMPONENT_VERSION => '1.1.4',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Allows admins and users to customize the home page landing screen."),
        COMPONENT_TITLE => _("Home Page Modification"),
        COMPONENT_CONFIGFUNCTION => "homepagemod_component_config_func",
    );

    register_component($homepagemod_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_HOME_PAGE_OPTIONS, 'homepagemod_options_callback');
        register_callback(CALLBACK_MENUS_INITIALIZED, 'homepagemod_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function homepagemod_component_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }
    if (get_product_release() < 207) {
        return false;
    }
    return true;
}


///////////////////////////////////////////////////////////////////////////////////////////
// CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function homepagemod_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    $result = 0;
    $output = "";

    $component_name = "homepagemod";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // Default homepage settings
            $settings_default = array(
                "destination_type" => "default",
                "destination_url" => "",
                "home_page_title" => "Nagios XI",
                "allow_user_override" => 1
            );

            // Saved settings
            $settings_raw = get_option("homepagemod_component_options");
            if ($settings_raw != "") {
                $settings_default = unserialize($settings_raw);
            }

            // Settings passed to us
            $settings = grab_array_var($inargs, "settings", $settings_default);

            // Fix checkboxes
            $settings["enabled"] = checkbox_binary(grab_array_var($settings, "enabled", ""));
            $settings["allow_user_override"] = checkbox_binary(grab_array_var($settings, "allow_user_override", ""));

            $customhide = '';
            if ($settings['destination_type'] != 'custom') {
                $customhide = 'hide';
            }

            $output = ' 

    <script style="text/javascript">
    $(document).ready(function() {
        $("#dest").change(function() {
            if ($(this).val() == "custom") {
                $(".custom-url").show();
            } else {
                $(".custom-url").hide();
            }
        });
    });
    </script>

    <h5 class="ul">'._('Home Page Modification Settings').'</h5>

    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td class="vt">
                <label>'._('Home Page Title').':</label>
            </td>
            <td>
                <input type="text" name="settings[home_page_title]" class="form-control" value="'.htmlentities($settings["home_page_title"]).'" size="30">
                <div class="subtext">'._('Used to override the default home page title. Can be empty which will remove the title.').'</div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>'._('Home Page Destination').':</label>
            </td>
            <td>
                <select name="settings[destination_type]" id="dest" class="form-control">
                    <option value="default" '.is_selected($settings["destination_type"], "default").'>'._('Static XI 5 Home').'</option>
                    <option value="homedashboard" '.is_selected($settings["destination_type"], "homedashboard").'>'._('Home Dashboard').'</option>
                    <option value="custom" '.is_selected($settings["destination_type"], "custom").'>'._('Custom URL').'</option>
                </select>
                <div class="subtext">'._('Where should the home page be directed?').'</div>
            </td>
        </tr>
        <tr class="custom-url '.$customhide.'">
            <td class="vt">
                <label>'._('Custom URL').':</label>
            </td>
            <td>
                <input type="text" name="settings[destination_url]" class="form-control" value="'.htmlentities($settings["destination_url"]).'" size="50">
                <div class="subtext">'._('Specifies a custom URL to be shown as the default home page.').'</div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="checkbox">
                <label>
                    <input type="checkbox" class="checkbox" name="settings[allow_user_override]" '.is_checked($settings["allow_user_override"], 1).'>
                    '._('Allow users to override their default home page settings.').'
                </label>
            </td>
        </tr> 
    </table>';
            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:
            // Get variables
            $settings = grab_array_var($inargs, "settings", array());
            $settings["enabled"] = checkbox_binary(grab_array_var($settings, "enabled", 1));
            $settings["allow_user_override"] = checkbox_binary(grab_array_var($settings, "allow_user_override", ""));

            // Validate variables
            $errors = 0;
            $errmsg = array();

            if ($settings["enabled"] == 1) {
                if ($settings["destination_type"] == "custom" && $settings["destination_url"] == "") {
                    $errmsg[$errors++] = "Custom URL must be specified.";
                }
            }

            // Handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // Save settings
            set_option("homepagemod_component_options", serialize($settings));
            set_option("homepagemod_component_options_configured", 1);
            break;

        default:
            break;

    }

    return $output;
}


///////////////////////////////////////////////////////////////////////////////////////////
// CALLBACK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function homepagemod_options_callback($cbtype, &$cbargs)
{

    // Get our settings
    $settings_raw = get_option("homepagemod_component_options");
    if (empty($settings_raw)) {
        $settings = array(
            "enabled" => 1,
            "destination_type" => "default",
            "destination_url" => "",
            "home_page_title" => "Nagios XI",
            "allow_user_override" => 1
        );
    } else {
        $settings = unserialize($settings_raw);
    }

    // Initial values
    $enabled = grab_array_var($settings, "enabled");

    $global_configured = get_option("homepagemod_component_options_configured");
    $user_configured = get_user_meta(0, "homepagemod_component_options_configured");

    // Bail out if we're not enabled or the component hasn't been configured
    if ($enabled != 1) {
        return;
    }

    // Get defaults being passed in
    $page_title = grab_array_var($cbargs, "page_title");
    $page_url = grab_array_var($cbargs, "page_url");

    $redirect_url = false;
    $destination = "default";

    // GLOBAL SETTINGS
    $component_page_title = grab_array_var($settings, "home_page_title", "");
    $component_destination_url = grab_array_var($settings, "destination_url", "");
    $allow_user_override = grab_array_var($settings, "allow_user_override", 1);

    $page_title = $component_page_title;

    if ($settings["destination_type"] == "homedashboard") {
        $destination = "homedashboard";
    }
    if ($settings["destination_type"] == "custom" && $component_destination_url != "") {
        $page_url = $component_destination_url;
        $redirect_url = true;
        $destination = "custom";
    }

    // USER SETTINGS
    if ($user_configured == 1 && $allow_user_override == 1) {
        $settings_raw = get_user_meta(0, "homepagemod_component_options");
        if ($settings_raw != "") {
            $user_settings = unserialize($settings_raw);
        } else {
            $user_settings = array();
        }

        $user_page_title = grab_array_var($user_settings, "home_page_title", "");
        $user_destination_url = grab_array_var($user_settings, "destination_url", "");
        $user_page_title_sel = grab_array_var($user_settings, "home_page_title_sel", "default");

        if ($user_page_title_sel == 'custom') {
            $page_title = $user_page_title;
        }

        if ($user_settings["destination_type"] == "homedashboard") {
            $destination = "homedashboard";
        }
        if ($user_settings["destination_type"] == "custom" && $user_destination_url != "") {
            $page_url = $user_destination_url;
            $redirect_url = true;
            $destination = "custom";
        }
    }

    // Set options
    $cbargs["destination"] = $destination;
    $cbargs["page_title"] = $page_title;
    $cbargs["page_url"] = $page_url;
    $cbargs["redirect_url"] = $redirect_url;
}

function homepagemod_can_show_menu()
{
    $settings = get_option("homepagemod_component_options", array());
    if (!empty($settings)) { $settings = unserialize($settings); }
    $enabled = 1;
    $override = grab_array_var($settings, "allow_user_override", 0);
    if ($enabled == 0 || $override == 0) {
        return false;
    }
    return true;
}

function homepagemod_addmenu()
{
    $desturl = get_component_url_base("homepagemod");

    $mi = find_menu_item(MENU_ACCOUNT, "menu-account-accountinfo", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if ($order == "") {
        return;
    }

    $neworder = $order + .01;

    add_menu_item(MENU_ACCOUNT, array(
        "type" => "link",
        "title" => _("Home Page Options"),
        "id" => "menu-account-homepageopts",
        "order" => $neworder,
        "opts" => array(
            "href" => $desturl . '/useropts.php'
        ),
        "function" => "homepagemod_can_show_menu"
    ));
}