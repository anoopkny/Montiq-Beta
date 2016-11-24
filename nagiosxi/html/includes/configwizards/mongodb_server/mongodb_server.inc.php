<?php
//
// MongoDB Server Config Wizard
// Copyright (c) 2013-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

mongodbserver_configwizard_init();

function mongodbserver_configwizard_init()
{
    $name = "mongodbserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.4",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a MongoDB Server"),
        CONFIGWIZARD_DISPLAYTITLE => _("MongoDB Server"),
        CONFIGWIZARD_FUNCTION => "mongodbserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mongodb.png",
        CONFIGWIZARD_FILTER_GROUPS => array('database'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @return bool
 */
function mongodbserver_configwizard_check_prereqs()
{
    // Plugin doesn't exist
    if (!file_exists("/usr/local/nagios/libexec/check_mongodb.py")) {
        return false;
    }

    // Run the plugin to see if pymongo is installed
    $retval = 0;
    $cmdline = "/usr/local/nagios/libexec/check_mongodb.py | head --lines=1";
    $output = exec($cmdline);

    if (strstr($output, "No module named pymongo"))
        return false;

    return true;
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function mongodbserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mongodbserver";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            if (mongodbserver_configwizard_check_prereqs() == false) {
                $output .= '<p><b>' . _('Error') . ':</b> ' . _('It looks like you are missing pymongo on your Nagios XI server.') . '</p><p>' . _('To use this wizard you must install pymongo on your server. If you are using CentOS or RHEL you can run "yum install pymongo".') . '</p>';
            } else {

                $address = grab_array_var($inargs, "address", "");
                $port = grab_array_var($inargs, "port", "27017");
                $username = grab_array_var($inargs, "username", "");
                $password = grab_array_var($inargs, "password", "");

                // Swap out eligible user macros if detected
                $address = nagiosccm_replace_user_macros($address);
                $port = nagiosccm_replace_user_macros($port);
                $username = nagiosccm_replace_user_macros($username);
                $password = nagiosccm_replace_user_macros($password);

                if (!empty($services_serial))
                    $services = unserialize(base64_decode($services_serial));
                $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
                if (!empty($serviceargs_serial)) {
                    $serviceargs = unserialize(base64_decode($serviceargs_serial));
                }

                $output = '
<h5 class="ul">' . _('MongoDB Server') . '</h5>
<p>' . _('Specify the details for connecting to the MongoDB server you want to monitor') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the MongoDB server') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control">
            <div class="subtext">' . _('The port to use to connect to the MongoDB server. This defaults to port 27017, however if you changed the port number enter it here') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="textfield form-control">
            <div class="subtext">' . _('An authenticated user\'s username') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password for the authenticed user') . '.</div>
        </td>
    </tr> 
</table>';
            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = "No address specified.";
            if (have_value($username) == false)
                $errmsg[$errors++] = "No username specified.";
            if (have_value($password) == false)
                $errmsg[$errors++] = "No password specified.";

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

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);


            $services = grab_array_var($inargs, "services", array(
                "check_connection" => "on",
                "free_connections" => "on",
                "memory_usage" => "on",
                "mapped_memory" => "off",
                "lock_time" => "on",
                "flush_average" => "off",
                "last_flush" => "off",
                "index_miss_ratio" => "off",
                "num_dbs" => "on",
                "num_collections" => "on",
                "num_queries" => "on",
                "rep_lag" => "off",
                "rep_lag_percent" => "off",
                "rep_state" => "off"
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "check_connection_warning" => "2",
                "check_connection_critical" => "4",
                "free_connections_warning" => "70",
                "free_connections_critical" => "85",
                "memory_usage_warning" => "1",
                "memory_usage_critical" => "2",
                "mapped_memory_warning" => "1",
                "mapped_memory_critical" => "2",
                "lock_time_warning" => "5",
                "lock_time_critical" => "10",
                "flush_average_warning" => "100",
                "flush_average_critical" => "200",
                "last_flush_warning" => "200",
                "last_flush_critical" => "400",
                "index_miss_ratio_warning" => "0.005",
                "index_miss_ratio_critical" => "0.01",
                "num_dbs_warning" => "300",
                "num_dbs_critical" => "500",
                "num_collections_warning" => "300",
                "num_collections_critical" => "500",
                "num_queries_warning" => "150",
                "num_queries_critical" => "200",
                "rep_lag_warning" => "15",
                "rep_lag_critical" => "30",
                "rep_lag_percent_warning" => "50",
                "rep_lag_percent_critical" => "75",
                "rep_state_warning" => "0",
                "rep_state_critical" => "0"
            ));

            // Replace defaults with the given values
            $services_serial = grab_array_var($inargs, "services_serial");
            if (!empty($services_serial))
                $services = unserialize(base64_decode($services_serial));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if (!empty($serviceargs_serial)) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="port" value="' . htmlentities($port) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">
    
