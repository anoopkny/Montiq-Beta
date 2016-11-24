<?php
//
// Nagios CCM Integration Component
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables
grab_request_vars();

// Check prereqs and authentication
check_prereqs();
check_authentication();

if (is_authorized_to_configure_objects() == false) {
    echo "Not authorized";
    exit();
}

route_request();

function route_request()
{
    $cmd = grab_request_var("cmd", "");
    switch ($cmd) {
        default:
            nagioscorecfg_get_page();
            break;
    }
}

function nagioscorecfg_get_page()
{
    global $request;
    $dest = grab_request_var("dest", "");
    $nagios_ccm_url = nagiosql_get_base_url();
    $url = $nagios_ccm_url . "/" . $dest . "?menu=invisible";
    header("Location: " . $url);
}