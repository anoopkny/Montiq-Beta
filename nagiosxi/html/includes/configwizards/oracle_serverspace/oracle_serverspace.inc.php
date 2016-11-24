<?php
//
// Oracle Serverspace Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

oracleserverspace_configwizard_init();

function oracleserverspace_configwizard_init()
{
    $name = "oracleserverspace";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.5.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor an Oracle Server"),
        CONFIGWIZARD_DISPLAYTITLE => _("Oracle Serverspace"),
        CONFIGWIZARD_FUNCTION => "oracleserverspace_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "oracleserverspace.png",
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
function oracleserverspace_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "oracleserverspace";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "1521");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");
            $sid = grab_array_var($inargs, "sid", "");

            $address = nagiosccm_replace_user_macros($address);
            $port = nagiosccm_replace_user_macros($port);
            $username = nagiosccm_replace_user_macros($username);
            $password = nagiosccm_replace_user_macros($password);

            //sanity check for wizard requirements
            $sanity = '';
            if (!file_exists('/usr/lib/oracle') && !file_exists('/usr/lib64/oracle')) {
                $sanity .= "<div class='message'><ul class='errorMessage' style='margin-top: 0;'><li><strong>WARNING:</strong> " . _('Oracle libraries do not appear to be installed. See the') . "
                <a href='https://assets.nagios.com/downloads/nagiosxi/docs/Oracle_Plugin_Installation.pdf' title='Install Instructions' target='_blank'>
                " . _('Oracle Plugin Installation') . "</a> " . _('instructions for monitoring Oracle') . "</li></ul></div>";
            }

            $output = '
<div class="sectionTitle">' . _('Oracle Serverspace') . '</div>
' . $sanity . '
<p>' . _("Specify the details for connecting to the Oracle serverspace you want to monitor.") . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _("Address:") . '</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _("The IP address or FQDNS name of the Oracle server.") . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _("Port:") . '</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="form-control">
            <div class="subtext">' . _("The port to use to connect to the Oracle server.") . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _("Sid:") . '</label>
        </td>
        <td>
            <input type="text" size="5" name="sid" id="sid" value="' . htmlentities($sid) . '" class="form-control">
            <div class="subtext">' . _("The servicename (sid) to use to connect to the Oracle server.") . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _("Username:") . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control">
            <div class="subtext">' . _("The username used to connect to the Oracle server.") . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _("Password:") . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _("The password used to connect to the Oracle server.") . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $port = grab_array_var($inargs, "port", "");
            $username = grab_array_var($inargs, "username", "");
            $sid = grab_array_var($inargs, "sid", "");
            $password = grab_array_var($inargs, "password", "");

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

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $port = grab_array_var($inargs, "port", "");
            $sid = grab_array_var($inargs, "sid", "");
            $username = grab_array_var($inargs, "username", "");
            $password = grab_array_var($inargs, "password", "");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services = grab_array_var($inargs, "services", array(
                "connection-time" => "on",
                "connected-users" => "on",
                "sga-data-buffer-hit-ratio" => "on",
                "sga-library-cache-hit-ratio" => "on",
                "sga-dictionary-cache-hit-ratio" => "on",
                "sga-latches-hit-ratio" => "on",
                "sga-shared-pool-reload-ratio" => "on",
                "sga-shared-pool-free" => "on",
                "pga-in-memory-sort-ratio" => "on",
                "soft-parse-ratio" => "on",
                "retry-ratio" => "on",
                "redo-io-traffic" => "on",
                "roll-header-contention" => "on",
                "roll-block-contention" => "on",
                "roll-hit-ratio" => "on",
                "roll-wraps" => "on",
                "roll-extends" => "on",
                "flash-recovery-area-usage" => "on",
            ));
            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "connection-time_warning" => "1",
                "connection-time_critical" => "5",
                "connected-users_warning" => "50",
                "connected-users_critical" => "100",
                "sga-data-buffer-hit-ratio_warning" => "98:",
                "sga-data-buffer-hit-ratio_critical" => "95:",
                "sga-library-cache-hit-ratio_warning" => "98:",
                "sga-library-cache-hit-ratio_critical" => "95:",
                "sga-dictionary-cache-hit-ratio_warning" => "98:",
                "sga-dictionary-cache-hit-ratio_critical" => "95:",
                "sga-latches-hit-ratio_warning" => "98:",
                "sga-latches-hit-ratio_critical" => "95:",
                "sga-shared-pool-reload-ratio_warning" => "1",
                "sga-shared-pool-reload-ratio_critical" => "10",
                "sga-shared-pool-free_warning" => "10:",
                "sga-shared-pool-free_critical" => "5:",
                "pga-in-memory-sort-ratio_warning" => "99:",
                "pga-in-memory-sort-ratio_critical" => "90:",
                "soft-parse-ratio_warning" => "98:",
                "soft-parse-ratio_critical" => "90:",
                "retry-ratio_warning" => "1",
                "retry-ratio_critical" => "10",
                "redo-io-traffic_warning" => "100",
                "redo-io-traffic_critical" => "200",
                "roll-header-contention_warning" => "1",
                "roll-header-contention_critical" => "2",
                "roll-block-contention_warning" => "1",
                "roll-block-contention_critical" => "2",
                "roll-hit-ratio_warning" => "99:",
                "roll-hit-ratio_critical" => "98:",
                "roll-wraps_warning" => "1",
                "roll-wraps_critical" => "100",
                "roll-extends_warning" => "1",
                "roll-extends_critical" => "100",
                "flash-recovery-area-usage_warning" => "90",
                "flash-recovery-area-usage_critical" => "98",
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
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="sid" value="' . htmlentities($sid) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">

