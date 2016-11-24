<?php
// WEBSITE CONFIG WIZARD
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: website_defacement.inc.php 1174 2014-01-02 09:46:10 lgroschen $

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

// run the initialization function
website_defacementwizard_init();

function website_defacementwizard_init()
{

    $name = "website_defacement";

    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a website for defacement."),
        CONFIGWIZARD_DISPLAYTITLE => _("Website Defacement"),
        CONFIGWIZARD_FUNCTION => "website_defacementwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "website_defacement.png",
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

    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $url = grab_array_var($inargs, "url", "http://");

            $output = '

	<div class="sectionTitle">' . _("Monitor a website for defacement.") . '</div>
	
	<p><!--notes--></p>
			
	<table>
	<tr>
	<td valign="top">
	<label>' . _('Website Domain') . ':</label><br class="nobr" />
	</td>
	<td>
<input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="textfield" /><br class="nobr" />
	' . _('The full URL of the website you\'d like to monitor.') . '
	</td>
	</tr>

	</table>
			';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $url = grab_array_var($inargs, "url");

            // check for errors
            $errors = 0;
            $errmsg = array();
            //$errmsg[$errors++]="URL: $url";
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

            // get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $urlparts = parse_url($url);
            $hostname = grab_array_var($urlparts, "host");
            $ip = gethostbyname($hostname);
            $hostname = grab_array_var($inargs, "hostname", $hostname);
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr");
            $httpregexstr = grab_array_var($inargs, "httpregexstr");

            $services = grab_array_var($inargs, "services", array(
                "httpcontent" => "on",
                "httpregex" => "on"
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
		<input type="hidden" name="httpcontentstr" value="' . htmlentities($httpcontentstr) . '">
		<input type="hidden" name="httpregexstr" value="' . htmlentities($httpregexstr) . '">

	<div class="sectionTitle">' . _('Website Details') . '</div>
	
	<table>

	<tr>
	<td valign="top">
	<label>' . _('Website Domain') . ':</label><br class="nobr" />
	</td>
	<td>
<input type="text" size="60" name="url" id="url" value="' . htmlentities($url) . '" class="textfield" disabled/><br class="nobr" />
	</td>
	</tr>

	<tr>
	<td valign="top">
	<label>' . _('Host Name') . ':</label><br class="nobr" />
	</td>
	<td>
<input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield" /><br class="nobr" />
	' . _('The name you\'d like to have associated with this website.') . '
	</td>
	</tr>

	</table>

	<div class="sectionTitle">' . _('Defacement Monitoring Services') . '</div>
	
	<p>' . _('Specify which defacement services you\'d like to monitor your website with.') . '</p>
	
	<table>

	<tr>
	<td valign="top">
	<input type="checkbox" class="checkbox" id="httpcontent" name="services[httpcontent]" ' . is_checked(grab_array_var($services, "httpcontent"), "on") . '>
	</td>
	<td>
	<b>' . _('Defacement Content Locator') . '</b><br>
	' . _('Monitors the website to locate string values that are inserted in the field below. Click the Load Defaults to populate the field with commonly known strings. <br>You may also upload a text file to insert strings you want to keep track of into the text area:') . '<br><br>
	
	<script>
	//script to insert file contents into textarea
	function loadfile(input){
	   	var reader = new FileReader();
	    reader.onload = function(e){
	    	var existing = $("#httpcontentstr").val();
	        $("#httpcontentstr").val(existing + e.target.result);
	    }
	    reader.readAsText(input.files[0]);
	}
	</script>
	
	<button id="load-defaults">Load Defaults</button>
	<script>
		$(document).ready(function() {
			$("#load-defaults").click(function() {
				var defs_url = base_url + "/includes/configwizards/website_defacement/deface_defs.txt";
		        
		        $.get(defs_url, function(default_defs) {

		        		user_defs = $("#httpcontentstr").val();
		        		writeable = [];

		        		$.each(user_defs.split("\n"), function(i, user_def) {
		        			if(user_def)
		        				writeable.push($.trim(user_def));
		        		});

		        		$.each(default_defs.split("\n"), function(i, default_def) {
		        			var not_in_array = writeable.indexOf($.trim(default_def)) == -1;
		        			if(default_def && not_in_array) {
		        				console.log("Adding " + default_def);
		        				writeable.push(default_def);
		        			}
		        		});
						
			            var new_defs = writeable.join("\n");
			            $("#httpcontentstr").val(new_defs);
		        });

		        return false;
		    });
		});
	</script>

	<form action="FileReader">
	<input type="file" onchange="loadfile(this)"><br><br>
	</form>
	<label for="httpcontentstr">' . _('Insert a list of strings, each seperated with a new line (if using a single quote you must escape it)') . ':</label><br>
	<textarea rows="15" cols="70" name="serviceargs[httpcontentstr]" id="httpcontentstr" class="textfield">' . htmlentities($serviceargs["httpcontentstr"]) . '</textarea><br>
	</td>
	</tr>

	<tr>
	<td valign="top">
	<input type="checkbox" class="checkbox" id="httpregex" name="services[httpregex]" ' . is_checked(grab_array_var($services, "httpregex"), "on") . '>
	</td>
	<td>
	<b>' . _('Web Page Regular Expression Match') . '</b><br>
	' . _('Monitors the website to ensure the specified regular expression is found in the content of the web page.  A content mismatch may indicate that your website <br>has experienced a security breach or is not functioning correctly.') . '<br><br>
	<label for="httpregexstr">' . _('Regular Expression To Expect') . ': </label><input type="text" size="50" name="serviceargs[httpregexstr]" id="httpregexstr" value="' . htmlentities($serviceargs["httpregexstr"]) . '" class="textfield" /><br><br>
	</td>
	</tr>
	';

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
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr");
            $httpregexstr = grab_array_var($inargs, "httpregexstr");

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
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");
            $httpcontentstr = grab_array_var($inargs, "httpcontentstr");
            $httpregexstr = grab_array_var($inargs, "httpregexstr");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
			
				<input type="hidden" name="url" value="' . htmlentities($url) . '">
				<input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
				<input type="hidden" name="ip" value="' . htmlentities($ip) . '">
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
            $meta_arr["ip"] = $ip;
            $meta_arr["url"] = $url;
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
                    "icon_image" => "website_defacement.png",
                    "statusmap_image" => "website_defacement.png",
                    "_xiwizard" => $wizard_name,
                );
            }

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
                            "service_description" => "Website Defacement",
                            "use" => "xiwizard_check_deface_service",
                            "check_command" => "check_xi_deface!" . $serviceargs["httpcontentstr"] . "!--invert-regex",
                            "check_interval" => 60,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "httpregex":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Web Page Regex Match",
                            "use" => "xiwizard_check_deface_service",
                            "check_command" => "check_xi_deface!" . $serviceargs["httpregexstr"],
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

?>