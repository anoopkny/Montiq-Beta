<?php
//
// Unconfigured Object Config Wizard
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

passiveobject_configwizard_init();

function passiveobject_configwizard_init()
{
    $name = "passiveobject";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor hosts and services that have been passively submitted to Nagios, but have not yet been configured."),
        CONFIGWIZARD_DISPLAYTITLE => _("Unconfigured Passive Object"),
        CONFIGWIZARD_FUNCTION => 'passiveobject_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => 'passiveobject.png',
        CONFIGWIZARD_SHOWASAVAILABLE => false,
        CONFIGWIZARD_DISPLAYFUNCTION => 'is_admin'
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
function passiveobject_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "passiveobject";

    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $output = '
            <p>
            ' . _('This wizard can only be used when configuring passive hosts and services through the') . ' <a href="../admin/?xiwindow=missingobjects.php" target="_top">' . _('unconfigured objects') . '</a> page.
            </p>
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $hosts_serial = grab_array_var($inargs, "hosts_serial", "");
            $hosts = unserialize(base64_decode($hosts_serial));
            if (!is_array($hosts))
                $hosts = grab_array_var($inargs, "host", array());


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (count($hosts) == 0)
                $errmsg[$errors++] = _('No objects specified.  Use the') . " <a href='../admin/?xiwindow=missingobjects.php' target='_top'>" . _('unconfigured objects') . "</a> " . _('page to begin configuration.') . "";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            //print_r($inargs);

            // get variables that were passed to us

            $securitycheck = grab_array_var($inargs, "securitycheck", "no");
            $isvolatile = grab_array_var($inargs, "isvolatile", ($securitycheck == "yes") ? "yes" : "no");
            $statestalking = grab_array_var($inargs, "statestalking", ($securitycheck == "yes") ? "yes" : "no");

            $hosts_serial = grab_array_var($inargs, "hosts_serial", "");
            $hosts = unserialize(base64_decode($hosts_serial));
            if (!is_array($hosts))
                $hosts = grab_array_var($inargs, "host", array());


            $output = '
            
        <input type="hidden" name="securitycheck" value="' . htmlentities($securitycheck) . '">
        <input type="hidden" name="isvolatile" value="' . htmlentities($isvolatile) . '">
        <input type="hidden" name="statestalking" value="' . htmlentities($statestalking) . '">
        <input type="hidden" name="hosts_serial" value="' . base64_encode(serialize($hosts)) . '">

<h5 class="ul">' . _('Hosts') . '</h5>
    
    <p>' . _('This wizard will automatically create missing object definitions for the following hosts and their associated services:') . '</p>
    
<div style="overflow: auto; width: 400px; padding: 10px; height: 160px; border: 1px solid #DDD;">
';
            foreach ($hosts as $hn => $ts) {
                $output .= $hn . "<BR>";
            }
            $output .= '</div>';

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            //print_r($inargs);

            // get variables that were passed to us
            //$address=grab_array_var($inargs,"address");

            $hosts_serial = grab_array_var($inargs, "hosts_serial", "");
            $hosts = unserialize(base64_decode($hosts_serial));
            if (!is_array($hosts))
                $hosts = grab_array_var($inargs, "host", array());

            // check for errors
            $errors = 0;
            $errmsg = array();
            if (!is_array($hosts))
                $errmsg[$errors++] = _("No hosts specified.");
            else if (count($hosts) == 0)
                $errmsg[$errors++] = _("No hosts specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            $output .= _("There are no monitoring options to configure with passive objects.  Click Next to continue.");

            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            //print_r($inargs);

            // get variables that were passed to us
            $securitycheck = grab_array_var($inargs, "securitycheck", "no");
            $isvolatile = grab_array_var($inargs, "isvolatile", ($securitycheck == "yes") ? "yes" : "no");
            $statestalking = grab_array_var($inargs, "statestalking", ($securitycheck == "yes") ? "yes" : "no");

            $hosts_serial = grab_array_var($inargs, "hosts_serial", "");
            $hosts = unserialize(base64_decode($hosts_serial));
            if (!is_array($hosts))
                $hosts = grab_array_var($inargs, "host", array());

            $output = '

        <input type="hidden" name="securitycheck" value="' . htmlentities($securitycheck) . '">
        <input type="hidden" name="isvolatile" value="' . htmlentities($isvolatile) . '">
        <input type="hidden" name="statestalking" value="' . htmlentities($statestalking) . '">

        <input type="hidden" name="hosts_serial" value="' .
                base64_encode(serialize($hosts)) . '">


            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;
        /*
                case CONFIGWIZARD_MODE_GETSTAGE4OPTS:

                    // hide some notification options
                    $output='';
                    $result=CONFIGWIZARD_HIDE_OPTIONS;
                    $outargs[CONFIGWIZARD_HIDDEN_OPTIONS]=array(
                        CONFIGWIZARD_HIDE_NOTIFICATION_DELAY,
                        CONFIGWIZARD_HIDE_NOTIFICATION_INTERVAL,
                        );

                    break;
        */
        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hosts_serial = grab_array_var($inargs, "hosts_serial", "");
            $hosts = unserialize(base64_decode($hosts_serial));
            if (!is_array($hosts))
                $hosts = grab_array_var($inargs, "host", array());

            $securitycheck = grab_array_var($inargs, "securitycheck", "no");
            $isvolatile = grab_array_var($inargs, "isvolatile", ($securitycheck == "yes") ? "yes" : "no");
            $statestalking = grab_array_var($inargs, "statestalking", ($securitycheck == "yes") ? "yes" : "no");


            //echo "HOSTS<BR>";
            //print_r($hosts);
            //echo "<BR>";

            $objs = array();


            $datas = @file_get_contents(get_root_dir() . "/var/corelog.newobjects");
            if ($datas == "" || $datas == null)
                $newobjects = array();
            else
                $newobjects = @unserialize($datas);

            foreach ($hosts as $hostname => $ts) {


                if (!host_exists($hostname)) {
                    $objs[] = array(
                        "type" => OBJECTTYPE_HOST,
                        "use" => "xiwizard_passive_host",
                        "host_name" => $hostname,
                        "address" => $hostname,
                        "icon_image" => "passiveobject.png",
                        "statusmap_image" => "passiveobject.png",
                        'stalking_options' => ($statestalking == "yes") ? "o,u,d" : "n",
                        "_xiwizard" => $wizard_name,
                    );
                }

                foreach ($newobjects[$hostname]["services"] as $svcname => $sarr) {

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

            }

            //echo "OBJECTS:<BR>";
            //print_r($objs);
            //exit();

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

            //echo "COMMITOK!\n";
            //echo "INARGS:\n";
            //print_r($inargs);

            $services_serial = grab_array_var($inargs, "services_serial");
            $services = unserialize(base64_decode($services_serial));

            //echo "SERVICES:\n";
            //print_r($services);

            // initialize each new service with an OK state
            /*
            $servicename='SNMP Traps';
            $hosts=grab_array_var($services,"host");
            foreach($hosts as $hostname => $hoststate){
                echo "HOST/SVC => $hostname,SNMP Traps\n";
                $output="";
                $raw_command="PROCESS_SERVICE_CHECK_RESULT;".$hostname.
                    ";".$servicename.";0;Waiting for trap...\n";
                submit_direct_nagioscore_command($raw_command,$output);
                }
            */

            break;

        default:
            break;
    }

    return $output;
}