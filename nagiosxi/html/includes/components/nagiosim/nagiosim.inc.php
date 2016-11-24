<?php 
//
// Nagios IM Integration
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

// Include the helper file
require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// Respect the loglevel
define('IM_LOGLEVEL', 0);
define('IM_LOG_DEST', get_root_dir() . '/var/components/nagiosim.log');
define('IM_LOG_DEST_SL', get_root_dir() . '/var/components/nagiosim.statechanges.log');

// Respect the name
$nagiosim_component_name = "nagiosim";

// Run the initialization function
nagiosim_component_init();

function im_log($message, $level=0, $dest=IM_LOG_DEST)
{
    // Check file permission levels
    if (fileperms(IM_LOG_DEST) != '0664') {
        chmod(IM_LOG_DEST, 0664);
    }
    if (fileperms(IM_LOG_DEST_SL) != '0664') {
        chmod(IM_LOG_DEST_SL, 0664);
    }

    if ($level < IM_LOGLEVEL) {
        return;
    }

    switch ($level) {
        case 0:
            $loglevel = 'DEBUG';
            break;
        case 1:
            $loglevel = 'INFO';
            break;
        case 2:
            $loglevel = 'WARNING';
            break;
        case 3:
            $loglevel = 'ERROR';
            break;
        default:
            $loglevel = 'DEBUG';
            break;
    }

    $now = date("m-d-Y H:i:s");
    $log_message = "{$now} {$loglevel}: {$message}" . PHP_EOL;

    // Check if we need to truncate
    if (file_exists(IM_LOG_DEST)) {
        if (filectime(IM_LOG_DEST) < (time() - (7 * 24 * 60 * 60))) {
            unlink(IM_LOG_DEST);
        }
    }
    if (file_exists(IM_LOG_DEST_SL)) {
        if (filectime(IM_LOG_DEST_SL) < (time() - (7 * 24 * 60 * 60))) {
            unlink(IM_LOG_DEST_SL);
        }
    }

    error_log($log_message, 3, $dest);
}

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function nagiosim_component_init()
{
    global $nagiosim_component_name;

    // Boolean to check for latest version
    $versionok = nagiosim_component_checkversion();

    // Component description
    $desc = _("This component integrates Nagios Incident Manager with Nagios XI events.");

    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2011R3.2 or later.") . "</b>";
    }

    // Allow for future version updates
    $im_current_version = 101;

    // All components require a few arguments to be initialized correctly.
    $args = array(
        COMPONENT_NAME => $nagiosim_component_name,
        COMPONENT_VERSION => '2.2.5',
        COMPONENT_DATE => '11/16/2016',
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => $desc,
        COMPONENT_TITLE => _("Nagios IM Integration"),
        COMPONENT_CONFIGFUNCTION => "nagiosim_component_config_func"
    );

    // Register this component with XI
    register_component($nagiosim_component_name, $args);

    nagiosim_db_init($im_current_version);

    // Register the addmenu function
    if ($versionok) {
        // Add a new function into the global event handler
        // this callback saves the incident to xi_incidents table
        register_callback(CALLBACK_EVENT_PROCESSED, 'nagiosim_component_callback_incident');

        // This callback cleans/sends incidents every minute
        register_callback(CALLBACK_SUBSYS_CLEANER, 'nagiosim_component_flush_incidents');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////
// COMPONENT REGISTRATION FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function nagiosim_component_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }

    // Requires greater than 2009R1.2
    if (get_product_release() < 215) {
        return false;
    }

    return true;
}

/**
 * Manages component configuration for Admin->Manage Components page for this component
 */
