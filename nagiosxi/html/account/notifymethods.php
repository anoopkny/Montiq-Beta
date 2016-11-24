<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
decode_request_vars();
check_prereqs();
check_authentication();


route_request();


function route_request()
{
    global $request;

    if (isset($request['update'])) {
        do_updatemethods();
    } else {
        show_updatemethods();
    }
    exit;
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_updatemethods($error = false, $msg = "")
{
    global $request;
    global $notificationmethods;

    // Check contact details
    $contact_name = get_user_attr(0, "username");
    $arr = get_user_nagioscore_contact_info($contact_name);
    $is_nagioscore_contact = $arr["is_nagioscore_contact"]; // Is the user a Nagios Core contact?
    $has_nagiosxi_commands = $arr["has_nagiosxi_commands"]; // Does the contact have XI notification commands?

    // Defaults
    $notify_by_email = get_user_meta(0, 'notify_by_email');
    $notify_by_mobiletext = get_user_meta(0, 'notify_by_mobiletext');
    $mobile_number = get_user_meta(0, 'mobile_number');
    $mobile_provider = get_user_meta(0, 'mobile_provider');

    // Grab form variable values
    $notify_by_email = checkbox_binary(grab_request_var("notify_by_email", $notify_by_email));
    $notify_by_mobiletext = checkbox_binary(grab_request_var("notify_by_mobiletext", $notify_by_mobiletext));
    $mobile_number = grab_request_var("mobile_number", $mobile_number);
    $mobile_provider = grab_request_var("mobile_provider", $mobile_provider);

    // Get a list of mobile providers
    $mobile_providers = get_mobile_providers();

    do_page_start(array("page_title" => _('Notification Methods')), true);
?>

    <h1><?php echo _('Notification Methods'); ?></h1>

    <?php
    // Warn user about notifications being disabled
    if (get_user_meta(0, 'enable_notifications') == 0) {
        $nmsg = array();
        $nmsg[] = '<div><i class="fa fa-exclamation-triangle"></i> <strong>'._('Alert').'!</strong> '._('You currently have notifications disabled for your account').'. <a href="notifyprefs.php">'._('Change your settings').'</a> <i class="fa fa-arrow-circle-right"></i> '._('if you would like to receive alerts').'.</div>';
        echo '<div>';
        display_message(true, false, $nmsg);
        echo '</div>';
    }

    if ($is_nagioscore_contact == false || $has_nagiosxi_commands == false) {
        $error = $arr['error'];
        $msg = $arr['is_nagioscore_contact_message'] . $arr['has_nagiosxi_commands_message'];
    }

    display_message($error, false, $msg);

    if ($is_nagioscore_contact == false) {
        echo _("Management of notification preferences is not available because your account is not configured to be a monitoring contact.  Contact your Nagios XI administrator for details.");
    }

    if ($has_nagiosxi_commands == false) {
        echo _("Management of notification preferences is not available for your account.  Contact your Nagios XI administrator for details.");
    }

    if ($is_nagioscore_contact == true && $has_nagiosxi_commands == true) {
    ?>
        <p>
            <?php echo _("Specify the methods by which you'd like to receive alert messages.  <br><b>Note:</b>These methods are only used if you have <a href='notifyprefs.php'>enabled notifications</a> for your account."); ?>
        </p>
    <?php } ?>

    <form id="updateNotificationMethodsForm" method="post" action="">

        <input type="hidden" name="update" value="1">
        <?php echo get_nagios_session_protector(); ?>

        <?php
        if ($is_nagioscore_contact == true && $has_nagiosxi_commands == true) {
        
            // Get additional tabs
            $cbdata = array(
                "tabs" => array(),
            );
            do_callbacks(CALLBACK_USER_NOTIFICATION_METHODS_TABS_INIT, $cbdata);
            $customtabs = grab_array_var($cbdata, "tabs", array());
            ?>

            <script type="text/javascript">
            $(document).ready(function() {
                $("#tabs").tabs().show();
            });
            </script>

            <div id="tabs" class="hide">
                <ul>
                    <li><a href="#tab-email"><i class="fa fa-envelope"></i> <?php echo _('Email'); ?></a></li>
                    <li><a href="#tab-sms"><i class="fa fa-phone"></i> <?php echo _('Mobile Text (SMS)'); ?></a></li>
                    <?php
                    // Custom tabs
                    foreach ($customtabs as $ct) {
                        $id = grab_array_var($ct, "id");
                        $title = grab_array_var($ct, "title");
                        echo "<li><a href='#tab-custom-" . $id . "'>" . $title . "</a></li>";
                    }
                    ?>
                </ul>

                <div id="tab-email">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td class="checkbox">
                                <label><input type="checkbox" class="checkbox" name="notify_by_email" <?php echo is_checked($notify_by_email, 1); ?>> <?php echo _('Receive alerts via email'); ?>.</label>
                            </td>
                        </tr>
                    </table>
                </div>
                <div id="tab-sms">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td class="checkbox" colspan="2">
                                <label><input type="checkbox" class="checkbox" name="notify_by_mobiletext" <?php echo is_checked($notify_by_mobiletext, 1); ?>> <?php echo _('Receive text alerts to your cellphone'); ?>.</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="mnb"><?php echo _('Mobile Phone Number'); ?>:</label>
                            </td>
                            <td>
                                <input type="text" name="mobile_number" style="width: 120px;" id="mnb" value="<?php echo encode_form_val($mobile_number); ?>" class="textfield form-control">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="mps"><?php echo _('Mobile Phone Carrier'); ?>:</label>
                            </td>
                            <td>
                                <select name="mobile_provider" id="mps" class="dropdown form-control">
                                    <option value=""></option>
                                    <?php foreach ($mobile_providers as $pl => $pt) { ?>
                                    <option value="<?php echo $pl; ?>" <?php echo is_selected($mobile_provider, $pl); ?>><?php echo $pt . "</option>"; ?>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php
                $total_methods = 0;
                foreach ($notificationmethods as $name => $arr) {

                    $inargs = $request;
                    $outargs = array();
                    $result = 0;

                    $output = make_notificationmethod_callback($name, NOTIFICATIONMETHOD_MODE_GETCONFIGOPTIONS, $inargs, $outargs, $result);
                    if ($output != '') {
                        echo "<div id='tab-custom-" . $name . "'>";
                        echo $output;
                        echo "</div>";
                        $total_methods++;
                    }
                }
                ?>

            </div>

            <div>
                <button type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton"><?php echo _('Update Settings'); ?></button>
                <button type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton"><?php echo _('Cancel'); ?></button>
            </div>

        <?php } ?>

    </form>

    <?php
    do_page_end(true);
    exit();
}


function do_updatemethods()
{
    global $request;
    global $notificationmethods;

    // Check session
    check_nagios_session_protector();

    // Check contact details
    $contact_name = get_user_attr(0, "username");
    $arr = get_user_nagioscore_contact_info($contact_name);
    $is_nagioscore_contact = $arr["is_nagioscore_contact"]; // Is the user a Nagios Core contact?

    // User pressed the cancel button
    if (isset($request["cancelButton"])) {
        header("Location: notifyprefs.php");
    }

    // Not a nagios core contact
    if ($is_nagioscore_contact == false) {
        show_updatemethods();
        exit();
    }

    // Defaults
    $notify_by_email = "";
    $notify_by_mobiletext = "";
    $mobile_number = "";
    $mobile_provider = "";

    // Grab form variable values
    $notify_by_email = checkbox_binary(grab_request_var("notify_by_email", $notify_by_email));
    $notify_by_mobiletext = checkbox_binary(grab_request_var("notify_by_mobiletext", $notify_by_mobiletext));
    $mobile_number = grab_request_var("mobile_number", $mobile_number);
    $mobile_provider = grab_request_var("mobile_provider", $mobile_provider);

    // Check for errors
    $errmsg = array();
    $errors = 0;

    if ($notify_by_mobiletext == 1) {
        if (have_value($mobile_number) == false) {
            $errmsg[$errors++] = _("Missing mobile phone number.");
        } else if (!is_valid_mobile_number($mobile_number)) {
            $errmsg[$errors++] = _("Invalid mobile phone number.");
        }
        if (have_value($mobile_provider) == false) {
            $errmsg[$errors++] = _("No mobile carrier selected.");
        }
    }

    // Initialize the "ok" message
    $okmsg = array();
    $okmsg[] = _("Notification methods updated.");

    // Make callbacks to other notification methods
    foreach ($notificationmethods as $name => $arr) {

        $inargs = $request; // Pass request vars to methods
        $outargs = array();
        $result = 0;

        make_notificationmethod_callback($name, NOTIFICATIONMETHOD_MODE_SETCONFIGOPTIONS, $inargs, $outargs, $result);

        // Handle errors
        if ($result != 0) {
            if (array_key_exists(NOTIFICATIONMETHOD_ERROR_MESSAGES, $outargs)) {
                foreach ($outargs[NOTIFICATIONMETHOD_ERROR_MESSAGES] as $e)
                    $errmsg[$errors++] = $e;
            }
        }

        // Info messages
        if (array_key_exists(NOTIFICATIONMETHOD_INFO_MESSAGES, $outargs)) {
            foreach ($outargs[NOTIFICATIONMETHOD_INFO_MESSAGES] as $m) {
                $okmsg[] = $m;
            }
        }
    }

    // Handle errors
    if ($errors > 0) {
        show_updatemethods(true, $errmsg);
    }

    // Set new prefs
    set_user_meta(0, 'notify_by_email', $notify_by_email, false);
    set_user_meta(0, 'notify_by_mobiletext', $notify_by_mobiletext, false);
    set_user_meta(0, 'mobile_number', $mobile_number, false);
    set_user_meta(0, 'mobile_provider', $mobile_provider, false);

    // Log it to the audit log
    send_to_audit_log(_("User updated their notification method settings"), AUDITLOGTYPE_CHANGE);

    show_updatemethods(false, $okmsg);
}