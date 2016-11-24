<?php
// Availability REPORT DASHLET
//
// Copyright (c) 2010 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: availability.inc.php 2014-02-20 lgroschen $

include_once(dirname(__FILE__) . '/../dashlethelper.inc.php');

// respect the -dashboard- name
$availability_report_name = "availability";

// register a dashlet
$args = array();
$args[DASHLET_NAME] = "availability";
$args[DASHLET_TITLE] = "Availability Report";
$args[DASHLET_FUNCTION] = "availability_dashlet_func";
$args[DASHLET_DESCRIPTION] = "Displays Availability Report Graph.";
$args[DASHLET_WIDTH] = "350";
$args[DASHLET_HEIGHT] = "450";
$args[DASHLET_INBOARD_CLASS] = "availability_report_inboard";
$args[DASHLET_OUTBOARD_CLASS] = "availability_report_outboard";
$args[DASHLET_CLASS] = "availability_report";
$args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
$args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2015 Nagios Enterprises. All rights reserved.";
$args[DASHLET_HOMEPAGE] = "https://www.nagios.com";
$args[DASHLET_SHOWASAVAILABLE] = false;
register_dashlet($args[DASHLET_NAME], $args);

function availability_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{
    $output = "";
    $imgbase = get_base_url() . "";

    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $divId = uniqid();
            $url = "reports/availability.php";

            $url .= "?mode=getchart&dashify=1&hideoptions=1";

            // Check to make sure it's not using static hardcoded IPs
            if (strpos($url, "://") === FALSE) {
                $url = get_base_url() . $url;
            }

            // Availability args
            $host = grab_array_var($args, 'host', '');
            $service = grab_array_var($args, 'service', '');
            $hostgroup = grab_array_var($args, 'hostgroup', '');
            $servicegroup = grab_array_var($args, 'servicegroup', '');
            $dashtype = grab_array_var($args, 'dashtype', '');
            $host_availability = grab_array_var($args, 'host_availability', '');
            $host_up = grab_array_var($args, 'host_up', '');
            $host_down = grab_array_var($args, 'host_down', '');
            $host_unreachable = grab_array_var($args, 'host_unreachable', '');
            $title = grab_array_var($args, 'title', '');
            $subtitle = grab_array_var($args, 'subtitle', '');
            $data = grab_array_var($args, 'data', '');
            $legend = grab_array_var($args, 'legend', '');
            $reportperiod = grab_array_var($args, 'reportperiod', 'last24hours');
            $startdate = grab_array_var($args, 'startdate', '');
            $enddate = grab_array_var($args, 'enddate', '');
            $timeperiod = grab_array_var($args, 'timeperiod', '');
            $colors = grab_array_var($args, 'colors', '');

            // Advanced Availability args
            $assumeinitialstates = grab_array_var($args, 'assumeinitialstates', "yes");
            $assumestateretention = grab_array_var($args, 'assumestateretention', "yes");
            $assumestatesduringdowntime = grab_array_var($args, 'assumestatesduringdowntime', "yes");
            $includesoftstates = grab_array_var($args, 'includesoftstates', "no");
            $assumedhoststate = grab_array_var($args, 'assumedhoststate', 3);
            $assumedservicestate  = grab_array_var($args, 'assumedservicestate', 6);
            $dont_count_downtime = grab_array_var($args, 'dont_count_downtime', 0);
            $dont_count_warning = grab_array_var($args, 'dont_count_warning', 0);
            $dont_count_unknown = grab_array_var($args, 'dont_count_unknown', 0);
            $showonlygraphs = grab_array_var($args, 'showonlygraphs', '');

            foreach ($args as $key => $val) {
                $url .= "&" . $key . "=" .  urlencode($val);
            }

            if ($dashtype == "hostdata") {
                $graph_script = ' 
                    var api_url = "' . $url . '";

                    $.getJSON(api_url, function (chartdata) {

                        Highcharts.setOptions({
                            colors: ["#b2ff5f", "#FF795F", "#FFC45F"]
                        });

                        formatted_data = new Array();
                        $.each(chartdata.data, function (k, v) {
                            if (k == 0) {
                                formatted_data.push(["Up", parseFloat(v)]);
                            } else if (k == 1) {
                                formatted_data.push(["Down", parseFloat(v)]);
                            } else if (k == 2) {
                                formatted_data.push(["Unreachable", parseFloat(v)]);
                            }
                        });

                        var host_availability_chart_' . $divId . ';
                        var options = {
                            chart: {
                                renderTo: "host_availability_chart_' . $divId . '",
                                width: chartdata.width,
                                height: chartdata.height
                            },
                            credits: {
                                enabled: false
                            },
                            title: {
                                text: chartdata.graph_title
                            },
                            subtitle: {
                                text: "' . $subtitle . '"
                            },
                            plotOptions: {
                                pie: {
                                    size: 160,
                                    enableMouseTracking: false,
                                    animation: false,
                                    dataLabels: {
                                        enabled: true,
                                        color: "#000",
                                        connectorColor: "#000000",
                                        format: "{point.name} {point.percentage:.2f}%"
                                    }
                                }
                            },
                            series: [
                                {
                                    type: "pie",
                                    data: formatted_data
                                }
                            ]
                        }
                        host_availability_chart_' . $divId . ' = new Highcharts.Chart(options);
                    });';
                $container_div = '<div id="host_availability_chart_' . $divId . '" style="display: inline-block; margin-right: 10px;"></div>';
            } else if ($dashtype == "servicedata") {
                $graph_script = '
                    var api_url = "' . $url . '";

                    $.getJSON(api_url, function (chartdata) {

                        Highcharts.setOptions({
                            colors: ["#b2ff5f", "#FEFF5F", "#FFC45F", "#FF795F"]
                        });

                        formatted_data = new Array();
                        $.each(chartdata.data, function (k, v) {
                            if (k == 0) {
                                formatted_data.push(["Ok", parseFloat(v)]);
                            } else if (k == 1) {
                                formatted_data.push(["Warning", parseFloat(v)]);
                            } else if (k == 2) {
                                formatted_data.push(["Unknown", parseFloat(v)]);
                            } else if (k == 3) {
                                formatted_data.push(["Critical", parseFloat(v)]);
                            }
                        });

                        var service_availability_chart_' . $divId . ';
                        var options = {
                            chart: {
                                renderTo: "service_availability_chart_' . $divId . '",
                                width: chartdata.width,
                                height: chartdata.height
                            },
                            credits: {
                                enabled: false
                            },
                            title: {
                                text: chartdata.graph_title
                            },
                            subtitle: {
                                text: "' . $subtitle . '"
                            },
                            plotOptions: {
                                pie: {
                                    size: 160,
                                    enableMouseTracking: false,
                                    animation: false,
                                    dataLabels: {
                                        enabled: true,
                                        color: "#000",
                                        connectorColor: "#000000",
                                        format: "{point.name} {point.percentage:.2f}%"
                                    }
                                }
                            },
                            series: [
                                {
                                    type: "pie",
                                    data: formatted_data
                                }
                            ]
                        }
                        service_availability_chart_' . $divId . ' = new Highcharts.Chart(options);
                    });';
                $container_div = '<div id="service_availability_chart_' . $divId . '" style="display: inline-block; margin-right: 10px;"></div>';
            }

            $output .= '
            <div class="availability_dashlet" style="display: none;" id="' . $divId . '">

            <div class="infotable_title">Availability Report</div>
            ' . get_throbber_html() . '

            </div><!--ahost_status_summary_dashlet-->

            ' . $container_div . '

            <!-- end  dashlet -->
            <script type="text/javascript">
            $(document).ready(function(){
                ' . $graph_script . '

                get_' . $divId . '_content();

                // Re-build the content when we resize
                $("#' . $divId . '").closest(".ui-resizable").on("resizestop", function(e, ui) {
                    var height = ui.size.height - 17;
                    var width = ui.size.width;
                    get_' . $divId . '_content(height, width);
                });

                // Auto-update every x amount of time
                setInterval(get_' . $divId . '_content, 300*1000);
            });

            function get_' . $divId . '_content(height, width)
            {
                if (height == undefined) { var height = $("#' . $divId . '").parent().height() - 17; }
                if (width == undefined) { var width = $("#' . $divId . '").parent().width(); }
                    $("#' . $divId . '").load("' . $url . '");
            }
            </script>
            ';

            break;

        case DASHLET_MODE_PREVIEW:
            break;
    }

    return $output;
}
?>