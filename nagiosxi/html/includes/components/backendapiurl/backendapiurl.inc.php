<?php
//
// Backend API URL Component
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$backendapiurl_component_name = "backendapiurl";
backendapiurl_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function backendapiurl_component_init()
{
    global $backendapiurl_component_name;

    $args = array(
        COMPONENT_NAME => $backendapiurl_component_name,
        COMPONENT_VERSION => '1.0.1',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Provides information on the URLs used to access the Nagios XI backend API."),
        COMPONENT_TITLE => _("Backend API URL"),
        COMPONENT_CONFIGFUNCTION => "backendapiurl_component_config_func"
    );

    register_component($backendapiurl_component_name, $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
//CONFIG FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function backendapiurl_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    global $backendapiurl_component_name;

    $result = 0;
    $output = "";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            $username = grab_array_var($inargs, "username", "");
            $component_url = get_component_url_base($backendapiurl_component_name);

            // get list of users from the backend
            $args = array(
                "cmd" => "getusers",
            );
            $xmlusers = get_backend_xml_data($args);

            $output = '';

            $output .= '
                <p>
                <b>' . _('Developers') . ':</b>
                ' . _('The Nagios XI backend API can be used to access current and historical information on monitored hosts and services for integration into third-party frontends.  In order to access XML data via the backend API you must pass a username and a backend ticket to identify yourself.  Without the proper credentials, no data is returned.') . '
                </p>

                <div class="message">
                    <ul class="errorMessage">
                        <li><i class="fa fa-exclamation-triangle"></i> <strong>'._('Deprecated API').'</strong> '._('This feature is now deprecated. Please use the new REST API that is').' <a href="'.get_base_url().'help/?xiwindow=api.php" target="_top">'._('documented here').'</a>.</li>
                    </ul>
                </div>';

            $base_url = get_base_url();

            if ($username != "") {

                $uid = get_user_id($username);
                if ($uid == 0)
                    $backend_ticket = "";
                else
                    $backend_ticket = get_user_attr($uid, "backend_ticket");

                $output .= '

                <h5 class="ul">' . _('Backend API URLs') . '</h5>
                
                <p>' . _('You can use the URLs below to fetch information from the Nagios XI backend API.') . '
                <b>' . _('Note') . ':</b> ' . _('It is important to retain the <em>username</em> and <em>ticket</em> query parameters.') . '</p>
                
                <table class="table table-condensed table-no-border table-auto-width">
                    <thead>
                        <tr>
                            <th>' . _('Data Type') . '</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                ';

                $opts = array(
                    _("Current Host Status") => "gethoststatus",
                    _("Current Service Status") => "getservicestatus",
                    _("Current Program Status") => "getprogramstatus",
                    _("Current Program Performance") => "getprogramperformance",
                    _("System Statistics") => "getsysstat",
                    _("Log Entries") => "getlogentries",
                    _("State History") => "getstatehistory",
                    _("Comments") => "getcomments",
                    _("Scheduled Downtime") => "getscheduleddowntime",
                    _("Users") => "getusers",
                    _("Contact") => "getcontacts",
                    _("Hosts") => "gethosts",
                    _("Services") => "getservices",
                    _("Hostgroups") => "gethostgroups",
                    _("Servicegroups") => "getservicegroups",
                    _("Contactgroups") => "getcontactgroups",
                    _("Hostgroup Members") => "gethostgroupmembers",
                    _("Servicegroup Members") => "getservicegroupmembers",
                    _("Contactgroup Members") => "getcontactgroupmembers",
                );

                $x = 0;
                foreach ($opts as $desc => $urlopts) {
                    $x++;

                    $output .= '
                        <tr>
                            <td><label>' . $desc . ':</label></td>
                            <td>
                                <input class="form-control" type="text" size="80" name="url' . $x . '" value="' . $base_url . 'backend/?cmd=' . $urlopts . '&username=' . htmlentities($username) . '&ticket=' . htmlentities($backend_ticket) . '">
                            </td>
                        </tr>';
                }

                $output .= '
                    </tbody>
                </table>';

            }

            $output .= '

            <h5 class="ul">' . _('Account Selection') . '</h5>
            
            <p>' . _('Select the user account you would like to get backend API URLs for.') . '</p>
                
            <table class="table table-condensed table-no-border table-auto-width">
                <tr>
                    <td><label>' . _('User') . ':</label></td>
                    <td>
                        <select name="username" class="form-control">
                            <option value="">' . _('SELECT ONE') . '</option>';

                            if ($xmlusers) {
                                foreach ($xmlusers->user as $u) {
                                    $uid = get_user_id($u->username);
                                    $output .= "<option value='" . $u->username . "' " . is_selected($username, strval($u->username)) . ">" . $u->name . " (" . $u->username . ")</option>\n";
                                }
                            }

            $output .= '
                        </select>
                    </td>
                </tr>
            </table>';

            break;

        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            $username = grab_array_var($inargs, "username");
            if ($username == "") {
                $result = 1;
                $errmsg = array();
                $errmsg[] = _("Please select a username to obtain backend URL information.");
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
            }

            $okmsg = array();
            $outargs[COMPONENT_INFO_MESSAGES] = $okmsg;

            break;

        default:
            break;

    }

    return $output;
}