function nagiosim_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    // Initialize return code and output
    $result = 0;
    $output = "";

    switch ($mode) {

        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // Initial values
            $meta = is_null(get_option('im_component_options')) ? array() : unserialize(get_option("im_component_options"));
            $im_send = grab_array_var($meta, 'im_send', false);
            $url = grab_array_var($meta, 'url', '');
            $api_key = grab_array_var($meta, 'api_key', '');
            $threshold = grab_array_var($meta, 'threshold', 10);
            $max_age = grab_array_var($meta, 'max_age', 1);
            $users = grab_array_var($meta, 'users', '');
            $teams = grab_array_var($meta, 'teams', '');
            $type = grab_array_var($meta, 'type', '');
            $message = grab_array_var($meta, 'message', '');
            $title = grab_array_var($meta, 'title', '');
            $proxy = grab_array_var($meta, 'proxy', '');
            $hostgroups = grab_array_var($meta, 'hostgroups', array());
            $servicegroups = grab_array_var($meta, 'servicegroups', array());
            $resolve_incidents = grab_array_var($meta, 'resolve_incidents', false);
            $auto_resolve_status = grab_array_var($meta, 'auto_resolve_status', 'Resolved');
            $downtime_incidents = grab_array_var($meta, 'downtime_incidents', false);
            $force_strict = grab_array_var($meta, 'force_strict', 0);

            // IM callback registration flag
            $cb_registered = grab_array_var($meta, 'callback_registered', false);
            $cb_msg = "<span style='color:green;'>" . _("Callback registered with Nagios IM") . "</span>\n";
            if (!$cb_registered) {
                $cb_registered = nagiosim_component_create_remote_callback($url, $api_key, $proxy, $cb_msg);
            }

            // Select list options
            $hostgroup_opts = nagiosim_get_tbl_opts('hostgroup', $hostgroups);
            $servicegroup_opts = nagiosim_get_tbl_opts('servicegroup', $servicegroups);

            // Type, host, service, event_time, status, output
            if ($title == '') {
                $title = '%host% : %service% %status%';
            }

            if ($message == '') {
                $message = "****Nagios XI Incident*****\n
            
Type:%type%
Time:%event_time%
Host:%host%
Service: %service%
Status: %status%
Output: %output%

Nagios URL: <a href='%xiserverurl%' target='_blank'>Nagios XI</a>
Status URL: <a href='%xiserverurl%?xiwindow=%xiserverurl%includes/components/xicore/status.php?&show=%type%detail&host=%host%&service=%service%' target='_blank'>View Details</a>
";
            }

            // Build html for IM options
            $output = '
            <div class="single-half-col">
                <h5 class="ul">' . _('Connection Settings') . '</h5>
                <table class="table table-condensed table-no-border">
                    <tr>
                        <td style="width: 125px;"></td>
                        <td class="checkbox">
                            <label>
                                <input type="checkbox" name="im_send" id="im_send" ' . is_checked($im_send) . '>
                                ' . _('Enable the Nagios IM event handler.') . '
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Nagios IM URL') . ': <span class="req">*</span></label></td>
                        <td>
                            <input type="text" size="45" name="url" id="url" value="' . htmlentities($url) . '" class="textfield form-control" style="margin-right: 2rem;"> <strong>'._('Callback Status').':</strong> '.$cb_msg.' <i class="fa fa-question-circle pop" data-content="'._('Callback registration allows Nagios IM to submit comments and acknowledgments back to Nagios XI for related incidents.').'"></i>
                            <div class="subtext">'._('Must be accessible from this Nagios XI server. Normally'). ' http://&lt;serveraddress&gt;/nagiosim</div>
                            <input type="hidden" name="callback_registered" value="' . $cb_registered . '">
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('User API Key') . ': <span class="req">*</span></label></td>
                        <td>
                            <input type="text" size="45" name="api_key" id="api_key" value="' . $api_key . '" class="textfield form-control">
                            <div class="subtext">' . _("The API key unique to each user in Nagios IM. This can be found from the Admin->Edit User page in the Incident Manager interface. It is recommended to create a 'Nagios XI' user in the incident manager as a best practice for permissions.") . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Incident Type') . ': <span class="req">*</span></label></td>
                        <td>
                            <input type="text" name="type" id="type" value="' . $type . '" class="textfield form-control" placeholder="network-outage">
                            <div class="subtext">' . _('An Incident Type') . ' <strong>' . _('Alias') . '</strong> ' . _('defined in the Administration->Manage Incident Types page of Nagios IM.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Max Age') . ': <span class="req">*</span></label></td>
                        <td>
                            <input type="text" style="width: 40px; margin-right: 1rem;" name="max_age" id="max_age" value="' . $max_age . '" class="textfield form-control">'._('days').'
                            <div class="subtext">' . _('The amount of time in days Nagios XI will store an incident. If an incident is stored in Nagios XI a new incident will not be created in Nagios IM. This is used to prevent multiple incidents from being created by a single host or service experiencing frequent problems.') . '</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="single-half-col">
                <h5 class="ul">' . _('Integration Options') . '</h5>
                <table class="table table-condensed table-no-border">
                    <tr> 
                        <td class="vt" style="width: 175px;"><label>' . _('Incident Title') . ': <span class="req">*</span></label></td>
                        <td>
                            <input type="text" size="60" name="title" id="title" value="' . htmlentities($title) . '" class="textfield form-control">
                            <div class="subtext">' . _('The title format to be used for new incidents.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Incident Message') . ': <span class="req">*</span></label></td>
                        <td style="padding-bottom: 10px;"><textarea name="message" id="message" class="form-control" style="line-height: 1.6rem; height: 260px; width: 100%;">' . htmlentities($message) . '</textarea></td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Auto Resolve Incidents') . ':</label></td>
                        <td class="checkbox">
                            <label>
                                <input type="checkbox" name="resolve_incidents" style="margin-top: 9px;" id="resolve_incidents" ' . is_checked($resolve_incidents) . '> '._('with').' <select name="auto_resolve_status" class="form-control" style="margin-left: 4px;"><option value="Resolved" ' . is_selected($auto_resolve_status, "Resolved") . '>'._('Resolved').'</option><option value="Closed" ' . is_selected($auto_resolve_status, "Closed") . '>'._('Closed').'</option></select>
                            </label>
                            <div class="subtext">' . _('Automatically mark incidents as resolved in Nagios IM upon host or service recovery.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Send During Downtime') . ':</label></td>
                        <td class="checkbox">
                            <label>
                                <input type="checkbox" name="downtime_incidents" id="downtime_incidents" ' . is_checked($downtime_incidents) . '> '._('Send incidents during downtime') . '
                            </label>
                            <div class="subtext">' . _('Continue to send incidents for hosts/services that are in downtime') . '.</div>
                        </td>
                    </tr> 
                    <tr>
                        <td class="vt"><label>' . _('Forwarding Threshold') . ':</label></td>
                        <td>
                            <input type="text" style="width: 50px; margin-right: 5px;" name="threshold" id="threshold" value="' . $threshold . '" class="textfield form-control"> min
                            <div class="subtext">' . _('The amount of time in minutes Nagios XI will wait before forwarding a hard state change as an incident. If a threshold is used, the event will only be forwarded if the host or service remains in a problem state after the threshold has been exceeded. Enter 0 if to bypass the use of a threshold.') . '</div>
                        </td>
                    </tr>
                    <tr> 
                        <td class="vt"><label>' . _('IM Users') . ':</label></td>
                        <td>
                            <textarea style="width: 75%; height: 42px;" class="form-control" name="users" id="users">' . $users . '</textarea>
                            <div class="subtext">' . _('A comma delineated list of Nagios IM usernames to automatically assign incidents to.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('IM Teams') . ':</label></td>
                        <td>
                            <textarea style="width: 75%; height: 42px;" class="form-control" name="teams" id="teams">' . $teams . '</textarea>
                            <div class="subtext">' . _('A comma delineated list of Nagios IM teams to automatically assign incidents to.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Use Proxy') . ':</label></td>
                        <td class="checkbox">
                            <label>
                                <input type="checkbox" name="proxy" id="proxy" ' . is_checked($proxy) . '> <strong>' . _('Requires the proxy component.') . ' **' . _('experimental') . '**</strong>
                            </label>
                            <div class="subtext">' . _('Utilize a proxy if the proxy component is installed and enabled.') . '</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="single-half-col">
                <h5 class="ul">' . _('Advanced Settings (Filtering)') . '</h5>
                <table class="table table-condensed table-no-border">
                    <tr>
                        <td colspan="2"><strong>' . _('Filtering') . ':</strong> '
                    . _('If hostgroups') . ' <strong>' . _('OR') . '</strong> ' . _('servicegroups are selected, Nagios XI will only forward events for selected groups.') . '</td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Hostgroups') . ': </label></td>
                        <td>
                            <select name="hostgroups[]" id="hostgroups" multiple="multiple" class="form-control" style="min-width: 300px; height: 100px;">
                            ' . $hostgroup_opts . '
                            </select>
                            <div class="subtext">' . _('This is an <strong>optional</strong> filter. By default this component will forward all hard state changes. Select hostgroups to forward results only for the selected groups.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _('Servicegroups') . ': </label></td>
                        <td>
                            <select name="servicegroups[]" id="servicegroups" multiple="multiple" class="form-control" style="min-width: 300px; height: 100px;">
                            ' . $servicegroup_opts . '
                            </select>
                            <div class="subtext">' . _('This is an <strong>optional</strong> filter. By default this component will forward all hard state changes. Select servicegroups to forward results only for the selected groups.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt"><label>' . _("Strict Mode") . ':</label></td>
                        <td class="checkbox">
                            <label>
                                <input type="checkbox" value="1" name="force_strict" ' . is_checked($force_strict, 1) . '>
                                '._("Force only hosts in hostgroups and services in servicegroups directly defined above to be used.").'
                            </label>
                            <div class="subtext">'._("Does not include services of a host that is in a hostgroup if the service is not in the service group.").'</div>
                        </td>
                    </tr>
                </table>
            </div>
            ';
            break;

        // Save IM intrgration settings
        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            // Get variables
            $im_send = grab_array_var($inargs, 'im_send', false);
            $url = grab_array_var($inargs, 'url', '');
            $api_key = grab_array_var($inargs, 'api_key', '');
            $threshold = grab_array_var($inargs, 'threshold', 0);
            $max_age = grab_array_var($inargs, 'max_age', 1);
            $users = grab_array_var($inargs, 'users', '');
            $teams = grab_array_var($inargs, 'teams', '');
            $type = grab_array_var($inargs, 'type', '');
            $message = grab_array_var($inargs, 'message', '');
            $title = grab_array_var($inargs, 'title', '');
            $proxy = grab_array_var($inargs, 'proxy', '');
            $resolve_incidents = grab_array_var($inargs, 'resolve_incidents', false);
            $hostgroups = grab_array_var($inargs, 'hostgroups', array());
            $servicegroups = grab_array_var($inargs, 'servicegroups', array());
            $cb_registered = grab_array_var($inargs, 'callback_registered', false);
            $auto_resolve_status = grab_array_var($inargs, 'auto_resolve_status', 'Resolved');
            $downtime_incidents = grab_array_var($inargs, 'downtime_incidents', false);
            $force_strict = grab_array_var($inargs, 'force_strict', 0);

            // Get current meta info
            $meta = is_null(get_option('im_component_options')) ? array() : unserialize(get_option("im_component_options"));
            $metaurl = grab_array_var($meta, 'url', '');
            $metaapi_key = grab_array_var($meta, 'api_key', '');

            // Validate variables
            $errmsg = array();

            if ($url == '') {
                $errmsg[] = _("Please enter the full URL of the Nagios IM server");
            }

            if ($api_key == '') {
                $errmsg[] = _("Please enter an api_key for the Nagios IM server");
            }

            // Make blank 0
            if ($threshold == '') {
                $threshold = 0;
            }

            // Verify slug
            if (empty($errmsg)) {
                $args = array(
                    'api_key' => $api_key,
                    'title' => 'Test XI Incident',
                    'summary' => 'Testing slug given in Nagios IM Integation component settings.',
                    'type' => $type,
                    'request_method' => 'GET'
                );
                $querystring = http_build_query($args);
                $full_url = $url . '/index.php/api/incidents/add?' . $querystring;
                $opts = array('method' => 'post', 'return_info' => true);
                $http_array = load_url($full_url, $opts, $proxy);

                $xml = simplexml_load_string($http_array['body']);
                
                if ($http_array['info']['http_code'] == "200") {

                    // Delete incident that was created
                    $incident_id = $xml->incident['id'];
                    $args = array('api_key' => $api_key);
                    $querystring = http_build_query($args);
                    $full_url = $url.'/index.php/api/incidents/'.$incident_id.'/delete?'.$querystring;
                    $http_array = load_url($full_url, $opts, $proxy);

                } else {
                    if ($xml) {
                        $errmsg = $xml->info;
                    }
                }
            }

            // Handle errors
            if (!empty($errmsg)) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            // If URL or API changes create remote callback
            if ($metaurl != $url || $metaapi_key != $api_key) {
                $cb_msg = "<span style='color:green;'>" . _("Callback registered with Nagios IM") . "</span>\n";
                $cb_registered = nagiosim_component_create_remote_callback($url, $api_key, $proxy, $cb_msg);
            }

            if ($downtime_incidents == "on") {
                $downtime_incidents = true;
            }

            // Save settings
            $settings = array(
                'im_send' => $im_send,
                'url' => $url,
                'max_age' => $max_age,
                'api_key' => $api_key,
                'threshold' => $threshold,
                'users' => $users,
                'teams' => $teams,
                'type' => $type,
                'message' => $message,
                'title' => $title,
                'hostgroups' => $hostgroups,
                'servicegroups' => $servicegroups,
                'proxy' => $proxy,
                'callback_registered' => $cb_registered,
                'resolve_incidents' => $resolve_incidents,
                'auto_resolve_status' => $auto_resolve_status,
                'downtime_incidents' => $downtime_incidents,
                'force_strict' => $force_strict
            );

            set_option("im_component_options", serialize($settings));
            break;

        default:
            break;

    }

    return $output;
}

