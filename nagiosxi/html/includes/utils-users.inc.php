<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 03/22/2008
// $Id$

//require_once(dirname(__FILE__).'/common.inc.php');

////////////////////////////////////////////////////////////////////////////////
// XML DATA
////////////////////////////////////////////////////////////////////////////////

/**
 * @param array $args
 *
 * @return SimpleXMLElement
 */
function get_xml_users($args = array())
{
    $x = simplexml_load_string(get_users_xml_output($args));
    //print_r($x);
    return $x;
}


////////////////////////////////////////////////////////////////////////
// USER ACCOUNT FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param $username
 * @param $password
 * @param $name
 * @param $email
 * @param $level
 * @param $forcepasschange
 * @param $addcontact
 * @param $errmsg
 *
 * @return int|null
 */
function add_user_account($username, $password, $name, $email, $level, $forcepasschange, $addcontact, $api_enabled, &$errmsg)
{
    global $db_tables;

    $username = strtolower($username);

    $error = false;
    $errors = 0;

    $user_id = -1;

    // make sure we have required variables
    if (!have_value($username)) {
        $error = true;
        $errmsg[$errors++] = _("Username is blank.");
    }
    if (!have_value($email)) {
        $error = true;
        $errmsg[$errors++] = _("Email address is blank.");
    } else if (!valid_email($email)) {
        $error = true;
        $errmsg[$errors++] = _("Email address is invalid.");
    }
    if (!have_value($name)) {
        $error = true;
        $errmsg[$errors++] = _("Name is blank.");
    }
    if (!have_value($password)) {
        $error = true;
        $errmsg[$errors++] = _("Password is blank.");
    }
    if (!have_value($level)) {
        $error = true;
        $errmsg[$errors++] = _("Security level is blank.");
    }

    // does user account already exist?
    if (is_valid_user($username) == true) {
        $error = true;
        $errmsg[$errors++] = _("An account with that username already exists.");
    }

    // does the password meet the complexity requirements?
    if (!password_meets_complexity_requirements($password)) {
        $error = true;
        $errmsg[$errors++] = _("Specified password does not meet the complexity requirements.") . get_password_requirements_message();
    }
    
    // generate random backend ticket string
    $backend_ticket = random_string(64);

    // add account
    if ($error == false) {
        $sql = "INSERT INTO " . $db_tables[DB_NAGIOSXI]["users"] . " (username,email,name,password,backend_ticket) VALUES ('" . escape_sql_param($username, DB_NAGIOSXI) . "','" . escape_sql_param($email, DB_NAGIOSXI) . "','" . escape_sql_param($name, DB_NAGIOSXI) . "','" . md5($password) . "','" . $backend_ticket . "')";
        if (!exec_sql_query(DB_NAGIOSXI, $sql)) {
            $error = true;
            $errmsg[$errors++] = _("Failed to add account") . ": " . get_sql_error(DB_NAGIOSXI);
        } else
            $user_id = get_sql_insert_id(DB_NAGIOSXI, "xi_users_user_id_seq");
    }
    if ($error == false && $user_id < 1) {
        $errmsg[$errors++] = "Unable to get insert id for new user account";
        $error = true;
    }
    if ($error == false) {
        // assign privs
        if (!set_user_meta($user_id, 'userlevel', $level)) {
            $error = true;
            $errmsg[$errors++] = _("Unable to assign account privileges.");
        }
        // force password change at next login
        if ($forcepasschange == true)
            set_user_meta($user_id, 'forcepasswordchange', '1');

        // notification defaults
        set_user_meta($user_id, 'enable_notifications', '1', false);
        set_user_meta($user_id, 'notify_by_email', '1', false);

        set_user_meta($user_id, 'notify_host_down', '1', false);
        set_user_meta($user_id, 'notify_host_unreachable', '1', false);
        set_user_meta($user_id, 'notify_host_recovery', '1', false);
        set_user_meta($user_id, 'notify_host_flapping', '1', false);
        set_user_meta($user_id, 'notify_host_downtime', '1', false);
        set_user_meta($user_id, 'notify_service_warning', '1', false);
        set_user_meta($user_id, 'notify_service_unknown', '1', false);
        set_user_meta($user_id, 'notify_service_critical', '1', false);
        set_user_meta($user_id, 'notify_service_recovery', '1', false);
        set_user_meta($user_id, 'notify_service_flapping', '1', false);
        set_user_meta($user_id, 'notify_service_downtime', '1', false);

        // set password change time
        change_user_attr($user_id, 'last_password_change', time());

        // set api enabled
        change_user_attr($user_id, 'api_enabled', $api_enabled);
        change_user_attr($user_id, 'api_key', random_string(64));

        $notification_times = array();
        for ($day = 0; $day < 7; $day++) {
            $notification_times[$day] = array(
                "start" => "00:00",
                "end" => "24:00",
            );
        }
        $notification_times_raw = serialize($notification_times);
        set_user_meta($user_id, 'notification_times', $notification_times_raw, false);
    }

    // add/update corresponding contact to/in Nagios Core
    if ($error == false && $addcontact == true) {
        $contactargs = array(
            "contact_name" => $username,
            "alias" => $name,
            "email" => $email,
        );
        add_nagioscore_contact($contactargs);
    }

    // do user addition callbacks
    if ($error == false) {
        $cbargs = array(
            'username' => $username,
            'user_id' => $user_id,
            'password' => $password,
            );
        do_callbacks(CALLBACK_USER_CREATED, $cbargs);
    }

    if ($error == false) {
        send_to_audit_log("New user account '" . $username . "' created", AUDITLOGTYPE_SECURITY);
        return $user_id;
    } else
        return null;
}


