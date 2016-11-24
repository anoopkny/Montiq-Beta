<?php
/***************************************************************************\
 *                 MRTS - MRTG RRDtool Total Statistics v0.1                 *
 *****************************************************************************
 * This program is free software; you can redistribute it and/or modify it   *
 * under the terms of the GNU General Public License as published by the     *
 * Free Software Foundation; either version 2 of the License, or (at your    *
 * option) any later version.                                                *
 *****************************************************************************
 * This script is written by Thor Dreier                                     *
 * More information can be found at  http://apt-get.dk/mrts/                 *
 * \***************************************************************************/

/////////////Modifications for Nagios XI////////////////////
/*
*   v1.7.0
*   Added HighCharts graph section to match other Nagios XI charts
*   this change did not alter the origional component functionality
*   $graphtype: 0 => HighCharts, 1 => MRTS
*   - lgroschen
*/

/*
*   changed CSS classes to fit theme for Nagios XI
*   eliminated some line breaks in code to make it more compact and more similar to XI's format
*   moved functions to bottom of page to more easily trace MAIN script logic 
*   added wrapper functions to clean $_REQUEST variables 
*   added authorization functions to allow multi-tenancy with Nagios XI
*/

////////////////Nagios XI AUTH////////////////////////////
require_once(dirname(__FILE__) . '/../../common.inc.php');

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


///////////////////END XI AUTH///////////////////////////////

/* The directory where the rrd files are located */
/*$dir = '/var/log/mrtg';*/
$dir = '/var/lib/mrtg';
$extension = '.rrd';

/* List all devices that MRTS should'n display, */
$exclude = array('secret', 'topsecret');

/* RRDtool path - where are the the executable located */
$rrdcommand = '/usr/bin/rrdtool';


/***************************************************************************\
 *                It should be no need to edit anything below                *
 *                   this point, unless there are problems                   *
 *****************************************************************************
 * - or said in another way - if you change anything radical below here, you *
 * have to make the changes public, as this code is GNU licensed code        *
 * \***************************************************************************/


/***************************************************************************\
 *                                 Variables                                 *
 * \***************************************************************************/

/* File extension of the MRTG-RRD-files */
$extension = '.rrd';

/* This version */
$version = 'v0.1.1';

/* The title */
/*$title = "MRTS - MRTG RRDtool Total Statistics $version";*/
$title = _("Bandwidth Usage Report");

////////XI Mods, used reqeust wrapper function //////
$name = grab_request_var("name", "");
$viewlist = grab_request_var("viewlist", "");
$picture = grab_request_var("picture", "");
$period = grab_request_var("period", "");
$year = grab_request_var("year", "");
$month = grab_request_var("month", "");
//fetch authorized hosts for this user 
$authIPs = fetch_auth_ips();
$mode = grab_request_var('mode', 'default');
$graphtype = grab_request_var('graphtype', 0);
$br_args = array();
/////////////////////////////////////////////


/***************************************************************************\
 *                                All the rest                             *
 ***************************************************************************/

/* Find legalnames */
$legalnames = array();
if ($dirhandler = @opendir($dir)) {
    while (($filename = readdir($dirhandler)) !== false) {
        if (strpos($filename, "$extension") !== false) {
            $filename = substr($filename, 0, -strlen($extension));
            if (!in_array($filename, $exclude)) {
                $legalnames[] = $filename;
            }
        }
    }
    closedir($dirhandler);
}

$host_services_list = get_host_services_list();

// Initial page load handler - a little repetitive
if (!empty($legalnames) && empty($name) && $viewlist == 0) {
    $sorter = array();
    $legalnames = array_values($legalnames);

    foreach ($legalnames as $fullname) {
        // var_dump($fullname);
        parse_name($fullname, $ip, $port);
        if (verify_service_in_list($host_services_list, $fullname)) {
            if (($display_name = find_bwselect_display_name($host_services_list, $fullname)) != "") {
                $sorter[$fullname] = $display_name;
            }
        }
    }

    natsort($sorter);
    $sorter = each($sorter);

    $name = $sorter[0];
}

