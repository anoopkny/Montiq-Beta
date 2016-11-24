<?php
//
// Backend Handler Includes
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

// Standard return handlers
require_once(dirname(__FILE__) . '/handler-auditlog.inc.php');
require_once(dirname(__FILE__) . '/handler-commands.inc.php');
require_once(dirname(__FILE__) . '/handler-misc.inc.php');
require_once(dirname(__FILE__) . '/handler-objects.inc.php');
require_once(dirname(__FILE__) . '/handler-perms.inc.php');
require_once(dirname(__FILE__) . '/handler-reports.inc.php');
require_once(dirname(__FILE__) . '/handler-status.inc.php');
require_once(dirname(__FILE__) . '/handler-users.inc.php');

// Config handlers
require_once(dirname(__FILE__) . '/handler-config.inc.php');

// System handlers
require_once(dirname(__FILE__) . '/handler-systat.inc.php');