/**
 * Initialize xi_incidents database table if it's not there
 *
 * @param int $im_current_version : version number of this component
 */
function nagiosim_db_init($im_current_version)
{
    global $cfg;
    
    if ($cfg['db_info']['nagiosxi']['dbtype'] == 'pgsql') {
        $sql = "SELECT COUNT(relname) FROM pg_class WHERE relname = 'xi_incidents'";
        $rs = exec_sql_query(DB_NAGIOSXI, $sql, true);

        foreach ($rs as $row) {
            if ($row['count'] == 0) {

                // Create sequence
                $sql = "
                    CREATE SEQUENCE xi_incidents_id_seq
                        INCREMENT BY 1
                        NO MAXVALUE
                        NO MINVALUE
                        CACHE 1";
                exec_sql_query(DB_NAGIOSXI, $sql, true);

                // Create table
                $sql = "
                    CREATE TABLE IF NOT EXISTS xi_incidents (
                        id integer DEFAULT nextval('xi_incidents_id_seq'::regclass) NOT NULL,
                        incident_id integer DEFAULT 0,
                        submitted integer DEFAULT 0,
                        type varchar(16),
                        host varchar(96),
                        service varchar(96),
                        event_time timestamp without time zone NOT NULL,
                        status varchar(16), 
                        output text
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

                exec_sql_query(DB_NAGIOSXI, $sql, true);

                // Make sure we have access
                $sql = "ALTER TABLE public.xi_incidents OWNER TO nagiosxi";
            }
        }

    } else {

        // Do mysql version of the above
        $sql = "DESCRIBE xi_incidents;";
        $rs = exec_sql_query(DB_NAGIOSXI, $sql, false);
        if ($rs === false) {

            $sql = "CREATE TABLE IF NOT EXISTS xi_incidents (
                        id integer primary key auto_increment,
                        incident_id integer DEFAULT 0,
                        submitted integer DEFAULT 0,
                        type varchar(16),
                        host varchar(96),
                        service varchar(96),
                        event_time timestamp NOT NULL,
                        status varchar(16), 
                        output text);";
            exec_sql_query(DB_NAGIOSXI, $sql, true);
        }
    }

    set_option('im_component_version', $im_current_version);
    return;
}

