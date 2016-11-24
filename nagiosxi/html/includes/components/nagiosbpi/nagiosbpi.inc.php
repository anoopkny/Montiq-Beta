<?php
//
// Buisness Process Intelligence (BPI) Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

$bpi_component_name = "nagiosbpi";
bpi_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////


function bpi_component_init()
{
    global $bpi_component_name;
    $versionok = bpi_component_checkversion();

    $desc = '';
    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 5.2.0 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $bpi_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Advanced grouping and dependency tool for viewing business processes. Can be used for specialized checks. ") . $desc,
        COMPONENT_TITLE => "Nagios BPI",
        COMPONENT_VERSION => "2.5.0",
        COMPONENT_DATE => '08/09/2016',
        COMPONENT_CONFIGFUNCTION => "bpi_component_config_func",
        COMPONENT_PROTECTED => true,
        COMPONENT_ENCRYPTED => true,
        COMPONENT_TYPE => COMPONENT_TYPE_CORE
    );

    register_component($bpi_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'bpi_component_addmenu');
    }
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////


function bpi_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 511)
        return false;
    return true;
}


function bpi_component_addmenu($arg = null)
{
    global $bpi_component_name;
    $urlbase = get_component_url_base($bpi_component_name);

    $mi = find_menu_item(MENU_HOME, "menu-home-servicegroupgrid", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order + 0.1;
    add_menu_item(MENU_HOME, array(
        "type" => "linkspacer",
        "title" => "",
        "id" => "menu-home-bpi_spacer",
        "order" => $neworder,
        "opts" => array()
    ));

    $neworder = $neworder + 0.1;
    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => "BPI",
        "id" => "menu-home-bpi",
        "order" => $neworder,
        "opts" => array(
            "href" => $urlbase . "/index.php",
            "icon" => "fa-briefcase"
        )
    ));
}


