#!/usr/bin/php -q
<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

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
$hostname = grab_array_var($args,'host',''); 

//if hostname was passed instead of ID
if($hostname!='') {
	if(!db_connect_nagiosql()) 
		exit_with_error(2,"Unable to connect to nagiosql database\n");  
	
	$id = nagiosql_get_host_id($hostname); 
	if(!$id)
		exit_with_error(1,"Unable find host in nagiosql database\n");	
		
	//sanity checks so we don't delete a host with dependent relationships
	if(nagiosql_host_is_in_dependency($hostname) || nagiosql_host_has_services($hostname) || nagiosql_host_is_related_to_other_hosts($hostname) )
			exit_with_error(3,"Unable to delete host {$hostname}. Host has dependent relationships\n");		
}


if($id<=0)
	exit_with_error(1,"Unable find host in nagiosql database\nUsage: ./nagiosql_delete_host [--id=<host id>] [--host=<host_name>]\n");
	
$cmdline="/usr/bin/wget --load-cookies=".$cookiefile." ".$url." --no-check-certificate --post-data 'type=host&backend=1&cmd=delete&id=".$id."' -O nagiosql.delete.host";
echo "CMDLINE:\n";
echo $cmdline;
echo "\n";
$output=system($cmdline,$return_code);
	

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


?>