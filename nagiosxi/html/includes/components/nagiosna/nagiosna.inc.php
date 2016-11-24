<?php
// 
// Nagios Network Analyzer Integration Component
// Copyright (c) 2014-2015 Nagios Enterprises, LLC.  All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// Run the initialization function
$nagiosna_component_name = "nagiosna";
nagiosna_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function nagiosna_component_init()
{
    global $nagiosna_component_name;

    $desc = "";

    // check XI version
    $versionok = nagiosna_component_checkversion();
    if (!$versionok) {
        $desc .= " <b>" . _("Error: This component requires Nagios XI 2011R3.2 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $nagiosna_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => "Allows you to view Nagios Network Analyzer reports in Nagios XI and allows Nagios Network Analyzer to dynamically add hosts and services." . $desc,
        COMPONENT_TITLE => "Nagios Network Analyzer Integration",
        COMPONENT_VERSION => "1.2.8",
        COMPONENT_DATE => '07/28/2016',
        COMPONENT_CONFIGFUNCTION => "nagiosna_component_config_func",
        COMPONENT_PROTECTED => true,
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );

    register_component($nagiosna_component_name, $args);

    // Register an addmenu function
    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'nagiosna_component_addmenu');
        register_callback(CALLBACK_SERVICE_TABS_INIT, 'nagiosna_component_addtab');
        register_callback(CALLBACK_HOST_TABS_INIT, 'nagiosna_component_addtab');
    }

}

