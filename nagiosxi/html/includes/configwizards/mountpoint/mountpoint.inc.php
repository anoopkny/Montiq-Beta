<?php
// Mountpoint CONFIG WIZARD
//
// Copyright (c) 2016 Nagios Enterprises, LLC.  All rights reserved.
//

include_once(dirname(__FILE__).'/../configwizardhelper.inc.php');

// run the initialization function
mountpoint_configwizard_init();

function mountpoint_configwizard_init()
{
    $name = "mountpoint";
    
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.1",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a NFS, CIFS or DAVFS mountpoint."),
        CONFIGWIZARD_DISPLAYTITLE => _("Mountpoint"),
        CONFIGWIZARD_FUNCTION => "mountpoint_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "mountpoint.png",
        CONFIGWIZARD_FILTER_GROUPS => array('linux','otheros'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
        );

    register_configwizard($name,$args);
}

/**
 * @return array
 */
function mountpoint_configwizard_check_prereqs()
{
    $errors = array();

    if (!file_exists("/usr/local/nagios/libexec/check_mountpoints.sh")) {
        $errors[] = _('It looks like you are missing check_mountpoints.sh on your Nagios XI server. To use this wizard you must install the check_mountpoints.sh plugin on your server located in the this wizards plugin directory here: /usr/local/nagios/libexec/');
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
function mountpoint_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "mountpoint";
    
    // initialize return code and output
    $result = 0;
    $output = "";
    
    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $errors = mountpoint_configwizard_check_prereqs();

            if ($errors) {
                $output .= '<div class="message"><ul class="errorMessage">';

                foreach($errors as $error) {
                    $output .= "<li><p>$error</p></li>";
                }

                $output .= '</ul></div>';
            } else {

                $hostname = grab_array_var($inargs, "hostname", "localhost");
                $hostname = nagiosccm_replace_user_macros($hostname);

                // Save data from clicking "back" in stage 2
                $services = grab_array_var($inargs, "services", array());
                $serviceargs = grab_array_var($inargs, "serviceargs", array());
                
                $output = '
<input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
<input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '" />
<input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '" />
                
<h5 class="ul">' . _('Mountpoint Host Description') . '</h5>
<p style="max-width: 500px;">
' . _('This wizard will check if the specified mountpoints exist and if they are correctly implemented. The host name is the description that will bind all the mountpoint services together. Select a currently existing mountpoint host name and the services will be added to it.  These are the supported mountpoint types: nfs, nfs4, davfs, cifs, fuse, simfs, glusterfs, ocfs2, lustre') . '.
</p>

<table class="table table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Hostname') . ':</label>
        </td>
        <td>
            <input type="text" size="25" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control" />
            <div class="subtext">' . _("The IP address or FQDN of the host the wizard will monitor") . '</div>
        </td>
    </tr>
</table>';
            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname", "");

            // check for errors
            $errors = 0;
            $errmsg = array();

            if (have_value($hostname) == false)
                $errmsg[$errors++] = "No mountpoint specified.";

            if ($errors>0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES]=$errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname");
            $service_description = grab_array_var($inargs, "service_description", "_");

            $scansuccess = 1;
            $mounts = array();

            // scan for mounts
            $mountpoint_cmd = "/usr/local/nagios/libexec/check_mountpoints.sh -a";

            exec($mountpoint_cmd, $mount_output, $mount_return);

            if(!empty($mount_output)) {
                $mount_data = $mount_output[0];

                // parse response
                $response = preg_split("/:/", $mount_data);

                // if plugin returns OK
                if ($response[0] == "OK") {
                    preg_match("/\((.*?)\)$/", $mount_data, $mount_container);
                    $mount_data = explode(" ", $mount_container[1]);
                    $mount_data = array_filter($mount_data, 'strlen');

                    foreach ($mount_data as $key => $val) {
                        array_push($mounts, '"' . $val . '"');
                    }
                } else if ($response[0] == "CRITICAL") { // if plugin returns critical
                    preg_match_all("/:\s(.*?)\s;$/", $mount_data, $mount_container);
                    $mount_data = explode(" ; ", $mount_container[1][0]);

                    foreach ($mount_data as $key => $val) {
                        array_push($mounts, '"' . $val . '"');
                    }
                }

                // prepare for Javascript array
                $mounts = implode(",", $mounts);
            }
            // end of scan section

            $services = grab_array_var($inargs, "services", array(
                "check" => "off",
                "auto" => "off"
            ));
            
            $serviceargs['auto'] = grab_array_var($inargs, "serviceargs[auto]", array(
                "fstab" => "",
                "mtab" => ""
            ));

            $services_serial = grab_array_var($inargs, "services_serial");
            if(!empty($services_serial)){
                $services = unserialize(base64_decode($services_serial));
            }

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if(!empty($serviceargs_serial)) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            if (!array_key_exists('check', $services)) {
                $services['check'] = array();
                $serviceargs['check'] = array();
            }

            if (!array_key_exists('auto', $services)) {
                $services['auto'] = array();
                $serviceargs['auto'] = array();
            }

            for ($x = 0; $x < 2; $x++) {
                if (!array_key_exists($x, $services['check']))
                    $services['check'][$x] = '';

                if (!array_key_exists($x, $serviceargs['check'])) {
                    $serviceargs['check'][$x] = array(
                        'mountpoint' => '',
                        'write' => ''
                    );
                }
            }

            $output = '

<input type="hidden" name="hostname" value="' . encode_form_val($hostname) . '">
<input type="hidden" name="service_description" value="' . encode_form_val($service_description) . '">
<input type="hidden" name="services" value="' . encode_form_val($services) . '">
<input type="hidden" name="serviceargs" value="' . encode_form_val($serviceargs) . '">        

<h5 class="ul">' . _('Mountpoint Details') . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Hostname') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _("The IP address or FQDN of the host the wizard will monitor") . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Service Description') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="service_description" id="service_description" value="' . htmlentities($service_description) . '" class="textfield form-control">
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Mountpoint Service') . '</h5>
' . _('A Basic service that checks if the mountpoint that was set in the description is available.  The Scanned Mount List is a general scan of available mounts and their status using the plugin defaults.') . '<br>
<div class="message">
    <ul class="actionMessage">' . _('Write Test: This will create a temporary file and return OK if the write test was successful.') . '<li></li></ul>
</div><br>

<div style="display: inline-block; vertical-align: top; height: auto;">
    <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin-bottom: 0;">
        <tr>
            <th></th>' .
            '<th>' . _('Mountpoint Path') . '</th>' .
            '<th>' . _('Write Test') . '</th>' .
        '</tr>';

        for ($x = 0; $x < count($serviceargs['check']); $x++) {
            $mountpoint = encode_form_val($serviceargs['check'][$x]['mountpoint']);

            $output .= '
        <tr>
            <td>
                <input type="checkbox" class="checkbox" name="services[check][' . $x . ']" ' . (isset($services[$x]) ? is_checked($services[$x], 'on') : '') . '>
            </td>
            <td>
                <input type="text" size="25" name="serviceargs[check][' . $x . '][mountpoint]" value="' . $mountpoint . '" class="textfield form-control" />
            </td>
            <td>
                <center>
                    <input type="checkbox" class="checkbox" name="serviceargs[check][' . $x . '][write]" ' . (isset($serviceargs['check'][$x]['write']) ? is_checked($serviceargs['check'][$x]['write'], 'on') : '') . '>
                </center>
            </td>
        </tr>';
        }
        $output .= '
    </table>
</div>';

    // only display select box if we have mounts to display
    if (!empty($mounts)) {
        $output .= '
        <div style="display: inline-block; vertical-align: top; height: auto; margin: 5px 0 0 10px;">
            <b>' . _("Scanned Mount List") . '</b><br><select multiple id="mountList" class="form-control" style="width: 400px; min-height: 72px; margin: 5px 5px 5px 15px;" size="4"></select><br><a href="#" onClick="return false;" id="addMount">Add Selected</a>
        </div>';
    }

    $output .= '

<h5 class="ul">' . _('Auto Mountpoint Service') . '</h5>
' . _('Automatically scans through each mountpoint path located in default fstab file /etc/fstab.  If mountpoints are not mounted or are stale the wizard will return Critical with a list of bad mountpoints- OK mountpoints will not be listed.') . '
<div class="message">
    <ul class="actionMessage">' . _('To indicate a custom mountpoint fstab or mtab input the path in the indicated field.  Only one fstab or mtab can be indicated.') . '</ul>
</div>
<br>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Auto Select') . ':</label>
        </td>
        <td>
            <input type="checkbox" class="checkbox" name="services[auto]" ' . (isset($services['auto']) ? is_checked($services['auto'], 'off') : '') . '>
            <div class="subtext">' . _('This will turn the automatic mountpoint service on.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('fstab Path') . ':</label>
        </td>
        <td style="padding-bottom: 5px;">
            <input type="text" size="20" class="toggledisable1 textfield form-control" name="serviceargs[auto][fstab]" id="serviceargs[auto][fstab]" value="' . $serviceargs["auto"]["fstab"] . '">
            <div class="subtext">' . _('/etc/fstab will be used by the autoselect service as a default- use this field to change the default.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('mtab Path') . ':</label>
        </td>
        <td>
            <input type="text" size="20" class="toggledisable2 textfield form-control" name="serviceargs[auto][mtab]" id="serviceargs[auto][mtab]" value="' . $serviceargs["auto"]["mtab"] . '">
            <div class="subtext">' . _('Use this field to indicate a mtab path.') . '</div>
        </td>
    </tr>
</table>

<script type="text/javascript">
    $(document).ready(function() {
        wizard_populate();

        var mountcount = 0;

        $(".toggledisable1").on("input", function() {
            $(".toggledisable2").prop("disabled", this.value.length)
        });

        $(".toggledisable2").on("input", function() {
            $(".toggledisable1").prop("disabled", this.value.length)
        });

        // smart service selecter
        $("#addMount").click( function() {
            var element = "";
            var element = $("#mountList option:selected");
            var selected = element.length;
            var value = element.text();

            var count = 0;
            row_count = get_empty_field_count();

            if (selected > row_count) {
                row_count = get_empty_field_count();

                // count how many rows we need to trigger
                var create_inputs = selected - row_count;

                for (i = 0; i < create_inputs; i++) {
                    $(this).parent().prev().find("a.wizard-add-row").trigger("click");
                }
            }

            if (selected > 1) {
                $.each(element, function() {
                    var mountname = "";
                    var mountregex = "";
                    value = $(this).html();
                    $(this).remove();

                    // find empty input
                    targetmount = $("[name^=\'serviceargs[check][" + mountcount + "][mountpoint]\']").filter(function() { return $(this).val() == ""; });
                    targetmount.val(value);

                    mountcount++;
                });

                check_box_with_value();
            } else {
                element.remove();
                mountname = element.val();

                var targetmount = $("[name^=\'serviceargs[check]\']").filter(function() { return $(this).val() == ""; });
                targetmount = targetmount[0];
                targetmount = targetmount["name"];

                $("[name=" + "\'" + targetmount + "\'" + "]").val(mountname);

                check_box_with_value();
            }
        });

        // allow single double-click selector
        $("#mountList").on("dblclick", "option", function() {
            var element = "";
            var element = $("#mountList option:selected");
            var selected = element.length;
            var value = element.text();

            var mountregex = /(\/.*?)\s/;
            mountname = mountregex.exec(value);

            // use base value if regex failed to return anything
            if (!mountname) {
                mountname = value;
            } else {
                mountname = mountname[1];
            }
            element.remove();

            row_count = get_empty_field_count();

            // add row if needed
            if (row_count < 1) {
                $("#mountList").parent().prev().find("a.wizard-add-row").trigger("click");
            }

            // find empty input
            var targetmount = $("[name^=\'serviceargs[check]\']").filter(function() { return $(this).val() == ""; });
            targetmount = targetmount[0];
            targetmount = targetmount["name"];

            $("[name=" + "\'" + targetmount + "\'" + "]").val(mountname);           

            mountcount++;
            check_box_with_value();
        });
    });

    function wizard_populate() {
        // populate scanned data
        var mountlist = [' . $mounts . '];
        mountlist.sort(function (a, b) {
            return a.toLowerCase().localeCompare(b.toLowerCase());
        });

        var mount_list = $("#mountList");
        $.each(mountlist, function(key, value) {
            mount_list.append($("<option></option>").attr("value", value).text(value)); 
        });
    }

    function get_empty_field_count() {
        target = "";

        // find empty input fields
        target = $("[name^=\'serviceargs[check]\']").filter(function() { return $(this).val() == ""; });
        var row_count = target.length;

        return row_count;
    }

    // make sure checkboxes are checked
    function check_box_with_value() {
        var mountTargets = $("input[name^=\'serviceargs[check]\']").filter(function() { return $(this).val() !== ""; });

        $.each(mountTargets, function() {
            $(this).parent().prev("td").find("input").attr("checked", true);
        });
    }
</script>
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname");
            $service_description = grab_array_var($inargs, "service_description");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // check for errors
            $errors = 0;
            $errmsg = array();

            if (is_valid_service_name($service_description) == false)
                $errmsg[$errors++] = _("Invalid service prefix.  Can only contain alphanumeric characters, spaces, and the following:") . "<b>.\:_-</b>";

            if($errors>0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname");
            $service_description = grab_array_var($inargs, "service_description");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            $services_serial = base64_encode(serialize($services));
            $serviceargs_serial = base64_encode(serialize($serviceargs));


            $output = '

                <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
                <input type="hidden" name="service_description" value="' . htmlentities($service_description) . '" />
                <input type="hidden" name="services_serial" value="' . $services_serial . '">
                <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">

            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $hostname = grab_array_var($inargs, "hostname");
            $service_description = grab_array_var($inargs, "service_description");
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $output = '

                <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
                <input type="hidden" name="service_description" value="' . htmlentities($service_description) . '" />
                <input type="hidden" name="services_serial" value="' . $services_serial . '">
                <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">

            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs,"hostname", "");
            $service_description = grab_array_var($inargs, "service_description");
            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["service_description"] = $service_description;
            $meta_arr["services"] = $services;
            $meta_arr["serviceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if(!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_generic_host",
                    "host_name" => $hostname,
                    "address" => "localhost",
                    "icon_image" => "mountpoint.png",
                    "statusmap_image" => "mountpoint.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // see which services we should monitor
            foreach($services as $svc => $svcstate){

                // echo "PROCESSING: $svc -> $svcstate<BR>\n";

                switch($svc){

                    case "check":
                        foreach ($svcstate as $i => $v) {
                            // create service for each on checkbox
                            if ($v != "on")
                                continue;

                            $mountpointargs_check = "";

                            // create check using -i to ignore tab file
                            $mountpointargs_check = $serviceargs['check'][$i]['mountpoint'] . " -i";

                            if (isset($serviceargs['check'][$i]['write'])) {
                                $mountpointargs_check .= " -w";
                            }

                            $objs[] = array(
                                "type" => OBJECTTYPE_SERVICE,
                                "host_name" => $hostname,
                                "service_description" => $service_description . ": " . $serviceargs['check'][$i]['mountpoint'],
                                "use" => "xiwizard_mountpoint_check",
                                "check_command" => "check_mountpoint!" . $mountpointargs_check,
                                "icon_image" => "mountpoint.png",
                                "_xiwizard" => $wizard_name,
                            );
                        }
                        break;
                    
                    case "auto":
                        $mountpointargs_table = "";
                        $tab = "";

                        // create table check using the default, fstab or mtab file
                        if ($serviceargs['auto']['fstab'] != "") {
                            $mountpointargs_table = " -f '" .  $serviceargs['auto']['fstab'] . "' ";
                            $tab = "(fstab: " . $serviceargs['auto']['fstab'] . ")";
                        } else if ($serviceargs['auto']['mtab'] != "") {
                            $mountpointargs_table = " -m '" .  $serviceargs['auto']['mtab'] . "' -i ";
                            $tab = "(mtab: " . $serviceargs['auto']['mtab'] . ")";
                        }

                        $mountpointargs_table .= " -a";

                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "service_description" => $service_description . ": Autoselect " . $tab,
                            "use" => "xiwizard_mountpoint_check_table",
                            "check_command" => "check_mountpoint!" . $mountpointargs_table,
                            "icon_image" => "mountpoint.png",
                            "_xiwizard" => $wizard_name,
                        );
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
?>