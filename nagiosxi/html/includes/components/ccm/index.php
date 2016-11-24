<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: index.php
//  Desc: First page of the CCM. This page is designed to create either a Nagios XI compatable
//        page using the XI do_page_start or a Nagios CCM standalone page.
//

// Define environment
if (file_exists(dirname(__FILE__).'/../../../config.inc.php')) {
    define('ENVIRONMENT', 'nagiosxi');
} else {
    define('ENVIRONMENT', 'nagioscore');
}

// Start the session and initialization based on environment
if (ENVIRONMENT == "nagiosxi") {

    // Include the Nagios XI helper functions through the component helper file and initialize
    // anything we will need to authenticate ourselves to the CCM
    require_once(dirname(__FILE__).'/../componenthelper.inc.php');
    pre_init();
    init_session();

    // Auth checks (someday when CCM does not require a WGET NAGIOSQL LOGIN, delete this check)
    if (    grab_request_var('backend', 0) && (grab_request_var('cmd', '') == 'apply' 
        || grab_request_var('submit', '') == 'Login' 
        || (grab_request_var('cmd', '') == 'admin' && grab_request_var('type', '') == 'import') 
        || grab_request_var('cmd', '') == 'delete')
       ) {
        // Backend call... do not do XI auth on it, only CCM auth
    } else {
        check_authentication();
        if (!is_advanced_user()) {
            die(_('You do not have access to this page.'));
        }
    }

    // Check if automatic integretion exists
    $separate_ccm_login = get_option("separate_ccm_login", 0);

} else {
    session_start();
}

// Set the location of the CCM root directory
define('BASEDIR', dirname(__FILE__).'/');
require_once('includes/session.inc.php');

// Do session tracking / edit locking
if (ENVIRONMENT == "nagiosxi") {
    $obj_id = intval(grab_request_var('id', 0));
    $enable_locking = get_option('ccm_enable_locking', 1);

    // Do session tracking for page locks
    $ccm_session_id = session_tracking();

    // Check if there is currently a session on this page
    $lock = false;
    if ($enable_locking) {
        $lock = session_get_lock();
    }
}

ob_start();
print page_router();
$page_html = ob_get_clean();
ob_end_clean();

// Display page heading
if (ENVIRONMENT == "nagiosxi") {
    do_page_start(array("page_title" => _('CCM')), true);
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>CCM</title>
    <link rel='stylesheet' type='text/css' href='css/style_core.css?<?php echo VERSION; ?>' />
    <script type="text/javascript" src="javascript/jquery-1.7.2.min.js?<?php echo VERSION; ?>"></script>
    <script type="text/javascript" src="javascript/main_js.js?<?php echo VERSION; ?>"></script>
    <script type="text/javascript">
    var NAGIOSXI=<?php if (ENVIRONMENT == 'nagiosxi') { echo "true"; } else { echo "false"; } ?>;
    </script>
    <style type="text/css">
    #contentWrapper { margin: 0px auto; width: 80%; float: left; }
    </style>
</head>
<div id="whiteout"></div>
<div id="throbber" class="sk-spinner sk-spinner-center sk-spinner-three-bounce">
    <div class="sk-bounce1"></div>
    <div class="sk-bounce2"></div>l
    <div class="sk-bounce3"></div>
</div>
<body>
<?php
}

