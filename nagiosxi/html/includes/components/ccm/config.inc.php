<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: config.inc.php
//  Desc: Defines the Nagios CCM configuration array. For Nagios XI it pulls in an pre-made
//        configuration file or for the CCM standalone version it generates a small amount of
//        config values that it requires.
//

// Global configuration options for Nagios CCM 
$CFG = array();

// Use Nagios XI's generated configs or create configs for CCM standalone (Nagios Core)
if (ENVIRONMENT == 'nagiosxi') {
    require_once('/usr/local/nagiosxi/etc/components/ccm_config.inc.php'); 
} else {

    // Nagios Core file locations 
    $CFG['plugins_directory'] = '/usr/local/nagios/libexec';
    $CFG['command_file'] = '/usr/local/nagios/var/rw/nagios.cmd'; 
    $CFG['lock_file'] = '/usr/local/nagios/var/nagios.lock';
 
    // MySQL database connection info 
    $CFG['db'] = array(
        'server'   => 'localhost',
        'port'     => '3306',
        'database' => 'nagiosql',
        'username' => 'nagiosql',
        'password' => 'n@gweb'
    );
}

// Misc. global settings used somewhere in the CCM
$CFG['common']['install'] = 'passed';
$CFG['domain'] = 'localhost';
$CFG['default_pagelimit'] = 15;
$CFG['lock_file'] = '/usr/local/nagios/var/nagios.lock';

if (file_exists('/usr/share/pear/HTML/Template/IT.php')) {
    $CFG['pear_include'] = '/usr/share/pear/HTML/Template/IT.php';
} else {
    $CFG['pear_include'] = '/usr/share/php/HTML/Template/IT.php';
}

$CFG['audit_send'] = '/usr/local/nagiosxi/scripts/send_to_auditlog.php';