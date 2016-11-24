<?php
//
// Timeline Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);


route_request();

function route_request()
{
    global $request;

    $data = grab_request_var("data");
    switch ($data) {
        case "nagios":
            get_nagios_timeline_data();
            break;
        case "events":
        default:
            get_timeline_data();
            break;
    }
}

function get_nagios_timeline_data()
{

    header("Content-Type: application/json");
    ?>
    {
    'dateTimeFormat': 'iso8601',

    'events' : [

    {'start': '2009-09-11',
    'title': 'Nagios XI Inception',
    'description': 'Ethan Galstad and Mary Starr discuss the idea of a commercial Nagios solution and decide upon the Nagios XI name.',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2009-09-13',
    'end' : '2009-12-31',
    'title': 'Nagios XI Crunch Time',
    'description': 'Ethan works 80+ hour weeks of coding and preparing for the first XI release.',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2009-09-13',
    'title': 'Nagios XI Development Begins',
    'description': 'Ethan begins 80+ hour weeks of coding...',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2011-01-25',
    'title': 'Nagios XI 2009R1.4',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-11-15',
    'title': 'Nagios XI 2009R1.3G',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-11-02',
    'title': 'Nagios XI 2009R1.3F',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-09-27',
    'title': 'Nagios XI 2009R1.3E',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-09-18',
    'title': 'Nagios XI 2009R1.3D',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-09-16',
    'title': 'Nagios XI 2009R1.3C',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-09-03',
    'title': 'Nagios XI 2009R1.3B',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-08-18',
    'title': 'Nagios XI 2009R1.3',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-07-27',
    'title': 'Nagios XI 2009R1.2D',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-07-18',
    'title': 'Nagios XI 2009R1.2C',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-07-06',
    'title': 'Nagios XI 2009R1.2B',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-06-02',
    'title': 'Nagios XI 2009R1.2',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-04-02',
    'title': 'Nagios XI 2009R1.1H',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-03-17',
    'title': 'Nagios XI 2009R1.1G',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-02-08',
    'title': 'Nagios XI 2009R1.1F',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-02-02',
    'title': 'Nagios XI 2009R1.1E',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-01-26',
    'title': 'Nagios XI 2009R1.1D',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-01-17',
    'title': 'Nagios XI 2009R1.1C',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-01-14',
    'title': 'Nagios XI 2009R1.1B',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2010-01-09',
    'title': 'Nagios XI 2009R1.1A',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2009-13-31',
    'title': 'Nagios XI 2009R1',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2009-11-25',
    'title': 'Nagios XI 2009RC3',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2009-11-17',
    'title': 'Nagios XI 2009RC2',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },

    {'start': '2009-11-07',
    'title': 'Nagios XI 2009RC1',
    'description': 'Nagios XI Release',
    'image': '/nagiosxi/images/nagiosxi-logo-small.png',
    'link': 'http://www.nagios.com'
    },


    {'start': '2009-06-24',
    'title': 'Nagios Support Contracts Available',
    'description': 'Nagios Enterprises launches support contracts for Nagios.',
    'image': '',
    'link': 'http://www.nagios.com'
    },

    {'start': '2007-11-09',
    'title': 'Nagios Enterprises Launches',
    'description': 'Ethan Galstad and Mary Starr launch Nagios Enterprises, LLC',
    'image': '',
    'link': 'http://www.nagios.com'
    },

    {'start': '2007-11-09',
    'title': 'Nagios Enterprises Launches',
    'description': 'Ethan Galstad and Mary Starr launch Nagios Enterprises, LLC',
    'image': '',
    'link': 'http://www.nagios.com'
    },

    {'start': '1999-03-19',
    'title': 'NetSaint 0.0.1',
    'description': 'Ethan Galstad releases the first version of Nagios (originally called NetSaint)',
    'image': '',
    'link': 'http://www.netsaint.org'
    },

    {'start': '1999-03-19',
    'end' : '2009-12-31',
    'title': 'The Nagios Revolution Begins',
    'description': 'Nagios grows to become the de-facto industry standard in Open Source monitoring',
    'image': '',
    'link': 'http://www.nagios.org'
    },

    {'start': '2010-01-01',
    'end' : '2100-01-01',
    'title': 'Nagios Rules The Monitoring Space',
    'description': 'Nagios is the de-facto industry standard in Open Source monitoring',
    'image': '',
    'link': 'http://www.nagios.com'
    }

    ]
    }
<?php
}

function get_timeline_data()
{

    //header("Content-Type: application/json");
    header("Content-Type: text/plain");

    // get values passed in GET/POST request
    $page = grab_request_var("page", 1);
    $records = grab_request_var("records", 25);
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");
    $statetype = grab_request_var("statetype", "hard");
    
    // fix search
    if ($search == _("Search..."))
        $search = "";

    // we search for hosts, so clear host if search is present
    if ($search != "") {
        $host = "";
        $service = "";
    }

    // determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $args = array(
        "starttime" => $starttime,
        "endtime" => $endtime,
    );
    if ($search)
        $args["host_name"] = "lks:" . $search;
    if ($host != "")
        $args["host_name"] = $host;
    if ($service != "")
        $args["service_description"] = $service;
    if ($statetype == "hard")
        $args["state_type"] = 1;
    else if ($statetype == "hard")
        $args["state_type"] = 0;
    //print_r($args);
    $xml = get_xml_statehistory($args);

    $events = array();

    $imgurlbase = get_base_url() . "includes/components/nagioscore/ui/images/logos/";

    if ($xml) {

        foreach ($xml->stateentry as $se) {

            $state_time = strval($se->state_time);
            $objecttype_id = intval($se->objecttype_id);
            $host_name = strval($se->host_name);
            $service_description = strval($se->service_description);
            $state = intval($se->state);
            $output = strval($se->output);

            if ($objecttype_id == 0) {
                $state_text = host_state_to_string($state);
            } else {
                $state_text = service_state_to_string($state);
            }

            $thetime = strtotime($state_time);
            $start = date("r", $thetime);

            if ($objecttype_id == 0) {
                $title = $host_name . " " . $state_text;
                $desc = "<br><br>" . $output;
            } else {
                $title = $host_name . " - " . $service_description . " " . $state_text;
                $desc = "<br><br>" . $output;
            }

            $image = "";
            $link = "";

            $icon = get_object_icon_image($host_name, $service_description, true);
            if ($icon != "") {
                $image = $imgurlbase . $icon;
            }

            $events[] = array(
                'start' => $start,
                'title' => $title,
                'description' => $desc,
                'image' => $image,
                'link' => $link
            );
        }

        $data = array('events' => $events);
        print json_encode($data);

    }

}
