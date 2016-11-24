<?php
//
// MSSQL Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mssqlserver_configwizard_init();

function mssqlserver_configwizard_init()
{
    $name = "mssqlserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.8.7",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a MSSQL Server"),
        CONFIGWIZARD_DISPLAYTITLE => _("MSSQL Server"),
        CONFIGWIZARD_FUNCTION => "mssqlserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mssqlserver.png",
        CONFIGWIZARD_FILTER_GROUPS => array('windows','database'),
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
function mssqlserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mssqlserver";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "1433");
            $instance = grab_array_var($inargs, "instance", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "master");

            $address = nagiosccm_replace_user_macros($address);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $output = '
<h5 class="ul">' . _('MSSQL Database') . '</h5>
<p>' . _('Specify the details for connecting to the MSSQL database you want to monitor') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the MSSQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Instance') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="instance" id="instace" value="' . htmlentities($instance) . '" class="textfield form-control">
            <div class="subtext">' . _('The instance of the MSSQL server you wish to connect to') . '.</div>
        </td>
    </tr>    
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control">
            <div class="subtext">' . _('The port to use to connect to the MSSQL server. This defaults to port 1433, however if you are using a named instance you should delete this field, or if you are certain set the port number here') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control">
            <div class="subtext">' . _('The username used to connect to the MSSQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password used to connect to the MSSQL server') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "");
            $instance = grab_array_var($inargs, "instance", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "master");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = "No address specified.";
            if (have_value($port) == false && have_value($instance) == false)
                $errmsg[$errors++] = "No port number or instance specified.";
            if (have_value($username) == false)
                $errmsg[$errors++] = "No username specified.";
            if (have_value($password) == false)
                $errmsg[$errors++] = "No password specified.";
            if (have_value($database) == false)
                $errmsg[$errors++] = "No database specified.";
            if ($port && $instance)
                $errmsg[$errors++] = _("Cannot specify port and instance.");

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
            $instance = grab_array_var($inargs, "instance", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "master");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array(
                "connection_time" => "on",
                "bufferhitratio" => "on",
                "pagelooks" => "on",
                "freepages" => "on",
                "targetpages" => "on",
                "databasepages" => "on",
                "stolenpages" => "on",
                "lazywrites" => "on",
                "readahead" => "on",
                "pagereads" => "on",
                "checkpoints" => "on",
                "pagewrites" => "on",
                "lockrequests" => "on",
                "locktimeouts" => "on",
                "deadlocks" => "on",
                "lockwaits" => "on",
                "lockwait" => "on",
                "averagewait" => "on",
                "pagesplits" => "on",
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "connection_time_warning" => "1",
                "connection_time_critical" => "5",
                "bufferhitratio_warning" => "90",
                "bufferhitratio_critical" => "95",
                "pagelooks_warning" => "10",
                "pagelooks_critical" => "20",
                "freepages_warning" => "10",
                "freepages_critical" => "20",
                "targetpages_warning" => "70000",
                "targetpages_critical" => "90000",
                "databasepages_warning" => "300",
                "databasepages_critical" => "600",
                "stolenpages_warning" => "500",
                "stolenpages_critical" => "700",
                "lazywrites_warning" => "20",
                "lazywrites_critical" => "30",
                "readahead_warning" => "40",
                "readahead_critical" => "50",
                "pagereads_warning" => "20",
                "pagereads_critical" => "30",
                "checkpoints_warning" => "20",
                "checkpoints_critical" => "30",
                "pagewrites_warning" => "20",
                "pagewrites_critical" => "30",
                "lockrequests_warning" => "20",
                "lockrequests_critical" => "30",
                "locktimeouts_warning" => "20",
                "locktimeouts_critical" => "30",
                "deadlocks_warning" => "20",
                "deadlocks_critical" => "30",
                "lockwaits_warning" => "20",
                "lockwaits_critical" => "30",
                "lockwait_warning" => "2000",
                "lockwait_critical" => "3000",
                "averagewait_warning" => "20",
                "averagewait_critical" => "30",
                "pagesplits_warning" => "20",
                "pagesplits_critical" => "30",
            ));

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                //echo "ARGSSERIAL: $serviceargs_serial<BR>\n";
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="instance" value="' . htmlentities($instance) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="master">