<h5 class="ul">' . _("Oracle Server") . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _("Address:") . '</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _("Host Name:") . '</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _("The name you\'d like to have associated with this Oracle Tablespace.") . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _("Port:") . '</label>
        </td>
        <td>
            <input type="text" size="5" name="port" id="port" value="' . htmlentities($port) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _("Sid:") . '</label>
        </td>
        <td>
            <input type="text" size="5" name="sid" id="sid" value="' . htmlentities($sid) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _("Username:") . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _("Password:") . '</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _("Oracle Serverspace Metrics") . '</h5>
<p>' . _("Specify the metrics you'd like to monitor on the Oracle Serverspace.") . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <input type="checkbox" id="ct" class="checkbox" name="services[connection-time]" ' . is_checked(grab_array_var($services, "connection-time"), "on") . '>
        </td>
        <td>
            <label class="normal" for="ct">
                <b>' . _('Connection Time') . '</b><br>
                ' . _("Monitor amount time it takes to connect to the Oracle Server.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[connection-time_warning]" value="' . htmlentities($serviceargs["connection-time_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[connection-time_critical]" value="' . htmlentities($serviceargs["connection-time_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="cu" class="checkbox" name="services[connected-users]" ' . is_checked(grab_array_var($services, "connected-users"), "on") . '>
        </td>
        <td>
            <label class="normal" for="cu">
                <b>' . _("Connected Users") . '</b><br>
                ' . _("Monitor amount of connected users.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[connected-users_warning]" value="' . htmlentities($serviceargs["connected-users_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[connected-users_critical]" value="' . htmlentities($serviceargs["connected-users_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sgadb" class="checkbox" name="services[sga-data-buffer-hit-ratio]" ' . is_checked(grab_array_var($services, "sga-data-buffer-hit-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sgadb">
                <b>' . _("SGA Data Buffer Hit Ratio") . '</b><br>
                ' . _("Monitor the SGA Data Buffer.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-data-buffer-hit-ratio_warning]" value="' . htmlentities($serviceargs["sga-data-buffer-hit-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-data-buffer-hit-ratio_critical]" value="' . htmlentities($serviceargs["sga-data-buffer-hit-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sgalc" class="checkbox" name="services[sga-library-cache-hit-ratio]" ' . is_checked(grab_array_var($services, "sga-library-cache-hit-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sgalc">
                <b>' . _("SGA Library Cache Hit Ratio") . '</b><br>
                ' . _("Monitor the Library Cache.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-library-cache-hit-ratio_warning]" value="' . htmlentities($serviceargs["sga-library-cache-hit-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-library-cache-hit-ratio_critical]" value="' . htmlentities($serviceargs["sga-library-cache-hit-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sgadchr" class="checkbox" name="services[sga-dictionary-cache-hit-ratio]" ' . is_checked(grab_array_var($services, "sga-dictionary-cache-hit-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sgadchr">
                <b>' . _("SGA Dictionary Cache Hit Ratio") . '</b><br>
                ' . _("Monitor the SGA Dictionary.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-dictionary-cache-hit-ratio_warning]" value="' . htmlentities($serviceargs["sga-dictionary-cache-hit-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-dictionary-cache-hit-ratio_critical]" value="' . htmlentities($serviceargs["sga-dictionary-cache-hit-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sgasp" class="checkbox" name="services[sga-shared-pool-reload-ratio]" ' . is_checked(grab_array_var($services, "sga-shared-pool-reload-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sgasp">
                <b>' . _("SGA Shared Pool Reload Ratio") . '</b><br>
                ' . _("Monitor the SGA Shared Pool.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[sga-shared-pool-reload-ratio_warning]" value="' . htmlentities($serviceargs["sga-shared-pool-reload-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[sga-shared-pool-reload-ratio_critical]" value="' . htmlentities($serviceargs["sga-shared-pool-reload-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="sgaspf" class="checkbox" name="services[sga-shared-pool-free]" ' . is_checked(grab_array_var($services, "sga-shared-pool-free"), "on") . '>
        </td>
        <td>
            <label class="normal" for="sgaspf">
                <b>' . _("SGA Shared Pool Free") . '</b><br>
                ' . _("Monitor the SGA Shared Pool.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-shared-pool-free_warning]" value="' . htmlentities($serviceargs["sga-shared-pool-free_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-shared-pool-free_critical]" value="' . htmlentities($serviceargs["sga-shared-pool-free_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="pgaimsr" class="checkbox" name="services[pga-in-memory-sort-ratio]" ' . is_checked(grab_array_var($services, "pga-in-memory-sort-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="pgaimsr">
                <b>' . _("PGA In Memory Sort Ratio") . '</b><br>
                ' . _("Monitor the PGA Memory.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[pga-in-memory-sort-ratio_warning]" value="' . htmlentities($serviceargs["pga-in-memory-sort-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[pga-in-memory-sort-ratio_critical]" value="' . htmlentities($serviceargs["pga-in-memory-sort-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="spr" class="checkbox" name="services[soft-parse-ratio]" ' . is_checked(grab_array_var($services, "soft-parse-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="spr">
                <b>' . _("Soft Parse Ratio") . '</b><br>
                ' . _("Monitor the amount of soft parses.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[soft-parse-ratio_warning]" value="' . htmlentities($serviceargs["soft-parse-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[soft-parse-ratio_critical]" value="' . htmlentities($serviceargs["soft-parse-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rr" class="checkbox" name="services[retry-ratio]" ' . is_checked(grab_array_var($services, "retry-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rr">
                <b>' . _("Retry Ratio") . '</b><br>
                ' . _("Monitor redo buffer retries.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[retry-ratio_warning]" value="' . htmlentities($serviceargs["retry-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[retry-ratio_critical]" value="' . htmlentities($serviceargs["retry-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td clsas="vt">
            <input type="checkbox" id="riot" class="checkbox" name="services[redo-io-traffic]" ' . is_checked(grab_array_var($services, "redo-io-traffic"), "on") . '>
        </td>
        <td>
            <label class="normal" for="riot">
                <b>' . _("Redo I/O Traffic") . '</b><br>
                ' . _("Monitor the amount of I/O traffic from redos.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="2" name="serviceargs[redo-io-traffic_warning]" value="' . htmlentities($serviceargs["redo-io-traffic_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="2" name="serviceargs[redo-io-traffic_critical]" value="' . htmlentities($serviceargs["redo-io-traffic_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rhc" class="checkbox" name="services[roll-header-contention]" ' . is_checked(grab_array_var($services, "roll-header-contention"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rhc">
                <b>' . _("Roll Header Contention") . '</b><br>
                ' . _("Monitor contention for writes for the roll header.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-header-contention_warning]" value="' . htmlentities($serviceargs["roll-header-contention_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-header-contention_critical]" value="' . htmlentities($serviceargs["roll-header-contention_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rbc" class="checkbox" name="services[roll-block-contention]" ' . is_checked(grab_array_var($services, "roll-block-contention"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rbc">
                <b>' . _("Roll Block Contention") . '</b><br>
                ' . _("Monitor the roll block contention.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-block-contention_warning]" value="' . htmlentities($serviceargs["roll-block-contention_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-block-contention_critical]" value="' . htmlentities($serviceargs["roll-block-contention_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rhr" class="checkbox" name="services[roll-hit-ratio]" ' . is_checked(grab_array_var($services, "roll-hit-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rhr">
                <b>' . _("Roll Hit Ratio") . '</b><br>
                ' . _("Monitor the roll hit.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[roll-hit-ratio_warning]" value="' . htmlentities($serviceargs["roll-hit-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[roll-hit-ratio_critical]" value="' . htmlentities($serviceargs["roll-hit-ratio_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="rw" class="checkbox" name="services[roll-wraps]" ' . is_checked(grab_array_var($services, "roll-wraps"), "on") . '>
        </td>
        <td>
            <label class="normal" for="rw">
                <b>' . _("Roll Wraps") . '</b><br>
                ' . _("Monitor the roll wraps.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-wraps_warning]" value="' . htmlentities($serviceargs["roll-wraps_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-wraps_critical]" value="' . htmlentities($serviceargs["roll-wraps_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="re" class="checkbox" name="services[roll-extends]" ' . is_checked(grab_array_var($services, "roll-extends"), "on") . '>
        </td>
        <td>
            <label class="normal" for="re">
                <b>' . _("Roll Extends") . '</b><br>
                ' . _("Monitor the roll extends.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-extends_warning]" value="' . htmlentities($serviceargs["roll-extends_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[roll-extends_critical]" value="' . htmlentities($serviceargs["roll-extends_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="frau" class="checkbox" name="services[flash-recovery-area-usage]" ' . is_checked(grab_array_var($services, "flash-recovery-area-usage"), "on") . '>
        </td>
        <td>
            <label class="normal" for="frau">
                <b>' . _("Flash Recovery Area Usage") . '</b><br>
                ' . _("Monitor the flash recovery area.") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="1" name="serviceargs[flash-recovery-area-usage_warning]" value="' . htmlentities($serviceargs["flash-recovery-area-usage_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="1" name="serviceargs[flash-recovery-area-usage_critical]" value="' . htmlentities($serviceargs["flash-recovery-area-usage_critical"]) . '" class="form-control condensed">
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <input type="checkbox" id="lhr2" class="checkbox" name="services[sga-latches-hit-ratio]" ' . is_checked(grab_array_var($services, "sga-latches-hit-ratio"), "on") . '>
        </td>
        <td>
            <label class="normal" for="lhr2">
                <b>' . _("SGA Latches Hit Ratio") . '</b><br>
                ' . _("Monitor SGA Latches .") . '
            </label>
            <div class="pad-t5">
                <label><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-latches-hit-ratio_warning]" value="' . htmlentities($serviceargs["sga-latches-hit-ratio_warning"]) . '" class="form-control condensed">&nbsp;&nbsp;<label><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"></label> <input type="text" size="3" name="serviceargs[sga-latches-hit-ratio_critical]" value="' . htmlentities($serviceargs["sga-latches-hit-ratio_critical"]) . '" class="form-control condensed">
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

            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            $sid = grab_array_var($inargs, "sid", "");

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
            $sid = grab_array_var($inargs, "sid", "");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="port" value="' . htmlentities($port) . '">
        <input type="hidden" name="username" value="' . htmlentities($username) . '">
        <input type="hidden" name="password" value="' . htmlentities($password) . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        <input type="hidden" name="sid" value="' . htmlentities($sid) . '">
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

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            $sid = grab_array_var($inargs, "sid", "");
            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["port"] = $port;
            $meta_arr["username"] = $username;
            $meta_arr["password"] = $password;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            $meta_arr["sid"] = $sid;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);
            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_oracleserverspace_host",
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
                $add_to_description = "";
            } else {
                $commonopts = "--connect '{$address}:{$port}/{$sid}' --username '{$username}' --password '{$password}' ";
                $add_to_description = $sid . " ";
            }

            foreach ($services as $svcvar => $svcval) {

                $pluginopts = "";
                $pluginopts .= $commonopts;

                switch ($svcvar) {

                    case "connection-time":

                        $pluginopts .= "--mode connection-time  --warning " . $serviceargs["connection-time_warning"] . " --critical " . $serviceargs["connection-time_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Connection Time",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "connected-users":

                        $pluginopts .= "--mode connected-users  --warning " . $serviceargs["connected-users_warning"] . " --critical " . $serviceargs["connected-users_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Connected Users",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sga-data-buffer-hit-ratio":

                        $pluginopts .= "--mode sga-data-buffer-hit-ratio  --warning " . $serviceargs["sga-data-buffer-hit-ratio_warning"] . " --critical " . $serviceargs["sga-data-buffer-hit-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "SGA Data Buffer Hit Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sga-library-cache-hit-ratio":

                        $pluginopts .= "--mode sga-library-cache-gethit-ratio  --warning " . $serviceargs["sga-library-cache-hit-ratio_warning"] . " --critical " . $serviceargs["sga-library-cache-hit-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "SGA Library Cache Hit Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;


                    case "sga-dictionary-cache-hit-ratio":

                        $pluginopts .= "--mode sga-dictionary-cache-hit-ratio  --warning " . $serviceargs["sga-dictionary-cache-hit-ratio_warning"] . " --critical " . $serviceargs["sga-dictionary-cache-hit-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "SGA Dictionary Cache Hit Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sga-latches-hit-ratio":

                        $pluginopts .= "--mode sga-latches-hit-ratio  --warning " . $serviceargs["sga-latches-hit-ratio_warning"] . " --critical " . $serviceargs["sga-latches-hit-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "SGA Latch Hit Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sga-shared-pool-reload-ratio":

                        $pluginopts .= "--mode sga-shared-pool-reload-ratio  --warning " . $serviceargs["sga-shared-pool-reload-ratio_warning"] . " --critical " . $serviceargs["sga-shared-pool-reload-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "SGA Shared Pool Reload Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "sga-shared-pool-free":

                        $pluginopts .= "--mode sga-shared-pool-free  --warning " . $serviceargs["sga-shared-pool-free_warning"] . " --critical " . $serviceargs["sga-shared-pool-free_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "SGA Shared Pool Free",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "pga-in-memory-sort-ratio":

                        $pluginopts .= "--mode pga-in-memory-sort-ratio  --warning " . $serviceargs["pga-in-memory-sort-ratio_warning"] . " --critical " . $serviceargs["pga-in-memory-sort-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "PGA In Memory Sort Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "soft-parse-ratio":

                        $pluginopts .= "--mode soft-parse-ratio  --warning " . $serviceargs["soft-parse-ratio_warning"] . " --critical " . $serviceargs["soft-parse-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Soft Parse Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "retry-ratio":

                        $pluginopts .= "--mode retry-ratio  --warning " . $serviceargs["retry-ratio_warning"] . " --critical " . $serviceargs["retry-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Retry Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "redo-io-traffic":

                        $pluginopts .= "--mode redo-io-traffic  --warning " . $serviceargs["redo-io-traffic_warning"] . " --critical " . $serviceargs["redo-io-traffic_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Redo I/O Traffic",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "roll-header-contention":

                        $pluginopts .= "--mode roll-header-contention  --warning " . $serviceargs["roll-header-contention_warning"] . " --critical " . $serviceargs["roll-header-contention_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Roll Header Contention",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "roll-block-contention":

                        $pluginopts .= "--mode roll-block-contention  --warning " . $serviceargs["roll-block-contention_warning"] . " --critical " . $serviceargs["roll-block-contention_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Roll Block Contention",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "roll-hit-ratio":

                        $pluginopts .= "--mode roll-hit-ratio  --warning " . $serviceargs["roll-hit-ratio_warning"] . " --critical " . $serviceargs["roll-hit-ratio_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Roll Hit Ratio",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "roll-wraps":

                        $pluginopts .= "--mode roll-wraps --warning " . $serviceargs["roll-wraps_warning"] . " --critical " . $serviceargs["roll-wraps_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Roll Wraps",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "roll-extends":

                        $pluginopts .= "--mode roll-extends --warning " . $serviceargs["roll-extends_warning"] . " --critical " . $serviceargs["roll-extends_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Roll Extends",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
                            "_xiwizard" => $wizard_name,
                        );
                        break;

                    case "flash-recovery-area-usage":

                        $pluginopts .= "--mode flash-recovery-area-usage  --warning " . $serviceargs["flash-recovery-area-usage_warning"] . " --critical " . $serviceargs["flash-recovery-area-usage_critical"] . "";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $add_to_description . "Flash Recovery Area Usage",
                            "use" => "xiwizard_oracleserverspace_service",
                            "check_command" => "check_xi_oracleserverspace!" . $pluginopts,
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