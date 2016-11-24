<?php
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: main.php 75 2010-04-01 19:40:08Z egalstad $

require_once(dirname(__FILE__) . '/../../common.inc.php');


// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check prereqs
check_prereqs();

// check authentication
check_authentication(false);


route_request();

function route_request()
{
    global $request;

    $hostid = grab_request_var("hostid", "-1");
    $address = grab_request_var("address", "127.0.0l.1");
    $method = grab_request_var("method", "rdp");
    $confirm = grab_request_var("confirm", "0");

    if ($confirm == "1")
        do_confirm();

    if (isset($request["btnDeleteSaved"]))
        do_delete_saved();

    else if (isset($request["btnQuickConnect"]))
        do_connect(true);

    else {
        // potentially save selection
        do_save();
        do_connect(false);
    }


}

function do_connect($quick = false)
{

    $hostid = grab_request_var("hostid", "-1");
    $address = grab_request_var("address", "127.0.0l.1");
    $method = grab_request_var("method", "rdp");
    $use = grab_request_var("use", "-1");

    // lookup quick connect option
    if ($quick == true) {
        $settings = unserialize(get_option("rdp_component_options"));
        $savedopts = $settings["saved_addresses"][$hostid][$use];
        $address = $savedopts["address"];
        $method = $savedopts["method"];
    }

    switch ($method) {

        case "vnc":
            do_vnc();
            break;

        case "rdp":
        default:
            do_rdp();
            break;

        case "telnet":
        default:
            do_telnet();
            break;

        case "ssh":
        default:
            do_ssh();
            break;

    }
}

function do_delete_saved()
{

    $hostid = grab_request_var("hostid", "-1");
    $use = grab_request_var("use", "-1");
    //echo "USE: $use<BR>";

    if ($use > 0) {

        $settings = unserialize(get_option("rdp_component_options"));
        unset($settings["saved_addresses"][$hostid][$use]);
        set_option("rdp_component_options", serialize($settings));
    }

    // show screen again
    do_confirm();

    exit();
}

function do_save()
{

    // checkboxes
    $save = checkbox_binary(grab_request_var("save", ""));

    $hostid = grab_request_var("hostid", "-1");
    $address = grab_request_var("address", "127.0.0l.1");
    $method = grab_request_var("method", "rdp");

    if ($save == 1) {

        $settings_raw = get_option("rdp_component_options");
        if ($settings_raw == "") {
            $settings = array(
                "enabled" => 1,
            );
        } else
            $settings = unserialize($settings_raw);

        $saved_addresses = grab_array_var($settings, "saved_addresses", array());
        $host_settings = grab_array_var($saved_addresses, $hostid, array());

        $settings["saved_addresses"][$hostid][] = array("address" => $address, "method" => $method);

        set_option("rdp_component_options", serialize($settings));

        //echo "SAVED!";
    }

}

