<?php
// HYPERMAP DATA PRODUCER (FOR AJAX CALLS)
//
// Copyright (c) 2008-2009 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

function hypermap_replay_get_data_r($node, $arr, $level)
{

    $root_node_id = "__root__";

    $output = "";

    $nodeinfo = "";
    $nodedata = "";
    $statusurl = "";

    if ($node == $root_node_id) {
        $nodeinfo .= "<h4>Nagios Process</h4>";
    } else {

        //print_r($arr);

        // CURRENTLY BUSTED - Anything related to the historical state, including
        // $arr["current_state"]
        // $arr["status_text"]
        // $arr["status_time"]

        $state_text = "";
        /**/
        switch ($arr["current_state"]) {
            // up
            case 0:
                if ($arr["has_been_checked"] == true) {
                    $state_text = "Up";

                    $nodedata .= '
						"$color": "#ADEC52",
						"linewidth" : 1,
						"$dim": 5,
						';
                    //					"linecolor" : "#79FF01",
                } else {
                    $backendargs["host_name"] = $arr["host_name"];
                    $xml = get_xml_host_status($backendargs);
                    if (intval($xml->hoststatus->current_state) == 0){
                        $state_text = "Up";
                        $nodedata .= '
						"$color": "#ADEC52",
						"linewidth" : 1,
						"$dim": 5,
						';
                    }
                    else if (intval($xml->hoststatus->current_state) == 1){
                        $state_text = "Down";
                        $nodedata .= '
                        "$color": "#ff0000",
                        "linecolor" : "#FE6262",
                        "linewidth" : 3,
                        "$dim": 7,
                        ';
                    }
                    else if (intval($xml->hoststatus->current_state) == 2){
                        $state_text = "Unreachable";
                        $nodedata .= '
                        "$color": "#FF6B43",
                        "linecolor" : "#FF6B43",
                        "linewidth" : 3,
                        "$dim": 6,
                        ';
                    }
                    else {
                        $state_text = "Pending";
                        $nodedata .= '
                            "linewidth" : 1,
                            "$dim": 5,
                            ';
                    }
                }
                break;
            // down
            case 1:
                $state_text = "Down";
                $nodedata .= '
					"$color": "#ff0000",
					"linecolor" : "#FE6262",
					"linewidth" : 3,
					"$dim": 7,
					';
                break;
            // unreachable
            case 2:
                $state_text = "Unreachable";
                $nodedata .= '
					"$color": "#FF6B43",
					"linecolor" : "#FF6B43",
					"linewidth" : 3,
					"$dim": 6,
					';
                break;
            default:
                break;
        }
        /**/

        $nodeinfo = "<h4>" . $arr["host_name"] . "</h4>";
        $iconhtml = get_object_icon($arr["host_name"]);
        if ($iconhtml != "")
            $nodeinfo .= str_replace("\"", "\\\"", $iconhtml) . "<br>";

        $time_text = get_datetime_string($arr["status_time"]);
        /*
        $time_text="Unknown time";
        $state_text="Unknown state";
        $arr["status_text"]="Unknown status text";
        */

        $nodeinfo .= "<b>Time:</b> " . $time_text . "<br>";
        $nodeinfo .= "<b>State:</b> " . $state_text . "<br>";
        $nodeinfo .= "<b>Info:</b> " . str_replace("\"", "\\\"", $arr["status_text"]) . "<br>";
        $nodeinfo .= "";

        $statusurl = "<a href='" . get_base_url() . "/includes/components/xicore/status.php?show=hostdetail&host=" . $arr["host_name"] . "' target='_blank'><b>View Host Details</b></a>";
    }

    $output .= '{
		id: "' . $node . '",
		name: "' . $arr["host_name"] . '",
		level: "' . $level . '",
		data: {';
    $output .= $nodedata;
    $output .= '
			info: "' . $nodeinfo . '",
			statusurl: "' . str_replace("\"", "\\\"", $statusurl) . '",
		},
		children: [';

    foreach ($arr["children_arr"] as $cid => $carr) {
        $cdata = hypermap_replay_get_data_r($cid, $carr, $level + 1);
        if ($cdata != "") {
            $output .= '
			';
            $output .= $cdata;
            $output .= ',
			';
        }
    }

    $output .= ']
	}';

    return $output;
}


function hypermap_replay_get_data()
{

    $t = intval(grab_request_var("t"));

    $output = "";

    header("Content-type: text/plain");

    $map = get_host_parent_child_array_map($t);

    $output = hypermap_replay_get_data_r("__root__", $map, 0);

    echo $output;
}