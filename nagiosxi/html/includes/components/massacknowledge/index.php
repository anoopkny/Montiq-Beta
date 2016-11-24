<?php
//
// Mass Acknowledge Component
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and do prereq/auth checks
grab_request_vars();
check_prereqs();
check_authentication(false);

$title = _("Mass Acknowledgement");
do_page_start(array("page_title" => $title), true);
?>

<script type="text/javascript">

var allChecked = false;
var allCheckedSticky = false;
var allCheckedNotify = false;
var allCheckedPersist = false;
var checked = [];

function checkAll(host, obj) {
    if (host == 'all' && allChecked == false) {
        $('.select-all-for-host').each(function() {
            var host = $(this).data('host');
            $('input.'+host).prop('checked', true);
            checked[host] = true;
        });
        $('#checkAllButton').val('<?php echo _("Uncheck All Items"); ?>');
        allChecked = true;
        $('.hostcheck').prop('checked', true);
    } else if (host == 'all' && allChecked == true) {
        $('.select-all-for-host').each(function() {
            var host = $(this).data('host');
            $('input.'+host).prop('checked', false);
            checked[host] = false;
        });
        $('#checkAllButton').val('<?php echo _("Check All Items"); ?>');
        allChecked = false;
        $('.hostcheck').prop('checked', false);
    }
}

$(document).ready(function() {
    $('.select-all-for-host').click(function() {
        var host = $(this).data('host');
        if (checked[host] == false || checked[host] == undefined) {
            $('input.'+host).prop('checked', true);
            checked[host] = true;
        } else {
            $('input.'+host).prop('checked', false);
            checked[host] = false;
        }
    });
});

function checkAllSticky() {
    if (allCheckedSticky == false) {
        $('input.sticky').each(function () {
            this.checked = 'checked';
        });
        allCheckedSticky = true;
    }
    else {
        $('input.sticky').each(function () {
            this.checked = '';
        });
        allCheckedSticky = false;
    }
}


function checkAllNotify() {
    if (allCheckedNotify == false) {
        $('input.notify').each(function () {
            this.checked = 'checked';
        });
        allCheckedNotify = true;
    }
    else {
        $('input.notify').each(function () {
            this.checked = '';
        });
        allCheckedNotify = false;
    }

}

function checkAllPersist() {
    if (allCheckedPersist == false) {
        $('input.persist').each(function () {
            this.checked = 'checked';
        });
        allCheckedPersist = true;
    }
    else {
        $('input.persist').each(function () {
            this.checked = '';
        });
        allCheckedPersist = false;
    }

}

function checkAlldt() {
    if (allCheckedPersist == false) {
        $('input.dt').each(function () {
            this.checked = 'checked';
        });
        allCheckedPersist = true;
    }
    else {
        $('input.dt').each(function () {
            this.checked = '';
        });
        allCheckedPersist = false;
    }

}

function checkTime() {
    if ($('#massack_type').val() == 'acknowledgment') {
        $('#time').prop('disabled', true);
        $('.sticky').show();
        $('.notify').show();
        $('.persist').show();

    }
    else {
        $('#time').prop('disabled', false);
        $('.sticky').hide();
        $('.notify').hide();
        $('.persist').hide();
    }
}

$(document).ready(function() {

    $('#mass_type').change(function() {
        if ($(this).val() == 'ack') {
            $('#time').prop('disabled', true);
            $('#comment').prop('disabled', false);
            $('.sticky').show();
            $('.notify').show();
            $('.persist').show();
        } else if ($(this).val() == 'both') {
            $('#time').prop('disabled', false);
            $('#comment').prop('disabled', false);
            $('.sticky').show();
            $('.notify').show();
            $('.persist').show();
        } else if ($(this).val() == 'sc') {
            $('#time').prop('disabled', true);
            $('#comment').prop('disabled', true);
            $('.sticky').hide();
            $('.notify').hide();
            $('.persist').hide();
        } else {
            $('#time').prop('disabled', false);
            $('#comment').prop('disabled', false);
            $('.sticky').hide();
            $('.notify').hide();
            $('.persist').hide();
        }
    });

});

</script>

