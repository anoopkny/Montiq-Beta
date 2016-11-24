<?php
//
// Availability Report
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

ini_set('display_errors', 'off'); //graphs will not generate if error messaging turned on

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

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
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");

    // Check for proper permissions
    $auth = true;
    if ($service != "")
        $auth = is_authorized_for_service(0, $host, $service);
    else if ($host != "")
        $auth = is_authorized_for_host(0, $host);
    else if ($hostgroup != "")
        $auth = is_authorized_for_hostgroup(0, $hostgroup);
    else if ($servicegroup != "")
        $auth = is_authorized_for_servicegroup(0, $servicegroup);
    if ($auth == false) {
        echo _("ERROR: You are not authorized to view this report.");
        exit;
    }

    $mode = grab_request_var("mode", "");
    switch ($mode) {
        case "csv":
            get_availability_csv();
            break;
        case "pdf":
            export_report('availability', EXPORT_PDF);
            break;
        case "jpg":
            export_report('availability', EXPORT_JPG);
            break;
        case "getchart":
            get_chart_image();
            break;
        case "getservices":
            $host = grab_request_var("host", "");
            $args = array('brevity' => 1, 'host_name' => $host, 'orderby' => 'service_description:a');
            $oxml = get_xml_service_objects($args);
            echo '<option value="">['._("All Services").']</option>';
            if ($oxml) {
                foreach ($oxml->service as $serviceobj) {
                    $name = strval($serviceobj->service_description);
                    echo "<option value='" . $name . "' " . is_selected($service, $name) . ">$name</option>\n";
                }
            }
            break;
        case "getreport":
            run_availability_report();
            break;         
        default:
            display_availability();
            break;
    }
}


///////////////////////////////////////////////////////////////////
// CHART FUNCTIONS
///////////////////////////////////////////////////////////////////


function get_chart_image()
{

    $width = grab_request_var("width", 400);
    $height = grab_request_var("height", 300);
    $title = grab_request_var("title", "");
    $rawdata = grab_request_var("data", "");

    // If no Raw Data
    if ($rawdata == "") {
        $data = array();
    } else {
        $rawdata = explode(",", $rawdata);
        $data = $rawdata;
    }

    $json = array('data' => $data,
        'graph_title' => $title,
        'width' => $width,
        'height' => $height);

    print json_encode($json);
    die();
}

///////////////////////////////////////////////////////////////////
// BACKEND DATA FUNCTIONS
///////////////////////////////////////////////////////////////////

function get_availability_data($type = "host", $args, &$data)
{

    $data = get_xml_availability($type, $args);

    return true;
}

///////////////////////////////////////////////////////////////////
// REPORT GENERATION FUCNTIONS
///////////////////////////////////////////////////////////////////

// This function displays event log data in HTML
function display_availability()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");

    // Fix custom dates
    if ($reportperiod == "custom") {
        if ($enddate == "") {
            $enddate = strftime("%c", time());
        }
        if ($startdate == "") {
            $startdate = strftime("%c", time() - (60 * 60 * 24));
            $enddate = strftime("%c", time());
        }
    }

    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $export = grab_request_var("export", 0);
    $showonlygraphs = grab_request_var("showonlygraphs", 0);

    // Should we show detail by default?
    $showdetail = 1;
    if ($host == "" && $service == "" && $hostgroup == "" && $servicegroup == "") {
        $showdetail = 0;
    }

    $showdetail = grab_request_var("showdetail", $showdetail);

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // Advanced options
    $timeperiod = grab_request_var("timeperiod", "");
    $assumeinitialstates = grab_request_var("assumeinitialstates", "yes");
    $assumestateretention = grab_request_var("assumestateretention", "yes");
    $assumestatesduringdowntime = grab_request_var("assumestatesduringdowntime", "yes");
    $includesoftstates = grab_request_var("includesoftstates", "no");
    $assumedhoststate = grab_request_var("assumedhoststate", 3);
    $assumedservicestate = grab_request_var("assumedservicestate", 6);
    $advanced = grab_request_var("advanced", 0);
    $display_service_graphs = grab_request_var("servicegraphs", 0);
    $dont_count_downtime = checkbox_binary(grab_request_var("dont_count_downtime", 0));
    $dont_count_warning = checkbox_binary(grab_request_var("dont_count_warning", 0));
    $dont_count_unknown = checkbox_binary(grab_request_var("dont_count_unknown", 0));
    $no_services = checkbox_binary(grab_request_var("no_services", 0));

    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

    // Determine title
    if ($service != "")
        $title = _("Service Availability");
    else if ($host != "")
        $title = _("Host Availability");
    else if ($hostgroup != "")
        $title = _("Hostgroup Availability");
    else if ($servicegroup != "")
        $title = _("Servicegroup Availability");
    else
        $title = _("Availability Summary");

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    // Start the HTML page
    do_page_start(array("page_title" => $title), true);
?>

<script type="text/javascript">
$(document).ready(function () {

    showhidedates();
    verify_graphs_avail();

    // If we should run it right away
    if (!<?php echo $disable_report_auto_run; ?>) {
        run_availability_ajax();
    }

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

    $('#reportperiodDropdown').change(function () {
        showhidedates();
    });

    $('#hostList').searchable({maxMultiMatch: 9999});
    $('#serviceList').searchable({maxMultiMatch: 9999});
    $('#hostgroupList').searchable({maxMultiMatch: 9999});
    $('#servicegroupList').searchable({maxMultiMatch: 9999});
    if ($('#serviceList').is(':visible')) {
        $('.serviceList-sbox').show();
    } else {
        $('.serviceList-sbox').hide();
    }

    $('#hostList').change(function () {
        $('#hostgroupList').val('');
        $('#servicegroupList').val('');

        if ($(this).val() != '') {
            update_service_list();
            $('#serviceList').show();
            $('.serviceList-sbox').show();
        } else {
            $('#serviceList').val('').hide();
            $('.serviceList-sbox').hide();
        }
        verify_graphs_avail();
    });

    $('#servicegroupList').change(function () {
        $('#hostList').val('');
        $('#hostgroupList').val('');
        $('#serviceList').val('').hide();
        $('.serviceList-sbox').hide();
        verify_graphs_avail();
    });

    $('#hostgroupList').change(function () {
        $('#servicegroupList').val('');
        $('#hostList').val('');
        $('#serviceList').val('').hide();
        $('.serviceList-sbox').hide();
        verify_graphs_avail();
    });

    // Add the ability to show the advanced options section
    $('#advanced-options-btn').click(function () {
        if ($('#advanced-options').is(":visible")) {
            $('#advanced-options').hide();
            $('#advanced').val(0);
            $('#advanced-options-btn').html('<?php echo _("Advanced"); ?> <i class="fa fa-chevron-up"></i>');
        } else {
            $('#advanced-options').show();
            $('#advanced').val(1);
            $('#advanced-options-btn').html('<?php echo _("Advanced"); ?> <i class="fa fa-chevron-down"></i>');
        }
    });

    // Actually return the report
    $('#run').click(function() {
        run_availability_ajax();
    });

    // Get the export button link and send user to it
    $('.btn-export').on('mousedown', function(e) {
        var type = $(this).data('type');
        var formvalues = $("form").serialize();
        formvalues += '&mode=getreport';
        var url = "<?php echo get_base_url(); ?>reports/availability.php?" + formvalues + "&mode=" + type;
        if (e.which == 2) {
            window.open(url);
        } else if (e.which == 1) {
            window.location = url;
        }
    });

});

function verify_graphs_avail() {
    var host = $('#hostList').val();
    var hostgroup = $('#hostgroupList').val();
    var servicegroup = $('#servicegroupList').val();

    if (host == '' && hostgroup == '' && servicegroup == '') {
        $('#display-graphs').prop('disabled', true);
    } else {
        $('#display-graphs').prop('disabled', false);
    }
}

var report_sym = 0;
function run_availability_ajax() {
    report_sym = 1;
    setTimeout('show_loading_report()', 500);

    var formvalues = $("form").serialize();
    formvalues += '&mode=getreport';
    var url = 'availability.php?'+formvalues;

    $.get(url, {}, function(data) {
        report_sym = 0;
        hide_throbber();
        $('#report').html(data);
    });
}
</script>

<script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>

