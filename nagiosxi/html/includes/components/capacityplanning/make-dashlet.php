<?php
require_once(dirname(__FILE__) . '/cp-common.inc.php');
// cp-common handles request initialization and authentication.


echo '<script>';
require_once(dirname(__FILE__) . '/../../js/dashlets.js');
echo '</script>';

// Grab all the needed variables
$host = grab_request_var('host', '');
$service = grab_request_var('service', '');
$track = grab_request_var('track', '');
$method = grab_request_var('method', 'Holt-Winters');
$period = grab_request_var('period', '1 week');

// Make the actual dashlet
$hostlist = base64_encode(serialize(array($host => array('service' => $service, 'track' => $track))));
$hostoptions = base64_encode(serialize(array($host => array('method' => $method, 'period' => $period))));
$dargs = array(
    DASHLET_ARGS => array(
        'hostlist' => $hostlist,
        'hostoptions' => $hostoptions,
        'host' => $host
    ),
);

display_dashlet('capacityplanning', '', $dargs, DASHLET_MODE_OUTBOARD);
