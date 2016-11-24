<?php
//
// Common Backend Includes
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

// Backend defines
require_once(dirname(__FILE__) . '/constants.inc.php');

// Backend-specific routines
require_once(dirname(__FILE__) . '/errors.inc.php');
require_once(dirname(__FILE__) . '/utils.inc.php');
require_once(dirname(__FILE__) . '/handlers.inc.php');

// Use frontend logic for most stuff
require_once(dirname(__FILE__) . '/../../includes/common.inc.php');
require_once(dirname(__FILE__) . '/../../includes/utils.inc.php');