<?php
//
// Escalation Wizard
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();
grab_request_vars();
check_prereqs();
check_authentication(false);

// only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature. Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}

// MySQL debug output setting
$myDebug = false;

$html = route_request();

$title = _("Escalation Wizard");
do_page_start(array("page_title" => $title), true);
?>

<script type="text/javascript">

    function remove(id) {
        $('#' + id).remove();
    }

    $(document).ready(function () {
        $('#back').click(function () {
            var type = $('#objecttype').val();
            goBack(type);
        });

        //bind search event to enter key
        $('#search').keyup(function (ev) {
            if (ev.which == 13 && $('#search').val() != '') {
                var type = $('#objecttype').val();
                obj_search(type);
            }
        });

    });

    function goBack(type) {
        var stage = parseInt($('#stage').val()) - 2;
        //alert(type);
        window.location = 'escalationwizard.php?stage=' + stage + '&objecttype=' + type;
    }

    function obj_search(type) {
        var search = $('#search').val();
        window.location = 'escalationwizard.php?stage=2&objecttype=' + type + '&search=' + search;
    }

    function clear_search(type) {
        window.location = 'escalationwizard.php?stage=2&objecttype=' + type;
    }

    function finish_wizard() {

        //validate all necessary fields
        if (validate() == false)
            return;

        //submit and apply config
        $('#stage').val('5');
        $('#mainform').submit();
    }


    function completestage() {
        //validate all necessary fields
        if (!validate())
            return;

        $('#mainform').submit();
    }

    function validate() {

        var valid = true;
        $('input.required').each(function () {
            if ($(this).val() == '')
                valid = false;
        });

        if (valid == false) {
            alert('Missing required fields');
            return;
        }

        return valid;
    }

</script>

<h1><?php echo _("Escalation Wizard"); ?></h1>
<div id='main'>
    <?php echo $html; ?>
</div>

<?php
do_page_end(true); 

//////////////////////////////////////////FUNCTIONS////////////////////

function route_request()
{

    global $escape_request_vars;
    $escape_request_vars = false;

    $stage = grab_request_var('stage', 1);
    $type = grab_request_var('objecttype', 'host');
    if ($type != 'host' && $type != 'service') {
        $type = 'host';
    }

    switch ($stage) {
        case 2:
            $output = ew_stage_two($type);
            break;
        case 3:
            if ($type == 'service')
                $output = ew_stage_three($type);
            else { //skip ahead for hosts
                $arr = array(0, '');
                $level = 1;
                $output = ew_stage_four($type, $arr, $level);
            }
            break;
        case 4:
            $level = grab_request_var('level', 1);
            $submitted = grab_request_var('submitted', false);
            $arr = array(0, '');
            if ($submitted) { //level will only exist if "Add another stage" button was pressed
                list($errors, $msg) = ew_save_escalation($type);
                $arr = array($errors, $msg);
                if ($errors == 0)
                    $level++;
            }

            $output = ew_stage_four($type, $arr, $level);
            break;
        case 5:
            //save and apply config
            list($errors, $msg) = ew_save_escalation($type);
            $arr = array($errors, $msg);

            if ($errors > 0) {
                $newmsg = _("There was an error processing your request:") . " {$msg}.
                <br /><a href='escalationwizard.php' title='Run Wizard Again'>" . _("Run Escalation Wizard again") . "</a>";
                unset($_SESSION['se_wizard_objects']);
                $output = display_message($errors, true, $newmsg);

            } else { //success, redirect to apply config
                unset($_SESSION['se_wizard_objects']);
                header("Location: " . get_base_url() . "includes/components/nagioscorecfg/applyconfig.php");
            }
            break;
        case 1:
        default:
            $output = ew_stage_one();
            break;
    }

    return $output;
}


function ew_stage_one()
{

    $output = "
    <div id='ew_stage_1'>
        <p>" . _("The Escalation Wizard allows for an escalation chain to easily be defined for multiple hosts or services at once.") . "</p>
        <h2>" . _("Stage 1") . "</h2>
        <div id='prompt'>" . _("What type of escalation would you like to define?") . "</div>
        <div>
            <form id='mainform' method='post' action='escalationwizard.php'>
                <div style='margin: 15px 0 25px 0;'>
                    <div class='radio'>
                        <label>
                            <input type='radio' id='rad1' value='host' checked='checked' name='objecttype'>" . _("Host") . "
                        </label>
                    </div>
                    <div class='radio'>
                        <label>
                            <input type='radio' id='rad1' value='service' name='objecttype'>" . _("Service") . "
                        </label>
                    </div>
                </div>
                <button type='submit' class='btn btn-sm btn-primary' id='submit' name='submit'>" . _("Next") . " <i class='fa fa-chevron-right'></i></button>
                <input type='hidden' name='stage' id='stage' value='2'>
            </form> 
        </div>
    </div>  
";

    return $output;

}


