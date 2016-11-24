<?php
//
// Website Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

website_configwizard_init();

function website_configwizard_init()
{
    $name = "website";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a website."),
        CONFIGWIZARD_DISPLAYTITLE => _("Website"),
        CONFIGWIZARD_FUNCTION => "website_configwizard_func",
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
function website_configwizard_func($mode = "", $inargs, &$outargs, &$result)
{
    $wizard_name = "website";

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
<h5 class="ul">' . _("Monitor a website.") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Website URL') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="form-control">
            <div class="subtext">' . _('The full URL of the website you\'d like to monitor.') . '</div>
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
            $port = grab_array_var($inargs, "port", $port);
            $ssl = grab_array_var($inargs, "ssl", $ssl);
            $basicauth = grab_array_var($inargs, "basicauth", $basicauth);
            $username = grab_array_var($inargs, "username", $username);
            $password = grab_array_var($inargs, "password", $password);
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr", "");
            $httpregexstr = grab_array_var($inargs, "httpregexstr", "");
            $sslcertdays = grab_array_var($inargs, "sslcertdays", 30);
            $onredirect = grab_array_var($inargs, "onredirect", "ok");

            $hostname = nagiosccm_replace_user_macros($hostname);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $output = '
<input type="hidden" name="url" value="' . htmlentities($url) . '">

<h5 class="ul">' . _('Website Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Website URL') . ':</label>
        </td>
        <td>
            <input type="text" size="60" name="url" id="url" value="' . htmlentities($url) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this website.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="ip" id="ip" value="' . htmlentities($ip) . '" class="form-control">
            <div class="subtext">' . _('The IP address associated with the website fully qualified domain name (FQDN).') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Website Options') . '</h5>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Use SSL') . ':</label>
        </td>
        <td class="checkbox">
            <label><input type="checkbox" id="ssl" name="ssl" ' . is_checked($ssl, "on") . '> ' . _('Monitor the website using SSL/HTTPS.') . '</label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port:') . '</label>
        </td>
        <td>
            <input type="text" size="3" name="port" id="port" value="' . htmlentities($port) . '" class="form-control">
            <div class="subtext">' . _('The port to use when contacting the website.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('On Redirect:') . '</label>
        </td>
        <td>
            <select name="onredirect" class="form-control">
                <option ' . is_selected($onredirect, 'ok') . '>ok</option>
                <option ' . is_selected($onredirect, 'warning') . '>warning</option>
                <option ' . is_selected($onredirect, 'critical') . '>critical</option>
                <option ' . is_selected($onredirect, 'follow') . '>follow</option>
                <option ' . is_selected($onredirect, 'sticky') . '>sticky</option>
                <option ' . is_selected($onredirect, 'stickyport') . '>stickyport</option>
            </select>
            <div class="subtext">' . _('How to handle redirected pages. sticky is like follow but will stick to the specified IP address. stickyport ensures the port stays the same.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Credentials') . ':</label>
        </td>
        <td>
            <input type="text" size="10" name="username" id="username" value="' . htmlentities($username) . '" class="form-control" placeholder="'._('Username').'">
            <input type="password" size="10" name="password" id="password" value="' . htmlentities($password) . '" class="form-control" placeholder="'._('Password').'">
            <div class="subtext"><strong>'._('Basic authentication only.').'</strong> ' . _('The username and password to use to authenticate to the website').' ('._('optional').')</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Website Services') . '</h5>
