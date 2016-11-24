<?php
//
// Top Alert Producers Report
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
// 
// $Id$

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

route_request();

function route_request()
{
    $mode = grab_request_var("mode", "");
    switch ($mode) {
        case "csv":
            get_topalertproducers_csv();
            break;
        case "pdf":
            export_report('topalertproducers', EXPORT_PDF, EXPORT_LANDSCAPE);
            break;
        case "jpg":
            export_report('topalertproducers', EXPORT_JPG);
            break;
        case 'getpage':
            get_topalertproducers_page();
            break;
        case "getreport":
            get_topalertproducers_report();
            break;
        default:
            display_topalertproducers();
            break;
    }
}

///////////////////////////////////////////////////////////////////
// BACKEND DATA FUNCTIONS
///////////////////////////////////////////////////////////////////

// Grabs state history data in XML format from the backend
function get_topalertproducers_data($args)
{
    $xml = get_xml_topalertproducers($args);
    return $xml;
}

///////////////////////////////////////////////////////////////////
// REPORT GENERATION FUCNTIONS
///////////////////////////////////////////////////////////////////

// Displays the from/loader HTML
function display_topalertproducers()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 0);
    $records = grab_request_var("records", get_user_meta(0, 'report_defualt_recordlimit', 10));
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $statetype = grab_request_var("statetype", "hard");

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

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

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    do_page_start(array("page_title" => _("Top Alert Producers")), true);
?>

<script type="text/javascript">
$(document).ready(function () {

    showhidedates();

    // If we should run it right away
    if (!<?php echo $disable_report_auto_run; ?>) {
        run_topalertproducers_ajax();
    }

    $('#hostgroupList').searchable({maxMultiMatch: 9999});
    $('#servicegroupList').searchable({maxMultiMatch: 9999});
        
    $('#servicegroupList').change(function () {
        $('#hostgroupList').val('');
    });

    $('#hostgroupList').change(function () {
        $('#servicegroupList').val('');
    });

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

    // Actually return the report
    $('#run').click(function() {
        run_topalertproducers_ajax();
    });

    // Get the export button link and send user to it
    $('.btn-export').on('mousedown', function(e) {
        var type = $(this).data('type');
        var formvalues = $("form").serialize();
        formvalues += '&mode=getreport';
        var url = "<?php echo get_base_url(); ?>reports/topalertproducers.php?" + formvalues + "&mode=" + type;
        if (e.which == 2) {
            window.open(url);
        } else if (e.which == 1) {
            window.location = url;
        }
    });

});

var report_sym = 0;
function run_topalertproducers_ajax() {
    report_sym = 1;
    setTimeout('show_loading_report()', 500);

    var formvalues = $("form").serialize();
    formvalues += '&mode=getreport';
    var url = 'topalertproducers.php?'+formvalues;

    current_page = 1;

    $.get(url, {}, function(data) {
        report_sym = 0;
        hide_throbber();
        $('#report').html(data);
        $('#report .tt-bind').tooltip();
    });
}
</script>

<script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>

<form method="get" data-type="topalertproducers">
    <div class="well report-options">

        <input type="hidden" name="host" value="<?php echo htmlentities($host); ?>">
        <input type="hidden" name="service" value="<?php echo htmlentities($service); ?>">

        <div class="reportexportlinks">
            <?php echo get_add_myreport_html(_("Top Alert Producers"), $_SERVER['PHP_SELF'], array()); ?>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <?php echo _('Download'); ?> <i class="fa fa-caret-down r"></i>
                </button>
                 <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                    <li><a class="btn-export" data-type="csv" title="<?php echo _("Download as CSV"); ?>"><i class="fa fa-file-text-o l"></i> <?php echo _("CSV"); ?></a></li>
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

        <div class="reportoptionpicker">

            <?php echo _("Limit To"); ?>
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

            <span style="margin: 0 10px;">
                <?php echo _("State Types"); ?>
                <select id="statetypeDropdown" class="form-control" name="statetype">
                    <option value="soft" <?php echo is_selected("soft", $statetype); ?>><?php echo _("Soft"); ?></option>
                    <option value="hard" <?php echo is_selected("hard", $statetype); ?>><?php echo _("Hard"); ?></option>
                    <option value="both" <?php echo is_selected("both", $statetype); ?>><?php echo _("Both"); ?></option>
                </select>
            </span>

            <button type="button" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>

        </div>

    </div>
