<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: archivejson.php 2854 2014-08-23 18:29:04Z swilkerson $

require_once(dirname(__FILE__) . '/../coreuiproxy.inc.php');
header('Content-Type: application/json');
coreui_do_proxy("archivejson.cgi");
