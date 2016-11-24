<?php
// EVENT LOG REPORTS
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../../common.inc.php');

include_once(dirname(__FILE__) . '/ajax.inc.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication(false);


route_request();

function route_request()
{
    global $request;

    create_map();
}

function create_map()
{

    $t = grab_request_var("t", 0);

    //$rel=get_flat_host_parent_child_relationships();
    //echo "HOST RELATIONSHIPS:<BR>";
    //print_r($rel);
    //echo "<BR>";

    $map = get_host_parent_child_array_map($t);

    echo "TIME: $t<BR>";
    echo "HOST MAP:<BR>";
    print_r($map);
    echo "<BR>";

}
	
