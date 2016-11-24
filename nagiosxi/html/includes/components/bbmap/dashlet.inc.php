<?php
//
// BBMap Dashlet
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../../dashlets/dashlethelper.inc.php');


function bbmap_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";
    $imgbase = get_base_url() . "includes/components/bbmap/images/";


    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";

            $id = "bbmap_" . random_string(6);

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
            $output .= "	<link rel='stylesheet' type='text/css' href='" . $css_url . "includes/components/bbmap/bbmap.css' />
";

            $output .= '
			
			<div class="bbmap_dashlet" id="' . $id . '">
			
			<div class="infotable_title">' . _('Status Grid') . '</div>
			' . get_throbber_html() . '
			
			</div><!--ahost_status_summary_dashlet-->

			<script type="text/javascript">

            function get_' . $id . '_resize(data) {
                setTimeout(function() {
					thWidth = 1
                        $("#' . $id . '").find("th").each(function() {
                            if ($(this).outerWidth() > thWidth)
                                thWidth = $(this).outerWidth();
                        });
                        thWidth = thWidth + 10
                        $("#' . $id . '").find("div.inner").css("margin-left",thWidth+"px");
                        $("#' . $id . '").find("div.outer div.inner table.infotable th").css("width",thWidth+"px");
                } , 500);

                $(".tt-bind-d").tooltip({ placement: "left" });
                $(".tt-bind-dr").tooltip({ placement: "right" });
            }

			$(document).ready(function(){
                
				get_' . $id . '_content();
				$("#' . $id . '").everyTime(30*1000, "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});
				
				function get_' . $id . '_content(){
					$("#' . $id . '").each(function(){
						var optsarr = {
							"func": "get_bbmap_dashlet_html",
							"args": ' . $jargs . '
							}
						var opts=array2json(optsarr);
						get_ajax_data_innerHTML_with_callback("getxicoreajax",opts,true,this, "get_' . $id . '_resize");
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