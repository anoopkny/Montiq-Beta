<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// MISC AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////

/**
 * @return string
 */
function xicore_ajax_get_tray_alert_html()
{
    $critical_problem = false;
    $noncritical_problem = false;

    $html = "<ul>";

    $last_update_check_succeeded = get_option("last_update_check_succeeded");
    $update_available = get_option("update_available");

    //$update_available=1;
    //$last_update_check_succeeded=0;

    $base_url = get_base_url();

    if ($update_available) {
        $html .= "<li><img src='" . theme_image("info_small.png") . "'> <a href='https://go.nagios.com/upgradexi' target='_blank'>" . _("New Nagios XI release available!") . "</a></strong></li>";
        $noncritical_problem = true;
    } else if ($last_update_check_succeeded == 0 && is_admin() == true) {
        $html .= "<li><img src='" . theme_image("critical_small.png") . "'> <a href='" . $base_url . "admin/?xiwindow=updates.php'>" . _("Last update check failed") . "</a></li>";
        $noncritical_problem = true;
    }
    // get sysstat data
    $xml = get_xml_sysstat_data();
    if ($xml) {
        $problem = false;
        foreach ($xml->daemons->daemon as $d) {
            $status = intval($d->status);
            switch ($d["id"]) {
                case "nagioscore":
                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $problem = true;
                    break;
                case "pnp":
                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $problem = true;
                    break;
                case "ndoutils":
                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $problem = true;
                    break;
                default:
                    break;
            }
        }
        if ($problem == true) {
            $critical_problem = true;
            $html .= "<img src='" . theme_image("critical_small.png") . "'> <a href='" . $base_url . "admin/?xiwindow=sysstat.php'><b> " . _("System status degraded!") . "</b></a>";
        }
    }

    // get process status
    $args = array(
        "cmd" => "getprogramstatus",
    );
    $xml = get_backend_xml_data($args);
    if ($xml) {

        // active host checks
        $v = intval($xml->programstatus->active_host_checks_enabled);
        if ($v == 0) {
            $text = _("Active host checks are disabled");
            $img = theme_image("info_small.png");
            $html .= "<li><img src='" . $img . "' alt='" . $text . "' title='" . $text . "'> <a href='" . $base_url . "admin/?xiwindow=sysstat.php%3Fpageopt=monitoringengine'> " . $text . "</a></li>";
        }

        // active service checks
        $v = intval($xml->programstatus->active_service_checks_enabled);
        if ($v == 0) {
            $text = _("Active service checks are disabled");
            $img = theme_image("info_small.png");
            $html .= "<li><img src='" . $img . "' alt='" . $text . "' title='" . $text . "'> <a href='" . $base_url . "admin/?xiwindow=sysstat.php%3Fpageopt=monitoringengine'> " . $text . "</a></li>";
        }

        // notifications
        $v = intval($xml->programstatus->notifications_enabled);
        if ($v == 0) {
            $text = _("Notifications are disabled");
            $img = theme_image("info_small.png");
            $html .= "<li><img src='" . $img . "' alt='" . $text . "' title='" . $text . "'> <a href='" . $base_url . "admin/?xiwindow=sysstat.php%3Fpageopt=monitoringengine'> " . $text . "</a></li>";
        }
    }

    $html .= '</ul>';

    // check for unhandled problems...
    $problemhtml = "";

    // unhandled host problems
    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount
    $backendargs["current_state"] = "in:1,2"; // down or unreachable
    $backendargs["problem_acknowledged"] = 0; // not acknowledged
    $backendargs["scheduled_downtime_depth"] = 0; // not in downtime
    $xml = get_xml_host_status($backendargs);
    if ($xml) {
        $total = intval($xml->recordcount);
        if ($total > 0) {
            $noncritical_problem = true;
            $problemhtml .= "<li><img src='" . theme_image("warning_small.png") . "'> <a href='" . $base_url . "/?xiwindow=" . urlencode("includes/components/xicore/status.php?show=hosts&hoststatustypes=12&hostattr=10") . "'>
				<b>" . $total . "</b> " . _("Unhandled host problems") . "</a></li>";
        }
    }

    // unhandled service problems
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = 1; // combined host status
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount
    $backendargs["host_current_state"] = "0"; // host up
    $backendargs["host_problem_acknowledged"] = 0; // host not acknowledged
    $backendargs["host_scheduled_downtime_depth"] = 0; // host not in downtime
    $backendargs["current_state"] = "in:1,2,3"; // non-ok state
    $backendargs["problem_acknowledged"] = 0; // not acknowledged
    $backendargs["scheduled_downtime_depth"] = 0; // not in downtime
    $xml = get_xml_service_status($backendargs);
    if ($xml) {
        $total = intval($xml->recordcount);
        if ($total > 0) {
            $noncritical_problem = true;
            $problemhtml .= "<li><img src='" . theme_image("warning_small.png") . "'> <a href='" . $base_url . "/?xiwindow=" . urlencode("includes/components/xicore/status.php?show=services&hoststatustypes=2&servicestatustypes=28&serviceattr=10") . "'>
				<b>" . $total . "</b> " . _("Unhandled service problems") . "</a></li>";
        }
    }

    if ($critical_problem == true || $noncritical_problem == true) {
        $html .= "<ul>";
        $html .= $problemhtml;
        $html .= "</ul>";
    } else {
        $html .= "<p>" . _("No problems detected.") . "</p>";
    }

    $html .= '<br>';
    $html .= '<div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>';

    $html .= '<div id="tray_alerter_status" style="visibility: hidden;">';
    if ($critical_problem == true) {
        $html .= "<img src='" . theme_image("critical_small.png") . "' alt='Critical Problems Detected' title='"._('Critical Problems Detected')."'>";
    } else if ($noncritical_problem == true) {
        $html .= '<img src="' . theme_image("error.png") . '" class="spin-y" alt="Problems Detected" data-toggle="tooltip" data-placement="top" title="'._('Problems Detected').'">';
    } else {
        $html .= "<img src='" . theme_image("info_small.png") . "' alt='No Problems Detected' title='"._('Critical Problems Detected')."'>";
    }
    $html .= '</div>';

    return $html;
}


