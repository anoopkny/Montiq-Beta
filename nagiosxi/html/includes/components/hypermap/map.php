<?php
//
// Hypermap
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../../common.inc.php');
include_once(dirname(__FILE__) . '/ajax.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);


route_request();


function route_request()
{
    global $request;
    create_map();
}


function create_map()
{
    $map = get_host_parent_child_relationships();

    echo "HOST MAP:<BR>";
    print_r($map);
    echo "<BR>";
}