/**
 * @param $user_id
 * @param $attr
 *
 * @return null
 */
function get_user_attr($user_id, $attr)
{
    global $db_tables;

    // use logged in user's id
    if ($user_id == 0 && isset($_SESSION["user_id"]))
        $user_id = $_SESSION["user_id"];

    // make sure we have required variables
    if (!have_value($user_id))
        return null;
    if (!have_value($attr))
        return null;

    // get attribute
    $sql = "SELECT " . escape_sql_param($attr, DB_NAGIOSXI) . " FROM " . $db_tables[DB_NAGIOSXI]["users"] . " WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql, false))) {
        if ($rs->MoveFirst()) {
            return $rs->fields[$attr];
        }
    }
    return null;
}


/**
 * @param $user_id
 * @param $attr
 * @param $value
 *
 * @return bool
 */
function change_user_attr($user_id, $attr, $value)
{
    global $db_tables;

    // use logged in user's id
    if ($user_id == 0)
        $user_id = $_SESSION["user_id"];

    // make sure we have required variables
    if (!have_value($user_id))
        return false;
    if (!have_value($attr))
        return false;

    // update attribute
    $sql = "UPDATE " . $db_tables[DB_NAGIOSXI]["users"] . " SET " . escape_sql_param($attr, DB_NAGIOSXI) . "='" . escape_sql_param($value, DB_NAGIOSXI) . "' WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "'";

    if (!exec_sql_query(DB_NAGIOSXI, $sql))
        return false;
    return true;
}

/**
* @param $user_id
* @param $password
*/
function do_user_password_change_callback($user_id, $password) {

    // use logged in user's id if 0
    if ($user_id == 0)
        $user_id = $_SESSION["user_id"];

    $args = array(
        'user_id' => $user_id,
        'username' => get_user_attr($user_id, "username"),
        'password' => $password,
        );
    do_callbacks(CALLBACK_USER_PASSWORD_CHANGED, $args);
}


// checks if a user account exists
/**
 * @param $username
 *
 * @return bool
 */
function is_valid_user($username)
{
    $id = get_user_id($username);
    if (!have_value($id))
        return false;
    return true;
}


// checks if a user account exists (using id)
/**
 * @param $userid
 *
 * @return bool
 */
function is_valid_user_id($userid)
{
    global $db_tables;

    // force lowercase comparison
    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["users"] . " WHERE user_id='" . escape_sql_param($userid, DB_NAGIOSXI) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
        if ($rs->RecordCount() > 0)
            return $rs->fields["user_id"];
    }
    return false;
}


/**
 * @param $username
 *
 * @return null
 */
function get_user_id($username)
{
    global $db_tables;

    // force lowercase comparison
    // update - must used postgres LOWER() function as suggested by Mike G
    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["users"] . " WHERE lower(username)='" . escape_sql_param(strtolower($username), DB_NAGIOSXI) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
        if ($rs->RecordCount() > 0)
            return $rs->fields["user_id"];
    }
    return null;
}


