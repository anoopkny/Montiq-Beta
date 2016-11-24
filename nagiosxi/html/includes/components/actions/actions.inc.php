<?php
//
// Actions Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$actions_component_name = "actions";
actions_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function actions_component_init()
{
    global $actions_component_name;
    $versionok = actions_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<br><b>Error: This component requires Nagios XI 2009R1.8 or later.</b>";
    }

    $args = array(
        COMPONENT_NAME => $actions_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Adds custom actions to hosts and services.") . $desc,
        COMPONENT_TITLE => _("Actions"),
        COMPONENT_VERSION => '2.0.0',
        COMPONENT_CONFIGFUNCTION => "actions_component_config_func",
        COMPONENT_REQUIRES_VERSION => 520
    );

    register_component($actions_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_HOST_DETAIL_ACTION_LINK, 'actions_component_host_detail_action');
        register_callback(CALLBACK_SERVICE_DETAIL_ACTION_LINK, 'actions_component_service_detail_action');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// VERSION CHECK FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function actions_component_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }
    if (get_product_release() < 500) {
        return false;
    }
    return true;
}


///////////////////////////////////////////////////////////////////////////////////////////
// CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function actions_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    $result = 0;
    $output = "";

    $component_name = "actions";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // Default settings
            $settings_default = array(
                "enabled" => 1,
                "actions" => array(),
            );
            $settings_default["actions"][] = array(
                "enabled" => 0,
                "type" => "host",
                "action_type" => "url",
                "target" => "_blank",
                "host" => "/.*/",
                "service" => "",
                "hostgroup" => "",
                "servicegroup" => "",
                "code" => "",
                "url" => "http://www.google.com/search?q=%host%",
                "text" => "Search for host on Google",
            );
            $settings_default["actions"][] = array(
                "enabled" => 0,
                "type" => "service",
                "action_type" => "url",
                "target" => "_blank",
                "host" => "/.*/",
                "service" => "/.*/",
                "hostgroup" => "",
                "servicegroup" => "",
                "code" => "",
                "url" => "http://www.google.com/search?q=%host%+%service%",
                "text" => "Search for service on Google",
            );

            // Saved settings
            $settings_raw = get_option("actions_component_options");
            if ($settings_raw != "") {
                $settings_default = unserialize($settings_raw);
            }

            // Settings passed to us
            $settings = grab_array_var($inargs, "settings", $settings_default);


            // Checkboxes
            $enabled = checkbox_binary(grab_array_var($settings, "enabled", ""));
            foreach ($settings["actions"] as $x => $sa) {
                $settings["actions"][$x]["enabled"] = checkbox_binary(grab_array_var($sa, "enabled", ""));
            }

            // Trim empty lines
            foreach ($settings["actions"] as $x => $sa) {
                if ($sa["host"] == "" && $sa["service"] == "" && $sa["url"] == "") {
                    unset($settings["actions"][$x]);
                }
            }

            // Re-number items
            $settings_new = array();
            $y = 0;
            foreach ($settings["actions"] as $x => $sa) {
                $settings_new[$y++] = $sa;
            }
            $settings["actions"] = $settings_new;

            // Get hostgroups
            $hostgroups = array();
            $args = array(
                "orderby" => "hostgroup_name:a",
            );
            $xml = get_xml_hostgroup_objects($args);
            if ($xml) {
                foreach ($xml->hostgroup as $hg) {
                    $hgname = strval($hg->hostgroup_name);
                    $hgalias = strval($hg->alias);
                    $hostgroups[$hgname] = array(
                        "name" => $hgname,
                        "alias" => $hgalias,
                    );
                }
            }

            // Get servicegroups
            $servicegroups = array();
            $args = array(
                "orderby" => "servicegroup_name:a",
            );
            $xml = get_xml_servicegroup_objects($args);
            if ($xml) {
                foreach ($xml->servicegroup as $sg) {
                    $sgname = strval($sg->servicegroup_name);
                    $sgalias = strval($sg->alias);
                    $servicegroups[$sgname] = array(
                        "name" => $sgname,
                        "alias" => $sgalias,
                    );
                }
            }

            $component_url = get_component_url_base($component_name);

            $rec_actions = array();
            foreach ($settings["actions"] as $x => $sa) {
                if ($sa['action_type'] == "rec") { 
                    $rec_actions["action-$x"] = $sa;
                }
            }

            // Get all users for the permissions section
            $sql = "SELECT * FROM xi_users;";
            $rs = exec_sql_query(DB_NAGIOSXI, $sql);
            foreach ($rs as $user) {
                $users[] = array('name' => $user['name'],
                                 'username' => $user['username'],
                                 'id' => $user['user_id'],
                                 'level' => get_user_meta($user['user_id'], "userlevel"),
                                 'enabled' => $user['enabled']);
            }

            $output = '
                <style>
                .rec { display: none; }
                table.standardtable table.no-bg tr { background-color: transparent; }
                .rec-context-box .required div, .rec-context-box .context div { margin-top: 5px; }
                .rec-context-box .required, .rec-context-box .context { margin-bottom: 10px; }
                .rec-context-box .context img { margin-left: 5px; }
                .rec-context-box .context img:hover { cursor: pointer; }
                </style>

                <script type="text/javascript">
                var rec_component_base = base_url + "/includes/components/reactoreventhandler/";
                var api_url = rec_component_base + "api.php";
                var CHAINS;
                var SAVED_ACTIONS = '.json_encode($rec_actions).';

                function make_option(key, chain, selected) {
                    return "<option value=\'" + key + "\' " + selected + ">" + chain.name + "</option>";
                }

                // Replace context variables if they are obvious!
                function make_context_var(varname, vardefault)
                {
                    if (varname == "host" || varname == "hostname" || varname == "H") {
                        return "%host%";
                    } else if (varname == "hostaddress" || varname == "address" || varname == "ipaddress") {
                        return "%hostaddress%";
                    } else if (varname == "hoststate") {
                        return "%hoststate%";
                    } else if (varname == "service" || varname == "servicename" || varname == "srv" || varname == "S") {
                        return "%service%";
                    } else if (varname == "servicestate") {
                        return "%servicestate%";
                    } else if (varname == "hoststatetype") {
                        return "%hoststatetype%";
                    } else if (varname == "servicestatetype") {
                        return "%servicestatetype%";
                    } else {
                        return vardefault;
                    }
                }

                function load_reactor_instances(selector) {
                    var instance_id = selector.closest("tr.rec-instances-box").data("instanceid");
                    $.getJSON(api_url, { mode: "read_instances" }, function(data) {
                        if (data.length > 0) {
                            selector.html("<option></option>");
                            $.each(data, function(k, value) {
                                var selected = "";
                                if (instance_id == value.id) { selected = " selected"; }
                                selector.append("<option value=\'" + value.id + "\' " + selected + ">" + value.name + "</option>");
                            });
                        } else {
                            selector.attr("disabled", true);
                            selector.html("<option>'._("No Instances Available").'</option>");
                        }

                        // If instance_id exists, trigger a chain update
                        if (instance_id) {
                            selector.trigger("change");
                        }
                    });
                }

                function load_reactor_chains(instance, selector) {
                    var chain_id = selector.closest("tr.rec-chains-box").data("chainid");
                    $.getJSON(api_url, { mode: "read_reactor_chains", instance_id: instance }, function(data) {
                        CHAINS = data.reactorresult.chains.chain;
                        var chain_list = "<option></option>";
                        if (CHAINS.length > 0) {
                            $.each(CHAINS, function(key, chain) {
                                var selected = "";
                                if (chain.active == "1") {
                                    if (chain_id == chain["@attributes"]["id"]) { selected = "selected"; }
                                    chain_list += make_option(chain["@attributes"]["id"], chain, selected);
                                }
                            });
                        } else {
                            chain = CHAINS;
                            if (chain.active == "1") {
                                var selected = "";
                                if (chain_id = chain["@attributes"]["id"]) { selected = "selected"; }
                                chain_list += make_option(chain["@attributes"]["id"], chain, selected);
                            }
                        }
                        selector.html(chain_list);

                        // If chain_id exists then trigger context variable update
                        if (chain_id) {
                            selector.trigger("change");
                        }
                    });
                }

                function force_rec_displays() {
                    $(".action_type").trigger("change");
                }

                $(document).ready(function() {';

                $output .= '
                    // Select action type change
                    $(".action_type").change(function() {
                        var type = $(this).val();
                        if (type == "rec") {
                            $(this).closest("table").find("tr.url-box").hide();
                            $(this).closest("table").find("tr.url-target").hide();
                            $(this).closest("table").find("tr.rec-instances-box").show();
                            if ($(this).closest("table").find(".action_text").val() == "'._("Search for a service on Google").'") {
                                $(this).closest("table").find(".action_text").val("");
                            }
                            load_reactor_instances($(this).closest("table").find(".rec-instances"));
                        } else if (type == "command") {
                            $(this).closest("table").find("tr.url-target").hide();
                            $(this).closest("table").find("tr.url-box").show();
                            $(this).closest("table").find("tr.rec-instances-box").hide();
                        } else {
                            $(this).closest("table").find("tr.url-box").show();
                            $(this).closest("table").find("tr.url-target").show();
                            $(this).closest("table").find("tr.rec").hide();
                            if ($(this).closest("table").find(".action_text").val() == "") {
                                $(this).closest("table").find(".action_text").val("'._("Search for a service on Google").'");
                            }
                        }
                    });

                    // When selecting a reactor instance from the list get the chains
                    $(".rec-instances").change(function() {
                        var instance = $(this).val();
                        $(this).closest("table").find("tr.rec-chains-box").show();
                        load_reactor_chains(instance, $(this).closest("table").find("select.rec-chains"));
                    });

                    // Changing permission types
                    $(".perm-type").change(function() {
                        if ($(this).val() == "c") {
                            $(this).parent().find(".perm-users").show();
                        } else {
                            $(this).parent().find(".perm-users").hide();
                        }
                    });

                    // When selecting a particular chain
                    $(".rec-chains").change(function() {
                        var chain_id = $(this).val();
                        var action_id = $(this).closest("table").find(".rec-context-box").data("actionid");
                        if (chain_id) {
                            if (CHAINS.length > 0) {
                                $.each(CHAINS, function(k, v) {
                                    if (v["@attributes"]["id"] == chain_id) {
                                        chain = v;
                                    }
                                });
                            } else {
                                chain = CHAINS;
                            }

                            $(this).closest("table").find("tr.rec-context-box").show();

                            // Check if there is a saved action
                            var action = SAVED_ACTIONS["action-" + action_id];

                            // If chain requires any context variables
                            if (chain["required-context"].item) {
                                var context_vars = "<h4>'._("Required Context Variables").'</h4>";
                                var items = chain["required-context"].item;
                                var i = 0;
                                if (items.length > 0) {
                                    // items
                                    $.each(items, function(key, item) {
                                        if (item.default) { 

                                            var display = "";
                                            if (item.default.length > 20) {
                                                display = item.default.substring(0, 20);
                                                display += " ...";
                                            } else {
                                                display = item.default;
                                            }

                                            defaultvar = \' <img src="../includes/components/reactoreventhandler/images/resultset_previous.png" style="vertical-align: middle; cursor: pointer;" class="setdefault" title="'._("Set to default value").'"><input class="default" type="hidden" value="\' + item.default + \'"> '._("Default").': \' + display; 
                                        } else { 
                                            defaultvar = ""; 
                                        }

                                        if (action["rec_req_context_value"]) {
                                            context_value = action["rec_req_context_value"][i];
                                        } else {
                                            context_value = make_context_var(item.varname, item.default);
                                        }

                                        context_vars += \'<div>'._("Key").': <input class="key" type="text" name="settings[actions][\'+action_id+\'][rec_req_context_key][]" value="\' + item.varname + \'" readonly> '. _("Value").': <input class="value" type="text" name="settings[actions][\'+action_id+\'][rec_req_context_value][]" value="\' + context_value + \'">\' + defaultvar + "</div>";

                                        i++;
                                    });
                                } else {
                                    // item
                                    if (items.default) { 

                                            var display = "";
                                            if (items.default.length > 20) {
                                                display = items.default.substring(0, 20);
                                                display += " ...";
                                            } else {
                                                display = items.default;
                                            }

                                            defaultvar = \' <img src="../includes/components/reactoreventhandler/images/resultset_previous.png" style="vertical-align: middle; cursor: pointer;" class="setdefault" title="'._("Set to default value").'"><input class="default" type="hidden" value="\' + items.default + \'"> '._("Default").': \' + display; 
                                        } else { 
                                            defaultvar = ""; 
                                        }

                                        if (action["rec_req_context_value"]) {
                                            context_value = action["rec_req_context_value"][i];
                                        } else {
                                            context_value = items.default;
                                        }

                                    context_vars += \'<div>'._("Key").': <input class="key" type="text" name="settings[actions][\'+action_id+\'][rec_req_context_key][]" value="\' + items.varname + \'" readonly>'._("Value").': <input class="value" type="text" name="settings[actions][\'+action_id+\'][rec_req_context_value][]" value="\' + context_value + \'">\' + defaultvar + "</div>";
                                }
                                $(this).closest("table").find("tr.rec-context-box .required").html(context_vars);
                            } else {
                                $(this).closest("table").find("tr.rec-context-box .required").html("");   
                            }
                        } else {
                            $(this).closest("table").find("tr.rec-context-box").hide();
                        }

                        // Add non-required context variables to the list if we have them 
                        if (action["rec_context_key"]) {
                            var fields = $(this).closest("table").find("tr.rec-context-box .context-fields");
                            if (action["rec_chain"] != chain_id) {
                                fields.html("");
                            } else {
                                $.each(action["rec_context_key"], function(i, key) {
                                    fields.append(\'<div>'._("Key").': <input class="key" type="text" name="settings[actions][\'+action_id+\'][rec_context_key][]" value="\' + key + \'"> '._("Value").': <input class="value" type="text" name="settings[actions][\'+action_id+\'][rec_context_value][]" value="\' + action["rec_context_value"][i] + \'"> <img class="removefield" src="../includes/components/reactoreventhandler/images/textfield_delete.png" title="'._("Remove context variable").'">\');
                                });
                            }
                        }

                    });

                    // Set the default buttons to work
                    $(".rec-context-box").on("click", ".setdefault", function() {
                        var default_val = $(this).parent().find(".default").val();
                        $(this).parent("div").find("input.value").val(default_val);
                    });

                    // Add additional context fields
                    $(".add-additional").click(function() {
                        var action_id = $(this).closest("table").find(".rec-context-box").data("actionid");
                        $(this).closest("div").find(".context-fields").append(\'<div>'._("Key").': <input class="key" type="text" name="settings[actions][\'+action_id+\'][rec_context_key][]" value=""> '._("Value").': <input class="value" type="text" name="settings[actions][\'+action_id+\'][rec_context_value][]" value=""> <img class="removefield" src="../includes/components/reactoreventhandler/images/textfield_delete.png" title="'._("Remove context variable").'">\');
                    });

                    // Remove context variables
                    $(".rec-context-box").on("click", ".removefield", function() {
                        $(this).parent("div").remove();
                    });

                    ';

             $output .= '
                    force_rec_displays();
                    ';

            $output .= '
                });
                </script>

                <h5 class="ul">'._("Actions Enabled").'</h5>
                
                <table class="table table-no-border table-condesnsed table-auto-width">
                    <tr>
                        <td></td>
                        <td class="checkbox">
                            <label>
                                <input type="checkbox" class="checkbox" id="enabled" name="settings[enabled]" ' . is_checked($enabled, 1) . ' style="vertical-align: top;">
                            '._("Enable custom actions in Nagios XI").'
                            </label>
                        </td>
                    </tr>
                </table>';

            $output .= '
                <h5 class="ul">'._("Actions").'</h5>
                <p><strong>'._("Notes").'</strong>:</p>

                <ul>
                    <li>'._("The <i>Host</i> and <i>Service</i> fields are regular expression patterns passed to preg_match().  A link will only be displayed for hosts and services that match the expressions specified.").'</li>
                    <li>'._("The <i>URL/Command</i> field can contain macros that are substituted for each host and service.").'</li>
                    <li>'._("The <i>Code</i> field can contain optional PHP code to be evaluated.").'</li>
                    <li>'._("The <i>URL/Command</i>, <i>Code</i>, and <i>Action Text</i> fields can contain variables.").'</li>
                </ul>
                
                <table class="table table-no-border table-condesnsed table-striped">
                    <thead>
                        <tr>
                            <th style="text-align: center;">'._("Enabled").'</th>
                            <th style="text-align: center;">'._("Delete").'</th>
                            <th>'._("Match Criteria").'</th>
                            <th>'._("Action").'</th>
                            <th>'._("Code").'</th>
                            <th>'._("Permissions").'</th>
                        </tr>
                    </thead>
                    <tbody>';

            // Add an empty row at the end ...
            $settings["actions"][] = array(
                "enabled" => 0,
                "type" => "any",
                "host" => "",
                "service" => "",
                "hostgroup" => "",
                "servicegroup" => "",
                "action_type" => "url",
                "url" => "",
                "target" => "_blank",
                "text" => "",
                "code" => "",
            );

            foreach ($settings["actions"] as $x => $sa) {
                $output .= '<tr>';
                $output .= '<td style="text-align: center;">
                                <input type="checkbox" name="settings[actions][' . $x . '][enabled]" ' . is_checked($sa["enabled"], 1) . '>
                            </td>';
                 $output .= '<td style="text-align: center;">
                            <a href="#" onclick="$(this).parent().parent().remove()"><img class="tableMultiButton" src="'.theme_image("cross.png").'" border="0"
                             alt="'._("Delete").'" title="'._("Delete").'"></a>
                             </td>';
                $output .= '<td style="vertical-align: top;">';
                $output .= '<table class="table table-condensed table-no-border table-auto-width table-no-bg">';
                $output .= '<tr>
                                <td>
                                    <label>'._("Object Type").':</label>
                                </td>
                                <td>
                                    <select name="settings[actions][' . $x . '][type]" class="form-control">
                                        <option value="any" ' . is_selected($sa["type"], "any") . '>'._("Any").'</option>
                                        <option value="host" ' . is_selected($sa["type"], "host") . '>'._("Host").'</option>
                                        <option value="service" ' . is_selected($sa["type"], "service") . '>'._("Service").'</option>
                                    </select>
                                </td>
                            </tr>';
                $output .= '<tr>
                                <td>
                                    <label>'._("Host").':</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="settings[actions][' . $x . '][host]" size="10" value="' . encode_form_val($sa["host"]) . '">
                                </td>
                            </tr>';
                $output .= '<tr>
                                <td>
                                    <label>'._("Service").':</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="settings[actions][' . $x . '][service]" size="10" value="' . encode_form_val($sa["service"]) . '">
                                </td>
                            </tr>';
                $output .= '<tr>
                                <td>
                                    <label>'._("Hostgroup").':</label>
                                </td>
                                <td>
                                    <select name="settings[actions][' . $x . '][hostgroup]" class="form-control">
                                        <option value="" ' . is_selected($sa["hostgroup"], "") . '></option>';
                foreach ($hostgroups as $hg) {
                    $output .= '<option value="' . encode_form_val($hg["name"]) . '" ' . is_selected($settings["actions"][$x]["hostgroup"], $hg["name"]) . '>' . encode_form_val($hg["name"]) . '</option>';
                }
                $output .= '    </td>
                            </tr>';
                $output .= '<tr>
                                <td>
                                    <label>'._("Servicegroup").':</label>
                                </td>
                                <td>
                                    <select name="settings[actions][' . $x . '][servicegroup]" class="form-control">
                                        <option value="" ' . is_selected($sa["servicegroup"], "") . '></option>';
                foreach ($servicegroups as $sg) {
                    $output .= '<option value="' . encode_form_val($sg["name"]) . '" ' . is_selected($settings["actions"][$x]["servicegroup"], $sg["name"]) . '>' . encode_form_val($sg["name"]) . '</option>';
                }
                $output .= '    </td>
                            </tr>';
                $output .= '</table>';
                $output .= '</td>';
                $output .= '<td style="vertical-align: top;">';
                $output .= '<table class="table table-condensed table-no-border table-auto-width table-no-bg">';
                $output .= '<tr>
                                <td>
                                    <label>'._("Action Type").':</label>
                                </td>
                                <td>
                                    <select class="action_type form-control" name="settings[actions][' . $x . '][action_type]">
                                        <option value="url" ' . is_selected($sa["action_type"], "url") . '>'._("URL").'</option>
                                        <option value="command" ' . is_selected($sa["action_type"], "command") . '>'._("Command").'</option>';

                                        if (function_exists('reactoreventhandler_component_init')) {
                                            $output .= '<option value="rec"' . is_selected($sa["action_type"], "rec") . '>'._("Reactor Event Chain").'</option>';
                                        }

                $output .= '        </select>
                                </td>
                            </tr>';
                $output .= '<tr class="url-box">
                                <td>
                                    <label>'._("URL / Command").':</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="settings[actions][' . $x . '][url]" value="' . encode_form_val($sa["url"]) . '" size="40">
                                </td>
                            </tr>';
                $output .= '<tr class="url-target">
                                <td>
                                    <label>'._("Target").':</label>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="settings[actions][' . $x . '][target]" value="' . encode_form_val($sa["target"]) . '" size="40" placeholder="_blank">
                                </td>
                            </tr>';
                $output .= '<tr>
                                <td>
                                    <label>'._("Action Text").':</label>
                                </td>
                                <td>
                                    <input class="action_text form-control" type="text" name="settings[actions][' . $x . '][text]" value="' . encode_form_val($sa["text"]) . '" size="40">
                                </td>
                            </tr>';
                $output .= '<tr class="rec rec-instances-box" data-instanceid="' . grab_array_var($sa, 'rec_instance', '') . '">
                                <td><label>'._("Reactor Instance").':</label></td>
                                <td><select class="rec-instances form-control" name="settings[actions][' . $x . '][rec_instance]"></select></td>
                            </tr>';
                $output .= '<tr class="rec rec-chains-box" data-chainid="' . grab_array_var($sa, 'rec_chain', '') . '">
                                <td><label>'._("Reactor Chains").':</label></td>
                                <td><select class="rec-chains form-control" name="settings[actions][' . $x . '][rec_chain]"></select></td>
                            </tr>';

                if (function_exists('reactoreventhandler_component_init')) {
                    $output .= '<tr class="rec rec-context-box" data-actionid="' . $x . '">
                                    <td colspan="2">
                                        <div class="required"></div>
                                        <div class="context">
                                            <h4>'._("Additional Context Variables").' <img src="../includes/components/reactoreventhandler/images/textfield_add.png" title="'._("Add an additional context variable").'" class="add-additional"></h4>
                                            <div class="context-fields">
                                            </div>
                                        </div>
                                    </td>
                                </tr>';
                }

                $output .= '</tbody></table>';
                $output .= '</td>';

                if ($sa["code"] == "") {
                    $sa["code"] = "/*
if ((%objecttype% == 'host' && '%hoststateid%' != '0') || (%objecttype%=='service' && '%servicestateid%'!='0')) {
    \$img = '/nagiosxi/images/schedulecheck.png';
    \$showlink = true;
} else {
    \$showlink = false;
}
*/";
                }

                $output .= '<td style="vertical-align: top;">
                                <textarea class="code form-control fullsize" name="settings[actions][' . $x . '][code]">' . encode_form_val($sa["code"]) . '</textarea>
                            </td>';

                $hide_custom = '';

                $perms = grab_array_var($sa, 'perms', '');
                if ($perms != "c") {
                    $hide_custom = ' hide';
                }

                $output .= '<td class="vt">
                                <select name="settings[actions]['.$x.'][perms]" class="perm-type form-control">
                                    <option value="" '.is_selected($perms, "").'>'._('Everyone').'</option>
                                    <option value="au" '.is_selected($perms, "au").'>'._('Admin & Users (No Read Only)').'</option>
                                    <option value="ao" '.is_selected($perms, "ao").'>'._('Admin Only').'</option>
                                    <option value="c" '.is_selected($perms, "c").'>'._('Custom').'</option>
                                </select>
                                <div class="perm-users'.$hide_custom.'">
                                    <select name="settings[actions]['.$x.'][permusers][]" class="form-control" style="width: 100%; height: 100%; min-height: 135px;" multiple>';

                $permusers = grab_array_var($sa, 'permusers', array());
                foreach ($users as $u) {
                    $a = '';
                    $sel = '';
                    if ($u['level'] == 255) { $a = ' [a]'; }
                    if (in_array($u['id'], $permusers)) { $sel = ' selected'; }
                    $output .= '<option value="'.$u['id'].'"'.$sel.'>'.$u['name'].' ('.$u['username'].')'.$a.'</option>';
                }
                                    
                $output .= '        </select>
                                </div>
                            </td>';
                
                $output .= '</tr>';
            }

            $output .= '</table>';
            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // Get variables
            $settings = grab_array_var($inargs, "settings", array("settings" => array()));

            // Fix checkboxes
            $settings["enabled"] = checkbox_binary(grab_array_var($settings, "enabled", ""));
            foreach ($settings["actions"] as $x => $sa) {
                $settings["actions"][$x]["enabled"] = checkbox_binary(grab_array_var($sa, "enabled", ""));
            }
            $enabled = grab_array_var($settings, "enabled");

            // Trim empty lines
            foreach ($settings["actions"] as $x => $sa) {
                if ($sa["host"] == "" && $sa["service"] == "" && $sa["url"] == "" ) {
                    unset($settings["actions"][$x]);
                }
            }

            // Renumber items & add a UID for each item
            $settings_new = array();
            $y = 0;
            foreach ($settings["actions"] as $x => $sa) {
                $sa["uid"] = random_string(6);
                $settings_new[$y++] = $sa;
            }
            $settings["actions"] = $settings_new;

            // Validate variables
            $errors = 0;
            $errmsg = array();
            if ($enabled == 1) {
                foreach ($settings["actions"] as $x => $sa) {
                    if ($sa["enabled"] == 1) {
                        if ($sa["action_type"] == "url" && $sa["url"] == "") {
                            $errmsg[$errors++] = "No URL specified on line " . ($x + 1);
                        }
                        if (have_value($sa["text"]) == false) {
                            $errmsg[$errors++] = "No action text specified on line " . ($x + 1);
                        }
                    }
                }
            }

            // Handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // Save settings
            set_option("actions_component_options", serialize($settings));
            break;

        default:
            break;
    }

    return $output;
}


