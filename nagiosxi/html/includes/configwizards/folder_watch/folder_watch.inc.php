<?php
// FOLDER WATCH WIZARD
//
// Copyright (c) 2008-2014 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: folder_watch.inc.php 1283 2014-22-14 10:47:10 lgroschen $

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

// run the initialization function
folder_watch_configwizard_init();

function folder_watch_configwizard_init() {
    
    $name = "folder_watch";
    
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.0.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor directories or files with a Perl driven regex that can query count, size or age."),
        CONFIGWIZARD_DISPLAYTITLE => _("Folder Watch"),
        CONFIGWIZARD_FUNCTION => "folder_watch_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "folder_watch.png",
        CONFIGWIZARD_FILTER_GROUPS => array('linux','otheros'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
        );
        
    register_configwizard($name,$args);
    }

/**
 * @return array
 */
function folder_watch_configwizard_check_prereqs() {

    $errors = array();

    if(!file_exists("/usr/local/nagios/libexec/folder_watch.pl")) {
        $errors[] = _('It looks like you are missing the folder_watch.pl plugin on your Nagios XI server. To use this wizard you must install the plugin on your server.');
    }

    // Run the plugin to see if the perl module is installed
    $cmd = "perl -MDate::Parse -e ';'";
    $proc = proc_open($cmd, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (is_resource($proc)) {
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($proc);
    }

    // Verify there were no errors running the plugin and that the perl module exists
    if (!empty($stderr) || strpos($stdout, "Can't locate") !== false) {
        $errors[] = _('It looks like the install script has failed. You may need to run the script as root:') . '<br>
            <b>sh /usr/local/nagiosxi/html/includes/configwizards/folder_watch/install.sh</b>';
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
function folder_watch_configwizard_func($mode = "", $inargs = null, &$outargs, &$result) {

    $wizard_name = "folder_watch";

    // initialize return code and output
    $result = 0;
    $output = "";
    
    // initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch($mode){
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $errors = folder_watch_configwizard_check_prereqs();

            if($errors) {
                $output .= '<div class="message"><ul class="errorMessage">';
                foreach($errors as $error) {
                    $output .= "<li><p>$error</p></li>";
                }
                $output .= '</ul></div>';
            } else {
                
                $address = grab_array_var($inargs, "address", "");
                $address = nagiosccm_replace_user_macros($address);

                $dirpath = grab_array_var($inargs, "dirpath");
                $dirpath = nagiosccm_replace_user_macros($dirpath);

                $ssh = grab_array_var($inargs, "ssh", "on");
                $ssh_username = grab_array_var($inargs, "ssh_username", "nagios");

                $output = '
<h5 class="ul">' . _("Folder Path and Server Information") . '</h5>

<div class="message"><ul class="actionMessage">' . _("<b>Folder Watch by SSH:</b><br>To configure the remote host to allow open SSH for this wizard refer to this document: <a href='https://assets.nagios.com/downloads/nagiosxi/docs/Monitoring_Hosts_Using_SSH.pdf' target='_blank'>Monitoring_Hosts_Using_SSH.pdf</a>") . "<br><br>" . _("This Wizard will not run properly if you are not able to login with passwordless SSH into the remote host.") . '<li></li></ul></div>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Folder to Watch') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="dirpath" id="dirpath" value="' . htmlentities($dirpath) . '" class="textfield form-control" />
            <div class="subtext">' . _('The path of the folder or file you\'d like to monitor.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Server Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="'.htmlentities($address).'" class="textfield form-control" />
            <div class="subtext">' . _('The IP address or FQDNS name of the server you wish to connect to using SSH.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('SSH') . ':</label>
        </td>
        <td>
            <input type="checkbox" class="checkbox" id="ssh" name="ssh" ' . is_checked($ssh) . ' />
            <div class="subtext">' . _('If this box is checked the wizard will SSH into a remote host.  If you want to run this wizard locally uncheck this box.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Username') . ':</label>
        </td>
        <td>
            <input type="text" size="15" name="ssh_username" id="ssh_username" value="' . htmlentities($ssh_username) . '" class="textfield form-control" /><br>
        </td>
    </tr>
</table>

<script type="text/javascript">
    $(document).ready(function() {
        var ssh_check = document.getElementById("ssh");

        $(ssh_check).click(function() {
            if ($(ssh_check).is(":checked") == true) {
                $(\'#ssh_username\').removeAttr("disabled");
                $(\'#ssh_username\').attr("enabled", "enabled");
            } 

            if ($(ssh_check).is(":checked") == false) {
                $(\'#ssh_username\').removeAttr("enabled");
                $(\'#ssh_username\').attr("disabled", "disabled");
            }
        });
    });
</script>';
            }
            break;
            
        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:
        
            // get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");
            $dirpath = grab_array_var($inargs, "dirpath");
            $ssh = grab_array_var($inargs, "ssh");
            $ssh_username = grab_array_var($inargs, "ssh_username", "");
            $connection = "";

            // initialize errors
            $errors = 0;
            $errmsg = array();
            
            if(have_value($address) == false)
                $errmsg[$errors++] = _("No Address specified.");
            
            if(have_value($dirpath) == false)
                $errmsg[$errors++] = _("No File Path Specified.");

            if($errors>0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:
        
            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $dirpath = grab_array_var($inargs, "dirpath");
            $ssh = grab_array_var($inargs, "ssh");
            $ssh_username = grab_array_var($inargs, "ssh_username", "");
            $servicename = grab_array_var($inargs, "servicename", "_");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            //populate directory statistics
            $ssh_stats = true;
            if ($ssh == "on") {
                // remove statistics since we cannot run plugin through ssh as apache
                $ssh_stats = false;
            } else {
                $tstamp = shell_exec('/usr/local/nagios/libexec/folder_watch.pl -D "' . $dirpath . '" -F \'[^\0]+\' --filetype="files" -a -f');
                preg_match("/age_oldest=(\d+)s/", $tstamp, $oldest);
                preg_match("/age_newest=(\d+)s/", $tstamp, $newest);

                $sstamp = shell_exec('/usr/local/nagios/libexec/folder_watch.pl -D "' . $dirpath . '" -F \'[^\0]+\' --filetype="files" -s -f');
                preg_match("/size_largest=(\d+)B/", $sstamp, $largest);
                preg_match("/size_smallest=(\d+)B/", $sstamp, $smallest);

                $dircheck = shell_exec('/usr/local/nagios/libexec/folder_watch.pl -D "' . $dirpath . '" -F \'[^\0]+\' --filetype="files"');
                preg_match("/\d+/", $dircheck, $filecount);
            }


            $services = grab_array_var($inargs, "services", array(
                "fcount" => "on",
                "fage" => "off",
                "fsize" => "off",
                "query" => "[^\\0]+"
            ));

            $serviceargs = grab_array_var($inargs, "serviceargs", array(
                "recursive" => "off",
                "hidden" => "off",
                "checktype" => "",
                "warning_prefix" => ">",
                "critical_prefix" => ">",
                "fagecompare" => "regular",
                "fsizecompare" => "regular",
                "fagewarn" => "",
                "fagecrit" => "",
                "fsizewarn" => "",
                "fsizecrit" => ""
            ));

            $services_serial = grab_array_var($inargs, "services_serial");
            if(!empty($services_serial)) {
                $services = unserialize(base64_decode($services_serial));
            }

            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if(!empty($serviceargs_serial)) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            // verified SSH message if using SSH
            $ssh_message = "";
            if ($ssh == "on") {
                $ssh_message = '<img src="' . get_base_url() . 'images/ok_small.png"><b> ' . _("SSH Connection Verified") . '</b>';
            }

            $output='

    <input type="hidden" name="address" value="' . htmlentities($address) . '" />
    <input type="hidden" name="ssh" value="' . htmlentities($ssh) . '" />
    <input type="hidden" name="ssh_username" value="' . htmlentities($ssh_username) . '" />
    <input type="hidden" name="dirpath" value="' . htmlentities($dirpath) . '" />
    <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '" />
    <input type="hidden" name="warning" value="' . htmlentities($warning) . '" />
    <input type="hidden" name="critical" value="' . htmlentities($critical) . '" />

<script type="text/javascript">
$(document).ready(function() {
    var ssh_stats = ' . json_encode($ssh_stats) . ';
    var stat_div = document.getElementById("stat_div");
    var stat_title = document.getElementById("stat_title");

    if (ssh_stats == false) {
        stat_div.parentNode.removeChild(stat_div);
        stat_title.parentNode.removeChild(stat_title);
    }
});
</script>   

<h5 class="ul">' . _('Directory Details') . '</h5>
    
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Directory') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="dirpath" id="dirpath" value="' . htmlentities($dirpath) . '" class="textfield form-control" disabled/>
            <div class="subtext">' . _('This is the directory you have chosen to watch. Satistics associated with it are below.') . '</div>
        </td>
    </tr>
    <tr>
        <td id="stat_title" class="vt">
            <label>' . _('Statistics') . ':</label>
        </td>
        <td>
            <div id="stat_div">
                <table class="table table-condensed table-bordered table-auto-width">
                    <tr>
                        <td class="vt">
                            <label>' . _('File Count') . ': &nbsp;</label>
                        </td>
                        <td style="padding-top: 12px;">
                            <p><b>' . @$filecount[0] . '</b></p>
                            <div class="subtext">' . _('The number of non-hidden files in the directory.') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label>' . _('File Age') . ':</label>
                        </td>
                        <td style="padding-top: 12px;">
                            <p><b>' . _('Oldest') . ':</b>&nbsp;&nbsp;&nbsp;' . date("r", time() - @$oldest[1]).'&nbsp; (' . @$oldest[1] . ')<br>
                            <b>' . _('Newest').':</b>&nbsp;' . date("r", time() - @$newest[1]) . '&nbsp;(' . @$newest[1] . ')</p>
                            <div class="subtext">' . _('Current File Ages (seconds).') . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="vt">
                            <label>' . _('File Size') . ':</label>
                        </td>
                        <td style="padding-top: 12px;">
                            <p><b>' . _('Largest') . ':</b>&nbsp;&nbsp;&nbsp;' . @$largest[1] . '<br>
                            <b>' . _('Smallest') . ':</b>&nbsp;' . @$smallest[1] . '</p>
                            <div class="subtext">' . _('Current File Sizes in bytes.') . '</div>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="hostname" id="hostname" value="' . htmlentities($hostname) . '" class="textfield form-control" /> ' .  $ssh_message . ' 
            <div class="subtext">' . _('The name you would like to have associated with this directory.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Service Description') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="servicename" id="servicename" value="' . htmlentities($servicename) . '" class="textfield form-control" />
            <div class="subtext">' . _('The service description that you would like to have used for specific services you select below.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Wizard Options') . '</h5>
<p>' . _('Specify which options you want to monitor a directory with.') . '</p>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Include Subdirectories') . ':</label>
        </td>
        <td>
            <input type="checkbox" class="checkbox" name="serviceargs[recursive]" id="recursive" ' . is_checked(grab_array_var($serviceargs,"recursive")) . '/>
            <div class="subtext">' . _('This option will recursively include subdirectories in all directory searches.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Include Hidden Files') . ':</label>
        </td>
        <td>
            <input type="checkbox" class="checkbox" name="serviceargs[hidden]" id="hidden" ' . is_checked(grab_array_var($serviceargs,"hidden")) . '/>
            <div class="subtext">' . _('This option will include hidden files in all directory searches.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Query Type') . ':</label>
        </td>
        <td>
            <select name="serviceargs[checktype]" id="checktype" style="width:auto" class="form-control">
                <option value="" ' . is_selected(grab_array_var($serviceargs, "checktype"), "") . '>Both</option>
                <option value="files" ' . is_selected(grab_array_var($serviceargs, "checktype"), "files") . '>Files</option>
                <option value="dir" ' . is_selected(grab_array_var($serviceargs, "checktype"), "dir") . '>Directories</option>
            </select>
            <div class="subtext">' . _('Query only files, directories or both.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Directory Services') . '</h5>
<p style="width:500px;">' . _('Specify which services you want to monitor a directory with. The Regex Expression will be used for all the services below.  To include an age or size query select the corresponding checkbox.') . '</p>
<div class="message" style="width:500px;"><ul class="actionMessage">' . _('The default regex for all files/directories is ') . '<b>[^\0]+</b>' . _('.  This translates into one or more non-null characters and is intended to be used as a wildcard.') . '<li></li></ul></div>

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Regular Expression') . ':&nbsp;&nbsp;&nbsp;</label>
        </td>
        <td>
            <input type="text" size="40" id="query" name="services[query]" value="' . htmlentities($services["query"]) . '" class="textfield form-control" />
            <div class="subtext">' . _('The Regex pattern to query.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('File Count') . ':</label>
            <input type="checkbox" class="checkbox" style="display: inline-block; float: right;" id="fcount" name="services[fcount]" ' . is_checked(grab_array_var($services, "fcount")) . '>
        </td>
        <td>
            <select name="serviceargs[warning_prefix]" id="warning_prefix" style="width:auto" class="form-control">
                <option value=">" ' . is_selected(grab_array_var($serviceargs, "warning_prefix"), ">") . '> > </option>
                <option value="<" ' . is_selected(grab_array_var($serviceargs, "warning_prefix"), "<") . '> < </option>
                <option value="=" ' .is_selected(grab_array_var($serviceargs, "warning_prefix"), "=") . '> = </option>
            </select>
            <input type="text" size="8" name="warning" id="warning" placeholder="Warning" value="' . htmlentities($warning) . '" class="textfield form-control" />
            <select name="serviceargs[critical_prefix]" id="critical_prefix" style="width:auto" class="form-control">
                <option value=">" ' . is_selected(grab_array_var($serviceargs, "critical_prefix"), ">") . '> > </option>
                <option value="<" ' . is_selected(grab_array_var($serviceargs, "critical_prefix"), "<") . '> < </option>
                <option value="=" ' . is_selected(grab_array_var($serviceargs, "critical_prefix"), "=") . '> = </option>
            </select>
            <input type="text" size="8" name="critical" id="critical" placeholder="Critical" value="' . htmlentities($critical) . '" class="textfield form-control" />
            <div class="subtext">' . _('Check the number of files matching the regex query with this service.') . '</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('File Age') . ':</label>
            <input type="checkbox" class="checkbox" style="display: inline-block; float: right;" id="fage" name="services[fage]" ' . is_checked(grab_array_var($services, "fage")) . '>
        </td>
        <td>
            <input type="text" size="8" name="serviceargs[fagewarn]" id="fagewarn" placeholder="Warning" value="' . htmlentities($serviceargs["fagewarn"]) . '" class="textfield form-control" />
            <input type="text" size="8" name="serviceargs[fagecrit]" id="fagecrit" placeholder="Critical" value="' . htmlentities($serviceargs["fagecrit"]) . '" class="textfield form-control" />
            <select name="serviceargs[fagecompare]" id="fagecompare" style="width:auto" class="form-control">
                <option value="regular" ' . is_selected(grab_array_var($serviceargs, "fagecompare"), "regular") . '>Warning/ Critical</option>
                <option value="inrange" ' . is_selected(grab_array_var($serviceargs, "fagecompare"), "inrange") . '>Inside Range</option>
                <option value="outrange" ' . is_selected(grab_array_var($serviceargs, "fagecompare"), "outrange") . '>Outside Range</option>
            </select>
            <div class="subtext">'._('Check file age of the regex query in seconds with this service. Use ranges or Warning/Critical to check for age.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('File Size') . ':</label>
            <input type="checkbox" class="checkbox" style="display: inline-block; float: right;" id="fsize" name="services[fsize]" ' . is_checked(grab_array_var($services, "fsize")) . '>
        </td>
        <td>
            <input type="text" size="8" name="serviceargs[fsizewarn]" id="fsizewarn" placeholder="Warning" value="' . htmlentities($serviceargs["fsizewarn"]) . '" class="textfield form-control" />
            <input type="text" size="8" name="serviceargs[fsizecrit]" id="fsizecrit" placeholder="Critical" value="' . htmlentities($serviceargs["fsizecrit"]) . '" class="textfield form-control" />
            <select name="serviceargs[fsizecompare]" id="fsizecompare" style="width:auto" class="form-control">
                <option value="regular" ' . is_selected(grab_array_var($serviceargs, "fsizecompare"), "regular") . '>Warning/ Critical</option>
                <option value="inrange" ' . is_selected(grab_array_var($serviceargs, "fsizecompare"), "inrange") . '>Inside Range</option>
                <option value="outrange" ' . is_selected(grab_array_var($serviceargs, "fsizecompare"), "outrange") . '>Outside Range</option>
            </select>
            <div class="subtext">' . _('Check file size of the regex query in bytes with this service. Use ranges or Warning/Critical to check for size.') . '</div>
        </td>
    </tr>
</table>';

    break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $dirpath = grab_array_var($inargs, "dirpath");
            $ssh = grab_array_var($inargs, "ssh");
            $ssh_username = grab_array_var($inargs, "ssh_username");
            $hostname = grab_array_var($inargs, "hostname");
            $servicename = grab_array_var($inargs, "servicename");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");
            $services = grab_array_var($inargs, "services", array());
            $serviceargs = grab_array_var($inargs, "serviceargs", array());

            // check for errors
            $errors = 0;
            if(have_value($hostname) == false)
                $errmsg[$errors++] = _("No Host Name Specified. You Must Set a Unique Host Name.");

            if(is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");

            if(is_valid_service_name($servicename) == false)
                $errmsg[$errors++] = _("Invalid service prefix.  Can only contain alphanumeric characters, spaces, and the following:") . "<b>.\:_-</b>";

            if(array_key_exists("fcount",$services)) {
                if(empty($warning)) {
                    $errmsg[$errors++] = _("No Warning Value Specified. You Must Set a Warning Value for File Count.");
                } elseif(is_numeric($warning) == false) {
                    $errmsg[$errors++] = _("The Warning Value Must Be An Integer.");
                }
            
                if(empty($critical)) {
                    $errmsg[$errors++] = _("No Critical Value Specified. You Must Set a Critical Value for File Count.");
                } elseif(is_numeric($critical) == false) {
                    $errmsg[$errors++] = _("The Critical Value Must Be An Integer.");
                }
            }

            if(have_value($services["query"]) == false)
                $errmsg[$errors++] = _("No Regex Query Specified. You Must Set a Regex pattern to search for.");

            if(array_key_exists("fage",$services)) {
                if(empty($serviceargs["fagewarn"]) || empty($serviceargs["fagecrit"])) {
                    $errmsg[$errors++] = _("You must specify a pair of values to make a *File Age* comparison.");
                }
            }

            if(array_key_exists("fsize",$services)) {
                if(empty($serviceargs["fsizewarn"]) || empty($serviceargs["fsizecrit"])) {
                    $errmsg[$errors++] = _("You must specify a pair of values to make a *File Size* comparison.");
                }       
            }
        
            if($errors>0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:
        
            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $dirpath = grab_array_var($inargs, "dirpath");
            $ssh = grab_array_var($inargs, "ssh");
            $ssh_username = grab_array_var($inargs, "ssh_username");
            $hostname = grab_array_var($inargs, "hostname");
            $servicename = grab_array_var($inargs, "servicename");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");
            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $services_serial = grab_array_var($inargs, "services_serial", base64_encode(serialize($services)));
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", base64_encode(serialize($serviceargs)));

            $output = '
<input type="hidden" name="address" value="'.htmlentities($address).'" />
<input type="hidden" name="dirpath" value="'.htmlentities($dirpath).'" />
<input type="hidden" name="ssh" value="'.htmlentities($ssh).'" />
<input type="hidden" name="ssh_username" value="'.htmlentities($ssh_username).'" />
<input type="hidden" name="hostname" value="'.htmlentities($hostname).'" />
<input type="hidden" name="servicename" value="'.htmlentities($servicename).'" />
<input type="hidden" name="warning" value="'.htmlentities($warning).'" />
<input type="hidden" name="critical" value="'.htmlentities($critical).'" />
<input type="hidden" name="services_serial" value="'.$services_serial.'" />
<input type="hidden" name="serviceargs_serial" value="'.$serviceargs_serial.'" />
            ';

            break;
            
        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;
            
        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:
            
            $output='
                
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:
        
            $address = grab_array_var($inargs,"address");
            $dirpath = grab_array_var($inargs,"dirpath");
            $ssh = grab_array_var($inargs, "ssh");
            $ssh_username = grab_array_var($inargs, "ssh_username");
            $hostname = grab_array_var($inargs, "hostname");
            $servicename = grab_array_var($inargs, "servicename");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["address"] = $address;
            $meta_arr["dirpath"] = $dirpath;
            $meta_arr["ssh"] = $ssh;
            $meta_arr["ssh_username"] = $ssh_username;
            $meta_arr["hostname"] = $hostname;
            $meta_arr["servicename"] = $servicename;
            $meta_arr["warning"] = $warning;
            $meta_arr["critical"] = $critical;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name,$dirpath,"",$meta_arr);

            // check age and size comparison type
            $fage = "";
            if (array_key_exists("fage", $services)) {
                if ($serviceargs["fagecompare"] == 'inrange') {
                    $fage = " -a @" . $serviceargs["fagewarn"] . ":" . $serviceargs["fagecrit"];
                } else if ($serviceargs["fagecompare"] == 'outrange') {
                    $fage = " -a " . $serviceargs["fagewarn"] . ":" . $serviceargs["fagecrit"];
                } else {
                    $fage = " -a " . $serviceargs["fagewarn"] . "," . $serviceargs["fagecrit"];
                }
            }

            $fsize = "";
            if (array_key_exists("fsize", $services)) {
                if ($serviceargs["fsizecompare"] == 'inrange') {
                    $fsize = " -s @" . $serviceargs["fsizewarn"] . ":" . $serviceargs["fsizecrit"];
                } else if ($serviceargs["fsizecompare"] == 'outrange') {
                    $fsize = " -s " . $serviceargs["fsizewarn"] . ":" . $serviceargs["fsizecrit"];
                } else {
                    $fsize = " -s " . $serviceargs["fsizewarn"] . "," . $serviceargs["fsizecrit"];
                }
            }

            // Create proper commands for plugin locally and remotely
            $cmd = "";
            
            switch ($ssh) {

                case "on":

                    // Command using ssh on a remote host - check for requested services and use relative host address
                    $cmd = "-C 'ssh " . $ssh_username . "@" . '$HOSTADDRESS$' . " ls -l " . $dirpath . "'";

                    if (array_key_exists("hidden", $serviceargs)) {
                        if ($serviceargs["hidden"] !== 'off') {
                            $cmd = "-C 'ssh " . $ssh_username . "@" . '$HOSTADDRESS$' . " ls -lA " . $dirpath . "'";
                        }
                    }

                    $cmd .= " -F " . escapeshellarg($services["query"]);

                    if (array_key_exists("checktype", $serviceargs)) {
                        if ($serviceargs["checktype"] == "files") {
                            $cmd .= " -T 'files'";
                        } else if($serviceargs["checktype"] == "dir") {
                            $cmd .= " -T 'dir'";
                        }
                    }

                    if (array_key_exists("recursive", $serviceargs)) {
                        if (have_value($serviceargs["recursive"]) !== 'off') {
                            $cmd .= " -r";
                        }
                    }

                    break;

                default:

                    // Local Command - check for requested services
                    $cmd = " -D " . $dirpath . " -F " . escapeshellarg($services["query"]);

                    if (array_key_exists("hidden", $serviceargs)) {
                        if ($serviceargs["hidden"] !== 'off') {
                            $cmd = " -D " . $dirpath . " -F " . escapeshellarg($services["query"]) . " -C 'ls -lA'";
                        }
                    }

                    if (array_key_exists("checktype", $serviceargs)) {
                        if ($serviceargs["checktype"] == "files") {
                            $cmd .= " -T 'files'";
                        } else if($serviceargs["checktype"] == "dir") {
                            $cmd .= " -T 'dir'";
                        }
                    }

                    if (array_key_exists("recursive", $serviceargs)) {
                        if (have_value($serviceargs["recursive"]) !== 'off') {
                            $cmd .= " -r";
                        }
                    }

                    break;
            }

            // alert level prefix check, allows in and outside ranges
            $alert_levels = "";
            if ($serviceargs["warning_prefix"] !== '>') {
                $alert_levels .= " -w" . $serviceargs["warning_prefix"] . $warning;
            } else {
                $alert_levels .= " -w " . $warning;
            }

            if ($serviceargs["critical_prefix"] !== '>') {
                $alert_levels .= " -c" . $serviceargs["critical_prefix"] . $warning;
            } else {
                $alert_levels .= " -c " . $critical;
            }

            // check for existing host
            $objs = array();
            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_generic_host",
                    "host_name" => $hostname,
                    "address" => $address,
                    "icon_image" => "folder_watch.png",
                    "statusmap_image" => "folder_watch.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // see which services we should monitor
            foreach($services as $svc => $svcstate) {

                switch ($svc) {

                    case "fcount":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "use" => "xiwizard_check_file_service",
                            "service_description" => $servicename . ": File Count query of " . $dirpath,
                            "check_command" => "check_file_service!" . $cmd . "!" . $alert_levels,
                            "_xiwizard" => $wizard_name,
                            );

                        break;

                    case "fage":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "use" => "xiwizard_check_file_sa_service",
                            "service_description" => $servicename . ": Age query of " . $dirpath,
                            "check_command" => "check_file_size_age!" . $cmd . "!" . $fage,
                            "_xiwizard" => $wizard_name,
                            );

                        break;

                    case "fsize":
                        $objs[] = array(
                            "type" => OBJECTTYPE_SERVICE,
                            "host_name" => $hostname,
                            "use" => "xiwizard_check_file_sa_service",
                            "service_description" => $servicename . ": Size query of " . $dirpath,
                            "check_command" => "check_file_size_age!" . $cmd . "!" . $fsize,
                            "_xiwizard" => $wizard_name,
                            );

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