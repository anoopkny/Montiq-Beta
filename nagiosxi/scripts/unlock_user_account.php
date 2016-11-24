#!/usr/bin/php -q
<?php
// UNLOCK USER ACCOUNT
//
// Copyright (c) 2016 Nagios Enterprises, LLC.
//  

define("SUBSYSTEM", 1);

require_once(dirname(__FILE__) . '/../html/config.inc.php');
require_once(dirname(__FILE__) . '/../html/includes/utils.inc.php');

unlock_account();
	
function unlock_account() {

	global $argv;
	$username = "";
	$args = parse_argv($argv);
	
	if (array_key_exists("username", $args)) {
		$username = grab_array_var($args, "username");
	}
	
	if ($username == "") {
		echo "Nagios XI Unlock User Account Tool\n";
		echo "Copyright (c) 2016 Nagios Enterprises, LLC\n";
		echo "\n";
		echo "Usage: {$argv[0]} --username=<username>\n";
		echo "\n";
		echo "Unlocks specified Nagios XI user account.\n";
		exit(1);
	}
	
	// make database connections
	$db_ok = db_connect_all();
	if ($db_ok == false) {
		echo "ERROR CONNECTING TO DATABASES!\n";
		exit(1);
	}
	
	$user_id = get_user_id($username);
	if ($user_id <= 0) {
		echo "ERROR: Unable to get user id for \"$username\" account.\n";
		exit(1);
	}
		
    change_user_attr($user_id, "login_attempts", 0);
    change_user_attr($user_id, "last_attempt", 0);
	
	echo "Account for \"$username\" has been unlocked.\n";
		
	exit(0);
}

?>