/**
 * @return string
 */
function xicore_ajax_get_login_alert_popup_html()
{

    //return "";
    //sleep(10);

    $html = "";

    $last_update_check_succeeded = get_option("last_update_check_succeeded");
    $update_available = get_option("update_available");

    //$update_available=1;
    //$last_update_check_succeeded=0;

    $base_url = get_base_url();

    if ($update_available) {
        $html .= "
	<strong><img src='" . theme_image("info_small.png") . "'> " . _("New Nagios XI Release Available!") . "</strong>
	<br>" . _("A new version of Nagios XI is available.  The new version may have important security or bug fixes that should be applied to this server.") . "<br><ul>";

        if (is_admin() == true)
            $html .= "<li><a href='" . $base_url . "admin/?xiwindow=updates.php'><b>" . _("See details") . "</b></a></li>";
        $html .= "<li><a href='https://go.nagios.com/upgradexi' target='_blank'><b>" . _("Download the latest version") . "</b></a></li>";
        $html .= "</ul>";
        $html .= "<hr>";
    } else if ($last_update_check_succeeded == 0) {
        $html .= "
	<strong><img src='" . theme_image("critical_small.png") . "'> " . _("Update Check Failed.") . " </strong>
	<br>" . _("The last update check failed.  Make sure your Nagios XI server can access the Internet and check for program updates.
	Staying updated with the latest release of Nagios XI is important to preventing security breaches.") . "<br><ul>";
        if (is_admin() == true)
            $html .= "<li><a href='" . $base_url . "admin/?xiwindow=updates.php'><b>" . _("Try a manual update check") . "</b></a></li>";
        $html .= "</ul>";
        $html .= "<hr>";
    }

    // get sysstat data
    $xml = get_xml_sysstat_data();
    if ($xml) {
        $problem = false;
        foreach ($xml->daemons->daemon as $d) {
            $status = intval($d->status);
            switch ($d["id"]) {
                case "nagioscore":
                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $problem = true;
                    break;
                case "pnp":
                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $problem = true;
                    break;
                case "ndoutils":
                    if ($status != SUBSYS_COMPONENT_STATUS_OK)
                        $problem = true;
                    break;
                default:
                    break;
            }
        }
        if ($problem == true) {
            $html .= "<strong><img src='" . theme_image("critical_small.png") . "'> " . _("System Status Degraded!") . "</strong>
			<br>" . _("One or more critical components of Nagios XI has been stopped, is disabled, or has malfunctioned.
			This can cause problems with monitoring, notifications, reporting, and more. You should investigate this problem immediately") . ".<br>
			<ul>
			<li><a href='" . $base_url . "admin/?xiwindow=sysstat.php'><b>" . _("Check system status") . "</b></a></li>
			<li><a href='" . $base_url . "admin/?xiwindow=sysstat.php'><b>" . _("Check monitoring engine status") . "</b></a></li>
			</ul>
	<hr>";
        }
    }

    // check for unhandled problems...
    $problem = false;
    $problemhtml = "";

    // unhandled host problems
    $backendargs = array();
    $backendargs["cmd"] = "gethoststatus";
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount
    $backendargs["current_state"] = "in:1,2"; // down or unreachable
    $backendargs["problem_acknowledged"] = 0; // not acknowledged
    $backendargs["scheduled_downtime_depth"] = 0; // not in downtime
    $xml = get_xml_host_status($backendargs);
    if ($xml) {
        $total = intval($xml->recordcount);
        if ($total > 0) {
            $problem = true;
            $problemhtml .= "<li><a href='" . $base_url . "/?xiwindow=" . urlencode("includes/components/xicore/status.php?show=hosts&hoststatustypes=12&hostattr=10") . "'>
			<b>" . $total . " " . _("Unhandled Host Problems") . "</b></a></li>";
        }
    }

    // unhandled service problems
    $backendargs = array();
    $backendargs["cmd"] = "getservicestatus";
    $backendargs["combinedhost"] = 1; // combined host status
    $backendargs["limitrecords"] = false; // don't limit records
    $backendargs["totals"] = 1; // only get recordcount
    $backendargs["host_current_state"] = "0"; // host up
    $backendargs["host_problem_acknowledged"] = 0; // host not acknowledged
    $backendargs["host_scheduled_downtime_depth"] = 0; // host not in downtime
    $backendargs["current_state"] = "in:1,2,3"; // non-ok state
    $backendargs["problem_acknowledged"] = 0; // not acknowledged
    $backendargs["scheduled_downtime_depth"] = 0; // not in downtime
    $xml = get_xml_service_status($backendargs);
    if ($xml) {
        $total = intval($xml->recordcount);
        if ($total > 0) {
            $problem = true;
            $problemhtml .= "<li><a href='" . $base_url . "/?xiwindow=" . urlencode("includes/components/xicore/status.php?show=services&hoststatustypes=2&servicestatustypes=28&serviceattr=10") . "'>
			<b>" . $total . " " . _("Unhandled Service Problems") . "</b></a></li>";
        }
    }


    if ($problem == true) {
        $html .= "<strong><img src='" . theme_image("critical_small.png") . "'> " . _("Unhandled Problems!") . "</strong>
		<br>" . _("There are one or more unhandled problems that require attention") . ".<br><ul>";
        $html .= $problemhtml;
        $html .= "</ul>";
        $html .= "<hr>";
    }


    return $html;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_pagetop_alert_content_html($args = null)
{

    $admin = is_admin();
    $urlbase = get_base_url();

    $error = false;
    $warning = false;

    $output = "";

    // get sysstat data
    $xml = get_xml_sysstat_data();

    if ($xml == null) {
        if ($admin == true)
            $output .= "<a href='" . $urlbase . "admin/sysstat.php'>";

        $text = _("Could not read program data!");
        $img = theme_image("critical_small.png");
        $output .= "<img src='" . $img . "'> " . $text;

        if ($admin == true)
            $output .= "</a>";
    } else {

        foreach ($xml->daemons->daemon as $d) {

            if ($admin == true) {
                $output .= "<a href='" . $urlbase . "admin/?xiwindow=" . urlencode("sysstat.php") . "'>";
            }

            $text = "";

            $status = intval($d->status);

            switch ($status) {
                case SUBSYS_COMPONENT_STATUS_OK:
                    $img = theme_image("ok_small.png");
                    break;
                case SUBSYS_COMPONENT_STATUS_ERROR:
                    $img = theme_image("critical_small.png");
                    $error = true;
                    break;
                case SUBSYS_COMPONENT_STATUS_UNKNOWN:
                    $img = theme_image("unknown_small.png");
                    $warning = true;
                    break;
                default:
                    break;
            }

            switch ($d["id"]) {
                case "nagioscore":
                    $text = "Monitoring engine";
                    if ($status == SUBSYS_COMPONENT_STATUS_OK)
                        $stext = _("Running");
                    else
                        $stext = _("Not running");
                    break;
                case "pnp":
                    $text = "Performance grapher";
                    if ($status == SUBSYS_COMPONENT_STATUS_OK)
                        $stext = _("Running");
                    else
                        $stext = _("Not running");
                    break;
                case "ndoutils":
                    $text = "Database backend";
                    if ($status == SUBSYS_COMPONENT_STATUS_OK)
                        $stext = _("Running");
                    else
                        $stext = _("Not running");
                    break;
                default:
                    //$output.="D: ".$d["id"];
                    break;
            }

            $output .= "<img src='" . $img . "' alt='" . $stext . "' title='" . $stext . "'> " . $text;
            $output .= "</a>";
        }

        
    }

    // get process status
    $args = array(
        "cmd" => "getprogramstatus",
    );
    $xml = get_backend_xml_data($args);
    if ($xml) {

        // notifications
        $v = intval($xml->programstatus->notifications_enabled);
        if ($v == 0) {
            $text = _("Disabled");
            $img = theme_image("info_small.png");
        } else {
            $text = _("Enabled");
            $img = theme_image("ok_small.png");
        }

        if ($admin == true) { $output .= "<a href='" . $urlbase . "admin/?xiwindow=" . urlencode("sysstat.php?pageopt=monitoringengine") . "'>"; }
        $output .= "<img class='tt' src='" . $img . "' alt='" . $text . "' title='" . $text . "'> " . _('Notifications');
        if ($admin == true) { $output .= "</a>"; }

        // active host checks
        $v = intval($xml->programstatus->active_host_checks_enabled);
        if ($v == 0) {
            $text = _("Disabled");
            $img = theme_image("info_small.png");
        } else {
            $text = _("Enabled");
            $img = theme_image("ok_small.png");
        }

        if ($admin == true) { $output .= "<a href='" . $urlbase . "admin/?xiwindow=" . urlencode("sysstat.php?pageopt=monitoringengine") . "'>";}
        $output .= "<img src='" . $img . "' alt='" . $text . "' title='" . $text . "'> " . _("Active host checks");
        if ($admin == true) { $output .= "</a>"; }

        // active service checks
        $v = intval($xml->programstatus->active_service_checks_enabled);
        if ($v == 0) {
            $text = _("Disabled");
            $img = theme_image("info_small.png");
        } else {
            $text = _("Enabled");
            $img = theme_image("ok_small.png");
        }

        if ($admin == true) {$output .= "<a href='" . $urlbase . "admin/?xiwindow=" . urlencode("sysstat.php?pageopt=monitoringengine") . "'>"; }
        $output .= "<img src='" . $img . "' alt='" . $text . "' title='" . $text . "'> " . _('Active service checks');
        if ($admin == true) { $output .= "</a>"; }
        
    }

    $class = "ok";
    $t = _("System Ok") . ":&nbsp;";
    if ($error == true) {
        $class = "error";
        $t = _("System Problem") . ":&nbsp;";
    } else if ($warning == true) {
        $class = "warning";
        $t = _("System Problem") . ":&nbsp;";
    }
    $pre = "<div class='pagetopalert" . $class . "'>";

    $post = "";
    $post .= "</div>";


    if (get_theme() == 'xi5') {

        $img = '<img src="'.theme_image("ok_small.png").'">';
        $text = _('System is OK');
        if ($error) {
            $text = _('System problems detected');
            $img = '<img src="'.theme_image("critical_small.png").'">';
        } else if ($warning) {
            $text = _('System warnings detected');
            $img = '<img src="'.theme_image("warning_small.png").'">';
        }

        return '<a id="topalert-popover" title="'.$text.'" data-placement="bottom" data-content="'.$output.'">'.$img.'</a>';
    }

    return $pre . $t . $output . $post;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_xi_news_feed_html($args = null)
{

    $output = '';

    $output .= '
	<table class="infotable">
	<thead>
	<tr><th>&nbsp;</th></tr>
	</thead>
	<tbody>
	';

    $output .= "<tr><td>";
    $output .= "<ul>";

    // where do we get news from
    $url = "https://www.nagios.com/backend/feeds/products/nagiosxi/";

    $update_news = false;
    $news = array();
    $newsraw = get_meta(METATYPE_NONE, 0, "xinews");
    if ($newsraw == null || have_value($newsraw) == false)
        $update_news = true;
    else {

        $news = unserialize($newsraw);

        // is it time to update the news? */
        $now = time();
        if (($now - intval($news["time"])) > 60 * 60 * 24)
            $update_news = true;

        //print_r($news);
    }

    //$update_news=true;

    // fetch new news
    if ($update_news == true) {

        // fetch news
        $rss = simplexml_load_file($url);
        //$rss=new SimpleXMLElement($url,LIBXML_NOCDATA,true);
        //print_r($rss);

        $newsitems = array();
        foreach ($rss->channel->item as $i) {
            $newsitems[] = array(
                "link" => strval($i->link),
                "title" => strval($i->title),
                "description" => strval($i->description),
            );
        }

        // cache news
        $news["time"] = time();
        //$news["rss"]=json_encode($rss);
        $news["rss"] = json_encode($newsitems);
        //print_r($news);
        set_meta(METATYPE_NONE, 0, "xinews", serialize($news));
        $newsitems_s = json_decode($news["rss"]);
        $newsitems = (array)$newsitems_s;
    } // use cached news
    else {
        //print_r($newsraw);
        $news = unserialize($newsraw);
        $newsitems_s = json_decode($news["rss"]);
        $newsitems = (array)$newsitems_s;
    }


    $x = 0;
    //print_r($newsitems);
    foreach ($newsitems as $is) {
        $x++;
        if ($x > 3)
            break;
        $i = (array)$is;
        $link = strval($i["link"]);
        $title = strval($i["title"]);
        $description = strval($i["description"]);
        $output .= "<li><a href='" . $link . "' target='_blank'>" . $title . "</a><br>" . $description . "</li>";
    }


    $output .= "</ul>";
    $output .= "</td></tr>";

    $output .= '
	</tbody>
	</table>
	';

    return $output;
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_available_updates_html($args = null)
{

    // Check if we are going to force an update
    $force = false;
    if ($args != null) {
        if ($args['force'] == "yes") {
            $force = true;
        }
    }

    // check for updates
    do_update_check($force);

    $update_info = array(
        "last_update_check_time" => get_option("last_update_check_time"),
        "last_update_check_succeeded" => get_option("last_update_check_succeeded"),
        "update_available" => get_option("update_available"),
        "update_version" => get_option("update_version"),
        "update_release_date" => get_option("update_release_date"),
        "update_release_notes" => get_option("update_release_notes"),
    );

    //print_r($update_info);

    if ($update_info["last_update_check_succeeded"] == 0) {
        $update_str = "<p><div style='float: left; margin-right: 10px;'><img src='" . theme_image("unknown_small.png") . "'></div>
		<b>" . _("Update Check Problem: Last update check failed.") . "</b></p>";
    } else if ($update_info["update_available"] == 1) {
        $update_str = "<p><div style='float: left; margin-right: 10px;'><img src='" . theme_image("critical_small.png") . "'></div>
		<b>" . _("A new Nagios XI update is available") . ".</b></p>";

        if ($update_info["update_release_notes"] != "")
            $update_str .= "<p>" . $update_info["update_release_notes"];

        $update_str .= "<p>" . _("Visit") . " <a href='https://www.nagios.com/products/nagiosxi/' target='_blank'>www.nagios.com</a> " . _("to obtain the latest update") . ".</p>";
    } else {
        $update_str = "<p><div style='float: left; margin-right: 10px;'><img src='" . theme_image("ok_small.png") . "'></div>
		<b>" . _("Your Nagios XI installation is up to date.") . "</b></p>";
    }
    //$update_str.="<BR><BR>";

    $output = '';

    $output .= '
	<div class="infotable-wrapper">
	<table class="infotable">
	<tbody>
	';

    $output .= '<tr><td colspan="2">' . $update_str . '</td></tr>';

    $output .= '<tr><td>' . _('Latest Available Version') . ':</td><td>' . $update_info["update_version"] . '</td></tr>';
    $output .= '<tr><td>' . _('Installed Version') . ':</td><td>' . get_product_version() . '</td></tr>';
    $output .= '<tr><td>' . _('Last Update Check') . ':</td><td>' . get_datetime_string($update_info["last_update_check_time"]) . '</td></tr>';

    $output .= '
	</tbody>
	</table>
	</div>
	';

    $output .= '
	<div style="clear:both;" class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
	';

    return $output;
}

/**
* function to set specified user api key and return that value
*
* @param args array - contains user_id as element
*
* @return string - string of new random api key
*/
function xicore_ajax_set_random_api_key($args) {

    if (empty($args['user_id']))
        return "";

    $user_id = $args['user_id'];

    if (!is_valid_user_id($user_id))
        return "";

    change_user_attr($user_id, 'api_key', random_string(64));

    return get_user_attr($user_id, 'api_key');
}

 