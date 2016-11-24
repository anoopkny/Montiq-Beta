<?php
//
// Unconfigured Objects
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id: main.php 370 2010-11-09 12:48:24Z egalstad $

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
    $delete = grab_request_var("delete");
    $configure = grab_request_var("configure");
    $purge = grab_request_var("purge");

    if ($delete == 1) {
        do_delete();
    } else if ($configure == 1) {
        do_configure();
    } else if ($purge == 1) {
        do_purge();
    } else {
        show_objects();
    }
}


function do_configure()
{
    
    $hosts = grab_request_var("host", array());
    $services = grab_request_var("service", array());

    // Check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    $wizard_url = "https://exchange.nagios.org/directory/Addons/Configuration/Configuration-Wizards/Unconfigured-Passive-Object-Wizard/details";

    // Check for errors
    if (in_demo_mode() == true) {
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    }
    if (count($hosts) == 0 && count($services) == 0) {
        $errmsg[$errors++] = _("No objects selected.");
    }
    if (!file_exists(get_base_dir() . "/includes/configwizards/passiveobject/passiveobject.inc.php")) {
        $errmsg[$errors++] = "You must install the unconfigured passive object wizard to configure the selected hosts and services.  You can get the wizard <a href='" . $wizard_url . "' target='_blank'>here</a>.";
    }

    if ($errors > 0) {
        show_objects(true, $errmsg);
    }

    $url = "../config/monitoringwizard.php?wizard=passiveobject&update=1&nextstep=3&nsp=" . get_nagios_session_protector_id();
    foreach ($hosts as $host_name => $id) {
        $url .= "&host[" . urlencode($host_name) . "]=1";
    }
    foreach ($services as $host_name => $service_name) {
        $url .= "&service[" . urlencode($host_name) . "]=" . urlencode($service_name);
    }

    header("Location: " . $url);
}


function do_delete()
{
    
    $hosts = grab_request_var("host", array());
    $services = grab_request_var("service", array());

    // Check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Check for errors
    if (in_demo_mode() == true) {
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    }
    if (count($hosts) == 0 && count($services) == 0) {
        $errmsg[$errors++] = "No objects selected";
    }

    if ($errors > 0) {
        show_objects(true, $errmsg);
    }

    // Load object file
    clearstatcache();
    $datas = file_get_contents(get_root_dir() . "/var/corelog.newobjects");
    $newobjects = unserialize($datas);

    // Delete hosts
    foreach ($hosts as $hn => $id) {
        send_to_audit_log("User deleted host '" . $hn . "' from unconfigured objects", AUDITLOGTYPE_DELETE);
        unset($newobjects[$hn]);
    }

    // Delete services
    foreach ($services as $hn => $sn) {
        send_to_audit_log("User deleted service '" . $sn . "' (on host '" . $hn . "') from unconfigured objects", AUDITLOGTYPE_DELETE);
        unset($newobjects[$hn]['services'][$sn]);

        // Did we delete all the services?
        if (count($newobjects[$hn]['services']) == 0) {
            unset($newobjects[$hn]['services']);
        }
    }

    file_put_contents(get_root_dir() . "/var/corelog.newobjects", serialize($newobjects));

    header("Location: ?deleted=1");
}


