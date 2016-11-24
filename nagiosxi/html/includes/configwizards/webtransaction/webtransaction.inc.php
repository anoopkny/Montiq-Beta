<?php
//
// Web Transaction Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id$
//
// TODO
// * Read timeout and global timeout values from last run (look at config.xml file)

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

webtransaction_configwizard_init();

function webtransaction_configwizard_init()
{
    $name = "webtransaction";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a synthentic web transaction."),
        CONFIGWIZARD_DISPLAYTITLE => _("Web Transaction"),
        CONFIGWIZARD_FUNCTION => "webtransaction_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "whirl.png",
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
function webtransaction_configwizard_func($mode = "", $inargs, &$outargs, &$result)
{
    $wizard_name = "webtransaction";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $url = grab_array_var($inargs, "url", "http://");
            $url = nagiosccm_replace_user_macros($url);

            $servicename = grab_array_var($inargs, "servicename", "Web Transaction");

            $output = '
<h5 class="ul">' . _('Web Information') . '</h5>
<p>' . _('Monitoring a synthentic web transaction is a process which may involve several steps, including the submission and processing of data.  Transaction logic is handled using') . ' <a href="http://www.webinject.org/plugin.html" target="_blank">WebInject</a>, ' . _('so you must be familiar with its syntax before monitoring a transaction.') . ' </p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Transaction Name') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="servicename" id="servicename" value="' . htmlentities($servicename) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this synthetic transaction test.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Primary URL') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="form-control">
            <div class="subtext">' . _('The primary URL that this transaction is associated with.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $servicename = grab_array_var($inargs, "servicename");

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($url) == false)
                $errmsg[$errors++] = _("No URL specified.");
            else if (!valid_url($url))
                $errmsg[$errors++] = _("Invalid URL.");
            if (is_valid_service_name($servicename) == false)
                $errmsg[$errors++] = _("Invalid transaction name.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $servicename = grab_array_var($inargs, "servicename");
            $testcasedata_serial = grab_array_var($inargs, "testcasedata_serial", "");

            $urlparts = parse_url($url);
            $hostname = $urlparts["host"];
            $urlscheme = $urlparts["scheme"];
            if ($urlscheme == "https")
                $ssl = 1;
            else
                $ssl = 0;

            $ip = gethostbyname($hostname);

            // Get existing or create new test data
            if ($testcasedata_serial != "") {
                $testcasedata = unserialize(base64_decode($testcasedata_serial));
                $timeout = grab_array_var($inargs, "timeout", 1);
                $globaltimeout = grab_array_var($inargs, "globaltimeout", 30);
            } else {
                $testcasedata_arr = webtransaction_configwizard_get_testdata($hostname, $servicename, $url);
                $testcasedata = $testcasedata_arr["testcase_data"];
                $timeout = $testcasedata_arr["timeout"];
                $globaltimeout = $testcasedata_arr["global_timeout"];
            }

            $output = '
<input type="hidden" name="url" value="' . htmlentities($url) . '">
<input type="hidden" name="servicename" value="' . htmlentities($servicename) . '">

<h5 class="ul">' . _('Transaction Host Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Primary URL') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . htmlentities($url) . '" class="form-control" disabled>
            <div class="subtext">' . _('The primary URL that this transaction is associated with.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtex">' . _('The name you\'d like to have associated with the primary URL.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('IP Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="ip" id="ip" value="' . htmlentities($ip) . '" class="form-control">
            <div class="subtext">' . _('The IP address associated with the primary URL\'s fully qualified domain name (FQDN).') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Transaction Details') . '</h5>
<p>' . _('Specify the details of how the transaction should be monitored.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Transaction Name') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="servicename" id="servicename" value="' . htmlentities($servicename) . '" class="form-control" disabled>
            <div class="subtext">' . _('The name you\'d like to have associated with this synthetic transaction test.') . '</div>
        </td>
    </tr>

    <tr>
        <td class="vt">
            <label>' . _('Test Case Data') . ':</label>
        </td>
        <td>
            <div style="margin-bottom: 5px;">
                ' . _('Transaction test case data must be formatted according to') . ' <a href="http://www.webinject.org/plugin.html" target="_blank">WebInject</a> ' . _('standards.') . '<br>
                <a href="http://www.webinject.org/manual.html#tcsetup" target="_blank">' . _('Read the') . ' WebInject ' . _('test case documentation') . '</a> ' . _('for more information on creating test case data.') . '
            </div>
            <textarea name="testcasedata" class="form-control" style="margin-bottom: 5px; width: 650px; height: 200px;">' . htmlentities($testcasedata) . '</textarea>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Timeout') . ':</label>
        </td>
        <td>
            <input type="text" size="2" name="timeout" id="timeout" value="' . htmlentities($timeout) . '" class="form-control"> ' . _('seconds') . '
            <div class="subtext">' . _('The response timeout for each test case.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Global Timeout') . ':</label>
        </td>
        <td>
            <input type="text" size="2" name="globaltimeout" id="globaltimeout" value="' . htmlentities($globaltimeout) . '" class="form-control"> ' . _('seconds') . '
            <div class="subtext">' . _('A global timeout for running all tests.  A warning message will be returned if total execution time exceeds this value.') . '</div>
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

            $hostname = nagiosccm_replace_user_macros($hostname);

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (is_valid_service_name($servicename) == false)
                $errmsg[$errors++] = _("Invalid transaction name.");
            if (have_value($url) == false)
                $errmsg[$errors++] = _("No URL specified.");
            else if (!valid_url($url))
                $errmsg[$errors++] = _("Invalid URL.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $url = grab_array_var($inargs, "url");
            $hostname = grab_array_var($inargs, "hostname");
            $servicename = grab_array_var($inargs, "servicename");
            $testcasedata = grab_array_var($inargs, "testcasedata");
            $timeout = grab_array_var($inargs, "timeout");
            $globaltimeout = grab_array_var($inargs, "globaltimeout");
            $ip = grab_array_var($inargs, "ip");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $testcasedata_serial = grab_array_var($inargs, "testcasedata_serial", base64_encode(serialize($testcasedata)));

            $output = '
            
        <input type="hidden" name="url" value="' . htmlentities($url) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="servicename" value="' . htmlentities($servicename) . '">
        <input type="hidden" name="ip" value="' . htmlentities($ip) . '">
        <input type="hidden" name="timeout" value="' . htmlentities($timeout) . '">
        <input type="hidden" name="globaltimeout" value="' . htmlentities($globaltimeout) . '">
        <input type="hidden" name="testcasedata_serial" value="' . $testcasedata_serial . '">
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
            $timeout = grab_array_var($inargs, "timeout");
            $globaltimeout = grab_array_var($inargs, "globaltimeout");

            $testcasedata_serial = grab_array_var($inargs, "testcasedata_serial", "");
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $testcasedata = unserialize(base64_decode($testcasedata_serial));
            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $urlparts = parse_url($url);
            $hostaddress = $urlparts["host"];

            // Write webinject data files
            webtransaction_configwizard_write_testdata_files($hostname, $servicename, $testcasedata, $timeout, $globaltimeout);

            // Save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["ip"] = $ip;
            $meta_arr["url"] = $url;
            $meta_arr["timeout"] = $timeout;
            $meta_arr["globaltimeout"] = $globaltimeout;
            $meta_arr["testcasedata"] = $testcasedata;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, $servicename, $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_webtransaction_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "www_server.png",
                    "statusmap_image" => "www_server.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => $servicename,
                "use" => "xiwizard_webtransaction_webinject_service",
                "check_command" => "check_xi_service_webinject!" . webtransaction_configwizard_get_testdata_filebase($hostname, $servicename) . "_config.xml",
                "_xiwizard" => $wizard_name,
            );

            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