/* If a device have been chosen */
if ($name != "") {
    parse_name($name, $ip, $port);

    /* If the device name is valid */
    if (validname($name) && isset($authIPs[$ip])) {
        /////////////////GRAPH IMAGES/////////////////
        /* If the script should generate a picture */
        if ($picture != "") //if(isset($_GET['picture']))
        {
            $fname = filename($name);
            header("content-type: image/png");
            $rrdcommand = "$rrdcommand graph - -v 'Bytes/s' -b 1024 -w 390 DEF:avgin=$fname:ds0:AVERAGE AREA:avgin#00CC00:'Traffic in' DEF:avgout=$fname:ds1:AVERAGE LINE2:avgout#0000FF:'Traffic out'";

            /* Last day */
            if ($period == 'day')
                $rrdcommand .= ' -t "Traffic the last day" -s -86400';

            /* Last week */
            else if ($period == 'week')
                $rrdcommand .= ' -t "Traffic the last week" -s -604800';

            /* Last month */
            else if ($period == 'month')
                $rrdcommand .= ' -t "Traffic the last month" -s -2678400';

            /* Last year */
            else if ($period == 'year')
                $rrdcommand .= ' -t "Traffic the last year" -s -31622400';

            /* If year and month is supplied, then generate picture for that month */
            else if (is_numeric($year) && is_numeric($month)) {
                $name = monthname($month) . ' ' . $year;
                $rrdcommand .= " -t 'Traffic for $name' " . monthstartend($year, $month);
                $rrdcommand .= " -x DAY:1:WEEK:1:DAY:1:86400:%d ";
            }

            echo `$rrdcommand`;
        } /* If year and month is supplied, then generate page for that month */
        else if (is_numeric($year) && is_numeric($month)) {
            //determin display mode
            if ($mode == 'default')
                echo top($name);
            if ($mode == 'csv') {
                header("Content-Disposition: attachment; filename={$name}.csv");
                header('Content-type:plain/text');
                print _("Bandwidth Usage Report: ") . $name . "\n\n";
            }
            if ($mode == 'pdf') {
                get_report_pdf();
                die();
            }
            if ($mode == 'jpg') {
                get_report_jpg();
                die();
            }

            $mname = monthname($month) . ' ' . $year;

            if ($mode == 'default') {
                if ($graphtype == 1) {
                    printf("<img src=\"%s?name=%s&amp;year=%s&amp;month=%s&amp;picture=yes&amp;graphtype=1\" alt=\"%s\">", $_SERVER['SCRIPT_NAME'], $name, $year, $month, $mname);
                } else {
                    // highcharts month graph
                    $period = "month";
                    create_highcharts_graph($br_args, $ip, $name, $legalnames, $host_services_list, $period, $mname, $month, $year);
                }
            }

            $lastdate = 0;
            //XIMOD: initialize $days array to prevent PHP notices
            $days = array();
            for ($i = 0; $i < 32; $i++)
                $days[$i] = array('in' => 0, 'out' => 0);

            /* Get statistics for the selected month */
            if ($fp = popen("$rrdcommand fetch " . filename($name) . " AVERAGE -r 864000 " . monthstartend($year, $month), 'r')) {
                fgets($fp, 4096);
                while (!feof($fp)) {
                    $line = trim(fgets($fp, 4096));

                    if ($line != '') {
                        list($date, $in, $out) = split('( )+', $line);
                        list($date) = split(':', $date);
                        if ($lastdate != 0) {
                            if (!is_numeric($in))
                                $in = 0;
                            if (!is_numeric($out))
                                $out = 0;

                            $in = $in * ($date - $lastdate);
                            $out = $out * ($date - $lastdate);

                            if ($month == date('n', $lastdate) && $year == date('Y', $lastdate)) {
                                $day = date('j', $lastdate);
                                if ($day) {
                                    $days[$day]['in'] += $in;
                                    $days[$day]['out'] += $out;
                                }
                            }

                        } //if($lastdate != 0)

                        $lastdate = $date;

                    } //if($line != '')
                } //while(!feof($fp))

                pclose($fp);

                if ($mode == 'csv')
                    showmonth_csv($year, $month, $days);
                else
                    showmonth($year, $month, $days);
            } //if($fp = popen($test, 'r'))

            if ($mode == 'default')
                echo bottom();

        }// END if(is_numeric($year) && is_numeric($month))
        /* Else generate main device page */
        else {
            if ($mode == 'csv') {
                header("Content-Disposition: attachment; filename={$name}.csv");
                header('Content-type:plain/text');
                print _("Bandwidth Usage Report: ") . $name . "\n\n";
            } else if ($mode == 'pdf') {
                get_report_pdf();
            } else if ($mode == 'jpg') {
                get_report_jpg();
            } else
                echo top($name);

            /* Find out when the database was last updated */
            if ($fp = popen("$rrdcommand info " . filename($name), 'r')) {
                if ($graphtype == 1) {
                    $key = '';
                    while (!feof($fp)) {
                        @list($key, $value) = split(' = ', trim(fgets($fp, 4096)));
                        if ($key == 'last_update' && $mode != 'csv') {
                            printf(_("Last updated") . ": %s<br>\n", date("Y-m-d H:i:s", $value));
                            break;
                        }
                    }
                    pclose($fp);
                }
            }

            if ($mode == 'default') {
                if ($graphtype == 1) {
                    // MRTG Graph images
                    print '<div style="width: 1024px; display: inline-block;">';
                    printf("<img src=\"%s?name=%s&amp;period=day&amp;picture=yes\" alt=\"Daily\">", $_SERVER['SCRIPT_NAME'], $name);
                    printf("<img src=\"%s?name=%s&amp;period=week&amp;picture=yes\" alt=\"Weekly\">", $_SERVER['SCRIPT_NAME'], $name);
                    printf("<img src=\"%s?name=%s&amp;period=month&amp;picture=yes\" alt=\"Monthly\">", $_SERVER['SCRIPT_NAME'], $name);
                    printf("<img src=\"%s?name=%s&amp;period=year&amp;picture=yes\" alt=\"Yearly\">\n", $_SERVER['SCRIPT_NAME'], $name);
                    print '</div>';
                } else {
                    // highcharts multiple graphs
                    $period = "multiple";
                    create_highcharts_graph($br_args, $ip, $name, $legalnames, $host_services_list, $period, null, null, null);
                }
            }

            $lastdate = 0;
            $months = array();

            /* Get statistics for the last two year */
            if ($fp = popen("$rrdcommand fetch " . filename($name) . " AVERAGE -s -63331200 -e +31622400", 'r')) {
                fgets($fp, 4096);
                while (!feof($fp)) {
                    $line = trim(fgets($fp, 4096));
                    if ($line != '') {
                        list($date, $in, $out) = split('( )+', $line);
                        list($date) = split(':', $date);
                        if ($lastdate != 0) {
                            if (!is_numeric($in))
                                $in = 0;
                            if (!is_numeric($out))
                                $out = 0;

                            $in = $in * ($date - $lastdate);
                            $out = $out * ($date - $lastdate);
                            $year = date('Y', $lastdate);
                            $month = date('n', $lastdate);

                            if (!array_key_exists($year, $months))
                                $months[$year] = array();
                            if (!array_key_exists($month, $months[$year]))
                                $months[$year][$month] = array();
                            if (!array_key_exists('in', $months[$year][$month]))
                                $months[$year][$month]['in'] = 0;
                            if (!array_key_exists('out', $months[$year][$month]))
                                $months[$year][$month]['out'] = 0;

                            $months[$year][$month]['in'] += $in;
                            $months[$year][$month]['out'] += $out;

                        } //if($lastdate != 0)

                        $lastdate = $date;

                    } //if($line != '')
                } //while(!feof($fp))
                pclose($fp);

                $year = date('Y');
                $lastyear = date('Y') - 1;

                if ($mode == 'csv') {
                    showyear_csv($year, $months[$year]);
                    showyear_csv($lastyear, $months[$lastyear]);
                } else {
                    showyear($year, $months[$year]);
                    showyear($lastyear, $months[$lastyear]);
                }


            } //if($fp = popen($test, 'r'))
            if ($mode == 'default')
                echo bottom();

        } //else

    } //if(validname($name))
    /* If device name has been provided, but it is not valid */
    else {
        //printf("Error: Device graph does not exist or not authorized for host.");
        header("Location: ?viewlist=1");
    }

} //if(isset($name))
/* If device name has been given, show the main page */
else if ($viewlist == 1 || empty($legalnames) || empty($name)) {
    //show main listing
    echo top('All Devices');
    ?>
    <div class='container-bw'>
        <table class='table table-condensed table-auto-width table-striped table-bordered'>
            <thead>
            <tr>
                <th><?php echo _("Host Name"); ?></th>
                <th><?php echo _("Address"); ?></th>
                <th><?php echo _("Port"); ?></th>
                <th><?php echo _("Description"); ?></th>
                <th><?php echo _("Action"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            // sort names
            natsort($legalnames);
            $slist = $host_services_list;

            $lastip = "";
            foreach ($legalnames as $name) {
                parse_name($name, $ip, $port);

                //XI MOD - adding host authorization
                if (!isset($authIPs[$ip])) continue; //skip unauthorized hosts

                if (!verify_service_in_list($slist, $name)) continue; // has the service been deleted? if so remove it from list

                //printf("<a href=\"%s?name=%s\">%s</a><br>\n", $_SERVER['SCRIPT_NAME'], $name, $name);
                echo "<tr>";
                $theip = $ip;
                if ($lastip == $ip) //still on the same host  //XI MOD - added hostname with IP for clarity
                {
                    $theip = "";
                    echo "<td></td>"; //add a blank td
                } else
                    echo "<td>" . $authIPs[$ip] . "</td>";

                echo "<td>" . $theip . "</td>";
                echo "<td>" . $port . "</td>";
                echo "<td>".find_bwselect_service_name($slist, $name)."</td>";
                printf("<td><span><i class='fa fa-share'></span></i>&nbsp;<a href=\"%s?name=%s\">%s</a></td>\n", $_SERVER['SCRIPT_NAME'], $name, _("View Report"));
                //echo "<td>".$name."</td>";
                echo "</tr>";

                $lastip = $ip;
            }

            if (empty($legalnames))
                echo "<tr><td colspan='4'>" . _("No valid devices are being monitored at this time") . "</td></tr>\n";
            ?>
            </tbody>
        </table>
    </div> <!-- end container div -->
    <?php
    echo bottom();
}

//////////////////////////////////////END MAIN/////////////////////////

/***************************************************************************\
 *                                 Functions                                 *
 * \***************************************************************************/


/* Change this to get another top on the site */
function top($name)
{
    global $request;
    global $name;
    global $legalnames;
    global $host_services_list;

    $graphtype = grab_request_var('graphtype');

    $hideoptions = grab_request_var('hideoptions', false);
    ?>
    <?php
    do_page_start(array("page_title" => $GLOBALS['title']), true);
    ?>
    
<!-- <script type="text/javascript" src="bandwidthreport.js"></script> -->

    <style type="text/css">
        .container-bw {
            margin: -10 0 0 10px;
            text-align: center;
        }

        table.bw {
            margin: 10px auto;
        }

        /* added CSS to center large tables */

        .datahead, .total1head, .total2head, .data, .total1, .total2 /* removed - border: 1px solid black;*/
        {
            width: 60px;
        }

        .datahead, .total1head, .total2head {
            font-size: 12px;
            font-weight: bold;
        }

        .data, .total1, .total2 {
            font-size: 10px;
        }

        .total1, .total1head {
            background-color: #dddddd;
        }

        .total2, .total2head {
            background-color: #bbbbbb;
        }

        h1 {
            text-align: left;
        }

        h2, h3 {
            text-align: center;
        }

    </style>

    <div class='container-bw'>

    <?php

    // Get a list of host/service names
    $list = $host_services_list;

    $title = find_bwselect_display_name($list, $name);
    parse_name($name, $ip, $port);
    if ($ip == "" || $port == "")
        $subtitle = $name;
    else
        $subtitle = $ip . " Port " . $port;

    //export to csv icon
    $url = 'index.php?';
    foreach ($request as $key => $value)
        $url .= '&' . urlencode($key) . '=' . urlencode($value);

    if ($name != '' && !$hideoptions) {
        ?>

    <form method="get" action="">
        <div class="well report-options">

            <div>

                <div class="reportexportlinks">                    
                   <?php echo get_add_myreport_html(_("Bandwidth Report"), $_SERVER["REQUEST_URI"], array()); ?>

                   <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <?php echo _('Download'); ?> <i class="fa fa-caret-down r"></i>
                        </button>
                         <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                            <li><a class="btn-export" data-type="csv" title="<?php echo _("Download as CSV"); ?>"><i class="fa fa-file-text-o l"></i> <?php echo _("CSV"); ?></a></li>
                            <li><a class="btn-export" data-type="pdf" title="<?php echo _("Download as PDF"); ?>"><i class="fa fa-file-pdf-o l"></i> <?php echo _("PDF"); ?></a></li>
                            <li><a class="btn-export" data-type="jpg" title="<?php echo _("Download as JPG"); ?>"><i class="fa fa-file-image-o l"></i> <?php echo _("JPG"); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="reportoptionpicker fl">
                    <?php echo _("View Report For"); ?>: <select name="name" id="name" class="form-control">
                        <?php
                        $legalnames = array_values($legalnames);
                        $sorter = array();

                        foreach ($legalnames as $fullname) {
                            parse_name($fullname, $ip, $port);
                            if (verify_service_in_list($list, $fullname)) {
                                if (($display_name = find_bwselect_display_name($list, $fullname)) != "") {
                                    $sorter[$fullname] = $display_name;
                                }
                            }
                        }

                        natsort($sorter);
                        foreach ($sorter as $fullname => $display_name) {
                            echo "<option value='" . $fullname . "' " . is_selected($name, $fullname) . ">" . $display_name . "</option>";
                        }
                        ?>
                    </select>
                     <div class="checkbox" style="margin: 0 10px;">
                        <label>
                            <input type="checkbox" name="graphtype" value="1" <?php echo is_checked($graphtype, 1); ?>> <?php echo _("Use Old Graphs"); ?>
                        </label>
                    </div>
                    <input type="submit" class="btn btn-sm btn-primary" name="btnSubmit" value="<?php echo _('Run'); ?>">
                </div>

                <a href="?viewlist=1" class="fl" style="line-height: 29px; margin-left: 20px;"><?php echo _("See all available reports"); ?></a>

                <div style="clear: both;"></div>

            </div>

        </div>
    </form>

    <?php
    } ?>
    <h1 style="padding-top: 0;"><?php echo $GLOBALS['title']; ?></h1>

    <input type="hidden" name="graphtype" value="<?php echo $graphtype; ?>">

    <h2><?php echo $title; ?></h2>
    <h4><?php echo $subtitle; ?></h4>

    <div style="display: inline-block;">
        <div id="daily_bandwidth_chart" style="display: inline-block;"></div>
        <div id="weekly_bandwidth_chart" style="display: inline-block;"></div>
    </div>
    <div style="display: inline-block;">
        <div id="monthly_bandwidth_chart" style="display: inline-block;"></div>
        <div id="yearly_bandwidth_chart" style="display: inline-block;"></div>
    </div>
<?php

}


/* Change this to get another bottom on the site */
function bottom()
{
    $graphtype = grab_request_var('graphtype');
    $month = grab_request_var('month');
    $year = grab_request_var('year');

    ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#name').searchable({maxMultiMatch: 999});
        });

        // Get the export button link and send user to it
        $('.btn-export').on('mousedown', function(e) {
            var type = $(this).data('type');
            var graphtype = "<?php echo $graphtype; ?>";

            // if only month, add month/year to URL
            var month = "<?php echo $month; ?>";
            var year = "<?php echo $year; ?>";
            if (month) {
                var monthstring = "&year=" + year;
                monthstring += "&month=" + month;
            }

            var formvalues = $("form").serialize();
            var url = "<?php echo get_base_url(); ?>includes/components/bandwidthreport/index.php?" + formvalues + "&mode=" + type + "&graphtype=" + graphtype + monthstring;
            if (e.which == 2) {
                window.open(url);
            } else if (e.which == 1) {
                window.location = url;
            }
        });
    </script>
    </div> <!-- end main container -->
    <!--<hr />-->
    <div class='container-bw'>
        <?php 
        if ($graphtype == 1) {
            echo _("Report generated by an enhanced version of MRTS for Nagios XI."); 
        } else {
            echo _("Report generated by HighCharts and an enhanced version of MRTS for Nagios XI."); 
        }
        ?>
    </div>

    </body>
    </html>
