<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/common.inc.php');


// SYSTEM STATISTICS *************************************************************************
function fetch_sysstat_info()
{
    global $request;
    $output = get_sysstat_data_xml_output($request);
    print backend_output($output);
}
