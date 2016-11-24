<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

require_once(dirname(__FILE__) . '/../includes/configwizards.inc.php');


// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

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
    $baseurl = get_base_url();
    $authorized = is_authorized_to_configure_objects();

    do_page_start(array("page_title" => _('Configuration Options')), true);
    ?>

    <h1><?php echo _('Configuration Options'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p><?php echo _("Choose what how you would like to configure Nagios XI. To get started right away, try using a configuration wizard under the 'Start Monitoring Now' section."); ?></p>

    <?php if ($authorized) { ?>
    <div style="width: 50%; min-width: 840px;">
        <div style="width: 50%; display: inline-block;">
            <div style="padding: 20px;">
                <a href="monitoringwizard.php" class="well" style="text-align: center; margin: 0; display: block; color: #000; text-decoration: none;">
                    <img src="<?php echo theme_image("config-wizard.png"); ?>">
                    <h4><?php echo _("Start Monitoring Now"); ?></h4>
                    <p><?php echo _("Quickly monitor a new device, server, application, or service using an easy configuration wizard."); ?></p>
                    <div><span style="color: #4D89F9;"><?php echo _('Run a configuration wizard'); ?></span> <i class="fa fa-chevron-right r"></i></div>
                </a>
            </div>
        </div><div style="width: 50%; display: inline-block;">
            <div style="padding: 20px;">
                <a href="<?php echo get_base_url().'includes/components/autodiscovery/'; ?>" class="well" style="text-align: center; margin: 0; display: block; color: #000; text-decoration: none;">
                    <img src="<?php echo get_base_url() . "includes/components/nagioscore/ui/images/logos/autodiscovery.png"; ?>">
                    <h4><?php echo _("Auto-Discovery"); ?></h4>
                    <p><?php echo _("Run an auto-discovery job to automatically find hardware, devices, and services to monitor."); ?></p>
                    <div><span style="color: #4D89F9;"><?php echo _('Use the auto-discovery tool'); ?></span> <i class="fa fa-chevron-right r"></i></div>
                </a>
            </div>
        </div>
    </div>
    <?php } ?>

    <div style="width: 50%; min-width: 840px;">
        <?php if ($authorized && is_advanced_user()) { ?>
        <div style="width: 50%; display: inline-block;">
            <div style="padding: 20px;">
                <a href="<?php echo get_base_url().'includes/components/ccm/xi-index.php'; ?>" target="_top" class="well" style="text-align: center; margin: 0; display: block; color: #000; text-decoration: none;">
                    <img src="<?php echo get_base_url().'includes/components/ccm/images/ccm.png'; ?>">
                    <h4><?php echo _('Advanced Configuration'); ?></h4>
                    <p><?php echo _('Manage your monitoring config files using an advanced web interface. <strong><em>Recommended for experienced users</em></strong>.'); ?></p>
                    <div><span style="color: #4D89F9;"><?php echo _('Go to Nagios Core Config Manager'); ?></span> <i class="fa fa-chevron-right r"></i></div>
                </a>
            </div>
        </div><?php } ?><div style="width: 50%; display: inline-block;">
            <div style="padding: 20px;">
                <a href="<?php echo $baseurl; ?>account/" target="_top" class="well" style="text-align: center; margin: 0; display: block; color: #000; text-decoration: none;">
                    <img src="<?php echo theme_image("config-account.png"); ?>">
                    <h4><?php echo _("Manage Account Settings"); ?></h4>
                    <p><?php echo _("Modify your account information, preferences, and notification settings."); ?></p>
                    <div><span style="color: #4D89F9;"><?php echo _('Edit your profile settings'); ?></span> <i class="fa fa-chevron-right r"></i></div>
                </a>
            </div>
        </div>
    </div>

    <?php
    // Include other component-specific items
    if ($authorized) {
        $args = array();
        do_callbacks(CALLBACK_CONFIG_SPLASH_SCREEN, $args);
    }

    do_page_end(true);
    exit();
}