function do_confirm()
{

    $hostid = grab_request_var("hostid", "-1");
    $address = grab_request_var("address", "127.0.0l.1");
    $method = grab_request_var("method", "rdp");

    do_page_start(array("page_title" => _("Connection Settings")), true);

    echo "
	<html>
	<head>
	<title>" . _("Connect To Host") . "</title>
	</head>
	<body>
	";

    echo "<h1>" . _("Connect To Host") . "</h1>";

    echo "<p>" . _("Specify the address of the host you would like to connect to, along with the connection method.  <strong>Note:</strong> The address of the host may differ if you are connecting from outside a firewall or when using port forwarding.") . "</p>";

    echo "
	<form method='post' action=''>
	<input type='hidden' name='confirm' value='0'>
	<input type='hidden' name='hostid' value='" . encode_form_val($hostid) . "'>
	";

    $settings = unserialize(get_option("rdp_component_options"));
    $saved_addresses = grab_array_var($settings, "saved_addresses", array());
    $host_settings = grab_array_var($saved_addresses, $hostid, array());
    //echo "SETTINGS:";
    //print_r($settings);
    if (count($host_settings) > 0) {
        echo "<div class='sectionTitle'>" . _("Quick Connect Options") . "</div>";
        echo "
		<table border='0'>
		<tr>
		<td>" . _("Saved setting") . ":</td>
		<td>
		<select name='use'>
		";
        foreach ($host_settings as $id => $hs) {
            echo "<option value='" . $id . "'>" . $hs["address"], " (" . $hs["method"] . ")</option>";
        }
        $hostsettings[$hostid] = array($address, $method);

        echo "
		</select>
		<input type='submit' name='btnQuickConnect' value='" . _("Connect") . "'>
		</td>
		</tr>
		<tr>
		<td></td>
		<td>
		<input type='submit' name='btnDeleteSaved' value='" . _("Delete") . "'>
		</td>
		</tr>
		</table>
		";
    }

    echo "<div class='sectionTitle'>" . _("Connection Options") . "</div>";
    echo "
	<table border='0'>	
	<tr>
	<td>" . _("Address") . ":</td>
	<td><input type='text' width='40' name='address' value='" . encode_form_val($address) . "'></td>
	</tr>
	<tr>
	<td>" . _("Method") . ":</td>
	<td>
	<select name='method'>
	<option value='rdp' " . is_selected($method, "rdp") . ">RDP</option>
	<option value='vnc' " . is_selected($method, "vnc") . ">VNC</option>
	<option value='telnet' " . is_selected($method, "telnet") . ">Telnet</option>
	<option value='ssh' " . is_selected($method, "ssh") . ">SSH</option>
	</select>
	</td>
	</tr>
	";

    echo "
	<tr>
	<td></td>
	<td>
		<label><input type='checkbox' name='save' id='save'> " . _("Save these settings") . "</label>
	</td>
	</tr>
	";

    echo "
	<tr>
	<td></td>
	<td><input type='submit' name='btnSubmit' value='" . _("Connect") . "'></td>
	</tr>
	</form>
	";

    /*
    echo "
    <script type='text/javascript'>
    $(document).ready(function() {
     $('#btnSubmit').click(function() {
             location.reload();
       });
    });
    </script>
    ";
    */

    echo "
	</body>
	</html>
	";

    do_page_end(true);

    exit();
}


function do_telnet()
{

    $address = grab_request_var("address", "127.0.0l.1");

    header("Location: telnet://" . $address);

    exit();
}

function do_ssh()
{

    $address = grab_request_var("address", "127.0.0l.1");

    header("Location: ssh://" . $address);

    exit();
}


function do_vnc()
{


    $address = grab_request_var("address", "127.0.0l.1");

    header("Content-type: application/octet-stream");
    header("content-disposition: attachment;filename=\"vnc-" . $address . ".vnc\"");

    echo "[Connection]
Host=" . $address . "
Port=5900
[Options]
UseLocalCursor=1
UseDesktopResize=1
FullScreen=0
FullColour=0
LowColourLevel=1
PreferredEncoding=ZRLE
AutoSelect=1
Shared=0
SendPtrEvents=1
SendKeyEvents=1
SendCutText=1
AcceptCutText=1
Emulate3=0
PointerEventInterval=0
Monitor=
MenuKey=F8
";

}


function do_rdp()
{

    $address = grab_request_var("address", "127.0.0l.1");

    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"rdp-" . $address . ".rdp\"");


    echo "
screen mode id:i:1
desktopwidth:i:800
desktopheight:i:600
session bpp:i:16
winposstr:s:0,9,603,284,1891,1335
auto connect:i:0
full address:s:" . $address . "
compression:i:1
keyboardhook:i:2
audiomode:i:2
redirectdrives:i:0
redirectprinters:i:1
redirectcomports:i:0
redirectsmartcards:i:1
displayconnectionbar:i:1
autoreconnection enabled:i:1
alternate shell:s:
shell working directory:s:
disable wallpaper:i:0
disable full window drag:i:0
disable menu anims:i:0
disable themes:i:0
disable cursor setting:i:0
bitmapcachepersistenable:i:1
redirectclipboard:i:1
redirectposdevices:i:0
authentication level:i:0
prompt for credentials:i:0
negotiate security layer:i:1
remoteapplicationmode:i:0
allow desktop composition:i:0
allow font smoothing:i:1
gatewayhostname:s:
gatewayusagemethod:i:0
gatewaycredentialssource:i:4
gatewayprofileusagemethod:i:0
drivestoredirect:s:
promptcredentialonce:i:1
";
}


?>

