<?php
//
// New REST API utils and classes for Nagios XI 5
//

// Includes for CCM functionality
define('ENVIRONMENT', 'nagiosxi');
define('BASEDIR', dirname(__FILE__).'/../../includes/components/ccm/');
include_once('../../includes/components/ccm/includes/common_functions.inc.php');
include_once('../../includes/components/ccm/config.inc.php');
include_once('../../includes/components/ccm/classes/Db.php');
include_once('../../includes/components/ccm/classes/config_class.php');
include_once('../../includes/components/ccm/classes/data_class.php');
include_once('../../includes/components/ccm/classes/import_class.php');
include_once('../../includes/components/ccm/classes/mysql_class.php');

$ccmDB = new Db();

// Load CCM config settings
// TODO: This should be changed to "ccm_cfg" to not be confused with Nagios XI
$CFG['settings'] = array(); 
$settings = $ccmDB->query("SELECT * FROM tbl_settings;"); 
foreach ($settings as $s) { 
    $CFG[$s['category']][$s['name']] = $s['value']; 
}

// Add data to the session
// =======================
$_SESSION['SETS'] = $CFG;
$_SESSION['domain'] = 1; // Currently we only support single domain configs 
$_SESSION['pagelimit'] = $CFG['common']['pagelines'];

// Initialize base global objects
$myDataClass = new nagdata;
$myConfigClass = new nagconfig;
$myDBClass = new mysqldb; 
$myImportClass = new nagimport;

// Reference objects inside of global objects
$myDataClass->myDBClass =& $myDBClass;
$myDataClass->myConfigClass =& $myConfigClass;
$myConfigClass->myDBClass =& $myDBClass;
$myConfigClass->myDataClass =& $myDataClass;
$myImportClass->myDataClass =& $myDataClass;
$myImportClass->myDBClass =& $myDBClass;
$myImportClass->myConfigClass =& $myConfigClass;

//
// ACTUAL API SECTION
//

class API extends NagiosAPI
{
    protected $user;

    public function __construct($request, $origin)
    {
        parent::__construct($request);

        $apikey = new APIKey();
        
        if (!array_key_exists('apikey', $this->request)) {
            throw new Exception(_('No API Key provided'));
        } else if (!$apikey->verify_key($this->request['apikey'], $origin, $reason)) {
            throw new Exception($reason);
        }
    }

    protected function convert_to_output_type($data)
    {
        $type = grab_array_var($this->request, 'outputtype', 'json');
        if ($type == 'xml') {
            return $data;
        }
        return xml2json::transformXmlStringToJson($data);
    }

    protected function objects($verb, $args)
    {
        switch ($this->method)
        {
            case 'GET':
                switch ($verb)
                {
                    case 'hoststatus':
                        $data = get_host_status_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'servicestatus':
                        $data = get_service_status_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'logentries':
                        $data = get_logentries_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'statehistory':
                        $data = get_statehistory_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'comment':
                        $data = get_comments_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'downtime':
                        $data = get_scheduled_downtime_xml_output();
                        return $this->convert_to_output_type($data);
                        break;

                    case 'contact':
                        $data = get_contact_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'host':
                        $data = get_host_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'service':
                        $data = get_service_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'hostgroup':
                        $data = get_hostgroup_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'servicegroup':
                        $data = get_servicegroup_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'contactgroup':
                        $data = get_contactgroup_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'hostgroupmembers':
                        $data = get_hostgroup_member_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'servicegroupmembers':
                        $data = get_servicegroup_member_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'contactgroupmembers':
                        $data = get_contactgroup_member_objects_xml_output($this->request);
                        return $this->convert_to_output_type($data);
                        break;

                    case 'rrdexport':
                        $data = get_rrd_json_output($this->request);
                        return $data;
                        break;

                    case 'cpexport':
                        require_once('../../includes/components/capacityplanning/cp-common.inc.php');
                        $data = get_cp_array_data();
                        return $data;
                        break;

                    default:
                        return array('error' => _('Unknown API endpoint.'));
                        break;
                }
                break;

            case 'POST':
                return array('error' => _('Unknown API endpoint.'));
                break;

            case 'PUT':
                return array('error' => _('Unknown API endpoint.'));
                break;

            case 'DELETE':
                return array('error' => _('Unknown API endpoint.'));
                break;
        }
    }

