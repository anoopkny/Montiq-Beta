<?php
//
// Metrics Component
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../componenthelper.inc.php');
include_once(dirname(__FILE__) . '/dashlet.inc.php');

$metrics_component_name = "metrics";
metrics_component_init();

////////////////////////////////////////////////////////////////////////
// COMPONENT INIT FUNCTIONS
////////////////////////////////////////////////////////////////////////

function metrics_component_init()
{
    global $metrics_component_name;
    $versionok = metrics_component_checkversion();

    $desc = "";
    if (!$versionok) {
        $desc = "<b>" . _("Error: This component requires Nagios XI 2011R1 or later.") . "</b>";
    }

    $args = array(
        COMPONENT_NAME => $metrics_component_name,
        COMPONENT_AUTHOR => "Nagios Enterprises, LLC",
        COMPONENT_DESCRIPTION => _("Displays metrics such as disk, CPU, and memory usage. ") . $desc,
        COMPONENT_TITLE => _("Metrics"),
        COMPONENT_VERSION => '1.2.9'
    );

    register_component($metrics_component_name, $args);

    if ($versionok) {
        register_callback(CALLBACK_MENUS_INITIALIZED, 'metrics_component_addmenu');
    }

    $args = array();
    $args[DASHLET_NAME] = "metrics";
    $args[DASHLET_TITLE] = "Metrics Overview";
    $args[DASHLET_FUNCTION] = "metrics_dashlet_func";
    $args[DASHLET_DESCRIPTION] = _("Displays an overview metrics such as disk, CPU, and memory usage.");
    $args[DASHLET_WIDTH] = "350";
    $args[DASHLET_HEIGHT] = "250";
    $args[DASHLET_INBOARD_CLASS] = "metrics_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS] = "metrics_map_outboard";
    $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2011 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE] = true;
    register_dashlet($args[DASHLET_NAME], $args);

    $args = array();
    $args[DASHLET_NAME] = "metricsguage";
    $args[DASHLET_TITLE] = "Metrics Guage";
    $args[DASHLET_FUNCTION] = "metricsguage_dashlet_func";
    $args[DASHLET_DESCRIPTION] = _("Displays service metric such as disk, CPU, and memory usage.");
    $args[DASHLET_WIDTH] = "200";
    $args[DASHLET_HEIGHT] = "150";
    $args[DASHLET_INBOARD_CLASS] = "metrics_map_inboard";
    $args[DASHLET_OUTBOARD_CLASS] = "metrics_map_outboard";
    $args[DASHLET_AUTHOR] = "Nagios Enterprises, LLC";
    $args[DASHLET_COPYRIGHT] = "Dashlet Copyright &copy; 2011 Nagios Enterprises. All rights reserved.";
    $args[DASHLET_HOMEPAGE] = "http://www.nagios.com";
    $args[DASHLET_SHOWASAVAILABLE] = false;
    register_dashlet($args[DASHLET_NAME], $args);
}


///////////////////////////////////////////////////////////////////////////////////////////
// MISC FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function metrics_component_checkversion()
{
    if (!function_exists('get_product_release'))
        return false;
    if (get_product_release() < 415)
        return false;
    return true;
}

function metrics_component_addmenu($arg = null)
{
    global $metrics_component_name;

    $mi = find_menu_item(MENU_HOME, "menu-home-sectionend-details", "id");
    if ($mi == null)
        return;

    $order = grab_array_var($mi, "order", "");
    if ($order == "")
        return;

    $neworder = $order - 0.1;

    add_menu_item(MENU_HOME, array(
        "type" => "linkspacer",
        "order" => $neworder,
    ));

    add_menu_item(MENU_HOME, array(
        "type" => "link",
        "title" => _("Metrics"),
        "id" => "menu-home-metrics",
        "order" => $neworder + 0.001,
        "opts" => array(
            "href" => get_base_url() . 'includes/components/metrics/',
            "icon" => "fa-tachometer"
        )
    ));
}


///////////////////////////////////////////////////////////////////////////////////////////
// AJAX FUNCTIONS
///////////////////////////////////////////////////////////////////////////////////////////