function ew_stage_two($type, $errors = array(0, ''))
{
    $uppername = ucfirst($type);
    $search = escape_sql_param(grab_request_var('search', ''), DB_NAGIOSQL);
    $optionString = get_option_list($type, $search);
    $info = ($type == 'service') ? "" . _("Config Names") . " :: " . _("Service Descriptions") : _('Host Names');

    $hg_options = '';
    if ($type == 'host') {
        $opts = get_tbl_opts('hostgroup');
        $hg_options = "<div style='margin: 15px 0 5px 0; font-weight: bold;'>" . _("Host Groups") . "</div>
            <select name='hostgroups[]' id='hostgroups' class='form-control' style='width: 300px; height: 100px;' multiple='multiple'>{$opts}</select>";
    }

    $feedback = '';
    if ($errors[0] > 0) {
        $feedback = display_message($errors[0], true, $errors[1], true);
    }

    $output = "
    <div id='ew_stage_2'>
        <p>" . _("Which") . " {$uppername}s " . _("would you like to create escalations for? Use Ctrl + click to select and deselect.") . "</p>
        {$feedback}
        <h2>"._('Stage 2')."</h2>
        <div>
            <form id='mainform' method='post' action='escalationwizard.php'>

                <div style='margin-bottom: 5px; font-weight: bold;'>{$info}</div>

                <div style='margin-bottom: 5px;'>
                    <input type='text' class='form-control' name='search' id='search' placeholder='"._('Search')."...' value='{$search}'>
                    <button type='button' class='btn btn-sm btn-default' style='vertical-align: top;' id='actionSearch' onclick='javascript:obj_search(\"{$type}\");'><i class='fa fa-search'></i></button>
                    <button type='button' class='btn btn-sm btn-default' style='vertical-align: top;' id='clearSearch' onclick='javascript:clear_search(\"{$type}\");'><i class='fa fa-times'></i></button>
                </div>
                
                <select class='required form-control' style='height: 300px; min-width: 200px; width: 25%;' name='selected[]' id='selected' multiple='multiple'>
                    {$optionString}
                </select>
                
                {$hg_options}

                <div style='margin-top: 20px;'>
                    <button type='button' class='btn btn-sm btn-default' id='back' name='back'><i class='fa fa-chevron-left'></i> " . _("Back") . "</button>
                    <button type='button' class='btn btn-sm btn-primary' id='proceed' name='proceed'onclick='completestage()'>" . _('Next') . " <i class='fa fa-chevron-right'></i></button>
                    <input type='hidden' name='stage' id='stage' value='3' /> 
                    <input type='hidden' name='objecttype' id='objecttype' value='{$type}' />
                </div>
            </form> 
        </div>
    </div>  
";

    return $output;
}

/**
 *    select service -> host and servie->hostgroup relationships we'll use. Skip for hosts escalations
 *
 */
function ew_stage_three($type, $errors = array(0, ''), $ids = null)
{

    if ($ids == null)
        $ids = grab_request_var('selected', array());

    //host escalations will skip to stage 4....

    //check for errors if we're coming back from stage 4
    $feedback = '';
    if (empty($ids)) {
        $output = ew_stage_two($type, array(1, 'You must select at least one item'));
        return $output;
    }
    if ($errors[0] > 0)
        $feedback = display_message($errors[0], true, $errors[1], true);

    $output = "
    <div id='ew_stage_3'>
        <p>" . _("Select objects this escalation chain will affect. The") . " <strong>" . _("Config Name") . "</strong>
        " . _("is how the escalation will be identified in the Core Config Manager.") . " </p>
        {$feedback}
        <div id='formdiv'>
        <form id='mainform' method='post' action='escalationwizard.php'>
            <table class=' table table-condensed table-striped table-bordered' id='formcontent'>
                <thead>
                    <tr class='center'><th>" . _("Config Name") . "</th>
                        <th>" . _("Service Description") . "</th>
                        <th>" . _("Related Hosts") . "</th>
                        <th>" . _("Related Hostgroups") . "</th>
                        <th style='width: 40px;'></th>
                    </tr>
                </thead>
                <tbody>";

    $rowcount = 0;
    foreach ($ids as $id) {
        //fetch more info on the selected objects, including relationships
        $service = get_object_details($type, $id);
        $host_opts = get_html_list($id, $service['hosts'], 'host');
        $hostgroup_opts = get_html_list($id, $service['hostgroups'], 'hostgroup');

        $output .= "<tr id='tr_{$rowcount}'>
                    <iinput type='hidden' name='services[{$id}][id]' value='{$id}' />
                    <td><input type='text' class='form-control' name='services[{$id}][config_name]' value='{$service['config_name']}' /></td>
                    <td><input type='hidden' name='services[{$id}][desc]' value='{$service['service_description']}' />{$service['service_description']}</td>
                    <td>{$host_opts}</td>
                    <td>{$hostgroup_opts}</td>
                    <td class='center'><a class='remove tt-bind' title='"._('Remove')."' data-placement='left' href='javascript:remove(\"tr_{$rowcount}\")'><i style='font-size: 16px;' class='fa fa-trash-o'></i></a></td>
                </tr>";
        $rowcount++;
    }

    //close table and form
    $output .= "</tbody>
            </table>

            <div>
                <button type='button' class='btn btn-sm btn-default' id='back' name='back'><i class='fa fa-chevron-left'></i> " . _("Back") . "</button>
                <button type='submit' class='btn btn-sm btn-primary' id='submit' name='submit'>" . _("Next") . " <i class='fa fa-chevron-right'></i></button>
                <input type='hidden' name='stage' id='stage' value='4'> 
                <input type='hidden' name='objecttype' id='objecttype' value='{$type}'>
            </div>
        </form> 
        </div>
    </div>  
    ";

    return $output;
}



