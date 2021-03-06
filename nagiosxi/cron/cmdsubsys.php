#!/bin/env php -q
<?php
//
// Command Subsystem Cron
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

define("SUBSYSTEM", 1);

require_once(dirname(__FILE__).'/../html/config.inc.php');
require_once(dirname(__FILE__).'/../html/includes/utils.inc.php');

$max_time = 59;
$logging = true;

init_cmdsubsys();
do_cmdsubsys_jobs();

function init_cmdsubsys()
{
    // Make database connections
    $dbok = db_connect_all();
    if ($dbok == false) {
        echo "ERROR CONNECTING TO DATABASES!\n";
        exit();
    }

    return;
}

function do_cmdsubsys_jobs()
{
    global $max_time;
    global $logging;

    // Enable logging?  
    $logging = is_null(get_option('enable_subsystem_logging')) ? true : get_option("enable_subsystem_logging");
    
    $start_time = time();
    $t = 0;

    while(1) {
        $n = 0;

        // Bail if if we're been here too long
        $now = time();
        if (($now - $start_time) > $max_time) {
            break;
        }

        $n += process_commands();
        $t += $n;
        
        // Sleep for 1 second if we didn't do anything...
        if ($n == 0) {
            update_sysstat();
            if ($logging) {
                echo ".";
            }
            usleep(1000000);
        }
    }

    update_sysstat();
    echo "\n";
    echo "PROCESSED $t COMMANDS\n";

    // Handle misc background jobs (update checks, etc)
    do_uloop_jobs();
}   
    
function update_sysstat()
{
    // Record our run in sysstat table
    $arr = array(
        "last_check" => time()
    );
    $sdata = serialize($arr);
    update_systat_value("cmdsubsys", $sdata);
}

function process_commands()
{
    global $db_tables;
    global $cfg;

    // Get the next queued command
    $sql = "SELECT * FROM ".$db_tables[DB_NAGIOSXI]["commands"]." WHERE status_code='0' AND event_time<=NOW() ORDER BY submission_time ASC";
    $args = array(
        "sql" => $sql,
        "useropts" => array(
            "records" => 1
        )
    );
    $sql = limit_sql_query_records($args, $cfg['db_info'][DB_NAGIOSXI]['dbtype']);
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql, true, false))) {
        if (!$rs->EOF) {
            process_command_record($rs);
            return 1;
        }
    }
    return 0;
}
    
function process_command_record($rs)
{
    global $db_tables;
    global $cfg;
    global $logging;
    
    if ($logging) {
        echo "PROCESSING COMMAND ID ".$rs->fields["command_id"]."...\n";
    }
    
    $command_id = $rs->fields["command_id"];
    $command = intval($rs->fields["command"]);
    $command_data = $rs->fields["command_data"];
    $userid = $rs->fields["submitter_id"];
    $username = get_user_attr($userid, "username");
    
    // If the command is htaccess hide the password in the db -JO
    $clear_command_data = "";
    if ($command == COMMAND_NAGIOSXI_SET_HTACCESS) {
        $clear_command_data = ", command_data=''";
    }
    
    // Immediately update the command as being processed
    $sql = "UPDATE ".$db_tables[DB_NAGIOSXI]["commands"]." SET status_code='".escape_sql_param(COMMAND_STATUS_PROCESSING, DB_NAGIOSXI)."', processing_time=NOW()" . $clear_command_data . " WHERE command_id='".escape_sql_param($command_id, DB_NAGIOSXI)."'";
    exec_sql_query(DB_NAGIOSXI, $sql);

    // Process the command
    $result_code = process_command($command, $command_data, $result, $username);

    // Mark the command as being completed
    $sql = "UPDATE ".$db_tables[DB_NAGIOSXI]["commands"]." SET status_code='".escape_sql_param(COMMAND_STATUS_COMPLETED,DB_NAGIOSXI)."', result_code='".escape_sql_param($result_code, DB_NAGIOSXI)."', result='".escape_sql_param($result, DB_NAGIOSXI)."', processing_time=NOW() WHERE command_id='".escape_sql_param($command_id, DB_NAGIOSXI)."'";
    exec_sql_query(DB_NAGIOSXI, $sql);
}
    

