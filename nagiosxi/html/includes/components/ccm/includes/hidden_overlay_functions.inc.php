<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: hidden_overlay_function.inc.php
//  Desc: Creates the HTML for all of the hidden overlays.
//

/**
 * Builds a hidden overlay div and populates values based on parameters given 
 *
 * @param string   $type nagios object type (host, service, command, etc)
 * @param string   $optionValue the DB fieldname for that objects name (host_name, service_description, template_name)
 * @param bool $BA boolean switch, are there two-way relationships possible for this object (host->hostgroup, hostgroup->host) 
 * @param bool $tplOpts boolean switch for showing template options 
 * @param string $fieldArray optional specification for which select list to use 
 *
 * @return string returns populated html select lists for the $type object
 */
function build_hidden_overlay($type, $optionValue, $BA=false, $tplOpts=false, $fieldArray='', $exactType='')
{
    global $FIELDS;
    global $unique;
    $curr_page = ccm_grab_request_var('type');

    $full_title = _('Manage') . ' ' . ccm_get_full_title($type, true);

    $Title = ucfirst($type); 
    $Titles = ucfirst($type).'s'; 
    if ($fieldArray == '') {
        $fieldArray = 'sel'.$Title.'Opts'; 
    }

    $html = "<!-- ------------------------------------ {$Titles} ($type) --------------------- -->

    <div class='overlay' id='{$type}Box'>

    <div class='overlay-title'>
        <h2>{$full_title}</h2>
        <div class='overlay-close ccm-tt-bind' data-placement='left' title='"._('Close')."' onclick='killOverlay(\"{$type}Box\")'><i class='fa fa-times'></i></div>
        <div class='clear'></div>
    </div>

    <div class='left'>
        <div class='listDiv'>
            <div class='filter'>
                <span class='clear-filter ccm-tt-bind' title='"._('Clear')."'><i class='fa fa-times fa-14'></i></span>
                <input type='text' id='filter{$Titles}' class='form-control fc-fl' style='border-bottom: 0;' placeholder='"._('Filter')."...'>
            </div>
            <select name='sel{$Titles}[]' class='form-control fc-m lists' multiple='multiple' id='sel{$Titles}' ondblclick='transferMembers(\"sel{$Titles}\", \"tbl{$Titles}\", \"{$type}s\")'>
                <!-- option value is tbl ID -->
    "; 
    
    // Special case for hostService array
    if ($type == 'hostservice') {
        foreach ($FIELDS['selHostServiceOpts'] as $key => $opt) {
            $disabled = '';
            $html .= "<option ";
            if (grab_array_var($opt, 'active', 1) == 0) { $disabled = " disabled='disabled' class='disabled'"; }
            if (in_array($key, $FIELDS['pre_hostservices_AB'])) $html .= "selected='selected' ";
            if (in_array($key, $FIELDS['pre_hostservices_BA'])) $disabled .= "disabled='disabled' class='hiddenDependency' ";
            $html .= " id='".$unique++."' title='".$opt['name']."' value='".$key."'".$disabled.">".$opt['name']."</option>";
        }
    } else if ($type == 'parent') {
        foreach ($FIELDS['selParentOpts'] as $key => $opt) {
            // The pre_hosts_BA are child elements of a parent host
            $pre_array = isset($FIELDS['pre_'.$type.'s_AB']) ? $FIELDS['pre_'.$type.'s_AB'] : $FIELDS['pre_'.$type.'s'] ;
            $child = '';
            $html .= '<option ';
            if (in_array($opt['id'], $pre_array)) $html .= "selected='selected' orderid='".array_search($opt['id'], $pre_array)."' ";
            if (in_array($opt['id'], $FIELDS['pre_hosts_BA'])) { $html .= 'disabled="disabled" class="child" '; $child = ' ['._('Child').']'; }
            else if ($opt['active'] == 0) { $html .= ' disabled="disabled" class="disabled"'; }
            $html .= ' id="'.$unique++.'" title="'.$opt[$optionValue].'" value="'.$opt['id'].'">'.$opt[$optionValue].$child.'</option>';
        }
    }
    // If there are two-way database relationships for this object
    else if ($BA == true) {
        foreach ($FIELDS[$fieldArray] as $opt) {
            $html .= '<option ';
            if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_AB'])) $html .= "selected='selected' ";
            if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_BA'])) {
                $html .= "disabled='disabled' class='hiddenDependency' title='"._('Object has a relationship established elsewhere')."' ";
            } else if (grab_array_var($opt,'active',1) == 0 && $opt[$optionValue] != "*") {
                // If the object is not active we should turn it to disabled
                $html .= "disabled='disabled' class='disabled' ";
            }

            if ($type == 'host' || $type == 'hostgroup') {
                if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_AB_exc'])) { $html .= 'data-exclude="1" '; }
            }

            $html .= " id='".$unique++."' title='{$opt[$optionValue]}' value='".$opt['id']."'>".$opt[$optionValue].'</option>';
        }
    // Only one-way DB relationships (i.e. service dependency)
    } else {
        $pre_array = isset($FIELDS['pre_'.$type.'s_AB']) ? $FIELDS['pre_'.$type.'s_AB'] : $FIELDS['pre_'.$type.'s'] ;
        
        if ($exactType == "serviceescalation" || $exactType == "servicedependency") {
            $uniq_services = array();
            foreach ($pre_array as $i) {
                foreach ($FIELDS[$fieldArray] as $v) {
                    if ($v['id'] == $i) {
                        $html .= '<option selected="selected" ';
                        // Display hostnames just for the service/host dependencies (and don't display in serviceescalation form)
                        $name = $v[$optionValue];
                        $html .= " id='".$unique++."' title='{$name}' value='".$v['id']."'>".$name.'</option>';
                        $uniq_services[] = $v[$optionValue];
                    }
                }
            }
        }

        foreach ($FIELDS[$fieldArray] as $opt) {

            // If it needs to be a unique service let's only display a service name once
            if ($exactType == "serviceescalation" || $exactType == "servicedependency") {
                if (!in_array($opt[$optionValue], $uniq_services)) {
                    $uniq_services[] = $opt[$optionValue];
                } else {
                    continue;
                }
            }

            $html.= '<option ';
            $disabled = "";
            if (grab_array_var($opt,'active',1) == 0) { $disabled = " disabled='disabled' class='disabled'"; }
            if (in_array($opt['id'], $pre_array)) {
                $html .= "selected='selected' orderid='".array_search($opt['id'], $pre_array)."'";
            }

            if ($type == 'host' || $type == 'hostgroup') {
                if (in_array($opt['id'], $FIELDS['pre_'.$type.'s_AB_exc'])) { $html .= 'data-exclude="1" '; }
            }

            // Display hostnames just for the service/host dependencies (and don't display in serviceescalation form)
            $name = $opt[$optionValue];
            if ($type == "service" && $exactType != "serviceescalation" && $exactType != "servicedependency") {
                if ($name != "*") {
                    $name = $opt['host_name'] . " - " . $name;
                }
            }

            $html .= " id='".$unique++."' title='{$name}' value='".$opt['id']."'".$disabled.">".$name.'</option>';
        }
    }
    $html .= "  </select>
                <div class='overlay-left-bottom'>
                    <button type='button' class='btn btn-sm btn-primary fl' onclick='transferMembers(\"sel{$Titles}\", \"tbl{$Titles}\", \"{$type}s\")'>"._("Add Selected")." <i class='fa fa-chevron-right'></i></button>";

    if ($tplOpts==true) { //template options     
        
        $radType = $type.'s';

        // Deal with inconsistent DB naming convention in NagiosQL. Make sure we have the correct form field name
        $radType = (isset($FIELDS['contact_groups_tploptions']) && $type=='contactgroup') ? 'contact_groups' : $radType;
        $radType = (isset($FIELDS['host_name_tploptions']) && $type=='host') ? 'host_name' : $radType;
        $radType = (isset($FIELDS['hostgroup_name_tploptions']) && $type=='hostgroup') ? 'hostgroup_name' : $radType;
        $radType = ($type=='hostcommand') ? 'host_notification_commands': $radType;
        $radType = ($type=='servicecommand') ? 'service_notification_commands': $radType;
        $v = $radType.'_tploptions';

        $html .= "
            <div class='fr' style='line-height: 30px;'>
                <div class='btn-group' data-toggle='buttons'>
                    <label class='btn btn-xs btn-default ".($FIELDS[$v] == 0 ? 'active' : '' )."'>
                        <input type='radio' name='rad{$Title}' id='rad{$Title}0' value='0' ".@check($radType.'_tploptions', '0', true).">+
                    </label>
                    <label class='btn btn-xs btn-default ".($FIELDS[$v] == 1 ? 'active' : '' )."'>
                        <input type='radio' name='rad{$Title}' id='rad{$Title}1' value='1' ". @check($radType.'_tploptions', '1', true). ">"._('Null')."
                    </label>
                    <label class='btn btn-xs btn-default ".($FIELDS[$v] == 2  ? 'active' : '' )."'>
                        <input type='radio' name='rad{$Title}' id='rad{$Title}2' value='2' ". @check($radType.'_tploptions', '2', true). ">"._('Standard')."
                    </label>
                </div>
            </div>
            <div class='fr' style='line-height: 30px;'>
                <label style='margin-right: 10px;' class='ccm-tt-bind' title='{$Title} "._('inheritance options')."'>
                    <i class='fa fa-info-circle fa-14 tooltip-info' style='vertical-align: middle;'></i>
                </label>
            </div>";
    }

    $html .= "       <div class='fr ccm-label' style='margin-right: 20px;'>
                        <div><i class='fa fa-fw fa-link'></i> <span class='ccm-tt-bind qtt' title='"._('An example of a relationship that can only be linked in one direction is a child host with a host defined as the parent cannot be set as a child from the host it is already a child of.')."'>"._("Relationship defined elsewhere")."</span></div>
                        <div><i class='fa fa-fw fa-exclamation-circle'></i> "._('Inactive object')."</div>
                    </div>
                    <div class='clear'></div>
                </div>
            <div class='closeOverlay'>
                <button type='button' class='btn btn-sm btn-default' onclick='killOverlay(\"{$type}Box\")'>"._("Close")."</button>
            </div>
        </div>
    </div>
    <!-- end leftBox -->

    <div class='right'>
        <div class='right-container'>
            <table class='table table-no-margin table-small-border-bottom table-x-condensed'>
                <thead>
                    <tr>
                        <th colspan='2'>
                            <span class='thMember'>"._("Assigned")."</span>
                            <a class='fr' title='Remove All' href='javascript:void(0)' onclick=\"removeAll('tbl{$Titles}')\">"._("Remove All")."</a>
                            <div class='clear'></div>
                        </th>
                    </tr>
                </thead>
            </table>
            <div class='assigned-container'>
                <table class='table table-x-condensed table-hover table-assigned' id='tbl{$Titles}'>
                    <tbody>
                        <!-- insert selected items here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
                                  
    <!-- $type radio buttons -->  
                                        
    </div> <!-- end {$type}box --> ";  

    return $html;
}