    protected function config($verb, $args)
    {
        if (is_admin() == false) { return array('error' => _('Authenticiation failed.')); }

        switch ($this->method)
        {
            case 'GET':
                return array('info' => _('Not implemented yet. Use a POST request to add objects or a DELETE request to remove them.'));
                break;

            case 'POST':
                switch ($verb)
                {
                    case 'host':
                        return $this->create_cfg_host();
                        break;

                    case 'service':
                        return $this->create_cfg_service();
                        break;

                    case 'hostgroup':
                        return $this->create_cfg_hostgroup();
                        break;

                    case 'servicegroup':
                        return $this->create_cfg_servicegroup();
                        break;

                    //case 'command':
                    //    return $this->create_cfg_command();
                    //    break;

                    default:
                        return array('error' => _('Unknown API endpoint.'));
                        break;
                }
                break;

            case 'PUT':
                return array('info' => _('This section has not yet been implemented.'));
                break;

            case 'DELETE':
                switch ($verb)
                {
                    case 'host':
                        return $this->remove_cfg_host();
                        break;

                    case 'service':
                        return $this->remove_cfg_service();
                        break;
                }
                print_r($this->request);
                break;
        }

    }

    protected function system($verb, $args)
    {
        if (is_admin() == false) { return array('error' => _('Authenticiation failed.')); }

        switch ($this->method)
        {
            case 'GET':
                switch ($verb)
                {
                    case 'user':
                        return $this->get_users($args[0]);
                        break;

                    case 'status':
                        return $this->get_system_status($args[0]);
                        break;

                    case 'applyconfig':
                        return $this->apply_config();
                        break;

                    case 'importconfig':
                        return $this->import_config();
                        break;

                    default:
                        return array('error' => _('Unknown API endpoint.'));
                        break;
                }
                break;

            case 'POST':
                switch ($verb)
                {
                    case 'user':
                        return $this->add_user();
                        break;

                    case 'applyconfig':
                        return $this->apply_config();
                        break;

                    case 'importconfig':
                        return $this->import_config();
                        break;

                    default:
                        return array('error' => _('Unknown API endpoint.'));
                        break;
                }
                break;

            case 'PUT':
                return array('info' => _('This section has not yet been implemented.'));
                break;

            case 'DELETE':
                switch ($verb)
                {
                    case 'user':
                        return $this->delete_user($args[0]);
                        break;

                    default:
                        return array('error' => _('Unknown API endpoint.'));
                        break;
                }
                break;
        }
    }

    protected function apply_config()
    {
        submit_command(COMMAND_NAGIOSCORE_APPLYCONFIG);
        return array('success' => _('Apply config command has been sent to the backend.'));
    }

    protected function import_config()
    {
        submit_command(COMMAND_NAGIOSCORE_IMPORTONLY);
        return array('success' => _('Import configs command has been sent to the backend.'));
    }

    protected function get_users($user_id = 0)
    {
        global $sqlquery;
        global $db_tables;

        $arr = array();

        $fieldmap = array(
            "user_id" => $db_tables[DB_NAGIOSXI]["users"] . ".user_id",
            "username" => $db_tables[DB_NAGIOSXI]["users"] . ".username",
            "name" => $db_tables[DB_NAGIOSXI]["users"] . ".name",
            "email" => $db_tables[DB_NAGIOSXI]["users"] . ".email",
            "enabled" => $db_tables[DB_NAGIOSXI]["users"] . ".enabled",
        );
        $args = array(
            "sql" => $sqlquery['GetUsers'],
            "fieldmap" => $fieldmap
        );

        if (!empty($user_id)) {
            $args['useropts'] = array("user_id" => $user_id);
        }

        $sql = generate_sql_query(DB_NAGIOSXI, $args);

        if (!($rs = exec_sql_query(DB_NAGIOSXI, $sql))) {
            handle_backend_db_error(DB_NAGIOSXI);
        } else {
            $arr['records'] = $rs->RecordCount();
            if (!empty($arr['records'])) {
                $arr['users'] = array();
                while (!$rs->EOF) {
                    $arr['users'][] = array('user_id' => $rs->fields['user_id'],
                                            'username' => $rs->fields['username'],
                                            'name' => $rs->fields['name'],
                                            'email' => $rs->fields['email'],
                                            'enabled' => $rs->fields['enabled']);
                    $rs->MoveNext();
                }
            }
        }

        return $arr;
    }

