<?php @"SourceGuardian"; //v10.1.6 ?><?php // Copyright (c) 2008-2016 Nagios Enterprises, LLC.  All rights reserved. ?><?php
if(!function_exists('sg_load')){$__v=phpversion();$__x=explode('.',$__v);$__v2=$__x[0].'.'.(int)$__x[1];$__u=strtolower(substr(php_uname(),0,3));$__ts=(@constant('PHP_ZTS') || @constant('ZEND_THREAD_SAFE')?'ts':'');$__f=$__f0='ixed.'.$__v2.$__ts.'.'.$__u;$__ff=$__ff0='ixed.'.$__v2.'.'.(int)$__x[2].$__ts.'.'.$__u;$__ed=@ini_get('extension_dir');$__e=$__e0=@realpath($__ed);$__dl=function_exists('dl') && function_exists('file_exists') && @ini_get('enable_dl') && !@ini_get('safe_mode');if($__dl && $__e && version_compare($__v,'5.2.5','<') && function_exists('getcwd') && function_exists('dirname')){$__d=$__d0=getcwd();if(@$__d[1]==':') {$__d=str_replace('\\','/',substr($__d,2));$__e=str_replace('\\','/',substr($__e,2));}$__e.=($__h=str_repeat('/..',substr_count($__e,'/')));$__f='/ixed/'.$__f0;$__ff='/ixed/'.$__ff0;while(!file_exists($__e.$__d.$__ff) && !file_exists($__e.$__d.$__f) && strlen($__d)>1){$__d=dirname($__d);}if(file_exists($__e.$__d.$__ff)) dl($__h.$__d.$__ff); else if(file_exists($__e.$__d.$__f)) dl($__h.$__d.$__f);}if(!function_exists('sg_load') && $__dl && $__e0){if(file_exists($__e0.'/'.$__ff0)) dl($__ff0); else if(file_exists($__e0.'/'.$__f0)) dl($__f0);}if(!function_exists('sg_load')){$__ixedurl='http://www.sourceguardian.com/loaders/download.php?php_v='.urlencode($__v).'&php_ts='.($__ts?'1':'0').'&php_is='.@constant('PHP_INT_SIZE').'&os_s='.urlencode(php_uname('s')).'&os_r='.urlencode(php_uname('r')).'&os_m='.urlencode(php_uname('m'));$__sapi=php_sapi_name();if(!$__e0) $__e0=$__ed;if(function_exists('php_ini_loaded_file')) $__ini=php_ini_loaded_file(); else $__ini='php.ini';if((substr($__sapi,0,3)=='cgi')||($__sapi=='cli')||($__sapi=='embed')){$__msg="\nPHP script '".__FILE__."' is protected by SourceGuardian and requires a SourceGuardian loader '".$__f0."' to be installed.\n\n1) Download the required loader '".$__f0."' from the SourceGuardian site: ".$__ixedurl."\n2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="\n3) Edit ".$__ini." and add 'extension=".$__f0."' directive";}}$__msg.="\n\n";}else{$__msg="<html><body>PHP script '".__FILE__."' is protected by <a href=\"http://www.sourceguardian.com/\">SourceGuardian</a> and requires a SourceGuardian loader '".$__f0."' to be installed.<br><br>1) <a href=\"".$__ixedurl."\" target=\"_blank\">Click here</a> to download the required '".$__f0."' loader from the SourceGuardian site<br>2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="<br>3) Edit ".$__ini." and add 'extension=".$__f0."' directive<br>4) Restart the web server";}}$msg.="</body></html>";}die($__msg);exit();}}return sg_load('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');