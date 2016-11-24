<?php
//
// Alert Cloud
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

// Initialization stuff and authentication
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
            display_alertcloud();
            break;
    }
}

function display_alertcloud()
{
    $width = grab_request_var("width", 600);
    $height = grab_request_var("height", 500);
    $bgcolor = grab_request_var("bgcolor", "ffff");
    $tcolor = grab_request_var("tcolor", "0BA000");
    $tcolor2 = grab_request_var("tcolor2", "0BA000");
    $speed = grab_request_var("speed", 50);
    $distr = grab_request_var("distr", 1);
    $hicolor = grab_request_var("hicolor", 1);
    $trans = grab_request_var("trans", "true");
    $data = grab_request_var("data", "alerts");

    do_page_start(array("page_title" => "Alert Cloud"), true);
?>

    <h1><?php echo _("Alert Cloud"); ?></h1>

    <div class="reportexportlinks">
        <?php echo get_add_myreport_html("Alert Cloud", $_SERVER["REQUEST_URI"], array()); ?>
    </div>

    <p>
        <?php echo _("The alert cloud provides a dynamic, visual representation of the state of your network.  Host names are color-coded to indicate their state, as well as the state of services associated with them."); ?>
    </p>

    <div>
        <table class="table table-condensed table-bordered table-auto-width">
            <tbody>
                <tr>
                    <td><b><?php echo _("Color Legend"); ?>:</b></td>
                    <td><span style="color: #0BA000;"><b><?php echo _("Green"); ?></b></span> - <?php echo _("Host is up and all services are ok"); ?></td>
                    <td><span style="color: #E80202;"><b><?php echo _("Red"); ?></b></span> - <?php echo _("Host is down or unreachable"); ?></td>
                    <td><span style="color: #FFA121;"><b><?php echo _("Orange"); ?></b></span> - <?php echo _(" Host is up, but one or more services have problems"); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="fl">
    <?php
    $dargs = array(
        DASHLET_ARGS => array(
            "width" => $width,
            "height" => $height,
            "bgcolor" => $bgcolor,
            "trans" => $trans,
            "tcolor" => $tcolor,
            "tcolor2" => $tcolor2,
            "hicolor" => $hicolor,
            "speed" => $speed,
            "distr" => $distr,
            "data" => $data,
        ),
    );
    display_dashlet("alertcloud", "", $dargs, DASHLET_MODE_OUTBOARD);
    ?>
    </div>

<?php
    do_page_end(true);
}