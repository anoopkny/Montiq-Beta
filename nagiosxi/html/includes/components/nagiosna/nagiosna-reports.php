<?php
// NAGIOS NETWORK ANALYZER REPORTS
//
// Copyright (c) 2014-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: $

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

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

// Route the request to the proper location
route_request();

function route_request()
{
    global $request;

    $mode = grab_request_var("mode", "");
    switch ($mode) {

        case "pdf":
            get_nna_reports_pdf();
            break;
        case "jpg":
            get_nna_reports_jpg();
            break;

        default:
            display_nna_reports();
            break;
    }
}

/*
 * Display the Reports
 *  - Grabs saved reports from the current NNA Server(s) selected in config settings and displays them
 */
function display_nna_reports()
{
    do_page_start(array("page_title" => _("Nagios Network Analyzer Reports")), true);

    // Check to make sure the user has NNA integration or not, display some info on
    // how to get integration if they don't already have Network Analyzer
    if (!has_nna_servers()) {
        display_nna_no_servers();
        do_page_end(true);
        exit();
    }

    $temp = nna_get_first_data();

    // Grab the passed values
    $hideoptions = grab_request_var("hideoptions", 0);
    $rid = grab_request_var("report", 1);
    $object_type = grab_request_var("object_type", "source");
    $object = grab_request_var("object", $temp['object']);
    $server = grab_request_var("nna_server", $temp['server']);
    $object_name = grab_request_var("object_name", $temp['object_name']);
    $useview = grab_request_var("useview", 0);
    $view_id = grab_request_var("view_id", "");
    $view_name = grab_request_var("view_name", "");
    $excluders = grab_request_var("excluders", array());
    $manual_run = grab_request_var("manual_run", 0);

    list($address, $secure, $api_key) = explode("|", $server);

    // If we have an object and rid and not disabled loading
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);
    if ($object > 0 && $rid > 0 && ($manual_run == 1 || $disable_report_auto_run == 0)) {

        // Create the api url
        if ($secure) {
            $sec = "s";
        } else {
            $sec = "";
        }
        $nna_url = "http" . $sec . "://" . $address . "/nagiosna/index.php";
        $api_url = $nna_url . "/api/";
        $report_viz_url = $api_url . "reports/reportviz?rid=" . $rid . "&token=" . $api_key;

        $url = $api_url . "reports/execute?q[rid]=" . $rid;
        if ($object_type == "source") {
            $url .= "&q[sid]=" . $object;
            $report_viz_url .= "&sid=" . $object;
        } else if ($object_type == "sourcegroup") {
            $url .= "&q[gid]=" . $object;
            $report_viz_url .= "&gid=" . $object;
        }

        // Check if view and add on
        if ($useview) {
            $url .= "&q[vid]=" . $view_id;
            $report_viz_url .= "&vid=" . $view_id;
        }

        // Get the report data
        $url .= "&token=" . $api_key;
        $json = file_get_contents($url);
        $report_data = json_decode($json);

        // Grab the actual report info
        $url = $api_url . "reports/read?q[rid]=" . $rid;
        $url .= "&token=" . $api_key;
        $json = file_get_contents($url);
        $report = json_decode($json);
        $report = $report[0];

    }

    // Hide all options if the users is generating a PDF
    if (!$hideoptions) {
        ?>

        <script type="text/javascript">
            var nna_server = "<?php echo $server; ?>";
            var report_id = <?php echo $rid; ?>;
            var object_type = "<?php echo $object_type; ?>";
            var object = <?php if (empty($object)) { echo '""'; } else { echo $object; } ?>;
            var object_name = "<?php echo $object_name; ?>";
            var view_id = "<?php echo $view_id; ?>";
            var show_excluders = <?php if (empty($excluders)) { echo 0; } else { echo 1; } ?>;

            $(document).ready(function () {

                if (nna_server == "") {
                    nna_server = $('#nna_server').val();
                }

                // Load all reports
                load_reports_from_server(nna_server);

                // Load default sources or sourcegroups
                if (object_type == "source") {
                    load_sources_from_server(nna_server);
                } else if (object_type == "sourcegroup") {
                    load_sourcegroups_from_server(nna_server);
                }
                $('input[name="object_type"][value="' + object_type + '"]').prop('checked', true);

                // Load reports on server change
                $('#nna_server').change(function () {
                    $('#use-view-container').hide();
                    load_reports_from_server($(this).val());
                    load_nna_objects();
                });

                // Save the view_id when changing views
                $('#views').change(function () {
                    view_id = $(this).val();
                    $('#view_name').val($(this).text());
                });

                // Show excluders
                $('#show-excluders').click(function () {
                    if (show_excluders) {
                        $('#excluders').hide();
                        show_excluders = 0;
                        $('#show-excluders').html("[+] <?php echo _('Show Column Excluders'); ?>");
                    } else {
                        $('#excluders').show();
                        show_excluders = 1;
                        $('#show-excluders').html("[-] <?php echo _('Hide Column Excluders'); ?>");
                    }
                });

                // Load sources or sourcegroups on change
                $('input[name="object_type"]').change(function () {
                    load_nna_objects();
                });

                $('#object').change(function () {
                    var obj = $(this).find("option:selected");
                    var server = $('#nna_server').val().split("|");
                    var api_url = get_nna_api_url(server[0], server[1]);
                    load_sources_views(api_url, server[2], obj.val());
                    $('#object_name').val(obj.text());
                });

            });

            function load_reports_from_server(server) {
                var server = server.split("|");
                var api_url = get_nna_api_url(server[0], server[1]);
                $('#error').hide();

                $.get(api_url + "reports/read", {token: server[2]}, function (data) {

                    $('#report').html("");
                    $.each(data, function (k, v) {
                        var selected = "";
                        if (v.rid == report_id) {
                            selected = "selected";
                        }
                        $('#report').append('<option value="' + v.rid + '" ' + selected + '>' + v.name + '</option>');
                    });

                }, 'json')
                    .fail(function (data) {
                        var error = '<?php echo _("Failed connect to API. Check your connection to the host (using SSL?) and make sure your Nagios Network Analyzer is version 2014R1.5 or higher."); ?>';
                        if (data.status == 404) {
                            error = '<?php echo _("404 - API not found. The address may be wrong."); ?>';
                        }
                        $("#error").html(error).show();
                    });

            }

            function load_sources_from_server(server) {
                var server = server.split("|");
                var api_url = get_nna_api_url(server[0], server[1]);

                $.get(api_url + "sources/read", {token: server[2]}, function (data) {

                    $('#object').html("");
                    var selected_id = 0;
                    $.each(data, function (k, v) {
                        var selected = "";
                        if (object_type == "source" && object == v.sid) {
                            selected = "selected";
                            selected_id = v.sid;
                        }
                        $('#object').append('<option value="' + v.sid + '" ' + selected + '>' + v.name + '</option>');
                    });

                    // Make sure the actual name is saved and sent
                    $('#object_name').val($('#object option:selected').text());

                    // Grab the source and create a "Use View checkbox if it has any associated views"
                    load_sources_views(api_url, server[2], selected_id);

                }, 'json');
            }

            function load_sourcegroups_from_server(server) {
                var server = server.split("|");
                var api_url = get_nna_api_url(server[0], server[1]);

                $.get(api_url + "groups/read", {token: server[2]}, function (data) {

                    $('#object').html("");
                    $.each(data, function (k, v) {
                        var selected = "";
                        if (object_type == "sourcegroup" && object == v.gid) {
                            selected = "selected";
                        }
                        $('#object').append('<option value="' + v.gid + '" ' + selected + '>' + v.name + '</option>');
                    });

                    // Make sure the actual name is saved and sent
                    $('#object_name').val($('#object option:selected').text());

                }, 'json');
            }

            function load_sources_views(api_url, token, sid) {
                $.get(api_url + "views/get_views", {token: token, 'q[sid]': sid}, function (data) {

                    if (!data.error) {
                        var views = data;
                        if (views.length > 0) {
                            $('#use-view-container').show();
                            $('#views').html('');
                            $.each(views, function (k, v) {

                                var selected = "";
                                if (v.vid == view_id) {
                                    selected = " selected";
                                }

                                $('#views').append('<option value="' + v.vid + '"' + selected + '>' + v.name + '</option>');
                            });
                        } else {
                            $('#use-view-container').hide();
                        }

                        // Make sure the actual name is saved and sent
                        $('#view_name').val($('#views option:selected').text());
                    }

                }, 'json');
            }

            function get_nna_api_url(address, https) {
                var secure = "";
                if (https == "1") {
                    secure = "s";
                }

                return "http" + secure + "://" + address + "/nagiosna/index.php/api/";
            }

            function load_nna_objects() {
                var checked_type = $('input[name="object_type"]:checked').val();
                if (checked_type == "source") {
                    load_sources_from_server($('#nna_server').val());
                } else if (checked_type == "sourcegroup") {
                    $('#use-view-container').hide();
                    load_sourcegroups_from_server($('#nna_server').val());
                }
            }
        </script>

        <div class="reportexportlinks">
            <?php
            $pdf_url = $_SERVER['REQUEST_URI'];
            echo get_add_myreport_html("Nagios Network Analyzer Query", $pdf_url, array());
            if (strpos($pdf_url, "?") === false) {
                $pdf_fixed_url = $pdf_url . "?mode=pdf";
                $jpg_fixed_url = $pdf_url . "?mode=jpg";
            } else {
                $pdf_fixed_url = $pdf_url . "&mode=pdf";
                $jpg_fixed_url = $pdf_url . "&mode=jpg";
            }
            ?>
            <!--//-->
            <a href="<?php echo $pdf_fixed_url; ?>" alt="<?php echo _("Download As PDF"); ?>"
               title="<?php echo _("Download As PDF"); ?>"><img src="<?php echo theme_image("pdf.png"); ?>"></a>
            <a href="<?php echo $jpg_fixed_url; ?>" alt="<?php echo _("Download As JPG"); ?>"
               title="<?php echo _("Download As JPG"); ?>"><img src="<?php echo theme_image("jpg.png"); ?>"></a>
        </div>

        <h1><?php echo _("Network Report"); ?></h1>

        <style>
            .excluders label, .excluders span {
                margin-right: 10px;
            }
        </style>

        <form method="get">
            <div>
                <?php echo _("NNA Server:"); ?> <?php display_nna_servers(false, $server); // Get all the NNA servers available in XI ?>
                <span style="margin-left: 10px;">
                    <label>
                        <input type="radio" value="source" name="object_type" checked> <?php echo _("Source"); ?>
                    </label>
                    <label style="margin-left: 10px;">
                        <input type="radio" value="sourcegroup" name="object_type"> <?php echo _("Sourcegroup"); ?>
                    </label>
                </span>
                <span style="margin-left: 10px;">
                    <select id="object" name="object" class="form-control"></select>
                </span>
                <span style="margin-left: 10px; <?php if (!$useview) { echo "display: none;"; } ?>" id="use-view-container">
                    <label><input type="checkbox" name="useview" value="1" <?php if ($useview) { echo "checked"; } ?>> <?php echo _("Use a View"); ?>: </label>
                    <select id="views" name="view_id" class="form-control"></select>
                    <input type="hidden" id="view_name" name="view_name" value="<?php echo $view_name; ?>">
                </span>
                <span style="margin-left: 10px;">
                    <?php if (empty($excluders)) { ?>
                        <a style="cursor: pointer;" id="show-excluders">[+] <?php echo _("Show Column Excluders"); ?></a>
                    <?php } else { ?>
                        <a style="cursor: pointer;" id="show-excluders">[-] <?php echo _("Hide Column Excluders"); ?></a>
                    <?php } ?>
                </span>
            </div>
            <div style="margin-top: 10px; <?php if (empty($excluders)) {
                echo "display: none;";
            } ?>" id="excluders" class="excluders">
                <table>
                    <tr>
                        <td>
                            <span><?php echo _("Exclude the checked columns from the report table"); ?>:</span>
                        </td>
                        <td>
                            <label>
                                <input type="checkbox" name="excluders[]" value="start" <?php if (in_array("start", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Start Date"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="end" <?php if (in_array("end", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("End Date"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="duration" <?php if (in_array("duration", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Duration"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="protocol" <?php if (in_array("protocol", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Protocol"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="srcip" <?php if (in_array("srcip", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Source IP"); ?>
                                </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="dstip" <?php if (in_array("dstip", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Destination IP"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="srcport" <?php if (in_array("srcport", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Source Port"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="dstport" <?php if (in_array("dstport", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Destination Port"); ?>
                            </label>
                        </td>
                    <tr>
                    <tr>
                        <td><?php echo _("(Useful if you can't fit the data on a pdf)"); ?></td>
                        <td>
                            <label>
                                <input type="checkbox" name="excluders[]" value="flows" <?php if (in_array("flows", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Flows"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="flowspercent" <?php if (in_array("flowspercent", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Flows %"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="packets" <?php if (in_array("packets", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Packets"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="packetspercent" <?php if (in_array("packetspercent", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Packets %"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="bytes" <?php if (in_array("bytes", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Bytes"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="bytespercent" <?php if (in_array("bytespercent", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Bytes %"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="pps" <?php if (in_array("pps", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Packets/Sec"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="pbs" <?php if (in_array("pbs", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Bits/Sec"); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="excluders[]" value="bpp" <?php if (in_array("bpp", $excluders)) { echo "checked"; } ?>>
                                <?php echo _("Bits/Packet"); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="margin-top: 10px;">
                <span><?php echo _("Report to run:"); ?> <select id="report" name="report" class="form-control"></select></span>
                <span style="margin-left: 10px;"><button class="btn btn-sm btn-primary" style="vertical-align: top;" type="submit"><?php echo _("Run Report"); ?></button></span>
            </div>
            <!-- Set a variable to let us know it's okay to run this -->
            <input type="hidden" name="manual_run" value="1">
            <input type="hidden" value="" name="object_name" id="object_name">
        </form>

        <div class="error" id="error" style="display:none;"></div>

    <?php

    } // End: Hideoptions

    if ($report && $report_data) {

        $dn = false;

        // Strip record header (the top th part) from records
        $records = $report_data->records;
        $head = array_slice($records, 0, 1);
        $head = $head[0];
        $records = array_slice($records, 1);
        $summary = $report_data->summary;

        if ($records > 0) {
            $record = $records[0];
            if (array_key_exists('srcdn', $record) || array_key_exists('dstdn', $record)) {
                $dn = true;
            }
        }

        //print_r($summary);
        $order = $report->toporder;

        // Let's display the graphs?
        $vizdata = json_decode(file_get_contents($report_viz_url));

        // Get aggs and aggt
        foreach ($vizdata->datasets as $k => $v) {
            $aggs[] = $k;
            $aggt[] = $v;
        }
        ?>

        <script>
        var aggs = <?php echo json_encode($aggs); ?>;
        var aggt = <?php echo json_encode($aggt); ?>;
        var g_title = '<?php echo $report->name; ?>';

        $(document).ready(function () {
            make_pie();

            $.each(aggs, function (i, d) {
                make_chord(d, 600, 900, aggt[i]);
            });

            $('.fullscreen-button').click(function () {
                resize_to_fullscreen(this);
            });

            $(window).resize(function() {
                $('.well').width($(window).width()-60);
                $('.well').height($(window).height()-60);
            });

        });

        function resize_to_fullscreen(node) {

            var div_id = $(node).attr('data');
            var w = $(window).width();
            var h = $(window).height();
            $(div_id).addClass('front-and-center').width(w - 60).height(h - 60);
            $(div_id).addClass('well').css({'z-index': 10000});

            switch (div_id) {
                case '#pie':
                    pie.setSize(w - 170, h - 180);
                    var opt = pie.series[0].options;
                    opt.dataLabels.enabled = true;
                    pie.series[0].update(opt);
                    break;
                <?php foreach($vizdata->datasets as $dataset => $garbage): ?>
                case '#<?php echo $dataset; ?>':

                    break;
                <?php endforeach ?>
                default:
                    break;
            }

            $(div_id).css('position', 'fixed')
                     .css('top', 10)
                     .css('left', 10);

            var title = $(node).parent().text();

            $(div_id).prepend('<span id="graph-title"><a id="make-normal-size" onclick="resize_to_normal(\'' + div_id + '\')" class="btn btn-sm btn-default"><i class="fa fa-compress"></i></a>&nbsp;' + title + '</span>');
        }

        function resize_to_normal(div_id) {
            $(div_id).css({top: 0, left: 0, 'z-index': 1, position: 'inherit'})
                .removeClass('front-and-center')
                .removeClass('well')
                .width('').height('')
                .children('span#graph-title').each(function (i, d) {
                    $(d).remove();
                });

            switch (div_id) {
                case '#pie':
                    pie.setSize($('#pie').width(), $('.thumbnails li').height() - 65);
                    pie.setTitle('');
                    var opt = pie.series[0].options;
                    opt.dataLabels.enabled = false;
                    pie.series[0].update(opt);
                    break;
                <?php foreach($vizdata->datasets as $dataset => $garbage): ?>
                case '#<?php echo $dataset; ?>':

                    break;
                <?php endforeach ?>
                default:
                    break;
            }
        }

        function make_pie() {
            var json = <?php echo $vizdata->json; ?>;

            $(function () {

                pie = new Highcharts.Chart({
                    chart: {
                        renderTo: 'pie',
                        backgroundColor: null,
                        plotBorderWidth: null,
                        plotShadow: false,
                        animation: false
                    },
                    credits: {
                        enabled: false
                    },
                    title: {
                        text: ' '
                    },
                    tooltip: {
                        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                    },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            animation: false,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: false,
                                style: {
                                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                                },
                                connectorColor: 'silver',
                                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                            }
                        }
                    },
                    series: json
                });
                pie.setTitle('');

                var n_width = $('#pie').width();
                if (n_width == 0) {
                    n_width = 200;
                }
                pie.setSize(n_width, $('#pie').height());
            });

            if (json[0].data[0].name == null) {
                $('#highcharts-0').html('<div class="text-center"><?php echo _("No Data"); ?></div>');
            }
        }

        function make_chord(agg2, h, w, title) {

            var r1 = h / 2 - 50,
                r0 = r1 - 80;

            var fill = d3.scale.category20c();

            var chord = d3.layout.chord()
                .padding(.04)
                .sortSubgroups(d3.descending)
                .sortChords(d3.descending);

            var arc = d3.svg.arc()
                .innerRadius(r0)
                .outerRadius(r0 + 20);

            var svg = d3.select("#" + agg2).append("svg:svg")
                .attr("viewBox", "0 0 " + w + " " + h)
                .attr("width", "100%")
                .attr("height", "100%")
                .append("svg:g")
                .attr("transform", "translate(" + w / 2 + "," + h / 2 + ")");
            window[agg2] = svg;

            d3.json('<?php echo $nna_url . $vizdata->apiurl . "?" . $vizdata->chordquery; ?>' + "&type=report&agg2=" + agg2, function (imports) {

                var indexByName = {},
                    nameByIndex = {},
                    matrix = imports.matrix,
                    labels = imports.names,
                    diff = imports.diff,
                    warning = imports.warning,
                    n = 0;

                self.names = [];

                // Compute a unique index for each package name.
                labels.forEach(function (d) {
                    if (!(d in indexByName)) {
                        nameByIndex[n] = d;
                        indexByName[d] = n++;
                        names.push(d);
                    }
                });

                chord.matrix(matrix);

                var g = svg.selectAll("g.group")
                    .data(chord.groups)
                    .enter().append("svg:g")
                    .attr("class", "group")
                    .on("mouseover", fade(.02))
                    .on("mouseout", fade(.80));

                g.append("svg:path")
                    .style("stroke", function (d) {
                        return fill(d.index);
                    })
                    .style("fill", function (d) {
                        return fill(d.index);
                    })
                    .attr("d", arc);

                g.append("svg:text")
                    .each(function (d) {
                        d.angle = (d.startAngle + d.endAngle) / 2;
                    })
                    .attr("dy", ".35em")
                    .attr("text-anchor", function (d) {
                        return d.angle > Math.PI ? "end" : null;
                    })
                    .attr("transform", function (d) {
                        return "rotate(" + (d.angle * 180 / Math.PI - 90) + ")"
                        + "translate(" + (r0 + 26) + ")"
                        + (d.angle > Math.PI ? "rotate(180)" : "");
                    })
                    .attr("class", function (d) {
                        return (diff[d.index]) ? 'black' : 'gray';
                    })
                    .text(function (d) {
                        return nameByIndex[d.index];
                    });

                svg.selectAll("path.chord")
                    .data(chord.chords)
                    .enter().append("svg:path")
                    .attr("class", "chord")
                    .style("stroke", function (d) {
                        return d3.rgb(fill(d.source.index)).darker();
                    })
                    .style("fill", function (d) {
                        return fill(d.source.index);
                    })
                    .attr("d", d3.svg.chord().radius(r0));

                $('#' + agg2).children('.reportviz-throbber').hide();

                svg.append('svg:title').text(title);

                if (warning) {
                    svg.append('text')
                        .attr('x', width / 2)
                        .attr('y', 0 - (margin.top / 2))
                        .attr('text-anchor', 'middle')
                        .style('font-size', '10px')
                        .text('Dataset was truncated due to size.');
                }

            });

            // Returns an event handler for fading a given chord group.
            function fade(opacity) {
                return function (d, i) {
                    svg.selectAll("path.chord")
                        .filter(function (d) {
                            return d.source.index != i && d.target.index != i;
                        })
                        .transition()
                        .style("stroke-opacity", opacity)
                        .style("fill-opacity", opacity);
                };
            }
        }
        </script>

        <div <?php if (!$hideoptions) {
            echo 'style="margin-top: 30px;"';
        } ?>>

            <h1><?php echo _("Report") . ": " . $report->name; ?></h1>

            <p>

            <div><?php echo ucfirst($object_type); ?>:
                <strong><?php echo $object_name; ?></strong> <?php if ($useview && $view_name) {
                    echo _('using view ') . "<strong>" . $view_name . "</strong>";
                } ?> on <?php echo $address; ?>
                (<?php echo _("Showing top") . " " . $report->top . " " . _("based on") . " " . nna_human_readable_header('val', $report) . " " . _("and ordered by") . " " . nna_human_readable_header($report->toporder); ?>
                )
            </div>
            <div style="margin-top: 5px;">
                <?php echo _("Timeframe") . ": " . nna_human_readable_timeframe($report); ?>
            </div>
            <?php
            if (!empty($report->rawquery)) {
                echo '<div style="margin-top: 5px;">' . _("Limiters") . ': ' . $report->rawquery . '</div>';
            }
            ?>
            </p>

            <div width='100%' style="margin-top: 20px;">
                <ul class='thumbnails' style='margin:0px auto;'>
                    <li style="width: 22%; margin-bottom: 0;">
                        <div class='thumbnail' style="<?php if ($hideoptions) {
                            echo "height: 200px;";
                        } else {
                            echo "height: 250px;";
                        } ?>">
                            <div id='pie' style="<?php if ($hideoptions) {
                                echo "height: 200px;";
                            } else {
                                echo "height: 250px;";
                            } ?>" class='chartrender'>
                            </div>
                        </div>
                        <h5><a class='btn btn-sm btn-default fullscreen-button' style="margin-right: 5px;" data='#pie'><i
                                    class='fa fa-expand'></i></a> <?php echo _("Pie Chart"); ?></h5>
                    </li>
                    <?php foreach ($vizdata->datasets as $dataset => $description): ?>
                        <li style="width: 22%; margin-bottom: 0;">
                            <div class='thumbnail' style="<?php if ($hideoptions) {
                                echo "height: 200px;";
                            } else {
                                echo "height: 250px;";
                            } ?>">
                                <div id='<?php echo $dataset; ?>' class='chartrender'>
                                    <div class='reportviz-throbber'>
                                        <div class='valign-throbber centerme'>
                                            <i class='fa fa-spinner fa-spin'></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <h5><a class='btn btn-sm btn-default fullscreen-button' style="margin-right: 5px;"
                                   data='#<?php echo $dataset; ?>'><i
                                        class='fa fa-expand'></i></a> <?php echo _("Chord Diagram") . " (" . $description . ")"; ?>
                            </h5>
                        </li>
                    <?php endforeach ?>
                </ul>
                <div style="clear:both;"></div>
            </div>

            <table <?php if (get_option('theme', 'xi2014') == "xi2014") {
                echo 'class="table table-striped table-bordered table-hover"';
            } else {
                echo 'class="standardtable"';
            } ?> style="margin-top: 15px;">
                <thead>
                <tr>
                    <?php
                    foreach ($head as $k => $h) {
                        if (!in_array($k, $excluders)) {
                            $highlighted = "";
                            if ($k == $report->toporder) {
                                $highlighted = 'style="background-color: #EEE;"';
                            }                
                            echo '<th ' . $highlighted . '>' . nna_human_readable_header($h, $report, $dn) . '</th>';
                        }
                    }
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php
                // Display a message if there is no data
                if (count($records) == 0) {
                    echo '<tr><td colspan="99">No data returned for this report.</td></tr>';
                } else {

                    // Display data
                    foreach ($records as $record) {
                        echo '<tr>';
                        foreach ($record as $k => $v) {
                            if ($k == "srcdn" || $k == "dstdn") { continue; }
                            if (!in_array($k, $excluders)) {

                                // Show a little bar with the amount of data sent
                                $display = '';
                                $make = false;
                                if ($k == "bytes") {
                                    $percent = round(($v / $summary->bytes) * 100, 2);
                                    $make = true;
                                } else if ($k == "flows") {
                                    $percent = round(($v / $summary->flows) * 100, 2);
                                    $make = true;
                                } else if ($k == "packets") {
                                    $percent = round(($v / $summary->packets) * 100, 2);
                                    $make = true;
                                }

                                // Make the little bar's html
                                if ($make) {
                                    $display = '<div style="background-color: #FFF; height: 2px; width: 100%;"><div style="background-color: #666; height: 2px; width: ' . $percent . '%;"></div></div>';
                                }

                                $highlighted = "";
                                if ($k == $report->toporder) {
                                    $highlighted = 'style="background-color: #EEE;"';
                                }

                                if ($dn) {
                                    if ($k == "srcip") {
                                        if (array_key_exists('srcdn', $record)) {
                                            $v = $record->srcdn;
                                        }
                                    } else if ($k == "dstip") {
                                        if (array_key_exists('dstdn', $record)) {
                                            $v = $record->dstdn;
                                        }
                                    }
                                }

                                echo '<td ' . $highlighted . '>' . nna_human_readable_value($v, $k) . $display .'</td>';
                            }
                        }
                        echo '</tr>';
                    }
                }
                ?>
                </tbody>
            </table>

        </div>

    <?php
    } // End Report

    do_page_end(true);
}

// End: Display reports (display_nna_reports)

// Function to do the pdf generating for this report
function get_nna_reports_pdf()
{
    global $cfg;

    // Grab the backend ticket and username
    $username = $_SESSION["username"];
    $backend_ticket = get_user_attr(0, "backend_ticket");

    // Assemble actual URL that will be gotten
    $uri = str_replace("mode=pdf", "hideoptions=1", $_SERVER["REQUEST_URI"]);
    $fullurl = "http://127.0.0.1{$uri}";
    $urlparts = parse_url($fullurl);
    $uri = str_replace("/nagiosxi", "", $urlparts['path']);
    $newurl = get_internal_url() . $uri;
    if (!empty($urlparts['query'])) {
        $newurl .= "?";
        $newurl .= $urlparts['query'];
    }

    // Add the username and ticket
    $newurl .= "&username=" . $username;
    $newurl .= "&ticket=" . $backend_ticket;

    // Add language to url
    $language = $_SESSION['language'];
    $newurl .= "&locale=" . $language;

    // Do page rendering

    $tmpfiles = array();
    $aurl = $newurl;

    $afile = "page.pdf";
    $fname = get_tmp_dir() . "/scheduledreport-" . $username . "-" . time() . "-" . $afile;

    $cmdft = '--footer-spacing 3 --margin-bottom 15mm --footer-font-size 9 --footer-right "Page [page] of [toPage]" --footer-left "' . get_datetime_string(time(), DT_SHORT_DATE_TIME, DF_AUTO, "null") . '"';

    $cmd = "/usr/bin/wkhtmltopdf --lowquality --no-outline -O Landscape {$cmdft} '{$aurl}' '{$fname}' 2>&1";
    $out = @exec($cmd);

    if (!file_exists($fname)) {
        echo "\n\n************\nERROR: Failed to render URL '" . $aurl . "' as '" . $fname . "'\n************\n\n";
        die();
    } else {
        // We'll be outputting a PDF
        header('Content-type: application/pdf');

        // It will be called execsummary.pdf
        //header('Content-Disposition: attachment; filename="execsummary.pdf"');

        // The PDF source is in original.pdf
        readfile($fname);
        unlink($fname);
    }
}

function get_nna_reports_jpg()
{
    global $cfg;

    // Grab the backend ticket and username
    $username = $_SESSION["username"];
    $backend_ticket = get_user_attr(0, "backend_ticket");

    // Assemble actual URL that will be gotten
    $uri = str_replace("mode=jpg", "hideoptions=1", $_SERVER["REQUEST_URI"]);
    $fullurl = "http://127.0.0.1{$uri}";
    $urlparts = parse_url($fullurl);
    $uri = str_replace("/nagiosxi", "", $urlparts['path']);
    $newurl = get_internal_url() . $uri;
    if (!empty($urlparts['query'])) {
        $newurl .= "?";
        $newurl .= $urlparts['query'];
    }

    // Add the username and ticket
    $newurl .= "&username=" . $username;
    $newurl .= "&ticket=" . $backend_ticket;

    // Add language to url
    $language = $_SESSION['language'];
    $newurl .= "&locale=" . $language;

    // Do page rendering

    $tmpfiles = array();
    $aurl = $newurl;

    $afile = "page.jpg";
    $fname = get_tmp_dir() . "/scheduledreport-" . $username . "-" . time() . "-" . $afile;

    $cmd = "/usr/bin/wkhtmltoimage --lowquality '{$aurl}' '{$fname}' 2>&1";
    $out = @exec($cmd);

    if (!file_exists($fname)) {
        echo "\n\n************\nERROR: Failed to render URL '" . $aurl . "' as '" . $fname . "'\n************\n\n";
        die();
    } else {
        // We'll be outputting a JPG
        header('Content-type: application/jpg');
        header('Content-Disposition: attachment; filename="networkreport.jpg"');

        // The JPG source is in original.jpg
        readfile($fname);
        unlink($fname);
    }
}

?>