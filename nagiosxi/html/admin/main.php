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

    if (is_admin() == false) {
        echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
        exit();
    }

    $pageopt = grab_request_var("pageopt", "");
    switch ($pageopt) {
        case "":
            show_admin_splash();
            break;
        default:
            show_missing_feature();
            break;
    }
}


function show_missing_feature()
{
    do_missing_feature_page();
}

function show_admin_splash()
{

    do_page_start(array("page_title" => _("Admin")), true);

    ?>

    <h1><?php echo _("Administration"); ?></h1>

    <?php echo _("<p>Manage your XI installation with the administrative options available to you in this section.  Make sure you complete any setup tasks that are shown below before using your XI installation.</p>"); ?>
    <br>

    <div style="float: left; margin-right: 25px;">

        <div>
            <?php
            display_dashlet("xicore_admin_tasks", "", null, DASHLET_MODE_OUTBOARD);
            ?>
        </div>


    </div><!--left float -->


    <div style="float: left;">

        <?php
        display_dashlet("xicore_component_status", "", null, DASHLET_MODE_OUTBOARD);
        ?>

    </div><!--right float-->


    <?php

    do_page_end(true);
}


?>

