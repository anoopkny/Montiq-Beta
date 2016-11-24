<?php
//
// iSMS Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

isms_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function isms_component_init()
{
    $component_name = "isms";
    $args = array(
        COMPONENT_NAME => $component_name,
        COMPONENT_VERSION => "1.2.3",
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Provides integration with the Mutli-Tech iSMS.") . "
        <strong>Requires</strong> Multi-Tech iSMS modem <a href='https://assets.nagios.com/downloads/nagiosxi/docs/MultiTech_iSMS_Integration_With_XI.pdf' target='_blank' title='Documentation' >
        setup</a>.",
        COMPONENT_TITLE => _("Multi-Tech iSMS Integration"),
        COMPONENT_CONFIGFUNCTION => "isms_component_config_func"
    );
    register_component($component_name, $args);

    $args = array(
        NOTIFICATIONMETHOD_FUNCTION => 'isms_component_notificationmethod_func',
    );
    register_notificationmethod('isms', $args);

    register_callback(CALLBACK_USER_NOTIFICATION_METHODS_TABS_INIT, 'isms_component_methods_addtab');
    register_callback(CALLBACK_USER_NOTIFICATION_MESSAGES_TABS_INIT, 'isms_component_messages_addtab');
}


///////////////////////////////////////////////////////////////////////////////////////////
// TAB FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function isms_component_messages_addtab($cbtype, &$cbdata)
{
    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);
    $enabled = grab_array_var($settings, "enabled", 0);
    if ($enabled != 1)
        return;

    $newtab = array(
        "id" => "isms",
        "title" => "iSMS",
    );

    $cbdata["tabs"][] = $newtab;
}


function isms_component_methods_addtab($cbtype, &$cbdata)
{
    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);
    $enabled = grab_array_var($settings, "enabled", 0);
    if ($enabled != 1)
        return;

    $newtab = array(
        "id" => "isms",
        "title" => "iSMS",
    );

    $cbdata["tabs"][] = $newtab;
}


