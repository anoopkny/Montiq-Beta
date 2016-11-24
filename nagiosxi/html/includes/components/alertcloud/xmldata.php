<?php
//
// Alert Cloud
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff and authentication check
pre_init();
init_session();
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    global $request;

    $mode = grab_request_var("mode");
    switch ($mode) {
        default:
            getxml();
            break;
    }
}

function getxml()
{
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["limitrecords"] = false; // Don't limit records
    $backendargs["combinedhost"] = true; // Get host status too

    $xml = get_xml_service_status($backendargs);
?>
    <tags>
        <?php

        $base_url = get_base_url() . "includes/components/xicore/status.php?show=services&host=";

        if ($xml) {
            $lasthost = "";
            $totalservices = 0;
            $size = 8;
            $class = "";
            $color = "";
            $url = "";
            $serviceproblems = 0;

            foreach ($xml->servicestatus as $ss) {

                $hostname = strval($ss->host_name);
                $servicename = strval($ss->name);
                $hoststate = intval($ss->host_current_state);
                $servicestate = intval($ss->current_state);


                if ($lasthost != $hostname && $lasthost != "") {

                    // write data for last host
                    if ($totalservices > 10)
                        $size = 16;
                    else if ($totalservices > 5)
                        $size = 12;
                    else
                        $size = 8;
                    if ($serviceproblems > 0 && $color == "")
                        $color = 'color="0xFFA121"';
                    echo '<a href="' . $url . '" class="' . $class . '" title="' . $hostname . '" rel="tag" style="font-size: ' . $size . 'pt;" ' . $color . ' target="_blank">' . $lasthost . '</a>';

                    // reset data
                    $totalservices = 0;
                    $color = "";
                    $size = 8;
                    $serviceproblems = 0;
                }

                $totalservices++;

                $url = $base_url . $hostname;

                if ($hoststate != STATE_UP) {
                    $color = 'color="0xE80202"';
                }

                if ($servicestate != STATE_OK)
                    $serviceproblems++;

                $lasthost = $hostname;
            }

            // last host
            if ($lasthost != "") {

                // write data for last host
                if ($totalservices > 10)
                    $size = 16;
                else if ($totalservices > 5)
                    $size = 12;
                else
                    $size = 8;
                if ($serviceproblems > 0 && $color == "")
                    $color = 'color="0xFFA121"';
                echo '<a href="' . $url . '" class="' . $class . '" title="' . $lasthost . '" rel="tag" style="font-size: ' . $size . 'pt;" ' . $color . ' target="_blank">' . $lasthost . '</a>';
            }

        }
        ?>
    </tags>
<?php
}