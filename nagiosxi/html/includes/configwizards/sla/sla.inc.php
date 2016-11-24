<?php
//
// SLA Config Wizard
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__).'/../configwizardhelper.inc.php');

// Run the initialization function
sla_configwizard_init();

function sla_configwizard_init()
{
    $name = "sla";

    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.5",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor Service Level Agreements (SLA) to ensure they are met."),
        CONFIGWIZARD_DISPLAYTITLE => _("SLA"),
        CONFIGWIZARD_FUNCTION => "sla_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "sla.png",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
        );

    register_configwizard($name,$args);
}

/**
 * @return array
 */
function sla_configwizard_check_prereqs()
{
    $errors = array();

    if(!file_exists("/usr/local/nagios/libexec/check_xisla.php")){
        $errors[] = _('It looks like you are missing check_xisla.php on your Nagios XI server. To use this wizard you must install the check_xisla.php plugin on your server located in the this wizards plugin directory here: /usr/local/nagios/libexec/');
    }

    return $errors;
}

/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function sla_configwizard_func($mode="",$inargs=null,&$outargs,&$result)
{
    $wizard_name = "sla";
    
    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch($mode){
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            $address = grab_array_var($inargs, "address", "localhost");
            $username = grab_array_var($inargs, "username", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $security_level = grab_array_var($inargs, "security_level", "none");
            $remote = grab_array_var($inargs, "remote", 0);

            $address = nagiosccm_replace_user_macros($address);
            $username = nagiosccm_replace_user_macros($username);

            $errors = sla_configwizard_check_prereqs();

            // get list of users from the backend
            $args = array(
                "cmd" => "getusers",
            );

            $xmlusers = get_backend_xml_data($args);

            if($errors) {
                $output .= '<div class="message"><ul class="errorMessage">';
                foreach($errors as $error) {
                    $output .= "<li><p>$error</p></li>";
                }
                $output.='</ul></div>';
            } else {

                $services = grab_array_var($inargs,"services",array());
                $serviceargs = grab_array_var($inargs,"serviceargs",array());

                $output = '

    <input type="hidden" name="address" value="' . $address . '">
    <input type="hidden" name="username" value="' . $username . '">
    <input type="hidden" name="auth_file" value="' . $auth_file . '">
    <input type="hidden" name="security_level" value="' . $security_level . '">
    <input type="hidden" name="remote" value="' . $remote . '">

<h5 class="ul">' . _('Nagios XI ') . '</h5>
    <p>' . _('Fill in the credentials for the Nagios XI server that will be running the SLA query. By default the SLA wizard will run locally') . '.</p>

    <input id="remotecheck" class="btn btn-sm btn-primary" value="Remote Host" style="width: 100px;"><p style="font-size: 1rem; margin: 5px 0 10px 0;">' . _('Toggles local and remote wizard options') . '</p>

    <table class="table table-condensed table-no-border table-auto-width">
        <tbody id="localbody" style="display: block;">
            <tr>
                <td class="vt">
                    <label>' . _("Nagios XI Host") . ':&nbsp;&nbsp;</label>
                </td>
                <td>
                    <input type="text" size="25" name="address" id="address" value="localhost" class="form-control" disabled/>
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label>' . _('Nagios XI User') . ':</label>
                </td>
                <td class="vt">
                    <select name="username" style="width:312px" class="form-control">
                        <option value="">' . _('Select User') . '</option>
                        ';
                        if ($xmlusers) {

                            foreach ($xmlusers->user as $u) {
                                // print_r($u);

                                $uid = get_user_id($u->username);

                                $output .= "<option id='username' value='" . $u->username . "' " . is_selected($username, strval($u->username)) . ">" . $u->name . " (" . $u->username . ")</option>\n";
                            }
                        } else {  // if not-admin set as session user
                                $output .= "<option id='username' value='" . $_SESSION['username'] . "' " . is_selected($username, strval($u->username)) . ">" . $_SESSION['username'] . "</option>\n";
                        }

                        $output .= '
                    </select>
                    <div class="subtext">' . _("Select a local user to run this wizard with") . '.</div>
                </td>
            </tr>
        </tbody>
        <tbody id="remotebody" style="display: none; border: none;">
            <tr>
                <td class="vt">
                    <label>' . _("Nagios XI Host") . ':</label>
                </td>
                <td>
                    <input type="text" size="25" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" />
                    <div class="subtext">' . _("The FQDN of the remote host the wizard will query the SLA data from") . '.</div>
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label>' . _('Auth File:') . '</label>
                </td>
                <td>
                    <input type="text" size="50" name="auth_file" id="auth_file" value="' . htmlentities($auth_file) . '" class="form-control">

                    <button type="button" id="local_test_btn" class="btn btn-sm btn-info" style="vertical-align: top;"><i class="fa fa-unlock-alt l"></i>' . _("Test Permissions") . '</button>
                    <span style="display: none; margin-left: 3px;" id="local_test_loader">
                        <img height="15" width="15" style="vertical-align: text-top;" title="' . _("Testing Authorization File Permissions") . '" src="' . theme_image("throbber.gif") . '">
                    </span>
                    <span id="local_test" style="display: none; margin-left: 3px;"></span>

                    <div class="subtext">' . _('File to use with Nagios XI username and ticket for authencation, details below.') . '</div>
                    <table class="table table-condensed table-no-border table-auto-width" style="background-color: #E9E9E9; border: 1px solid #CCC; max-width: 382px;">
                        <tr>
                            <td>
                                <b>' . _("Authorization file details:") . '</b>
                            </td><br>
                        </tr>
                        <tr>
                           <td>
                                <div class="subtext">' . _("When running the wizard locally the file will be automatically generated at this location: ") . '</div>
                                <div class="subtext"><b>/usr/local/nagiosxi/var/components/xisla_auth.txt</b></div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="subtext">' . _("When running the wizard remotely you may create a file anywhere that has read/write access for the nagios user and group with this format:") . '</div>
                                <div class="subtext"><b>' . _("username=xxxxxxxx") . '</b><br><b>' . _("ticket=xxxxxxxx") . '</b></div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="vt">
                    <label>' . _("Server Security") . ':</label>
                </td>
                <td>
                    <select name="security_level" id="security_level" class="form-control">
                        <option value="none"';
                            if ($security_level == "none") { $output .= "selected"; }
                            $output .= '>' . _("None") . ' </option>
                        <option value="ssl"';
                            if ($security_level == "ssl") { $output .= "selected"; }
                            $output .= '>' . _("SSL") . ' </option>
                    </select>
                    <div class="subtext">' . _("The type of security (if any) to use for the connection to the server.") . '</div>
                </td>
            </tr>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            var remote_div = $("input[name=remote]");
            var name = "";
            name = $("#remotecheck");

            if (remote_div.val() == 1) {
                name.val("Remote Host");
                remote_div.val("1");
                $("#localbody").hide();
                $("#remotebody").show();
            } else {
                $("#auth_file").val("");
                name.val("Local Host");
                remote_div.val("0");
                $("#localbody").show();
                $("#remotebody").hide();
            }

            $("#remotecheck").click(function(index) {
                name = $("#remotecheck");
                if (name.val() !== "Remote Host") {
                    name.val("Remote Host");
                    remote_div.val("1");
                } else {
                    name.val("Local Host");
                    remote_div.val("0");
                    $("#auth_file").val("");
                }

                $("#localbody").toggle();
                $("#remotebody").toggle();
            });

            $("#local_test_btn").click( function(e) {
                e.preventDefault();
                var form_data = $("#auth_file").val();

                $(this).attr("disabled", true);
                $("#local_test_loader").show();
                $("#local_test").html("");
                
                $.post("../includes/configwizards/sla/ajax.php", {type: "local", local: form_data}, function (data) {

                    if (data.success) {
                        // Success
                        $("#local_test").html(data.success);
                    } else {
                        // Display error message
                        $("#local_test").html(data.error);
                    }

                    $("#local_test").show();
                    $("#local_test_btn").attr("disabled", false);
                    $("#local_test_loader").hide();

                }, "json");
            });
        });
    </script>
        ';
        }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $username = grab_array_var($inargs, "username", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $security_level = grab_array_var($inargs, "security_level");
            $remote = grab_array_var($inargs, "remote");

            // check for errors
            $errors = 0;
            $errmsg = array();

            if (!$remote) {
                if (!empty($username) == false)
                    $errmsg[$errors++] = _("No Nagios XI Username specified.  Select one from the dropdown.");
            }

            if ($remote) {
                if (!empty($auth_file) == false)
                    $errmsg[$errors++] = _("No Nagios XI SLA Authentication file has been specified.");

                // Verify then write auth file contents
                if ($auth_file !== "") {
                    $auth_test = file_get_contents($auth_file);

                    if ($auth_test === false) {
                        $errmsg[$errors++] = _("The Authentication file does not exist, could not be read or is not formatted correctly.");
                    }
                }
            }

            if ($errors>0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES]=$errmsg;
                $result=1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $ha = @gethostbyaddr($address);
            $username = grab_array_var($inargs, "username", "");

            $hostname = grab_array_var($inargs, "hostname", $ha ? $ha : $address);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $auth_file = grab_array_var($inargs, "auth_file", "");
            $security_level = grab_array_var($inargs, "security_level");
            $remote = grab_array_var($inargs, "remote", "");
            
            // create auth file
            if (!$remote) {
                $auth_file = '/usr/local/nagiosxi/var/components/xisla_auth.txt';

                $handler = @fopen($auth_file, 'rw');

                // If no file we should make one
                if ($handler === false) {
                    touch('/usr/local/nagiosxi/var/components/xisla_auth.txt');
                    $handler = fopen($auth_file, 'rw');
                }

                $auth_file_content = stream_get_contents($handler);

                if (empty($auth_file_content)) {
                    $contents = "username={$username}\r\nticket=";
                    fwrite($handler, $contents);
                }

                fclose($handler);

                $uri = "";
            } else {
                // Verify then write auth file contents
                $auth_creds = file_get_contents($auth_file);
                $auth_creds = explode("\n", $auth_creds);

                if ($remote && !empty($auth_creds)) {
                    $username = split("=", $auth_creds[0]);
                    $username = $username[1];
                }

                // need the ticket to make api requests
                if ($remote && !empty($auth_creds)) {
                    $ticket = split("=", $auth_creds[1]);
                    $ticket = $ticket[1];
                }

                // Check for SLL
                if ($security_level == "ssl") {
                    $uri = "https";
                } else {
                    $uri = "http";
                }

                $uri .= "://";
            }

            $warning = grab_array_var($inargs, "warning", "");
            $critical = grab_array_var($inargs, "critical", "");

            // Initialize container vars for form
            $host = "";
            $service = "";
            $hostgroup = "";
            $servicegroup = "";

            // If no serialized data, use current request data
            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");

            if (!empty($services_serial))
                $services = unserialize(base64_decode($services_serial));

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            if (!empty($serviceargs_serial)) 
                $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // Check if retention data available
            if (!is_array($services) && !is_array($serviceargs)) {
                // Initialize services array variables
                $services_default = grab_array_var($inargs, "services", array(
                    "host" => array(),
                    "service" => array(),
                    "hostgroup" => array(),
                    "servicegroup" => array()
                ));

                $services = grab_array_var($inargs, "services", $services_default);
                $services_serial = base64_encode(serialize($services));

                // Initialize serviceargs array variables
                $serviceargs_default = array(
                    "host" => array(),
                    "service" => array(),
                    "hostgroup" => array(),
                    "servicegroup" => array(),
                    "advanced" => array(
                        "mode" => 0,
                        "assumeinitials" => "yes",
                        "assumestater" => "yes",
                        "assumedown" => "yes",
                        "softstate" => "no",
                        "asssumehs" => 3,
                        "asssumess" => 6,
                        "manual_run" => 1,
                        "reportperiod" => "last24hours",
                        "dont_count_downtime" => "",
                        "dont_count_warning" => "",
                        "dont_count_unknown" => "",
                    )
                );

                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);

                foreach ($services as $svc => $val) {
                    for ($x = 0; $x < 2; $x++) {
                        if (!array_key_exists($x, $services))
                            $services[$svc][$x] = 'off';

                        if (!array_key_exists($x, $services)) {
                            $serviceargs[$svc][$x] = array(
                                $svc => $svc,
                                "username" => $username,
                                "warning" => "",
                                "critical" => ""
                            );
                        }
                    }
                }

                $serviceargs_serial = base64_encode(serialize($serviceargs));
            } else {
                foreach ($services as $svc => $val) {
                    $limiter = count($services[$svc]);
                    for ($x = 0; $x < $limiter; $x++) {
                        if (!array_key_exists($x, $services)) {
                            $services[$svc][$x] = $val;
                        }

                        if (!array_key_exists($x, $serviceargs)) {
                            foreach ($serviceargs[$svc][$x] as $key => $value) {
                                $serviceargs[$svc][$x][$key] = $value;
                            }
                        }
                    }
                }
            }

            $output = '

        <input type="hidden" name="address" value="' . htmlentities($address) . '" />
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
        <input type="hidden" name="username" value="' . $username . '" />
        <input type="hidden" name="auth_file" value="' . $auth_file . '" />
        <input type="hidden" name="warning" value="' . $warning . '" />
        <input type="hidden" name="critical" value="' . $critical . '" />
        <input type="hidden" name="remote" value="' . $remote . '">
        <input type="hidden" name="security_level" value="' . $security_level . '">
        <input type="hidden" name="uri" value="' . $uri . '">

    <h5 class="ul">' . _('Nagios XI Settings') . '</h5>
    <p>'._('These are the settings used to connect to the Nagios XI server where the SLA report will be run').'.</p>

    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td class="vt">
                <label>' . _("Nagios XI Host") . ':</label>
            </td>
            <td>
                <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
                <div class="subtext">' . _("The FQDN of the Nagios XI host the wizard will query the SLA data from") . '.</div>
            </td>
        </tr>
        <tr>
            <td class="vt">
                <label>' . _('Nagios XI User') . ':</label>
            </td>
            <td>
                <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control" />
                <div class="subtext">' . _("The User account associated with the Nagios XI server") . '.</div>
            </td>
        </tr>
        <tr>';

        // if remote show auth file
        if ($remote == 1) {
            $output .= '
                <td class="vt">
                    <label>' . _('Auth File') . ':</label>
                </td>
                <td>
                    <input type="text" size="50" name="auth_file" id="auth_file" value="' . $auth_file . '" class="form-control">
                    <div class="subtext">' . _("File to use with Nagios XI username and ticket for authencation.") . '</div>
                </td>';
        }

        $output .= '
        </tr>
    </table>

    <div style="height: 10px;"></div>

    <h5 class="ul">' . _('SLA Report Advanced Settings') . '</h5>
    <p>' . _('These are settings used to manipulate the way SLA data is reported and received. These settings will apply to all services in the wizard') . '.</p>

    <table class="table table-condensed table-no-border table-auto-width">
        <tr>
            <td>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("Assume Initial States") . '
                    <select name="serviceargs[advanced][assumeinitials]" class="form-control">
                        <option value="yes" ';
                            if ($serviceargs['advanced'] && $serviceargs['advanced']['assumeinitials'] == "yes") { $output .= "selected"; }
                            $output .= '>' . _("Yes") . '</option>
                        <option value="no"'; 
                            if ($serviceargs['advanced']['assumeinitials'] == "no") {  $output .= "selected"; } 
                            $output .= '>' .  _("No") . '</option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("Assume State Retention") . '
                    <select name="serviceargs[advanced][assumestater]" class="form-control">
                        <option value="yes"';
                            if ($serviceargs['advanced']['assumestater'] == "yes") { $output .= "selected"; }
                            $output .= '>' . _("Yes") . ' </option>
                        <option value="no"';
                            if ($serviceargs['advanced']['assumestater'] == "no") { $output .= "selected"; }
                            $output .= '>' . _("No") . ' </option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("Assume States During Program Downtime") . '
                    <select name="serviceargs[advanced][assumedown]" class="form-control">
                        <option value="yes"';
                            if ($serviceargs['advanced']['assumedown'] == "yes") { $output .= "selected"; }
                            $output .= '>' . _("Yes") . ' </option>
                        <option value="no"';
                            if ($serviceargs['advanced']['assumedown'] == "no") { $output .= "selected"; }
                            $output .= '>' . _("No") . ' </option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("Include Soft States") . '
                    <select name="serviceargs[advanced][softstate]" class="form-control">
                        <option value="no"';
                            if ($serviceargs['advanced']['softstate'] == "no") { $output .= "selected"; }
                            $output .= '>' . _("No") . ' </option>
                        <option value="yes"';
                            if ($serviceargs['advanced']['softstate'] == "yes") { $output .= "selected"; }
                            $output .= '>' . _("Yes") . ' </option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("First Assumed Host State") . '
                    <select name="serviceargs[advanced][asssumehs]" class="form-control">
                        <option value="0"';
                            if ($serviceargs['advanced']['asssumehs'] == 0) { $output .= "selected"; }
                            $output .= '>' . _("Unspecified") . ' </option>
                        <option value="-1"';
                            if ($serviceargs['advanced']['asssumehs'] == -1) { $output .= "selected"; }
                            $output .= '>' . _("Current State") . ' </option>
                        <option value="3"';
                            if ($serviceargs['advanced']['asssumehs'] == 3) { $output .= "selected"; }
                            $output .= '>' . _("Host Up") . ' </option>
                        <option value="4"';
                            if ($serviceargs['advanced']['asssumehs'] == 4) { $output .= "selected"; }
                            $output .= '>' . _("Host Down") . ' </option>
                        <option value="5"';
                            if ($serviceargs['advanced']['asssumehs'] == 5) { $output .= "selected"; }
                            $output .= '>' . _("Host Unreachable") . ' </option>
                    </select>
                </div>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("First Assumed Service State") . '
                    <select name="serviceargs[advanced][asssumess]" class="form-control">
                        <option value="0"';
                            if ($serviceargs['advanced']['asssumess'] == 0) { $output .= "selected"; }
                            $output .= '>' . _("Unspecified") . ' </option>
                        <option value="-1"';
                            if ($serviceargs['advanced']['asssumess'] == -1) { $output .= "selected"; }
                            $output .= '>' . _("Current State") . ' </option>
                        <option value="6"';
                            if ($serviceargs['advanced']['asssumess'] == 6) { $output .= "selected"; }
                            $output .= '>' . _("Service Ok") . ' </option>
                        <option value="7"';
                            if ($serviceargs['advanced']['asssumess'] == 7) { $output .= "selected"; }
                            $output .= '>' . _("Service Warning") . ' </option>
                        <option value="8"';
                            if ($serviceargs['advanced']['asssumess'] == 8) { $output .= "selected"; }
                            $output .= '>' . _("Service Unknown") . ' </option>
                        <option value="9"';
                            if ($serviceargs['advanced']['asssumess'] == 9) { $output .= "selected"; }
                            $output .= '>' . _("Service Critical") . ' </option>
                    </select>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div style="float: left; margin-right: 10px; padding-bottom: 10px;">
                    ' . _("Report Time Period") . '';
                        if ($remote == 1) {
                            // This section has not been aded to the db_field section of the API for now just display an input to change timeperiods manually

                            // $remoteobjects = file_get_contents($uri . $address . "/nagiosxi/api/v1/system/db_field?field=nagios_timeperiods&apikey=" . $ticket);
                            // foreach ($remoteobjects as $object) {
                            //     $tp = (string)$object->username;
                            //     if (!empty($tp)) {
                            //         $output .= "<option " . is_selected($serviceargs['advanced']['reportperiod'], $tp) . ">" . $tp . "</option>";
                            //     }
                            // }
                            $output .= '
                                <input name="serviceargs[advanced][reportperiod]" value="' . $serviceargs['advanced']['reportperiod'] . '" class="form-control">
                            ';
                        } else {
                        $output .= '
                        <select name="serviceargs[advanced][reportperiod]" class="form-control" style="width: 150px;">
                            <option value="" ';
                            if ($serviceargs['advanced']['reportperiod'] == "") { $output .= "selected"; }
                            $output .= '>' . _("None") . ' </option>';
                            // Get a list of reportperiods
                            $request = array("objecttype_id" => 9);
                            $objects = new SimpleXMLElement(get_objects_xml_output($request, false));
                            foreach ($objects as $object) {
                                $tp = (string)$object->name1;
                                if (!empty($tp)) {
                                    $output .= "<option " . is_selected($serviceargs['advanced']['reportperiod'], $tp) . ">" . $tp . "</option>";
                                }
                            }
                        }
                        $output .= '
                    </select>
                </div>
                <div style="float: left; margin: 2px 4px 2px 10px;">
                    <label title="' . _('This will count any state during scheduled downtime as OK for the SLA report') . '">
                        <input type="checkbox" name="serviceargs[advanced][dont_count_downtime]" style="margin: 4px;" ';
                            if ($serviceargs['advanced']['dont_count_downtime'] == "on") { $output .= "checked";  }
                            $output .= '>' . _("Hide scheduled downtime") . '
                    </label>
                </div>
                <div style="float: left; margin: 2px 4px 2px 10px;">
                    <label title="' . _('This will count any WARNING state as OK for the SLA report') . '">
                        <input type="checkbox" name="serviceargs[advanced][dont_count_warning]" style="margin: 4px;" ';
                        if ($serviceargs['advanced']['dont_count_warning'] == "on") { $output .= "checked";  }
                        $output .= '>' . _("Hide Warning states") . '
                    </label>
                </div>
                <div style="float: left; margin: 2px 4px 2px 10px;">
                    <label title="' . _('This will count any UNKNOWN state as OK for the SLA report') . '">
                        <input type="checkbox" name="serviceargs[advanced][dont_count_unknown]" style="margin: 4px;" ';
                        if ($serviceargs['advanced']['dont_count_unknown'] == "on") { $output .= "checked";  }
                        $output .= '>' . _("Hide Unknown/Unreachable states") . '
                    </label>
                </div>
            </td>
        </tr>
    </table>

<script>
    $(document).ready(function() {
        $("#tabs").tabs();

        var remote = "' . $remote . '";
        var servicetarget = "";
        var current = "";
        var retain = true;

        $(".adddeleterow").on("change", ".hostSList", function () {
            var selected = "";
            $(this).data("oldhost", $(this).data("newhost") || "");
            $(this).data("newhost", $(this).val());

            if ($(this).val() != "") {
                servicetarget = $(this).parents("tr").find(".serviceList");
                selected = servicetarget.val();

                var reload = reload_service_list($(this).data("oldhost"), $(this).data("newhost"));
                update_service_list($(this).val(), servicetarget, selected, reload);
            } else {
                $(this).parents("tr").find(".serviceList").html("<option value=\'\'>" + "Service:" +  "</option>");
            }
        });

        function update_service_list(host, servicetarget, selected, reload) {
            // Check if we have a selection and retain
            if (selected && reload) {
                selected = selected.replace(/\s/g, "+");

                if (remote !== 1) {
                    $.get("/nagiosxi/reports/sla.php?mode=getservices&host=" + host + "&service=" + selected, function(data) {
                        servicetarget.html(data);
                    });
                }
            } else {
                if (remote !== 1) {
                    $.get("/nagiosxi/reports/sla.php?mode=getservices&host=" + host, function(data) {
                        servicetarget.html(data);
                    });
                }
            }
        }

        // Do we need to reload services
        function reload_service_list(oldhost, newhost) {
            if (oldhost == "") {
                return true;
            } else {
                return false;
            }
        }

        // Data retention trigger for loaded service list
        if ($(".hostSList").val()) {
            retain = false;
            $(".adddeleterow .hostSList").trigger("change");
        }
    });
</script>

    <input type="hidden" name="address" id="address" value="' . $address . '">

<h5 class="ul">' . _('Check SLA Setup') . '</h5>
    <div class="message" style="max-width:700px;"><ul class="actionMessage">' . _("Warning and Critical for the SLA wizard support targeted and ranged thresholds following the Nagios Plugins guidelines ") . '<a target="_blank" href="https://nagios-plugins.org/doc/guidelines.html#THRESHOLDFORMAT">here</a>.<br><br>' . _("Normally when using warning and critical values to alert warning must be lower than critical, but for SLA the higher the percentage the better.  Because of this the wizard will automatically add a '@' in front of the warning and critical values to reverse them.") . '</ul>
    </div>

<div id="tabs" style="max-width:700px;">
    <ul>
        <li><a href="#host" id="selecthost">Host</a></li>
        <li><a href="#service" id="selectservice">Service</a></li>
        <li><a href="#hostgroup" id="selecthostgroup">Hostgroup</a></li>
        <li><a href="#servicegroup" id="selectservicegroup">Servicegroup</a></li>
    </ul>
    <div id="host">
            <table class="adddeleterow table table-condensed table-no-border table-auto-width">
                <tr>
                    <th></th>' .
                    '<th>' . _('Target Host') . '</th>' .
                    '<th>' . _('Warning') . '</th>' .
                    '<th>' . _('Critical') . '</th>' .
                '</tr>';

                for ($x = 0; $x < count($serviceargs['host']); $x++) {
                    // Initialize
                    ${"host_" . "{$x}"} = "";
                    ${"warning_" . "{$x}"} = "";
                    ${"critical_" . "{$x}"} = "";

                    if (isset($services['host']) && $services['host'][0][0] == "on") {
                        foreach ($serviceargs['host'][$x] as $key => $val) {
                            $namer = $key . "_" . $x;
                            ${$namer} = $val;
                        }
                    }

                    // Set values
                    $host = encode_form_val(${"host_" . "{$x}"});
                    $warning = encode_form_val(${"warning_" . "{$x}"});
                    $critical = encode_form_val(${"critical_" . "{$x}"});

                    $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" class="checkbox" name="services[host][' . $x . ']" ' . (isset($services['host'][$x]) ? is_checked($services['host'][$x], 1) : '') . '>
                        </td>
                        <td>';
                            if ($remote == 1) {
                                $output .= "<input type='text' size='25' name='serviceargs[host][" . $x . "][host]' value='" . $host . "' class='form-control' />";
                            } else {
                                $args = array('brevity' => 1, 'orderby' => 'host_name:a');
                                $oxml = get_xml_host_objects($args);

                                $output .= '<select name="serviceargs[host][' . $x . '][host]" class="hostList form-control" style="width: 150px;">
                                    <option value="">' . _("Host") . ': </option>';

                                if ($oxml) {
                                    foreach ($oxml->host as $hostobject) {
                                        if (isset($hostobject->host_name)) {
                                            $name = strval($hostobject->host_name);
                                        }

                                        $output .= "<option value='" . $name . "' " . is_selected($host, $name) . ">$name</option>\n";
                                    }
                                }

                                $output .= '</select>';
                            }
                    $output .= '
                        </td>
                        <td>
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[host][' . $x . '][warning]" value="' . $warning . '" class="form-control" />
                        </td>
                        <td>
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[host][' . $x . '][critical]" value="' . $critical . '" class="form-control" />
                        </td>
                    </tr>';
                }
        $output .= '
        </table>
    </div> <!-- Closes #host -->

    <div id="service">
        ';
        $output .= '
            <table class="adddeleterow table table-condensed table-no-border table-auto-width">
                <tr>
                    <th></th>' .
                    '<th>' . _('Target Host') . '</th>' .
                    '<th>' . _('Target Service') . '</th>' .
                    '<th>' . _('Warning') . '</th>' .
                    '<th>' . _('Critical') . '</th>' .
                '</tr>';
            for ($x = 0; $x < count($serviceargs['service']); $x++) {
                // Initialize
                ${"service_" . "{$x}"} = "";
                ${"host_" . "{$x}"} = "";
                ${"warning_" . "{$x}"} = "";
                ${"critical_" . "{$x}"} = "";

                if (isset($services['service']) && $services['service'][0][0] == "on") {
                    foreach ($serviceargs['service'][$x] as $key => $val) {
                        $namer = $key . "_" . $x;
                        ${$namer} = $val;
                    }
                }

                // Set values
                $service = encode_form_val(${"service_" . "{$x}"});
                $host = encode_form_val(${"host_" . "{$x}"});
                $warning = encode_form_val(${"warning_" . "{$x}"});
                $critical = encode_form_val(${"critical_" . "{$x}"});

                $output .= '
                <tr>
                    <td>
                        <input type="checkbox" class="checkbox" name="services[service][' . $x . ']" ' . (isset($services['service'][$x]) ? is_checked($services['service'][$x], 1) : '') . '>
                    </td>
                    <td>';
                        if ($remote == 1) {
                            $output .= "<input type='text' size='25' name='serviceargs[service][" . $x . "][host]' value='" . $host . "' class='form-control' />";
                        } else {
                            $args = array('brevity' => 1, 'orderby' => 'host_name:a');
                            $oxml = get_xml_host_objects($args);

                            $output .= '<select name="serviceargs[service][' . $x . '][host]" class="hostSList form-control" style="width: 150px;">
                            <option value="">' . _("Host") . ': </option>';

                            if ($oxml) {
                                foreach ($oxml->host as $hostobject) {
                                    if (isset($hostobject->host_name)) {
                                        $hname = strval($hostobject->host_name);
                                    }

                                    $output .= "<option value='" . $hname . "' " . is_selected($host, $hname) . ">$hname</option>\n";
                                }
                            }

                            $output .= '</select>';
                        }
                    $output .= '
                    </td>
                    <td>';
                        if ($remote == 1) {
                            $output .= "<input type='text' size='25' name='serviceargs[service][" . $x . "][service]' value='" . $service . "' class='form-control' />";
                        } else {
                            $args = array('brevity' => 1, 'host_name' => $host, 'orderby' => 'service_description:a');
                            $oxml = get_xml_service_objects($args);

                            $output .= '<select name="serviceargs[service][' . $x . '][service]" class="serviceList form-control" style="width: 250px;">
                            <option value="">' . _("Service") . ': </option>';

                            if ($oxml) {
                                foreach ($oxml->service as $serviceobject) {
                                    if (isset($serviceobject->service_description)) {
                                        $sname = strval($serviceobject->service_description);
                                    }

                                    $sname = strval(isset($serviceobject->service_description));
                                    $output .= "<option value='" . $sname . "' " . is_selected($service, $sname) . ">$sname</option>\n";
                                }
                            }

                            $output .= '</select>';
                        }
                    $output .= '
                    </td>
                    <td>
                        <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[service][' . $x . '][warning]" value="' . $warning . '" class="form-control" />
                    </td>
                    <td>
                        <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[service][' . $x . '][critical]" value="' . $critical . '" class="form-control" />
                    </td>
                </tr>';
            }
        $output .= '
            </table>
    </div> <!-- Closes #service -->

    <div id="hostgroup">
        ';
        $output .= '
            <table class="adddeleterow table table-condensed table-no-border table-auto-width">
                <tr>
                    <th></th>' .
                    '<th>' . _('Target Hostgroup') . '</th>' .
                    '<th>' . _('Warning') . '</th>' .
                    '<th>' . _('Critical') . '</th>' .
                '</tr>';

                for ($x = 0; $x < count($serviceargs['hostgroup']); $x++) {
                    // Initialize
                    ${"hostgroup_" . "{$x}"} = "";
                    ${"warning_" . "{$x}"} = "";
                    ${"critical_" . "{$x}"} = "";

                    if (isset($services['hostgroup']) && $services['hostgroup'][0][0] == "on") {
                        foreach ($serviceargs['hostgroup'][$x] as $key => $val) {
                            $namer = $key . "_" . $x;
                            ${$namer} = $val;
                        }
                    }

                    // Set values
                    $hostgroup = encode_form_val(${"hostgroup_" . "{$x}"});
                    $warning = encode_form_val(${"warning_" . "{$x}"});
                    $critical = encode_form_val(${"critical_" . "{$x}"});

                    $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" class="checkbox" name="services[hostgroup][' . $x . ']" ' . (isset($services['hostgroup'][$x]) ? is_checked($services['hostgroup'][$x], 1) : '') . '>
                        </td>
                        <td>';
                            if ($remote == 1) {
                                $output .= "<input type='text' size='25' name='serviceargs[hostgroup][" . $x . "][hostgroup]' value='" . $hostgroup . "' class='form-control' />";
                            } else {
                                $args = array('orderby' => 'hostgroup_name:a');
                                $oxml = get_xml_hostgroup_objects($args);

                                $output .= '<select name="serviceargs[hostgroup][' . $x . '][hostgroup]" class="hostgroupList form-control" style="width: 150px;">
                                <option value="">' . _("Hostgroup") . ': </option>';

                                if ($oxml) {
                                    foreach ($oxml->hostgroup as $hg) {
                                        if (isset($hg->hostgroup_name)) {
                                            $name = strval($hg->hostgroup_name);
                                        }

                                        $output .= "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
                                    }
                                }

                                $output .= '</select>';
                            }
                    $output .= '
                        </td>
                        <td>
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[hostgroup][' . $x . '][warning]" value="' . $warning . '" class="form-control" />
                        </td>
                        <td>
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[hostgroup][' . $x . '][critical]" value="' . $critical . '" class="form-control" />
                        </td>
                    </tr>';
                }
                $output .= '
            </table>
    </div> <!-- Closes #hostgroup -->

    <div id="servicegroup">
        ';
        $output .= '
            <table class="adddeleterow table table-condensed table-no-border table-auto-width">
                <tr>
                    <th></th>' .
                    '<th>' . _('Target Servicegroup') . '</th>' .
                    '<th>' . _('Warning') . '</th>' .
                    '<th>' . _('Critical') . '</th>' .
                '</tr>';

                for ($x = 0; $x < count($serviceargs['servicegroup']); $x++) {
                    // Initialize
                    ${"servicegroup_" . "{$x}"} = "";
                    ${"warning_" . "{$x}"} = "";
                    ${"critical_" . "{$x}"} = "";

                    if (isset($services['servicegroup']) && $services['servicegroup'][0][0] == "on") {
                        foreach ($serviceargs['servicegroup'][$x] as $key => $val) {
                            $namer = $key . "_" . $x;
                            ${$namer} = $val;
                        }
                    }

                    // Set values
                    $servicegroup = encode_form_val(${"servicegroup_" . "{$x}"});
                    $warning = encode_form_val(${"warning_" . "{$x}"});
                    $critical = encode_form_val(${"critical_" . "{$x}"});

                    $output .= '
                    <tr>
                        <td>
                            <input type="checkbox" class="checkbox" name="services[servicegroup][' . $x . ']" ' . (isset($services['servicegroup'][$x]) ? is_checked($services['servicegroup'][$x], 1) : '') . '>
                        </td>
                        <td>';
                                if ($remote == 1) {
                                    $output .= "<input type='text' size='25' name='serviceargs[servicegroup][" . $x . "][servicegroup]' value='" . $servicegroup . "' class='form-control' />";
                                } else {
                                    $args = array('orderby' => 'servicegroup_name:a');
                                    $oxml = get_xml_servicegroup_objects($args);

                                    $output .= '<select name="serviceargs[servicegroup][' . $x . '][servicegroup]" class="servicegroupList form-control" style="width: 150px;">
                                    <option value="">' . _("Servicegroup") . ': </option>';

                                    if ($oxml) {
                                        foreach ($oxml->servicegroup as $sg) {
                                            if (isset($sg->servicegroup_name)) {
                                                $name = strval($sg->servicegroup_name);
                                            }

                                            $output .= "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
                                        }
                                    }

                                    $output .= '</select>';
                                }
                    $output .= '
                        </td>
                        <td>
                            <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[servicegroup][' . $x . '][warning]" value="' . $warning . '" class="form-control" />
                        </td>
                        <td>
                            <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label>&nbsp;@&nbsp;<input type="text" size="6" name="serviceargs[servicegroup][' . $x . '][critical]" value="' . $critical . '" class="form-control" />
                        </td>
                    </tr>';
                }

                $output .= '
            </table>
    </div> <!-- Closes #servicegroup -->
</div> <!-- Closes #tabs -->

            ';

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $username = grab_array_var($inargs, "username", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");
            $remote = grab_array_var($inargs, "remote", "");
            $security_level = grab_array_var($inargs, "security_level", "");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs", "");

            // Use encoded data if user came back from future screen.
            $services_serial = grab_array_var($inargs, 'services_serial');
            if ($services_serial) {
                $services = unserialize(base64_decode($services_serial));
            }
            $serviceargs_serial = grab_array_var($inargs, 'serviceargs_serial');
            if ($serviceargs_serial) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            // If no serialized data, use current request data if available.
            if (!$services)
                $services = grab_array_var($inargs, 'services');
            if (!$serviceargs)
                $serviceargs = grab_array_var($inargs, 'serviceargs');

            // check for errors
            $errors = 0;
            $errmsg = array();
                
            if ($errors>0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
                
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            $output .= _('The selected SLA Report will be monitored based on the check interval.  Click Finish to continue.');
            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $username = grab_array_var($inargs, "username", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");
            $remote = grab_array_var($inargs, "remote", "");
            $security_level = grab_array_var($inargs, "security_level", "");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");
            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
                <input type="hidden" name="address" value="' . htmlentities($address) . '" />
                <input type="hidden" name="hostname" value="' . $hostname . '" />
                <input type="hidden" name="username" value="' . $username . '" />
                <input type="hidden" name="auth_file" value="' . $auth_file . '" />
                <input type="hidden" name="warning" value="' . $warning . '" />
                <input type="hidden" name="critical" value="' . $critical . '" />
                <input type="hidden" name="remote" value="' . $remote . '" />
                <input type="hidden" name="security_level" value="' . $security_level . '" />
                <input type="hidden" name="services_serial" value="' . $services_serial . '">
                <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">
        
                <!--SERVICES='.serialize($services).'<BR>
                SERVICEARGS='.serialize($serviceargs).'<BR>-->        
            ';
            break;
            
        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:
             
            break;
            
        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            break;
            
        case CONFIGWIZARD_MODE_GETOBJECTS:
            $address = grab_array_var($inargs, "address", "");
            $hostname = grab_array_var($inargs, "hostname");
            $username = grab_array_var($inargs, "username", "");
            $auth_file = grab_array_var($inargs, "auth_file", "");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");
            $remote = grab_array_var($inargs, "remote", "");
            $security_level = grab_array_var($inargs, "security_level", "");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["address"] = $address;
            $meta_arr["hostname"] = $hostname;
            $meta_arr["username"] = $username;
            $meta_arr["auth_file"] = $auth_file;
            $meta_arr["warning"] = $warning;
            $meta_arr["critical"] = $critical;
            $meta_arr["remote"] = $remote;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $address, "", $meta_arr);

            // Add ticket to auth file if running wizard locally
            if (!$remote) {
                $ticket = "";
                if ($username != "") {
                    $uid = get_user_id($username);

                    if ($uid !== 0 || $uid !== null)
                        $ticket = get_user_attr($uid, "backend_ticket");
                }

                $filename = '/usr/local/nagiosxi/var/components/xisla_auth.txt';
                $file = fopen($filename, "r+");

                $target = "ticket=";
                $writeposition = 0;

                while (!feof($file)) {
                    $line = fgets($file);

                    if (strpos($line, $target) !== false) {
                        $insertPos = ftell($file);

                        fseek($file, $insertPos);
                        fwrite($file, $ticket);
                    }
                }

                fclose($file);
            }

            // Parse advanced options and create string
            $advancedoptions = "";
            foreach ($serviceargs['advanced'] as $key => $value) {
                if ($key == "dont_count_downtime" || $key == "dont_count_warning" || $key == "dont_count_unknown" ) {
                    if ($value == "on") {
                        $advancedoptions .= $key . "=yes,";
                    }
                } else {
                    $advancedoptions .= $key . "=" . $value . ",";
                }
            }

            // Remove any trailing commas
            $advancedoptions = rtrim($advancedoptions, ",");

            // construct service check command variable list
            $argvars = "";

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_generic_host",
                    "host_name" => $hostname,
                    "address" => $address,
                    "icon_image" => "sla.png",
                    "statusmap_image" => "sla.png",
                    "_xiwizard" => $wizard_name,
                    );
            }

            // see which services we should monitor
            foreach ($services as $svc => $svcstate) {

                switch ($svc) {

                    case "host":
                        foreach ($svcstate as $i => $v) {
                            // create service for each on checkbox
                            if ($v != "on")
                                continue;

                            $host = urlencode($serviceargs['host'][$i]['host']);

                            $argvars = "-H " . $address;
                            $argvars .= " -h '" . $host . "'";
                            $argvars .= " -A " . $auth_file;
                            $argvars .= " -w @" . $serviceargs['host'][$i]['warning'];
                            $argvars .= " -c @" . $serviceargs['host'][$i]['critical'];
                            $argvars .= " -a " . $advancedoptions;

                            // check for SSL
                            if ($security_level == "ssl") {
                                $argvars .= " --ssl true";
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "use" => "xiwizard_check_sla",
                                "service_description" => "SLA Wizard Host: " . $host,
                                "check_command" => "check_xi_sla!" . $argvars,
                                "_xiwizard" => $wizard_name,
                                );
                        }
                        break;

                    case "service":
                        foreach ($svcstate as $i => $v) {
                            // create service for each on checkbox
                            if ($v != "on")
                                continue;

                            $host = urlencode($serviceargs['service'][$i]['host']);
                            $service = urlencode($serviceargs['service'][$i]['service']);
                            $service_description = "";

                            if ($service == "") {
                                $service_description = "Service Average";
                                $service = "average";
                            } else {
                                $service_description = $service;
                                $service_description = str_replace($service_description, "+", " ");
                            }

                            $argvars = "-H " . $address;
                            $argvars .= " -h '" . $host . "'";
                            $argvars .= " -s '" . $service . "'";
                            $argvars .= " -A " . $auth_file;
                            $argvars .= " -w @" . $serviceargs['service'][$i]['warning'];
                            $argvars .= " -c @" . $serviceargs['service'][$i]['critical'];
                            $argvars .= " -a " . $advancedoptions;

                            // check for SSL
                            if ($security_level == "ssl") {
                                $argvars .= " --ssl true";
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "use" => "xiwizard_check_sla",
                                "service_description" => "SLA Wizard Service: " . $service_description . " - Host: " . $host,
                                "check_command" => "check_xi_sla!" . $argvars,
                                "_xiwizard" => $wizard_name,
                                );
                        }
                        break;

                    case "hostgroup":
                        foreach ($svcstate as $i => $v) {
                            // create service for each on checkbox
                            if ($v != "on")
                                continue;

                            $hostgroup = urlencode($serviceargs['hostgroup'][$i]['hostgroup']);

                            $argvars = "-H " . $address;
                            $argvars .= " -g '" . $hostgroup . "'";
                            $argvars .= " -A " . $auth_file;
                            $argvars .= " -w @" . $serviceargs['hostgroup'][$i]['warning'];
                            $argvars .= " -c @" . $serviceargs['hostgroup'][$i]['critical'];
                            $argvars .= " -a " . $advancedoptions;

                            // check for SSL
                            if ($security_level == "ssl") {
                                $argvars .= " --ssl true";
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "use" => "xiwizard_check_sla",
                                "service_description" => "SLA Wizard Hostgroup: " . $hostgroup,
                                "check_command" => "check_xi_sla!" . $argvars,
                                "_xiwizard" => $wizard_name,
                                );
                        }
                        break;

                    case "servicegroup":
                        foreach ($svcstate as $i => $v) {
                            // create service for each on checkbox
                            if ($v != "on")
                                continue;

                            $servicegroup = urlencode($serviceargs['servicegroup'][$i]['servicegroup']);

                            $argvars = "-H " . $address;
                            $argvars .= " -e '" . $servicegroup . "'";
                            $argvars .= " -A " . $auth_file;
                            $argvars .= " -w @" . $serviceargs['servicegroup'][$i]['warning'];
                            $argvars .= " -c @" . $serviceargs['servicegroup'][$i]['critical'];
                            $argvars .= " -a " . $advancedoptions;

                            // check for SSL
                            if ($security_level !== "ssl") {
                                $argvars .= " --ssl true";
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "use" => "xiwizard_check_sla",
                                "service_description" => "SLA Wizard Servicegroup: " . $servicegroup,
                                "check_command" => "check_xi_sla!" . $argvars,
                                "_xiwizard" => $wizard_name,
                                );
                        }
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