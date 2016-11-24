<?php
//
// VMware Config Wizard
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

vmware_configwizard_init();

define('VMWARE_SERVICENAMES',
    serialize(array(
            array('CPU', TRUE, 'CPU Usage', 0, 1, 2, 3, 0, 1, 2, 3),
            array('MEM', TRUE, 'Memory', 0, 1, 2, 3, 0, 1, 2, 3),
            array('NET', TRUE, 'Networking', 0, 1, 2, 3, 0, 1, 2, 3),
            array('IO', TRUE, 'Input / Output', 0, 1, 2, 3, 0, 1, 2, 3),
            array('VMFS', FALSE, 'Datastore usage', 0, 1, 2, 3, 0, 1, 2, 3),
            array('RUNTIME', TRUE, 'VM Status', 0, 1, 2, 3, 0, 1, 2, 3),
            array('SERVICE', FALSE, 'Services', 0, 1, 2, 3, 0, 1, 2, 3),
        )
    )
);

define('VMWARE_BASICDATA',
    serialize(array(
            array(
                array("wng", "warning", "Warning"),
                array("crt", "critical", "Critical"),
            ),
            array(
                array("low", "low", "%s Below:"),
                array("hi", "high", "Above:"),
            )
        )
    )
);

define('VMWARE_HOSTINPUTF', '');

function vmware_configwizard_init()
{
    $name = 'vmware';
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.6.9",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _('Monitor a VMware host or guest VM.'),
        CONFIGWIZARD_DISPLAYTITLE => _('VMware'),
        CONFIGWIZARD_FUNCTION => 'vmware_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => 'vmware.png',
        CONFIGWIZARD_REQUIRES_VERSION => 512
    );
    register_configwizard($name, $args);
}

/**
 * Checks to verify that VMware SDK is installed and the plugin is working
 *
 * @return bool
 */
