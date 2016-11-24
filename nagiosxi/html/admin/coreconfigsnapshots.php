<?php
//
// Core Config Snapshots
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}


route_request();


function route_request()
{
    global $request;

    if (isset($request["download"]))
        do_download();
    else if (isset($request["view"]))
        do_view();
    else if (isset($request["delete"]))
        do_delete();
    else if (isset($request["doarchive"]))
        do_archive();
    else if (isset($request["restore"]))
        do_restore();
    else if (isset($request["rename"]))
        do_rename();
    else
        show_log();
}


function show_log($error = false, $msg = "")
{

    $snapshots = get_nagioscore_config_snapshots();

    do_page_start(array("page_title" => _('Configuration Snapshots')), true);
?>

    <h1><?php echo _('Configuration Snapshots'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p><?php echo _('The latest configuration snapshots of the XI monitoring engine are shown below. Download the most recent snapshots as backups or get vital information for troubleshooting configuration errors.'); ?></p>

    <script type="text/javascript">
    function verify() {
        var answer = confirm("<?php echo _('Are you sure you want to restore the NagiosQL database?'); ?>");
        if (answer) {
            $("#childcontentthrobber").css("visibility", "visible");
            return true;
        }
        return false;
    }

    function verify_delete_archive() {
        var conf = confirm("<?php echo _('Are you sure you want to permanently delete this archived Configuration Snapshot?');?>");
        if (conf) {
            $("#childcontentthrobber").css("visibility", "visible");
            return true;
        }
        return false;
    }

    $(document).ready(function() {
        // View the config output
        $('.view').click(function () {
            var a = $(this);
            var ts = a.data('timestamp');
            var ar = a.data('archive');
            var res = a.data('result');

            whiteout();
            show_throbber();
            
            $.get('coreconfigsnapshots.php', { view: ts, archive: ar, result: res }, function (data) {
                var text_header = "<?php echo _('View Command Output'); ?>";
                var content = "<div id='popup_header' style='margin-bottom: 10px;'><b>" + text_header + "</b></div><div id='popup_data'></div>";
                content += "<div><textarea style='width: 600px; height: 240px;' class='code'>" + data + "</textarea></div>";

                hide_throbber();
                set_child_popup_content(content);
                display_child_popup();
            });
        });
    });
    </script>

    <div style="margin-top: 20px;">
        <h4><?php echo _("Recent Snapshots"); ?></h4>
        <table class="table table-striped table-condensed table-auto-width">
            <thead>
                <tr>
                    <th><?php echo _('Date'); ?></th>
                    <th><?php echo _('Snapshot Result'); ?></th>
                    <th><?php echo _('Filename'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $x = 0;
                $archives = 0;
                foreach ($snapshots as $snapshot) {

                    if ($snapshot["archive"]) {
                        $archives++;
                        continue;
                    }
                    $x++;

                    $resultstring = "Config Ok";
                    $rowclass = "";
                    $qstring = "result=ok&archive=0";
                    $result = 'ok';
                    if ($snapshot["error"] == true) {
                        $resultstring = "Config Error";
                        $rowclass = "alert";
                        $qstring = "result=error&archive=0";
                        $result = 'error';
                    }

                    if (($x % 2) != 0)
                        $rowclass .= " odd";
                    else
                        $rowclass .= " even";

                    echo "<tr class=" . $rowclass . ">";
                    echo "<td>" . $snapshot["date"] . "</td>";
                    echo "<td>" . $resultstring . "</td>";
                    echo "<td>" . $snapshot["file"] . "</td>";
                    echo '<td class="actions">';
                    echo "<a href='?download=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('package_go.png')."' class='tt-bind' title='"._('Download')."'></a>";
                    echo '<a class="view" data-timestamp="'. $snapshot["timestamp"] .'" data-result="'. $result .'" data-archive="0"><img src="'.theme_image('page_white.png').'" class="tt-bind" title="'._('View command output').'"></a>';
                    if ($snapshot["error"] == true) {
                        echo "<a href='?delete=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('cross.png')."' class='tt-bind' title='"._('Delete')."'></a>";
                    } else {
                        echo "<a href='?restore=" . $snapshot["timestamp"] . "&" . $qstring . "' onclick='return verify();'><img src='".theme_image('arrow_undo.png')."' class='tt-bind' title='"._('Restore')."'></a>";
                        echo "<a href='?doarchive=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('folder_go.png')."' class='tt-bind' title='"._('Archive')."'></a>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <?php
    if ($archives > 0) {
        ?>
        <h4><?php echo _("Archived Snapshots"); ?></h4>
        <table class="table table-striped table-condensed table-auto-width">
            <thead>
                <tr>
                    <th><?php echo _('Date'); ?></th>
                    <th><?php echo _('Snapshot Result'); ?></th>
                    <th><?php echo _('Filename'); ?></th>
                    <th><?php echo _('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>

            <?php
            $x = 0;
            foreach ($snapshots as $snapshot) {

                if ($snapshot["archive"] == false)
                    continue;

                $x++;

                $resultstring = "Config Ok";
                $rowclass = "";
                $qstring = "result=ok&archive=1";
                $result = 'ok';
                if ($snapshot["error"] == true) {
                    $resultstring = "Config Error";
                    $rowclass = "alert";
                    $qstring = "result=error&archive=1";
                    $result = 'error';
                }

                if (($x % 2) != 0)
                    $rowclass .= " odd";
                else
                    $rowclass .= " even";

                echo "<tr class=" . $rowclass . ">";
                echo "<td>" . $snapshot["date"] . "</td>";
                echo "<td>" . $resultstring . "</td>";
                echo "<td>" . $snapshot["file"] . "</td>";
                echo '<td class="actions">';

                // Remove the ending of filename
                $filename = str_replace(".tar.gz", "", $snapshot['file']);

                echo "<a href='?rename=" . $snapshot["timestamp"] . "&file=" . $filename . "&" . $qstring . "'><img src='".theme_image('pencil.png')."' class='tt-bind' title='"._('Rename')."'></a>";
                echo "<a href='?download=" . $filename . "&" . $qstring . "'><img src='".theme_image('package_go.png')."' class='tt-bind' title='"._('Download')."'></a>";
                echo '<a class="view" data-timestamp="'. $snapshot["timestamp"] .'" data-result="'. $result .'" data-archive="1"><img src="'.theme_image('page_white.png').'" class="tt-bind" title="'._('View command output').'"></a>';
                echo "<a href='?restore=" . $filename . "&" . $qstring . "' onclick='return verify();'><img src='".theme_image('arrow_undo.png')."' class='tt-bind' title='"._('Restore')."'></a>";
                echo "<a href='?delete=" . $filename . "&" . $qstring . "' onclick='return verify_delete_archive();'><img src='".theme_image('cross.png')."' class='tt-bind' title='"._('Delete')."'></a>";
            }
            echo "</td>";
            echo "</tr>";
            ?>

            </tbody>
        </table>

        <form method="post" action="" id="rename_form">
            <input type="hidden" name="">
        </form>

    <?php
    }

    do_page_end(true);
    exit();
}

function do_download()
{
    global $cfg;

    $result = grab_request_var("result", "ok");
    $ts = grab_request_var("download", "");
    $archive = grab_request_var("archive", 0);

    // Base checkpoints dir
    $dir = $cfg['nom_checkpoints_dir'];
    if ($archive == "1") {
        $dir .= "archives/";
    }
    if ($result == "error") {
        $dir .= "errors/";
    }

    // Clean the timestamp
    $ts = str_replace("..", "", $ts);
    $ts = str_replace("/", "", $ts);
    $ts = str_replace("\\", "", $ts);

    $thefile = $dir . $ts . ".tar.gz";

    header('Content-type: application/x-gzip');
    header("Content-length: " . filesize($thefile));
    header('Content-Disposition: attachment; filename="' . basename($thefile) . '"');
    readfile($thefile);
    exit();
}

function do_view()
{
    global $cfg;

    $result = grab_request_var("result", "ok");
    $ts = grab_request_var("view", "");
    $archive = grab_request_var("archive", 0);

    // base checkpoints dir
    $dir = $cfg['nom_checkpoints_dir'];
    if ($archive == "1")
        $dir .= "archives/";
    if ($result == "error")
        $dir .= "errors/";

    // clean the timestamp
    $ts = str_replace("..", "", $ts);
    $ts = str_replace("/", "", $ts);
    $ts = str_replace("\\", "", $ts);

    $thefile = $dir . $ts . ".txt";

    echo file_get_contents($thefile);
    exit();
}

function do_delete()
{

    // In demo mode
    if (in_demo_mode() == true) {
        show_log(true, _("Changes are disabled while in demo mode."));
    }

    $ts = grab_request_var("delete", "");
    $archived = grab_request_var("archive", 0);

    // Send to audit log
    send_to_audit_log(_("User deleted core config snapsnot") . " '" . $ts . "'", AUDITLOGTYPE_DELETE);

    // Clean the filename
    $ts = str_replace("..", "", $ts);
    $ts = str_replace("/", "", $ts);
    $ts = str_replace("\\", "", $ts);

    if ($ts == "") {
        show_log();
    }

    // Allow archived snapshot deletion
    if ($archived) {
        $id = submit_command(COMMAND_DELETE_ARCHIVE_SNAPSHOT, $ts);
    } else {
        $id = submit_command(COMMAND_DELETE_CONFIGSNAPSHOT, $ts);
    }

    if ($id <= 0) {
        show_log(true, _("Error submitting command."));
    } else {
        for ($x = 0; $x < 14; $x++) {
            $status_code = -1;
            $args = array(
                "cmd" => "getcommands",
                "command_id" => $id,
            );
            $xml = get_backend_xml_data($args);
            if ($xml) {
                if ($xml->command[0]) {
                    $status_code = intval($xml->command[0]->status_code);
                }
            }
            if ($status_code == 2) {
                show_log(false, _("Config snapshot deleted."));
                exit();
            }
            usleep(500000);
        }
    }
    show_log(false, _("Config snapshot deleted."));
    exit();
}

function do_archive()
{

    // In demo mode
    if (in_demo_mode() == true) {
        show_log(true, _("Changes are disabled while in demo mode."));
    }

    $ts = grab_request_var("doarchive", "");

    // Send it to the audit log
    send_to_audit_log(_("User archived core config snapsnot") . " '" . $ts . "'", AUDITLOGTYPE_DELETE);

    // Clean the filename
    $ts = str_replace("..", "", $ts);
    $ts = str_replace("/", "", $ts);
    $ts = str_replace("\\", "", $ts);

    if ($ts == "") {
        show_log();
    }

    $id = submit_command(COMMAND_ARCHIVE_SNAPSHOT, $ts);
    if ($id <= 0) {
        show_log(true, _("Error submitting command."));
    } else {
        for ($x = 0; $x < 14; $x++) {
            $status_code = -1;
            $args = array(
                "cmd" => "getcommands",
                "command_id" => $id,
            );
            $xml = get_backend_xml_data($args);
            if ($xml) {
                if ($xml->command[0]) {
                    $status_code = intval($xml->command[0]->status_code);
                }
            }
            if ($status_code == 2) {
                show_log(false, _('Snapshot archived'));
                exit();
            }
            usleep(500000);
        }
    }
    show_log(false, _('Snapshot scheduled for archiving'));
    exit();
}

function do_restore()
{
    global $cfg;

    // In demo mode
    if (in_demo_mode() == true) {
        show_log(true, _("Changes are disabled while in demo mode."));
    }

    $ts = grab_request_var("restore", "");
    $archive = grab_request_var("archive", 0);

    $baseurl = get_base_url();
    
    // Send to audit log
    send_to_audit_log(_("User restored system to config snapsnot") . " '" . $ts . "'", AUDITLOGTYPE_CHANGE);

    // Clean the filename
    $ts = str_replace("..", "", $ts);
    $ts = str_replace("/", "", $ts);
    $ts = str_replace("\\", "", $ts);

    if ($ts == "") {
        show_log();
    }

    if ($archive == 1) {
        $dir = $cfg['nom_checkpoints_dir'] . '/../nagiosxi/archives';
    } else {
        $dir = $cfg['nom_checkpoints_dir'] . '/../nagiosxi';
    }

    if (!file_exists($dir . "/" . $ts . "_nagiosql.sql.gz")) {
        show_log(true, "This snapshot doesn't exist");
        exit();
    }

    if ($archive == 1) {
        $id = submit_command(COMMAND_RESTORE_NAGIOSQL_SNAPSHOT, $ts . " restore archives");
    } else {
        $id = submit_command(COMMAND_RESTORE_NAGIOSQL_SNAPSHOT, $ts . " restore");
    }

    if ($id <= 0) {
        show_log(true, _("Error submitting command."));
    } else {
        for ($x = 0; $x < 14; $x++) {
            $status_code = -1;
            $args = array(
                "cmd" => "getcommands",
                "command_id" => $id,
            );
            $xml = get_backend_xml_data($args);
            if ($xml) {
                if ($xml->command[0]) {
                    $status_code = intval($xml->command[0]->status_code);
                }
            }
            if ($status_code == 2) {
                show_log(false, "NagiosQL Snapshot Restored.</br><strong><a href='" . nagioscorecfg_get_component_url(true) . "applyconfig.php?cmd=confirm'>" . _("Apply Configuration") . "</a></strong> &nbsp;<a href='" . $baseurl . "includes/components/ccm/xi-index.php' target='_top'>View Config</a>");
                exit();
            }
            usleep(500000);
        }
    }
    show_log(false, _("Configure snapshot restore has been scheduled."));
    exit();
}

// Rename an archived snapshot
function do_rename()
{

    $ts = grab_request_var("rename", "");
    $file = grab_request_var("file", "");
    $new_name = grab_request_var("new_name", "");
    $cancel = grab_request_var("cancel", 0);

    if ($ts == '' || $file == '' || $cancel) {
        show_log();
    }

    if (!$new_name) {

        // Display the rename form

        do_page_start(array("page_title" => _('Monitoring Configuration Snapshots')), true);

        echo "<h1>" . _('Monitoring Configuration Snapshots') . "</h1>";
        echo '<p>' . _('Rename an archived configuration snapshot. Archived snapshots must have no spaces in their names.') . '</p>';

        ?>
        <table class="table table-striped table-condensed table-auto-width">
            <thead>
                <tr>
                    <th><?php echo _('Date'); ?></th>
                    <th><?php echo _('Filename'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo date("Y-m-d H:i:s", $ts); ?></td>
                    <td><?php echo $file . ".tar.gz"; ?></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php echo _('Rename Snapshot To'); ?></h4>
        <form method="post">
            <p style="margin-bottom: 30px;"><input type="text" class="form-control" name="new_name"> .<?php echo $ts; ?>.tar.gz</p>
            <button type="submit" class="btn btn-sm btn-primary"><?php echo _('Rename'); ?></button>
            <button type="submit" class="btn btn-sm btn-default" name="cancel" value="1"><?php echo _('Cancel'); ?></button>
        </form>

        <?php
        do_page_end(true);

    } else {

        //
        // RENAME THE ARCHIVED SNAPSHOT
        //

        // Actually set the name!
        $command_data = array();
        $command_data[0] = str_replace(".tar.gz", "", $file);
        $command_data[1] = $new_name . "." . $ts;
        $command_data = serialize($command_data);

        // Send command to the subsystem
        $id = submit_command(COMMAND_RENAME_ARCHIVE_SNAPSHOT, $command_data);

        if ($id <= 0)
            show_log(true, _("Error submitting command."));
        else {
            for ($x = 0; $x < 14; $x++) {
                $status_code = -1;
                $args = array(
                    "cmd" => "getcommands",
                    "command_id" => $id,
                );
                $xml = get_backend_xml_data($args);
                if ($xml) {
                    if ($xml->command[0]) {
                        $status_code = intval($xml->command[0]->status_code);
                    }
                }
                if ($status_code == 2) {
                    show_log(false, _("Snapshot has been renamed."));
                    exit();
                }
                usleep(500000);
            }
        }
        show_log(false, _("Snapshot scheduled to be renamed."));
        exit();
    }
}