///////////////////////////////////////////////////////////////////////////////////////////
// NOTIFICATION METHOD FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function isms_component_notificationmethod_func($mode = "", $inargs, &$outargs, &$result)
{

    $component_name = "isms";

    // initialize return values
    $result = 0;
    $outargs = array();
    $output = '';

    // bail if this component has been disabled by the admin
    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);
    $isms_enabled = grab_array_var($settings, "enabled", 0);
    if ($isms_enabled != 1)
        return $output;

    switch ($mode) {

        case NOTIFICATIONMETHOD_MODE_GETCONFIGOPTIONS:

            // defaults
            $isms_notifications_enabled = get_user_meta(0, 'isms_notifications_enabled');
            $isms_mobile_number = get_user_meta(0, 'isms_mobile_number');
            if ($isms_mobile_number == "")
                $isms_mobile_number = get_user_meta(0, 'mobile_number');

            // get values from form submission
            $isms_notifications_enabled = grab_request_var("isms_notifications_enabled", $isms_notifications_enabled);
            $isms_mobile_number = grab_request_var("isms_mobile_number", $isms_mobile_number);

            $isms_notifications_enabled = checkbox_binary($isms_notifications_enabled);

            $component_url = get_component_url_base($component_name);

            $output = "
            
            <p><img src='" . $component_url . "/images/multitech.png' alt='Multi-Tech iSMS' title='Multi-Tech iSMS'></p>
            
            <table class='table table-condensed table-no-border table-auto-width'>
                <tr>
                    <td class='vt'>
                        <input type='checkbox' class='checkbox' name='isms_notifications_enabled' " . is_checked($isms_notifications_enabled, 1) . ">
                    </td>
                    <td>
                        <b>"._('SMS Text Message')."</b><br>
                        " . _("Receive out-of-band SMS alerts via the") . " <a href='http://www.multitech.com/en_US/PRODUCTS/Families/MultiModemiSMS/' target='_top'>Multi-Tech iSMS</a>.
                        <div class='pad-t5'>
                            <input type='text' size='15' name='isms_mobile_number'value='" . $isms_mobile_number . "' class='form-control condensed' placeholder='"._('Phone number')."'>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type='checkbox' class='checkbox' name='isms_send_test' id='ts'>
                    </td>
                    <td>
                        <label for='ts' style='font-weight: normal; color: #000;'>" . _("Send a test SMS message to the number specified above.") . "</label>
                    </td>
                </tr>
            </table>";
            break;

        case NOTIFICATIONMETHOD_MODE_SETCONFIGOPTIONS:

            $isms_notifications_enabled = grab_array_var($inargs, "isms_notifications_enabled", 0);
            $isms_notifications_enabled = checkbox_binary($isms_notifications_enabled);
            $isms_mobile_number = grab_array_var($inargs, "isms_mobile_number", "");

            // check for errors
            $errors = 0;
            $errmsg = array();
            $okmsg = array();
            if ($isms_notifications_enabled == 1) {
                if ($isms_mobile_number == "") {
                    $errmsg[$errors++] = _("Mobile phone number for SMS alerts is blank.");
                }
            }

            // handle errors
            if ($errors > 0) {
                $outargs[NOTIFICATIONMETHOD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            // save values
            set_user_meta(0, 'isms_notifications_enabled', $isms_notifications_enabled);
            set_user_meta(0, 'isms_mobile_number', $isms_mobile_number);

            // send a test message
            $testnumber = grab_array_var($inargs, "isms_send_test", "");
            $smsoutput = array();
            $smsresult = -1;
            if ($testnumber != "" && $errors == 0) {
                //echo "SENDING TEST...";
                $args = array(
                    "number" => $isms_mobile_number,
                    "message" => "This is a test SMS message from Nagios XI\n" . get_option('url'),
                );
                $smsresult = isms_component_send_sms($args, $smsoutput);

                // handle errors
                if ($smsresult == 1) {
                    $errmsg[$errors++] = _("An error occurred sending the test SMS message.");
                    $outargs[NOTIFICATIONMETHOD_ERROR_MESSAGES] = $errmsg;
                    $result = 1;
                    //echo "<BR>ERRORS<BR><BR>";
                    //print_r($errmsg);
                    return '';
                }

                // success message
                $okmsg = array();
                if ($smsresult == 0) {
                    $okmsg[] = _("Test SMS message sent to ") . $isms_mobile_number . _(" successfully.");
                    $outargs[NOTIFICATIONMETHOD_INFO_MESSAGES] = $okmsg;
                }
            }

            break;

        case NOTIFICATIONMETHOD_MODE_GETMESSAGEFORMAT:


            // defaults/saved values
            $isms_notifications_host_message = isms_component_get_host_message(0);
            $isms_notifications_service_message = isms_component_get_service_message(0);

            // newly submitted values
            $isms_notifications_host_message = grab_array_var($inargs, "isms_notifications_host_message", $isms_notifications_host_message);
            $isms_notifications_service_message = grab_array_var($inargs, "isms_notifications_service_message", $isms_notifications_service_message);

            $component_url = get_component_url_base($component_name);


            // warn user about notifications being disabled
            if (get_user_meta(0, 'isms_notifications_enabled') == 0) {
                $msg = "<div>" . _("Note: You currently have SMS notifications disabled.") . "  <a href='notifymethods.php#tab-custom-isms'>" . _("Change settings") . "</a>.</div>";
                $output .= get_message_text(true, false, $msg);
            }

            $output .= '

<p><img src="' . $component_url . '/images/multitech.png" alt="Multi-Tech iSMS" title="Multi-Tech iSMS"></p>

<h5 class="ul">' . _('SMS Message Settings') . '</h5>
    
<p>' . _('Specify the format of the SMS messages you want to receive.') . '<br><b>' . _('NOTE') . ':</b> ' . _('The maximum length of SMS text messages is 160 characters.  Messages longer than this limit will be trimmed.') . '</p>
    
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Host Alert Message') . ':</label>
        </td>
        <td>
            <textarea name="isms_notifications_host_message" style="width: 400px; height: 100px;" class="form-control">' . htmlentities($isms_notifications_host_message) . '</textarea>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Service Alert Message') . ':</label>
        </td>
        <td>
            <textarea name="isms_notifications_service_message" style="width: 400px; height: 100px;" class="form-control">' . htmlentities($isms_notifications_service_message) . '</textarea>
        </td>
    </tr>
</table>';

            break;

        case NOTIFICATIONMETHOD_MODE_SETMESSAGEFORMAT:

            // newly submitted values
            $isms_notifications_host_message = grab_array_var($inargs, "isms_notifications_host_message", "");
            $isms_notifications_service_message = grab_array_var($inargs, "isms_notifications_service_message", "");

            // save options
            set_user_meta(0, "isms_notifications_host_message", $isms_notifications_host_message);
            set_user_meta(0, "isms_notifications_service_message", $isms_notifications_service_message);


            break;


        default:
            $output = "";
            break;
    }

    return $output;
}


///////////////////////////////////////////////////////////////////////////////////////////
//CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function isms_component_config_func($mode = "", $inargs, &$outargs, &$result)
{

    // initialize return code and output
    $result = 0;
    $output = "";

    $component_name = "isms";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $settings_raw = get_option("isms_component_options");
            if ($settings_raw == "")
                $settings = array();
            else
                $settings = unserialize($settings_raw);

            // initial values
            $address = grab_array_var($settings, "address", "");
            $http_port = grab_array_var($settings, "http_port", "81");
            $username = grab_array_var($settings, "username", "admin");
            $password = grab_array_var($settings, "password", "");
            $enabled = grab_array_var($settings, "enabled", "");
            $authorized_responders = grab_array_var($settings, "authorized_responders", "");

            //echo "ACI1: $autocreateissues<BR>";

            // values passed to us
            $address = grab_array_var($inargs, "address", $address);
            $http_port = grab_array_var($inargs, "http_port", $http_port);
            $username = grab_array_var($inargs, "username", $username);
            $password = grab_array_var($inargs, "password", $password);
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", $enabled));
            $authorized_responders = grab_array_var($inargs, "authorized_responders", $authorized_responders);

            //echo "ACI2: $autocreateissues<BR>";

            //$autocreateissues=checkbox_binary($autocreateissues);

            //echo "ACI3: $autocreateissues<BR>";

            //print_r($inargs);

            $component_url = get_component_url_base($component_name);

            $output = '
            
<p><a href="http://www.multitech.com" target="_blank"><img src="' . $component_url . '/images/multitech.png"></a></p>
<p>' . _('Allows integration between Nagios XI and a Multi-Tech iSMS GSM modem.') . ' <strong>' . _('Requires') . '</strong> ' . _('Multi-Tech iSMS modem') . ' <a href="https://assets.nagios.com/downloads/nagiosxi/docs/MultiTech_iSMS_Integration_With_XI.pdf" target="_blank" title="Documentation">' . _('setup') . '</a>.</p>

<h5 class="ul">' . _('Integration Settings') . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td></td>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="enabled" name="enabled" ' . is_checked($enabled, 1) . '>
                ' . _('Enable integration') . '
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Sender Settings') . '</h5>

<p>' . _('These settings relate to sending SMS alerts from Nagios XI through the iSMS Send API.') . '</p>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . $address . '" class="form-control">
            <div class="subtext">' . _('The IP address of the iSMS.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('HTTP Port:') . '</label>
        </td>
        <td>
            <input type="text" size="4" name="http_port" id="http_port" value="' . $http_port . '" class="form-control">
            <div class="subtext">' . _('The HTTP port used to access the iSMS Send API') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . $username . '" class="form-control">
            <div class="subtext">' . _('The username used to authenticate to the iSMS.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password:') . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . $password . '" class="form-control">
            <div class="subtext">' . _('The password used to authenticate to the iSMS.') . '</div>
        </td>
    </tr>
</table>';

            $receive_url = get_component_url_base($component_name, false) . "/receiver.php";
            /*
            $output.='

            <div class="sectionTitle">Receiver Settings</div>

            <p>These settings relate to processing SMS text messages received from the iSMS Receive API.</p>

            <table>

            <tr>
            <td valign="top">
            <label>Receive API Page:</label>
            </td>
            <td>
        <input type="textfield" size="60" name="receive_url" id="receive_url" value="'.$receive_url.'" class="textfield" readonly />
            Use this URL when configuring the iSMS Receive API page.  Read-Only.<br><br>
            </td>
            </tr>

            <tr>
            <td valign="top">
            <label>Authorized Responders:</label>
            </td>
            <td>
        <textarea name="authorized_responders" cols="20" rows="5">
        '.$authorized_responders.'
        </textarea>
            
            Phone numbers of cellphones that are authorized to acknowledge problems, disable notifications, and submit other commands.<br>Enter one phone number per line in international format (e.g. +16515555555).
            </td>
            </tr>

            </table>
        ';
        */
            $output .= '

<h5 class="ul">' . _('Test Message') . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td valign="top">
        <label>' . _('Phone Number') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="testnumber" id="testnumber" value="" class="form-control">
            <div class="subtext">' . _('Enter a mobile phone number to send a test SMS message to.  This is used for testing the Nagios XI and Multi-Tech iSMS integration.') . '</div>
        </td>
    </tr>
</table>

<p style="font-size: 7pt;">' . _('Multi-Tech and the Multi-Tech logo are trademarks or registered trademarks of') . ' <a href="http://www.multitech.com" target="_blank">Multi-Tech Systems, Inc.</a></p>
            ';
            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // get variables
            $address = grab_array_var($inargs, "address", "");
            $http_port = grab_array_var($inargs, "http_port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $enabled = checkbox_binary(grab_array_var($inargs, "enabled", ""));
            $authorized_responders = grab_array_var($inargs, "authorized_responders", "");

            // validate variables
            $errors = 0;
            $errmsg = array();
            if ($enabled == 1) {
                if (have_value($address) == false) {
                    $errmsg[$errors++] = "No address specified.";
                }
                if (have_value($http_port) == false) {
                    $errmsg[$errors++] = "No HTTP port specified.";
                }
                if (have_value($username) == false) {
                    $errmsg[$errors++] = "No username specified.";
                }
                if (have_value($password) == false) {
                    $errmsg[$errors++] = "No password specified.";
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
                "address" => $address,
                "http_port" => $http_port,
                "username" => $username,
                "password" => $password,
                "enabled" => $enabled,
                "authorized_responders" => $authorized_responders,
            );
            set_option("isms_component_options", serialize($settings));

            // send a test message
            $testnumber = grab_array_var($inargs, "testnumber", "");
            $smsoutput = array();
            $smsresult = -1;
            if ($testnumber != "") {
                $args = array(
                    "number" => $testnumber,
                    "message" => "Test Multi-Tech iSMS message from Nagios XI\n" . get_option('url'),
                );
                $smsresult = isms_component_send_sms($args, $smsoutput);

                // handle errors
                if ($smsresult == 1) {
                    $errmsg[$errors++] = "An error occurred sending the test SMS message.";
                    $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                    $result = 1;
                    return '';
                }
            }

            // info messages
            $okmsg = array();
            $okmsg[] = _("Multi-Tech iSMS settings updated.");
            if ($smsresult == 0) {
                $okmsg[] = _("Test SMS message sent to ") . $testnumber . _(" successfully.");
            }
            if ($smsresult >= 0) {
                //$okmsg[]=serialize($smsoutput);
            }
            $outargs[COMPONENT_INFO_MESSAGES] = $okmsg;

            break;

        default:
            break;

    }

    return $output;
}

function isms_component_get_host_message($user_id)
{
    $txt = get_user_meta($user_id, 'isms_notifications_host_message');
    if ($txt == "")
        $txt = "%host% %type% (%hoststate%) %hostoutput% Addr: %hostaddress%  Time: %datetime% Nagios URL: %xiserverurl%";
    return $txt;
}

function isms_component_get_service_message($user_id)
{
    $txt = get_user_meta($user_id, 'isms_notifications_service_message');
    if ($txt == "")
        $txt = "%host% / %service% %type% (%servicestate%) %serviceoutput% Time: %datetime% Nagios URL: %xiserverurl%";
    return $txt;
}


////////////////////////////////////////////////////////////////////////
// EVENT HANDLER AND NOTIFICATION FUNCTIONS
////////////////////////////////////////////////////////////////////////

register_callback(CALLBACK_EVENT_PROCESSED, 'isms_component_eventhandler');
//isms_component_eventhandler_register_callbacks();

function isms_component_eventhandler_register_callbacks()
{

    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);

    // bail out of component is not enabled
    $enabled = grab_array_var($settings, "enabled", "");
    if ($enabled != 1)
        return;

    register_callback(CALLBACK_EVENT_PROCESSED, 'isms_component_eventhandler');
}

function isms_component_eventhandler($cbtype, $args)
{

    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);

    // bail out of component is not enabled
    $enabled = grab_array_var($settings, "enabled", "");
    if ($enabled != 1)
        return;

    switch ($args["event_type"]) {
        case EVENTTYPE_NOTIFICATION:
            isms_component_handle_notification_event($args);
            break;
        default:
            break;
    }
}

