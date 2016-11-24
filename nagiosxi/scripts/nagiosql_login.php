#!/usr/bin/php -q
<?php
// LOGIN TO NAGIOSQL AND SAVE SESSION COOKIES
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

define("SUBSYSTEM",1);

require_once(dirname(__FILE__).'/../html/config.inc.php');

// make database connections
$dbok=db_connect_all();
if($dbok==false){
	echo "ERROR CONNECTING TO DATABASES!\n";
	exit(7);
	}

$username=get_component_credential("nagiosql","username");
$password=get_component_credential("nagiosql","password");
//check for https
$https=grab_array_var($cfg,"use_https",false);
$url=($https==true)?"https":"http";
//check for port #
$port = grab_array_var($cfg,'port_number',false); 
$port = ($port) ? ':'.$port : ''; 

$url.="://localhost".$port.get_component_url_base("ccm",false)."/";

echo "URL: $url\n";

$cookiefile="nagiosql.cookies";

$cmdline="/usr/bin/wget --save-cookies $cookiefile --keep-session-cookies $url --no-check-certificate --post-data 'submit=Login&hidelog=true&loginSubmitted=true&backend=1&username=$username&password=$password' -O nagiosql.login";

//echo "USERNAME: $username\n";
//echo "PASSWORD: $password\n";
//echo "URL: $url\n";
echo "CMDLINE\n";
echo $cmdline;
//echo "\n";

$output=system($cmdline,$return_code);

//login verification for nagiosql
$f = @fopen('nagiosql.login','r');
$check = false;
while(!feof($f))
{
	$line = fgets($f,256);
	$string = '\'index.php?cmd=logout';
	if(strpos($line,$string))
	{
		echo "LOGIN SUCCESSFUL!\n";
		$check = true;
		break;
	}
}
@fclose($f);

//bail if we didn't find nagiosql contents
if(!$check) exit(2);

//echo "RETURN CODE IS: $return_code\n";

//bail if wget experienced an error 
if($return_code > 0) exit(2);

exit(0);

?>