/**
 *    stage 4 of wizard, verifies input and returns success | fail message
 *    this stage can be repeated many times to create many stages of escalations
 */
function ew_stage_four($type, $errors = array(0, ''), $level = 1)
{
    if ($level == 1) {
        $_SESSION['se_wizard_objects'] = array();
    }

    // Service escalation
    if ($level == 1) {
        $services = grab_request_var('services', array());
        $_SESSION['se_wizard_objects']['services'] = $services;
    } else {
        $services = $_SESSION['se_wizard_objects']['services'];
    }

    if ($type == 'host') {
        $selected = grab_request_var('selected', array());
        $hostgroups = grab_request_var('hostgroups', array());
        if ($level == 1) {
            $_SESSION['se_wizard_objects']['hosts'] = $selected;
            $_SESSION['se_wizard_objects']['hostgroups'] = $hostgroups;
        } else {
            $selected = $_SESSION['se_wizard_objects']['hosts'];
            $hostgroups = $_SESSION['se_wizard_objects']['hostgroups'];
        }
    }

    //preselections
    $contacts = grab_request_var('contacts', array());
    $contactgroups = grab_request_var('contactgroups', array());
    $timeperiod = grab_request_var('timeperiod', array(0));

    //populate and prepopulate list options
    $contact_opts = get_tbl_opts('contact', $contacts);
    $contactgroup_opts = get_tbl_opts('contactgroup', $contactgroups);
    $timeperiod_opts = get_tbl_opts('timeperiod', $timeperiod);

    //previous stage values
    $first = grab_request_var('first', 5);
    $last = grab_request_var('last', 10);
    $interval = grab_request_var('interval', 5);
    $options = grab_request_var('options', array());
    $config = grab_request_var('config', '');

    //increment notification counts if we're chaining
    if ($level > 1) {
        $first = $last;
        $last = $first + 5;
    }

    //////////////html for stage//////////////

    //show what configs we're building
    $config_list = '';
    $config_name = '';
    $config_class = '';
    $config_input = '';

    // Conditional elements for host / service type
    if ($type == 'host') {
        $config_class = 'hide';
        $config_name = grab_request_var('config_name', '');
        $config_input = "<div style='margin-bottom: 10px;'>
                            <div style='line-height: 22px;'><label>" . _("Config Name") . "</label></div>
                            <div class='input-group'>
                                <input type='text' name='config_name' value='{$config_name}' class='required form-control'>
                                <div class='input-group-addon'>_lv{$level}</div>
                            </div>
                        </div>";
        $esc_options = "<label class='btn btn-xs btn-default'>
                            <input name='options[]' type='checkbox' class='checkbox' value='d'> "._('Down')." 
                        </label>";
    } else {
        foreach ($services as $service) {
            $config_list .= "<div>{$service['config_name']}_lv{$level}</div>\n";
        }

        $esc_options = "<label class='btn btn-xs btn-default'>
                            <input name='options[]' type='checkbox' class='checkbox' value='w'> "._('Warning')." 
                        </label>
                        <label class='btn btn-xs btn-default'>
                            <input name='options[]' type='checkbox' class='checkbox' value='c'> "._('Critical')." 
                        </label>";
    }

    //check for errors if we're coming back from stage 4
    $feedback = '';
    if ($errors[0] > 0 || $level > 1) {
        if ($errors[0] == 0) {
            $msg_type = FLASH_MSG_SUCCESS;
        } else {
            $msg_type = FLASH_MSG_ERROR;
        }
        flash_message($errors[1], $msg_type);
    }

    //echo "FEEDBACK: $feedback <br />";
    //array_dump($errors);

    //hide back button after first save
    $back = ($level > 1) ? '' : " <button type='button' class='btn btn-sm btn-default' id='back' name='back'><i class='fa fa-chevron-left'></i> "._('Back')."</button>";

    $output = "
    <div id='ew_stage_4'>

        <div id='prompt'>" . _("Escalations can have any number of <strong>escalation levels</strong> for a host or service. Original contacts for a host or service are not preserved during an escalation unless they are also  defined as an escalated contact. This wizard can be used to create several levels of escalations for the  selected set of hosts or services. To create additional escalation levels, click <strong>Save and Add Another Escalation</strong>.  When you've completed defining escalation levels for this selection of objects, click <strong>Done</strong> to Apply Configuration") . "</div>

        <h2>Escalation Level {$level}</h2>
        {$feedback}

        <div id='formdiv'>
            <form id='mainform' method='post' action='escalationwizard.php'>
                <div id='formcontent'>
                    <div style='float: left; width: 300px; margin-right: 25px;'>
                        {$config_input}
                        <div style='margin-bottom: 10px;'>
                            <div style='line-height: 22px;'><label>" . _("Contacts") . "</label></div>
                            <select name='contacts[]' id='contacts' class='form-control' style='width: 100%; height: 89px;' multiple='multiple'>
                                {$contact_opts}
                            </select>
                        </div>
                        <div style='margin-bottom: 10px;'>
                            <div style='line-height: 22px;'><label>" . _("Contact Groups") . "</label></div>
                            <select name='contactgroups[]' id='contactgroups' class='form-control' style='width: 100%; height: 89px;' multiple='multiple'>
                                {$contactgroup_opts}
                            </select>
                        </div>
                        <div>
                            <div style='line-height: 22px;'><label>" . _("Escalation Timeperiod") . "</label></div>
                            <select name='timeperiod' class='form-control' style='width: 100%;' id='timeperiod'>
                                {$timeperiod_opts}
                            </select>
                        </div>
                    </div>
                    <div style='float: left; width: 260px;'>
                        <div style='margin-bottom: 10px;'>
                            <div style='line-height: 22px;'><label>" . _("First Notification") . "</label> <i style='vertical-align: text-top;' title='"._('First Notification')."' class='pop fa fa-14 fa-question-circle' data-content='"._('This directive is a number that identifies the first notification for which this escalation is effective.')."<br><br>"._('For instance, if you set this value to 3, this escalation will only be used if the service is in a non-OK state long enough for a third notification to go out.')."'></i></div>
                            <div><input type='text' name='first' id='first' value='{$first}' style='width: 100%;' class='required form-control'></div>
                        </div>
                        <div style='margin-bottom: 10px;'>
                            <div style='line-height: 22px;'><label>" . _("Last Notification") . "</label> <i style='vertical-align: text-top;' title='"._('Last Notification')."' class='pop fa fa-14 fa-question-circle' data-content='"._('This directive is a number that identifies the last notification for which this escalation is effective.')."<br><br>"._('For instance, if you set this value to 5, this escalation will not be used if more than five notifications are sent out for the service. Setting this value to 0 means to keep using this escalation entry forever (no matter how many notifications go out).')."'></i></div>
                            <div><input type='text' name='last' id='last' value='{$last}' style='width: 100%;' class='required form-control'></div>
                        </div>
                        <div style='margin-bottom: 10px;'>
                            <div style='line-height: 22px;'><label>" . _("Notification Interval") . "</label> <i style='vertical-align: text-top;' title='"._('Notification Interval')."' class='pop fa fa-14 fa-question-circle' data-content='"._('This directive is used to determine the interval at which notifications should be made while this escalation is valid.')."'></i></div>
                            <div class='input-group'>
                                <input type='text' name='interval' id='interval' value='{$interval}' class='required form-control'>
                                <div class='input-group-addon'>mins</div>
                            </div>
                        </div>
                        <div>
                            <div style='line-height: 22px;'><label>" . _("Escalation Options") . "</label></div>
                            <div class='btn-group' data-toggle='buttons'>
                                {$esc_options}
                                <label class='btn btn-xs btn-default'>
                                    <input name='options[]' type='checkbox' class='checkbox' value='r'> "._('Up')." 
                                </label>
                                <label class='btn btn-xs btn-default'>
                                    <input name='options[]' type='checkbox' class='checkbox' value='u'> "._('Unreachable')." 
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class='clear'></div>

                    <div class='{$config_class}' style='margin-top: 20px; width: 440px;'>
                        <table class='table table-condensed table-no-margin table-striped table-bordered'>
                            <tr><th>" . _("The following escalations will be generated from this wizard") . "</th></tr>
                            <tr><td>{$config_list}</td></tr>
                        </table>
                    </div>

                    <div style='margin-top: 20px;'>
                        {$back}
                        <button type='button' class='btn btn-sm btn-info' id='save' name='save' onclick='completestage()'>" . _("Save and Add Another Level") . "</button>
                        <button type='button' class='btn btn-sm btn-primary tt-bind' title='"._('Save and Apply Configuration')."' id='alldone' name='alldone' onclick=\"javascript:finish_wizard();\">" . _("Finish") . "</button>
                        <br /><br />
                        <input type='hidden' name='done' value='false' />
                        <input type='hidden' name='stage' id='stage' value='4' /> 
                        <input type='hidden' name='level' id='stage' value='{$level}' />
                        <input type='hidden' name='objecttype' id='objecttype' value='{$type}' />
                        <input type='hidden' name='submitted' id='submitted' value='true' />
                    </div>
                </div>
            </form> 
        </div>
    </div>          
    
    ";

    return $output;
}


