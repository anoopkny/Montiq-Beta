<?php //api access for host/service lists for graph explorer 

require_once(dirname(__FILE__) . '/../../../common.inc.php');
include_once(dirname(__FILE__) . '/../dashlet.inc.php');
require_once(dirname(__FILE__) . '/../visFunctions.inc.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication(false);

$host = grab_request_var('host');
$service = grab_request_var('service');
$all = grab_request_var('all');
show_service_datatypes($host, $service, $all);
