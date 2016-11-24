<?php
//
// Nagios XI Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

nagiosxiserver_configwizard_init();

function nagiosxiserver_configwizard_init()
{
    $name = "nagiosxiserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a remote Nagios XI server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Nagios XI Server"),
        CONFIGWIZARD_FUNCTION => "nagiosxiserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "nagiosxiserver.png",
        CONFIGWIZARD_VERSION => "1.2.3",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @param $url
 * @param $username
 * @param $password
 *
 * @return string
 */
function nagiosxiserver_configwizard_get_ticket($url, $username, $password)
{
    $ticket = "";

    // Form the url to get the user's ticket
    $xiurl = $url;
    $xiurl .= "/backend/";
    $xiurl .= "?cmd=getticket&username=" . $username . "&password=" . md5($password);

    // Args to control how we get data
    $args = array(
        'method' => 'get',
        'return_info' => false,
    );

    $data = load_url($xiurl, $args);

    // Turn the data into XML
    if ($data) {
        $xml = @simplexml_load_string($data);
        if ($xml) {
            $ticket = strval($xml);
        }
    }

    return $ticket;
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function nagiosxiserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "nagiosxiserver";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $url = grab_array_var($inargs, "url", "");
            $username = grab_array_var($inargs, "username", "nagiosadmin");
            $password = grab_array_var($inargs, "password", "");

            $address = nagiosccm_replace_user_macros($address);
            $url = nagiosccm_replace_user_macros($url);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $output = '
<h5 class="ul">' . _('Nagios XI Server') . '</h5>
<p>' . _('Specify the details for the remote Nagios XI server you want to monitor.') . ' <strong>' . _('Note:') . '</strong> ' . _('This wizard requires that the remote Nagios XI server be running 2009R1.2B or later.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address:') . '</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the remote Nagios XI server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>URL:</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="textfield form-control">
            <div class="subtext">' . _('The full URL used to the remote Nagios XI server\'s web interface. Make sure you include the full path.') . '<br>' . _('Example:') . ' <b>http://192.168.1.1/nagiosxi/</b></div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Authentication Credentials') . '</h5>