<?php
$submitted = grab_request_var('submitted', false);
$feedback = '';

// Display output from command submissions 
if ($submitted) {
    $exec_errors = 0;
    $error_string = '';
    $feedback = massacknowledge_core_commands();
}

// Create array of hosts that have unhandled services problems
$massack_hosts = massacknowledge_get_hosts();

// Fetch all service problems
$problem_services = massacknowledge_get_unhandled_service_problems();

function ma_comp($a, $b) {
    return strnatcmp($a['service_description'], $b['service_description']);
}

function ma_host_comp($a, $b) {
    return strnatcmp($a, $b);
}

foreach ($problem_services as &$p) {
    usort($p, 'ma_comp');
}

// Sort the hostnames alphabetically
uksort($massack_hosts, 'ma_host_comp');

// get downtimes
$downtimes = massacknowledge_get_downtimes();

if (is_readonly_user(0)) {
    $html = _("You are not authorized for this component.");
} else {
    $html = massacknowledge_build_html($massack_hosts, $problem_services, $feedback);
    $html .= massacknowledge_build_downtime_html($downtimes);
}

print $html;

/////////////////FUNCTIONS/////////////////////////////////

function massacknowledge_build_html($hosts, $problem_services, $feedback)
{
    $html = "
        <div id='massack_wrapper'>
        <h1>" . _('Mass Acknowledgments and Downtime Scheduling') . "</h1>
        {$feedback}
        <div id='massack_info'>
            <p>" . _("Use this tool to schedule downtime or to acknowledge large groups of unhandled problems. For scheduled downtime, specify the length of downtime in minutes to schedule 'flexible' downtime. Commands may take a few moments to take effect on status details.") . "<br>" . _("Please note that you may only submit characters that are from your locale. In other words, if your locale is set to en_US, you may not submit Japanese characters for submission,  you must first change your locale to ja_JP and then submit your message.") . "</p>
        </div>

        <div id='massack'>";

    $html .= '<form id="form_massack" action="' . htmlentities($_SERVER['PHP_SELF']) . '" method="post">';

    $html .= "<div class='well' style='margin: 10px 0 0 0;'>
                <input type='hidden' id='submitted' name='submitted' value='true' />
                <label for='massack_type'>" . _('Command Type') . "</label>

                <select name='type' id='mass_type' class='form-control'>
                    <option value='ack'>" . _("Acknowledgement") . "</option>
                    <option value='dt'>" . _("Schedule Downtime") . "</option>
                    <option value='both'>" . _("Acknowledge and Schedule Downtime") . "</option>
                    <option value='sc'>" . _("Schedule Immediate Check<") . "/option>
                </select> &nbsp;

                <label for='time'>" . _("Time") . "</label>
                <input type='text' id='time' name='time' value='120' class='form-control' disabled='disabled' size='4'> min
                
                <label for='massack_comment' style='margin-left: 10px;'>" . _("Comment") . "</label>
                <input type='text' class='form-control' id='comment' name='comment' value='" . _('Problem is acknowledged') . "' size='50'>

                <button type='submit' class='btn btn-sm btn-primary' style='margin-left: 10px; vertical-align: top;' id='submit'>" . _("Submit Commands") . "</button>
            </div>

            <div class='fl'>
                <div style='padding: 20px 0 10px 0;'>
                    <input type='button' class='btn btn-sm btn-default fl' id='checkAllButton' onclick='checkAll(\"all\")' title='"._('Check All Hosts and Services')."' value='" . _("Check All Items") . "'>
                    
                    <a href=\"" . htmlentities($_SERVER['PHP_SELF']) . "\" class='btn btn-sm btn-default fr' title='"._('Update List')."'><i class='fa fa-refresh l'></i> " . _("Update List") . "</a>
                    <div class='clear'></div>
                </div>

                <table class='table table-condensed table-striped table-bordered table-auto-width' id='massack_table'>
                    <thead>
                        <tr>
                            <th>" . _("Host Name") . "</th>
                            <th>" . _("Unhandled Service Problems") . "</th>
                            <th>" . _("Service Status") . "</th>
                            <th class='stickyhead center'>
                                <div class='checkbox'>
                                    <label style='font-weight: bold;'>
                                        <input type='checkbox' onchange='checkAllSticky()'>" . _("Sticky") . "
                                    </label>
                                </div>
                            </th>
                            <th class='notifyhead center'>
                                <div class='checkbox'>
                                    <label style='font-weight: bold;'>
                                        <input type='checkbox' onchange='checkAllNotify()'>" . _("Notify") . "
                                    </label>
                                </div>
                            </th>
                            <th class='persisthead center'>
                                <div class='checkbox'>
                                    <label style='font-weight: bold;'>
                                        <input type='checkbox' onchange='checkAllPersist()'>" . _("Persistent") . "
                                    </label>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>";

    $hostcount = 0;
    foreach ($hosts as $host) {
        //skip hosts that have no problems or problem services
        if ($host['problem'] == false && !isset($problem_services[$host['host_name']])) continue;
        //html variables
        $host_checkbox = ($host['problem'] == true) ? "<input type='checkbox' class='hostcheck' name='hosts[]' value='{$host['host_name']}' />" : '';
        $checkAll = (isset($problem_services[$host['host_name']])) ? '<a class="select-all-for-host" data-host="host'.$hostcount.'" >'. _("Toggle checboxes for this Host").'</a>' : '&nbsp;';
        $host_class = host_class($host['host_state'], $host['has_been_checked']);

        $html .= "<tr>
            <td class='{$host_class}'>{$host_checkbox} {$host['host_name']} </td>
            <td class='aligncenter'> {$checkAll} </td>
            <td> &nbsp; </td>";
        if ($host_checkbox != '')
            $html .= "
            <td class='centertd'><input type='checkbox' class='sticky' name='sticky[{$host['host_name']}]' value='2' /></td>
            <td class='centertd'><input type='checkbox' class='notify' name='notify[{$host['host_name']}]' value='1' /></td>
            <td class='centertd'><input type='checkbox' class='persist' name='persist[{$host['host_name']}]' value='1' /></td>
            </tr>";
        else
            $html .= "<td></td><td></td><td></td></tr>";

        if (isset($problem_services[$host['host_name']])) {
            foreach ($problem_services[$host['host_name']] as $service) {
                $html .= "
                <tr>
                    <td> &nbsp; </td>
                    <td class='alignleft " . service_class($service['current_state'], $service['has_been_checked']) . "'>
                        <div class='checkbox'>
                            <label>
                                <input class='host{$hostcount} servicecheck' type='checkbox' name='services[]' value='{$host['host_name']}::{$service['service_description']}'>
                                {$service['service_description']}
                            </label>
                        </div>
                    </td>
                    <td><div class='plugin_output'>{$service['plugin_output']}</div></td>
                    <td class='centertd'><input type='checkbox' class='sticky' name='sticky[{$host['host_name']}::{$service['service_description']}]' value='2'></td>
                    <td class='centertd'><input type='checkbox' class='notify' name='notify[{$host['host_name']}::{$service['service_description']}]' value='1'></td>
                    <td class='centertd'><input type='checkbox' class='persist' name='persist[{$host['host_name']}::{$service['service_description']}]' value='1'></td>
                </tr>";
            }
            $hostcount++;
        }

    }
    $html .= "</tbody></table>
    </div><div class='clear'></div></form>";
    $html .= "</div></div>";

    return $html;
}

