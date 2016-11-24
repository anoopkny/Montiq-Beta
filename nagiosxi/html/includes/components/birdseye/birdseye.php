<?php
// Birdseye Component
//
// Default file that displays the actual NOC-like display
//
// Copyright (c) 2013-2015 Nagios Enterprises, LLC.  All rights reserved.
// 

require_once(dirname(__FILE__) . '/../../common.inc.php');
require_once(dirname(__FILE__) . '/ajaxreqs.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication(false);

// Do actual stuff
$title = _("Birdseye");

//$down = be_get_all_down();

$info = get_xml_program_status(array());
$status = array("nagios" => intval($info->programstatus->is_currently_running),
    "notifications" => intval($info->programstatus->notifications_enabled));

//echo "<pre>";
//print_r($down);
//echo "</pre>";

?>
<!DOCTYPE html>
<html>
<!-- Produced by Nagios XI.  Copyyright (c) 2008-2013 Nagios Enterprises, LLC (www.nagios.com). All Rights Reserved. -->
<head>
    <title>Nagios XI - <?php echo $title; ?></title>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <?php do_page_head_links(); // Add Nagios XI links ?>
    <link rel="stylesheet" type="text/css" href="styles/default.css">
    <script type="text/javascript" src="includes/masonry.pkgd.min.js"></script>
    <script type="text/javascript" src="includes/main.js"></script>
</head>
<body class="day">
<div id="be-body">
    <div id="be-dash">
        <div id="be-header">
            <div style="float: left; width: 200px;">
                <h1 id="test"><?php echo $title; ?> 
                    <img src="images/lightbulb.png" title="Turn lights off!" id="toggle-lights">
                </h1>
            </div>
            <div style="float: left; height: 40px; width: 20%;">
                <div class="stat"><?php if ($status['nagios']) {
                        echo '<img src="images/tick.png" title="Running">';
                    } else {
                        echo '<img src="images/cross.png" title="Not running">';
                    } ?> <?php echo _("Monitoring Engine"); ?>
                </div>
                <div class="stat"><?php if ($status['notifications']) {
                        echo '<img src="images/tick.png" title="Enabled">';
                    } else {
                        echo '<img src="images/cross.png" title="Disabled">';
                    } ?> <?php echo _("Notifications Enabled"); ?>
                </div>
            </div>
            <div style="float: left; height: 40px; width: 25%;">
                <div class="stat" style="cursor: pointer;" onclick="change_handled()">
                  <img src="images/bullet_wrench.png">
                  <span class="handled"><?php echo _("Hiding Handled"); ?></span>
                </div>
                <div class="stat" style="cursor: pointer;" onclick="change_soft()">
                  <img src="images/bullet_wrench.png">
                  <span class="soft"><?php echo _("Hiding Soft States"); ?></span>
                </div>
            </div>
            <div class="float: left;" id="clock"></div>
            <!-- <div class="float: left;">
                <span id="testup" style="cursor:pointer;">Test Toggle</span> <span id="ticker"></span>
            </div> -->
            <div style="clear: both;"></div>
        </div>
        <div id="be-hosts">
            <!--
            <img style="width: 22px; height: 22px; vertical-align: top; margin-right: 5px;" src="../nagioscore/ui/images/logos/'.$host['icon'].'" title="" alt="">
            -->
        </div>
    </div>
    <div id="be-status">
    </div>
</div>

<?php
do_page_end(true);
// End Nagios xI page
?>
