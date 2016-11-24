<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//
// $Id$

include_once('utils.inc.php');

// Redirect to login screen if user is not authenticated
// This is used in: most pages that require authentication checking
/**
 * @param bool $redirect
 */
function check_authentication($redirect = true)
{
    global $request;
    // Some pages are used by both frontend and backend, so check for backend...
    if (defined("BACKEND")) {
        echo "BACKEND DEFINED";
        check_backend_authentication();
        return;
    }

    if (is_authenticated() == false) {

        // Check backend ticket
        if (is_backend_authenticated() == true) {
            return;
        }

        // Don't redirect user
        if ($redirect == false) {
            echo "Your session has timed out.";
        } // redirect user to login screen
        else {
            $redirecturl = $_SERVER['PHP_SELF'];
            $redirecturl .= "%3f"; // Question mark

            $redirect_q_string = urlencode($_SERVER['QUERY_STRING']);
            $redirecturl .= $redirect_q_string;

            $theurl = get_base_url() . PAGEFILE_LOGIN . "?redirect=$redirecturl";
            $theurl .= "&noauth=1"; // Needed for auto-login
            header("Location: " . $theurl);
        }

        exit();
    }

    // Do callbacks
    $args = array();
    do_callbacks(CALLBACK_AUTHENTICATION_PASSED, $args);
}

// Checks if user is authenticated
// - Only for locations that are actually doing auth checks, not redirecting on FAIL
// Used in: login.php, index.php, pargeparts.inc.php, auth.inc.php, header.inc.php and footer.inc.php
/**
 * @return bool
 */
function is_authenticated()
{

    // Some pages are used by both frontend and backend, so check for backend...
    if (defined("BACKEND")) {
        return is_backend_authenticated();
    }

    // Verify authenticated by fusion
    if (is_fusion_authenticated() == true) {
        return true;
    }

    // Session variable is set, so they are already logged in
    if (isset($_SESSION["user_id"])) {
        if (is_valid_user_id($_SESSION["user_id"]) === false) {
            return false;
        }
        return true;
    }

    // HTTP BASIC AUTHENTICATION support
    $remote_user = "";
    if (isset($_SERVER["REMOTE_USER"])) {
        $remote_user = $_SERVER["REMOTE_USER"];
    }

    if ($remote_user != "") {
        $uid = get_user_id($remote_user);
        //echo "UID: $uid<BR>";
        // user has authenticated, and they are configured in Nagios XI!
        if ($uid > 0) {
            //echo "GOOD TO GO FOR BASIC AUTH!<BR>";
            // set session variables
            $_SESSION["user_id"] = $uid;
            $_SESSION["username"] = $remote_user;
            return true;
        } else {
            //echo "NO GO!<BR>";
            return false;
        }
    }

    return false;
}

// Check if HTTP BASIC authentication is being used
/**
 * @return bool
 */
function is_http_basic_authenticated()
{
    $remote_user = "";
    if (isset($_SERVER["REMOTE_USER"])) {
        $remote_user = $_SERVER["REMOTE_USER"];
    }
    if ($remote_user != "") {
        return true;
    } else {
        return false;
    }
}

// Determines if auto-login is enabled
/**
 * @return bool
 */
function is_autologin_enabled()
{
    $opt_s = get_option("autologin_options");
    if ($opt_s == "") {
        return false;
    } else {
        $opts = unserialize($opt_s);
    }

    $enabled = grab_array_var($opts, "autologin_enabled");
    $username = grab_array_var($opts, "autologin_username");

    if ($enabled == 1 && $username != "" && is_valid_user($username)) {
        return true;
    }

    return false;
}

// Check if fusion has sent a request variable to be authenticated
/**
 * @param bool $return
 *
 * @return bool
 */
function is_fusion_authenticated($return = false)
{
    $fa_data = grab_request_var("fa", "");

    // Check if we are authenticating and the user is already authenticated
    if (isset($_SESSION["user_id"]) && is_valid_user_id($_SESSION["user_id"])) {
        if (!empty($fa_data)) {
            remove_fa_and_redirect();
        }
    }

    // Check if we should log the user in
    if (!empty($fa_data)) {

        $data = unserialize(base64_decode($fa_data));

        // Check username and get the user's uid
        if (empty($data['username'])) {
            return false;
        } else {
            $uid = get_user_id($data['username']);
        }

        // Check the password
        if (!empty($uid)) {
            if (!empty($data['password'])) {
                // Check if password is correct and if they should be logged in or able to view a page
                if ($data["password"] == get_user_attr($uid, "password")) {
                    if ($return) {
                        return true;
                    } // Some places need to know if they are authenticated for API access only
                    $_SESSION["user_id"] = $uid;
                    $_SESSION["username"] = get_user_attr($uid, "username");
                    remove_fa_and_redirect();
                }
            }
        }
    }

    return false;
}

// Removes FA data (fa) and redirects the user to the proper location afterwards
function remove_fa_and_redirect()
{
    // Parse the url to remove the FA data
    $url = (isset($_SERVER['https']) ? "https://" : "http://");
    $url_parts = parse_url($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    // Add the path back into the url
    $url .= $url_parts['path'];

    // Remove FA and add all the other parts back in
    $query_params = explode("&", $url_parts['query']);
    foreach ($query_params as $k => $param) {
        if (strpos($param, "fa=") !== false) {
            unset($query_params[$k]);
        }
    }
    $url .= "?" . implode("&", $query_params);

    // Send the user back to the location they were going to without the fa_data
    header("Location: " . $url);
    exit();
}