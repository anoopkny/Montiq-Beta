<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// Start session
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs(false);
check_authentication(false);

export_perfdata();

// return exported perfdata
function export_perfdata() 
{

    $type = grab_request_var("type", "xml");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "_HOST_");
    $start = grab_request_var("start", "");
    $end = grab_request_var("end", "");
    $step = grab_request_var("step", "");
    $filename = grab_request_var("filename", $host . "_" . $service);
    $export_type = constant('EXPORT_RRD_' . strtoupper($type));

    $exported_data = get_rrd_data($host, $service, $export_type, $start, $end, $step);
    $tmp_name = md5(rand());

    switch($type) {
        case "csv":
            $mime_type = "text/csv";
            break;
        case "json":
            $mime_type = "application/json";
            break;
        case "xml":
            $mime_type = "application/xml";
            break;
        default:
            die("Invalid type specified.");
    }

    // generate the temporary file
    if (!file_put_contents("/tmp/$tmp_name.$type", $exported_data)) { 
        die("Couldn't create temporary file. Check that the directory permissions for
            the /tmp directory are set to 777.");
    }

    // output the file and then delete
    header("Content-Disposition: attachment; filename=\"$filename.$type\"");
    header("Content-Type: $mime_type");
    echo file_get_contents("/tmp/$tmp_name.$type");
    unlink("/tmp/$tmp_name.$type");
}

?>