// get all users in the database
/**
 * @return null
 */
function get_user_list()
{
    global $db_tables;

    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["users"] . " ORDER BY username ASC";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql)))
        return $rs;
    return null;
}


/**
 * @param      $userid
 * @param bool $deletecontact
 *
 * @return bool
 */
function delete_user_id($userid, $deletecontact = true)
{
    global $db_tables;

    $username = get_user_attr($userid, "username");

    // log it
    send_to_audit_log("User deleted account '" . $username . "'", AUDITLOGTYPE_SECURITY);

    // delete corresponding contact from Nagios Core
    if ($deletecontact == true) {
        delete_nagioscore_contact($username);
        //return;
    }

    // delete user account
    $sql = "DELETE FROM " . $db_tables[DB_NAGIOSXI]["users"] . " WHERE user_id='" . escape_sql_param($userid, DB_NAGIOSXI) . "'";
    if (!($rs = exec_sql_query(DB_NAGIOSXI, $sql)))
        return false;

    // delete user meta
    $sql = "DELETE FROM " . $db_tables[DB_NAGIOSXI]["usermeta"] . " WHERE user_id='" . escape_sql_param($userid, DB_NAGIOSXI) . "'";
    if (!($rs = exec_sql_query(DB_NAGIOSXI, $sql)))
        return false;


    return true;
}


////////////////////////////////////////////////////////////////////////
// USER AUTHORIZATION FUNCTION
////////////////////////////////////////////////////////////////////////

/**
 * @param int $user_id
 *
 * @return bool
 */
function is_admin($user_id = 0)
{
    // subsystem cron jobs run with admin privileges
    if (defined("SUBSYSTEM"))
        return true;

    // use logged in user's id
    if ($user_id == 0 && isset($_SESSION["user_id"]))
        $user_id = $_SESSION["user_id"];

    // get user's level
    if (empty($_SESSION['userlevel'])) {
        $level = get_user_meta($user_id, 'userlevel');
    } else {
        $level = $_SESSION['userlevel'];
    }

    // return true if admin or false if not
    if (intval($level) == L_GLOBALADMIN)
        return true;
    else
        return false;
}


/**
 * @return array
 */
function get_authlevels()
{

    $levels = array(
        L_USER => _("User"),
        L_GLOBALADMIN => _("Admin")
    );

    return $levels;
}


/**
 * @param $level
 *
 * @return bool
 */
function is_valid_authlevel($level)
{

    $levels = get_authlevels();

    return array_key_exists($level, $levels);
}


////////////////////////////////////////////////////////////////////////
// MISC USER FUNCTION
////////////////////////////////////////////////////////////////////////

/**
 * @param int $userid
 *
 * @return bool
 */
function is_advanced_user($userid = 0)
{

    if ($userid == 0)
        $userid = $_SESSION["user_id"];

    // admins are experts
    if (is_admin($userid) == true)
        return true;

    // certain users are experts
    $advanceduser = get_user_meta($userid, "advanced_user");
    if ($advanceduser == 1)
        return true;
    else
        return false;

}

/**
 * @param int $userid
 *
 * @return bool
 */
function is_readonly_user($userid = 0)
{
    if ($userid == 0) {
        if (!empty($_SESSION['user_id'])) {
            $userid = $_SESSION['user_id'];
        }
    }

    // Admins are always read/write
    if (is_admin($userid) == true) {
        return false;
    }

    // Certain users are experts
    $readonlyuser = get_user_meta($userid, "readonly_user");
    if ($readonlyuser == 1) {
        return true;
    } else {
        return false;
    }
}


////////////////////////////////////////////////////////////////////////
// USER META DATA FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @param $user_id
 * @param $key
 *
 * @return null
 */
function get_user_meta($user_id, $key, $default=null)
{
    global $db_tables;

    // use logged in user's id
    if ($user_id == 0) {
        if (!isset($_SESSION["user_id"])) {
            return null;
        } else {
            $user_id = $_SESSION["user_id"];
        }
    }

    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["usermeta"] . " WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "' AND keyname='" . escape_sql_param($key, DB_NAGIOSXI) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
        if ($rs->MoveFirst()) {
            return $rs->fields["keyvalue"];
        }
    }

    return $default;
}