    protected function add_user()
    {
        $missing = array();
        $args = $this->request;

        if (in_demo_mode()) {
            return array("error" => _("Can not use this action in demo mode."));
        }

        // Get values
        $username = strtolower(grab_array_var($args, "username", ""));
        $password = grab_array_var($args, "password", "");
        $email = grab_array_var($args, "email", "");
        $name = grab_array_var($args, "name", "");
        $level = grab_array_var($args, "auth_level", "user");
        $forcechangepass = grab_array_var($args, "force_pw_change", 1);
        $email_info = grab_array_var($args, "email_info", 1);
        $add_contact = grab_array_var($args, "monitoring_contact", 1);
        $enable_notifications = grab_array_var($args, "enable_notifications", 1);
        $language = grab_array_var($args, "language", "");
        $date_format = grab_array_var($args, "defaultDateFormat", DF_ISO8601);
        $number_format = grab_array_var($args, "defaultNumberFormat", NF_2);
        $authorized_for_all_objects = grab_array_var($args, "can_see_all_hs", 0);
        $authorized_for_all_object_commands = grab_array_var($args, "can_control_all_hs", 0);
        $authorized_to_configure_objects = grab_array_var($args, "can_reconfigure_hs", 0);
        $authorized_for_monitoring_system = grab_array_var($args, "can_control_engine", 0);
        $advanced_user = grab_array_var($args, "can_use_advanced", 0);
        $readonly_user = grab_array_var($args, "read_only", 0);
        $api_enabled = grab_array_var($args, "api_enabled", 0);

        // Verify that everything we need is here
        $required = array('username', 'password', 'email', 'name');
        foreach ($required as $r) {
            if (!array_key_exists($r, $args)) {
                $missing[] = $r;
            }
        }

        // Verify auth level
        $auth = '';
        if ($level == "user") {
            $level = 1;
        } else if ($level == "admin") {
            $level = 255;
        } else {
            $auth = array('auth_level' => _('Must enter either user or admin.'));
        }

        // We are missing required fields
        if (count($missing) > 0 || !empty($auth)) {
            $errormsg = array('error' => _('Could not create user. Missing required fields.'));
            if (!empty($auth)) { $errormsg['messages'] = $auth; }
            if (count($missing) > 0) { $errormsg['missing'] = $missing; }
            return $errormsg;
        }

        // If everything looks okay then proceed to create the user
        $user_id = add_user_account($username, $password, $name, $email, $level, $forcechangepass, $add_contact, $api_enabled, $errmsg);

        set_user_meta($user_id, 'name', $name);
        set_user_meta($user_id, 'language', $language);
        set_user_meta($user_id, 'theme', $theme);
        set_user_meta($user_id, "date_format", $date_format);
        set_user_meta($user_id, "number_format", $number_format);
        set_user_meta($user_id, "authorized_for_all_objects", $authorized_for_all_objects);
        set_user_meta($user_id, "authorized_for_all_object_commands", $authorized_for_all_object_commands);
        set_user_meta($user_id, "authorized_to_configure_objects", $authorized_to_configure_objects);
        set_user_meta($user_id, "authorized_for_monitoring_system", $authorized_for_monitoring_system);
        set_user_meta($user_id, "advanced_user", $advanced_user);
        set_user_meta($user_id, "readonly_user", $readonly_user);
        set_user_meta($user_id, "auth_type", 'local');

        if ($add_contact) {
            set_user_meta($user_id, "enable_notifications", $enable_notifications);
        }

        // Update nagios cgi config file
        update_nagioscore_cgi_config();

        if ($email_info) {
            $adminname = get_option("admin_name");
            $adminemail = get_option("admin_email");
            $url = get_option("url");

            // Use this for debug output in PHPmailer log
            $debugmsg = "";

            // Set where email is coming from for PHPmailer log
            $send_mail_referer = "api/includes/utils-api.inc.php > API - Add User";

            $msg = _("An account has been created for you to access Nagios XI.  You can login using the following information:\n\nUsername: %s\nPassword: %s\nURL: %s\n\n");
            $message = sprintf($msg, $username, $password, $url);
            $opts = array(
                "from" => $adminname . " <" . $adminemail . ">\r\n",
                "to" => $email,
                "subject" => _("Nagios XI Account Created"),
                "message" => $message,
            );
            send_email($opts, $debugmsg, $send_mail_referer);
        }

        // Log it
        if ($level == L_GLOBALADMIN) {
            send_to_audit_log("User account '" . $username . "' was created with GLOBAL ADMIN privileges", AUDITLOGTYPE_SECURITY);
        }

        $ret = array('success' => _('User account') . ' ' . $username . ' ' . _('was added successfully!'));
        $ret['userid'] = $user_id;
        return $ret;
    }

