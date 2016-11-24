<?php
// HYPERMAP REPLAY DASHLET
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

function hypermap_replay_dashlet($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
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

            $id = "hypermap_replay_" . random_string(6);

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
			<div class="hypermap_replay_dashlet" id="' . $id . '">
			';

            //$output.='			Hello Hypermap!!!  ARGS='.serialize($args);

            $output .= hypermap_replay_get_output($args);

            $output .= '
			</div>
			';

            break;
        case DASHLET_MODE_PREVIEW:

            $imgurl = get_component_url_base() . "hypermap_replay/hypermap_preview.png";
            $output = '
			<img src="' . $imgurl . '">
			';

            break;
        default:
            break;
    }
    return $output;
}

function hypermap_replay_get_output($args)
{

    $starttime = grab_array_var($args, "starttime");
    $endtime = grab_array_var($args, "endtime");
    $timepoints = grab_array_var($args, "timepoints", 10);
    $refresh = grab_array_var($args, "refresh", 6);

    $p = get_base_url() . "/includes/components/hypermap_replay/";

    $output = "";

    $type = grab_array_var($args, "type");
    switch ($type) {

        default:
            $output .= '
<!-- CSS Files -->
<link type="text/css" href="' . $p . 'css/base.css" rel="stylesheet" />
<link type="text/css" href="' . $p . 'css/hypermap.css" rel="stylesheet" />

<!--[if IE]><script language="javascript" type="text/javascript" src="' . $p . 'js/Extras/excanvas.js"></script><![endif]-->

<!-- JIT Library File -->
<script language="javascript" type="text/javascript" src="' . $p . 'js/jit.js"></script>

<!-- Example File -->
<script language="javascript" type="text/javascript" src="' . $p . 'js/hypermap.js"></script>

<script language="javascript" type="text/javascript">
	$(document).ready(function() {
	
		var hypermap_replay_enabled=1;
	
		$("#hypermap-replay-slider").slider({
			min: ' . $starttime . ',
			max: ' . $endtime . ',
			stop: function(event, ui) {
			
				var value=$("#hypermap-replay-slider").slider("option","value");
				
				// stop refresh
				stop_hypermap_replay_timer();
				
				// refresh the map with the new time
				hypermap_replay_refresh(0,value);
				},
			start: function(event, ui){
				stop_hypermap_replay_timer();
				},
			slide: function(event, ui){
				var value=ui.value;
				var formattedTime=hypermap_refresh_get_timestr(value);
				$("#hypermap-replay-timestamp").html(formattedTime);
				}
			/*
			,
			change: function(event, ui) {
				var value=ui.value;
				var oldval=$("#hypermap-replay-timestamp").innerHTML;
				alert(oldval);
				$("#hypermap-replay-timestamp").innerHTML="<b>"+value+"</b>";
				var x=3;
				}
			*/
			});
			
		$("#hypermap-replay-control").click(function() {
			if(hypermap_replay_enabled==1){
				stop_hypermap_replay_timer();
				}
			else{
				start_hypermap_replay_timer();
				}
			});

		// draw initial hypermap
		hypermap_replay_init(' . $starttime . ');
		
		// start auto-refresh
		start_hypermap_replay_timer();

		// stop auto-refresh
		function stop_hypermap_replay_timer(){
			hypermap_replay_enabled=0;
			// stop timer
			$("#hypermap-replay-container").stopTime("timer-hypermap-replay");
			$("#hypermap-replay-control").html("<img border=\'0\' src=\'/nagiosxi/images/resume.png\' alt=\'Resume\' title=\'Resume\'/>");
			}
		
		// refresh hypermap occassionally
		function start_hypermap_replay_timer(){
		
			hypermap_replay_enabled=1;
		
			$("#hypermap-replay-container").everyTime(' . $refresh . '*1000, "timer-hypermap-replay", function(i) {
				var maxtimepoints=' . $timepoints . ';
				var thispoint=(i % maxtimepoints);
				//if(i == maxtimepoints)
					//thispoint=maxtimepoints;
				var pointtime=(' . $endtime . '-' . $starttime . ')/' . $timepoints . ';
				hypermap_replay_refresh(i,parseInt(' . $starttime . '+(thispoint * pointtime)));
				
				});
			$("#hypermap-replay-control").html("<img border=\'0\' src=\'/nagiosxi/images/pause.png\' alt=\'Pause\' title=\'Pause\'/>");
			}
			
		
		});
</script>

<div id="hypermap-replay-slider"></div>
<div id="hypermap-replay-control"><img border=\'0\' src=\'/nagiosxi/images/pause.png\' alt=\'Pause\' title=\'Pause\'/></div>
<div id="hypermap-replay-timestamp"></div>

<div id="hypermap-replay-container">


<div id="hypermap-replay-center-container">
    <div id="hypermap-replay-infovis"></div>    
</div>

<div id="hypermap-replay-right-container">

<div id="hypermap-replay-inner-details"></div>

<div id="hypermap-replay-help">
<h3>' . _('About This Report') . '</h3>
<p>
' . _('The network replay displays the historical status of network devices (hosts) over time, which is useful for understanding how outages affect the overall health of the network.') . '
</p>
<p>
' . _('Use the slider bar to move through time. The automatic refresh may be stopped or resumed using the controls above.  Click on a node to center it and obtain status details.') . '
</p>
</div>

</div>

<div id="hypermap-replay-log"></div>
</div>			


			';
            break;
    }

    return $output;
}