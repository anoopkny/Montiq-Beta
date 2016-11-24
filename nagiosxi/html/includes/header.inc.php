<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

include_once(dirname(__FILE__) . '/common.inc.php');

?>

<!--- HEADER START -->

<?php
// Default logo stuff
$logo = "nagiosxi-logo-small.png";
//$logo_alt = get_product_name();
$logo_alt = "Montiq Beta";
$logo_url = get_base_url();
$logo_target = "_top";

// Use custom logo if it exists
$logosettings_raw = get_option("custom_logo_options");
if ($logosettings_raw == "")
    $logosettings = array();
else
    $logosettings = unserialize($logosettings_raw);

$custom_logo_enabled = grab_array_var($logosettings, "enabled");
if ($custom_logo_enabled == 1) {
    $logo = grab_array_var($logosettings, "logo", $logo);
    $logo_alt = grab_array_var($logosettings, "logo_alt", $logo_alt);
    $logo_url = grab_array_var($logosettings, "logo_url", $logo_url);
    $logo_target = grab_array_var($logosettings, "logo_target", $logo_target);
}

if (get_theme() == "xi5") {

    if (!$custom_logo_enabled) {
?>

    <div id="toplogo">
        <a href="<?php echo $logo_url; ?>" target="<?php echo $logo_target; ?>">
            <img src="<?php echo get_base_url(); ?>images/nagios_logo_white_transbg.png" border="0" class="xi-logo" alt="<?php echo $logo_alt; ?>" title="<?php echo $logo_alt; ?>">
            XI
        </a>
    </div>
    
    <?php  } else { ?>

    <div id="toplogo">
        <a href="<?php echo $logo_url; ?>" target="<?php echo $logo_target; ?>">
            <img src="<?php echo get_base_url(); ?>images/<?php echo $logo; ?>" border="0" alt="<?php echo $logo_alt; ?>" title="<?php echo $logo_alt; ?>">
        </a>
    </div>

    <?php } ?>

    <div id="topmenu">
        <?php if (is_authenticated() == true) { ?>
            <div class="mainmenu">
                <div><a href="<?php echo get_base_url(); ?>"><?php echo _("Home"); ?></a></div>
                <div><a href="<?php echo get_base_url(); ?>views/"><?php echo _("Views"); ?></a></div>
                <div><a href="<?php echo get_base_url(); ?>dashboards/"><?php echo _("Dashboards"); ?></a></div>
                <div><a href="<?php echo get_base_url(); ?>reports/"><?php echo _("Reports"); ?></a></div>
                <?php  if (is_authorized_to_configure_objects() == true) { ?>
                    <div id="config-menulink">
                        <span>
                            <a href="<?php echo get_base_url(); ?>config/"><?php echo _("Configure"); ?></a>
                            <ul class="config-dropdown">
                                <li><a href="<?php echo get_base_url(); ?>config/?xiwindow=monitoringwizard.php"><i class="fa fa-magic l"></i> <?php echo _('Configuration Wizards'); ?></a></li>
                                <?php if (is_advanced_user()) { ?>
                                <li><a href="<?php echo get_base_url(); ?>includes/components/ccm/xi-index.php"><i class="fa fa-cog l"></i> <?php echo _('Core Config Manager'); ?></a></li>
                                <?php } ?>
                            </ul>
                        </span>
                    </div>
                <?php } ?>
                <div><a href="<?php echo get_base_url(); ?>tools/"><?php echo _("Tools"); ?></a></div>
                <div><a href="<?php echo get_base_url(); ?>help/"><?php echo _("Help"); ?></a></div>
                <?php if (is_admin() == true) { ?>
                    <div><a href="<?php echo get_base_url(); ?>admin/"><?php echo _("Admin"); ?></a></div>
                <?php } ?>
            </div>
            <div class="hiddenmenu">
                <div id="mdropdown">
                    <span>
                        <span class="nav-head"><i class="fa fa-chevron-down l"></i> <?php echo _("Navigation"); ?></span>
                        <ul class="dropdown-items">
                            <li><a href="<?php echo get_base_url(); ?>"><?php echo _("Home"); ?></a></li>
                            <li><a href="<?php echo get_base_url(); ?>views/"><?php echo _("Views"); ?></a></li>
                            <li><a href="<?php echo get_base_url(); ?>dashboards/"><?php echo _("Dashboards"); ?></a></li>
                            <li><a href="<?php echo get_base_url(); ?>reports/"><?php echo _("Reports"); ?></a></li>
                            <?php  if (is_authorized_to_configure_objects() == true) { ?>
                                <li><a href="<?php echo get_base_url(); ?>config/"><?php echo _("Configure"); ?></a></li>
                            <?php } ?>
                            <li><a href="<?php echo get_base_url(); ?>tools/"><?php echo _("Tools"); ?></a></li>
                            <li><a href="<?php echo get_base_url(); ?>help/"><?php echo _("Help"); ?></a></li>
                            <?php if (is_admin() == true) { ?>
                                <li><a href="<?php echo get_base_url(); ?>admin/"><?php echo _("Admin"); ?></a></li>
                            <?php } ?>
                        </ul>
                    </span>
                </div>
            </div>
        <?php } else { ?>
        <div class="mainmenu">
            <div><a href="<?php echo get_base_url() . PAGEFILE_LOGIN; ?>"><?php echo _("Login"); ?></a></div>
        </div>
        <?php } ?>
    </div>

    <?php if (is_authenticated() == true) { ?>
    <div class="header-right ext">
        <span class="ext-menu">
            <i class="fa fa-bars"></i>
            <ul>
                <li id="schedulepagereport" class="tt-bind" data-placement="left" title="<?php echo _('Schedule page'); ?>"><a href="#"><i class="fa fa-clock-o"></i></a></li>
                <li id="popout" class="tt-bind" data-placement="left" title="<?php echo _('Popout'); ?>"><a href="#"><i class="fa fa-share-square-o"></i></a></li>
                <li id="addtomyviews" class="tt-bind" data-placement="left" title="<?php echo _('Add to my views'); ?>"><a href="#"><i class="fa fa-plus-circle"></i></a></li>
                <li id="permalink" class="tt-bind" data-placement="left" title="<?php echo _('Get permalink'); ?>"><a href="#"><i class="fa fa-chain"></i></a></li>
                <li id="feedback" class="tt-bind" data-placement="left" title="<?php echo _('Send us feedback'); ?>"><a href="#"><i class="fa fa-comment-o"></i></a></li>
            </ul>
        </span>
    </div>
    <div class="header-right profile">
        <a href="<?php echo get_base_url(); ?>account/" style="margin-right: 1.5rem;"><i class="fa fa-user"></i> <span><?php echo $_SESSION["username"]; ?></span></a>
        <?php if (is_http_basic_authenticated() == false) { ?>
            <a href="<?php echo get_base_url() . PAGEFILE_LOGIN; ?>?logout&amp;nsp=<?php echo get_nagios_session_protector_id(); ?>"><i class="fa fa-power-off"></i> <span><?php echo _("Logout"); ?></span></a>
        <?php } ?>
    </div>
    <div class="header-right system-alerts">
        <?php display_pagetop_alerts(); ?>
    </div>
    <div class="header-right search">
        <div class="search-field hide">
            <form method="post" target="maincontentframe" action="<?php echo get_base_url(); ?>includes/components/xicore/status.php?show=services">
                <input type="hidden" name="navbarsearch" value="1"/>
                <input type="text" class="search-query form-control" name="search" id="navbarSearchBox" value="" placeholder="<?php echo _('Search...'); ?>"/>
            </form>
        </div>
        <a href="#" id="open-search" title="<?php echo _('Search'); ?>"><i class="fa fa-search"></i></a>
    </div>
    <?php } ?>

<?php } else { ?>
    <div id="toplogo">
        <a href="<?php echo $logo_url; ?>" target="<?php echo $logo_target; ?>">
            <img src="<?php echo get_base_url(); ?>images/<?php echo $logo; ?>" border="0" alt="<?php echo $logo_alt; ?>" title="<?php echo $logo_alt; ?>">
        </a>
    </div>
    <div id="pagetopalertcontainer">
        <?php if (is_authenticated() == true) {
            display_pagetop_alerts();
        } ?>
    </div>
    <div id="authinfo">
        <?php if (is_authenticated() == true) { ?>
            <div id="authinfoname">
                <?php echo _("Logged in as"); ?>: <a href="<?php echo get_base_url(); ?>account/"><?php echo $_SESSION["username"]; ?></a>
            </div>
            <?php if (is_http_basic_authenticated() == false) { ?>
                <div id="authlogout">
                    <a href="<?php echo get_base_url() . PAGEFILE_LOGIN; ?>?logout&amp;nsp=<?php echo get_nagios_session_protector_id(); ?>"><?php echo _("Logout"); ?></a>
                </div>
            <?php
            }
        }
        ?>
    </div>
<?php
}

