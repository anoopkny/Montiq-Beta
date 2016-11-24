<?php
//
// Script that runs after upgrade
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/includes/auth.inc.php');
require_once(dirname(__FILE__) . '/includes/utils.inc.php');
require_once(dirname(__FILE__) . '/includes/pageparts.inc.php');

// Initialization stuff
pre_init();
init_session();
check_prereqs();

// Do the actual upgrade (force upgrade from CLI only)
if (PHP_SAPI == 'cli') {
    do_upgrade();
} else {
    header("Location: index.php");
}

function do_upgrade()
{

    ///////////////////////////////////////////////////////
    ////// 2009R1.2 FIXES /////////////////////////////////
    ///////////////////////////////////////////////////////

    // Random PNP / nagios core backend password (used for performance graphs)
    if (get_component_credential("pnp", "username") != "nagiosxi") {
        $nagioscore_backend_password = random_string(6);
        $pnp_username = "nagiosxi";
        set_component_credential("pnp", "username", $pnp_username);
        set_component_credential("pnp", "password", $nagioscore_backend_password);
        $args = array(
            "username" => $pnp_username,
            "password" => $nagioscore_backend_password
        );
        submit_command(COMMAND_NAGIOSXI_SET_HTACCESS, serialize($args));
    }

    ///////////////////////////////////////////////////////
    ////// 2009R1.2D FIXES /////////////////////////////////
    ///////////////////////////////////////////////////////

    // Randomize default nagiosadmin backend ticket
    $uid = get_user_id("nagiosadmin");
    if ($uid > 0) {
        $backend_ticket = get_user_attr($uid, "backend_ticket");
        if ($backend_ticket == "1234") {
            change_user_attr($uid, "backend_ticket", random_string(8));
        }
    }
    
    // Set installation flags
    set_db_version();
    set_install_version();

    // Do update check
    do_update_check(true);
}