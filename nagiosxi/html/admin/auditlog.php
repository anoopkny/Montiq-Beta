<?php
//
// Audit Log (Enterprise Feature)
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check prereqs
grab_request_vars();
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}

route_request();

function route_request()
{
    $mode = grab_request_var("mode", "");
    switch ($mode) {
        case "getreport":
            get_auditlog_report();
            break;
        case "getpage":
            get_auditlog_page();
            break;
        case "csv":
            get_auditlog_csv();
            break;
        case "pdf":
            export_report('auditlog', EXPORT_PDF, EXPORT_LANDSCAPE);
            break;
        default:
            show_auditlog();
            break;
    }
}


function get_auditlog_page()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Check enterprise license
    $efe = enterprise_features_enabled();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 1);
    $records = grab_request_var("records", 10);
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $details = grab_request_var("details", 'hide');
    $export = grab_request_var('export', 0);
    $type = grab_request_var('type', '');
    $source = grab_request_var("source", "");

    // Expired enterprise license can only stay on 1st page
    if ($efe == false) {
        $page = 1;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1,
        "records" => ""
    );
    if ($search) {
        $args["message"] = "lk:" . $search . ";source=lk:" . $search . ";user=lk:" . $search . ";details=lk:" . $search;
    }

    if (!empty($type)) {
        $args["type"] = intval($type);
    }

    if ($source != "") {
        $args["source"] = $source;
    }

    $xml = get_auditlog_xml($args);
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
        "source" => $source,
        "type" => $type,
        "details" => $details
    );
    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);

    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "records" => $records . ":" . $first_record,
    );

    if ($search) {
        $args["message"] = "lk:" . $search . ";source=lk:" . $search . ";user=lk:" . $search . ";details=lk:" . $search;
    }
    if ($source != "") {
        $args["source"] = $source;
    }
    if ($type != "") {
        $args["type"] = $type;
    }
    $xml = get_auditlog_xml($args);
    ?>

    <table class="auditlogtable table table-condensed table-striped table-bordered">
        <thead>
        <tr>
            <th style="width: 140px;"><?php echo _("Date / Time"); ?></th>
            <th><?php echo _("ID"); ?></th>
            <th><?php echo _("Source"); ?></th>
            <th><?php echo _("Type"); ?></th>
            <th><?php echo _("User"); ?></th>
            <th><?php echo _("IP Address"); ?></th>
            <th><?php echo _("Message"); ?></th>
            <?php if ($details == "show") { ?><th><?php echo _("Details"); ?></th><?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($xml) {

            $cols = 7;
            if ($details == "show") { $cols = 8; }
            $x = 0;

            if ($total_records == 0) {
                echo "<tr><td colspan='".$cols."'>" . _("No matching results found. Try expanding your search criteria.") . "</td></tr>\n";
            } else foreach ($xml->auditlogentry as $a) {

                $x++;
                if ($efe == false && $x > 5)
                    break;

                $user = strval($a->user);
                $ip = strval($a->ip_address);
                if ($user == "NULL")
                    $user = "";
                if ($ip == "NULL")
                    $ip = "";

                echo "<tr >";
                echo "<td nowrap><span class='notificationtime'>" . $a->log_time . "</span></td>";
                echo "<td>" . $a->id . "</td>";
                echo "<td>" . $a->source . "</td>";
                echo "<td>" . $a->typestr . "</td>";
                echo "<td>" . $user . "</td>";
                echo "<td>" . $ip . "</td>";
                echo "<td>" . $a->message . "</td>";
                if ($details == "show") { echo "<td>" . $a->details . "</td>"; }
                echo "</tr>";
            }
            if ($efe == false) {
                echo "<tr><td colspan='".$cols."'>" . enterprise_limited_feature_message(_("Limited messages shown. Purchase Enterprise Edition to enable full functionality.")) . "</td></tr>\n";
            }
        }
        ?>
        </tbody>
    </table>

    <?php
}


