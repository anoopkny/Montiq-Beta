<?php
//
// Home Page Mod Componentl
// User-Specific options for home page mod.
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and do auth checks
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    global $request;

    if (isset($request['update'])) {
        do_update_options();
    } else {
        show_options();
    }
}

function show_options($error = false, $msg = "")
{
    $referer = htmlentities($_SERVER['HTTP_REFERER']);

    // Get settings specified by admin
    $settings_raw = get_option("homepagemod_component_options");
    if (empty($settings_raw)) {
        $settings_raw = get_option("homepagemod_component_options");
    }
    if (empty($settings_raw)) {
        $settings = array(
            "enabled" => 1,
            "destination_type" => "default",
            "destination_url" => "",
            "home_page_title" => "Nagios XI",
            "home_page_title_sel" => "default",
            "allow_user_override" => 0
        );
    } else {
        $settings = unserialize($settings_raw);
    }

    $home_page_title = grab_array_var($settings, "home_page_title", "Nagios XI");
    $allow_override = grab_array_var($settings, 'allow_user_override', 0);

    // Default settings
    $settings_default = array(
        "destination_type" => "default",
        "destination_url" => "",
        "home_page_title" => $home_page_title,
        "home_page_title_sel" => "default"
    );

    // Saved settings
    $settings_raw = get_user_meta(0, "homepagemod_component_options");
    if ($settings_raw != "") {
        $settings_default = unserialize($settings_raw);
    }

    // Settings passed to us
    $settings = grab_request_var("settings", $settings_default);

    $title = _('Home Page Options');

    // Let the user know if they can't override the home page
    if ($allow_override != 1) {
        $error = true;
        $msg .= _("Home page modifications are currently disabled.");
    }

    // Start the HTML page
    do_page_start(array("page_title" => $title), true);
?>

    <script type="text/javascript">
    $(document).ready(function() {

        $('#dest').change(function() {
            var dest = $(this).val();
            if (dest == 'custom') {
                $('.custom-url').show();
            } else {
                $('.custom-url').hide();
            }
        });

        $('#title-sel').change(function() {
            if ($(this).val() == 'custom') {
                $('#title').show();
            } else {
                $('#title').hide();
            }
        });

    });
    </script>

    <h1><?php echo $title; ?></h1>
    <?php display_message($error, false, $msg); ?>
    <p><?php echo _("You can use the settings on this page to affect/override the default home page you see when you first login."); ?></p>

    <form id="manageOptionsForm" method="post" action="<?php echo encode_form_val($_SERVER['PHP_SELF']); ?>">

        <input type="hidden" name="options" value="1">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="update" value="1">
        <input type="hidden" name="referer" value="<?php echo $referer; ?>">

        <h5 class="ul"><?php echo _('Home Page Modification Settings'); ?></h5>
    
        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td class="vt">
                    <label><?php echo _('Home Page Title'); ?></label>
                </td>
                <td>
                    <select name="settings[home_page_title_sel]" id="title-sel" class="form-control">
                        <option value="default" <?php echo is_selected($settings["home_page_title_sel"], "default"); ?>><?php echo _('Admin Default'); ?></option>
                        <option value="custom" <?php echo is_selected($settings["home_page_title_sel"], "custom"); ?>><?php echo _('Custom'); ?></option>
                    </select>
                    <input type="text" name="settings[home_page_title]" id="title" class="form-control" <?php if ($settings['home_page_title_sel'] != 'custom') { echo 'style="display: none;"'; } ?> value="<?php echo encode_form_val($settings["home_page_title"]); ?>" size="30">
                    <div class="subtext"><?php echo _('Used to override the default home page title. Does not work on Static XI 5 Home. Can be empty which will make no title display.'); ?></div>
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label><?php echo _('Home Page Destination'); ?></label>
                </td>
                <td>
                    <select name="settings[destination_type]" id="dest" class="form-control">
                        <option value="default" <?php echo is_selected($settings["destination_type"], "default"); ?>><?php echo _('Admin Default'); ?></option>
                        <option value="statichome" <?php echo is_selected($settings["destination_type"], "statichome"); ?>><?php echo _('Static XI 5 Home'); ?></option>
                        <option value="homedashboard" <?php echo is_selected($settings["destination_type"], "homedashboard"); ?>><?php echo _('Home Dashboard'); ?></option>
                        <option value="custom" <?php echo is_selected($settings["destination_type"], "custom"); ?>><?php echo _('Custom URL'); ?></option>
                    </select>
                    <div class="subtext"><?php echo _('Where should the home page be directed?'); ?></div>
                </td>
            </tr>
            <tr class="custom-url <?php if ($settings["destination_type"] != 'custom') { echo 'hide'; } ?>">
                <td class="vt">
                    <label><?php echo _('Custom URL'); ?></label>
                </td>
                <td>
                    <input type="text" name="settings[destination_url]" class="form-control" value="<?php echo encode_form_val($settings["destination_url"]); ?>" size="50">
                    <div class="subtext"><?php echo _('Specifies a custom URL to be shown as the default home page.'); ?></div>
                </td>
            </tr>
        </table>

        <div id="formButtons">
            <button type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton"><?php echo _('Update Settings'); ?></button>
            <button type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton"><?php echo _('Cancel'); ?></button>
        </div>

    </form>
    <?php
    do_page_end(true);
}


function do_update_options()
{
    global $request;

    // User pressed the cancel button
    if (isset($request["cancelButton"])) {
        $url = $request['referer'];
        if (empty($url)) {
            $url = "main.php";
        }
        header("Location: ".$url);
    }

    // Check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    $settings = grab_request_var("settings", array());

    // Make sure we have requirements
    if (in_demo_mode() == true) {
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    }
    if ($settings["destination_type"] == "custom" && have_value($settings["destination_url"]) == false) {
        $errmsg[$errors++] = _("Destination URL must be specified.");
    }

    // Handle errors
    if ($errors > 0) {
        show_options(true, $errmsg);
    }

    // Update options
    set_user_meta(0, "homepagemod_component_options", serialize($settings), false);
    set_user_meta(0, "homepagemod_component_options_configured", 1, false);

    show_options(false, _("Settings Updated."));
}