///////////////////////////////////////////////////////////////////////////////////////////
// ACTION FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function actions_component_host_detail_action($cbtype, &$cbargs)
{
    // Get our settings
    $settings_raw = get_option("actions_component_options");
    if ($settings_raw == "") {
        $settings = array(
            "enabled" => 0,
        );
    } else {
        $settings = unserialize($settings_raw);
    }

    // Initial values
    $enabled = grab_array_var($settings, "enabled");

    // Bail out if we're not enabled...
    if ($enabled != 1) {
        return;
    }

    // Add the action link
    $hostname = grab_array_var($cbargs, "hostname");
    $host_id = grab_array_var($cbargs, "host_id");
    $hoststatus_xml = grab_array_var($cbargs, "hoststatus_xml");

    // Get variables
    $objectvars = actions_component_get_host_vars($hostname, $host_id, $hoststatus_xml);
    $component_url = get_component_url_base("actions");

    // Find matching actions
    foreach ($settings["actions"] as $x => $sa) {

        if ($sa["enabled"] != 1) {
            continue;
        }

        // Must be 'host' or 'any' type
        if ($sa["type"] != "host" && $sa["type"] != "any") {
            continue;
        }

        // Must match host name
        if ($sa["host"] != "" && preg_match($sa["host"], $hostname) == 0) {
            continue;
        }

        // Must match hostgroup if specified
        if ($sa["hostgroup"] != "" && is_host_member_of_hostgroup($hostname, $sa["hostgroup"]) == false) {
            continue;
        }

        // Must match servicegroup if specified
        if ($sa["servicegroup"] != "" && is_host_member_of_servicegroup($hostname, $sa["servicegroup"]) == false) {
            continue;
        }

        // Must match the user permissions
        if (!empty($sa['perms'])) {
            if ($sa['perms'] == 'au') {
                if (is_readonly_user()) {
                    continue;
                }
            } else if ($sa['perms'] == 'ao') {
                if (!is_admin()) {
                    continue;
                }
            } else if ($sa['perms'] == 'c') {
                $user_id = $_SESSION['user_id'];
                if (!in_array($user_id, $sa['permusers'])) {
                    continue;
                }
            }
        }

        $img = $component_url . "/images/action.png";

        // Run the action type
        if ($sa["action_type"] == "url") {

            $url = $sa["url"];
            $target = $sa["target"];
            $hrefopts = "";

            // Process vars in url
            foreach ($objectvars as $var => $val) {
                $tvar = "%" . $var . "%";
                if ($var != "notesurl" && $var != "actionurl")
                    $url = str_replace($tvar, urlencode($val), $url);
                else
                    $url = str_replace($tvar, urldecode($val), $url);
            }
        } else if ($sa["action_type"] == "command") {
            $url = $component_url . "/runcmd.php?action=" . urlencode($x) . "&uid=" . urlencode($sa["uid"]) . "&host=" . urlencode($hostname);
            $target = "_blank";
            $hrefopts = "";
        } else if ($sa["action_type"] == "rec") {
            $url = $component_url . "/runrec.php?uid=" . urlencode($sa["uid"]) . "&host=" . urlencode($hostname);
            $target = "_blank";
            $hrefopts = "";
            $img = get_component_url_base("reactoreventhandler") . "/images/chart_organisation.png";
        }

        // Action text
        $text = $sa["text"];

        // Get optional code to run
        $code = $sa["code"];

        // Process vars in text, and php code
        foreach ($objectvars as $var => $val) {
            $tvar = "%" . $var . "%";
            $text = str_replace($tvar, $val, $text);
            $code = str_replace($tvar, $val, $code);
        }

        $showlink = true;

        // Execute PHP code
        if ($code != "") {
            eval($code);
        }

        // Code indicated we shouldn't show this link
        if ($showlink == false) {
            continue;
        }

        // Do special link for reactor event chains
        if ($sa["action_type"] == "rec") {
            $link = '<a onclick="javascript:void window.open(\''.$url.'\', \''.time().'\', \'width=700, height=400, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1, left=\'+($(window).width()/2-350)+\', top=\'+($(window).height()/2-200)); return false;"><div class="commandimage"><img src="' . $img . '" alt="' . encode_form_val($text) . '" title="' . encode_form_val($text) . '"></div><div class="commandtext">' . encode_form_val($text) . '</div></a>';
        } else {
            $link = '<div class="commandimage"><a onclick="javascript:void window.open(\''.$url.'\', \''.time().'\', \'width=700, height=400, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1, left=\'+($(window).width()/2-350)+\', top=\'+($(window).height()/2-200)); return false;" target="' . $target . '" ' . $hrefopts . '><img src="' . $img . '" alt="' . encode_form_val($text) . '" title="' . encode_form_val($text) . '"></div><div class="commandtext">' . encode_form_val($text) . '</a></div>';
        }

        $cbargs["actions"][] = '<li>' . $link  . '</li>';
    }

}