/**
 * Attempts to create the appropriate callback function in Nagios Incident Manager via it's api
 *
 * @param string $url     : Nagios IM URL fetched from component settings array
 * @param string $api_key : Nagios IM user api_key from settings array
 * @param bool   $proxy   : enable proxy on the load_url function
 * @param string $msg     : Feedback message for UI
 */
function nagiosim_component_create_remote_callback($url, $api_key, $proxy, &$msg)
{
    $msg = '';

    // Basic requirements
    if ($url == '' || $api_key == '') {
        $msg = "<span style='color:red;'>" . _("Unable to register remote callback with Nagios IM. Missing URL and/or API Key.") . "</span>\n";
        return false;
    }

    $args = array(
        'api_key' => $api_key,
        'name' => 'XI Component Callback',
        'enabled' => true,
        'request_method' => 'GET',
        'callback_url' => get_base_url() . 'includes/components/nagiosim/nagiosim.php?mode=update&token=' . trim($api_key)
    );

    $querystring = http_build_query($args);
    $full_url = $url . '/index.php/api/callbacks/add/?' . $querystring;
    $opts = array('method' => 'post', 'return_info' => true);

    $http_array = load_url($full_url, $opts, $proxy);

    $xml = simplexml_load_string($http_array['body']);
    if ($xml) {
        $node = $xml->status;
        if ($node['code'] == 200) {
            $msg = "<span style='color:green;'>" . _("Callback registered with Nagios IM.") . " </span>\n";
            return true;
        } else {
            $msg = "<span style='color:red;'>{$xml->status} {$xml->info}.</span>\n";
            return false;
        }
    } else {
        $msg = "<span style='color:red;'>" . _("Unable to register remote callback with Nagios IM. API Registration failed.") . "</span>\n";
        return false;
    }
}

