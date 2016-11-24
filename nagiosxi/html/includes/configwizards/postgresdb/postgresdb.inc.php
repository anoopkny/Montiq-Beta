<?php
//
// Postgres DB Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

postgresdb_configwizard_init();

function postgresdb_configwizard_init()
{
    $name = "postgresdb";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Postgres database."),
        CONFIGWIZARD_DISPLAYTITLE => _("Postgres Database"),
        CONFIGWIZARD_FUNCTION => "postgresdb_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "postgres.png",
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
function postgresdb_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "postgresdb";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "5432");
            $username = grab_array_var($inargs, "username", "postgres");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "postgres");

            $address = nagiosccm_replace_user_macros($address);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $output = '
<h5 class="ul">' . _('Postgres Database') . '</h5>
<p>' . _('Specify the details for connecting to the Postgres database you want to monitor.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address:') . '</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the Postgres server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port:') . '</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="form-control">
            <div class="subtext">' . _('The port to use to connect to the Postgres server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control">
            <div class="subtext">' . _('The username used to connect to the Postgres server.') . '</div>
        </td>
    </tr>

    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _('The password used to connect to the Postgres server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="form-control">
            <div class="subtext">' . _('The database to connect to on the Postgres server.') . '</div>
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

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = "";
            $services_default = array(
                "connection" => "on",
                "database_size" => "on",
                "table_sizes" => "on",
                "relation_sizes" => "on",
                "sequences" => "on",
            );

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", $services_default);

            $serviceargs = "";
            $serviceargs_default = array(
                "database_size_warning" => "500MB",
                "database_size_critical" => "1GB",
                "table_sizes_warning" => "200MB",
                "table_sizes_critical" => "400MB",
                "relation_sizes_warning" => "50MB",
                "relation_sizes_critical" => "100MB",
                "sequences_warning" => "30%",
                "sequences_critical" => "10%",
            );

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
            if (!is_array($serviceargs))
                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="' . htmlentities($database) . '">

<h5 class="ul">'._('Postgres Server').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this Postgres server.') . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Postgres Database Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor for the Postgres database.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="cs" class="checkbox" name="services[connection]" ' . is_checked(grab_array_var($services, "connection"), "on") . '>
        </td>
        <td>
            <label class="normal" for="cs">
                <b>' . _('Connection Status') . '</b><br>
                ' . _('Monitor the ability to connect to the database.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ds" class="checkbox" name="services[database_size]" ' . is_checked(grab_array_var($services, "database_size"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ds">
                <b>' . _('Database Size') . '</b><br>
                ' . _('Monitor the size of the database') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="5" name="serviceargs[database_size_warning]" value="' . htmlentities($serviceargs["database_size_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="5" name="serviceargs[database_size_critical]" value="' . htmlentities($serviceargs["database_size_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ts" class="checkbox" name="services[table_sizes]" ' . is_checked(grab_array_var($services, "table_sizes"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ts">
                <b>' . _('Table Sizes') . '</b><br>
                ' . _('Monitor the size of the tables in the database.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="5" name="serviceargs[table_sizes_warning]" value="' . htmlentities($serviceargs["table_sizes_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="5" name="serviceargs[table_sizes_critical]" value="' . htmlentities($serviceargs["table_sizes_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rs" class="checkbox" name="services[relation_sizes]" ' . is_checked(grab_array_var($services, "relation_sizes"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rs">
                <b>' . _('Relation Sizes') . '</b><br>
                ' . _('Monitor the size of the relations in the database.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="5" name="serviceargs[relation_sizes_warning]" value="' . htmlentities($serviceargs["relation_sizes_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="5" name="serviceargs[relation_sizes_critical]" value="' . htmlentities($serviceargs["relation_sizes_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="s" class="checkbox" name="services[sequences]" ' . is_checked(grab_array_var($services, "sequences"), "on") . '>
        </td>
        <td>
            <label class="normal" for="s">
                <b>' . _('Sequences') . '</b><br>
                ' . _('Monitor the percent of remaining sequences in the database.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[sequences_warning]" value="' . htmlentities($serviceargs["sequences_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[sequences_critical]" value="' . htmlentities($serviceargs["sequences_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>   
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

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
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
            ';
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
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_postgresdb_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "postgres.png",
                    "statusmap_image" => "postgres.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $commonopts = "-H " . $address . " --port=" . $port . " --dbuser=" . $username . " --dbname=" . $database . " ";

            foreach ($services as $svcvar => $svcval) {

                $pluginopts = "";
                $pluginopts .= $commonopts;

                if ($password != "")
                    $pluginopts .= " --dbpass=\"" . $password . "\"";

                switch ($svcvar) {

                    case "connection":

                        $pluginopts .= " --action=connection";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Database Connection - " . $database,
                            "use" => "xiwizard_postgresdb_service",
                            "check_command" => "check_xi_postgres_db!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "database_size":

                        $pluginopts .= " --action=database_size";
                        if ($serviceargs["database_size_warning"] != "")
                            $pluginopts .= " --warning=" . $serviceargs["database_size_warning"] . "";
                        if ($serviceargs["database_size_critical"] != "")
                            $pluginopts .= " --critical=" . $serviceargs["database_size_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Database Size - " . $database,
                            "use" => "xiwizard_postgresdb_service",
                            "check_command" => "check_xi_postgres_db!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "table_sizes":

                        $pluginopts .= " --action=table_size";
                        if ($serviceargs["table_sizes_warning"] != "")
                            $pluginopts .= " --warning=" . $serviceargs["table_sizes_warning"] . "";
                        if ($serviceargs["table_sizes_critical"] != "")
                            $pluginopts .= " --critical=" . $serviceargs["table_sizes_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Table Sizes - " . $database,
                            "use" => "xiwizard_postgresdb_service",
                            "check_command" => "check_xi_postgres_db!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "relation_sizes":

                        $pluginopts .= " --action=relation_size";
                        if ($serviceargs["relation_sizes_warning"] != "")
                            $pluginopts .= " --warning=" . $serviceargs["relation_sizes_warning"] . "";
                        if ($serviceargs["relation_sizes_critical"] != "")
                            $pluginopts .= " --critical=" . $serviceargs["relation_sizes_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Table Sizes - " . $database,
                            "use" => "xiwizard_postgresdb_service",
                            "check_command" => "check_xi_postgres_db!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sequences":

                        $pluginopts .= " --action=sequence";
                        if ($serviceargs["sequences_warning"] != "")
                            $pluginopts .= " --warning=" . $serviceargs["sequences_warning"] . "";
                        if ($serviceargs["sequences_critical"] != "")
                            $pluginopts .= " --critical=" . $serviceargs["sequences_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Database Sequences - " . $database,
                            "use" => "xiwizard_postgresdb_service",
                            "check_command" => "check_xi_postgres_db!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    default:
                        break;
                }
            }

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