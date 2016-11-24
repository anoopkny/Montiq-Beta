<?php
//
// Reset Credentials
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}


route_request();


function route_request()
{
    global $request;

    // Don't cache credentials, etc.
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");

    if (isset($request['update'])) {
        do_update_options();
    } else {
        show_options();
    }
}


function show_options($error = false, $msg = "")
{
    do_page_start(array("page_title" => _('Security Credentials')), true);

    $opt = get_option("security_credentials_updated");

    if ($opt == 1) {
        $config_admin_password = "";
    } else {
        $config_admin_password = random_string(6);
    }

    $old_subsystem_ticket = get_subsystem_ticket();
    if ($opt == 1) {
        $subsystem_ticket = $old_subsystem_ticket;
    } else {
        $subsystem_ticket = random_string(12);
    }

    if ($opt == 1) {
        $config_backend_password = get_component_credential("nagiosql", "password");
    } else {
        $config_backend_password = random_string(6);
    }

    $old_nagioscore_backend_password = get_component_credential("pnp", "password");
    if ($opt == 1) {
        $nagioscore_backend_password = $old_nagioscore_backend_password;
    } else {
        $nagioscore_backend_password = random_string(8);
    }

    if (in_demo_mode() == true) {
        $config_admin_password = "********";
        $old_subsystem_ticket = "********";
        $subsystem_ticket = "********";
        $config_backend_password = "********";
        $nagioscore_backend_password = "********";
    }
?>

    <h1><?php echo _('Security Credentials'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p><?php echo _('Use this form to reset various internal security credentials used by your XI system. This is an important step to ensure your XI system does not use default passwords or tokens, which may leave it open to a security breach.'); ?></p>

    <form id="manageOptionsForm" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">

        <input type="hidden" name="options" value="1">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="update" value="1">

        <h5 class="ul"><?php echo _('Component Credentials'); ?></h5>

        <p><?php echo _('The credentials listed below are used to manage various aspects of the XI system. Remember these passwords! '); ?></p>
        
        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <label><?php echo _('New Config Manager Admin Password'); ?>:</label>
                </td>
                <td>
                    <input type="password" size="15" name="config_admin_password" id="config_admin_password" value="<?php echo encode_form_val($config_admin_password); ?>" class="textfield form-control" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
                <td>
                    <a href="<?php echo get_base_url() . "includes/components/ccm/"; ?>"
                       target="_self"><?php echo _("Open Config Manager"); ?></a><br>
                    <?php echo _('Admin Username'); ?>: <strong>nagiosadmin</strong>
                </td>
            <tr>
        </table>

        <h5 class="ul"><?php echo _('Sub-System Credentials'); ?></h5>

        <p><?php echo _('You do not need to remember the credentials below, as they are only used internally by the XI system.'); ?></p>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <label><?php echo _('XI Subsystem Ticket'); ?>:</label>
                </td>
                <td>
                    <input type="password" size="15" name="subsystem_ticket" id="subsystem_ticket" value="<?php echo $subsystem_ticket; ?>" class="textfield form-control" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
            <tr>
            <tr>
                <td>
                    <label><?php echo _('Config Manager Backend Password'); ?>:</label>
                </td>
                <td>
                    <input type="password" size="15" name="config_backend_password" id="config_backend_password" value="<?php echo encode_form_val($config_backend_password); ?>" class="textfield form-control" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
            <tr>
            <tr>
                <td>
                    <label><?php echo _('Nagios Core Backend Password'); ?>:</label>
                </td>
                <td>
                    <input type="password" size="15" name="nagioscore_backend_password" id="nagioscore_backend_password" value="<?php echo encode_form_val($nagioscore_backend_password); ?>" class="textfield form-control" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
            <tr>
        </table>

        <div id="formButtons">
            <button type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton"><?php echo _('Update Credentials'); ?></button>
            <button type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton"><?php echo _('Cancel'); ?></button>
        </div>

    </form>

    <?php
    do_page_end(true);
    exit();
}


function do_update_options()
{
    global $request;

    // User pressed the cancel button
    if (isset($request["cancelButton"])) {
        header("Location: main.php");
    }

    // Check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Get values
    $subsystem_ticket = grab_request_var("subsystem_ticket");
    $config_backend_password = grab_request_var("config_backend_password");
    $nagioscore_backend_password = grab_request_var("nagioscore_backend_password");
    $config_admin_password = grab_request_var("config_admin_password");

    // Make sure we have requirements
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    if (have_value($subsystem_ticket) == false)
        $errmsg[$errors++] = _("No subsystem ticket.");
    if (have_value($config_backend_password) == false)
        $errmsg[$errors++] = _("No config backend password.");
    if (have_value($nagioscore_backend_password) == false)
        $errmsg[$errors++] = _("No Nagios Core backend password.");


    // Handle errors
    if ($errors > 0) {
        show_options(true, $errmsg);
    }

    // Config manager (naigosql) admin password
    if (have_value($config_admin_password) == true) {
        nagiosql_update_user_password("nagiosadmin", $config_admin_password);
        send_to_audit_log(_("User changed Core Config Manager admin password"), AUDITLOGTYPE_SECURITY);
    }

    // Backend subsystem ticket
    set_option("subsystem_ticket", $subsystem_ticket);

    // Config manager (nagiosql) backend password
    set_component_credential("nagiosql", "password", $config_backend_password);
    $nagiosql_username = get_component_credential("nagiosql", "username");
    nagiosql_update_user_password($nagiosql_username, $config_backend_password);

    // Nagios core backend password (used by pnp)
    $pnp_username = get_component_credential("pnp", "username");
    set_component_credential("pnp", "password", $nagioscore_backend_password);
    $args = array(
        "username" => $pnp_username,
        "password" => $nagioscore_backend_password
    );
    submit_command(COMMAND_NAGIOSXI_SET_HTACCESS, serialize($args));

    // Mark that security credentials were updates
    set_option("security_credentials_updated", 1);

    // Send a log to the audit log
    send_to_audit_log(_("User updated system security credentials"), AUDITLOGTYPE_SECURITY);

    show_options(false, _("Security credentials updated successfully."));
}