// Add tabs to the Host/Service details pages
function nagiosna_component_addtab($cbtype, &$cbdata)
{
    // Check if tabs are enabled
    $tabs_disabled = get_option("nna_disable_tabs", 0);
    if ($tabs_disabled == 1) {
        return;
    }

    $data = array('host_name' => $cbdata['host']);
    $xml = get_host_objects_xml_output($data);
    $xml = simplexml_load_string($xml);
    $host_address = strval($xml->host->address);
    $host_name = strval($xml->host->host_name);

    $content = "";

    if (!has_nna_servers()) {

        $content .= display_nna_no_servers(true);

    } else {

        $content .= '<p>' . _("Showing the last 24 hours worth of netflow data on the selected Network Analyzer instance aggregated by source IP and destination IP and sorted by bytes.") . '</p>';
        $content .= '<div style="padding-bottom: 10px;">' . _("Nagios Network Analyzer Server:") . " " . display_nna_servers(true) . ' &nbsp; Raw Query: <span style="font-weight: bold;" id="rawquery"></span> (<a class="nna_link" href="" target="_blank">Open this query in Network Analyzer</a>)</div>';

        $content .= '<script>
        var host_address = "' . trim($host_address) . '";
        var nna_fullscreen = false;

        // Load anonymous query
        function load_nagiosna_anon_query() 
        {
            var server = $("#nna_server option:selected").val();
            server = server.split("|");
            var nna_url = get_nna_url(server[0], server[1]);

            var rawquery = encodeURIComponent("src ip " + host_address + " or dst ip " + host_address);

            var qdata = {
                token: server[2],
                "q[begindate]": "-24 hours",
                "q[gid]": 1,
                "q[rawquery]": rawquery,
                "q[aggregate_csv]": "srcip,dstip"
            }

            $("#rawquery").html("src ip " + host_address + " or dst ip " + host_address);
            $(".nna_link").attr("href", nna_url + "/groups/queries/1?q[rawquery]=" + rawquery + "&q[begindate]=" + encodeURIComponent("-24 hours") + "&q[aggregate_csv]=" + encodeURIComponent("srcip,dstip") + "&q[enddate]=" + encodeURIComponent("-1 second"));

            console.log(nna_url);

            $.get(nna_url + "/api/queries/execute_anonymous", qdata, function(data) {

                // Check if there is data before continuing...
                if (data.records.length == 0) {
                    $("#nna-no-data-message").show();
                    $("#nna-chord-diagram").hide();
                    $("#nna-table-data").hide();
                } else {
                    $("#nna-chord-diagram").show();
                    $("#nna-no-data-message").hide();
                    $("#nna-table-data").show();
                }

                // Load Summary Table

                var summary_table = $("#na_summary_table tbody");
                summary_table.html("");

                var body = "<tr>";
                $.each(data.summary, function(k, v) {
                    if (k == "totalbytes" || k == "totalpackets" || k == "totalflows") {
                        body += "<td>" + human_readable_size(v, k) + "</td>";
                    }
                });
                body += "</tr>";

                summary_table.append(body);

                // Sort the data by bytes!
                method = function(a,b) { return b["bytes"] - a["bytes"]; };
                data.records.sort(method);

                // Load Data Table (only 10 rows)
                var data_table = $("#na_data_table tbody");
                var display = ["srcip", "dstip", "bytes", "bps"];

                var body = "";
                var i = 0;
                $.each(data.records, function(k, record) { 
                    if (i < 10) {

                        var class1 = record.dstip.split(".").join("").trim();
                        var class2 = record.srcip.split(".").join("").trim();

                        body += "<tr class=\"brief-data-row " + class1 + " " + class2 + "\">";
                        $.each(record, function(k, v) {
                            if ($.inArray(k, display) != -1) {
                                if (k == "srcip" || k == "dstip") {
                                    if (v.trim() == host_address) { v = "<span style=\"border-bottom: 1px dotted #000; cursor: help;\" title=\"Ip Address of ' . $host_name . '\">" + v + "</span>"; }
                                    else { v = "<span class=\"hl\">" + v + "<span>"; }
                                }
                                body += "<td>" + human_readable_size(v, k) + "</td>";
                            }
                        })
                        body += "</tr>";
                    }
                    i++;
                });
                
                data_table.html("");
                data_table.append(body);

                if (i > 10) {

                    $("#data_table_info").html("Showing 10 of " + i + " records.");

                }

            }, "json");

        }

        $(document).ready(function() {
            var locationObj = window.location;
                if (locationObj.hash == "#tab-custom-nagiosna") {

                    load_nagiosna_anon_query();
                    do_make_chord_full();
                }
                var tabContainers = $("#tabs > div");
                $("#tabs ul.tabnavigation a").click(function () {
                    if (this.hash == "#tab-custom-nagiosna"){
                        load_nagiosna_anon_query();
                        do_make_chord_full();
                    }
                    return false;
                });
            

            $("#nna_server").change(function() {
                // Load the tables for the query based on server
                load_nagiosna_anon_query();
            });
        
            $(".fullscreen-button").click(function() {
                if (nna_fullscreen) {
                    resize_to_normal(this);
                    nna_fullscreen = false;
                } else {
                    resize_to_fullscreen(this);
                    nna_fullscreen = true;
                }
            });
        });

        function get_nna_url(address, https)
        {
            var secure = "";
            if (https == "1") {
                secure = "s";
            }

            return "http" + secure + "://" + address + "/nagiosna/index.php";
        }

        function human_readable_size(size, bytes) {
            var gb = 1073741824; // bytes
            var mb = 1048576;
            var kb = 1024;

            if (bytes == "bytes" || bytes == "totalbytes") { 
                if (size > gb) {
                    return (size / gb).toFixed(2) + " GB";
                } else if (size > mb) {
                    return (size / mb).toFixed(2) + " MB";
                } else if (size > kb) {
                    return (size / kb).toFixed(2) + " kB";
                } else {
                     return Math.round(size) + " B";
                }
            } else {
                return size;
            }
        }

        function do_make_chord_full()
        {
            // Ceate the NNA url
            var server = $("#nna_server option:selected").val();
            server = server.split("|");
            var nna_url = get_nna_url(server[0], server[1]);

            var rawquery = encodeURIComponent("src ip " + host_address + " or dst ip " + host_address);

            var qdata = {
                token: server[2],
                "q[begindate]": "-24 hours",
                "gid": 1,
                "q[rawquery]": rawquery,
                "q[aggregate_csv]": "srcip,dstip"
            }

            $.get(nna_url + "/api/queries/queryviz", qdata, function(data) {

                var url = nna_url + data.apiurl + "?" + data.apiquery + data.chordquery;
                make_chord(560, 640, "Relational Mapping", url);

            }, "json");
        }

        function make_chord(h, w, title, url)
        {

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

            d3.json(url, function(imports) {
            
              if(imports.names.length == 1 && imports.names[0] == "Other") {
                  $("#relationaltarget").children(".reportviz-throbber").hide();
                  $("#relationaltarget").html("<p style=\'margin-top:40px;\' class=\'text-center\'>' . _("No Data") . '</p>");
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
              labels.forEach(function(d) {
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
                  .on("mouseenter", function() { $(this).css("font-weight", "bold"); highlight_table($(this).text()); })
                  .on("mouseleave", function() { $(this).css("font-weight", "normal"); clear_highlight_table(); })
                  .on("mouseout", fade(.80));

              g.append("svg:path")
                  .style("stroke", function(d) { return fill(d.index); })
                  .style("fill", function(d) { return fill(d.index); })
                  .attr("d", arc);

              g.append("svg:text")
                  .each(function(d) { d.angle = (d.startAngle + d.endAngle) / 2; })
                  .attr("dy", ".35em")
                  .attr("text-anchor", function(d) { return d.angle > Math.PI ? "end" : null; })
                  .attr("transform", function(d) {
                    return "rotate(" + (d.angle * 180 / Math.PI - 90) + ")"
                        + "translate(" + (r0 + 26) + ")"
                        + (d.angle > Math.PI ? "rotate(180)" : "");
                  })
                  .text(function(d) { return nameByIndex[d.index]; })
                  .style(function(d) { return "color", "red" });

              svg.selectAll("path.chord")
                  .data(chord.chords)
                  .enter().append("svg:path")
                  .attr("class", "chord")
                  .style("stroke", function(d) { return d3.rgb(fill(d.source.index)).darker(); })
                  .style("fill", function(d) { return fill(d.source.index); })
                  .attr("d", d3.svg.chord().radius(r0));
              
              $("#relationaltarget").children(".reportviz-throbber").hide();

            });

            // Returns an event handler for fading a given chord group.
            function fade(opacity) {
              return function(d, i) {
                svg.selectAll("path.chord")
                    .filter(function(d) { return d.source.index != i && d.target.index != i; })
                    .transition()
                    .style("stroke-opacity", opacity)
                    .style("fill-opacity", opacity);
              };
            }
        }

        function resize_to_fullscreen(node) {
            var div_id = $(node).attr("data");
            var w = $(window).width();
            var h = $(window).height();
            $(div_id).addClass("front-and-center").width(w - 60).height(h - 60);
            $(div_id).addClass("well").css({ "z-index": 10000 });
            
            $(div_id).offset({
                top: 10,
                left: 10
            });
            
            //$(div_id).prepend("<span id=\'graph-title\'><a id=\'make-normal-size\' onclick=\'resize_to_normal(\"" + div_id + "\")\' class=\'btn\'><i class=\'icon-resize-small\'></i></a>&nbsp;' . _("Relational Mapping") . '</span>");
        }

        function resize_to_normal(node) {
            var div_id = $(node).attr("data");
            $(div_id).css({ top:0, left:0, "z-index": 1 })
            .removeClass("front-and-center")
            .removeClass("well")
            .width("").height("")
            .children("span#graph-title").each(function(i,d) { $(d).remove(); });
        }

        // Highlight the address
        function highlight_table(ip_address)
        {
            clear_highlight_table();
            var class1 = ip_address.split(".").join("").trim();
            $("." + class1 + " td").css("background-color", "#EEE");
            $("." + class1 + " td span.hl").css("font-weight", "bold");
        }
        function clear_highlight_table()
        {
            $(".brief-data-row td").css("background-color", "transparent")
            $(".brief-data-row td span.hl").css("font-weight", "normal");
        }
        </script>';

        $content .= "<div style='float: left; margin-right: 40px; cursor: pointer;' id='nna-chord-diagram' class='fullscreen-button' data='#relationaltarget'>
                        <div class='thumbnail' style='height: 640px; width: 640px; border: none; box-shadow: none;'>
                            <div id='relationaltarget' class='chartrender'>
                                <div class='reportviz-throbber'>
                                    <div class='valign-throbber centerme'>
                                        <i class='fa fa-spinner fa-spin'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>";

        $content .= '<div id="nna-table-data" style="float: left;">
                    <h3>' . _("Summary Data") . '</h3>
                    <table class="table table-striped table-bordered" id="na_summary_table">
                        <thead>
                            <tr>
                                <th>' . _("Total Flows") . '</th>
                                <th>' . _("Total Bytes") . '</th>
                                <th>' . _("Total Packets") . '</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>';

        $content .= '<h3>' . _("Brief Data Table (10 Records, Bytes Only)") . '</h3>
                    <table class="table table-bordered table-striped" id="na_data_table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">' . _("Source IP") . '</th>
                                <th style="width: 100px;">' . _("Destination IP") . '</th>
                                <th style="width: 80px;">' . _("Bytes") . '</th>
                                <th style="width: 60px;">' . _("Bytes/Sec") . '</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <p><span id="data_table_info"></span> <a class="nna_link" href="" target="_blank">See all records and details in Network Analyzer</a></p>
                    </div><div style="clear:both;"></div>';

    }

    $content .= '<p style="font-weight: bold; display: none;" id="nna-no-data-message">' . _("There is no data in your Network Analyzer instance for this Host/Service.") . '</p>';

    // Add new tab to Status pages
    $newtab = array(
        "id" => "nagiosna",
        "title" => _("Network Traffic Analysis"),
        "content" => $content,
        "icon" => '<i class="fa fa-signal fa-14"></i>',
    );
    $cbdata["tabs"][] = $newtab;
}


///////////////////////////////////////////////////////////////////////////////////////////
//CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function nagiosna_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    global $nagiosna_component_name;

    // Initialize return code and output
    $result = 0;
    $output = "";

    switch ($mode) {

        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:
            $instance_id = 0;

            // Get all instances
            $saved_instances = get_option("nagiosna_component_instances");
            if (!empty($saved_instances)) {
                $saved_instances = unserialize($saved_instances);
            }

            // Initial values (if they are saved or not)
            $instances = grab_array_var($inargs, "instances", $saved_instances);
            $nna_disable_tabs = grab_array_var($inargs, "nna_disable_tabs", get_option("nna_disable_tabs", 0));
            $component_url = get_component_url_base($nagiosna_component_name);

            $output = '';
            $output .= '
    <h5 class="ul">' . _("Component Settings") . '</h5>
    <p>' . _("These are all the general settings for this component") . '.</p>

    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td></td>
            <td class="checkbox">
                <label>
                    <input id="nna_disable_tabs" name="nna_disable_tabs" type="checkbox" value="1" ' . is_checked($nna_disable_tabs, 1) . '>
                    ' . _("Disable Host/Service Tabs from being shown") . '
                </label>
            </td>
        </tr>
    </table>

    <h5 class="ul">' . _("Nagios Network Analyzer Servers") . '</h5>
    
    <p>' . _("Specify the addresses and a users API Key for each of the Nagios Network Analyzer servers. These servers will be shown in the reports section as servers to run reports on and are also allowed to send service and host directives to this Nagios XI box for dynamic host/service creation with passive checks from inside the Nagios Network Analyzer server.") . '</p>
    
    <div style="margin: 15px 0;">
        <a href="#" class="add_server_button">' . _("Add a Server") . '</a>
    </div>

    <table class="table table-bordered table-striped table-auto-width" id="nna_servers">
        <thead>
            <tr><th colspan="99">' . _("Nagios Network Analyzer Servers") . '</th></tr>
        </thead>
        <tbody>';

            foreach ($instances as $instance) {

                $checked = "";
                if (!empty($instance['enabled'])) {
                    $checked = " checked";
                }

                $secure = "";
                if (!empty($instance['secure'])) {
                    $secure = " checked";
                }

                $output .= '
        <tr>
            <td>' . _("Name") . ': <input type="text" class="form-control" name="instances[' . $instance_id . '][name]" value="' . $instance['name'] . '"></td>
            <td>' . _("IP Address / Hostname") . ': <input type="text" class="form-control" name="instances[' . $instance_id . '][address]" value="' . $instance['address'] . '"></td>
            <td>' . _("API Key") . ': <input type="text" class="form-control" name="instances[' . $instance_id . '][api_key]" value="' . $instance['api_key'] . '" style="width: 220px;"></td>
            <td class="checkbox">
                <label><input type="checkbox" value="1" name="instances[' . $instance_id . '][secure]" ' . $secure . '> ' . _("Use SSL") . '</label>
            </td>
            <td class="checkbox">
                <label><input type="checkbox" value="1" name="instances[' . $instance_id . '][enabled]" ' . $checked . '> ' . _("Allow this host to use Dynamic Integration") . '</label>
            </td>
            <td><a href="#" class="remove_server_button">' . _("Remove") . '</a></td>
        </tr>
        ';

                $instance_id++;
            }


            $output .= '
        </tbody>
    </table>

    <script type="text/javascript">
    $(document).ready(function() {

        var instance_id = ' . $instance_id . ';

        nna_bind_server_buttons();

        $(".add_server_button").click(function() {
            var server_html = \'<tr> \
                            <td>' . _("Name") . ': <input type="text" name="instances[\' + instance_id + \'][name]"></td> \
                            <td>' . _("IP Address / Hostname") . ': <input type="text" name="instances[\' + instance_id + \'][address]"></td> \
                            <td>' . _("API Key") . ': <input type="text" name="instances[\' + instance_id + \'][api_key]" style="width: 220px;"></td> \
                            <td> \
                                <label><input type="checkbox" value="1" name="instances[\' + instance_id + \'][secure]"> ' . _("Use SSL") . '</label> \
                            </td> \
                            <td> \
                                <label><input type="checkbox" value="1" name="instances[\' + instance_id + \'][enabled]"> ' . _("Allow this host to use Dynamic Integration") . '</label> \
                            </td> \
                            <td><a href="#" class="remove_server_button">' . _("Remove") . '</a></td> \
                        </tr>\';
            instance_id++;
            $("#nna_servers").append(server_html);
            nna_bind_server_buttons();
        });

    });

    function nna_bind_server_buttons()
    {
        $(".remove_server_button").unbind("click");
        $(".remove_server_button").click(function() {
            $(this).closest("tr").remove();
        });
    }
    </script>

    ';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // Get variables
            $instances = grab_array_var($inargs, "instances", array());
            $nna_disable_tabs = grab_array_var($inargs, "nna_disable_tabs", 0);

            // Validate variables
            $errors = 0;
            $errmsg = array();

            // Check for empty instances
            $saveable_instances = array();
            foreach ($instances as $instance) {
                if (empty($instance['name']) || empty($instance['address']) || empty($instance['api_key'])) {
                    $errors++;
                    $errmsg = _("You must enter a name, address, and api key for each server.");
                } else {
                    if (!isset($instance['secure'])) {
                        $instance['secure'] = 0;
                    }
                    $saveable_instances[] = $instance;
                }
            }

            // Save settings
            set_option("nagiosna_component_instances", serialize($saveable_instances));
            set_option("nna_disable_tabs", intval($nna_disable_tabs));

            // Handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // Info messages
            $okmsg = array();
            $okmsg[] = _("Settings updated.");
            $outargs[COMPONENT_INFO_MESSAGES] = $okmsg;
            break;

        default:
            break;
    }

    return $output;
}

// Function to add menu items
function nagiosna_component_addmenu($arg = null)
{
    global $nagiosna_component_name;

    // Retrieve the URL for the component
    $urlbase = get_component_url_base($nagiosna_component_name);

    // Get event log report
    $mi = find_menu_item(MENU_REPORTS, "menu-reports-nagiosxi-eventlog", "id");
    if ($mi == null) {
        return;
    }

    $order = grab_array_var($mi, "order", "");
    if (empty($order)) {
        return;
    }

    $neworder = $order + 0.2; // Below capacity planning...

    // Add the Report button
    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Network Report"),
        "id" => "menu-reports-nna-reports",
        "order" => $neworder,
        "opts" => array("href" => $urlbase . "/nagiosna-reports.php")
    ));

    $neworder = $neworder + 0.2; // Below nna reports...

    // Add the Queries button
    add_menu_item(MENU_REPORTS, array(
        "type" => "link",
        "title" => _("Network Query"),
        "id" => "menu-reports-nna-queries",
        "order" => $neworder,
        "opts" => array("href" => $urlbase . "/nagiosna-queries.php")
    ));

}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

