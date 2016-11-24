#!/bin/env php -q
<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//
// Cleans up old files around the system 
//

define("SUBSYSTEM", 1);

require_once(dirname(__FILE__).'/../html/config.inc.php');
require_once(dirname(__FILE__).'/../html/includes/common.inc.php');

init_cleaner();
do_cleaner_jobs();

// Connects to databases
function init_cleaner()
{
    $dbok = db_connect_all();
    if ($dbok == false) {
        echo "ERROR CONNECTING TO DATABASES!\n";
        exit();
    }

    return;
}

function do_cleaner_jobs()
{
    global $cfg;
    $t = 0;
        
    // CLEANUP NAGIOSQL BACKUPS
    // Delete backups greater than 24 hours old
    $cmdline = $cfg['script_dir']."/nagiosql_trim_backups.sh";
    system($cmdline, $return_code);
    
    // CLEANUP OLD NOM CHECKPOINTS
    // Keep only the most recent checkpoints
    $cmdline = $cfg['script_dir']."/nom_trim_nagioscore_checkpoints.sh";
    system($cmdline, $return_code);

    // Misc cleanup functions
    $args = array(); 
    do_callbacks(CALLBACK_SUBSYS_CLEANER, $args); 
        
    update_sysstat();
    
    check_xi_file_integrity();
    
    echo "\n";
    echo "PROCESSED $t COMMANDS\n";
}
    
function update_sysstat()
{
    // Record our run in sysstat table
    $arr = array("last_check" => time());
    $sdata = serialize($arr);
    update_systat_value("cleaner", $sdata);
}