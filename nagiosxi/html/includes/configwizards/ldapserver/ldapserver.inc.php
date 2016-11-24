<?php
//
// LDAP Server Config Wizard
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

ldapserver_configwizard_init();

function ldapserver_configwizard_init()
{
    $name = "ldapserver";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "1.3.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Monitor an LDAP server."),
        CONFIGWIZARD_DISPLAYTITLE => _("LDAP Server"),
        CONFIGWIZARD_FUNCTION => "ldapserver_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "directory_services3.png",
        CONFIGWIZARD_FILTER_GROUPS => array('network'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
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
function ldapserver_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "ldapserver";

    // Initialize return code and output
    $result = 0;
    $output = "";

    // Initialize output args - pass back the same data we got
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:

            $address = grab_array_var($inargs, "address", "");
            $address = nagiosccm_replace_user_macros($address);

            $output = '
<h5 class="ul">' . _('LDAP Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('Address').':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . encode_form_val($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the device or server associated with the LDAP server') . '.</div>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address", "");

            // Check for errors
            $errors = 0;
            $errmsg = array();
            if (have_value($address) == false)
                $errmsg[$errors++] = _("No address specified.");

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;

        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Get variables that were passed to us
            $address = grab_array_var($inargs, "address");

            $ha = @gethostbyaddr($address);
            if ($ha == "")
                $ha = $address;
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $port = grab_array_var($inargs, "port");
            $port = nagiosccm_replace_user_macros($port);

            $password = grab_array_var($inargs, "password");
            $password = nagiosccm_replace_user_macros($password);

            $base = grab_array_var($inargs, "base", "");
            $bind_dn = grab_array_var($inargs, "bind_dn");
            $security = grab_array_var($inargs, "security");
            $version = grab_array_var($inargs, "version");
            $search = grab_array_var($inargs, "search");

            $services = grab_array_var($inargs, "services", array("server" => "on", "transfer" => ""));


            $output = '
<input type="hidden" name="address" value="' . encode_form_val($address) . '">

<h5 class="ul">' . _('LDAP Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . encode_form_val($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . encode_form_val($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this LDAP server') . '.</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('LDAP Settings') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('LDAP Base') . ':</label><br class="nobr" />
        </td>
        <td>
            <input type="text" size="32" name="base" value="' . encode_form_val($base) . '" placeholder="ou=unit,o=org,c=at" class="textfield form-control">
            <div class="subtext">' . _('The LDAP base to use') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Bind DN') . ':</label>
        </td>
        <td>
            <input type="text" size="32" name="bind_dn" value="' . encode_form_val($bind_dn) . '" class="textfield form-control">
            <div class="subtext">' . _('LDAP bind DN (if required)') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Password') . ':</label>
        </td>
        <td>
            <input type="password" size="16" name="password" value="' . encode_form_val($password) . '" class="textfield form-control">
            <div class="subtext">' . _('The password used to login to the LDAP server (if required)') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Version') . ':</label>
        </td>
        <td>
            <select name="version" class="form-control">
                <option value="2" ' . is_selected($version, '2') . '>2</option>
                <option value="3" ' . is_selected($version, '3') . '>3</option>
            </select>
            <div class="subtext">' . _('Version of LDAP protocol to use') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Security').':</label>
        </td>
        <td>
            <select name="security" class="form-control">
                <option value="" ' . is_selected($security, '') . '>' . _('None') . '</option>
                <option value="ssl" ' . is_selected($security, 'ssl') . '>' . _('SSL') . '</option>
                <option value="starttls" ' . is_selected($security, 'starttls') . '>STARTTLS</option>
            </select>
            <div class="subtext">' . _('Security to use for LDAP connection (optional)') . '.</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Port Override') . ':</label>
        </td>
        <td>
            <input type="text" size="3" name="port" value="' . encode_form_val($port) . '" placeholder="389" class="textfield form-control">
            <div class="subtext">' . _('The port number the LDAP server runs on.  Defaults to port 389 (non-SSL) or 636 (SSL)') . '.</div>
        </td>
    </tr>