// Check to make sure the proper version of nagios xi is installed
function nagiosna_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;

    //requires greater than 20011R3.2
    if (get_product_release() < 208)
        return false;

    return true;
}

// Check to see if there are any network analyzer servers listed
function has_nna_servers()
{
    $serialized_instances = get_option("nagiosna_component_instances");
    if (empty($serialized_instances)) {
        return false;
    } else {
        $instances = unserialize($serialized_instances);
        if (count($instances) > 0) {
            return true;
        }
    }
    return false;
}

// Get the very first server's info
function nna_get_first_data()
{
    $data = array("server" => "",
        "object" => "",
        "object_name" => "");

    if (has_nna_servers()) {
        $instances = unserialize(get_option("nagiosna_component_instances"));
        $i = current($instances);
        $data = array();
        $data['server'] = $i['address'] . "|" . $i['secure'] . "|" . $i['api_key'];

        // Get the first source
        $s = "http";
        if ($i['secure']) {
            $s = 'https';
        }
        $sources = json_decode(file_get_contents($s . "://" . $i['address'] . "/nagiosna/index.php/api/sources/read?token=" . $i['api_key']));

        if (count($sources) >= 1 && !isset($sources->error)) {
            $source = current($sources);

            $data['object'] = $source->sid;
            $data['object_name'] = $source->name;
            return $data;
        }
    }

    return $data;
}

