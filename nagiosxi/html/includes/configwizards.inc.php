<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

if (!isset($configwizards))
    $configwizards = array();

// include all dashlets - only if we're in the UI
if (defined("BACKEND") == false && defined("SUBSYSTEM") == false) {
    $p = dirname(__FILE__) . "/configwizards/";
    $subdirs = scandir($p);
    foreach ($subdirs as $sd) {
        if ($sd == "." || $sd == "..")
            continue;
        $d = $p . $sd;
        if (is_dir($d)) {
            $cf = $d . "/$sd.inc.php";
            if (file_exists($cf))
                include_once($cf);
        }
    }
}