////////////////////////////////////////////////////////////    
//  CALLBACK FUNCTIONS
////////////////////////////////////////////////////////////

/**
 * Callback wrapper to handle different event types
 *
 * @param string $cbtype : Callback type, defined constant in XI
 * @param mixed  $args   : Event data array
 */
function nagiosim_component_callback_incident($cbtype, $args)
{
    if ($args["event_type"] == EVENTTYPE_STATECHANGE) {
        nagiosim_component_handle_statechange_event($args);
    }
}

/**
 * Saves hard state changes to the database to process as incidents
 *
 * @param mixed $args : array of event data from XI event handler
 */
function nagiosim_component_handle_statechange_event($args)
{
    // Only run if enabled
    if (!nagiosim_component_enabled()) {
        return;
    }

    $meta = grab_array_var($args, "event_meta", array());
    $handler_type = grab_array_var($meta, "handler-type", "");
    $statetype = grab_array_var($meta, $handler_type . 'statetype', 'SOFT');
    $state = grab_array_var($meta, $handler_type . 'state');

    // Cancel out if it's a soft state
    if ($statetype == 'SOFT') { return; }

    im_log("-------- NAGIOS IM CALLBACK FUNCTION --------", 0, IM_LOG_DEST_SL);

    // Add ability to close ticket and remove incident from XI's DB.
    if (($state == 'OK' || $state == 'UP') && nagiosim_component_autoresolve_enabled()) {
        $c = 0;
        $host = grab_array_var($meta, 'host', '_HOST_');
        $service = grab_array_var($meta, 'service', '');
        $output = grab_array_var($meta, $handler_type . 'output', '');

        $incidents = nagiosim_component_find_incidents($host, $service);
        foreach ($incidents as $incident) {
            im_log(" - Found ".$host." - ".$service." in OK state", 0, IM_LOG_DEST_SL);
            $bool = nagiosim_component_resolve_incident($incident['incident_id'], $output, IM_LOG_DEST_SL);
            $c += intval($bool);
        }

        im_log("{$c} incidents resolved in Nagios IM", 0, IM_LOG_DEST_SL);
    
    } else if ($state != 'OK' && $state != 'UP') {

        im_log(" - New incident in HARD warning or critical state", 0, IM_LOG_DEST_SL);
        im_log("   Saving new incident to database for sending ...", 0, IM_LOG_DEST_SL);
    
        // Grab remaining info
        $host = grab_array_var($meta, 'host', '_HOST_');
        $service = grab_array_var($meta, 'service', '');
        $state = grab_array_var($meta, $handler_type . 'state');
        $output = grab_array_var($meta, $handler_type . 'output', '');

        im_log("   Host: $host, Service: $service, State: $state", 0, IM_LOG_DEST_SL);

        // Move to incidents queue
        $sql = "INSERT INTO xi_incidents (type,host,service,event_time,status,output)
                VALUES ( '{$handler_type}','{$host}','{$service}','{$args['event_time']}','{$state}','{$output}')";
        exec_sql_query(DB_NAGIOSXI, $sql, true);
    }

    im_log("---------------------------------------------", 0, IM_LOG_DEST_SL);
}

/**
 * Forwards appropriate incidents to Nagios IM and deletes any stale or forwarded incidents
 *
 * @param string $cbtype : callback type
 * @param mixed  $args   : misc args array, should be empty
 */
