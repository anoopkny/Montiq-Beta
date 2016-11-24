<?php
//
// Nagiostats Config Wizard
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

nagiostats_configwizard_init();

function nagiostats_configwizard_init()
{
    $name = "nagiostats";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.2.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor internal performance of your XI server."),
        CONFIGWIZARD_DISPLAYTITLE => _("Nagiostats Wizard"),
        CONFIGWIZARD_FUNCTION => "nagiostats_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "nagiostats.png",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * Error suppressing function for printing session variables -> used for repopulating the form when going "back"
 *
 * @param $value
 * @param $type
 *
 * @return string
 */
function val($value, $type)
{
    if (!isset($_SESSION['nagiostats'])) return;
    switch ($type) {
        case 'svc':
            if (isset($_SESSION['nagiostats']['svc'][$value]) && $_SESSION['nagiostats']['svc'][$value] != '') return 'checked="checked"';
            break;

        case 'ntf':
            if (isset($_SESSION['nagiostats']['ntf'][$value]) && $_SESSION['nagiostats']['svc'][$value] != '') return 'checked="checked"';
            break;

        case 'warn':
        case 'crit':
        default:
            if (isset($_SESSION['nagiostats'][$type][$value]) && $value != '') return $_SESSION['nagiostats'][$type][$value];
    }
}


/**
 * @param string $mode
 * @param        $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function nagiostats_configwizard_func($mode = "", $inargs, &$outargs, &$result)
{

    $wizard_name = "nagiostats";

    // initialize return code and output
    $result = 0;
    $output = "";

    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;


    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            unset($_SESSION['nagiostats']);
            $hostname = isset($_SESSION['nagiostats']['hostname']) ? $_SESSION['nagiostats']['hostname'] : 'localhost';
            $hostname = nagiosccm_replace_user_macros($hostname);

            $output = '
            <h5 class="ul">'._('Verify Localhost Host Name').'</h5>
            <p>' . _('This wizard will allow you to monitor performance statistics of the local Nagios server.') . '</p>
            <table class="table table-condensed table-no-border table-auto-width">
                <tr>
                    <td class="vt">
                        <label>'._('Host Name').'</label>
                    </td>
                    <td>
                        <input type="text" name="hostname" id="hostname" value="' . $hostname . '" class="form-control">
                        <div class="subtext">' . _('This wizard assumes the localhost has already been created.') . '</div>
                    </td>
                </tr>
            </table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            $hostname = grab_array_var($inargs, "hostname") == '' ? 'localhost' : htmlentities(grab_array_var($inargs, "hostname"));
            $_SESSION['nagiostats']['hostname'] = $hostname;

            $latency = _("The amount of seconds that a scheduled check lagged behind its scheduled check time. For instance, if a check was scheduled for 03:14:15 and it didn't get executed until 03:14:17, there would be a check latency of 2.0 seconds. On-demand checks have a latency of zero seconds.");

            $buffer = _("Buffers slots are used to hold external commands that have been read from the external command file (by a separate thread) before they are processed by the Nagios daemon. If your Nagios daemon is receiving a lot of passive checks or external commands, you could end up in a situation where the buffers are always full. This results in child processes (external scripts, NSCA daemon, etc.) blocking when they attempt to write to the external command file.");

            $execTime = _("A number indicating the amount of seconds that the check took to execute (i.e. the amount of time the check was executing).");
            
            // Big checklist of services, with inputs for warning, critical, and notifications_enabled
            $output = '
<h5 class="ul">' . _('Nagiostats Information') . '</h5>
<p>' . _('Nagiostats is a binary included with Nagios that is used to monitor internal performance. This wizard allows users to select from a list of options to analyze local Nagios server performance, and produce graphs and reports over time. Not all checks are relevant for every install, so select checks based on your monitoring environment. See the Nagios Core Documentation on') . '<a href="http://nagios.sourceforge.net/docs/3_0/tuning.html" title="'._('Performance Tuning').'" target="_blank">' . _('Tuning Nagios Performance') . '</a>. <strong>' . _('Important Notes') . ': </strong>' . _('selecting the 1min checks will create a check that runs every 1min, 15min checks run every 15min.') . ' <strong>' . _('Enter all warning and critical thresholds as integers.') . '</strong> ' . _('All warning and critical thresholds are optional.') . '</p>
            
<p><a href="javascript:void(0)" id="selectAll">' . _('Select/Deselect All Services') . '</a> / <a href="javascript:void(0)" id="selectNtf">' . _('Select/Deselect All Notifications') . '</a></p>

<table class="table table-condensed table-auto-width table-striped">
    <thead>
        <tr>
            <th>' . _('Check Name') . '</th>
            <th>' . _('Selected') . '</th>
            <th>' . _('Notifications') . '</th>
            <th><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> '._('Warning').'</th>
            <th><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> '._('Critical').'</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <label for="ahc1">
                    <a href="http://nagios.sourceforge.net/docs/3_0/activechecks.html" title="Active Checks Explained" target="_blank">' . _('Active') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/hostchecks.html" target="_blank" title="Host Checks Explained">' . _('Host Checks') . '</a> 1min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="ahc1" name="svc[ahc1]" ' . @val("ahc1", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[ahc1]" ' . @val("ahc1", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[ahc1]" size="4" value="' . @val("ahc1", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[ahc1]" size="4" value="' . @val("ahc1", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="ahc5">
                    <a href="http://nagios.sourceforge.net/docs/3_0/activechecks.html" title="Active Checks Explained" target="_blank">' . _('Active') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/hostchecks.html" target="_blank" title="Host Checks Explained">' . _('Host Checks') . '</a> 5min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="ahc5" name="svc[ahc5]" ' . @val("ahc5", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[ahc5]" ' . @val("ahc5", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[ahc5]" size="4" value="' . @val("ahc5", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[ahc5]" size="4" value="' . @val("ahc5", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="ahc1">
                    <a href="http://nagios.sourceforge.net/docs/3_0/activechecks.html" title="Active Checks Explained" target="_blank">' . _('Active') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/hostchecks.html" target="_blank" title="Host Checks Explained">' . _('Host Checks') . '</a> 15min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="ahc15" name="svc[ahc15]" ' . @val("ahc15", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[ahc15]" ' . @val("ahc15", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[ahc15]" size="4" value="' . @val("ahc15", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[ahc15]" size="4" value="' . @val("ahc15", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="asc1">
                    <a href="http://nagios.sourceforge.net/docs/3_0/activechecks.html" title="Active Checks Explained" target="_blank">' . _('Active') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/servicechecks.html" title="Service Checks Explained" target="_blank">' . _('Service Checks') . '</a> 1min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="asc1" name="svc[asc1]" ' . @val("asc1", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[asc1]" ' . @val("asc1", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[asc1]" size="4" value="' . @val("asc1", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[asc1]" size="4" value="' . @val("asc1", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="asc5">
                    <a href="http://nagios.sourceforge.net/docs/3_0/activechecks.html" title="Active Checks Explained" target="_blank">' . _('Active') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/servicechecks.html" title="Service Checks Explained" target="_blank">' . _('Service Checks') . '</a> 5min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="asc5" name="svc[asc5]" ' . @val("asc5", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[asc5]" ' . @val("asc5", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[asc5]" size="4" value="' . @val("asc5", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[asc5]" size="4" value="' . @val("asc5", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="asc15">
                    <a href="http://nagios.sourceforge.net/docs/3_0/activechecks.html" title="Active Checks Explained" target="_blank">' . _('Active') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/servicechecks.html" title="Service Checks Explained" target="_blank">' . _('Service Checks') . '</a> 15min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="asc15" name="svc[asc15]" ' . @val("asc15", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[asc15]" ' . @val("asc15", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[asc5]" size="4" value="' . @val("asc15", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[asc5]" size="4" value="' . @val("asc15", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="phc1">
                    <a href="http://nagios.sourceforge.net/docs/3_0/passivechecks.html" title="Passive Checks Explained" target="_blank">' . _('Passive') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/hostchecks.html" target="_blank" title="Host Checks Explained">' . _('Host Checks') . '</a> 1min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="phc1" name="svc[phc1]" ' . @val("phc1", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[phc1]" ' . @val("phc1", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[phc1]" size="4" value="' . @val("phc1", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[phc1]" size="4" value="' . @val("phc1", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="phc5">
                    <a href="http://nagios.sourceforge.net/docs/3_0/passivechecks.html" title="Passive Checks Explained" target="_blank">' . _('Passive') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/hostchecks.html" target="_blank" title="Host Checks Explained">' . _('Host Checks') . '</a> 5min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="phc5" name="svc[phc5]" ' . @val("phc5", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[phc5]" ' . @val("phc5", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[phc5]" size="4" value="' . @val("phc5", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[phc5]" size="4" value="' . @val("phc5", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="phc1">
                    <a href="http://nagios.sourceforge.net/docs/3_0/passivechecks.html" title="Passive Checks Explained" target="_blank">' . _('Passive') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/hostchecks.html" target="_blank" title="Host Checks Explained">' . _('Host Checks') . '</a> 15min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="phc15" name="svc[phc15]" ' . @val("phc15", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[phc15]" ' . @val("phc15", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[phc15]" size="4" value="' . @val("phc15", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[phc15]" size="4" value="' . @val("phc15", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="psc1">
                    <a href="http://nagios.sourceforge.net/docs/3_0/passivechecks.html" title="Passive Checks Explained" target="_blank">' . _('Passive') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/servicechecks.html" title="Service Checks Explained" target="_blank">' . _('Service Checks') . '</a> 1min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="psc1" name="svc[psc1]" ' . @val("psc1", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[psc1]" ' . @val("psc1", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[psc1]" size="4" value="' . @val("psc1", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[psc1]" size="4" value="' . @val("psc1", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="psc5">
                    <a href="http://nagios.sourceforge.net/docs/3_0/passivechecks.html" title="Passive Checks Explained" target="_blank">' . _('Passive') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/servicechecks.html" title="Service Checks Explained" target="_blank">' . _('Service Checks') . '</a> 5min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="psc5" name="svc[psc5]" ' . @val("psc5", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[psc5]" ' . @val("psc5", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[psc5]" size="4" value="' . @val("psc5", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[psc5]" size="4" value="' . @val("psc5", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td>
                <label for="psc15">
                    <a href="http://nagios.sourceforge.net/docs/3_0/passivechecks.html" title="Passive Checks Explained" target="_blank">' . _('Passive') . '</a>
                    <a href="http://nagios.sourceforge.net/docs/3_0/servicechecks.html" title="Service Checks Explained" target="_blank">' . _('Service Checks') . '</a> 15min
                </label>
            </td>
            <td><input class="svc" type="checkbox" id="psc15" name="svc[psc15]" ' . @val("psc15", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[psc15]" ' . @val("psc15", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[psc5]" size="4" value="' . @val("psc15", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[psc5]" size="4" value="' . @val("psc15", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td><label for="hxt">' . _('Average Host') . ' <a href="javascript:void(0)" title="' . $execTime . '">' . _('Execution Time') . '</a></label></td>
            <td><input class="svc" type="checkbox" id="hxt" name="svc[hxt]" ' . @val("hxt", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[hxt]" ' . @val("hxt", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[hxt]" size="4" value="' . @val("hxt", "warn") . '" class="form-control condensed"> ms </td>
            <td class="crit"><input type="text" name="crit[hxt]" size="4" value="' . @val("hxt", "crit") . '" class="form-control condensed"> ms</td>
        </tr>
        <tr>
            <td><label for="sxt">' . _('Average Service') . ' <a href="javascript:void(0)" title="' . $execTime . '">' . _('Execution Time') . '</a></label></td>
            <td><input class="svc" type="checkbox" id="sxt" name="svc[sxt]" ' . @val("sxt", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[sxt]" ' . @val("sxt", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[sxt]" size="4" value="' . @val("sxt", "warn") . '" class="form-control condensed"> ms</td>
            <td class="crit"><input type="text" name="crit[sxt]" size="4" value="' . @val("sxt", "crit") . '" class="form-control condensed"> ms</td>
        </tr>
        <tr>
            <td><label for="ahlat">' . _('Average Active Host') . ' <a href="javascript:void(0)" title="' . $latency . '">' . _('Latency') . '</a></label></td>
            <td><input class="svc" type="checkbox" id="ahlat" name="svc[ahlat]" ' . @val("ahlat", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[ahlat]" ' . @val("ahlat", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[ahlat]" size="4" value="' . @val("ahlat", "warn") . '" class="form-control condensed"> ms</td>
            <td class="crit"><input type="text" name="crit[ahlat]" size="4" value="' . @val("ahlat", "crit") . '" class="form-control condensed"> ms</td>
        </tr>
        <tr><td><label for="aslat">' . _('Average Active Service') . ' <a href="javascript:void(0)" title="' . $latency . '">' . _('Latency') . '</a></label></td>
            <td><input class="svc" type="checkbox" id="aslat" name="svc[aslat]" ' . @val("aslat", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[aslat]" ' . @val("aslat", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[aslat]" size="4" value="' . @val("aslat", "warn") . '" class="form-control condensed"> ms</td>
            <td class="crit"><input type="text" name="crit[aslat]" size="4" value="' . @val("aslat", "crit") . '" class="form-control condensed"> ms</td>
        </tr>
        <tr><td><label for="phlat">' . _('Average Passive Host') . ' <a href="javascript:void(0)" title="' . $latency . '">' . _('Latency') . '</a></label></td>
            <td><input class="svc" type="checkbox" id="phlat" name="svc[phlat]" ' . @val("phlat", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[phlat]" ' . @val("phlat", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[phlat]" size="4" value="' . @val("phlat", "warn") . '" class="form-control condensed"> ms</td>
            <td class="crit"><input type="text" name="crit[phlat]" size="4" value="' . @val("phlat", "crit") . '" class="form-control condensed"> ms</td>
        </tr>
        <tr><td><label for="pslat">' . _('Average Passive Service') . ' <a href="javascript:void(0)" title="' . $latency . '">' . _('Latency') . '</a></label></td>
            <td><input class="svc" type="checkbox" id="pslat" name="svc[pslat]" ' . @val("pslat", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[pslat]" ' . @val("pslat", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[pslat]" size="4" value="' . @val("pslat", "warn") . '" class="form-control condensed"> ms</td>
            <td class="crit"><input type="text" name="crit[pslat]" size="4" value="' . @val("pslat", "crit") . '" class="form-control condensed"> ms</td>
        </tr>
        <tr>
            <td><label for="eco1"><a href="http://nagios.sourceforge.net/docs/3_0/configmain.html#external_command_buffer_slots" title="External Commands Explained" target="_blank">' . _('External Commands') . '</a>  ' . _('Used') . ' 1min</label></td>
            <td><input class="svc" type="checkbox" id="eco1" name="svc[eco1]" ' . @val("eco1", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[eco1]" ' . @val("eco1", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[eco1]" size="4" value="' . @val("eco1", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[eco1]" size="4" value="' . @val("eco1", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td><label for="eco5"><a href="http://nagios.sourceforge.net/docs/3_0/configmain.html#external_command_buffer_slots" title="External Commands Explained" target="_blank">' . _('External Commands') . '</a>  ' . _('Used') . ' 5min</label></td>
            <td><input class="svc" type="checkbox" id="eco5" name="svc[eco5]" ' . @val("eco5", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[eco5]" ' . @val("eco5", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[eco5]" size="4" value="' . @val("eco5", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[eco5]" size="4" value="' . @val("eco5", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td><label for="eco15"><a href="http://nagios.sourceforge.net/docs/3_0/configmain.html#external_command_buffer_slots" title="External Commands Explained" target="_blank">' . _('External Commands') . '</a>  ' . _('Used') . ' 15min</label></td>
            <td><input class="svc" type="checkbox" id="eco15" name="svc[eco15]" ' . @val("eco15", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[eco15]" ' . @val("eco15", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[eco15]" size="4" value="' . @val("eco15", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[eco15]" size="4" value="' . @val("eco15", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td><label for="hprob">' . _('Total Host Problems') . '</label></td>
            <td><input class="svc" type="checkbox" id="hprob" name="svc[hprob]" ' . @val("hprob", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[hprob]" ' . @val("hprob", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[hprob]" size="4" value="' . @val("hprob", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[hprob]" size="4" value="' . @val("hprob", "crit") . '" class="form-control condensed"></td>
        </tr>
        <tr>
            <td><label for="sprob">' . _('Total Service Problems') . '</label></td>
            <td><input class="svc" type="checkbox" id="sprob" name="svc[sprob]" ' . @val("sprob", "svc") . '></td>
            <td><input type="checkbox" class="ntf" name="ntf[sprob]" ' . @val("sprob", "ntf") . '></td>
            <td class="warn"><input type="text" name="warn[sprob]" size="4" value="' . @val("sprob", "warn") . '" class="form-control condensed"></td>
            <td class="crit"><input type="text" name="crit[sprob]" size="4" value="' . @val("sprob", "crit") . '" class="form-control condensed"></td>
        </tr>
    </tbody>
</table>
    
<script type="text/javascript">
$(document).ready(function() {
    $("#nagiostatInfo").hide(); 
    
    var allChecked = false; 
    $("#selectAll").click(function () {
        if(allChecked==false) {
            $(".svc:checkbox").each(function (){
                this.checked = "checked";
            });
            allChecked=true; 
        }
        else {
            $(".svc:checkbox").each(function () {
                this.checked = "";
            });
            allChecked = false; 
        }   
    });
    
    var ntfChecked = false;
    $("#selectNtf").click(function () {
        if(ntfChecked==false) {
            $(".ntf:checkbox").each(function (){
                this.checked = "checked";
            });
            ntfChecked=true; 
        }
        else {
            $(".ntf:checkbox").each(function () {
                this.checked = "";
            });
            ntfChecked=false; 
        }   
    });
});  
</script>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:

            $outargs[CONFIGWIZARD_HIDDEN_OPTIONS] = array();

            $outargs[CONFIGWIZARD_OVERRIDE_OPTIONS] = array(
                "max_check_attempts" => 3,
            );
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            $output .= "<p><strong>" . _('Note:') . " </strong>" . _('Enabling Notifications for this wizard are specified on the previous page. Click') . "
                    <strong>" . _('Next') . "</strong> " . _('to set up contacts and notification details.') . "";
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            $services = grab_array_var($inargs, "svc", array());
            $notifications = grab_array_var($inargs, "ntf", array());
            $warnings = grab_array_var($inargs, "warn", array());
            $criticals = grab_array_var($inargs, "crit", array());

            $_SESSION['nagiostats']['svc'] = $services; //save data
            $_SESSION['nagiostats']['ntf'] = $notifications;
            $_SESSION['nagiostats']['warn'] = $warnings;
            $_SESSION['nagiostats']['crit'] = $criticals;
            //serialize and encode arrays to pass along

            $output .= '<p>' . _('Click') . ' <b>' . _('Next') . '</b> ' . _('to continue.') . '</p>';

            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE4OPTS:

            $outargs[CONFIGWIZARD_HIDDEN_OPTIONS] = array(
                CONFIGWIZARD_HIDE_NOTIFICATION_OPTIONS,
                //CONFIGWIZARD_HIDE_CHECK_INTERVAL,
                //CONFIGWIZARD_HIDE_RETRY_INTERVAL,
            );

            $output .= _("Notification options for each service are defined on the previous page....");
            $result = CONFIGWIZARD_HIDE_OPTIONS;

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:
            //pull tmp session vars
            $services = $_SESSION['nagiostats']['svc'];
            $notifications = $_SESSION['nagiostats']['ntf'];
            $warnings = $_SESSION['nagiostats']['warn'];
            $criticals = $_SESSION['nagiostats']['crit'];
            $hostname = $_SESSION['nagiostats']['hostname'];

            $objs = array();
            //change to $key value
            $i = 0;
            foreach ($services as $key => $value) {
                $notifs = 0;
                if (array_key_exists($key, $notifications) && $notifications[$key] == 'on') $notifs = 1;
                $warn = isset($warnings[$key]) ? $warnings[$key] : '';
                $crit = isset($criticals[$key]) ? $criticals[$key] : '';


                //default config properties
                $objs[$i] = array(
                    "type" => OBJECTTYPE_SERVICE,
                    "host_name" => $hostname,
                    "service_description" => '', //to be filled in below
                    "use" => "xiwizard_nagiostats_service",
                    "check_command" => "check_nagiosxi_performance!{$key}!{$warn}!{$crit}",
                    "_xiwizard" => $wizard_name,
                    "notifications_enabled" => $notifs,
                    "check_interval" => 5,
                );
                // see which services we should monitor
                switch ($key) { //service descriptions          //TODO  add check intervals
                    //active checks
                    case "ahc1":
                        $objs[$i]['service_description'] = 'ActiveHostChecks 1min';
                        $objs[$i]['check_interval'] = 1;
                        break;
                    case "ahc5":
                        $objs[$i]['service_description'] = 'ActiveHostChecks 5min';
                        break;
                    case "ahc15":
                        $objs[$i]['service_description'] = 'ActiveHostChecks 15min';
                        $objs[$i]['check_interval'] = 15;
                        break;
                    case "asc1":
                        $objs[$i]['service_description'] = 'ActiveServiceChecks 1min';
                        $objs[$i]['check_interval'] = 1;
                        break;
                    case "asc5":
                        $objs[$i]['service_description'] = 'ActiveServiceChecks 5min';
                        break;
                    case "asc15":
                        $objs[$i]['service_description'] = 'ActiveServiceChecks 15min';
                        $objs[$i]['check_interval'] = 15;
                        break;
                    //passive checks
                    case "phc1" :
                        $objs[$i]['service_description'] = 'PassiveHostChecks 1min';
                        $objs[$i]['check_interval'] = 1;
                        break;
                    case "phc5" :
                        $objs[$i]['service_description'] = 'PassiveHostChecks 5min';
                        break;
                    case "phc15" :
                        $objs[$i]['service_description'] = 'PassiveHostChecks 15min';
                        $objs[$i]['check_interval'] = 15;
                        break;
                    case "psc1" :
                        $objs[$i]['service_description'] = 'PassiveServiceChecks 1min';
                        $objs[$i]['check_interval'] = 1;
                        break;
                    case "psc5" :
                        $objs[$i]['service_description'] = 'PassiveServiceChecks 5min';
                        break;
                    case "psc15" :
                        $objs[$i]['service_description'] = 'PassiveServiceChecks 15min';
                        $objs[$i]['check_interval'] = 15;
                        break;

                    //execution and latency times
                    case "hxt":
                        $objs[$i]['service_description'] = 'AvgHostExecTime';
                        break;
                    case "sxt":
                        $objs[$i]['service_description'] = 'AvgServiceExecTime';
                        break;
                    case "ahlat":
                        $objs[$i]['service_description'] = 'AvgActiveHostLatency';
                        break;
                    case "aslat":
                        $objs[$i]['service_description'] = 'AvgActiveServiceLatency';
                        break;
                    case "phlat":
                        $objs[$i]['service_description'] = 'AvgPassiveHostLatency';
                        break;
                    case "pslat":
                        $objs[$i]['service_description'] = 'AvgPassiveHostLatency';
                        break;

                    // external command usage
                    case "eco1":
                        $objs[$i]['service_description'] = 'ExternalCommandsUsed 1min';
                        $objs[$i]['check_interval'] = 1;
                        break;
                    case "eco5":
                        $objs[$i]['service_description'] = 'ExternalCommandsUsed 5min';
                        break;
                    case "eco15":
                        $objs[$i]['service_description'] = 'ExternalCommandsUsed 1min';
                        $objs[$i]['check_interval'] = 1;
                        break;

                    // problem count
                    case "sprob":
                        $objs[$i]['service_description'] = 'Total Service Problems';
                        break;
                    case "hprob":
                        $objs[$i]['service_description'] = 'Total Host Problems';
                        break;
                    default:
                        unset($objs[$i]); // unset object if there's no service description defined
                        break;
                }

                $i++; // increment for objects

            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;
            unset($_SESSION['nagiostats']); // kill temp session data

            break;

        default:
            break;
    }

    return $output;
}