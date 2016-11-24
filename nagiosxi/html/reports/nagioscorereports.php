<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication();


route_request();

function route_request()
{

    show_legacy_reports_page();
}


function show_legacy_reports_page()
{

    licensed_feature_check();

    do_page_start(array("page_title" => _("Reports")), true);

    ?>
    <h1><?php echo _("Reports"); ?></h1>

    <p><?php /* //this lstr was replaced with blank text..// echo ""; */ ?></p>

    <div class="legacyreportslist">

        <?php
        show_legacy_report("avail.php", "avail.png", _("Availability Report"), _("Provides an availability report of uptime for hosts and services.  Useful for determining SLA requirements and compliance."));

        show_legacy_report("trends.php", "trends.png", _("Trends Report"), _("Provides a graphical, timeline breakdown of the state of a particular host or service."));

        show_legacy_report("history.php?host=all", "history.png", _("Alert History Report"), _("Provides a record of historical alerts for hosts and services."));

        show_legacy_report("summary.php", "summary.png", _("Alert Summary Report"), _("Provides a report of top alert producers.  Useful for determining the biggest trouble-makers in your IT infrastructure."));

        show_legacy_report("histogram.php", "histogram.png", _("Alert Histogram Report"), _("Provides a frequency graph of host and service alerts.  Useful for seeing when alerts most often occur for a particular host or service."));

        show_legacy_report("notifications.php?contact=all", "notifications.png", _("Notifications Report"), _("Provides a historical record of host and service notifications that have been sent to contacts."));

        ?>

    </div>

    <?php
    do_page_end(true);
}

/**
 * @param $url
 * @param $img
 * @param $title
 * @param $desc
 */
function show_legacy_report($url, $img, $title, $desc)
{

    $baseurl = $nagioscoreui_path = nagioscore_get_ui_url() . $url;
    $imgurl = get_base_url() . "includes/components/xicore/images/legacyreports/" . $img;
    ?>
    <div class="legacyreport">
        <div class="legacyreportimage">
            <a href="<?php echo $baseurl; ?>"><img src="<?php echo $imgurl; ?>" title="<?php echo $title; ?>"></a>
        </div>
        <div class="legacyreportdescription">
            <div class="legacyreporttitle">
                <a href="<?php echo $baseurl; ?>"><?php echo $title; ?></a>
            </div>
            <?php echo $desc; ?>
        </div>
    </div>
<?php
}

?>

