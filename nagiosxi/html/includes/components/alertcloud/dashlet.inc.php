<?php
//
// Alert Cloud
// Copyright (c) 2010-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../../dashlets/dashlethelper.inc.php');

function alertcloud_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";
    $imgbase = get_base_url() . "includes/components/alertcloud/images/";

    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            $output = '
            <div style="padding: 0 5px 20px 5px;">
				<div class="row">
					<div class="col-sm-4">
						<LABEL FOR="width">' . _('Cloud Width') . '</LABEL><BR>
						<INPUT TYPE="text" NAME="width" class="form-control" style="width: 150px;" VALUE="350">
					</div>
					<div class="col-sm-4">
						<LABEL FOR="height">' . _('Cloud Height') . '</LABEL><BR>
						<INPUT TYPE="text" NAME="height" class="form-control" style="width: 150px;" VALUE="350">
					</div>
					<div class="col-sm-4">
						<LABEL FOR="height">' . _('Touch Speed') . '</LABEL><BR>
						<INPUT TYPE="text" NAME="speed" class="form-control" style="width: 150px;" VALUE="50">
					</div>
				</div>
			</div>';
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";

            $id = "alertcloud_" . random_string(6);

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
			<div class="alertcloud_dashlet" id="' . $id . '">
			
			<div class="infotable_title">'._('Alert Cloud').'</div>
			' . get_throbber_html() . '
			</div>

			<script type="text/javascript">

			$(document).ready(function() {

				get_' . $id . '_content();
					
				$("#' . $id . '").everyTime(90*1000, "timer-' . $id . '", function(i) {
					get_' . $id . '_content();
				});

				
				function get_' . $id . '_content() {
					var args = ' . $jargs . ';

					// Get new height and width
					var h = $("#' . $id . '").closest(".dashboarddashletcontainer").height();
					var w = $("#' . $id . '").closest(".dashboarddashletcontainer").width();
					if (w) {
						args.width = w-40;
					}
					if (h) {
						args.height = h-80;
					}

					$("#' . $id . '").each(function() {
						var optsarr = {
							"func": "get_alertcloud_dashlet_html",
							"args": args
						}
						var opts = array2json(optsarr);
						get_ajax_data_innerHTML("getxicoreajax", opts, true, this);
					});
				}
			});
			
			$(document).ready(function() {
				$("#' . $id . '").closest(".ui-resizable").on("resizestop", function(e, ui) {
					var height = ui.size.height - 36;
					var width = ui.size.width;
					
					$("#' . $id . ' .well").css("width", width).css("height", height);

					$("#alertCloudObject").attr("height", height-40).attr("width", width-40);
				});
			});

			</script>';
            break;

        case DASHLET_MODE_PREVIEW:
            $output = "<p><img src='" . $imgbase . "preview.png'></p>";
            break;
    }

    return $output;
}