function bpi_component_config_func($mode = "", $inargs, &$outargs, &$result)
{
    // Initialize return code and output
    $result = 0;
    $output = "";

    switch ($mode) {
        case COMPONENT_CONFIGMODE_GETSETTINGSHTML:

            // Initial values
            $configfile = is_null(get_option('bpi_configfile')) ? get_root_dir() . '/etc/components/bpi.conf' : get_option('bpi_configfile');
            $backupfile = is_null(get_option('bpi_backupfile')) ? get_root_dir() . '/etc/components/bpi.conf.backup' : get_option('bpi_backupfile');
            $logfile = is_null(get_option('bpi_logfile')) ? get_root_dir() . '/var/components/bpi.log' : get_option('bpi_logfile');
            $ignore_handled = (get_option('bpi_ignore_handled') == 'on') ? 'checked="checked"' : '';
            $xmlfile = is_null(get_option('bpi_xmlfile')) ? get_root_dir() . '/var/components/bpi.xml' : get_option('bpi_xmlfile');
            $xmlthreshold = is_null(get_option('bpi_xmlthreshold')) ? 90 : get_option('bpi_xmlthreshold');
            $multiplier = is_null(get_option('bpi_multiplier')) ? 30 : get_option('bpi_multiplier');
            $showallgroups = (get_option('bpi_showallgroups') == 'on') ? 'checked="checked" ' : '';
            $output_ok = get_option("bpi_output_ok", 'Group health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).');
            $output_warn = get_option("bpi_output_warn", 'Group health below warning threshold of $WARNTHRESHHOLD$%! Health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).');
            $output_crit = get_option("bpi_output_crit", 'Group health below critical threshold of $CRITTHRESHHOLD$%! Health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).');

            $output = '

<script type="text/javascript">
function reset_bpi_status_defaults() {
    $("#bpi_output_ok").val("Group health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).");
    $("#bpi_output_warn").val("Group health below warning threshold of $WARNTHRESHHOLD$%! Health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).");
    $("#bpi_output_crit").val("Group health below critical threshold of $CRITTHRESHHOLD$%! Health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).");
}
</script>

<h5 class="ul">' . _('Nagios BPI Settings') . '</h5>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('BPI Group Configuration File') . ':</label>
        </td>
        <td>
            <input type="text" size="45" name="bpi_configfile" id="bpi_configfile" value="' . encode_form_val($configfile) . '" class="form-control">
            <div class="subtext">' . _('The directory location of your bpi.conf file.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('BPI Group Backup Configuration File') . ':</label>
        </td>
        <td>
            <input type="text" size="45" name="bpi_backupfile" id="bpi_backupfile" value="' . encode_form_val($backupfile) . '" class="form-control">
            <div class="subtext">' . _('The directory location of your bpi.conf.backup file.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('BPI Log File') . ':</label>
        </td>
        <td>
            <input type="text" size="45" name="bpi_logfile" id="bpi_logfile" value="' . encode_form_val($logfile) . '" class="form-control">
            <div class="subtext">' . _('The directory location of your bpi.log file.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('BPI XML Cache') . ':</label>
        </td>
        <td>
            <input type="text" size="45" name="bpi_xmlfile" id="bpi_xmlfile" value="' . encode_form_val($xmlfile) . '" class="form-control">
            <div class="subtext">' . _('The directory location of your bpi.xml file. This file is used to cache check results for BPI service checks and to decrease CPU usage from BPI checks.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('XML Cache Threshold') . ':</label></td>
        <td>
            <input type="text" size="4" name="bpi_xmlthreshold" id="bpi_xmlthreshold" value="' . encode_form_val($xmlthreshold) . '" class="form-control">
            <div class="subtext">' . _('This is the age limit for cached BPI check result data.  If a BPI service check detects this file as being
            too old, it will recalculate the status of all BPI groups and cache to the XML file.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('AJAX Multiplier') . '</label>
        </td>
        <td>
            <input type="text" size="4" name="bpi_multiplier" id="bpi_multiplier" value="' . encode_form_val($multiplier) . '" class="form-control">
            <div class="subtext">' . _('The AJAX multiplier is the amount of time before the data on the BPI page reloads. For large installations use a larger number to reduce CPU usage.') . '</div>
        </td>
    </tr>
    <tr>
        <td>
            <label for="problemhandler">' . _('Logic Handling For Problem States') . '</label>
        </td>
        <td>
            <input type="checkbox" ' . $ignore_handled . ' name="bpi_ignore_handled" id="bpi_ignore_handled">
            ' . _('Ignore host and service problems that are acknowledged or in scheduled downtime.') . ' ' . _("Handled problems will not be factored into the group's problem percentage.") . '
        </td>
    </tr>
    <tr>
        <td>
            <label for="showallgroups">' . _('Show All Groups To All Users') . '</label>
        </td>
        <td>
            <input type="checkbox" ' . $showallgroups . ' name="bpi_showallgroups" id="bpi_showallgroups">
            ' . _('This will bypass the normal permissions schemes for BPI groups and show all groups to all users. Host and service permissions for Nagios objects will still be honored, so contacts will still only see hosts or services that they are authorized for.') . '
        </td>
    </tr>
</table>
<h5 class="ul">' . _('Nagios BPI Status Text') . '</h5>
<p>' . _('You can use the following substitutions in your custom Status Texts') . ':
    <ul>
        <li><strong>$HEALTH$</strong> - ' . _("The overall health of the BPI group. (Percentage)") . '</li>
        <li><strong>$PROBLEMPERCENTAGE$</strong> - ' . _("The percentage of the group health that is a problem. (e.g. 100% - $HEALTH$). (Percentage)") . '</li>
        <li><strong>$MEMBERCOUNT$</strong> - ' . _("The total amount of members in the BPI group. (Integer)") . '</li>
        <li><strong>$PROBLEMCOUNT$</strong> - ' . _("The total amount of problem members in the BPI group. (Integer)") . '</li>
        <li><strong>$CRITTHRESHHOLD$</strong> - ' . _("The current critical threshhold. (Percentage)") . '</li>
        <li><strong>$WARNTHRESHHOLD$</strong> - ' . _("The current warning threshhold. (Percantage)") . '</li>
    </ul>
</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('OK Status Text') . ':</label>
        </td>
        <td>
            <input type="text" size="120" name="bpi_output_ok" id="bpi_output_ok" value="' . $output_ok . '" class="form-control">
            <div class="subtext">' . _('The status text to display for OK states.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('WARNING Status Text') . ':</label>
        </td>
        <td>
            <input type="text" size="120" name="bpi_output_warn" id="bpi_output_warn" value="' . $output_warn . '" class="form-control">
            <div class="subtext">' . _('The status text to display for WARNING states.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('CRITICAL Status Text') . ':</label>
        </td>
        <td>
            <input type="text" size="120" name="bpi_output_crit" id="bpi_output_crit" value="' . $output_crit . '" class="form-control">
            <div class="subtext">' . _('The status text to display for CRITICAL states.') . '</div>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <button type="button" class="btn btn-xs btn-info" id="resetbpistatusdefaults" name="resetbpistatusdefaults" onclick="reset_bpi_status_defaults();">'. _("Reset BPI Status Texts to Defaults") . '</button>
        </td>
    </tr>
</table>';

            break;

        // Save config settings for component
        case COMPONENT_CONFIGMODE_SAVESETTINGS:

            $configfile = grab_array_var($inargs, "bpi_configfile", get_root_dir() . "/etc/bpi.conf");
            $backupfile = grab_array_var($inargs, "bpi_backupfile", get_root_dir() . "/etc/bpi.conf.backup");
            $logfile = grab_array_var($inargs, "bpi_logfile", "/usr/local/nagios/var/bpi.log");
            $xmlfile = grab_array_var($inargs, "bpi_xmlfile", "/usr/local/nagios/var/bpi.xml");
            $xmlthreshold = grab_array_var($inargs, "bpi_xmlthreshold", 90);
            $multiplier = grab_array_var($inargs, "bpi_multiplier", 30);
            $ignore_handled = grab_array_var($inargs, "bpi_ignore_handled", false);
            $showallgroups = grab_array_var($inargs, "bpi_showallgroups", false);
            $output_ok = grab_array_var($inargs, "bpi_output_ok", "Group health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).");
            $output_warn = grab_array_var($inargs, "bpi_output_warn", "Group health below warning threshold of $WARNTHRESHHOLD$%! Health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).");
            $output_crit = grab_array_var($inargs, "bpi_output_crit", "Group health below critical threshold of $CRITTHRESHHOLD$%! Health is $HEALTH$% with $PROBLEMCOUNT$ problem(s).");

            // Validate variables
            $errors = 0;
            $errmsg = array();

            // Handle errors
            if ($errors > 0) {
                $outargs[COMPONENT_ERROR_MESSAGES] = $errmsg;
                $result = 1;
                return '';
            }

            set_option("bpi_configfile", $configfile);
            set_option("bpi_backupfile", $backupfile);
            set_option("bpi_logfile", $logfile);
            set_option("bpi_ignore_handled", $ignore_handled);
            set_option("bpi_xmlfile", $xmlfile);
            set_option("bpi_xmlthreshold", $xmlthreshold);
            set_option("bpi_multiplier", $multiplier);
            set_option("bpi_showallgroups", $showallgroups);
            set_option("bpi_output_ok", $output_ok);
            set_option("bpi_output_warn", $output_warn);
            set_option("bpi_output_crit", $output_crit);
            break;

        default:
            break;

    }

    return $output;
}