</form>

<div id="report"></div>

<?php
}


function get_topalertproducers_page()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 0);
    $records = grab_request_var("records", 10);
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $statetype = grab_request_var("statetype", "hard");
    $export = grab_request_var("export", 0);

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

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

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    // Limit hosts by hostgroup or host
    $host_ids = array();
    
    // Limit by hostgroup
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup, true);
    }

    // Limit service by servicegroup
    $service_ids = array();
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    $object_ids_str = "";
    $y = 0;

    foreach ($host_ids as $hid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $hid;
        $y++;
    }

    foreach ($service_ids as $sid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $sid;
        $y++;
    }
    
    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // TOTAL RECORD COUNT (FOR PAGING): if you wanted to get the total count of records in a given timeframe (instead of the records themselves), use this:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1
    );

    switch ($statetype) {
        case "soft":
            $args["state_type"] = 0;
            break;
        case "hard":
            $args["state_type"] = 1;
            break;
        default:
            break;
    }

    if ($search) {
        $args["output"] = "lk:" . $search . ";host_name=lk:" . $search .";service_description=lk:". $search;
    }

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
    }

    if ($service != "") {
        $args["service_description"] = $service;
    }

    $xml = get_topalertproducers_data($args);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Determine paging information
    $args = array(
        "reportperiod" => $reportperiod,
        "startdate" => $startdate,
        "enddate" => $enddate,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "search" => $search,
        "host" => $host,
        "service" => $service,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup,
        "statetype" => $statetype
    );
    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);

    // SPECIFIC RECORDS (FOR PAGING): if you want to get specific records, use this type of format:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "records" => $records . ":" . $first_record
    );

    switch ($statetype) {
        case "soft":
            $args["state_type"] = 0;
            break;
        case "hard":
            $args["state_type"] = 1;
            break;
        default:
            break;
    }

    if ($search) {
        $args["output"] = "lk:" . $search . ";host_name=lk:" . $search .";service_description=lk:". $search;
    }
    
    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
    }
    
    if ($service != "") {
        $args["service_description"] = $service;
    }

    $xml = get_topalertproducers_data($args);
?>

<table class="table table-condensed table-hover table-striped table-bordered">
    <thead>
        <tr>
            <th><?php echo _("Total Alerts"); ?></th>
            <th><?php echo _("Host"); ?></th>
            <th><?php echo _("Service"); ?></th>
            <th><?php echo _("Latest Alert"); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    if ($xml) {
        if ($total_records == 0) {
            echo "<tr><td colspan='4'>" . _("No matching results found.  Try expanding your search criteria") . ".</td></tr>\n";
        } else foreach ($xml->producer as $p) {

            $trclass = "";

            $total_alerts = intval($p->total_alerts);
            $host_name = strval($p->host_name);
            $service_description = strval($p->service_description);

            $host_alias = get_host_alias($host_name);
            $service_alias = get_service_alias($host_name, $service_description);

            if ($export) {
                $burl = get_external_url();
            } else {
                $burl = get_base_url();
            }

            $base_url = $burl . "includes/components/xicore/status.php";
            $host_url = $base_url . "?show=hostdetail&host=" . urlencode($host_name);
            $service_url = $base_url . "?show=servicedetail&host=" . urlencode($host_name) . "&service=" . urlencode($service_description);

            $history_url = $burl . "reports/statehistory.php?host=" . urlencode($host_name) . "&service=" . urlencode($service_description) . "&reportperiod=" . urlencode($reportperiod) . "&startdate=" . urlencode($startdate) . "&enddate=" . urlencode($enddate);


            echo "<tr class='" . $trclass . "'>";
            echo "<td><a href='" . $history_url . "'>" . $total_alerts . "</a></td>";
            echo "<td><a href='" . $host_url . "'>" . $host_name . $host_alias . "</a></td>";
            echo "<td><a href='" . $service_url . "'>" . $service_description . $service_alias . "</a></td>";
            echo "<td nowrap>" . $p->last_state_time . "</td>";
            echo "</tr>";
        }
    }
    ?>
    </tbody>
</table>

<?php
}