function xicore_ajax_get_metricsguage_dashlet_html($args = null)
{

    $output = '';

    $host = grab_array_var($args, "host", "");
    $service = grab_array_var($args, "service", "");
    $metric = grab_array_var($args, "metric", "");

    $percent = grab_array_var($args, "percent", "");
    $current = grab_array_var($args, "current", "");
    $uom = grab_array_var($args, "uom", "");
    $warn = grab_array_var($args, "warn", "");
    $crit = grab_array_var($args, "crit", "");
    $min = grab_array_var($args, "min", "");
    $max = grab_array_var($args, "max", "");

    $plugin_output = grab_array_var($args, "plugin_output", "");

    $sortorder = grab_array_var($args, "sortorder", "desc");

    $mode = grab_array_var($args, "mode", DASHLET_MODE_INBOARD);


    if ($mode == DASHLET_MODE_INBOARD) {
        $args = array(
            "host" => $host,
            "service" => $service,
            "metric" => $metric,
        );
        $metricdata = get_service_metrics($args);
        foreach ($metricdata as $id => $arr) {
            $current = grab_array_var($arr, "current");
            $warn = grab_array_var($arr, "warn");
            $crit = grab_array_var($arr, "crit");
            $min = grab_array_var($arr, "min");
            $max = grab_array_var($arr, "max");
            $percent = grab_array_var($arr, "sortval");
        }
        //$output.="LOOKUP<BR>";
    }


    $base_url = get_base_url();


    $display_val = $percent . "%";


    $imgurlb = "";
    // adjustments for load
    if ($metric == "load") {
        $max = $crit * 1.2;
        $percent = ($current / ($max - $min)) * 100;
        $display_val = $current;
        //$percent=$current;
        //$imgurlb="&minscale=0&maxscale=$max";
    }
    //$imgurl="index.php?mode=chart&percent=".urlencode($percent)."&current=".urlencode($current)."&min=".urlencode($min)."&max=".urlencode($max)."&warn=".urlencode($warn)."&crit=".urlencode($crit).$imgurlb;
    //$output.='<img src="'.$imgurl.'"><br>';

    /*
    $output.='PERCENT=<b>'.$percent.'</b>%<BR>';
    $output.='CURRENT=<b>'.$current.'</b> '.$uom.'<br>';
    $output.='MIN=<b>'.$min.'</b><br>';
    $output.='MAX=<b>'.$max.'</b><br>';
    $output.='WARN=<b>'.$warn.'</b><br>';
    $output.='CRIT=<b>'.$crit.'</b><br>';
    */

    $warn_width = 0;
    $crit_width = 0;
    if ($crit > 0 && $max > 0) {
        $crit_width = ($crit / ($max - $min)) * 100;
    }
    if ($warn > 0 && $max > 0) {
        $warn_width = ($warn / ($max - $min)) * 100;
    }

    $width = intval($percent);
    $color = "blue";

    if ($crit_width > 0 && $width >= $crit_width)
        $color = "#FF795F";
    else if ($warn_width > 0 && $width >= $warn_width)
        $color = "#FEFF5F";
    else
        $color = "#B2FF5F";

    $status_url = $base_url . "includes/components/xicore/status.php?show=servicedetail&host=" . urlencode($host) . "&service=" . urlencode($service);
    $title = $plugin_output;


    //metrics_component_get_display_params($metric,intval($percent),$width,$color);
    $output .= '<div style="position: relative; border: 1px solid #aaaaaa; width: 100px; height: 16px; ">';

    $output .= '<div style="background-color: ' . $color . '; width: ' . $width . 'px; height: 14px; text-align: center;">';
    $output .= '<b>' . $display_val . '</b>';
    $output .= '</div>';

    if ($crit_width > 0)
        $output .= '<div style="position: absolute; top: 0px; left: 0px; border-right: 1px solid red; width: ' . $crit_width . 'px; height: 14px;"></div>';
    if ($warn_width > 0)
        $output .= '<div style="position: absolute; top: 0px; left: 0px; border-right: 1px solid #e5e500; width: ' . $warn_width . 'px;  height: 14px;"></div>';

    $output .= '</div>';
    
    $output .= '<a href="' . $status_url . '" title="' . $title . '"><b>' . $host . '</b><br><b>' . $service . '</b></a><br>';

    return $output;
}

