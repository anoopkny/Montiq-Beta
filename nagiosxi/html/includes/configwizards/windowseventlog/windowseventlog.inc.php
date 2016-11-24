<?php
//
// Windows Event Log Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//
// $Id$

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

windowseventlog_configwizard_init();

function windowseventlog_configwizard_init()
{
    $name = "windowseventlog";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _('Monitor Windows event logs.'),
        CONFIGWIZARD_DISPLAYTITLE => _('Windows Event Log'),
        CONFIGWIZARD_FUNCTION => 'windowseventlog_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => 'windowseventlog.png',
        CONFIGWIZARD_FILTER_GROUPS => array('windows'),
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
function windowseventlog_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "windowseventlog";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $securitycheck = grab_array_var($inargs, "securitycheck", "no");

            $output = '
<h5 class="ul">' . _('Host') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the Windows machine you will be monitoring.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", array(1 => "System EventLog", 2 => "Application EventLog", 3 => "Security EventLog", 4 => "", 5 => "", 6 => ""));

            $agent32_url = "https://assets.nagios.com/downloads/addons/nageventlog/nagevlog-setup-1.9.2.exe";
            $doc_url = "https://assets.nagios.com/downloads/nagiosxi/docs/Monitoring_Windows_Event_Logs_With_NagEventLog.pdf";

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Host') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this host.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Agent Installation') . '</h5>
<p><strong>' . _('Important!') . '</strong>  ' . _('If you have not already done so, you must install the NagEventLog agent on the Windows machine.') . '</p>
<p><a href="' . $agent32_url . '"><img src="' . theme_image("download.png") . '"></a> <a href="' . $agent32_url . '"><b>' . _('Download 32-Bit Agent') . '</b></a><br></p>
<p>' . _('Note: Additional versions of the agent are available') . ' <a href="http://www.steveshipway.org/software/f_nagios.html" target="_blank">here</a>.<br>' . _('Instructions for installing and configuring the agent can be found') . ' <a href="' . $doc_url . '">' . _('here') . '</a>.
</p>
       
<h5 class="ul">' . _('Event Log Service Names') . '</h5>
<p>' . _('Specify the service names that are associated with event log filters in the NagEventLog agent on the remote Windows machine.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Service Name(s)') . ':</label>
        </td>
        <td>';

            foreach ($services as $sid => $svcname) {
                $output .= '<input type="text" size="30" name="services[' . htmlentities($sid) . ']" value="' . htmlentities($svcname) . '" class="form-control" style="margin-right: 10px;">';
            }

            $output .= '
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (!is_array($services))
                $errmsg[$errors++] = _("No service names specified.");
            else if (count($services) == 0)
                $errmsg[$errors++] = _("No service names specified.");
            else {
                $havesvc = false;
                foreach ($services as $svc) {
                    if ($svc != "")
                        $havesvc = true;
                }
                if ($havesvc == false)
                    $errmsg[$errors++] = _("No service names specified.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            $output .= '<div style="margin-bottom: 20px;">' . _('There are no monitoring options to configure with event logs. Click Next to continue.') . '</div>';

            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            //print_r($inargs);

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services");

            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '

        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="address" value="' . htmlentities($address) . '">

        <input type="hidden" name="services_serial" value="' .
                base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' .
                base64_encode(serialize($serviceargs)) . '">

        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->

            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE4OPTS:

            // hide some notification options
            $output = '';
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            $outargs[CONFIGWIZARD_HIDDEN_OPTIONS] = array(
                CONFIGWIZARD_HIDE_NOTIFICATION_DELAY,
                CONFIGWIZARD_HIDE_NOTIFICATION_INTERVAL,
            );

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs,
                "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $isvolatile = "no";
            $statestalking = "yes";

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_windowsserver_host",
                    "host_name" => $hostname,
                    "address" => $address,
                    "icon_image" => "windowsserver.png",
                    //"statusmap_image" => "windowseventlog.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            foreach ($services as $svcname) {

                if (trim($svcname) == "")
                    continue;

                //echo "PROCESSING: $hostname -> $hoststate<BR>\n";

                $objs[] = array(
                    'type' => OBJECTTYPE_SERVICE,
                    'host_name' => $hostname,
                    'service_description' => $svcname,
                    'use' => 'xiwizard_windowseventlog_service',
                    'check_interval' => 1,
                    'retry_interval' => 1,
                    'max_check_attempts' => 1,
                    'notification_interval' => 1,
                    'is_volatile' => ($isvolatile == "yes") ? 1 : 0,
                    'stalking_options' => ($statestalking == "yes") ? "o,w,u,c" : "n",
                    'icon_image' => 'windowseventlog.png',
                    '_xiwizard' => $wizard_name,
                );
            }

            // add a heartbeat service
            $objs[] = array(
                'type' => OBJECTTYPE_SERVICE,
                'host_name' => $hostname,
                'service_description' => "EventLog Agent",
                'use' => 'xiwizard_windowseventlog_service',
                'check_interval' => 1,
                'retry_interval' => 1,
                'max_check_attempts' => 1,
                'notification_interval' => 0,
                'is_volatile' => 0,
                'stalking_options' => "n",
                //'icon_image' => 'windowseventlog.png',
                '_xiwizard' => $wizard_name,
            );

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;


        // THE FOLLOWING MODES ARE POST-CONFIGURATION CALLBACKS
        // THEY CAN BE USED TO DO CONFIGURATION TASKS, ETC AFTER A NEW
        //      CONFIGURATION HAS BEEN SUBMITTED

        case CONFIGWIZARD_MODE_COMMITERROR:
            //echo "COMMITERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITCONFIGERROR:
            //echo "COMMITCONFIGERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITPERMSERROR:
            //echo "COMMITPERMSERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITOK:

            break;

        default:
            break;
    }

    return $output;
}