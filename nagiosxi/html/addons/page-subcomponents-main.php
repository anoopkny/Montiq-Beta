<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/common.inc.php');

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


route_request();

function route_request()
{

    $pageopt = grab_request_var("pageopt", "info");
    switch ($pageopt) {
        default:
            show_subcomponents_page();
            break;
    }
}


function show_subcomponents_page()
{

    do_page_start(array("page_title" => _("Addons")), true);

    ?>
    <h1><?php echo _("Addons"); ?></h1>

    <p><?php echo _("Nagios XI includes several proven, enterprise-grade Open Source addons.  You may access these addons directly using the links below."); ?></p>

    <div class="subcomponentslist">

        <?php
        show_subcomponent("subcomponent-nagioscore", "nagioscore.png", "Nagios Core", _("Nagios&reg; Core&trade; provides the primary monitoring and alerting engine."));

        show_subcomponent("subcomponent-nagiocorecfg", "nagioscorecfg.png", "Nagios Core Config Manager", _("Nagios Core Config Manager provides an advanced graphical interface for configuring the Nagios Core monitoring and alerting engine. Recommended for advanced users only."));


        ?>

    </div>



    <?php
    do_page_end(true);
}


/**
 * @param $page
 * @param $img
 * @param $title
 * @param $desc
 */
function show_subcomponent($page, $img, $title, $desc)
{

    $baseurl = get_base_url() . "?page=" . $page;
    $imgurl = get_base_url() . "includes/components/xicore/images/subcomponents/" . $img;
    ?>
    <div class="subcomponent">
        <div class="subcomponentimage">
            <a href="<?php echo $baseurl; ?>" target="_top"><img src="<?php echo $imgurl; ?>"
                                                                 title="<?php echo $title; ?>"></a>
        </div>
        <div class="subcomponentdescription">
            <div class="subcomponenttitle">
                <a href="<?php echo $baseurl; ?>" target="_top"><?php echo $title; ?></a>
            </div>
            <?php echo $desc; ?>
        </div>
    </div>
<?php
}

?>