function xicore_ajax_get_metrics_dashlet_html($args = null)
{

    $host = grab_array_var($args, "host", "");
    $hostgroup = grab_array_var($args, "hostgroup", "");
    $servicegroup = grab_array_var($args, "servicegroup", "");
    $maxitems = grab_array_var($args, "maxitems", 20);
    $metric = grab_array_var($args, "metric", "disk");
    $showperfgraphs = grab_array_var($args, "showperfgraphs", true);
    $details = grab_array_var($args, "details");

    // special "all" stuff
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // can do hostgroup OR servicegroup OR host
    if ($hostgroup != "") {
        $servicegroup = "";
        $host = "";
    } else if ($servicegroup != "") {
        $host = "";
    }


    // get service metrics
    $metricdata = get_service_metrics($args);

    $output = "";

    $pretitle = "";
    if ($host != "")
        $pretitle = "For Host '$host' ";
    else if ($hostgroup != "")
        $pretitle = "For Hostgroup '$hostgroup' ";
    else if ($servicegroup != "")
        $pretitle = "For Servicegroup '$servicegroup' ";

    $output .= '<h5>' . get_metric_description($metric) . ' ' . $pretitle . '</h5>';

    //$output.='ARGS='.serialize($args).'<BR>';

    $output .= '
    <table class="table table-condensed table-auto-width table-striped table-bordered">
        <thead>
            <tr>
                <th>' . _('Host') . '</th>
                <th>' . _('Service') . '</th>
                <th>' . get_metric_value_description($metric) . '</th>';

    // hide details column if requested
    if (!$details) {
        $output .= '
                <th>
                    ' . _('Details') . '
                </th>
            </tr>';
    } else {
        $output .= '
            </tr>';
    }

    $output .= '
        </thead>

        <tbody>';

    $base_url = get_base_url();
    $status_url = $base_url . "includes/components/xicore/status.php";

    $current_item = 0;
    if (count($metricdata) > 0) {
        foreach ($metricdata as $id => $arr) {

            $current_item++;
            if ($current_item > $maxitems)
                break;

            $current = grab_array_var($arr, "current");
            $warn = grab_array_var($arr, "warn");
            $crit = grab_array_var($arr, "crit");

            $hostname = grab_array_var($arr, "host_name");
            $servicename = grab_array_var($arr, "service_name");

            $output .= '<tr>';
            $output .= '
                    <td>
                        <a href="' . $status_url . '?show=hostdetail&host=' . urlencode($hostname) . '">' . $hostname . '</a>
                    </td>';
            $output .= '
                    <td>
                        <a href="' . $status_url . '?show=servicedetail&host=' . urlencode($hostname) . '&service=' . urlencode($servicename) . '">' . $servicename . '</a>
                    </td>';
            $output .= '<td>';
            $output .= '<span style="float: left; margin-right: 10px;">' . $arr["displayval"] . '</span>';

            metrics_component_get_display_params($metric, intval($arr["sortval"]), $current, $warn, $crit, $width, $color);

            $output .= '<div style="background-color: ' . $color . '; width: ' . $width . 'px; height: 15px; margin-left: 55px;"></div>';
            $output .= '</td>';

            if (!$details) {
                $output .= '<td>';
                $output .= $arr["output"];
                $output .= '</td>';
            }

            $output .= '</tr>';
        }
    } else {
        $output .= '<tr>
                        <td colspan="4">
                            ' . _('No matching data to display.') . '
                        </td>
                    </tr>';
    }

    $output .= '
        </tbody>
    </table>';

    $output .= '
        <div class="ajax_date">
            ' . _('Last Updated') . ': ' . get_datetime_string(time()) . '
        </div>';

    return $output;
}


function metrics_component_get_display_params($metric, $sortval, $current, $warn, $crit, &$width, &$color)
{

    $width = $sortval;
    $color = "green";

    $use_global_thresholds = false;

    if ($use_global_thresholds == true) {

        switch ($metric) {

            case "disk":

                // some defaults (should be configurable later)
                if ($sortval > 90)
                    $color = "red";
                else if ($sortval > 75)
                    $color = "orange";
                else if ($sortval > 60)
                    $color = "yellow";
                break;

            case "cpu":

                // some defaults (should be configurable later)
                if ($sortval > 95)
                    $color = "red";
                else if ($sortval > 85)
                    $color = "orange";
                else if ($sortval > 70)
                    $color = "yellow";
                break;

            case "memory":

                // some defaults (should be configurable later)
                if ($sortval > 95)
                    $color = "red";
                else if ($sortval > 90)
                    $color = "orange";
                else if ($sortval > 85)
                    $color = "yellow";
                break;

            // special case for load
            case "load":
                if ($width > 100)
                    $width = 100;
                break;

            default:
                break;
        }
    } else {

        if ($current > $crit)
            $color = "#FF795F";
        else if ($current > $warn)
            $color = "#FEFF5F";
        else
            $color = "#B2FF5F";
    }

    if ($width == 0)
        $width = 1;
    if ($width < 0)
        $width = 0;
}


?>