function isms_component_handle_notification_event($args)
{

    $meta = $args["event_meta"];
    $contact = $meta["contact"];
    $nt = $meta["notification-type"];

    // find the XI user
    $user_id = get_user_id($contact);
    if ($user_id <= 0)
        return;

    echo "==== iSMS Notification Handler ====\n";

    // bail if user has notifications disabled completely
    $notifications_enabled = get_user_meta($user_id, 'enable_notifications');
    if ($notifications_enabled != 1) {
        echo "ERROR: User has (global) notifications disabled!\n";
        return;
    }

    // set user id session variable - used later in date/time, preference, etc. functions
    if (!defined("NAGIOSXI_USER_ID"))
        define("NAGIOSXI_USER_ID", $user_id);

    echo " iSMS: CONTACT=$contact, USERID=$user_id\n";

    // get settings
    $isms_notifications_enabled = get_user_meta($user_id, "isms_notifications_enabled");
    $isms_mobile_number = get_user_meta($user_id, 'isms_mobile_number');

    // not enabled for this user
    if ($isms_notifications_enabled != 1) {
        echo " iSMS: User has SMS notifications disabled\n";
        return 1;
    }

    // don't have a mobile number
    if ($isms_mobile_number == "") {
        echo "iSMS: User does not have a mobile number specified\n";
        return 1;
    }

    // Support for SMS notification options
    if (get_product_release() > 407) {

        // Get SMS notification options for user 
        $notify_sms_host_recovery = get_user_meta($user_id, 'notify_sms_host_recovery', get_user_meta($user_id, 'notify_host_recovery'));
        $notify_sms_host_down = get_user_meta($user_id, 'notify_sms_host_down', get_user_meta($user_id, 'notify_host_down'));
        $notify_sms_host_unreachable = get_user_meta($user_id, 'notify_sms_host_unreachable', get_user_meta($user_id, 'notify_host_unreachable'));
        $notify_sms_host_flapping = get_user_meta($user_id, 'notify_sms_host_flapping', get_user_meta($user_id, 'notify_host_flapping'));
        $notify_sms_host_downtime = get_user_meta($user_id, 'notify_sms_host_downtime', get_user_meta($user_id, 'notify_host_downtime'));
        $notify_sms_service_recovery = get_user_meta($user_id, 'notify_sms_service_recovery', get_user_meta($user_id, 'notify_service_recovery'));
        $notify_sms_service_warning = get_user_meta($user_id, 'notify_sms_service_warning', get_user_meta($user_id, 'notify_service_warning'));
        $notify_sms_service_unknown = get_user_meta($user_id, 'notify_sms_service_unknown', get_user_meta($user_id, 'notify_service_unknown'));
        $notify_sms_service_critical = get_user_meta($user_id, 'notify_sms_service_critical', get_user_meta($user_id, 'notify_service_critical'));
        $notify_sms_service_flapping = get_user_meta($user_id, 'notify_sms_service_flapping', get_user_meta($user_id, 'notify_service_flapping'));
        $notify_sms_service_downtime = get_user_meta($user_id, 'notify_sms_service_downtime', get_user_meta($user_id, 'notify_service_downtime'));

        // Service
        if ($nt == "service") {
            switch ($meta['type']) {
                case "PROBLEM":
                    if (($notify_sms_service_warning != 1) && ($meta['servicestateid'] == 1))
                        return 1;
                    else if (($notify_sms_service_critical != 1) && ($meta['servicestateid'] == 2))
                        return 1;
                    else if (($notify_sms_service_unknown != 1) && ($meta['servicestateid'] == 3))
                        return 1;
                    break;
                case "RECOVERY":
                    if ($notify_sms_service_recovery != 1)
                        return 1;
                    break;
                case "FLAPPINGSTART":
                case "FLAPPINGSTOP":
                    if ($notify_sms_service_flapping != 1)
                        return 1;
                    break;
                case "DOWNTIMESTART":
                case "DOWNTIMECANCELLED":
                case "DOWNTIMEEND":
                    if ($notify_sms_service_downtime != 1)
                        return 1;
                    break;
            }    
        } else {
            // Host
            switch ($meta['type']) {
                case "PROBLEM":
                    if (($notify_sms_host_down != 1) && ($meta['hoststateid'] == 1))
                        return 1;
                    else if (($notify_sms_host_unreachable != 1) && ($meta['hoststateid'] == 2))
                        return 1;
                break;
                case "RECOVERY":
                    if ($notify_sms_host_recovery != 1)
                        return 1;
                break;
                case "FLAPPINGSTART":
                    case "FLAPPINGSTOP":
                    if ($notify_sms_host_flapping != 1)
                        return 1;
                break;
                case "DOWNTIMESTART":
                    case "DOWNTIMECANCELLED":
                    case "DOWNTIMEEND":
                    if ($notify_sms_host_downtime != 1)
                        return 1;
                break;
            }    
        }
    }
    
    // get the SMS message
    if ($meta["notification-type"] == "service") {
        $message = isms_component_get_service_message($user_id);
    } else {
        $message = isms_component_get_host_message($user_id);
    }

    echo " iSMS: RAW MESSAGE='" . $message . "'\n";

    // process notification text (replace variables)
    $message = process_notification_text($message, $meta);

    // trim the message
    $message = substr($message, 0, 159);

    echo " iSMS: SMS MESSAGE='" . $message . "'\n";

    $args = array(
        "number" => $isms_mobile_number,
        "message" => $message,
    );
    //echo "iSMS: SMS ARGS:\n";
    //print_r($args);
    //echo "\n";

    // send the SMS message
    $outargs = array();
    $smsresult = isms_component_send_sms($args, $outargs);


    echo "SMS RESULT=$smsresult\n";
    //echo "SMS OUTARGS:\n";
    //print_r($outargs);
    //echo "\n";

    return 0;
}


