<?php
//
// MongoDB Database Config Wizard
// Copyright (c) 2013-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mongodb_database_configwizard_init();

function mongodb_database_configwizard_init()
{
    $name = "mongodb_database";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a MongoDB Database"),
        CONFIGWIZARD_DISPLAYTITLE => _("MongoDB Database"),
        CONFIGWIZARD_FUNCTION => "mongodb_database_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mongodb_db.png",
        CONFIGWIZARD_FILTER_GROUPS => array('database'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @return bool
 */
function mongodb_database_configwizard_check_prereqs()
{
    // plugin doesn't exist
    if (!file_exists("/usr/local/nagios/libexec/check_mongodb.py")) {
        return false;
    }

    // run the plugin to see if pymongo is installed
    $retval = 0;
    $cmdline = "/usr/local/nagios/libexec/check_mongodb.py | head --lines=1";
    $output = exec($cmdline);

    if (strstr($output, "No module named pymongo"))
        return false;

    return true;
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function mongodb_database_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mongodb_database";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            if (mongodb_database_configwizard_check_prereqs() == false) {

                $output .= '<p><b>' . _('Error') . ':</b> ' . _('It looks like you are missing pymongo on your Nagios XI server.') . '</p><p>' . _('To use this wizard you must install pymongo on your server. If you are using CentOS or RHEL you can run "yum install pymongo".') . '</p>';
            } else {

                $address = grab_array_var($inargs, "address", "");
                $port = grab_array_var($inargs, "port", "27017");
                $username = grab_array_var($inargs, "username", "");
                $password = grab_array_var($inargs, "password", "");
                $database = grab_array_var($inargs, "database", "test");

                // Swap out eligible user macros if detected
                $address = nagiosccm_replace_user_macros($address);
                $port = nagiosccm_replace_user_macros($port);
                $username = nagiosccm_replace_user_macros($username);
                $password = nagiosccm_replace_user_macros($password);

                if (!empty($services_serial))
                    $services = unserialize(base64_decode($services_serial));
                $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
                if (!empty($serviceargs_serial)) {
                    $serviceargs = unserialize(base64_decode($serviceargs_serial));
                }

                $output = '
<h5 class="ul">' . _('MongoDB Information') . '</h5>
<p>' . _('Specify the details for connecting to the MongoDB database you want to monitor') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the MongoDB server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control">
            <div class="subtext">' . _('The port to use to connect to the MongoDB server. This defaults to port 27017, however if you changed the port number enter it here') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control">
            <div class="subtext">' . _('The username used to connect to the database') . '.</div>
        </td>
    </tr> 
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password for the above user') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="30" name="database" id="database" value="' . htmlentities($database) . '" class="textfield form-control">
            <div class="subtext">' . _('The database you want to monitor') . '.</div>
        </td>
    </tr>
</table>';
            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "27017");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "test");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            if (have_value($username) == false)
                $errmsg[$errors++] = _("No username specified.");
            if (have_value($password) == false)
                $errmsg[$errors++] = _("No password specified.");
            if (have_value($database) == false)
                $errmsg[$errors++] = _("No database specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "27017");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "test");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array(
                "collections" => "on",
                "objects" => "on",
                "database_size" => "on"
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "collections_warning" => "50",
                "collections_critical" => "200",
                "objects_warning" => "200",
                "objects_critical" => "1000",
                "database_size_warning" => "33554432", // 32 MB
                "database_size_critical" => "67108864" // 64 MB
            ));

            // Replace defaults with the given values
            $services_serial = grab_array_var($inargs, "services_serial");
            if (!empty($services_serial))
                $services = unserialize(base64_decode($services_serial));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if (!empty($serviceargs_serial)) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="' . htmlentities($database) . '">
    
