<?php
//
// Metrics Dashlet
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../../dashlets/dashlethelper.inc.php');

// Guage
function metricsguage_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";
    $imgbase = get_base_url() . "includes/components/metrics/images/";

    $metric = grab_array_var($args, "metric", "");

    $args["mode"] = $mode;


    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";

            $id = "metricsguage_" . random_string(6);

            // ajax updater args
            $ajaxargs = $args;
            // build args for javascript
            $n = 0;
            $jargs = "{";
            foreach ($ajaxargs as $var => $val) {
                if ($n > 0)
                    $jargs .= ", ";
                $jargs .= "\"$var\" : \"$val\"";
                $n++;
            }
            $jargs .= "}";

            $css_url = get_base_url();
            $output .= "	<link rel='stylesheet' type='text/css' href='" . $css_url . "includes/components/metrics/metrics.css' />
";

            $output .= '
			
			<div class="metricsguage_dashlet" id="' . $id . '">
			
			<h5>' . get_metric_description($metric) . '</h5>
			' . get_throbber_html() . '
			
			</div><!--ahost_status_summary_dashlet-->

			<script type="text/javascript">
			$(document).ready(function(){
			
				get_' . $id . '_content();
					
				$("#' . $id . '").everyTime(300*1000, "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});
				
				function get_' . $id . '_content(){
					$("#' . $id . '").each(function(){
						var optsarr = {
							"func": "get_metricsguage_dashlet_html",
							"args": ' . $jargs . '
							}
						var opts=array2json(optsarr);
						get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
						});
					}
			});
			</script>
			';

            break;

        case DASHLET_MODE_PREVIEW:
            $output = "<p><img src='" . $imgbase . "guagepreview.png'></p>";
            break;
    }

    return $output;
}


// overview dashlet
function metrics_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";
    $imgbase = get_base_url() . "includes/components/metrics/images/";

    $metric = grab_array_var($args, "metric", "");


    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";

            $id = "metrics_" . random_string(6);

            // ajax updater args
            $ajaxargs = $args;
            // build args for javascript
            $n = 0;
            $jargs = "{";
            foreach ($ajaxargs as $var => $val) {
                if ($n > 0)
                    $jargs .= ", ";
                $jargs .= "\"$var\" : \"$val\"";
                $n++;
            }
            $jargs .= "}";

            $css_url = get_base_url();
            $output .= "	<link rel='stylesheet' type='text/css' href='" . $css_url . "includes/components/metrics/metrics.css' />
";

            $output .= '
			
			<div class="metrics_dashlet" id="' . $id . '">
			
			<div class="infotable_title">' . get_metric_description($metric) . '</div>
			' . get_throbber_html() . '
			
			</div><!--ahost_status_summary_dashlet-->

			<script type="text/javascript">
			$(document).ready(function(){
			
				get_' . $id . '_content();
					
				$("#' . $id . '").everyTime(300*1000, "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});
				
				function get_' . $id . '_content(){
					$("#' . $id . '").each(function(){
						var optsarr = {
							"func": "get_metrics_dashlet_html",
							"args": ' . $jargs . '
							}
						var opts=array2json(optsarr);
						get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
						});
					}
			});
			</script>
			';

            break;

        case DASHLET_MODE_PREVIEW:
            $output = "<p><img src='" . $imgbase . "preview.png'></p>";
            break;
    }

    return $output;
}


?>