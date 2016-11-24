<?php
//
// Copyright (c) 2011 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 03/22/2008
// $Id: utils-nagioscore.inc.php 321 2010-09-27 16:34:01Z egalstad $

//require_once(dirname(__FILE__).'/common.inc.php');


///////////////////////////////////////////////////////////////////////////////////////////
// MIB FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

/**
 * @return string
 */
function get_mib_dir()
{
    $mib_dir = "/usr/share/snmp/mibs";
    if (!is_dir($mib_dir)) {
        $mib_dir = "/usr/share/mibs";
    }
    return $mib_dir;
}

/**
 * @return array
 */
function get_mibs()
{

    $mibs = array();

    $dir = get_mib_dir();

    $p = $dir;
    $direntries = file_list($p, "");
    foreach ($direntries as $de) {

        $file = $de;
        $filepath = $dir . "/" . $file;
        $ts = filemtime($filepath);

        $perms = fileperms($filepath);
        $perm_string = file_perms_to_string($perms);

        $ownerarr = fileowner($filepath);
        if (function_exists('posix_getpwuid')) {
            $ownerarr = posix_getpwuid($ownerarr);
            $owner = $ownerarr["name"];
        } else
            $owner = $ownerarr;
        $grouparr = filegroup($filepath);
        if (function_exists('posix_getgrgid')) {
            $grouparr = posix_getgrgid($grouparr);
            $group = $grouparr["name"];
        } else
            $group = $grouparr;

        $info = pathinfo($file);
        $mib_name = basename($file, '.' . grab_array_var($info, "extension", ""));

        if ($mib_name == "" || $file == ".index")
            continue;

        $mibs[] = array(
            "mibname" => $mib_name,
            "file" => $file,
            "timestamp" => $ts,
            "date" => get_datetime_string($ts),
            "perms" => $perms,
            "permstring" => $perm_string,
            "owner" => $owner,
            "group" => $group,
        );
    }

    return $mibs;
}

