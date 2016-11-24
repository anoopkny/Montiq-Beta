<?php
// MINEMAP
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: eventlog.php 359 2010-10-31 17:08:47Z egalstad $

require_once(dirname(__FILE__) . '/../../common.inc.php');

include_once(dirname(__FILE__) . '/dashlet.inc.php');

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


route_request();

function route_request()
{
    global $request;

    $mode = grab_request_var("mode");
    switch ($mode) {
        case "getdata":
            minemap_get_data();
            break;
        default:
            display_minemap();
            break;
    }
}

function display_minemap()
{

    $type = grab_request_var("type", "");
    $host = grab_request_var("host", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    
    $manual_run = grab_request_var("manual_run", 0);

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);
    
    // makes sure user has appropriate license level
    licensed_feature_check();

    // start the HTML page
    do_page_start(array("page_title" => "Minemap"), true);

    ?>
    

    <form action="" method="get">
		<div class="well report-options">
			<label><?php echo _("Limit To"); ?>:</label>
			<select name="hostgroup" id="hostgroupList" class="form-control">
				<option value=""><?php echo _("Hostgroup"); ?>:</option>
				<?php
				$args = array('orderby' => 'hostgroup_name:a');
				$xml = get_xml_hostgroup_objects($args);
				if ($xml) {
					foreach ($xml->hostgroup as $hg) {
						$name = strval($hg->hostgroup_name);
						echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
					}
				}
				?>
			</select>
			<select name="servicegroup" id="servicegroupList" class="form-control">
				<option value=""><?php echo _("Servicegroup"); ?>:</option>
				<?php
				$args = array('orderby' => 'servicegroup_name:a');
				$xml = get_xml_servicegroup_objects($args);
				if ($xml) {
					foreach ($xml->servicegroup as $sg) {
						$name = strval($sg->servicegroup_name);
						echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
					}
				}
				?>
			</select>

			<button type="submit" class="btn btn-sm btn-primary" name="goButton" id="goButton"><?php echo _('Update'); ?></button>
		   <!-- Set a variable to let us know it's okay to run this -->
		   <input type="hidden" name="manual_run" value="1">
		   </div>
       </form>
	   
	   <h1><?php echo _("Minemap"); ?></h1>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#servicegroupList').change(function () {
                $('#hostgroupList').val('');
            });
            $('#hostgroupList').change(function () {
                $('#servicegroupList').val('');
            });
        });
    </script>

    <?php
        // Die right here if we don't want to auto-load the page
        if ($disable_report_auto_run == 1 && $manual_run == 0 ) {
            die();
        }
     ?>
    <?php
    $dargs = array(
        DASHLET_ARGS => array(
            "type" => $type,
            "host" => $host,
            "hostgroup" => $hostgroup,
            "servicegroup" => $servicegroup,
        ),
    );
    /*
    echo "ARGS GOING IN=";
    print_r($dargs);
    echo "<BR>";
    */
    display_dashlet("minemap", "", $dargs, DASHLET_MODE_OUTBOARD);
    ?>

    <?php

    // closes the HTML page
    do_page_end(true);
}
	