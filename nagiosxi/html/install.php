<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/includes/auth.inc.php');
require_once(dirname(__FILE__) . '/includes/utils.inc.php');
require_once(dirname(__FILE__) . '/includes/pageparts.inc.php');

// Initialization stuff
pre_init();
init_session();

// grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();

route_request();

function route_request()
{
    if (install_needed() == false) {
        header("Location: " . get_base_url());
        exit();
    }

    $pageopt = get_pageopt("");
    switch ($pageopt) {
        case "install":
            do_install();
            break;
        default:
            show_install();
            break;
    }
}


function show_install($error = false, $msg = "")
{

    $url = get_base_url();
    $admin_name = "Nagios Administrator";
    $admin_email = "root@localhost";
    $admin_password = random_string(6);

    do_page_start(array("page_title" => _('Nagios XI Installer')));
?>

<div style="padding: 20px 30px;">

    <h1><?php echo _('Nagios XI Installer'); ?></h1>

    <?php display_message($error, "", $msg); ?>

    <p><?php echo _("Welcome to the Nagios XI installation.  Just answer a few simple questions and you'll be ready to go."); ?></p>

    <form id="manageOptionsForm" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">

        <input type="hidden" name="install" value="1">
        <?php echo get_nagios_session_protector(); ?>

        <div class="sectionTitle"><?php echo _('General Program Settings'); ?></div>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <label for="urlBox"><?php echo _('Program URL'); ?>:</label>
                </td>
                <td>
                    <input type="text" size="45" name="url" id="urlBox" value="<?php echo encode_form_val($url); ?>" class="textfield form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="adminNameBox"><?php echo _('Administrator Name'); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" name="admin_name" id="adminNameBox" value="<?php echo encode_form_val($admin_name); ?>" class="textfield form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="adminEmailBox"><?php echo _('Administrator Email Address'); ?>:</label>
                </td>
                <td>
                    <input type="text" size="40" name="admin_email" id="adminEmailBox" value="<?php echo encode_form_val($admin_email); ?>" class="textfield form-control">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="adminUsername"><?php echo _('Administrator Username'); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" name="admin_username" id="adminUsername" value="nagiosadmin" class="textfield form-control" disabled>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="adminPasswordBox"><?php echo _('Administrator Password'); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" name="admin_password" id="adminPasswordBox" value="<?php echo encode_form_val($admin_password); ?>" class="textfield form-control">
                </td>
            </tr>
        </table>

        <?php
        $current_timezone = get_current_timezone();
        if (!empty($cfg_timezone) && $cfg_timezone != $current_timezone) {
            $current_timezone = $cfg_timezone;
        }
        $timezones = get_timezones();
        ?>

        <div class="sectionTitle"><?php echo _("Timezone Settings"); ?></div>
        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td><label><?php echo _("Timezone"); ?>:</label></td>
                <td>
                    <select id="timezone" name="timezone" class="form-control">
                        <?php
                        $set = false;
                        foreach ($timezones as $name => $tz) { ?>
                            <option value="<?php echo $tz; ?>"<?php if ($tz == $current_timezone) { echo "selected"; $set = true; } ?>><?php echo $name; ?></option>
                        <?php
                        }

                        if (!$set) { ?>
                            <option value="<?php echo $current_timezone; ?>" selected><?php echo $current_timezone; ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        </table>

        <div style="padding-top: 5px;">
            <button type="submit" class="btn btn-sm btn-primary" name="updateButton" id="updateButton"><?php echo _('Install'); ?> <i class="fa fa-chevron-right r"></i></button>
        </div>

    </form>

</div>

    <?php
    do_page_end();
    exit();
}


function do_install()
{

    // check session
    check_nagios_session_protector();

    // get values
    $url = grab_request_var("url", "");
    $admin_name = grab_request_var("admin_name", "");
    $admin_email = grab_request_var("admin_email", "");
    $admin_password = grab_request_var("admin_password", "");

    // check for errors
    $errors = 0;
    $errmsg = array();
    if (have_value($url) == false)
        $errmsg[$errors++] = _("URL is blank.");
    else if (!valid_url($url))
        $errmsg[$errors++] = _("Invalid URL.");
    if (have_value($admin_name) == false)
        $errmsg[$errors++] = _("Name is blank.");
    if (have_value($admin_email) == false)
        $errmsg[$errors++] = _("Email address is blank.");
    else if (!valid_email($admin_email))
        $errmsg[$errors++] = _("Email address is invalid.");
    if (have_value($admin_password) == false)
        $errmsg[$errors++] = _("Password is blank.");

    $uid = get_user_id("nagiosadmin");
    if ($uid <= 0)
        $errmsg[$errors++] = "Unable to get user id for admin account.";

    // handle errors
    if ($errors > 0) {
        //echo "ERRORS: $errors<BR>\n";
        //print_r($errmsg);
        //exit();
        show_install(true, $errmsg);
    }

    // set global options
    set_option("admin_name", $admin_name);
    set_option("admin_email", $admin_email);
    set_option("url", $url);

    // modify the admin account
    //$errmsg="";
    //add_user_account("nagiosadmin",$admin_password,$admin_name,$admin_email,L_GLOBALADMIN,0,$errmsg);
    change_user_attr($uid, "email", $admin_email);
    change_user_attr($uid, "name", $admin_name);
    change_user_attr($uid, "password", md5($admin_password));
    change_user_attr($uid, "backend_ticket", random_string(8));
    change_user_attr($uid, "api_key", random_string(64));

    // do user password change callback
    do_user_password_change_callback($uid, $admin_password);

    // Config manager (naigosql) admin password
    nagiosql_update_user_password("nagiosadmin", $admin_password);

    // Config manager (nagiosql) backend password
    $config_backend_password = random_string(12);
    set_component_credential("nagiosql", "password", $config_backend_password);
    $nagiosql_username = get_component_credential("nagiosql", "username");
    nagiosql_update_user_password($nagiosql_username, $config_backend_password);

    // random PNP / nagios core backend password (used for performance graphs)
    $nagioscore_backend_password = random_string(6);
    $pnp_username = get_component_credential("pnp", "username");
    set_component_credential("pnp", "password", $nagioscore_backend_password);
    $args = array(
        "username" => $pnp_username,
        "password" => $nagioscore_backend_password
    );
    submit_command(COMMAND_NAGIOSXI_SET_HTACCESS, serialize($args));

    // clear license acceptance for nagiosadmin
    set_user_meta($uid, "license_version", -1, false);
    set_user_meta($uid, "auth_type", "local");

    // clear inital task settings
    set_option("system_settings_configured", 0);
    set_option("security_credentials_updated", 0);
    set_option("mail_settings_configured", 0);

    // set installation flags
    set_db_version();
    set_install_version();

    // check trial start date
    $ts = get_trial_start();
    // make sure something didn't get whacked with the customer's install
    if ($ts == 0 || $ts > time()) {
        // todo...
    }

    // delete force install file if it exists
    if (file_exists("/tmp/nagiosxi.forceinstall"))
        unlink("/tmp/nagiosxi.forceinstall");

    // tun on automatic update checks
    set_option('auto_update_check', true);

    // do an update check
    do_update_check(true, true);

    // Get the timezone
    $new_timezone = grab_request_var("timezone", "");
    set_option('timezone', $new_timezone);

    // Update the timezone if we need to!
    $current_timezone = get_current_timezone();
    if ($current_timezone != $new_timezone) {
        $cmd_id = submit_command(COMMAND_CHANGE_TIMEZONE, $new_timezone);
    }
    
    show_install_complete();
}

// Show the finished install page
/**
 * @param bool   $error
 * @param string $msg
 */
function show_install_complete($error = false, $msg = "")
{
    $admin_password = grab_request_var("admin_password", "");

    do_page_start(array("page_title" => _('Installation Complete')));
?>

<div style="padding: 20px 30px;">

    <h1><?php echo _('Installation Complete'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p><?php echo _('Congratulations! You have successfully installed Nagios XI.'); ?></p>
    <p><?php echo _("You may now login to Nagios XI using the following credentials"); ?>:</p>

    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td><?php echo _("Username"); ?>:</td>
            <td><b>nagiosadmin</b></td>
        </tr>
        <tr>
            <td><?php echo _("Password"); ?>:</td>
            <td><b><?php echo $admin_password; ?></b></td>
        </tr>
    </table>

    <p>
        <a href="login.php" class="btn btn-sm btn-primary" target="_blank" rel="noreferrer"><b>Login to Nagios XI</b></a>
    </p>

</div>
<script>setTimeout(function(){ga('send', 'event', 'nagiosxi', 'Install', 'Complete');}, 4000);</script>
    <?php
    do_page_end();
    exit();
}