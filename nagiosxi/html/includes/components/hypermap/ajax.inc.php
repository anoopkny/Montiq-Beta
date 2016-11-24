<?php
//
// Hypermap Data API (For AJAX calls)
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//  
// $Id: eventlog.php 359 2010-10-31 17:08:47Z egalstad $


function hypermap_get_data_r($node, $arr, $level)
{
    $root_node_id = "__root__";
    $output = "";
    $nodeinfo = "";
    $nodedata = "";
    $statusurl = "";

    if ($node == $root_node_id) {
        $nodeinfo .= "<h4>" . _("Nagios Process") . "</h4>";
    } else {

        $state_text = "";
        switch ($arr["current_state"]) {
            // up
            case 0:
                if ($arr["has_been_checked"] == true) {
                    $state_text = _("Up");

                    $nodedata .= '
                        "$color": "#ADEC52",
                        "linewidth" : 1,
                        "$dim": 5,
                        ';
                } else {
                    $state_text = _("Pending");
                    $nodedata .= '
                        "linewidth" : 1,
                        "$dim": 5,
                        ';
                }
                break;
            // down
            case 1:
                $state_text = _("Down");
                $nodedata .= '
                    "$color": "#ff0000",
                    "linecolor" : "#FE6262",
                    "linewidth" : 3,
                    "$dim": 7,
                    ';
                break;
            // unreachable
            case 2:
                $state_text = _("Unreachable");
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

        $nodeinfo = "<h4 class='hypermap_heading'>" . $arr["host_name"] . "</h4>";
        $iconhtml = get_object_icon($arr["host_name"]);
        if ($iconhtml != "")
            $nodeinfo .= str_replace("\"", "\\\"", $iconhtml) . "<br>";
        $nodeinfo .= "<b>"._('State:')."</b> " . $state_text . "<br>";
        $nodeinfo .= "<b>"._('Info:')."</b> " . str_replace("\"", "\\\"", $arr["status_text"]) . "<br>";
        $nodeinfo .= "<br>";

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
        $cdata = hypermap_get_data_r($cid, $carr, $level + 1);
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


function hypermap_get_data()
{
    $output = "";

    header("Content-type: text/plain");

    $map = get_host_parent_child_array_map();
    $output = hypermap_get_data_r("__root__", $map, 0);

    echo $output;
}