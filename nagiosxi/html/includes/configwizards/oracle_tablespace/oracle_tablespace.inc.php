<?php
//
// Oracle Tablespace Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

oracletablespace_configwizard_init();

function oracletablespace_configwizard_init()
{
    $name = "oracletablespace";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor an Oracle Tablespace"),
        CONFIGWIZARD_DISPLAYTITLE => _("Oracle Tablespace"),
        CONFIGWIZARD_FUNCTION => "oracletablespace_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "oracletablespace.png",
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
function oracletablespace_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "oracletablespace";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "1521");
            $sid = grab_array_var($inargs, "sid", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "SYSTEM");

            $address = nagiosccm_replace_user_macros($address);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            // Sanity check for wizard requirements
            $sanity = '';
            if ((!file_exists('/usr/lib/oracle') && !file_exists('/usr/lib64/oracle')) ||
                !file_exists('/usr/local/nagios/libexec/check_oracle_health')
            ) {
                $sanity .= "<div class='message'><ul class='errorMessage' style='margin-top: 0;'><li><strong>" . _('WARNING:') . "</strong> O" . _('racle libraries do not appear to be installed. See the') . " <a href='https://assets.nagios.com/downloads/nagiosxi/docs/Oracle_Plugin_Installation.pdf' title='"._('Install Instructions')."' target='_blank'>" . _('Oracle Plugin Installation') . "</a> " . _('instructions for monitoring Oracle') . "</li></ul></div>";
            }

            $output = '
<h5 class="ul">'._('Oracle Tablespace').'</h5>
' . $sanity . '
<p>' . _('Specify the details for connecting to the Oracle tablespace you want to monitor.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the Oracle server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="form-control">
            <div class="subtext">' . _('The port to use to connect to the Oracle server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('SID') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="sid" id="sid" value="' . htmlentities($sid) . '" class="form-control">
            <div class="subtext">' . _('The SID to use to connect to the Oracle server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control">
            <div class="subtext">' . _('The username used to connect to the Oracle server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password:') . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _('The password used to connect to the Oracle server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Database:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="form-control">
            <div class="subtext">' . _('The tablespace to connect to on the Oracle server.') . '</div>
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
            $sid = grab_array_var($inargs, "sid", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            if (have_value($port) == false)
                $errmsg[$errors++] = _("No port number specified.");
            if (have_value($username) == false)
                $errmsg[$errors++] = _("No username specified.");
            if (have_value($password) == false)
                $errmsg[$errors++] = _("No password specified.");
            if (have_value($database) == false)
                $errmsg[$errors++] = _("No tablespace specified.");

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
            $sid = grab_array_var($inargs, "sid", "");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array(
                "usage" => "on",
                "free" => "on",
                "remaining-time" => "on",
                "fragmentation" => "on",
                "io-balance" => "on",
                "can-allocate-next" => "on",
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "usage_warning" => "90",
                "usage_critical" => "98",
                "free_warning" => "5:",
                "free_critical" => "2:",
                "remaining-time_warning" => "10",
                "remaining-time_critical" => "20",
                "fragmentation_warning" => "30:",
                "fragmentation_critical" => "20:",
                "io-balance_warning" => "50",
                "io-balance_critical" => "100",
                "can-allocate-next_warning" => "20",
                "can-allocate-next_critical" => "30",
            ));

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="' . htmlentities($database) . '">
<input type="hidden" name="sid" value="' . htmlentities($sid) . '">

