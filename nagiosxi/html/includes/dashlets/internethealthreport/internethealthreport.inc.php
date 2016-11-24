<?php
//
// Internet Health Report
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../dashlethelper.inc.php');

internethealthreport_dashlet_init();

function internethealthreport_dashlet_init()
{
    $name = "internethealthreport";

    $args = array(
        DASHLET_NAME => $name,
        //DASHLET_VERSION => "1.0",
        //DASHLET_DATE => "09-26-2009",
        DASHLET_AUTHOR => "Nagios Enterprises, LLC",
        DASHLET_DESCRIPTION => _("Keynote Internet Health Report delivers up-to-the-minute metrics on overall Internet performance, monitoring availability and latency between major Tier One backbones."),
        DASHLET_COPYRIGHT => "Dashlet Copyright &copy; 2009-2015 Nagios Enterprises, LLC.<br>Data Copyright &copy; ".date('Y', time())." Keynote Systems, Inc.",
        DASHLET_LICENSE => "MIT",
        //DASHLET_HOMEPAGE => "https://www.nagios.com",
        DASHLET_URL => "http://www.internetpulse.net/",
        DASHLET_PREVIEW_IMAGE => get_dashlet_url_base("internethealthreport") . "/preview.png",
        DASHLET_TITLE => _("Internet Health Report"),
        DASHLET_OUTBOARD_CLASS => "internethealthreport_outboardclass",
        DASHLET_INBOARD_CLASS => "internethealthreport_inboardclass",
        DASHLET_PREVIEW_CLASS => "internethealthreport_previewclass",
        DASHLET_CSS_FILE => "internethealthreport.css",
        DASHLET_WIDTH => "800",
        DASHLET_HEIGHT => "200",
        DASHLET_OPACITY => "0.7",
        DASHLET_BACKGROUND => "",
    );

    register_dashlet($name, $args);
}