function massacknowledge_build_downtime_html($downtimes)
{
    $html = "
        <div id='downtime_wrapper'>
        <h1 style='padding-top: 0;'>" . _('Mass Remove Downtime') . "</h1>
        <div id='downtime_info'>
            <p>" . _("Use this tool to remove scheduled downtimes.  Commands may take a few moments to take effect on status details.") . "</p>
        </div>      
        <div id='massdt'>
        <form id='form_massack' action=\"" . htmlentities($_SERVER['PHP_SELF']) . "\" method='post'>
            <div class='fl'>
            <input type='hidden' name='submitted' value='true'>
            <input type='hidden' name='type' value='removedt'>
            <input type='button' class='btn btn-sm btn-default' onclick='checkAlldt()' title='"._('Check All Downtime')."' value='" . _("Check All'") . ">
            <input type='submit' class='btn btn-sm btn-primary' value='" . _("Remove Downtimes") . "'>
            <a href=\"" . htmlentities($_SERVER['PHP_SELF']) . "\" class='btn btn-sm btn-default fr' title='"._('Update List')."'><i class='fa fa-refresh l'></i> " . _("Update List") . "</a>
            <table class='table table-condensed table-bordered table-auto-width table-striped servicestatustable' style='margin: 10px 0;' id='massdt_table'>
                <tr><th>" . _("Host Name") . "</th>
                    <th>" . _("Service Description") . "</th>
                    <th>" . _("Start Time") . "</th>
                    <th>" . _("End Time") . "</th>
                </tr>";

    $hostcount = 0;
    foreach ($downtimes as $host) {

        foreach ($host as $downtime) {
            //html variables
            $downtime_value = ($downtime['service_description'] == "") ? "h" : "s";
            $html .= "<tr><td><a href='javascript:checkAll(\"dt{$hostcount}\");'>{$downtime['host_name']}</a></td>
            <td> <input type='checkbox' class='dt dt{$hostcount}' name='downtime[]' value='$downtime_value-{$downtime['downtime_id']}'>{$downtime['service_description']} </td>
            <td> {$downtime['scheduled_start_time']} </td>
            <td> {$downtime['scheduled_end_time']} </td> 
        </tr>";

        }
        $hostcount++;
    }
    $html .= "</table>
    </div>
    <div class='clear'></div><input type='button' class='btn btn-sm btn-default' onclick='checkAlldt()' title='"._('Check All Downtime')."' value='" . _("Check All'") . ">
            <input type='submit' class='btn btn-sm btn-primary' value='" . _("Remove Downtimes") . "'></form>";
    $html .= "</div></div></body></html>";

    if (count($downtimes) == 0)
        $html = "";
    return $html;
}