////////////////////////////////////////////////////////////////////////
// ISMS NOTIFICATION FUNCTIONS
////////////////////////////////////////////////////////////////////////

function isms_component_send_sms($args, &$outargs)
{

    $number = grab_array_var($args, "number");
    $message = grab_array_var($args, "message");

    // bail if empty number or message
    if ($number == "" || $message == "")
        return 1;

    // load settings
    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);

    $address = grab_array_var($settings, "address");
    $http_port = grab_array_var($settings, "http_port");
    $username = grab_array_var($settings, "username");
    $password = grab_array_var($settings, "password");

    // bail out if we don't have the required info
    if ($address == "" || $http_port == "" || $username == "" || $password == "")
        return 1;

    // construct the URL for the send API
    $url = "http://" . $address . ":" . $http_port . "/sendmsg?user=" . $username . "&passwd=" . $password . "&cat=1&to=\"" . urlencode($number) . "\"&text=" . rawurlencode($message);

    // send the request
    $urloutput = load_url($url, array('method' => 'get', 'return_info' => false));

    // check output for indication of success
    $res = strpos($urloutput, "ID:");
    if ($res === FALSE)
        return 1;

    $outargs = array();
    $outargs["url"] = $url;
    $outargs["result"] = $urloutput;

    return 0;
}


?>