<?php

} //end bottom() 

/* Create updated graphs using HighCharts */
function create_highcharts_graph($br_args, $ip, $name, $legalnames, $host_services_list, $period, $mname, $month, $year) {

    // set containers for each period type
    if ($period == "month") {
        $br_args['container'] = array('monthly_bandwidth_chart');
    } else {
        $br_args['container'] = array('daily_bandwidth_chart', 'weekly_bandwidth_chart', 'monthly_bandwidth_chart', 'yearly_bandwidth_chart');    
    }

    //  sort select list
    natsort($legalnames);
    $legalnames = array_values($legalnames);

    if (empty($br_args['host'])) {
        $bandHost = find_bwselect_host_name($host_services_list, $name);
        $br_args['host'] = $bandHost;
    }

    if (empty($br_args['service'])) {
        $bandService = find_bwselect_service_name($host_services_list, $name);
        $br_args['service'] = $bandService;
    }

    //determine graph type 
    $graph = '';

    // initialize - if true show each day in graph
    $br_args['tickPixelInterval'] = 0;
    
    // Check if they are being sent as hostname or servicename
    if (empty($br_args['host'])) {
        $br_args['host'] = grab_request_var('hostname', NULL);
    }

    if (empty($br_args['service'])) {
        $br_args['service'] = grab_request_var('servicename', NULL);
    }

    //establish necessary vars
    if ($period == "month") {
        // parse month for graph
        $daysinmonth = date("t", mktime(0, 0, 0, $month, 1, $year));

        $start = 1;
        $end = $daysinmonth;

        $br_args['start'] = $month . "/" . $start . "/" . $year;
        $br_args['end'] = $month . "/" . $end . "/" . $year;
        // $br_args['increment'] = '1728';
        // display every other day
        $br_args['tickPixelInterval'] = 1;
    } else {
        $br_args['start'] = grab_request_var('start', '-24h');
        $br_args['end'] = grab_request_var('end','');
    }

    $br_args['container'] = grab_request_var('div', $br_args['container']); 
    $br_args['filter'] = grab_request_var('filter',''); 
    $br_args['height'] = grab_request_var('height', 250);
    $br_args['width'] = grab_request_var('width', 500);
    $br_args['view'] = grab_request_var('view', -1);
    $br_args['link'] = grab_request_var('link', '');
    $br_args['render_mode'] = grab_request_var('render_mode', '');
    $br_args['no_legend'] = grab_request_var('no_legend', 0);

    //timeline requirements  
    if(!isset($br_args['host'])) die(_("Host name is required. Could not find host name in current hosts. This may be from having an RRD file with no host configured anymore."));
    if(!isset($br_args['service'])) $br_args['service'] =  '_HOST_';
    require(dirname(__FILE__).'/fetch_rrd.php');
    require(dirname(__FILE__).'/templates/timeline.inc.php');

    //gather necessary data for timeline            
    //make a get call to the fetch_rrd.php script to grab the data and do JSON encode 
    $xmlDoc = '/usr/local/nagios/share/perfdata/'.pnp_convert_object_name($br_args['host']).'/'.pnp_convert_object_name($br_args['service']).'.xml';

    // Get the xmlDoc and units of measurement/names
    if (file_exists($xmlDoc)) {
        $xmlDat = simplexml_load_file($xmlDoc);
        $br_args['units'] = $xmlDat->xpath('/NAGIOS/DATASOURCE/UNIT');  // Units of measurement from perfdata 
        $br_args['names'] = $xmlDat->xpath('/NAGIOS/DATASOURCE/NAME');  // Perfdata names (rta and pl)
        $br_args['datatypes'] = $br_args['names']; 
    }

    print "<script type='text/javascript'>";

    //////////////////////////////////////////////////
    // Create a graph for each bandwidth report div //
    foreach($br_args['container'] as $key => $div) {

        // Make start date based on div
        if (isset($br_args['container'][$key]) && $div == 'weekly_bandwidth_chart') {
            $br_args['start'] = ge_format_start_time('-1w', $br_args['view']);
            $br_args['title'] = "Traffic the last Week";
        } else if (isset($br_args['container'][$key]) && $div == 'monthly_bandwidth_chart') {
            if ($period == "month") {
                $br_args['start'] = ge_format_start_time(strtotime($br_args['start']), $br_args['view']);
                $br_args['title'] = "Traffic for " . $mname;
            } else {
                $br_args['start'] = ge_format_start_time('-1m', $br_args['view']);
                $br_args['title'] = "Traffic the last Month";
            }            
        } else if (isset($br_args['container'][$key]) && $div == 'yearly_bandwidth_chart') {
            $br_args['start'] = ge_format_start_time('-1y', $br_args['view']);
            $br_args['title'] = "Traffic the last Year";
        } else if (isset($br_args['container'][$key]) && $div == 'daily_bandwidth_chart') {
            $br_args['start'] = ge_format_start_time('-24h', $br_args['view']);
            $br_args['title'] = "Traffic the last Day" ;
        }

        // Retrieve RRD data if it's available
        $br_args['nodata'] = false;

        if ($rrd = fetch_rrd($br_args)) {
            // Add ability to filter performance data sets
            $br_args['datastrings'] = $rrd['sets']; 
            $br_args['count'] = $rrd['count']; // Data points retrieved
            $br_args['increment'] = $rrd['increment'];
        } else {
            $br_args['nodata'] = true;
            $br_args['count'] = 0;
            $br_args['increment'] = 0;
        }

        $br_args['start'] .= '000'; // Make javacscript start time

        $br_args['UOM']  = ''; 
        // Concatenate UOM string for multiple data sets
        if (isset($br_args['units'])) {
            for ($i = 0; $i < count($br_args['units']); $i++) {
                $unit = $br_args['units'][$i];
                if ($unit == "%%") { $unit = "%"; }
                $br_args['UOM'] .= $unit.' ';
            }
        }

        // Misc vars for timeline
        $br_args['container'] = $div;

        // Lets create a URL to the host/service data pages
        if (empty($br_args['link'])) {
            if ($br_args['service'] == "_HOST_" || $br_args['service'] == "HOST") {
                $hs_url = get_base_url() . "/includes/components/xicore/status.php?show=hostdetail&host=" . $br_args['host'];
            } else {
                $br_args['service'] = str_replace("_", "+", $br_args['service']);
                $hs_url = get_base_url() . "/includes/components/xicore/status.php?show=servicedetail&host=" . $br_args['host'] . "&service=" . urlencode($br_args['service']);
            }
        } else {
            $hs_url = $br_args['link'];
        }

        $br_args['hs_url'] = $hs_url;
        $graph = fetch_timeline($br_args);

        print $graph;
    }

    print "</script>";
}