/**
 * @param $hostname
 * @param $servicename
 * @param $url
 *
 * @return array
 */
function webtransaction_configwizard_get_testdata($hostname, $servicename, $url)
{

    $data = array(
        "testcase_data" => "",
        "timeout" => 10,
        "global_timeout" => 30,
    );

    $files = webtransaction_configwizard_get_testdata_files($hostname, $servicename, $filebase);

    if (file_exists($files["testdata_file"])) {
        $fh = fopen($files["testdata_file"], "r");
        if ($fh) {
            while (!feof($fh)) {
                $data["testcase_data"] .= fgets($fh);
            }
            fclose($fh);
        }
    }

    if ($data["testcase_data"] == "")
        $data["testcase_data"] = '<testcases repeat="1">

<case
    id="1"
    url="' . $url . '"
/>

</testcases>
';

    return $data;
}

/**
 * @param $hostname
 * @param $servicename
 *
 * @return string
 */
function webtransaction_configwizard_get_testdata_filebase($hostname, $servicename)
{
    $filebase = "";
    $filebase .= preg_replace('/[ .\:_-]/', '_', $hostname);
    $filebase .= "__";
    $filebase .= preg_replace('/[ .\:_-]/', '_', $servicename);
    return $filebase;
}

/**
 * @param $hostname
 * @param $servicename
 * @param $filebase
 *
 * @return array
 */
function webtransaction_configwizard_get_testdata_files($hostname, $servicename, &$filebase)
{

    $files = array();

    $filebase = webtransaction_configwizard_get_testdata_filebase($hostname, $servicename);

    $base = get_root_dir() . "/etc/components/webinject/";
    $base .= $filebase;

    $files["config_file"] = $base . "_config.xml";
    $files["testdata_file"] = $base . "_testdata.xml";

    //print_r($files);

    return $files;
}


/**
 * @param     $hostname
 * @param     $servicename
 * @param     $testcasedata
 * @param int $timeout
 * @param int $globaltimeout
 */
function webtransaction_configwizard_write_testdata_files($hostname, $servicename, $testcasedata, $timeout = 10, $globaltimeout = 30)
{

    $files = webtransaction_configwizard_get_testdata_files($hostname, $servicename, $filebase);

    //print_r($files);

    $fh = fopen($files["testdata_file"], "w+");
    if ($fh) {
        fputs($fh, $testcasedata);
        fclose($fh);
    }

    $fh = fopen($files["config_file"], "w+");
    if ($fh) {

        $cfgdata = "<testcasefile>" . $filebase . "_testdata.xml</testcasefile>
<useragent>" . _('WebInject Application Tester') . "</useragent>
<timeout>" . $timeout . "</timeout>
<globaltimeout>" . $globaltimeout . "</globaltimeout>
<reporttype>nagios</reporttype>";

        fputs($fh, $cfgdata);
        fclose($fh);
    }
}