function get_auditlog_report()
{
    global $request;

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Check enterprise license
    $efe = enterprise_features_enabled();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 1);
    $records = grab_request_var("records", get_user_meta(0, 'report_defualt_recordlimit', 10));
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $source = grab_request_var("source", "");
    $details = grab_request_var("details", 'hide');
    $hideoptions = grab_request_var('hideoptions', 0);
    $type = grab_request_var('type', '');
    $export = grab_request_var('export', 0);
    $host = grab_request_var('host', '');
    $service = grab_request_var('service', '');
    $hostgroup = grab_request_var('hostgroup', '');
    $servicegroup = grab_request_var('servicegroup', '');

    // Expired enterprise license can only stay on 1st page
    if ($efe == false) {
        $page = 1;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1,
        "records" => ""
    );
    if ($search) {
        $args["message"] = "lk:" . $search . ";source=lk:" . $search . ";user=lk:" . $search . ";details=lk:" . $search;
    }

    if (!empty($type)) {
        $args["type"] = intval($type);
    }

    if ($source != "") {
        $args["source"] = $source;
    }

    $xml = get_auditlog_xml($args);
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
        "source" => $source,
        "type" => $type,
        "details" => $details
    );
    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);

    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "records" => $records . ":" . $first_record,
    );

    if ($search) {
        $args["message"] = "lk:" . $search . ";source=lk:" . $search . ";user=lk:" . $search . ";details=lk:" . $search;
    }
    if ($source != "") {
        $args["source"] = $source;
    }
    if ($type != "") {
        $args["type"] = $type;
    }
    $xml = get_auditlog_xml($args);

    if ($export) {

        do_page_start(array("page_title" => _("Notifications"), "enterprise" => true), true);

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
                <img src="<?php echo get_base_url(); ?>images/<?php echo $logo; ?>" border="0" alt="<?php echo $logo_alt; ?>" title="<?php echo $logo_alt; ?>">
            </div>
            <div style="float: left; height: 44px;">
                <h1 style="margin: 0; padding: 0 0 5px 0;"><?php echo _("Audit Log"); ?></h1>
                <div><?php echo _("Report covers from"); ?>:
                    <strong><?php echo get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></strong> <?php echo _("to"); ?>
                    <strong><?php echo get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></strong>
                </div>
            </div>
            <div style="clear:both;"></div>
        </div>

        <?php } else { ?>

        <h1><?php echo _('Audit Log'); ?></h1>
        <p><?php echo _('The audit log provides admins with a record of changes that occur on the XI system, which is useful for ensuring your organization meets compliance requirements.'); ?></p>

        <div>
            <?php echo _("From"); ?>:
            <b><?php echo get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b>
            <?php echo _("to"); ?>
            <b><?php echo get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b>
        </div>

        <?php } ?>

    <div class="recordcounttext">
        <?php
        $clear_args = array(
            "reportperiod" => $reportperiod,
            "startdate" => $startdate,
            "enddate" => $enddate,
            "starttime" => $starttime,
            "endtime" => $endtime,
            "source" => $source,
            "type" => $type,
            "details" => $details
        );
        echo table_record_count_text($pager_results, $search, true, $clear_args, '', true);
        ?>
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
    <?php } ?>

<script>
var report_url = '<?php echo get_base_url(); ?>admin/auditlog.php';
var report_url_args = {
    reportperiod: '<?php echo $reportperiod; ?>',
    startdate: '<?php echo $startdate; ?>',
    enddate: '<?php echo $enddate; ?>',
    starttime: '<?php echo $starttime; ?>',
    endtime: '<?php echo $endtime; ?>',
    search: '<?php echo $search; ?>',
    details: '<?php echo $details; ?>',
    host: '<?php echo $host; ?>',
    service: '<?php echo $service; ?>',
    hostgroup: '<?php echo $hostgroup; ?>',
    servicegroup: '<?php echo $servicegroup; ?>',
    type: '<?php echo $type; ?>',
    source: '<?php echo $source; ?>'
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
        <div id="loadscreen-spinner" class="sk-spinner sk-spinner-rotating-plane sk-spinner-center hide"></div>
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
}


function show_auditlog($error = false, $msg = "")
{
    global $request;
    $theme = get_theme();

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

    if (enterprise_features_enabled() == true) {
        $fullaccess = true;
    } else {
        $fullaccess = false;
    }

    // Makes sure user has appropriate license level
    licensed_feature_check();

    // Check enterprise license
    $efe = enterprise_features_enabled();

    // Get values passed in GET/POST request
    $page = grab_request_var("page", 1);
    $records = grab_request_var("records", get_user_meta(0, 'report_defualt_recordlimit', 10));
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $source = grab_request_var("source", "");
    $details = grab_request_var("details", 'hide');
    $hideoptions = grab_request_var('hideoptions', 0);
    $type = grab_request_var('type', '');

    // Expired enterprise license can only stay on 1st page
    if ($efe == false) {
        $page = 1;
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
        "totals" => 1,
        "records" => ""
    );
    if ($search) {
        $args["message"] = "lk:" . $search . ";source=lk:" . $search . ";user=lk:" . $search . ";details=lk:" . $search;
    }

    if (!empty($type)) {
        $args["type"] = intval($type);
    }

    if ($source != "") {
        $args["source"] = $source;
    }

    $xml = get_auditlog_xml($args);
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
        "source" => $source,
        "type" => $type,
        "details" => $details
    );
    $pager_results = get_table_pager_info("", $total_records, $page, $records, $args);
    $first_record = (($pager_results["current_page"] - 1) * $records);

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    do_page_start(array("page_title" => _("Audit Log"), "enterprise" => true), true);
?>

