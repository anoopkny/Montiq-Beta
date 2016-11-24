<?php
//
// Website Defacement Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: website_defacement.inc.php 1174 2014-01-02 09:46:10 lgroschen $

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

website_defacementwizard_init();

function website_defacementwizard_init()
{
    $name = "website_defacement";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.4",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a website for defacement."),
        CONFIGWIZARD_DISPLAYTITLE => _("Website Defacement"),
        CONFIGWIZARD_FUNCTION => "website_defacementwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "website_defacement.png",
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
function website_defacementwizard_func($mode = "", $inargs, &$outargs, &$result)
{
    $wizard_name = "website_defacement";

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
<h5 class="ul">' . _("Monitor a website for defacement.") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('URL to Monitor') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="form-control">
            <div class="subtext">' . _('The URL of the website you\'d like to monitor.') . '</div>
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
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr");
            $httpregexstr = grab_array_var($inargs, "httpregexstr");
            $servicename = grab_array_var($inargs, "servicename", websitedeface_configwizard_url_to_name($url));
            $port = grab_array_var($inargs, "port", $port);
            $ssl = grab_array_var($inargs, "ssl", $ssl);
            $basicauth = grab_array_var($inargs, "basicauth", $basicauth);
            $username = grab_array_var($inargs, "username", $username);
            $password = grab_array_var($inargs, "password", $password);
            $onredirect = grab_array_var($inargs, "onredirect", "ok");

            $hostname = nagiosccm_replace_user_macros($hostname);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $services = grab_array_var($inargs, "services", array(
                "httpcontent" => "on",
                "httpregex" => "off",
                "regexinvert" => "off",
                "cinput" => "off"
            ));

            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "httpcontentstr" => "",
                "httpregexstr" => ""
            ));

            //Replace defaults with given values
            $services_serial = grab_array_var($inargs, "services_serial");
            if (!empty($services_serial)) {
                $services = unserialize(base64_decode($services_serial));
            }
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if (!empty($serviceargs_serial)) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            $output = '
<input type="hidden" name="url" value="' . htmlentities($url) . '">
<input type="hidden" name="httpcontentstr" value="' . encode_form_val($httpcontentstr) . '">
<input type="hidden" name="httpregexstr" value="' . encode_form_val($httpregexstr) . '">
<input type="hidden" name="cinput" value="' . htmlentities($services['cinput']) . '">
<input type="hidden" name="servicename" value="' . htmlentities($servicename) . '">

<h5 class="ul">' . _('URL Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('URL') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="30" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this website.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Service Name Prefix') . ':</label>
        </td>
        <td>
            <input type="text" size="30" name="servicename" id="servicename" value="' . htmlentities($servicename) . '" class="form-control">
            <div class="subtext">' . _('The service name prefix that you\'d like to have used for specific URL services you select below.  This prefix helps to identify this URL when monitoring different URLs on the same web server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="30" name="ip" id="ip" value="' . htmlentities($ip) . '" class="form-control">
            <div class="subtext">' . _('The IP address associated with the website fully qualified domain name (FQDN).') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('URL Options') . '</h5>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Use SSL') . ':</label>
        </td>
        <td class="checkbox">
            <label><input type="checkbox" id="ssl" name="ssl" ' . is_checked($ssl, "on") . '> ' . _('Monitor the URL using SSL/HTTPS.') . '</label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="3" name="port" id="port" value="' . htmlentities($port, ENT_COMPAT, 'UTF-8') . '" class="form-control">
            ' . _('The port to use when contacting the website.') . '<br><br>
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
            <input type="text" size="15" name="username" id="username" value="' . htmlentities($username, ENT_COMPAT, 'UTF-8') . '" class="form-control" placeholder="'._('Username').'">
            <input type="password" size="15" name="password" id="password" value="' . htmlentities($password, ENT_COMPAT, 'UTF-8') . '" class="form-control" placeholder="'._('Password').'">
            <div class="subtext">' . _('The username and password to use to authenticate to the URL (optional).  If specified, basic authentication is used.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Defacement Monitoring Services') . '</h5>
