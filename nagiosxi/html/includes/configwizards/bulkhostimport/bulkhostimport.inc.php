<?php
//
// Bulk Host Import Config Wizard
// Copyright (c) 2014-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

bulkhostimport_configwizard_init();

function bulkhostimport_configwizard_init()
{
    $name = "bulkhostimport";

    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Clones existing hosts quickly and easily.  Supports import from auto-discovery jobs and CSV input."),
        CONFIGWIZARD_DISPLAYTITLE => "Bulk Host Cloning and Import",
        CONFIGWIZARD_FUNCTION => "bulkhostimport_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "bulkimport.png",
        CONFIGWIZARD_VERSION => "2.0.2",
        CONFIGWIZARD_DATE => "04/28/2016",
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );

    register_configwizard($name, $args);
}


/**
 * @return bool
 */
function bulkhostimport_configwizard_checkversion()
{
    if (!function_exists('get_product_release')) {
        return false;
    }
    $ver = get_product_release();
    if ($ver < 206) {
        return false;
    }
    return true;
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function bulkhostimport_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "bulkhostimport";
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {

        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $host = grab_array_var($inargs, "host", "");

            if (bulkhostimport_configwizard_checkversion() == false) {
                $output = '<p><strong>' . _('Error') . ':</strong> ' . _('This wizard requires Nagios XI 2011R1.6 or later') . '.</p>';
            } else {

                $output = '
<p>' . _('This wizard allows you to clone existing hosts quicky and easily.  It supports import of new hosts in bulk.') . '<br>' . _('New host information is specified in CSV format and each newly imported host is given the same services as an existing host that is being monitored.  This is useful if you setup one host as a template and want to setup several other hosts using the same template') . '.</p>

<h5 class="ul">' . _('Template Host Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width table-padded">
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <select class="form-control" name="host">
                <OPTION VALUE=""></OPTION>';

                $args = array("is_active" => 1);
                $xmlhosts = get_xml_host_objects($args);
                $hosts = array();
                foreach ($xmlhosts->host as $h) {
                    $hosts[] = $h;
                }
                $hosts = array_reverse($hosts);

                foreach ($hosts as $h) {
                    $hname = strval($h->host_name);
                    $hdesc = $hname;
                    $halias = strval($h->alias);
                    if ($halias != $hname && $halias != "") {
                        $hdesc .= " (" . $halias . ")";
                    }
                    $output .= '<OPTION VALUE="' . $hname . '" ' . is_selected($host, $hname) . '>' . $hdesc . '</OPTION>';
                }

                $output .= '
            </select>
            <div class="subtext">' . _('Select an existing host that should be cloned and used as the template for new hosts') . '.</div>
        </td>
    </tr>
</table>';

            }
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $host = grab_array_var($inargs, "host", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();

            if (bulkhostimport_configwizard_checkversion() == false) {
                $errmsg[$errors++] = _("Error: This wizard requires Nagios XI 2011R1.6 or later");
            }

            if (have_value($host) == false) {
                $errmsg[$errors++] = _("No template host specified.");
            } else if (host_exists($host) == false) {
                $errmsg[$errors++] = _("Template host could not be found.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $host = grab_array_var($inargs, "host");
            $csvdata = grab_array_var($inargs, "csvdata");
            $field1 = grab_array_var($inargs, "field1", "address");
            $field2 = grab_array_var($inargs, "field2");
            $field3 = grab_array_var($inargs, "field3");
            $field4 = grab_array_var($inargs, "field4");
            $field5 = grab_array_var($inargs, "field5");
            $overwrite = grab_array_var($inargs, "overwrite", 1);
            $field4 = grab_array_var($inargs, "field5");
            $field5 = grab_array_var($inargs, "field5");

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }
            if (!is_array($services)) {
                $services_default = array();
                $services = grab_array_var($inargs, "services", $services_default);
            }

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }
            if (!is_array($serviceargs)) {
                $serviceargs_default = array();
                $serviceargs = grab_array_var($inargs, "serviceargs", $serviceargs_default);
            }

            $output = '
<script type="text/javascript">
var allChecked = false;
function CheckAll()
{
    $(".checkbox").each(function() {
        this.checked = "checked";
    });
}   
function UncheckAll()
{
    $(".checkbox").each(function() { 
        this.checked = "";
    });
}
</script>

<input type="hidden" name="host" value="' . encode_form_val($host) . '">

<h5 class="ul">' . _('Host Template') . '</h5>
<table class="table table-condensed table-no-border table-auto-width" style="margin-bottom: 10px;">
    <tr>
        <td>';

            // Show host icon
            $img = get_object_icon_image($host);
            $imghtml = get_object_icon_html($img, "");
            $output .= $imghtml;

            $output .= '
        </td>
        <td>
            <a href="' . get_host_status_detail_link($host) . '" target="_blank"><b>' . encode_form_val($host) . '</b></a>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Select Service Templates') . '</h5>
<p>' . _('Specify the services from the template host that should cloned') . '.</p>
<div>
    <i class="fa fa-check-square-o"></i> <a href="javascript:void(0);" onclick="CheckAll()" title="Check All">' . _('Check All') . '</a> &nbsp;/&nbsp;
    <i class="fa fa-square-o"></i> <a href="javascript:void(0);" onclick="UncheckAll()" title="Uncheck All">' . _('Uncheck All') . '</a>
</div>

<div style="margin: 10px 0 20px 0;">';

            $args = array(
                "host_name" => $host,
                "is_active" => 1,
                'orderby' => 'service_description:a'
            );
            $tservices = get_xml_service_objects($args);
            foreach ($tservices->service as $ts) {
                $sname = strval($ts->service_description);
                if (service_exists($host, $sname)) {

                    // Checks if the service can be cloned or not... if it can be then display a checkbox or display disabled checkbox if it can't be
                    if (service_is_cloneable($host, $sname)) {
                        $output .= '<div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="checkbox" name="services[' . encode_form_val($sname) . ']" ' . is_checked(grab_array_var($services, $sname)) . '>' . encode_form_val($sname) . '
                                        </label>
                                        (<a href="' . get_service_status_detail_link($host, $sname) . '" target="_blank">'._('Details').'</a>)
                                    </div>';
                    } else {
                        $output .= '<tr>
                                <td><input type="checkbox" class="checkbox" disabled></td>
                                <td><a href="' . get_service_status_detail_link($host, $sname) . '" target="_blank"><b>' . encode_form_val($sname) . '</b></a> (' . _("Inherited - Can not be cloned using the wizard") . ')</td>
                            </tr>';
                    }
                }
            }

            $output .= '
</div>

<h5 class="ul">' . _('Import / Cloning Data') . '</h5>
<p>' . _('Enter addresses of new hosts that should be created by cloning the template host and services specified above') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('Fields').':</label>
        </td>
        <td>
            <table class="table table-condensed table-no-border table-auto-width table-no-padding">
            <tr><th>' . _('Field') . ' 1</th><th>' . _('Field') . ' 2</th><th>' . _('Field') . ' 3</th><th>' . _('Field') . ' 4</th><th>' . _('Field') . ' 5</th></tr>
            <tr>
                <td>
                    <select name="field1" class="form-control">
                        <option value="ignore" >' . _('IGNORE') . '</option>
                        <option value="address" ' . is_selected($field1, "address") . '>' . _('Address') . '</option>
                        <option value="hostname" ' . is_selected($field1, "hostname") . '>' . _('Name') . '</option>
                        <option value="hostalias" ' . is_selected($field1, "hostalias") . '>' . _('Description') . '</option>
                        <option value="hostgroup" ' . is_selected($field1, "hostgroup") . '>' . _('Hostgroup') . '</option>
                        <option value="parenthost" ' . is_selected($field1, "parenthost") . '>' . _('Parent Host') . '</option>
                    </select>
                </td>
                <td>
                    <select name="field2" class="form-control">
                        <option value="ignore" >' . _('IGNORE') . '</option>
                        <option value="address" ' . is_selected($field2, "address") . '>' . _('Address') . '</option>
                        <option value="hostname" ' . is_selected($field2, "hostname") . '>' . _('Name') . '</option>
                        <option value="hostalias" ' . is_selected($field2, "hostalias") . '>' . _('Description') . '</option>
                        <option value="hostgroup" ' . is_selected($field2, "hostgroup") . '>' . _('Hostgroup') . '</option>
                        <option value="parenthost" ' . is_selected($field2, "parenthost") . '>' . _('Parent Host') . '</option>
                    </select>
                </td>
                <td>
                    <select name="field3" class="form-control">
                        <option value="ignore" >' . _('IGNORE') . '</option>
                        <option value="address" ' . is_selected($field3, "address") . '>' . _('Address') . '</option>
                        <option value="hostname" ' . is_selected($field3, "hostname") . '>' . _('Name') . '</option>
                        <option value="hostalias" ' . is_selected($field3, "hostalias") . '>' . _('Description') . '</option>
                        <option value="hostgroup" ' . is_selected($field3, "hostgroup") . '>' . _('Hostgroup') . '</option>
                        <option value="parenthost" ' . is_selected($field3, "parenthost") . '>' . _('Parent Host') . '</option>
                    </select>
                </td>
                <td>
                    <select name="field4" class="form-control">
                        <option value="ignore" >' . _('IGNORE') . '</option>
                        <option value="address" ' . is_selected($field4, "address") . '>' . _('Address') . '</option>
                        <option value="hostname" ' . is_selected($field4, "hostname") . '>' . _('Name') . '</option>
                        <option value="hostalias" ' . is_selected($field4, "hostalias") . '>' . _('Description') . '</option>
                        <option value="hostgroup" ' . is_selected($field4, "hostgroup") . '>' . _('Hostgroup') . '</option>
                        <option value="parenthost" ' . is_selected($field4, "parenthost") . '>' . _('Parent Host') . '</option>
                    </select>
                </td>
                <td>
                    <select name="field5" class="form-control">
                        <option value="ignore" >' . _('IGNORE') . '</option>
                        <option value="address" ' . is_selected($field5, "address") . '>' . _('Address') . '</option>
                        <option value="hostname" ' . is_selected($field5, "hostname") . '>' . _('Name') . '</option>
                        <option value="hostalias" ' . is_selected($field5, "hostalias") . '>' . _('Description') . '</option>
                        <option value="hostgroup" ' . is_selected($field5, "hostgroup") . '>' . _('Hostgroup') . '</option>
                        <option value="parenthost" ' . is_selected($field5, "parenthost") . '>' . _('Parent Host') . '</option>
                    </select>
                </td>
            </tr>
            </table>
            <div class="subtext">' . _('Specify the type of data that is present in the fields of the data below') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Data').':</label>
        </td>
        <td>
            <textarea name="csvdata" style="min-width: 700px; min-height: 200px; font-family: Consolas, Courier New, monospace;" class="form-control">' . $csvdata . '</textarea>
            <div class="subtext">' . _('The addresses of new hosts that should be created by cloning the template host and services specified above.  Multiple fields should be separated by a comma.  One entry per line.') . '</div>
        </td>
    </tr>
    <tr>
        <td><label>' . _("Options") . ':</label></td>
        <td class="checkbox">
            <label>
                <input type="checkbox" value="1" name="overwrite" ' . is_checked($overwrite, 1) . '> ' . _("Replace Parent Host and/or Hostgroup with the above selected (if any) instead of adding the given Parent and/or Hostgroup to the new host.") . '
            </label>
        </td>
    </tr>
</table>
    
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $host = grab_array_var($inargs, "host");
            $csvdata = grab_array_var($inargs, "csvdata");
            $overwrite = grab_array_var($inargs, "overwrite", 0);

            // Grab all 5 fields
            for ($i = 1; $i < 6; $i++) {
                $fields[$i] = grab_array_var($inargs, "field" . $i);
            }

            $host_template = nagiosql_read_host_config_from_file($host);

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial", "");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            }
            if (!is_array($services)) {
                $services = grab_array_var($inargs, "services", array());
            }

            // check for errors
            $errors = 0;
            $errmsg = array();

            // Not sure why we were requiring services to be selected, so I removed them 6/21/2013 -SW
            //if(count($services)==0)
            //  $errmsg[$errors++]=_("No template services selected.");

            // Check for required field values
            $has_address = false;
            $has_non_unique = false;
            foreach ($fields as $field) {

                // Check for address
                if ($field == "address") {
                    $has_address = true;
                }

                // Check for non-unique within the fields
                $c = 0;
                foreach ($fields as $f2_check) {
                    if ($field == $f2_check && $field != "ignore") {
                        $c++;
                    }
                    if ($c > 1) {
                        $has_non_unique = true;
                    }
                }
            }

            // If no address, then complain
            if (!$has_address) {
                $errmsg[$errors++] = _("Address must be present in CSV fields.");
            }

            // If there are any non-unique values let's error
            if ($has_non_unique) {
                $errmsg[$errors++] = _("Import / cloning data fields must be unique.");
            }

            // check CSV data
            $csva1 = explode("\n", $csvdata);
            $csva2 = array_unique($csva1);
            $csva3 = array_filter($csva2);
            $csvarray = $csva3;
            if (count($csvarray) == 0) {
                $errmsg[$errors++] = _("No import / cloning data provided.");
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:
            $output .= '<div style="margin-bottom: 20px;">' . _('Monitoring options will be inherited from the template host and services you selected. Click Next to continue.') . '</div>';
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $host = grab_array_var($inargs, "host");
            $csvdata = grab_array_var($inargs, "csvdata");
            $overwrite = grab_array_var($inargs, "overwrite", 0);

            // Grab all 5 fields
            for ($i = 1; $i < 6; $i++) {
                $fields[$i] = grab_array_var($inargs, "field" . $i);
            }

            $services = "";
            $services_serial = grab_array_var($inargs, "services_serial");
            if ($services_serial != "") {
                $services = unserialize(base64_decode($services_serial));
            } else {
                $services = grab_array_var($inargs, "services");
            }

            $serviceargs = "";
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial");
            if ($serviceargs_serial != "") {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            } else {
                $serviceargs = grab_array_var($inargs, "serviceargs");
            }

            $output = '<input type="hidden" name="host" value="' . encode_form_val($host) . '">
                       <input type="hidden" name="csvdata" value="' . encode_form_val($csvdata) . '">
                       <input type="hidden" name="field1" value="' . encode_form_val($fields[1]) . '">
                       <input type="hidden" name="field2" value="' . encode_form_val($fields[2]) . '">
                       <input type="hidden" name="field3" value="' . encode_form_val($fields[3]) . '">
                       <input type="hidden" name="field4" value="' . encode_form_val($fields[4]) . '">
                       <input type="hidden" name="field5" value="' . encode_form_val($fields[5]) . '">
                       <input type="hidden" name="overwrite" value="' . encode_form_val($overwrite) . '">
                       <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
                       <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETSTAGE4OPTS:

            $output .= '<div style="margin-bottom: 20px;">' . _('Notification options will be inherited from the template host and services you selected. Click Next to continue.') . '</div>';
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            $outargs[CONFIGWIZARD_HIDDEN_OPTIONS] = array(
                CONFIGWIZARD_HIDE_NOTIFICATION_OPTIONS,
                CONFIGWIZARD_HIDE_NOTIFICATION_INTERVAL,
                CONFIGWIZARD_HIDE_NOTIFICATION_TARGETS,
            );

            break;

        case CONFIGWIZARD_MODE_GETSTAGE5OPTS:

            $output .= '<div style="margin-bottom: 20px;">' . _('Group membership will be inherited from the template host and services you selected unless you put hostgroup and/or parent host relationship data in. Click Next to continue.') . '</div>';
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            $outargs[CONFIGWIZARD_HIDDEN_OPTIONS] = array(
                CONFIGWIZARD_HIDE_HOSTGROUPS,
                CONFIGWIZARD_HIDE_SERVICEGROUPS,
                CONFIGWIZARD_HIDE_PARENT_HOSTS,
            );

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $host = grab_array_var($inargs, "host", "");
            $csvdata = grab_array_var($inargs, "csvdata");
            $overwrite = grab_array_var($inargs, "overwrite", 0);

            // Grab all 5 fields
            for ($i = 1; $i < 6; $i++) {
                $fields[$i] = grab_array_var($inargs, "field" . $i);
            }

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // This will be the NEW objects array
            $objs = array();

            // Fetch template service settings from config file
            $service_templates = array();
            foreach ($services as $svc => $svcstate) {

                // Execute query to get the config name of the service
                $config_name = '';
                $sql = "SELECT tbl_service.config_name FROM tbl_service
                        LEFT JOIN tbl_lnkServiceToHost
                        ON tbl_service.id = tbl_lnkServiceToHost.idMaster
                        LEFT JOIN tbl_host
                        ON tbl_lnkServiceToHost.idSlave = tbl_host.id
                        WHERE tbl_host.host_name = '".escape_sql_param($host, DB_NAGIOSQL)."'
                        AND tbl_service.service_description = '".escape_sql_param($svc, DB_NAGIOSQL)."';";
                $rs = exec_sql_query(DB_NAGIOSQL, $sql);
                if ($rs->RecordCount() == 1) {
                    $config_name = $rs->fields['config_name'];
                }

                $service_templates[$svc] = nagiosql_read_service_config_from_file($host, $svc, $config_name);
            }

            // Fetch template host settings from config file
            $host_template = nagiosql_read_host_config_from_file($host);

            // Get the CSV data into an array
            $csva1 = explode("\n", $csvdata);
            $csva2 = array_unique($csva1);
            $csva3 = array_filter($csva2);
            $csvarray = $csva3;

            // For each CSV row let's process it as a host (possibly a NEW host)
            foreach ($csvarray as $c) {

                $cf = explode(",", $c);

                // Grab all the values in the CSV array for this row
                $fv = array();
                for ($i = 0; $i < 5; $i++) {
                    $val = grab_array_var($cf, $i, "");
                    $fv[$i + 1] = str_replace('"', '', $val); // Scott's fix for " in CSV data -JO
                }

                // Default all these...
                $address = "";
                $hostname = "";
                $hostalias = "";
                $hostgroup = "";
                $parenthost = "";

                // Add values to the correct variables
                for ($i = 1; $i < 6; $i++) {
                    switch ($fields[$i]) {
                        case "address":
                            $address = trim($fv[$i]);
                            break;
                        case "hostname":
                            $hostname = trim($fv[$i]);
                            break;
                        case "hostalias":
                            $hostalias = trim($fv[$i]);
                            break;
                        case "hostgroup":
                            $hostgroup = trim($fv[$i]);
                            break;
                        case "parenthost":
                            $parenthost = trim($fv[$i]);
                            break;
                    }
                }

                // Check for a valid address or bail
                if ($address == "") {
                    continue;
                }

                // If there's no hostname we can set it to the address
                if ($hostname == "") {
                    $hostname = $address;
                }

                // Replace hostgroup and parent host if they don't exist
                if (!host_exists($parenthost)) {
                    $parenthost = "";
                }
                if (!hostgroup_exists($hostgroup)) {
                    $hostgroup = "";
                }

                // Bail if the hostname isn't valid
                if (is_valid_host_name($hostname) == false) {
                    continue;
                }

                // Add the host
                if (!host_exists($hostname)) {

					foreach ($host_template as $cv => $cvv) {

						// For parents and hostgroups let's append to what they may have given it in the CSV
						// ONLY DO THIS IF THE OVERWRITE SELECTOR IS SET TO 0 (NOT OVERWRITING TEMPLATE'S)
						if (($cv == "hostgroups" || $cv == "parents") && $overwrite == 0) {
							if (!empty($cvv) || $cvv != "null") {
								$temp = explode(",", $cvv);

								$add = true;
								foreach ($temp as $t) {
									if ($t == $newhost[$cv]) {
										$add = false;
									}
								}

								if ($add) {
									$temp[] = $newhost[$cv];
								}

								if (count($temp) > 1) {
									$temp = array_diff($temp, array("null")); // Remove the "null" value
								}

								$newhost[$cv] = implode(",", $temp);
								continue;
							}
						}

						$newhost[$cv] = $cvv;

                    }
					
                    // The list of added things that the CSV is adding to a new host (hostname, address, etc)
                    $newhost["type"] = OBJECTTYPE_HOST;
                    $newhost["use"] = grab_array_var($host_template, "use", "generic-host");
                    $newhost["host_name"] = $hostname;
                    $newhost["address"] = $address;
                    $newhost["alias"] = $hostalias;
					if ($overwrite == 1) {
                        if ($hostgroup != "") $newhost["hostgroups"] = $hostgroup;
                        if ($parenthost != "") $newhost["parents"] = $parenthost;
					}

                    // Loop through and set all HOST TEMPLATE variables to the NEW HOST
				

                    $objs[] = $newhost;
                }

                // Add services to the host
                foreach ($services as $svc => $svcstate) {

                    $newsvc = array();

                    foreach ($service_templates[$svc] as $cv => $cvv) {
                            $newsvc[$cv] = $cvv;
                    }
					
					// Check host_name + option
                    $plus = "";
                    $hn = grab_array_var($service_templates[$svc], "host_name", "");
                    if (strpos(trim($hn), "+") === 0) {
                        $plus = "+";
                    }

                    
					$newsvc["type"] = OBJECTTYPE_SERVICE;
                    $newsvc["host_name"] = $plus.$hostname;
                    $newsvc["service_description"] = $svc;

                    $objs[] = $newsvc;
                }

            }

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_SKIP_OBJECTS_RECONFIGURE] = true;
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}


function bulkhostimport_configwizard_get_fval()
{
    // Not sure what this does but not deleting it for reqs
}


/**
 * Checks the CCM database to make sure service is actually a part of the host.
 *
 * @param $host
 * @param $service
 *
 * @return bool
 */
function service_is_cloneable($host, $service)
{
    global $db_tables;

    // Make sure this'll work for UTF8
    exec_sql_query(DB_NAGIOSQL, "set names 'utf8'");

    // Get all DIRECTLY LINKED services for the host
    $sql = "SELECT s.service_description FROM " . $db_tables[DB_NAGIOSQL]['service'] . " AS s
            INNER JOIN " . $db_tables[DB_NAGIOSQL]['lnkServiceToHost'] . " AS r ON r.idMaster = s.id
            INNER JOIN " . $db_tables[DB_NAGIOSQL]['host'] . " AS h ON h.id = r.idSlave
            WHERE h.host_name = '" . $host . "'";

    $rs = exec_sql_query(DB_NAGIOSQL, $sql);
    while ($row = $rs->FetchRow()) {
        if ($row['service_description'] == $service) {
            return true; // This means we found a hard relationship and this service can be cloned
        }
    }

    return false; // This means the service is inherited somehow... can't be cloned via the wizard
}