/**
 * Creates html overlay for the command-line test output
 *
 * @return string Returns the overlay's HTML
 */
function build_command_output_box()
{  
    $html = "
    <!-- ------------------------------------ Test Check Commands --------------------- -->
    <div class='overlay' id='commandOutputBox'>
        <div class='overlay-title'>
            <h2>"._('Run Check Command')."</h2>
            <div class='overlay-close ccm-tt-bind' data-placement='bottom' title='"._('Close')."' onclick='killOverlay(\"commandOutputBox\")'><i class='fa fa-times'></i></div>
            <div class='clear'></div>
        </div>
        <div id='command_input'>
            <div class='input-group' style='width: 280px;'>
                <label class='input-group-addon'>Host Address</label>
                <input type='text' id='check_address' class='form-control text'>
            </div>
        </div>
        <button type='button' class='btn btn-sm btn-primary' id='run_command'><i class='fa fa-play' style='margin-right: 3px;'></i> " . _("Run Check Command") . "</button>
        <div id='command_output'>
        </div>
        <div>
            <button type='button' class='btn btn-sm btn-default' id='overlay-close-btn' onclick=\"killOverlay('commandOutputBox')\">"._("Close")."</button>
        </div>
    </div>"; 
    
    return $html; 
}


/**
 * Creates html overlay for the free-variable definition form 
 *
 * @return string Returns html for the free variables overlay
 */ 