/* Checks if a name is a valid device name */
function validname($name)
{
    return in_array($name, $GLOBALS['legalnames']);
} //function validname($name)


/* Convert a device name to a file name */
function filename($name)
{
    return $GLOBALS['dir'] . '/' . $name . $GLOBALS['extension'];
} //function filename($name)


/* Formats a number with KB, MB etc. */
function humanreadable($size)
{
    $names = array('B', 'KB', 'MB', 'GB', 'TB');
    $times = 0;
    while ($size > 1024) {
        $size = round(($size * 100) / 1024) / 100;
        $times++;
    }
    return "$size " . $names[$times];
} //function humanreadable($size)


/* Convert a month number to a month name */
function monthname($no)
{
    $names = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    return $names[$no];
} //function monthname($no)


/* Convert year and month number to a string useful for rrdtool  */
function monthstartend($year, $month)
{
    $start = mktime(0, 0, 0, $month, 1, $year);
    if ($month == 12)
        $end = mktime(0, 0, 0, 1, 1, $year + 1);
    else
        $end = mktime(0, 0, 0, $month + 1, 1, $year);
    return " -s $start -e $end ";
}


/* Output HTML for a year */
function showyear($year, $months)
{
    $name = grab_request_var("name", "");
    $mode = grab_request_var('mode', 'default');
    $graphtype = grab_request_var('graphtype');
    $sumyear = array();
    $sumquater = array();

    printf("<h3>Year: %s</h3>\n", $year);
    printf("<table class='table table-condensed table-auto-width table-striped table-bordered bw'><tr><td></td>\n");

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        if ($graphtype == 1) {
            for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
                printf("<td class=\"datahead\"><a href=\"%s?name=%s&amp;year=%s&amp;month=%s&amp;graphtype=1\">%s (%s)</a></td>\n", $_SERVER['SCRIPT_NAME'], $name, $year, $i, monthname($i), $i);
        } else {
            for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
                printf("<td class=\"datahead\"><a href=\"%s?name=%s&amp;year=%s&amp;month=%s\">%s (%s)</a></td>\n", $_SERVER['SCRIPT_NAME'], $name, $year, $i, monthname($i), $i);
        }

        /* quater */
        printf("<td class=\"total1head\">Quarter&nbsp;%s</td>\n", $quater);
    }
    /* year */
    printf("<td class=\"total2head\">"._('Year')."</td>\n");
    printf("</tr><tr><td class=\"datahead\">"._('In')."</td>\n");

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++) {
            if (!array_key_exists($quater, $sumquater))
                $sumquater[$quater] = array();
            if (!array_key_exists('in', $sumquater[$quater]))
                $sumquater[$quater]['in'] = 0;

            printf("<td class=\"data\">%s</td>\n", humanreadable($months[$i]['in']));
            //$sumyear['in'] += $months[$i]['in'];
            //$sumquater[$quater]['in'] += $months[$i]['in'];
            if (array_key_exists($i, $months)) {
                if (!array_key_exists('in', $sumyear))
                    $sumyear['in'] = 0;
                if (!array_key_exists('in', $sumquater))
                    $sumquater['in'] = 0;
                $sumyear['in'] += grab_array_var($months[$i], 'in', 0);
                $sumquater[$quater]['in'] += grab_array_var($months[$i], 'in', 0);
            }
        }
        /* quater */
        printf("<td class=\"total1\">%s</td>\n", humanreadable($sumquater[$quater]['in']));
    }
    /* year */
    printf("<td class=\"total2\">%s</td>\n", humanreadable($sumyear['in']));
    printf("</tr><tr><td class=\"datahead\">"._('Out')."</td>\n");

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++) {
            if (!array_key_exists($quater, $sumquater))
                $sumquater[$quater] = array();
            if (!array_key_exists('out', $sumquater[$quater]))
                $sumquater[$quater]['out'] = 0;

            printf("<td class=\"data\">%s</td>\n", humanreadable($months[$i]['out']));
            //$sumyear['out'] += $months[$i]['out'];
            //$sumquater[$quater]['out'] += $months[$i]['out'];
            if (array_key_exists($i, $months)) {
                if (!array_key_exists('out', $sumyear))
                    $sumyear['out'] = 0;
                if (!array_key_exists('out', $sumquater))
                    $sumquater['out'] = 0;
                $sumquater[$quater]['out'] += grab_array_var($months[$i], 'out', 0);
                $sumyear['out'] += grab_array_var($months[$i], 'out', 0);
            }
        }
        /* quater */
        printf("<td class=\"total1\">%s</td>\n", humanreadable($sumquater[$quater]['out']));
    }
    /* year */
    printf("<td class=\"total2\">%s</td>\n", humanreadable($sumyear['out']));
    printf("</tr><tr><td class=\"datahead\">"._('Max')."</td>\n");

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
            printf("<td class=\"data\">%s</td>\n", humanreadable(max($months[$i]['in'], $months[$i]['out'])));

        /* quater */
        printf("<td class=\"total1\">%s</td>\n", humanreadable(max($sumquater[$quater]['in'], $sumquater[$quater]['out'])));
    }
    /* year */
    printf("<td class=\"total2\">%s</td>\n", humanreadable(max($sumyear['in'], $sumyear['out'])));
    printf("</tr><tr><td class=\"datahead\">"._('Sum')."</td>\n");

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
            printf("<td class=\"data\">%s</td>\n", humanreadable($months[$i]['in'] + $months[$i]['out']));

        /* quater */
        printf("<td class=\"total1\">%s</td>\n", humanreadable($sumquater[$quater]['in'] + $sumquater[$quater]['out']));
    }
    /* year */
    printf("<td class=\"total2\">%s</td>\n", humanreadable($sumyear['in'] + $sumyear['out']));
    printf("</tr></table>\n");

} //function showyear($year, $months)