<h5 class="ul">'._('Oracle Server').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address:') . '</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this Oracle Tablespace.') . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Port:') . '</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('SID:') . '</label>
        </td>
        <td>
            <input type="text" size="5" name="sid" id="sid" value="' . htmlentities($sid) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Username:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Password:') . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Tablespace:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Oracle Tablespace Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the Oracle Tablespace.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="tu" class="checkbox" name="services[usage]" ' . is_checked(grab_array_var($services, "usage"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tu">
                <b>' . _('Tablespace Usage') . '</b><br>
                ' . _('Monitor amount of space used on the tablespace.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[usage_warning]" value="' . htmlentities($serviceargs["usage_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[usage_critical]" value="' . htmlentities($serviceargs["usage_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tfs" class="checkbox" name="services[free]" ' . is_checked(grab_array_var($services, "free"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tfs">
                <b>' . _('Tablespace Free Space') . '</b><br>
                ' . _('Monitor amount of space free on the tablespace.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[free_warning]" value="' . htmlentities($serviceargs["free_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[free_critical]" value="' . htmlentities($serviceargs["free_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="trtuf" class="checkbox" name="services[remaining-time]" ' . is_checked(grab_array_var($services, "remaining-time"), "on") . '>
        </td>
        <td>
            <label class="normal" for="trtuf">
                <b>' . _('Tablespace Remaining Time Until Full') . '</b><br>
                ' . _('Monitor indicators of how much time until tablespace is full.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[remaining-time_warning]" value="' . htmlentities($serviceargs["remaining-time_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[remaining-time_critical]" value="' . htmlentities($serviceargs["remaining-time_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tf" class="checkbox" name="services[fragmentation]" ' . is_checked(grab_array_var($services, "fragmentation"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tf">
                <b>' . _('Tablespace Fragmentation') . '</b><br>
                ' . _('Monitor indicators of how fragmented a tablespace is.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[fragmentation_warning]" value="' . htmlentities($serviceargs["fragmentation_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[fragmentation_critical]" value="' . htmlentities($serviceargs["fragmentation_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tiob" class="checkbox" name="services[io-balance]" ' . is_checked(grab_array_var($services, "io-balance"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tiob">
                <b>' . _('Tablespace IO Balance') . '</b><br>
                ' . _('Monitor indicators IO Balance.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[io-balance_warning]" value="' . htmlentities($serviceargs["io-balance_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[io-balance_critical]" value="' . htmlentities($serviceargs["io-balance_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="cane" class="checkbox" name="services[can-allocate-next]" ' . is_checked(grab_array_var($services, "can-allocate-next"), "on") . '>
        </td>
        <td>
            <label class="normal" for="cane">
                <b>' . _('Can Allocate Next Extent') . '</b><br>
                ' . _('Monitor segments of a tablespace which can allocate next extent.') . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[can-allocate-next_warning]" value="' . htmlentities($serviceargs["can-allocate-next_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[can-allocate-next_critical]" value="' . htmlentities($serviceargs["can-allocate-next_critical"]) . '" class="form-control condensed">
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
                $errmsg[$errors++] = "Invalid host name.";

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
            $sid = grab_array_var($inargs, "sid", "");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="database" value="' . htmlentities($database) . '">
        <input type="hidden" name="sid" value="' . htmlentities($sid) . '">
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
            $sid = grab_array_var($inargs, "sid", "");
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
            $meta_arr["sid"] = $sid;
            $meta_arr["password"] = $password;
            $meta_arr["database"] = $database;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_oracletablespace_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "oracle.png",
                    "statusmap_image" => "oracle.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // common plugin opts
            if (have_value($sid) == false) {
                $commonopts = "--connect '{$address}:{$port}' --username '{$username}' --password '{$password}' ";
            } else {
                $commonopts = "--connect '{$address}:{$port}/{$sid}' --username '{$username}' --password '{$password}' ";
            }

            foreach ($services as $svcvar => $svcval) {

                $pluginopts = "";
                $pluginopts .= $commonopts;

                switch ($svcvar) {

                    case "usage":

                        $pluginopts .= "--mode tablespace-usage  --warning " . $serviceargs["usage_warning"] . " --critical " . $serviceargs["usage_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Tablespace ".$database." Usage",
                            "use" => "xiwizard_oracletablespace_service",
                            "check_command" => "check_xi_oracletablespace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "free":

                        $pluginopts .= "--mode tablespace-free  --warning " . $serviceargs["free_warning"] . " --critical " . $serviceargs["free_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Tablespace ".$database." Free Space",
                            "use" => "xiwizard_oracletablespace_service",
                            "check_command" => "check_xi_oracletablespace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "remaining-time":

                        $pluginopts .= "--mode tablespace-remaining-time  --warning " . $serviceargs["remaining-time_warning"] . " --critical " . $serviceargs["remaining-time_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Tablespace ".$database." Remaining Time",
                            "use" => "xiwizard_oracletablespace_service",
                            "check_command" => "check_xi_oracletablespace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "fragmentation":

                        $pluginopts .= "--mode tablespace-fragmentation  --warning " . $serviceargs["fragmentation_warning"] . " --critical " . $serviceargs["fragmentation_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Tablespace ".$database." Fragmentation",
                            "use" => "xiwizard_oracletablespace_service",
                            "check_command" => "check_xi_oracletablespace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    case "io-balance":

                        $pluginopts .= "--mode tablespace-io-balance  --warning " . $serviceargs["io-balance_warning"] . " --critical " . $serviceargs["io-balance_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Tablespace ".$database." IO Balance",
                            "use" => "xiwizard_oracletablespace_service",
                            "check_command" => "check_xi_oracletablespace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "can-allocate-next":

                        $pluginopts .= "--mode tablespace-can-allocate-next  --warning " . $serviceargs["can-allocate-next_warning"] . " --critical " . $serviceargs["can-allocate-next_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "Tablespace ".$database." Can Allocate Next",
                            "use" => "xiwizard_oracletablespace_service",
                            "check_command" => "check_xi_oracletablespace!" . $pluginopts,
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