<p>' . _('Specify credentials that should be used to authenticate to the Nagios XI server.  You must authenticate with a user account that has Administrator privileges.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Username:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control">
            <div class="subtext">' . _('The username used to authenticate to the remote Nagios XI server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password:') . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password used to authenticate to the remote Nagios XI server.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $url = grab_array_var($inargs, "url", "");
            $username = grab_array_var($inargs, "username", "nagiosadmin");
            $password = grab_array_var($inargs, "password", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            if (have_value($url) == false)
                $errmsg[$errors++] = _("No URL specified.");
            if (have_value($username) == false)
                $errmsg[$errors++] = _("No username specified.");
            if (have_value($password) == false)
                $errmsg[$errors++] = _("No password specified.");

            // Check authentication, version
            if ($errors == 0) {

                // Get ticket...
                $ticket = nagiosxiserver_configwizard_get_ticket($url, $username, $password);

                // See if we have a ticket
                if (!have_value($ticket)) {
                    $errmsg[$errors++] = _("Unable to authenticate to remote Nagios XI server - Check your credentials and the remote XI server version.");
                }

            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $url = grab_array_var($inargs, "url", "");
            $username = grab_array_var($inargs, "username", "nagiosadmin");
            $password = grab_array_var($inargs, "password", "");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array(
                "ping" => "on",
                "webui" => "on",
                "daemons" => "on",
                "jobs" => "on",
                "datatransfer" => "on",
                "load" => "on",
                "iowait" => "on",
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "load_warning" => "5,4,4",
                "load_critical" => "10,10,7",
                "iowait_warning" => "5",
                "iowait_critical" => "15",
            ));

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            // Get ticket used to access Nagios XI backend
            $ticket = nagiosxiserver_configwizard_get_ticket($url, $username, $password);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="url" value="' . htmlentities($url) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="ticket" value="' . htmlentities($ticket) . '">

<h5 class="ul">' . _('Nagios XI Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this Nagios server.') . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>URL:</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Username:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Password:') . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Nagios XI Server Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the remote Nagios server.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="p" class="checkbox" name="services[ping]" ' . is_checked(grab_array_var($services, "ping"), "on") . '>
        </td>
        <td>
            <label class="normal" for="p">
                <b>Ping</b><br> 
                ' . _('Checks the server with an ICMP ping.  Useful for monitoring network availability of the Nagios server.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" id="wui" class="checkbox" name="services[webui]" ' . is_checked(grab_array_var($services, "webui"), "on") . '>
        </td>
        <td>
            <label class="normal" for="wui">
                <b>' . _('Nagios XI Web Interface') . '</b><br>
                ' . _('Checks the availability of the remote Nagios XI server\'s web interface.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" id="md" class="checkbox" name="services[daemons]" ' . is_checked(grab_array_var($services, "daemons"), "on") . '>
        </td>
        <td>
            <label class="normal" for="md">
                <b>' . _('Monitoring Daemons') . '</b><br>
                ' . _('Monitors the XI server to ensure the monitoring engine and supporting daemons are running.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" id="mj" class="checkbox" name="services[jobs]" ' . is_checked(grab_array_var($services, "jobs"), "on") . '>
        </td>
        <td>
            <label class="normal" for="mj">
                <b>' . _('Monitoring Jobs') . '</b><br>
                ' . _('Monitors the XI server to ensure the core jobs are running.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="l" class="checkbox" name="services[load]" ' . is_checked(grab_array_var($services, "load"), "on") . '>
        </td>
        <td>
            <label class="normal" for="l">
                <b>'._('Load').'</b><br>
                ' . _('Monitors the load on the server (1/5/15 minute values).') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_warning]" value="' . htmlentities(grab_array_var($serviceargs, "load_warning")) . '" class="textfield form-control condensed"> &nbsp;
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="7" name="serviceargs[load_critical]" value="' . htmlentities(grab_array_var($serviceargs, "load_critical")) . '" class="textfield form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="iow" class="checkbox" name="services[iowait]" ' . is_checked(grab_array_var($services, "iowait"), "on") . '>
        </td>
        <td>
            <label class="normal" for="iow">
                <b>I/O Wait</b><br>
                ' . _('Monitors the server iowait CPU statistics (a measure of disk read/write wait time).') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[iowait_warning]" value="' . htmlentities(grab_array_var($serviceargs, "iowait_warning")) . '" class="textfield form-control condensed"> % &nbsp;
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[iowait_critical]" value="' . htmlentities(grab_array_var($serviceargs, "iowait_critical")) . '" class="textfield form-control condensed"> %
            </div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $url = grab_array_var($inargs, "url", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $ticket = grab_array_var($inargs, "ticket", "");

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
            $url = grab_array_var($inargs, "url", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $ticket = grab_array_var($inargs, "ticket", "");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="url" value="' . htmlentities($url) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="ticket" value="' . htmlentities($ticket) . '">
        <input type="hidden" name="services_serial" value="' . $services_serial . '">
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">
        
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
            $url = grab_array_var($inargs, "url", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $ticket = grab_array_var($inargs, "ticket", "");

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
            $meta_arr["url"] = $url;
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["ticket"] = $ticket;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_nagiosxiserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "nagiosxiserver.png",
                    "statusmap_image" => "nagiosxiserver.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // common plugin opts
            $commonopts = "--address=" . $address . " --url=" . $url . " --username=" . $username . " --ticket=\"" . $ticket . "\" ";

            foreach ($services as $svcvar => $svcval) {

                $pluginopts = "";
                $pluginopts .= $commonopts;

                switch ($svcvar) {

                    case "ping":

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Ping",
                            "use" => "xiwizard_nagiosxiserver_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "webui":

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "HTTP",
                            "use" => "xiwizard_nagiosxiserver_http_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "daemons":

                        $pluginopts = $commonopts . " --mode=daemons";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Nagios XI Daemons",
                            "use" => "xiwizard_nagiosxiserver_service",
                            "check_command" => "check_xi_nagiosxiserver!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "jobs":

                        $pluginopts = $commonopts . " --mode=jobs";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Nagios XI Jobs",
                            "use" => "xiwizard_nagiosxiserver_service",
                            "check_command" => "check_xi_nagiosxiserver!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "iowait":

                        $pluginopts = $commonopts . " --mode=iowait --warn=\"" . grab_array_var($serviceargs, "iowait_warning") . "\" --crit=\"" . grab_array_var($serviceargs, "iowait_critical") . "\"";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "I/O Wait",
                            "use" => "xiwizard_nagiosxiserver_service",
                            "check_command" => "check_xi_nagiosxiserver!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "load":

                        $pluginopts = $commonopts . " --mode=load --warn=\"" . grab_array_var($serviceargs, "load_warning") . "\" --crit=\"" . grab_array_var($serviceargs, "load_critical") . "\"";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Load",
                            "use" => "xiwizard_nagiosxiserver_service",
                            "check_command" => "check_xi_nagiosxiserver!" . $pluginopts,
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