/* Output CSV for a year 
 *  XI MOD: Modified Thor Dreier's showyear() function to create CSV output. All logic written by Thor Dreier.  
 */
function showyear_csv($year, $months)
{
    $name = grab_request_var("name", "");
    $sumyear = array();
    $sumquater = array();

    print _("Year").": {$year}\n";

    print ','; //start of table
    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
            print monthname($i) . "({$i}),";

        /* quater */
        print _("Quarter")." {$quater},";
    }
    /* year */
    print _("Year")."\n"; //endline
    print _("In").",";

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++) {
            if (!array_key_exists($quater, $sumquater))
                $sumquater[$quater] = array();
            if (!array_key_exists('in', $sumquater[$quater]))
                $sumquater[$quater]['in'] = 0;

            print humanreadable($months[$i]['in']) . ',';

            if (array_key_exists($i, $months)) {
                if (!array_key_exists('in', $sumyear))
                    $sumyear['in'] = 0;
                if (!array_key_exists('in', $sumquater))
                    $sumquater['in'] = 0;
                $sumyear['in'] += grab_array_var($months[$i], 'in', 0);
                $sumquater[$quater]['in'] += grab_array_var($months[$i], 'in', 0);
            }
        }
        /* quater */
        print humanreadable($sumquater[$quater]['in']) . ',';
    }
    /* year */
    print humanreadable($sumyear['in']) . ",\n"; //end line
    print _('Out').',';

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++) {
            if (!array_key_exists($quater, $sumquater))
                $sumquater[$quater] = array();
            if (!array_key_exists('out', $sumquater[$quater]))
                $sumquater[$quater]['out'] = 0;

            print humanreadable($months[$i]['out']) . ',';

            if (array_key_exists($i, $months)) {
                if (!array_key_exists('out', $sumyear))
                    $sumyear['out'] = 0;
                if (!array_key_exists('out', $sumquater))
                    $sumquater['out'] = 0;
                $sumquater[$quater]['out'] += grab_array_var($months[$i], 'out', 0);
                $sumyear['out'] += grab_array_var($months[$i], 'out', 0);
            }
        }
        /* quarter */
        print humanreadable($sumquater[$quater]['out']) . ',';
    }
    /* year */
    print humanreadable($sumyear['out']) . "\n"; //endline
    //MAX
    print _("Max").",";

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
            print humanreadable(max($months[$i]['in'], $months[$i]['out'])) . ',';

        /* quater */
        print humanreadable(max($sumquater[$quater]['in'], $sumquater[$quater]['out'])) . ',';
    }
    /* year */
    print humanreadable(max($sumyear['in'], $sumyear['out'])) . "\n"; //newline

    //SUM
    print "Sum,";

    for ($quater = 1; $quater <= 4; $quater++) {
        /* month */
        for ($i = ($quater - 1) * 3 + 1; $i <= ($quater - 1) * 3 + 3; $i++)
            print humanreadable($months[$i]['in'] + $months[$i]['out']) . ',';

        /* quater */
        print humanreadable($sumquater[$quater]['in'] + $sumquater[$quater]['out']) . ',';
    }
    /* year */
    print humanreadable($sumyear['in'] + $sumyear['out']) . "\n"; //newline

    print "\n\n";
} //function showyear_csv($year, $months)