function get_topalertproducers_report()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 0);
    $records = grab_request_var("records", get_user_meta(0, 'report_defualt_recordlimit', 10));
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $statetype = grab_request_var("statetype", "hard");
    $export = grab_request_var("export", 0);

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

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

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    // Limit hosts by hostgroup or host
    $host_ids = array();
    
    // Limit by hostgroup
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup, true);
    }

    // Limit service by servicegroup
    $service_ids = array();
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    $object_ids_str = "";
    $y = 0;

    foreach ($host_ids as $hid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $hid;
        $y++;
    }

    foreach ($service_ids as $sid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $sid;
        $y++;
    }
    
    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // TOTAL RECORD COUNT (FOR PAGING): if you wanted to get the total count of records in a given timeframe (instead of the records themselves), use this:
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1
    );

    switch ($statetype) {
        case "soft":
            $args["state_type"] = 0;
            break;
        case "hard":
            $args["state_type"] = 1;
            break;
        default:
            break;
    }

    if ($search) {
        $args["output"] = "lk:" . $search . ";host_name=lk:" . $search .";service_description=lk:". $search;
    }

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
    }

    if ($service != "") {
        $args["service_description"] = $service;
    }

    $xml = get_topalertproducers_data($args);
    $total_records = 0;
    if ($xml) {
        $total_records = intval($xml->recordcount);
    }

    // Determine paging information
    $args = array(
        "reportperiod" => $reportperiod,
        "startdate" => $startdate,
        "enddate" => $enddate,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "search" => $search,
        "host" => $host,
        "service" => $service,
        "hostgroup" => $hostgroup,
        "servicegroup" => $servicegroup,
        "statetype" => $statetype
    );
    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);


    $title = _("Top Alert Producers");
    $sub_title = "";
    if ($service != "") {
        $title = _("Service Top Alert Producers");
        $sub_title = "
        <div class='servicestatusdetailheader'>
            <div class='serviceimage'>
                " . show_object_icon($host, $service, true) . "
            </div>
            <div class='servicetitle'>
                <div class='servicename'>
                    <a href='" . get_service_status_detail_link($host, $service) . "'>" . htmlentities($service) . "</a>" . get_service_alias($host, $service) . "
                </div>
                <div class='hostname'>
                    <a href='" . get_host_status_detail_link($host) . "'>" . htmlentities($host) . "</a>" . get_host_alias($host) . "
                </div>
            </div>
        </div>";

    } else if ($host != "") {
        $title = _("Host Top Alert Producers");
        $sub_title = "
            <div class='hoststatusdetailheader'>
                <div class='hostimage'>
                    " . show_object_icon($host, "", true) . "
                </div>
                <div class='hosttitle'>
                    <div class='hostname'>
                        <a href='" . get_host_status_detail_link($host) . "'>" . htmlentities($host) . "</a>" . get_host_alias($host) . "
                    </div>
                </div>
            </div>";
    } else if ($hostgroup != "") {
        $title = _("Hostgroup Top Alert Producers");
        $sub_title = "
            <div class='hoststatusdetailheader'>
                <div class='hosttitle'>
                    <div class='hostname'>" . htmlentities($hostgroup) . get_hostgroup_alias($hostgroup) . "</div>
                </div>
            </div>";

    } else if ($servicegroup != "") {
        $title = _("Servicegroup Top Alert Producers");
        $sub_title = "
            <div class='hoststatusdetailheader'>
                <div class='hosttitle'>
                    <div class='hostname'>" . htmlentities($servicegroup) . get_servicegroup_alias($servicegroup) . "</div>
                </div>
            </div>";
    }
    $report_covers_from = "
                <div>" . _("Report covers from") . ":
                    <strong>" . get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null") . "</strong> " . _("to") . "
                    <strong>" . get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null") . "</strong>
                </div>";



    // If the page is being rendered for PDF
    if ($export) {

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

        <script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>

        <div style="padding-bottom: 10px;">
            <div style="float: left; margin-right: 30px;">
                <img src="<?php echo get_base_url(); ?>images/<?php echo $logo; ?>" border="0"
                     alt="<?php echo $logo_alt; ?>" title="<?php echo $logo_alt; ?>">
            </div>
            <div style="float: left; height: 44px;">
                <div
                    style="font-weight: bold; font-size: 22px; padding-bottom: 4px;"><?php echo $title; ?></div>
                <?php echo $report_covers_from; ?>
            </div>
            <div style="clear:both;"></div>
            <?php echo $sub_title; ?>
        </div>

        <?php } else { ?>
        
        <h1><?php echo $title; ?></h1>
        <?php echo $sub_title; ?>
        <?php echo $report_covers_from; ?>

    <?php } ?>

    <div class="recordcounttext">
        <?php
        $clear_args = array(
            "reportperiod" => $reportperiod,
            "startdate" => $startdate,
            "enddate" => $enddate,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "host" => $host,
            "service" => $service,
            "statetype" => $statetype
        );
        echo table_record_count_text($pager_results, $search, true, $clear_args, '', true);
        ?>
    </div>

    <?php if (!$export){ ?>

    <div class="ajax-pagination">
        <button class="btn btn-xs btn-default first-page" title="<?php echo _('Last Page'); ?>"><i class="fa fa-fast-backward"></i></button>
        <button class="btn btn-xs btn-default previous-page" title="<?php echo _('Previous Page'); ?>" disabled><i class="fa fa-chevron-left l"></i></button>
        <span style="margin: 0 10px;"><?php echo _('Page'); ?> <span class="pagenum">1 <?php echo _('of'); ?> <?php echo $pager_results['total_pages']; ?></span></span>
        <button class="btn btn-xs btn-default next-page" title="<?php echo _('Next Page'); ?>"><i class="fa fa-chevron-right r"></i></button>
        <button class="btn btn-xs btn-default last-page" title="<?php echo _('Last Page'); ?>"><i class="fa fa-fast-forward"></i></button>

        <select class="form-control condensed num-records">
            <option value="5"<?php if ($pager_results['records_per_page'] == 5) { echo ' selected'; } ?>>5 <?php echo _('Per Page'); ?></option>
            <option value="10"<?php if ($pager_results['records_per_page'] == 10) { echo ' selected'; } ?>>10 <?php echo _('Per Page'); ?></option>
            <option value="25"<?php if ($pager_results['records_per_page'] == 25) { echo ' selected'; } ?>>25 <?php echo _('Per Page'); ?></option>
            <option value="50"<?php if ($pager_results['records_per_page'] == 50) { echo ' selected'; } ?>>50 <?php echo _('Per Page'); ?></option>
            <option value="100"<?php if ($pager_results['records_per_page'] == 100) { echo ' selected'; } ?>>100 <?php echo _('Per Page'); ?></option>
        </select>

        <input type="text" class="form-control condensed jump-to">
        <button class="btn btn-xs btn-default tt-bind jump" title="<?php echo _('Jump to Page'); ?>"><i class="fa fa-chevron-circle-right fa-12"></i></button>
    </div>

    <?php } ?>

