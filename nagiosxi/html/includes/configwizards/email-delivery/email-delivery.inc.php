<?php
//
// Email Delivery Config Wizard
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

email_delivery_configwizard_init();

function email_delivery_configwizard_init()
{
    $name = "email-delivery";
    $args = array(
        CONFIGWIZARD_NAME => $name,
        CONFIGWIZARD_VERSION => "2.0.3",
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _("Test mail servers reception and simulated users inspection of email messages."),
        CONFIGWIZARD_DISPLAYTITLE => _("Email Delivery"),
        CONFIGWIZARD_FUNCTION => "email_delivery_configwizard_func",
        CONFIGWIZARD_PREVIEWIMAGE => "email-delivery.png",
        CONFIGWIZARD_FILTER_GROUPS => array('email'),
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
function email_delivery_configwizard_func($mode = "", $inargs = null, &$outargs, &$result)
{
    $wizard_name = "email-delivery";

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
<h5 class="ul">' . _('Email Server') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>' . _('Address') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="address" id="address" value="' . htmlentities($address) . '" class="textfield form-control">
            <div class="subtext">' . _('The IP address or FQDNS name of the device or server associated with the Email Delivery check') . '.</div>
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

            $ha = @gethostbyaddr($address);
            if (empty($ha)) {
                $ha = $address;
            }
            $hostname = grab_array_var($inargs, "hostname", $ha);
            $hostname = nagiosccm_replace_user_macros($hostname);

            $servicedesc = grab_array_var($inargs, "servicedesc", 'Email Delivery');
            $smtpto = grab_array_var($inargs, "smtpto");
            $smtpfrom = grab_array_var($inargs, "smtpfrom");
            $smtp_address = grab_array_var($inargs, "smtp_address");
            $smtp_username = grab_array_var($inargs, "smtp_username");
            $smtp_password = grab_array_var($inargs, "smtp_password");
            $smtp_port = grab_array_var($inargs, "smtp_port", '25');
            $smtp_tls = grab_array_var($inargs, "smtp_tls");
            $imap_address = grab_array_var($inargs, "imap_address");
            $imap_username = grab_array_var($inargs, "imap_username");
            $imap_password = grab_array_var($inargs, "imap_password");
            $imap_port = grab_array_var($inargs, "imap_port", '143');
            $imap_ssl = grab_array_var($inargs, "imap_ssl");

            $output = '            
<input type="hidden" name="address" value="' . htmlentities($address) . '">

<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('Host Name').':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="Host_Name" value="' . htmlentities($hostname) . '" class="textfield form-control">
            <div class="subtext">'._('The Host Name in Nagios XI.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('Service Description').':</label>
        </td>
        <td>
            <input type="text" size="20" name="servicedesc" id="Service Description" value="' . htmlentities($servicedesc) . '" class="textfield form-control">
            <div class="subtext">'._('The Description you would like to have associated with this test case.').'</div>
        </td>
    </tr>
</table>

<h5 class="ul">'._('Email Details').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('To Address').':</label>
        </td>
        <td>
            <input type="text" size="20" name="smtpto" id="To_Address" value="' . htmlentities($smtpto) . '" class="textfield form-control">
            <div class="subtext">'._('The email address to send the test message to.  This is used in the envelope headers and must match the IMAP account you wish to check.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('From Address').':</label>
        </td>
        <td>
            <input type="text" size="20" name="smtpfrom" id="From_Address" value="' . htmlentities($smtpfrom) . '" class="textfield form-control">
            <div class="subtext">'._('Address to use as the from/sender address in the envelope headers.').'</div>
        </td>
    </tr>
</table>

<h5 class="ul">'._('SMTP Details').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('SMTP Address').':</label>
        </td>
        <td>
            <input type="text" size="30" name="smtp_address" id="SMTP Address" value="' . htmlentities($smtp_address) . '" class="textfield form-control">
            <div class="subtext">'._('The IP address or FQDNS name of the SMTP server.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('SMTP Username').':</label>
        </td>
        <td>
            <input type="text" size="20" name="smtp_username" id="SMTP Username" value="' . htmlentities($smtp_username) . '" class="textfield form-control">
            <div class="subtext">'._('The username used to login to the SMTP server.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('SMTP Password').':</label>
        </td>
        <td>
            <input type="password" size="20" name="smtp_password" id="SMTP Password" value="' . htmlentities($smtp_password) . '" class="textfield form-control">
            <div class="subtext">'._('The password used to login to the SMTP server.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('SMTP Port').':</label>
        </td>
        <td>
            <input type="text" size="6" name="smtp_port" id="SMTP Port" value="' . htmlentities($smtp_port) . '" class="textfield form-control">
            <div class="subtext">'._('Service port on the SMTP server.').'</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>'._('SMTP TLS').':</label>
        </td>
        <td class="checkbox">
            <label>
                <input type="checkbox" name="smtp_tls" id="SMTP TLS" class="checkfield" ' . is_checked($smtp_tls, "on") . '>
                '._('Use this to enable or disable TLS/AUTH for the SMTP plugin.').'
            </label>
        </td>
    </tr>
</table>

<h5 class="ul">'._('IMAP Details').'</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td class="vt">
            <label>'._('IMAP Address').':</label>
        </td>
        <td>
            <input type="text" size="30" name="imap_address" id="IMAP Address" value="' . htmlentities($imap_address) . '" class="textfield form-control">
            <div class="subtext">'._('The IP address or FQDNS name of the IMAP server.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('IMAP Username').':</label>
        </td>
        <td>
            <input type="text" size="20" name="imap_username" id="IMAP Username" value="' . htmlentities($imap_username) . '" class="textfield form-control">
            <div class="subtext">'._('The username used to login to the IMAP server.').'</div>
        </td>
    </tr>
    <tr>
        <td clsss="vt">
            <label>'._('IMAP Password').':</label>
        </td>
        <td>
            <input type="password" size="20" name="imap_password" id="IMAP Password" value="' . htmlentities($imap_password) . '" class="textfield form-control">
            <div class="subtext">'._('The password used to login to the IMAP server.').'</div>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>'._('IMAP Port').':</label>
        </td>
        <td>
            <input type="text" size="6" name="imap_port" id="IMAP Port" value="' . htmlentities($imap_port) . '" class="textfield form-control">
            <div class="subtext">'._('Service port on the IMAP server.').'</div>
        </td>
    </tr>
    <tr>
        <td>
            <label>'._('IMAP SSL').':</label>
        </td>
        <td class="checkbox">
            <label>
                <input type="checkbox" name="imap_ssl" id="IMAP SSL" class="checkfield" ' . is_checked($imap_ssl, "on") . '>
                '._('Use this to enable or disable SSL for the IMAP plugin.').'
            </label>
        </td>
    </tr>
</table>';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $servicedesc = grab_array_var($inargs, "servicedesc");
            $smtpto = grab_array_var($inargs, "smtpto");
            $smtpfrom = grab_array_var($inargs, "smtpfrom");
            $smtp_address = grab_array_var($inargs, "smtp_address");
            $smtp_username = grab_array_var($inargs, "smtp_username");
            $smtp_password = grab_array_var($inargs, "smtp_password");
            $smtp_port = grab_array_var($inargs, "smtp_port");
            $smtp_tls = grab_array_var($inargs, "smtp_tls");
            $imap_address = grab_array_var($inargs, "imap_address");
            $imap_username = grab_array_var($inargs, "imap_username");
            $imap_password = grab_array_var($inargs, "imap_password");
            $imap_port = grab_array_var($inargs, "imap_port");
            $imap_ssl = grab_array_var($inargs, "imap_ssl");


            // check for errors
            $errors = 0;
            $errmsg = array();
            if (is_valid_host_name($hostname) == false)
                $errmsg[$errors++] = _("Invalid host name.");
            if (is_valid_service_name($servicedesc) == false)
                $errmsg[$errors++] = _("Invalid service description.");
            if ($smtp_address == "")
                $errmsg[$errors++] = _("SMTP address cannot be blank.");
            if ($smtp_port == "")
                $errmsg[$errors++] = _("SMTP port cannot be blank.");
            if ($imap_address == "")
                $errmsg[$errors++] = _("IMAP address cannot be blank.");
            if ($imap_username == "")
                $errmsg[$errors++] = _("IMAP username cannot be blank.");
            if ($imap_password == "")
                $errmsg[$errors++] = _("IMAP password cannot be blank.");
            if ($imap_port == "")
                $errmsg[$errors++] = _("IMAP port cannot be blank.");

            if ($smtpto == "")
                $errmsg[$errors++] = _("To address cannot be blank.");
            if ($smtpfrom == "")
                $errmsg[$errors++] = _("From address cannot be blank.");


            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }

            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:

            // get variables that were passed to us
            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $servicedesc = grab_array_var($inargs, "servicedesc");
            $smtpto = grab_array_var($inargs, "smtpto");
            $smtpfrom = grab_array_var($inargs, "smtpfrom");
            $smtp_address = grab_array_var($inargs, "smtp_address");
            $smtp_username = grab_array_var($inargs, "smtp_username");
            $smtp_password = grab_array_var($inargs, "smtp_password");
            $smtp_port = grab_array_var($inargs, "smtp_port");
            $smtp_tls = grab_array_var($inargs, "smtp_tls");
            $imap_address = grab_array_var($inargs, "imap_address");
            $imap_username = grab_array_var($inargs, "imap_username");
            $imap_password = grab_array_var($inargs, "imap_password");
            $imap_port = grab_array_var($inargs, "imap_port");
            $imap_ssl = grab_array_var($inargs, "imap_ssl");

            $output = '
            
        <input type="hidden" name="address" value="' . htmlentities($address) . '">
        <input type="hidden" name="hostname" value="' . htmlentities($hostname) . '">
        <input type="hidden" name="servicedesc" value="' . htmlentities($servicedesc) . '">
        <input type="hidden" name="smtpto" value="' . htmlentities($smtpto) . '">
        <input type="hidden" name="smtpfrom" value="' . htmlentities($smtpfrom) . '">
        <input type="hidden" name="smtp_address" value="' . htmlentities($smtp_address) . '">
        <input type="hidden" name="smtp_username" value="' . htmlentities($smtp_username) . '">
        <input type="hidden" name="smtp_password" value="' . htmlentities($smtp_password) . '">
        <input type="hidden" name="smtp_port" value="' . htmlentities($smtp_port) . '">
        <input type="hidden" name="smtp_tls" value="' . htmlentities($smtp_tls) . '">
        <input type="hidden" name="imap_address" value="' . htmlentities($imap_address) . '">
        <input type="hidden" name="imap_username" value="' . htmlentities($imap_username) . '">
        <input type="hidden" name="imap_password" value="' . htmlentities($imap_password) . '">
        <input type="hidden" name="imap_port" value="' . htmlentities($imap_port) . '">
        <input type="hidden" name="imap_ssl" value="' . htmlentities($imap_ssl) . '">
        
            ';
            break;

        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:

            break;

        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:


            $output = '<p></p>';
            break;

        case CONFIGWIZARD_MODE_GETOBJECTS:

            $address = grab_array_var($inargs, "address");
            $hostname = grab_array_var($inargs, "hostname");
            $servicedesc = grab_array_var($inargs, "servicedesc");
            $smtpto = grab_array_var($inargs, "smtpto");
            $smtpfrom = grab_array_var($inargs, "smtpfrom");
            $smtp_address = grab_array_var($inargs, "smtp_address");
            $smtp_username = grab_array_var($inargs, "smtp_username");
            $smtp_password = grab_array_var($inargs, "smtp_password");
            $smtp_port = grab_array_var($inargs, "smtp_port");
            $smtp_tls = grab_array_var($inargs, "smtp_tls");
            $imap_address = grab_array_var($inargs, "imap_address");
            $imap_username = grab_array_var($inargs, "imap_username");
            $imap_password = grab_array_var($inargs, "imap_password");
            $imap_port = grab_array_var($inargs, "imap_port");
            $imap_ssl = grab_array_var($inargs, "imap_ssl");

            // save data for later use in re-entrance
            $meta_arr = array();
            $meta_arr["hostname"] = $hostname;
            $meta_arr["address"] = $address;
            $meta_arr["servicedesc"] = $servicedesc;
            $meta_arr["smtpto"] = $smtpto;
            $meta_arr["smtpfrom"] = $smtpfrom;
            $meta_arr["smtp_address"] = $smtp_address;
            $meta_arr["smtp_username"] = $smtp_username;
            $meta_arr["smtp_password"] = $smtp_password;
            $meta_arr["smtp_port"] = $smtp_port;
            $meta_arr["smtp_tls"] = $smtp_tls;
            $meta_arr["imap_address"] = $imap_address;
            $meta_arr["imap_username"] = $imap_username;
            $meta_arr["imap_password"] = $imap_password;
            $meta_arr["imap_port"] = $imap_port;
            $meta_arr["imap_ssl"] = $imap_ssl;
            save_configwizard_object_meta($wizard_name, $hostname, "", $meta_arr);

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    "type" => OBJECTTYPE_HOST,
                    "use" => "xiwizard_generic_host",
                    "host_name" => $hostname,
                    "address" => $hostaddress,
                    "icon_image" => "email-delivery.png",
                    "statusmap_image" => "email-delivery.png",
                    "_xiwizard" => $wizard_name,
                );
            }

            // email service
            $emailargs = "--mailto $smtpto --mailfrom $smtpfrom -H $address ";
            $emailargs .= "--smtp-server $smtp_address --smtp-username '$smtp_username' --smtp-password '$smtp_password' --smtp-port $smtp_port ";
            $emailargs .= "--imap-server $imap_address --username '$imap_username' --password '$imap_password' --imap-port $imap_port";
            if ($smtp_tls)
                $emailargs .= " --smtptls";
            if ($imap_ssl)
                $emailargs .= " --imapssl";


            $objs[] = array(
                "type" => OBJECTTYPE_SERVICE,
                "host_name" => $hostname,
                "service_description" => $servicedesc,
                "use" => "xiwizard_generic_service",
                "check_command" => "check_email_delivery!" . $emailargs,
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