// If using the new style
$theme = get_theme();
if (use_2014_features() && $theme == "xi2014") {

    // Find out what tab is active
    $active = "home";

    $filename = $_SERVER['SCRIPT_FILENAME'];
    if (strpos($filename, "html/admin")) {
        $active = "admin";
    } else if (strpos($filename, "html/views")) {
        $active = "views";
    } else if (strpos($filename, "html/dashboards")) {
        $active = "dashboards";
    } else if (strpos($filename, "html/reports")) {
        $active = "reports";
    } else if (strpos($filename, "html/config") || strpos($filename, "includes/components/ccm")) {
        $active = "configure";
    } else if (strpos($filename, "html/tools")) {
        $active = "tools";
    } else if (strpos($filename, "html/help")) {
        $active = "help";
    } else if (strpos($filename, "login.php")) {
        $active = "login";
    }

    ?>

    <!-- New Nagios XI Navbar -->
    <div class="navbar navbar-inverse">
        <div class="container-fluid">
            <ul class="nav navbar-nav pull-left">
                <?php if (is_authenticated() === true) { ?>
                    <li<?php if ($active == "home") {
                        echo ' class="active"';
                    } ?>><a href="<?php echo get_base_url(); ?>"><?php echo _("Home"); ?></a></li>
                    <li<?php if ($active == "views") {
                        echo ' class="active"';
                    } ?>><a href="<?php echo get_base_url(); ?>views/"><?php echo _("Views"); ?></a></li>
                    <li<?php if ($active == "dashboards") {
                        echo ' class="active"';
                    } ?>><a href="<?php echo get_base_url(); ?>dashboards/"><?php echo _("Dashboards"); ?></a>
                    </li>
                    <li<?php if ($active == "reports") {
                        echo ' class="active"';
                    } ?>><a href="<?php echo get_base_url(); ?>reports/"><?php echo _("Reports"); ?></a></li>
                    <?php if (is_authorized_to_configure_objects() === true) { ?>
                        <li<?php if ($active == "configure") {
                            echo ' class="active"';
                        } ?>><a href="<?php echo get_base_url(); ?>config/"><?php echo _("Configure"); ?></a>
                        </li>
                    <?php
                    } ?>
                    <li<?php if ($active == "tools") { echo ' class="active"'; } ?>><a href="<?php echo get_base_url(); ?>tools/"><?php echo _("Tools"); ?></a></li>
                    <li<?php if ($active == "help") { echo ' class="active"'; } ?>><a href="<?php echo get_base_url(); ?>help/"><?php echo _("Help"); ?></a></li>
                    <?php if (is_admin() === true) { ?>
                        <li<?php if ($active == "admin") { echo ' class="active"'; } ?>><a href="<?php echo get_base_url(); ?>admin/"><?php echo _("Admin"); ?></a></li>
                    <?php } ?>
                <?php } else { ?>
                    <li<?php if ($active == "login") { echo ' class="active"'; } ?>>
                        <a href="<?php echo get_base_url() . PAGEFILE_LOGIN; ?>"><?php echo _("Login"); ?></a>
                    </li>
                <?php } ?>
            </ul>
            <?php if (is_authenticated() === true) { ?>
                <ul class="nav navbar-nav pull-right">
                    <li class="navbar-icons">
                        <div id="schedulepagereport">
                            <a href="#" title="<?php echo _('Schedule page'); ?>"><i class="fa fa-clock-o"></i></a>
                        </div>
                        <div id="permalink">
                            <a href="#" title="<?php echo _('Get permalink'); ?>"><i class="fa fa-chain"></i></a>
                        </div>
                        <div id="feedback">
                            <a href="#" title="<?php echo _('Send us feedback'); ?>"><i class="fa fa-comment"></i></a>
                        </div>
                        <div id="addtomyviews">
                            <a href="#" title="<?php echo _('Add to My Views'); ?>"><i class="fa fa-plus-circle"></i></a>
                        </div>
                        <div id="popout">
                            <a href="#" title="<?php echo _('Popout'); ?>"><i class="fa fa-share-square-o"></i></a>
                        </div>
                    </li>
                </ul>
                <form method="post" class="navbar-search pull-right" target="maincontentframe" action="<?php echo get_base_url(); ?>includes/components/xicore/status.php?show=services">
                    <input type="hidden" name="navbarsearch" value="1"/>
                    <input type="text" class="search-query" name="search" id="navbarSearchBox" value="" placeholder="<?php echo _('Search...'); ?>"/>
                </form>
            <?php } ?>
    </div>
    </div>

<?php
// Classic XI Style
} else if ($theme != 'xi5') {
?>

    <div id="topmenucontainer">
        <ul class="menu">
            <?php if (is_authenticated() == true) { ?>
                <li><a href="<?php echo get_base_url(); ?>"><?php echo _("Home"); ?></a></li>
                <li><a href="<?php echo get_base_url(); ?>views/"><?php echo _("Views"); ?></a></li>
                <li><a href="<?php echo get_base_url(); ?>dashboards/"><?php echo _("Dashboards"); ?></a></li>
                <li><a href="<?php echo get_base_url(); ?>reports/"><?php echo _("Reports"); ?></a></li>
                <?php if (is_authorized_to_configure_objects() == true) { ?>
                    <li><a href="<?php echo get_base_url(); ?>config/"><?php echo _("Configure"); ?></a></li>
                <?php } ?>
                <li><a href="<?php echo get_base_url(); ?>tools/"><?php echo _("Tools"); ?></a></li>
                <li><a href="<?php echo get_base_url(); ?>help/"><?php echo _("Help"); ?></a></li>
                <?php if (is_admin() == true) { ?>
                    <li><a href="<?php echo get_base_url(); ?>admin/"><?php echo _("Admin"); ?></a></li>
                <?php
                }
            } else {
                ?>
                <li><a href="<?php echo get_base_url() . PAGEFILE_LOGIN; ?>"><?php echo _("Login"); ?></a></li>
            <?php } ?>
        </ul>
    </div>
    <?php if (is_authenticated() == true) { ?>
    <div id="primarybuttons">
        <div id="schedulepagereport">
            <a href="#" alt="<?php echo _("Schedule Page"); ?>" title="<?php echo _("Schedule Page"); ?>"></a>
        </div>
        <div id="permalink">
            <a href="#" alt="<?php echo _("Get Permalink"); ?>" title="<?php echo _("Get Permalink"); ?>"></a>
        </div>
        <div id="feedback">
            <a href="#" alt="<?php echo _("Send Us Feedback"); ?>" title="<?php echo _("Send Us Feedback"); ?>"></a>
        </div>
    </div>
    <?php
    }
}
?>

