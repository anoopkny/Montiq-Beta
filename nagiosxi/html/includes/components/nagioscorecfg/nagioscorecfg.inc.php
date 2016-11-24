<?php
//
// Nagios CCM Integration Component
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// run the initialization function
nagioscorecfg_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function nagioscorecfg_component_init()
{
    $name = "nagioscorecfg";
    $args = array(
        COMPONENT_NAME => $name,
        COMPONENT_TITLE => "Nagios Core Config Manager (CCM) Integration",
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => "Provides CCM integration into the Nagios XI web UI.",
        COMPONENT_PROTECTED => true,
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );
    register_component($name, $args);
}

///////////////////////////////////////////////////////////////////////////////////////////
// URL FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

/**
 * @param bool $directory_only
 *
 * @return string
 */
function nagioscorecfg_get_component_url($directory_only = false)
{
    $url = get_base_url();
    $url .= "/includes/components/nagioscorecfg/";
    if ($directory_only == false) {
        $url .= "nagioscorecfg.php";
    }
    return $url;
}