// Display NNA server's friendly name based on value
function get_nna_server_name($server)
{
    $instances = unserialize(get_option("nagiosna_component_instances"));
    list($address, $sercure, $api_key) = explode("|", $server);
    foreach ($instances as $i) {
        if ($address == $i['address'] && $api_key == $i['api_key']) {
            return $i['name'] . ' (' . $i['address'] . ')';
        }
    }
    return "";
}

// Display some information about integrating servers for report/queries
function display_nna_no_servers($return = false)
{
    $out = '
	
<div style="float: left; margin-right: 25px; width: 500px;">

<div>
<h2>' . _('Network Analyzer Server Integration Required') . '</h2>
<p>' . _('In order to run reports or queries from your Nagios Network Analyzer servers, you\'ll need to add them in the ') . '<a href="/nagiosxi/admin/components.php?config=nagiosna">Nagios Network Analyzer ' . _('configuration menu') . '</a>.</br>
' . _('To add servers, you\'ll need the IP address and an API Key for the server. We recommend creating a specific user and use that API Key for Nagios XI integration.') . '</p>


<div class="bluebutton" style="width: 300px;">
<a href="/nagiosxi/admin/components.php?config=nagiosna" target="_new">' . _("Configure Network Analyzer Integration") . '</a>
</div>
</div>


