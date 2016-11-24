<?php
//
// MySQL Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mysqlserver_configwizard_init();

function mysqlserver_configwizard_init()
{
    $name = "mysqlserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a MySQL server."),
        CONFIGWIZARD_DISPLAYTITLE => _("MySQL Server"),
        CONFIGWIZARD_FUNCTION => "mysqlserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mysql.png",
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
function mysqlserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mysqlserver";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "3306");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "information_schema");

            $address = nagiosccm_replace_user_macros($address);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            $output = '
<h5 class="ul">' . _('MySQL Server') . '</h5>
<p>'._('Specify the details for connecting to the MySQL server you want to monitor.').'</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control">
            <div class="subtext">' . _('The port to use to connect to the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control">
            <div class="subtext">' . _('The username used to connect to the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password used to connect to the MySQL server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Database') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="database" id="database" value="' . htmlentities($database) . '" class="textfield form-control">
            <div class="subtext">' . _('The database to connect to on the MySQL server') . '.</div>
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
                $errmsg[$errors++] = _("No database specified.");

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

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = "";
            $services_default = array(
                "connection_time" => "on",
                "uptime" => "on",
                "threads_connected" => "on",
                "threadcache_hitrate" => "on",
                "qcache_hitrate" => "on",
                "keycache_hitrate" => "on",
                "bufferpool_hitrate" => "on",
                "log_waits" => "on",
                "tablecache_hitrate" => "on",
                "index_usage" => "on",
                "slow_queries" => "on",
                "long_running_procs" => "on",

                "slave_io_running" => "",
                "slave_sql_running" => "",
                "slave_lag" => "",
            );

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", $services_default);

            $serviceargs = "";
            $serviceargs_default = array(
                "connection_time_warning" => "1",
                "connection_time_critical" => "5",
                "uptime_warning" => array("10", ""),
                "uptime_critical" => array("5", ""),
                "threads_connected_warning" => "10",
                "threads_connected_critical" => "20",
                "threadcache_hitrate_warning" => array("90", ""),
                "threadcache_hitrate_critical" => array("80", ""),
                "qcache_hitrate_warning" => array("90", ""),
                "qcache_hitrate_critical" => array("80", ""),
                "keycache_hitrate_warning" => array("99", ""),
                "keycache_hitrate_critical" => array("95", ""),
                "bufferpool_hitrate_warning" => array("99", ""),
                "bufferpool_hitrate_critical" => array("95", ""),
                "log_waits_warning" => "1",
                "log_waits_critical" => "10",
                "tablecache_hitrate_warning" => array("99", ""),
                "tablecache_hitrate_critical" => array("95", ""),
                "index_usage_warning" => array("90", ""),
                "index_usage_critical" => array("80", ""),
                "slow_queries_warning" => "0.1",
                "slow_queries_critical" => "1",
                "long_running_procs_warning" => "10",
                "long_running_procs_critical" => "20",

                "slave_lag_warning" => "15",
                "slave_lag_critical" => "30",
            );

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                //echo "ARGSSERIAL: $serviceargs_serial<BR>\n";
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
            if (!is_array($serviceargs))
                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
<input type="hidden" name="database" value="' . htmlentities($database) . '">

<h5 class="ul">' . _('MySQL Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
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
            <div class="subtext">' . _('The name you\'d like to have associated with this MySQL server') . '.</div>
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

