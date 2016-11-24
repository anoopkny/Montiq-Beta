<?php
//
// Latest Alerts Dashlet
// Copyright (c) 2010-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: latestalerts.inc.php 3 2010-04-02 21:41:26Z egalstad $

include_once(dirname(__FILE__) . '/../../dashlets/dashlethelper.inc.php');

function latestalerts_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{
    $output = "";
    $imgbase = get_base_url() . "includes/components/latestalerts/images/";

    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";
            $id = "latestalerts_" . random_string(6);
            $ajaxargs = $args;
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
            $output .= "	<link rel='stylesheet' type='text/css' href='" . $css_url . "includes/components/latestalerts/latestalerts.css' />
";

            $output .= '
			<div class="latestalerts_dashlet" id="' . $id . '">
						
			<div class="infotable_title">'._("Latest Alerts").'</div>
			' . get_throbber_html() . '
						
			</div><!--ahost_status_summary_dashlet-->

			<script type="text/javascript">
			$(document).ready(function(){
			
				get_' . $id . '_content();
					
				$("#' . $id . '").everyTime(90*1000, "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});
				
				function get_' . $id . '_content(){
					$("#' . $id . '").each(function(){
						var optsarr = {
							"func": "get_latestalerts_dashlet_html",
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