function process_command($command, $command_data, &$output, $username)
{
    global $cfg;
    global $logging;
    
    // Don't reveal password data for certain commands
    if ($logging && ($command != 1100 && $command != 2881)) {
        echo "PROCESS COMMAND: CMD=$command, DATA=$command_data\n";
    }
    
    $output = "";
    $return_code = 0;
    
    // Get the base dir for scripts
    $base_dir = $cfg['root_dir'];
    $script_dir = $cfg['script_dir'];
    
    // Default to no command data
    $cmdline = "";
    $script_name = "";
    $script_data = "";
    
    // Post-command function call
    $post_func = "";
    $post_func_args = array();
    
    switch ($command) {
    
        case COMMAND_NAGIOSCORE_SUBMITCOMMAND:
            echo "COMMAND DATA: $command_data\n";
        
            // Command data is serialized so decode it...
            $cmdarr = unserialize($command_data);
            
            if ($logging) {
                echo "CMDARR:\n";
                print_r($cmdarr);
            }

              if(array_key_exists("multi_cmd", $cmdarr)){
                $err = 0;
                foreach($cmdarr['multi_cmd'] as $cmdarray){
                    $cmdarray_multi = (array) $cmdarray;
                    if ($logging) {
                        echo "CMDARR:\n";
                        print_r($cmdarray_multi);
                    }
                    if (array_key_exists("cmd", $cmdarray_multi)) {
                        $corecmdid = strval($cmdarray_multi["cmd"]);
                    } else {
                        return COMMAND_RESULT_ERROR;
                    }
                    
                    $nagioscorecmd = get_nagioscore_command($corecmdid, $cmdarray_multi);
                    send_to_audit_log("cmdsubsys: User [" . $username . "] submitted a command to Nagios Core: ".$nagioscorecmd, AUDITLOGTYPE_INFO);
                    echo "CORE CMD: $nagioscorecmd\n";
                
                    // SECURITY CONSIDERATION:
                    // We write directly to the Nagios command file to avoid shell interpretation of meta characters
                    if ($logging) {
                        echo "SUBMITTING A NAGIOSCORE COMMAND...\n";
                    }
                    if (($result = submit_direct_nagioscore_command($nagioscorecmd, $output)) == false)
                        $err++;

                }
                if ($err > 0) {
                    return COMMAND_RESULT_ERROR;
                } else {
                    return COMMAND_RESULT_OK;
                }
              } else{
               if (array_key_exists("cmd", $cmdarr)) {
                    $corecmdid = strval($cmdarr["cmd"]);
                } else {
                    return COMMAND_RESULT_ERROR;
                }
                
                $nagioscorecmd = get_nagioscore_command($corecmdid, $cmdarr);
                send_to_audit_log("cmdsubsys: User [" . $username . "] submitted a command to Nagios Core: ".$nagioscorecmd, AUDITLOGTYPE_INFO);
                echo "CORE CMD: $nagioscorecmd\n";
            
                // SECURITY CONSIDERATION:
                // We write directly to the Nagios command file to avoid shell interpretation of meta characters
                if ($logging) {
                    echo "SUBMITTING A NAGIOSCORE COMMAND...\n";
                }
                if (($result = submit_direct_nagioscore_command($nagioscorecmd, $output)) == false) {
                    return COMMAND_RESULT_ERROR;
                } else {
                    return COMMAND_RESULT_OK;
                }
              }
            break;

        case COMMAND_NAGIOSCORE_APPLYCONFIG:
            $script_name = "reconfigure_nagios.sh";
            echo "APPLYING NAGIOSCORE CONFIG...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] applied a new configuration to Nagios Core", AUDITLOGTYPE_INFO);

            //do callback functions
            $args = array(); 
            do_callbacks(CALLBACK_SUBSYS_APPLYCONFIG, $args);
            break;
            
        case COMMAND_NAGIOSCORE_RECONFIGURE:
            $script_name = "reconfigure_nagios.sh";
            echo "RECONFIGURING NAGIOSCORE ...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] reconfigured Nagios Core", AUDITLOGTYPE_INFO);

            // Do callback functions
            $args = array(); 
            do_callbacks(CALLBACK_SUBSYS_APPLYCONFIG, $args);
            break;
            
        // NAGIOSQL COMMANDS
        case COMMAND_NAGIOSCORE_IMPORTONLY:
            $script_name = "import_nagiosql.sh";
            echo "IMPORTING CONFIGURATION FILES ...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] applied a new configuration to CCM without Applying configuration", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NAGIOSQL_DELETECONTACT:
            $script_name = "nagiosql_delete_object.sh";
            $script_data = "contact ".$command_data;
            echo "DELETING CONTACT ...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] deleted contact '".$command_data."'", AUDITLOGTYPE_DELETE);
            break;
        case COMMAND_NAGIOSQL_DELETETIMEPERIOD:
            $script_name = "nagiosql_delete_object.sh";
            $script_data = "timeperiod ".$command_data;
            echo "DELETING TIMEPERIOD ...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] deleted timeperiod '".$command_data."'", AUDITLOGTYPE_DELETE);
            break;
        case COMMAND_NAGIOSQL_DELETESERVICE:
            $script_name = "nagiosql_delete_object.sh";
            $script_data = "service ".$command_data;
            echo "DELETING SERVICE ...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] deleted service '".$command_data."'", AUDITLOGTYPE_DELETE);
            break;
        case COMMAND_NAGIOSQL_DELETEHOST:
            $script_name = "nagiosql_delete_object.sh";
            $script_data = "host ".$command_data;
            echo "DELETING HOST ...\n";
            send_to_audit_log("cmdsubsys: User [" . $username . "] deleted host '".$command_data."'", AUDITLOGTYPE_DELETE);
            break;
            
            
        // DAEMON COMMANDS
        // NAGIOS CORE
        case COMMAND_NAGIOSCORE_GETSTATUS:
            $cmdline = "sudo ".$script_dir."/manage_services.sh status nagios";
            break;
        case COMMAND_NAGIOSCORE_START:
            $cmdline = "sudo ".$script_dir."/manage_services.sh start nagios";
            send_to_audit_log("cmdsubsys: User [" . $username . "] started Nagios Core", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NAGIOSCORE_STOP:
            $cmdline = "sudo ".$script_dir."/manage_services.sh stop nagios";
            send_to_audit_log("cmdsubsys: User [" . $username . "] stopped Nagios Core", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NAGIOSCORE_RESTART:
            $cmdline = "sudo ".$script_dir."/manage_services.sh restart nagios";
            send_to_audit_log("cmdsubsys: User [" . $username . "] restarted Nagios Core", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NAGIOSCORE_RELOAD:
            $cmdline = "sudo ".$script_dir."/manage_services.sh reload nagios";
            send_to_audit_log("cmdsubsys: User [" . $username . "] reloaded Nagios Core configuration", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NAGIOSCORE_CHECKCONFIG:
            $cmdline = "sudo ".$script_dir."/manage_services.sh checkconfig nagios";
            break;

        // NDO2DB
        case COMMAND_NDO2DB_GETSTATUS:
            $cmdline = "sudo ".$script_dir."/manage_services.sh status ndo2db";
            break;
        case COMMAND_NDO2DB_START:
            $cmdline = "sudo ".$script_dir."/manage_services.sh start ndo2db";
            send_to_audit_log("cmdsubsys: User [" . $username . "] started NDO2DB", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NDO2DB_STOP:
            $cmdline = "sudo ".$script_dir."/manage_services.sh stop ndo2db";
            send_to_audit_log("cmdsubsys: User [" . $username . "] stopped NDO2DB", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NDO2DB_RESTART:
            $cmdline = "sudo ".$script_dir."/manage_services.sh restart ndo2db";
            send_to_audit_log("cmdsubsys: User [" . $username . "] restarted NDO2DB", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NDO2DB_RELOAD:
            $cmdline = "sudo ".$script_dir."/manage_services.sh reload ndo2db";
            send_to_audit_log("cmdsubsys: User [" . $username . "] reloaded NDO2DB configuration", AUDITLOGTYPE_INFO);
            break;

        // NPCD
        case COMMAND_NPCD_GETSTATUS:
            $cmdline = "sudo ".$script_dir."/manage_services.sh status npcd";
            break;
        case COMMAND_NPCD_START:
            $cmdline = "sudo ".$script_dir."/manage_services.sh start npcd";
            send_to_audit_log("cmdsubsys: User [" . $username . "] started NPCD", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NPCD_STOP:
            $cmdline = "sudo ".$script_dir."/manage_services.sh stop npcd";
            send_to_audit_log("cmdsubsys: User [" . $username . "] stopped NPCD", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NPCD_RESTART:
            $cmdline = "sudo ".$script_dir."/manage_services.sh restart npcd";
            send_to_audit_log("cmdsubsys: User [" . $username . "] restarted NPCD", AUDITLOGTYPE_INFO);
            break;
        case COMMAND_NPCD_RELOAD:
            $cmdline = "sudo ".$script_dir."/manage_services.sh reload npcd";
            send_to_audit_log("cmdsubsys: User [" . $username . "] reloaded NPCD configuration", AUDITLOGTYPE_INFO);
            break;
            
        // Apache
        case COMMAND_RESTART_HTTP:
            $cmdline = "sudo ".$script_dir."/manage_services.sh restart httpd";
            send_to_audit_log("cmdsubsys: User [" . $username . "] restarted HTTPD", AUDITLOGTYPE_INFO);
            break;

		case COMMAND_NAGIOSXI_SET_HTACCESS:
			$cmdarr = unserialize($command_data);	
			$cmdline = $cfg['htpasswd_path']." -b -s ".$cfg['htaccess_file']." ".$cmdarr["username"]." '".$cmdarr["password"]."'";
			break;

        case COMMAND_NAGIOSXI_DEL_HTACCESS:
            $cmdarr = unserialize($command_data);   
            $cmdline = $cfg['htpasswd_path']." -D ".$cfg['htaccess_file']." ".$cmdarr["username"];
            break;

        case COMMAND_DELETE_CONFIGWIZARD:
            $dir = cmdsubsys_clean_str($command_data);

            if (empty($dir)) {
                return COMMAND_RESULT_ERROR;
            }
            
            $cmdline = "rm -rf ".get_base_dir()."/includes/configwizards/".$dir;
            break;

        case COMMAND_INSTALL_CONFIGWIZARD:
            if ($logging) {
                echo "INSTALLING CONFIGWIZARD...\n";
                echo "RAW COMMAND DATA: $command_data\n";
            }

            $file = cmdsubsys_clean_str($command_data);
            
            if ($logging) {
                echo "CONFIGWIZARD FILE: '".$file."'\n";
            }

            if (empty($file)) {
                echo "FILE ERROR!\n";
                return COMMAND_RESULT_ERROR;
            }
            
            // Create a new temp directory for holding the unzipped wizard
            $tmpname = random_string(5);
            if ($logging) {
                echo "TMPNAME: $tmpname\n";
            }
            $tmpdir = get_tmp_dir()."/".$tmpname;
            system("rm -rf ".$tmpdir);
            mkdir($tmpdir);
            
            // Unzip wizard to temp directory
            $cmdline = "cd ".$tmpdir." && unzip -o ".get_tmp_dir()."/configwizard-".$file;
            system($cmdline);
            
            // Determine wizard directory/file name
            $cdir = system("ls -1 ".$tmpdir."/");
            $cname = $cdir;
            if (preg_match("/(.*)(-[0-9a-f]{40})(.*)/", $cname, $hash_matches) == 1) {
                $cname = $hash_matches[1] . $hash_matches[3];
            }
            if (preg_match("/(.*)(-master|-development)(.*)/", $cname, $branch_matches) == 1) {
                $cname = $branch_matches[1] . $branch_matches[3];
            }
            
            // Make sure this is a config wizard
            $cmdline = "grep register_configwizard ".$tmpdir."/".$cdir."/".$cname.".inc.php | wc -l";
            if ($logging) {
                echo "CMD=$cmdline";
            }

            $out = system($cmdline, $rc);
            
            if ($logging) {
                echo "OUT=$out";
            }

            // Delete temp directory if it's not a config wizard
            if ($out == "0") {
                system("rm -rf ".$tmpdir);
                $output = "Uploaded zip file is not a config wizard.";
                echo $output."\n";
                return COMMAND_RESULT_ERROR;
            }
            
            if ($logging) { 
                echo "Wizard looks ok...";
            }
            
            // Null-op
            $cmdline = "/bin/true";
            
            // Make new wizard directory (might exist already)
            @mkdir(get_base_dir()."/includes/configwizards/".$cname);
            
            // Move wizard to production directory and delete temp directory
            $cmdline = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir." && cp -rf ".$tmpdir."/".$cdir."/* ".get_base_dir()."/includes/configwizards/".$cname." && rm -rf ".$tmpdir;

            $post_func = "install_configwizard";
            $post_func_args = array(
                "wizard_name" => $cname,
                "wizard_dir" => get_base_dir()."/includes/configwizards/".$cname
            );
            break;

        case COMMAND_UPGRADE_CONFIGWIZARD:
            if (!empty($command_data)) {
                $command_data = unserialize($command_data);
            }

            if ($logging) {
                echo "AUTO UPGRADING CONFIG WIZARDS";
                echo "CMD DATA: ";
                if (!empty($command_data)) {
                    print_r($command_data);
                }
            }
            
            $cmdline = "/bin/true";
            
            $proxy = false;
            if (have_value(get_option('use_proxy'))) {
                $proxy = true;
            }

            $options = array(
                'return_info' => true,
                'method' => 'get',
                'timeout' => 60,
                'debug' => true
            );

            // If command data is empty, we need to upgrade ALL the config wizards
            // Grab a list of all the available config wizards and get a list of what needs to be upgraded
            if (empty($command_data)) {
                $needs_upgrade = get_all_configwizards_needing_upgrade();
                if (!empty($needs_upgrade)) {

                    foreach ($needs_upgrade as $w) {
                        
                        $url = $w['download'];
                        $name = $w['name'];
                        
                        // Start installation process
                        $tmpdir = get_tmp_dir().'/' . $name;
                        
                        // Fetch the url
                        $result = load_url($url, $options, $proxy);
                        if (empty($result["body"])){
                            $cmdline = "/bin/false";
                            break 2;
                        }
                        // Download the file to temp directory
                        file_put_contents(get_tmp_dir() ."/".$name.".zip", $result["body"]);
                        $cmd = 'cd ' . get_tmp_dir() . '; mv ' . $name . '.zip configwizard-' . $name .'.zip;';
                        system($cmd);

                        // Unzip the file and 
                        $cmd = 'cd ' . get_tmp_dir() . ' && unzip -o configwizard-' . $name . '.zip;';
                        system($cmd);

                        $cmd = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir." && cp -rf ".$tmpdir."/".$cdir." ".get_base_dir()."/includes/configwizards/ && rm -rf ".$tmpdir;
                        system($cmd);

                        $args = array("wizard_name" => $name, "wizard_dir" => get_base_dir().'/includes/configwizards/'.$name, "allow_restart" => false);
                        install_configwizard($args);

                    }

                    // Restart core once all wizards are updated
                    reconfigure_nagioscore();
                }
            } else {

                // Grab the command data
                $name = $command_data['name'];
                $url = $command_data['url'];

                // Start installation process
                $tmpdir = get_tmp_dir().'/' . $name;

                // Fetch the url
                $result = load_url($url, $options, $proxy);
                
                if (empty($result["body"])){
                    $cmdline = "/bin/false";
                    break;
                }
                // Download the file to temp directory
                file_put_contents(get_tmp_dir() ."/".$name.".zip", $result["body"]);
                $cmd = 'cd ' . get_tmp_dir() . '; mv ' . $name . '.zip configwizard-' . $name .'.zip;';
                system($cmd);

                // Unzip the file and 
                $cmd = 'cd ' . get_tmp_dir() . ' && unzip -o configwizard-' . $name . '.zip;';
                system($cmd);

                $cmd = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir." && cp -rf ".$tmpdir."/".$cdir." ".get_base_dir()."/includes/configwizards/ && rm -rf ".$tmpdir;
                system($cmd);

                $args = array("wizard_name" => $name, "wizard_dir" => get_base_dir().'/includes/configwizards/'.$name);
                install_configwizard($args);
            }

            break;

        case COMMAND_PACKAGE_CONFIGWIZARD:
            $dir = cmdsubsys_clean_str($command_data);
            
            if (empty($dir)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "cd ".get_base_dir()."/includes/configwizards && zip -r ".get_tmp_dir()."/configwizard-".$dir.".zip ".$dir;
            break;

        case COMMAND_DELETE_DASHLET:
            $dir = cmdsubsys_clean_str($command_data);
            
            if (empty($dir)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "rm -rf ".get_base_dir()."/includes/dashlets/".$dir;
            break;

        case COMMAND_INSTALL_DASHLET:
            $file = cmdsubsys_clean_str($command_data);
            
            if (empty($file)) {
                return COMMAND_RESULT_ERROR;
            }

            // Create a new temp directory for holding the unzipped dashlet
            $tmpname = random_string(5);
            if ($logging) {
                echo "TMPNAME: $tmpname\n";
            }
            $tmpdir = get_tmp_dir()."/".$tmpname;
            system("rm -rf ".$tmpdir);
            mkdir($tmpdir);
            
            // Unzip dashlet to temp directory
            $cmdline = "cd ".$tmpdir." && unzip -o ".get_tmp_dir()."/dashlet-".$file;
            system($cmdline);
            
            // Determine dashlet directory/file name
            $cdir = system("ls -1 ".$tmpdir."/");
            if (strlen($cdir) > 40) {
                $a = explode('-', $cdir);
                if (strlen(end($a)) == 40) {
                    $i = count($a);
                    unset($a[$i-1]);
                    unset($a[$i-2]);
                    $cname = implode('-', $a);
                }
            } else {
                $cname = $cdir;
            }
            
            // Make sure this is a dashlet
            $isdashlet = true;

            // Check for register_dashlet...
            $cmdline = "grep register_dashlet ".$tmpdir."/".$cdir."/".$cname.".inc.php | wc -l";
            if ($logging) {
                echo "CMD=$cmdline";
            }
            
            $out = system($cmdline, $rc);
            
            if ($logging) {
                echo "OUT=$out";
            }       
            
            // Verify it was a dashlet...
            if ($out == "0") {
                $isdashlet = false;
            }
            
            // Check to make sure its not a component...
            $cmdline = "grep register_component ".$tmpdir."/".$cdir."/".$cname.".inc.php | wc -l";
            if ($logging) {
                echo "CMD=$cmdline";
            }
            
            $out = system($cmdline, $rc);
            
            if ($logging) {
                echo "OUT=$out";
            }
            
            // Verify that it is not a component
            if ($out != "0") {
                $isdashlet = false;
            }

            // Delete template if it isn't a dashlet
            if ($isdashlet == false) {
                system("rm -rf ".$tmpdir);
                $output = "Uploaded zip file is not a dashlet.";
                echo $output."\n";
                return COMMAND_RESULT_ERROR;
            }
            
            if ($logging) {
                echo "Dashlet looks ok...";
            }
            
            // Make new dashlet directory (might exist already)
            @mkdir(get_base_dir()."/includes/dashlets/".$cname);
            
            // Move dashlet to production directory and delete temp directory
            $cmdline = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir." && cp -rf ".$tmpdir."/".$cdir."/* ".get_base_dir()."/includes/dashlets/".$cname." && rm -rf ".$tmpdir;
            break;

        case COMMAND_PACKAGE_DASHLET:
            $dir = cmdsubsys_clean_str($command_data);

            if (empty($dir)) {
                return COMMAND_RESULT_ERROR;
            }
            
            $cmdline = "cd ".get_base_dir()."/includes/dashlets && zip -r ".get_tmp_dir()."/dashlet-".$dir.".zip ".$dir;
            break;

        case COMMAND_DELETE_COMPONENT:
            $dir = cmdsubsys_clean_str($command_data);
            
            if (empty($dir)) {
                return COMMAND_RESULT_ERROR;
            }
            
            $cmdline = "rm -rf ".get_base_dir()."/includes/components/".$dir;
            break;

        case COMMAND_INSTALL_COMPONENT:
            $file = cmdsubsys_clean_str($command_data);

            if (empty($file)) {
                return COMMAND_RESULT_ERROR;
            }

            // Create a new temp directory for holding the unzipped component
            $tmpname = random_string(5);
            if ($logging) {
                echo "TMPNAME: $tmpname\n";
            }
            $tmpdir = get_tmp_dir()."/".$tmpname;
            system("rm -rf ".$tmpdir);
            mkdir($tmpdir);
            
            // Unzip component to temp directory
            $cmdline = "cd ".$tmpdir." && unzip -o ".get_tmp_dir()."/component-".$file;
            system($cmdline);
            
            // Determine component directory/file name
            $cdir = system("ls -1 ".$tmpdir."/");
            if (strlen($cdir) > 40) {
                $a = explode('-', $cdir);
                if (strlen(end($a)) == 40) {
                    $i = count($a);
                    unset($a[$i-1]);
                    unset($a[$i-2]);
                    $cname = implode('-', $a);
                }
            } else {
                $cname = $cdir;
            }

            // Make an exception to the grep command for the following components and deny the others from being uploaded
            $except = array('capacityplanning', 'bulkmodifications', 'autodiscovery', 'ldap_ad_integration', 'deploynotification', 'nagiosbpi', 'scheduledbackups', 'scheduledreporting');
            $deny = array('profile');
            if (!in_array($cname, $except)) {
                if (!in_array($cname, $deny)) {

                    // Make sure this is a component
                    $cmdline = "grep register_component ".$tmpdir."/".$cdir."/".$cname.".inc.php | wc -l";

                    if ($logging) {
                        echo "CMD=$cmdline";
                    }

                    $out = system($cmdline, $rc);
                    
                    if ($logging) {
                        echo "OUT=$out";
                    }

                    // Delete temp directory if it's not a component
                    if ($out == "0") {
                        system("rm -rf ".$tmpdir);
                        $output = "Uploaded zip file is not a component.";
                        echo $output."\n";
                        return COMMAND_RESULT_ERROR;
                    }

                } else {
                    system("rm -rf ".$tmpdir);
                    $output = "Uploaded component cannot be updated through the UI.";
                    echo $output."\n";
                    return COMMAND_RESULT_ERROR;
                }
            }

            if ($logging) {
                echo "Component looks ok...";
            }
            
            // Null-op
            $cmdline = "/bin/true";
            
            // Make new component directory (might exist already)
            @mkdir(get_base_dir()."/includes/components/".$cname);
            
            // Move component to production directory and delete temp directory
            // and added permissions fix to make sure all new components are executable
            $cmdline = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir." && cp -rf ".$tmpdir."/".$cdir."/* ".get_base_dir()."/includes/components/".$cname." && rm -rf ".$tmpdir;

            $component_name = $cname;
            $post_func = "install_component";
            $post_func_args = array(
                "component_name" => $component_name,
                "component_dir" => get_base_dir()."/includes/components/".$component_name,
            );
            break;

        case COMMAND_UPGRADE_COMPONENT:

            if (!empty($command_data)) {
                $command_data = unserialize($command_data);
            }

            if ($logging) {
                echo "AUTO UPGRADING COMPONENTS";
                echo "CMD DATA: ";
                if (!empty($command_data)) {
                    print_r($command_data);
                }
            }

            $cmdline = "/bin/true";

            $proxy = false;
            if (have_value(get_option('use_proxy'))) {
                $proxy = true;
            }

            $options = array(
                'return_info' => true,
                'method' => 'get',
                'timeout' => 60,
                'debug' => true
            );
            
            // If command data is empty, we need to upgrade ALL the config wizards
            // Grab a list of all the available config wizards and get a list of what needs to be upgraded
            if (count($command_data) > 1) {
                
                foreach ($command_data as $c) {

                    $name = $c['name'];
                    $url = $c['url'];

                    // Create a new temp directory for holding the unzipped component
                    $tmpname = random_string(5);
                    if ($logging) {
                        echo "TMPNAME: $tmpname\n";
                    }
                    $tmpdir = get_tmp_dir()."/".$tmpname;
                    system("rm -rf ".$tmpdir);
                    mkdir($tmpdir);
                    
                    // Fetch the url
                    $result = load_url($url, $options, $proxy);
                    if (empty($result["body"])){
                        $cmdline = "/bin/false";
                        break 2;
                    }
                    
                    file_put_contents($tmpdir."/".$name.".zip", $result["body"]);
                    // Download and unzip component
                    $cmd = "cd ".$tmpdir."; unzip -o  ".$name.".zip;";
                    system($cmd);

                    @mkdir(get_base_dir()."/includes/components/".$name);

                    // Move component to production directory and delete temp directory
                    // and added permissions fix to make sure all new components are executable
                    $cmd = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir."/".$name." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir."/".$name." && cp -rf ".$tmpdir."/".$name." ".get_base_dir()."/includes/components/ && rm -rf ".$tmpdir;
                    system($cmd);

                    install_component(array("component_name" => $name, "component_dir" => get_base_dir()."/includes/components/".$name));
                }
                    $cmdline = "/bin/true";

            } else {

                // Only upgrade a single component
                $name = $command_data[0]['name'];
                $url = $command_data[0]['url'];

                // Create a new temp directory for holding the unzipped component
                $tmpname = random_string(5);
                if ($logging) {
                    echo "TMPNAME: $tmpname\n";
                }
                $tmpdir = get_tmp_dir()."/".$tmpname;
                system("rm -rf ".$tmpdir);
                mkdir($tmpdir);

                // Fetch the url
                $result = load_url($url, $options, $proxy);
                if (empty($result["body"])){
                    $cmdline = "/bin/false";
                    break 2;
                }
                file_put_contents($tmpdir."/".$name.".zip", $result["body"]);
                // Download and unzip component
                $cmdline = "cd ".$tmpdir."; unzip -o  ".$name.".zip;";
                system($cmdline);

                @mkdir(get_base_dir()."/includes/components/".$name);

                // Move component to production directory and delete temp directory
                // and added permissions fix to make sure all new components are executable
                $cmdline = ". ".get_root_dir()."/var/xi-sys.cfg && chmod -R 755 ".$tmpdir."/".$name." && chown -R \$nagiosuser:\$nagiosgroup ".$tmpdir."/".$name." && cp -rf ".$tmpdir."/".$name." ".get_base_dir()."/includes/components/ && rm -rf ".$tmpdir;

                $post_func = "install_component";
                $post_func_args = array(
                    "component_name" => $name,
                    "component_dir" => get_base_dir()."/includes/components/".$name
                );
            }

            break;

        case COMMAND_PACKAGE_COMPONENT:
            $dir = cmdsubsys_clean_str($command_data);

            if (empty($dir)) {
                return COMMAND_RESULT_ERROR;
            }
            
            $cmdline = "cd ".get_base_dir()."/includes/components && zip -r ".get_tmp_dir()."/component-".$dir.".zip ".$dir;
            break;

        case COMMAND_DELETE_CONFIGSNAPSHOT:
            $ts = cmdsubsys_clean_str($command_data);
            
            if (empty($ts)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "rm -rf ".$cfg['nom_checkpoints_dir']."errors/".$ts.".tar.gz";
            break;

        case COMMAND_RESTORE_CONFIGSNAPSHOT:
            $cmdline = get_root_dir()."/scripts/nom_restore_nagioscore_checkpoint_specific.sh ".$command_data;
            break;

        case COMMAND_RESTORE_NAGIOSQL_SNAPSHOT:
            $cmdline = get_root_dir()."/scripts/nagiosql_snapshot.sh ".$command_data;
            break;

        case COMMAND_ARCHIVE_SNAPSHOT:
            $ts = $command_data;
            $archive_dir = $cfg['nom_checkpoints_dir']."/archives";
            $ql_archive_dir = $cfg['nom_checkpoints_dir']."/../nagiosxi/archives";
            
            if (!is_dir($archive_dir)) {
                mkdir($archive_dir);
            }
            
            if (!is_dir($ql_archive_dir)) {
                mkdir($ql_archive_dir);
            }
                
            $cmdline = "cp ".$cfg['nom_checkpoints_dir']."/$ts.tar.gz ".$cfg['nom_checkpoints_dir']."/$ts.txt $archive_dir;";
            $cmdline .= "cp ".$cfg['nom_checkpoints_dir']."/../nagiosxi/".$ts."_nagiosql.sql.gz $ql_archive_dir;";
            break;

        case COMMAND_DELETE_ARCHIVE_SNAPSHOT:
            $ts = cmdsubsys_clean_str($command_data);
            $archive_dir = $cfg['nom_checkpoints_dir']."/archives";
            $ql_archive_dir = $cfg['nom_checkpoints_dir']."/../nagiosxi/archives";

            if (empty($ts)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "rm -rf ".$archive_dir."/".$ts.".tar.gz";
            $cmdline .= " ".$archive_dir."/".$ts.".txt";
            $cmdline .= " ".$ql_archive_dir."/".$ts."_nagiosql.sql.gz;";
            break;

        case COMMAND_RENAME_ARCHIVE_SNAPSHOT:
            $command_data = unserialize($command_data);
            $old_name = $command_data[0];
            $new_name = $command_data[1];
            $archive_dir = $cfg['nom_checkpoints_dir']."/archives";
            $ql_archive_dir = $cfg['nom_checkpoints_dir']."/../nagiosxi/archives";

            if (empty($old_name) || empty($new_name)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "mv ".$archive_dir."/".$old_name.".tar.gz ".$archive_dir."/".$new_name.".tar.gz;";
            $cmdline .= "mv ".$archive_dir."/".$old_name.".txt ".$archive_dir."/".$new_name.".txt;";
            $cmdline .= "mv ".$ql_archive_dir."/".$old_name."_nagiosql.sql.gz ".$ql_archive_dir."/".$new_name."_nagiosql.sql.gz;";
            break;

        case COMMAND_CREATE_SYSTEM_BACKUP:
            $data = unserialize($command_data);
            
            // If there is a name set
            if (!empty($data[0])) {
                $name = " -n " . $data[0];
            } else {
                $name = "";
            }

            // If there is a directory set
            if (!empty($data[1])) {
                $d = rtrim($data[1], "/");
                $dir = " -d " . $d;
            } else {
                $dir = "";
            }
            
            $cmdline = get_root_dir()."/scripts/backup_xi.sh" . $name . $dir;
            break;

        case COMMAND_DELETE_SYSTEM_BACKUP:
            $data = unserialize($command_data);

            // If there is a name set
            if (!empty($data[0])) {
                $name = $data[0];
            } else {
                $name = "";
            }

            // If there is a directory set
            if (!empty($data[1])) {
                $d = rtrim($data[1], "/");
                $dir = $d;
            } else {
                $dir = "/store/backups/nagiosxi";
            }

            if (empty($name)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "rm -rf " . $dir . "/" . $name . ".tar.gz";
            break;

        case COMMAND_RENAME_SYSTEM_BACKUP:
            $data = unserialize($command_data);
            $old_name = $data[0];
            $new_name = $data[1];

            if (empty($old_name) || empty($new_name)) {
                return COMMAND_RESULT_ERROR;
            }

            $cmdline = "mv /store/backups/nagiosxi/" . $old_name . ".tar.gz /store/backups/nagiosxi/" . $new_name . ".tar.gz";
            break;
        
        case COMMAND_UPDATE_XI_TO_LATEST:
            $data = unserialize($command_data);
            $file = $data[0];
            
            $tmpdir = get_tmp_dir();
            
            $proxy = false;
            if (have_value(get_option('use_proxy'))) {
                $proxy = true;
            }

            $options = array(
                'return_info' => true,
                'method' => 'get',
                'timeout' => 300,
                'debug' => true
            );
            
            // Fetch the url
            $result = load_url($file, $options, $proxy);
            if (empty($result["body"])){
                $cmdline = "/bin/false";
                break;
            }
            
            if (file_exists($tmpdir."/xi-latest.tar.gz"))
                unlink($tmpdir."/xi-latest.tar.gz");
            file_put_contents($tmpdir."/xi-latest.tar.gz", $result["body"]);
            file_put_contents($tmpdir."/upgrade.log", "STARTING XI UPGRADE\n");
                    
            $cmdline = "sudo ".get_root_dir()."/scripts/upgrade_to_latest.sh";
            break;
        
        case COMMAND_CHANGE_TIMEZONE:
            $timezone = $command_data;
            $cmdline = "sudo ".get_root_dir()."/scripts/change_timezone.sh -z '$timezone'";
            break;

        case COMMAND_RUN_CHECK_CMD:
            if (!empty($command_data)) {
                $cmdline = $command_data;
            } else {
                return COMMAND_RESULT_ERROR;
            }
            break;

        default:
            echo "INVALID COMMAND ($command)!\n";
            return COMMAND_RESULT_ERROR;
            break;
    }
    
    // We're running a script, so generate the command line to execute
    if ($script_name != "") {
        if ($script_data != "") {
            $cmdline = sprintf("cd %s && ./%s %s", $script_dir, $script_name, $script_data);
        } else {
            $cmdline = sprintf("cd %s && ./%s", $script_dir, $script_name);
        }
    }

    // Run the system command (and don't reveal credentials)
    if ($command == COMMAND_NAGIOSXI_SET_HTACCESS) {
        echo "Setting new htaccess credentials\n";
    } else {
        echo "CMDLINE=$cmdline\n";
    }

    $return_code = 127;
    $output = "";
    if ($cmdline != "") {
        $output = system($cmdline, $return_code);
    }
    
    echo "OUTPUT=$output\n";
    echo "RETURNCODE=$return_code\n";
    
    // Run the post function call
    if ($return_code == 0 && $post_func != "" && function_exists($post_func)) {
        echo "RUNNING POST FUNCTION CALL: $post_func\n";
        $return_code = $post_func($post_func_args);
        echo "POST FUNCTION CALL RETURNCODE=$return_code\n";
    }

    // Do callbacks
    $args = array(
        'command' => $command,
        'command_data' => $command_data
    );
    do_callbacks(CALLBACK_SUBSYS_GENERIC, $args);

    if ($return_code != 0) {
        return $return_code;
    }
    return COMMAND_RESULT_OK;
}

function cmdsubsys_clean_str($x)
{
    $x = str_replace("..", "", $x);
    $x = str_replace("/", "", $x);
    $x = str_replace("\\", "", $x);
    return $x;
}