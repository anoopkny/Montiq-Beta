<?php
//
// Metrics Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    global $request;
    $mode = grab_request_var("mode");

    switch ($mode) {
        case "chart":
            get_chart();
            break;
        case "highchart":
            highcarts_graph_data_parse();
            break;
        case "pnpchart":
            pnp_graph_data_parse();
            break;
        default:
            display_metrics();
            break;
    }
}

function display_metrics()
{

    $showperfgraphs = 0;
    $sortorder = "desc";
    $metric = "disk";
    $maxitems = 20;
    $type = "";
    $tab = "";
    $mc_args = array();

    // use saved values
    $prefs_raw = get_user_meta(0, "metrics_prefs");
    if (have_value($prefs_raw)) {
        $prefs = unserialize($prefs_raw);

        $showperfgraphs = grab_array_var($prefs, "showperfgraphs");
        $sortorder = grab_array_var($prefs, "sortorder");
        $metric = grab_array_var($prefs, "metric");
        $maxitems = grab_array_var($prefs, "maxitems");
        $type = grab_array_var($prefs, "type");
        $tab = grab_array_var($prefs, "tab");
    }

    // grab request vars
    $type = grab_request_var("type", $type);
    $host = grab_request_var("host", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $maxitems = grab_request_var("maxitems", $maxitems);
    $metric = grab_request_var("metric", $metric);
    $sortorder = grab_request_var("sortorder", $sortorder);
    $showperfgraphs = grab_request_var("showperfgraphs", $showperfgraphs);
    $tab = grab_request_var("tab", $tab);
    $details = grab_request_var("details", 0);
    $graphtype = grab_request_var("graphtype", 0);
    $advanced = grab_request_var("advanced", 0);

    // Settings for auto-running
    $manual_run = grab_request_var("manual_run", 0);

    // Hacky fix for maxitems being set to nothing...
    if ($maxitems == "") {
        $maxitems = 0;
    }

    // save values
    $prefs = array(
        "showperfgraphs" => $showperfgraphs,
        "sortorder" => $sortorder,
        "metric" => $metric,
        "maxitems" => $maxitems,
        "type" => $type,
        "tab" => $tab,
        "details" => $details
    );
    set_user_meta(0, "metrics_prefs", serialize($prefs), false);


    // makes sure user has appropriate license level
    licensed_feature_check();

    // start the HTML page
    do_page_start(array("page_title" => "Metrics"), true);
?>

<script type="text/javascript">
    $(document).ready(function () {
        $('#servicegroupList').change(function () {
            $('#hostgroupList').val('');
        });

        $('#hostgroupList').change(function () {
            $('#servicegroupList').val('');
        });

        // Add the ability to show the advanced options section
        $('#advanced-options-btn').click(function () {
            if ($('#advanced-options').is(":visible")) {
                $('#advanced-options').hide();
                $('#advanced').val(0);
                $('#advanced-options-btn').html('<?php echo _("Advanced"); ?> <i class="fa fa-chevron-down"></i>');
            } else {
                $('#advanced-options').show();
                $('#advanced').val(1);
                $('#advanced-options-btn').html('<?php echo _("Advanced"); ?> <i class="fa fa-chevron-up"></i>');
            }
        });
    });
</script>

<form action="" method="get" id="metricsForm">
    <div class="well report-options">
        
        <div>

            <div class="reportoptionpicker clear">
                <?php echo _("Limit To"); ?>
                <select name="hostgroup" id="hostgroupList" style="width: 150px;" class="form-control">
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
                <select name="servicegroup" id="servicegroupList" style="width: 150px; margin-right: 5px;" class="form-control">
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
                <?php echo _("Metric"); ?>
                <select name="metric" id="metricList" class="form-control" style="margin-right: 5px;">
                    <?php
                    $metrics = get_metric_names();
                    foreach ($metrics as $mn => $md) {
                        $name = $mn;
                        echo "<option value='" . $name . "' " . is_selected($metric, $name) . ">$md</option>\n";
                    }
                    ?>
                </select>
                <?php echo _("Show"); ?>
                <select name="sortorder" id="sortorderList" class="form-control">
                    <?php
                    $sortorders = array(
                        "desc" => "Top",
                        "asc" => "Bottom",
                    );
                    foreach ($sortorders as $sn => $sd) {
                        $name = $sn;
                        echo "<option value='" . $name . "' " . is_selected($sortorder, $name) . ">$sd</option>\n";
                    }
                    ?>
                </select>
                <input type="text" name="maxitems" class="form-control" value="<?php echo htmlentities($maxitems); ?>" size="2">
                
                <a id="advanced-options-btn" class="tt-bind" data-placement="bottom" title="<?php echo _('Toggle advanced options'); ?>"><?php echo _('Advanced'); ?>  <?php if (!$advanced) { echo '<i class="fa fa-chevron-down"></i>'; } else { echo '<i class="fa fa-chevron-up"></i>'; } ?></a>
                <input type="hidden" value="<?php echo intval($advanced); ?>" id="advanced" name="advanced">

                <button type="submit" id="goButton" class='btn btn-sm btn-primary' name='goButton'><?php echo _("Run"); ?></button>
            </div>

            <div id="advanced-options" style="<?php if (!$advanced) { echo 'display: none;'; } ?>">
                <div class="floatbox">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="details" value="1" <?php echo is_checked($details, 1); ?>> <?php echo _("Remove Details"); ?>
                        </label>
                    </div>
                </div>
                <div class="floatbox">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="graphtype" value="1" <?php echo is_checked($graphtype, 1); ?>> <?php echo _("Use Old Graphs"); ?>
                        </label>
                    </div>
                </div>

                <div style="clear: both;"></div>
            </div>

        </div>
        <input type="hidden" name="manual_run" value="1">
        <input type="hidden" name="disable_metrics_auto_run" value="1">

    </div>
</form>

    <h1 style="padding-top: 0;"><?php echo _("Metrics"); ?></h1>
    <div class="clear"></div>

    <!-- Disable metrics auto-run settings -->
    <input type="hidden" name="tab_hash" id="tab_hash" value="metricsFormtabname" />
    <input type="hidden" name="tab" class="form-control" value="<?php echo encode_form_val($tab); ?>" id="metricsFormtabname">
    <input type="hidden" name="graphtype" value="<?php echo encode_form_val($graphtype); ?>">
    <input type="hidden" name="details" value="<?php echo encode_form_val($details); ?>">

    <?php
    // Disable running if they disabled running metrics automatically
    $disable_metrics_auto_run = get_option("disable_metrics_auto_run", 0);
    if ($disable_metrics_auto_run == 0 || ($disable_metrics_auto_run == 1 && $manual_run == 1)) {
    ?>

    <div id="tabs" style="display: none;">
        <ul>
            <li><a href="#tab-summary"><?php echo _("Summary"); ?></a></li>
            <li><a href="#tab-graphs"><?php echo _("Graphs"); ?></a></li>
            <li><a href="#tab-gauges"><?php echo _("Gauges"); ?></a></li>
        </ul>

        <div id="tab-summary">
            <?php
            $dargs = array(
                DASHLET_ARGS => array(
                    "type" => $type,
                    "host" => $host,
                    "hostgroup" => $hostgroup,
                    "servicegroup" => $servicegroup,
                    "maxitems" => $maxitems,
                    "metric" => $metric,
                    "sortorder" => $sortorder,
                    "showperfgraphs" => $showperfgraphs,
                    "details" => $details
                ),
            );
            /*
            echo "ARGS GOING IN=";
            print_r($dargs);
            echo "<BR>";
            */
            display_dashlet("metrics", "", $dargs, DASHLET_MODE_OUTBOARD);
            ?>
        </div>

        <?php

        // get service metrics
        $args = array(
            "type" => $type,
            "host" => $host,
            "hostgroup" => $hostgroup,
            "servicegroup" => $servicegroup,
            "maxitems" => $maxitems,
            "metric" => $metric,
            "sortorder" => $sortorder,
            "details" => $details
        );
        $metricdata = get_service_metrics($args);

        ?>

        <div id="tab-graphs">
            <div class="stausdetail_chart_timeframe_selector"<?php if (!use_2014_features()) {
                echo ' style="display:none;"';
            } ?>>
                <?php echo _('Graph Timeframe'); ?>:
                <select id="metrics-timeframe-select" class="form-control">
                    <option value="0"><?php echo _('Last 4 Hours'); ?></option>
                    <option value="1" selected><?php echo _('Last 24 Hours'); ?></option>
                    <option value="2"><?php echo _('Last 7 Days'); ?></option>
                    <option value="3"><?php echo _('Last 30 Days'); ?></option>
                    <option value="4"><?php echo _('Last 365 Days'); ?></option>
                </select>
            </div>

        <script type="text/javascript">
            $(document).ready(function () {
                load_perfgraphs_panel();

                // Timeframe selection
                $("#metrics-timeframe-select").change(function () {
                    load_perfgraphs_panel();
                });
            });
        
            function load_perfgraphs_panel() {
                var base_url = "<?php echo get_base_url(); ?>";
                var graphtype = <?php echo json_encode(encode_form_val($graphtype)); ?>;
                var metricdata = "<?php echo base64_encode(serialize($metricdata)); ?>";
                var mc_args = "<?php echo base64_encode(serialize($mc_args)); ?>";

                // Load default time settings
                <?php if (use_2014_features()) { ?>
                    var view = $("#metrics-timeframe-select option:selected").val();
                <?php } else { ?>
                    view = 1;
                <?php } ?>

                if (graphtype == 1) {
                    $.ajax({
                        type: "POST",
                        url: base_url + "includes/components/metrics/?cmd=getxicoreajax&tab=tab-graphs&mode=pnpchart",
                        data: { metricdata: metricdata, mc_args: mc_args, view: view, json: 1 },
                        success: function(data) {
                            $("#chart").html(data);
                        }
                    });
                } else {
                    $.ajax({
                        type: "POST",
                        url: base_url + "includes/components/metrics/?tab=tab-graphs&mode=highchart",
                        data: { metricdata: metricdata, mc_args: mc_args, view: view, json: 1 },
                        success: function(data) {
                            $("#highcharts").html(data);
                        }
                    });
                }
            }
        </script>

            <?php
            if ($graphtype == 1) {
                // create pnp graph container
                echo '<div id="chart" style="display: inline-block;"></div>';
            } else {
                $json = 0;
                // create highcharts container div
                echo '<div id="highcharts" style="display: inline-block;"></div>';
                // this section is generated by ajax
            }
            ?>
        </div>

        <div id="tab-gauges">
            <?php
            foreach ($metricdata as $id => $arr) {

                $hostname = $arr["host_name"];
                $servicename = $arr["service_name"];

                ?>
                <div style="margin: 0 25px 25px 0; float: left;">
                    <?php

                    $dargs = array(
                        DASHLET_ARGS => array(
                            "host" => $hostname,
                            "service" => $servicename,
                            "metric" => $metric,
                            "percent" => $arr["sortval"],
                            "current" => $arr["current"],
                            "uom" => $arr["uom"],
                            "warn" => $arr["warn"],
                            "crit" => $arr["crit"],
                            "min" => $arr["min"],
                            "max" => $arr["max"],
                            "plugin_output" => $arr["output"],
                        ),
                    );

                    display_dashlet("metricsguage", "", $dargs, DASHLET_MODE_OUTBOARD);

                    ?>
                </div>
            <?php

            }

            ?>

        </div>
    </div>

    <?php
    }

    // closes the HTML page
    do_page_end(true);
}


///////////////////////////////////////////////////////////////////
// FUNCTIONS
///////////////////////////////////////////////////////////////////

function get_chart()
{

    require_once('../jpgraph/src/jpgraph.php');
    require_once('../jpgraph/src/jpgraph_odo.php');


    $width = grab_request_var("width", 160);
    $height = grab_request_var("height", 80);
    $percent = grab_request_var("percent", 0);
    $current = grab_request_var("current", 0);
    $warn = grab_request_var("warn", 0);
    $crit = grab_request_var("crit", 0);
    $min = grab_request_var("min", 0);
    $max = grab_request_var("max", 0);
    $minscale = grab_request_var("minscale", 0);
    $maxscale = grab_request_var("maxscale", 100);

    $colors = array('#1E90FF', '#2E8B57', '#ADFF2F', '#DC143C', '#BA55D3');


    // create canvas
    $canvas = new OdoGraph($width, $height);

    // Create the odometer
    $odo = new Odometer();

    // set needle
    $odo->needle->Set($percent);
    $odo->needle->SetStyle(NEEDLE_STYLE_STRAIGHT);
    $odo->needle->SetShadow();

    //$odo->scale->Set($minscale,$maxscale);

    $crit_start = 0;
    $crit_end = 100;
    $warn_start = 0;
    $warn_end = 0;
    if ($crit > 0 && $max > 0) {
        $crit_start = ($crit / ($max - $min)) * 100;
        $odo->AddIndication($crit_start, $crit_end, "red");
    }
    if ($warn > 0 && $max > 0 && $crit_start > 0) {
        $warn_start = ($warn / ($max - $min)) * 100;
        $odo->AddIndication($warn_start, $crit_start, "yellow");
    }


    // canvas colors
    $canvas->SetMargin(0, 0, 0, 0);
    $canvas->SetMarginColor("white");
    $canvas->SetColor("white");

    // odo colors
    $odo->SetColor("white");

    // add odometer to canvas
    $canvas->Add($odo);

    $canvas->Stroke();
}

/* Create updated graphs using HighCharts */
function create_highcharts_graph($mc_args, $hostname, $servicename) {

    // get host
    $mc_args['host'] = $hostname;
    $mc_args['service'] = $servicename;

    // parse host/service name and create div
    $hostDiv = str_replace(".", "_", $hostname);
    $serviceDiv = str_replace(" ", "_", $servicename);
    $serviceDiv = str_replace("/", "Root", $serviceDiv);
    $serviceDiv = str_replace(str_split('\\/:*?"<>|'), "", $serviceDiv);
    $mc_args['container'] = $hostDiv . "_" . $serviceDiv . "_metric_graph";

    //determine graph type 
    $graph = '';

    // initialize - if true show each day in graph
    $mc_args['tickPixelInterval'] = 0;
    
    // Check if they are being sent as hostname or servicename
    if (empty($mc_args['host']))
        $mc_args['host'] = grab_request_var('hostname', NULL);

    if (empty($mc_args['service']))
        $mc_args['service'] = grab_request_var('servicename', NULL);

    $mc_args['start'] = grab_request_var('start', '-24h');
    $mc_args['end'] = grab_request_var('end','');

    // $mc_args['container'] = grab_request_var('div', $mc_args['container']);
    $mc_args['filter'] = grab_request_var('filter',''); 
    $mc_args['height'] = grab_request_var('height', 250);
    $mc_args['width'] = grab_request_var('width', 500);
    $mc_args['view'] = grab_request_var('view', -1);
    $mc_args['link'] = grab_request_var('link', '');
    $mc_args['render_mode'] = grab_request_var('render_mode', '');
    $mc_args['no_legend'] = grab_request_var('no_legend', 0);
    $mc_args['start'] = ge_format_start_time('-24h', $mc_args['view']);
    $mc_args['title'] = "$hostname";

    //timeline requirements  
    if(!isset($mc_args['host'])) die("Host name is required for timeline graph");
    if(!isset($mc_args['service'])) $mc_args['service'] =  '_HOST_';
    require_once(dirname(__FILE__).'/fetch_rrd.php');
    require_once(dirname(__FILE__).'/templates/metrics_template.inc.php');

    //gather necessary data for graph           
    //make a get call to the fetch_rrd.php script to grab the data and do JSON encode 
    $xmlDoc = '/usr/local/nagios/share/perfdata/'.pnp_convert_object_name($mc_args['host']).'/'.pnp_convert_object_name($mc_args['service']).'.xml';

    // Get the xmlDoc and units of measurement/names
    if (file_exists($xmlDoc)) {
        $xmlDat = simplexml_load_file($xmlDoc);
        $mc_args['units'] = $xmlDat->xpath('/NAGIOS/DATASOURCE/UNIT');  // Units of measurement from perfdata 
        $mc_args['names'] = $xmlDat->xpath('/NAGIOS/DATASOURCE/NAME');  // Perfdata names (rta and pl)
        $mc_args['datatypes'] = $mc_args['names']; 
    }

    print "<script type='text/javascript'>";

    // Retrieve RRD data if it's available
    $mc_args['nodata'] = false;

    if ($rrd = fetch_rrd($mc_args)) {
        // Add ability to filter performance data sets
        $mc_args['datastrings'] = $rrd['sets']; 
        $mc_args['count'] = $rrd['count']; // Data points retrieved
        $mc_args['increment'] = $rrd['increment'];
    } else {
        $mc_args['nodata'] = true;
        $mc_args['count'] = 0;
        $mc_args['increment'] = 0;
    }

    $mc_args['start'] .= '000'; // Make javacscript start time

    $mc_args['UOM']  = ''; 
    // Concatenate UOM string for multiple data sets
    if (isset($mc_args['units'])) {
        for ($i = 0; $i < count($mc_args['units']); $i++) {
            $unit = $mc_args['units'][$i];
            if ($unit == "%%") { $unit = "%"; }
            $mc_args['UOM'] = $unit.' ';
        }
    }

    // Lets create a URL to the host/service data pages
    if (empty($mc_args['link'])) {
        if ($mc_args['service'] == "_HOST_" || $mc_args['service'] == "HOST") {
            $hs_url = get_base_url() . "/includes/components/xicore/status.php?show=hostdetail&host=" . $mc_args['host'];
        } else {
            $mc_args['service'] = str_replace("_", "+", $mc_args['service']);
            $hs_url = get_base_url() . "/includes/components/xicore/status.php?show=servicedetail&host=" . $mc_args['host'] . "&service=" . urlencode($mc_args['service']);
        }
    } else {
        $hs_url = $mc_args['link'];
    }

    $mc_args['hs_url'] = $hs_url;
    $graph = fetch_timeline($mc_args);

    print $graph;

    print "</script>";
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

            $s = array("display_name" => $service->host_name . " - " . $service->display_name,
                       "host_name" => strval($service->host_name),
                       "service_name" => strval($service->display_name));

            $services[$c_clean] = $s;
        }
    }

    return $services;
}

