<?php
//
// Alert Timeline
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: eventlog.php 359 2010-10-31 17:08:47Z egalstad $

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

// grab GET or POST variables and check pre-reqs
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
            display_timeline();
            break;
    }
}

function similetimeline_insert_includes()
{
    $url = get_base_url() . "includes/components/similetimeline";
    ?>
    <script type="text/javascript">
        Timeline_ajax_url = "<?php echo $url;?>/timeline_2.3.0/timeline_ajax/simile-ajax-api.js";
        Timeline_urlPrefix = '<?php echo $url;?>/timeline_2.3.0/timeline_js/';
        Timeline_parameters = 'bundle=true';
    </script>
    <!-- NOTE: The local copy of timeline does not appear to show time durations properly, but the online version does -->
    <script src="<?php echo $url; ?>/timeline_2.3.0/timeline_js/timeline-api.js" type="text/javascript"></script>

<?php
}

function display_timeline()
{
    global $request;

    register_callback(CALLBACK_PAGE_HEAD, 'similetimeline_insert_includes');

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $datatype = grab_request_var("datatype", "events");

    // Fix search
    if ($search == _("Search..."))
        $search = "";

    // We search for hosts, so clear host if search is present
    if ($search != "") {
        $host = "";
        $service = "";
    }

    if ($datatype == "nagios") {
        $reportperiod = "custom";
        $startdate = "1999-03-19";
        $enddate = time();
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Start the HTML page
    do_page_start(array("page_title" => "Event Timeline"), true);
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

<form method="get" action="<?php echo htmlentities($_SERVER["REQUEST_URI"], ENT_COMPAT, 'UTF-8'); ?>">
    <div class="well report-options">

        <div>

            <input type="hidden" name="host" value="<?php echo htmlentities($host, ENT_COMPAT, 'UTF-8'); ?>">
            <input type="hidden" name="service" value="<?php echo htmlentities($service, ENT_COMPAT, 'UTF-8'); ?>">
            <input type="hidden" name="hostgroup" value="<?php echo htmlentities($hostgroup, ENT_COMPAT, 'UTF-8'); ?>">
            <input type="hidden" name="servicegroup" value="<?php echo htmlentities($servicegroup, ENT_COMPAT, 'UTF-8'); ?>">

            <div class="reportexportlinks">
                <?php echo get_add_myreport_html("Alert Timeline", $_SERVER["REQUEST_URI"], array()); ?>
            </div>
            <div class="reportsearchbox">
                <?php
                // search box
                $searchclass = "textfield";
                if (have_value($search) == true) {
                    $searchstring = $search;
                    $searchclass .= " newdata";
                } else
                    $searchstring = _("Search...");
                ?>

                <input type="text" size="15" name="search" id="searchBox" value="<?php echo encode_form_val($searchstring); ?>" class="<?php echo $searchclass; ?> form-control"/>
            </div>

            <div class="reporttimepicker">
                <div class="period">
                    <?php echo _("Period"); ?>&nbsp;
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
                    <input class="textfield form-control" type="text" id='startdateBox' name="startdate" value="<?php echo encode_form_val($startdate); ?>" size="16"><div id="startdatepickercontainer"></div>
                    <div class="reportstartdatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                    <?php echo _("To"); ?>
                    <input class="textfield form-control" type="text" id='enddateBox' name="enddate" value="<?php echo encode_form_val($enddate); ?>" size="16"><div id="enddatepickercontainer"></div>
                    <div class="reportenddatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                </div>
                
                <button type="submit" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>
            </div>
            <div style="clear: both;"></div>

        </div>

    </div>
</form>

    <h1 style="padding-bottom: 10px;"><?php echo _("Alert Timeline"); ?></h1>

    <?php
    if ($service != "") {
        ?>
        <div class="servicestatusdetailheader">
            <div class="serviceimage">
                <!--image-->
                <?php show_object_icon($host, $service, true); ?>
            </div>
            <div class="servicetitle">
                <div class="servicename"><a
                        href="<?php echo get_service_status_detail_link($host, $service); ?>"><?php echo htmlentities($service); ?></a>
                </div>
                <div class="hostname"><a
                        href="<?php echo get_host_status_detail_link($host); ?>"><?php echo htmlentities($host, ENT_COMPAT, 'UTF-8'); ?></a>
                </div>
            </div>
        </div>
        <br clear="all">

    <?php
    } else if ($host != "") {
        ?>
        <div class="hoststatusdetailheader">
            <div class="hostimage">
                <!--image-->
                <?php show_object_icon($host, "", true); ?>
            </div>
            <div class="hosttitle">
                <div class="hostname"><a
                        href="<?php echo get_host_status_detail_link($host); ?>"><?php echo htmlentities($host, ENT_COMPAT, 'UTF-8'); ?></a>
                </div>
            </div>
        </div>
        <br clear="all">
    <?php
    }
    ?>

    <div style="clear: left;">
        <?php echo _("From"); ?>:
        <b><?php echo get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b> <?php echo _("to"); ?>
         <b><?php echo get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b>
    </div>


    <?php
    if ($search != "")
        echo "<p>" . _('Showing results for') . " '<b><i>" . htmlentities($search, ENT_COMPAT, 'UTF-8') . "</i></b>'</p>";
    ?>


    <?php
    $ajaxurl = get_base_url() . "includes/components/similetimeline/getdata.php";
    $ajaxurl .= "?1";
    foreach ($request as $var => $val)
        $ajaxurl .= "&" . urlencode($var) . "=" . urlencode($val);
    $ajaxurl .= "&type=" . urlencode($datatype);
    ?>

    <script type="text/javascript">
        $(document).ready(function () {

            onLoad();

            $(window).resize(function () {
                onResize();
            });

        });
    </script>

    <?php
    // "Jun 28 2006 00:00:00 GMT"
    $timeline_starttime = $starttime;
    $startdate = date("M j Y G:i:s T", $timeline_starttime);
    ?>

    <div id="my-timeline" style="margin-top: 10px; border: 1px solid #aaa">
        <img src="<?php echo theme_image("throbber1.gif"); ?>">
        <b><?php echo _("Generating data"); ?>...</b>
    </div>

    <?php
    $yearinterval = 250;
    if ($datatype == "nagios") {
        $yearinterval = 100;
    }
    ?>

    <script type="text/javascript">
        var tl;
        function onLoad() {

            var h = $(window).height() - $('.report-options').outerHeight() - 110;
            $('#my-timeline').css('height', h+'px');

            var eventSource = new Timeline.DefaultEventSource();

            <?php
                if($datatype=="nagios"){
            ?>
            var bandInfos = [
                Timeline.createBandInfo({
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "80%",
                    intervalUnit: Timeline.DateTime.DAY,
                    intervalPixels: 200
                }),
                Timeline.createBandInfo({
                    showEventText: false, // causes problems if enabled
                    //trackHeight:    0.35,
                    //trackGap:       0.4,
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "10%",
                    intervalUnit: Timeline.DateTime.MONTH,
                    intervalPixels: 200
                }),
                Timeline.createBandInfo({
                    showEventText: false,
                    trackHeight: 0.3,
                    trackGap: 0.6,
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "10%",
                    intervalUnit: Timeline.DateTime.YEAR,
                    intervalPixels: <?php echo $yearinterval;?>
                })
            ];

            bandInfos[1].syncWith = 0;
            bandInfos[1].highlight = true;
            bandInfos[2].syncWith = 1;
            bandInfos[2].highlight = true;
            <?php
                    }
                else{
            ?>
            var bandInfos = [
                Timeline.createBandInfo({
                    //showEventText:  true,
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "70%",
                    intervalUnit: Timeline.DateTime.HOUR,
                    intervalPixels: 150
                }),
                Timeline.createBandInfo({
                    showEventText: false,
                    trackHeight: 0.5,
                    trackGap: 0.2,
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "10%",
                    intervalUnit: Timeline.DateTime.DAY,
                    intervalPixels: 200
                }),
                Timeline.createBandInfo({
                    showEventText: false, // causes problems if enabled
                    trackHeight: 0.35,
                    trackGap: 0.4,
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "10%",
                    intervalUnit: Timeline.DateTime.MONTH,
                    intervalPixels: 200
                }),
                Timeline.createBandInfo({
                    showEventText: false,
                    trackHeight: 0.3,
                    trackGap: 0.6,
                    eventSource: eventSource,
                    date: "<?php echo date("c", $timeline_starttime);?>",
                    width: "10%",
                    intervalUnit: Timeline.DateTime.YEAR,
                    intervalPixels: <?php echo $yearinterval;?>
                })
            ];

            bandInfos[1].syncWith = 0;
            bandInfos[1].highlight = true;
            bandInfos[2].syncWith = 1;
            bandInfos[2].highlight = true;
            bandInfos[3].syncWith = 2;
            bandInfos[3].highlight = true;
            <?php
                }
            ?>

            tl = Timeline.create(document.getElementById("my-timeline"), bandInfos);

            Timeline.loadJSON("<?php echo $ajaxurl;?>", function (json, url) {
                eventSource.loadJSON(json, url);
            });

        }

        var resizeTimerID = null;
        function onResize() {
            if (resizeTimerID == null) {
                resizeTimerID = window.setTimeout(function () {
                    resizeTimerID = null;
                    var h = $(window).height() - $('.report-options').outerHeight() - 110;
                    $('#my-timeline').css('height', h+'px');
                    tl.layout();
                }, 200);
            }
        }
    </script>

    <?php

    // closes the HTML page
    do_page_end(true);
}