<form method="get" data-type="availability">
    <div class="well report-options">
        
        <div>

            <div class="reportexportlinks">
                <?php echo get_add_myreport_html($title, $_SERVER['PHP_SELF'], array()); ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <?php echo _('Download'); ?> <i class="fa fa-caret-down r"></i>
                    </button>
                     <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                        <li class="service-csv"><a class="btn-export" data-type="csv&csvtype=host" title="<?php echo _("Download only host data as CSV"); ?>"><i class="fa fa-file-text-o l"></i> <?php echo _("Host CSV"); ?></a></li>
                        <li class="host-csv"><a class="btn-export" data-type="csv&csvtype=service" title="<?php echo _("Download only service data as CSV"); ?>"><i class="fa fa-file-text-o l"></i> <?php echo _("Service CSV"); ?></a></li>
                        <li><a class="btn-export" data-type="pdf" title="<?php echo _("Download as PDF"); ?>"><i class="fa fa-file-pdf-o l"></i> <?php echo _("PDF"); ?></a></li>
                        <li><a class="btn-export" data-type="jpg" title="<?php echo _("Download as JPG"); ?>"><i class="fa fa-file-image-o l"></i> <?php echo _("JPG"); ?></a></li>
                    </ul>
                </div>
            </div>

            <div class="reporttimepicker">
                <div class="period">
                    <?php echo _("Period"); ?>
                    <select id="reportperiodDropdown" name="reportperiod" class="form-control">
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
                    <input class="textfield form-control" type="text" id='enddateBox' name="enddate" value="<?php echo encode_form_val($enddate); ?>" size="16">
                    <div id="enddatepickercontainer"></div>
                    <div class="reportenddatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                </div>
            </div>

            <div class="reportoptionpicker clear">
                <?php echo _("Limit To"); ?>

                <select name="host" id="hostList" style="width: 150px;" class="form-control">
                    <option value=""><?php echo _("Host"); ?>:</option>
                    <?php
                    $args = array('brevity' => 1, 'orderby' => 'host_name:a');
                    $oxml = get_xml_host_objects($args);
                    if ($oxml) {
                        foreach ($oxml->host as $hostobject) {
                            $name = strval($hostobject->host_name);
                            echo "<option value='" . $name . "' " . is_selected($host, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
                <select name="service" id="serviceList" class="form-control" style="width: 200px; <?php if (empty($service) && empty($host)) { echo 'display: none;'; } ?>">
                    <option value="">[<?php echo _("All Services"); ?>]</option>
                    <?php
                    $args = array('brevity' => 1, 'host_name' => $host, 'orderby' => 'service_description:a');
                    $oxml = get_xml_service_objects($args);
                    if ($oxml) {
                        foreach ($oxml->service as $serviceobj) {
                            $name = strval($serviceobj->service_description);
                            echo "<option value='" . $name . "' " . is_selected($service, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
                <select name="hostgroup" id="hostgroupList" style="width: 150px;" class="form-control">
                    <option value=""><?php echo _("Hostgroup"); ?>:</option>
                    <?php
                    $args = array('orderby' => 'hostgroup_name:a');
                    $oxml = get_xml_hostgroup_objects($args);
                    if ($oxml) {
                        foreach ($oxml->hostgroup as $hg) {
                            $name = strval($hg->hostgroup_name);
                            echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>
                <select name="servicegroup" id="servicegroupList" style="width: 150px;" class="form-control">
                    <option value=""><?php echo _("Servicegroup"); ?>:</option>
                    <?php
                    $args = array('orderby' => 'servicegroup_name:a');
                    $oxml = get_xml_servicegroup_objects($args);
                    if ($oxml) {
                        foreach ($oxml->servicegroup as $sg) {
                            $name = strval($sg->servicegroup_name);
                            echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
                        }
                    }
                    ?>
                </select>

                <a id="advanced-options-btn" class="tt-bind" data-placement="bottom" title="<?php echo _('Toggle advanced options'); ?>"><?php echo _('Advanced'); ?>  <?php if (!$advanced) { echo '<i class="fa fa-chevron-up"></i>'; } else { echo '<i class="fa fa-chevron-down"></i>'; } ?></a>
                <input type="hidden" value="<?php echo intval($advanced); ?>" id="advanced" name="advanced">

                <button type="button" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>
            </div>

            <div id="advanced-options" style="<?php if (!$advanced) { echo 'display: none;'; } ?>">

                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("Assume Initial States"); ?>:
                    <select name="assumeinitialstates" class="form-control condensed">
                        <option value="yes" <?php if ($assumeinitialstates == "yes") { echo "selected"; } ?>><?php echo _("Yes"); ?></option>
                        <option value="no" <?php if ($assumeinitialstates == "no") { echo "selected"; } ?>><?php echo _("No"); ?></option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("Assume State Retention"); ?>:
                    <select name="assumestateretention" class="form-control condensed">
                        <option value="yes" <?php if ($assumestateretention == "yes") { echo "selected"; } ?>><?php echo _("Yes"); ?></option>
                        <option value="no" <?php if ($assumestateretention == "no") { echo "selected"; } ?>><?php echo _("No"); ?></option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("Assume States During Program Downtime"); ?>:
                    <select name="assumestatesduringdowntime" class="form-control condensed">
                        <option value="yes" <?php if ($assumestatesduringdowntime == "yes") { echo "selected"; } ?>><?php echo _("Yes"); ?></option>
                        <option value="no" <?php if ($assumestatesduringdowntime == "no") { echo "selected"; } ?>><?php echo _("No"); ?></option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("Include Soft States"); ?>:
                    <select name="includesoftstates" class="form-control condensed">
                        <option value="no" <?php if ($includesoftstates == "no") { echo "selected"; } ?>><?php echo _("No"); ?></option>
                        <option value="yes" <?php if ($includesoftstates == "yes") { echo "selected"; } ?>><?php echo _("Yes"); ?></option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("First Assumed Host State"); ?>:
                    <select name="assumedhoststate" class="form-control condensed">
                        <option value="0" <?php if ($assumedhoststate == 0) { echo "selected"; } ?>><?php echo _("Unspecified"); ?></option>
                        <option value="-1" <?php if ($assumedhoststate == -1) { echo "selected"; } ?>><?php echo _("Current State"); ?></option>
                        <option value="3" <?php if ($assumedhoststate == 3) { echo "selected"; } ?>><?php echo _("Host Up"); ?></option>
                        <option value="4" <?php if ($assumedhoststate == 4) { echo "selected"; } ?>><?php echo _("Host Down"); ?></option>
                        <option value="5" <?php if ($assumedhoststate == 5) { echo "selected"; } ?>><?php echo _("Host Unreachable"); ?></option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("First Assumed Service State"); ?>:
                    <select name="assumedservicestate" class="form-control condensed">
                        <option value="0" <?php if ($assumedservicestate == 0) { echo "selected"; } ?>><?php echo _("Unspecified"); ?></option>
                        <option value="-1" <?php if ($assumedservicestate == -1) { echo "selected"; } ?>><?php echo _("Current State"); ?></option>
                        <option value="6" <?php if ($assumedservicestate == 6) { echo "selected"; } ?>><?php echo _("Service Ok"); ?></option>
                        <option value="8" <?php if ($assumedservicestate == 8) { echo "selected"; } ?>><?php echo _("Service Warning"); ?></option>
                        <option value="7" <?php if ($assumedservicestate == 7) { echo "selected"; } ?>><?php echo _("Service Unknown"); ?></option>
                        <option value="9" <?php if ($assumedservicestate == 9) { echo "selected"; } ?>><?php echo _("Service Critical"); ?></option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    <?php echo _("Report Time Period"); ?>:
                    <select name="timeperiod" style="width: 150px;" class="form-control condensed">
                        <option value="" <?php if ($timeperiod == "") {
                            echo "selected";
                        } ?>><?php echo _("None"); ?></option>
                        <?php
                        // Get a list of timeperiods
                        $request = array("objecttype_id" => 9);
                        $objects = new SimpleXMLElement(get_objects_xml_output($request, false));
                        foreach ($objects as $object) {
                            $tp = (string)$object->name1;
                            if (!empty($tp)) {
                                echo "<option " . is_selected($timeperiod, $tp) . ">" . $tp . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="floatbox">
                    <div class="checkbox">
                        <label title="<?php echo _('Must select a host/service, hostgroup, or servicegroup selected'); ?>">
                            <input type="checkbox" value="1" id="display-graphs" name="servicegraphs" <?php echo is_checked($display_service_graphs, 1); ?>> <?php echo _("Display service performance graphs"); ?>
                        </label>
                    </div>
                </div>
                
                <div class="floatbox">
                    <div class="checkbox">
                    <label title="<?php echo _('This will count any state during scheduled downtime as OK for the Availability report'); ?>">
                        <input type="checkbox" name="dont_count_downtime" <?php echo is_checked($dont_count_downtime, 1); ?>> <?php echo _("Hide scheduled downtime"); ?>
                    </label>
                    </div>
                </div>
                
                <div class="floatbox">
                    <div class="checkbox">
                        <label title="<?php echo _('This will count any WARNING state as OK for the Availability report'); ?>">
                            <input type="checkbox" name="dont_count_warning" <?php echo is_checked($dont_count_warning, 1); ?>> <?php echo _("Hide WARNING states"); ?>
                        </label>
                    </div>
                </div>
                
                <div class="floatbox">
                    <div class="checkbox">
                        <label title="<?php echo _('This will count any UNKNOWN state as OK for the Availability report'); ?>">
                            <input type="checkbox" name="dont_count_unknown" <?php echo is_checked($dont_count_unknown, 1); ?>> <?php echo _("Hide UNKNOWN/UNREACHABLE states"); ?>
                        </label>
                    </div>
                </div>

                <div class="floatbox">
                    <div class="checkbox">
                        <label title="<?php echo _('Show only the hosts with no service data shown'); ?>">
                            <input type="checkbox" name="no_services" <?php echo is_checked($no_services, 1); ?>> <?php echo _("Do not show service data"); ?>
                        </label>
                    </div>
                </div>

                <div style="clear: both;"></div>

            </div>

        </div>
    </div>
</form>

<div id="report"></div>

<?php
}

function run_availability_report()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");

    // Fix custom dates
    if ($reportperiod == "custom") {
        if ($enddate == "") {
            $enddate = strftime("%c", time());
        }
        if ($startdate == "") {
            $startdate = strftime("%c", time() - (60 * 60 * 24));
            $enddate = strftime("%c", time());
        }
    }

    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $export = grab_request_var("export", 0);
    $showonlygraphs = grab_request_var("showonlygraphs", 0);

    // Should we show detail by default?
    $showdetail = 1;
    if ($host == "" && $service == "" && $hostgroup == "" && $servicegroup == "") {
        $showdetail = 0;
    }

    $showdetail = grab_request_var("showdetail", $showdetail);

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // Advanced options
    $timeperiod = grab_request_var("timeperiod", "");
    $assumeinitialstates = grab_request_var("assumeinitialstates", "yes");
    $assumestateretention = grab_request_var("assumestateretention", "yes");
    $assumestatesduringdowntime = grab_request_var("assumestatesduringdowntime", "yes");
    $includesoftstates = grab_request_var("includesoftstates", "no");
    $assumedhoststate = grab_request_var("assumedhoststate", 3);
    $assumedservicestate = grab_request_var("assumedservicestate", 6);
    $advanced = grab_request_var("advanced", 0);
    $display_service_graphs = grab_request_var("servicegraphs", 0);
    $dont_count_downtime = checkbox_binary(grab_request_var("dont_count_downtime", 0));
    $dont_count_warning = checkbox_binary(grab_request_var("dont_count_warning", 0));
    $dont_count_unknown = checkbox_binary(grab_request_var("dont_count_unknown", 0));
    $no_services = checkbox_binary(grab_request_var("no_services", 0));

    // Determine title
    if ($service != "") {
        $title = _("Service Availability");
    } else if ($host != "") {
        $title = _("Host Availability");
    } else if ($hostgroup != "") {
        $title = _("Hostgroup Availability");
    } else if ($servicegroup != "") {
        $title = _("Servicegroup Availability");
    } else {
        $title = _("Availability Summary");
    }

    // LOGO FOR GENERATED PDFS
    if ($export && !$showonlygraphs) {

        do_page_start(array("page_title" => $title), true);

        // Default logo stuff
        $logo = "nagiosxi-logo-small.png";
        $logo_alt = get_product_name();

        // Use custom logo if it exists
        $logosettings_raw = get_option("custom_logo_options");
        if ($logosettings_raw == "") {
            $logosettings = array();
        } else {
            $logosettings = unserialize($logosettings_raw);
        }

        $custom_logo_enabled = grab_array_var($logosettings, "enabled");
        if ($custom_logo_enabled == 1) {
            $logo = grab_array_var($logosettings, "logo", $logo);
            $logo_alt = grab_array_var($logosettings, "logo_alt", $logo_alt);
        }

        ?>

        <div style="padding-bottom: 10px;">
            <div style="float: left; margin-right: 30px;">
                <img src="<?php echo get_base_url(); ?>images/<?php echo $logo; ?>" border="0"
                     alt="<?php echo $logo_alt; ?>" title="<?php echo $logo_alt; ?>">
            </div>
            <div style="float: left; height: 44px;">
                <div style="font-weight: bold; font-size: 22px; padding-bottom: 4px;"><?php echo $title; ?></div>
                <div><?php echo _("Report covers from"); ?>:
                    <strong><?php echo get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></strong> <?php echo _("to"); ?>
                    <strong><?php echo get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></strong>
                </div>
            </div>
            <div style="clear:both;"></div>
        </div>

    <?php
    } else if (!$showonlygraphs) {

        $state_history_link = '';
        if ($service != "") {
            $url = "statehistory.php?host=" . urlencode($host) . "&service=" . urlencode($service) . "&reportperiod=" . urlencode($reportperiod) . "&startdate=" . urlencode($startdate) . "&enddate=" . urlencode($enddate);
            $state_history_link = "<a href='" . $url . "' style='margin-left: 4px;'><img src='" . theme_image("history2.png") . "' alt='" . _("View State History") . "' title='" . _("View State History") . "'></a>";
        } else if ($host != "") {
            $url = "statehistory.php?host=" . urlencode($host) . "&reportperiod=" . urlencode($reportperiod) . "&startdate=" . urlencode($startdate) . "&enddate=" . urlencode($enddate);
            $state_history_link = "<a href='" . $url . "' style='margin-left: 4px;'><img src='" . theme_image("history2.png") . "' alt='" . _("View State History") . "' title='" . _("View State History") . "'></a>";
        }
    ?>

    <h1><?php echo $title; ?> <?php echo $state_history_link; ?></h1>

    <?php
    }

    if (!$showonlygraphs) {
        if ($service != "") {
            ?>
            <div class="servicestatusdetailheader">
                <div class="serviceimage">
                    <!--image-->
                    <?php show_object_icon($host, $service, true); ?>
                </div>
                <div class="servicetitle">
                    <div class="servicename">
                        <a href="<?php echo get_service_status_detail_link($host, $service); ?>"><?php echo htmlentities($service); ?></a>
                        <?php echo get_service_alias($host, $service); ?>
                    </div>
                    <div class="hostname">
                        <a href="<?php echo get_host_status_detail_link($host); ?>"><?php echo htmlentities($host); ?></a>
                        <?php echo get_host_alias($host); ?>
                    </div>
                </div>
            </div>
            <br clear="all">

        <?php
        } else if ($host != "") {
            ?>
            <div class="hoststatusdetailheader">
                <div class="hostimage">
                    <?php show_object_icon($host, "", true); ?>
                </div>
                <div class="hosttitle">
                    <div class="hostname">
                        <a href="<?php echo get_host_status_detail_link($host); ?>"><?php echo htmlentities($host); ?></a>
                        <?php echo get_host_alias($host); ?>
                    </div>
                </div>
            </div>
            <br clear="all">
        <?php
        } else if ($hostgroup != "") {
            ?>
            <div class="hoststatusdetailheader">
                <div class="hosttitle">
                    <div class="hostname"><?php echo htmlentities($hostgroup) . get_hostgroup_alias($hostgroup); ?></div>
                </div>
            </div>
        <?php
        } else if ($servicegroup != "") {
            ?>
            <div class="hoststatusdetailheader">
                <div class="hosttitle">
                    <div class="hostname"><?php echo htmlentities($servicegroup) . get_servicegroup_alias($servicegroup); ?></div>
                </div>
            </div>
        <?php
        }
    }

    if (!$export && !$showonlygraphs) {
    ?>

        <div class="report-covers">
            <?php echo _("Report covers from"); ?>:
            <b><?php echo get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b> <?php echo _("to"); ?>
            <b><?php echo get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b>
        </div>

    <?php } ?>

    <div id='availabilityreport' class="availabilityreport">
    <?php

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC SERVICE
    ///////////////////////////////////////////////////////////////////////////
    if ($service != "") {

        // Get service availability
        $args = array(
            "host" => $host,
            "service" => $service,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        get_availability_data("service", $args, $servicedata);

        // Check if we have data
        $have_data = false;
        if ($servicedata && intval($servicedata->havedata) == 1)
            $have_data = true;
        if ($have_data == false) {
            echo "<p>" . _("Availability data is not available when monitoring engine is not running") . "</p>";
        } // we have data..
        else {

            $service_ok = 0;
            $service_warning = 0;
            $service_unknown = 0;
            $service_critical = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }
                    
                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }
                    
                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }
                }
            }

            // Service chart
            $service_availability = _("Service Availability");
            $service_availability_sub = $host . ": " . $service;
            // $url = "availability.php?mode=getchart&title=" . urlencode($service_availability) . "&data=" . $service_ok . "," . $service_warning . "," . $service_unknown . "," . $service_critical . "&legend=Ok,Warning,Unknown,Critical&colors=" . get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical");

            $dargs = array(
                DASHLET_ARGS => array(
                    'dashtype' => 'servicedata',
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'timeperiod' => $timeperiod,
                    'title' => $service_availability,
                    'subtitle' => $service_availability_sub,
                    'data' => "{$service_ok},{$service_warning},{$service_unknown},{$service_critical}",
                    'legend' => "Ok,Warning,Unknown,Critical",
                    'colors' => get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical")
                ),
            );

            display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);

            // Service table
            if ($servicedata && !$showonlygraphs) {
                echo '<div class="availability-services">
                        <div style="float: left; padding-right: 20px;">';
                echo "<h5>" . _("Service Data") . "</h5>";
                echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";
                echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("Service") . "</th><th>" . _("Ok") . "</th><th>" . _("Warning") . "</th><th>" . _("Unknown") . "</th><th>" . _("Critical") . "</th></tr></thead>";
                echo "<tbody>";
                $lasthost = "";
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                        $wa = floatval($s->percent_known_time_warning);
                        $un = floatval($s->percent_known_time_unknown);
                        $cr = floatval($s->percent_known_time_critical);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                        $wa = floatval($s->percent_known_time_warning_unscheduled);
                        $un = floatval($s->percent_known_time_unknown_unscheduled);
                        $cr = floatval($s->percent_known_time_critical_unscheduled);
                    }
                    
                    if ($dont_count_warning) {
                        $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                        $wa = floatval(0);
                    }
                    
                    if ($dont_count_unknown) {
                        $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                        $un = floatval(0);
                    }

                    // Newline
                    if ($lasthost != $hn && $lasthost != "") {
                        echo "<tr><td colspan='6'></td></tr>";
                    }

                    echo "<tr>";
                    if ($lasthost != $hn)
                        echo "<td>" . $hn . "</td>";
                    else
                        echo "<td></td>";
                    echo "<td>" . $sd . "</td>";
                    echo "<td>" . $ok . "%</td>";
                    echo "<td>" . $wa . "%</td>";
                    echo "<td>" . $un . "%</td>";
                    echo "<td>" . $cr . "%</td>";
                    echo "</tr>";

                    $lasthost = $hn;
                }

                echo "</tbody>";
                echo "</table>
                </div>";

                // Loop through each service and display a perfdata graph
                if ($display_service_graphs && !$showonlygraphs) {
                    echo '<div style="float: left;">';
                    foreach ($servicedata->serviceavailability->service as $s) {

                        $host = $s->host_name;
                        $service = $s->service_description;

                        // If rendering as pdf
                        if ($export)
                            $mode = "pdf";
                        else
                            $mode = "";

                        if (pnp_chart_exists($host, $service)) {
                            echo "<div class='serviceperfgraphcontainer'>";
                            $dargs = array(
                                DASHLET_ADDTODASHBOARDTITLE => _("Add This Performance Graph To A Dashboard"),
                                DASHLET_ARGS => array(
                                    "host_id" => get_host_id($host),
                                    "hostname" => $host,
                                    "servicename" => $service,
                                    "startdate" => date("Y-m-d H:i", $starttime),
                                    "enddate" => date("Y-m-d H:i", $endtime),
                                    "width" => "",
                                    "height" => "",
                                    "mode" => PERFGRAPH_MODE_SERVICEDETAIL,
                                    "render_mode" => $mode),
                                DASHLET_TITLE => $host . " " . $service . " " . _("Performance Graph"));

                            display_dashlet("xicore_perfdata_chart", "", $dargs, DASHLET_MODE_OUTBOARD);
                            echo "</div>";
                        }
                    }
                    echo '</div>';
                }
                // End: Display Service Graphs

                echo '</div>';
            }

        }

    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC HOST
    ///////////////////////////////////////////////////////////////////////////
    else if ($host != "") {

        // Get host availability
        $args = array(
            "host" => $host,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        get_availability_data("host", $args, $hostdata);

        // getservice availability
        $args = array(
            "host" => $host,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        get_availability_data("service", $args, $servicedata);

        // Check if we have data
        $have_data = false;
        if ($hostdata && $servicedata && intval($hostdata->havedata) == 1 && intval($servicedata->havedata) == 1)
            $have_data = true;
        if ($have_data == false) {
            echo "<p>" . _("Availability data is not available when monitoring engine is not running") . ".</p>";
        } // we have data..
        else {


            $host_up = 0;
            $host_down = 0;
            $host_unreachable = 0;

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {
                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($host_up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $host_up);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }
                    
                    if ($dont_count_unknown) {
                        $host_up = (($host_up += floatval($h->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $host_up);
                        $host_unreachable = floatval(0);
                    }
                    
                }
            }

            // Host chart
            $host_availability = _("Host Availability");
            $host_availability_sub = $host;
            // $url = "availability.php?mode=getchart&title=" . urlencode($host_availability) . "&data=" . $host_up . "," . $host_down . "," . $host_unreachable . "&legend=Up,Down,Unreachable&colors=" . get_avail_color("up") . "," . get_avail_color("down") . "," . get_avail_color("unreachable");

            $dargs = array(
                DASHLET_ARGS => array(
                    'dashtype' => 'hostdata',
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'timeperiod' => $timeperiod,
                    'title' => $host_availability,
                    'subtitle' => $host_availability_sub,
                    'data' => "{$host_up},{$host_down},{$host_unreachable}",
                    'legend' => "Up,Down,Unreachable",
                    'colors' => get_avail_color("up") . "," . get_avail_color("down") . "," . get_avail_color("unreachable")
                )
            );

            display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }
                    
                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }
                    
                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            if (!$no_services) {

                $average_service_availability = _("Average Service Availability");
                $average_service_availability_sub = $host . _(": All Services");
                // Service chart
                // $url = "availability.php?mode=getchart&title=Average+Service+Availability&data=" . $avg_service_ok . "," . $avg_service_warning . "," . $avg_service_unknown . "," . $avg_service_critical . "&legend=Ok,Warning,Unknown,Critical&colors=" . get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical");

                $dargs = array(
                    DASHLET_ARGS => array(
                        'dashtype' => 'servicedata',
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'timeperiod' => $timeperiod,
                        'title' => $average_service_availability,
                        'subtitle' => $average_service_availability_sub,
                        'data' => "{$avg_service_ok},{$avg_service_warning},{$avg_service_unknown},{$avg_service_critical}",
                        'legend' => "Ok,Warning,Unknown,Critical",
                        'colors' => get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical")
                    ),
                );

                // only show service chart if there are services (some percent exists)
                if (($avg_service_ok + $avg_service_warning + $avg_service_unknown + $avg_service_critical) > 0) {
                    display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);
                }

            }

            // Host table
            if ($hostdata && !$showonlygraphs) {
                echo "<h5>" . _("Host Data") . "</h5>";
                echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";
                echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("UP") . "</th><th>" . _("Down") . "</th><th>" . _("Unreachable") . "</th></tr></thead>";
                echo "<tbody>";
                foreach ($hostdata->hostavailability->host as $h) {
                    $hn = strval($h->host_name);

                    if (!$dont_count_downtime) {
                        $up = floatval($h->percent_known_time_up);
                        $dn = floatval($h->percent_known_time_down);
                        $un = floatval($h->percent_known_time_unreachable);
                    } else {
                        $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                        $dn = floatval($h->percent_known_time_down_unscheduled);
                        $un = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $up = (($up += floatval($h->percent_known_time_unreachable_unscheduled)) > 100 ? 100 : $up);
                        $un = floatval(0);
                    }

                    echo "<tr>";
                    echo "<td>" . $hn . "</td>";
                    echo "<td>" . $up . "%</td>";
                    echo "<td>" . $dn . "%</td>";
                    echo "<td>" . $un . "%</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
            }

            if ($servicedata && !$showonlygraphs && !$no_services) {
                echo '<div class="availability-services">
                        <div style="float: left; padding-right: 20px;">';

                echo "<h5>" . _("Service Data") . "</h5>";
                echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";
                echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("Service") . "</th><th>" . _("Ok") . "</th><th>" . _("Warning") . "</th><th>" . _("Unknown") . "</th><th>" . _("Critical") . "</th></tr></thead>";

                echo "<tbody>";
                $lasthost = "";
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                        $wa = floatval($s->percent_known_time_warning);
                        $un = floatval($s->percent_known_time_unknown);
                        $cr = floatval($s->percent_known_time_critical);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                        $wa = floatval($s->percent_known_time_warning_unscheduled);
                        $un = floatval($s->percent_known_time_unknown_unscheduled);
                        $cr = floatval($s->percent_known_time_critical_unscheduled);
                    }
                    
                    if ($dont_count_warning) {
                        $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                        $wa = floatval(0);
                    }
                    
                    if ($dont_count_unknown) {
                        $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                        $un = floatval(0);
                    }

                    // newline
                    if ($lasthost != $hn && $lasthost != "") {
                        echo "<tr><td colspan='6'></td></tr>";
                    }

                    echo "<tr>";
                    if ($lasthost != $hn)
                        echo "<td>" . $hn . "</td>";
                    else
                        echo "<td></td>";
                    echo "<td>" . $sd . "</td>";
                    echo "<td>" . $ok . "%</td>";
                    echo "<td>" . $wa . "%</td>";
                    echo "<td>" . $un . "%</td>";
                    echo "<td>" . $cr . "%</td>";
                    echo "</tr>";

                    $lasthost = $hn;
                }


                echo "</tbody><tfoot>";
                echo "<tr><td></td><td><b>" . _("Average") . "</b></td><td>" . number_format($avg_service_ok, 3) . "%</td><td>" . number_format($avg_service_warning, 3) . "%</td><td>" . number_format($avg_service_unknown, 3) . "%</td><td>" . number_format($avg_service_critical, 3) . "%</td></tr>";
                echo "</foot>";
                echo "</table>";
                echo '</div>';

                // Loop through each service and display a perfdata graph
                if ($display_service_graphs && !$showonlygraphs) {
                    echo '<div style="float: left;">';
                    foreach ($servicedata->serviceavailability->service as $s) {

                        $host = $s->host_name;
                        $service = $s->service_description;

                        // If rendering as pdf
                        if ($export)
                            $mode = "pdf";
                        else
                            $mode = "";

                        if (pnp_chart_exists($host, $service)) {
                            echo "<div class='serviceperfgraphcontainer'>";
                            $dargs = array(
                                DASHLET_ADDTODASHBOARDTITLE => _("Add This Performance Graph To A Dashboard"),
                                DASHLET_ARGS => array(
                                    "host_id" => get_host_id($host),
                                    "hostname" => $host,
                                    "servicename" => $service,
                                    "startdate" => date("Y-m-d H:i", $starttime),
                                    "enddate" => date("Y-m-d H:i", $endtime),
                                    "width" => "",
                                    "height" => "",
                                    "mode" => PERFGRAPH_MODE_SERVICEDETAIL,
                                    "render_mode" => $mode),
                                DASHLET_TITLE => $host . " " . $service . " " . _("Performance Graph"));

                            display_dashlet("xicore_perfdata_chart", "", $dargs, DASHLET_MODE_OUTBOARD);
                            echo "</div>";
                        }
                    }
                    echo '</div>';
                }
                // End: Display Service Graphs
            }

            echo '</div>';
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC HOSTGROUP OR SERVICEGROUP
    ///////////////////////////////////////////////////////////////////////////
    else if ($hostgroup != "" || $servicegroup != "") {

        //echo "STARTTIME2: $starttime<BR>";
        //echo "ENDTIME2: $endtime<BR>";

        // get host availability
        $args = array(
            "host" => "",
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        if ($hostgroup != "")
            $args["hostgroup"] = $hostgroup;
        else
            $args["servicegroup"] = $servicegroup;
        get_availability_data("host", $args, $hostdata);

        // getservice availability
        $args = array(
            "host" => "",
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        if ($hostgroup != "")
            $args["hostgroup"] = $hostgroup;
        else
            $args["servicegroup"] = $servicegroup;
        get_availability_data("service", $args, $servicedata);

        // check if we have data
        $have_data = false;
        if ($hostdata && $servicedata && intval($hostdata->havedata) == 1 && intval($servicedata->havedata) == 1)
            $have_data = true;
        if ($have_data == false) {
            echo "<p>" . _("Availability data is not available when monitoring engine is not running") . ".</p>";
        }
        else {

            $avg_host_up = 0;
            $avg_host_down = 0;
            $avg_host_unreachable = 0;
            $count_host_up = 0;
            $count_host_down = 0;
            $count_host_unreachable = 0;

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {
                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($host_up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $host_up);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }
                    
                    if ($dont_count_unknown) {
                        $host_up = (($host_up += floatval($h->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $host_up);
                        $host_unreachable = floatval(0);
                    }

                    update_avail_avg($avg_host_up, $host_up, $count_host_up);
                    update_avail_avg($avg_host_down, $host_down, $count_host_down);
                    update_avail_avg($avg_host_unreachable, $host_unreachable, $count_host_unreachable);
                }
            }

            // Host chart
            $title = _('Average Host Availability');

            if ($hostgroup != "")
                $targetgroup = $hostgroup;
            else
                $targetgroup = $servicegroup;

            $subtitle = $targetgroup . _(': All Hosts');
            $up = _('Up');
            $down = _('Down');
            $unreachable = _('Unreachable');
            // $url = "availability.php?mode=getchart&title=$title&data=" . $avg_host_up . "," . $avg_host_down . "," . $avg_host_unreachable . "&legend=$up,$down,$unreachable&colors=" . get_avail_color("up") . "," . get_avail_color("down") . "," . get_avail_color("unreachable");

            $dargs = array(
                DASHLET_ARGS => array(
                    'dashtype' => 'hostdata',
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'timeperiod' => $timeperiod,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'data' => "{$avg_host_up},{$avg_host_down},{$avg_host_unreachable}",
                    'legend' => "{$up},{$down},{$unreachable}",
                    'colors' => get_avail_color("up") . "," . get_avail_color("down") . "," . get_avail_color("unreachable")
                )
            );

            display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }
                    
                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }
                    
                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            if (!$no_services) {

                // Service chart
                $title = _('Average Service Availability');

                if ($hostgroup != "")
                    $targetgroup = $hostgroup;
                else
                    $targetgroup = $servicegroup;

                $subtitle = $targetgroup . _(': All Services');
                $ok = _('Ok');
                $warning = _('Warning');
                $unknown = _('Unknown');
                $critical = _('Critical');
                // $url = "availability.php?mode=getchart&title=$title&data=" . $avg_service_ok . "," . $avg_service_warning . "," . $avg_service_unknown . "," . $avg_service_critical . "&legend=$ok,$warning,$unknown,$critical&colors=" . get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical");
                
                $dargs = array(
                    DASHLET_ARGS => array(
                        'dashtype' => 'servicedata',
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'timeperiod' => $timeperiod,
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'data' => "{$avg_service_ok},{$avg_service_warning},{$avg_service_unknown},{$avg_service_critical}",
                        'legend' => "{$ok},{$warning},{$unknown},{$critical}",
                        'colors' => get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical")
                    ),
                );

                display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);
            
            }

            // Host table
            if ($hostdata && !$showonlygraphs) {
                echo "<h5>" . _('Host Data') . "</h5>";
                echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";
                echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("UP") . "</th><th>" . _("Down") . "</th><th>" . _("Unreachable") . "</th></tr></thead>";
                echo "<tbody>";
                if ($showdetail == 1) {
                    $lasthost = "";
                    foreach ($hostdata->hostavailability->host as $h) {

                        $hn = strval($h->host_name);

                        if (!$dont_count_downtime) {
                            $up = floatval($h->percent_known_time_up);
                            $dn = floatval($h->percent_known_time_down);
                            $un = floatval($h->percent_known_time_unreachable);
                        } else {
                            $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                            $dn = floatval($h->percent_known_time_down_unscheduled);
                            $un = floatval($h->percent_known_time_unreachable_unscheduled);
                        }
                        
                        if ($dont_count_unknown) {
                            $up = (($up += floatval($h->percent_known_time_unreachable_unscheduled)) > 100 ? 100 : $up);
                            $un = floatval(0);
                        }

                        echo "<tr>";
                        echo "<td>" . $hn . "</td>";
                        echo "<td>" . number_format($up, 3) . "%</td>";
                        echo "<td>" . number_format($dn, 3) . "%</td>";
                        echo "<td>" . number_format($un, 3) . "%</td>";
                        echo "</tr>";

                        $lasthost = $hn;
                    }
                }

                echo "</tbody><tfoot>";
                echo "<tr><td><b>" . _("Average") . "</b></td><td>" . number_format($avg_host_up, 3) . "%</td><td>" . number_format($avg_host_down, 3) . "%</td><td>" . number_format($avg_host_unreachable, 3) . "%</td></tr>";
                echo "</tfoot>";
                echo "</table>";
            }

            // Service table
            if ($servicedata && !$showonlygraphs && !$no_services) {
                echo '<div class="availability-services">
                        <div style="float: left; padding-right: 20px;">';

                echo "<h5>" . _('Service Data') . "</h5>";
                echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";

                echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("Service") . "</th><th>" . _("Ok") . "</th><th>" . _("Warning") . "</th><th>" . _("Unknown") . "</th><th>" . _("Critical") . "</th></tr></thead>";
                echo "<tbody>";
                if ($showdetail == 1) {
                    $lasthost = "";
                    foreach ($servicedata->serviceavailability->service as $s) {

                        $hn = strval($s->host_name);
                        $sd = strval($s->service_description);

                        if (!$dont_count_downtime) {
                            $ok = floatval($s->percent_known_time_ok);
                            $wa = floatval($s->percent_known_time_warning);
                            $un = floatval($s->percent_known_time_unknown);
                            $cr = floatval($s->percent_known_time_critical);
                        } else {
                            $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                            $wa = floatval($s->percent_known_time_warning_unscheduled);
                            $un = floatval($s->percent_known_time_unknown_unscheduled);
                            $cr = floatval($s->percent_known_time_critical_unscheduled);
                        }
                        
                        if ($dont_count_warning) {
                            $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                            $wa = floatval(0);
                        }
                        
                        if ($dont_count_unknown) {
                            $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                            $un = floatval(0);
                        }

                        echo "<tr>";
                        if ($lasthost != $hn)
                            echo "<td>" . $hn . "</td>";
                        else
                            echo "<td></td>";
                        echo "<td>" . $sd . "</td>";
                        echo "<td>" . number_format($ok, 3) . "%</td>";
                        echo "<td>" . number_format($wa, 3) . "%</td>";
                        echo "<td>" . number_format($un, 3) . "%</td>";
                        echo "<td>" . $cr . "%</td>";
                        echo "</tr>";

                        $lasthost = $hn;
                    }
                }

                echo "</tbody><tfoot>";
                echo "<tr><td></td><td><b>" . _("Average") . "</b></td><td>" . number_format($avg_service_ok, 3) . "%</td><td>" . number_format($avg_service_warning, 3) . "%</td><td>" . number_format($avg_service_unknown, 3) . "%</td><td>" . number_format($avg_service_critical, 3) . "%</td></tr>";
                echo "</tfoot>";
                echo "</table>";
                echo '</div>';

                // Loop through each service and display a perfdata graph
                if ($display_service_graphs && !$showonlygraphs) {
                    echo '<div style="float: left;">';
                    foreach ($servicedata->serviceavailability->service as $s) {

                        $host = $s->host_name;
                        $service = $s->service_description;

                        // If rendering as pdf
                        if ($export)
                            $mode = "pdf";
                        else
                            $mode = "";

                        if (pnp_chart_exists($host, $service)) {
                            echo "<div class='serviceperfgraphcontainer'>";
                            $dargs = array(
                                DASHLET_ADDTODASHBOARDTITLE => _("Add This Performance Graph To A Dashboard"),
                                DASHLET_ARGS => array(
                                    "host_id" => get_host_id($host),
                                    "hostname" => $host,
                                    "servicename" => $service,
                                    "startdate" => date("Y-m-d H:i", $starttime),
                                    "enddate" => date("Y-m-d H:i", $endtime),
                                    "width" => "",
                                    "height" => "",
                                    "mode" => PERFGRAPH_MODE_SERVICEDETAIL,
                                    "render_mode" => $mode),
                                DASHLET_TITLE => $host . " " . $service . " " . _("Performance Graph"));

                            display_dashlet("xicore_perfdata_chart", "", $dargs, DASHLET_MODE_OUTBOARD);
                            echo "</div>";
                        }
                    }
                    echo '</div>';
                }
                // End: Display Service Graphs

                echo '</div>';
            }

        }

    }


    ///////////////////////////////////////////////////////////////////////////
    // OVERVIEW (ALL HOSTS AND SERVICES)
    ///////////////////////////////////////////////////////////////////////////
    else {

        // get host availability
        $args = array(
            "host" => "all",
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        get_availability_data("host", $args, $hostdata);

        // getservice availability
        $args = array(
            "host" => "all",
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        get_availability_data("service", $args, $servicedata);

        // check if we have data
        $have_data = false;
        if ($hostdata && $servicedata && intval($hostdata->havedata) == 1 && intval($servicedata->havedata) == 1)
            $have_data = true;
        if ($have_data == false) {
            echo "<p>" . _("Availability data is not available when monitoring engine is not running") . ".</p>";
        } // we have data..
        else {

            $avg_host_up = 0;
            $avg_host_down = 0;
            $avg_host_unreachable = 0;
            $count_host_up = 0;
            $count_host_down = 0;
            $count_host_unreachable = 0;

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {
                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($host_up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $host_up);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $host_up = (($host_up += floatval($h->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $host_up);
                        $host_unreachable = floatval(0);
                    }

                    update_avail_avg($avg_host_up, $host_up, $count_host_up);
                    update_avail_avg($avg_host_down, $host_down, $count_host_down);
                    update_avail_avg($avg_host_unreachable, $host_unreachable, $count_host_unreachable);
                }
            }

            // Host chart
            $title = _('Average Host Availability');
            $subtitle = _('All Hosts');
            $up = _('Up');
            $down = _('Down');
            $unreachable = _('Unreachable');
            // $url = "availability.php?mode=getchart&type=host&title=$title&data=" . $avg_host_up . "," . $avg_host_down . "," . $avg_host_unreachable . "&legend=$up,$down,$unreachable&colors=" . get_avail_color("up") . "," . get_avail_color("down") . "," . get_avail_color("unreachable");

            $dargs = array(
                DASHLET_ARGS => array(
                    'dashtype' => 'hostdata',
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                    'timeperiod' => $timeperiod,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'data' => "{$avg_host_up},{$avg_host_down},{$avg_host_unreachable}",
                    'legend' => "{$up},{$down},{$unreachable}",
                    'colors' => get_avail_color("up") . "," . get_avail_color("down") . "," . get_avail_color("unreachable")
                )
            );

            display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            if (!$no_services) {

                // Service chart
                $title = _('Average Service Availability');
                $subtitle = _('All Services');
                $ok = _('Ok');
                $warning = _('Warning');
                $unknown = _('Unknown');
                $critical = _('Critical');
                // $url = "availability.php?mode=getchart&type=service&title=$title&data=" . $avg_service_ok . "," . $avg_service_warning . "," . $avg_service_unknown . "," . $avg_service_critical . "&legend=$ok,$warning,$unknown,$critical&colors=" . get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical");

                $dargs = array(
                    DASHLET_ARGS => array(
                        'dashtype' => 'servicedata',
                        'starttime' => $starttime,
                        'endtime' => $endtime,
                        'timeperiod' => $timeperiod,
                        'title' => $title,
                        'subtitle' => $subtitle,
                        'data' => "{$avg_service_ok},{$avg_service_warning},{$avg_service_unknown},{$avg_service_critical}",
                        'legend' => "{$ok},{$warning},{$unknown},{$critical}",
                        'colors' => get_avail_color("ok") . "," . get_avail_color("warning") . "," . get_avail_color("unknown") . "," . get_avail_color("critical")
                    ),
                );

                display_dashlet("availability", "", $dargs, DASHLET_MODE_OUTBOARD);

            }

            if (!$showonlygraphs) {

                // Host table
                if ($hostdata && !$showonlygraphs) {
                    echo "<h5>" . _('Host Data') . "</h5>";
                    echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";
                    echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("UP") . "</th><th>" . _("Down") . "</th><th>" . _("Unreachable") . "</th></tr></thead>";
                    echo "<tbody>";
                    if ($showdetail == 1) {
                        $lasthost = "";
                        foreach ($hostdata->hostavailability->host as $h) {

                            $hn = strval($h->host_name);

                            if (!$dont_count_downtime) {
                                $up = floatval($h->percent_known_time_up);
                                $dn = floatval($h->percent_known_time_down);
                                $un = floatval($h->percent_known_time_unreachable);
                            } else {
                                $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                                $dn = floatval($h->percent_known_time_down_unscheduled);
                                $un = floatval($h->percent_known_time_unreachable_unscheduled);
                            }

                            if ($dont_count_unknown) {
                                $up = (($up += floatval($h->percent_known_time_unreachable_unscheduled)) > 100 ? 100 : $up);
                                $un = floatval(0);
                            }

                            echo "<tr>";
                            if ($lasthost != $hn)
                                echo "<td>" . $hn . "</td>";
                            else
                                echo "<td></td>";
                            echo "<td>" . $sd . "</td>";
                            echo "<td>" . number_format($ok, 3) . "%</td>";
                            echo "<td>" . number_format($wa, 3) . "%</td>";
                            echo "<td>" . number_format($un, 3) . "%</td>";
                            echo "<td>" . $cr . "%</td>";
                            echo "</tr>";

                            $lasthost = $hn;
                        }
                    }

                    echo "</tbody><tfoot>";
                    echo "<tr><td><b>" . _("Average") . "</b></td><td>" . number_format($avg_host_up, 3) . "%</td><td>" . number_format($avg_host_down, 3) . "%</td><td>" . number_format($avg_host_unreachable, 3) . "</td></tr>";
                    echo "</tfoot>";
                    echo "</table>";
                }

                // Service table
                if ($servicedata && !$showonlygraphs && !$no_services) {
                    echo "<h5>" . _("Service Data") . "</h5>";
                    echo "<table class='table table-condensed table-auto-width table-striped table-bordered'>";

                    echo "<thead><tr><th>" . _("Host") . "</th><th>" . _("Service") . "</th><th>" . _("Ok") . "</th><th>" . _("Warning") . "</th><th>" . _("Unknown") . "</th><th>" . _("Critical") . "</th></tr></thead>";
                    echo "<tbody>";
                    if ($showdetail == 1) {
                        $lasthost = "";
                        foreach ($servicedata->serviceavailability->service as $s) {

                            $hn = strval($s->host_name);
                            $sd = strval($s->service_description);

                            if (!$dont_count_downtime) {
                                $ok = floatval($s->percent_known_time_ok);
                                $wa = floatval($s->percent_known_time_warning);
                                $un = floatval($s->percent_known_time_unknown);
                                $cr = floatval($s->percent_known_time_critical);
                            } else {
                                $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                                $wa = floatval($s->percent_known_time_warning_unscheduled);
                                $un = floatval($s->percent_known_time_unknown_unscheduled);
                                $cr = floatval($s->percent_known_time_critical_unscheduled);
                            }

                            if ($dont_count_warning) {
                                $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                                $wa = floatval(0);
                            }

                            if ($dont_count_unknown) {
                                $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                                $un = floatval(0);
                            }

                            echo "<tr>";
                            if ($lasthost != $hn)
                                echo "<td>" . $hn . "</td>";
                            else
                                echo "<td></td>";
                            echo "<td>" . $sd . "</td>";
                            echo "<td>" . number_format($ok, 3) . "%</td>";
                            echo "<td>" . number_format($wa, 3) . "%</td>";
                            echo "<td>" . number_format($un, 3) . "%</td>";
                            echo "<td>" . $cr . "</td>";
                            echo "</tr>";

                            $lasthost = $hn;
                        }
                    }

                    echo "</tbody><tfoot>";
                    echo "<tr><td></td><td><b>" . _("Average") . "</b></td><td>" . number_format($avg_service_ok, 3) . "%</td><td>" . number_format($avg_service_warning, 3) . "%</td><td>" . number_format($avg_service_unknown, 3) . "%</td><td>" . number_format($avg_service_critical, 3) . "%</td></tr>";
                    echo "</tfoot>";
                    echo "</table>";
                }
            }

        } // end show only graphs
    }
    ?>
    </div>
    <?php
    // closes the HTML page
    if (!$showonlygraphs) {
        do_page_end(true);
    }
}

/**
 * @param $apct
 * @param $npct
 * @param $cnt
 */
function update_avail_avg(&$apct, $npct, &$cnt)
{

    $newpct = (($apct * $cnt) + $npct) / ($cnt + 1);

    $cnt++;

    $apct = $newpct;
}

/**
 * @param $state
 *
 * @return string
 */
function get_avail_color($state)
{
    switch ($state) {
        case "up":
        case "ok":
            $c = "56DA56";
            break;
        case "down":
            $c = "E9513D";
            break;
        case "unreachable":
            $c = "CB2525";
            break;
        case "warning":
            $c = "F6EB3A";
            break;
        case "critical":
            $c = "F35F3D";
            break;
        case "unknown":
            $c = "F3AC3D";
            break;
        default:
            $c = "000000";
            break;
    }
    return "%23" . $c;
}

function get_availability_csv()
{

    // get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");

    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");

    $csvtype = grab_request_var("csvtype", "service");

    // determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // Advanced options
    $timeperiod = grab_request_var("timeperiod", "");
    $assumeinitialstates = grab_request_var("assumeinitialstates", "yes");
    $assumestateretention = grab_request_var("assumestateretention", "yes");
    $assumestatesduringdowntime = grab_request_var("assumestatesduringdowntime", "yes");
    $includesoftstates = grab_request_var("includesoftstates", "no");
    $assumedhoststate = grab_request_var("assumedhoststate", 3);
    $assumedservicestate = grab_request_var("assumedservicestate", 6);
    $advanced = grab_request_var("advanced", 0);
    $display_service_graphs = grab_request_var("servicegraphs", 0);
    $dont_count_downtime = checkbox_binary(grab_request_var("dont_count_downtime", 0));
    $dont_count_warning = checkbox_binary(grab_request_var("dont_count_warning", 0));
    $dont_count_unknown = checkbox_binary(grab_request_var("dont_count_unknown", 0));

    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"availability.csv\"");
    write_csv_header($csvtype);

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC SERVICE
    ///////////////////////////////////////////////////////////////////////////
    if ($service != "") {

        // get service availability
        $args = array(
            "host" => $host,
            "service" => $service,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "timeperiod" => $timeperiod,
            "assume_initial_states" => $assumeinitialstates,
            "assume_state_retention" => $assumestateretention,
            "assume_states_during_not_running" => $assumestatesduringdowntime,
            "include_soft_states" => $includesoftstates,
            "initial_assumed_host_state" => $assumedhoststate,
            "initial_assumed_service_state" => $assumedservicestate
        );
        get_availability_data("service", $args, $servicedata);

        // check if we have data
        $have_data = false;
        if ($servicedata && intval($servicedata->havedata) == 1)
            $have_data = true;
        if ($have_data == false) {
            echo _("Availability data is not available when monitoring engine is not running") . ".\n";
        } // we have data..
        else {

            // service table
            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                        $wa = floatval($s->percent_known_time_warning);
                        $un = floatval($s->percent_known_time_unknown);
                        $cr = floatval($s->percent_known_time_critical);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                        $wa = floatval($s->percent_known_time_warning_unscheduled);
                        $un = floatval($s->percent_known_time_unknown_unscheduled);
                        $cr = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                        $wa = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                        $un = floatval(0);
                    }

                    write_service_csv_data($hn, $sd, $ok, $wa, $un, $cr);
                }
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC HOST
    ///////////////////////////////////////////////////////////////////////////
    else if ($host != "") {

        // get host availability
        if ($csvtype == "host") {
            $args = array(
                "host" => $host,
                "starttime" => $starttime,
                "endtime" => $endtime,
                "timeperiod" => $timeperiod,
                "assume_initial_states" => $assumeinitialstates,
                "assume_state_retention" => $assumestateretention,
                "assume_states_during_not_running" => $assumestatesduringdowntime,
                "include_soft_states" => $includesoftstates,
                "initial_assumed_host_state" => $assumedhoststate,
                "initial_assumed_service_state" => $assumedservicestate
            );
            get_availability_data("host", $args, $hostdata);
        } // getservice availability
        else {
            $args = array(
                "host" => $host,
                "starttime" => $starttime,
                "endtime" => $endtime,
                "timeperiod" => $timeperiod,
                "assume_initial_states" => $assumeinitialstates,
                "assume_state_retention" => $assumestateretention,
                "assume_states_during_not_running" => $assumestatesduringdowntime,
                "include_soft_states" => $includesoftstates,
                "initial_assumed_host_state" => $assumedhoststate,
                "initial_assumed_service_state" => $assumedservicestate
            );
            get_availability_data("service", $args, $servicedata);
        }

        // check if we have data
        $have_data = false;
        if (($csvtype == "host" && $hostdata && intval($hostdata->havedata) == 1) || ($servicedata && intval($servicedata->havedata) == 1))
            $have_data = true;
        if ($have_data == false) {
            echo _("Availability data is not available when monitoring engine is not running") . ".\n";
        } // we have data..
        else {

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            $host_up = 0;
            $host_down = 0;
            $host_unreachable = 0;

            // Host table
            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {

                    $host_name = strval($h->host_name);

                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($host_up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $host_up);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $host_up = (($host_up += floatval($h->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $host_up);
                        $host_unreachable = floatval(0);
                    }

                    write_host_csv_data($host_name, $host_up, $host_down, $host_unreachable);
                }
            }

            // Service table
            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                        $wa = floatval($s->percent_known_time_warning);
                        $un = floatval($s->percent_known_time_unknown);
                        $cr = floatval($s->percent_known_time_critical);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                        $wa = floatval($s->percent_known_time_warning_unscheduled);
                        $un = floatval($s->percent_known_time_unknown_unscheduled);
                        $cr = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                        $wa = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                        $un = floatval(0);
                    }

                    write_service_csv_data($hn, $sd, $ok, $wa, $un, $cr);
                }

                // Averages
                write_service_csv_data("", "AVERAGE", $avg_service_ok, $avg_service_warning, $avg_service_unknown, $avg_service_critical);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // SPECIFIC HOSTGROUP OR SERVICEGROUP
    ///////////////////////////////////////////////////////////////////////////
    else if ($hostgroup != "" || $servicegroup != "") {

        // Get host availability
        if ($csvtype == "host") {
            $args = array(
                "host" => "",
                "starttime" => $starttime,
                "endtime" => $endtime,
                "timeperiod" => $timeperiod,
                "assume_initial_states" => $assumeinitialstates,
                "assume_state_retention" => $assumestateretention,
                "assume_states_during_not_running" => $assumestatesduringdowntime,
                "include_soft_states" => $includesoftstates,
                "initial_assumed_host_state" => $assumedhoststate,
                "initial_assumed_service_state" => $assumedservicestate
            );
            if ($hostgroup != "")
                $args["hostgroup"] = $hostgroup;
            else
                $args["servicegroup"] = $servicegroup;
            get_availability_data("host", $args, $hostdata);
        } // getservice availability
        else {
            $args = array(
                "host" => "",
                "starttime" => $starttime,
                "endtime" => $endtime,
                "timeperiod" => $timeperiod,
                "assume_initial_states" => $assumeinitialstates,
                "assume_state_retention" => $assumestateretention,
                "assume_states_during_not_running" => $assumestatesduringdowntime,
                "include_soft_states" => $includesoftstates,
                "initial_assumed_host_state" => $assumedhoststate,
                "initial_assumed_service_state" => $assumedservicestate
            );
            if ($hostgroup != "")
                $args["hostgroup"] = $hostgroup;
            else
                $args["servicegroup"] = $servicegroup;
            get_availability_data("service", $args, $servicedata);
        }

        // Check if we have data
        $have_data = false;
        if (($csvtype == "host" && $hostdata && intval($hostdata->havedata) == 1) || ($servicedata && intval($servicedata->havedata) == 1))
            $have_data = true;
        if ($have_data == false) {
            echo _("Availability data is not available when monitoring engine is not running") . ".\n";
        } // we have data..
        else {

            $avg_host_up = 0;
            $avg_host_down = 0;
            $avg_host_unreachable = 0;
            $count_host_up = 0;
            $count_host_down = 0;
            $count_host_unreachable = 0;

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {
                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($host_up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $host_up);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $host_up = (($host_up += floatval($h->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $host_up);
                        $host_unreachable = floatval(0);
                    }

                    update_avail_avg($avg_host_up, $host_up, $count_host_up);
                    update_avail_avg($avg_host_down, $host_down, $count_host_down);
                    update_avail_avg($avg_host_unreachable, $host_unreachable, $count_host_unreachable);
                }
            }


            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            // Host table
            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {

                    $hn = strval($h->host_name);

                    if (!$dont_count_downtime) {
                        $up = floatval($h->percent_known_time_up);
                        $dn = floatval($h->percent_known_time_down);
                        $un = floatval($h->percent_known_time_unreachable);
                    } else {
                        $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                        $dn = floatval($h->percent_known_time_down_unscheduled);
                        $un = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $up = (($up += floatval($h->percent_known_time_unreachable_unscheduled)) > 100 ? 100 : $up);
                        $un = floatval(0);
                    }

                    write_host_csv_data($hn, $up, $dn, $un);
                }

                // Averages
                write_host_csv_data("AVERAGE", $avg_host_up, $avg_host_down, $avg_host_unreachable);
            }

            // Service table
            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                        $wa = floatval($s->percent_known_time_warning);
                        $un = floatval($s->percent_known_time_unknown);
                        $cr = floatval($s->percent_known_time_critical);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                        $wa = floatval($s->percent_known_time_warning_unscheduled);
                        $un = floatval($s->percent_known_time_unknown_unscheduled);
                        $cr = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                        $wa = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                        $un = floatval(0);
                    }

                    write_service_csv_data($hn, $sd, $ok, $wa, $un, $cr);
                }

                // Averages
                write_service_csv_data("", "AVERAGE", $avg_service_ok, $avg_service_warning, $avg_service_unknown, $avg_service_critical);
            }
        }

    }


    ///////////////////////////////////////////////////////////////////////////
    // OVERVIEW (ALL HOSTS AND SERVICES)
    ///////////////////////////////////////////////////////////////////////////
    else {

        // Get host availability
        if ($csvtype == "host") {
            $args = array(
                "host" => "all",
                "starttime" => $starttime,
                "endtime" => $endtime,
                "timeperiod" => $timeperiod,
                "assume_initial_states" => $assumeinitialstates,
                "assume_state_retention" => $assumestateretention,
                "assume_states_during_not_running" => $assumestatesduringdowntime,
                "include_soft_states" => $includesoftstates,
                "initial_assumed_host_state" => $assumedhoststate,
                "initial_assumed_service_state" => $assumedservicestate
            );
            get_availability_data("host", $args, $hostdata);
        } // getservice availability
        else {
            $args = array(
                "host" => "all",
                "starttime" => $starttime,
                "endtime" => $endtime,
                "timeperiod" => $timeperiod,
                "assume_initial_states" => $assumeinitialstates,
                "assume_state_retention" => $assumestateretention,
                "assume_states_during_not_running" => $assumestatesduringdowntime,
                "include_soft_states" => $includesoftstates,
                "initial_assumed_host_state" => $assumedhoststate,
                "initial_assumed_service_state" => $assumedservicestate
            );
            get_availability_data("service", $args, $servicedata);
        }

        // Check if we have data
        $have_data = false;
        if (($csvtype == "host" && $hostdata && intval($hostdata->havedata) == 1) || ($servicedata && intval($servicedata->havedata) == 1))
            $have_data = true;
        if ($have_data == false) {
            echo _("Availability data is not available when monitoring engine is not running") . ".\n";
        } // we have data..
        else {

            $avg_host_up = 0;
            $avg_host_down = 0;
            $avg_host_unreachable = 0;
            $count_host_up = 0;
            $count_host_down = 0;
            $count_host_unreachable = 0;

            if ($hostdata) {
                foreach ($hostdata->hostavailability->host as $h) {
                    if (!$dont_count_downtime) {
                        $host_up = floatval($h->percent_known_time_up);
                        $host_down = floatval($h->percent_known_time_down);
                        $host_unreachable = floatval($h->percent_known_time_unreachable);
                    } else {
                        $host_up = (($host_up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled)) > 100 ? 100 : $host_up);
                        $host_down = floatval($h->percent_known_time_down_unscheduled);
                        $host_unreachable = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $host_up = (($host_up += floatval($h->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $host_up);
                        $host_unreachable = floatval(0);
                    }

                    update_avail_avg($avg_host_up, $host_up, $count_host_up);
                    update_avail_avg($avg_host_down, $host_down, $count_host_down);
                    update_avail_avg($avg_host_unreachable, $host_unreachable, $count_host_unreachable);
                }
            }

            $avg_service_ok = 0;
            $avg_service_warning = 0;
            $avg_service_unknown = 0;
            $avg_service_critical = 0;
            $count_service_critical = 0;
            $count_service_warning = 0;
            $count_service_unknown = 0;

            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {
                    if (!$dont_count_downtime) {
                        $service_ok = floatval($s->percent_known_time_ok);
                        $service_warning = floatval($s->percent_known_time_warning);
                        $service_unknown = floatval($s->percent_known_time_unknown);
                        $service_critical = floatval($s->percent_known_time_critical);
                    } else {
                        $service_ok = (($service_ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval($s->percent_known_time_warning_unscheduled);
                        $service_unknown = floatval($s->percent_known_time_unknown_unscheduled);
                        $service_critical = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_warning = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $service_ok = (($service_ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $service_ok);
                        $service_unknown = floatval(0);
                    }

                    update_avail_avg($avg_service_ok, $service_ok, $count_service_ok);
                    update_avail_avg($avg_service_warning, $service_warning, $count_service_warning);
                    update_avail_avg($avg_service_unknown, $service_unknown, $count_service_unknown);
                    update_avail_avg($avg_service_critical, $service_critical, $count_service_critical);
                }
            }

            // Host table
            if ($hostdata) {

                foreach ($hostdata->hostavailability->host as $h) {

                    $hn = strval($h->host_name);

                    if (!$dont_count_downtime) {
                        $up = floatval($h->percent_known_time_up);
                        $dn = floatval($h->percent_known_time_down);
                        $un = floatval($h->percent_known_time_unreachable);
                    } else {
                        $up = floatval($h->percent_known_time_up) + floatval($h->percent_known_time_down_scheduled) + floatval($h->percent_known_time_unreachable_scheduled);
                        $dn = floatval($h->percent_known_time_down_unscheduled);
                        $un = floatval($h->percent_known_time_unreachable_unscheduled);
                    }

                    if ($dont_count_unknown) {
                        $up = (($up += floatval($h->percent_known_time_unreachable_unscheduled)) > 100 ? 100 : $up);
                        $un = floatval(0);
                    }

                    write_host_csv_data($hn, $up, $dn, $un);
                }

                // Averages
                write_host_csv_data("AVERAGE", $avg_host_up, $avg_host_down, $avg_host_unreachable);
            }

            // Service table
            if ($servicedata) {
                foreach ($servicedata->serviceavailability->service as $s) {

                    $hn = strval($s->host_name);
                    $sd = strval($s->service_description);

                    if (!$dont_count_downtime) {
                        $ok = floatval($s->percent_known_time_ok);
                        $wa = floatval($s->percent_known_time_warning);
                        $un = floatval($s->percent_known_time_unknown);
                        $cr = floatval($s->percent_known_time_critical);
                    } else {
                        $ok = (($ok = floatval($s->percent_known_time_ok) + floatval($s->percent_known_time_warning_scheduled) + floatval($s->percent_known_time_critical_scheduled) + floatval($s->percent_known_time_unknown_scheduled)) > 100 ? 100 : $ok);
                        $wa = floatval($s->percent_known_time_warning_unscheduled);
                        $un = floatval($s->percent_known_time_unknown_unscheduled);
                        $cr = floatval($s->percent_known_time_critical_unscheduled);
                    }

                    if ($dont_count_warning) {
                        $ok = (($ok += floatval($s->percent_known_time_warning_unscheduled)) > 100 ? 100 : $ok);
                        $wa = floatval(0);
                    }

                    if ($dont_count_unknown) {
                        $ok = (($ok += floatval($s->percent_known_time_unknown_unscheduled)) > 100 ? 100 : $ok);
                        $un = floatval(0);
                    }

                    write_service_csv_data($hn, $sd, $ok, $wa, $un, $cr);
                }

                // Averages
                write_service_csv_data("", "AVERAGE", $avg_service_ok, $avg_service_warning, $avg_service_unknown, $avg_service_critical);
            }
        }
    }
}


/**
 * @param $csvtype
 */
function write_csv_header($csvtype)
{
    if ($csvtype == "service")
        echo "host,service,ok %,warning %,unknown %,critical %\n";
    else
        echo "host,up %,down %,unreachable %\n";
}

/**
 * @param $hn
 * @param $up
 * @param $dn
 * @param $un
 */
function write_host_csv_data($hn, $up, $dn, $un)
{
    echo "\"$hn\",$up,$dn,$un\n";
}

/**
 * @param $hn
 * @param $sn
 * @param $ok
 * @param $wa
 * @param $un
 * @param $cr
 */
function write_service_csv_data($hn, $sn, $ok, $wa, $un, $cr)
{
    echo "\"$hn\",\"$sn\",$ok,$wa,$un,$cr\n";
}