<p>' . _('Specify which defacement services you\'d like to monitor your website with.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="httpcontent" name="services[httpcontent]" ' . is_checked(grab_array_var($services, "httpcontent"), "on") . '>
        </td>
        <td>
            <label class="normal" for="httpcontent">
                <b>' . _('Defacement Content Locator') . '</b><br>
                ' . _('Monitors the website to locate string values that are inserted in the field below. Click the Load Defaults to populate the field with commonly known strings. <br>You may also upload a text file to insert strings you want to keep track of into the text area:') . '
            </label>
            <div class="pad-t5">
                <label for="httpcontentstr">' . _('Insert a list of strings, each seperated with a new line (if using a single quote you must escape it)') . ':</label><br>
                <textarea rows="15" cols="60" name="serviceargs[httpcontentstr]" id="httpcontentstr" class="textfield">' . encode_form_val($serviceargs["httpcontentstr"]) . '</textarea><br>

                <form action="FileReader">
                    <div class="input-group">
                        <span class="input-group-btn">
                            <span class="btn btn-sm btn-primary btn-file"> '._('Load File').'&hellip; <input type="file" onchange="loadfile(this)"></span>
                        </span>
                    </div>
                </form>
                <br>

                <script>
                //get JSON data and populate default definition checkboxes
                var url = base_url + "includes/configwizards/website_defacement/defaults.php";
                var wd_defaults;

                $.getJSON(url, function(data) {
                    wd_defaults = data;

                    $.each(wd_defaults, function(k, v) {
                        var def_checkbox = $("<span class=\'checkbox\' style=\'display: inline-block; margin: 0 10px 0 0;\'><label><input type=\'checkbox\' class=\'wd_d\' name=\'services[cinput]\' value=\'" + k + "\'></input>" + k + "</label></span>");

                        $("#defaults").prepend(def_checkbox);
                    });
                });

                //script to insert file contents into textarea
                function loadfile(input){
                    var reader = new FileReader();
                    reader.onload = function(e){
                        var existing = $("#httpcontentstr").val();
                        $("#httpcontentstr").val(existing + "\n" + e.target.result);
                    }
                    reader.readAsText(input.files[0]);
                }

                $(document).ready(function() {
                    $("#load-defaults").click(function() {
                        var defs = "";
                        user_defs = $("#httpcontentstr").val();
                        writeable = [];

                        $.each(user_defs.split("\n"), function(i, user_def) {
                            if(user_def)
                                writeable.push($.trim(user_def));
                        });

                        $(\'.wd_d\').each(function(d, i) {
                            if($(this).attr(\'checked\')) {
                                defs = wd_defaults[$(this).val()];

                                $.each(defs.split("\n"), function(i, default_def) {
                                    var not_in_array = writeable.indexOf($.trim(default_def)) == -1;
                                    if(default_def && not_in_array) {
                                        //console.log("Adding " + default_def);
                                        writeable.push(default_def);
                                    }
                                });
                            }
                        });

                        var new_defs = writeable.join("\n");
                        $("#httpcontentstr").val(new_defs);
                    });
                });
                </script>
                
                <div id="defaults" class="row" style="margin-left: 0px;">
                    <button type="button" class="btn btn-xs btn-default" id="load-defaults">'._('Load Defaults').'</button>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" id="httpregex" name="services[httpregex]" ' . is_checked(grab_array_var($services, "httpregex")) . '>
        </td>
        <td>
            <label class="normal" for="httpregex">
                <b>' . _('Web Page Regular Expression Match') . '</b><br>
                ' . _('Monitors the website to ensure the specified regular expression is found in the content of the web page.  A content mismatch may indicate that your website<br> has experienced a security breach or is not functioning correctly.  To include multiple expressions use the "|" after each expression with no spaces.') . '
            </div>
            <div class="pad-t5">
                ' . _('Regular Expression To Expect') . ': <input type="text" size="50" name="serviceargs[httpregexstr]" id="httpregexstr" value="' . encode_form_val($serviceargs["httpregexstr"]) . '" class="form-control condensed"> <label style="font-weight: normal; margin-left: 10px;"><input type="checkbox" id="regexinvert" name="services[regexinvert]" style="vertical-align: middle; margin: 0 5px 0 0;"' . is_checked(grab_array_var($services, "regexinvert")) . '>' . _('Invert Regex Search') . '</label>
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
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr");
            $httpregexstr = grab_array_var($inargs, "httpregexstr");

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
            }

            if (array_key_exists("httpregex", $services)) {
                if (array_key_exists("httpregexstr", $serviceargs)) {
                    if (have_value($serviceargs["httpregexstr"]) == false)
                        $errmsg[$errors++] = _("You must specify a regular expression to expect in the web page content.");
                }
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $servicename = grab_array_var($inargs, "servicename");
            $hostname = grab_array_var($inargs, "hostname");
            $ip = grab_array_var($inargs, "ip");
            $ssl = grab_array_var($inargs, "ssl");
            $port = grab_array_var($inargs, "port");
            $username = grab_array_var($inargs, "username");
            $password = grab_array_var($inargs, "password");
            $onredirect = grab_array_var($inargs, "onredirect");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr");
            $httpregexstr = grab_array_var($inargs, "httpregexstr");

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
            $servicename = grab_array_var($inargs, "servicename");
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

            $serviceargs['httpcontentstr'] = preg_replace("/\r?\n/", "|", $serviceargs['httpcontentstr']);

            $urlparts = parse_url($url);
            $hostaddress = $urlparts["host"];

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
            $meta_arr["onredirect"] = $onredirect;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            if (array_key_exists("regexinvert", $services)) {
                if (have_value($services["regexinvert"]) != 'checked') {
                    $regexinvert = "";
                } else {
                    $regexinvert = "!--invert-regex";
                }
            }

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_website_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "website_defacement.png",
                    "statusmap_image" => "website_defacement.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $onredirect = " -f " . $onredirect; // on redirect, follow (OK status)

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

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                // echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch ($svc) {

                    case "http":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "HTTP",
                            "use" => "xiwizard_check_deface_host",
                            "check_command" => "check-host-alive-http!-H \"" . $url . "\" ",
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "httpcontent":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $servicename . " Website Defacement",
                            "use" => "xiwizard_check_deface_service",
                            "check_command" => "check_xi_deface!" . $serviceargs["httpcontentstr"] . "!" . $urlpath . "!" . $onredirect . " --invert-regex",
                            "check_interval" => 60,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "httpregex":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $servicename . " Web Page Regex Match",
                            "use" => "xiwizard_check_deface_service",
                            "check_command" => "check_xi_deface!" . $serviceargs["httpregexstr"] . "!" . $urlpath . "!" . $onredirect . $regexinvert,
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

/**
 * @param $url
 *
 * @return mixed|string
 */
function websitedeface_configwizard_url_to_name($url)
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