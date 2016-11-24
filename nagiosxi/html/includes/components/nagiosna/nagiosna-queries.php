<?php
// NAGIOS NETWORK ANALYZER queryS
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
            get_nna_queries_pdf();
            break;
        case "jpg":
            get_nna_queries_jpg();
            break;

        default:
            display_nna_queries();
            break;
    }
}

/*
 * Display the Queries
 *  - Grabs saved queries from the current NNA Server(s) selected in config settings and displays them
 */
function display_nna_queries()
{
    do_page_start(array("page_title" => _("NNA Queries")), true);

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
    $qid = grab_request_var("query", 1);
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

    // If we have an object and qid and not disabled loading
    $disable_report_auto_run = get_option("disable_report_auto_run", 0);
    if ($object > 0 && $qid > 0 && ($manual_run == 1 || $disable_report_auto_run == 0)) {

        // Create the api url
        if ($secure) {
            $sec = "s";
        } else {
            $sec = "";
        }
        $nna_url = "http" . $sec . "://" . $address . "/nagiosna/index.php";
        $api_url = $nna_url . "/api/";
        $query_viz_url = $api_url . "queries/queryviz?qid=" . $qid . "&token=" . $api_key;

        $url = $api_url . "queries/execute?q[qid]=" . $qid;
        if ($object_type == "source") {
            $url .= "&q[sid]=" . $object;
            $query_viz_url .= "&sid=" . $object;
        } else if ($object_type == "sourcegroup") {
            $url .= "&q[gid]=" . $object;
            $query_viz_url .= "&gid=" . $object;
        }

        // Check if view and add on
        if ($useview) {
            $url .= "&q[vid]=" . $view_id;
            $query_viz_url .= "&vid=" . $view_id;
        }

        // Get the query data
        $url .= "&token=" . $api_key;
        $json = file_get_contents($url);
        $query_data = json_decode($json);

        // Grab the actual query info
        $url = $api_url . "queries/read?q[qid]=" . $qid;
        $url .= "&token=" . $api_key;
        $json = file_get_contents($url);
        $query = json_decode($json);
        $query = $query[0];

    }

    // Hide all options if the users is generating a PDF
    if (!$hideoptions) {
        ?>

        <script type="text/javascript">
            var nna_server = "<?php echo $server; ?>";
            var query_id = <?php echo $qid; ?>;
            var object_type = "<?php echo $object_type; ?>";
            var object = <?php if (empty($object)) { echo '""'; } else { echo $object; } ?>;
            var object_name = "<?php echo $object_name; ?>";
            var view_id = "<?php echo $view_id; ?>";
            var show_excluders = <?php if (empty($excluders)) { echo 0; } else { echo 1; } ?>;

            $(document).ready(function () {

                if (nna_server == "") {
                    nna_server = $('#nna_server').val();
                }

                // Load all querys
                load_queries_from_server(nna_server);

                // Load default sources or sourcegroups
                if (object_type == "source") {
                    load_sources_from_server(nna_server);
                } else if (object_type == "sourcegroup") {
                    load_sourcegroups_from_server(nna_server);
                }
                $('input[name="object_type"][value="' + object_type + '"]').prop('checked', true);

                // Load querys on server change
                $('#nna_server').change(function () {
                    $('#use-view-container').hide();
                    load_queries_from_server($(this).val());
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

            function load_queries_from_server(server) {
                var server = server.split("|");
                var api_url = get_nna_api_url(server[0], server[1]);
                $('#error').hide();

                $.get(api_url + "queries/read", {token: server[2]}, function (data) {

                    $('#query').html("");
                    $.each(data, function (k, v) {
                        var selected = "";
                        if (v.qid == query_id) {
                            selected = "selected";
                        }
                        $('#query').append('<option value="' + v.qid + '" ' + selected + '>' + v.name + '</option>');
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

        <h1><?php echo _("Network Query"); ?></h1>

        <style>
            .excluders label, .excluders span {
                margin-right: 10px;
            }
        </style>

        <form method="get">
            <div>
                <?php echo _("Nagios Network Analyzer Server:"); ?> <?php display_nna_servers(false, $server); // Get all the NNA servers available in XI ?>
                <span style="margin-left: 10px;">
                <label><input type="radio" value="source" name="object_type" checked> <?php echo _("Source"); ?>
                </label>
                <label style="margin-left: 10px;"><input type="radio" value="sourcegroup"
                                                         name="object_type"> <?php echo _("Sourcegroup"); ?>
                </label>
            </span>
            <span style="margin-left: 10px;">
                <select id="object" name="object" class="form-control"></select>
            </span>
            <span style="margin-left: 10px; <?php if (!$useview) {
                echo "display: none;";
            } ?>" id="use-view-container">
                <label><input type="checkbox" name="useview" value="1" <?php if ($useview) {
                        echo "checked";
                    } ?>> <?php echo _("Use a View"); ?>: </label>
                <select id="views" name="view_id" class="form-control"></select>
                <input type="hidden" id="view_name" name="view_name" value="<?php echo $view_name; ?>">
            </span>
            <span style="margin-left: 10px;">
                <?php if (empty($excluders)) { ?>
                    <a style="cursor: pointer;"
                       id="show-excluders">[+] <?php echo _("Show Column Excluders"); ?></a>
                <?php } else { ?>
                    <a style="cursor: pointer;"
                       id="show-excluders">[-] <?php echo _("Hide Column Excluders"); ?></a>
                <?php } ?>
            </span>
            </div>
            <div style="margin-top: 10px; <?php if (empty($excluders)) {
                echo "display: none;";
            } ?>" id="excluders" class="excluders">
                <table>
                    <tr>
                        <td><span><?php echo _("Exclude the checked columns from the queries display table"); ?>
                                :</span></td>
                        <td>
                            <label><input type="checkbox" name="excluders[]"
                                          value="start" <?php if (in_array("start", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Flow Start"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="end" <?php if (in_array("end", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Flow End"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="duration" <?php if (in_array("duration", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Duration"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="srcip" <?php if (in_array("srcip", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Source IP"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="dstip" <?php if (in_array("dstip", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Destination IP"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="srcport" <?php if (in_array("srcport", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Source Port"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="dstport" <?php if (in_array("dstport", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Destination Port"); ?></label>
                        </td>
                    <tr>
                    <tr>
                        <td><?php echo _("(Useful if generating a PDF with this data)"); ?></td>
                        <td>
                            <label><input type="checkbox" name="excluders[]"
                                          value="flows" <?php if (in_array("flows", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Flows"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="packets" <?php if (in_array("packets", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Packets"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="bytes" <?php if (in_array("bytes", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Bytes"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="pps" <?php if (in_array("pps", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Packets/Sec"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="bps" <?php if (in_array("bps", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Bytes/Sec"); ?></label>
                            <label><input type="checkbox" name="excluders[]"
                                          value="Bpp" <?php if (in_array("Bpp", $excluders)) {
                                    echo "checked";
                                } ?>> <?php echo _("Bytes/Packet"); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="margin-top: 10px;">
                <span><?php echo _("Query to run:"); ?> <select id="query" name="query" class="form-control"></select></span>
                <span style="margin-left: 10px;"><button type="submit" class="btn btn-sm btn-primary" style="vertical-align: top;"><?php echo _("Run Query"); ?></button></span>
            </div>
            <!-- Set a variable to let us know it's okay to run this -->
            <input type="hidden" name="manual_run" value="1">
            <input type="hidden" value="" name="object_name" id="object_name">
        </form>

        <div class="error" id="error" style="display:none;"></div>

    <?php

    } // End: Hideoptions

    //echo "<pre>";
    //print_r($query_data);
    //echo "</pre>";

    if ($query && $query_data) {

        // List of ignored columns
        $ignore = array("protocol", "srcas", "dstas", "tos");

        // Strip record header (the top th part) from records
        $records = $query_data->records;
        $summary = $query_data->summary;
        $head = @$query_data->records[0];

        // Let's display the graphs?
        $vizdata = json_decode(file_get_contents($query_viz_url));
        ?>

        <script>
            $(document).ready(function () {

                make_chord(400, 900, 'Relational Mapping');

                $('.fullscreen-button').click(function () {
                    resize_to_fullscreen(this);
                });
            });

            function make_chord(h, w, title) {

                var r1 = h / 2,
                    r0 = r1 - 80;


                var fill = d3.scale.category20c();

                var chord = d3.layout.chord()
                    .padding(.04)
                    .sortSubgroups(d3.descending)
                    .sortChords(d3.descending);

                var arc = d3.svg.arc()
                    .innerRadius(r0)
                    .outerRadius(r0 + 20);

                var svg = d3.select("#relationaltarget").append("svg:svg")
                    .attr("viewBox", "0 0 " + w + " " + h)
                    .attr("width", "100%")
                    .attr("height", "100%")
                    .append("svg:g")
                    .attr("transform", "translate(" + w / 2 + "," + h / 2 + ")");

                d3.json('<?php echo $nna_url . $vizdata->apiurl . "?" . $vizdata->apiquery . $vizdata->chordquery; ?>', function (imports) {

                    if (imports.names.length == 1 && imports.names[0] == 'Other') {
                        $('#relationaltarget').children('.reportviz-throbber').hide();
                        $('#relationaltarget').html('<p style="margin-top:40px;" class="text-center"><?php echo _("No Data"); ?></p>');
                        return;
                    }

                    var indexByName = {},
                        nameByIndex = {},
                        matrix = imports.matrix,
                        labels = imports.names,
                        denote = imports.denote,
                        n = 0;

                    self.names = [];

                    // Returns the Flare package name for the given class name.
                    function name(name) {
                        return name.substring(0, name.lastIndexOf(".")).substring(6);
                    }

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
                        .text(function (d) {
                            return nameByIndex[d.index];
                        })
                        .style(function (d) {
                            return "color", "red"
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

                    $('#relationaltarget').children('.reportviz-throbber').hide();

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

            function resize_to_fullscreen(node) {

                var div_id = $(node).attr('data');
                var w = $(window).width();
                var h = $(window).height();
                $(div_id).addClass('front-and-center').width(w - 60).height(h - 60);
                $(div_id).addClass('well').css({'z-index': 10000});

                $(div_id).offset({
                    top: 10,
                    left: 10
                });

                $(div_id).prepend('<span id="graph-title"><a id="make-normal-size" onclick="resize_to_normal(\'' + div_id + '\')" class="btn btn-sm btn-default"><i class="fa fa-compress"></i></a>&nbsp;<?php echo _("Relational Mapping"); ?></span>');
            }

            function resize_to_normal(div_id) {
                $(div_id).css({top: 0, left: 0, 'z-index': 1})
                    .removeClass('front-and-center')
                    .removeClass('well')
                    .width('').height('')
                    .children('span#graph-title').each(function (i, d) {
                        $(d).remove();
                    });
            }

        </script>

        <div <?php if (!$hideoptions) {
            echo 'style="margin-top: 30px;"';
        } ?>>

            <h1><?php echo _("Query") . ": " . $query->name; ?></h1>

            <p>
                <?php if ($query->description) { ?>

            <div style="margin-bottom: 5px;"><?php echo _("Description"); ?>
                : <?php echo $query->description ?></div>
            <?php } // End if there is a description ?>
            <div><?php echo ucfirst($object_type); ?>:
                <strong><?php echo $object_name; ?></strong> <?php if ($useview && $view_name) {
                    echo _('using view ') . "<strong>" . $view_name . "</strong>";
                } ?> on <?php echo $address; ?></div>
            <div style="margin-top: 5px;">
                <?php echo _("Timeframe") . ": " . nna_human_readable_timeframe($query); ?>
            </div>
            <?php
            if (!empty($query->rawquery)) {
                echo '<div style="margin-top: 5px;">' . _("Raw Query") . ': ' . $query->rawquery . '</div>';
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
                            <div id="relationaltarget" class='chartrender'>
                                <div class='reportviz-throbber'>
                                    <div class='valign-throbber centerme'>
                                        <i class='fa fa-spinner fa-spin'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h5><a class='btn btn-sm btn-default fullscreen-button' data="#relationaltarget"
                               style="margin-right: 5px;"><i
                                    class='fa fa-expand'></i></a> <?php echo _("Chord Diagram"); ?></h5>
                    </li>
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
                    if (is_array($head) || is_object($head)) {
                        foreach ($head as $k => $h) {
                            if (!in_array($k, $excluders) && !in_array($k, $ignore)) {
                                echo '<th>' . nna_human_readable_header($k, $query) . '</th>';
                            }
                        }
                    } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                // Display a message if there is no data
                if (count($records) == 0) {
                    echo '<tr><td colspan="99">No data returned for this query.</td></tr>';
                } else {

                    // Display data
                    foreach ($records as $record) {
                        echo '<tr>';
                        foreach ($record as $k => $v) {
                            if (!in_array($k, $excluders) && !in_array($k, $ignore)) {

                                // If they are 0
                                $star_vals = array("srcip", "dstip", "srcport", "dstport");
                                if (in_array($k, $star_vals)) {
                                    if ($v == 0 || $v == "0") {
                                        $value = "*";
                                    } else {
                                        $value = $v;
                                    }
                                } else {
                                    $value = nna_human_readable_value($v, $k);
                                }

                                echo '<td>' . $value . '</td>';
                            }
                        }
                        echo '</tr>';
                    }
                }
                ?>
                </tbody>
            </table>

            <?php if (count($records) != 0) { ?>

                <table <?php if (get_option('theme', 'xi2014') == "xi2014") {
                    echo 'class="table table-striped table-bordered table-hover"';
                } else {
                    echo 'class="standardtable"';
                } ?> style="margin-top: 15px;">
                    <thead>
                    <tr>
                        <?php
                        foreach ($summary as $k => $h) {
                            echo '<th>' . nna_human_readable_header($k) . '</th>';
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <?php
                        foreach ($summary as $k => $h) {
                            echo '<td>' . nna_human_readable_value($h, $k) . '</td>';
                        }
                        ?>
                    </tr>
                    </tbody>
                </table>

            <?php } ?>

        </div>

    <?php
    } // End query

    do_page_end(true);
}

// End: Display querys (display_nna_querys)

// Function to do the pdf generating for this query
function get_nna_queries_pdf()
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

    $cmd = "/usr/bin/wkhtmltopdf --no-outline -O Landscape {$cmdft} '{$aurl}' '{$fname}' 2>&1";
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

function get_nna_queries_jpg()
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

    $cmd = "/usr/bin/wkhtmltoimage '{$aurl}' '{$fname}' 2>&1";
    $out = @exec($cmd);

    if (!file_exists($fname)) {
        echo "\n\n************\nERROR: Failed to render URL '" . $aurl . "' as '" . $fname . "'\n************\n\n";
        die();
    } else {
        // We'll be outputting a JPG
        header('Content-type: application/jpg');
        header('Content-Disposition: attachment; filename="networkquery.jpg"');

        // The JPG source is in original.jpg
        readfile($fname);
        unlink($fname);
    }
}

?>