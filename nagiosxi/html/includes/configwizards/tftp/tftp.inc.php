<?php
//
// TFTP Server Config Wizard
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__).'/../configwizardhelper.inc.php');

tftp_configwizard_init();

function tftp_configwizard_init()
{
    $name = "tftp";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.1",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a TFTP Server Connection or Specific File"),
        CONFIGWIZARD_DISPLAYTITLE => _("TFTP"),
        CONFIGWIZARD_FUNCTION => "tftp_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "tftp.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @return array
 */
function tftp_configwizard_check_prereqs()
{
    $errors = array();
    exec("which tftp 2>&1", $output, $return_var);
    if ($return_var != 0) {
        $errors[] = _('It looks like you are missing tftp on your Nagios XI server.').'<br><br> Run: &nbsp; <b>yum install tftp -y</b> &nbsp; as root user on your Nagios XI server.';
    }
    return $errors;
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function tftp_configwizard_func($mode="", $inargs=null, &$outargs, &$result)
{
    $wizard_name = "tftp";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $errors = tftp_configwizard_check_prereqs();

            if ($errors) {
                $output .= '<div class="message"><ul class="errorMessage">';
                foreach ($errors as $error) {
                    $output .= "<li><p>$error</p></li>";
                }
                $output .= '</ul></div>';
            }
            else {
        
                $address = grab_array_var($inargs, "address", "");
                $address = nagiosccm_replace_user_macros($address);

                // Save data from clicking "back" in stage 2
                $services = grab_array_var($inargs, "services", array());
                $serviceargs = grab_array_var($inargs, "serviceargs", array());

                $output = '
<input type="hidden" name="services_serial" value="'.base64_encode(serialize($services)).'">
<input type="hidden" name="serviceargs_serial" value="'.base64_encode(serialize($serviceargs)).'">

<h5 class="ul">'._('TFTP Information').'</h5>
<p>'._('Specify the TFTP Server You Want to Monitor').'.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('TFTP Host').':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">'._('Add the TFTP server FQDN here').'.</div>
        </td>
    </tr>
</table>';
            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false) {
                $errmsg[$errors++] = _("No address specified.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $filename = grab_array_var($inargs, "filename", "");
            $filesize = grab_array_var($inargs, "filesize", "");

            $services = grab_array_var($inargs, "services", array(
                "tftpconnect" => "on",
                "id" => "on"
            ));

            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "filename" => "",
                "filesize" => ""
            ));

            $services_serial = grab_array_var($inargs, "services_serial");
            if (!empty($services_serial)) {
                $services = unserialize(base64_decode($services_serial));
            }

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if (!empty($serviceargs_serial)) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            if (!array_key_exists('id', $services)) $services['id'] = array();
            if (!array_key_exists('id', $serviceargs)) $serviceargs['id'] = array();
            for ($x = 0; $x < 2; $x++) {
                if (!array_key_exists($x, $services['id']))
                    $services['id'][$x] = '';

                if (!array_key_exists($x, $serviceargs['id'])) {
                    $serviceargs['id'][$x] = array(
                        'id' => '',
                        'filename' => '',
                        'filesize' => ''
                    );
                }
            }

            $output = '
<input type="hidden" name="address" value="' . encode_form_val($address) . '" />
<input type="hidden" name="filename" value="' . encode_form_val($filename) . '" />
<input type="hidden" name="filesize" value="' . encode_form_val($filesize) . '" />

<h5 class="ul">'._('TFTP Server').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>'._('Address').':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="'.htmlentities($address).'" class="form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('TFTP Connect Service') . '</h5>
<p>' . _('A service that checks a TFTP server availability by trying to write a file without permissions.') . '</p>
<table class="table table-no-border table-auto-width">
    <tr>
        <td class="checkbox">
            <label>
                <input type="checkbox" class="checkbox" id="tftpconnect" name="services[tftpconnect]" ' . is_checked(grab_array_var($services,"tftpconnect")) . '> ' . _('TFTP Connection Test Service') . '
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('TFTP Get Service') . '</h5>
<p>' . _('A service that checks a file located on a TFTP server for sanity or to check size.') . '</p>

<div class="message"><ul class="actionMessage">' . _('You must include the exact size of the file in bytes or the check will not run successfully and you will recieve a message similar to <b>\'File size mismatch: expected 17 bytes, got 15 bytes.\'</b>') . '<li></li></ul></div>

<table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr><th></th>' .
        '<th>' . _('File Name') . '</th>' .
        '<th>' . _('File Size') . ' ' . _('(bytes)') . '</th>' .
    '</tr>';

    for ($x = 0; $x < count($serviceargs['id']); $x++) {
        $filename = encode_form_val($serviceargs['id'][$x]['filename']);
        $filesize = encode_form_val($serviceargs['id'][$x]['filesize']);

        $output .= '<tr>
        <td><input type="checkbox" class="checkbox" name="services[id][' . $x . ']" ' . (isset($services['id'][$x]) ? is_checked($services['id'][$x], 'on') : '') . '></td>
        <td><input type="text" size="25" name="serviceargs[id][' . $x . '][filename]" value="' . $filename . '" class="form-control"></td>
        <td><input type="text" size="10" name="serviceargs[id][' . $x . '][filesize]" value="' . $filesize . '" class="form-control"></td>
        </tr>';
    }

    $output .= '</table>
    <div style="height: 20px;"></div>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $filename = grab_array_var($inargs, "filename");
            $filesize = grab_array_var($inargs, "filesize");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_domain_name($address) == false)
                $errmsg[$errors++] = _("Invalid domain name.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            $output = '<div style="margin-bottom: 20px;">'._('The selected TFTP server will be queried to return a check value. Click Finish to continue.').'</div>';
            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $filename = grab_array_var($inargs, "filename");
            $filesize = grab_array_var($inargs, "filesize");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());
            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
                <input type="hidden" name="address" value="'.htmlentities($address).'">
                <input type="hidden" name="filename" value="'.htmlentities($filename).'">
                <input type="hidden" name="filesize" value="'.htmlentities($filesize).'">
                <input type="hidden" name="services_serial" value="'.$services_serial.'">
                <input type="hidden" name="serviceargs_serial" value="'.$serviceargs_serial.'">
        
                <!--SERVICES='.serialize($services).'<BR>
                SERVICEARGS='.serialize($serviceargs).'<BR>-->        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $address = grab_array_var($inargs, "address");
            $filename = grab_array_var($inargs, "filename");
            $filesize = grab_array_var($inargs, "filesize");
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $output = '
                <input type="hidden" name="address" value="'.htmlentities($address).'">
                <input type="hidden" name="filename" value="'.htmlentities($filename).'">
                <input type="hidden" name="filesize" value="'.htmlentities($filesize).'">
            ';

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $address=grab_array_var($inargs,"address","");
            $filename=grab_array_var($inargs,"filename", "");
            $filesize=grab_array_var($inargs,"filesize", "");
            $services=grab_array_var($inargs,"services",array());
            $serviceargs=grab_array_var($inargs,"serviceargs",array());
            $services_serial=grab_array_var($inargs,"services_serial","");
            $serviceargs_serial=grab_array_var($inargs,"serviceargs_serial","");

            $services=unserialize(base64_decode($services_serial));
            $serviceargs=unserialize(base64_decode($serviceargs_serial));

            $servicename = "";

            // save data for later use in re-entrance
            $meta_arr=array();
            $meta_arr["address"]=$address;
            $meta_arr["filename"]=$filename;
            $meta_arr["filesize"]=$filesize;
            save_configwizard_object_meta($wizard_name,$address,"",$meta_arr);            

            $objs=array();

            if(!host_exists($address)){
                $objs[]=array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_tftp_host",
                    "host_name" => $address,
                    "check_command" => "check-host-alive-tftp!" . $address,
                    "icon_image" => "tftp.png",
                    "statusmap_image" => "tftp.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // see which services we should monitor
            foreach($services as $svc => $svcstate){

                // echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch($svc){

                    case "tftpconnect":
                        $objs[]=array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $address,
                            "service_description" => "TFTP Connect",
                            "use" => "xiwizard_tftp_service_connect",
                            "check_command" => "check_tftp_connect!" . $address,
                            "icon_image" => "tftp.png",
                            "_xiwizard" => $wizard_name,
                        );
                        break;
                    
                    case "id":
                        // create service for each file we are getting
                        foreach($serviceargs[$svc] as $k => $i) {
                            // make sure id is checked "on" to run
                            if ($services['id'][$k] != "on")
                                continue;

                            $servicename = escapeshellarg($i['filename']);
                            $filename = escapeshellarg($i['filename']);
                            $filesize = escapeshellarg($i['filesize']);

                            $objs[]=array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $address,
                                "service_description" => "TFTP Get:" . $servicename,
                                "use" => "xiwizard_tftp_service_get",
                                "check_command" => "check_tftp_get!" . $address . "!" . $filename  . "!" . $filesize,
                                "icon_image" => "tftp.png",
                                "_xiwizard" => $wizard_name,
                            );
                        }
                        break;

                    default:
                        break;
                }
            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS]=$objs;

            break;

        default:
            break;
        }

    return $output;
}