<?php @"SourceGuardian"; //v10.1.6 ?><?php // Copyright (c) 2008-2016 Nagios Enterprises, LLC.  All rights reserved. ?><?php
if(!function_exists('sg_load')){$__v=phpversion();$__x=explode('.',$__v);$__v2=$__x[0].'.'.(int)$__x[1];$__u=strtolower(substr(php_uname(),0,3));$__ts=(@constant('PHP_ZTS') || @constant('ZEND_THREAD_SAFE')?'ts':'');$__f=$__f0='ixed.'.$__v2.$__ts.'.'.$__u;$__ff=$__ff0='ixed.'.$__v2.'.'.(int)$__x[2].$__ts.'.'.$__u;$__ed=@ini_get('extension_dir');$__e=$__e0=@realpath($__ed);$__dl=function_exists('dl') && function_exists('file_exists') && @ini_get('enable_dl') && !@ini_get('safe_mode');if($__dl && $__e && version_compare($__v,'5.2.5','<') && function_exists('getcwd') && function_exists('dirname')){$__d=$__d0=getcwd();if(@$__d[1]==':') {$__d=str_replace('\\','/',substr($__d,2));$__e=str_replace('\\','/',substr($__e,2));}$__e.=($__h=str_repeat('/..',substr_count($__e,'/')));$__f='/ixed/'.$__f0;$__ff='/ixed/'.$__ff0;while(!file_exists($__e.$__d.$__ff) && !file_exists($__e.$__d.$__f) && strlen($__d)>1){$__d=dirname($__d);}if(file_exists($__e.$__d.$__ff)) dl($__h.$__d.$__ff); else if(file_exists($__e.$__d.$__f)) dl($__h.$__d.$__f);}if(!function_exists('sg_load') && $__dl && $__e0){if(file_exists($__e0.'/'.$__ff0)) dl($__ff0); else if(file_exists($__e0.'/'.$__f0)) dl($__f0);}if(!function_exists('sg_load')){$__ixedurl='http://www.sourceguardian.com/loaders/download.php?php_v='.urlencode($__v).'&php_ts='.($__ts?'1':'0').'&php_is='.@constant('PHP_INT_SIZE').'&os_s='.urlencode(php_uname('s')).'&os_r='.urlencode(php_uname('r')).'&os_m='.urlencode(php_uname('m'));$__sapi=php_sapi_name();if(!$__e0) $__e0=$__ed;if(function_exists('php_ini_loaded_file')) $__ini=php_ini_loaded_file(); else $__ini='php.ini';if((substr($__sapi,0,3)=='cgi')||($__sapi=='cli')||($__sapi=='embed')){$__msg="\nPHP script '".__FILE__."' is protected by SourceGuardian and requires a SourceGuardian loader '".$__f0."' to be installed.\n\n1) Download the required loader '".$__f0."' from the SourceGuardian site: ".$__ixedurl."\n2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="\n3) Edit ".$__ini." and add 'extension=".$__f0."' directive";}}$__msg.="\n\n";}else{$__msg="<html><body>PHP script '".__FILE__."' is protected by <a href=\"http://www.sourceguardian.com/\">SourceGuardian</a> and requires a SourceGuardian loader '".$__f0."' to be installed.<br><br>1) <a href=\"".$__ixedurl."\" target=\"_blank\">Click here</a> to download the required '".$__f0."' loader from the SourceGuardian site<br>2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="<br>3) Edit ".$__ini." and add 'extension=".$__f0."' directive<br>4) Restart the web server";}}$msg.="</body></html>";}die($__msg);exit();}}return sg_load('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');
