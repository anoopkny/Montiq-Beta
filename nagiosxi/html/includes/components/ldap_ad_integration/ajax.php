<?php
//
// Active Directory Integration
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__).'/../../common.inc.php');
require_once(dirname(__FILE__).'/../componenthelper.inc.php');
include_once(dirname(__FILE__).'/ldap_ad_integration.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables, check prereqs and authentication
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    $cmd = grab_request_var('cmd', '');

    switch ($cmd)
    {
        case 'getcerts':
            get_certificates();
            break;

        case 'getcertinfo':
            get_certificate_info();
            break;

        case 'addcert':
            add_certificate();
            break;

        case 'delcert':
            delete_certificate();
            break;

        case 'getserver':
            get_ldap_ad_server();
            break;

        case 'getxiusers':
            get_xi_users();
            break;

        default:
            break;
    }
}

function get_certificate_info()
{
    $cert = grab_request_var('cert', '');

    $info = openssl_x509_parse($cert);
    if ($info === false) {
        $output = array("message" => _("Certificate is not valid."),
                        "error" => 1);
    } else {
        $output = array("certinfo" => $info,
                        "error" => 0);
    }

    print @json_encode($output);
}

function add_certificate()
{
    $cert = grab_request_var('cert', '');
    $info = openssl_x509_parse($cert);
    if ($info === false) {
        $output = array("message" => _("Certificate is not valid."),
                        "error" => 1);
        print json_encode($output);
        return;
    }

    $hostname = $info['subject']['CN'];
    $issuer = $info['issuer']['CN'];
    $id = uniqid();

    // Place the cert into the proper area (/etc/openldap/cacerts)
    $cert_file = "/etc/openldap/certs/$id.crt";
    $pem_file = "/etc/openldap/certs/$id.pem";
    file_put_contents($cert_file, $cert);
    shell_exec("openssl x509 -in $cert_file -text > $pem_file;");
    
    // Get the hashed version of the certificate
    $hash = trim(shell_exec("openssl x509 -noout -hash -in $pem_file"));

    // Before we go any farther, let's make sure this cert is unique
    $exists = false;
    if ($dh = opendir("/etc/openldap/cacerts")) {
        while (false !== ($entry = readdir($dh))) {
            if ($entry == $hash.".0") {
                $exists = true;
            }
        }
        closedir($dh);
    }

    // Fail out if it exists or we can continue
    if ($exists) {
        // Remove files we created
        shell_exec("rm -rf $cert_file $pem_file");
        $output = array("message" => _("This certificate has already been added."),
                        "error" => 1);
        print json_encode($output);
        return;
    }

    // Link the actual file to the ca certs directory
    shell_exec("cd /etc/openldap/cacerts; ln -s $pem_file ".$hash.".0;");

    // Get the list of certificates already installed
    $certs = get_option('active_directory_component_ca_certs');
    if (empty($certs)) {
        $certs = array();
    } else {
        $certs = unserialize(base64_decode($certs));
    }

    $data = array("id" => $id,
                  "host" => $hostname,
                  "issuer" => $issuer,
                  "cert_file" => $cert_file,
                  "pem_file" => $pem_file,
                  "valid_from" => $info['validFrom_time_t'],
                  "valid_to" => $info['validTo_time_t']);

    // Save data into the certs option
    $certs[] = $data;
    $encoded = base64_encode(serialize($certs));
    set_option('active_directory_component_ca_certs', $encoded);

    // Send cmdsubsys command to restart apache
    submit_command(COMMAND_RESTART_HTTP);

    $output = array("message" => _("The certificate was added successfully."),
                    "error" => 0);
    print json_encode($output);
}

function delete_certificate()
{
    $cert_id = grab_request_var('cert_id', '');
    if (empty($cert_id)) {
        $output = array("message" => _("Must pass a valid certificate ID."),
                        "error" => 1);
        print json_encode($output);
    }

    // Get all the certs
    $certs = get_option('active_directory_component_ca_certs');
    if (empty($certs)) {
        $certs = array();
    } else {
        $certs = unserialize(base64_decode($certs));
    }

    // Loop through all the certificates and remove it
    $remove = array();
    $new_certs = array();
    if (count($certs) > 0) {
        foreach ($certs as $cert) {
            if ($cert['id'] != $cert_id) {
                $new_certs[] = $cert;
            } else {
                $remove = $cert;
            }
        }
    }

    // Remove the cert from the filesystem
    if (!empty($remove)) {
        shell_exec("rm -rf /etc/openldap/cacerts/`openssl x509 -noout -hash -in ".$remove['pem_file']."`.0;");
        shell_exec("rm -rf ".$remove['cert_file']." ".$remove['pem_file'].";");
    }

    $encoded = base64_encode(serialize($new_certs));
    set_option('active_directory_component_ca_certs', $encoded);
    return;
}

function get_certificates()
{
    $certs = get_option('active_directory_component_ca_certs');
    if (empty($certs)) {
        $certs = array();
    } else {
        $certs = unserialize(base64_decode($certs));
    }

    // Return list of certs as JSON object
    print json_encode($certs);
}

function get_ldap_ad_server()
{
    $server_id = grab_request_var('server_id', '');
    $servers = get_option('ldap_ad_integration_component_servers');
    if (!empty($servers)) {
        $servers = unserialize(base64_decode($servers));
    } else {
        return;
    }

    // Check for server id in all servers
    foreach ($servers as $server) {
        if ($server['id'] == $server_id) {
            // Found the server, return it in JSON
            print json_encode($server);
        }
    }
}

function get_xi_users()
{
    $sql = "SELECT * FROM xi_users WHERE TRUE ORDER BY xi_users.email ASC";
    $rs = exec_sql_query(DB_NAGIOSXI, $sql);

    $users = array();
    foreach ($rs as $user) {
        $users[] = $user['username'];
    }

    print json_encode($users);
}