function ew_save_escalation($type)
{
    global $myDebug;
    if ($type == 'service') {
        $services = $_SESSION['se_wizard_objects']['services'];
    } else {
        $hosts = $_SESSION['se_wizard_objects']['hosts'];
        $hostgroups = $_SESSION['se_wizard_objects']['hostgroups'];
    }

    $level = grab_request_var('level', 1);

    //populate and prepopulate list options
    $contacts = grab_request_var('contacts', array());
    $contactgroups = grab_request_var('contactgroups', array());
    $timeperiod = grab_request_var('timeperiod', 'NULL');

    //previous stage values
    $first = intval(grab_request_var('first', 5));
    $last = intval(grab_request_var('last', 10));
    $interval = intval(grab_request_var('interval', 5));
    $options = grab_request_var('options', array());

    $table = ($type == 'host') ? 'hostescalation' : 'serviceescalation';
    $lnkTable = ucfirst($table);

    //determine relationship flags
    $intSelContact = empty($contacts) ? 0 : 1;
    $intSelContactGroup = empty($contactgroups) ? 0 : 1;

    //escalation options
    $strEO = implode(',', $options);

    $errors = 0;
    $msg = '';

    if ($type == 'service') {
        foreach ($services as $serviceid => $service) {

            $hosts = grab_array_var($service, 'host_name', array());
            $hostgroups = grab_array_var($service, 'hostgroup_name', array());
            //host and hostgroup relationships?
            $intSelHost = empty($hosts) ? 0 : 1;
            $intSelHostGroup = empty($hostgroups) ? 0 : 1;

            //force naming convention for escalation levels
            $config = escape_sql_param($service['config_name'], DB_NAGIOSQL) . "_lv{$level}";

            //run main query
            $query = "INSERT INTO `tbl_{$table}` SET `config_name`='{$config}', `host_name`={$intSelHost},
                 `hostgroup_name`=$intSelHostGroup, `contacts`=$intSelContact,
                `contact_groups`=$intSelContactGroup, `first_notification`={$first}, `last_notification`={$last},
                `notification_interval`={$interval}, `escalation_period`='" . escape_sql_param($timeperiod, DB_NAGIOSQL) . "',
                `escalation_options`='{$strEO}', `config_id`=1, `active`='1', `last_modified`=NOW()";
            if ($type == 'service')
                $query .= ",`service_description`=1";

            exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
            //get service escalation ID we just made
            $insertID = mysql_insert_id();

            //bail now if things went bad
            $errmsg = mysql_error();
            if ($errmsg != '') {
                $errors++;
                $msg .= "<span class='error'>" . _("There was a problem saving the configuration") . ": \"$config\".</span><br />";
                continue;
            }

            //handle host relationships
            if ($intSelHost) {
                foreach ($hosts as $id) {
                    $query = "INSERT INTO `tbl_lnk{$lnkTable}ToHost` SET `idMaster`={$insertID}, `idSlave`={$id}";
                    exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
                    //echo "QUERY: ".$query." ".mysql_error()."<br />";
                }
            }

            //handle hostgroup relationships
            if ($intSelHostGroup) {
                foreach ($hostgroups as $id) {
                    $query = "INSERT INTO `tbl_lnk{$lnkTable}ToHostgroup` SET `idMaster`={$insertID}, `idSlave`={$id}";
                    exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
                    //echo "QUERY: ".$query." ".mysql_error()."<br />";
                }
            }
            //handle contact relationships
            if ($intSelContact) {
                foreach ($contacts as $id) {
                    $query = "INSERT INTO `tbl_lnk{$lnkTable}ToContact` SET `idMaster`={$insertID}, `idSlave`={$id}";
                    exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
                    //echo "QUERY: ".$query." ".mysql_error()."<br />";
                }
            }
            //handle contactgroup relationships
            if ($intSelContactGroup) {
                foreach ($contactgroups as $id) {
                    $query = "INSERT INTO `tbl_lnk{$lnkTable}ToContactgroup` SET `idMaster`={$insertID}, `idSlave`={$id}";
                    exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
                    //echo "QUERY: ".$query." ".mysql_error()."<br />";
                }
            }

            //service escalation to service relationships
            if ($type == 'service') {
                $query = "INSERT INTO `tbl_lnk{$lnkTable}ToService` SET `idMaster`={$insertID}, `idSlave`={$serviceid}";
                exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
                //echo "QUERY: ".$query." ".mysql_error()."<br />";
            }

            $msg .= _("Escalation") . " <b>".$config."</b> " . _("saved successfully.");

        } //end objects loop
    } else { //type is host
        //host and hostgroup relationships?
        $intSelHost = empty($hosts) ? 0 : 1;
        $intSelHostGroup = empty($hostgroups) ? 0 : 1;
        $config_name = grab_request_var('config_name');

        //force naming convention for escalation levels
        $config = escape_sql_param($config_name, DB_NAGIOSQL) . "_lv{$level}";

        //run main query
        $query = "INSERT INTO `tbl_{$table}` SET `config_name`='{$config}', `host_name`={$intSelHost},
             `hostgroup_name`=$intSelHostGroup, `contacts`=$intSelContact,
            `contact_groups`=$intSelContactGroup, `first_notification`={$first}, `last_notification`={$last},
            `notification_interval`={$interval}, `escalation_period`='" . escape_sql_param($timeperiod, DB_NAGIOSQL) . "',
            `escalation_options`='{$strEO}', `config_id`=1, `active`='1', `last_modified`=NOW()";
        exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
        //get service escalation ID we just made
        $insertID = mysql_insert_id();

        //bail now if things went bad
        $errmsg = mysql_error();
        if ($errmsg != '') {
            $errors++;
            $msg .= "<span class='error'>" . _("There was a problem saving the configuration") . ": \"$config\".</span><br />";
        }

        //handle host relationships
        if ($intSelHost) {
            foreach ($hosts as $id) {
                $query = "INSERT INTO `tbl_lnk{$lnkTable}ToHost` SET `idMaster`={$insertID}, `idSlave`={$id}";
                exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
            }
        }

        //handle hostgroup relationships
        if ($intSelHostGroup) {
            foreach ($hostgroups as $id) {
                $query = "INSERT INTO `tbl_lnk{$lnkTable}ToHostgroup` SET `idMaster`={$insertID}, `idSlave`={$id}";
                exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
            }
        }
        //handle contact relationships
        if ($intSelContact) {
            foreach ($contacts as $id) {
                $query = "INSERT INTO `tbl_lnk{$lnkTable}ToContact` SET `idMaster`={$insertID}, `idSlave`={$id}";
                exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
            }
        }
        //handle contactgroup relationships
        if ($intSelContactGroup) {
            foreach ($contactgroups as $id) {
                $query = "INSERT INTO `tbl_lnk{$lnkTable}ToContactgroup` SET `idMaster`={$insertID}, `idSlave`={$id}";
                exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
            }
        }

        $msg .= _("Escalation") . " <b>".$config."</b> " . _("saved successfully.");
    }

    //echo $msg;

    return array($errors, $msg);
}