/**
 * @param $user_id
 *
 * @return array
 */
function get_all_user_meta($user_id)
{
    global $db_tables;

    $meta = array();

    // use logged in user's id
    if ($user_id == 0) {
        if (!isset($_SESSION["user_id"]))
            return null;
        else
            $user_id = $_SESSION["user_id"];
    }

    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["usermeta"] . " WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
        while (!$rs->EOF) {
            $meta[$rs->fields["keyname"]] = $rs->fields["keyvalue"];
            $rs->MoveNext();
        }
    }
    return $meta;
}


/**
 * @param bool $overwrite
 *
 * @return null
 */
function get_user_meta_session_vars($overwrite = false)
{
    global $db_tables;

    if (!isset($_SESSION["user_id"]))
        return null;

    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["usermeta"] . " WHERE user_id='" . escape_sql_param($_SESSION["user_id"], DB_NAGIOSXI) . "' AND autoload='1'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql, false))) {
        while (!$rs->EOF) {
            // set session variable - skip some
            switch ($rs->fields["keyname"]) {
                case "user_id"; // security risk
                    break;
                default:
                    if (!($overwrite == false && isset($_SESSION[$rs->fields["keyname"]])))
                        $_SESSION[$rs->fields["keyname"]] = $rs->fields["keyvalue"];
                    break;
            }
            $rs->MoveNext();
        }
    }
    return null;
}


/**
 * @param      $user_id
 * @param      $key
 * @param      $value
 * @param bool $sessionload
 *
 * @return mixed
 */
function set_user_meta($user_id, $key, $value, $sessionload = true)
{
    global $db_tables;

    // use logged in user's id
    if ($user_id == 0)
        $user_id = $_SESSION["user_id"];

    $autoload = 0;
    if ($sessionload == true)
        $autoload = 1;

    // see if data exists already
    $key_exists = false;
    $sql = "SELECT * FROM " . $db_tables[DB_NAGIOSXI]["usermeta"] . " WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "' AND keyname='" . escape_sql_param($key, DB_NAGIOSXI) . "'";
    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
        if ($rs->RecordCount() > 0)
            $key_exists = true;
    }

    // insert new key
    if ($key_exists == false) {
        $sql = "INSERT INTO " . $db_tables[DB_NAGIOSXI]["usermeta"] . " (user_id,keyname,keyvalue,autoload) VALUES ('" . escape_sql_param($user_id, DB_NAGIOSXI) . "','" . escape_sql_param($key, DB_NAGIOSXI) . "','" . escape_sql_param($value, DB_NAGIOSXI) . "','" . $autoload . "')";
        return exec_sql_query(DB_NAGIOSXI, $sql);
    } // update existing key
    else {
        $sql = "UPDATE " . $db_tables[DB_NAGIOSXI]["usermeta"] . " SET keyvalue='" . escape_sql_param($value, DB_NAGIOSXI) . "', autoload='" . $autoload . "' WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "' AND keyname='" . escape_sql_param($key, DB_NAGIOSXI) . "'";
        return exec_sql_query(DB_NAGIOSXI, $sql);
    }

}


/**
 * @param $user_id
 * @param $key
 *
 * @return mixed
 */
function delete_user_meta($user_id, $key)
{
    global $db_tables;

    // use logged in user's id
    if ($user_id == 0)
        $user_id = $_SESSION["user_id"];

    $sql = "DELETE FROM " . $db_tables[DB_NAGIOSXI]["usermeta"] . " WHERE user_id='" . escape_sql_param($user_id, DB_NAGIOSXI) . "' AND keyname='" . escape_sql_param($key, DB_NAGIOSXI) . "'";
    return exec_sql_query(DB_NAGIOSXI, $sql);
}


////////////////////////////////////////////////////////////////////////
// USER MASQUERADE FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * @param int $user_id
 */
