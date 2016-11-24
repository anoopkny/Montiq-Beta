<?php

include_once(dirname(__FILE__) . '/../../dashlets/dashlethelper.inc.php');


function capacityplanning_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";
    $imgbase = get_base_url() . "includes/components/capacityplanning/images/";

    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            break;

        case DASHLET_MODE_OUTBOARD:

            $output = "";

            $id = "capacityplanning_" . uniqid();


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

            $output .= '
            <div class="capacityplanning_dashlet" id="' . $id . '">

            <div class="infotable_title">' . _("Capacity Planning") . '</div>
            ' . get_throbber_html() . '

            </div><!--ahost_status_summary_dashlet-->

            <script type="text/javascript">
            function test() {

            }

            $(document).ready(function(){

                get_' . $id . '_content();
                
                // Refresh every 6 hours
                $("#' . $id . '").everyTime(6*3600*1000, "timer-' . $id . '", function(i) {
                    get_' . $id . '_content();
                });

                function add_ge_tooltips() {
                    $("#' . $id . ' .ge-tt-bind").tooltip();
                }

                function get_' . $id . '_content(height, width){
                    var optsarr = {
                        "func": "get_capacityplanning_dashlet_html",
                        "args": ' . $jargs . '
                    }
                        
                    var opts=array2json(optsarr);
                    get_ajax_data_innerHTML_with_callback("getxicoreajax", opts, true, $("#' . $id . '"), "bind_tt");
                    
                    // Stop clicking in graph from moving dashlet
                    $("#' . $id . '").closest(".ui-draggable").draggable({ cancel: "#' . $id . '" });
                }
            });
            </script>
            ';

            break;

        case DASHLET_MODE_INBOARD:

            $output = "";

            $id = "capacityplanning_" . uniqid();


            $ajaxargs = $args;

            $ajaxargs['hide_data'] = true;

            $n = 0;
            $jargs = "{";
            foreach ($ajaxargs as $var => $val) {
                if ($n > 0)
                    $jargs .= ", ";
                $jargs .= "\"$var\" : \"$val\"";
                $n++;
            }

            $jargs .= "}";

            // Enterprise only feature, check to make sure enterprise is enabled
            echo enterprise_message();
            if (enterprise_features_enabled()) {

                $output .= '
                <div class="capacityplanning_dashlet" id="' . $id . '">

                <div class="infotable_title">' . _("Capacity Planning") . '</div>
                ' . get_throbber_html() . '

                </div><!--ahost_status_summary_dashlet-->

                <link href="' . get_base_url() . '/includes/components/capacityplanning/includes/capacityplanning.css" rel="stylesheet" type="text/css" />
                <script type="text/javascript" src="' . get_base_url() . '/includes/components/capacityplanning/includes/capacityplanning.js"></script>
                <script type="text/javascript" src="' . get_base_url() . '/includes/components/capacityplanning/includes/capacityreport.js.php"></script>

                <script type="text/javascript">
                $(document).ready(function(){
                    
                    get_' . $id . '_content();

                    // Refresh every 6 hours
                    $("#' . $id . '").everyTime(6*3600*1000, "timer-' . $id . '", function(i) {
                        get_' . $id . '_content();
                    });
                    
                    // Re-build the content when we resize
                    $("#' . $id . '").closest(".ui-resizable").on("resizestop", function(e, ui) {
                        var height = ui.size.height;
                        var width = ui.size.width;
                        get_' . $id . '_content(height, width);
                    });
                });

                function get_' . $id . '_content(height, width){
                    var optsarr = {
                        "func": "get_capacityplanning_dashlet_html",
                        "args": ' . $jargs . '
                    }
                            
                    if (height == undefined) { var height = $("#' . $id . '").parent().height() - 17; }
                    if (width == undefined) { var width = $("#' . $id . '").parent().width(); }
                            
                    optsarr["args"]["height"] = height - 24;
                    optsarr["args"]["width"] = width - 90;
                            
                    var opts=array2json(optsarr);

                    get_ajax_data_innerHTML("getxicoreajax", opts, true, $("#' . $id . '"));
                            
                    // Stop clicking in graph from moving dashlet
                    $("#' . $id . '").closest(".ui-draggable").draggable({cancel: "#' . $id . '"});
                }
                </script>
                ';
            }

            break;

        case DASHLET_MODE_PREVIEW:
            $output = "<p><img src='" . $imgbase . "preview.png'></p>";
            break;
    }

    return $output;
}
