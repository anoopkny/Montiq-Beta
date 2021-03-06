<?php
//
// Website URL Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

websiteurl_configwizard_init();

function websiteurl_configwizard_init()
{
    $name = "websiteurl";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.5",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a specific web URL."),
        CONFIGWIZARD_DISPLAYTITLE => _("Website URL"),
        CONFIGWIZARD_FUNCTION => "websiteurl_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "www_server.png",
        CONFIGWIZARD_FILTER_GROUPS => array('website'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @param string $mode
 * @param        $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function websiteurl_configwizard_func($mode = "", $inargs, &$outargs, &$result)
{
    $wizard_name = "websiteurl";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $url = grab_array_var($inargs, "url", "http://");
            $url = nagiosccm_replace_user_macros($url);

            $output = '
<h5 class="ul">' . _('URL Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('URL') . ':</label>
        </td>
        <td>
            <input type="text" size="60" name="url" id="url" value="' . htmlentities($url) . '" class="form-control">
            <div class="subtext">' . _('Enter the full URL you\'d like to monitor.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($url) == false)
                $errmsg[$errors++] = _("No URL specified.");
            else if (!valid_url($url))
                $errmsg[$errors++] = _("Invalid URL.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $urlparts = parse_url($url);

            $hostname = grab_array_var($urlparts, "host");
            $urlscheme = grab_array_var($urlparts, "scheme");
            $port = grab_array_var($urlparts, "port");
            $username = grab_array_var($urlparts, "user");
            $password = grab_array_var($urlparts, "pass");
            if ($urlscheme == "https")
                $ssl = "on";
            else
                $ssl = "off";
            if ($port == "") {
                if ($ssl == "on")
                    $port = 443;
                else
                    $port = 80;
            }
            $basicauth = "";
            if ($username != "")
                $basicauth = "on";

            $ip = gethostbyname($hostname);

            $hostname = grab_array_var($inargs, "hostname", $hostname);
            $servicename = grab_array_var($inargs, "servicename", websiteurl_configwizard_url_to_name($url));
            $port = grab_array_var($inargs, "port", $port);
            $ssl = grab_array_var($inargs, "ssl", $ssl);
            $basicauth = grab_array_var($inargs, "basicauth", $basicauth);
            $username = grab_array_var($inargs, "username", $username);
            $password = grab_array_var($inargs, "password", $password);

            $hostname = nagiosccm_replace_user_macros($hostname);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $services = grab_array_var($inargs, "services", array());
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));

            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));

            if (count($services) == 0) {
                $services["http"] = "on";
                $services["httpcontent"] = "";
                $services["httpregex"] = "";
            }
            if (count($serviceargs) == 0) {
                $serviceargs["httpservicename"] = "URL Status";
                $serviceargs["httpcontentservicename"] = "URL Content";
                $serviceargs["httpregexservicename"] = "URL Content Regex";

                $serviceargs["httpcontentstr"] = "";
                $serviceargs["httpregexstr"] = "";

            }

            $output = '
<input type="hidden" name="url" value="' . htmlentities($url) . '">

