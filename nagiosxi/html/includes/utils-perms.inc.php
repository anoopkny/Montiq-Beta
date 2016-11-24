<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 03/22/2008
// $Id$

//require_once(dirname(__FILE__).'/common.inc.php');


////////////////////////////////////////////////////////////////////////
// PERMISSIONS FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param int $userid
 *
 * @return bool
 */
function is_authorized_to_configure_objects($userid = 0)
{

    if ($userid == 0)
        $userid = $_SESSION["user_id"];

    // admins can do everything
    if (is_admin($userid) == true)
        return true;

    // block users who are not authorized to configure objects
    $authcfgobjects = get_user_meta($userid, "authorized_to_configure_objects");
    if ($authcfgobjects == 1)
        return true;
    else
        return false;

}

/**
 * @param int $userid
 *
 * @return bool
 */
function is_authorized_for_monitoring_system($userid = 0)
{

    if ($userid == 0 && isset($_SESSION["user_id"]))
        $userid = $_SESSION["user_id"];

    // admins can do everything
    if (is_admin($userid) == true)
        return true;

    $authsys = get_user_meta($userid, "authorized_for_monitoring_system");
    if ($authsys == 1)
        return true;
    else
        return false;

}

/**
 * @param int $userid
 *
 * @return bool
 */
function is_authorized_for_all_objects($userid = 0)
{

    if ($userid == 0) {

        // subsystem jobs don't get limited
        if (defined("SUBSYSTEM")) {
            return true;
        }

        // get user id from session
        if (isset($_SESSION["user_id"]))
            $userid = $_SESSION["user_id"];
    }

    // admins can do everything
    if (is_admin($userid) == true)
        return true;

    // some other users can see everything
    $authallobjects = get_user_meta($userid, "authorized_for_all_objects");
    if ($authallobjects == 1)
        return true;
    else
        return false;

}

/**
 * @param int $userid
 *
 * @return bool
 */
function is_authorized_for_all_object_commands($userid = 0)
{

    if ($userid == 0)
        $userid = $_SESSION["user_id"];

    // admins can do everything
    if (is_admin($userid) == true)
        return true;

    // some other users can control everything
    $authallobjects = get_user_meta($userid, "authorized_for_all_object_commands");
    if ($authallobjects == 1)
        return true;
    else
        return false;

}