<script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>

<script type="text/javascript">
$(document).ready(function () {

    // If we should run it right away
    if (!<?php echo $disable_report_auto_run; ?>) {
        run_auditlog_ajax();
    }
    
    $('#reportperiodDropdown').change(function() {
        if ($(this).val() == "custom") {
            $('.cal').show();
        } else {
            $('.cal').hide();
        }
    });

    $('#startdateBox').click(function () {
        if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
            $('#startdateBox').val('<?php echo $auto_start_date;?>');
            $('#enddateBox').val('<?php echo $auto_end_date;?>');
        }
    });

    $('#enddateBox').click(function () {
        if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
            $('#startdateBox').val('<?php echo $auto_start_date;?>');
            $('#enddateBox').val('<?php echo $auto_end_date;?>');
        }
    });

    // Actually return the report
    $('#run').click(function() {
        run_auditlog_ajax();
    });

    // Get the export button link and send user to it
    $('.btn-export').on('mousedown', function(e) {
        var type = $(this).data('type');
        var formvalues = $("form").serialize();
        formvalues += '&mode=getreport';
        var url = "<?php echo get_base_url(); ?>admin/auditlog.php?" + formvalues + "&mode=" + type;
        if (e.which == 2) {
            window.open(url);
        } else if (e.which == 1) {
            window.location = url;
        }
    });
});

var report_sym = 0;
function run_auditlog_ajax() {
    report_sym = 1;
    setTimeout('show_loading_report()', 500);

    var formvalues = $("form").serialize();
    formvalues += '&mode=getreport';
    var url = 'auditlog.php?'+formvalues;

    current_page = 1;

    $.get(url, {}, function(data) {
        report_sym = 0;
        hide_throbber();
        $('#report').html(data);
        $('#report .tt-bind').tooltip();
    });
}
</script>

<form method="get" id="report-options" data-type="notifications">
    <div class="well report-options">

        <div class="reportexportlinks">
            <?php // echo get_add_myreport_html(_('Audit Log'), $_SERVER['PHP_SELF'], array()); ?>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <?php echo _('Download'); ?> <i class="fa fa-caret-down r"></i>
                </button>
                 <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                    <li><a class="btn-export" data-type="csv" title="<?php echo _("Download as CSV"); ?>"><i class="fa fa-file-text-o l"></i> <?php echo _("CSV"); ?></a></li>
                    <li><a class="btn-export" data-type="pdf" title="<?php echo _("Download as PDF"); ?>"><i class="fa fa-file-pdf-o l"></i> <?php echo _("PDF"); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="reportsearchbox">
            <input type="text" size="15" name="search" id="searchBox" value="<?php echo encode_form_val($search); ?>" placeholder="<?php echo _("Search..."); ?>" class="textfield form-control">
        </div>

        <div class="reporttimepicker">
            <?php echo _("Period"); ?>&nbsp;
            <select id='reportperiodDropdown' name="reportperiod" class="form-control vam">
                <?php
                $tp = get_report_timeperiod_options();
                foreach ($tp as $shortname => $longname) {
                    echo "<option value='" . $shortname . "' " . is_selected($shortname, $reportperiod) . ">" . $longname . "</option>";
                }
                ?>
            </select>

            <span class="cal" <?php if ($reportperiod == "custom") { echo 'style="display: inline-block;"'; } ?>>
                <?php echo _("From"); ?>&nbsp;
                <input class="textfield form-control vam cal-input" type="text" id="startdateBox" name="startdate" value="<?php echo encode_form_val($startdate); ?>">
                <div class="reportstartdatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                <div id="startdatepickercontainer"></div>

                <?php echo _("To"); ?>&nbsp;
                <input class="textfield form-control vam cal-input" type="text" id='enddateBox' name="enddate" value="<?php echo encode_form_val($enddate); ?>">
                <div class="reportenddatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div><div id="enddatepickercontainer"></div>
            </span>

            <label style="font-weight: normal; cursor: pointer; margin-left: 1rem;">
                <input type="checkbox" name="details" value="show" <?php echo is_checked($details, 'show'); ?> style="vertical-align: text-top; margin: 0;"> <?php echo _("Show Details"); ?>
            </label>
            
            <span style="margin-left: 10px;">
                <?php echo _('Type'); ?>&nbsp;
                <select name="type" class="form-control vam">
                    <option value="" <?php echo is_selected($type, ''); ?>>Any</option>
                    <option value="1" <?php echo is_selected($type, 1); ?>>ADD</option>
                    <option value="2" <?php echo is_selected($type, 2); ?>>DELETE</option>
                    <option value="4" <?php echo is_selected($type, 4); ?>>MODIFY</option>
                    <option value="8" <?php echo is_selected($type, 8); ?>>CHANGE</option>
                    <option value="16" <?php echo is_selected($type, 16); ?>>SECURITY</option>
                    <option value="32" <?php echo is_selected($type, 32); ?>>INFO</option>
                    <option value="64" <?php echo is_selected($type, 64); ?>>OTHER</option>
                </select>
            </span>

            <span style="margin-left: 10px;">
                <?php echo _('Source'); ?>&nbsp;
                <select name="source" class="form-control vam">
                    <option value="" <?php echo is_selected($source, ''); ?>>Any</option>
                    <option value="Nagios XI" <?php echo is_selected($source, 'Nagios XI'); ?>>Nagios XI</option>
                    <option value="Nagios CCM" <?php echo is_selected($source, 'Nagios CCM'); ?>>Nagios CCM</option>
                </select>
            </span>

            <span style="margin-left: 10px;">
                <button type="button" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>
            </span>

        </div>

    </div>
