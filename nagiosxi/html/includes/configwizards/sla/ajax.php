<?php
//
// SLA WIZARD : AJAX FUNCTION CALLS
//
// Copyright (c) 2013-2016 Nagios Enterprises, LLC.  All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization
pre_init();
init_session();
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    global $request;

    if (isset($request["type"])) {
        switch ($request["type"]) {
            case "local":
                do_local_check();
                break;
        }
    } else {
        echo _("Could not process the request.");
        exit();
    }
}

function do_local_check()
{
    $local = grab_request_var("local");
    clearstatcache();

    if (!file_exists($local)) {
        print json_encode(array("error" => _("The specified file does not exist.")));
        exit();
    }

    if (!is_writable($local)) {
        print json_encode(array("error" => _("The file is likely not writeable by user 'apache' or group 'nagios' - check permissions.")));
        exit();
    }

    print json_encode(array("success" => _('The file exists and is writeable.')));
}