function masquerade_as_user_id($user_id = -1)
{

    // only admins can masquerade
    if (is_admin() == false)
        return;

    $original_user = $_SESSION["username"];

    if (!is_valid_user_id($user_id)) {
        return;
    }

    $username = get_user_attr($user_id, "username");

    //echo "GOOD TO GO";

    ///////////////////////////////////////////////////////////////
    // DESTROY CURRENT USER SESSION
    ///////////////////////////////////////////////////////////////
    //  destroy the session.
    deinit_session();
    init_session();

    // reinitialize theme
    //init_theme();

    // reinitialize the menu
    //init_menus();

    ///////////////////////////////////////////////////////////////
    // SETUP NEW USER SESSION
    ///////////////////////////////////////////////////////////////

    // set session variables
    $_SESSION["user_id"] = $user_id;
    $_SESSION["username"] = $username;

    // load user session variables (e.g. preferences)
    get_user_meta_session_vars(true);

    // log it
    send_to_audit_log("User '" . $original_user . "' masqueraded as user '" . $username . "'", AUDITLOGTYPE_SECURITY);
}


////////////////////////////////////////////////////////////////////////
// DEFAULT VIEWS/DASHBOARDS FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * @param int $userid
 */
function add_default_views($userid = 0)
{

    // add some views for the user if they don't have any
    $views = get_user_meta($userid, "views");
    if ($views == null || $views == "") {
        add_view($userid, "/nagiosxi/includes/components/xicore/tac.php", _("Tactical Overview"));
        add_view($userid, "/nagiosxi/includes/components/xicore/status.php?show=services&hoststatustypes=2&servicestatustypes=28&serviceattr=10", _("Open Problems"));
        add_view($userid, "/nagiosxi/includes/components/xicore/status.php?show=hosts", _("Host Detail"));
        add_view($userid, "/nagiosxi/includes/components/xicore/status.php?show=services", _("Service Detail"));
        add_view($userid, "/nagiosxi/includes/components/xicore/status.php?show=hostgroups&hostgroup=all&style=overview", _("Hostgroup Overview"));
    }
}

/**
 * @param int $userid
 */
function add_default_dashboards($userid = 0)
{

    // add some dashboards for the user if they don't have any
    $add = false;
    $dashboards = get_user_meta($userid, "dashboards");
    if ($dashboards == null || $dashboards == "")
        $add = true;
    if ($add == true) {

        // home page dashboard
        add_dashboard($userid, "Home Page", array(), HOMEPAGE_DASHBOARD_ID);
        // add some dashlets to the home dashboard (done later...)

        // empty dashboard
        add_dashboard($userid, "Empty Dashboard", array(), null);
    }

    // fix blank homepage dashboard
    init_home_dashboard_dashlets($userid);
}

// add default dashlets to a blank home dashboard
/**
 * @param int $userid
 */
function init_home_dashboard_dashlets($userid = 0)
{

    $homedash = get_dashboard_by_id($userid, HOMEPAGE_DASHBOARD_ID);
    if ($homedash == null)
        return;

    $dashcount = count($homedash["dashlets"]);
    if ($dashcount == 0) {

        $getting_started_left = 30;

        if (is_admin()) {

            // Administrative Tasks
            add_dashlet_to_dashboard($userid, HOMEPAGE_DASHBOARD_ID, "xicore_admin_tasks", _("Administrative Tasks"),
                array("height" => 361, "width" => 330, "top" => 30, "left" => 30, "pinned" => 0, "zindex" => "1"), array());

            // Server Stats
            add_dashlet_to_dashboard($userid, HOMEPAGE_DASHBOARD_ID, "xicore_server_stats", _("Server Statistics"),
                array("height" => 495, "width" => 330, "top" => 421, "left" => 30, "pinned" => 0, "zindex" => "1"), array());

            // Push 'Getting Started' to the right of 'Administrative Tasks'
            $getting_started_left = 400;
        }

        // Getting started
        add_dashlet_to_dashboard($userid, HOMEPAGE_DASHBOARD_ID, "xicore_getting_started", _("Getting Started"), 
            array("height" => 379, "width" => 330, "top" => 30, "left" => $getting_started_left, "pinned" => 0, "zindex" => "1"), array());

    }
}

// determine user's highcharts_default_type preference
/**
 * @param int $userid
 *
 * @return string will return one of the following: line, spline, area, stacked
 */
function get_highcharts_default_type($userid = 0) 
{
    $allowed_values = array("stacked", "area", "line", "spline");
    $user_highcharts_default_type = get_user_meta($userid, "highcharts_default_type");

    // first check if the user has a type set, which overrides the sys default
    if (in_array($user_highcharts_default_type, $allowed_values))
        return $user_highcharts_default_type;

    // now check if we have a system default set to return
    $highcharts_default_type = get_option('highcharts_default_type', 'line');
    if (in_array($highcharts_default_type, $allowed_values))
        return $highcharts_default_type;

    // welp, looks like we're stuck with this..
    return 'line';
}