function nagiosim_component_flush_incidents($cbtype, $args)
{
    global $cfg;

    // Only run if enabled
    if (!nagiosim_component_enabled()) {
        return;
    }

    // Load settings
    $settings = unserialize(get_option("im_component_options"));
    $threshold = grab_array_var($settings, 'threshold', 0);
    $hostgroups = grab_array_var($settings, 'hostgroups', array());
    $servicegroups = grab_array_var($settings, 'servicegroups', array());
    $force_strict = grab_array_var($settings, 'force_strict', 0);
    $downtime_incidents = grab_array_var($settings, 'downtime_incidents', false);

    // Get recent incidents that have not been sent
    if ($cfg['db_info']['nagiosxi']['dbtype'] == 'pgsql') {
        $sql = "SELECT * FROM xi_incidents WHERE
                event_time < ( now() - INTERVAL '{$threshold} MINUTES' )
                AND submitted=0 ORDER BY event_time ASC";
    } else {
        $sql = "SELECT * FROM xi_incidents WHERE
                event_time < DATE_SUB(now(), INTERVAL {$threshold} MINUTE)
                AND submitted=0 ORDER BY event_time ASC";
    }
    $rs = exec_sql_query(DB_NAGIOSXI, $sql, true);

    if ($rs->recordCount() > 0) {
        im_log("######### NAGIOS IM FLUSH INCIDENTS #########");
        im_log("Number of incidents to flush: " . $rs->recordCount());
    }
    
    // Process incidents
    $c = 0;
    foreach ($rs as $r) {

        $send = true;
        $host = grab_array_var($r, 'host');
        $service = grab_array_var($r, 'service');
        $incident_id = grab_array_var($r, 'incident_id');
        $id = grab_array_var($r, 'id');
        $hostgroup_filter = false;

        // Check object state one more time...
        $args = array('host_name' => $host, 'brevity' => 1);

        // Host type
        if (empty($service)) {
            $xml = get_xml_host_status($args);
            $node = 'hoststatus';
        }
        // Service type
        else {
            $args['service_description'] = $service;
            $xml = get_xml_service_status($args);
            $node = 'servicestatus';
        }

        $current_state = intval($xml->$node->current_state);

        // Check if it's in downtime and should not be sent
        $delete_downtime = 0;
        if ($xml->node->scheduled_downtime_depth > 0) {
            if (!$downtime_incidents) {
                $delete_downtime = 1;
            }
        }

        // Cancel send if object has recovered or it's in downtime and should be deleted
        if ($current_state == 0 || $delete_downtime == 1) {
            $send = false;
            im_log("Recovery or Downtime: {$host} {$service}, removing incident");
            nagiosim_component_remove_incident_by_id($id);
            continue;
        }

        // Filter by hostgroups, assume we're not in the filter
        if (!empty($hostgroups)) {
            $send = false;
            foreach ($hostgroups as $groupname) {
                if (is_host_member_of_hostgroup($host, $groupname)) {
                    if (!empty($service) && $force_strict) {
                        $send = false;
                    } else {
                        $hostgroup_filter = true;
                        $send = true;
                    }
                    break;
                }
            }
        }

        // Filter by servicegroups
        if (!empty($servicegroups) && $hostgroup_filter == false) {
            if (!empty($service)) {
                foreach ($servicegroups as $groupname) {
                    if (is_service_member_of_servicegroup($host, $service, $groupname)) {
                        $send = true;
                        break;
                    }
                }
            } else if (!$force_strict) {
                foreach ($servicegroups as $groupname) {
                    if (is_host_member_of_servicegroup($host, $groupname)) {
                        $send = true;
                        break;
                    }
                }
            }
        }

        // Check if incident has already been created and exists (DOES NOT CHECK WITH IM)
        if ($send) {
            $sql = "SELECT COUNT(*) as count FROM xi_incidents WHERE submitted=1 AND host='{$host}' AND service='{$service}'";
            $rs = exec_sql_query(DB_NAGIOSXI, $sql, true);
            foreach ($rs as $row) {
                $count = $row['count'];
                if ($count > 0) {
                    $send = false;
                    im_log("Incident with XI-ID $id and IM-ID $incident_id exists in database, skipping...");
                }
            }
        }

        // Time to send it off!
        if ($send) {
            $bool = nagiosim_component_send_incident($r, $settings);
            if ($bool) {
                $c++;
            } else {
                break;
            }
        } else {
            // Remove incident from queue
            im_log("Removing incident XI-ID $id from postgres database");
            nagiosim_component_remove_incident_by_id($id);
        }
    }

    if ($c > 0) {
        im_log("{$c} incidents sent to Nagios IM");
        im_log("#############################################");
    }

    // Do some stale checks for auto-resolving incidents that should have been resolved
    // but weren't because the event handler wasn't ran at the correct time to allow it to close the incident
    if (nagiosim_component_autoresolve_enabled()) {

        // Get all incidents that have been sent 
        $sql = "SELECT * FROM xi_incidents WHERE submitted = 1 AND incident_id != 0";
        $rs = exec_sql_query(DB_NAGIOSXI, $sql, true);

        if ($rs->recordCount() > 0) {
            im_log('--------- NAGIOS IM STALE INCIDENTS ----------');
            im_log("Number of stale incidents: " . $rs->recordCount());
        }

        $c = 0;
        foreach ($rs as $incident) {
            if (!empty($incident['service'])) {
                $request = array("host_name" => $incident['host'], "name" => $incident['service'], "brevity" => 2);
                $xml = simplexml_load_string(get_service_status_xml_output($request));
                if (intval($xml->recordcount) > 0) {
                    if (intval($xml->servicestatus->current_state) == 0) {
                        $output = strval($xml->servicestatus->status_text);

                        // Log this
                        im_log(' - Incident: '.$incident['host'].' - '.$incident['service'].' is currently OK and has an open incident ...');

                        $t = nagiosim_component_resolve_incident($incident['incident_id'], $output);
                        if ($t) $c++;
                    }
                }
            } else {
                $request = array("name" => $incident['host'], "brevity" => 2);
                $xml = simplexml_load_string(get_host_status_xml_output($request));
                if (intval($xml->recordcount) > 0) {
                    if (intval($xml->hoststatus->current_state) == 0) {
                        $output = strval($xml->hoststatus->status_text);
                        $t = nagiosim_component_resolve_incident($incident['incident_id'], $output);
                        if ($t) $c++;
                    }
                }
            }
        }

        if ($c > 0) {
            im_log($c.' incident(s) closed in Nagios IM');
        }
        im_log('----------------------------------------------');
    }
}