///////////////////////////////END STAGES /////////////////////////////


//////////////////////////FUNCTIONS/////////////////////////////////////
/**
 *    get html option list based on object type
 *
 * @param string $type   'host' | 'service'
 * @param string $search form input search
 *
 * @return string $output html option string
 */
function get_option_list($type, $search)
{
    $output = "";
    if ($type == 'host') {
        $opts = get_all_ccm_hosts($search);
        $name = 'host_name';
    } else {
        $opts = get_all_ccm_services($search);
        $name = 'name';
    }

    foreach ($opts as $row) {
        $output .= "<option value='{$row['id']}'>{$row[$name]}</option>\n";
    }

    return $output;
}


/**
 *    get related option list for a single service ID
 *
 * @param int $id : service id
 *
 * @return string $hostopts: html option list
 */
function get_html_list($id, $hosts, $type = 'host')
{
    $field = ($type == 'host') ? 'host_name' : 'hostgroup_name';

    foreach ($hosts as $host) {
        $html .= "<div class='checkbox'><label><input type='checkbox' checked='checked' name='services[{$id}][{$field}][]' value='{$host['id']}' />{$host[$field]}</label></div>";
    }

    return $html;
}


/**
 *    returns a select list of nagios objects, with preselections
 *
 * @param string $table     : nagios object type
 * @param mixed  $preselect : array of preselected object ID's
 *
 * @return string $options: html option string
 */