<h5 class="ul">' . _('MySQL Server Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the MySQL server') . '.</p>
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
            <input type="checkbox" id="u" class="checkbox" name="services[uptime]" ' . is_checked(grab_array_var($services, "uptime"), "on") . '>
        </td>
        <td>
            <label class="normal" for="u">
                <b>' . _('Uptime') . '</b><br>
                ' . _('Monitor the time the MySQL server has been running. Lower numbers are worse and are indicative of the server having been restarted') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[uptime_warning][0]" value="' . htmlentities($serviceargs["uptime_warning"][0]) . '" class="form-control condensed"> min &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[uptime_warning][1]" value="' . htmlentities($serviceargs["uptime_warning"][1]) . '" class="form-control condensed"> min
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[uptime_critical][0]" value="' . htmlentities($serviceargs["uptime_critical"][0]) . '" class="form-control condensed"> min &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[uptime_critical][1]" value="' . htmlentities($serviceargs["uptime_critical"][1]) . '" class="form-control condensed"> min
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="oc" class="checkbox" name="services[threads_connected]" ' . is_checked(grab_array_var($services, "threads_connected"), "on") . '>
        </td>
        <td>
            <label class="normal" for="oc">
                <b>' . _('Open Connections') . '</b><br>
                ' . _('Monitor the number of currently open connections') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[threads_connected_warning]" value="' . htmlentities($serviceargs["threads_connected_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[threads_connected_critical]" value="' . htmlentities($serviceargs["threads_connected_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tch" class="checkbox" name="services[threadcache_hitrate]" ' . is_checked(grab_array_var($services, "threadcache_hitrate"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tch">
                <b>' . _('Thread Cache Hitrate') . '</b><br>
            ' . _('Monitor the thread cache hit rate') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[threadcache_hitrate_warning][0]" value="' . htmlentities($serviceargs["threadcache_hitrate_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[threadcache_hitrate_warning][1]" value="' . htmlentities($serviceargs["threadcache_hitrate_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[threadcache_hitrate_critical][0]" value="' . htmlentities($serviceargs["threadcache_hitrate_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[threadcache_hitrate_critical][1]" value="' . htmlentities($serviceargs["threadcache_hitrate_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="qch" class="checkbox" name="services[qcache_hitrate]" ' . is_checked(grab_array_var($services, "qcache_hitrate"), "on") . '>
        </td>
        <td>
            <label class="normal" for="qch">
                <b>' . _('Query Cache Hitrate') . '</b><br>
                ' . _('Monitor the query cache hit rate') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[qcache_hitrate_warning][0]" value="' . htmlentities($serviceargs["qcache_hitrate_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[qcache_hitrate_warning][1]" value="' . htmlentities($serviceargs["qcache_hitrate_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[qcache_hitrate_critical][0]" value="' . htmlentities($serviceargs["qcache_hitrate_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[qcache_hitrate_critical][1]" value="' . htmlentities($serviceargs["qcache_hitrate_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="kch" class="checkbox" name="services[keycache_hitrate]" ' . is_checked(grab_array_var($services, "keycache_hitrate"), "on") . '>
        </td>
        <td>
            <label class="normal" for="kch">
                <b>' . _('MyISAM Key Cache Hitrate') . '</b><br>
                ' . _('Monitor the MyISAM key cache hit rate') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[keycache_hitrate_warning][0]" value="' . htmlentities($serviceargs["keycache_hitrate_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[keycache_hitrate_warning][1]" value="' . htmlentities($serviceargs["keycache_hitrate_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[keycache_hitrate_critical][0]" value="' . htmlentities($serviceargs["keycache_hitrate_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[keycache_hitrate_critical][1]" value="' . htmlentities($serviceargs["keycache_hitrate_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="bph" class="checkbox" name="services[bufferpool_hitrate]" ' . is_checked(grab_array_var($services, "bufferpool_hitrate"), "on") . '>
        </td>
        <td>
            <label class="normal" for="bph">
                <b>' . _('InnoDB Buffer Pool Hitrate') . '</b><br>
                ' . _('Monitor the InnoDB buffer pool hit rate') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[bufferpool_hitrate_warning][0]" value="' . htmlentities($serviceargs["bufferpool_hitrate_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[bufferpool_hitrate_warning][1]" value="' . htmlentities($serviceargs["bufferpool_hitrate_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[bufferpool_hitrate_critical][0]" value="' . htmlentities($serviceargs["bufferpool_hitrate_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[bufferpool_hitrate_critical][1]" value="' . htmlentities($serviceargs["bufferpool_hitrate_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lw" class="checkbox" name="services[log_waits]" ' . is_checked(grab_array_var($services, "log_waits"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lw">
                <b>' . _('Log Waits') . '</b><br>
                ' . _('Monitor the InnoDB log waits due to small log buffers') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_waits_warning]" value="' . htmlentities($serviceargs["log_waits_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[log_waits_critical]" value="' . htmlentities($serviceargs["log_waits_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="tchr" class="checkbox" name="services[tablecache_hitrate]" ' . is_checked(grab_array_var($services, "tablecache_hitrate"), "on") . '>
        </td>
        <td>
            <label class="normal" for="tchr">
                <b>' . _('Table Cache Hitrate') . '</b><br>
                ' . _('Monitor the table cache hit rate') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[tablecache_hitrate_warning][0]" value="' . htmlentities($serviceargs["tablecache_hitrate_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[tablecache_hitrate_warning][1]" value="' . htmlentities($serviceargs["tablecache_hitrate_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[tablecache_hitrate_critical][0]" value="' . htmlentities($serviceargs["tablecache_hitrate_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[tablecache_hitrate_critical][1]" value="' . htmlentities($serviceargs["tablecache_hitrate_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="iu" class="checkbox" name="services[index_usage]" ' . is_checked(grab_array_var($services, "index_usage"), "on") . '>
        </td>
        <td>
            <label class="normal" for="iu">
                <b>' . _('Index Usage') . '</b><br>
                ' . _('Monitor the usage of indexes') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[index_usage_warning][0]" value="' . htmlentities($serviceargs["index_usage_warning"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[index_usage_warning][1]" value="' . htmlentities($serviceargs["index_usage_warning"][1]) . '" class="form-control condensed"> %
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> &nbsp;<label>' . _('Low') . ':</label> <input type="text" size="1" name="serviceargs[index_usage_critical][0]" value="' . htmlentities($serviceargs["index_usage_critical"][0]) . '" class="form-control condensed"> % &nbsp;<label>' . _('High') . ':</label> <input type="text" size="1" name="serviceargs[index_usage_critical][1]" value="' . htmlentities($serviceargs["index_usage_critical"][1]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sq" class="checkbox" name="services[slow_queries]" ' . is_checked(grab_array_var($services, "slow_queries"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sq">
                <b>' . _('Slow Queries') . '</b><br>
                ' . _('Monitor the number of slow queries') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[slow_queries_warning]" value="' . htmlentities($serviceargs["slow_queries_warning"]) . '" class="form-control condensed"> /sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[slow_queries_critical]" value="' . htmlentities($serviceargs["slow_queries_critical"]) . '" class="form-control condensed"> /sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lrp" class="checkbox" name="services[long_running_procs]" ' . is_checked(grab_array_var($services, "long_running_procs"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lrp">
                <b>' . _('Long Running Processes') . '</b><br>
                ' . _('Monitor the number of long running processes') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[long_running_procs_warning]" value="' . htmlentities($serviceargs["long_running_procs_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[long_running_procs_critical]" value="' . htmlentities($serviceargs["long_running_procs_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" id="sio" class="checkbox" name="services[slave_io_running]" ' . is_checked(grab_array_var($services, "slave_io_running"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sio">
                <b>' . _('Slave I/O') . '</b><br>
                ' . _('Checks to make sure the MySQL slave I/O is running') . '.
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" id="ssql" class="checkbox" name="services[slave_sql_running]" ' . is_checked(grab_array_var($services, "slave_sql_running"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ssql">
                <b>' . _('Slave SQL') . '</b><br>
                ' . _('Checks to make sure the MySQL slave SQL is running') . '.
            </label>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="slag" class="checkbox" name="services[slave_lag]" ' . is_checked(grab_array_var($services, "slave_lag"), "on") . '>
        </td>
        <td>
            <label class="normal" for="slag">
                <b>' . _('Slave Lag') . '</b><br>
                ' . _('Monitor the time the slave is behind the master') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[slave_lag_warning]" value="' . htmlentities($serviceargs["slave_lag_warning"]) . '" class="form-control condensed"> sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[slave_lag_critical]" value="' . htmlentities($serviceargs["slave_lag_critical"]) . '" class="form-control condensed"> sec
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
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $database = grab_array_var($inargs, "database", "");

            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "")
                $services = unserialize(base64_decode($services_serial));
            else
                $services = grab_array_var($inargs, "services");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "")
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            else
                $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
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
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mysqlserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "mysql.png",
                    "statusmap_image" => "mysql.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // common plugin opts
            $commonopts = "--hostname=" . $address . " --port=" . $port . " --username=" . $username . " --password=\"" . $password . "\" --database=" . $database . " ";

            foreach ($services as $svcvar => $svcval) {

                $pluginopts = "";
                $pluginopts .= $commonopts;

                switch ($svcvar) {

                    case "connection_time":

                        $pluginopts .= "--mode connection-time --warning " . $serviceargs["connection_time_warning"] . " --critical " . $serviceargs["connection_time_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Connection Time",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "uptime":

                        $pluginopts .= "--mode uptime --warning " . $serviceargs["uptime_warning"][0] . ":" . $serviceargs["uptime_warning"][1] . " --critical " . $serviceargs["uptime_critical"][0] . ":" . $serviceargs["uptime_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Uptime",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "threads_connected":

                        $pluginopts .= "--mode threads-connected --warning " . $serviceargs["threads_connected_warning"] . " --critical " . $serviceargs["threads_connected_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Open Connections",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "threadcache_hitrate":

                        $pluginopts .= "--mode threadcache-hitrate --warning " . $serviceargs["threadcache_hitrate_warning"][0] . ":" . $serviceargs["threadcache_hitrate_warning"][1] . " --critical " . $serviceargs["threadcache_hitrate_critical"][0] . ":" . $serviceargs["threadcache_hitrate_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Thread Cache Hit Rate",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "qcache_hitrate":

                        $pluginopts .= "--mode qcache-hitrate --warning " . $serviceargs["qcache_hitrate_warning"][0] . ":" . $serviceargs["qcache_hitrate_warning"][1] . " --critical " . $serviceargs["qcache_hitrate_critical"][0] . ":" . $serviceargs["qcache_hitrate_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Query Cache Hit Rate",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "keycache_hitrate":

                        $pluginopts .= "--mode keycache-hitrate --warning " . $serviceargs["keycache_hitrate_warning"][0] . ":" . $serviceargs["keycache_hitrate_warning"][1] . " --critical " . $serviceargs["keycache_hitrate_critical"][0] . ":" . $serviceargs["keycache_hitrate_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL MyISAM Key Cache Hit Rate",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "bufferpool_hitrate":

                        $pluginopts .= "--mode bufferpool-hitrate --warning " . $serviceargs["bufferpool_hitrate_warning"][0] . ":" . $serviceargs["bufferpool_hitrate_warning"][1] . " --critical " . $serviceargs["bufferpool_hitrate_critical"][0] . ":" . $serviceargs["bufferpool_hitrate_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL InnoDB Buffer Pool Hit Rate",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "log_waits":

                        $pluginopts .= "--mode log-waits --warning " . $serviceargs["log_waits_warning"] . " --critical " . $serviceargs["log_waits_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL InnoDB Log Waits",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "tablecache_hitrate":

                        $pluginopts .= "--mode tablecache-hitrate --warning " . $serviceargs["tablecache_hitrate_warning"][0] . ":" . $serviceargs["tablecache_hitrate_warning"][1] . " --critical " . $serviceargs["tablecache_hitrate_critical"][0] . ":" . $serviceargs["tablecache_hitrate_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Table Cache Hit Rate",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "index_usage":

                        $pluginopts .= "--mode index-usage --warning " . $serviceargs["index_usage_warning"][0] . ":" . $serviceargs["index_usage_warning"][1] . " --critical " . $serviceargs["index_usage_critical"][0] . ":" . $serviceargs["index_usage_critical"][1];

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Index Usage",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "slow_queries":

                        $pluginopts .= "--mode slow-queries --warning " . $serviceargs["slow_queries_warning"] . " --critical " . $serviceargs["slow_queries_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Slow Queries",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "long_running_procs":

                        $pluginopts .= "--mode long-running-procs --warning " . $serviceargs["long_running_procs_warning"] . " --critical " . $serviceargs["long_running_procs_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Long Running Processes",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "slave_lag":

                        $pluginopts .= "--mode slave-lag --warning " . $serviceargs["slave_lag_warning"] . " --critical " . $serviceargs["slave_lag_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Slave Lag",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "slave_io_running":

                        $pluginopts .= "--mode slave-io-running";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Slave I/O",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "slave_sql_running":

                        $pluginopts .= "--mode slave-sql-running";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MySQL Slave SQL",
                            "use" => "xiwizard_mysqlserver_service",
                            "check_command" => "check_xi_mysql_health!" . $pluginopts,
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