function actions_component_service_detail_action($cbtype, &$cbargs)
{
    // Get our settings
    $settings_raw = get_option("actions_component_options");
    if ($settings_raw == "") {
        $settings = array(
            "enabled" => 0,
        );
    } else {
        $settings = unserialize($settings_raw);
    }

    // Initial values
    $enabled = grab_array_var($settings, "enabled");

    // Bail out if we're not enabled...
    if ($enabled != 1) {
        return;
    }

    $hostname = grab_array_var($cbargs, "hostname");
    $servicename = grab_array_var($cbargs, "servicename");
    $service_id = grab_array_var($cbargs, "service_id");
    $servicestatus_xml = grab_array_var($cbargs, "servicestatus_xml");

    // Get variables
    $objectvars = actions_component_get_service_vars($hostname, $servicename, $service_id, $servicestatus_xml);
    $component_url = get_component_url_base("actions");

    // Find matching actions
    foreach ($settings["actions"] as $x => $sa) {

        if ($sa["enabled"] != 1) {
            continue;
        }

        // Must be 'service' or 'any' type
        if ($sa["type"] != "service" && $sa["type"] != "any") {
            continue;
        }

        // Must match host name
        if ($sa["host"] != "" && preg_match($sa["host"], $hostname) == 0) {
            continue;
        }

        // Must match service name
        if ($sa["service"] != "" && preg_match($sa["service"], $servicename) == 0) {
            continue;
        }

        // Must match hostgroup if specified
        if ($sa["hostgroup"] != "" && is_service_member_of_hostgroup($hostname, $servicename, $sa["hostgroup"]) == false) {
            continue;
        }

        // Must match servicegroup if specified
        if ($sa["servicegroup"] != "" && is_service_member_of_servicegroup($hostname, $servicename, $sa["servicegroup"]) == false) {
            continue;
        }

        // Must match the user permissions
        if (!empty($sa['perms'])) {
            if ($sa['perms'] == 'au') {
                if (is_readonly_user()) {
                    continue;
                }
            } else if ($sa['perms'] == 'ao') {
                if (!is_admin()) {
                    continue;
                }
            } else if ($sa['perms'] == 'c') {
                $user_id = $_SESSION['user_id'];
                if (!in_array($user_id, $sa['permusers'])) {
                    continue;
                }
            }
        }

        $img = $component_url . "/images/action.png";

        // URL
        if ($sa["action_type"] == "url") {

            $url = $sa["url"];
            $target = $sa["target"];
            $hrefopts = "";

            // Process vars in url
            foreach ($objectvars as $var => $val) {
                $tvar = "%" . $var . "%";
                if ($var != "notesurl" && $var != "actionurl")
                    $url = str_replace($tvar, urlencode($val), $url);
                else
                    $url = str_replace($tvar, urldecode($val), $url);
            }
        } // COMMAND
        else if ($sa["action_type"] == "command") {
            $url = $component_url . "/runcmd.php?action=" . urlencode($x) . "&uid=" . urlencode($sa["uid"]) . "&host=" . urlencode($hostname) . "&service=" . urlencode($servicename);
            $target = "_blank";
            $hrefopts = "";
        } else if ($sa["action_type"] == "rec") {
            $url = $component_url . "/runrec.php?uid=" . urlencode($sa["uid"]) . "&host=" . urlencode($hostname) . "&service=" . urlencode($servicename);
            $target = "_blank";
            $hrefopts = "";
            $img = get_component_url_base("reactoreventhandler") . "/images/chart_organisation.png";
        }

        // Action text
        $text = $sa["text"];

        // Get optional code to run
        $code = $sa["code"];

        // Process vars in text, and php code
        foreach ($objectvars as $var => $val) {
            $tvar = "%" . $var . "%";
            $text = str_replace($tvar, $val, $text);
            $code = str_replace($tvar, $val, $code);
        }

        $showlink = true;

        // Execute PHP code
        if ($code != "") {
            eval($code);
        }

        // Code indicated we shouldn't show this link
        if ($showlink == false) {
            continue;
        }

        // Do special link for reactor event chains
        if ($sa["action_type"] == "rec") {
            $link = '<a onclick="javascript:void window.open(\''.$url.'\', \''.time().'\', \'width=700, height=400, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1, left=\'+($(window).width()/2-350)+\', top=\'+($(window).height()/2-200)); return false;"><div class="commandimage"><img src="' . $img . '" alt="' . encode_form_val($text) . '" title="' . encode_form_val($text) . '"></div><div class="commandtext">' . encode_form_val($text) . '</div></a>';
        } else {
            $link = '<div class="commandimage"><a onclick="javascript:void window.open(\''.$url.'\', \''.time().'\', \'width=700, height=400, toolbar=0, menubar=0, location=0, status=0, scrollbars=1, resizable=1, left=\'+($(window).width()/2-350)+\', top=\'+($(window).height()/2-200)); return false;" ' . $hrefopts . '><img src="' . $img . '" alt="' . encode_form_val($text) . '" title="' . encode_form_val($text) . '"></div><div class="commandtext">' . encode_form_val($text) . '</a></div>';
        }

        $cbargs["actions"][] = '<li>' . $link . '</li>';
    }

}


