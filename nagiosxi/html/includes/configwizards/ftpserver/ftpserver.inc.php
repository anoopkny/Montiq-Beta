<?php
//
// FTP Server Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

ftpserver_configwizard_init();

function ftpserver_configwizard_init()
{
    $name = "ftpserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor login and file transfer capabilities of an FTP server."),
        CONFIGWIZARD_DISPLAYTITLE => "FTP Server",
        CONFIGWIZARD_FUNCTION => "ftpserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "ftpserver.png",
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
function ftpserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "ftpserver";

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
<h5 class="ul">' . _('FTP Server') . '</h5>   
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the device or server associated with the FTP server') . '.</div>
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

            $services_serial = grab_array_var($inargs, "services_serial");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            else
                $services = grab_array_var($inargs, "services", array(
                    "server" => "on",
                    "transfer" => "",
                ));
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            else
                $serviceargs = grab_array_var($inargs, "serviceargs", array(
                    "server_port" => "21",
                    "server_ssl" => "",
                    "transfer_username" => "",
                    "transfer_password" => "",
                    "transfer_port" => "21",
                ));

            $server_ssl = grab_array_var("server_ssl", $serviceargs);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('FTP Server') . '</h5>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this FTP server.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('FTP Server Metrics') . '</h5>
<p>' . _('Check the options you would like to monitor on the FTP server.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[server]" ' . is_checked(htmlentities($services["server"]), "on") . '>
        </td>
        <td>
            <b>' . _('FTP Server') . '</b><br>
            ' . _('Check the FTP server to ensure it can be contacted by clients.') . '<br>
            <div class="pad-t5">
                <label>' . _('Port') . ':</label>
                <input type="text" size="3" name="serviceargs[server_port]" value="' . htmlentities($serviceargs["server_port"]) . '" class="textfield form-control condensed">
                <span class="checkbox" style="display: inline-block; margin-left: 10px;">
                    <label>
                        <input type="checkbox" name="serviceargs[server_ssl]" ' . is_checked($server_ssl, "on") . '>
                        ' . _('Use SSL') . '
                    </label>
                </span>
            </div>
        </td>
    </tr>
    <tr>
        <td valign="top">
            <input type="checkbox" class="checkbox" name="services[transfer]" ' . is_checked($services["transfer"], "on") . '>
        </td>
        <td>
            <b>' . _('File Transfer') . '</b><br>
            ' . _('Check the FTP server to ensure a test file can be uploaded to and deleted from the root directory. Does not support SSL.') . '
            <div class="pad-t5">
                <label>' . _('Username') . ':</label>
                <input type="text" size="10" name="serviceargs[transfer_username]" value="' . htmlentities($serviceargs["transfer_username"]) . '" class="textfield form-control condensed">
                <label>' . _('Password') . ':</label>
                <input type="text" size="10" name="serviceargs[transfer_password]" value="' . htmlentities($serviceargs["transfer_password"]) . '" class="textfield form-control condensed">
                <label>' . _('Port') . ':</label>
                <input type="text" size="3" name="serviceargs[transfer_port]" value="' . htmlentities($serviceargs["transfer_port"]) . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // Check for errors
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
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
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

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_ftpserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "ftpserver.png",
                    "statusmap_image" => "ftpserver.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            if (array_key_exists("server", $services)) {

                $pluginopts = "";
                if (array_key_exists("server_port", $serviceargs))
                    $pluginopts .= " -p " . $serviceargs["server_port"];
                if (array_key_exists("server_ssl", $serviceargs))
                    $pluginopts .= " -S";

                $objs[] = array(
                    "type" => OBJECTTYPE_SERVICE,
                    "host_name" => $hostname,
                    "service_description" => "FTP Server",
                    "use" => "xiwizard_ftpserver_server_service",
                    "check_command" => "check_xi_service_ftp!" . $pluginopts,
                    "_xiwizard" => $wizard_name,
                    "icon_image" => "ftpserver.png",
                );
            }

            if (array_key_exists("transfer", $services)) {

                $username = grab_array_var($serviceargs, "transfer_username");
                $password = grab_array_var($serviceargs, "transfer_password");
                $port = grab_array_var($serviceargs, "transfer_port");

                $objs[] = array(
                    "type" => OBJECTTYPE_SERVICE,
                    "host_name" => $hostname,
                    "service_description" => "FTP Transfer",
                    "use" => "xiwizard_ftpserver_transfer_service",
                    "check_command" => "check_ftp_fully!" . $username . "!" . $password . "!" . $port,
                    "_xiwizard" => $wizard_name,
                );
            }

            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}