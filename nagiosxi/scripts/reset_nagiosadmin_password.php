#!/usr/bin/php -q
<?php
//
// Reset Nagios XI 'nagiosadmin' admin password
// Copyright (c) 2016 Nagios Enterprises, LLC.
//  

define("SUBSYSTEM",1);

require_once(dirname(__FILE__).'/../html/config.inc.php');
require_once(dirname(__FILE__).'/../html/includes/utils.inc.php');


reset_nagiosadmin_password();


function reset_nagiosadmin_password()
{
    global $argv;

    $newpassword = "";
    $args = parse_argv($argv);

    if (array_key_exists("password", $args)) {
        $newpassword = grab_array_var($args, "password");
    }

    if (empty($newpassword)) {
        echo "Nagios XI Admin Password Reset Tool\n";
        echo "Copyright (c) 2016 Nagios Enterprises, LLC\n";
        echo "\n";
        echo "Usage: ".$argv[0]." --password=<newpassword>\n";
        echo "\n";
        echo "Resets password used to login to the Nagios XI interface as the nagiosadmin user.\n";
        exit(1);
    }

    // Make database connections
    $dbok = db_connect_all();
    if (!$dbok) {
        echo "ERROR CONNECTING TO DATABASES!\n";
        exit();
    }

    $uid = get_user_id("nagiosadmin");
    if ($uid <= 0) {
        echo "ERROR: Unable to get user id for nagiosadmin account.";
        exit(1);
    }

    change_user_attr($uid, "password", md5($newpassword));
    change_user_attr($uid, "login_attempts", 0);
    change_user_attr($uid, "last_attempt", 0);
    change_user_attr($uid, "last_edited", time());
    change_user_attr($uid, "last_edited_by", 1);

    echo "Password changed for: nagiosadmin\n";

    exit(0);
}
?>