function actions_component_get_host_vars($hostname, $host_id = -1, $hoststatus_xml = null)
{

    $hostaddress = $hostname;

    $objectvars = array();

    // find the host's address (and possibly id)
    $args = array(
        "cmd" => "gethosts",
    );
    if ($host_id == -1)
        $args["name"] = $hostname;
    else
        $args["host_id"] = $host_id;

    $xml = get_xml_host_objects($args);
    if ($xml) {
        $hostaddress = strval($xml->host->address);
        $notesurl = strval($xml->host->notes_url);
        $actionurl = strval($xml->host->action_url);
        $notes = strval($xml->host->notes);
    }

    // Try getting the user
    $username = get_user_attr(0, "username");

    if (empty($username))
        $username = "UNKNOWN_USER";
    
    // get hostgroups
    $args = array(
        "cmd" => "gethostgroupmembers",
        "host_id" => $host_id,
    );
    $hostgroupsxml = get_backend_xml_data($args);
    
    $hostgroupnames = "";
    $hg_cnt = 1;
    foreach($hostgroupsxml->hostgroup as $hg){
        $hostgroupnames .= strval($hg->hostgroup_name);
        if ($hg_cnt++ != $hostgroupsxml->recordcount)
            $hostgroupnames .= ",";
    }

    // fetch host status if needed
    if ($hoststatus_xml == null) {
        $args = array(
            "cmd" => "gethoststatus",
            "name" => $hostname,
        );
        $hoststatus_xml = get_xml_host_status($args);
    }

    // variables
    $objectvars = array(
        "username" => $username,
        "objecttype" => "host",
        "host" => $hostname,
        "hostname" => $hostname,
        "hostaddress" => $hostaddress,
        "notesurl" => $notesurl,
        "actionurl" => $actionurl,
        "notes" => $notes,
        "hostgroupnames" => $hostgroupnames,
        "hostid" => strval($hoststatus_xml->hoststatus->host_id),
        "hostdisplayname" => strval($hoststatus_xml->hoststatus->display_name),
        "hostalias" => strval($hoststatus_xml->hoststatus->alias),
        "hoststateid" => intval($hoststatus_xml->hoststatus->current_state),
        "hoststatetype" => strval($hoststatus_xml->hoststatus->state_type),
        "hoststatustext" => strval($hoststatus_xml->hoststatus->status_text),
        "hoststatustextlong" => strval($hoststatus_xml->hoststatus->status_text_long),
        "hostperfdata" => strval($hoststatus_xml->hoststatus->performance_data),
        "hostchecktype" => strval($hoststatus_xml->hoststatus->check_type),
        "hostactivechecks" => strval($hoststatus_xml->hoststatus->active_checks_enabled),
        "hostpassivechecks" => strval($hoststatus_xml->hoststatus->passive_checks_enabled),
        "hostnotifications" => strval($hoststatus_xml->hoststatus->notifications_enabled),
        "hostacknowledged" => strval($hoststatus_xml->hoststatus->problem_acknowledged),
        "hosteventhandler" => strval($hoststatus_xml->hoststatus->event_handler_enabled),
        "hostflapdetection" => strval($hoststatus_xml->hoststatus->flap_detection_enabled),
        "hostisflapping" => strval($hoststatus_xml->hoststatus->is_flapping),
        "hostpercentstatechange" => strval($hoststatus_xml->hoststatus->percent_state_change),
        "hostdowntime" => strval($hoststatus_xml->hoststatus->scheduled_downtime_depth),
        "hostlatency" => strval($hoststatus_xml->hoststatus->latency),
        "hostexectime" => strval($hoststatus_xml->hoststatus->execution_time),
        "hostlastcheck" => strval($hoststatus_xml->hoststatus->last_check),
        "hostnextcheck" => strval($hoststatus_xml->hoststatus->next_check),
        "hosthasbeenchecked" => strval($hoststatus_xml->hoststatus->has_been_checked),
        "hostshouldbescheduled" => strval($hoststatus_xml->hoststatus->should_be_scheduled),
        "hostcurrentattempt" => strval($hoststatus_xml->hoststatus->current_check_attempt),
        "hostmaxattempts" => strval($hoststatus_xml->hoststatus->max_check_attempts),
    );
    
    $freevariables_host=actions_component_get_freevariables($hostname);

    if($freevariables_host){
        foreach($freevariables_host->customvar as $freevariable)
            $objectvars['_HOST'.$freevariable->name] = $freevariable->value;
    }

    return $objectvars;
}

