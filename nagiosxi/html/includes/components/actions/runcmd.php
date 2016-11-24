<?php
//
// Actions Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//  

require_once(dirname(__FILE__) . '/../../common.inc.php');
require_once('./actions.inc.php');

// Initialization stuff
pre_init();
init_session();
grab_request_vars();

check_prereqs();
check_authentication(false);

doit();

function doit()
{

    $action = grab_request_var("action", -1);
    $uid = grab_request_var("uid", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");

    $debug = grab_request_var("debug", 0);

    if ($debug) {
        echo "GOT IT!<BR><BR>";
        echo "ACTION: $action<BR>";
        echo "UID: $uid<BR>";
        echo "HOST: $host<BR>";
        echo "SERVICE: $service<BR>";
    }

    // get our settings
    $settings_raw = get_option("actions_component_options");
    if ($settings_raw == "") {
        $settings = array(
            "enabled" => 0,
        );
    } else {
        $settings = unserialize($settings_raw);
    }

    // initial values
    $enabled = grab_array_var($settings, "enabled");

    //print_r($settings);

    // bail out if we're not enabled...
    if ($enabled != 1) {
        echo "ERROR: Component is disabled.";
        return;
    }

    // find the action
    $actarr = $settings["actions"][$action];
    if (!is_array($actarr)) {
        echo "ERROR: Invalid action id.";
        return;
    }

    //check the uid
    /*
    if($uid!=$actarr["uid"]){
        echo "ERROR: Invalid UID.";
        return;
        }
    */

    // check the type
    if ($actarr["action_type"] != "command") {
        echo "ERROR: Invalid action type.";
        return;
    }


    if ($debug) {
        echo "ACTION ARRAY:<BR>";
        print_r($actarr);
    }

    $rawcmd = $actarr["url"];
    if ($debug)
        echo "RAW CMD: $rawcmd<BR>";

    // get variables
    if ($service == "")
        $objectvars = actions_component_get_host_vars($host);
    else {
        // fetch host status if needed

        $objectvars = actions_component_get_service_vars($host, $service);
    }
    if ($debug) {
        echo "OBJECTVARS:<BR>";
        print_r($objectvars);
    }

    // process vars in cmd
    foreach ($objectvars as $var => $val) {
        $tvar = "%" . $var . "%";
        $rawcmd = str_replace($tvar, $val, $rawcmd);
    }

    $cmdline = escapeshellcmd($rawcmd);

    if ($debug) {
        echo "ESCAPED: $cmdline<BR>";
    }

    do_page_start(array("page_title" => _('Run Command')), true);

    echo "<p>"._('Running').": <b>$rawcmd</b></p>";
    echo "<pre>";

    // disable buffering
    ob_implicit_flush(true);
    ob_end_flush();

    session_write_close();
    system($cmdline);

    echo "</pre>";

    echo "<div>
            <div class='fl'>
                <a class='btn btn-sm btn-default' onClick=\"window.location.reload()\"><i class='fa fa-refresh fa-l'></i> "._('Run Again')."</a>
            </div>
            <div class='fr'>
                <a href='javascript:window.close();' class='btn btn-sm btn-default'><i class='fa fa-times fa-l'></i> "._('Close')."</a>
            </div>
            <div class='clear'></div>
        </div>";

    do_page_end(true);
}