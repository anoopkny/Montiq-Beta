<?php
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/includes/common.inc.php');

// start session
init_session();

// grab GET or POST variables 
grab_request_vars(false);

// check prereqs
check_prereqs();

// check authentication
check_authentication();

// handle request
route_request();


function route_request()
{
    global $request;

    //array_dump($_REQUEST);

    // make sure we have some query specified
    if (!isset($request['term']))
        exit();
    $query = $request['term'];

    // hostname might be passed with service queries
    $hostname = grab_request_var("host", "");

    if (isset($request['type']))
        $cmd = strtolower($request['type']);
    else
        $cmd = "";

    //echo "Q: $query, TYPE: $cmd";

    switch ($cmd) {
        case "users":
            suggest_users($query);
            break;
        case "services":
            suggest_services($query, $hostname);
            break;
        case "hostgroups":
            suggest_hostgroups($query);
            break;
        case "servicegroups":
            suggest_servicegroups($query);
            break;
        case "objects":
            suggest_objects($query);
            break;
        default:
        case "hosts":
            suggest_hosts($query);
            break;
        case "multi":
            suggest_multi($query);
            break;
            
    }

    exit();
}


/**
 * @param $query
 */
function suggest_users($query)
{

    $names = array();

    // back xml result from backend. if $query="egalstad", this fetches the following URL:
    //     http://dev1/nagiosreports/backend/?cmd=getusers&username=lks:egalstad

    // search on  username,(full)name, and email address

    // get usernames
    $searchstring = "lks:" . $query;
    $args = array(
        "cmd" => "getusers",
        "username" => $searchstring
    );
    $res1 = get_backend_data($args);

    // get names
    $args = array(
        "cmd" => "getusers",
        "name" => $searchstring
    );
    $res2 = get_backend_data($args);

    // get email addresses
    $args = array(
        "cmd" => "getusers",
        "email" => $searchstring
    );
    $res3 = get_backend_data($args);

    // load the results into xml
    $xres1 = simplexml_load_string($res1);
    $xres2 = simplexml_load_string($res2);
    $xres3 = simplexml_load_string($res3);

    if ($xres1) {
        foreach ($xres1->user as $u) {
            $names[] = strtolower($u->username);
        }
    }
    if ($xres2) {
        foreach ($xres2->user as $u) {
            $names[] = strtolower($u->name);
        }
    }
    if ($xres3) {
        foreach ($xres3->user as $u) {
            $names[] = strtolower($u->email);
        }
    }

    natcasesort($names);
    $names = array_flip(array_flip($names));

    //foreach($names as $name)
    //echo $name."|\n";
    echo json_encode($names);

}


/**
 * @param $query
 */
function suggest_hosts($query)
{

    $names = array();

    // search on host name
    $args = array(
        "cmd" => "gethosts",
        "host_name" => "lk:" . $query . ";alias=lk:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'host_name:a',
        'records' => 10,
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);
    
    if ($xres1) {
        foreach ($xres1->host as $obj) {
            $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=services&host='.urlencode(strval($obj->host_name)), 
                                     'value' => strval($obj->host_name),
                                     'category' => _('Host'),
                                     'label' => (stripos(strval($obj->host_name),$query) !== false) ? strval($obj->host_name) : _('[A] ') . strval($obj->alias)
                                    );
        }
    }

    //natcasesort($names);
    //$names=array_flip(array_flip($names));

    echo json_encode($names);

    //foreach($names as $name)
    //	echo $name."|\n";
}

/**
 * @param $query
 */
function suggest_hostgroups($query)
{

    $names = array();

    // search on hostgroup name
    $args = array(
        "cmd" => "gethostgroups",
        "hostgroup_name" => "lks:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'hostgroup_name:a',
        'records' => 10,
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);

    if ($xres1) {
        foreach ($xres1->hostgroup as $obj) {
            //$names[]=strtolower($obj->hostgroup_name);
            $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=services&hostgroup='.urlencode(strval($obj->hostgroup_name)), 
                                      'value' => strval($obj->hostgroup_name),
                                      'category' => _('Hostgroup'),
                                      'label' => (stripos(strval($obj->hostgroup_name),$query) !== false) ? strval($obj->hostgroup_name) : _('[A] '). strval($obj->alias)
                                    );
        }
    }

    //natcasesort($names);
    //$names=array_flip(array_flip($names));

    //foreach($names as $name)
    //echo $name."|\n";
    echo json_encode($names);

}

/**
 * @param        $query
 * @param string $hostname
 */
function suggest_services($query, $hostname = "")
{

    $names = array();

    // search on service name
    $args = array(
        "cmd" => "getservices",
        "service_description" => "lks:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'service_description:a',
        'records' => 10,
    );
    if ($hostname != "")
        $args["host_name"] = $hostname;
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);

    if ($xres1) {
        foreach ($xres1->service as $obj) {
            //$names[]=strtolower($obj->service_description);
            //$names[] = strval($obj->service_description);
            $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=services&search='.urlencode(strval($obj->service_description)), 
                                      'value' => strval($obj->service_description),
                                      'category' => _('Service'),
                                      'label' => strval($obj->service_description)
                                    );
        }
    }

    //natcasesort($names);
    //$names=array_flip(array_flip($names));

    //foreach($names as $name)
    //	echo $name."|\n";
    echo json_encode($names);

}