<h5 class="ul">' . _('MSSQL Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>'._('Address').':</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this MSSQL Database') . '.</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Instance') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="instance" id="instance" value="' . htmlentities($instance) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('MSSQL Server Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the MSSQL Server') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="ct" class="checkbox" name="services[connection_time]" ' . is_checked(grab_array_var($services, "connection_time"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ct">
                <b>' . _('Connection Time') . '</b><br>
                ' . _('Monitor the time it takes to connect to the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[connection_time_warning]" value="' . htmlentities($serviceargs["connection_time_warning"]) . '" class="form-control condensed"> sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[connection_time_critical]" value="' . htmlentities($serviceargs["connection_time_critical"]) . '" class="form-control condensed"> sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="bhr" class="checkbox" name="services[bufferhitratio]" ' . is_checked(grab_array_var($services, "bufferhitratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="bhr">
                <b>' . _('Buffer Hit Ratio') . '</b><br>
                ' . _('Monitor the Buffer Hit Ratio') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[bufferhitratio_warning]" value="' . htmlentities($serviceargs["bufferhitratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[bufferhitratio_critical]" value="' . htmlentities($serviceargs["bufferhitratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="pl" class="checkbox" name="services[pagelooks]" ' . is_checked(grab_array_var($services, "pagelooks"), "on") . '>
        </td>
        <td>
            <label class="normal" for="pl">
                <b>' . _('Page Looks') . '</b><br>
                ' . _('Monitor the number of page looks per second') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagelooks_warning]" value="' . htmlentities($serviceargs["pagelooks_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagelooks_critical]" value="' . htmlentities($serviceargs["pagelooks_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="fp" class="checkbox" name="services[freepages]" ' . is_checked(grab_array_var($services, "freepages"), "on") . '>
        </td>
        <td>
            <label class="normal" for="fp">
                <b>' . _('Free Pages') . '</b><br>
                ' . _('Monitor the free pages') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[freepages_warning]" value="' . htmlentities($serviceargs["freepages_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[freepages_critical]" value="' . htmlentities($serviceargs["freepages_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" class="checkbox" name="services[targetpages]" ' . is_checked(grab_array_var($services, "targetpages"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tp">
                <b>' . _('Target Pages') . '</b><br>
                ' . _('The amount of target pages') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[targetpages_warning]" value="' . htmlentities($serviceargs["targetpages_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[targetpages_critical]" value="' . htmlentities($serviceargs["targetpages_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="dp" class="checkbox" name="services[databasepages]" ' . is_checked(grab_array_var($services, "databasepages"), "on") . '>
        </td>
        <td>
            <label class="normal" for="dp">
                <b>' . _('Database Pages') . '</b><br>
                ' . _('The amount of database pages') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[databasepages_warning]" value="' . htmlentities($serviceargs["databasepages_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[databasepages_critical]" value="' . htmlentities($serviceargs["databasepages_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sp" class="checkbox" name="services[stolenpages]" ' . is_checked(grab_array_var($services, "stolenpages"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sp">
                <b>' . _('Stolen Pages') . '</b><br>
                ' . _('The amount of stolen pages') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[stolenpages_warning]" value="' . htmlentities($serviceargs["stolenpages_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[stolenpages_critical]" value="' . htmlentities($serviceargs["stolenpages_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lw" class="checkbox" name="services[lazywrites]" ' . is_checked(grab_array_var($services, "lazywrites"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lw">
                <b>' . _('Lazy Writes') . '</b><br>
                ' . _('The amount of lazy writes per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[lazywrites_warning]" value="' . htmlentities($serviceargs["lazywrites_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[lazywrites_critical]" value="' . htmlentities($serviceargs["lazywrites_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ra" class="checkbox" name="services[readahead]" ' . is_checked(grab_array_var($services, "readahead"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ra">
                <b>' . _('Read Aheads') . '</b><br>
                ' . _('The amount of readaheads per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[readahead_warning]" value="' . htmlentities($serviceargs["readahead_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[readahead_critical]" value="' . htmlentities($serviceargs["readahead_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="pr" class="checkbox" name="services[pagereads]" ' . is_checked(grab_array_var($services, "pagereads"), "on") . '>
        </td>
        <td>
            <label class="normal" for="pr">
                <b>' . _('Page Reads') . '</b><br>
                ' . _('The amount of page reads per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagereads_warning]" value="' . htmlentities($serviceargs["pagereads_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagereads_critical]" value="' . htmlentities($serviceargs["pagereads_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="cp" class="checkbox" name="services[checkpoints]" ' . is_checked(grab_array_var($services, "checkpoints"), "on") . '>
        </td>
        <td>
            <label class="normal" for="cp">
                <b>' . _('Check Pages') . '</b><br>
                ' . _('The amount of checkpoint pages per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[checkpoints_warning]" value="' . htmlentities($serviceargs["checkpoints_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[checkpoints_critical]" value="' . htmlentities($serviceargs["checkpoints_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="pw2" class="checkbox" name="services[pagewrites]" ' . is_checked(grab_array_var($services, "pagewrites"), "on") . '>
        </td>
        <td>
            <label class="normal" for="pw2">
                <b>' . _('Page Writes') . '</b><br>
                ' . _('The amount of page writes per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagewrites_warning]" value="' . htmlentities($serviceargs["pagewrites_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagewrites_critical]" value="' . htmlentities($serviceargs["pagewrites_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lr" class="checkbox" name="services[lockrequests]" ' . is_checked(grab_array_var($services, "lockrequests"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lr">
                <b>' . _('Lock Requests') . '</b><br>
                ' . _('The amount of lock requests per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[lockrequests_warning]" value="' . htmlentities($serviceargs["lockrequests_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[lockrequests_critical]" value="' . htmlentities($serviceargs["lockrequests_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lt2" class="checkbox" name="services[locktimeouts]" ' . is_checked(grab_array_var($services, "locktimeouts"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lt2">
                <b>' . _('Lock Timeouts') . '</b><br>
                ' . _('The amount of lock timeouts per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[locktimeouts_warning]" value="' . htmlentities($serviceargs["locktimeouts_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[locktimeouts_critical]" value="' . htmlentities($serviceargs["locktimeouts_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="d" class="checkbox" name="services[deadlocks]" ' . is_checked(grab_array_var($services, "deadlocks"), "on") . '>
        </td>
        <td>
            <label class="normal" for="d">
                <b>' . _('Deadlocks') . '</b><br>
                ' . _('The amount of deadlocks per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[deadlocks_warning]" value="' . htmlentities($serviceargs["deadlocks_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[deadlocks_critical]" value="' . htmlentities($serviceargs["deadlocks_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lw23" class="checkbox" name="services[lockwaits]" ' . is_checked(grab_array_var($services, "lockwaits"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lw23">
                <b>' . _('Lock Waits') . '</b><br>
                ' . _('The amount of lockwaits per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[lockwaits_warning]" value="' . htmlentities($serviceargs["lockwaits_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[lockwaits_critical]" value="' . htmlentities($serviceargs["lockwaits_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ps" class="checkbox" name="services[pagesplits]" ' . is_checked(grab_array_var($services, "pagesplits"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ps">
                <b>' . _('Page Splits') . '</b><br>
                ' . _('The amount of page splits per sec') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagesplits_warning]" value="' . htmlentities($serviceargs["pagesplits_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[pagesplits_critical]" value="' . htmlentities($serviceargs["pagesplits_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lwt" class="checkbox" name="services[lockwait]" ' . is_checked(grab_array_var($services, "lockwait"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lwt">
                <b>' . _('Lock Wait Time') . '</b><br>
                ' . _('Monitor the lock wait time') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[lockwait_warning]" value="' . htmlentities($serviceargs["lockwait_warning"]) . '" class="form-control condensed"> ms&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[lockwait_critical]" value="' . htmlentities($serviceargs["lockwait_critical"]) . '" class="form-control condensed"> ms
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="awt" class="checkbox" name="services[averagewait]" ' . is_checked(grab_array_var($services, "averagewait"), "on") . '>
        </td>
        <td>
            <label class="normal" for="awt">
                <b>' . _('Average Wait Time') . '</b><br>
                ' . _('Monitor the average wait time for execution') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[averagewait_warning]" value="' . htmlentities($serviceargs["averagewait_warning"]) . '" class="form-control condensed"> ms&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[averagewait_critical]" value="' . htmlentities($serviceargs["averagewait_critical"]) . '" class="form-control condensed"> ms
            </div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "");
            $instance = grab_array_var($inargs, "instance", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "master");

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());


            // check for errors
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
            $port = grab_array_var($inargs, "port", "");
            $instance = grab_array_var($inargs, "instance", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "master");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '" />
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
        <input type="hidden" name="port" value="' . htmlentities($port) . '" />
        <input type="hidden" name="instance" value="' . htmlentities($instance) . '" />
        <input type="hidden" name="username" value="' . htmlentities($username) . '" />
        <input type="hidden" name="password" value="' . htmlentities($password) . '" />
        <input type="hidden" name="database" value="' . htmlentities($database) . '" />
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '" />
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '" />
        
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
            $instance = grab_array_var($inargs, "instance", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "master");

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
            $meta_arr["instance"] = $instance;
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["database"] = $database;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mssqlserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "mssqlserver.png",
                    "statusmap_image" => "mssqlserver.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // common plugin opts
            $commonopts = "-U '$username' -P '$password' ";
            if ($instance)
                $commonopts .= "-I '$instance' ";
            if ($port)
                $commonopts .= "-p $port ";

            foreach ($services as $svcvar => $svcval) {

                $pluginopts = "";
                $pluginopts .= $commonopts;

                switch ($svcvar) {

                    case "connection_time":

                        $pluginopts .= "--time2connect --warning " . $serviceargs["connection_time_warning"] . " --critical " . $serviceargs["connection_time_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Connection Time",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "bufferhitratio":

                        $pluginopts .= "--bufferhitratio --warning " . $serviceargs["bufferhitratio_warning"] . ": --critical " . $serviceargs["bufferhitratio_critical"] . ":";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Buffer Hit Ratio",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagelooks":

                        $pluginopts .= "--pagelooks --warning " . $serviceargs["pagelooks_warning"] . " --critical " . $serviceargs["pagelooks_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Page Looks Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    case "freepages":

                        $pluginopts .= "--freepages --warning " . $serviceargs["freepages_warning"] . " --critical " . $serviceargs["freepages_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Free Pages",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "targetpages":

                        $pluginopts .= "--targetpages --warning " . $serviceargs["targetpages_warning"] . " --critical " . $serviceargs["targetpages_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Target Pages",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "databasepages":

                        $pluginopts .= "--databasepages --warning " . $serviceargs["databasepages_warning"] . " --critical " . $serviceargs["databasepages_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Database Pages",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "stolenpages":

                        $pluginopts .= "--stolenpages --warning " . $serviceargs["stolenpages_warning"] . " --critical " . $serviceargs["stolenpages_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Stolen Pages",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "lazywrites":

                        $pluginopts .= "--lazywrites --warning " . $serviceargs["lazywrites_warning"] . " --critical " . $serviceargs["lazywrites_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Lazy Writes Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "readahead":

                        $pluginopts .= "--readahead --warning " . $serviceargs["readahead_warning"] . " --critical " . $serviceargs["readahead_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Readaheads Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagereads":

                        $pluginopts .= "--pagereads --warning " . $serviceargs["pagereads_warning"] . " --critical " . $serviceargs["pagereads_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Page Reads Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagewrites":

                        $pluginopts .= "--pagewrites --warning " . $serviceargs["pagewrites_warning"] . " --critical " . $serviceargs["pagewrites_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Page Writes Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "checkpoints":

                        $pluginopts .= "--checkpoints --warning " . $serviceargs["checkpoints_warning"] . " --critical " . $serviceargs["checkpoints_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Checkpoint Pages Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "lockrequests":

                        $pluginopts .= "--lockrequests --warning " . $serviceargs["lockrequests_warning"] . " --critical " . $serviceargs["lockrequests_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Lock Requests Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "locktimeouts":

                        $pluginopts .= "--locktimeouts --warning " . $serviceargs["locktimeouts_warning"] . " --critical " . $serviceargs["locktimeouts_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Lock Timeouts Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "deadlocks":

                        $pluginopts .= "--deadlocks --warning " . $serviceargs["deadlocks_warning"] . " --critical " . $serviceargs["deadlocks_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Deadlocks Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "lockwaits":

                        $pluginopts .= "--lockwaits --warning " . $serviceargs["lockwaits_warning"] . " --critical " . $serviceargs["lockwaits_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Lock Waits Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pagesplits":

                        $pluginopts .= "--pagesplits --warning " . $serviceargs["pagesplits_warning"] . " --critical " . $serviceargs["pagesplits_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Page Splits Per Sec",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "lockwait":

                        $pluginopts .= "--lockwait --warning " . $serviceargs["lockwait_warning"] . " --critical " . $serviceargs["lockwait_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Lock Wait Times",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "averagewait":

                        $pluginopts .= "--averagewait --warning " . $serviceargs["averagewait_warning"] . " --critical " . $serviceargs["averagewait_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Average Wait Time",
                            "use" => "xiwizard_mssqlserver_service",
                            "check_command" => "check_xi_mssql_server!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    default:
                        break;
                }
            }

            //~ echo "OBJECTS:<BR>";
            //~ print_r($objs);
            //~ exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


?>
