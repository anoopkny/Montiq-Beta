<?php @"SourceGuardian"; //v10.1.6 ?><?php // Copyright (c) 2008-2016 Nagios Enterprises, LLC.  All rights reserved. ?><?php
if(!function_exists('sg_load')){$__v=phpversion();$__x=explode('.',$__v);$__v2=$__x[0].'.'.(int)$__x[1];$__u=strtolower(substr(php_uname(),0,3));$__ts=(@constant('PHP_ZTS') || @constant('ZEND_THREAD_SAFE')?'ts':'');$__f=$__f0='ixed.'.$__v2.$__ts.'.'.$__u;$__ff=$__ff0='ixed.'.$__v2.'.'.(int)$__x[2].$__ts.'.'.$__u;$__ed=@ini_get('extension_dir');$__e=$__e0=@realpath($__ed);$__dl=function_exists('dl') && function_exists('file_exists') && @ini_get('enable_dl') && !@ini_get('safe_mode');if($__dl && $__e && version_compare($__v,'5.2.5','<') && function_exists('getcwd') && function_exists('dirname')){$__d=$__d0=getcwd();if(@$__d[1]==':') {$__d=str_replace('\\','/',substr($__d,2));$__e=str_replace('\\','/',substr($__e,2));}$__e.=($__h=str_repeat('/..',substr_count($__e,'/')));$__f='/ixed/'.$__f0;$__ff='/ixed/'.$__ff0;while(!file_exists($__e.$__d.$__ff) && !file_exists($__e.$__d.$__f) && strlen($__d)>1){$__d=dirname($__d);}if(file_exists($__e.$__d.$__ff)) dl($__h.$__d.$__ff); else if(file_exists($__e.$__d.$__f)) dl($__h.$__d.$__f);}if(!function_exists('sg_load') && $__dl && $__e0){if(file_exists($__e0.'/'.$__ff0)) dl($__ff0); else if(file_exists($__e0.'/'.$__f0)) dl($__f0);}if(!function_exists('sg_load')){$__ixedurl='http://www.sourceguardian.com/loaders/download.php?php_v='.urlencode($__v).'&php_ts='.($__ts?'1':'0').'&php_is='.@constant('PHP_INT_SIZE').'&os_s='.urlencode(php_uname('s')).'&os_r='.urlencode(php_uname('r')).'&os_m='.urlencode(php_uname('m'));$__sapi=php_sapi_name();if(!$__e0) $__e0=$__ed;if(function_exists('php_ini_loaded_file')) $__ini=php_ini_loaded_file(); else $__ini='php.ini';if((substr($__sapi,0,3)=='cgi')||($__sapi=='cli')||($__sapi=='embed')){$__msg="\nPHP script '".__FILE__."' is protected by SourceGuardian and requires a SourceGuardian loader '".$__f0."' to be installed.\n\n1) Download the required loader '".$__f0."' from the SourceGuardian site: ".$__ixedurl."\n2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="\n3) Edit ".$__ini." and add 'extension=".$__f0."' directive";}}$__msg.="\n\n";}else{$__msg="<html><body>PHP script '".__FILE__."' is protected by <a href=\"http://www.sourceguardian.com/\">SourceGuardian</a> and requires a SourceGuardian loader '".$__f0."' to be installed.<br><br>1) <a href=\"".$__ixedurl."\" target=\"_blank\">Click here</a> to download the required '".$__f0."' loader from the SourceGuardian site<br>2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="<br>3) Edit ".$__ini." and add 'extension=".$__f0."' directive<br>4) Restart the web server";}}$msg.="</body></html>";}die($__msg);exit();}}return sg_load('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');
