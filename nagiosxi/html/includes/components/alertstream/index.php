<?php
//
// Alert Stream Report
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__).'/../../common.inc.php');

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
    $mode = grab_request_var("mode");

    switch ($mode) {
        case "getreport":
            get_alertstream_report();
            break;
        default:
            display_alertstream();
            break;
    }
}


function display_alertstream()
{

    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", ""); 
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $statetype = grab_request_var("statetype", "both");

    $width = grab_request_var("width", 750);
    $height = grab_request_var("height", 425);

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

    // We search for hosts, so clear host if search is present
    if (!empty($search)) {
        $host = "";
        $service = "";
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    $auto_start_date = get_datetime_string(strtotime('yesterday'), DT_SHORT_DATE);
    $auto_end_date = get_datetime_string(strtotime('today'), DT_SHORT_DATE);

    // Makes sure user has appropriate license level
    licensed_feature_check();

    do_page_start(array("page_title" => _("Alert Stream")), true);
?>

<script type="text/javascript">
$(document).ready(function() {

    // If we should run it right away
    if (!<?php echo $disable_report_auto_run; ?>) {
        run_alertstream_ajax();
    }

    showhidedates();

    $('#hostList').searchable({maxMultiMatch: 9999});

    $('#hostList').change(function() {
        if ($(this).val() != '') {
            $('#searchBox').val('');
        }
    });

    $("#searchBox").each(function() {
        $(this).myautocomplete({ source: suggest_url+'?type=host', 
                               minLength: 1,
                               select: function(e, ui){
                                   $('#maincontentframe').attr('src', ui.item.url);
                                   e.preventDefault();
                                   $("#navbarSearchBox").val('');
                                }
                            });
    });

    $('#searchBox').keypress(function() {
        $('#hostList').val('');
    }); 

    $('#startdateBox').click(function () {
        $('#reportperiodDropdown').val('custom');
        if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
            $('#startdateBox').val('<?php echo $auto_start_date;?>');
            $('#enddateBox').val('<?php echo $auto_end_date;?>');
        }
    });
    $('#enddateBox').click(function () {
        $('#reportperiodDropdown').val('custom');
        if ($('#startdateBox').val() == '' && $('#enddateBox').val() == '') {
            $('#startdateBox').val('<?php echo $auto_start_date;?>');
            $('#enddateBox').val('<?php echo $auto_end_date;?>');
        }
    });

    // Actually return the report
    $('#run').click(function() {
        run_alertstream_ajax();
    });

    $('#reportperiodDropdown').change(function () {
        showhidedates();
    });

    // Get the export button link and send user to it
    $('.btn-export').on('mousedown', function(e) {
        var type = $(this).data('type');
        var formvalues = $("form").serialize();
        formvalues += '&mode=getreport';
        var url = "<?php echo get_base_url(); ?>includes/components/alertstream/index.php?" + formvalues + "&mode=" + type;
        if (e.which == 2) {
            window.open(url);
        } else if (e.which == 1) {
            window.location = url;
        }
    });

});

var report_sym = 0;
function run_alertstream_ajax() {
    report_sym = 1;
    setTimeout('show_loading_report()', 500);

    var formvalues = $("form").serialize();
    formvalues += '&mode=getreport';
    var url = 'index.php?'+formvalues;

    current_page = 1;

    $.get(url, {}, function(data) {
        report_sym = 0;
        hide_throbber();
        $('#report').html(data);
        $('#report .tt-bind').tooltip();
    });
}
</script>

<script type='text/javascript' src='<?php echo get_base_url(); ?>includes/js/reports.js?<?php echo get_build_id(); ?>'></script>

<form method="get" data-type="alertstream">
    <div class="well report-options">
    
        <div class="reportexportlinks">
            <?php echo get_add_myreport_html(_("Alert Stream"), $_SERVER['PHP_SELF'], array()); ?>
        </div>

        <div class="reportsearchbox">
            <input type="text" size="15" name="search" id="searchBox" value="<?php echo encode_form_val($search); ?>" placeholder="<?php echo _("Search..."); ?>" class="textfield form-control">
        </div>
        
        <div class="reporttimepicker">
            
            <!--
            <div class="period">
                <?php echo _("Period"); ?>
                <select id="reportperiodDropdown" name="reportperiod" class="form-control">
                    <?php
                    $tp = get_report_timeperiod_options();
                    foreach ($tp as $shortname => $longname) {
                        echo "<option value='" . $shortname . "' " . is_selected($shortname, $reportperiod) . ">" . $longname . "</option>";
                    }
                    ?>
                </select>
            </div>
            -->

            <div id="customdates" class="cal">
                <?php echo _("From"); ?>
                <input class="textfield form-control" type="text" id='startdateBox' name="startdate" value="<?php echo encode_form_val($startdate); ?>" size="16"><div id="startdatepickercontainer"></div>
                <div class="reportstartdatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
                <?php echo _("To"); ?>
                <input class="textfield form-control" type="text" id='enddateBox' name="enddate" value="<?php echo encode_form_val($enddate); ?>" size="16">
                <div id="enddatepickercontainer"></div>
                <div class="reportenddatepicker"><i class="fa fa-calendar fa-cal-btn"></i></div>
            </div>
        </div>

        <div class="reportoptionpicker clear">
            <?php echo _("Limit To"); ?>

            <select name="host" id="hostList" style="width: 150px;" class="form-control">
                <option value=""><?php echo _("Host"); ?>:</option>
                <?php
                $args = array('brevity' => 1, 'orderby' => 'host_name:a');
                $oxml = get_xml_host_objects($args);
                if ($oxml) {
                    foreach ($oxml->host as $hostobject) {
                        $name = strval($hostobject->host_name);
                        echo "<option value='" . $name . "' " . is_selected($host, $name) . ">$name</option>\n";
                    }
                }
                ?>
            </select>

            <!--
            <select name="hostgroup" id="hostgroupList" style="width: 150px;" class="form-control">
                <option value=""><?php echo _("Hostgroup"); ?>:</option>
                <?php
                $args = array('orderby' => 'hostgroup_name:a');
                $oxml = get_xml_hostgroup_objects($args);
                if ($oxml) {
                    foreach ($oxml->hostgroup as $hg) {
                        $name = strval($hg->hostgroup_name);
                        echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
                    }
                }
                ?>
            </select>
            <select name="servicegroup" id="servicegroupList" style="width: 150px;" class="form-control">
                <option value=""><?php echo _("Servicegroup"); ?>:</option>
                <?php
                $args = array('orderby' => 'servicegroup_name:a');
                $oxml = get_xml_servicegroup_objects($args);
                if ($oxml) {
                    foreach ($oxml->servicegroup as $sg) {
                        $name = strval($sg->servicegroup_name);
                        echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
                    }
                }
                ?>
            </select>
            -->
        
            <span style="margin-left: 10px;">
                <?php echo _("States"); ?>
                <select id="statetypeDropdown" name="statetype" class="form-control">
                    <option value="soft" <?php echo is_selected("soft", $statetype); ?>><?php echo _("Soft"); ?></option>
                    <option value="hard" <?php echo is_selected("hard", $statetype); ?>><?php echo _("Hard"); ?></option>
                    <option value="both" <?php echo is_selected("both", $statetype); ?>><?php echo _("Both"); ?></option>
                </select>
            </span>

            <button type="button" id="run" class='btn btn-sm btn-primary' name='reporttimesubmitbutton'><?php echo _("Run"); ?></button>

        </div>
    </div>
</form>

<div id="report"></div>

<?php
    do_page_end(true);
}