<p>' . _('Specify which services you\'d like to monitor for the website.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="http" name="services[http]" checked>
        </td>
        <td>
            <label class="normal" for="http">
                <b>' . _('HTTP') . '</b><br>
                ' . _('Includes basic monitoring of the website to ensure the web server responds with a valid HTTP response.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="ping" name="services[ping]" checked>
        </td>
        <td>
            <label class="normal" for="ping">
                <b>'._('Ping').'</b><br>
                ' . _('Monitors the website server with an ICMP ping.  Useful for watching network latency and general uptime of your web server.  Not all web servers support this.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="dns" name="services[dns]" checked>
        </td>
        <td>
            <label class="normal" for="dns">
                <b>' . _('DNS Resolution') . '</b><br>
                ' . _('Monitors the website DNS name to ensure it resolves to a valid IP address.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" class="checkbox" id="dnsip" name="services[dnsip]" checked>
        </td>
        <td>
            <label class="normal" for="dnsip">
                <b>' . _('DNS IP Match') . '</b><br>
                ' . _('Monitors the website DNS name to ensure it resolves to the current known IP address.  Helps ensure your DNS doesn\'t change unexpectedly, which may mean a security breach has occurred.') . '
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="httpcontent" name="services[httpcontent]">
        </td>
        <td>
            <label class="normal" for="httpcontent">
                <b>' . _('Web Page Content') . '</b><br>
                ' . _('Monitors the website to ensure the specified string is found in the content of the web page.  A content mismatch may indicate that your website has experienced a security breach or is not functioning correctly.') . '
            </label>
            <div class="pad-t5">
                <label for="httpcontentstr">' . _('Content String To Expect') . ':</label> <input type="text" size="20" name="serviceargs[httpcontentstr]" id="httpcontentstr" value="' . htmlentities($httpcontentstr) . '" class="form-control condensed" placeholder="'._('Some string').'...">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="httpcontent" name="services[httpregex]">
        </td>
        <td>
            <label class="normal" for="httpcontent">
                <b>' . _('Web Page Regular Expression Match') . '</b><br>
                ' . _('Monitors the website to ensure the specified regular expression is found in the content of the web page.  A content mismatch may indicate that your website has experienced a security breach or is not functioning correctly.') . '
            </label>
            <div class="pad-t5">
                <label for="httpcontentstr">' . _('Regular Expression To Expect') . ':</label> <input type="text" size="20" name="serviceargs[httpregexstr]" id="httpregexstr" value="' . htmlentities($httpregexstr) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    ';

            if ($ssl == "on") {
                $output .= '
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="sslcert" name="services[sslcert]" ' . is_checked($ssl, 1) . '>
        </td>
        <td>
            <label class="normal" for="sslcert">
                <b>' . _('SSL Certificate') . '</b><br>
                ' . _('Monitors the expiration date of the website\'s SSL certificate and alerts you if it expires within the specified number of days.  Helps ensure that SSL certificates don\'t inadvertently go un-renewed.') . '
            </label>
            <div class="pad-t5">
                <label for="sslcertdays">' . _('Days To Expiration') . ':</label> <input type="text" size="2" name="serviceargs[sslcertdays]" id="sslcertdays" value="' . htmlentities($sslcertdays) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
        ';
            }


            $output .= '
    </table>

            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $hostname = grab_array_var($inargs, "hostname");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (have_value($url) == false)
                $errmsg[$errors++] = _("No URL specified.");
            else if (!valid_url($url))
                $errmsg[$errors++] = _("Invalid URL.");
            if (array_key_exists("httpcontent", $services)) {
                if (array_key_exists("httpcontentstr", $serviceargs)) {
                    if (have_value($serviceargs["httpcontentstr"]) == false)
                        $errmsg[$errors++] = _("You must specify a string to expect in the web page content.");
                }
            }
            if (array_key_exists("httpregex", $services)) {
                if (array_key_exists("httpregexstr", $serviceargs)) {
                    if (have_value($serviceargs["httpregexstr"]) == false)
                        $errmsg[$errors++] = _("You must specify a regular expression to expect in the web page content.");
                }
            }
            if (array_key_exists("sslcert", $services)) {
                if (array_key_exists("sslcertdays", $serviceargs)) {
                    $n = intval($serviceargs["sslcertdays"]);
                    if ($n <= 0)
                        $errmsg[$errors++] = _("Invalid number of days for SSL certificate expiration.");
                } else
                    $errmsg[$errors++] = _("You must specify the number of days until SSL certificate expiration.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $hostname = grab_array_var($inargs, "hostname");
            $ip = grab_array_var($inargs, "ip");
            $ssl = grab_array_var($inargs, "ssl");
            $port = grab_array_var($inargs, "port");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $onredirect = grab_array_var($inargs, "onredirect");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
            
        <input type="hidden" name="url" value="' . htmlentities($url) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="ip" value="' . htmlentities($ip) . '">
        <input type="hidden" name="ssl" value="' . htmlentities($ssl) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="onredirect" value="' . $onredirect . '">
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
            $ip = grab_array_var($inargs, "ip", "");
            $url = grab_array_var($inargs, "url", "");
            $ssl = grab_array_var($inargs, "ssl");
            $port = grab_array_var($inargs, "port");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $onredirect = grab_array_var($inargs, "onredirect");

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
            $meta_arr["ip"] = $ip;
            $meta_arr["url"] = $url;
            $meta_arr["ssl"] = $ssl;
            $meta_arr["port"] = $port;
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["onredirect"] = $onredirect;
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

            $pluginopts .= " -f " . $onredirect; // on redirect, follow (OK status)
            $pluginopts .= " -I " . $ip; // ip address

            $urlpath = grab_array_var($urlparts, "path", "/");
            $pluginopts .= " -u \"" . $urlpath . "\"";

            if ($ssl == "on")
                $pluginopts .= " -S";
            if ($port != "")
                $pluginopts .= " -p " . $port;
            if ($username != "")
                $pluginopts .= " -a \"" . $username . ":" . $password . "\"";

            if ($ssl == "on")
                $objs[0]["check_command"] = "check_xi_service_http!" . $pluginopts;

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                //echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "http":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "HTTP",
                            "use" => "xiwizard_website_http_service",
                            "check_command" => "check_xi_service_http!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "httpcontent":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Web Page Content",
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
                            "service_description" => "Web Page Regex Match",
                            "use" => "xiwizard_website_http_content_service",
                            "check_command" => "check_xi_service_http!-r \"" . $serviceargs["httpregexstr"] . "\" " . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sslcert":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "SSL Certificate",
                            "use" => "xiwizard_website_http_cert_service",
                            "check_command" => " check_xi_service_http_cert!" . $serviceargs["sslcertdays"],
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "ping":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Ping",
                            "use" => "xiwizard_website_ping_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "dns":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "DNS Resolution",
                            "use" => "xiwizard_website_dns_service",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "dnsip":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "DNS IP Match",
                            "use" => "xiwizard_website_dnsip_service",
                            "check_command" => "check_xi_service_dns!-a " . $ip . "",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    default:
                        break;
                }
            }

            //echo "OBJECTS:<BR>";
            //print_r($objs);

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}