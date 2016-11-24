<?php
//
// XI Core Component Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/ajaxhelpers.inc.php');
include_once(dirname(__FILE__) . '/dashlets.inc.php');
include_once(dirname(__FILE__) . '/status-utils.inc.php');


xicore_component_init();


////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////


function xicore_component_init()
{
    $name = "xicore";
    $args = array(
        COMPONENT_NAME => $name,
        COMPONENT_TITLE => _("Nagios XI Core Functions"),
        COMPONENT_AUTHOR => _("Nagios Enterprises, LLC"),
        COMPONENT_DESCRIPTION => _("Provides core functions and interface functionality for Nagios XI."),
        COMPONENT_COPYRIGHT => _("Copyright (c) 2009-2016 Nagios Enterprises"),
        COMPONENT_PROTECTED => true,
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );
    register_component($name, $args);
}


////////////////////////////////////////////////////////////////////////
// EVENT HANDLER AND NOTIFICATION FUNCTIONS
////////////////////////////////////////////////////////////////////////


register_callback(CALLBACK_EVENT_PROCESSED, 'xicore_eventhandler');


/**
 * @param $cbtype
 * @param $args
 */
function xicore_eventhandler($cbtype, $args)
{
    switch ($args["event_type"]) {
        case EVENTTYPE_NOTIFICATION:
            xicore_handle_notification_event($args);
            break;
        default:
            break;
    }
}


/**
 * @param $args
 */
