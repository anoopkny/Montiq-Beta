<?php
require_once(dirname(__FILE__) . '/cp-common.inc.php');

route_request();

function route_request()
{
    $action = grab_request_var('cmd', null);

    $host = grab_request_var('host', null);
    $service = grab_request_var('service', null);
    $track = grab_request_var('track', null);

    switch ($action) {
        case 'timeframe':
            $result = get_timeframe($host, $service, $track);
            break;
        case 'extrapolate':
            $options = grab_request_var('options', array());
            $result = get_extrapolation($host, $service, $track, $options);
            break;
        default:
            $result = '{"error": "Unknown command."}';
            break;
    }

    header('Content-type: application/json');
    print $result;
}
