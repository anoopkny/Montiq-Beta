<?php
// iSMS RECEIVER API
//
// Copyright (c) 2010-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();


////////////////////////////////////////////////////////////////////////
// RECIEVER API
////////////////////////////////////////////////////////////////////////

// execute the main function
isms_component_receiver_api();

function isms_component_receiver_api()
{
    global $request;

    $sender_number = "";
    $message_text = "";
    $message_date = "";
    $message_time = "";

    $xml = "";
    $xmldata = grab_request_var("XMLDATA", "");
    if ($xmldata != "") {
        $xml = simplexml_load_string($xmldata);
        if ($xml) {
            // we have xml!

            $sender_number = strval($xml->SenderNumber);
            $message_text = strval($xml->Message);
            $message_date = strval($xml->Date);
            $message_time = strval($xml->Time);
        }
    }


    // make sure we have message text before proceeding
    if ($message_text == "" || $sender_number == "") {
        echo "EMPTY NUMBER OR MESSAGE";
        exit();
    }

    // get components settings
    $settings_raw = get_option("isms_component_options");
    if ($settings_raw == "")
        $settings = array();
    else
        $settings = unserialize($settings_raw);

    // bail out of component is not enabled
    $enabled = grab_array_var($settings, "enabled", "");
    if ($enabled != 1) {
        echo "COMPONENT NOT ENABLED";
        return;
    }

    // bail out if sender is not on authorized list
    $authorized = false;
    $authorized_responders = grab_array_var($settings, "authorized_responders", "");
    $responders = explode("\n", $authorized_responders);

    if ($sender_number == $authorized_responders)
        $authorized = true;
    else {
        $match = $sender_number;
        $matchtests = "";
        foreach ($responders as $x => $r) {
            $pos = strpos($r, $match);
            $matchtests .= "MATCH[$match] X[$x]=>R[$r], POS=[$pos]\n";
            if ($pos === false) {
            } else {
                $authorized = true;
                break;
            }
        }
    }

    $email_message = "*** RECEIVE API ***\n";
    $email_message .= "SENDER: $sender_number\n";
    $email_message .= "TEXT: $message_text\n";
    $email_message .= "DATE: $message_date\n";
    $email_message .= "TIME: $message_time\n";
    $email_message .= "AUTHORIZED: [$authorized]\n";
    $email_message .= "RESPONDERS: " . serialize($responders) . "\n";
    $email_message .= "MATCHTESTS: $matchtests\n";
    //$email_message.="RECEIVED:\n".serialize($request)."\n\n";
    //$email_message.="XML\n".serialize($xml);

    // send an email to debug information we received
    $args = array(
        "from" => "Test",
        "to" => "egalstad@nagios.com",
        "subject" => "iSMS Receiver",
        "message" => $email_message,
    );
    send_email($args);

    if ($authorized == false) {
        echo "SENDER NOT AUTHORIZED";
        return;
    }

    print_r($args);
}


?>