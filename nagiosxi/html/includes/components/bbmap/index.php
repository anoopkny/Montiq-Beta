<?php
//
// Better Bullet Map (BBMap) Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

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

    $mode = grab_request_var("mode");
    switch ($mode) {
        case "getdata":
            minemap_get_data();
            break;
        default:
            display_bbmap();
            break;
    }
}

function display_bbmap()
{

    $type = grab_request_var("type", "");
    $host = grab_request_var("host", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $manual_run = grab_request_var("manual_run", 0);

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

    // Makes sure user has appropriate license level
    licensed_feature_check();

    do_page_start(array("page_title" => "BBmap"), true);
?>

<script type="text/javascript">
$(document).ready(function () {
    $('#servicegroupList').change(function () {
        $('#hostgroupList').val('');
    });
    $('#hostgroupList').change(function () {
        $('#servicegroupList').val('');
    });
});
</script>

<form action="" method="get">
    <div class="well report-options">
        <label><?php echo _("Limit To"); ?>:</label>
        <select name="hostgroup" id="hostgroupList" class="form-control">
            <option value=""><?php echo _("Hostgroup"); ?>:</option>
            <?php
            $args = array('orderby' => 'hostgroup_name:a');
            $xml = get_xml_hostgroup_objects($args);
            if ($xml) {
                foreach ($xml->hostgroup as $hg) {
                    $name = strval($hg->hostgroup_name);
                    echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
                }
            }
            ?>
        </select>
        <select name="servicegroup" id="servicegroupList" class="form-control">
            <option value=""><?php echo _("Servicegroup"); ?>:</option>
            <?php
            $args = array('orderby' => 'servicegroup_name:a');
            $xml = get_xml_servicegroup_objects($args);
            if ($xml) {
                foreach ($xml->servicegroup as $sg) {
                    $name = strval($sg->servicegroup_name);
                    echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
                }
            }
            ?>
        </select>

        <button type="submit" class="btn btn-sm btn-primary" name="goButton" id="goButton"><?php echo _('Update'); ?></button>

        <!-- Set a variable to let us know it's okay to run this -->
        <input type="hidden" name="manual_run" value="1">
    </div>
</form>

<h1 style="padding-top: 0;"><?php echo _("BBMap"); ?></h1>

<?php
// Die right here if we don't want to auto-load the page
if ($disable_report_auto_run == 1 && $manual_run == 0 ) {
    die();
}
?>

<div style="width: 98.5%">
    <?php
    $dargs = array(
        DASHLET_ARGS => array(
            "type" => $type,
            "host" => $host,
            "hostgroup" => $hostgroup,
            "servicegroup" => $servicegroup
        )
    );
    display_dashlet("bbmap", "", $dargs, DASHLET_MODE_OUTBOARD);
    ?>
</div>

    <?php
    do_page_end(true);
}