function massacknowledge_get_hosts()
{
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["brevity"] = 1;
    $xml = get_xml_host_status($backendargs);
    $hosts = array();
    if ($xml) {
        foreach ($xml->hoststatus as $x) {
            $name = "$x->name";
            $state = "$x->current_state";
            $problem = true;
            if (("$x->current_state" == 0 && "$x->has_been_checked" == 1) || "$x->scheduled_downtime_depth" > 0 ||
                "$x->problem_acknowledged" > 0
            )
                //problem diverted
                $problem = false;

            $hosts[$name] = array('host_state' => $state, 'host_name' => $name, 'problem' => $problem, 'has_been_checked' => "$x->has_been_checked");
        }
    } else echo "can't find host xml!";
    return $hosts;
}


function massacknowledge_get_unhandled_service_problems()
{
//  global $massack_hosts;  
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = 1;
    $backendargs["current_state"] = "in:1,2,3";
    $backendargs["has_been_checked"] = 1;
    $backendargs["problem_acknowledged"] = 0;
    $backendargs["scheduled_downtime_depth"] = 0;
    $backendargs['is_active'] = 1;
    $xml = get_xml_service_status($backendargs);
    $problem_services = array();

    if ($xml) {
        foreach ($xml->servicestatus as $x) {

            $host_state = intval($x->host_current_state);
            $service = array('host_name' => "$x->host_name",
                'service_description' => "$x->name",
                //  'host_state'        => $host_state,
                'current_state' => "$x->current_state",
                'plugin_output' => "$x->status_text",
                'has_been_checked' => "$x->has_been_checked");

            //$massack_hosts["$x->host_name"] = "$x->host_name";
            $problem_services["$x->host_name"][] = $service;
        }
    }
        
    return $problem_services;

    //end if
} //end function 


