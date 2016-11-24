<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../../includes/common.inc.php');

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

    draw_page();
}

function draw_page()
{

    do_page_start(array("page_id" => "subcomponent-nagioscorecfg-page"));
    ?>
    <div id="leftnav">
        <?php print_menu(MENU_CORECONFIGMANAGER); ?>
    </div>

    <div id="maincontent">
        <iframe src="<?php echo nagioscorecfg_get_component_url() . "?dest=admin.php"; ?>" width="100%" frameborder="0"
                id="maincontentframe" name="maincontentframe">
            [Your user agent does not support frames or is currently configured not to display frames. ]
        </iframe>
        <div id="viewtools">
            <div id="popout">
                <a href="#"><img src="<?php echo get_base_url(); ?>/images/popout.png" border="0"
                                 alt="<?php echo _("Popout"); ?>" title="<?php echo _("Popout"); ?>"></a>
            </div>
            <div id="addtomyviews">
                <a href="#"><img src="<?php echo get_base_url(); ?>/images/addtomyviews.png" border="0"
                                 alt="<?php echo _("Add to My Views"); ?>"
                                 title="<?php echo _("Add to My Views"); ?>"></a>
            </div>
        </div>
    </div>

    <?php
    do_page_end();
}

?>