    protected function delete_user($user_id = 0)
    {
        if (in_demo_mode()) {
            return array("error" => _("Can not use this action in demo mode."));
        }

        if (!is_valid_user_id($user_id)) {
            return array("error" => _('User with') . ' ID ' . $user_id . ' ' . _('does not exist.'));
        }

        if ($user_id == $_SESSION["user_id"]) {
            return array("error" => _('Cannot delete the user you are currently using.'));
        }

        // Remove user from http
        update_nagioscore_cgi_config();
        $args = array(
            "username" => get_user_attr($user_id, 'username'),
        );
        submit_command(COMMAND_NAGIOSXI_DEL_HTACCESS, serialize($args));
        delete_user_id($user_id);

        return _('Success. User removed.');
    }

    protected function get_system_status()
    {
        return get_program_status_xml_output(array(), true);
    }

    // =========================
    // Configuration
    // =========================

    protected function create_cfg_host()
    {
        global $firsthost;
        global $myImportClass;
        $args = $this->request;
        $applyconfig = false;

        // Verify required arguments were sent
        $required = array('host_name', 'address', 'max_check_attempts', 'check_period', 'notification_interval', 'notification_period');
        $missing = array();
        foreach ($required as $r) {
            if (!array_key_exists($r, $args)) {
                $missing[] = $r;
            }
        }

        if (!array_key_exists('contacts', $args) && !array_key_exists('contact_groups', $args)) {
            $missing[] = 'contacts OR contact_groups';
        }
        $required[] = 'contacts';
        $required[] = 'contact_groups';

        if (count($missing) > 0 && empty($args['force'])) {
            return array('error' => _('Missing required variables'), 'missing' => $missing);
        }

        // Check if we should applyconfig nagios after
        if (array_key_exists('applyconfig', $args)) {
            if ($args['applyconfig'] == 1) {
                $applyconfig = true;
            }
        }

        $obj = array("type" => OBJECTTYPE_HOST);
        foreach ($required as $r) {
            if (array_key_exists($r, $args)) {
                $obj[$r] = $args[$r];
            }
        }

        // Add any optional components that we need to
        $others = array('alias', 'display_name', 'parents', 'hourly_value', 'hostgroups', 'check_command', 'use', 'initial_state', 'check_interval', 'retry_interval', 'active_checks_enabled', 'passive_checks_enabled', 'obsess_over_host', 'check_freshness', 'freshness_threshold', 'event_handler', 'event_handler_enabled', 'low_flap_threshold', 'high_flap_threshold', 'flap_detection_enabled', 'flap_detection_options', 'process_perf_data', 'retain_status_information', 'retain_nonstatus_information', 'first_notification_delay', 'notification_options', 'notifications_enabled', 'stalking_options', 'notes', 'notes_url', 'action_url', 'icon_image', 'icon_image_alt', 'vrml_image', 'statusmap_image', '2d_coords', '3d_coords');

        foreach ($others as $o) {
            if (array_key_exists($o, $args)) {
                if ($o == 'check_command') { $args[$o] = stripslashes($args[$o]); }
                $obj[$o] = $args[$o];
            }
        }

        // Add any free variables that we find (they must start with an underscore)
        foreach ($args as $key => $value) {
            if (substr($key, 0, 1) === '_') {
                $obj[$key] = $value;
            }
        }

        // Commit the data to a file
        $objs = array($obj);
        $str = get_cfg_objects_str($objs, $firsthost);
        $myImportClass->fileImport($str, 1, true);

        if ($applyconfig) {
            submit_command(COMMAND_NAGIOSCORE_APPLYCONFIG);
            $msg = ' ' . _('Config applied, Nagios Core was restarted.');
        } else {
            $msg = ' ' . _('Config imported but not yet applied.');
        }

        return array('success' => _('Successfully added') . ' ' . $args['host_name'] . ' ' . _('to the system.') . $msg);
    }

