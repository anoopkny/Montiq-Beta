<?php
// Standalone Graph
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: graph.php 359 2010-10-31 17:08:47Z swilkerson $

require_once(dirname(__FILE__) . '/../../common.inc.php');


// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication(true);

route_request();

function route_request()
{
    global $request;

    $mode = grab_request_var("mode");
    switch ($mode) {
        default:
            graphexplorer_get_graph();
            break;
    }
}

function graphexplorer_get_graph(){
    global $request;
    
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "_HOST_");
    $width = grab_request_var("width", "600");
    $height = grab_request_var("height", "260");
    $view = grab_request_var("view", "");
    $start = grab_request_var("start", "");
    $end = grab_request_var("end", "");
    
    $title = $host;
    $title .= ($service == "_HOST_") ? " Graph" : " : " . $service . " Graph";
    
    // start the HTML page
    do_page_start(array("page_title" => $title), true);
    
    echo "<div id='scriptcontainer'></div>
    <div id='graphcontainer'></div>
    <script type='text/javascript'>  
    $(document).ready(function(){
        var host = '".urlencode($host)."';
        var service = '".urlencode($service)."';
        var args = 'height=".$height."&width=".$width."&type=timeline&host=' + host + '&service=' + service + '&div=graphcontainer&view=".$view."&start=".$start."&end=".$end."';
        var url = base_url + 'includes/components/graphexplorer/visApi.php?' + args;
        $('#scriptcontainer').load(base_url + 'includes/components/graphexplorer/visApi.php?' + args, function () {
        });
    });
    </script>";
    
    // closes the HTML page
    do_page_end(true);
}




        
        