</form>

<div id="report"></div>

<?php

}

/**
 * This function gets the XML records of audit log data.
 * @return SimpleXMLElement
 */
function get_auditlog_xml($args=array())
{

    // makes sure user has appropriate license level
    licensed_feature_check();

    // get values passed in GET/POST request
    $reportperiod = grab_array_var($args, "reportperiod", grab_request_var("reportperiod", "last24hours"));
    $startdate = grab_array_var($args, "startdate", grab_request_var("startdate", ""));
    $enddate = grab_array_var($args, "enddate", grab_request_var("enddate", ""));
    $search = grab_array_var($args, "search", grab_request_var("search", ""));
    $source = grab_array_var($args, "source", grab_request_var("source", ""));
    $type = grab_array_var($args, "type", grab_request_var("type", ""));
    $user = grab_array_var($args, "user", grab_request_var("user", ""));
    $ip_address = grab_array_var($args, "ip_address", grab_request_var("ip_address", ""));
    $records = grab_array_var($args, "records", grab_request_var("records", ""));
    $details = grab_request_var("details", 'hide');

    // fix search
    if ($search == _("Search..."))
        $search = "";


    // determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // get XML data from backend - the most basic example
    // this will return all records (no paging), so it can be used for CSV export
    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
    );
    if ($source != "")
        $args["source"] = $source;
    if ($user != "")
        $args["user"] = $user;
    if ($type != "")
        $args["type"] = $type;
    if ($ip_address != "")
        $args["ip_address"] = $ip_address;
    if ($search) {
        $args["message"] = "lk:" . $search . ";source=lk:" . $search . ";user=lk:" . $search . ";ip_address=lk:" . $search . ";details=lk:" . $search;
    }

    if ($records != "") {
        $args["records"] = $records;
    }

    $xml = get_xml_auditlog($args);
    return $xml;
}

// Generates a CSV file of audit log data
function get_auditlog_csv()
{

    $xml = get_auditlog_xml(array("records" => ""));

    // output header for csv
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"auditlog.csv\"");

    // column definitions
    echo "id,time,source,user,type,ip_address,message,details\n";

    // bail out of trial expired
    if (enterprise_features_enabled() == false)
        return;

    //print_r($xml);
    //exit();

    if ($xml) {
        foreach ($xml->auditlogentry as $a) {

            echo "\"" . $a->id . "\",\"" . $a->log_time . "\",\"" . $a->source . "\",\"" . $a->user . "\",\"" . $a->type . "\",\"" . $a->ip_address . "\",\"" . $a->message . "\"\n";
        }
    }
}