/* Output HTML for a month */
function showmonth($year, $month, $days)
{
    $summonth = array('in' => 0, 'out' => 0);
    $daysinmonth = date("t", mktime(0, 0, 0, $month, 1, $year));

    printf("<h3>"._("Month").": %s %s</h3>\n", $year, monthname($month));

    for ($j = 1; $j <= 2; $j++) {
        if ($j == 1) {
            $start = 1;
            $end = 16;
        } else {
            $start = 17;
            $end = $daysinmonth;
        }

        printf("<table class='table table-condensed table-auto-width table-striped table-bordered bw'><tr><td></td>\n");

        for ($i = $start; $i <= $end; $i++)
            printf("<td class=\"datahead\">"._("Day")." %s</td>\n", $i);

        if ($j == 2)
            printf("<td class=\"total2head\">"._("Month")."</td>\n");

        printf("</tr><tr><td class=\"datahead\">"._("In")."</td>\n");


        for ($i = $start; $i <= $end; $i++) {
            printf("<td class=\"data\">%s</td>\n", humanreadable($days[$i]['in']));
            $summonth['in'] += $days[$i]['in'];
        }
        if ($j == 2)
            printf("<td class=\"total2\">%s</td>\n", humanreadable($summonth['in']));

        printf("</tr><tr><td class=\"datahead\">"._("Out")."</td>\n");

        for ($i = $start; $i <= $end; $i++) {
            printf("<td class=\"data\">%s</td>\n", humanreadable($days[$i]['out']));
            $summonth['out'] += $days[$i]['out'];
        }
        if ($j == 2)
            printf("<td class=\"total2\">%s</td>\n", humanreadable($summonth['out']));

        printf("</tr><tr><td class=\"datahead\">"._("Max")."</td>\n");

        for ($i = $start; $i <= $end; $i++)
            printf("<td class=\"data\">%s</td>\n", humanreadable(max($days[$i]['in'], $days[$i]['out'])));

        if ($j == 2)
            printf("<td class=\"total2\">%s</td>\n", humanreadable(max($summonth['in'], $summonth['out'])));


        printf("</tr><tr><td class=\"datahead\">"._("Sum")."</td>\n");

        for ($i = $start; $i <= $end; $i++)
            printf("<td class=\"data\">%s</td>\n", humanreadable($days[$i]['in'] + $days[$i]['out']));

        if ($j == 2)
            printf("<td class=\"total2\">%s</td>\n", humanreadable($summonth['in'] + $summonth['out']));

        printf("</tr></table>\n");

    } //for($j=1; $j<=2; $j++)
} //showmonth($year, $month, $days)