<script>
var report_url = '<?php echo get_base_url(); ?>reports/topalertproducers.php';
var report_url_args = {
    reportperiod: "<?php echo encode_form_val($reportperiod); ?>",
    startdate: "<?php echo encode_form_val($startdate); ?>",
    enddate: "<?php echo encode_form_val($enddate); ?>",
    starttime: "<?php echo encode_form_val($starttime); ?>",
    endtime: "<?php echo encode_form_val($endtime); ?>",
    search: "<?php echo encode_form_val($search); ?>",
    host: "<?php echo encode_form_val($host); ?>",
    service: "<?php echo encode_form_val($service); ?>",
    hostgroup: "<?php echo encode_form_val($hostgroup); ?>",
    servicegroup: "<?php echo encode_form_val($servicegroup); ?>",
    statetype: "<?php echo encode_form_val($statetype); ?>",

}
var record_limit = <?php echo $pager_results['records_per_page']; ?>;
var max_records = <?php echo $pager_results['total_records']; ?>;
var max_pages = <?php echo $pager_results['total_pages']; ?>;

$(document).ready(function() {
    load_page();
});
</script>

    <div class="reportentries">
        <div id="loadscreen" class="hide"></div>
        <div id="loadscreen-spinner" class="sk-spinner sk-spinner-center sk-spinner-rotating-plane hide"></div>
        <div class="report-data" style="min-height: 140px;"></div>
    </div>

    <?php if (!$export) { ?>

    <div class="ajax-pagination">
        <button class="btn btn-xs btn-default first-page" title="<?php echo _('Last Page'); ?>"><i class="fa fa-fast-backward"></i></button>
        <button class="btn btn-xs btn-default previous-page" title="<?php echo _('Previous Page'); ?>" disabled><i class="fa fa-chevron-left l"></i></button>
        <span style="margin: 0 10px;"><?php echo _('Page'); ?> <span class="pagenum">1 <?php echo _('of'); ?> <?php echo $pager_results['total_pages']; ?></span></span>
        <button class="btn btn-xs btn-default next-page" title="<?php echo _('Next Page'); ?>"><i class="fa fa-chevron-right r"></i></button>
        <button class="btn btn-xs btn-default last-page" title="<?php echo _('Last Page'); ?>"><i class="fa fa-fast-forward"></i></button>

        <select class="form-control condensed num-records">
            <option value="5"<?php if ($pager_results['records_per_page'] == 5) { echo ' selected'; } ?>>5 <?php echo _('Per Page'); ?></option>
            <option value="10"<?php if ($pager_results['records_per_page'] == 10) { echo ' selected'; } ?>>10 <?php echo _('Per Page'); ?></option>
            <option value="25"<?php if ($pager_results['records_per_page'] == 25) { echo ' selected'; } ?>>25 <?php echo _('Per Page'); ?></option>
            <option value="50"<?php if ($pager_results['records_per_page'] == 50) { echo ' selected'; } ?>>50 <?php echo _('Per Page'); ?></option>
            <option value="100"<?php if ($pager_results['records_per_page'] == 100) { echo ' selected'; } ?>>100 <?php echo _('Per Page'); ?></option>
        </select>

        <input type="text" class="form-control condensed jump-to">
        <button class="btn btn-xs btn-default tt-bind jump" title="<?php echo _('Jump to Page'); ?>"><i class="fa fa-chevron-circle-right fa-12"></i></button>
    </div>

    <?php
    }

    do_page_end(true);
}