function massacknowledge_core_commands()
{
    global $exec_errors;
    global $error_string;

    //print_r($_POST);

    $hosts = grab_request_var('hosts', array());
    $services = grab_request_var('services', array());
    $sticky = grab_request_var('sticky', array());
    $notify = grab_request_var('notify', array());
    $persist = grab_request_var('persist', array());
    $message = grab_request_var('comment', '');
    $mode = grab_request_var('type', 'both');
    $time = grab_request_var('time', 0);
    $username = get_user_attr($_SESSION['user_id'], 'name');
    $username = $username == '' ? $_SESSION['username'] : $username; //default to session username

    //bail if missing required values
    if (count($hosts) == 0 && count($services) == 0 && $mode != "removedt")
        return feedback_message('You must specify at least one service', true);
    if ($message == '' && $mode != "removedt" && $mode != "sc")
        return feedback_message('You must specify a comment', true);

    //make sure script is executable
    //if(!is_executable(dirname(__FILE__).'/ack_Host.sh')) exec('chmod +x ack_Host.sh');
    if ($mode != "removedt") {
        //loop through any host specific commands   
        foreach ($hosts as $host) {
            $stick = grab_array_var($sticky, $host, 1);
            $notif = grab_array_var($notify, $host, 0);
            $persistent = grab_array_var($persist, $host, 0);
            massacknowledge_exec_script($host, $username, $message, $service = false, $mode, $time, $stick, $notif, $persistent);
        }
        //loop through service specific commands 
        foreach ($services as $service) {
            $stick = grab_array_var($sticky, $service, 1);
            $notif = grab_array_var($notify, $service, 0);
            $persistent = grab_array_var($persist, $service, 0);
            $vals = explode('::', $service);
            massacknowledge_exec_script($vals[0], $username, $message, $vals[1], $mode, $time, $stick, $notif, $persistent);
        }

    } else { // it is a downtime removal
        $downtimes = grab_request_var('downtime', array());
        foreach ($downtimes as $id) {
            massacknowledge_del_downtime_exec_script($id);

        }
    }
    //return feedback for front-end
    if ($exec_errors == 0)
        return feedback_message(_('Commands processed successfully! Your command submissions may take a few moments to update in the display.'));
    else
        return feedback_message("$exec_errors " . _("errors were encountered while processing these commands") . " <br />$error_string", true);
}

function massacknowledge_del_downtime_exec_script($id)
{
    global $cfg;
    global $exec_errors;
    global $error_string;

    //split to determine host or service
    $splitid = explode("-", $id);
    //security measures 
    $dt_id = escapeshellcmd($splitid[1]);

    if ($splitid[0] == "h")
        $dtCommand = "DEL_HOST_DOWNTIME";
    else
        $dtCommand = "DEL_SVC_DOWNTIME";

    $pipe = $cfg['component_info']['nagioscore']['cmd_file'];
    $now = time();

    $dtString = "/bin/echo '[$now] $dtCommand;$dt_id\n' > $pipe";

    if ($dtCommand == "DEL_HOST_DOWNTIME") {
        send_to_audit_log("Nagios XI auditlog: Scheduled downtime removed on Host ID: " . $dt_id . " by " . $_SESSION['username'] . " (massacknowledge)", AUDITLOGTYPE_MODIFY);
    } else {
        send_to_audit_log("Nagios XI auditlog: Scheduled downtime removed on Service ID: " . $dt_id . " by " . $_SESSION['username'] . " (massacknowledge)", AUDITLOGTYPE_MODIFY);
    }

    $bool = exec($dtString);

    //handle errors
    if ($bool > 0) {
        $exec_errors++;
        //$error_string .=$output.'<br />';
    }


}