    protected function create_cfg_service()
    {
        global $firsthost;
        global $myImportClass;
        $args = $this->request;
        $applyconfig = false;

        // Verify required arguments were sent
        $required = array('host_name', 'service_description', 'max_check_attempts', 'check_interval', 'retry_interval', 'check_period', 'notification_interval', 'notification_period');
        $missing = array();
        foreach ($required as $r) {
            if (!array_key_exists($r, $args)) {
                $missing[] = $r;
            }
        }

        if (!array_key_exists('contacts', $args) && !array_key_exists('contact_groups', $args)) {
            $missing[] = 'contacts OR contact_groups';
        }
        $required[] = 'contacts';
        $required[] = 'contact_groups';

        if (count($missing) > 0 && empty($args['force'])) {
            return array('error' => _('Missing required variables'), 'missing' => $missing);
        }

        // Check if we should applyconfig nagios after
        if (array_key_exists('applyconfig', $args)) {
            if ($args['applyconfig'] == 1) {
                $applyconfig = true;
            }
        }

        $obj = array("type" => OBJECTTYPE_SERVICE);
        foreach ($required as $r) {
            if (array_key_exists($r, $args)) {
                $obj[$r] = $args[$r];
            }
        }

        // Add any optional components that we need to
        $others = array('hostgroup_name', 'display_name', 'parents', 'hourly_value', 'servicegroups', 'is_volatile', 'use', 'initial_state', 'active_checks_enabled', 'passive_checks_enabled', 'obsess_over_service', 'check_freshness', 'check_command', 'freshness_threshold', 'event_handler', 'event_handler_enabled', 'low_flap_threshold', 'high_flap_threshold', 'flap_detection_enabled', 'flap_detection_options', 'process_perf_data', 'retain_status_information', 'retain_nonstatus_information', 'first_notification_delay', 'notification_options', 'notifications_enabled', 'stalking_options', 'notes', 'notes_url', 'action_url', 'icon_image', 'icon_image_alt');

        foreach ($others as $o) {
            if (array_key_exists($o, $args)) {
                if ($o == 'check_command') { $args[$o] = stripslashes($args[$o]); }
                $obj[$o] = $args[$o];
            }
        }

        // Add any free variables that we find (they must start with an underscore)
        foreach ($args as $key => $value) {
            if (substr($key, 0, 1) === '_') {
                $obj[$key] = $value;
            }
        }

        // Commit the data to a file
        $objs = array($obj);
        $str = get_cfg_objects_str($objs, $firsthost);
        $myImportClass->fileImport($str, 1, true);

        if ($applyconfig) {
            submit_command(COMMAND_NAGIOSCORE_APPLYCONFIG);
            $msg = ' ' . _('Config applied, Nagios Core was restarted.');
        } else {
            $msg = ' ' . _('Config imported but not yet applied.');
        }

        return array('success' => _('Successfully added') . ' ' . $args['host_name'] . ' :: ' . $args['service_description'] . ' ' . _('to the system.') . $msg);
    }

    protected function create_cfg_hostgroup()
    {
        global $firsthost;
        global $myImportClass;
        $args = $this->request;
        $applyconfig = false;

        $required = array('hostgroup_name', 'alias');
        $missing = array();
        foreach ($required as $r) {
            if (!array_key_exists($r, $args)) {
                $missing[] = $r;
            }
        }

        if (count($missing) > 0) {
            return array('error' => _('Missing required variables'), 'missing' => $missing);
        }

        // Check if we should applyconfig nagios after
        if (array_key_exists('applyconfig', $args)) {
            if ($args['applyconfig'] == 1) {
                $applyconfig = true;
            }
        }

        $obj = array("type" => OBJECTTYPE_HOSTGROUP);
        foreach ($required as $r) {
            $obj[$r] = $args[$r];
        }

        $others = array('members', 'hostgroup_members', 'notes', 'notes_url', 'action_url');

        foreach ($others as $o) {
            if (array_key_exists($o, $args)) {
                $obj[$o] = $args[$o];
            }
        }

        // Commit the data to a file
        $objs = array($obj);
        $str = get_cfg_objects_str($objs, $firsthost);
        $myImportClass->fileImport($str, 1, true);

        if ($applyconfig) {
            submit_command(COMMAND_NAGIOSCORE_APPLYCONFIG);
            $msg = ' ' . _('Config applied, Nagios Core was restarted.');
        } else {
            $msg = ' ' . _('Config imported but not yet applied.');
        }

        return array('success' => _('Successfully added') . ' ' . $args['hostgroup_name'] . ' ' . _('to the system.') . $msg);
    }