// Let's display the red asterik next to apply config if we have apply configuration needed
if (ENVIRONMENT == "nagiosxi") {
?>
<script type="text/javascript">
var CCM_SESSION_ID = <?php echo $ccm_session_id ? $ccm_session_id : 0; ?>;
var CCM_LOCK = <?php if (!empty($lock)) { echo json_encode($lock); } else { echo '{ }'; } ?>;

$(document).ready(function() {

    if (CCM_SESSION_ID) {

        $(window).on('beforeunload', function() {
            $.post('ajax.php', { cmd: 'removesession', ccm_session_id: CCM_SESSION_ID }, function(d) { });
        });

        // Update the session if user is just sitting on a page (or editing it)
        var update_id = setInterval(update_session_and_lock, 10000);

        check_page_usage();
    }

    $(window).resize(function() {
        $('#lock-notice').center().css('top', '250px');
    });

    $('#remove-lock').click(function() {
        $.post('ajax.php', { cmd: 'takelock', lock_id: CCM_LOCK.id, ccm_session_id: CCM_SESSION_ID }, function(d) {
            if (d.success) {
                CCM_LOCK = { }
                $('#lock-notice').hide();
                clear_whiteout();
            }
        }, 'json');
    });
});

function update_session_and_lock()
{
    // Update session and return lock values
    var vars = { cmd: 'updatesession', ccm_session_id: CCM_SESSION_ID, obj_id: <?php echo $obj_id; ?> };
    if (CCM_LOCK.id) {
        vars.lock_id = CCM_LOCK.id;
    }

    // Update session and get new lock if there is one
    $.post('ajax.php', vars, function(d) {
        if (d.has_new_lock) {
            CCM_LOCK = d.lock;
            $('.lock-text').html(d.locktext);
            check_page_usage();
        }
    }, 'json');
}

function check_page_usage()
{
    if (CCM_LOCK.id) {
        whiteout();
        $('#lock-notice').center().css('top', '250px').show();
    }
}

<?php
    $ac_needed = get_option("ccm_apply_config_needed", 0);
    if ($ac_needed == 1) {
        $cmd = grab_request_var('cmd', '');
?>
window.parent.$("#ccm-apply-menu-link").html('<span class="tooltip-apply" data-placement="right" title="<?php echo _("There are modifications to objects that have not been applied yet. Apply configuration for new changes to take affect."); ?>"><i class="fa fa-fw fa-asterisk urgent"></i> <?php echo _("Apply Configuration"); ?></span>');
window.parent.$('.tooltip-apply').tooltip({ template: '<div class="tooltip ccm-tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>', container: 'body' });

<?php if (empty($cmd)) { ?>
window.parent.$('#fullscreen').addClass('white');
window.parent.$('div#leftnav a').click(function() {
    window.parent.$('#fullscreen').removeClass('white');
    window.parent.$('div#leftnav a').unbind();
});
<?php
        }
    }
?>

</script>
<?php } ?>
    <div id="screen-overlay"></div>
    <div id="whiteout"></div>
    <div id="lock-notice" class="hide info-popup" style="text-align: center; padding: 25px;">
        <h4><i class="fa fa-exclamation-triangle" style="vertical-align: middle;"></i> <?php echo _('The page is currently being edited by another user.'); ?></h4>
        <div class="lock-text">
            <?php if (!empty($lock)) { ?>
            <b><?php echo $lock['username']; ?></b> <?php echo _('started editing at'); ?> <?php echo get_datetime_string($lock['started'], DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?>
            <?php } ?>
        </div>
        <div class="btns">
            <button type="button" id="remove-lock" class="btn btn-sm btn-danger"><?php echo _('Remove Lock'); ?></button>
            <a href="<?php echo grab_request_var('returnUrl', ''); ?>" class="btn btn-sm btn-default"><?php echo _('Cancel'); ?></a>
        </div>
    </div>
    <div id="loginMsgDiv" <?php if (ENVIRONMENT == "nagiosxi" && $separate_ccm_login == 0) { echo 'style="display: none;"'; } ?>>
        <span <?php if(!($_SESSION['loginStatus'] === false)) echo "class='deselect'"; ?>>
            <div <?php if($_SESSION['loginStatus'] === false) echo "class='error'"; ?>>
                <?php if (!empty($_SESSION['loginMessage'])) { echo $_SESSION['loginMessage']; } ?>
            </div>
        </span>
    </div>

<?php
// Display the actual page through the page router...
print $page_html;

// Create page ending based on environment
if (ENVIRONMENT == "nagiosxi") {
    do_page_end(true);
} else {
?>
</body>
</html>
<?php
}