function actions_component_get_service_vars($hostname, $servicename, $service_id, $servicestatus_xml)
{

    $objectvars = array();

    $hostaddress = $hostname;

    if ($servicestatus_xml == null) {
        $args = array(
            "cmd" => "getservicestatus",
            "host_name" => $hostname,
            "service_description" => $servicename,
            "combinedhost" => 1,

        );
        $servicestatus_xml = get_xml_service_status($args);
    }

    // find the host's address
    $args = array(
        "cmd" => "gethosts",
        "host_id" => intval($servicestatus_xml->servicestatus->host_id),
    );
    $xml = get_xml_host_objects($args);
    if ($xml) {
        $hostaddress = strval($xml->host->address);
        $hostalias = strval($xml->host->alias);
        
    }
    
    $service_id = get_service_id($hostname, $servicename);
    
    $args = array(
        "service_id" => $service_id,
    );
    
    $xml = get_xml_service_objects($args);
    if ($xml) {
        $notesurl = strval($xml->service->notes_url);
        $actionurl = strval($xml->service->action_url);
        $notes = strval($xml->service->notes);
    }

    // Try getting the user
    $username = get_user_attr(0, "username");

    if (empty($username))
        $username = "UNKNOWN_USER";
    
    // get servicegroups
    $args = array(
        "cmd" => "getservicegroupmembers",
        "service_id" => $service_id,
    );
    $servicegroupsxml = get_backend_xml_data($args);

    $servicegroupnames = "";
    $sg_cnt = 1;
    foreach($servicegroupsxml->servicegroup as $sg){
            $servicegroupnames .= encode_form_val($sg->servicegroup_name);
            if ($sg_cnt++ != $servicegroupsxml->recordcount)
                $servicegroupnames .= ",";
    }

    // variables
    $objectvars = array(
        "username" => $username,
        "objecttype" => "service",
        "service" => $servicename,
        "servicename" => $servicename,
        "notesurl" => $notesurl,
        "actionurl" => $actionurl,
        "notes" => $notes,
        "servicegroupnames" => $servicegroupnames,
        "serviceid" => strval($servicestatus_xml->servicestatus->service_id),
        "servicedisplayname" => strval($servicestatus_xml->servicestatus->display_name),
        "servicestateid" => intval($servicestatus_xml->servicestatus->current_state),
        "servicestatetype" => strval($servicestatus_xml->servicestatus->state_type),
        "servicestatustext" => strval($servicestatus_xml->servicestatus->status_text),
        "servicestatustextlong" => strval($servicestatus_xml->servicestatus->status_text_long),
        "serviceperfdata" => strval($servicestatus_xml->servicestatus->performance_data),
        "servicechecktype" => strval($servicestatus_xml->servicestatus->check_type),
        "serviceactivechecks" => strval($servicestatus_xml->servicestatus->active_checks_enabled),
        "servicepassivechecks" => strval($servicestatus_xml->servicestatus->passive_checks_enabled),
        "servicenotifications" => strval($servicestatus_xml->servicestatus->notifications_enabled),
        "serviceacknowledged" => strval($servicestatus_xml->servicestatus->problem_acknowledged),
        "serviceeventhandler" => strval($servicestatus_xml->servicestatus->event_handler_enabled),
        "serviceflapdetection" => strval($servicestatus_xml->servicestatus->flap_detection_enabled),
        "serviceisflapping" => strval($servicestatus_xml->servicestatus->is_flapping),
        "servicepercentstatechange" => strval($servicestatus_xml->servicestatus->percent_state_change),
        "servicedowntime" => strval($servicestatus_xml->servicestatus->scheduled_downtime_depth),
        "servicelatency" => strval($servicestatus_xml->servicestatus->latency),
        "serviceexectime" => strval($servicestatus_xml->servicestatus->execution_time),
        "servicelastcheck" => strval($servicestatus_xml->servicestatus->last_check),
        "servicenextcheck" => strval($servicestatus_xml->servicestatus->next_check),
        "servicehasbeenchecked" => strval($servicestatus_xml->servicestatus->has_been_checked),
        "serviceshouldbescheduled" => strval($servicestatus_xml->servicestatus->should_be_scheduled),
        "servicecurrentattempt" => strval($servicestatus_xml->servicestatus->current_check_attempt),
        "servicemaxattempts" => strval($servicestatus_xml->servicestatus->max_check_attempts),


        "host" => $hostname,
        "hostname" => $hostname,
        "hostaddress" => $hostaddress,
        "hostid" => strval($servicestatus_xml->servicestatus->host_id),
        "hostdisplayname" => strval($servicestatus_xml->servicestatus->host_display_name),
        "hostalias"=> $hostalias,
        "hoststateid" => intval($servicestatus_xml->servicestatus->host_current_state),
        "hoststatetype" => strval($servicestatus_xml->servicestatus->host_state_type),
        "hoststatustext" => strval($servicestatus_xml->servicestatus->host_status_text),
        "hoststatustextlong" => strval($servicestatus_xml->servicestatus->host_status_text_long),
        "hostperfdata" => strval($servicestatus_xml->servicestatus->host_performance_data),
        "hostchecktype" => strval($servicestatus_xml->servicestatus->host_check_type),
        "hostactivechecks" => strval($servicestatus_xml->servicestatus->host_active_checks_enabled),
        "hostpassivechecks" => strval($servicestatus_xml->servicestatus->host_passive_checks_enabled),
        "hostnotifications" => strval($servicestatus_xml->servicestatus->host_notifications_enabled),
        "hostacknowledged" => strval($servicestatus_xml->servicestatus->host_problem_acknowledged),
        "hosteventhandler" => strval($servicestatus_xml->servicestatus->host_event_handler_enabled),
        "hostflapdetection" => strval($servicestatus_xml->servicestatus->host_flap_detection_enabled),
        "hostisflapping" => strval($servicestatus_xml->servicestatus->host_is_flapping),
        "hostpercentstatechange" => strval($servicestatus_xml->servicestatus->host_percent_state_change),
        "hostdowntime" => strval($servicestatus_xml->servicestatus->host_scheduled_downtime_depth),
        "hostlatency" => strval($servicestatus_xml->servicestatus->host_latency),
        "hostexectime" => strval($servicestatus_xml->servicestatus->host_execution_time),
        "hostlastcheck" => strval($servicestatus_xml->servicestatus->host_last_check),
        "hostnextcheck" => strval($servicestatus_xml->servicestatus->host_next_check),
        "hosthasbeenchecked" => strval($servicestatus_xml->servicestatus->host_has_been_checked),
        "hostshouldbescheduled" => strval($servicestatus_xml->servicestatus->host_should_be_scheduled),
        "hostcurrentattempt" => strval($servicestatus_xml->servicestatus->host_current_check_attempt),
        "hostmaxattempts" => strval($servicestatus_xml->servicestatus->host_max_check_attempts),
    );
    
    $freevariables_host=actions_component_get_freevariables($hostname);

    if($freevariables_host){
        foreach($freevariables_host->customvar as $freevariable)
            $objectvars['_HOST'.$freevariable->name] = $freevariable->value;
    }
    
    $freevariables_service=actions_component_get_freevariables($hostname,$servicename);

    if($freevariables_service){
        foreach($freevariables_service->customvar as $freevariable)
            $objectvars['_SERVICE'.$freevariable->name] = $freevariable->value;
    }

    return $objectvars;
}

function actions_component_get_freevariables($hostname,$servicename=null){

	$args=array(
		"host_name" => $hostname
		);
    if(!$servicename){
        $x=get_xml_custom_host_variable_status($args);
        if($x->customhostvarstatus->customvars)
            return $x->customhostvarstatus->customvars;
    } else {
        $args["service_description"] = $servicename;
        $x=get_xml_custom_service_variable_status($args);
        if($x->customservicevarstatus->customvars)
            return $x->customservicevarstatus->customvars;
    }
		
	return false;
	}
