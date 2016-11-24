#!/usr/bin/php -q
<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

// require_once(dirname(__FILE__).'/nagiosql_login.php');

require_once(dirname(__FILE__).'/../html/config.inc.php');

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
if($id<=0)
	exit();
	
$cmdline="/usr/bin/wget --load-cookies=".$cookiefile." ".$url." --no-check-certificate --post-data 'type=contact&backend=1&cmd=delete&id=".$id."' -O nagiosql.delete.contact";
echo "CMDLINE:\n";
echo $cmdline;
echo "\n";
$output=system($cmdline,$return_code);
	





?>