    protected function create_cfg_servicegroup()
    {
        global $firsthost;
        global $myImportClass;
        $args = $this->request;
        $applyconfig = false;

        $required = array('servicegroup_name', 'alias');
        $missing = array();
        foreach ($required as $r) {
            if (!array_key_exists($r, $args)) {
                $missing[] = $r;
            }
        }

        if (count($missing) > 0) {
            return array('error' => _('Missing required variables'), 'missing' => $missing);
        }

        // Check if we should applyconfig nagios after
        if (array_key_exists('applyconfig', $args)) {
            if ($args['applyconfig'] == 1) {
                $applyconfig = true;
            }
        }

        $obj = array("type" => OBJECTTYPE_SERVICEGROUP);
        foreach ($required as $r) {
            $obj[$r] = $args[$r];
        }

        $others = array('members', 'servicegroup_members', 'notes', 'notes_url', 'action_url');

        foreach ($others as $o) {
            if (array_key_exists($o, $args)) {
                $obj[$o] = $args[$o];
            }
        }

        // Commit the data to a file
        $objs = array($obj);
        $str = get_cfg_objects_str($objs, $firsthost);
        $myImportClass->fileImport($str, 1, true);

        if ($applyconfig) {
            submit_command(COMMAND_NAGIOSCORE_APPLYCONFIG);
            $msg = ' ' . _('Config applied, Nagios Core was restarted.');
        } else {
            $msg = ' ' . _('Config imported but not yet applied.');
        }

        return array('success' => _('Successfully added') . ' ' . $args['servicegroup_name'] . ' ' . _('to the system.') . $msg);
    }

    protected function create_cfg_command()
    {
        $args = $this->request;
        $applyconfig = true;
        

    }

    protected function remove_cfg_host()
    {
        $applyconfig = false;
        $missing = array();
        
        if (!array_key_exists('host_name', $this->request)) { $missing[] = 'host_name'; }

        if (count($missing) > 0) {
            return array('error' => _('Missing required variables'), 'missing' => $missing);
        }

        $host = grab_array_var($this->request, 'host_name', '');
        $hid = nagiosql_get_host_id($host);

        if ($hid <= 0) {
            return array('error' => _('Could not find a unique id for this host.'));
        }

        if (nagiosccm_can_host_be_deleted($host) == false) {
            return array('error' => _('Host cannot be deleted using this method. Must be deleted through the CCM.'));
        }

        // Check if we should applyconfig nagios after
        if (array_key_exists('applyconfig', $this->request)) {
            if ($this->request['applyconfig'] == 1) {
                $applyconfig = true;
            }
        }

        if ($applyconfig) {
            $msg = ' ' . _('Config applied, Nagios Core was restarted.');
        } else {
            $msg = ' ' . _('Config imported but not yet applied.');
        }

        delete_nagioscore_host($host, $applyconfig);
        return array('success' => _('Removed') . ' ' . $host . ' ' . _('from the system.') . $msg);
    }

    protected function remove_cfg_service()
    {
        $applyconfig = false;
        $missing = array();

        if (!array_key_exists('host_name', $this->request)) { $missing[] = 'host_name'; }
        if (!array_key_exists('service_description', $this->request)) { $missing[] = 'service_description'; }
        
        if (count($missing) > 0) {
            return array('error' => _('Missing required variables'), 'missing' => $missing);
        }

        $host = grab_array_var($this->request, 'host_name', '');
        $service = grab_array_var($this->request, 'service_description', '');
        $sid = nagiosql_get_service_id($host, $service);

        if ($sid <= 0) {
            return array('error' => _('Could not find a unique id for this service.'));
        }

        if (can_service_be_deleted($host, $service) == false) {
            return array('error' => _('Service cannot be deleted using this method. Must be deleted through the CCM.'));
        }

        // Check if we should applyconfig nagios after
        if (array_key_exists('applyconfig', $this->request)) {
            if ($this->request['applyconfig'] == 1) {
                $applyconfig = true;
            }
        }

        if ($applyconfig) {
            $msg = ' ' . _('Config applied, Nagios Core was restarted.');
        } else {
            $msg = ' ' . _('Config imported but not yet applied.');
        }

        delete_nagioscore_service($host, $service, $applyconfig);
        return array('success' => _('Removed') . ' ' . $host . ' :: ' . $service . ' ' . _('from the system.') . $msg);
    }

}


