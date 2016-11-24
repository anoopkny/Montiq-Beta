<?php
//
// Hypermap
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: eventlog.php 359 2010-10-31 17:08:47Z egalstad $

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
            hypermap_get_data();
            break;
        default:
            display_hypermap();
            break;
    }
}


function display_hypermap()
{
    $type = grab_request_var("type", "");
    $refresh = grab_request_var("refresh", 60);

    // Makes sure user has appropriate license level
    licensed_feature_check();

    do_page_start(array("page_title" => _("Hypermap")), true);
?>

<script type="text/javascript">
$(document).ready(function() {
    var height = $(window).height() - 90;
    $('#hypermap-container').css('height', height+'px');
    $('#hypermap-left-container').css('height', height+'px');
    $('#hypermap-right-container').css('height', height+'px');
    $('#hypermap-center-container').css('height', height+'px');
    $('#hypermap-infovis').css('height', height+'px');
});
</script>

<h1><?php echo _("Hypermap"); ?></h1>

<?php
    $dargs = array(
        DASHLET_ARGS => array(
            "type" => $type,
            "refresh" => $refresh
        )
    );
    display_dashlet("hypermap", "", $dargs, DASHLET_MODE_OUTBOARD);

    do_page_end(true);
}
