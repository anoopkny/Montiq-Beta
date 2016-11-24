<?php
// MINEMAP DASHLET
//
// Copyright (c) 2010-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id: minemap.inc.php 3 2010-04-02 21:41:26Z egalstad $

include_once(dirname(__FILE__) . '/../../dashlets/dashlethelper.inc.php');


function minemap_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";
    $imgbase = get_base_url() . "includes/components/minemap/images/";

    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";

            $id = "minemap_" . random_string(6);

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

            $output .= '
			<div class="minemap_dashlet" id="' . $id . '">
			
			<div class="infotable_title">Status Grid</div>
			' . get_throbber_html() . '
			
			</div><!--ahost_status_summary_dashlet-->

			<script type="text/javascript">
			$(document).ready(function(){
			
				get_' . $id . '_content();
					
				$("#' . $id . '").everyTime(30*1000, "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});
				
				function get_' . $id . '_content(){
					$("#' . $id . '").each(function(){
						var optsarr = {
							"func": "get_minemap_dashlet_html",
							"args": ' . $jargs . '
							}
						var opts=array2json(optsarr);
						get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
						setTimeout(function() {
							$(".tt-bind-d").tooltip({ placement: "left" });
							$(".tt-bind-dr").tooltip({ placement: "right" });
						} , 500);
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