function get_alertstream_report()
{
    // Get values passed in GET/POST request
    $reportperiod = grab_request_var("reportperiod", "last24hours");
    $startdate = grab_request_var("startdate", "");
    $enddate = grab_request_var("enddate", "");
    $search = grab_request_var("search", "");
    $host = grab_request_var("host", "");
    $service = grab_request_var("service", ""); 
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $statetype = grab_request_var("statetype", "both");
    $hostservice = grab_request_var("hostservice", "");

    $width = grab_request_var("width", 750);
    $height = grab_request_var("height", 425);

    // Do not do any processing unless we have default report running enabled
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);

    // We search for hosts, so clear host if search is present
    if (!empty($search)) {
        $host = $search;
        $service = "";
    }

    // Determine start/end times based on period
    get_times_from_report_timeperiod($reportperiod, $starttime, $endtime, $startdate, $enddate);

    // Makes sure user has appropriate license level
    licensed_feature_check();
?>

<h1><?php echo _("Alert Stream"); ?></h1>

<?php if (!empty($service)) { ?>

<div class="servicestatusdetailheader">
    <div class="serviceimage">
        <?php show_object_icon($host, $service, true); ?>
    </div>
    <div class="servicetitle">
        <div class="servicename"><a href="<?php echo get_service_status_detail_link($host, $service); ?>"><?php echo encode_form_val($service); ?></a></div>
        <div class="hostname"><a href="<?php echo get_host_status_detail_link($host); ?>"><?php echo encode_form_val($host); ?></a></div>
    </div>
</div>

<?php } else if (!empty($host)) { ?>

<div class="hoststatusdetailheader">
    <div class="hostimage">
        <?php show_object_icon($host, "", true); ?>
    </div>
        <div class="hosttitle">
        <div class="hostname"><a href="<?php echo get_host_status_detail_link($host); ?>"><?php echo encode_form_val($host); ?></a></div>
    </div>
</div>
<?php } ?>

