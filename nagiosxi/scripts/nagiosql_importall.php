#!/usr/bin/php -q
<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

define("SUBSYSTEM",1);

echo "IMPORTING CONFIG FILES...";

require_once(dirname(__FILE__).'/../html/config.inc.php');

$https=grab_array_var($cfg,"use_https",false);
$url=($https==true)?"https":"http";
//check for port #
$port = grab_array_var($cfg,'port_number',false); 
$port = ($port) ? ':'.$port : ''; 

$url.="://localhost".$port.get_component_url_base("ccm",false)."/";

echo "URL: $url\n";

$cookiefile="nagiosql.cookies";

// IMPORT ALL FILES
$dir="/usr/local/nagios/etc/import/";


$fl=file_list($dir,"/.*\.cfg/");
print_r($fl);
foreach($fl as $f)
{
	import_file($dir.$f);
}
exit(0); 

function import_file($f)
{
	global $url;
	global $cookiefile;
	
	echo "IMPORTING $f\n";
	//return;

	$cmdline="/usr/bin/wget --load-cookies=".$cookiefile." ".$url." --no-check-certificate --post-data 'backend=1&cmd=admin&type=import&importsubmitted=true&chbOverwrite=1&subForm=Import&selImportFile[]=".$f."' -O nagiosql.import.monitoring";
	echo "CMDLINE:\n";
	echo $cmdline;
	echo "\n";
	system($cmdline,$return_code);

	if($return_code == 0){
		// delete the file once it has been imported
		unlink($f);
	}
	else{
		echo "ERROR: Could not import file $f.\n";
		exit(3);
	}
	
}





?>