function do_purge()
{
    file_put_contents(get_root_dir() . "/var/corelog.newobjects", serialize(array()));
    show_objects(false, $msg = _("Deleted Objects List cleared successfully!"));
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_objects($error = false, $msg = "")
{

    $deleted = grab_request_var("deleted");
    $configured = grab_request_var("configured");

    $wizard_url = "https://exchange.nagios.org/directory/Addons/Configuration/Configuration-Wizards/Unconfigured-Passive-Object-Wizard/details";

    // Is the listener enabled? (as of 2011r2.3) -MG
    $listen = is_null(get_option('enable_unconfigured_objects')) ? true : get_option('enable_unconfigured_objects');
    $perflink = "<a href='performance.php' title='Performance Settings'>" . _("Performance Settings") . "</a>";

    if ($error == false && $deleted == 1) {
        $msg = _("Objects deleted.");
    }
    if (!$listen) {
        $error = true;
        $msg = _("Unconfigured objects listener is currently disabled.  This feature can be enabled from the ") . $perflink . _(" page by selecting the 'Subsystem' tab.");
    }
    if ($error == false && $configured == 1) {
        $msg = _("Objects configured.");
    }

    do_page_start(array("page_title" => _('Unconfigured Objects')), true);
?>

    <h1><?php echo _('Unconfigured Objects'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p>
        <?php echo _("This page shows host and services that check results have been received for, but which have not yet been configured in Nagios."); ?><br><?php echo _("Passive checks may be received by NSCA or NRDP (as defined in your"); ?>
        <a href="dtinbound.php"><?php echo _("inbound transfer settings"); ?></a>) <?php echo _("or through the direct check submission API."); ?>
    </p>
    <p>
        <?php echo _("You may delete unneeded host and services or add them to your monitoring configuration through this page. Note that a large amount of persistant unused passive checks can result in a performance decrease."); ?>
    </p>
    <p>
        <a href='missingobjects.php?purge=1' title='Clear Unconfigured Objects'><?php echo _("Clear Unconfigured Objects List"); ?></a>
    </p>

    <?php
    if (!file_exists(get_base_dir() . "/includes/configwizards/passiveobject/passiveobject.inc.php"))
        echo "<p><strong>Note:</strong> " . _("You must install the unconfigured passive object wizard to configure the selected hosts and services. You can get the wizard from") .
            " <a href='" . $wizard_url . "' target='_blank'>Nagios Exchange</a>.</p>";
    ?>

    <form method="get" action="">
        <?php echo get_nagios_session_protector(); ?>

        <script type="text/javascript">
            $(document).ready(function () {
                $("#checkall").click(function () {
                    var checked_status = this.checked;
                    $("input[type='checkbox']").each(function () {
                        this.checked = checked_status;
                    });
                });
            });
        </script>

        <table class="table table-condensed table-striped table-auto-width">
            <thead>
            <tr>
                <th><input type="checkbox" id="checkall"></th>
                <th><?php echo _("Host"); ?></th>
                <th><?php echo _("Service"); ?></th>
                <th><?php echo _("Last Seen"); ?></th>
                <th><?php echo _("Actions"); ?></th>
            </tr>
            </thead>
            <tbody>
                <?php
                $datas = null;
                clearstatcache();
                $f = get_root_dir() . "/var/corelog.newobjects";
                if (file_exists($f)) {
                    $datas = file_get_contents($f);
                }
                if ($datas == "" || $datas == null) {
                    $newobjects = array();
                } else {
                    $newobjects = @unserialize($datas);
                }

                $current_host = 0;
                $displayed = 0;

                foreach ($newobjects as $hn => $arr) {
                    //skip hidden (deleted) hosts
                    if (grab_array_var($arr, 'hide_all', false) == true)
                        continue;

                    $svcs = $arr["services"];
                    $hidden = grab_array_var($arr, 'hidden_services', array());
                    if (!is_array($hidden))
                        $hidden = array();

                    //refactor services array to handle hidden (deleted) items from the list
                    $show_svcs = array();
                    foreach ($svcs as $sn => $val) {
                        if (in_array($sn, $hidden))
                            continue;
                        $show_svcs[$sn] = $val;
                    }

                    //overwrite old array
                    $svcs = $show_svcs;
                    $total_services = count($svcs);

                    // skip if host/service already exists
                    if ($total_services == 0 && host_exists($hn) == true)
                        continue;
                    else if ($total_services > 0) {
                        $missing = 0;
                        foreach ($svcs as $sn => $sarr) {
                            if (service_exists($hn, $sn) == true || in_array($sn, $hidden)) //hide deleted services
                                continue;
                            $missing++;
                        }
                        if ($missing == 0)
                            continue;
                    }

                    $displayed++;

                    if ($current_host > 0)
                        echo "<tr><td colspan='5'></td></tr>";

                    // xxx recalculate the $total_services using the same logic as in the loop below  - submitted by forum user nagiosadmin42 - 10/22/2012
                    $total_services = 0;
                    foreach ($svcs as $sn => $sarr) {
                        if (service_exists($hn, $sn) == true || in_array($sn, $hidden))
                            continue;
                        $total_services++;
                    }

                    echo "<tr>";
                    echo "<td rowspan='" . ($total_services + 1) . "'><input type='checkbox' name='host[" . $hn . "]'></td>";
                    echo "<td rowspan='" . ($total_services + 1) . "'>" . $hn . "</td>";
                    echo "<td>-</td>";
                    echo "<td>" . get_datetime_string($arr["last_seen"]) . "</td>";
                    echo "<td>";
                    echo "<a href='?delete=1&amp;host[" . $hn . "]=1&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton' src='" . theme_image("b_delete.png") . "' border='0' alt='" . _("Delete") . "' title='" . _("Delete") . "'></a>";
                    echo "<a href='?configure=1&amp;host[" . $hn . "]=1&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton' src='" . theme_image("b_next.png") . "' border='0' alt='Configure' title='Configure'></a>";
                    echo "</td>";
                    echo "</tr>";

                    $svcs = $arr["services"];
                    if ($total_services > 0) {
                        foreach ($svcs as $sn => $sarr) {

                            if (service_exists($hn, $sn) == true || in_array($sn, $hidden))
                                continue;

                            echo "<tr>";
                            echo "<td>" . $sn . "</td>";
                            echo "<td>" . get_datetime_string($arr["last_seen"]) . "</td>";
                            echo "<td>";
                            echo "<a href='?delete=1&amp;service[" . $hn . "]=" . $sn . "&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton' src='" . theme_image("b_delete.png") . "' border='0' alt='" . _("Delete") . "' title='" . _("Delete") . "'></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    }

                    $current_host++;
                }
                if ($displayed == 0) {
                    echo "<tr><td colspan='5'>" . _("No unconfigured passive objects found") . ".</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="tableListMultiOptions">
            <?php echo _("With Selected:"); ?>
            <button class="tableMultiItemButton" title="<?php echo _('Remove'); ?>" value="1" name="delete" type="submit">
                <img src="<?php echo theme_image('cross.png'); ?>" class="tt-bind" title="<?php echo _('Remove'); ?>">
            </button>
            <button class="tableMultiItemButton" title="<?php echo _('Configure'); ?>" value="1" name="configure" type="submit">
                <img src="<?php echo theme_image('cog_go.png'); ?>" class="tt-bind" title="<?php echo _('Configure'); ?>">
            </button>
        </div>

    </form>

    <?php

    do_page_end(true);
    exit();
}