// Gets the XML for top alert producers report
function get_topalertproducers_xml()
{
    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $statetype = grab_request_var("statetype", "hard");

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

    // Special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // Can do hostgroup or servicegroup or host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }

    // Limit hosts by hostgroup or host
    $host_ids = array();
    
    // Limit by hostgroup
    if ($hostgroup != "") {
        $host_ids = get_hostgroup_member_ids($hostgroup, true);
    }
    
    // Limit service by servicegroup
    $service_ids = array();
    if ($servicegroup != "") {
        $service_ids = get_servicegroup_member_ids($servicegroup);
    }

    $object_ids_str = "";
    $y = 0;

    foreach ($host_ids as $hid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $hid;
        $y++;
    }

    foreach ($service_ids as $sid) {
        if ($y > 0)
            $object_ids_str .= ",";
        $object_ids_str .= $sid;
        $y++;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // Get XML data from backend - the most basic example
    // this will return all records (no paging), so it can be used for CSV export
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
    );

    switch ($statetype) {
        case "soft":
            $args["state_type"] = 0;
            break;
        case "hard":
            $args["state_type"] = 1;
            break;
        default:
            break;
    }

    if ($object_ids_str != "") {
        $args["object_id"] = "in:" . $object_ids_str;
    } else {
        if ($host != "") {
            $args["host_name"] = $host;
        }
        if ($service != "") {
            $args["service_description"] = $service;
        }
    }

    $xml = get_topalertproducers_data($args);

    return $xml;
}

// This function generates a CSV file of the "Top Alert Producers" report
function get_topalertproducers_csv()
{
    $xml = get_topalertproducers_xml();

    // Output header for csv
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"topalertproducers.csv\"");

    echo "totalalerts,host,service,latestalert" . "\n";

    if ($xml) {
        foreach ($xml->producer as $p) {
            $host_name = strval($p->host_name);
            $service_description = strval($p->service_description);
            $total_alerts = intval($p->total_alerts);
            echo $total_alerts . ",\"" . $host_name . "\",\"" . $service_description . "\"," . $p->last_state_time . "\n";
        }
    }
}

///////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS
///////////////////////////////////////////////////////////////////

// Return corresponding image and text to use
function get_statehistory_type_info($objecttype, $state, $statetype, &$img, &$text)
{
    $img = "info.png";
    $text = "";

    if ($objecttype == OBJECTTYPE_HOST) {
        switch ($state) {
            case 0:
                $img = "recovery.png";
                break;
            case 1:
                $img = "critical.png";
                break;
            case 2:
                $img = "critical.png";
                break;
        }
    } else {
        switch ($state) {
            case 0:
                $img = "recovery.png";
                break;
            case 1:
                $img = "warning.png";
                break;
            case 2:
                $img = "critical.png";
                break;
            case 3:
                $img = "unknown.png";
                break;
        }
    }
}