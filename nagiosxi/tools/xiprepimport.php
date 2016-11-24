#!/usr/bin/php -q
<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

define("SUBSYSTEM",1);

require_once(dirname(__FILE__).'/../html/config.inc.php');

$url="http://localhost".$cfg['component_info']['nagiosql']['direct_url']."/admin/contacts.php";
$cookiefile="nagiosql.cookies";

$args=parse_argv($argv);
$c=count($args);
if($c<1){
	echo "Nagios XI Configuration Import Prep Utilitiy\n";
	echo "Copyright (c) 2009 Nagios Enterprises, LLC\n";
	echo "License: GPL\n";
	echo "Usage: xiprepimport.php [configfile]\n";
	echo "\n";
	exit();
	}
	
$source=$args[0];

process_config_file($source);

function process_config_file($source){

	$fh=fopen($source,"r");
	if(!$fh){
		echo "Error opening config file '".$source."'!\n";
		exit(2);
		}
		
	// initialize arrays
	$thisobject=array();
	
	$inservice=false;
	$objectname="";
	
	// open "others file"
	//$fname="_others.cfg";
	$fname=basename($source);
	// make sure others file and orig files aren't the same
	if($fname==$source){
		echo "Error: Source file cannot reside in the current working directory\n";
		exit(2);
		}
	$fho=fopen($fname,"a+");
	if(!$fho){
		echo "Cannot open file '".$fname."' for writing!\n";
		exit(2);
		}
	
	while(!feof($fh)){
		$buf=fgets($fh);
		$buf=trim($buf);
		if(strstr($buf,'#')==$buf)
			continue;
		if($buf=="")
			continue;
		//echo $buf."\n";
		
		// start of service definition
		if((strstr($buf,"define service{")==$buf) || (strstr($buf,"define service ")==$buf)){
			$thisobject=array();
			$objectname="";
			$thisobject[]=$buf;
			$inservice=true;
			}
				
		// mid or end of service definition
		else if($inservice==true){
		
			// end of service definition
			if(strstr($buf,"}")==$buf){
				$thisobject[]=$buf;
				$inobject=false;
				
				$fname=$objectname;
				if($fname=="")
					$fname="_empty_host";
				$fname.=".cfg";
				write_entries_to_file($fname,$thisobject);
				}
		
			//else
			else{
			
				$thisobject[]=$buf;
				
				$parts=preg_split("/[\s]+/",$buf);
				//$parts=explode("\t",$buf);
				
				//echo "PARTS: ";
				//print_r($parts);
				$var=trim(array_shift($parts));
				$val=trim(implode(' ', $parts));

				/* remove spaces */
				$val=str_replace(', ', ',',$val);
				
				if(strstr($var,"host_name")==$var){
					if($objectname=="")
						$objectname=get_object_name($val);
					else
						$objectname="_multiple_hosts";
					}
				else if(strstr($var,"hostgroup_name")==$var)
						$objectname="_multiple_hosts";
				}
			}
			
		// other definitions/entries
		else{
			//echo "OTHER: $buf\n";
			$buf2 = str_replace(', ', ',', $buf);
			//echo "OTHER: $buf2\n";
			fprintf($fho,"%s\n",$buf2);
			}
		}
	

	fclose($fh);
	fclose($fho);
	}
	
function get_object_name($rawname){

	$multiname="_multiple_hosts";

	if(strstr($rawname,"*"))
		$name=$multiname;
	else if(strstr($rawname,","))
		$name=trim($multiname);
	else if(strstr($rawname,"!"))
		$name=$multiname;
	else{
		$length=strpos($rawname,';');
		if($length===false){
			$name=trim($rawname);
			}
		else{
			$name=trim(substr($rawname,0,$length));
			}
		}
	return $name;
	}
	
function write_entries_to_file($fname,$entries){

	//echo "WRITING: $fname\n";
	//print_r($entries);
	//return;

	$fh=fopen($fname,"a+");
	if(!$fh){
		fclose($fh);
		echo "Error opening ".$fname." for writing!\n";
		exit(2);
		}
		
	foreach($entries as $entry){
		fprintf($fh,"%s\n",$entry);
		}
		
	fclose($fh);
	}

	
?>