////////////////////////////////
// PASSWORD REQUIREMENT FUNCTIONS
////////////////////////////////
// these are the defaults for password requirements
/**
 * @param bool $default - default false, if true, returns the default pw_requirements_array (for testing purposes)
 *
 * @return array - will be full of password requirement info
 */
function get_pw_requirements_array($default = false) {

    $defaults = array(
        'max_age'               => 90,
        'min_length'            => 8,
        'enforce_complexity'    => 0,
        'complex_upper'         => 2,
        'complex_lower'         => 2,
        'complex_numeric'       => 2,
        'complex_special'       => 2,
        );

    if ($default)
        return $defaults;

    $pw_requirements = get_option('pw_requirements');
    $pw = is_null($pw_requirements) ? $defaults : unserialize($pw_requirements);
    if ($pw === false)
        $pw = $defaults;

    return $pw;
}


// returns true if the password is within an allowed age range, false if not
/**
 * @param mixed $user - if numeric, then used as user's id, if non-numeric, used as username
 *
 * @return bool true if the password for specified user is within the allowed range
 */
function allowed_password_age($user) {

    $userid = is_numeric($user) ? $user : get_user_id($user);

    $pw_enforce_requirements = get_option('pw_enforce_requirements', 0);
    if (!$pw_enforce_requirements)
        return true;

    $pw = get_pw_requirements_array();

    if (intval($pw['max_age']) == 0)
        return true;

    $password_set_timestamp = get_user_attr($userid, 'last_password_change', 0);

    if ($password_set_timestamp == 0)
        return true;

    if ((time() - $password_set_timestamp) >= (intval($pw['max_age']) * 86400))
        return false;

    return true;
}


// returns true if specified string meets password requirements [based on enforcement policy], false if otherwise
/**
 * @param string $password - the string to test against the pw_complexity_requirements
 * @param bool $default - default false, if true, returns the default pw_requirements_array (for testing purposes)
 *
 * @return bool true if the password supplied meets the complexity requirements
 */
function password_meets_complexity_requirements($password, $default = false) {

    $pw_enforce_requirements = get_option('pw_enforce_requirements', 0);
    if (!$pw_enforce_requirements)
        return true;

    $default = $default == true ? true : false;
    $pw = get_pw_requirements_array($default);

    // check length
    if (strlen($password) < $pw['min_length'])
        return false;

    // see if we need to enforce any other complexity
    if (!$pw['enforce_complexity'])
        return true;

    $password_array = str_split($password);

    // check uppercase letter count in current locale
    if ($pw['complex_upper'] > 0) {
        $upper_count = 0;
        foreach($password_array as $char) {
            if (ctype_upper($char)) {
                $upper_count++;
            }
        }
        if ($upper_count < $pw['complex_upper'])
            return false;
    }

    // check lowercase letter count in current locale
    if ($pw['complex_lower'] > 0) {
        $lower_count = 0;
        foreach($password_array as $char) {
            if (ctype_lower($char)) {
                $lower_count++;
            }
        }
        if ($lower_count < $pw['complex_lower'])
            return false;
    }

    // check numeric count in current locale
    if ($pw['complex_numeric'] > 0) {
        $numeric_count = 0;
        foreach($password_array as $char) {
            if (ctype_digit($char)) {
                $numeric_count++;
            }
        }
        if ($numeric_count < $pw['complex_numeric'])
            return false;
    }

    // check special character count in current locale
    if ($pw['complex_special'] > 0) {
        $special_count = 0;
        foreach($password_array as $char) {
            if (!ctype_alnum($char)) {
                $special_count++;
            }
        }
        if ($special_count < $pw['complex_special'])
            return false;
    }

    return true;
}

// get a more detailed, gettexted password requirements message
/**
 * @return
 */
