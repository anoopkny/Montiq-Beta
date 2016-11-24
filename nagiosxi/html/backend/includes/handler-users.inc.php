<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/common.inc.php');


// USERS (FRONTEND)  *************************************************************************
function fetch_users()
{
    global $request;
    $output = get_users_xml_output($request);
    print backend_output($output);
}