<div>
<h2>' . _('Don\'t Have Nagios Network Analyzer?') . '</h2>

<p>' . _("Nagios Network Analyzer is an enterprise-grade tool for monitoring network activity and traffic using NetFlow and sFlow.  Learn more by visiting") . ' <a href="http://go.nagios.com/networkanalyzer?utm_source=Nagios%20XI&utm_medium=Text%20Link&utm_content=Network%20Analyzer&utm_campaign=Cross-Promotion" target="_new">go.nagios.com/networkanalyzer</a>
</p>
<p><strong>' . _('Free 60-day trial available.') . '</strong></p>

<div class="bluebutton" style="width: 300px;">
<a href="http://go.nagios.com/networkanalyzer?utm_source=Nagios%20XI&utm_medium=Text%20Link&utm_content=Network%20Analyzer&utm_campaign=Cross-Promotion" target="_new">' . _("Learn More About Network Analyzer") . '</a>
</div>
</div>
</div>

<div style="float: left;">

<h3>' . _('Learn More About Nagios Network Analyzer Integration') . '</h3>
<iframe width="448" height="252" src="https://go.nagios.com/networkanalyzer-component-embed" frameborder="0" allowfullscreen></iframe> 
</div>

</p>

';
    if ($return) {
        return $out;
    } else {
        echo $out;
        return "";
    }
}