/**
 * @param $query
 */
function suggest_servicegroups($query)
{

    $names = array();

    // search on servicegroup name
    $args = array(
        "cmd" => "getservicegroups",
        "servicegroup_name" => "lks:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'servicegroup_name:a',
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);

    if ($xres1) {
        foreach ($xres1->servicegroup as $obj) {
            //$names[]=strtolower($obj->servicegroup_name);
            $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=services&servicegroup='.urlencode(strval($obj->servicegroup_name)), 
                                      'value' => strval($obj->servicegroup_name),
                                      'category' => _('Servicegroup'),
                                      'label' => (stripos(strval($obj->servicegroup_name),$query) !== false) ? strval($obj->servicegroup_name) : _('[A] '). strval($obj->alias)
                                    );
        }
    }

    //natcasesort($names);
    //$names=array_flip(array_flip($names));

    //foreach($names as $name)
    //echo $name."|\n";
    echo json_encode($names);

}


/**
 * @param $query
 */
function suggest_objects($query)
{

    $names = array();

    // search on both name and description

    // get name1 (name)
    $args = array(
        "cmd" => "getobjects",
        "name1" => "lks:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        "records" => 10,
    );
    $res1 = get_backend_data($args);

    // get name2 (description)
    $args = array(
        "cmd" => "getobjects",
        "name2" => "lks:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        "records" => 10,
    );
    $res2 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);
    $xres2 = simplexml_load_string($res2);

    if ($xres1) {
        foreach ($xres1->object as $obj) {
            //$names[]=strtolower($obj->name1);
            $names[] = strval($obj->name1);
        }
    }
    if ($xres2) {
        foreach ($xres2->object as $obj) {
            //$names[]=strtolower($obj->name2);
            $names[] = strval($obj->name2);
        }
    }

    natcasesort($names);
    $names = array_flip(array_flip($names));

    //foreach($names as $name)
    //echo $name."|\n";
    echo json_encode($names);

}

/**
 * @param $query
 */
function suggest_multi($query)
{

    $names = array();

    //SERVICE
    $args = array(
        "cmd" => "getservices",
        "service_description" => "lk:" . $query . ";alias=lk:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'service_description:a',
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);

    debug($xres1);

    if ($xres1) {
        $services_count = 0;
        $existing_services = array();
        foreach ($xres1->service as $obj) {
            if (!in_array(strval($obj->service_description), $existing_services)) {
                $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=services&search='.urlencode(strval($obj->service_description)),
                                          'value' => strval($obj->service_description),
                                          'category' => _("Service"),
                                          'label' => (stripos(strval($obj->service_description),$query) !== false) ? strval($obj->service_description) : _('[A] ') . strval($obj->display_name)
                                        );
                $existing_services[] = strval($obj->service_description);
                if ($services_count++ == 10)
                    break;
            }
        }
    }
    
    //HOST
    $args = array(
        "cmd" => "gethosts",
        "host_name" => "lk:" . $query . ";alias=lk:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'host_name:a',
        'records' => 10,
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);
    
    if ($xres1) {
        foreach ($xres1->host as $obj) {
           $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=hosts&host='.urlencode(strval($obj->host_name)), 
                                     'value' => strval($obj->host_name),
                                     'category' => _('Host'),
                                     'label' => (stripos(strval($obj->host_name),$query) !== false) ? strval($obj->host_name) : _('[A] ') . strval($obj->alias)
                                    );
        }
    }
    
    // HOSTGROUP
    $args = array(
        "cmd" => "gethostgroups",
        "hostgroup_name" => "lk:" . $query . ";alias=lk:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'hostgroup_name:a',
        'records' => 10,
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);

    if ($xres1) {
        foreach ($xres1->hostgroup as $obj) {
            $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=hostgroups&hostgroup='.urlencode(strval($obj->hostgroup_name)), 
                                      'value' => strval($obj->hostgroup_name),
                                      'category' => _('Hostgroup'),
                                      'label' => (stripos(strval($obj->hostgroup_name),$query) !== false) ? strval($obj->hostgroup_name) : _('[A] '). strval($obj->alias)
                                    );
        }
    }

    
    // SERVICEGROUP
    $args = array(
        "cmd" => "getservicegroups",
        "servicegroup_name" => "lk:" . $query . ";alias=lk:" . $query,
        "brevity" => 1,
        "is_active" => 1,
        'orderby' => 'servicegroup_name:a',
    );
    $res1 = get_backend_data($args);

    $xres1 = simplexml_load_string($res1);

    if ($xres1) {
        foreach ($xres1->servicegroup as $obj) {
            //$names[]=strtolower($obj->servicegroup_name);
            $names[] = (object) array('url' => get_base_url().'/includes/components/xicore/status.php?show=servicegroups&servicegroup='.urlencode(strval($obj->servicegroup_name)), 
                                      'value' => strval($obj->servicegroup_name),
                                      'category' => _('Servicegroup'),
                                      'label' => (stripos(strval($obj->servicegroup_name),$query) !== false) ? strval($obj->servicegroup_name) : _('[A] '). strval($obj->alias)
                                    );
        }
    }
    
    
    
    echo json_encode(array_slice($names, 0, 10));

}