/**
 * Uses Curl to post single incident data to Nagios IM
 *
 * @param mixed $arr      : Incident data from xi_incidents table
 * @param mixed $settings : im_component_options from config array
 *                        return bool: success | failure to send
 */
function nagiosim_component_send_incident($arr, $settings)
{
    im_log(" - Creating a new incident in IM for: ".$arr['host']." - ".$arr['service']);

    $msg_array = array(
        'host' => grab_array_var($arr, 'host'),
        'service' => grab_array_var($arr, 'service', ''),
        'type' => grab_array_var($arr, 'type'),
        'event_time' => grab_array_var($arr, 'event_time'),
        'status' => grab_array_var($arr, 'status'),
        'output' => grab_array_var($arr, 'output'),
        'xiserverurl' => get_external_url(),
    );

    // Process title
    $title = grab_array_var($settings, 'title');
    foreach ($msg_array as $var => $val) {
        $tvar = "%" . $var . "%";
        $title = str_replace($tvar, $val, $title);
    }

    // Process summary messages
    $summary = grab_array_var($settings, 'message');
    foreach ($msg_array as $var => $val) {
        $tvar = "%" . $var . "%";
        $summary = str_replace($tvar, $val, $summary);
    }

    // Extract from settings the API stuff
    $url = grab_array_var($settings, 'url');
    $url .= '/index.php/api/incidents/add/';

    $proxy = false;
    $im_use_proxy = grab_array_var($settings, 'proxy', false);
    $xi_use_proxy = get_option('use_proxy');

    // Experimental support for proxies
    if ($im_use_proxy == true && $xi_use_proxy == true) {
        $proxy = true;
    }

    // Process settings array into vars for the query string
    $users = explode(',', grab_array_var($settings, 'users'));
    array_walk($users, 'trim');
    $teams = explode(',', grab_array_var($settings, 'teams'));
    array_walk($teams, 'trim');

    $type = grab_array_var($settings, 'type');
    $args = array(
        'api_key' => grab_array_var($settings, 'api_key'),
        'users' => $users,
        'teams' => $teams,
        'type' => $type,
        'summary' => $summary,
        'title' => $title
    );

    // Build URL for curl request
    $querystring = http_build_query($args);
    $full_url = $url . '?' . $querystring;
    $opts = array('method' => 'post', 'return_info' => true);
    $http_array = load_url($full_url, $opts, $proxy);

    // Process XML returned from the API
    $xml = simplexml_load_string($http_array['body']);
    if ($xml) {

        $node = $xml->incident;
        $id = intval($node['id']);
        $xi_id = grab_array_var($arr, 'id');
        $code = intval($node['code']);

        im_log("   Response XML:\n".print_r($http_array['body'], true));
        im_log("   Response code: ".$code);

        im_log("   Incident added: XI-ID: {$xi_id}. IM-ID: {$id}");
        
        $sql = "UPDATE xi_incidents SET incident_id='{$id}',submitted=1 WHERE id='{$xi_id}'";
        exec_sql_query(DB_NAGIOSXI, $sql, true);
        return true;

    } else {
        $code = grab_array_var($http_array['info'], 'http_code', 999);
        im_log("   Sending to Nagios IM error: {$code}");
        return false;
    }
}

/**
 * Resolves an incident in Nagios IM if the host/service returns to an OK state
 *
 * @param int    $id     : The nagiosim incident ID
 * @param string $output : The plugin output for the recovered object
 *
 * @return bool: True on success | False on failure
 */