function vmware_configwizard_check_prereqs()
{
    // Plugin doesn't exist
    if (!file_exists("/usr/local/nagios/libexec/check_esx3.pl")) {
        return false;
    }
        
    // Run the plugin to see if the SDK is installed
    $cmd = "LANG=C LC_ALL=C /usr/local/nagios/libexec/check_esx3.pl | head --lines=1";
    $proc = proc_open($cmd, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (is_resource($proc)) {
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($proc);
    }
    
    // Verify there were no errors running the plugin and that the perl module exists
    if (!empty($stderr) || strpos($stdout, "Missing perl module") !== false) {
        return false;
    }

    return true;
}

/**
 * @param null $inargs
 *
 * @return array
 */
function vmware_configwizard_parseinargs($inargs = null)
{
    // Get variables that were passed to us
    $address = grab_array_var($inargs, 'address', '');
    $hostname = grab_array_var($inargs, 'hostname', '');
    $ha = '';
    if ($hostname == '') {
        $ha = $address == '' ? '' : @gethostbyaddr($address);
        if ($ha == '')
            $ha = $address;
    }
    $hostname = grab_array_var($inargs, 'hostname', $ha);
    $type = grab_array_var($inargs, 'type', 'host');
    $username = grab_array_var($inargs, 'username', '');
    $password = grab_array_var($inargs, 'password', '');

    // User macros
    $address = nagiosccm_replace_user_macros($address);
    $hostname = nagiosccm_replace_user_macros($hostname);
    $username = nagiosccm_replace_user_macros($username);
    $password = nagiosccm_replace_user_macros($password);

    $services_serial = grab_array_var($inargs, 'services_serial', '');
    $serviceargs_serial = grab_array_var($inargs, 'serviceargs_serial', '');
    $guests_serial = grab_array_var($inargs, 'guests_serial', '');

    $services = unserialize(base64_decode($services_serial));
    $serviceargs = unserialize(base64_decode($serviceargs_serial));
    $guests = unserialize(base64_decode($guests_serial));
    if (!is_array($services))
        $services = array();
    if (!is_array($serviceargs))
        $serviceargs = array();
    if (!is_array($guests))
        $guests = array();
    $srvlock = 0;
    $guestlock = 0;

    foreach (array_keys($inargs) as $argu) {
        if ($type == 'guest' && preg_match('/^activate_(.*)$/', $argu, $matches)) {
            if (!$guestlock) {
                $guests = array();
                $guestlock = -1;
            }
            $argt = base64_decode($matches[1]);
            $guests[$argt] = grab_array_var($inargs, "alias_${matches[1]}", $argt);
        }
        if (preg_match('/^service_(.*)$/', $argu, $matches)) {
            if (!$srvlock) {
                $services = array();
                $srvlock = -1;
            }
            $services[$matches[1]] = TRUE;
        }
        if (preg_match('/^serviceargs_([^-]*)-(.*)$/', $argu, $matches)) {
            $argt = $matches[1] . '_' . $matches[2];
            if (array_search($argt, $serviceargs) === FALSE)
                $serviceargs[$argt] = grab_array_var($inargs, $argu, '');
        }
    }

    unset ($argu);

    return array($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests);
}

/**
 * @param $output
 * @param $s
 * @param $services
 * @param $serviceargs
 * @param $mode
 */
function vmware_configwizard_pushcheckboxandargs(&$output, $s, $services, $serviceargs, $mode)
{
    $sl = strtolower($s[0]);
    $output .= '<div class="checkbox"><label><input type="checkbox" id="ckhbx_' . htmlentities($sl) . '" name="service_' . htmlentities($sl) . '"' . (array_key_exists($sl, $services) ? ' checked="yes"' : '') . '></input>' . htmlentities($s[2]) . '</label></div>';
}

/**
 * @param $serviceargs
 * @param $svcl
 *
 * @return string
 */
function vmware_configwizard_getrangeargs($serviceargs, $svcl)
{
    $ret = '';
    $dab = array();
    foreach (array_shift(unserialize(VMWARE_BASICDATA)) as $da) {
        foreach (array_pop(unserialize(VMWARE_BASICDATA)) as $db) {
            $key = '-' . $da[1] . '_' . $db[1];
            array_push($dab,
                grab_array_var($serviceargs,
                    $svcl . $key));
        }
    }
    list($wl, $wh, $cl, $ch) = $dab;
    unset($dab, $da, $db);
    if (!($wh == '' && $wl == ''))
        $ret .= ' -w ' . $wl . ':' . $wh;
    if (!($ch == '' && $cl == ''))
        $ret .= ' -c ' . $cl . ':' . $ch;

    return $ret;
}

/**
 * @param $objs
 * @param $type
 * @param $hostname
 * @param $address
 *
 * @return array
 */
function vmware_configwizard_makehost(&$objs, $type, $hostname, $address)
{
    return array(
        'type' => OBJECTTYPE_HOST,
        'use' => 'xiwizard_generic_host',
        'host_name' => $hostname,
        'address' => $address,
        'icon_image' => 'vmware.png',
        'statusmap_image' => 'vmware.png',
        '_xiwizard' => 'vmware',
    );
}

/**
 * @param $objs
 * @param $hostname
 * @param $address
 * @param $type
 * @param $services
 * @param $serviceargs
 * @param $guests
 */
function vmware_configwizard_makeservices(&$objs, $hostname, $address, $type, $services, $serviceargs, $guests)
{
    $fil = get_root_dir() . '/etc/components/vmware/' . preg_replace("/[ '.\:_-]/", '_', $hostname) . '_auth.txt';
    if (!host_exists($hostname))
        $objs[] = vmware_configwizard_makehost($objs, $type, $hostname, $address);
    switch ($type) {
        case 'guest':
            foreach ($guests as $guestaddress => $guestname) {

                // see which services we should monitor
                foreach (unserialize(VMWARE_SERVICENAMES) as $s) {
                    $sl = strtolower($s[0]);
                    if (array_key_exists($sl, $services)) {
                        $warn = vmware_configwizard_getrangeargs($serviceargs, $sl);
                        $objs[] = array('type' => OBJECTTYPE_SERVICE,
                            'host_name' => $hostname,
                            'service_description' => "${guestname} ${s[2]}",
                            'use' => 'xiwizard_generic_service',
                            'check_command' => "check_esx3_guest!$fil!$guestaddress!${s[0]}!$warn!",
                            '_xiwizard' => 'vmware',
                        );
                    }
                }
            }
            break;

        case 'host':

            // see which services we should monitor
            foreach (unserialize(VMWARE_SERVICENAMES) as $s) {
                $sl = strtolower($s[0]);
                if (array_key_exists($sl, $services)) {
                    $warn = vmware_configwizard_getrangeargs($serviceargs, $sl);

                    $objs[] = array('type' => OBJECTTYPE_SERVICE,
                        'host_name' => $hostname,
                        'service_description' => "${s[2]} for VMHost",
                        'use' => 'xiwizard_generic_service',
                        'check_command' => 'check_esx3_host!' . $fil . '!' . $s[0] . '!' . $warn,
                        '_xiwizard' => 'vmware',
                    );
                }
            }
            break;

        default:
            break;
    }
}

/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function vmware_configwizard_func($mode = '', $inargs = null, &$outargs, &$result)
{
    // Initialize return code and output
    $result = 0;
    $output = '';

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {

        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $output = '';

            if (vmware_configwizard_check_prereqs() == false) {

                $output .= '<div class="message" style="margin-top: 20px;">
                                <ul class="errorMessage">
                                    <li><strong>'._('Error').':</strong>
                                    '._('It appears as though you have not installed the VMware SDK or ESX plugins on your Nagios XI server.  You must have these components properly installed on your system before using this wizard.').'</li>
                                </ul>
                            </div>
                            <p style="padding-bottom: 20px;">
                                '._('To complete the installation of the required components please follow the').' <strong><a href="http://library.nagios.com/library/products/nagiosxi/documentation/272-monitoring-vmware-with-nagios-xi" target="_blank">'._('Monitoring VMware with Nagios XI').'</a></strong> documentation.
                            </p>';
            
            } else {
                list($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests) = vmware_configwizard_parseinargs($inargs);

                $output .= '
<input type="hidden" name="services_serial" value="' . htmlentities(base64_encode(serialize($services))) . '">
<input type="hidden" name="serviceargs_serial" value="' . htmlentities(base64_encode(serialize($serviceargs))) . '">
<input type="hidden" name="guests_serial" value="' . htmlentities(base64_encode(serialize($guests))) . '">

<h5 class="ul">' . _('VMware Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="30" name="address" id="address" value="' . htmlentities($address) . '" class="form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the VMware (server) host you would like to monitor.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username:') . '</label>
        </td>
        <td>
            <input type="text" size="20" name="username" id="username" value="' . htmlentities($username) . '" class="form-control">
            <div class="subtext">' . _('The password used to authenticated to the VMware server.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="20" name="password" id="password" value="' . htmlentities($password) . '" class="form-control">
            <div class="subtext">' . _('The password used to authenticated to the VMware server.') . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Monitoring Mode') . ':</label>
        </td>
        <td>    
            ' . _('Would you like to monitor the VMware host (server) or a guest VM?') . '
            <div class="pad-t5">
                <div class="radio">
                    <label><input type="radio" name="type" value="host" ' . ($type === "host" ? ' checked="yes"' : '') . '>' . _('Monitor the VMware host') . '</label>
                </div>
                <div class="radio">
                    <label><input type="radio" name="type" value="guest" ' . ($type === "guest" ? ' checked="yes"' : '') . '>' . _('Monitor a guest VM on the VMWare host') . '</label>
                </div>
            </div>
        </td>
    </tr>
</table>';

            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            list($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests) = vmware_configwizard_parseinargs($inargs);

            // check for errors
            $errors = 0;
            $errmsg = array();
            if (vmware_configwizard_check_prereqs() == false) {
                $errmsg[$errors++] = _('Required software components are missing.');
            } else {
                if (have_value($address) == false)
                    $errmsg[$errors++] = _('No address specified.');
                if (have_value($username) == false)
                    $errmsg[$errors++] = _('Username not specified.');
                if (have_value($password) == false)
                    $errmsg[$errors++] = _('Password not specified.');
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            list($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests) = vmware_configwizard_parseinargs($inargs);

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">
<input type="hidden" name="type" value="' . htmlentities($type) . '">
<input type="hidden" name="username" value="' . htmlentities($username) . '">
<input type="hidden" name="password" value="' . htmlentities($password) . '">

<h5 class="ul">' . _('VMware Details') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('VMware Mode') . ':</label>
        </td>
        <td>
            ' . ucfirst(htmlentities($type)) . '
        </td>
    </tr>
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
</table>';

            switch ($type) {
                case 'guest':

                    $output .= '    <script type="text/javascript">
                                $(function() 
                                {
                                    $("#vmware_settings").tabs();
                                });
                                $(document).ready(function() {
                                    $(\'#checkAll\').click(function(event) {  //on click
                                        if(this.checked) { // check select status
                                            $(\'#vmware_settings-2 input:checkbox\').each(function() { //loop through each checkbox
                                                this.checked = true;  //select all checkboxes with class "checkbox1"              
                                            });
                                        }else{
                                            $(\'#vmware_settings-2 input:checkbox\').each(function() { //loop through each checkbox
                                                this.checked = false; //deselect all checkboxes with class "checkbox1"                      
                                            });        
                                        }
                                    });
                                   
                                });
                                </script>

                                <div id="vmware_settings" style="margin-top: 20px;">
                                    <ul>
                                        <li><a href="#vmware_settings-1">' . _('Monitored Metrics') . '</a></li>
                                        <li><a href="#vmware_settings-2">' . _('Guest Selection') . '</a></li>
                                    </ul>
                                    <div id="vmware_settings-1">
                                        <h5 class="ul">' . _('VMware Monitored Metrics') . '</h5>
                                        <p>' . _('Select the metrics you\'d like to monitor on each of the guests you select.') . '</p>';
                    foreach (unserialize(VMWARE_SERVICENAMES) as $s) {
                        if ($s[1])
                            vmware_configwizard_pushcheckboxandargs($output, $s, $services, $serviceargs, $mode);
                    };
                    
                    $output .= '    </div>
                            <div id="vmware_settings-2">';

                    // Run the get guests perl file to get a list of guest VMs...
                    $cmd = get_root_dir() . '/html/includes/configwizards/vmware/scripts/getguests.pl -H ' . $address . ' -u ' . $username . ' -p ' . escapeshellarg($password);
                    $proc = proc_open($cmd, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
                    if (is_resource($proc)) {
                        $data = stream_get_contents($pipes[1]);
                        fclose($pipes[1]);
                        $stderr = stream_get_contents($pipes[2]);
                        fclose($pipes[2]);
                        proc_close($proc);
                    }

                    $data = explode("\n", $data);

                    if (!empty($stderr)) {
                        $output .= '    <h5 class="ul">' . _('Error') . '</h5>
                                        <p>' . _('It appears there are no guests for this VMware host. The error message is below. This may be because the SDK is not installed, your credentials are wrong, or the host is not a VMware server.') . '</p>
                                        <pre>' . $stderr . '</pre>
                                    </div>
                                </div>';
                        return $output;
                    }

                    $output .= '    <h5 class="ul">' . _('VMware Guest Selection') . '</h5>
                            <p>' . _('Specify which guests you\'d like to monitor on the VMware host (server).') . '</p>
                            <table class="table table-condensed table-no-border table-auto-width">
                                <thead>
                                    <tr>
                                        <th style="vertical-align:middle;horizontal-align:middle;"> <input type="checkbox" id="checkAll" title="'._('Check All').'"></th>
                                        <th>' . _('VM Name') . '</th>
                                        <th>' . _('IP Address') . '</th>
                                        <th>' . _('Current Status') . '</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    $rownumber = 2; //Used for determing row color
                    $rowstyles = 'vertical-align:middle;horizontal-align:middle;';
                    foreach ($data as &$element) {
                        ($rownumber++ % 2) ? $tclass = 'odd' : $tclass = 'even';
                        $element = explode("\x00", $element);
                        $nam = base64_encode($element[0]);
                        $idnt = $element[0];
                        if(empty($nam))
                            continue;
                        $nametextfield = sprintf('<input type="text" size="35" name="alias_%s" id="alias_%s" value="%s" class="form-control">', htmlentities($nam), htmlentities($nam), htmlentities(array_key_exists($idnt, $guests) ? $guests[$idnt] : $idnt));

                        /* Now we will draw the tables.
                            $element[0] is the VM Name (Text Field) set to $nametextfield for readability.
                            $element[2] is the IP Address and is used to set $ipaddress variable.
                            $element[3] is the VM status and is used to set $powerstatus variable.*/
                        // Setting nice looking powerestatus variable.
                        ($element[3] == 'poweredOff') ? $powerstatus = '<font color="gray">' . _('Powered Off') . '</font>' : $powerstatus = '<font color="Green"><b>' . _('Powered On') . '<b></font>';
                        // Setting nice looking ipaddress variable.
                        ($element[2] == '') ? $ipaddress = '<font color="gray">' . _('None Defined') . '</font>' : $ipaddress = '<b>' . $element[2] . '</b>';
                        if (count($guests) === 0) {
                            //print_r($element);
                            $output .= '    <tr class="' . $tclass . '">
                                            <td style="' . $rowstyles . '">
                                                <input type="checkbox" name="activate_' . htmlentities($nam) . '"' . ($element[3] === 'poweredOn' ? ' checked="yes"' : '') . '>';
                        } else {
                            $output .= '    <tr>
                                            <td style="' . $rowstyles . '">
                                                <input type="checkbox" name="activate_' . htmlentities($nam) . '"' . (array_key_exists($idnt, $guests) ? ' checked="yes"' : '') . '>';
                        }

                        $output .= '        </td>
                                        <td style="' . $rowstyles . '">' . $nametextfield . '</td>
                                        <td style="' . $rowstyles . '">' . $ipaddress . '</td>
                                        <td style="' . $rowstyles . '">' . $powerstatus . '</td>
                                    </tr>';
                    }

                    unset($element, $tmp, $data);

                    $output .= '        </tbody>
                                    </table>
                                    </div>
                                </div>';
                    break;

                case "host":

                    $output .= '    <h5 class="ul">' . _('VMware Host Metrics') . '</h5>
                                <p>' . _('Specify which metrics you\'d like to monitor on the VMware host (server).') . '</p>';
                    foreach (unserialize(VMWARE_SERVICENAMES) as $s) {
                        vmware_configwizard_pushcheckboxandargs($output, $s, $services, $serviceargs, $mode);
                    }
                    $output .= '<div style="height: 20px;"></div>';
                    break;
                default:
                    break;
            }

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            list($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests) = vmware_configwizard_parseinargs($inargs);

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) === false)
                $errmsg[$errors++] = _('Invalid host name.');

            foreach ($services as $s)
                if (is_valid_service_name($s) === false)
                    $errmsg[$errors++] = sprintf(_('Invalid service name') . " %s", $s);

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            list($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests) = vmware_configwizard_parseinargs($inargs);

            $output = ' <input type="hidden" name="address" value="' . htmlentities($address) . '">
                        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
                        <input type="hidden" name="type" value="' . htmlentities($type) . '">
                        <input type="hidden" name="username" value="' . htmlentities($username) . '">
                        <input type="hidden" name="password" value="' . htmlentities($password) . '">
                        <input type="hidden" name="services_serial" value="' . htmlentities(base64_encode(serialize($services))) . '">
                        <input type="hidden" name="serviceargs_serial" value="' . htmlentities(base64_encode(serialize(
                    $serviceargs))) . '">
                        <input type="hidden" name="guests_serial" value="' . htmlentities(base64_encode(serialize($guests))) . '">';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            list($hostname, $address, $type, $username, $password, $services, $serviceargs, $guests) = vmware_configwizard_parseinargs($inargs);

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr['hostname'] = $hostname;
            $meta_arr['address'] = $address;
            $meta_arr['username'] = $username;
            $meta_arr['password'] = $password;
            $meta_arr['type'] = $type;
            $meta_arr['services'] = $services;
            $meta_arr['serviceargs'] = $serviceargs;
            $meta_arr['guests'] = $guests;
            save_configwizard_object_meta('vmware', $hostname, '', $meta_arr);

            $objs = array();

            // write auth data file
            $fil = get_root_dir() . '/etc/components/vmware';
            if (!file_exists($fil))
                mkdir($fil, 0770);
            $fil .= '/' . preg_replace('/[ .\:_-]/', '_', $hostname) . '_auth.txt';

            $fh = fopen($fil, 'w+');
            if ($fh) {
                fputs($fh, 'username=' . $username . "\npassword=" . $password . '');
                fclose($fh);
            }

            vmware_configwizard_makeservices($objs, $hostname, $address, $type, $services, $serviceargs, $guests);

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:

            break;
    }

    return $output;
}