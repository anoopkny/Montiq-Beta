<?php
//
// XI Core Ajax Helper Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// COMMENTS AJAX FUNCTIONS
////////////////////////////////////////////////////////////////////////        

/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_comments_html($args = null)
{

    if (!is_array($args)) {
        $args = array(
            "cmd" => "getcomments"
        );
    } else {
        $args["cmd"] = "getcomments";
    }

    $xml = get_backend_xml_data($args);

    $output = '';

    $output .= '<div class="infotable_title">' . _("Acknowledgements and Comments") . '</div>';

    if ($xml == null || intval($xml->recordcount) == 0) {
        $output .= _('No comments or acknowledgements.');
    } else {

        $output .= '
        <table class="table table-condensed table-striped table-bordered" style="margin-bottom: 5px;">
            <thead>
                <tr>
                    <th>' . _('Host') . '</th>
                    <th>' . _('Service') . '</th>
                    <th>' . _('Comment') . '</th>
                    <th class="center" style="width: 60px;">' . _('Action') . '</th>
                </tr>
            </thead>
            <tbody>';

        $x = 0;
        foreach ($xml->comment as $c) {

            if (($x % 2) == 0) {
                $rowclass = "even";
            } else {
                $rowclass = "odd";
            }

            $objecttype = intval($c->objecttype_id);

            $hostname = strval($c->host_name);
            $servicename = strval($c->service_description);

            switch (intval($c->entry_type)) {
                case COMMENTTYPE_ACKNOWLEDGEMENT:
                    $typeimg = theme_image("ack.png");
                    break;
                default:
                    $typeimg = theme_image("comment.png");
                    break;
            }
            $type = "<img src='" . $typeimg . "'>";
            $timestr = get_datetime_string_from_datetime($c->comment_time);
            $author = strval($c->author_name);
            $comment = (get_option('allow_status_html') == true) ? html_entity_decode($c->comment_data) : strval($c->comment_data);

            $hoststr = "<a href='" . get_host_status_detail_link($hostname) . "'>" . $hostname . "</a>";
            $servicestr = "<a href='" . get_service_status_detail_link($hostname, $servicename) . "'>" . $servicename . "</a>";

            $output .= '<tr class="' . $rowclass . '">
                            <td valign="top" nowrap>' . $hoststr . '</td>
                            <td valign="top" nowrap>' . $servicestr . '</td>
                            <td>
                                <div>' . $type . ' By <b>' . $author . '</b> at ' . $timestr . '</div>
                                <div>' . $comment . '</div>
                            </td>';

            // Is user authorized for command?
            if ($objecttype == OBJECTTYPE_HOST) {
                $auth_command = is_authorized_for_host_command(0, $hostname);
            } else {
                $auth_command = is_authorized_for_service_command(0, $hostname, $servicename);
            }

            if ($auth_command) {
                $cmd["command_args"]["cmd"] = ($objecttype == OBJECTTYPE_HOST) ? NAGIOSCORE_CMD_DEL_HOST_COMMENT : NAGIOSCORE_CMD_DEL_SVC_COMMENT;
                $cmd["command_args"]["comment_id"] = intval($c->internal_id);
                $action = "<a href='#' " . get_nagioscore_command_ajax_code($cmd) . "><img src='" . theme_image("cross.png") . "' alt='" . _('Delete') . "' title='" . _('Delete') . "'></a>";
                $output .= '<td class="center">' . $action . '</td>';
            } else
                $output .= '<td></td>';
            $output .= '</tr>';

            $x++;
        }

        $output .= '
        </tbody>
        </table>
        ';
    }


    $output .= '
    <div class="ajax_date">' . _('Last Updated') . ': ' . get_datetime_string(time()) . '</div>
    ';


    return $output;
}