function massacknowledge_exec_script($host, $username, $message, $service = false, $mode = 'both', $time, $sticky, $notify, $persistent)
{
    global $cfg;
    global $exec_errors;
    global $error_string;

    // Set our locale for PHP
    // If we don't do this, escapeshellcmd will remove all unicode.
    $locale = grab_array_var($_SESSION, 'language', 'en_US');
    if ($locale == 'en_EN') {
        $locale = 'en_US';
    }

    if (FALSE == setlocale(LC_ALL, $locale.".UTF-8")) {
        return FALSE;
    }

    $seconds = ($time * 60);
    $pipe = $cfg['component_info']['nagioscore']['cmd_file'];
    $now = time();
    $dtEnd = $now + $seconds;

    if ($service) {
        $ackCommand = 'ACKNOWLEDGE_SVC_PROBLEM';
        $dtCommand = 'SCHEDULE_SVC_DOWNTIME';
        $scCommand = 'SCHEDULE_FORCED_SVC_CHECK';
        $ackString = "/bin/echo " . escapeshellarg("[$now] $ackCommand;$host;$service;$sticky;$notify;$persistent;$username;$message") . " > $pipe";
        $dtString = "/bin/echo " . escapeshellarg("[$now] $dtCommand;$host;$service;$now;$dtEnd;1;0;$seconds;$username;$message") . " > $pipe";
        $scString = "/bin/echo " . escapeshellarg("[$now] $scCommand;$host;$service;$now") . " > $pipe"; 
    } else {
        $ackCommand = 'ACKNOWLEDGE_HOST_PROBLEM';
        $dtCommand = 'SCHEDULE_HOST_DOWNTIME';
        $scCommand = 'SCHEDULE_FORCED_HOST_CHECK';
        $ackString = "/bin/echo " . escapeshellarg("[$now] $ackCommand;$host;$sticky;$notify;$persistent;$username;$message") . " > $pipe";
        $dtString = "/bin/echo " . escapeshellarg("[$now] $dtCommand;$host;$now;$dtEnd;1;0;$seconds;$username;$message") . " > $pipe";
        $scString = "/bin/echo " . escapeshellarg("[$now] $scCommand;$host;$now") . " > $pipe";
    }

    $output = array();

    switch ($mode) {
        case 'ack':
            exec($ackString, $output, $returncode);
            break;
        case 'dt':
            exec($dtString, $output, $returncode);

            if ($dtCommand == "SCHEDULE_HOST_DOWNTIME") {
                send_to_audit_log("Nagios XI auditlog: Scheduled downtime submitted on Host: " . $host . " by " . $username . " (massacknowledge)", AUDITLOGTYPE_MODIFY);
            } else {
                send_to_audit_log("Nagios XI auditlog: Scheduled downtime submitted on Host: " . $host . ", Service: " . $service . " by " . $username . " (massacknowledge)", AUDITLOGTYPE_MODIFY);
            }
            break;
        case 'sc':
            exec($scString, $output, $returncode);
            break;
        case 'both':
            exec($ackString, $output, $returncode);
        case 'dt':
            exec($dtString, $output, $returncode);
            break;
    }

    // Handle errors
    if ($returncode > 0) {
        $exec_errors++;
    }
}

function feedback_message($msg, $error = false)
{
    $class = ($error) ? 'errorMessage' : 'actionMessage';
    $icon = "<img src='" . theme_image("info_small.png") . "'>";

    if ($error) {
        $icon = "<img src='" . theme_image("critical_small.png") . "'>";
    }

    $html = "<div class='{$class} standalone'>
                {$icon} {$msg}
            </div>";
    return $html;
}

function host_class($code, $has_been_checked=1)
{
    if ($has_been_checked != 1)
        return '';
    switch ($code) {
        case 0:
            return "hostup";
        case 1:
            return 'hostdown';
        default:
            return 'hostunreachable';
    }
}

function service_class($code, $has_been_checked=1)
{
    if ($has_been_checked != 1)
        return '';
    switch ($code) {
        case 0:
            return "serviceok";
        case 1:
            return 'servicewarning';
        case 2:
            return 'servicecritical';
        default:
            return 'serviceunknown';
    }
}

function massacknowledge_get_downtimes()
{
    $backendargs = array();
    $backendargs["cmd"] = "getscheduleddowntime";

    $xml = get_backend_xml_data($backendargs);
    $downtimes = array();
    if ($xml) {
        foreach ($xml->scheduleddowntime as $x) {
            $downtime_id = "$x->internal_id";
            $host_name = "$x->host_name";
            $service_description = "$x->service_description";
            $scheduled_start_time = "$x->scheduled_start_time";
            $scheduled_end_time = "$x->scheduled_end_time";
            $duration = "$x->duration";

            $downtimes[$host_name][] = array('downtime_id' => $downtime_id, 'host_name' => $host_name, 'service_description' => $service_description, 'scheduled_start_time' => $scheduled_start_time, 'scheduled_end_time' => $scheduled_end_time, 'duration' => $duration);
        }
    } else echo "can't find host xml!";
    return $downtimes;
}