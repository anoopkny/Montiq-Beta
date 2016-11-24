<?php
//
// Proxy Component
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id: globalconfig.php 319 2010-09-24 19:18:25Z egalstad $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs 
grab_request_vars();
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}


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
    global $request;

    // Proxy options
    $use_proxy = grab_request_var("use_proxy", get_option('use_proxy')); //checkbox
    $proxy_address = grab_request_var("proxy_address", get_option('proxy_address'));
    $proxy_port = grab_request_var("proxy_port", get_option('proxy_port'));
    $proxy_auth = grab_request_var("proxy_auth", get_option('proxy_auth'));
    $proxy_tunnel = grab_request_var("proxy_tunnel", get_option('proxy_tunnel', 1));

    do_page_start(array("page_title" => _("Proxy Configuration")), true);
?>

    <h1><?php echo _("Proxy Configuration"); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p><?php echo _('Set up the proxy that Nagios XI will use when contacting the Nagios update server.'); ?></p>

    <form id="manageOptionsForm" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">

        <input type="hidden" name="options" value="1">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="update" value="1">

        <div class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="use_proxy" name="use_proxy" <?php echo is_checked($use_proxy, 1); ?>>
                <?php echo _("Enable proxy for update checks"); ?>
            </label>
        </div>

        <h5 class="ul"><?php echo _("Proxy Settings"); ?></h5>

        <table class="table table-no-border table-condensed table-auto-width">
            <tr>
                <td><label for="proxy_address"><?php echo _("Proxy Address"); ?>:</label></td>
                <td>
                    <input type="text" size="45" name="proxy_address" id="proxy_address" value="<?php echo encode_form_val($proxy_address); ?>" class="textfield form-control">
                </td>
            </tr>
            <tr>
                <td><label for="adminNameBox"><?php echo _("Proxy Port"); ?>:</label></td>
                <td>
                    <input type="text" size="4" name="proxy_port" id="proxy_port" value="<?php echo encode_form_val($proxy_port); ?>" class="textfield form-control">
                </td>
            </tr>
            <tr>
                <td><label for="proxy_auth"><?php echo _("Proxy Auth"); ?>:</td>
                <td>
                    <input type="text" size="30" name="proxy_auth" id="proxy_auth" value="<?php echo encode_form_val($proxy_auth); ?>" class="textfield form-control" placeholder="username:password">
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" class="checkbox" id="proxy_tunnel" name="proxy_tunnel" <?php echo is_checked($proxy_tunnel, 1); ?>>
                        <?php echo _("Use HTTP tunnel"); ?>
                    </label>
                </td>
            </tr>
        </table>

        <div id="formButtons">
            <button type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton" id="updateButton"><?php echo _('Update Settings'); ?></button>
            <button type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton" id="cancelButton"><?php echo _('Cancel'); ?></button>
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
        header("Location: /nagiosxi/admin/main.php");
    }

    // Check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Proxy address
    $use_proxy = grab_request_var('use_proxy', '');
    if (have_value($use_proxy) == true) {
        $use_proxy = 1;
    } else {
        $use_proxy = 0;
    }

    $proxy_address = grab_request_var('proxy_address', '');
    $proxy_port = grab_request_var('proxy_port', '');
    $proxy_auth = grab_request_var('proxy_auth', '');
    $proxy_tunnel = grab_request_var('proxy_tunnel', '');
    if (have_value($proxy_tunnel) == true)
        $proxy_tunnel = 1;
    else
        $proxy_tunnel = 0;

    // handle errors
    if ($errors > 0)
        show_options(true, $errmsg);

    // update options
    set_option('use_proxy', $use_proxy);
    set_option('proxy_address', $proxy_address);
    set_option('proxy_port', $proxy_port);
    set_option('proxy_auth', $proxy_auth);
    set_option('proxy_tunnel', $proxy_tunnel);

    // success!
    show_options(false, "Proxy settings updated successfully!");
}