function get_tbl_opts($table, $preselect = array())
{
    global $myDebug;

    //exception for timeperiod selection
    if (!is_array($preselect))
        $preselect = array($preselect);

    $query = "SELECT id,{$table}_name FROM tbl_{$table} ORDER BY {$table}_name ASC";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, $myDebug);

    $options = '';
    foreach ($rs as $r) {
        $options .= "<option value='{$r['id']}' ";
        if (in_array($r['id'], $preselect)) $options .= " selected='selected' ";
        $options .= ">" . $r[$table . '_name'] . "</options>\n";
    }

    return $options;
}

/*  
*   gets more detailed info fields for selected $ids
*   @param string $type 'host' | 'service'
*   @param mixed $ids array of selected object ids
*   @return string $rows html string of table rows
*/
function get_object_details($type, $id)
{
    global $myDebug;

    if ($type == 'service') {
        $related_hosts = array();
        $related_hostgroups = array();
        //host relationships
        $sql = "SELECT a.id, a.host_name FROM tbl_host a
                INNER JOIN tbl_lnkServiceToHost b on b.idSlave=a.id 
                WHERE b.idMaster = '{$id}'";

        if ($rs = exec_sql_query(DB_NAGIOSQL, $sql, $myDebug)) {
            foreach ($rs as $row)
                $related_hosts[] = $row;
        }

        //hostgroup relationships
        $sql = "SELECT a.id, a.hostgroup_name FROM tbl_hostgroup a
        INNER JOIN tbl_lnkServiceToHostgroup b on b.idSlave=a.id 
        WHERE b.idMaster = '{$id}'";

        if ($rs = exec_sql_query(DB_NAGIOSQL, $sql, $myDebug)) {
            foreach ($rs as $row)
                $related_hostgroups[] = $row;
        }

    }
    if ($type == 'host')
        $query = "SELECT `id`,`host_name`,`alias`,`display_name` FROM tbl_host WHERE `id`='{$id}'";
    else //services
        $query = "SELECT `id`,`config_name`,`service_description` FROM tbl_service WHERE id='{$id}'";

    //echo $query."<br />";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
    //echo "COUNT ".$rs->recordCount()."<br />";
    //$rows = rs_to_table_row($rs,$type);
    foreach ($rs as $r)
        $object = $r;

    $object['hosts'] = $related_hosts;
    $object['hostgroups'] = $related_hostgroups;

    return $object;

} 