function xicore_handle_notification_event($args)
{

    $debug = is_null(get_option('enable_subsystem_logging')) ? true : get_option("enable_subsystem_logging");

    // Use this for debug output in PHPmailer log
    $debugmsg = "";

    /*
    $opts=array(
        "from" => "Nagios XI <root@localhost>",
        "to" => "egalstad@nagios.com",
        "subject" => "XI Notification",
        );
    $opts["message"]="A notification was processed...\n\nData:\n\n".serialize($args)."\n\n\n\nEvent Meta:\n\n".serialize($args["event_meta"])."\n\n";
    send_email($opts);
    */

    if ($debug == true) {
        //echo "A notification is being processed...Data:\n\n";
        //print_r($args);
        //echo "\n\n\n\nEvent Meta:\n\n";
        //print_r($args["event_meta"]);
        //echo "\n\n";
    }

    $meta = $args["event_meta"];
    $contact = $meta["contact"];
    $nt = $meta["notification-type"];

    // find the XI user
    $user_id = get_user_id($contact);
    if ($user_id <= 0) {
        if ($debug == true)
            echo "ERROR: Could not find user_id for contact '" . $contact . "'\n";
        return;
    }

    if ($debug == true)
        echo "Got XI user id for contact '" . $contact . "': $user_id\n";

    // set user id session variable - used later in date/time, preference, etc. functions
    //$_SESSION["user_id"]=$user_id;
    if (!defined("NAGIOSXI_USER_ID"))
        define("NAGIOSXI_USER_ID", $user_id);

    // bail if user has notifications disabled completely
    $notifications_enabled = get_user_meta($user_id, 'enable_notifications');
    if ($notifications_enabled != 1) {
        if ($debug == true)
            echo "" . _('ERROR: User has (global) notifications disabled!') . "\n";
        return;
    }

    // EMAIL NOTIFICATIONS
    $notify = get_user_meta($user_id, "notify_by_email");

    // Default Priority
    $priority = ($meta['type'] == "PROBLEM") ? 1 : 0;

    // Get email notification options for user
    $notify_host_recovery = get_user_meta($user_id, 'notify_host_recovery');
    $notify_host_down = get_user_meta($user_id, 'notify_host_down');
    $notify_host_unreachable = get_user_meta($user_id, 'notify_host_unreachable');
    $notify_host_flapping = get_user_meta($user_id, 'notify_host_flapping');
    $notify_host_downtime = get_user_meta($user_id, 'notify_host_downtime');
    $notify_host_acknowledgment = get_user_meta($user_id, 'notify_host_acknowledgment', 1);
    $notify_service_recovery = get_user_meta($user_id, 'notify_service_recovery');
    $notify_service_warning = get_user_meta($user_id, 'notify_service_warning');
    $notify_service_unknown = get_user_meta($user_id, 'notify_service_unknown');
    $notify_service_critical = get_user_meta($user_id, 'notify_service_critical');
    $notify_service_flapping = get_user_meta($user_id, 'notify_service_flapping');
    $notify_service_downtime = get_user_meta($user_id, 'notify_service_downtime');
    $notify_service_acknowledgment = get_user_meta($user_id, 'notify_service_acknowledgment', 1);
    //priority settings
    $notify_host_recovery_priority = get_user_meta($user_id, 'notify_host_recovery_priority');
    $notify_host_down_priority = get_user_meta($user_id, 'notify_host_down_priority');
    $notify_host_unreachable_priority = get_user_meta($user_id, 'notify_host_unreachable_priority');
    $notify_host_flapping_priority = get_user_meta($user_id, 'notify_host_flapping_priority');
    $notify_host_downtime_priority = get_user_meta($user_id, 'notify_host_downtime_priority');
    $notify_host_acknowledgment_priority = get_user_meta($user_id, 'notify_host_acknowledgment_priority');
    $notify_service_recovery_priority = get_user_meta($user_id, 'notify_service_recovery_priority');
    $notify_service_warning_priority = get_user_meta($user_id, 'notify_service_warning_priority');
    $notify_service_unknown_priority = get_user_meta($user_id, 'notify_service_unknown_priority');
    $notify_service_critical_priority = get_user_meta($user_id, 'notify_service_critical_priority');
    $notify_service_flapping_priority = get_user_meta($user_id, 'notify_service_flapping_priority');
    $notify_service_downtime_priority = get_user_meta($user_id, 'notify_service_downtime_priority');
    $notify_service_acknowledgment_priority = get_user_meta($user_id, 'notify_service_acknowledgment_priority');

    // Service
    if ($nt == "service") {
        switch ($meta['type']) {
            case "PROBLEM":
				if (($notify_service_warning != 1) && ($meta['servicestateid'] == 1))
                    $notify = 0;
				else if (($notify_service_critical != 1) && ($meta['servicestateid'] == 2))
					$notify = 0;
				else if (($notify_service_unknown != 1) && ($meta['servicestateid'] == 3))
					$notify =0;
                
                   if (($notify_service_warning_priority != 1) && ($meta['servicestateid'] == 1))
                        $priority = 0;
				else if (($notify_service_critical_priority != 1) && ($meta['servicestateid'] == 2))
					$priority = 0;
				else if (($notify_service_unknown_priority != 1) && ($meta['servicestateid'] == 3))
					$priority = 0;
				break;
            case "RECOVERY":
                if ($notify_service_recovery != 1)
					$notify = 0;
                
                $priority = $notify_service_recovery_priority;

				break;
            case "ACKNOWLEDGEMENT":
                if ($notify_service_acknowledgment != 1)
                    $notify = 0;
                
                $priority = $notify_service_acknowledgment_priority;
                break;
            case "FLAPPINGSTART":
            case "FLAPPINGSTOP":
                if ($notify_service_flapping != 1)
                    $notify = 0;
                
                $priority = $notify_service_flapping_priority;
                break;
            case "DOWNTIMESTART":
            case "DOWNTIMECANCELLED":
            case "DOWNTIMEEND":
                if ($notify_service_downtime != 1)
                    $notify = 0;
                
                $priority = $notify_service_downtime_priority;
                break;
        }    
    } else {
	// Host
        switch ($meta['type']) {
            case "PROBLEM":
                if (($notify_host_down != 1) && ($meta['hoststateid'] == 1))
                    $notify = 0;
				else if (($notify_host_unreachable != 1) && ($meta['hoststateid'] == 2))
					$notify = 0;
                
                if (($notify_host_down_priority != 1) && ($meta['hoststateid'] == 1))
                    $priority = 0;
				else if (($notify_host_unreachable_priority != 1) && ($meta['hoststateid'] == 2))
					$priority = 0;
				break;
            case "RECOVERY":
                if ($notify_host_recovery != 1)
					$notify = 0;
                
                $priority = $notify_host_recovery_priority;
				break;
            case "ACKNOWLEDGEMENT":
                if ($notify_host_acknowledgment != 1)
                    $notify = 0;
                
                $priority = $notify_host_acknowledgment_priority;
                break;
            case "FLAPPINGSTART":
            case "FLAPPINGSTOP":
                if ($notify_host_flapping != 1)
                    $notify = 0;
                
                $priority = $notify_host_flapping_priority;
                break;
            case "DOWNTIMESTART":
			case "DOWNTIMECANCELLED":
			case "DOWNTIMEEND":
                if ($notify_host_downtime != 1)
                    $notify = 0;
                
                $priority = $notify_host_downtime_priority;
                break;
        }    
    }

    if ($notify == 1) {
        if ($debug == true)
            echo _("An email notification will be sent") . "...\n\n";

        // get the user's email address
        $email = get_user_attr($user_id, "email");

        // get the email subject and message
        if ($nt == "service") {
            $subject = get_user_service_email_notification_subject($user_id);
            $message = get_user_service_email_notification_message($user_id);
        } else {
            $subject = get_user_host_email_notification_subject($user_id);
            $message = get_user_host_email_notification_message($user_id);
        }

        // process the alert text and replace variables
        $subject = process_notification_text($subject, $meta);
        $message = process_notification_text($message, $meta);

        // Set where email is coming from for PHPmailer log
        $send_mail_referer = "includes/components/xicore/xicore.inc.php > Event Handler Notification Email";

        $admin_email = get_option("admin_email");
        $opts = array(
            "from" => "Nagios XI <" . $admin_email . ">",
            "to" => $email,
            "subject" => $subject,
            "high_priority" => $priority
        );
        $opts["message"] = $message;

        if ($debug == true) {
            echo "Email Notification Data:\n\n";
            print_r($opts);
            echo "\n\n";
        }

        send_email($opts, $debugmsg, $send_mail_referer);
    } else {
        if ($debug == true)
            echo "" . _('User has email notifications disabled...') . "\n\n";
    }

    // MOBILE TEXT NOTIFICATIONS
    $notify = get_user_meta($user_id, "notify_by_mobiletext");
  
    // Get SMS notificaiton options for user 
    $notify_sms_host_recovery = get_user_meta($user_id, 'notify_sms_host_recovery', $notify_host_recovery);
    $notify_sms_host_down = get_user_meta($user_id, 'notify_sms_host_down', $notify_host_down);
    $notify_sms_host_unreachable = get_user_meta($user_id, 'notify_sms_host_unreachable', $notify_host_unreachable);
    $notify_sms_host_flapping = get_user_meta($user_id, 'notify_sms_host_flapping', $notify_host_flapping);
    $notify_sms_host_downtime = get_user_meta($user_id, 'notify_sms_host_downtime', $notify_host_downtime);
    $notify_sms_host_acknowledgment = get_user_meta($user_id, 'notify_sms_host_acknowledgment', $notify_host_acknowledgment);
    $notify_sms_service_recovery = get_user_meta($user_id, 'notify_sms_service_recovery', $notify_service_recovery);
    $notify_sms_service_warning = get_user_meta($user_id, 'notify_sms_service_warning', $notify_service_warning);
    $notify_sms_service_unknown = get_user_meta($user_id, 'notify_sms_service_unknown', $notify_service_unknown);
    $notify_sms_service_critical = get_user_meta($user_id, 'notify_sms_service_critical', $notify_service_critical);
    $notify_sms_service_flapping = get_user_meta($user_id, 'notify_sms_service_flapping', $notify_service_flapping);
    $notify_sms_service_downtime = get_user_meta($user_id, 'notify_sms_service_downtime', $notify_service_downtime);
    $notify_sms_service_acknowledgment = get_user_meta($user_id, 'notify_sms_service_acknowledgment', $notify_service_acknowledgment);
    
	// Service
    if ($nt == "service") {
        switch ($meta['type']) {
            case "PROBLEM":
                if (($notify_sms_service_warning != 1) && ($meta['servicestateid'] == 1))
                    $notify = 0;
				else if (($notify_sms_service_critical != 1) && ($meta['servicestateid'] == 2))
                    $notify = 0;
				else if (($notify_sms_service_unknown != 1) && ($meta['servicestateid'] == 3))
                    $notify = 0;
				break;
            case "RECOVERY":
                if ($notify_sms_service_recovery != 1)
                    $notify = 0;
				break;
            case "ACKNOWLEDGEMENT":
                if ($notify_sms_service_acknowledgment != 1)
                    $notify = 0;
                break;
            case "FLAPPINGSTART":
            case "FLAPPINGSTOP":
                if ($notify_sms_service_flapping != 1)
                    $notify = 0;
                break;
            case "DOWNTIMESTART":
            case "DOWNTIMECANCELLED":
			case "DOWNTIMEEND":
                if ($notify_sms_service_downtime != 1)
                    $notify = 0;
                break;
        }    
    } else {
	// Host
        switch ($meta['type']) {
            case "PROBLEM":
                if (($notify_sms_host_down != 1) && ($meta['hoststateid'] == 1))
                    $notify = 0;
				else if (($notify_sms_host_unreachable != 1) && ($meta['hoststateid'] == 2))
                    $notify = 0;
				break;
            case "RECOVERY":
                if ($notify_sms_host_recovery != 1)
                    $notify = 0;
				break;
            case "ACKNOWLEDGEMENT":
                if ($notify_sms_host_acknowledgment != 1)
                    $notify = 0;
                break;
            case "FLAPPINGSTART":
            case "FLAPPINGSTOP":
                if ($notify_sms_host_flapping != 1)
                    $notify = 0;
                break;
            case "DOWNTIMESTART":
            case "DOWNTIMECANCELLED":
			case "DOWNTIMEEND":
                if ($notify_sms_host_downtime != 1)
                    $notify = 0;
                break;
        }    
    }

    if ($notify == 1) {
        if ($debug == true)
            echo "" . _('A mobile text notification will be sent...') . "\n\n";

        // get the user's mobile info
        $mobile_number = get_user_meta($user_id, "mobile_number");
        $mobile_provider = get_user_meta($user_id, "mobile_provider");

        // generate the email address to use
        $email = get_mobile_text_email($mobile_number, $mobile_provider);

        if ($debug == true)
            echo "Mobile number: " . $mobile_number . ", Mobile provider: " . $mobile_provider . " => Mobile Email: " . $email . "\n\n";

        // get the email subject and message
        if ($nt == "service") {
            $subject = get_user_service_mobiletext_notification_subject($user_id);
            $message = get_user_service_mobiletext_notification_message($user_id);
        } else {
            $subject = get_user_host_mobiletext_notification_subject($user_id);
            $message = get_user_host_mobiletext_notification_message($user_id);
        }

        // process the alert text and replace variables
        $subject = process_notification_text($subject, $meta);
        $message = process_notification_text($message, $meta);

        // Set where email is coming from for PHPmailer log
        $send_mail_referer = "includes/components/xicore/xicore.inc.php > Event Handler Notification Mobile Text";

        $admin_email = get_option("admin_email");
        $opts = array(
            "from" => "Nagios XI <" . $admin_email . ">",
            "to" => $email,
            "subject" => $subject,
        );
        $opts["message"] = $message;

        if ($debug == true) {
            echo "Mobile Text Notification Data:\n\n";
            print_r($opts);
            echo "\n\n";
        }

        send_email($opts, $debugmsg, $send_mail_referer);
    } else {
        if ($debug == true)
            echo "User has mobile text notifications disabled...\n\n";
    }

}

