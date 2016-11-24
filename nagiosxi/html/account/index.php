<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();


draw_page();


function draw_page()
{
    do_page_start(array("page_title" => _("Account Information")), false);
    ?>
    <div id="leftnav">
        <?php print_menu(MENU_ACCOUNT); ?>
    </div>
    <div id="maincontent">
        <div id="maincontentspacer">
            <IFRAME src="<?php echo get_window_frame_url("main.php"); ?>" width="100%" frameborder="0"
                    id="maincontentframe" name="maincontentframe">
                [Your user agent does not support frames or is currently configured not to display frames. ]
            </IFRAME>

            <?php if (get_theme() != 'xi5') { ?>
            <div id="viewtools">
                <div id="popout">
                    <a href="#"><img src="<?php echo get_base_url(); ?>/images/popout.png" border="0" alt="<?php echo _("Popout"); ?>" title="<?php echo _("Popout"); ?>"></a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php
    do_page_end(false);
}