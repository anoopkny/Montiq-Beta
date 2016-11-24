<?php
//
// Postgres Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

postgresserver_configwizard_init();

function postgresserver_configwizard_init()
{
    $name = "postgresserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a Postgres server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Postgres Server"),
        CONFIGWIZARD_FUNCTION => "postgresserver_configwizard_func",
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
function postgresserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "postgresserver";

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
<h5 class="ul">' . _('Postgres Server') . '</h5>
<p>' . _('Specify the details for connecting to the Postgres server you want to monitor.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td valign="top">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the Postgres server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
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
                "backends" => "on",
                "wal_files" => "",
            );

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", $services_default);

            $serviceargs = "";
            $serviceargs_default = array(
                "backends_warning" => "5",
                "backends_critical" => "10",
                "wal_files_warning" => "10",
                "wal_files_critical" => "20",
            );

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                //echo "ARGSSERIAL: $serviceargs_serial<BR>\n";
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

<h5 class="ul">' . _('Postgres Server') . '</h5>
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
            <label>' . _('Host Name') . ':</label>
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

<h5 class="ul">' . _('Postgres Server Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the Postgres server.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="cs" class="checkbox" name="services[connection]" ' . is_checked(grab_array_var($services, "connection"), "on") . '>
        </td>
        <td>
            <label class="normal" for="cs">
                <b>' . _('Connection Status') . '</b><br>
                ' . _('Monitor the ability to connect to the server.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="bc" class="checkbox" name="services[backends]" ' . is_checked(grab_array_var($services, "backends"), "on") . '>
        </td>
        <td>
            <label class="normal" for="bc">
                <b>' . _('Backend Connections') . '</b><br>
                ' . _('Monitor the time the number of connections to the server.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[backends_warning]" value="' . htmlentities($serviceargs["backends_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[backends_critical]" value="' . htmlentities($serviceargs["backends_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="wf" class="checkbox" name="services[wal_files]" ' . is_checked(grab_array_var($services, "wal_files"), "on") . '>
        </td>
        <td>
            <label class="normal" for="wf">
                <b>' . _('WAL Files') . '</b><br>
                ' . _('Monitor the time the number of WAL files in the pg_xlog directory.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[wal_files_warning]" value="' . htmlentities($serviceargs["wal_files_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[wal_files_critical]" value="' . htmlentities($serviceargs["wal_files_critical"]) . '" class="form-control condensed">
            </div>
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

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // Check for errors
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

            // Get variables that were passed to us
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
                    "use" => "xiwizard_postgresserver_host",
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
                $pluginopts = $commonopts;
                if ($password != "")
                    $pluginopts .= " --dbpass=\"" . $password . "\"";

                switch ($svcvar) {

                    case "connection":

                        $pluginopts .= "--action=connection";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Connection Status",
                            "use" => "xiwizard_postgresserver_service",
                            "check_command" => "check_xi_postgres!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "backends":

                        $pluginopts .= " --action=backends";
                        if ($serviceargs["backends_warning"] != "")
                            $pluginopts .= " --warning=" . $serviceargs["backends_warning"] . "";
                        if ($serviceargs["backends_critical"] != "")
                            $pluginopts .= " --critical=" . $serviceargs["backends_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres Backend Connections",
                            "use" => "xiwizard_postgresserver_service",
                            "check_command" => "check_xi_postgres!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "wal_files":

                        $pluginopts .= " --action=wal_files";
                        if ($serviceargs["wal_files_warning"] != "")
                            $pluginopts .= " --warning=" . $serviceargs["wal_files_warning"] . "";
                        if ($serviceargs["wal_files_critical"] != "")
                            $pluginopts .= " --critical=" . $serviceargs["wal_files_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Postgres WAL Files",
                            "use" => "xiwizard_postgresserver_service",
                            "check_command" => "check_xi_postgres!" . $pluginopts,
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