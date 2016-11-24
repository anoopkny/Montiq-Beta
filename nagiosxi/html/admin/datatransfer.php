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

// only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}

// route request
route_request();


function route_request()
{

    show_page();

    exit;
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_page($error = false, $msg = "")
{

    do_page_start(array("page_title" => _("Check Data Transfer")), true);
    ?>

    <h1><?php echo _("Check Data Transfer"); ?></h1>


    <?php
    display_message($error, false, $msg);
    ?>

    <p><?php echo _("Configure settings for transferring host and service check results to and from this Nagios XI server."); ?></p>



    <br clear="all">
    <p>
        <a href="dtoutbound.php"><img src="<?php echo theme_image("dtoutbound.png"); ?>"
                                      style="float: left; margin-right: 10px;">
            <?php echo _("Manage Outbound Transfer Settings"); ?></a><br>
        <?php echo _("Configure outbound check transfer options.  Useful for distributed monitoring and redundant/failover setups."); ?>
    </p>

    <br clear="all">
    <p>
        <a href="dtinbound.php"><img src="<?php echo theme_image("dtinbound.png"); ?>"
                                     style="float: left; margin-right: 10px;">
            <?php echo _("Manage Inbound Transfer Settings"); ?></a><br>
        <?php echo _("Configure inbound check reception options.  Useful for receiving passive checks from external hosts, applications, and third-party addons."); ?>
    </p>

    <?php

    do_page_end(true);
    exit();
}

?>