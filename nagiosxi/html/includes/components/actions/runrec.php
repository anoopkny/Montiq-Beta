<?php
//
// Actions Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//  

require_once(dirname(__FILE__) . '/../../common.inc.php');
require_once('./actions.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab vars and check prereqs/authenticaiton
grab_request_vars();
check_prereqs();
check_authentication(false);

do_page_start(array("page_title" => _("Running Reactor Event Chain")), true);

// Create the actual run...

$uid = grab_request_var("uid", "");
$host = grab_request_var("host", "");
$service = grab_request_var("service", "");

$settings_raw = get_option("actions_component_options");
if ($settings_raw == "") {
    $settings = array(
        "enabled" => 0,
    );
} else {
    $settings = unserialize($settings_raw);
}

$enabled = grab_array_var($settings, "enabled");
if (!$enabled) {
    echo _("The Actions component is currently disabled.");
    die();
}

// Get the current action
$action = array();
foreach ($settings['actions'] as $a) {
    if ($a['uid'] == $uid && $a['action_type'] == "rec") {
        $action = $a;
    }
}

// Verify action called actually exists
if (empty($action)) {
    echo _("The action you requested to run does not exist or is not a reactor event chain action.");
    die();
}

// Get variables
if (empty($service)) {
    $objectvars = actions_component_get_host_vars($host);
} else {
    $objectvars = actions_component_get_service_vars($host, $service);
}

// echo "<pre>";
// print_r($objectvars);
// echo "</pre>";

// Get the replaceable vars
$replaceables = array();
$replacewith = array();
foreach ($objectvars as $var => $val) {
    $replaceables[] = "%".$var."%";
    $replacewith[] = $val;
}

$context = array();

// Required context
if (!empty($action['rec_req_context_key'])){
    foreach ($action['rec_req_context_key'] as $i => $c) {
        $context[$c] = str_replace($replaceables, $replacewith, $action['rec_req_context_value'][$i]);
    }
}

// Regular context
if (!empty($action['rec_context_key'])){
    foreach ($action['rec_context_key'] as $i => $c) {
        $context[$c] = str_replace($replaceables, $replacewith, $action['rec_context_value'][$i]);
    }
}

// Grab Reactor instance inforamtion
$instance = get_reactor_instances(false, $action['rec_instance']);
?>

<style>
#status { line-height: 16px; display: none; }
#running { display: none; }
span.SUCCESS { font-weight: bold; color: green; }
span.FAILURE { font-weight: bold; color: red; }
</style>

<script type="text/javascript">
var chain_id = '<?php echo $action["rec_chain"] ?>';
var api_url = '<?php echo $instance["api_url"] ?>';
var api_key = '<?php echo $instance["api_key"] ?>';
var variables = <?php echo (!empty($context)) ?json_encode($context) : '{}'; ?>;
var js_pointer;
var js_runid;
var js_line_id = 0;

$(document).ready(function() {

    // Let's send the request to the Reactor instance
    send_reactor_request();

    $("#run").on('click', '#view-status', function() {
        if ($("#status").is(":visible")) {
            $("#status").hide();
        } else {
            $("#status").show();
        }
    });

});

function send_reactor_request() {
    variables['api_key'] = api_key;
    $.post(api_url + "/eventchain/" + chain_id + "/run", variables, function(data) {
        $xml = $(data);
        var run_id = $xml.find("run").attr("id");
        if (run_id) {
            $("#throbber").hide();
            $("#run").append("<p><?php echo _('Event chain running at'); ?> <strong>" + $xml.find("run > created").text() + "</strong> <?php echo _('with ID '); ?><strong>" + run_id + "</strong> ... <a id=\"view-status\"><?php echo _('View Run Log'); ?></a></p>");

            $xml.find("log item").each(function(k, item) {
                $("#status").append("<div>" + $(item).find("created").text() + " - STEP " + $(item).find("step").text() + " - " + $(item).find("data").text() + "</div>");
                js_line_id++;
            });

            $("#running").show();

            // Let's get updates while it runs...
            js_runid = run_id;
            js_pointer = setInterval(update_reactor_request, 1000);
        }
    });
}

function update_reactor_request() {
    $.post(api_url + "/eventchain/" + chain_id + "/runs/" + js_runid, variables, function(data) {
        $xml = $(data);
        var status = $xml.find("run status").text();
        if (status == "COMPLETE") {
            clearInterval(js_pointer);
            $("#running").hide();

            var result = $xml.find("run result").text();
            $("#final").append("<p><?php echo _('Event chain finished running.'); ?></p><p><?php echo _('Result'); ?>: <span class='" + result + "'>" + result + "</span></p>");
        }

        // Update the actual box with more status information
        $xml.find("log item").each(function(k, item) {
            if (js_line_id <= k) {
                $("#status").append("<div>" + $(item).find("created").text() + " - STEP " + $(item).find("step").text() + " - " + $(item).find("data").text() + "</div>");
                js_line_id++;
            }
        });

    });
}
</script>

<h1><?php echo _("Running Reactor Event Chain"); ?></h1>
<p><?php echo _("Sending event chain run command to: ") . "<strong>" . $instance['name'] . "</strong>"; ?> ...</p>
<div id="run"></div>
<div id="status"></div>
<div id="throbber"><img src="<?php echo theme_image("throbber1.gif"); ?>"></div>
<div id="running"><img src="<?php echo theme_image("throbber1.gif"); ?>"> <?php echo _("Running"); ?> ...</div>
<div id="final"></div>

<?php
do_page_end(true);