<h5 class="ul">' . _('MongoDB Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>Address:</label>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this MongoDB Database') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="port" id="port" value="' . htmlentities($username) . '" class="textfield form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('MongoDB Server Metrics') . '</h5>
<p>' . _('Specify the metrics you\'d like to monitor on the MongoDB Server') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="cc" class="checkbox" name="services[check_connection]" ' . is_checked(grab_array_var($services, "check_connection"), "on") . '>
        </td>
        <td>
            <label class="normal" for="cc">
                <b>' . _('Check Connection') . '</b><br>
                ' . _('Monitor the connection to host') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[check_connection_warning]" value="' . htmlentities($serviceargs["check_connection_warning"]) . '" class="form-control condensed"> sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[check_connection_critical]" value="' . htmlentities($serviceargs["check_connection_critical"]) . '" class="form-control condensed"> sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="fc" class="checkbox" name="services[free_connections]" ' . is_checked(grab_array_var($services, "free_connections"), "on") . '>
        </td>
        <td>
            <label class="normal" for="fc">
                <b>' . _('Free Connections') . '</b><br>
                ' . _('Monitor the percent of free connections available') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[free_connections_warning]" value="' . htmlentities($serviceargs["free_connections_warning"]) . '" class="form-control condensed"> %&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[free_connections_critical]" value="' . htmlentities($serviceargs["free_connections_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="mu" class="checkbox" name="services[memory_usage]" ' . is_checked(grab_array_var($services, "memory_usage"), "on") . '>
        </td>
        <td>
            <label class="normal" for="mu">
                <b>' . _('Memory Usage') . '</b><br>
                ' . _('Monitor the MongoDB server\'s memory usage') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[memory_usage_warning]" value="' . htmlentities($serviceargs["memory_usage_warning"]) . '" class="form-control condensed"> GB&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[memory_usage_critical]" value="' . htmlentities($serviceargs["memory_usage_critical"]) . '" class="form-control condensed"> GB
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="mmu" class="checkbox" name="services[mapped_memory]" ' . is_checked(grab_array_var($services, "mapped_memory"), "on") . '>
        </td>
        <td>
            <label class="normal" for="mmu">
                <b>' . _('Mapped Memory Usage') . '</b><br>
                ' . _('Monitor the mapped memory usage of the MongoDB server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[mapped_memory_warning]" value="' . htmlentities($serviceargs["mapped_memory_warning"]) . '" class="form-control condensed"> GB&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[mapped_memory_critical]" value="' . htmlentities($serviceargs["mapped_memory_critical"]) . '" class="form-control condensed"> GB
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="ltp" class="checkbox" name="services[lock_time]" ' . is_checked(grab_array_var($services, "lock_time"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ltp">
                <b>' . _('Lock Time Percent') . '</b><br>
                ' . _('Monitor the percent of time the MongoDB server is locked') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[lock_time_warning]" value="' . htmlentities($serviceargs["lock_time_warning"]) . '" class="form-control condensed"> %&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[lock_time_critical]" value="' . htmlentities($serviceargs["lock_time_critical"]) . '" class="form-control condensed"> %
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="aft" class="checkbox" name="services[flush_average]" ' . is_checked(grab_array_var($services, "flush_average"), "on") . '>
        </td>
        <td>
            <label class="normal" for="aft">
                <b>' . _('Average Flush Time') . '</b><br>
                ' . _('Monitor the average time it takes to preform a flush') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[flush_average_warning]" value="' . htmlentities($serviceargs["flush_average_warning"]) . '" class="form-control condensed"> ms&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[flush_average_critical]" value="' . htmlentities($serviceargs["flush_average_critical"]) . '" class="form-control condensed"> ms
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lft" class="checkbox" name="services[last_flush]" ' . is_checked(grab_array_var($services, "last_flush"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lft">
                <b>' . _('Last Flush Time') . '</b><br>
                ' . _('Monitor the time since the last flush') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[last_flush_warning]" value="' . htmlentities($serviceargs["last_flush_warning"]) . '" class="form-control condensed"> ms&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[last_flush_critical]" value="' . htmlentities($serviceargs["last_flush_critical"]) . '" class="form-control condensed"> ms
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="imr" class="checkbox" name="services[index_miss_ratio]" ' . is_checked(grab_array_var($services, "index_miss_ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="imr">
                <b>' . _('Index Miss Ratio') . '</b><br>
                ' . _('Monitor ratio of index hits to misses') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[index_miss_ratio_warning]" value="' . htmlentities($serviceargs["index_miss_ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[index_miss_ratio_critical]" value="' . htmlentities($serviceargs["index_miss_ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="nd" class="checkbox" name="services[num_dbs]" ' . is_checked(grab_array_var($services, "num_dbs"), "on") . '>
        </td>
        <td>
            <label class="normal" for="nd">
                <b>' . _('Number of Databases') . '</b><br>
                ' . _('Monitor the number of databases') . '.
            </div>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[num_dbs_warning]" value="' . htmlentities($serviceargs["num_dbs_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[num_dbs_critical]" value="' . htmlentities($serviceargs["num_dbs_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="nc" class="checkbox" name="services[num_collections]" ' . is_checked(grab_array_var($services, "num_collections"), "on") . '>
        </td>
        <td>
            <label class="normal" for="nc">
                <b>' . _('Number of Collections') . '</b><br>
                ' . _('Monitor the number of collections') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[num_collections_warning]" value="' . htmlentities($serviceargs["num_collections_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[num_collections_critical]" value="' . htmlentities($serviceargs["num_collections_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="qps" class="checkbox" name="services[num_queries]" ' . is_checked(grab_array_var($services, "num_queries"), "on") . '>
        </td>
        <td>
            <label class="normal" for="qps">
                <b>' . _('Queries Per Second') . '</b><br>
                ' . _('Monitor the amount of queries per second') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[num_queries_warning]" value="' . htmlentities($serviceargs["num_queries_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[num_queries_critical]" value="' . htmlentities($serviceargs["num_queries_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('MongoDB Server Replication Metrics') . '</h5>
<p>' . _('These options require you to be using MongoDBs replication features. If replication isn\'t set up you will recieve errors') . '.</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td>
            <input type="checkbox" id="rep" class="checkbox" name="services[rep_state]" ' . is_checked(grab_array_var($services, "rep_state"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rep">
                <b>' . _('Replication State') . '</b><br>
                ' . _('Monitor the replication state') . '. ' . _('This check doesn\'t require any arguments for warning/critical') . '.
            </label>
        </td>
    </tr>

    <tr>
        <td class="vt">
            <input type="checkbox" id="rlag" class="checkbox" name="services[rep_lag]" ' . is_checked(grab_array_var($services, "rep_lag"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rlag">
                <b>' . _('Replication Lag') . '</b><br>
                ' . _('Monitor replication lag of the server') . '. ' . _('This check may show apparent lag of < 10 sec even if there isn\'t lag') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[rep_lag_warning]" value="' . htmlentities($serviceargs["rep_lag_warning"]) . '" class="form-control condensed"> sec&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[rep_lag_critical]" value="' . htmlentities($serviceargs["rep_lag_critical"]) . '" class="form-control condensed"> sec
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rlagp" class="checkbox" name="services[rep_lag_percent]" ' . is_checked(grab_array_var($services, "rep_lag_percent"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rlagp">
                <b>' . _('Replication Lag Percent') . '</b><br>
                ' . _('Monitor the percent of replication lag on the server') . '.
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[rep_lag_percent_warning]" value="' . htmlentities($serviceargs["rep_lag_percent_warning"]) . '" class="form-control condensed"> %&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[rep_lag_percent_critical]" value="' . htmlentities($serviceargs["rep_lag_percent_critical"]) . '" class="form-control condensed"> %
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
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");

            $services = grab_array_var($inargs, "services", array());
            if (empty($services)) {
                $services_serial = grab_array_var($inargs, "services_serial");
            } else {
                $services_serial = base64_encode(serialize($services));
            }
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            if (empty($serviceargs)) {
                $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            } else {
                $serviceargs_serial = base64_encode(serialize($serviceargs));
            }

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '" />
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
        <input type="hidden" name="port" value="' . htmlentities($port) . '" />
        <input type="hidden" name="username" value="' . htmlentities($username) . '" />
        <input type="hidden" name="password" value="' . htmlentities($password) . '" />
        <input type="hidden" name="services_serial" value="' . $services_serial . '" />
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '" />
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");

            $services = grab_array_var($inargs, "services", array());
            if (empty($services)) {
                $services_serial = grab_array_var($inargs, "services_serial");
            } else {
                $services_serial = base64_encode(serialize($services));
            }
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            if (empty($serviceargs)) {
                $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            } else {
                $serviceargs_serial = base64_encode(serialize($serviceargs));
            }

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '" />
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
        <input type="hidden" name="port" value="' . htmlentities($port) . '" />
        <input type="hidden" name="username" value="' . htmlentities($username) . '" />
        <input type="hidden" name="password" value="' . htmlentities($password) . '" />
        <input type="hidden" name="services_serial" value="' . $services_serial . '" />
        <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '" />
            
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["port"] = $port;
            $meta_arr["username"] = $username;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_mongodbserver_host",
                    "host_name" => $hostname,
                    "address" => $address,
                    "icon_image" => "mongodb.png",
                    "statusmap_image" => "mongodb.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            foreach ($services as $svcvar => $svcval) {

                switch ($svcvar) {

                    case "check_connection":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Connection",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!connect!" . $port . "!" . $serviceargs["check_connection_warning"] . "!" . $serviceargs["check_connection_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "free_connections":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Free Connections",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!connections!" . $port . "!" . $serviceargs["free_connections_warning"] . "!" . $serviceargs["free_connections_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "memory_usage":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Memory Usage",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!memory!" . $port . "!" . $serviceargs["memory_usage_warning"] . "!" . $serviceargs["memory_usage_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "mapped_memory":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Mapped Memory Usage",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!memory_mapped!" . $port . "!" . $serviceargs["mapped_memory_warning"] . "!" . $serviceargs["mapped_memory_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "lock_time":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Lock Time",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!lock!" . $port . "!" . $serviceargs["lock_time_warning"] . "!" . $serviceargs["lock_time_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "flush_average":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Flush Time Average",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!flushing!" . $port . "!" . $serviceargs["flush_average_warning"] . "!" . $serviceargs["flush_average_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "last_flush":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Last Flush Time",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!last_flush_time!" . $port . "!" . $serviceargs["last_flush_warning"] . "!" . $serviceargs["last_flush_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "index_miss_ratio":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Index Miss Ratio",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!index_miss_ratio!" . $port . "!" . $serviceargs["index_miss_ratio_warning"] . "!" . $serviceargs["index_miss_ratio_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "num_dbs":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Databases",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!databases!" . $port . "!" . $serviceargs["num_dbs_warning"] . "!" . $serviceargs["num_dbs_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "num_collections":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Collections",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!collections!" . $port . "!" . $serviceargs["num_collections_warning"] . "!" . $serviceargs["num_collections_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "num_queries":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Queries Per Second",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!queries_per_second!" . $port . "!" . $serviceargs["num_queries_warning"] . "!" . $serviceargs["num_queries_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "rep_lag":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Replication Lag",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!replication_lag!" . $port . "!" . $serviceargs["rep_lag_warning"] . "!" . $serviceargs["rep_lag_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "rep_lag_percent":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Replication Lag Percentage",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!replication_lag_percent!" . $port . "!" . $serviceargs["rep_lag_percent_warning"] . "!" . $serviceargs["rep_lag_percent_critical"] . "!" . $username . "!" . $password,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "rep_state":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => "MongoDB Replicaset State",
                            "use" => "xiwizard_mongodbserver_service",
                            "check_command" => "check_mongodb_server!replset_state!" . $port . "!" . $serviceargs["rep_state_warning"] . "!" . $serviceargs["rep_state_critical"] . "!" . $username . "!" . $password,
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