// Display a dropdown list of servers available
function display_nna_servers($return = false, $server = '')
{
    $instances = unserialize(get_option("nagiosna_component_instances"));

    $out = '<select id="nna_server" name="nna_server" class="form-control">';

    $server_address = '';
    if (!empty($server)) {
        $server = explode("|", $server);
        $server_address = $server[0];
    }

    foreach ($instances as $instance) {
        $selected = '';
        if ($server_address == $instance['address']) {
            $selected = 'selected';
        }
        $out .= '<option value="' . $instance['address'] . '|' . $instance['secure'] . '|' . $instance['api_key'] . '" ' . $selected . '>' . $instance['name'] . ' (' . $instance['address'] . ')</option>';
    }

    $out .= '</select>';

    if ($return) {
        return $out;
    } else {
        echo $out;
        return "";
    }
}

// Get a readable header for reports/queries
function nna_human_readable_header($title, $report=null, $dn=false)
{
    switch ($title) {
        case 'ts':
            return _("Start Date");

        case 'start':
            return _("Flow Start");

        case 'te':
            return _("End Date");

        case 'end':
            return _("Flow End");

        case 'td':
            return _("Duration");

        case 'duration':
            return _("Duration");

        case 'pr':
            return _("Protocol");

        case 'val':
            switch ($report->toptype) {
                case 'srcip':
                    if ($dn) {
                        return _("Source Hostname");
                    }
                    return _("Source IP");

                case 'dstip':
                    if ($dn) {
                        return _("Destination Hostname");
                    }
                    return _("Destination IP");

                case 'srcport':
                    return _("Source Port");

                case 'dstport':
                    return _("Destination Port");
            }
            break;

        case 'fl':
            return _("Flows");

        case 'flP':
            return _("Flow %");

        case 'ipkt':
            return _("Packets");

        case 'ipktP':
            return _("Packet %");

        case 'ibyt':
            return _("Bytes");

        case 'ibytP':
            return _("Byte %");

        case 'ipps':
        case 'pps';
            return _("Packets/Sec");

        case 'ipbs':
        case 'pbs':
            return _("Bits/Sec");

        case 'ibpp':
        case 'bpp':
            return _("Bits/Packet");

        case 'bytes':
            return _("Bytes");

        case 'flows':
            return _("Flows");

        case 'packets':
            return _("Packets");

        case 'srcip':
            if ($dn) {
                return _("Source Hostname");
            }
            return _("Source IP");

        case 'srcport':
            return _("Source Port");

        case 'dstip':
            if ($dn) {
                return _("Destination Hostname");
            }
            return _("Destination IP");

        case 'dstport':
            return _("Destination Port");

        case 'Bpp':
            return _("Bytes/Packet");

        case 'bps':
            return _("Bytes/Sec");

        case 'totalflows':
            return _("Total Flows");

        case 'totalbytes':
            return _("Total Bytes");

        case 'totalpackets':
            return _("Total Packets");

        case 'avgbps':
            return _("Average Bytes/Sec");

        case 'avgpps':
            return _("Average Packets/Sec");

        case 'avgbpp':
            return _("Average Bytes/Packet");
        
        default:
            return " ";
    }
}

// Create a readable timeframe for reports
function nna_human_readable_timeframe($report)
{
    if ($report->begindate == "-24 hours") {
        return _("Last 24 Hours");
    } else if ($report->begindate == "-48 hours") {
        return _("Last 48 Hours");
    } else if ($report->begindate == "-1 week") {
        return _("Last Week");
    } else if ($report->begindate == "-1 month") {
        return _("Last Month");
    } else if ($report->enddate == "-1 second") {
        return $report->begindate;
    } else {
        $start = strtotime($report->begindate);
        $end = strtotime($report->enddate);
        return get_datetime_string($start, DT_SHORT_DATE_TIME, DF_AUTO, "null") . " to " . get_datetime_string($end, DT_SHORT_DATE_TIME, DF_AUTO, "null");
    }
    return "";
}

// Display a readable value
function nna_human_readable_value($value, $type)
{
    if ($type == "bytes" || $type == "totalbytes") {
        $size = $value;
        $base = log($size) / log(1024);
        $suffixes = array('B', 'kB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), 2) . " " . $suffixes[floor($base)];
    }
    return $value;
}