/**
 *    Builds the html set of table rows based on result set from $rs
 *
 * @param mixed  $rs   ADODB database object
 * @param string $type 'host' | 'service'
 *
 * @return string $rows html string of table rows
 */

function rs_to_table_row($rs, $type)
{
    $rows = '';
    $count = 0;
    $img = '<img border="0" title="Close" alt="Close" src="' . get_base_url() . '/images/b_close.png">';
    if ($type == 'host') {
        $rows .= "<tr><th>" . _("Host Name") . "</th>
                    <th>" . _("New Name") . "</th>
                    <th>" . _("Alias") . "</th>
                    <th>" . _("Display Name") . "</th>
                    <th>" . _("Remove") . "</th></tr>\n";
        foreach ($rs as $r) {
            $class = ($count++ % 2 == 1) ? 'even' : 'odd';
            $rows .= "
            <tr class='{$class}' id='tr_{$count}'>
                <td><input type='hidden' name='rows[{$count}][id]' value='{$r['id']}' />
                    <input type='hidden' name='rows[{$count}][old_name]' value='{$r['host_name']}' />
                    {$r['host_name']}
                </td>
                <td>
                    <input type='text' class='td' name='rows[{$count}][host_name]' value='{$r['host_name']}' />
                </td>               
                <td>
                    <input type='text' class='td' name='rows[{$count}][alias]' value='{$r['alias']}' />
                </td>
                <td>
                    <input type='text' class='td' name='rows[{$count}][display_name]' value='{$r['display_name']}' />
                </td>
                <td class='center'><a class='remove' href='javascript:remove(\"tr_{$count}\")'> {$img} </a></td>
            </tr>\n";
        }

    } else { //service
        $rows .= "<tr><th>" . _("Config Name") . "</th>
                <th>" . _("Old Service Description") . "</th>
                <th>" . _("New Service Description") . "</th>
                <th>" . _("Affected Hosts") . "</th>
                <th>" . _("Remove") . "</th></tr>\n";
        foreach ($rs as $r) {
            $class = ($count++ % 2 == 1) ? 'even' : 'odd';
            $rows .= "
            <tr class='{$class}' id='tr_{$count}'>
                <td><input type='hidden' name='rows[{$count}][id]' value='{$r['id']}' />
                    <input type='hidden' name='rows[{$count}][old_name]' value='{$r['service_description']}' />
                    <input type='hidden' name='rows[{$count}][config_name]' value='{$r['config_name']}' />
                    {$r['config_name']}
                </td>
                <td>
                    {$r['service_description']}
                </td>
                <td>
                    <input type='text' class='td' name='rows[{$count}][service_description]' value='{$r['service_description']}' />
                </td>
                <td class='td_hostlist'>" . get_service_to_host_list($r['id']) . "</td>
                <td><a class='remove' href='javascript:remove(\"tr_{$count}\")'> {$img} </a></td>
            </tr>\n";
        }
    }

    //echo "ROWCOUNT: $count <br />";
    return $rows;
}

