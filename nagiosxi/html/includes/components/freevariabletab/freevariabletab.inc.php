<?php
//
// Free Variables Tab Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: freevariabletab.inc.php 38 2010-06-07 22:28:29Z swilkerson $

require_once(dirname(__FILE__).'/../componenthelper.inc.php');

$freevariabletab_component_name = "freevariabletab";
freevariabletab_component_init();


////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////


function freevariabletab_component_init()
{
    global $freevariabletab_component_name;
    $versionok = freevariabletab_component_checkversion();
    
    $desc = "";
    if (!$versionok) {
        $desc = "<b>"._("Error: This component requires Nagios XI 2009R1.2C or later.")."</b>";
    }
    
    $args = array(
        COMPONENT_NAME => $freevariabletab_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Adds a new tabs on host and service detail screens to show free variables from config.  ").$desc,
        COMPONENT_TITLE => _("Free Variable Tab"),
        COMPONENT_VERSION => '1.0.1',
        COMPONENT_CONFIGFUNCTION => "freevariabletab_component_config_func"
    );
        
    register_component($freevariabletab_component_name, $args);
    
    if ($versionok) {
        register_callback(CALLBACK_SERVICE_TABS_INIT, 'freevariabletab_component_addtab');
        register_callback(CALLBACK_HOST_TABS_INIT, 'freevariabletab_component_addtab');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function freevariabletab_component_checkversion()
{
    if(!function_exists('get_product_release'))
        return false;
    if(get_product_release()<113)
        return false;
    if(!function_exists('get_nagios_session_protector'))
        return false;
    return true;
}


function freevariabletab_component_config_func($mode="", $inargs, &$outargs, &$result)
{
    $result = 0;
    $output = '';
    $component_name = "freevariabletab";

    switch ($mode)
    {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:
            $show = get_option('show_custom_variables_tab', 0);

            $output .= '';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:
            $show = 0;

            set_option('show_custom_variables_tab', $show);
            break;

        default:
            break;
    }

    return $output;
}


function freevariabletab_component_addtab($cbtype, &$cbdata)
{
    $hostname = grab_array_var($cbdata, "host");
    $servicename = grab_array_var($cbdata, "service");

    $content = '<div class="infotable_title">'._('Free Variables').'</div>';

    $freevariables = freevariabletab_component_get_freevariables($hostname, $servicename);

    if ($freevariables) {
        $content .= '<table class="table table-bordered table-striped" style="max-width: 800px;"><tr><th style="width: 200px;">'._('Name').'</th><th>'._('Value').'</th></tr>';
        
        foreach ($freevariables->customvar as $freevariable) {
            $content .= '<tr><td>'.$freevariable->name.'</td><td>'.$freevariable->value.'</td></tr>'; 
        }

        $content .= '</table>';
    } else {
        $content .= _("No free variables currently set");
    }

    $newtab = array(
        "id" => "freevariabletab",
        "title" => _("Free Variables"),
        "content" => $content,
        "icon" => '<i class="fa fa-tasks fa-14"></i>'
    );

    $cbdata["tabs"][] = $newtab;
}

    
function freevariabletab_component_get_freevariables($hostname, $servicename=null)
{
    $args = array(
        "host_name" => $hostname
    );
    
    if (!$servicename) {
        $x = get_xml_custom_host_variable_status($args);
        if ($x->customhostvarstatus->customvars) {
            return $x->customhostvarstatus->customvars;
        }
    } else {
        $args["service_description"] = $servicename;
        $x = get_xml_custom_service_variable_status($args);
        if ($x->customservicevarstatus->customvars) {
            return $x->customservicevarstatus->customvars;
        }
    }

    return false;
}
