<?php
//
// NRDS Config Manager
// Written by: Scott Wilkerson (nagios@nagios.org)
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// 

require_once(dirname(__FILE__) . '/../../common.inc.php');
require_once('/usr/local/nrdp/server/config.inc.php');

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
    $mode = grab_request_var('mode');

    switch ($mode) {
        // not currently used
        case "download":
            do_download();
            break;
        case "upload":
            do_upload();
            break;
        case "delete":
            do_delete();
            break;
        case "Save":
            do_save(false);
            break;
        case "Apply":
            do_save(true);
            break;
        case "Cancel":
            show_configs();
            break;
        case "next":
            do_edit();
            break;
        case "create":
            do_create();
            break;
        case "edit":
            do_edit();
            break;
        /*
        // maybe this will be possible in the future, but Apache doesn't have write access to the correct directories
        case "install":
            do_install();
            break;
        */
        default:
            show_configs();
            break;
    }
}

function show_configs($error = false, $msg = "")
{
    global $request;
    global $cfg;

    $componentbase = dirname(__FILE__);

    // Check to see if NRDS has installed
    if (!file_exists("$componentbase/installed.nrds")) {
        $error = true;
        $msg .= "You must run the following as root to complete the install.";
        do_page_start(array("page_title" => _("NRDS Config Manager")), true);
        echo "<h1>"._('NRDS Config Manager')."</h1>";
        display_message($error, false, $msg);
        echo "<pre align='left'>cd $componentbase 
chmod +x install.sh
./install.sh</pre><br/>";
        do_page_end(true);
        exit();
    }

    // Check that NRDP is configured with token
    if (!isset($cfg['authorized_tokens'][0])) {
        $error = true;
        $msg .= "" . _('You need to configure the') . " <a href='" . $cfg['base_url'] . "/admin/dtinbound.php'>NRDP server</a> <br>" . _('before you can create a NRDS config') . ".<br/><br/>";
    }

    // Verify product versions
    if ($cfg['product_version'] < 1.2) {
        $error = true;
        $msg .= _('You must have the version 1.2 or greater of the NRDP Server before you can create a NRDS config') . ".<br/><br/>";
    }

    $templates = get_nrds_configs();
    do_page_start(array("page_title" => _("NRDS Config Manager")), true);
?>

    <script type="text/javascript">
    function displayInstructions(url, config, os) {
        $('.instructionConfigName').html(config);
        if (os.substr(0, 7) == "Windows") {

            var arch = os.substr(9, 6);
            if (arch == "32-bit") {
                $('.instructionURLWindows').html("<a href=\"" + url + "\" target=\"_blank\">NRDS_Win_32.exe</a>");
            }
            if (arch == "64-bit") {
                $('.instructionURLWindows').html("<a href=\"" + url + "\" target=\"_blank\">NRDS_Win_64.exe</a>");
            }

            $('#instructiondivUnix').slideUp('slow');
            $('#instructiondivWindows').slideDown('slow');

        } else {
            $('.instructionURLUnix').html(url);
            $('#instructiondivWindows').slideUp('slow');
            $('#instructiondivUnix').slideDown('slow');
        }
    }
    function closeInstructions() {
        $('#instructiondivUnix').slideUp('slow');
        $('#instructiondivWindows').slideUp('slow');
    }
    </script>

    <style type="text/css">
    #instructiondivUnix, #instructiondivWindows { padding: 0px; margin-top: 10px; font-size: 10px; display: none; }
    div.right { width: 100%; text-align: right; }
    div.leftInstructions { float: left; width: 10px; }
    div.rightInstructions { float: left; border: 1px solid gray; padding: 3px; }
    div.smallPadding { padding: 5px; }
    </style>

    <h1><?php echo _('NRDS Config Manager'); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <div id="instructiondivUnix">
        <div><a href="#" onclick="closeInstructions();return false;">(x) <?php echo _("close"); ?></a></div>
        <div class='smallPadding'>
            <p><?php echo _("The following commands can be run as root on all clients that will use the"); ?>
                <b><span class='instructionConfigName'></span></b> <?php echo _("config"); ?>.</p>

            <p><?php echo _("The install process will perform the following operations"); ?>:</p>
            <ul>
                <li><?php echo _("Install NRDS client"); ?></li>
                <li><?php echo _("Add a nagios user and group"); ?></li>
                <li><?php echo _("Add cron job to process checks"); ?></li>
                <li><?php echo _("Download plugins from the NRDP server"); ?></li>
            </ul>
            <p><?php echo _("There are 2 items you need to modify below"); ?>,
                <b>HOSTNAME</b> <?php echo _("and"); ?> <b>INTERVAL</b>.<br/>
                <b>HOSTNAME</b>
                - <?php echo _("The name the client will send to the Nagios server as the host"); ?>.<br/>
                <b>INTERVAL</b>
                - <?php echo _("The frequency in minutes that you want the checks to be run. (1-59)"); ?></p>
        </div>
        <div class='rightInstructions'>
            cd /tmp<br/>
            wget -O <span class='instructionConfigName'></span>.tar.gz "<span class='instructionURLUnix'></span>"<br/>
            gunzip -c <span class='instructionConfigName'></span>.tar.gz | tar xf -<br/>
            cd clients<br/>
            ./installnrds <b>HOSTNAME INTERVAL</b><br/>
        </div>
        <br clear="all">
        <br clear="all">
    </div>
    <div id="instructiondivWindows">
        <div><a href="#" onclick="closeInstructions();return false;">(x) <?php echo _("close"); ?></a></div>
        <div class='smallPadding'>
            <p><?php echo _("Download the installation utility using the following URL and install it as administrator for each Windows client"); ?>
                .</p>

            <p><span class='instructionURLWindows'></span></p>

            <p><?php echo _("The NRDS performs the following actions on the Windows host"); ?></p>
            <ul>
                <li><?php echo _("Install NRDS client"); ?></li>
                <li><?php echo _("Create scheduled task"); ?></li>
                <li><?php echo _("Download plugins from NRDP server"); ?></li>
                <li><?php echo _("Send initial check results to Nagios"); ?></li>
            </ul>
            <p><?php echo _("The first time NRDS runs, agent plugins will be downloaded from the NRDS server"); ?>
                .</p>
        </div>
        <br clear="all">
        <br clear="all">
    </div>


    <p><a href="?mode=create" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> <?php echo _("Create Config"); ?></a></p>

    <table class="table table-condensed table-striped table-auto-width">
        <thead>
            <tr>
                <th><?php echo _('Config Name'); ?></th>
                <th><?php echo _('Directory'); ?></th>
                <th><?php echo _('Owner'); ?></th>
                <th><?php echo _('Group'); ?></th>
                <th><?php echo _('Permissions'); ?></th>
                <th><?php echo _('Last Changed'); ?></th>
                <th><?php echo _('Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $x = 0;
            if (!empty($templates)) {
                foreach ($templates as $template) {
                    $x++;
                    $template_name = substr($template["file"], 0, -4);
                    echo "<tr>";
                    echo "<td>" . $template_name . "</td>";
                    echo "<td>" . $template["dir"] . "</td>";
                    echo "<td>" . $template["owner"] . "</td>";
                    echo "<td>" . $template["group"] . "</td>";
                    echo "<td>" . $template["permstring"] . "</td>";
                    echo "<td>" . $template["date"] . "</td>";
                    echo "<td class='actions'>";
                    echo "<a href='?mode=edit&edit=" . urlencode($template_name) . "&dir=" . urlencode($template["dir"]) . "'><img src='" . theme_image("editfile.png") . "' alt='" . _('Edit') . "' class='tt-bind' title='" . _('Edit') . "'></a>";
                    echo "<a href='#' onclick='displayInstructions(\"" . get_client_download_url($template_name, $template["dir"]) . "\",\"" . $template_name . "\",\"" . get_config_os($template["dir"] . "/" . $template["file"]) . "\");return false;'><img src='" . theme_image("detail.png") . "' alt='"._('Client install instructions')."' class='tt-bind' title='"._('Client install instructions')."'></a>";
                    echo "<a href='" . get_client_download_url($template_name, $template["dir"]) . "'><img src='" . theme_image("download.png") . "' alt='" . _('Download client') . " Client' class='tt-bind' title='" . _('Download client') . "'></a>";
                    echo "<a href='?mode=delete&delete=" . urlencode($template_name) . "&dir=" . urlencode($template["dir"]) . "&nsp=" . get_nagios_session_protector_id() . "'><img src='" . theme_image("cross.png") . "' class='tt-bind' alt='" . _('Delete') . "' title='" . _('Delete') . "'></a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="7">'._('No configurations have been created.').'</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <p style="width: 40%; min-width: 600px;">
        <?php echo _("This component allows administrators to manage Nagios Remote Data Sender (NRDS) config files to be distributed to remote clients. The clients will process the checks passively at the interval specified when installed. Any modifications to the config will be picked up by the clients using that configuration. Additionally any plugins needed by the remote machine will be downloaded every time the configuration changes"); ?>.
    </p>
    <p style="width: 40%; min-width: 600px;">
        <?php echo _("Once the client starts sending results, if the host/service has not been configured yet it will be found in"); ?> <a
            href="<?php echo $cfg['base_url']; ?>/admin/missingobjects.php"><?php echo _("Unconfigured Objects"); ?></a> <?php echo _("and can easily be added to the monitoring config."); ?>
    </p>

    <?php
    do_page_end(true);
    exit();
}


function do_create($error = false, $msg = "")
{
    global $request;
    global $cfg;

    $oses = array("AIX", "Linux", "Mac OS X", "Solaris", "Windows (32-bit)", "Windows (64-bit)");
    $configvar = grab_request_var("configvar", "");
    $configvar["CONFIG_OS"] = grab_array_var($configvar, "CONFIG_OS", "Linux");

    do_page_start(array("page_title" => _("Create NRDS Config")), true);
?>

    <h1><?php echo _("Create NRDS Config"); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <script type="text/javascript">
    $(document).ready(function () {
        $('input[name="configvar[CONFIG_OS]"]').focus();
    });
    </script>

    <form enctype="multipart/form-data" action="" method="post">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="mode" value="next">

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td><label><?php echo _("Operating System"); ?></label></td>
                <td>
                    <select name="configvar[CONFIG_OS]" class="form-control">
                        <?php
                        foreach ($oses as $os) {
                            echo "<option value='$os'";
                            if (trim($configvar["CONFIG_OS"]) == $os) echo "selected";
                            echo ">$os</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>

        <button type="submit" class="btn btn-sm btn-primary"><?php echo _('Next'); ?> <i class="fa fa-chevron-right"></i></button>
        <a href="nrdsconfigmanager.php" class="btn btn-sm btn-default"><?php echo _('Cancel'); ?></a>
    </form>

    <?php

    do_page_end(true);
    exit();
}

function do_edit($error = false, $msg = "")
{
    global $request;
    global $cfg;

    $configvar = grab_request_var("configvar", "");
    if (grab_array_var($configvar, "CONFIG_NAME", "") != "") {
        $file = $configvar["CONFIG_NAME"] . ".cfg";
    } else {
        $file = grab_request_var("edit", "") . ".cfg";
    }
    $tdir = grab_request_var("dir", "configs");

    // Clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // Clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_nrds_config_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    /*
     * OS is only declared for the first run through as it is a variable
     * that is passed to this page. If you are editing a file, you cannot
     * access the $os variable here, you will be able to access it after
     * the file gets parsed in the if block below.
     */
    $os = grab_array_var($configvar, "CONFIG_OS", "Linux");

    switch ($os) {
        case "Linux":
        case "Mac OS X":
            $nagiosdir = "/usr/local/nagios";
            $nrdpdir = "/usr/local/nrdp";
            break;
        case "AIX";
        case "HP-UX";
        case "Solaris";
            $nagiosdir = "/opt/nagios";
            $nrdpdir = "/opt/nagios/nrdp";
            break;
    }

    // Read file
    if (file_exists($thefile) && $file != "") {
        $handle = @fopen($thefile, "r");
        $os = get_config_os("$tdir/$file");
        if ($handle) {
            $configvar["commands"] = "";
            while (($buffer = fgets($handle, 4096)) !== false) {
                if (($buffer[0] != "#" && $buffer[0] != "#") && strpos($buffer, '=') !== false) {
                    if (substr($buffer, 0, 7) == "command" || preg_match('/^[a-zA-Z0-9 %]+\|[a-zA-Z0-9 %]+/', $buffer)) {
                        if (substr($os, 0, 7) == "Windows") {
                            $buffer = str_replace("\r\n", "\n", $buffer);
                        }
                        $configvar["commands"] .= $buffer;
                    } else {
                        $tmpvar = explode("=", $buffer);
                        $configvar[$tmpvar[0]] = trim($tmpvar[1], "\" \t\n\r\0\x0B");
                    }
                }
            }
        }
        fclose($handle);
    } else {

        // These are the defaults used to create a new config
        $configvar["CONFIG_VERSION"] = 0.0;
        $configvar["CONFIG_NAME"] = "";

        $configvar["URL"] = "http://SERVER_NAME/nrdp/";
        $configvar["URL"] = str_replace("nagiosxi", "nrdp", get_base_url());
        if ($cfg["require_https"] == true) {
            $configvar["URL"] = str_replace('http:', 'https:', $configvar["URL"]);
        }

        $configvar["TOKEN"] = "";
        $configvar["HOSTNAME"] = "";
        $configvar["LOG_FILE"] = "";
        $configvar["UPDATE_CONFIG"] = 1;
        $configvar["UPDATE_PLUGINS"] = 1;

        if (substr($os, 0, 7) == "Windows") {
            $configvar["PLUGIN_DIR"] = "C:\\Program Files\\Nagios\\NRDS_Win\\plugins";
            $configvar["HOSTNAME"] = "";
            $configvar["LOG_FILE"] = "C:\\Program Files\\Nagios\\NRDS_Win\\logs\\NRDS_Debug.log";
            $configvar["IGNORE_SSL_CERTIFICATE_ERRORS"] = "1";
            $configvar["COMMAND_PREFIX"] = "";
            $configvar["SEND_NRDP"] = "";
            $configvar["TMPDIR"] = "";
            $configvar["commands"] = 'command[__HOST__] = $PLUGIN_DIR$\\check_winping.exe -H 127.0.0.1 --warning 200,40% --critical 400,80%' . "\n";
            $configvar["commands"] .= 'command[nrpe_winprocess] = $PLUGIN_DIR$\\check_winprocess.exe --warning 40 --critical 50' . "\n";
            $configvar["commands"] .= 'command[nrpe_process] = $PLUGIN_DIR$\\cpuload_nrpe_nt.exe 70 90' . "\n";
            $configvar["commands"] .= 'command[nrpe_diskspace] = $PLUGIN_DIR$\\check_pdm.exe --disk --drive C: -w 97.5 -c 99.5' . "\n";
            $configvar["commands"] .= 'command[nrpe_eventlog] = $PLUGIN_DIR$\\eventlog_nrpe_nt.exe -m 7200' . "\n";
            $configvar["commands"] .= 'command[nrpe_memload] = $PLUGIN_DIR$\\check_pdm.exe --memory -w 90 -c 99' . "\n";
            $configvar["commands"] .= 'command[nrpe_physical_mem] = $PLUGIN_DIR$\check_pdm.exe --memory pagefile -w 80 -c 95' . "\n";
            $configvar["commands"] .= 'command[nrpe_service] = $PLUGIN_DIR$\\service_nrpe_nt.exe "DNS Client"' . "\n";
        } else if ($os === "NCPA") {
            $configvar["PLUGIN_DIR"] = "plugins/";
            $configvar["LOG_FILE"] = "BUILTIN";
            $configvar["COMMAND_PREFIX"] = "UNUSED";
            $configvar["SEND_NRDP"] = "BUILTIN";
            $configvar["TMPDIR"] = "BUILTIN";
            $configvar["commands"] = "%HOSTNAME%|CPU Usage = api/cpu/percent --warning 20 --critical 30\n";
            $configvar["commands"] .= "%HOSTNAME%|Memory Usage = api/memory/virtual/percent --warning 60 --critical 80\n";
            $configvar["commands"] .= "%HOSTNAME%|Swap Usage = api/memory/swap/percent --warning 40 --critical 80\n";
        } else {
            $configvar["PLUGIN_DIR"] = $nagiosdir . "/libexec";
            $configvar["COMMAND_PREFIX"] = "";
            $configvar["SEND_NRDP"] = $nrdpdir . "/clients/send_nrdp.sh";
            $configvar["TMPDIR"] = $nrdpdir . "/clients/tmp";
            $configvar["commands"] = "command[__HOST__]=" . $nagiosdir . "/libexec/check_ping -H localhost -w 200.0,40% -c 400.0,80% -p 1\n";
            $configvar["commands"] .= "command[Check Users]=" . $nagiosdir . "/libexec/check_users -w 5 -c 10 \n";
            $configvar["commands"] .= "command[Check Load]=" . $nagiosdir . "/libexec/check_load -w 15,10,5 -c 30,25,20\n";
            $configvar["commands"] .= "command[Check Disk]=" . $nagiosdir . "/libexec/check_disk -w 20% -c 10% -p /\n";
            switch ($os) {
                case "Linux":
                case "Mac OS X":
                    $configvar["commands"] .= "command[Check Zombie Procs]=" . $nagiosdir . "/libexec/check_procs -w 5 -c 10 -s Z\n";
                    $configvar["commands"] .= "command[Check Total Procs]=" . $nagiosdir . "/libexec/check_procs -w 150 -c 200\n";
                    break;
                case "Solaris":
                    $configvar["commands"] .= "command[Check Total Procs]=" . $nagiosdir . "/libexec/custom_check_procs -w 150 -c 200\n";
                    break;
                case "AIX":
                case "HP-UX":
                    break;
            }
        }
    }

    // Check that NRDP is configured with token
    if (!isset($cfg['authorized_tokens'][0])) {
        $error = true;
        $msg = "" . _('You need to configure the') . " <a href='" . $cfg['base_url'] . "/admin/dtinbound.php'>NRDP server</a> <br>" . _('before you can create a NRDS config') . ".<br/><br/>";
    }

    // Check product versions
    if ($cfg['product_version'] < 1.2) {
        $error = true;
        $msg .= "" . _('You must have the version 1.2 or greater of the NRDP Server before you can create a NRDS config') . ".<br/><br/>";
    }
    do_page_start(array("page_title" => _("Edit NRDS Config")), true);
?>

    <h1><?php echo _("Edit NRDS Config"); ?></h1>

    <?php display_message($error, false, $msg); ?>

    <script type="text/javascript">
    $(document).ready(function () {
        $('input[name="configvar[CONFIG_NAME]"]').focus();
    });
    </script>

    <form enctype="multipart/form-data" action="" method="post">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="dir" value="<?php echo htmlentities($tdir); ?>">
        <input type="hidden" name="file" value="<?php echo htmlentities($file); ?>">
        <input type="hidden" name="configvar[CONFIG_VERSION]" value="<?php echo htmlentities($configvar["CONFIG_VERSION"] + 0.1); ?>">
        <input type="hidden" name="configvar[CONFIG_OS]" value="<?php echo htmlentities($configvar["CONFIG_OS"]); ?>">

        <div class="sectionTitle"><?php echo _("Main Config"); ?></div>
        <p><?php echo _("URL is the NRDP URL on this server. The URL must be reachable by the client."); ?></p>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <strong> <?php echo _("VERSION"); ?>:</strong>
                </td>
                <td>
                    <strong><?php echo htmlentities($configvar["CONFIG_VERSION"]); ?></strong>
                </td>
            </tr>
            <tr>
                <td>
                    <label>CONFIG_NAME</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[CONFIG_NAME]" value="<?php echo htmlentities($configvar["CONFIG_NAME"]); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>URL</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[URL]" value="<?php echo htmlentities($configvar["URL"]); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>TOKEN</label>
                </td>
                <td>
                    <select name="configvar[TOKEN]" class="form-control">
                        <?php
                        foreach ($cfg['authorized_tokens'] as $token) {
                            echo "<option value='$token'";
                            if (trim($configvar["TOKEN"]) == $token) echo "selected";
                            echo ">$token</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>

        <div class="sectionTitle"><?php echo _('Commands'); ?></div>
        <p>
            <?php if ($os === 'NCPA') : ?>
                (One per line) format:<br/>
                %HOSTNAME%|SERVICE NAME = /command/definition Any Amount Of Args
            <?php else : ?>
                (One per line) format: <br/>
                command[SERVICE_NAME]=/path/to/check_plugin ARGS
            <?php endif; ?>
        </p>
        <div>
            <textarea class="form-control" style="width: 50%; min-width: 600px; height: 160px; font-family: consolas, courier new; font-size: 1.2rem; line-height: 1.5rem;" name="configvar[commands]" wrap="off"><?php echo htmlentities($configvar["commands"]); ?></textarea>
        </div>

        <div class="sectionTitle"><?php echo _("Additional Settings"); ?> </div>
        <p><?php echo _("These items are for advanced configurations and aren't normally changed."); ?></p>

        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <label>PLUGIN_DIR</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[PLUGIN_DIR]" value="<?php echo htmlentities($configvar["PLUGIN_DIR"]); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label>SEND_NRDP</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[SEND_NRDP]" value="<?php echo htmlentities($configvar["SEND_NRDP"]); ?>" <?php disable_if_reserved($configvar["SEND_NRDP"]); ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label>TMPDIR</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[TMPDIR]" value="<?php echo htmlentities($configvar["TMPDIR"]); ?>" <?php disable_if_reserved($configvar["TMPDIR"]); ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label>COMMAND_PREFIX</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[COMMAND_PREFIX]" value="<?php echo htmlentities($configvar["COMMAND_PREFIX"]); ?>" <?php disable_if_reserved($configvar["COMMAND_PREFIX"]); ?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label>LOG_FILE</label>
                </td>
                <td>
                    <input type="text" size="30" class="form-control" name="configvar[LOG_FILE]" value="<?php echo htmlentities($configvar["LOG_FILE"]); ?>" <?php disable_if_reserved($configvar["LOG_FILE"]); ?>>
                </td>
            </tr>

            <?php if (substr($os, 0, 7) == "Windows") { ?>
                <tr>
                    <td>
                        <label>IGNORE_SSL_CERTIFICATE_ERRORS</label>
                    </td>
                    <td>
                        <select name="configvar[IGNORE_SSL_CERTIFICATE_ERRORS]" class="form-control">
                            <option value="1" <?php if ($configvar["IGNORE_SSL_CERTIFICATE_ERRORS"] == 1) echo "selected"; ?>><?php echo _('Yes'); ?></option>
                            <option value="0" <?php if ($configvar["IGNORE_SSL_CERTIFICATE_ERRORS"] == 0) echo "selected"; ?>><?php echo _('No'); ?></option>
                        </select>
                    </td>
                </tr>
            <?php } ?>

            <tr>
                <td>
                    <label>UPDATE_CONFIG</label>
                </td>
                <td>
                    <select name="configvar[UPDATE_CONFIG]" class="form-control">
                        <option value="1" <?php if ($configvar["UPDATE_CONFIG"] == 1) echo "selected"; ?>><?php echo _('Yes'); ?></option>
                        <option value="0" <?php if ($configvar["UPDATE_CONFIG"] == 0) echo "selected"; ?>><?php echo _('No'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <label>UPDATE_PLUGINS</label>
                </td>
                <td>
                    <select name="configvar[UPDATE_PLUGINS]" class="form-control">
                        <option value="1" <?php if ($configvar["UPDATE_PLUGINS"] == 1) echo "selected"; ?>><?php echo _('Yes'); ?></option>
                        <option value="0" <?php if ($configvar["UPDATE_PLUGINS"] == 0) echo "selected"; ?>><?php echo _('No'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <input type="submit" class="submitbutton btn btn-sm btn-primary" name="mode" value="Save">
        <input type="submit" class="submitbutton btn btn-sm btn-info" name="mode" value="Apply">
        <input type="submit" class="submitbutton btn btn-sm btn-default" name="mode" value="Cancel">
    </form>

    <?php

    do_page_end(true);
    exit();
}

function do_save($apply = false)
{
    global $cfg;
    global $request;

    // In demo mode
    if (in_demo_mode() == true) {
        show_configs(true, _("Changes are disabled while in demo mode."));
    }

    // Check session
    check_nagios_session_protector();

    $tdir = grab_request_var("dir", "configs");
    $configvar = grab_request_var("configvar", "");
    if (grab_array_var($configvar, "CONFIG_NAME", "") == '') {
        $msg = "CONFIG_NAME cannot be empty";
        $error = true;
        do_edit($error, $msg);
    } elseif (grab_array_var($configvar, "URL", "") == '') {
        $msg = "URL cannot be empty";
        $error = true;
        do_edit($error, $msg);
    }

    $eol = "\n";
    if (substr($configvar["CONFIG_OS"], 0, 7) == "Windows") $eol = "\r\n";

    $file = $configvar["CONFIG_NAME"] . ".cfg";
    $file_content = "";

    if ($configvar["CONFIG_OS"] === 'NCPA') {
        make_ncpa_config($file_content, $configvar);
    } else {
        $command_content = "";
        foreach ($configvar as $key => $value) {
            if ($key != "commands") {
                if (substr($configvar["CONFIG_OS"], 0, 7) == "Windows") {
                    $file_content .= "$key=" . trim($value) . $eol;
                } else {
                    $file_content .= "$key=\"" . trim($value) . '"' . $eol;
                }
            } else {
                $command_content .= trim($value) . $eol;
                if (substr($configvar["CONFIG_OS"], 0, 7) == "Windows") {
                    $command_content = str_replace("\n", $eol, $command_content);
                }
            }
        }
        $file_content = $file_content . $eol . $eol . $command_content . $eol . $eol;
    }

    // Clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // Clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_nrds_config_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    $result = file_put_contents($thefile, str_replace("\r", "", $file_content));
    chmod($thefile, 0660);

    chgrp($thefile, filegroup(dirname($thefile)));
    if ($result === FALSE) {
        $msg = _("Error writing to file.") . " " . $thefile;
        $error = true;
    } else {
        $msg = _("File saved successfully.");
        $error = false;
    }

    if ($apply == true) {
        do_edit($error, $msg);
    } else {
        show_configs($error, $msg);
    }
}


function get_client_download_url($configname, $dir)
{
    global $cfg;

    $file = "$configname.cfg";
    $dir = get_nrds_config_dir() . "/" . $dir;
    $thefile = $dir . "/" . $file;
    if (file_exists($thefile) && $file != "") {
        $handle = fopen($thefile, "r");
        if ($handle) {
            $configvar["commands"] = "";
            while (($buffer = fgets($handle, 4096)) !== false) {
                // ignore lines beginning with # and ;
                if ($buffer[0] != "#" && $buffer[0] != ";") {
                    //grab command lines seperately
                    if (substr($buffer, 0, 7) == "command") {
                        $configvar["commands"] .= $buffer;
                    } else {
                        $tmpvar = explode("=", $buffer);
                        if (!isset($tmpvar[1])) $tmpvar[1] = "";
                        $configvar[trim($tmpvar[0])] = trim($tmpvar[1], "\" \t\n\r\0\x0B");
                    }
                }
            }
            fclose($handle);

            /*
             * Adding some translations from NCPA variable names to
             * NRDS variable names.
             */
            if (!array_key_exists('URL', $configvar) && isset($configvar['parent'])) {
                $configvar['URL'] = trim($configvar['parent']);
            }
            if (!array_key_exists('TOKEN', $configvar) && isset($configvar['token'])) {
                $configvar['TOKEN'] = $configvar['token'];
            }
        }
    }

    return trim($configvar['URL']) . '?cmd=nrdsgetclient&token=' . trim($configvar['TOKEN']) . '&configname=' . urlencode(trim($configname));
}

// Not currently used
function do_download()
{
    global $cfg;

    $result = grab_request_var("result", "ok");
    $file = grab_request_var("download", "") . ".cfg";
    $tdir = grab_request_var("dir", "configs");

    // Clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // Clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_nrds_config_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    $mime_type = "";
    header('Content-type: ' . "text/plain");
    header("Content-length: " . filesize($thefile));
    header('Content-Disposition: attachment; filename="' . basename($thefile) . '"');
    readfile($thefile);
    exit();
}

// Not currently used
function do_upload()
{
    global $cfg;
    global $request;

    // In demo mode
    if (in_demo_mode() == true) {
        show_configs(true, _("Changes are disabled while in demo mode."));
    }

    // Check session
    check_nagios_session_protector();

    $uploaded_file = grab_request_var("uploadedfile");

    $target_path = get_nrds_config_dir() . "/templates";
    $target_path .= "/";
    $target_path .= basename($_FILES['uploadedfile']['name']);

    if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
        chmod($target_path, 0664);
        show_configs(false, _("New graph template was installed successfully."));
    } else {
        show_configs(true, _("Graph template could not be installed - directory permissions may be incorrect."));
    }

    exit();
}

function do_delete()
{
    global $cfg;
    global $request;

    // In demo mode
    if (in_demo_mode() == true) {
        show_configs(true, _("Changes are disabled while in demo mode."));
    }

    // Check session
    check_nagios_session_protector();

    $file = grab_request_var("delete", "") . ".cfg";
    $tdir = grab_request_var("dir", "templates");

    // Clean the filename
    $file = str_replace("..", "", $file);
    $file = str_replace("/", "", $file);
    $file = str_replace("\\", "", $file);

    // Clean the directory
    $tdir = str_replace("..", "", $tdir);
    $tdir = str_replace("/", "", $tdir);
    $tdir = str_replace("\\", "", $tdir);

    $dir = get_nrds_config_dir() . "/" . $tdir;
    $thefile = $dir . "/" . $file;

    if (unlink($thefile) === TRUE) {
        show_configs(false, "The file was deleted sucessfully.");
    } else {
        show_configs(true, "Unable to delete the file " . $thefile);
    }
}

function get_nrds_config_dir()
{
    return "/usr/local/nrdp";
}

function get_nrds_configs()
{
    global $cfg;

    $templates = array();
    $basedir = get_nrds_config_dir();

    $dirs = array($basedir . "/configs");

    foreach ($dirs as $dir) {

        $p = $dir;
        $direntries = file_list($p, "");
        foreach ($direntries as $de) {

            $file = $de;
            $filepath = $dir . "/" . $file;
            $ts = filemtime($filepath);

            $perms = fileperms($filepath);
            $perm_string = file_perms_to_string($perms);

            $ownerarr = fileowner($filepath);
            if (function_exists('posix_getpwuid')) {
                $ownerarr = posix_getpwuid($ownerarr);
                $owner = $ownerarr["name"];
            } else
                $owner = $ownerarr;
            $grouparr = filegroup($filepath);
            if (function_exists('posix_getgrgid')) {
                $grouparr = posix_getgrgid($grouparr);
                $group = $grouparr["name"];
            } else
                $group = $grouparr;

            $dir_name = basename($dir);

            if (substr($file, -4) == ".cfg")
                $templates[] = array(
                    "dir" => $dir_name,
                    "file" => $file,
                    "timestamp" => $ts,
                    "date" => get_datetime_string($ts),
                    "perms" => $perms,
                    "permstring" => $perm_string,
                    "owner" => $owner,
                    "group" => $group,
                );
        }
    }

    return $templates;
}

// Good theory, but Apache doesn't have write access to the correct directories
function do_install()
{
    global $cfg;
    global $request;
    global $nrdsconfigmanager_component_name;
    
    // Retrieve the URL for this component
    $componentbase = dirname(__FILE__);

    $script = "mkdir -p $componentbase/tmp;cd $componentbase/tmp;wget https://assets.nagios.com/downloads/nrdp/nrds.tar.gz;tar xzf nrds.tar.gz;cd nrds;./installnrdsserver;touch $componentbase/installed.nrds";
    $rslt = system($script, $retval);
    if (file_exists('/usr/local/nrdp/plugins/Generic/utils.sh')) {
        show_configs();
    } else {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}

function get_config_os($file)
{
    $os = "Linux";
    $path = get_nrds_config_dir() . "/" . $file;
    if (file_exists($path) && $path != "") {
        $handle = fopen($path, "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                if (strpos($buffer, "CONFIG_OS", 0) === 0) {
                    // We found the OS, break out of the loop and return it.
                    $tmpvar = explode("=", $buffer);
                    $os = trim($tmpvar[1], "\" \t\n\r\0\x0B");
                    fclose($handle);
                    return $os;
                }
            }
            fclose($handle);
        }
    }
    
    // We couldn't find it, return the default.
    return $os;
}

function make_ncpa_config(&$config, $configvar)
{
    $config = "
[listener]
ip = 0.0.0.0
port = 5693
uid = nagios 
gid = nagcmd
pidfile = var/ncpa_listener.pid
logfile = var/ncpa_listener.log
loglevel = info

[api]
community_string = {$configvar['TOKEN']}

[passive]
sleep = 300
handlers = nrdp
uid =
gid =
pidfile = var/ncpa_passive.pid
logfile = var/ncpa_passive.log
loglevel = info

[nrdp]
parent = {$configvar['URL']}
token = {$configvar['TOKEN']}

[nrds]
# Most of this is required to maintain compatability with NRDS
# and is not actually referred to by the NCPA agent.
CONFIG_VERSION={$configvar['CONFIG_VERSION']}
CONFIG_OS=NCPA
CONFIG_NAME={$configvar['CONFIG_NAME']}
LOG_FILE=BUILTIN
URL={$configvar['URL']}
TOKEN={$configvar['TOKEN']}
COMMAND_PREFIX=UNUSED
SEND_NRDP=BUILTIN
TMPDIR=BUILTIN
PLUGIN_DIR={$configvar['PLUGIN_DIR']}
UPDATE_CONFIG=1
UPDATE_PLUGINS=1

[passive checks]
{$configvar['commands']}

[plugin directives]
plugin_path = {$configvar['PLUGIN_DIR']}
.vbs = cscript \$plugin_name \$plugin_args //NoLogo
.ps1 = powershell -ExecutionPolicy Unrestricted \$plugin_name \$plugin_args
.sh = /bin/sh \$plugin_name \$plugin_args";
}

function disable_if_reserved($input)
{
    if ($input === 'BUILTIN' || $input === 'UNUSED') {
        echo 'disabled';
    }
}
