<?php
//
// Hypermap Dashlet
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: eventlog.php 359 2010-10-31 17:08:47Z egalstad $


function hypermap_dashlet($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{

    $output = "";

    if ($args == null)
        $args = array();

    switch ($mode) {
        case DASHLET_MODE_GETCONFIGHTML:
            $output = '';
            break;
        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $id = "hypermap_" . random_string(6);
            $ajaxargs = $args;

            // Build args for javascript
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
            <div class="hypermap_dashlet" id="' . $id . '">
            ';

            $output .= hypermap_get_output($args);

            $output .= '
            </div>
            ';

            break;
        case DASHLET_MODE_PREVIEW:

            $imgurl = get_component_url_base() . "hypermap/hypermap_preview.png";
            $output = '
            <img src="' . $imgurl . '">
            ';

            break;
        default:
            break;
    }
    return $output;
}


function hypermap_get_output($args)
{

    $p = get_base_url() . "/includes/components/hypermap/";

    $output = "";

    $type = grab_array_var($args, "type");
    $refresh = grab_array_var($args, "refresh", 60);


    switch ($type) {

        default:
            $output .= '
<!-- CSS Files -->
<link type="text/css" href="' . $p . 'css/base.css" rel="stylesheet" />
<link type="text/css" href="' . $p . 'css/hypermap.css" rel="stylesheet" />

<!--[if IE]><script language="javascript" type="text/javascript" src="' . $p . 'js/Extras/excanvas.js"></script><![endif]-->

<script language="javascript" type="text/javascript" src="' . $p . 'js/jit.js"></script>
<script language="javascript" type="text/javascript" src="' . $p . 'js/hypermap.js"></script>

<script language="javascript" type="text/javascript">
$(document).ready(function() {

    // Draw initial hypermap
    hypermap_init();
    
    // Refresh hypermap occassionally
    $("#hypermap-container").everyTime(' . $refresh . '*1000, "timer-hypermap", function(i) {
        hypermap_refresh(i);
    });

});
</script>

<div id="hypermap-container">


<!--
<div id="hypermap-left-container">
<div class="text">
<h4>
' . _('Tree Animation  ') . '
</h4> 

          <b>' . _('Use the mouse wheel</b> to zoom and <b>drag and drop the canvas</b> to pan.') . '
            
</div>
<div id="id-list"></div>
<div style="text-align:center;"><a href="example1.js">' . _('See the Example Code') . '</a></div>
</div>
//-->

<div id="hypermap-center-container">
    <div id="hypermap-infovis"></div>    
</div>

<div id="hypermap-right-container">

<div id="hypermap-inner-details"></div>

<div id="hypermap-help">
<h3>' . _('About This Map') . '</h3>
<p>
' . _('The hypermap displays the current status of network devices (hosts).') . '
</p>
<p>
' . _('Click on a node to center it and obtain detailed status information.') . '
</p>
</div>

</div>

<div id="hypermap-log"></div>
</div>          
            ';
            break;
    }

    return $output;
}