<?php
// TOOL FUNCTIONS
//
// Copyright (c) 2011-2014 Nagios Enterprises, LLC.  All rights reserved.
//
// $Id$

//require_once(dirname(__FILE__).'/common.inc.php');


////////////////////////////////////////////////////////////////////////////////
// MY TOOLS FUNCTIONS
////////////////////////////////////////////////////////////////////////////////

/**
 * @param int $userid
 *
 * @return array|mixed
 */
function get_mytools($userid = 0)
{
    $mytools_s = get_user_meta($userid, 'mytools');

    if ($mytools_s == null) {
        $mytools = array();
    } else {
        $mytools = unserialize(base64_decode($mytools_s));
    }

    foreach ($mytools as $k => $r) {
        $n[$k] = $r['name'];
    }
    array_multisort($n, SORT_ASC, $mytools);

    return $mytools;
}

/**
 * @param int $userid
 * @param     $id
 *
 * @return null
 */
function get_mytool_id($userid = 0, $id)
{
    $mytools = get_mytools($userid);

    if (array_key_exists($id, $mytools)) {
        return $mytools[$id];
    }

    return null;
}

/**
 * @param int $userid
 * @param     $id
 *
 * @return string
 */
function get_mytool_url($userid = 0, $id)
{
    $url = "";
    $mytool = get_mytool_id($userid, $id);

    if ($mytool != null) {
        $url = $mytool["url"];
    }

    return $url;
}

// UPDATE or CREATE a new tool for a user
/**
 * @param int $userid
 * @param int $id
 * @param     $name
 * @param     $url
 *
 * @return array|mixed
 */
function update_mytool($userid = 0, $id = null, $name, $url)
{
    $mytools = get_mytools($userid);
    
    // Find ID in list of tools or generate new one
    $valid = false;
    foreach ($mytools as $i => $t) {
        if ($i == $id) {
            $valid = true;
            break; 
        }
    }

    if (!$valid) {
        $id = random_string(6);
    }

    // Encoding for character-based languages
    $newtool = array("name" => encode_form_val($name),
                     "url" => $url);

    // Save tool to user meta data
    $mytools[$id] = $newtool;
    set_user_meta($userid, 'mytools', base64_encode(serialize($mytools)), false);

    return $mytools;
}

/**
 * @param int $userid
 * @param     $id
 */
function delete_mytool($userid = 0, $id)
{
    $mytools = get_mytools(0);
    unset($mytools[$id]);
    set_user_meta(0, 'mytools', base64_encode(serialize($mytools)), false);
}


////////////////////////////////////////////////////////////////////////////////
// COMMON TOOLS FUNCTIONS
////////////////////////////////////////////////////////////////////////////////

/**
 * @param int $userid
 *
 * @return array|mixed
 */
function get_commontools($userid = 0)
{
    $ctools_s = get_option('commontools');

    if ($ctools_s == null) {
        $ctools = array();
    } else {
        $ctools = unserialize(base64_decode($ctools_s));
    }

    foreach ($ctools as $k => $r) {
        $n[$k] = $r['name'];
    }
    array_multisort($n, SORT_ASC, $ctools);

    return $ctools;
}

/**
 * @param $id
 *
 * @return null
 */
function get_commontool_id($id)
{
    $ctools = get_commontools();

    if (array_key_exists($id, $ctools)) {
        return $ctools[$id];
    }

    return null;
}

/**
 * @param $id
 *
 * @return string
 */
function get_commontool_url($id)
{
    $url = "";
    $ctool = get_commontool_id($id);

    if ($ctool != null) {
        $url = $ctool["url"];
    }

    return $url;
}

// UPDATE or CREATE common tools
/**
 * @param int $id
 * @param     $name
 * @param     $url
 *
 * @return array|mixed
 */
function update_commontool($id = null, $name, $url)
{
    $ctools = get_commontools();

    // Find ID in list of tools or generate new one
    $valid = false;
    foreach ($ctools as $i => $t) {
        if ($i == $id) {
            $valid = true;
            break; 
        }
    }

    if (!$valid) {
        $id = random_string(6);
    }

    // Encoding for character-based languages
    $newtool = array("name" => encode_form_val($name),
                     "url" => $url);

    // Save tools and return list
    $ctools[$id] = $newtool;
    set_option('commontools', base64_encode(serialize($ctools)));

    return $ctools;
}

/**
 * @param $id
 */
function delete_commontool($id)
{
    $ctools = get_commontools();
    unset($ctools[$id]);
    set_option('commontools', base64_encode(serialize($ctools)));
}