// Get the pdf version generated
function get_report_pdf()
{
    global $cfg;

    //get user name
    $username = $_SESSION["username"];
    // get backend ticket
    $backend_ticket = get_user_attr(0, "backend_ticket");

    // Assemble actual URL that will be gotten
    $prefix = ($cfg['use_https'] == true) ? 'https' : 'http';
    $uri = str_replace("mode=pdf", "hideoptions=1", $_SERVER["REQUEST_URI"]);
    $fullurl = "{$prefix}://127.0.0.1{$uri}";
    //echo "FULLURL: $fullurl\n";

    $urlparts = parse_url($fullurl);

    $newurl = "";
    $newurl .= get_internal_url() . "/includes/components/bandwidthreport/index.php";
    $newurl .= "?";
    if (isset($urlparts['query']))
        $newurl .= $urlparts['query'];

    $newurl .= "&username=" . $username;
    $newurl .= "&ticket=" . $backend_ticket;

    //echo "NEWURL: $newurl\n";

    $tmpfiles = array();

    // Add language to url
    $language = $_SESSION['language'];
    $newurl .= "&locale=" . $language;

    // Do page rendering

    $aurl = $newurl;

    $afile = "page.pdf";
    $fname = get_tmp_dir() . "/scheduledreport-" . $username . "-" . time() . "-" . $afile;

    $cmdft = '--footer-spacing 3 --margin-bottom 15mm --footer-font-size 9 --footer-right "Page [page] of [toPage]" --footer-left "' . get_datetime_string(time(), DT_SHORT_DATE_TIME, DF_AUTO, "null") . '"';

    $cmd = "/usr/bin/wkhtmltopdf --no-outline {$cmdft} '{$aurl}' '{$fname}' 2>&1";
    $out = @exec($cmd);

    //echo "CMD: $cmd\n";
    //exit();

    if (!file_exists($fname)) {
        echo "\n\n************\nERROR: Failed to render URL '" . $aurl . "' as '" . $fname . "'\n************\n\n";
    } else {
        // We'll be outputting a PDF
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="bandwidth.pdf"');

        // The PDF source is in original.pdf
        readfile($fname);
        unlink($fname);
    }
}

