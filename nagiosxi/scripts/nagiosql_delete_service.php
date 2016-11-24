#!/usr/bin/php -q
<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$
// this script allows services to be deleted from the command-line either by service ID, or in bulk by config_name

// require_once(dirname(__FILE__).'/nagiosql_login.php');

require_once(dirname(__FILE__).'/../html/config.inc.php');
require_once(dirname(__FILE__).'/../html/includes/components/nagiosql/nagiosql.inc.php');

$https=grab_array_var($cfg,"use_https",false);
$url=($https==true)?"https":"http";
//check for port #
$port = grab_array_var($cfg,'port_number',false); 
$port = ($port) ? ':'.$port : ''; 

$url.="://localhost".$port.get_component_url_base("ccm",false)."/";
echo "URL: $url\n";

$cookiefile="nagiosql.cookies";

$args=parse_argv($argv);

$id=grab_array_var($args,"id",0);
$config = grab_array_var($args,'config',''); 

if($config=='' && $id==0) 
	exit_with_error(1,"Usage: ./nagiosql_delete_service.php [--id=<service id>] [--config=<config_name>]\n");

//if hostname was passed instead of ID
if($config!='') {
	if(!db_connect_nagiosql()) exit_with_error(2,"Unable to connect to nagiosql database\n");  	
	$ids = get_deletable_services_by_config_name($config);  
}
else //else delete by single ID 
	$ids = array($id); 


if(empty($ids) && $id==0) 
	exit_with_error(1,"Unable find services in nagiosql database.\nUsage: ./nagiosql_delete_service.php [--id=<service id>] [--config=<config_name>]\n");

$count = 0;
foreach($ids as $myId) {	

	$cmdline="/usr/bin/wget --load-cookies=".$cookiefile." ".$url." --no-check-certificate --post-data 'type=service&backend=1&cmd=delete&id=".$myId."' -O nagiosql.delete.service";
	echo "CMDLINE:\n";
	echo $cmdline;
	echo "\n";
	$output=system($cmdline,$return_code);
	$count++; 
}

//success!
echo $count." services deleted successfully!\n"; 
exit(0); 
	


////////////////////FUNCTIONS//////////////////

/**
*	exit with specified exit code and message 
*	1 - Usage error
*	2 - DB connection failed
*	3 - Dependent relationship
*
*/ 
function exit_with_error($code,$msg) {
	print $msg;
	exit($code); 
}
	
function get_deletable_services_by_config_name($config) {
	global $db_tables;
	$sql = "SELECT `id`,`config_name`,`service_description` FROM ".$db_tables[DB_NAGIOSQL]["service"]." 
			WHERE `config_name`='".escape_sql_param($config,DB_NAGIOSQL)."'";
	$rs = exec_sql_query(DB_NAGIOSQL,$sql); 
	if(!$rs) exit_with_error(1,"Failed to retrieve service ID's from nagiosql database\n");

	$ids = array(); 
	foreach($rs as $r) {
		//sanity checks, only grab deletable services 
		if(!nagiosql_is_service_unique($config,$r['service_description']) || nagiosql_service_is_in_dependency($config,$r['service_description']) )
			continue; 
			
		$ids[] = $r['id']; 
	}	
				
	return $ids; 	
}



?>