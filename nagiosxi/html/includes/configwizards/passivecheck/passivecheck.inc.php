<?php
//
// Passive Check Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

passivecheck_configwizard_init();

function passivecheck_configwizard_init()
{
    $name = "passivecheck";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor passive service checks and events such as security alerts."),
        CONFIGWIZARD_DISPLAYTITLE => _("Passive Check"),
        CONFIGWIZARD_FUNCTION => 'passivecheck_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => 'passivecheck.png',
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
function passivecheck_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "passivecheck";

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
<h5 class="ul">' . _('Host Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td valign="top">
            <label>' . _('Address:') . '</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the device or server associated with the passive check(s).') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Check Type:') . '</label>
        </td>
        <td>
            <select name="securitycheck" class="form-control">
                <option value="yes" ' . is_selected($securitycheck, 'yes') . '>' . _('Security-Related Check') . '</option>
                <option value="no" ' . is_selected($securitycheck, 'no') . '>' . _('Other Check Type') . '</option>
            </select>
            <div class="subtext">' . _('What type of passive check(s) are you configuring?  Your selection here will be used to set defaults on the next screen.') . '</div>
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

            $securitycheck = grab_array_var($inargs, "securitycheck", "no");
            $isvolatile = grab_array_var($inargs, "isvolatile", ($securitycheck == "yes") ? "yes" : "no");
            $statestalking = grab_array_var($inargs, "statestalking", ($securitycheck == "yes") ? "yes" : "no");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services", array(1 => ($securitycheck == "yes") ? "Security Alert" : "Passive Service", 2 => "", 3 => "", 4 => "", 5 => ""));

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="securitycheck" value="' . htmlentities($securitycheck) . '">
<input type="hidden" name="isvolatile" value="' . htmlentities($isvolatile) . '">
<input type="hidden" name="statestalking" value="' . htmlentities($statestalking) . '">

<h5 class="ul">' . _('Host Information') . '</h5>
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
            <div class="subtext">' . _('The name you\'d like to have associated with this host.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Passive Services') . '</h5>
<p>' . _('Define one or more service names that should be configured as passive services associated with the host.') . '</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Service Name(s):') . '</label>
        </td>
        <td>';

            foreach ($services as $sid => $svcname) {
                $output .= '<input type="text" size="20" style="margin-right: 10px;" name="services[' . htmlentities($sid) . ']" value="' . htmlentities($svcname) . '" class="form-control">';
            }

            $output .= '
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Service Options') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Volatility:') . '</label>
        </td>
        <td>
            <select name="version" class="form-control">
                <option value="yes" ' . is_selected($isvolatile, 'yes') . '>' . _('Volatile') . '</option>
                <option value="no" ' . is_selected($isvolatile, 'no') . '>' . _('Non-volatile') . '</option>
            </select>
            <div class="subtext">' . _('Should the service(s) be volatile?  Volatile services generate alerts each time a non-OK event is received, which can be useful when monitoring security events.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Stalking:') . '</label>
        </td>
        <td>
            <select name="version" class="form-control">
                <option value="yes" ' . is_selected($statestalking, 'yes') . '>' . _('Enabled') . '</option>
                <option value="no" ' . is_selected($statestalking, 'no') . '>' . _('Disabled') . '</option>
            </select>
            <div class="subtext">' . _('Should the service(s) be stalked?  Stalked services will have their output data (textual alert information) logged by Nagios each time newly received output differs from the most recent previously received output.  This can be useful to track important or security-related information.') . '</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us=
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

            $output .= _("<div style='padding-bottom: 20px;'>There are no monitoring options to configure with passive checks.</div>");

            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            //print_r($inargs);

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $securitycheck = grab_array_var($inargs, "securitycheck", "no");
            $isvolatile = grab_array_var($inargs, "isvolatile", ($securitycheck == "yes") ? "yes" : "no");
            $statestalking = grab_array_var($inargs, "statestalking", ($securitycheck == "yes") ? "yes" : "no");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $services = unserialize(base64_decode($services_serial));
            if (!is_array($services))
                $services = grab_array_var($inargs, "services");

            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '

        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="securitycheck" value="' . htmlentities($securitycheck) . '">
        <input type="hidden" name="isvolatile" value="' . htmlentities($isvolatile) . '">
        <input type="hidden" name="statestalking" value="' . htmlentities($statestalking) . '">

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
            $securitycheck = grab_array_var($inargs, "securitycheck", "no");
            $isvolatile = grab_array_var($inargs, "isvolatile", ($securitycheck == "yes") ? "yes" : "no");
            $statestalking = grab_array_var($inargs, "statestalking", ($securitycheck == "yes") ? "yes" : "no");

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_passive_host",
                    "host_name" => $hostname,
                    "address" => $address,
                    'stalking_options' => ($statestalking == "yes") ? "o,u,d" : "n",
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
                    'use' => 'xiwizard_passive_service',
                    'check_interval' => 1,
                    'retry_interval' => 1,
                    'max_check_attempts' => 1,
                    'is_volatile' => ($isvolatile == "yes") ? 1 : 0,
                    'stalking_options' => ($statestalking == "yes") ? "o,w,u,c" : "n",
                    //'icon_image' => 'passivecheck.png',
                    '_xiwizard' => $wizard_name,
                );
            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;


        // THE FOLLOWING MODES ARE POST-CONFIGURATION CALLBACKS
        // THEY CAN BE USED TO DO CONFIGURATION TASKS, ETC AFTER A NEW
        //      CONFIGURATION HAS BEEN SUBMITTED

        case CONFIGWIZARD_MODE_COMMITERROR:
            echo "COMMITERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITCONFIGERROR:
            echo "COMMITCONFIGERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITPERMSERROR:
            echo "COMMITPERMSERROR!\n";
            break;

        case CONFIGWIZARD_MODE_COMMITOK:

            $services_serial = grab_array_var($inargs, "services_serial");
            $services = unserialize(base64_decode($services_serial));

            break;

        default:
            break;
    }

    return $output;
}