<?php
//
// Nagios XI API Documentation
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');
require_once(dirname(__FILE__) . '/html-helpers.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);

route_request();

function route_request()
{
    $page = grab_request_var("page", "");

    switch ($page) {
        default:
            show_main_api_page();
            break;
    }
}

function show_main_api_page()
{
    $backend_url = get_product_portal_backend_url();
    $apikey = get_apikey();

    do_page_start(array("page_title" => _('Backend API - Objects Reference')), true);
?>

    <!-- Keep the help section part of the page -->
    <script>
    $(document).ready(function() {
        resize_nav();
        $(window).resize(function() {
            resize_nav();
        });
    });

    function resize_nav() {
        var width = $('.nav-box').width();
        $('.help-right-nav').css('width', width);
    }
    </script>

    <div class="container-fluid" style="padding: 0 5px;">
        <div class="row">
            <div class="col-sm-8 col-md-9 col-lg-9">

                <h1><?php echo _('Backend API - Objects Reference'); ?></h1>
                <p><?php echo _('This is a read-only backend for getting the host, services, and other objects.'); ?></p>

                <div class="help-section obj-reference">
                    <a name="building-queries"></a>
                    <h4><?php echo _('Building Limited Queries'); ?></h4>
                    <p><?php echo _('Sometimes you will need to only see a specific subset of data. Since these queries are generally akin to MySQL queries on databases, there are some modifiers that you can add in to get the data that you want. This section will show some examples of these modifiers and give a small reference table of modifiers that are available for these API objects.'); ?></p>
                    <p><?php echo _('Values in <em>italics</em> are considered optional and are not necessary to use the paramater.'); ?> <b class="ref-tt"><?php echo _('Bold and underlined with dots'); ?></b> <?php echo _('means there is a help tooltip or popup describing the functionality of the value. Anything inside parenthesis ( ) is a default value. Anything inside brackets [ ] is an optional additional argument.'); ?></p>
                    <table class="table table-condensed table-bordered table-no-margin">
                        <thead>
                            <tr>
                                <td><?php echo _('Parameter'); ?></td>
                                <td><?php echo _('Values'); ?></td>
                                <td><?php echo _('Examples'); ?></td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>pretty</td>
                                <td><b>1</b></td>
                                <td><?php echo _('If the value is 1, the API displays readable JSON. This is helpful when develiping and should not be used in a production API call.'); ?></td>
                            </tr>
                            <tr>
                                <td>starttime</td>
                                <td>&lt;timestamp&gt; (Default: -24 hours)</td>
                                <td><code>objects/statehistory?starttime=<?php echo strtotime('-1 week'); ?></code> <a href="<?php echo get_base_url(); ?>api/v1/objects/statehistory?starttime=<?php echo strtotime('-1 week'); ?>&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a> - <?php echo _('Displays the last week of data until now.'); ?></td>
                            </tr>
                            <tr>
                                <td>endttime</td>
                                <td>&lt;timestamp&gt; (Default: now)</td>
                                <td><code>objects/statehistory?starttime=<?php echo strtotime('-2 weeks'); ?>&amp;endtime=<?php echo strtotime('-1 week'); ?></code> <a href="<?php echo get_base_url(); ?>api/v1/objects/statehistory?starttime=<?php echo strtotime('-2 weeks'); ?>&amp;endtime=<?php echo strtotime('-1 week'); ?>&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a> - <?php echo _('Displays 1 week of data starting 2 weeks ago. Should be used with starttime.'); ?></td>
                            </tr>
                            <tr>
                                <td>records</td>
                                <td>&lt;amount&gt;<em>:&lt;starting at&gt;</em></td>
                                <td>
                                    <div><code>objects/hoststatus?records=1</code> <a href="<?php echo get_base_url(); ?>api/v1/objects/hoststatus?records=1&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a> - <?php echo _('Displays only the first record.'); ?></div>
                                    <div><code>objects/hoststatus?records=10:20</code> <a href="<?php echo get_base_url(); ?>api/v1/objects/hoststatus?records=10:20&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a> - <?php echo _('Displays the the next 10 records after the 20th record.'); ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td>orderby</td>
                                <td>&lt;column&gt;<em>:&lt;<b class="ref-tt tt-bind" title="<?php echo _('Ascending'); ?> [ 0 to 10 ] [ A to Z ]">a</b> or <b class="ref-tt tt-bind" title="<?php echo _('Descending'); ?> [ 10 to 0 ] [ Z to A ]">d</b>&gt;</em></td>
                                <td>
                                    <div><code>objects/hoststatus?orderby=name:a</code> <a href="<?php echo get_base_url(); ?>api/v1/objects/hoststatus?orderby=name:a&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a> - <?php echo _('Displays the items ordered by the name field and ascending values.'); ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td>&lt;column&gt;</td>
                                <td><em class="ref-pop"><b class="ref-tt pop" data-content="<div style='font-style: normal; font-size: 12px;'><table class='table table-condensed table-bordered'><thead><tr><td><?php echo _('Type'); ?></td><td><?php echo _('SQL Equivalent'); ?></td><td><?php echo _('Description'); ?></td></tr></thead><tbody><tr><td></td><td>= value</td><td><?php echo _('Default equals match'); ?></td></tr><tr><td><b>ne:</b></td><td>!= value</td><td><?php echo _('Not equals match'); ?></td></tr><tr><td><b>lt:</b></td><td>&lt; value</td><td><?php echo _('Less than match'); ?></td></tr><tr><td><b>lte:</b></td><td>&lt;= value</td><td><?php echo _('Less than or equal match'); ?></td></tr><tr><td><b>gt:</b></td><td>&gt; value</td><td><?php echo _('Greater than match'); ?></td></tr><tr><td><b>gte:</b></td><td>&gt;= value</td><td><?php echo _('Greater than or equal match'); ?></td></tr><tr><td><b>lks:</b></td><td>LIKE value%</td><td><?php echo _('Beginning of string match'); ?></td></tr><tr><td><b>nlks:</b></td><td>NOT LIKE value%</td><td><?php echo _('Beginning of string non-match'); ?></td></tr><tr><td><b>lke:</b></td><td>LIKE %value</td><td><?php echo _('End of string match'); ?></td></tr><tr><td><b>nlke:</b></td><td>NOT LIKE value%</td><td><?php echo _('End of string non-match'); ?></td></tr><tr><td><b>lk:</b> or <b>lkm:</b></td><td><?php echo _('LIKE %value%'); ?></td><td><?php echo _('General mid string match'); ?></td></tr><tr><td><b>nlk:</b> or <b>nlkm:</b></td><td>NOT LIKE %value%</td><td><?php echo _('General mid string non-match'); ?></td></tr><tr><td><b>in:</b></td><td>IN (values)</td><td><?php echo _('In comma-separated list'); ?></td></tr><tr><td><b>nin:</b></td><td>NOT IN (values)</td><td><?php echo _('Not in comma-separated list'); ?></td></tr></tbody></table></div>">&lt;type&gt;:</b></em>&lt;value&gt;</td>
                                <td>
                                    <div><code>objects/hoststatus?name=lk:local</code> <a href="<?php echo get_base_url(); ?>api/v1/objects/hoststatus?name=lk:local&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a>  - <?php echo _("Displays any matching name with 'local' anywhere in the string."); ?></div>
                                    <div><code>objects/hoststatus?name=in:localhost,nagios,testhost</code> <a href="<?php echo get_base_url(); ?>api/v1/objects/hoststatus?name=in:localhost,nagios,testhost&amp;pretty=1&amp;apikey=<?php echo $apikey; ?>" target="_blank" rel="noreferrer" class="tt-bind" style="vertical-align: middle;" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share"></i></a>  - <?php echo _("Displays any matching name with the comma-separated list."); ?></div>
                                    <div><b><?php echo _('Note'); ?>:</b> <?php echo _('You can use multiple different column names in a row such as:'); ?> <code>host_name=localhost&amp;current_state=1</code></div>
                                </td>
                            </tr>
                            <tr>
                                <td>outputtype</td>
                                <td><b>json</b> or <b>xml</b> (Default: json)</td>
                                <td>
                                    <div><?php echo _('<b>Optional Parameter</b>: <em>Only available in objects API</em>. Use this variable to get XML output instead of JSON. By default output is JSON when not using this parameter.'); ?></div>
                                    <div><?php echo _('The pretty paramater cannot be used with'); ?> <code>outputtype=xml</code></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-hoststatus"></a>
                    <h4>GET objects/hoststatus</h4>
                    <p><?php echo _('This command returns the current host status.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/hoststatus?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/hoststatus?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "hoststatuslist": {
        "recordcount": "12",
        "hoststatus": [
            {
                "@attributes": {
                    "id": "401"
                },
                "instance_id": "1",
                "host_id": "202",
                "name": "tset",
                "display_name": "tset",
                "address": "127.0.53.53",
                "alias": "tset",
                "status_update_time": "2015-09-24 02:05:51",
                "status_text": "OK - 127.0.53.53: rta 0.021ms, lost 0%",
                "status_text_long": "",
                "current_state": "0",
                "icon_image": "server.png",
                "icon_image_alt": "",
                "performance_data": "rta=0.021ms;3000.000;5000.000;0; pl=0%;80;100;; rtmax=0.060ms;;;; rtmin=0.011ms;;;;",
                "should_be_scheduled": "1",
                "check_type": "0",
                "last_state_change": "2015-09-22 12:11:41",
                "last_hard_state_change": "2015-09-22 12:11:41",
                "last_hard_state": "0",
                "last_time_up": "2015-09-24 02:05:51",
                "last_time_down": "1969-12-31 18:00:00",
                "last_time_unreachable": "1969-12-31 18:00:00",
                "last_notification": "1969-12-31 18:00:00",
                "next_notification": "1969-12-31 18:00:00",
                "no_more_notifications": "0",
                "acknowledgement_type": "0",
                "current_notification_number": "0",
                "event_handler_enabled": "1",
                "process_performance_data": "1",
                "obsess_over_host": "1",
                "modified_host_attributes": "0",
                "event_handler": "",
                "check_command": "check_xi_host_ping!3000.0!80%!5000.0!100%",
                "normal_check_interval": "40",
                "retry_check_interval": "5",
                "check_timeperiod_id": "128",
                "has_been_checked": "1",
                "current_check_attempt": "1",
                "max_check_attempts": "4",
                "last_check": "2015-09-24 02:05:51",
                "next_check": "2015-09-24 02:45:51",
                "state_type": "1",
                "notifications_enabled": "0",
                "problem_acknowledged": "0",
                "passive_checks_enabled": "1",
                "active_checks_enabled": "1",
                "flap_detection_enabled": "1",
                "is_flapping": "0",
                "percent_state_change": "0",
                "latency": "0",
                "execution_time": "0.00226",
                "scheduled_downtime_depth": "0"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-servicestatus"></a>
                    <h4>GET objects/servicestatus</h4>
                    <p><?php echo _('This command returns the current service status.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/servicestatus?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/servicestatus?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "servicestatuslist": {
        "recordcount": "59",
        "servicestatus": [
            {
                "@attributes": {
                    "id": "3081"
                },
                "instance_id": "1",
                "service_id": "195",
                "host_id": "187",
                "host_name": "test123",
                "name": "Domain Expiration",
                "host_display_name": "",
                "host_address": "127.0.0.1",
                "display_name": "Domain Expiration",
                "status_update_time": "2015-09-24 02:33:54",
                "status_text": "CRITICAL - Domain &apos;test123&apos; will expire in -16699 days (The expiration date displayed in this record is the date the).",
                "status_text_long": "",
                "current_state": "2",
                "performance_data": "",
                "should_be_scheduled": "1",
                "check_type": "0",
                "last_state_change": "2015-09-22 05:44:33",
                "last_hard_state_change": "2015-09-22 05:48:27",
                "last_hard_state": "2",
                "last_time_ok": "2015-09-19 19:27:11",
                "last_time_warning": "1969-12-31 18:00:00",
                "last_time_critical": "2015-09-21 02:24:18",
                "last_time_unknown": "1969-12-31 18:00:00",
                "last_notification": "2015-09-23 05:48:25",
                "next_notification": "2015-09-23 06:48:25",
                "no_more_notifications": "0",
                "acknowledgement_type": "0",
                "current_notification_number": "2",
                "process_performance_data": "1",
                "obsess_over_service": "1",
                "event_handler_enabled": "1",
                "modified_service_attributes": "0",
                "event_handler": "",
                "check_command": "check_xi_domain_v2!test123!-w 4!-c 6",
                "normal_check_interval": "1440",
                "retry_check_interval": "1",
                "check_timeperiod_id": "128",
                "icon_image": "",
                "icon_image_alt": "",
                "has_been_checked": "1",
                "current_check_attempt": "5",
                "max_check_attempts": "5",
                "last_check": "2015-09-23 05:48:25",
                "next_check": "2015-09-24 05:48:25",
                "state_type": "1",
                "notifications_enabled": "1",
                "problem_acknowledged": "0",
                "flap_detection_enabled": "1",
                "is_flapping": "0",
                "percent_state_change": "6.12",
                "latency": "0",
                "execution_time": "0.51",
                "scheduled_downtime_depth": "0",
                "passive_checks_enabled": "1",
                "active_checks_enabled": "1"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-logentries"></a>
                    <h4>GET objects/logentries</h4>
                    <p><?php echo _('This command returns a list of log entries.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/logentries?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/logentries?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "logentries": {
        "recordcount": "1433",
        "logentry": [
            {
                "instance_id": "1",
                "entry_time": "2015-09-24 03:30:25",
                "logentry_type": "2",
                "logentry_data": "Warning: Return code of 255 for check of service &apos;Swap Usage&apos; on host &apos;1921515&apos; was out of bounds."
            },
            {
                "instance_id": "1",
                "entry_time": "2015-09-24 03:30:10",
                "logentry_type": "2",
                "logentry_data": "Warning: Return code of 255 for check of service &apos;\/ Disk Usage&apos; on host &apos;1921515&apos; was out of bounds."
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-statehistory"></a>
                    <h4>GET objects/statehistory</h4>
                    <p><?php echo _('This command returns a list of state changes.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/statehistory?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/statehistory?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "statehistory": {
        "recordcount": "2",
        "stateentry": [
            {
                "instance_id": "1",
                "state_time": "2015-09-24 03:39:52",
                "object_id": "175",
                "objecttype_id": "2",
                "host_name": "127.0.0.1",
                "service_description": "Load",
                "state_change": "1",
                "state": "2",
                "state_type": "0",
                "current_check_attempt": "1",
                "max_check_attempts": "5",
                "last_state": "0",
                "last_hard_state": "0",
                "output": "awfawfawf"
            },
            {
                "instance_id": "1",
                "state_time": "2015-09-24 03:39:48",
                "object_id": "175",
                "objecttype_id": "2",
                "host_name": "127.0.0.1",
                "service_description": "Load",
                "state_change": "1",
                "state": "0",
                "state_type": "1",
                "current_check_attempt": "5",
                "max_check_attempts": "5",
                "last_state": "3",
                "last_hard_state": "3",
                "output": "awfawfawf"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-comment"></a>
                    <h4>GET objects/comment</h4>
                    <p><?php echo _('This command returns a list of comments.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/comment?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/comment?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "comments": {
        "recordcount": "1",
        "comment": {
            "@attributes": {
                "id": "229"
            },
            "instance_id": "1",
            "comment_id": "229",
            "comment_type": "1",
            "object_id": "172",
            "objecttype_id": "1",
            "host_name": "127.0.0.1",
            "service_description": "",
            "entry_type": "1",
            "entry_time": "2015-09-24 02:33:54",
            "entry_time_usec": "195080",
            "comment_time": "2015-09-07 19:34:04",
            "internal_id": "12",
            "author_name": "Nagios Administrator",
            "comment_data": "test123",
            "is_persistent": "1",
            "comment_source": "1",
            "expires": "0",
            "expiration_time": "1969-12-31 18:00:00"
        }
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-downtime"></a>
                    <h4>GET objects/downtime</h4>
                    <p><?php echo _('This command returns a list of scheduled downtimes.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/downtime?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/downtime?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "scheduleddowntimelist": {
        "recordcount": "2",
        "scheduleddowntime": [
            {
                "@attributes": {
                    "id": "76"
                },
                "instance_id": "1",
                "downtime_type": "2",
                "object_id": "172",
                "objecttype_id": "1",
                "host_name": "127.0.0.1",
                "service_description": "",
                "entry_time": "2015-09-24 04:21:08",
                "author_name": "Nagios Administrator",
                "comment_data": "testapi",
                "internal_id": "66",
                "triggered_by": "0",
                "fixed": "1",
                "duration": "7200",
                "scheduled_start_time": "2015-09-26 04:09:59",
                "scheduled_end_time": "2015-09-26 06:09:59",
                "was_started": "0",
                "actual_start_time": "0000-00-00 00:00:00",
                "actual_start_time_usec": "0"
            },
            {
                "@attributes": {
                    "id": "75"
                },
                "instance_id": "1",
                "downtime_type": "2",
                "object_id": "172",
                "objecttype_id": "1",
                "host_name": "127.0.0.1",
                "service_description": "",
                "entry_time": "2015-09-24 04:20:58",
                "author_name": "Nagios Administrator",
                "comment_data": "test123",
                "internal_id": "65",
                "triggered_by": "0",
                "fixed": "1",
                "duration": "7200",
                "scheduled_start_time": "2015-09-24 04:09:53",
                "scheduled_end_time": "2015-09-24 06:09:53",
                "was_started": "1",
                "actual_start_time": "2015-09-24 04:20:58",
                "actual_start_time_usec": "6588"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-contact"></a>
                    <h4>GET objects/contact</h4>
                    <p><?php echo _('This command returns a list of contacts.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/contact?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/contact?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "contactlist": {
        "recordcount": "4",
        "contact": [
            {
                "@attributes": {
                    "id": "137"
                },
                "instance_id": "1",
                "contact_name": "xi_default_contact",
                "is_active": "1",
                "config_type": "1",
                "alias": "Default Contact",
                "email_address": "root@localhost",
                "pager_address": "",
                "host_timeperiod_id": "129",
                "service_timeperiod_id": "129",
                "host_notifications_enabled": "1",
                "service_notifications_enabled": "1",
                "can_submit_commands": "1",
                "notify_service_recovery": "0",
                "notify_service_warning": "0",
                "notify_service_unknown": "0",
                "notify_service_critical": "0",
                "notify_service_flapping": "0",
                "notify_service_downtime": "0",
                "notify_host_recovery": "0",
                "notify_host_down": "0",
                "notify_host_unreachable": "0",
                "notify_host_flapping": "0",
                "notify_host_downtime": "0"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-host"></a>
                    <h4>GET objects/host</h4>
                    <p><?php echo _('This command returns a list of hosts.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/host?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/host?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "hostlist": {
        "recordcount": "12",
        "host": [
            {
                "@attributes": {
                    "id": "202"
                },
                "instance_id": "1",
                "host_name": "tset",
                "is_active": "1",
                "config_type": "1",
                "alias": "tset",
                "display_name": "tset",
                "address": "127.0.53.53",
                "check_interval": "40",
                "retry_interval": "5",
                "max_check_attempts": "4",
                "first_notification_delay": "0",
                "notification_interval": "1",
                "passive_checks_enabled": "1",
                "active_checks_enabled": "1",
                "notifications_enabled": "0",
                "notes": "",
                "notes_url": "",
                "action_url": "",
                "icon_image": "server.png",
                "icon_image_alt": "",
                "statusmap_image": "server.png"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-service"></a>
                    <h4>GET objects/service</h4>
                    <p><?php echo _('This command returns a list of services.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/service?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/service?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "servicelist": {
        "recordcount": "59",
        "service": [
            {
                "@attributes": {
                    "id": "195"
                },
                "instance_id": "1",
                "host_name": "test123",
                "service_description": "Domain Expiration",
                "is_active": "1",
                "config_type": "1",
                "display_name": "Domain Expiration",
                "check_interval": "1440",
                "retry_interval": "1",
                "max_check_attempts": "5",
                "first_notification_delay": "0",
                "notification_interval": "60",
                "passive_checks_enabled": "1",
                "active_checks_enabled": "1",
                "notifications_enabled": "1",
                "notes": "",
                "notes_url": "",
                "action_url": "",
                "icon_image": "",
                "icon_image_alt": ""
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-hostgroup"></a>
                    <h4>GET objects/hostgroup</h4>
                    <p><?php echo _('This command returns a list of host groups.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/hostgroup?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/hostgroup?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "hostgrouplist": {
        "recordcount": "2",
        "hostgroup": [
            {
                "@attributes": {
                    "id": "234"
                },
                "instance_id": "1",
                "hostgroup_name": "windows-servers",
                "is_active": "1",
                "config_type": "1",
                "alias": "windows-servers"
            },
            {
                "@attributes": {
                    "id": "141"
                },
                "instance_id": "1",
                "hostgroup_name": "linux-servers",
                "is_active": "1",
                "config_type": "1",
                "alias": "Linux Servers"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-servicegroup"></a>
                    <h4>GET objects/servicegroup</h4>
                    <p><?php echo _('This command returns a list of service groups.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/servicegroup?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/servicegroup?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "servicegrouplist": {
        "recordcount": "1",
        "servicegroup": {
            "@attributes": {
                "id": "235"
            },
            "instance_id": "1",
            "servicegroup_name": "Test Service Group",
            "is_active": "1",
            "config_type": "1",
            "alias": "test123"
        }
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-contactgroup"></a>
                    <h4>GET objects/contactgroup</h4>
                    <p><?php echo _('This command returns a list of contact groups.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/contactgroup?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/contactgroup?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "contactgrouplist": {
        "recordcount": "2",
        "contactgroup": [
            {
                "@attributes": {
                    "id": "138"
                },
                "instance_id": "1",
                "contactgroup_name": "admins",
                "is_active": "1",
                "config_type": "1",
                "alias": "Nagios Administrators"
            },
            {
                "@attributes": {
                    "id": "139"
                },
                "instance_id": "1",
                "contactgroup_name": "xi_contactgroup_all",
                "is_active": "1",
                "config_type": "1",
                "alias": "All Contacts"
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-hostgroupmembers"></a>
                    <h4>GET objects/hostgroupmembers</h4>
                    <p><?php echo _('This command returns a list of host group members.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/hostgroupmembers?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/hostgroupmembers?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "hostgrouplist": {
        "recordcount": "3",
        "hostgroup": [
            {
                "@attributes": {
                    "id": "234"
                },
                "instance_id": "1",
                "hostgroup_name": "windows-servers",
                "members": {
                    "host": {
                        "@attributes": {
                            "id": ""
                        },
                        "host_name": ""
                    }
                }
            },
            {
                "@attributes": {
                    "id": "141"
                },
                "instance_id": "1",
                "hostgroup_name": "linux-servers",
                "members": {
                    "host": [
                        {
                            "@attributes": {
                                "id": "140"
                            },
                            "host_name": "localhost"
                        },
                        {
                            "@attributes": {
                                "id": "208"
                            },
                            "host_name": "esfgsegseg"
                        }
                    ]
                }
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-servicegroupmembers"></a>
                    <h4>GET objects/servicegroupmembers</h4>
                    <p><?php echo _('This command returns a list of service group members.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/servicegroupmembers?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/servicegroupmembers?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "servicegrouplist": {
        "recordcount": "7",
        "servicegroup": {
            "@attributes": {
                "id": "235"
            },
            "instance_id": "1",
            "servicegroup_name": "Something",
            "members": {
                "service": [
                    {
                        "@attributes": {
                            "id": "173"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Ping"
                    },
                    {
                        "@attributes": {
                            "id": "174"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Yum Updates"
                    },
                    {
                        "@attributes": {
                            "id": "175"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Load"
                    },
                    {
                        "@attributes": {
                            "id": "177"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Memory Usage"
                    },
                    {
                        "@attributes": {
                            "id": "178"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Swap Usage"
                    },
                    {
                        "@attributes": {
                            "id": "180"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Users"
                    },
                    {
                        "@attributes": {
                            "id": "181"
                        },
                        "host_name": "127.0.0.1",
                        "service_description": "Total Processes"
                    }
                ]
            }
        }
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-contactgroupmembers"></a>
                    <h4>GET objects/contactgroupmembers</h4>
                    <p><?php echo _('This command returns a list of contact group members.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/contactgroupmembers?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/contactgroupmembers?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "contactgrouplist": {
        "recordcount": "4",
        "contactgroup": [
            {
                "@attributes": {
                    "id": "139"
                },
                "instance_id": "1",
                "contactgroup_name": "xi_contactgroup_all",
                "members": {
                    "contact": [
                        {
                            "@attributes": {
                                "id": "130"
                            },
                            "contact_name": "nagiosadmin"
                        },
                        {
                            "@attributes": {
                                "id": "159"
                            },
                            "contact_name": "jomann"
                        },
                        {
                            "@attributes": {
                                "id": "161"
                            },
                            "contact_name": "lgroshen"
                        }
                    ]
                }
            },
            {
                "@attributes": {
                    "id": "138"
                },
                "instance_id": "1",
                "contactgroup_name": "admins",
                "members": {
                    "contact": {
                        "@attributes": {
                            "id": "130"
                        },
                        "contact_name": "nagiosadmin"
                    }
                }
            }
        ]
    }
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-rrdexport"></a>
                    <h4>GET objects/rrdexport</h4>
                    <p><?php echo _('This command returns an exported RRD performance data file.'); ?></p>
                    <div>
                        <h6>Arguments:</h6>
                        <?php
                            $rrd_argument_table = new help_table("Parameter", "Required", "Description", "Default");
                            $rrd_argument_table
                                ->tr(_("host_name"),            _("Yes"),   _("The name of the host to grab performance data for."),                        "")
                                ->tr(_("service_description"),  _("No"),    _("The name of the service to grab performance data for."),                     _("_HOST_"))
                                ->tr(_("start"),                _("No"),    _("Datetime to specify the start of the exported data, in Unix timestamp."),    _("NOW - 24 Hours"))
                                ->tr(_("stop"),                 _("No"),    _("Datetime to specify the end of the exported data, in Unix timestamp."),      _("NOW"))
                                ->tr(_("step"),                 _("No"),    _("Interval between data points, in seconds."),                                 _("300"))
                                ->tr(_("columns[]"),            _("No"),    _("Array of columns to display (e.g.: &amp;columns[]=pl&amp;columns[]=rta)."),  _("All available"))
                                ->write();
                        ?>
                    </div>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/rrdexport?apikey=<?php echo $apikey; ?>&amp;pretty=1&amp;host_name=localhost"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/rrdexport?apikey=<?php echo $apikey; ?>&pretty=1&host_name=localhost" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "meta": {
        "start": "1453838100",
        "step": "300",
        "end": "1453838400",
        "rows": "2",
        "columns": "4",
        "legend": {
            "entry": [
                "rta",
                "pl",
                "rtmax",
                "rtmin"
            ]
        }
    },
    "data": {
        "row": [
            {
                "t": "1453838100",
                "v": [
                    "6.0373333333e-03",
                    "0.0000000000e+00",
                    "1.7536000000e-02",
                    "3.0000000000e-03"
                ]
            },
            {
                "t": "1453838400",
                "v": [
                    "6.0000000000e-03",
                    "0.0000000000e+00",
                    "1.7037333333e-02",
                    "3.0000000000e-03"
                ]
            }
        ]
    }
}
</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="objects-cpexport"></a>
                    <h4>GET objects/cpexport</h4>
                    <p><?php echo _('This command returns the historical and predicted performance data for a host, service, and RRD track. This is essentially a stripped down version of the Capacity Planning report and allows running the Capacity Planning script on a single object for inclusion elsewhere.'); ?></p>
                    <div>
                        <h6>Arguments:</h6>
                        <?php
                            $cp_argument_table = new help_table("Parameter", "Required", "Description", "Default");
                            $cp_argument_table
                                ->tr(_("host_name"),            _("Yes"),   _("The name of the host to use for capacity planning."),                        "")
                                ->tr(_("service_description"),  _("Yes"),   _("The name of the service to use for capacity planning."),                     "")
                                ->tr(_("track"),                _("Yes"),   _("The name of the track in the RRD file for use in capacity planning. You can get this from the Capacity Planning report."), "")
                                ->tr(_("period"),               _("No"),    _("Number of weeks or months to run prediction for."),                          "1 week")
                                ->tr(_("method"),               _("No"),    _("Type of prediction method. Choose from:")." Holt-Winters, Linear Fit, Quadratic Fit, Cubic Fit",      "Holt-Winters")
                                ->write();
                        ?>
                    </div>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/objects/cpexport?apikey=<?php echo $apikey; ?>&amp;pretty=1&amp;host_name=localhost&amp;service_description=PING&amp;track=rta"</pre> <a href="<?php echo get_base_url(); ?>api/v1/objects/cpexport?apikey=<?php echo $apikey; ?>&pretty=1&host_name=localhost&service_description=PING&track=rta" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
<pre>
{
    "emax": 0.054638913633013,
    "eslope": -6.6762593089667e-9,
    "evalue_max": 0.048465071737742,
    "t_start": 1461607200,
    "warn_level": 100,
    "dmean": 0.049758636574074,
    "t_stop": 1463423400,
    "dmax": 0.066815333333333,
    "integrity": 0.99851411589896,
    "unit": "ms",
    "residue": 0.022304421929443,
    "emean": 0.043936264308191,
    "edate": 1463421600,
    "evalue": 0.042703899629423,
    "nd": 672,
    "ne": 336,
    "evalue_min": 0.036942727521105,
    "adjusted_sigma": 0.0057697452810991,
    "t_step": 1800,
    "crit_level": 500,
    "f_of_x_on_date": 0.041683033313948,
    "sigma": 0.0057611721083188,
    "data": {
        "historical": {
            "1461607200": 0.054187444444444,
            "1461609000": 0.046684444444444,
            "1461610800": 0.050581666666667,
            ...
        },
        "predicted": {
            "1462818600": 0.044343964403375,
            "1462820400": 0.042981548657107,
            "1462822200": 0.041565485688109,
            ...
        }
    },
    "name": "localhost_PING_rta"
}
</pre>
                </div>

             </div>
             <div class="col-sm-4 col-md-3 col-lg-3 nav-box">
                <div class="well help-right-nav">
                    <h5><?php echo _('Backend API - Objects Reference'); ?></h5>
                    <a href="#building-queries"><?php echo _('Building Limited Queries'); ?></a>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('Basic Objects'); ?></p>
                    <ul>
                        <li><a href="#objects-hoststatus"><?php echo _('GET objects/hoststatus'); ?></a></li>
                        <li><a href="#objects-servicestatus"><?php echo _('GET objects/servicestatus'); ?></a></li>
                        <li><a href="#objects-logentries"><?php echo _('GET objects/logentries'); ?></a></li>
                        <li><a href="#objects-statehistory"><?php echo _('GET objects/statehistory'); ?></a></li>
                        <li><a href="#objects-comment"><?php echo _('GET objects/comment'); ?></a></li>
                        <li><a href="#objects-downtime"><?php echo _('GET objects/downtime'); ?></a></li>
                        <li><a href="#objects-contact"><?php echo _('GET objects/contact'); ?></a></li>
                        <li><a href="#objects-host"><?php echo _('GET objects/host'); ?></a></li>
                        <li><a href="#objects-service"><?php echo _('GET objects/service'); ?></a></li>
                        <li><a href="#objects-hostgroup"><?php echo _('GET objects/hostgroup'); ?></a></li>
                        <li><a href="#objects-servicegroup"><?php echo _('GET objects/servicegroup'); ?></a></li>
                        <li><a href="#objects-contactgroup"><?php echo _('GET objects/contactgroup'); ?></a></li>
                    </ul>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('Group Members'); ?></p>
                    <ul>
                        <li><a href="#objects-hostgroupmembers"><?php echo _('GET objects/hostgroupmembers'); ?></a></li>
                        <li><a href="#objects-servicegroupmembers"><?php echo _('GET objects/servicegroupmembers'); ?></a></li>
                        <li><a href="#objects-contactgroupmembers"><?php echo _('GET objects/contactgroupmembers'); ?></a></li>
                    </ul>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('Data Exporting'); ?></p>
                    <ul>
                        <li><a href="#objects-rrdexport"><?php echo _('GET objects/rrdexport'); ?></a></li>
                        <li><a href="#objects-cpexport"><?php echo _('GET objects/cpexport'); ?></a></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

<?php
    do_page_end(true);
}
?>