/**
 * @param $objects
 * @param $firsthost
 */
function get_cfg_objects_str($objects, &$firsthost)
{
    $have_first_host = false;
    $ncfg = "";

    // FIRST PROCESS NON-SERVICES
    foreach ($objects as $oid => $obj) {

        // Get the object type
        $oname = "";
        switch ($obj["type"]) {
            case OBJECTTYPE_HOST:
                $oname = "host";
                break;
            case OBJECTTYPE_HOSTGROUP:
                $oname = "hostgroup";
                break;
            case OBJECTTYPE_SERVICEGROUP:
                $oname = "servicegroup";
                break;
            case OBJECTTYPE_COMMAND:
                $oname = "command";
                break;
            default:
                break;
        }

        // Unhandled object types
        if ($oname == "") {
            continue;
        }

        // Write the object definition to file
        $ncfg .= "define " . $oname . "{\n";

        foreach ($obj as $var => $val) {
            if ($var == "type")
                continue;
            $ncfg .= $var . "\t" . $val . "\n";

            if ($oname == "host" && $have_first_host == false && $var == "host_name") {
                $have_first_host = true;
                $firsthost = $val;

                // Preload ndoutils with host
                add_ndoutils_object(OBJECTTYPE_HOST, $firsthost);
            }
        }
        $ncfg .= "}\n";
    }

    // PROCESS SERVICES
    $openfile = false;
    $importfiles = array();
    $multihost = false;
    foreach ($objects as $oid => $obj) {

        if ($have_first_host == false) {
            $have_first_host = true;
            $firsthost = $obj["host_name"];
        } else {
            if ($firsthost != $obj["host_name"])
                $multihost = true;
        }

        // Get the object type
        $oname = "";
        switch ($obj["type"]) {
            case OBJECTTYPE_SERVICE:
                $oname = "service";

                // Preload ndoutils with service
                add_ndoutils_object(OBJECTTYPE_SERVICE, $obj["host_name"], $obj["service_description"]);

                break;
            default:
                break;
        }

        // unhandled object types
        if ($oname == "")
            continue;

        // write the object definition to file
        $ncfg .= "define " . $oname . "{\n";

        foreach ($obj as $var => $val) {
            if ($var == "type")
                continue;
            $ncfg .= $var . "\t" . $val . "\n";
        }
        $ncfg .= "}\n";
    }

    return $ncfg;
}


