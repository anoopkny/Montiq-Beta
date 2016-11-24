<?php
//
// Nagios Network Analyzer Config Wizard
// Copyright (c) 2014-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

nna_configwizard_init();

function nna_configwizard_init()
{
    $name = "nna";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a source, view, or sourcegroup on a Nagios Network Analyzer server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Nagios Network Analyzer"),
        CONFIGWIZARD_FUNCTION => "nna_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "networkanalyzer.png",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios','network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function nna_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "nna";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            // Get variables that were passed to us
            $nna_server = grab_array_var($inargs, "nna_server", "||");
            $object_type = grab_array_var($inargs, "object_type", "");
            $object_id = grab_array_var($inargs, "object_id", 0);
            $hostname = grab_array_var($inargs, "hostname", "");

            $hostname = nagiosccm_replace_user_macros($hostname);

            // Grab the view
            $use_view = grab_array_var($inargs, "use_view", 0);
            $view_id = grab_array_var($inargs, "view_id", 0);
            $view_name = grab_array_var($inargs, "view_name", "");

            $output = '
            <script type="text/javascript">
            $(document).ready(function () {

                $("input[name=submitButton2]").prop("disabled", true);
                nna_connect();

                $("#obj-type").change(function() {
                    load_nna_objects($(this).val() + "s");
                    if ($(this).val() == "group") {
                        $("#view-selector").hide();
                    }
                });

                $(".obj-selector").change(function() {

                    var oname = $(this).children("option:selected").text();
                    var obj_name = "";

                    if ($("#obj-type").val() == "source") {
                        obj_name = "NNA Source - " + oname;
                    } else if ($("#obj-type").val() == "group") {
                        obj_name = "NNA Sourcegroup - " + oname;
                    }
                    $("#hostname").val(obj_name);

                    $("#object_id").val($(this).val());
                    load_views($(this).val());
                });

                $("#views").change(function() {
                    // Set the view name to whatever is selected
                    $("#view_name").val($("#views option:selected").text());
                });

            });

            function nna_connect() {
                var server = $("#nna_server").val();
                server = server.split("|");

                var secure = "http";
                if (server[1] == 1) {
                    secure = "https";
                }

                var nna_api_url = secure + "://" + server[0] + "/nagiosna/index.php/api/";
                var token = server[2];

                $("#connect-error").hide();
                $.post(nna_api_url + "system/cpu_status", { token: token }, function(data) {
                    if (data.error) {
                        $("#connect-error").html("' . _("Authentication failed. Please check your API key.") . '").show();
                    } else {
                        $("input[name=submitButton2]").prop("disabled", false);
                        $("#hostname-info").hide();
                        load_nna_objects("sources");
                        $("#hostname-selector").show();
                    }
                }).fail(function(data) {
                    var error = "' . _("Failed connect to API. Check your connection to the host (using SSL?) and make sure your Nagios Network Analyzer is version 2014R1.5 or higher.") . '";
                    if (data.status == 404) {
                        error = "404 - API not found. The address may be wrong.";
                    }
                    $("#hostname-info").html(error).show();
                });
            }

            function load_nna_objects(object) {
                var server = $("#nna_server").val();
                server = server.split("|");

                var secure = "http";
                if (server[1] == 1) {
                    secure = "https";
                }
                var nna_api_url = secure + "://" + server[0] + "/nagiosna/index.php/api/";
                var token = server[2];
                $(".obj-selector").hide();
                $("#" + object).html("");
                $.post(nna_api_url + object + "/read", { token:token }, function(data) {
                    if (data.error) {
                        $("#error").html(data.error).show();
                    } else {
                        var fobj_id = 0;
                        var fobj_name = "";
                        var objs = data;
                        if (objs.length > 0) {
                            $.each(objs, function(k, v) {
                                var id;
                                if (v.sid) {
                                    id = v.sid;
                                } else if (v.gid) {
                                    id = v.gid
                                }

                                if (k == 0) {
                                    fobj_id = id;
                                    if (v.sid) {
                                        fobj_name = "NNA Source - " + v.name;
                                    } else if (v.gid) {
                                        fobj_name = "NNA Sourcegroup - " + v.name;
                                    }

                                }

                                $("#" + object).append("<option value=\'" + id + "\'>" + v.name + "</option>");
                            });
                            $("#" + object).show();
                            $("#add-obj").show();
                        } else {
                            $("#error").html("No " + object + " available.").show();
                        }

                        // For first run we select the first object and put it into id/type and hostname
                        $("#hostname").val(fobj_name);
                        $("#object_type").val(object);
                        $("#object_id").val(fobj_id);
                        
                        // If a source, lets get the views
                        if (object == "sources") {
                            load_views(fobj_id);
                        }
                    }
                }, "json");
            }

            function load_views(sid)
            {
                var server = $("#nna_server").val();
                server = server.split("|");

                var secure = "http";
                if (server[1] == 1) {
                    secure = "https";
                }
                var nna_api_url = secure + "://" + server[0] + "/nagiosna/index.php/api/";
                var token = server[2];
                $.post(nna_api_url + "views/get_views", { token: token, "q[sid]": sid }, function(views) {
                    if (views.length > 0) {
                        $("#view-selector").show();
                        $("#views").html("");
                        $.each(views, function(k, v) {
                            $("#views").append("<option value=\'" + v.vid + "\'>" + v.name + "</option>");
                        });
                    } else {
                        $("#view-selector").hide();
                    }

                    // Set the view name to whatever is selected
                    $("#view_name").val($("#views option:selected").text());
                }, "json");
            }

            </script>';


            // If NNA servers exist we can display a dropdown otherwise we need to display a message
            if (has_nna_servers()) {
                $output .= '

<h5 class="ul">' . _('Nagios Network Analyzer Server') . '</h5>
<p>' . _('Select one of your Nagios Network Analyzer server\'s source, sourcegroup, or view.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('NNA Server') . ':</label>
        </td>
        <td>';
                $output .= display_nna_servers(true);
                $output .= '
        </td>
    </tr>
    <tr>
        <td><label>' . _('Host Name') . ':</label></td>
        <td>
            <span id="hostname-info"><strong>' . _("Connecting...") . '</strong></span>
            <span style="display:none;" id="hostname-selector">
                <select id="obj-type" class="form-control">
                    <option value="source">' . _("Source") . '</option>
                    <option value="group">' . _("Sourcegroup") . '</option>
                </select>
                <select class="obj-selector form-control" id="sources"></select>
                <select class="obj-selector form-control" id="groups"></select>
                <span style="margin-left: 10px;" class="obj-selector" id="error"></span>
            </span>
            <input type="hidden" value="" id="object_type" name="object_type">
            <input type="hidden" value="" id="object_id" name="object_id">
            <input type="hidden" value="" id="hostname" name="hostname">
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <div id="view-selector" style="display: none;">
                <label><input type="checkbox" value="1" name="use_view" ' . is_checked($use_view, 1) . '> ' . _("Use a View") . ':</label>
                <select name="view_id" id="views" class="form-control"></select>
                <input type="hidden" value="" id="view_name" name="view_name">
            </div>
        </td>
    </tr>
</table>';

            } else {
                // Display NO NNA server message
                $output .= display_nna_no_servers(true);
                $output .= '<div style="height: 100px;"></div>';
            }

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $nna_server = grab_array_var($inargs, "nna_server", "||");
            $object_type = grab_array_var($inargs, "object_type", "");
            $object_id = grab_array_var($inargs, "object_id", 0);
            $hostname = grab_array_var($inargs, "hostname", "");

            // grab the view..
            $use_view = grab_array_var($inargs, "use_view", 0);
            $view_id = grab_array_var($inargs, "view_id", 0);
            $view_name = grab_array_var($inargs, "view_name", "");

            // Grab server vars
            list($address, $secure, $token) = explode("|", $nna_server);

            // check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($address) == false) {
                $errmsg[$errors++] = "No address specified.";
            }
            if (have_value($token) == false) {
                $errmsg[$errors++] = "No API key specified.";
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // get variables that were passed to us
            $nna_server = grab_array_var($inargs, "nna_server", "||");
            $object_type = grab_array_var($inargs, "object_type", "");
            $object_id = grab_array_var($inargs, "object_id", 0);
            $hostname = grab_array_var($inargs, "hostname", "");

            // grab the view..
            $use_view = grab_array_var($inargs, "use_view", 0);
            $view_id = grab_array_var($inargs, "view_id", 0);
            $view_name = grab_array_var($inargs, "view_name", "");

            // Grab server vars
            list($address, $secure, $token) = explode("|", $nna_server);

            if ($object_type == 'sources') {
                $object_js_type = "vars.sid = " . $object_id . ";";
            } else if ($object_type == 'groups') {
                $object_js_type = "vars.gid = " . $object_id . ";";
            }

            if ($use_view) {
                $object_js_view = "vars.vid = " . $view_id . ";";
            }

            // Grab graph data for the source
            $s = "http";
            if ($secure) {
                $s = "https";
            }
            $nna_api_url = $s . "://" . $address . "/nagiosna/index.php/api/graphs/execute";

            // Grab the services from the session
            $services = grab_array_var($_SESSION, "nna_services", array());
            if (empty($services)) {
                $services['bytes']['monitor'] = 1;
                $services['flows']['monitor'] = 1;
                $services['packets']['monitor'] = 1;
                $services['behavior']['monitor'] = 1;
            }

            // Create the new hostname if we are using a view
            if ($use_view && strpos($hostname, $view_name) === FALSE) {
                $hostname .= " - " . $view_name;
            }

            // Display some javascript
            $output = "
            <script type='text/javascript'>
            $(document).ready(function() {
                
                var api_url = '" . $nna_api_url . "';

                //loading($('#source-graph'));

                var vars = { 'q[Bytes]': 'bytes', 
                             'q[Flows]': 'flows', 
                             'q[Packets]': 'packets',
                             'token': '" . $token . "',
                             'begindate': '-1 week' }

                " . $object_js_type . "
                " . $object_js_view . "

                $.post(api_url, vars, function(data) {
                    
                    console.log(data);

                    var top_bytes = 0;
                    $.each(data[0].data, function(k,bytes) {
                        if (top_bytes < bytes) {
                            top_bytes = bytes;
                        }
                    })

                    var top_packets = 0;
                    $.each(data[2].data, function(k,packets) {
                        if (top_packets < packets) {
                            top_packets = packets;
                        }
                    })

                    var top_flows = 0;
                    $.each(data[1].data, function(k,flows) {
                        if (top_flows < flows) {
                            top_flows = flows;
                        }
                    })

                    $('#bytes-critical').val(Math.round(top_bytes * 1.4));
                    $('#bytes-warning').val(Math.round(top_bytes * 1.2));

                    $('#packets-critical').val(Math.round(top_packets * 1.4));
                    $('#packets-warning').val(Math.round(top_packets * 1.2));

                    $('#flows-critical').val(Math.round(top_flows * 1.4));
                    $('#flows-warning').val(Math.round(top_flows * 1.2));

                    var title = 'Last Week of Bandwidth Data';
                    var text = 'Select or deselect the types of data to show on the graph using the legend';
                    var series;
                    
                    if (data.error || data[0].total == 0) {
                        title = 'No Data Available';
                        text = 'There is no data available for the currently selected time period.';
                        series= [];
                    } else {
                        series = [{
                            name: data[0].name,
                            pointInterval: data[0].pointInterval,
                            pointStart: data[0].pointStart, 
                            data: data[0].data
                        },
                        {
                            name: data[2].name,
                            pointInterval: data[2].pointInterval,
                            pointStart: data[2].pointStart, 
                            data: data[2].data
                        },
                        {
                            name: data[1].name,
                            pointInterval: data[1].pointInterval,
                            pointStart: data[1].pointStart, 
                            data: data[1].data
                        }]
                    }

                    GRAPH = new Highcharts.Chart({
                        chart: {
                            type: 'area',
                            renderTo: 'nna-graph',
                            zoomType: 'x',
                            resetZoomButton: {
                                theme: {
                                    display: 'none'
                                }
                            },
                        },
                        credits: {
                                enabled: false
                        },
                        colors: [
                            '#5e8ff6',
                            '#2156c3',
                            '#2e2929'
                        ],
                        exporting: {
                            enabled: true
                        },
                        title: {
                            text: title
                        },
                        subtitle: {
                            text: text
                        },
                        xAxis: {
                            type: 'datetime',
                            title: {
                                text: 'Time'
                            }
                        },
                        yAxis: {
                            title: {
                                text: ''
                            },
                            type: 'logarithmic'
                        },
                        tooltip: {
                            formatter: function() {
                                    var h = '<b>' + Highcharts.dateFormat('%m/%d/%Y %H:%M', this.x) + '</b>';

                                    $.each(this.points, function(i, point) {
                                        h += '<br/>' + point.series.name + ': ' + (point.y).toFixed(0);
                                    });

                                    return h;
                                },
                            shared: true
                        },
                        legend: {
                            borderWidth: 0
                        },
                        plotOptions: {
                            area: {
                                lineWidth: 2,
                                marker: {
                                    enabled: false
                                }
                            }
                        },
                        series: series
                    })

                }, 'json');
            });
            </script>

<input type='hidden' name='nna_server' value='" . $nna_server . "'>
<input type='hidden' name='object_type' value='" . $object_type . "'>
<input type='hidden' name='object_id' value='" . $object_id . "'>
<input type='hidden' name='use_view' value='" . $use_view . "'>
<input type='hidden' name='view_id' value='" . $view_id . "'>
<input type='hidden' name='view_name' value='" . $view_name . "'>";

            $output .= '
<h5 class="ul">' . _('Nagios Network Analyzer Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td><label>' . _("NNA Server") . ':</label></td>
        <td><input type="text" style="width: 250px;" value="' . get_nna_server_name($nna_server) . '" class="form-control" readonly></td>
    </tr>
    <tr>
        <td><label>' . _("Host Name") . ':</label></td>
        <td><input type="text" style="width: 300px;" name="hostname" value="' . htmlentities($hostname) . '" class="form-control"></td>
    </tr>
</table>

<h5 class="ul">' . _('Select What to Monitor') . '</h5>
<div style="float: left;">
    <p>' . _("Select if you'd like to monitor including bytes, flows, packets and behavior on sources.<br/>The graph on the right is provided to help with estimating the warning and critical thresholds.") . '</p>
    <p style="padding: 0 0 20px 0; margin: 0;">' . _("Default values are created by the following:<br/> Warning Threshold: <strong>20% above max value</strong>,<br/> Critical Threshold: <strong>40% above max value</strong>") . '</p>
        
    <table class="table table-no-border table-auto-width">
        <tr>
            <td class="vt">
                <input type="checkbox" id="b" class="checkbox" value="1" name="services[bytes][monitor]" ' . is_checked($services['bytes']['monitor'], 1) . '>
            </td>
            <td>
                <label class="normal" for="b">
                    <strong>'._('Bytes').'</strong><br/>
                    '._('Amount of bytes being transferred.').'
                </label>
                <div class="pad-t5">
                    <span>
                        <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> <input type="text" name="services[bytes][warning]" id="bytes-warning" value="' . $services['bytes']['warning'] . '" class="form-control condensed" style="width: 80px;"></label> bytes
                    </span> &nbsp;
                    <span>
                        <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> <input type="text" name="services[bytes][critical]" id="bytes-critical" value="' . $services['bytes']['critical'] . '" class="form-control condensed" style="width: 80px;"></label> bytes
                    </span>
                </div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <input type="checkbox" class="checkbox" value="1" name="services[flows][monitor]" ' . is_checked($services['flows']['monitor'], 1) . '>
            </td>
            <td>
                <label class="normal" for="f">
                    <strong>'._('Flows').'</strong><br/>
                    '._('Amount of flows being transferred.').'
                </label>
                <div class="pad-t5">
                    <span>
                        <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> <input type="text" name="services[flows][warning]" id="flows-warning" value="' . $services['flows']['warning'] . '" class="form-control condensed" style="width: 60px;"></label> flows
                    </span> &nbsp;
                    <span>
                        <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> <input type="text" name="services[flows][critical]" id="flows-critical" value="' . $services['flows']['critical'] . '" class="form-control condensed" style="width: 60px;"></label> flows
                    </span>
                </div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <input type="checkbox" class="checkbox" value="1" name="services[packets][monitor]" ' . is_checked($services['packets']['monitor'], 1) . '>
            </td>
            <td>
                <label class="normal" for="pks">
                    <strong>'._('Packets').'</strong><br/>
                    '._('Amount of packets being transferred.').'
                </label>
                <div class="pad-t5">
                    <span>
                        <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> <input type="text" name="services[packets][warning]" id="packets-warning" value="' . $services['packets']['warning'] . '" class="form-control condensed" style="width: 60px;"></label> packets
                    </span> &nbsp;
                    <span>
                        <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> <input type="text" name="services[packets][critical]" id="packets-critical" value="' . $services['packets']['critical'] . '" class="form-control condensed" style="width: 60px;"></label> packets
                    </span>
                </div>
            </td>
        </tr>
    ';

            // If the service is going on a source, we can add abnormal behavior
            if ($object_type == "sources") {

                $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" id="ab" class="checkbox" value="1" name="services[behavior][monitor]" ' . is_checked($services['behavior']['monitor'], 1) . '>
                        </td>
                        <td>
                            <label class="normal" for="ab">
                                <strong>'._('Abnormal Behavior').'</strong><br/>
                                '._('If there is abnormal behavior on the source this check will return critical.').'
                            </label>
                        </td>
                    </tr>';

            }

            $output .= '</table>
    </div>
    <div id="nna-graph" style="width: 600px; height: 300px; float: left; margin-left: 40px;"></div>
    <div style="clear: both; margin-bottom: 30px;"></div>';

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed
            $nna_server = grab_array_var($inargs, "nna_server", "||");
            $hostname = grab_array_var($inargs, "hostname", "");
            $services = grab_array_var($inargs, "services", array());
            $object_type = grab_array_var($inargs, "object_type", "");
            $object_id = grab_array_var($inargs, "object_id", 0);

            // grab the view..
            $use_view = grab_array_var($inargs, "use_view", 0);
            $view_id = grab_array_var($inargs, "view_id", 0);
            $view_name = grab_array_var($inargs, "view_name", "");

            // Checking for errors...
            $errors = 0;
            $errmsg = array();

            // If no services are going to be created
            if (count($services) == 0) {
                $_SESSION['nna_services'] = array();
                $errmsg[$errors++] = "You must add services to be defined.";
            }

            // Check to make sure there are warning and critical values for abnormal behavior
            if ($services['bytes']['monitor']) {
                if ($services['bytes']['warning'] == "" || $services['bytes']['critical'] == "") {
                    $errmsg[$errors++] = "You must set a warning and critical value if you are going to monitor bytes.";
                }
            }

            if ($services['flows']['monitor']) {
                if ($services['flows']['warning'] == "" || $services['flows']['critical'] == "") {
                    $errmsg[$errors++] = "You must set a warning and critical value if you are going to monitor flows.";
                }
            }

            if ($services['packets']['monitor']) {
                if ($services['packets']['warning'] == "" || $services['packets']['critical'] == "") {
                    $errmsg[$errors++] = "You must set a warning and critical value if you are going to monitor packets.";
                }
            }

            // Make sure hostname is valid
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = "Invalid host name.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $nna_server = grab_array_var($inargs, 'nna_server', '||');
            $hostname = grab_array_var($inargs, 'hostname');
            $services = grab_array_var($inargs, "services", array());
            $object_type = grab_array_var($inargs, "object_type", "");
            $object_id = grab_array_var($inargs, "object_id", 0);

            // grab the view..
            $use_view = grab_array_var($inargs, "use_view", 0);
            $view_id = grab_array_var($inargs, "view_id", 0);
            $view_name = grab_array_var($inargs, "view_name", "");

            // Store the services in session for later
            $_SESSION['nna_services'] = $services;

            $output = "
            <input type='hidden' name='hostname' value='" . htmlentities($hostname) . "' />
            <input type='hidden' name='nna_server' value='" . $nna_server . "' />
            <input type='hidden' name='object_type' value='" . $object_type . "' />
            <input type='hidden' name='object_id' value='" . $object_id . "' />
            <input type='hidden' name='use_view' value='" . $use_view . "' />
            <input type='hidden' name='view_id' value='" . $view_id . "' />
            <input type='hidden' name='view_name' value='" . $view_name . "' />";

            // print_r($services);
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            // Grab request vars
            $hostname = grab_array_var($inargs, "hostname", "");
            $nna_server = grab_array_var($inargs, "nna_server", "||");
            $services = grab_array_var($inargs, "services", array());
            $object_type = grab_array_var($inargs, "object_type", "");
            $object_id = grab_array_var($inargs, "object_id", 0);

            // grab the view..
            $use_view = grab_array_var($inargs, "use_view", 0);
            $view_id = grab_array_var($inargs, "view_id", 0);
            $view_name = grab_array_var($inargs, "view_name", "");

            // Grab stuff from NNA servers
            list($address, $use_https, $token) = explode("|", $nna_server);
            $hostaddress = $address;

            // Grab the services out of the session (and clear session for a new run)
            $services = grab_array_var($_SESSION, "nna_services", array());
            $_SESSION['nna_services'] = array();

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["token"] = $token;
            $meta_arr["services"] = $services;
            $meta_arr["https"] = $use_https;
            $meta_arr["object_type"] = $object_type;
            $meta_arr["object_id"] = $object_id;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {

                $exists_command = "";

                if ($object_type == "sources") {
                    $exists_command = "-S";
                } else if ($object_type == "groups") {
                    $exists_command = "-G";
                }

                $exists_command .= " " . $object_id . " --exists";

                // Check if secure
                if ($use_https) {
                    $exists_command .= " --secure";
                }

                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_nna_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "networkanalyzer.png",
                    "statusmap_image" => "networkanalyzer.png",
                    "check_command" => "check_xi_nna!" . $token . "!" . $exists_command,
                    "_xiwizard" => $wizard_name
                );
            }

            // Loop through all services and add them
            foreach ($services as $type => $service) {

                if (!$service['monitor']) {
                    continue;
                }

                $type_args = " -m " . $type;

                // Do for abnormal behavior services
                if ($type == 'behavior') {

                    $service_name = "Abnormal Behavior";
                    $check_command = "-S " . $object_id . $type_args;

                } // Do for normal services
                else {

                    if ($object_type == 'sources') {
                        $arg_type = "-S ";
                    } else if ($object_type == 'groups') {
                        $arg_type = "-G ";
                    }

                    $service_name = ucfirst($type);
                    $check_command = $arg_type . $object_id . $type_args . " -w " . $service['warning'] . " -c " . $service['critical'];
                }

                // Check if secure
                if ($use_https) {
                    $check_command .= " --secure";
                }

                // Check if we are using a view
                if ($use_view) {
                    $check_command .= " -v " . $view_id;
                }

                $objs[] = array(
                    "type" => OBJECTTYPE_SERVICE,
                    "host_name" => $hostname,
                    "service_description" => $service_name,
                    "use" => "xiwizard_nna_service",
                    "check_command" => "check_xi_nna!" . $token . "!" . $check_command,
                    "_xiwizard" => $wizard_name);

            }

            //~ echo "OBJECTS:<BR>";
            //~ print_r($objs);
            //~ exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}