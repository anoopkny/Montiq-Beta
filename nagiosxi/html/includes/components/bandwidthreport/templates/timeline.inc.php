<?php
// timeline.inc.php
// - Template file for highcharts timeline graph     

// Pass all args into template and return JSON code 
/**
 * @param $args
 *
 * @return string
 */
function fetch_timeline($args) {
    // Allow for height & width to be passed for resizing
    $height = grab_array_var($args, 'height', 250);
    $width = grab_array_var($args, 'width', 500);
    $hs_url = grab_array_var($args, "hs_url");
    $render_mode = grab_array_var($args, "render_mode", "");
    $random = uniqid();

    // If no hovering
    $no_hover = "";
    $no_hover_fill = "";
    if ($render_mode == "pdf") {
        $no_hover = "enableMouseTracking: false,";
        $no_hover_fill = "fillColor: '#FFFFFF'";
    }

    // Check width
    if (!empty($width)) {
        $width = "width: {$width},";
    }

    $tickPixelInterval = "";
    // if true show each day of the month
    if ($args['tickPixelInterval'] == 1) {
        $tickPixelInterval = "tickPixelInterval: 50,
                              dateTimeLabelFormats: {
                                day: '%d'
                              },";
    }

    // Create readable graph
    $args['title'] = str_replace("_", " ", $args['title']); // Replaces underscores with spaces
    $units = explode(" ", $args['UOM']);
    if (count($units) == 3) {
        if ($units[0] == $units[1] && $units[2] == "") {
            $args['UOM'] = $units[0]; // Fix double units of measurement
        }
    }

    // Special export settings for local exporting
    $filename = str_replace(array("  ", " ", ":", "__", "_-_"),
                            array(" ", "_", "-", "_", "-"),
                            strtolower($args['title']));
    $filename = trim($filename, "_");
    $exporting = "";
    if (get_option('highcharts_local_export_server', 1)) {
        $exporting = "exporting: {
            url: '".get_base_url()."includes/components/highcharts/exporting-server/index.php',
            sourceHeight: $('#{$args['container']}').height(),
            sourceWidth: $('#{$args['container']}').width(),
            filename: '{$filename}',
            chartOptions: { chart: { spacing: [15, 15, 15, 15] } }
         },";
    }

    // Begin heredoc string syntax 
    $graph = <<<GRAPH
        
        var COUNT_{$random} = {$args['count']}; //total rrd entries fetched 
        var UOM_{$random} = '{$args['UOM']}';
        var START_{$random} = {$args['start']};   //Date.UTC(2011, 1, 21) ->added below for correct datatype
        var TITLE_{$random} = '{$args['title']}';
        var CONTAINER_{$random} = '{$args['container']}';
    
        //reset default colors 
        Highcharts.setOptions({
            colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4'] 
        }); 

        //data points added below for correct datatype interpretation               
        //use browser's timezone offset for date        
        Highcharts.setOptions({
            global: { useUTC: false },
        });
                
            var chart;
            $(document).ready(function() {
                chart = new Highcharts.Chart({
                    {$exporting}
                    chart: {
                        renderTo: CONTAINER_{$random},
                        zoomType: 'x',
                        spacingRight: 20,
                        height: {$height},
                        {$width}
                        animation: false,
                        borderColor: 'gray',
                        borderRadius: 4,
                        borderWidth: 1,
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        //insert host/service name here 
                        text: TITLE_{$random},
                        style: {
                            fontSize: "16px",
                            fontWeight: "bold"
                        }
                    },
                    xAxis: {
                        {$tickPixelInterval}
                        type: 'datetime',
                        maxZoom: {$args['increment']}*1000,  //max zoom is 5 minutes 
                        title: {
                            text: null
                        }
                    },
                    yAxis: {
                        title: {
                            text: UOM_{$random}  // unit of measurement from perf data 
                        },
                        startOnTick: false,
                        showFirstLabel: false
                    },
                    tooltip: {
                        shared: true,
                        useHTML: true,
                        formatter: function() {
                            html = Highcharts.dateFormat("%A %b, %e - %l:%M %p", parseInt(this.x));
                            for (var i = 0; i < this.points.length; i++) {
                                html += '<br><span style="color:' + this.points[i].series.color + '">\u25CF</span> <b>' + this.points[i].series.name + '</b>: ' + Math.round(this.points[i].y * 1000) / 1000;
                            }
                            return html;
                        }                  
                    },
                    legend: {
                        enabled: true
                    },
                    plotOptions: {
                        area: {
                            {$no_hover}
                            lineWidth: 1,
                            marker: {
                                enabled: false,
                                states: {
                                    hover: {
                                        enabled: true,
                                        radius: 5
                                    }
                                }
                            },
                            shadow: false,
                            states: {
                                hover: {
                                    lineWidth: 1                        
                                }
                            },
                            fillOpacity: 0.5
                        }
                    },
GRAPH;
// End heredoc syntax

    if (!$args['nodata']) {
        $graph .= "series: [";

        // Loop for multiple data sets in perfdata 
        $series = array();
        for ($i = 0; $i < count($args['datastrings']); $i++) {

            $dtype = 'area';

            if ($args['names'][$i] == 'out')
                $dtype = 'line';

            $series[] = "
                    {
                        type: \"{$dtype}\",
                        name: \"{$args['names'][$i]}\",
                        pointInterval: {$args['increment']}*1000,
                        pointStart: {$args['start']},
                        data: [
                            " . implode(', ', $args['datastrings'][$i]) . "
                        ],
                        animation: false,
                        {$no_hover_fill}
                    }";
        }

        $graph .= implode(',', $series);
        $graph .= "]";
    }

    // End the highcharts graph syntax
    $graph .= " }, function(chart) {
                    chart.title.addClass('chartbutton');
                    chart.title.on('click', function() {
                        window.location = '" . $hs_url . "';
                    })
            });             
        });";

    return $graph;
}