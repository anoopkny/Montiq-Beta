<?php
//
// Google Map Dashlet
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__).'/../dashlethelper.inc.php');

googlemap_dashlet_init();

function googlemap_dashlet_init()
{
	global $googlemap_component_name;
	$name = "googlemapdashlet";
	
	$args = array(
		DASHLET_NAME => $name,
		
		// informative information
		DASHLET_VERSION => "1.1.0",
		DASHLET_DATE => "10-21-2015",
		DASHLET_AUTHOR => "Nagios Enterprises, LLC",
		DASHLET_DESCRIPTION => _("A dashlet that displays host status as an overlay on a google map. REQUIRES") . " <a href='https://assets.nagios.com/downloads/nagiosxi/components/googlemap.zip'>" . _("XI Google Map component") . "</a> " . _("be installed under Manage Components"),
		DASHLET_COPYRIGHT => "Copyright (c) 2009-2015 Nagios Enterprises",
		DASHLET_LICENSE => "BSD",
		DASHLET_HOMEPAGE => "http://www.nagios.com",
		
		// the good stuff - only one output method is used.  order of preference is 1) function, 2) url
		//DASHLET_FUNCTION => "testhtml_dashlet_func",
		DASHLET_URL => get_component_url_base($googlemap_component_name).'/map.php',
		//dashlet folder loc: /usr/local/nagiosxi/html/includes/dashlets/googlemap/(this)
		//component folder loc: /usr/local/nagiosxi/html/includes/dashlets/googlemap/map.php
		DASHLET_PREVIEW_IMAGE => get_dashlet_url_base($name)."/thumbnail.jpg",

		DASHLET_TITLE => "Google Map",
		DASHLET_OUTBOARD_CLASS => "googlemap_outboardclass",
		DASHLET_INBOARD_CLASS => "googlemap_inboardclass",
		DASHLET_PREVIEW_CLASS => "googlemap_previewclass",

		DASHLET_WIDTH => "350px",
		DASHLET_HEIGHT => "350px",
		DASHLET_OPACITY => "0.8",
		DASHLET_BACKGROUND => ""
	);
	
	register_dashlet($name, $args);
}