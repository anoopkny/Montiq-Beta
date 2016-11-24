<?php
//
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

route_request();

function route_request()
{
    $pageopt = grab_request_var("pageopt", "info");
    switch ($pageopt) {
        default:
            show_missing_feature_page();
            break;
    }
}


function show_missing_feature_page()
{
    do_missing_feature_page();
}