function get_report_jpg()
{
    global $cfg;

    //get user name
    $username = $_SESSION["username"];
    // get backend ticket
    $backend_ticket = get_user_attr(0, "backend_ticket");

    // Assemble actual URL that will be gotten
    $prefix = ($cfg['use_https'] == true) ? 'https' : 'http';
    $uri = str_replace("mode=jpg", "hideoptions=1", $_SERVER["REQUEST_URI"]);
    $fullurl = "{$prefix}://127.0.0.1{$uri}";
    //echo "FULLURL: $fullurl\n";

    $urlparts = parse_url($fullurl);

    $newurl = "";
    $newurl .= get_internal_url() . "/includes/components/bandwidthreport/index.php";
    $newurl .= "?";
    if (isset($urlparts['query']))
        $newurl .= $urlparts['query'];

    $newurl .= "&username=" . $username;
    $newurl .= "&ticket=" . $backend_ticket;

    //echo "NEWURL: $newurl\n";

    $tmpfiles = array();

    // Add language to url
    $language = $_SESSION['language'];
    $newurl .= "&locale=" . $language;

    // Do page rendering

    $aurl = $newurl;

    $afile = "page.jpg";
    $fname = get_tmp_dir() . "/scheduledreport-" . $username . "-" . time() . "-" . $afile;

    $cmd = "/usr/bin/wkhtmltoimage '{$aurl}' '{$fname}' 2>&1";
    $out = @exec($cmd);

    //echo "CMD: $cmd\n";
    //exit();

    if (!file_exists($fname)) {
        echo "\n\n************\nERROR: Failed to render URL '" . $aurl . "' as '" . $fname . "'\n************\n\n";
    } else {
        // We'll be outputting a JPG
        header('Content-type: application/jpg');
        header('Content-Disposition: attachment; filename="bandwidth.jpg"');

        // It will be called execsummary.jpg
        //

        // The JPG source is in original.jpg
        readfile($fname);
        unlink($fname);
    }
}

/*  Output CSV for a month 
*   XI MOD: modified original showmonth() function for CSV output
*/
function showmonth_csv($year, $month, $days)
{
    $summonth = array('in' => 0, 'out' => 0);
    $daysinmonth = date("t", mktime(0, 0, 0, $month, 1, $year));
    $start = 1;
    $end = $daysinmonth;

    print _("Month").": {$year} " . monthname($month) . "\n";

    //Day HEADERS
    print ',';
    for ($i = $start; $i <= $end; $i++)
        print _("Day")." {$i},";

    //if($j==2)
    print _("Month")."\n";

    print _("In").",";

    for ($i = $start; $i <= $end; $i++) {
        print humanreadable($days[$i]['in']) . ',';
        $summonth['in'] += $days[$i]['in'];
    }
    //end row
    print humanreadable($summonth['in']) . "\n";

    //OUT
    print "\n"._("Out").","; //newline
    for ($i = $start; $i <= $end; $i++) {
        print humanreadable($days[$i]['out']) . ',';
        $summonth['out'] += $days[$i]['out'];
    }
    //end row
    print humanreadable($summonth['out']) . "\n";

    //MAX
    print "\n"._("Max").","; //newline
    for ($i = $start; $i <= $end; $i++)
        print humanreadable(max($days[$i]['in'], $days[$i]['out'])) . ',';
    //end row
    print humanreadable(max($summonth['in'], $summonth['out'])) . "\n";

    //SUM
    print _("Sum").","; //newline
    for ($i = $start; $i <= $end; $i++)
        print humanreadable($days[$i]['in'] + $days[$i]['out']) . ',';
    //end row
    print humanreadable($summonth['in'] + $summonth['out']) . "\n";

    print "\n\n";
} //showmonth_csv($year, $month, $days)

function parse_name($name, &$ip, &$port)
{
    $pos = strpos($name, "_");
    $ip = substr($name, 0, $pos);
    $port = substr($name, $pos + 1);
}

//XI MOD - added function to check against authorized IP addresses
function fetch_auth_ips()
{
    $authIPs = array();
    $args = array('brevity' => 3);
    $XML = get_xml_host_objects($args);
    foreach ($XML->host as $host) //build restructured array based on IP address as key
        $authIPs["{$host->address}"] = "{$host->host_name}";

    return $authIPs;
}

// Get a list of host and services
function get_host_services_list()
{
    // Get the actual service/hostnames for the reports
    $str = get_service_status_xml_output(array());
    $x = simplexml_load_string($str);

    $services = array();
    foreach ($x->servicestatus as $service) {
        $c = explode("!", strval($service->check_command));

        if ($c[0] == "check_xi_service_mrtgtraf") {

            // Create their address and port
            $c_clean = str_replace(".rrd", "", $c[1]);
            list($address, $port) = explode("_", $c_clean);

            $s = array("display_name" => $service->host_name . " - " . $service->name,
                "host_name" => strval($service->host_name),
                "service_name" => strval($service->name));

            $services[$c_clean] = $s;
        }
    }

    return $services;
}

function find_bwselect_service_name($list, $fullname)
{
    if (array_key_exists($fullname, $list)) {
        return $list[$fullname]['service_name'];
    }
}

function find_bwselect_host_name($list, $fullname)
{
    if (array_key_exists($fullname, $list)) {
        return $list[$fullname]['host_name'];
    }
}
// Select the actual display name using the list given (this way it's only gotta make one XML call)
function find_bwselect_display_name($list, $fullname)
{
    if (array_key_exists($fullname, $list)) {
        return $list[$fullname]['display_name'];
    }
}

// Verify full name is in the list so we dont show deleted services
function verify_service_in_list($list, $fullname)
{
    foreach ($list as $key=>$value) {
        if($key == $fullname) {
            return true;
        }
    }

    return false;
}

// functions for highcharts graphs

// Checks to make sure the start time is correct format
/**
 * @param $start
 * @param $view
 *
 * @return int
 */
function ge_format_start_time($start, $view)
{
    // Date selected
    if ($view == 99) {
        return $start;
    } else if (is_numeric($start) || is_int($start)) {
        return $start; // Timestamp for custom times
    }

    // Check for view first
    if ($view >= 0) {
        if ($view == 0) {
            return (time() - 4*60*60);
        } else if ($view == 1) {
            return (time() - 24*60*60);
        } else if ($view == 2) {
            return strtotime("-7 days");
        } else if ($view == 3) {
            return strtotime("-1 month");
        } else if ($view == 4) {
            return strtotime("-1 year");
        }
    }

    // Then check for start time...
    if ($start == '-4h') {
        return (time() - 4*60*60);
    } else if ($start == '-24h') {
        return (time() - 24*60*60);
    } else if ($start == '-48h') {
        return (time() - 2*24*60*60);
    } else if ($start == '-1w') {
        return strtotime("-7 days");
    } else if ($start == '-1m') {
        return strtotime("-1 month");
    } else if ($start == '-1y') {
        return strtotime("-1 year");
    }
}
