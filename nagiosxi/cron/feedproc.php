#!/bin/env php -q
<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

define("SUBSYSTEM",1);

require_once(dirname(__FILE__).'/../html/config.inc.php');
require_once(dirname(__FILE__).'/../html/includes/utils.inc.php');

$max_time=55;
$sleep_time=20;

$listen = init_feedprocessor();
if($listen)
	do_feedprocessor_jobs();
else {
	echo "Listener DISABLED\n";
	update_sysstat(); 
}

function init_feedprocessor(){

	// make database connections
	$dbok=db_connect_all();
	if($dbok==false){
		echo "ERROR CONNECTING TO DATABASES!\n";
		exit();
		}
		
	$listen = is_null(get_option('enable_unconfigured_objects')) ? true : get_option('enable_unconfigured_objects');

	return $listen;
	}

function do_feedprocessor_jobs(){
	global $max_time;
	global $sleep_time;

	$start_time=time();
	$t=0;
	

	while(1){
	
		$n=0;
	
		// bail if if we're been here too long
		$now=time();
		if(($now-$start_time)>$max_time)
			break;
	
		$n+=process_feeds();
		$t+=$n;
		
		// sleep for 1 second if we didn't do anything...
		if($n==0){
			update_sysstat();
			echo ".";
			sleep($sleep_time);
			}
		}
		
	update_sysstat();
	echo "\n";
	echo "PROCESSED $t COMMANDS\n";
	}
	
	
function update_sysstat(){
	// record our run in sysstat table
	$arr=array(
		"last_check" => time(),
		);
	$sdata=serialize($arr);
	update_systat_value("feedprocessor",$sdata);
	}
	
	
function process_feeds(){
	global $db_tables;
	
	// parse Nagios Core log file for missing objects that passive checks were received form
	$cmd="php -q ".get_root_dir()."/scripts/parse_core_eventlog.php";
	exec($cmd);

	return 0;
	}
	

?>