<h5 class="ul">' . _('MongoDB Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this MongoDB Database') . '.</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="textfield form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('MongoDB Database Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the MongoDB database') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="nc" class="checkbox" name="services[collections]" ' . is_checked(grab_array_var($services, "collections"), "on") . '>
        </td>
        <td>
            <label class="normal" for="nc">
                <b>' . _('Number of Collections') . '</b><br>
                ' . _('Monitor the number of collections in the database') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[collections_warning]" value="' . htmlentities($serviceargs["collections_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[collections_critical]" value="' . htmlentities($serviceargs["collections_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="no" class="checkbox" name="services[objects]" ' . is_checked(grab_array_var($services, "objects"), "on") . '>
        </td>
        <td>
            <label class="normal" for="no">
                <b>' . _('Number of Objects') . '</b><br>
                ' . _('Monitor the number of objects (documents) in the database') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="4" name="serviceargs[objects_warning]" value="' . htmlentities($serviceargs["objects_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="4" name="serviceargs[objects_critical]" value="' . htmlentities($serviceargs["objects_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ds" class="checkbox" name="services[database_size]" ' . is_checked(grab_array_var($services, "database_size"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ds">
                <b>' . _('Database Size') . '</b><br>
                ' . _('Monitor the size of the database in bytes') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="10" name="serviceargs[database_size_warning]" value="' . htmlentities($serviceargs["database_size_warning"]) . '" class="form-control condensed"> b&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="10" name="serviceargs[database_size_critical]" value="' . htmlentities($serviceargs["database_size_critical"]) . '" class="form-control condensed"> b
            </div>
        </td>
    </tr>

    </table>
    ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "27017");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "test");

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = "Invalid host name.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "27017");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "test");

            $services = grab_array_var($inargs, "services", array());
            if (empty($services)) {
                $services_serial = grab_array_var($inargs, "services_serial");
            } else {
                $services_serial = base64_encode(serialize($services));
            }
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            if (empty($serviceargs)) {
                $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            } else {
                $serviceargs_serial = base64_encode(serialize($serviceargs));
            }

            $output = '
            
                <input type="hidden" name="address" value="' . htmlentities($address) . '" />
                <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
                <input type="hidden" name="port" value="' . htmlentities($port) . '" />
                <input type="hidden" name="username" value="' . htmlentities($username) . '" />
                <input type="hidden" name="password" value="' . htmlentities($password) . '" />
                <input type="hidden" name="database" value="' . htmlentities($database) . '" />
                <input type="hidden" name="services_serial" value="' . $services_serial . '" />
                <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '" />
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "27017");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "test");

            $services = grab_array_var($inargs, "services", array());
            if (empty($services)) {
                $services_serial = grab_array_var($inargs, "services_serial");
            } else {
                $services_serial = base64_encode(serialize($services));
            }
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            if (empty($serviceargs)) {
                $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            } else {
                $serviceargs_serial = base64_encode(serialize($serviceargs));
            }

            $output = '
            
                <input type="hidden" name="address" value="' . htmlentities($address) . '" />
                <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
                <input type="hidden" name="port" value="' . htmlentities($port) . '" />
                <input type="hidden" name="username" value="' . htmlentities($username) . '" />
                <input type="hidden" name="password" value="' . htmlentities($password) . '" />
                <input type="hidden" name="database" value="' . htmlentities($database) . '" />
                <input type="hidden" name="services_serial" value="' . $services_serial . '" />
                <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '" />
        
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            // Get the vars
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $database = grab_array_var($inargs, "database");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["port"] = $port;
            $meta_arr["database"] = $database;
            $meta_arr["username"] = $username;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mongodbdatabase_host",
                    "host_name" => $hostname,
                    "address" => $address,
                    "icon_image" => "mongodb.png",
                    "statusmap_image" => "mongodb.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            foreach ($services as $svcvar => $svcval) {

                switch ($svcvar) {

                    case "collections":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Database Collections",
                            "use" => "xiwizard_mongodbdatabase_service",
                            "check_command" => "check_mongodb_database!collections!" . $port . "!" . $serviceargs["collections_warning"] . "!" . $serviceargs["collections_critical"] . "!" . $username . "!" . $password . "!" . $database,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "objects":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Database Objects",
                            "use" => "xiwizard_mongodbdatabase_service",
                            "check_command" => "check_mongodb_database!objects!" . $port . "!" . $serviceargs["objects_warning"] . "!" . $serviceargs["objects_critical"] . "!" . $username . "!" . $password . "!" . $database,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "database_size":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Database Size",
                            "use" => "xiwizard_mongodbdatabase_service",
                            "check_command" => "check_mongodb_database!database_size!" . $port . "!" . $serviceargs["database_size_warning"] . "!" . $serviceargs["database_size_critical"] . "!" . $username . "!" . $password . "!" . $database,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    default:
                        break;
                }
            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}