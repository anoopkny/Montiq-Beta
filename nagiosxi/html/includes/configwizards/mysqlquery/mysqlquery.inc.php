<?php
//
// MySQL Query Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mysqlquery_configwizard_init();

function mysqlquery_configwizard_init()
{
    $name = "mysqlquery";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a MySQL database query."),
        CONFIGWIZARD_DISPLAYTITLE => "MySQL Query",
        CONFIGWIZARD_FUNCTION => "mysqlquery_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mysql.png",
        CONFIGWIZARD_FILTER_GROUPS => array('database'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );

    register_configwizard($name, $args);
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function mysqlquery_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mysqlquery";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "3306");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "information_schema");

            $address = nagiosccm_replace_user_macros($address);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $output = '
<h5 class="ul">'._('MySQL Server').'</h5>
<p>' . _('Specify the details for connecting to the MySQL server you want to monitor.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control">
            <div class="subtext">' . _('The port to use to connect to the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control">
            <div class="subtext">' . _('The username used to connect to the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password used to connect to the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="textfield form-control">
            <div class="subtext">' . _('The database to connect to on the MySQL server') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            if (have_value($port) == false)
                $errmsg[$errors++] = _("No port number specified.");
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
            $address = grab_array_var($inargs, "address");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $query = grab_array_var($inargs, "query", "SELECT COUNT(*) FROM sometable");
            $queryname = grab_array_var($inargs, "queryname", "Test Query");
            $warning = grab_array_var($inargs, "warning", "50");
            $critical = grab_array_var($inargs, "critical", "200");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="' . htmlentities($database) . '">

<h5 class="ul">' . _('MySQL Server') . '</h5>
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
            <div class="subtext">' . _("The name you'd like to have associated with this MySQL server") . '.</div>
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
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control" disabled>
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

<h5 class="ul">' . _('MySQL Query') . '</h5>
<p>' . _("Specify the details of the query you'd like to monitor") . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Query Name') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="queryname" id="queryname" value="' . htmlentities($queryname) . '" class="textfield form-control">
            <div class="subtext">' . _('A friendly name for the SQL query') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Query') . ':</label>
        </td>
        <td>
            <input type="text" style="width: 650px;" name="query" id="query" value="' . htmlentities($query) . '" class="textfield form-control">
            <div class="subtext">' . _('The SQL query to run.').' <b>'._('Must return an integer') . '.</b></div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Warning') . ':</label>
        </td>
        <td>
            <input type="text" size="4" name="warning" id="warning" value="' . htmlentities($warning) . '" class="textfield form-control">
            <div class="subtext">' . _('An optional warning threshold to use when checking the result of the SQL query') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Critical') . ':</label>
        </td>
        <td>
            <input type="text" size="4" name="critical" id="critical" value="' . htmlentities($critical) . '" class="textfield form-control">
            <div class="subtext">' . _('An optional critical threshold to use when checking the result of the SQL query') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $query = grab_array_var($inargs, "query", "");
            $queryname = grab_array_var($inargs, "queryname", "");
            $warning = grab_array_var($inargs, "warning", "");
            $critical = grab_array_var($inargs, "critical", "");

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (is_valid_service_name($queryname) == false)
                $errmsg[$errors++] = _("Invalid query name") . " '" . $queryname . "'.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $query = grab_array_var($inargs, "query", "");
            $queryname = grab_array_var($inargs, "queryname", "");
            $warning = grab_array_var($inargs, "warning", "");
            $critical = grab_array_var($inargs, "critical", "");

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            else
                $services = grab_array_var($inargs, "services");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            else
                $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="' . htmlentities($database) . '">
<input type="hidden" name="query" value="' . htmlentities($query) . '">
<input type="hidden" name="queryname" value="' . htmlentities($queryname) . '">
<input type="hidden" name="warning" value="' . htmlentities($warning) . '">
<input type="hidden" name="critical" value="' . htmlentities($critical) . '">
<input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
<input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
<!--SERVICES=' . serialize($services) . '<BR>
SERVICEARGS=' . serialize($serviceargs) . '<BR>-->';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:


            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $hostaddress = $address;
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $query = grab_array_var($inargs, "query", "");
            $queryname = grab_array_var($inargs, "queryname", "");
            $warning = grab_array_var($inargs, "warning", "");
            $critical = grab_array_var($inargs, "critical", "");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            /*
            echo "SERVICES<BR>";
            print_r($services);
            echo "<BR>";
            echo "SERVICEARGS<BR>";
            print_r($serviceargs);
            echo "<BR>";
            */

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["port"] = $port;
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["database"] = $database;
            $meta_arr["query"] = $query;
            $meta_arr["queryname"] = $queryname;
            $meta_arr["warning"] = $warning;
            $meta_arr["critical"] = $critical;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mysqlquery_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "mysql.png",
                    "statusmap_image" => "mysql.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // common plugin opts
            $commonopts = "--hostname=" . $address . " --port=" . $port . " --username=" . $username . " --password=\"" . $password . "\" --database=" . $database . " --mode sql  ";

            $pluginopts = $commonopts . " --name=" . rawurlencode($query) . " --name2=\"" . $queryname . "\"";
            if ($warning != "")
                $pluginopts .= " --warning=" . $warning;
            if ($critical != "")
                $pluginopts .= " --critical=" . $critical;


            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => "MySQL Query - " . $queryname,
                "use" => "xiwizard_mysqlquery_service",
                "check_command" => "check_xi_mysql_health!" . $pluginopts,
                "_xiwizard" => $wizard_name,
            );

            //echo "OBJECTS:<BR>";
            //print_r($objs);
            //exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


?>