<p class="report-covers">
    <?php echo _("Report covers from"); ?>: <b><?php echo get_datetime_string($starttime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b>
    <?php echo _("to"); ?> <b><?php echo get_datetime_string($endtime, DT_SHORT_DATE_TIME, DF_AUTO, "null"); ?></b>
    <?php if (!empty($search)) { echo "<br>Showing results for '<b><i>" . encode_form_val($search) . "</i></b>'"; } ?>
</p>

<p>
<?php echo _("The alert stream provides a visual representation of host and service alerts over time."); ?> 
<?php echo _("Clicking on a host name will cause the graph to drill down to show service alerts for that particular host."); ?>
</p>

<style>

#d3 { width: 960px; height: 500px; }

.axis path,
.axis line {
    fill: none;
    stroke: black;
    shape-rendering: crispEdges;
}

line.sharp {
  shape-rendering: crispEdges;
  stroke: #ccc;
}

.axis text {
    font-family: sans-serif;
    font-size: 11px;
}

</style>

<div id="d3"></div>

<?php

$statesql = "";
if ($statetype != 'both') {
    if ($statetype == 'soft') {
        $statesql = "AND state_type = 0";
    } else if ($statetype == 'hard') {
        $statesql = "AND state_type = 1";
    }
}

$host_sql = "";
$service_sql = "";
$service_sort_sql = "";
if (!empty($host)) {
    $host_sql = "AND name1 = '".escape_sql_param($host, DB_NDOUTILS)."'";
    $service_sql = " , name2 AS service_description";
    $service_sort_sql = ",service_description";
}

$gdata = array();

if ($reportperiod == 'last24hours') {
    $maxitems = 24;
    $gdata = array();

    // Create the x axis
    $ts = strtotime("-24 hours");
    $xaxis = array();
    for ($i = 0; $i < 24; $i++) {
        $ts += 3600;
        $xaxis[] = date('m-d-Y H:i', $ts);
    }

    // Grab the last 24 hours of state history
    $sql = "SELECT HOUR(state_time) AS h, COUNT(*) AS num, name1 AS host_name ".$service_sql."
FROM nagios.nagios_statehistory AS sh LEFT JOIN nagios.nagios_objects AS o ON sh.object_id = o.object_id
WHERE state_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) ".$statesql." ".$host_sql."
GROUP BY host_name".$service_sort_sql.",h;";
    $res = exec_sql_query(DB_NDOUTILS, $sql);
    $result = $res->getArray();

    if (empty($host)) {
        $data = array();
        foreach ($result as $x) {
            if (!array_key_exists($x['host_name'], $data)) { $data[$x['host_name']] = array(); }
            $data[$x['host_name']][$x['h']] = $x['num'];
        }
    } else {
        $data = array();
        foreach ($result as $x) {
            if (!array_key_exists($x['service_description'], $data)) { $data[$x['service_description']] = array(); }
            $data[$x['service_description']][$x['h']] = $x['num'];
        }
    }

    //print "<pre>";
    //print_r($hostdata);
    //print "</pre>";

    // Loop through and create the array
    $x = 0;
    foreach ($data as $obj => $d) {
        $gdata[$x]['data'] = array();
        $gdata[$x]['key'] = $obj;
        $hr = date('G', time());
        for ($i = 0; $i < 24; $i++) {
            $amount = 0;
            if (!empty($d[$hr])) { $amount = $d[$hr]; }
            $gdata[$x]['data'][$i]['y'] = intval($amount);
            $hr--;
            if ($hr == -1) {
                $hr = 23;
            }
        }
        $gdata[$x]['data'] = array_reverse($gdata[$x]['data']);
        $x++;
    }
}