function build_variable_box()
{
    $html = "
    <!-- ------------------------------------ Free Variables --------------------- -->
    <div class='overlay' id='variableBox'>

        <div class='overlay-title'>
            <h2>"._('Manage Free Variables')."</h2>
            <div class='overlay-close ccm-tt-bind' data-placement='bottom' title='"._('Close')."' onclick='killOverlay(\"variableBox\")'><i class='fa fa-times'></i></div>
            <div class='clear'></div>
        </div>

        <div class='left'>
            <input type='text' class='form-control' name='txtVariablename' id='txtVariablename' style='width:225px' placeholder='"._('Name')."'>
            <input type='text' class='form-control' name='txtVariablevalue' id='txtVariablevalue' style='width:225px' placeholder='"._('Value')."'><br />
            <div style='padding: 10px 0;'>
                <button type='button' class='btn btn-sm btn-primary' onclick='insertDefinition(false, false)'>"._("Insert")." <i class='fa fa-chevron-right'></i></a>
            </div>
        </div>
        <div class='right'>
            <div class='right-container'>
                <table class='table table-condensed table-hover table-assigned' id='tblVariables'>
                    <thead>
                        <tr>
                            <th style='width: 45%;'>"._("Name")."</th>
                            <th style='width: 45%;'>"._("Value")."</th>
                            <th style='width: 10%;'></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- insert selected items here -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class='closeOverlay'>
            <button type='button' class='btn btn-sm btn-default' onclick=\"killOverlay('variableBox')\">"._("Close")."</button>
        </div>
    </div>";

    return $html;
}