</table>

            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $port = grab_array_var($inargs, "port");
            $base = grab_array_var($inargs, "base");
            $bind_dn = grab_array_var($inargs, "bind_dn");
            $password = grab_array_var($inargs, "password");
            $security = grab_array_var($inargs, "security");
            $version = grab_array_var($inargs, "version");
            $search = grab_array_var($inargs, "search");

            $services = grab_array_var($inargs, "services", array());


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = "Invalid host name.";
            if ($base == "")
                $errmsg[$errors++] = "LDAP base is blank.";
            //if($port=="")
            //$errmsg[$errors++]="Invalid port number.";

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");

            $port = grab_array_var($inargs, "port");
            $base = grab_array_var($inargs, "base");
            $bind_dn = grab_array_var($inargs, "bind_dn");
            $password = grab_array_var($inargs, "password");
            $security = grab_array_var($inargs, "security");
            $version = grab_array_var($inargs, "version");
            $search = grab_array_var($inargs, "search");

            $services = grab_array_var($inargs, "services");
            $serviceargs = grab_array_var($inargs, "serviceargs");

            $output = '
            
        <input type="hidden" name="address" value="' . encode_form_val($address) . '">
        <input type="hidden" name="hostname" value="' . encode_form_val($hostname) . '">
        <input type="hidden" name="port" value="' . encode_form_val($port) . '">
        <input type="hidden" name="base" value="' . encode_form_val($base) . '">
        <input type="hidden" name="bind_dn" value="' . encode_form_val($bind_dn) . '">
        <input type="hidden" name="password" value="' . encode_form_val($password) . '">
        <input type="hidden" name="security" value="' . encode_form_val($security) . '">
        <input type="hidden" name="version" value="' . encode_form_val($version) . '">
        <input type="hidden" name="search" value="' . encode_form_val($search) . '">
        <input type="hidden" name="services_serial" value="' . base64_encode(serialize($services)) . '">
        <input type="hidden" name="serviceargs_serial" value="' . base64_encode(serialize($serviceargs)) . '">
        
        <!--SERVICES=' . serialize($services) . '<BR>
        SERVICEARGS=' . serialize($serviceargs) . '<BR>-->
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '
            ';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $hostname = grab_array_var($inargs, "hostname", "");
            $address = grab_array_var($inargs, "address", "");
            $hostaddress = $address;

            $port = grab_array_var($inargs, "port");
            $base = grab_array_var($inargs, "base");
            $bind_dn = grab_array_var($inargs, "bind_dn");
            $password = grab_array_var($inargs, "password");
            $security = grab_array_var($inargs, "security");
            $version = grab_array_var($inargs, "version");
            $search = grab_array_var($inargs, "search");

            $services_serial = grab_array_var($inargs, "services_serial", "");
            $serviceargs_serial = grab_array_var($inargs, "serviceargs_serial", "");

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            /*
            echo "SERVICES<BR>";
            print_r($services);
            echo "<BR>";
            echo "SERVICEARGS<BR>";
            print_r($serviceargs);
            echo "<BR>";
            */

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["port"] = $port;
            $meta_arr["base"] = $base;
            $meta_arr["bind_dn"] = $bind_dn;
            $meta_arr["password"] = $password;
            $meta_arr["security"] = $security;
            $meta_arr["version"] = $version;
            $meta_arr["search"] = $search;
            $meta_arr["services"] = $services;
            $meta_arr["serivceargs"] = $serviceargs;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_ldapserver_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "directory_services3.png",
                    "statusmap_image" => "directory_services3.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            $pluginopts = "";
            $pluginopts .= "-b \"" . $base . "\"";
            if ($bind_dn != "")
                $pluginopts .= " -D \"" . $bind_dn . "\"";
            if ($password != "")
                $pluginopts .= " -P \"" . $password . "\"";
            if ($version == "2")
                $pluginopts .= " -2";
            else if ($version == "3")
                $pluginopts .= " -3";
            if ($security == "ssl")
                $pluginopts .= " -S";
            else if ($security == "starttls")
                $pluginopts .= " -T";
            if ($port != "")
                $pluginopts .= " -p " . $port;

            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => "LDAP Server",
                "use" => "xiwizard_ldapserver_ldap_service",
                "check_command" => "check_xi_service_ldap!" . $pluginopts,
                "_xiwizard" => $wizard_name,
                "icon_image" => "directory_services.png",
            );

            //echo "OBJECTS:<BR>";
            //print_r($objs);
            //exit();

            // return the object definitions to the wizard
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;

            break;

        default:
            break;
    }

    return $output;
}