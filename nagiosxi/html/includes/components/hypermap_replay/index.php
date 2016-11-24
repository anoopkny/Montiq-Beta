<?php
//
// Hypermap Replay
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

    // Only certain people can see this
    if (is_authorized_for_all_objects() == false) {
        echo _("You are not authorized to view all hosts and services.");
        exit();
    }

    $mode = grab_request_var("mode");
    switch ($mode) {
        case "getdata":
            hypermap_replay_get_data();
            break;
        default:
            display_hypermap_replay();
            break;
    }
}

function display_hypermap_replay()
{
    include_once(dirname(__FILE__) . '/dashlet.inc.php');

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $type = grab_request_var("type", "");
    $timepoints = grab_request_var("timepoints", 10);
    $refresh = grab_request_var("refresh", 6);

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Start the HTML page
    do_page_start(array("page_title" => "Network Replay"), true);
?>

<script type="text/javascript">
    $(document).ready(function () {
        $('#startdateBox').click(function () {
            $('#reportperiodDropdown').val('custom');
            if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
                $('#startdateBox').val('<?php echo $auto_start_date;?>');
                $('#enddateBox').val('<?php echo $auto_end_date;?>');
            }
        });
        $('#enddateBox').click(function () {
            $('#reportperiodDropdown').val('custom');
            if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
                $('#startdateBox').val('<?php echo $auto_start_date;?>');
                $('#enddateBox').val('<?php echo $auto_end_date;?>');
            }
        });

        showhidedates();

        $('#reportperiodDropdown').change(function () {
            showhidedates();
        });

        function showhidedates() {
            if ($('#reportperiodDropdown').val() == 'custom')
                $('#customdates').show();
            else
                $('#customdates').hide();
        }

    });
</script>

<form method="get" action="<?php echo htmlentities($_SERVER["REQUEST_URI"]); ?>">
    <div class="well report-options">

        <div>

            <div class="reportexportlinks">
                <?php echo get_add_myreport_html("Network Replay", $_SERVER["REQUEST_URI"], array()); ?>
            </div>

            <div class="reporttimepicker">
                <div class="period">
                    <?php echo _("Period"); ?>
                    <select id='reportperiodDropdown' name="reportperiod" class="form-control">
                        <?php
                        $tp = get_report_timeperiod_options();
                        foreach ($tp as $shortname => $longname) {
                            echo "<option value='" . $shortname . "' " . is_selected($shortname, $reportperiod) . ">" . $longname . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div id="customdates" class="cal">
                    <?php echo _("From"); ?>
                    <input class="textfield form-control" type="text" id='startdateBox' name="startdate" value="<?php echo $startdate; ?>" size="16"/><div id="startdatepickercontainer"></div>
                    <div class="reportstartdatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                    <?php echo _("To"); ?>
                    <input class="textfield form-control" type="text" id='enddateBox' name="enddate" value="<?php echo $enddate; ?>" size="16"/><div id="enddatepickercontainer"></div>
                    <div class="reportenddatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                </div>

                <button type="submit" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>
            </div>

        </div>

    </div>
</form>

<h1 style="padding: 0 0 20px 0;"><?php echo _("Network Replay"); ?></h1>

<?php
// don't dislpay this as a dashlet - just call the function directly.  we might want it as a dashlet in the future
$args = array(
    "starttime" => $starttime,
    "endtime" => $endtime,
    "timepoints" => $timepoints,
    "refresh" => $refresh,
);
$output = hypermap_replay_dashlet(DASHLET_MODE_OUTBOARD, "", $args);
echo $output;
?>

<?php
    /*
    $dargs=array(
        DASHLET_ARGS => array(
            "type" => $type,
            ),
        );

        echo "ARGS GOING IN=";
    print_r($dargs);
    echo "<BR>";
    display_dashlet("hypermap_replay","",$dargs,DASHLET_MODE_OUTBOARD);
    */
    ?>

    <?php

    // closes the HTML page
    do_page_end(true);
}