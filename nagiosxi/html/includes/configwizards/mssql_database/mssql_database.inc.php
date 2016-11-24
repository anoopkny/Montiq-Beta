<?php
//
// MSSQL Database Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mssqldatabase_configwizard_init();

function mssqldatabase_configwizard_init()
{
    $name = "mssqldatabase";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.8",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a MSSQL Database"),
        CONFIGWIZARD_DISPLAYTITLE => _("MSSQL Database"),
        CONFIGWIZARD_FUNCTION => "mssqldatabase_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mssqldatabase.png",
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
function mssqldatabase_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mssqldatabase";

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
            <label>' . _('Address') . ':</label><br class="nobr" />
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
            <input type="text" size="40" name="instance" id="instance" value="' . htmlentities($instance) . '" class="textfield form-control">
            <div class="subtext">' . _('Instance name of the MSSQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control">
            <div class="subtext">' . _('The port to use to connect to the MSSQL server. This defaults to 1433, however, if you are using a named instance you should remove this number') . '.</div>
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
    <tr>
        <td class="vt">
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="textfield form-control">
            <div class="subtext">' . _('The database to connect to on the MSSQL server') . '.</div>
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
            $database = grab_array_var($inargs, "database", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");
            if (have_value($port) == false and have_value($instance) == false)
                $errmsg[$errors++] = _("No port number or instance name specified.\nOne must be specified.");
            if (have_value($username) == false)
                $errmsg[$errors++] = _("No username specified.");
            if (have_value($password) == false)
                $errmsg[$errors++] = _("No password specified.");
            if (have_value($database) == false)
                $errmsg[$errors++] = _("No database specified.");
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
            $instance = grab_array_var($inargs, "instance", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array(
                "connection_time" => "on",
                "logfileusage" => "on",
                "database_size" => "on",
                "active_transactions" => "on",
                "transactions_per_second" => "on",
                "log_cache_hit_rate" => "on",
                "log_growths" => "on",
                "log_shrinks" => "on",
                "log_truncs" => "on",
                "log_wait" => "on",
                "log_flushes" => "on",
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "connection_time_warning" => "1",
                "connection_time_critical" => "5",
                "logfileusage_warning" => array("0", "80"),
                "logfileusage_critical" => array("0", "90"),
                "database_size_warning" => "10000",
                "database_size_critical" => "100000",
                "active_transactions_warning" => "10",
                "active_transactions_critical" => "20",
                "transactions_per_second_warning" => "10",
                "transactions_per_second_critical" => "20",
                "log_cache_hit_rate_warning" => array("0", "95"),
                "log_cache_hit_rate_critical" => array("0", "97"),
                "log_truncs_warning" => "20",
                "log_truncs_critical" => "30",
                "log_growths_warning" => "20",
                "log_growths_critical" => "30",
                "log_shrinks_warning" => "20",
                "log_shrinks_critical" => "30",
                "log_wait_warning" => "100",
                "log_wait_critical" => "1000",
                "log_flushes_warning" => "20",
                "log_flushes_critical" => "30",
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
<input type="hidden" name="instance" value="' . htmlentities($instance) . '">

<h5 class="ul">' . _('MSSQL Server') . '</h5>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this MSSQL Database') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Instance') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="instance" id="instance" value="' . htmlentities($instance) . '" class="textfield form-control" disabled>
            <div class="subtext">' . _('Instance name of the MSSQL server') . '.</div>
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
    <tr>
        <td>
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="textfield form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('MSSQL Database Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the MSSQL Database') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="ct" class="checkbox" name="services[connection_time]" ' . is_checked(grab_array_var($services, "connection_time"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ct">
                <b>' . _('Connection Time') . '</b><br>
                ' . _('Monitor the time it takes to connect to the database') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[connection_time_warning]" value="' . htmlentities($serviceargs["connection_time_warning"]) . '" class="form-control condensed"> sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[connection_time_critical]" value="' . htmlentities($serviceargs["connection_time_critical"]) . '" class="form-control condensed"> sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ds" class="checkbox" name="services[database_size]" ' . is_checked(grab_array_var($services, "database_size"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ds">
                <b>' . _('Database Size') . '</b><br>
                ' . _('Monitor the time it takes to connect to the database') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="5" name="serviceargs[database_size_warning]" value="' . htmlentities($serviceargs["database_size_warning"]) . '" class="form-control condensed"> KB&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="5" name="serviceargs[database_size_critical]" value="' . htmlentities($serviceargs["database_size_critical"]) . '" class="form-control condensed"> KB
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lfu" class="checkbox" name="services[logfileusage]" ' . is_checked(grab_array_var($services, "logfileusage"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lfu">
                <b>' . _('Log File Usage') . '</b><br>
                ' . _('Monitor how much of the Log File is in use') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[logfileusage_warning][0]" value="' . htmlentities($serviceargs["logfileusage_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[logfileusage_warning][1]" value="' . htmlentities($serviceargs["logfileusage_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[logfileusage_critical][0]" value="' . htmlentities($serviceargs["logfileusage_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[logfileusage_critical][1]" value="' . htmlentities($serviceargs["logfileusage_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="oc" class="checkbox" name="services[active_transactions]" ' . is_checked(grab_array_var($services, "active_transactions"), "on") . '>
        </td>
        <td>
            <label class="normal" for="oc">
                <b>' . _('Open Connections') . '</b><br>
                ' . _('Monitor the number of currently open connections') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[active_transactions_warning]" value="' . htmlentities($serviceargs["active_transactions_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[active_transactions_critical]" value="' . htmlentities($serviceargs["active_transactions_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tps" class="checkbox" name="services[transactions_per_second]" ' . is_checked(grab_array_var($services, "transactions_per_second"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tps">
                <b>' . _('Transactions Per Second') . '</b><br>
                ' . _('Monitor the transactions per second') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[transactions_per_second_warning]" value="' . htmlentities($serviceargs["transactions_per_second_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[transactions_per_second_critical]" value="' . htmlentities($serviceargs["transactions_per_second_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lch" class="checkbox" name="services[log_cache_hit_rate]" ' . is_checked(grab_array_var($services, "log_cache_hit_rate"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lch">
                <b>' . _('Log Cache Hitrate') . '</b><br>
                ' . _('Monitor the log cache hit rate') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[log_cache_hit_rate_warning][0]" value="' . htmlentities($serviceargs["log_cache_hit_rate_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[log_cache_hit_rate_warning][1]" value="' . htmlentities($serviceargs["log_cache_hit_rate_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[log_cache_hit_rate_critical][0]" value="' . htmlentities($serviceargs["log_cache_hit_rate_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[log_cache_hit_rate_critical][1]" value="' . htmlentities($serviceargs["log_cache_hit_rate_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lw" class="checkbox" name="services[log_wait]" ' . is_checked(grab_array_var($services, "log_wait"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lw">
                <b>' . _('Log Waits') . '</b><br>
                ' . _('Monitor the log waits due to small log buffers') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_wait_warning]" value="' . htmlentities($serviceargs["log_wait_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_wait_critical]" value="' . htmlentities($serviceargs["log_wait_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lg" class="checkbox" name="services[log_growths]" ' . is_checked(grab_array_var($services, "log_growths"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lg">
                <b>' . _('Log Growths') . '</b><br>
                ' . _('Monitor the log growths due to improperly sized partitions') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_growths_warning]" value="' . htmlentities($serviceargs["log_growths_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_growths_critical]" value="' . htmlentities($serviceargs["log_growths_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ls" class="checkbox" name="services[log_shrinks]" ' . is_checked(grab_array_var($services, "log_shrinks"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ls">
                <b>' . _('Log Shrinks') . '</b><br>
                ' . _('Monitor the log shrinks due to improperly sized partitions') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_shrinks_warning]" value="' . htmlentities($serviceargs["log_shrinks_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_shrinks_critical]" value="' . htmlentities($serviceargs["log_shrinks_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lt" class="checkbox" name="services[log_truncs]" ' . is_checked(grab_array_var($services, "log_truncs"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lt">
                <b>' . _('Log Truncations') . '</b><br>
                ' . _('Monitor the log truncations due to malformed tables') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_truncs_warning]" value="' . htmlentities($serviceargs["log_truncs_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_truncs_critical]" value="' . htmlentities($serviceargs["log_truncs_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lfwt" class="checkbox" name="services[log_wait]" ' . is_checked(grab_array_var($services, "log_wait"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lfwt">
                <b>' . _('Log Flush Wait Times') . '</b><br>
                ' . _('Monitor the log flush wait times to load') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_truncs_warning]" value="' . htmlentities($serviceargs["log_wait_warning"]) . '" class="form-control condensed"> ms&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_wait_critical]" value="' . htmlentities($serviceargs["log_truncs_critical"]) . '" class="form-control condensed"> ms
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
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");
            $instance = grab_array_var($inargs, "instance", "");
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
            $database = grab_array_var($inargs, "database", "");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="instance" value="' . htmlentities($instance) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="database" value="' . htmlentities($database) . '">
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
            $username = grab_array_var($inargs, "username", "");
            $instance = grab_array_var($inargs, "instance", "");
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
            $meta_arr["password"] = $password;
            $meta_arr["database"] = $database;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            $meta_arr["instance"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mssqldatabase_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "mssqldatabase.png",
                    "statusmap_image" => "mssqldatabase.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // common plugin opts
            $commonopts = "-U '$username' -P '$password' -T '$database' ";
            if ($instance)
                $commonopts .= "-I $instance ";
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
                            "service_description" => $database . "MSSQL Connection Time",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "logfileusage":

                        $pluginopts .= "--logfileusage --warning " . $serviceargs["logfileusage_warning"][0] . ":" . $serviceargs["logfileusage_warning"][1] . " --critical " . $serviceargs["logfileusage_critical"][0] . ":" . $serviceargs["logfileusage_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Log File Usage",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "database_size":

                        $pluginopts .= "--datasize --warning " . $serviceargs["database_size_warning"] . " --critical " . $serviceargs["database_size_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . " MSSQL Database Size",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "active_transactions":

                        $pluginopts .= "--activetrans --warning " . $serviceargs["active_transactions_warning"] . " --critical " . $serviceargs["active_transactions_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Active Transactions",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "log_cache_hit_rate":

                        $pluginopts .= "--logcachehit --warning " . $serviceargs["log_cache_hit_rate_warning"][0] . ":" . $serviceargs["log_cache_hit_rate_warning"][1] . " --critical " . $serviceargs["log_cache_hit_rate_critical"][0] . ":" . $serviceargs["log_cache_hit_rate_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Log Cache Hit Rate",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    case "log_wait":

                        $pluginopts .= "--logwait --warning " . $serviceargs["log_wait_warning"] . " --critical " . $serviceargs["log_wait_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Log Flush Wait Time",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "log_growths":

                        $pluginopts .= "--loggrowths --warning " . $serviceargs["log_growths_warning"] . " --critical " . $serviceargs["log_growths_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Log Growths",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "log_shrinks":

                        $pluginopts .= "--logshrinks --warning " . $serviceargs["log_shrinks_warning"] . " --critical " . $serviceargs["log_shrinks_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Log Shrinks",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "log_truncs":

                        $pluginopts .= "--logtruncs --warning " . $serviceargs["log_truncs_warning"] . " --critical " . $serviceargs["log_truncs_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MSSQL Log Truncations",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "log_flushes":

                        $pluginopts .= "--logflushes --warning " . $serviceargs["log_flushes_warning"] . " --critical " . $serviceargs["log_flushes_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Log Flushes / Sec",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "transactions_per_second":

                        $pluginopts .= "--transpsec --warning " . $serviceargs["transactions_per_second_warning"] . " --critical " . $serviceargs["transactions_per_second_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $database . "MSSQL Transactions / Sec",
                            "use" => "xiwizard_mssqldatabase_service",
                            "check_command" => "check_xi_mssql_database!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    default:
                        break;
                }
            }

            // echo "OBJECTS:<BR>";
            // print_r($objs);
            // exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


?>
