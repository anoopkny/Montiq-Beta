<?php
//
// Domain Expiration Config Wizard
// Copyright (c) 2013-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');


/**
 * @param $domain_name
 *
 * @return bool
 */
function is_valid_domain_name($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
        && preg_match("/^.{1,253}$/", $domain_name) //overall length check
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)); //length of each label
}


domain_expiration_configwizard_init();

function domain_expiration_configwizard_init()
{
    $name = "domain_expiration";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.1.2",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor a domain expiration"),
        CONFIGWIZARD_DISPLAYTITLE => _("Domain Expiration"),
        CONFIGWIZARD_FUNCTION => "domain_expiration_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "domain_expiration.png",
        CONFIGWIZARD_FILTER_GROUPS => array('website','network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    );
    register_configwizard($name, $args);
}


/**
 * @return array
 */
function domain_expiration_configwizard_check_prereqs()
{
    $errors = array();

    if (!file_exists("/usr/local/nagios/libexec/check_domain.php")) {
        $errors[] = _('It looks like you are missing check_domain.php on your Nagios XI server. To use this wizard you must install domain expiration on your server.');
    }

    exec("which whois 2>&1", $output, $return_var);

    if ($return_var != 0) {
        $errors[] = _('It looks like you are missing jWhois on your Nagios XI server.') . '<br><br> Run: &nbsp; <b>yum install jwhois -y</b> &nbsp; as root user on your Nagios XI server.';
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
function domain_expiration_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "domain_expiration";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $errors = domain_expiration_configwizard_check_prereqs();

            if ($errors) {
                $output .= '<div class="message"><ul class="errorMessage">';
                foreach ($errors as $error) {
                    $output .= "<li><p>$error</p></li>";
                }
                $output .= '</ul></div>';
            } else {

                $address = grab_array_var($inargs, "address", "");
                $address = nagiosccm_replace_user_macros($address);

                // Save data from clicking "back" in stage 2
                $services = grab_array_var($inargs, "services", array());
                $serviceargs = grab_array_var($inargs, "serviceargs", array());

                $output = '
<input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
<input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
                
<h5 class="ul">' . _('Domain Expiration Information') . '</h5>
<p>' . _('Specify the details for connecting to the Domain you want to monitor') . '.</p>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('Add the domain that will be monitored here') . '.</div>
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
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            $output = '
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<h5 class="ul">' . _('Domain Expiration') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Warning') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="warning" id="warning" value="' . htmlentities($warning) . '" class="textfield form-control"> ' . _('days') . '
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('Critical') . ':</label>
        </td>
        <td>
            <input type="text" size="5" name="critical" id="critical" value="' . htmlentities($critical) . '" class="textfield form-control"> ' . _('days') . '
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_domain_name($address) == false)
                $errmsg[$errors++] = "Invalid domain name.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE3OPTS:
            $output .= '<div style="margin-bottom: 20px;">' . _('The selected domain will be checked once per day. Click next to continue.') . '</div>';
            $result = CONFIGWIZARD_HIDE_OPTIONS;
            break;

        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            $output = '
            
                <input type="hidden" name="address" value="' . htmlentities($address) . '" />
                <input type="hidden" name="warning" value="' . htmlentities($warning) . '" />
                <input type="hidden" name="critical" value="' . htmlentities($critical) . '" />
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            $address = grab_array_var($inargs, "address");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:

            $address = grab_array_var($inargs, "address");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            $output = '

                <input type="hidden" name="address" value="' . htmlentities($address) . '" />
                <input type="hidden" name="warning" value="' . htmlentities($warning) . '" />
                <input type="hidden" name="critical" value="' . htmlentities($critical) . '" />

            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $address = grab_array_var($inargs, "address", "");
            $warning = grab_array_var($inargs, "warning");
            $critical = grab_array_var($inargs, "critical");

            // Save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["address"] = $address;
            $meta_arr["warning"] = $warning;
            $meta_arr["critical"] = $critical;
            save_configwizard_object_meta($wizard_name, $address, "", $meta_arr);

            $objs = array();

            if (!host_exists($address)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_domain_expiration_host_v2",
                    "host_name" => $address,
                    "address" => $address,
                    "icon_image" => "domain_expiration.png",
                    "statusmap_image" => "domain_expiration.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $address,
                "service_description" => "Domain Expiration",
                "use" => "xiwizard_domain_expiration_service_v2",
                "check_command" => "check_xi_domain_v2!" . $address . "!-w " . $warning . "!-c " . $critical,
                "check_interval" => 1440,
                "_xiwizard" => $wizard_name,
            );

            // Return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}

?>
