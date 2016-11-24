<?php
//  This script was writen by webmaster@theworldsend.net, Aug.2001
//  http://www.theworldsend.net 
//  This is my first script. Enjoy.
//  
// Put it into whatever directory and call it. That's all.
// Updated to 4.2 code 
// Get Variable from form via register globals on/off
//-------------------------

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');

// initialization stuff
pre_init();

// start session
init_session();

// grab GET or POST variables 
grab_request_vars();

// check authentication
check_authentication(false);


$max_count = 10; //maximum count for ping command
$unix = 1; //set this to 1 if you are on a *unix system
$windows = 0; //set this to 1 if you are on a windows system
// -------------------------
// nothing more to be done.
// -------------------------
//globals on or off ?
$register_globals = (bool)ini_get('register_gobals');
$system = ini_get('system');
$unix = (bool)$unix;
$win = (bool)$windows;

// defaults
if ($register_globals) {
    $ip = getenv(REMOTE_ADDR);
    $self = $PHP_SELF;
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
    $self = $_SERVER['PHP_SELF'];
}

$submit = grab_request_var("submit");
$count = grab_request_var("count", 5);
$host = grab_request_var("host", $ip);
$cmd = grab_request_var("cmd");

// form submitted ?
if ($cmd != "") {
    // over count ?
    if ($count > $max_count) {
        echo _('Maximum ping count is: ') . $max_count;
        echo '<a href="' . $self . '">Back</a>';
    } else {

        // disable buffering
        ob_implicit_flush(true);
        ob_end_flush();

        // replace bad chars
        $host = preg_replace("/[^A-Za-z0-9.-]/", "", $host);
        $count = preg_replace("/[^0-9.]/", "", $count);
        echo '<body bgcolor="#FFFFFF" text="#000000"></body>';
        echo '<div style="float: right;"><a href="javascript:window.close();">' . _('Close This Window') . '</a></div>';
        echo "<b>" . _("Ping Output") . "</b>:<br>";
        echo "<hr>";
        echo '<pre>';
        //check target IP or domain
        if ($unix) {
            $cmdline = escapeshellcmd("ping -c$count -w$count $host");
            system($cmdline);
            //system("killall ping");// kill all ping processes in case there are some stalled ones or use echo 'ping' to execute ping without shell
        } else {
            system("ping -n $count $host");
        }
        echo '</pre>';
        echo "<hr>";
        echo '<p><a href="?">' . _('Ping another host') . '</a></p>';
    }
} else {
    echo '<body bgcolor="#FFFFFF" text="#000000"></body>';

    echo '<div style="float: right;"><a href="javascript:window.close();">Close This Window</a></div>';
    echo "<b>Host Ping Tool</b><br>";
    echo "<br>";

    echo '<form method="post" action="' . htmlentities($self) . '">';
    echo '   ' . _('IP Address or Host Name') . ': <input type="text" name="host" value="' . htmlentities($host) . '"></input>&nbsp;';
    echo '   ' . _('Ping Count') . ' <input type="text" name="count" size="2" value="' . htmlentities($count) . '"></input>';
    echo '<input type="hidden" name="cmd" value="ping">';
    echo '   <input type="submit" name="submit" value="' . _('Go') . '"></input>';
    echo '</form>';
    echo '<br><b>' . $system . '</b>';
    echo '</body></html>';
}
?>