<h5 class="ul">' . _('URL Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('URL') . ':</label>
        </td>
        <td>
            <a href="' . htmlentities($url) . '" target="_blank">' . htmlentities($url, ENT_COMPAT, 'UTF-8') . '</a><br>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname, ENT_COMPAT, 'UTF-8') . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with the website server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Service Name Prefix') . ':</label>
        </td>
        <td>
            <input type="text" size="50" name="servicename" id="servicename" value="' . htmlentities($servicename, ENT_COMPAT, 'UTF-8') . '" class="form-control">
            <div class="subtext">' . _('The service name prefix that you\'d like to have used for specific URL services you select below. This prefix helps to identify this URL when monitoring different URLs on the same web server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="ip" id="ip" value="' . htmlentities($ip, ENT_COMPAT, 'UTF-8') . '" class="form-control">
            <div class="subtext">' . _('The IP address associated with the website fully qualified domain name (FQDN).') . '</dib>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('URL Options') . '</h5> 
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Use SSL') . ':</label>
        </td>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="ssl" name="ssl" ' . is_checked($ssl, "on") . '>
                ' . _('Monitor the URL using SSL/HTTPS.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="3" name="port" id="port" value="' . htmlentities($port, ENT_COMPAT, 'UTF-8') . '" class="form-control">
            <div class="subtext">' . _('The port to use when contacting the website.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Credentials') . ':</label>
        </td>
        <td>
            <input type="text" size="10" name="username" id="username" value="' . htmlentities($username, ENT_COMPAT, 'UTF-8') . '" class="form-control" placeholder="'._('Username').'">
            <input type="password" size="10" name="password" id="password" value="' . htmlentities($password, ENT_COMPAT, 'UTF-8') . '" class="form-control" placeholder="'._('Password').'">
            <div class="subtext">' . _('The username and password to use to authenticate to the URL (optional).  If specified, basic authentication is used.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('URL Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the URL.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="http" name="services[http]" ' . is_checked($services["http"], "on") . '>
        </td>
        <td>
            <label class="normal" for="http">
                <b>' . _('URL Status') . '</b><br>
                ' . _('Includes basic monitoring of the URL to ensure the web server responds with a valid HTTP response.') . '
            </label>
            <div class="pad-t5">
                <label>' . _('Service Name') . ':</label> <input type="text" size="20" name="serviceargs[httpservicename]" id="httpservicename" value="' . htmlentities($serviceargs["httpservicename"], ENT_COMPAT, 'UTF-8') . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="httpcontent" name="services[httpcontent]"' . is_checked($services["httpcontent"], "on") . '>
        </td>
        <td>
            <label class="normal" for="httpcontent">
                <b>' . _('URL Content') . '</b><br>
                ' . _('Monitors the URL to ensure the specified string is found in the content of the web page.  A content mismatch may indicate that your website has experienced a security breach or is not functioning correctly.') . '
            </label>
            <div class="pad-t5">
                <label>' . _('Service Name') . ':</label> <input type="text" size="20" name="serviceargs[httpcontentservicename]" id="httpcontentservicename" value="' . htmlentities($serviceargs["httpcontentservicename"], ENT_COMPAT, 'UTF-8') . '" class="form-control condensed">&nbsp;

                <label for="httpcontentstr">' . _('Content String To Expect') . ':</label> <input type="text" size="20" name="serviceargs[httpcontentstr]" id="httpcontentstr" value="' . htmlentities($serviceargs["httpcontentstr"], ENT_COMPAT, 'UTF-8') . '" class="form-control condensed" placeholder="'._('Some string').'...">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="httpregex" name="services[httpregex]" ' . is_checked($services["httpregex"], "on") . '>
        </td>
        <td>
            <label class="normal" for="httpregex">
                <b>' . _('URL Content Regular Expression Match') . '</b><br>
                ' . _('Monitors the URL to ensure the specified regular expression is found in the content of the web page.  A content mismatch may indicate that your website has experienced a security breach or is not functioning correctly.') . '
            </label>
            <div class="pad-t5">
                <label>' . _('Service Name') . ':</label> <input type="text" size="20" name="serviceargs[httpregexservicename]" id="httpregexservicename" value="' . htmlentities($serviceargs["httpregexservicename"], ENT_COMPAT, 'UTF-8') . '" class="form-control condensed">&nbsp;
                <label for="httpcontentstr">' . _('Regular Expression To Expect') . ':</label> <input type="text" size="20" name="serviceargs[httpregexstr]" id="httpregexstr" value="' . htmlentities($serviceargs["httpregexstr"], ENT_COMPAT, 'UTF-8') . '" class="form-control condensed">
            </div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $hostname = grab_array_var($inargs, "hostname");
            $servicename = grab_array_var($inargs, "servicename");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (is_valid_service_name($servicename) == false)
                $errmsg[$errors++] = _("Invalid service prefix.  Can only contain alphanumeric characters, spaces, and the following: <b>.\:_-</b>");
            if (have_value($url) == false)
                $errmsg[$errors++] = _("No URL specified.");
            else if (!valid_url($url))
                $errmsg[$errors++] = _("Invalid URL.");

            if (array_key_exists("httpcontent", $services)) {
                if (array_key_exists("httpcontentstr", $serviceargs)) {
                    if (have_value($serviceargs["httpcontentstr"]) == false)
                        $errmsg[$errors++] = _("You must specify a string to expect in the web page content.");
                }
                if ($serviceargs["httpcontentservicename"] == "")
                    $errmsg[$errors++] = _("You must specify a service name for the URL Content service.");
                else if (is_valid_service_name($serviceargs["httpcontentservicename"]) == false)
                    $errmsg[$errors++] = _("Invalid URL Content service name.");
            }

            if (array_key_exists("httpregex", $services)) {
                if (array_key_exists("httpregexstr", $serviceargs)) {
                    if (have_value($serviceargs["httpregexstr"]) == false)
                        $errmsg[$errors++] = _("You must specify a regular expression to expect in the web page content.");
                }
                if ($serviceargs["httpregexservicename"] == "")
                    $errmsg[$errors++] = _("You must specify a service name for the URL Content Regular Expression Match service.");
                else if (is_valid_service_name($serviceargs["httpregexservicename"]) == false)
                    $errmsg[$errors++] = _("Invalid URL Content Regular Expression Match service name.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $servicename = grab_array_var($inargs, "servicename");
            $hostname = grab_array_var($inargs, "hostname");
            $ip = grab_array_var($inargs, "ip");
            $ssl = grab_array_var($inargs, "ssl");
            $port = grab_array_var($inargs, "port");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
            
        <input type="hidden" name="url" value="' . htmlentities($url) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="servicename" value="' . htmlentities($servicename) . '">
        <input type="hidden" name="ip" value="' . htmlentities($ip) . '">
        <input type="hidden" name="ssl" value="' . htmlentities($ssl) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
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
            $servicename = grab_array_var($inargs, "servicename");
            $ip = grab_array_var($inargs, "ip", "");
            $url = grab_array_var($inargs, "url", "");
            $ssl = grab_array_var($inargs, "ssl");
            $port = grab_array_var($inargs, "port");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $urlparts = parse_url($url);
            $hostaddress = $urlparts["host"];

            /*
            echo "SERVICES:<BR>";
            print_r($services);
            echo "<BR>";

            echo "SERVICE ARGS:<BR>";
            print_r($serviceargs);
            echo "<BR>";

            print_r($inargs);
            */
            //exit();

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["servicename"] = $servicename;
            $meta_arr["ip"] = $ip;
            $meta_arr["url"] = $url;
            $meta_arr["ssl"] = $ssl;
            $meta_arr["port"] = $port;
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_website_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "www_server.png",
                    "statusmap_image" => "www_server.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $pluginopts = "";

            $vhost = $urlparts["host"];
            //    These items shouldn't be needed because this gets set from the $HOSTADDRESS$ -SW
            //  if($vhost=="")
            //      $vhost=$ip;
            //  $pluginopts.=" -H ".$vhost; // virtual host name

            $pluginopts .= " -f ok"; // on redirect, follow (OK status)
            $pluginopts .= " -I " . $ip; // ip address

            $urlpath = grab_array_var($urlparts, "path", "");

            // Need to add query (after ?) and fragment (after #) back on -JO

            if (!empty($urlparts["query"])) {
                $urlpath .= "?" . $urlparts["query"];
            }

            if (!empty($urlparts["fragment"])) {
                $urlpath .= "#" . $urlparts["fragment"];
            }

            if ($urlpath == "")
                $urlpath = "/";
            $pluginopts .= " -u '" . $urlpath . "'";

            if ($ssl == "on")
                $pluginopts .= " -S";
            if ($port != "")
                $pluginopts .= " -p " . $port;
            if ($username != "")
                $pluginopts .= " -a '" . $username . ":" . $password . "'";


            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "http":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $servicename . " " . $serviceargs["httpservicename"],
                            "use" => "xiwizard_website_http_service",
                            "check_command" => "check_xi_service_http!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "httpcontent":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $servicename . " " . $serviceargs["httpcontentservicename"],
                            "use" => "xiwizard_website_http_content_service",
                            //"check_command" => "check_xi_service_http_content!".$serviceargs["httpcontentstr"],
                            "check_command" => "check_xi_service_http!-s \"" . $serviceargs["httpcontentstr"] . "\" " . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "httpregex":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $servicename . " " . $serviceargs["httpregexservicename"],
                            "use" => "xiwizard_website_http_content_service",
                            "check_command" => "check_xi_service_http!-r \"" . $serviceargs["httpregexstr"] . "\" " . $pluginopts,
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

/**
 * @param $url
 *
 * @return mixed|string
 */
function websiteurl_configwizard_url_to_name($url)
{

    $urlparts = parse_url($url);
    $path = grab_array_var($urlparts, "path", "");

    $path = str_replace("/", "_", $path);
    $path = str_replace("\\", "_", $path);
    $path = str_replace("?", "_", $path);
    $path = str_replace(";", "_", $path);
    $path = str_replace("&", "_", $path);
    $path = str_replace(":", "_", $path);

    if ($path == "")
        $path = "_";

    return $path;
}