function get_password_requirements_message() {

    $message = "";

    $pw_enforce_requirements = get_option('pw_enforce_requirements', 0);
    if (!$pw_enforce_requirements)
        return $message;

    $pw = get_pw_requirements_array();

    $message = "<br /><br />" . _("The password complexity requirements are as follows:") . "<br /><ul style='list-style-type: disc; margin: 0; padding: 0 0 0 30px;'>\n";
    $message_length = strlen($message);

    if ($pw['min_length'] > 0)
        $message .= "<li>" . sprintf(_("Minimum password length is: %d characters."), $pw['min_length']) . "</li>\n";

    // see if we need to enforce any other complexity
    if ($pw['enforce_complexity']) {

        if ($pw['complex_upper'] > 0)
            $message .= "<li>" . sprintf(_("Minimum of %d uppercase characters."), $pw['complex_upper']) . "</li>\n";

        if ($pw['complex_lower'] > 0)
            $message .= "<li>" . sprintf(_("Minimum of %d lowercase characters."), $pw['complex_lower']) . "</li>\n";

        if ($pw['complex_numeric'] > 0)
            $message .= "<li>" . sprintf(_("Minimum of %d numeric characters."), $pw['complex_numeric']) . "</li>\n";

        if ($pw['complex_special'] > 0)
            $message .= "<li>" . sprintf(_("Minimum of %d special characters."), $pw['complex_special']) . "</li>\n";
    }

    if (strlen($message) == $message_length) {
        return "";
    } else {
        $message .= "</ul>\n";
    }

    return $message;
}



////////////////////////////
// LOCKED ACCOUNT FUNCTIONS
////////////////////////////

// returns array of ids if there are any accounts that need unlocked or false if there aren't any that need unlocked
/**
 * @return mixed - array containing the ids of locked accounts if total locked accounts > 0, false otherwise
 */
function locked_account_list() {

    global $db_tables;

    $now = time();
    $account_login_attempts_before_lockout = get_option('account_login_attempts_before_lockout', 3);
    $account_lockout_period = get_option('account_lockout_period', 300);
    $user_ids = array();

    $sql = "SELECT user_id FROM " . $db_tables[DB_NAGIOSXI]["users"] . " WHERE (login_attempts >= {$account_login_attempts_before_lockout}) ";
    if ($account_lockout_period > 0)
        $sql .= " AND (({$now} - last_attempt <= {$account_lockout_period}) AND (last_attempt > 0))";

    if (($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
        foreach ($rs as $user) {
            $user_ids[] = $user['user_id'];
        }
    }

    if (count($user_ids) > 0)
        return $user_ids;
        
    return false;
}


// returns true if the specified username's account is locked or not
/**
 * @param mixed $user - if numeric, then used as user's id, if non-numeric, used as username
 *
 * @return bool returns true if the user's account is currently in a locked state
 */
function locked_account($user) {

    $userid = is_numeric($user) ? $user : get_user_id($user);

    // check if we even care about locked accounts
    $account_lockout = get_option('account_lockout', 0);
    if ($account_lockout != 1)
        return false;

    $account_login_attempts_before_lockout = get_option('account_login_attempts_before_lockout', 3);
    $account_lockout_period = get_option('account_lockout_period', 300);

    $login_attempts = get_user_attr($userid, "login_attempts", 0);
    $last_attempt = get_user_attr($userid, "last_attempt", 0);

    if (($login_attempts >= $account_login_attempts_before_lockout) && ($login_attempts != 0)) {

        // if we have indefinite lockout period then at this point we are in fact locked out
        if ($account_lockout_period <= 0) {
            return true;

        } else {

            // if we don't have a last_attempt at this point, something is funky
            if ($last_attempt == 0)
                return true;

            // we are locked out!
            if (time() - $last_attempt <= $account_lockout_period)
                return true;

            // we aren't locked out, but we need to clean some stuff up
            if (time() - $last_attempt > $account_lockout_period) {
                change_user_attr($userid, "login_attempts", 0);
                return false;
            }
        }
    }

    return false;
}


// increments the number of failed login attempts for username's account
/**
 * @param mixed $user - if numeric, then used as user's id, if non-numeric, used as username
 */
function failed_login_attempt($user) {

    $userid = is_numeric($user) ? $user : get_user_id($user);

    $login_attempts = get_user_attr($userid, "login_attempts", 0);
    $login_attempts++;
    change_user_attr($userid, "login_attempts", $login_attempts);

    // check if the account is already considered "locked" - and if so bail out before we update the last_attempt
    if (locked_account($userid))
        return;

    change_user_attr($userid, "last_attempt", time());
}