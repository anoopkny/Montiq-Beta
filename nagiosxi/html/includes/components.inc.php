<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

if (!isset($components))
    $components = array();

//echo "COMPONENTS\n";

if (defined("SKIPCOMPONENTS") == false) {

    // include all components
    $parentdir = dirname(__FILE__) . "/components/";
    $subdirs = scandir($parentdir);

    foreach ($subdirs as $subdir) {
        if ($subdir == "." || $subdir == "..")
            continue;

        $curdir = $parentdir . $subdir;
        if (is_dir($curdir)) {

            $component_file = $curdir . "/{$subdir}.inc.php";
            if (file_exists($component_file)) {

                $components_temp = $components;
                reset($components);

                include_once($component_file);

                foreach ($components as $name => $carray) {
                    $components[$name][COMPONENT_DIRECTORY] = basename($curdir);
                }

                $components_temp[$name] = $components[$name];
                $components = $components_temp;
            }
        }
    }
}