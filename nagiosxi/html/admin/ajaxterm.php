<?php
//
// AJAX Terminal
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}


route_request();


function route_request()
{
    show_ajaxterm();
}


function show_ajaxterm($error = false, $msg = "")
{
    do_page_start(array("page_title" => _("SSH Terminal"), "enterprise" => true), true);
?>

    <h1><?php echo _("SSH Terminal"); ?></h1>

    <?php
    // Make sure installer didn't fail
    if (file_exists('/etc/httpd/conf.d/ajaxterm.fail')) {
        $error = true;
        $msg .= _("It appears that ajaxterm has not been successfully installed. Please see the following") . "
        <a href='https://support.nagios.com/wiki/index.php/Nagios_XI:FAQs#Ajaxterm_Installation_Aborted' target='_blank' title='Nagios XI FAQ'>
        " . _("FAQ on completing Ajaxterm installation") . "</a>.";
    }

    display_message($error, false, $msg);
    
    // Enterprise Feature Neatness
    // ** THIS FILE NEEDS TO BE ENCRYPTED **

    // Enterprise Edition message
    if (get_theme() != 'xi5') {
       echo enterprise_message();
    }

    if (enterprise_features_enabled() == true) {
        show_ajaxterm_content(true);
    } else {
        show_ajaxterm_content(false);
    }

    do_page_end(true);
    exit();
}


function show_ajaxterm_content($fullaccess = false)
{

    // Use current URL to craft HTTPS url - this is needed to accomodate users who are
    // connecting through a NAT redirect/port forwarding setup
    $current = get_current_url(false, true);
    $default_url = $current;
    $pos = strpos($default_url, "/nagiosxi/admin/ajaxterm.php");
    $newurl = substr($default_url, 0, $pos) . "/nagios/ajaxterm/";

    // Force SSL by default
    $url = str_replace("http:", "https:", $newurl);

    // User can override the URL
    $url = grab_request_var("url", $url);

    // Check enterprise license
    $efe = enterprise_features_enabled();
?>

    <p>
        <?php echo _("The terminal provides you with a convenient, web-based session to the terminal of your Nagios XI server.  You can login to your Nagios XI server using this interface to perform upgrades, run diagnostics, and more. Recommended for Firefox or Chrome. Internet Explorer requires Compatibility Mode to be enabled in order to use the SSH Terminal."); ?>
    </p>

    <?php if ($efe == true) { ?>

        <p>
            <strong><?php echo _("NOTE"); ?>:</strong>
            <?php echo _("You must re-enter your login credentials to access the SSH terminal. If this is your first time accessing the SSH Terminal, you must open this page in a new window and allow security exceptions in your browser."); ?>
        </p>

        <form method="post" action="ajaxterm.php" style="margin-top: 20px;">
            <div id="formButtons">
                URL: <input type="text" size="45" name="url" id="urlBox" value="<?php echo encode_form_val($url); ?>" class="textfield form-control">
                <button type="submit" class="btn btn-sm btn-primary" style="vertical-align: top;" name="updateButton" id="updateButton"><?php echo _('Update'); ?></button>
            </div>
        </form>

        <p><a href="javascript: void(0)" onclick="window.open('<?php echo $url; ?>', 'windowname1', 'width=700,height=450,scrollbars=1'); return false;"><?php echo _('Open terminal in a new window'); ?></a></p>

        <iframe src="<?php echo $url; ?>" width="700px" height="450px"></iframe>
    
    <?php  } else { ?>

        <p><img src="<?php echo theme_image("ajaxterm.png"); ?>"></p>
    
    <?php
        if (get_theme() != 'xi5') {
            echo enterprise_limited_feature_message("This feature is only available in Enterprise Edition.");
        }
    }
}