function get_scheduled_downtime_xml_output()
{
    global $sqlquery;
    global $db_tables;
    global $request;

    // generate query
    $fieldmap = array(
        "instance_id" => $db_tables[DB_NDOUTILS]["instances"] . ".instance_id",
        "downtime_type" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".downtime_type",
        "entry_time" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".entry_time",
        "author_name" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".author_name",
        "comment_data" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".comment_data",
        "internal_id" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".internal_downtime_id",
        "triggered_by" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".triggered_by_id",
        "fixed" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".is_fixed",
        "duration" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".duration",
        "scheduled_start_time" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".scheduled_start_time",
        "scheduled_end_time" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".scheduled_end_time",
        "was_started" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".was_started",
        "actual_start_time" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".actual_start_time",
        "actual_start_time_usec" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".actual_start_time_usec",
        "object_id" => $db_tables[DB_NDOUTILS]["scheduleddowntime"] . ".object_id",
        "objecttype_id" => "obj1.objecttype_id",
        "host_name" => "obj1.name1",
        "service_description" => "obj1.name2"
    );
    $objectauthfields = array(
        "object_id"
    );
    $instanceauthfields = array(
        "instance_id"
    );
    $default_order = "scheduled_start_time DESC, scheduleddowntime_id DESC";
    $args = array(
        "sql" => $sqlquery['GetScheduledDowntime'],
        "fieldmap" => $fieldmap,
        "objectauthfields" => $objectauthfields,
        "objectauthperms" => P_READ,
        "instanceauthfields" => $instanceauthfields,
        "default_order" => $default_order
    );
    $sql = generate_sql_query(DB_NDOUTILS, $args);

    if (!($rs = exec_sql_query(DB_NDOUTILS, $sql))) {
        handle_backend_db_error(DB_NDOUTILS);
    } else {

        // Generate the XML
        $outputtype = grab_request_var("outputtype", "");
        $output = "<scheduleddowntimelist>\n";
        $output .= "  <recordcount>" . $rs->RecordCount() . "</recordcount>\n";

        if (!isset($request["totals"])) {
            while (!$rs->EOF) {
                if ($outputtype == "json") {

                } else {
                    $output .= "  <scheduleddowntime id='" . db_field($rs, 'scheduleddowntime_id') . "'>\n";
                }
                $output .= xml_db_field(2, $rs, 'instance_id', '', true);
                $output .= xml_db_field(2, $rs, 'downtime_type', '', true);
                $output .= xml_db_field(2, $rs, 'object_id', '', true);
                $output .= xml_db_field(2, $rs, 'objecttype_id', '', true);
                $output .= xml_db_field(2, $rs, 'host_name', '', true);
                $output .= xml_db_field(2, $rs, 'service_description', '', true);
                $output .= xml_db_field(2, $rs, 'entry_time', '', true);
                $output .= xml_db_field(2, $rs, 'author_name', '', true);
                $output .= xml_db_field(2, $rs, 'comment_data', '', true);
                $output .= xml_db_field(2, $rs, 'internal_downtime_id', 'internal_id', true);
                $output .= xml_db_field(2, $rs, 'triggered_by_id', 'triggered_by', true);
                $output .= xml_db_field(2, $rs, 'is_fixed', 'fixed', true);
                $output .= xml_db_field(2, $rs, 'duration', '', true);
                $output .= xml_db_field(2, $rs, 'scheduled_start_time', '', true);
                $output .= xml_db_field(2, $rs, 'scheduled_end_time', '', true);
                $output .= xml_db_field(2, $rs, 'was_started', '', true);
                $output .= xml_db_field(2, $rs, 'actual_start_time', '', true);
                $output .= xml_db_field(2, $rs, 'actual_start_time_usec', '', true);
                $output .= "  </scheduleddowntime>\n";
                $rs->MoveNext();
            }
        }

        $output .= "</scheduleddowntimelist>\n";

        return $output;
    }
}


function db_field($rs, $fieldname)
{
    return get_xml_db_field_val($rs, $fieldname);
}


function xml_db_field($level, $rs, $fieldname, $nodename = "", $return = false)
{
    $temp = get_xml_db_field($level, $rs, $fieldname, $nodename);
    if ($return) {
        return $temp;
    } else {
        echo $temp;
    }
}

function get_rrd_json_output($args) {

    if (in_demo_mode()) {
        return array("error" => _("Can not use this action in demo mode."));
    }

    if (empty($args['host_name'])) {
        return array('error' => _('You must provide host_name.'));
    }

    include_once('../../includes/utils-rrdexport.inc.php');

    // get values and defaults
    $host = $args['host_name'];
    $service = (empty($args['service_description']) ? "_HOST_" : $args['service_description']);
    $start = "";
    $end = "";
    $step = "";
    $columns_to_display = array();

    // check for proper timestamps - if *you* want the data, *you* convert to timestamp
    if (!empty($args['start'])) {
        $start = date("U", $args['start']);
        if (!$start)
            return array("error" => _("Start time must be in Unix timestamp format."));
    }
    if (!empty($args['end'])) {
        $end = date("U", $args['end']);
        if (!$end)
            return array("error" => _("End time must be in Unix timestamp format."));
    }

    // check for proper step value, default to 300 if nothing or invalid value
    if (!empty($args['step']) && is_numeric($args['step']))
        $step = $args['step'];

    // check for columns specified - if found, attempt to build an array of acceptable ones
    if (!empty($args['columns'])) {
        if (is_array($args['columns'])) {
            foreach($args['columns'] as $column) {
                $columns_to_display[] = $column;
            }
        } else {
            $columns_to_display[] = $args['columns'];
        }
    }
    
    $output = get_rrd_data($host, $service, 'json', $start, $end, $step, $columns_to_display);

    if (!$output) {
        return array("error" => _("No valid data returned."));
    } else {
        return $output;
    }
}