// Select the actual display name using the list given (this way it's only gotta make one XML call)
function find_bwselect_display_name($list, $fullname)
{
    if (array_key_exists($fullname, $list)) {
        return $list[$fullname]['display_name'];
    }
}

function highcarts_graph_data_parse() {
    // decode JSON if necessary
    $metricdata = grab_request_var("metricdata", "");
    $mc_args = grab_request_var("mc_args", "");
    $view = grab_request_var("view", "");
    $json = grab_request_var("json", "");

    if ($json > 0) {
        $metricdata = unserialize(base64_decode($metricdata));
        $mc_args = unserialize(base64_decode($mc_args));
    }

    foreach ($metricdata as $id => $arr) {
        // set name
        $hostname = $arr["host_name"];
        $servicename = $arr["service_name"];

        // host and service names used to create container div
        $hostDiv = str_replace(".", "_", $hostname);
        $serviceDiv = str_replace(" ", "_", $servicename);
        $serviceDiv = str_replace("/", "Root", $serviceDiv);
        $serviceDiv = str_replace(str_split('\\/:*?"<>|'), "", $serviceDiv);

        echo '<div id="' . $hostDiv . "_" . $serviceDiv . '_metric_graph" style="display: inline-block;"></div><br><br>';

        // create highcharts graph
        create_highcharts_graph($mc_args, $hostname, $servicename);
    }
}

function pnp_graph_data_parse() {
    // decode JSON if necessary
    $metricdata = grab_request_var("metricdata", "");
    $view = grab_request_var("view", "");
    $json = grab_request_var("json", "");

    if ($json > 0) {
        $metricdata = unserialize(base64_decode($metricdata));
    }

    foreach ($metricdata as $id => $arr) {
        $hostname = $arr["host_name"];
        $servicename = $arr["service_name"];

        if (perfdata_chart_exists($hostname, $servicename) == true) {
            $img = perfdata_get_graph_image_url($hostname, $servicename, 1, $view);
            $oid = get_service_id($hostname, $servicename);
            $perfurl = get_base_url() . "perfgraphs/?host=" . urlencode($hostname) . "&service=" . urlencode($servicename) . "&service_id=" . $oid . "&mode=2&view=" . $view;
            echo '<div style="margin-bottom: 25px;">';
            echo '<a href="' . $perfurl . '" target="_blank"><img src="' . $img . '"></a>';
            echo '</div>';
        }
    }
}