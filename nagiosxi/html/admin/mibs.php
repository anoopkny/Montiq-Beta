<?php
//
// Copyright (c) 2011-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id: mibs.php 451 2011-01-13 18:04:47Z egalstad $

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
    global $request;

    if (isset($request["download"])) {
        do_download();
    } else if (isset($request["upload"])) {
        $processMib = false;
        if (isset($request["processMibCheck"]) && $request["processMibCheck"] == 'YES') {
            $processMib = true;
        }
        do_upload($processMib);
    } else if (isset($request["delete"])) {
        do_delete();
    } else {
        show_mibs();
    }

    exit;
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_mibs($error = false, $msg = "")
{
    $mibs = get_mibs();

    do_page_start(array("page_title" => _('Manage MIBs')), true);

    $users = array();
    $u = explode("\n", file_get_contents('/etc/passwd'));
    foreach ($u as $l) {
        if (!empty($l)) {
            $x = explode(":", $l);
            $users[$x[2]] = $x[0];
        }
    }

    $groups = array();
    $g = explode("\n", file_get_contents('/etc/group'));
    foreach ($g as $l) {
        if (!empty($l)) {
            $x = explode(":", $l);
            $groups[$x[2]] = $x[0];
        }
    }
?>

    <h1><?php echo _('Manage MIBs'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <p>
        <?php echo _('Manage the MIBs installed on this server. There are hundreds of mibs available at '); ?>
        <a href="http://www.mibdepot.com/" target="_blank" rel="noreferrer">mibdepot<i class="fa fa-external-link fa-ml"></i></a> <?php echo _('and'); ?> <a href="http://www.oidview.com/mibs/detail.html" target="_blank">oidview<i class="fa fa-external-link fa-ml"></i></a>.
    </p>

    <div class="well">
        <form enctype="multipart/form-data" action="" method="post" style="margin: 0;">
            <input type="hidden" name="upload" value="1">
            <?php echo get_nagios_session_protector(); ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="50000000">

            <div class="fl" style="height: 29px; line-height: 29px; margin-right: 10px; font-weight: bold; color: #666;">
                <label><?php echo _('Upload a MIB'); ?>:</label>
            </div>
            <div class="fl">
                <div class="input-group" style="width: 240px;">
                    <span class="input-group-btn">
                        <span class="btn btn-sm btn-default btn-file">
                            <?php echo _('Browse'); ?>&hellip; <input type="file" name="uploadedfile">
                        </span>
                    </span>
                    <input type="text" class="form-control" style="width: 200px;" readonly>
                </div>
            </div>
            <div class="fl">
                <div class="checkbox" style="margin: 3px 10px;">
                    <label>
                        <input type="checkbox" name="processMibCheck" value="YES">
                        <?php echo _("Process trap"); ?>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-sm btn-primary"><?php echo _('Upload MIB'); ?></button>
            <div class="clear"></div>
        </form>
    </div>

    <table class="table table-condensed table-striped table-bordered table-auto-width">
        <thead>
            <tr>
                <th><?php echo _('MIB'); ?></th>
                <th><?php echo _('File'); ?></th>
                <th><?php echo _('Owner'); ?></th>
                <th><?php echo _('Group'); ?></th>
                <th><?php echo _('Permissions'); ?></th>
                <th><?php echo _('Date'); ?></th>
                <th class="center"><?php echo _('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>

        <?php
        $x = 0;
        foreach ($mibs as $mib) {

            if (array_key_exists($mib['group'], $groups)) {
                $group = $groups[$mib["group"]];
                if ($group != 'nagios' && $group != 'nagcmd') {
                    $group = '<em>' . $group . '</em>';
                }
            } else {
                $group = $mib['group'];
            }

            if (array_key_exists($mib['owner'], $users)) {
                $user = $users[$mib["owner"]];
                if ($user != 'nagios' && $user != 'nagcmd') {
                    $user = '<em>' . $user . '</em>';
                }
            } else {
                $user = $mib['owner'];
            }

            echo "<tr>";
            echo "<td>" . $mib["mibname"] . "</td>";
            echo "<td>" . $mib["file"] . "</td>";
            echo "<td>" . $user . "</td>";
            echo "<td>" . $group . "</td>";
            echo "<td>" . $mib["permstring"] . "</td>";
            echo "<td>" . $mib["date"] . "</td>";
            echo '<td class="center">';
            echo "<a href='?download=" . $mib["file"] . "'><img src='" . theme_image("download.png") . "' class='tt-bind' alt='" . _('Download') . "' title='" . _('Download') . "'></a> ";
            echo "<a href='?delete=" . $mib["file"] . "&nsp=" . get_nagios_session_protector_id() . "'><img src='" . theme_image("cross.png") . "' class='tt-bind' alt='" . _('Delete') . "' title='" . _('Delete') . "'></a>";
            echo "</td>";
            echo "</tr>\n";
        }
        ?>

        </tbody>
    </table>

    <?php

    do_page_end(true);
    exit();
}


function do_download()
{

    $file = grab_request_var("download", "");


    // clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    $dir = get_mib_dir();
    $thefile = $dir . "/" . $file;

    $mime_type = "";
    header('Content-type: ' . $mime_type);
    header("Content-length: " . filesize($thefile));
    header('Content-Disposition: attachment; filename="' . basename($thefile) . '"');
    readfile($thefile);
    exit();
}

/**
 * @param $src
 * @param $dest
 * @param $message
 *
 * @return int
 */
function convert_mib($src, $dest, &$message)
{
    $rc = 0;
    $lrc = 0;
    $convertmib_location = '/usr/bin/snmpttconvertmib';
    $integration_path = "<a href='https://assets.nagios.com/downloads/nagiosxi/docs/Integrating_SNMP_Traps_With_Nagios_XI.pdf'  target='_blank'>here</a>";
    $locate_output = array();
    $convert_results = array();
    $matches = array();
    $success_string = array();

    // locate snmpttconvertmib
    $locate_convertmib = 'which snmpttconvertmib 2>&1';
    exec($locate_convertmib, $locate_output, $lrc);

    // construct and execute command if snmpttconvertmib is available
    if ($lrc != 0) {
        // snmpttconvertmib.py is missing or not found error code
        $rc = 4;
    } else {
        if (!empty($locate_output))    
            $convertmib_location = $locate_output[0];
        $convert_cmd = $convertmib_location . ' --in=' . escapeshellarg($src) . ' --out=' . escapeshellarg($dest);
        exec($convert_cmd, $convert_results, $rc);
    }

    if ($rc != 0) {
        if ($rc == 4) {
            // show location output error and link to integration script
            $message = _("snmpttconvertmib is not in the correct location or is not installed. If you have not run the Nagios XI SNMP Integration script it is located " . $integration_path . ". ") . " ( rc: " . $rc . " = > " . $locate_output[0] . " ) ";
        } else {
            $message = _("Failed to convert uploaded file to snmptt mib.") . " (rc: " . $rc . ")";
        }
    } else {
        foreach ($convert_results as $line) {
            if (preg_match('/Failed translations:\s*(\d+)/', $line, $matches) === 1) {
                if ($matches[1] > 0) {
                    $rc = 1;
                    $message = _("Uploaded file had one or more failed translations when converting to snmptt mib.");
                    break;
                }
            } else if (strpos($line, "translations:") !== false) {
                $success_string[] = $line;
            } else if (strpos($line, "MIB file did not contain any TRAP-TYPE or NOTIFICATION-TYPE") !== false) {
                $rc = 2;
                $success_string[] = "MIB is added, but is does not contain any TRAP-TYPE definitions, so no traps were added, even though user input said there should be traps specified.";
                break;
            }
        }

        if ($rc === 0 || $rc === 2) {
            $message = implode("<br />", $success_string);
        }
    }
    return $rc;
}


/**
 * @return string
 */
function get_snmptt_ini_path()
{
    return '/etc/snmp/snmptt.ini';
}


/**
 * @param $mib
 * @param $message
 *
 * @return int
 */
function add_mib_to_conf($mib, &$message)
{
    //add processed mib to snmptt.ini
    $sed_status = 0;
    $sed_results = array();
    $snmptt_ini_path = get_snmptt_ini_path();

    $grep_cmd = "grep $mib $snmptt_ini_path";
    exec($grep_cmd, $grep_arry, $rc);
    if ($rc == 0) {
        return 0; //already in file
    }

    $add_to_snmpttini_cmd = "/bin/sed -i '/^snmptt_conf_files/a" . $mib . "' " . $snmptt_ini_path;
    exec($add_to_snmpttini_cmd, $sed_results, $sed_status);
    if ($sed_status != 0) {
        $message = _("Failed to converted mib path to snmptt.ini") . ". (rc: " . $sed_status . ")";
    }

    return $sed_status;
}

/**
 * @param $mib
 * @param $message
 *
 * @return mixed
 */
function run_addmib($mib, &$message)
{
    //run addmib command if command exists
    $addmib = '/usr/local/bin/addmib';
    $success_output = array();

    if (is_executable($addmib)) {
        exec($addmib . " " . $mib, $addmib_result, $addmib_returncode);

        if ($addmib_returncode != 0) {
            // send error message from exec
            $message = implode("<br>", $addmib_result);
            return 1;
        } else {
            foreach ($addmib_result as $line) {
                if (strpos($line, "translations:") !== false) {
                    $success_output[] = $line;
                } else if (strpos($line, "MIB file did not contain any TRAP-TYPE or NOTIFICATION-TYPE") !== false) {
                    $success_output[] = "MIB is added, but it does not contain any TRAP-TYPE definitions, so no traps were added, even though user input said there should be traps specified.";
                    break;
                }
            }

            $message = implode("<br>", $success_output);
            return 0;
        }
    }

    // addmib not executable
    return 1;
}


/**
 * @param $message
 *
 * @return int
 */
function restart_snmptt(&$message)
{
    $cmd = 'sudo '.get_root_dir().'/scripts/manage_services.sh restart snmptt';
    $rc = 0;
    exec($cmd, $results, $rc);
    if ($rc != 0) {
        $message = _("Failed to restart snmptt service");
    }

    return $rc;
}


/**
 * @param $processMib
 */
function do_upload($processMib)
{

    // demo mode
    if (in_demo_mode() == true)
        show_mibs(true, _("Changes are disabled while in demo mode."));
    if ($processMib) {
        if (!file_exists(get_snmptt_ini_path())) {
            show_mibs(true, _('MIB could not be installed - snmptt is not installed.'));
            exit();
        } elseif (!is_writable(get_snmptt_ini_path())) {
            show_mibs(true, 'snmptt.ini ' . _('is not writable by Nagios.') . '<br>' . _('Run the following from the command line') . ':<br><br>chown root.nagios /etc/snmp/snmptt.ini /etc/snmp<br>
    chmod g+w /etc/snmp/snmptt.ini /etc/snmp');
            exit();
        }
    }

    // check session
    check_nagios_session_protector();

    //print_r($request);

    $target_path = get_mib_dir();
    $target_path .= "/";
    $target_path .= basename($_FILES['uploadedfile']['name']);

    //echo "TEMP NAME: ".$_FILES['uploadedfile']['tmp_name']."<BR>\n";
    $test_write = move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path);

    if (!$test_write) {
        // error
        show_mibs(true, _("MIB could not be installed - directory permissions may be incorrect."));
    } else if ($processMib) {
        chmod($target_path, 0664);
        // success!

        // process mib
        $convert_path = get_mib_dir();
        $convert_path .= "/processed_mibs/";

        // make directory if doesn't exist
        if (!is_dir($convert_path))
            mkdir($convert_path);

        $target_path_parts = pathinfo($target_path);
        $convert_path .= $target_path_parts['filename'] . '.txt';
        $convert_message = "";

        //run addmib if present
        $rc_add = run_addmib($target_path, $convert_message);

        //Process mib with convertmib if addmib isn't executable or isn't installed
        if ($rc_add != 0) {
            $rc = convert_mib($target_path, $convert_path, $convert_message);
            if ($rc !== 0) {
                if ($rc === 1) {
                    unlink($convert_path);
                }
                show_mibs(true, $convert_message);
            } else if (add_mib_to_conf($convert_path, $message) != 0) {
                unlink($convert_path);
                show_mibs(true, $message);
            } else {
                $result_message = _('MIB file successfully processed') . ": <br>";
                $result_message .= $convert_message;
                if (restart_snmptt($restart_message) != 0)
                    $result_message .= "<br>" . $restart_message;

                show_mibs(false, $result_message);
            }
        }

        $result_message = _('MIB file successfully processed') . ". <br>";
        $error = false;

        if ($convert_message != "") {
            $result_message = $convert_message;
            $error = true;
        }
        if (restart_snmptt($restart_message) != 0)
            $result_message .= "<br>" . $restart_message;

        show_mibs($error, $result_message);

    } else {
        // successfully uploaded file
        chmod($target_path, 0664);
        show_mibs(false, _("New MIB was installed successfully."));
    }

    exit();
}

function do_delete()
{


    // demo mode
    if (in_demo_mode() == true)
        show_mibs(true, _("Changes are disabled while in demo mode."));

    // check session
    check_nagios_session_protector();

    $file = grab_request_var("delete", "");

    // clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    $dir = get_mib_dir();
    $thefile = $dir . "/" . $file;

    if (unlink($thefile) === TRUE) {
        // success!
        show_mibs(false, _("MIB deleted."));
    } else {
        // error
        show_mibs(true, _("MIB delete failed - directory permissions may be incorrect."));
    }
}

?>