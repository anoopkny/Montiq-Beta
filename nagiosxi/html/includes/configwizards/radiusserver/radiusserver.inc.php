<?php
//
// RADIUS Sserver Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

radiusserver_configwizard_init();

function radiusserver_configwizard_init()
{
    $name = "radiusserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a RADIUS server."),
        CONFIGWIZARD_DISPLAYTITLE => _("RADIUS Server"),
        CONFIGWIZARD_FUNCTION => "radiusserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "globe4.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
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
function radiusserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "radiusserver";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $output = '
<h5 class="ul">' . _('RADIUS Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the device or server associated with the RADIUS server.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $secret = grab_array_var($inargs, "secret");
            $port = grab_array_var($inargs, "port", "1812");

            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);
            $port = nagiosccm_replace_user_macros($port);

            $services = grab_array_var($inargs, "services", array("server" => "on", "transfer" => ""));

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('RADIUS Server') . '</h5>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this RADIUS server.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Server Options') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Secret') . ':</label>
        </td>
        <td>
            <input type="password" size="16" name="secret" value="' . htmlentities($secret) . '" class="form-control">
            <div class="subtext">' . _('The shared secret to use when logging into the RADIUS server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="16" name="username" value="' . htmlentities($username) . '" class="form-control">
            <div class="subtext">' . _('The username used to login to the RADIUS server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="text" size="16" name="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _('The password used to login to the RADIUS server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="3" name="port" value="' . htmlentities($port) . '" class="form-control">
            <div class="subtext">' . _('The port number the RADIUS server runs on.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $secret = grab_array_var($inargs, "secret");
            $port = grab_array_var($inargs, "port");

            $services = grab_array_var($inargs, "services", array());


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if ($username == "" || $password == "")
                $errmsg[$errors++] = _("Username or password is blank.");
            if ($secret == "")
                $errmsg[$errors++] = _("Secret is blank.");
            if ($port == "")
                $errmsg[$errors++] = _("Invalid port number.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $secret = grab_array_var($inargs, "secret");
            $port = grab_array_var($inargs, "port");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="secret" value="' . htmlentities($secret) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
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

            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $secret = grab_array_var($inargs, "secret");
            $port = grab_array_var($inargs, "port");

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
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["secret"] = $secret;
            $meta_arr["port"] = $port;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_radiusserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "globe4.png",
                    "statusmap_image" => "globe4.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $pluginopts = "";
            $pluginopts .= "-u \"" . $username . "\" -p \"" . $password . "\" -s \"" . $secret . "\"";

            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => "RADIUS Server",
                "use" => "xiwizard_radiusserver_radius_service",
                "check_command" => "check_radius_server_adv!" . $pluginopts,
                "_xiwizard" => $wizard_name,
                "icon_image" => "globe4.png",
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