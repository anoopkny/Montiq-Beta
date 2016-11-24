<?php
//
// Lates Alerts Component
// Copyright (c) 2011-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: eventlog.php 359 2010-10-31 17:08:47Z egalstad $

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
        case "getservices":
            latestalerts_get_services();
            break;
        default:
            display_alerts();
            break;
    }
}

function latestalerts_get_services()
{
    $host = grab_request_var("host", "");
    $args = array('brevity' => 1, 'host_name' => $host, 'orderby' => 'service_description:a');
    $oxml = get_xml_service_objects($args);
    echo '<option value="">['._("All Services").']</option>';
    echo '<option value="*">['._("Host Only").']</option>';
    if ($oxml) {
        foreach ($oxml->service as $serviceobj) {
            $name = strval($serviceobj->service_description);
            echo "<option value='" . $name . "'>$name</option>\n";
        }
    }
}

function display_alerts()
{

    $type = grab_request_var("type", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $maxitems = grab_request_var("maxitems", 20);

    // Makes sure user has appropriate license level
    licensed_feature_check();

    do_page_start(array("page_title" => _("Latest Alerts")), true);
?>

<script type="text/javascript">
$(document).ready(function() {
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
    });

    $('#servicegroupList').change(function () {
        $('#hostList').val('');
        $('#hostgroupList').val('');
        $('#serviceList').val('').hide();
        $('.serviceList-sbox').hide();
    });

    $('#hostgroupList').change(function () {
        $('#servicegroupList').val('');
        $('#hostList').val('');
        $('#serviceList').val('').hide();
        $('.serviceList-sbox').hide();
    });

    function update_service_list() {
        var host = $('#hostList').val();
        $.get('index.php?mode=getservices&host='+host, function(data) {
            $('#serviceList').html(data);
        });
    }
});
</script>

<form method="get" data-type="latestalerts">
    <div class="well report-options">

        <?php echo _("Limit To"); ?>&nbsp;
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
        <select name="service" id="serviceList" style="width: 150px; <?php if (empty($service) && empty($host)) { echo 'display: none;'; } ?>"  class="form-control">
            <option value="">[<?php echo _("All Services"); ?>]</option>
            <option value="*" <?php if ($service == '*') { echo 'selected'; } ?>>[<?php echo _("Host Only"); ?>]</option>
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

        <span style="margin: 0 10px;">
            <?php echo _("Max Items"); ?>
            <input type="text" name="maxitems" value="<?php echo htmlentities($maxitems); ?>" size="2" class="form-control">
        </span>

        <input type="submit" class="btn btn-sm btn-primary" name="goButton" value="<?php echo _("Update"); ?>" id="goButton">
    </div>
</form>

<h1 style="padding-top: 0;"><?php echo _("Latest Alerts"); ?></h1>

    <?php
    $dargs = array(
        DASHLET_ARGS => array(
            "type" => $type,
            "host" => $host,
            "service" => $service,
            "hostgroup" => $hostgroup,
            "servicegroup" => $servicegroup,
            "maxitems" => $maxitems
        )
    );
    display_dashlet("latestalerts", "", $dargs, DASHLET_MODE_OUTBOARD);

    do_page_end(true);
}
