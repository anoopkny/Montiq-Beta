<?php
//
// Mail settings for Nagios XI sent emails
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

    if (isset($request['update'])) {
        do_update_settings();
    } else {
        show_settings();
    }
}


function show_settings($error = false, $msg = "")
{

    // Get defaults
    $mailmethod = get_option("mail_method");
    if ($mailmethod == "") {
        $mailmethod = "sendmail";
    }

    $fromaddress = get_option("mail_from_address");
    if ($fromaddress == "") {
        $fromaddress = "Nagios XI <" . get_option("admin_email") . ">";
    }

    $smtphost = get_option("smtp_host");
    $smtpport = get_option("smtp_port");
    $smtpusername = get_option("smtp_username");
    $smtppassword = get_option("smtp_password");
    $smtpsecurity = get_option("smtp_security");
    $debug = get_option("php_sendmail_debug");

    if ($smtpsecurity == "") {
        $smtpsecurity = "none";
    }

    // Get variables submitted to us
    $mailmethod = grab_request_var("mailmethod", $mailmethod);
    $fromaddress = grab_request_var("fromaddress", $fromaddress);
    $smtphost = grab_request_var("smtphost", $smtphost);
    $smtpport = grab_request_var("smtpport", $smtpport);
    $smtpusername = grab_request_var("smtpusername", $smtpusername);
    $smtppassword = grab_request_var("smtppassword", $smtppassword);
    $smtpsecurity = grab_request_var("smtpsecurity", $smtpsecurity);

    do_page_start(array("page_title" => _('Mail Settings')), true);
?>

    <h1><?php echo _('Mail Settings'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p><?php echo _('Modify the settings used by your Nagios XI system for sending email alerts and informational messages.'); ?><br><strong><?php echo _('Note'); ?>:</strong> <?php echo _('Mail messages may fail to be delivered if your XI server does not have a valid DNS name.'); ?></p>
    <p><a href="testemail.php" class="btn btn-sm btn-info"><i class="fa fa-paper-plane"></i> <?php echo _("Send a Test Email"); ?></a></p>

    <form id="manageMailSettingsForm" method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>">

        <input type="hidden" name="update" value="1">
        <?php echo get_nagios_session_protector(); ?>

        <h5 class="ul"><?php echo _('General Mail Settings'); ?></h5>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td class="vt">
                    <label><?php echo _('Send Mail From'); ?>:</label>
                </td>
                <td>
                    <input name="fromaddress" type="text" class="textfield form-control" value="<?php echo encode_form_val($fromaddress); ?>" size="40">
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label><?php echo _('Mail Method'); ?>:</label>
                </td>
                <td>
                    <div class="radio" style="margin: 0;">
                        <label>
                            <input name="mailmethod" type="radio" value="sendmail" <?php echo is_checked($mailmethod, "sendmail"); ?>>Sendmail
                        </label>
                    </div>
                    <div class="radio" style="margin: 0;">
                        <label>
                            <input name="mailmethod" type="radio" value="smtp" <?php echo is_checked($mailmethod, "smtp"); ?>>SMTP
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label><?php echo _('Debug Log'); ?>:</label>
                </td>
                <td class="checkbox">
                    <label class="checkbox" style="margin-top: 6px !important;">
                        <input type="checkbox" class="checkbox" id="debug" name="debug" <?php echo is_checked($debug, 1); ?>><?php echo _('This will enable debug logging for phpmailer emails. The log is located here: <b>') . get_tmp_dir() . '/phpmailer.log</b>'; ?><br><?php echo _('Sendmail log location depends on your system (examples: <b>/var/log/maillog</b> or <b>/var/log/mail.log</b>)'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h5 class="ul"><?php echo _('SMTP Settings'); ?></h5>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <label><?php echo _('Host'); ?>:</label>
                </td>
                <td>
                    <input name="smtphost" type="text" class="textfield form-control" value="<?php echo encode_form_val($smtphost); ?>" size="40">
                </td>
            </tr>
            <tr>
                <td>
                    <label><?php echo _('Port'); ?>:</label>
                </td>
                <td>
                    <input name="smtpport" type="text" class="textfield form-control" value="<?php echo encode_form_val($smtpport); ?>" size="4">
                </td>
            </tr>
            <tr>
                <td>
                    <label><?php echo _('Username'); ?>:</label>
                </td>
                <td>
                    <input name="smtpusername" type="text" class="textfield form-control" value="<?php echo encode_form_val($smtpusername); ?>" size="20">
                </td>
            </tr>

            <tr>
                <td>
                    <label><?php echo _('Password'); ?>:</label>
                </td>
                <td>
                    <input name="smtppassword" type="password" class="textfield form-control" value="<?php echo encode_form_val($smtppassword); ?>" size="20" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label><?php echo _('Security'); ?>:</label>
                </td>
                <td>
                    <div class="radio" style="margin: 0;">
                        <label>
                            <input name="smtpsecurity" type="radio" value="none" <?php echo is_checked($smtpsecurity, "none"); ?>><?php echo _("None"); ?>
                        </label>
                    </div>
                    <div class="radio" style="margin: 0;">
                        <label>
                            <input name="smtpsecurity" type="radio" value="tls" <?php echo is_checked($smtpsecurity, "tls"); ?>>TLS
                        </label>
                    </div>
                    <div class="radio" style="margin: 0;">
                        <label>
                            <input name="smtpsecurity" type="radio" value="ssl" <?php echo is_checked($smtpsecurity, "ssl"); ?>>SSL
                        </label>
                    </div>
                </td>
            </tr>
        </table>

        <div id="formButtons">
            <button type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton" id="updateButton"><?php echo _('Update Settings'); ?></button>
            <button type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton" id="cancelButton"><?php echo _('Cancel'); ?></button>
        </div>


        <!--</fieldset>-->
    </form>


    <?php

    do_page_end(true);
    exit();
}


function do_update_settings()
{
    global $request;

    // user pressed the cancel button
    if (isset($request["cancelButton"]))
        header("Location: main.php");

    // check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // defaults
    $mailmethod = "sendmail";
    $fromaddress = "";
    $smtphost = "";
    $smtpport = "";
    $smtpusername = "";
    $smtppassword = "";
    $smtpsecurity = "";
    $debug = "";

    // get variables submitted to us
    $mailmethod = grab_request_var("mailmethod", $mailmethod);
    $fromaddress = grab_request_var("fromaddress", $fromaddress);
    $smtphost = grab_request_var("smtphost", $smtphost);
    $smtpport = grab_request_var("smtpport", $smtpport);
    $smtpusername = grab_request_var("smtpusername", $smtpusername);
    $smtppassword = grab_request_var("smtppassword", $smtppassword);
    $smtpsecurity = grab_request_var("smtpsecurity", $smtpsecurity);
    $debug = grab_request_var("debug", $debug);

    // make sure we have requirements
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    if (have_value($fromaddress) == false)
        $errmsg[$errors++] = _("No from address specified.");
    if ($mailmethod == "smtp") {
        if (have_value($smtphost) == false)
            $errmsg[$errors++] = _("No SMTP host specified.");
        if (have_value($smtpport) == false)
            $errmsg[$errors++] = _("No SMTP port specified.");
    }

    // update settings
    if (in_demo_mode() == false) {
        set_option("mail_method", $mailmethod);
        set_option("mail_from_address", $fromaddress);
        set_option("smtp_host", $smtphost);
        set_option("smtp_port", $smtpport);
        set_option("smtp_username", $smtpusername);
        set_option("smtp_password", $smtppassword);
        set_option("smtp_security", $smtpsecurity);
        set_option("php_sendmail_debug", $debug);
    }

    // handle errors
    if ($errors > 0)
        show_settings(true, $errmsg);

    // mark that settings were updated
    set_option("mail_settings_configured", 1);

    // log it
    send_to_audit_log("User updated global mail settings", AUDITLOGTYPE_CHANGE);

    // success!
    show_settings(false, _("Mail settings updated."));
}


?>