function nagiosim_component_resolve_incident($id, $output, $logdest=IM_LOG_DEST)
{
    // Only run if enabled
    if (!nagiosim_component_enabled()) {
        return;
    }

    im_log("    - Trying to resolve incident ID: {$id}", 0, $logdest);

    // Load settings
    $settings = unserialize(get_option("im_component_options"));

    // Extract from settings the API stuff
    $url = grab_array_var($settings, 'url');

    // Handle proxy option
    $proxy = false;
    $im_use_proxy = grab_array_var($settings, 'proxy', false);
    $xi_use_proxy = get_option('use_proxy');

    // Experimental support for proxies
    if ($im_use_proxy == true && $xi_use_proxy == true) {
        $proxy = true;
    }

    // Add a new message to the Incident first
    $args = array(
        'api_key' => grab_array_var($settings, 'api_key'),
        'title' => _('Nagios RECOVERY'),
        'message' => _('Nagios XI has detected a recovery for this incident. The ticket will be resolved automatically. Plugin Output: ' . $output)
    );

    // Build URL and send update to text to the incident
    $message_url = $url . '/index.php/api/incidents/' . $id . '/messages/';
    $querystring = http_build_query($args);
    $full_url = $message_url . '?' . $querystring;
    $opts = array('method' => 'post', 'return_info' => true);
    $http_array = load_url($full_url, $opts, $proxy);

    // Resolve incident
    $args = array(
        'api_key' => grab_array_var($settings, 'api_key'),
        'status' => grab_array_var($settings, 'auto_resolve_status', 'Resolved')
    );

    // Build URL for curl request
    $edit_url = $url . '/index.php/api/incidents/' . $id . '/edit/';
    $querystring = http_build_query($args);
    $full_url = $edit_url . '?' . $querystring;

    im_log("      Full URL: " . htmlentities($full_url), 0, $logdest);

    $opts = array('method' => 'post', 'return_info' => true);
    $http_array = load_url($full_url, $opts, $proxy);
    
    // Dump the http array into the currently running cron script log
    array_dump($http_array);

    // Process XML returned from the API
    $xml = simplexml_load_string($http_array['body']);
    if ($xml) {

        array_dump($xml); // This is only shown in the currently running cron script log
        $node = $xml->status;
        $code = intval($node['code']);

        im_log("      Response XML:\n".print_r($http_array['body'], true), 0, $logdest);
        im_log("      Response code: ".$code, 0, $logdest);

        if ($code == 200) {
            nagiosim_component_remove_incident($id);
            im_log("      Removing incident from postgres database", 0, $logdest);
            return true;
        }

    } else {
        $code = grab_array_var($http_array['info'], 'http_code', 999);
        im_log("      Nagios IM send error: {$code}", 0, $logdest);
    }

    return false;
}

/**
 * Simple boolean checker for whether or not event handler is enabled
 */
function nagiosim_component_enabled()
{
    $settings = unserialize(get_option('im_component_options'));
    if ($settings == false) {
        return false;
    }

    $im_send = grab_array_var($settings, 'im_send', false);
    return $im_send;
}

/**
 * Simple boolean check to see if auto-resolve is enabled
 */
function nagiosim_component_autoresolve_enabled()
{
    $settings = unserialize(get_option('im_component_options'));
    if ($settings == false) {
        return false;
    }

    $resolve = grab_array_var($settings, 'resolve_incidents', false);
    return $resolve;
}

/**
 * Removes item from xi_incidents table
 *
 * @param int $id : incident row id
 */
function nagiosim_component_remove_incident($id) {
    $sql = "DELETE FROM xi_incidents WHERE incident_id = '{$id}'";
    exec_sql_query(DB_NAGIOSXI, $sql, true);
}

function nagiosim_component_remove_incident_by_id($id) {
    $sql = "DELETE FROM xi_incidents WHERE id = '".intval($id)."'";
    exec_sql_query(DB_NAGIOSXI, $sql, true);
}

/**
 * Fetches a Nagios IM Incident(s) from XI's DB if it exists
 *
 * @param string $host    : hostname
 * @param string $service : service description
 */
function nagiosim_component_find_incidents($host, $service)
{
    $sql = "SELECT * FROM xi_incidents WHERE submitted=1 AND host='" . escape_sql_param($host, DB_NAGIOSXI) . "' AND service='" . escape_sql_param($service, DB_NAGIOSXI) . "'";
    $rs = exec_sql_query(DB_NAGIOSXI, $sql, true);
    if ($rs->recordCount() == 0) {
        return false;
    }

    $incidents = array();
    foreach ($rs as $row) {
        $incidents[] = $row;
    }

    return $incidents;
}

/**
 * Returns a select list of nagios objects, with preselections
 *
 * @param string $table     : nagios object type
 * @param mixed  $preselect : array of preselected object ID's
 *
 * @return string $options: html option string
 */
function nagiosim_get_tbl_opts($table, $preselect = array())
{
    global $myDebug;

    // Exception for timeperiod selection
    if (!is_array($preselect)) {
        $preselect = array($preselect);
    }

    $query = "SELECT id,{$table}_name FROM tbl_{$table} ORDER BY {$table}_name ASC";
    $rs = exec_sql_query(DB_NAGIOSQL, $query, $myDebug);

    $options = '<option value="NULL"></option>';
    foreach ($rs as $r) {
        $name = $r[$table . '_name'];
        $options .= "<option value='{$name}' ";
        if (in_array($name, $preselect)) $options .= " selected='selected' ";
        $options .= ">" . $name . "</options>\n";
    }

    return $options;
}
