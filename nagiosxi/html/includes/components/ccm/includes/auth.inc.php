<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: auth.inc.php
//  Desc: Authentication methods for the CCM. If the CCM is not being accessed from the
//        Nagios XI web instance and the Nagios XI system config setting for seperate CCM
//        login and user management has not been enabled then the user will automatically
//        be logged into the CCM as the Nagios XI user.
//

/**
 * Handles and verifies user authorization for all pages and syncs login with the
 * old NagiosQL and Nagios XI if auto login is turned on.
 *
 * @global array $_SESSION sets all login related session variables
 * @global object $myDBClass nagiosql object
 * @global object $myDataClass nagiosql object
 * @return bool $AUTH global variable if auth is good or not
 */
function check_auth()
{
    global $myDBClass;
    global $myDataClass; 

    // Grab any submitted login variables 
    $username = mysql_real_escape_string(ccm_grab_request_var('username', '')); 
    $password = mysql_real_escape_string(ccm_grab_request_var('password', ''));
    $loginID = ccm_grab_request_var('loginid', ''); 
    $login_submitted = ccm_grab_request_var('loginSubmitted', false);
    $hidelog = ccm_grab_request_var('hidelog', 0);

    // First check any existing CCM login
    if (isset($_SESSION['ccm_username']) && isset($_SESSION['ccm_login']) && $_SESSION['ccm_login'] == true) {
        $_SESSION['loginMessage'] = _('Logged in as: ').$_SESSION['ccm_username']." <a href='index.php?cmd=logout'>"._('Logout')."</a>";
        $_SESSION['loginStatus'] = true;
        return true; 
    }
    
    // Check if legacy CCM is already logged in      
    if (isset($_SESSION['username']) && isset($_SESSION['startsite']) && $_SESSION['startsite'] == '/nagiosql/admin.php') {   
        $_SESSION['ccm_username'] = $_SESSION['username'];
        $_SESSION['loginMessage'] = _('Logged in as: ').$_SESSION['ccm_username']." <a href='index.php?cmd=logout'>"._('Logout')."</a>";
        if (!isset($_SESSION['token'])) $_SESSION['token'] = md5(uniqid(mt_rand(), true));
        $_SESSION['loginStatus'] = true;
        return true;
    }

    // If we are using Nagios XI and are already logged into Nagios XI we can log in now
    if (ENVIRONMENT == "nagiosxi" && !$login_submitted) {

        // If we are in Nagios XI and viewing the CCM we need to log the user in using
        // the Nagios XI authentication if they are able to actually view the CCM and
        // if the Nagios XI server has the config option set to allow this.
        $separate_ccm_login = get_option("separate_ccm_login", 0);
        if ($separate_ccm_login == 0) {

            // Let's check out the user's credentials and see if they can use the CCM
            // and if they are we will go ahead and log them into the CCM using their
            // Nagios XI username as the CCM username.
            // For Advanced Users & Configure Access: is_authorized_to_configure_objects() && is_advanced_user()
            if (is_admin() && !in_demo_mode()) {
                $username = get_user_attr($_SESSION['user_id'], "username");
                ccm_process_login($username);

                // Write to the CCM log so that people know someone logged in
                $myDataClass->writeLog(_("Auto-login via Nagios XI successful"));
                return true;
            }
        }
    }
    
    // If we are using the standalone CCM or the separate login in Nagios XI we will do the
    // login processing if the login form was submitted.
    if ($login_submitted) {
        $str_sql = "SELECT * FROM `tbl_user` WHERE `username`='".$username."' AND `password`='".md5($password)."' AND `active`='1'";
        $booReturn = $myDBClass->getDataArray($str_sql, $arr_user_data, $int_data_count);
        if ($booReturn == false)  {
            if (!isset($strMessage)) {
                $strMessage = ""; 
            }
            $_SESSION['loginMessage']= _('Error while selecting data from database:')." ".$myDBClass->strDBError;
            $_SESSION['loginStatus'] = false;
        } else if ($int_data_count == 1) {
        
            // Process the login if we know the user is authenticated
            ccm_process_login($username);

            // Add the new last login time for the user in the database
            $strSQLUpdate = "UPDATE `tbl_user` SET `last_login`=NOW() WHERE `username`='".$username."'";
            $booReturn = $myDBClass->insertData($strSQLUpdate);
            $add_login_log = true;

            // Special login case for Nagios XI when applying configuration there are multiple logins into
            // the CCM and instead of displaying a bunch of login successfuls we are going to save that the
            // Nagios XI user logged in to apply configuration
            if (ENVIRONMENT == "nagiosxi") {
                if ($username == "nagiosxi" && $hidelog) {
                    $add_login_log = false;
                }
            }

            // Add the actual login to the database log
            if ($add_login_log) {
                $myDataClass->writeLog(_('Login successful'));
            }

            audit_log(AUDITLOGTYPE_SECURITY, $username." successfully logged into Nagios CCM");
            return true; 
        } else {
            $_SESSION['loginMessage'] = _('Contact your Nagios XI administrator if you have forgotten your login credentials.<br />Need to initialize or reset the config manager admin password? <a target="_blank" href="/nagiosxi/admin/?xiwindow=credentials.php">Click here</a>.');
            $_SESSION['loginStatus'] = false;
            $myDataClass->writeLog(_('Login failed!')." - Username: ".$username);
            audit_log(AUDITLOGTYPE_SECURITY, "CCM Login failed - Username: {$username}");
            return false; 
        }
    }
    
    // If we went through all the checks and logins and we haven't returned yet then we can
    // go ahead and let the user know they aren't logged in.
    $_SESSION['loginMessage'] = "Login Required!";
    $_SESSION['loginStatus'] = true;
    return false;
}

/**
 * Creates the actual login session and writes to the log when the user gets logged in
 * via any of the check_auth login methods.
 *
 * @param string $username The username of the logged in user
 */
function ccm_process_login($username)
{
    // Set login session values for the CCM
    $_SESSION['ccm_username'] = $username;
    $_SESSION['ccm_login'] = true;
    $_SESSION['timestamp'] = mktime();
    $_SESSION['token'] = md5(uniqid(mt_rand(), true));
    if (isset($_SESSION['language'])) { $_SESSION['ccm_language'] = $_SESSION['language']; }

    // Set login session values for the legacy CCM (NagiosQL 2.x)
    $_SESSION['startsite'] = '/nagiosql/admin.php';
    $_SESSION['username'] = $username; 
    $_SESSION['keystring'] = '11111111'; 
    $_SESSION['strLoginMessage'] = '';
    $_SESSION['loginStatus'] = true;
}