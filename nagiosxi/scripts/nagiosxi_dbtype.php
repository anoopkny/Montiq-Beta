#!/usr/bin/php -q
<?php
// RETURN NAGIOSXI DBTYPE FROM CFG
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$
define("SUBSYSTEM",1);
require_once(dirname(__FILE__).'/../html/config.inc.php');

$default_dbtype = "psql";
$dbtype = grab_array_var($cfg["db_info"]["nagiosxi"], "dbtype", $default_dbtype);

echo $dbtype;
exit(0);
?>