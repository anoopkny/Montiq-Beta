#!/usr/bin/php -q
<?php
//
// Nagios Core Global Event Handler
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//  

require_once(dirname(__FILE__) . '/handle_nagioscore.inc.php');

handle_event(EVENTSOURCE_NAGIOSCORE, EVENTTYPE_STATECHANGE);

?>