<?php
//
// SCHEDULED BACKUPS COMPONENT : AJAX FUNCTION CALLS
//
// Copyright (c) 2013-2015 Nagios Enterprises, LLC.  All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization
pre_init();
init_session();
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    global $request;

    if (isset($request["type"])) {
        switch ($request["type"]) {
            case "ftp-conn":
                do_ftp_conn_test();
                break;
            case "ftp-upload":
                do_ftp_upload_test();
                break;
            case "ssh-conn":
                do_ssh_conn_test();
                break;
            case "ssh-scp":
                do_ssh_scp_test();
                break;
            case "local":
                do_local_check();
                break;
        }
    } else {
        echo _("Could not process the request.");
        exit();
    }
}

// FTP TEST FUNCTIONS

function do_ftp_conn_test()
{
    $ftp = grab_request_var("ftp");

    if (empty($ftp['server']) || empty($ftp['port'])) {
        print json_encode(array("error" => _("Could not establish a connection. Must enter server host/ip and port.")));
        exit();
    }

    // Create FTP connection
    if ($ftp['secure']) {
        $f = @ftp_ssl_connect($ftp['server'], $ftp['port']);
    } else {
        $f = @ftp_connect($ftp['server'], $ftp['port']);
    }

    if (!$f) {
        print json_encode(array("error" => _("Could not connect to FTP server.")));
        exit();
    } else {

        // Try to log in
        if (!@ftp_login($f, $ftp['username'], $ftp['password'])) {
            print json_encode(array("error" => _("Could not authenticate.")));
            ftp_close($f);
            exit();
        }

    }

    ftp_close($f);
    print json_encode(array("success" => _("Connection established. Username/Password OK.")));
}

function do_ftp_upload_test()
{
    $ftp = grab_request_var("ftp");

    if (empty($ftp['server']) || empty($ftp['port'])) {
        print json_encode(array("error" => _("Could not establish a connection. Must enter server host/ip and port.")));
        exit();
    }

    // Create FTP connection
    if ($ftp['secure']) {
        $f = @ftp_ssl_connect($ftp['server'], $ftp['port']);
    } else {
        $f = @ftp_connect($ftp['server'], $ftp['port']);
    }

    if (!$f) {
        print json_encode(array("error" => _("Could not connect to FTP server.")));
        exit();
    } else {

        // Try to log in
        if (!@ftp_login($f, $ftp['username'], $ftp['password'])) {
            print json_encode(array("error" => _("Could not authenticate.")));
            ftp_close($f);
            exit();
        }

    }

    // Set passive mode
    if ($ftp['passive']) {
        ftp_pasv($f, true);
    }

    // Try to upload a file
    $localfile = 'includes/ftp_test_upload.txt';
    $remotefile = $ftp['dir'] . 'ftp_test_upload.txt';
    if (!ftp_put($f, $remotefile, $localfile, FTP_BINARY)) {
        print json_encode(array("error" => _("Error uploading file. Directory given may not exist.")));
        ftp_close($f);
        exit();
    }


    ftp_close($f);
    print json_encode(array("success" => _('Test file "ftp_test_upload.txt" uploaded successfully.')));
}

function do_ssh_conn_test()
{
    $ssh = grab_request_var("ssh");

    if (empty($ssh['server']) || empty($ssh['port'])) {
        print json_encode(array("error" => _("Could not establish a connection. Must enter server host/ip and port.")));
        exit();
    }

    // Connect to SSH server
    if (!$s = @ssh2_connect($ssh['server'], $ssh['port'])) {
        print json_encode(array("error" => _("Could not establish a connection to " . $ssh['server'] . ".")));
        exit();
    }

    // Try loggin in
    if (!ssh2_auth_password($s, $ssh['username'], $ssh['password'])) {
        print json_encode(array("error" => _("Could not authenticate.")));
        exit();
    }

    print json_encode(array("success" => _('Connection established. Username/Password OK.')));
}

function do_ssh_scp_test()
{
    $ssh = grab_request_var("ssh");

    if (empty($ssh['server']) || empty($ssh['port'])) {
        print json_encode(array("error" => _("Could not establish a connection. Must enter server host/ip and port.")));
        exit();
    }

    // Connect to SSH server
    if (!$s = @ssh2_connect($ssh['server'], $ssh['port'])) {
        print json_encode(array("error" => _("Could not establish a connection to " . $ssh['server'] . ".")));
        exit();
    }

    // Try loggin in
    if (!ssh2_auth_password($s, $ssh['username'], $ssh['password'])) {
        print json_encode(array("error" => _("Could not authenticate.")));
        exit();
    }

    // Try sending the file
    $localfile = 'includes/ssh_test_secure_copy.txt';
    $remotefile = $ssh['dir'] . 'ssh_test_secure_copy.txt';
    if (!ssh2_scp_send($s, $localfile, $remotefile, 0644)) {
        print json_encode(array("error" => _("Could not transfer file. Permissions may be wrong or folder may not exist.")));
        exit();
    }

    print json_encode(array("success" => _('Test file "ssh_test_secure_copy.txt" transferred successfully.')));
}

function do_local_check()
{
    $local = grab_request_var("local");
    clearstatcache();

    if (!is_dir($local['dir'])) {
        print json_encode(array("error" => _("The directory specified does not exist.")));
        exit();
    }

    if (!is_writable($local['dir'])) {
        print json_encode(array("error" => _("The directory is likely not writeable by user 'nagios' or group 'nagios' - check permissions.")));
        exit();
    }

    print json_encode(array("success" => _('The directory exists and is writeable.')));
}

?>