/**
 *    retrieves a full list of id->host_names from CCM DB
 *
 * @param string $search optional search string
 *
 * @return mixed $rs ADODB OBJECT type
 */
function get_all_ccm_hosts($search)
{
    $query = "SELECT `id`,`host_name` FROM tbl_host";
    if ($search != '')
        $query .= " WHERE `host_name` LIKE '%{$search}%'";
    $query .= " ORDER BY `host_name`";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    return $rs;
}

/**
 *    retrieves a full list of id->host_names from CCM DB
 *
 * @param string $search optional search string
 *
 * @return mixed $services array of services with id and host::service
 */
function get_all_ccm_services($search)
{

    $query = "SELECT id,config_name,service_description FROM tbl_service";
    if ($search != '')
        $query .= " WHERE `config_name` LIKE '%{$search}%' OR `service_description` LIKE '%{$search}%' ";
    $query .= " ORDER BY `config_name`,`service_description`";

    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    $services = array();
    foreach ($rs as $row) {
        $services[] = array(
            'name' => $row['config_name'] . ' :: ' . $row['service_description'],
            'id' => $row['id'],
        );
    }

    return $services;
}



/**
 *    Runs multiple SQL queries and gets all related hosts for a particular service.
 *    This function does NOT handle service->hostgroup->hostgroup->host relationships, but should do everything else
 *
 * @param int    $id         service ID from nagiosql tbl_service
 * @param string $hoststring REFERENCE variable to hoststring that will be used in SQL IN() search
 *
 * @return mixed $hosts array of all related hosts
 */
function get_service_to_host_relationships($id, &$hoststring)
{

    $hoststring = '';
    //handle service->host relationships
    $query = "SELECT a.id,a.host_name FROM tbl_lnkServiceToHost b JOIN  tbl_host a ON a.id=b.idSlave WHERE b.idMaster={$id}";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);

    foreach ($rs as $row) {
        $hoststring .= "'" . $row['host_name'] . "',";
        $hosts[] = $row['host_name']; //push related hosts into array
    }

    //check for service->hostgroup relationships
    //check relationships for hostgroup->host
    $query = "SELECT b.host_name FROM tbl_lnkHostgroupToHost a
            JOIN tbl_host b ON a.idSlave=b.id WHERE idMaster 
            IN (SELECT idSlave FROM tbl_lnkServiceToHostgroup WHERE idMaster={$id})";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    if ($rs && $rs->recordCount() > 0) { //there are hostgroup relationships
        foreach ($rs as $row) {
            $hoststring .= "'" . $row['host_name'] . "',";
            $hosts[] = $row['host_name']; //push related hosts into array
        }
    }

    //check relationships host->hostgroup
    $query = "SELECT b.host_name FROM tbl_lnkHostToHostgroup a
            JOIN tbl_host b ON a.idMaster=b.id WHERE idSlave 
            IN (SELECT idSlave FROM tbl_lnkServiceToHostgroup WHERE idMaster={$id})";

    $rs = exec_sql_query(DB_NAGIOSQL, $query, true);
    if ($rs && $rs->recordCount() > 0) { //there are hostgroup relationships
        foreach ($rs as $row) {
            $hoststring .= "'" . $row['host_name'] . "',";
            $hosts[] = $row['host_name']; //push related hosts into array
        }
    }

    //remove last comma
    $hoststring = substr($hoststring, 0, (strlen($hoststring) - 1));
    //echo $hoststring."<br />";

    //$hoststring will get returned as a reference variable

    return $hosts;

}

function get_service_to_host_list($id)
{

    $hosts = get_service_to_host_relationships($id, $hoststring);
    $output = "<ul class='hostlist'>\n";
    foreach ($hosts as $host)
        $output .= "<li>{$host}</li>\n";

    $output .= "</ul>\n";

    return $output;

}

function get_object_name($type, $id)
{
    global $myDebug;

    $field = $type == 'service' ? 'service_description' : 'host_name';
    $query = "SELECT `{$field}` FROM tbl_" . escape_sql_param($type, DB_NAGIOSQL) . " WHERE id='{$id}'";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, $myDebug);
    foreach ($rs as $row)
        $name = $row[$field];

    return $name;

}



?>