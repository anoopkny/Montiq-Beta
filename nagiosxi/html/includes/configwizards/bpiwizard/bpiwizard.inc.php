<?php
//
// BPI Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

bpiwizard_configwizard_init();

function bpiwizard_configwizard_init()
{
    $name = "bpiwizard";

    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Create service checks for your Nagios BPI groups."),
        CONFIGWIZARD_DISPLAYTITLE => _("BPI Wizard"),
        CONFIGWIZARD_FUNCTION => "bpiwizard_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "bpiwizard.png",
        CONFIGWIZARD_VERSION => "1.1.2",
        CONFIGWIZARD_DATE => "09/30/2016",
        CONFIGWIZARD_COPYRIGHT => "Copyright &copy; 2008-2016 Nagios Enterprises, LLC.",
        CONFIGWIZARD_AUTHOR => "Nagios Enterprises, LLC",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );

    register_configwizard($name, $args);
}


/**
 * Error suppressing function for printing session variables -> used for repopulating the form when going "back" 
 *
 * @param $value
 *
 * @return string
 */
function bpiVal($value)
{
    if (!isset($_SESSION['bpiwizard'])) return;
    if (isset($_SESSION['bpiwizard']['groups'][$value]) && $_SESSION['bpiwizard']['groups'][$value] != '') return 'checked="checked"';
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function bpiwizard_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{

    global $cfg;
    $wizard_name = "bpiwizard";

    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            //clear any previous wizard data
            if (!isset($_POST["backButton"])) {
                unset($_SESSION['bpiwizard']);
                $base = get_root_dir();
                //if we're using the new version of BPI
                if (file_exists($base . '/html/includes/components/nagiosbpi/bpi_display.php')) {
                    //echo "NEWVERSION<br />";
                    define('CLI', false);
                    $includefile = $cfg['root_dir'] . '/html/includes/components/nagiosbpi/inc.inc.php';
                    require($includefile);
                    $groupsArray = parse_bpi_conf();
                } else { //old version of BPI
                    //echo "OLD VERSION!<br />";
                    define('CONFIGFILE', get_base_dir() . '/includes/components/nagiosbpi/bpi.conf');
                    $configfile = get_base_dir() . '/includes/components/nagiosbpi/bpi.conf';
                    $includefile = get_base_dir() . '/includes/components/nagiosbpi/functions/read_conf.php';
                    require($includefile);
                    $groupsArray = get_info($configfile);
                }

                //make sure we have the necessary include
                if (file_exists($includefile)) {
                    $_SESSION['bpiwizard']['data'] = $groupsArray;
                } else {
                    $outargs[CONFIGWIZARD_ERROR_MESSAGES] = _('Nagios BPI is not installed as a component') . '
                    <a href="http://exchange.nagios.org/directory/Addons/Components/Nagios-Business-Process-Intelligence-%28BPI%29/details" title="Download BPI" target="_blank">' . _('Download Nagios BPI') . '</a>';
                }
            }
            $hostname = isset($_SESSION['bpiwizard']['hostname']) ? $_SESSION['bpiwizard']['hostname'] : grab_array_var($inargs, "hostname", "");;

            $output = '
<h5 class="ul">' . _('Create A BPI Dummy Host') . '</h5>
<table class="table table-condensed table-no-border table-auto-width table-padded">
    <tr>
        <td class="vt">
            <label>' . _('BPI Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The dummy host for your BPI services') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // get variables that were passed to us
            $hostname = grab_array_var($inargs, "hostname", "");
            $hostname = nagiosccm_replace_user_macros($hostname);

            if (!isset($_POST['backButton'])) //skip validation if back button pressed
            {
                // check for errors
                $errors = 0;
                $errmsg = array();
                //$errmsg[$errors++]="Address: '$address'";
                if (!isset($hostname) || $hostname == '')
                    $errmsg[$errors++] = "No name specified.";

                //check if BPI is installed
                if (!file_exists(get_base_dir() . '/includes/components/nagiosbpi/'))
                    $errmsg[$errors++] = _('Nagios BPI is not installed as a component') . '.
                    <a href="http://exchange.nagios.org/directory/Addons/Components/Nagios-Business-Process-Intelligence/details" title="Download BPI" target="_blank">' . _('Download Nagios BPI') . '</a>';

                if ($errors > 0) {
                    $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                    $result = 1;
                }
            }
            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:
            $hostname = grab_array_var($inargs, "hostname");
            $_SESSION['bpiwizard']['hostname'] = $hostname;
            $groupsArray = $_SESSION['bpiwizard']['data'];
            $output = '';

            $output .= '
<h5 class="ul">' . _('Add Services') . '</h5>
<p><label for="prepend">' . _('Prepend for Service Descriptions (optional)') . '</label></p>
<p><input type="text" name="prepend" class="form-control" id="prepend" value="'._('BPI Process').':"></p>
<p><a href="javascript:void(0)" id="selectAll">' . _('Select/Deselect All') . '</a></p>
<table id="bpigroups" class="table table-condensed table-bordered table-striped table-auto-width">
    <tr>
        <th>' . _('Group ID') . '</th>
        <th>' . _('Display Name') . '</th>
        <th>' . _('Selected') . '</th>
    </tr>';

            // Create checkboxes
            foreach ($groupsArray as $id => $array) {
                $output .= '
    <tr>
        <td>'.$id.'</td>
        <td>'.$array['title'].'</td>
        <td>'.grab_array_var('desc', $array, '').'</td>
        <td><input type="checkbox" name="groups['.$id.']" id="'.$id.'" '.@bpiVal($id).'></td>
    </tr>';
            }

            $output .= '
            </table>
<script type="text/javascript">
$(document).ready(function() {
    var allChecked = false;
    $("#selectAll").click(function() {
        if (allChecked == false) {
            $("#bpigroups input:checkbox").each(function() {
                this.checked = "checked";
            });
            allChecked = true; 
        } else {
            $("#bpigroups input:checkbox").each(function() {
                this.checked = "";
            });
            allChecked = false; 
        }   
    });
});     
</script>
            ';

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            if (!isset($_POST['backButton'])) {
                $groups = grab_array_var($inargs, "groups", array());
                $errors = 0;
                $errmsg = array();

                if (count($groups) == 0 && count($_SESSION['bpiwizard']['groups'] == 0)) {
                    $errmsg[$errors++] = _("You must select at least one group.");
                }

                if ($errors > 0) {
                    $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                    $result = 1;
                }
            }
            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:
            $groups = grab_array_var($inargs, "groups", array());
            $prepend = grab_array_var($inargs, "prepend", "");
            $_SESSION['bpiwizard']['prepend'] = $prepend;
            $_SESSION['bpiwizard']['groups'] = $groups;
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:
            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:
            $hostname = $_SESSION['bpiwizard']['hostname'];
            $groups = $_SESSION['bpiwizard']['groups'];
            $data = $_SESSION['bpiwizard']['data'];
            $prepend = $_SESSION['bpiwizard']['prepend'];
            $objs = array();

            // TODO: add a host template for the dummy host
            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_bpi_host",
                    "host_name" => $hostname,
                    "address" => '127.0.0.1',
                    "_xiwizard" => $wizard_name,
                );
            }

            // See which services we should monitor
            foreach ($groups as $group => $value) {

                $objs[] = array(
                    "type" => OBJECTTYPE_SERVICE,
                    "host_name" => $hostname,
                    "service_description" => $prepend . $data[$group]['title'],
                    "use" => "xiwizard_bpi_service",
                    "check_command" => "check_bpi!{$group}",
                    "_xiwizard" => $wizard_name,
                );
            }

            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;
            unset($_SESSION['bpiwizard']);
            break;

        default:
            break;
    }

    return $output;
}