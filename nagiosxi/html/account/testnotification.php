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
check_prereqs();
check_authentication();


route_request();


function route_request()
{
    global $request;

    if (isset($request['update']))
        do_test();
    else
        show_test();
    exit;
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_test($error = false, $msg = "")
{
    // check contact details
    $contact_name = get_user_attr(0, "username");
    $arr = get_user_nagioscore_contact_info($contact_name);
    $has_nagiosxi_commands = $arr["has_nagiosxi_commands"]; // does the contact have XI notification commands?

    do_page_start(array("page_title" => _("Send Test Notifications")), true);
    ?>

    <h1><?php echo _("Send Test Notifications"); ?></h1>

    <?php
    display_message($error, false, $msg);
    ?>

    <div>
    <?php
    if ($has_nagiosxi_commands == false) {
        echo _("Testing notification messages is not available for your account.  Contact your Nagios XI administrator for details.");
    } else {
        ?>

        <p><?php echo _("Click the button below to send test notifications to your email and/or mobile phone."); ?></p>

        <form method="post" action="">
            <input type="hidden" name="update" value="1"/>
            <?php echo get_nagios_session_protector(); ?>

            <?php

            $test_mobiletext = true;

            // get user's email
            $email = get_user_attr(0, "email");

            // get the user's mobile info
            $notify_by_mobiletext = get_user_meta(0, "notify_by_mobiletext");
            $mobile_number = get_user_meta(0, "mobile_number");
            $mobile_provider = get_user_meta(0, "mobile_provider");
            $mobile_email = get_mobile_text_email($mobile_number, $mobile_provider);

            // get mobile providers
            $mobile_providers = get_mobile_providers();

            if (!$notify_by_mobiletext || $mobile_number == "" || $mobile_provider == "" || $mobile_email == "")
                $test_mobiletext = false;   
            ?>

            <p>
                <?php echo _("Email notifications will be sent to"); ?>: <b><?php echo $email; ?></b> (<a href="<?php echo get_base_url() . "account/?xiwindow=main.php"; ?>" target="_top"><?php echo _("Change your email address"); ?></a>)
            </p>

            <?php if ($test_mobiletext == true) { ?>
            <p>
                <?php echo _("Your mobile number is ") . $mobile_number . _(" and your provider is ") . $mobile_providers[$mobile_provider]; ?>.<br>
                <?php echo _("Mobile notifications will be sent to"); ?>: <b><?php echo $mobile_email; ?></b> (<a href="<?php echo get_base_url() . "account/?xiwindow=notifyprefs.php"; ?>" target="_top"><b><?php echo _("Change your mobile settings"); ?></b></a>)
            </p>
            <?php } ?>

            <div id="formButtons">
                <button type="submit" class="submitbutton btn btn-sm btn-primary" id="updateButton" name="updateButton"><?php echo _("Send Test Notifications"); ?> <i class="fa fa-chevron-right r"></i></button>
            </div>

        </form>
    </div>

    <?php
    }

    do_page_end(true);
    exit();
}


/**
 * @return array
 */
function do_test()
{
    check_nagios_session_protector();

    // grab variables
    $email = grab_request_var("email", "");
    $mobile_number = grab_request_var("mobile_number", "");
    $mobile_provider = grab_request_var("mobile_provider", "");
    // Use this for debug output in PHPmailer log
    $debugmsg = "";

    $test_email = true;
    $test_mobiletext = true;

    $output = array();

    // get the admin email
    $admin_email = is_null(get_option("admin_email")) ? 'root@localhost' : get_option("admin_email"); //added default value if not set 12/19/2011 -MG

    // get the user's email address
    if ($email == "")
        $email = get_user_attr(0, "email");

    // send a test email notification
    if ($test_email == true) {

        // get the email subject and message
        $subject = _("Nagios XI Email Test");
        $message = _("This is a test email notification from Nagios XI");

        // Set where email is coming from for PHPmailer log
        $send_mail_referer = "account/testnotification.php > PHPmailer Test";

        $opts = array(
            "from" => "Nagios XI <" . $admin_email . ">",
            "to" => $email,
            "subject" => $subject,
        );
        $opts["message"] = $message;

        send_email($opts, $debugmsg, $send_mail_referer);

        $output[] = _("A test email notification was sent to") . " <b>" . $email . "</b>";
        $output[] = "";
    }

    // get the user's mobile info
    if ($mobile_number == "")
        $mobile_number = get_user_meta(0, "mobile_number");
    if ($mobile_provider == "")
        $mobile_provider = get_user_meta(0, "mobile_provider");

    if (!get_user_meta(0, "notify_by_mobiletext") || $mobile_number == "" || $mobile_provider == "")
        $test_mobiletext = false;

    // send a test mobile phone alert
    if ($test_mobiletext == true) {

        // generate the email address to use
        $mobile_email = get_mobile_text_email($mobile_number, $mobile_provider);

        // get the email subject and message
        $subject = _("Nagios XI Mobile Test");
        $message = _("Test alert from Nagios XI");

        // Set where email is coming from for PHPmailer log
        $send_mail_referer = "account/testnotification.php > Mobile Test";

        $opts = array(
            "from" => "Nagios XI <" . $admin_email . ">",
            "to" => $mobile_email,
            "subject" => $subject,
        );
        $opts["message"] = $message;

        send_email($opts, $debugmsg, $send_mail_referer);

        $output[] = _("A test mobile text notification was sent to") . " <b>" . $mobile_email . "</b>";
    }


    show_test(false, $output);
    return $output;
}