<?php display_feedback_layer(); ?>
<div id="popup_layer">
    <div id="popup_content">
        <div id="popup_close">
            <a id="close_popup_link" style="display: inline-block;" title="<?php echo _("Close"); ?>"><i class="fa fa-times" style="font-size: 16px;"></i></a>
        </div>
        <div id="popup_container">
        </div>
    </div>
</div>

<?php

function display_feedback_layer()
{
    global $cfg;

    $name = get_user_attr(0, 'name');
    $email = get_user_attr(0, 'email');
    ?>
    <div id="feedback_layer">
        <div id="feedback_content">

            <div id="feedback_close">
                <a id="close_feedback_link" style="display: inline-block;" title="<?php echo _("Close"); ?>"><i class="fa fa-times" style="font-size: 16px;"></i></a>
            </div>

            <div id="feedback_container">

                <div id="feedback_header">
                    <b><?php echo _("Send Us Feedback"); ?></b>
                    <p><?php echo _("We love input!  Tell us what you think about this product and you'll directly drive future innovation!"); ?></p>
                </div>
                <!-- feedback_header -->

                <div id="feedback_data">

                    <form id="feedback_form" method="get" action="<?php echo get_ajax_proxy_url(); ?>">

                        <input type="hidden" name="proxyurl" value="<?php echo $cfg['feedback_url']; ?>">
                        <input type="hidden" name="proxymethod" value="post">

                        <input type="hidden" name="product" value="<?php echo get_product_name(true); ?>">
                        <input type="hidden" name="version" value="<?php echo get_product_version(); ?>">
                        <input type="hidden" name="build" value="<?php echo get_product_build(); ?>">

                        <label for="feedbackCommentBox"><?php echo _("Comments"); ?>:</label>
                        <textarea class="textarea form-control" name="comment" style="width: 100%; height: 100px;"></textarea>

                        <label for="feedbackNameBox"><?php echo _("Your Name (Optional)"); ?>:</label>
                        <input type="text" size="30" name="name" id="feedbackNameBox" value="<?php echo encode_form_val($name); ?>" class="textfield form-control">

                        <label for="feedbackEmailAddressBox"><?php echo _("Your Email Address (Optional)"); ?>:</label>
                        <input type="text" size="30" name="email" id="feedbackEmailAddressBox" value="<?php echo encode_form_val($email); ?>" class="textfield form-control">

                        <div>
                            <div class="fl" id="feedbackFormButtons">
                                <input type="submit" class="submitbutton btn btn-sm btn-primary" name="submitButton" value="<?php echo _("Submit"); ?>" id="submitFeedbackButton">
                            </div>
                            <div class="fr feedback-pp">
                                <a href="<?php echo $cfg["privacy_policy_url"]; ?>" target="_blank" rel="noreferrer"><?php echo _("Privacy Policy"); ?></a>
                            </div>
                            <div class="clear"></div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
}


function display_pagetop_alerts()
{
    $id = "pagetopalertcontent";

    $output = ' <div id="' . $id . '"></div>

                <script type="text/javascript">

                function create_popover() {
                    if ("'.get_theme().'" == "xi5") {
                        $("#topalert-popover").tooltip({ placement: "left" });
                        $("#topalert-popover").popover({ html: true });
                    }
                }

                $(document).ready(function() {

                    get_' . $id . '_content();
                        
                    $("#' . $id . '").everyTime(' . get_dashlet_refresh_rate(30, "pagetop_alert_content") . ', "timer-' . $id . '", function(i) {
                        get_' . $id . '_content();
                    });
                    
                    function get_' . $id . '_content() {
                        $("#' . $id . '").each(function() {
                            var optsarr = {
                                "func": "get_pagetop_alert_content_html",
                                "args": ""
                            }
                            var opts = array2json(optsarr);
                            get_ajax_data_innerHTML_with_callback("getxicoreajax", opts, true, this, "create_popover");
                        });
                    }
                });
                </script>';

    echo $output;

}