if (!empty($gdata)) {
?>

<script>

    var v = <?php echo json_encode($gdata); ?>;
    var xaxis_labels = <?php echo json_encode($xaxis); ?>

    var n = <?php echo count($gdata); ?>, // number of layers
        m = <?php echo $maxitems; ?>, // number of samples per layer
        stack = d3.layout.stack()
                  .offset("silhouette")
                  .values(function(d) { return d.values; })
                  .x(function(d) { return d.date; })
                  .y(function(d) { return d.value; }),
        color = '';

    var format = d3.time.format("%m-%d-%Y %H:%M");

    var width = 1000,
        height = 450;

    var x = d3.time.scale()
        .range([40, width-40]);

    var y = d3.scale.linear()
        .range([height-55, 30]);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient('bottom')
        .ticks(d3.time.hours);

    var yAxis = d3.svg.axis()
        .scale(y)
        .ticks(0)
        .orient('left');

    var color = d3.scale.linear()
        .range(["#8fb6ff", "#164499"]);

    var area = d3.svg.area()
        .interpolate("basis")
        .x(function(d) { return x(d.date); })
        .y0(function(d) { return y(d.y0); })
        .y1(function(d) { return y(d.y0 + d.y); });

    var svg = d3.select("#d3").append("svg")
        .attr("width", width)
        .attr("height", height);

    var tooltip = d3.select('body')
        .append('div')
        .style('position', 'absolute')
        .style('z-index', '10')
        .style('visibility', 'hidden')
        .style('color', '#FFF')
        .style('background-color', '#333')
        .style('padding', '3px 6px')
        .style('border-radius', '3px')
        .style('opacity', '0.9');

    var data = [];
    v.forEach(function(o, i) {
        data[i] = new Object;
        var c = v[i];
        data[i].values = xaxis_labels.map(function(d, i2) {
            var o = new Object;
            o.date = format.parse(xaxis_labels[i2]);
            o.value = c.data[i2].y;
            return o;
        });
    });

    var layers = stack(data);

    var newarray = [];
    data.forEach(function(o, i) {
        newarray = newarray.concat(o.values);
    });

    x.domain(d3.extent(data[0].values, function(d) { return d.date; }));
    y.domain([0, d3.max(newarray, function(d) { return d.y0 + d.y; })]);

    svg.selectAll(".layer")
        .data(layers)
        .enter().append("path")
        .attr("class", "layer")
        .attr("d", function(d) { return area(d.values); })
        .on('click', function(d, i) {
            if (<?php if (empty($host)) { echo "true"; } else { echo "false"; } ?>) {
                window.location.href = "?host=" + v[i].key + "&state_type=<?php echo urlencode($statetype); ?>";
            } else {
                if (<?php if (!empty($service)) { echo "true"; } else { echo "false"; } ?>) {
                    window.location.href = "<?php echo get_base_url(); ?>includes/components/xicore/status.php?show=servicedetail&host=<?php echo urlencode($host); ?>&service=" + v[i].key;
                } else {
                    window.location.href = "<?php echo get_base_url(); ?>includes/components/xicore/status.php?show=hostdetail&host=<?php echo urlencode($host); ?>";
                }
            }
        })
        .on('mouseover', function(d, i) {
            if (v[i].key == '') { tt = 'HOST'; } else { tt = v[i].key; }
            tooltip.text(tt)
                   .style('visibility', 'visible');
            color = d3.select(this).style('fill');
            d3.select(this).style('opacity', '0.80')
                           .style('cursor', 'pointer');
        })
        .on('mousemove', function(d) {
            tooltip.style("top", (event.pageY-20)+"px").style("left",(event.pageX+10)+"px");
        })
        .on('mouseout', function(d) {
            tooltip.style('visibility', 'hidden');
            d3.select(this).style('opacity', '1.0');
        })
        .style("fill", function() { return color(Math.random()); });

    svg.append('g')
        .attr('class', 'x axis')
        .attr('transform', 'translate(0,'+(height-55)+')')
        .call(xAxis);

    svg.selectAll('.x.axis text')
        .attr('transform', function(d) {
            return 'translate('+this.getBBox().height*-1+','+this.getBBox().height*2+')rotate(-90)';
        });

    svg.append('g')
        .attr('class', 'axis')
        .attr('transform', 'translate(40, 0)')
        .call(yAxis);

    svg.append('text')
        .attr('text-anchor', 'middle')
        .attr('transform', 'translate(20, '+height/2+')rotate(-90)')
        .text('<?php echo _("Alerts"); ?>');

</script>

<?php
    } else {
?>
<script type="text/javascript">
$(document).ready(function() {
    $('#d3').html('<div class="nodata"><b><?php echo _("No Data"); ?>:</b> <?php echo _("No alerts in last 24 hours"); ?></div>');
    $('.nodata').position({ my: 'center', at: 'center', of: '#d3' });
});
</script>
<style>
#d3 { background-color: #EEE; clear: both; }
.nodata { position: absolute; font-size: 16px; }
</style>
<?php
    }
}
