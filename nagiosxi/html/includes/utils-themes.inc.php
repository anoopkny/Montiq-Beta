<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

////////////////////////////////////////////////////////////////////////
// THEME FUNCTIONS
////////////////////////////////////////////////////////////////////////

// Gets the current theme based on what the user or application default
// is currently set to
function get_theme()
{
    $theme = get_option('theme', 'xi5');
    $user_theme = get_user_meta(0, 'theme');

    if (!empty($user_theme)) {
        if ($user_theme != "NULL") {
            $theme = $user_theme;
        }
    }

    return $theme;
}

function theme_image($img = "")
{
    $url = get_base_url();
    $url .= "images/";
    $url .= $img;
    return $url;
}