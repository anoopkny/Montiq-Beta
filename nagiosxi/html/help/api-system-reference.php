<?php
//
// Nagios XI API Documentation
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);

if (!is_admin()) {
    die(_('Not authorized to view this page.'));
}

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

    do_page_start(array("page_title" => _('Backend API - System Reference')), true);
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

                <h1><?php echo _('Backend API - System Reference'); ?></h1>
                <p><?php echo _('The system section of the API allows management of the system, services, and backend.'); ?> <em><?php echo _('This API section is'); ?> <b><?php echo _('admin only'); ?></b>.</em></p>

                <div class="help-section obj-reference">
                    <a name="system-applyconfig"></a>
                    <h4>POST system/applyconfig</h4>
                    <p><?php echo _('Run the apply config command which imports configs and restarts Nagios Core. This should normally be ran after adding objects via the API if the <code>applyconfig=1</code> parameter is not sent.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XPOST "<?php echo get_base_url(); ?>api/v1/system/applyconfig?apikey=<?php echo $apikey; ?>"</pre>
                        <div class="clear"></div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="system-importconfig"></a>
                    <h4>GET system/importconfig</h4>
                    <p><?php echo _('Runs the import command which imports all configuration files into the CCM. This command does not restart Nagios Core.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/system/importconfig?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/system/importconfig?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="system-status"></a>
                    <h4>GET system/status</h4>
                    <p><?php echo _('Gives the output of the current system status.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/system/status?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/system/status?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "instance_id": "1",
    "instancne_name": "localhost",
    "status_update_time": "2015-09-21 01:48:14",
    "program_start_time": "2015-09-20 12:21:20",
    "program_run_time": "48419",
    "program_end_time": "0000-00-00 00:00:00",
    "is_currently_running": "1",
    "process_id": "105075",
    "daemon_mode": "1",
    "last_command_check": "1969-12-31 18:00:00",
    "last_log_rotation": "2015-09-21 00:00:00",
    "notifications_enabled": "1",
    "active_service_checks_enabled": "1",
    "passive_service_checks_enabled": "1",
    "active_host_checks_enabled": "1",
    "passive_host_checks_enabled": "1",
    "event_handlers_enabled": "1",
    "flap_detection_enabled": "1",
    "process_performance_data": "1",
    "obsess_over_hosts": "0",
    "obsess_over_services": "0",
    "modified_host_attributes": "0",
    "modified_service_attributes": "0",
    "global_host_event_handler": "xi_host_event_handler",
    "global_service_event_handler": "xi_service_event_handler"
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="user"></a>
                    <h4>GET system/user</h4>
                    <p><?php echo _('Lists all users in the Nagios XI system.'); ?></p>
                    <div>
                        <h6><?php echo _('Example cURL Request'); ?>:</h6>
                        <pre class="curl-request">curl -XGET "<?php echo get_base_url(); ?>api/v1/system/user?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre> <a href="<?php echo get_base_url(); ?>api/v1/system/user?apikey=<?php echo $apikey; ?>&amp;pretty=1" target="_blank" rel="noreferrer" class="api-popout tt-bind" title="<?php echo _('Open URL in browser window'); ?>"><i class="fa fa-share icon-large"></i></a>
                        <div class="clear"></div>
                    </div>
                    <h6><?php echo _('Response JSON'); ?>:</h6>
                    <pre>{
    "records": 2,
    "users": [
        {
            "user_id": "2",
            "username": "jmcdouglas",
            "name": "Jordan McDouglas",
            "email": "jmcdouglas@localhost",
            "enabled": "1"
        },
        {
            "user_id": "1",
            "username": "nagiosadmin",
            "name": "Nagios Administrator",
            "email": "root@localhost",
            "enabled": "1"
        }
    ]
}</pre>
                </div>

                <div class="help-section obj-reference">
                    <a name="add-user"></a>
                    <h4>POST system/user</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-6 col-lg-6 col-xl-5">
                                <p><?php echo _('Creates a new user in the Nagios XI system. Values in bold are defaults.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                            <th><?php echo _('Values'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>username</td>
                                            <td>string</td>
                                            <td>user name</td>
                                        </tr>
                                        <tr>
                                            <td>password</td>
                                            <td>string</td>
                                            <td>password</td>
                                        </tr>
                                        <tr>
                                            <td>name</td>
                                            <td>string</td>
                                            <td>name</td>
                                        </tr>
                                        <tr>
                                            <td>email</td>
                                            <td>string</td>
                                            <td>email address</td>
                                        </tr>
                                        <tr>
                                            <td>force_pw_change</td>
                                            <td>integer</td>
                                            <td><b>1</b> or 0</td>
                                        </tr>
                                        <tr>
                                            <td>email_info</td>
                                            <td>integer</td>
                                            <td><b>1</b> or 0</td>
                                        </tr>
                                        <tr>
                                            <td>monitoring_contact</td>
                                            <td>integer</td>
                                            <td><b>1</b> or 0</td>
                                        </tr>
                                        <tr>
                                            <td>enable_notifications</td>
                                            <td>integer</td>
                                            <td><b>1</b> or 0</td>
                                        </tr>
                                        <tr>
                                            <td>language</td>
                                            <td>string</td>
                                            <td><b>xi default</b> or &lt;language&gt;</td>
                                        </tr>
                                        <tr>
                                            <td>date_format</td>
                                            <td>integer</td>
                                            <td>
                                                <b>1 - YYYY-MM-DD</b><br>
                                                2 - MM/DD/YYYY<br>
                                                3 - DD/MM/YYYY
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>number_format</td>
                                            <td>integer</td>
                                            <td>
                                                <b>1 - 1000.00</b><br>
                                                2 - 1,000.00<br>
                                                3 - 1.000,00<br>
                                                4 - 1 000.00<br>
                                                5 - 1'000.00
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>auth_level</td>
                                            <td>string</td>
                                            <td><b><?php echo _('user'); ?></b> <?php echo _('or admin'); ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"><?php echo _('If user type selected: (Ignored if user is admin)'); ?></td>
                                        </tr>
                                        <tr>
                                            <td>can_see_all_hs</td>
                                            <td>integer</td>
                                            <td><b>0</b> or 1</td>
                                        </tr>
                                        <tr>
                                            <td>can_control_all_hs</td>
                                            <td>integer</td>
                                            <td><b>0</b> or 1</td>
                                        </tr>
                                        <tr>
                                            <td>can_reconfigure_hs</td>
                                            <td>integer</td>
                                            <td><b>0</b> or 1</td>
                                        </tr>
                                        <tr>
                                            <td>can_control_engine</td>
                                            <td>integer</td>
                                            <td><b>0</b> or 1</td>
                                        </tr>
                                        <tr>
                                            <td>can_use_advanced</td>
                                            <td>integer</td>
                                            <td><b>0</b> or 1</td>
                                        </tr>
                                        <tr>
                                            <td>read_only</td>
                                            <td>integer</td>
                                            <td><b>0</b> or 1 (<?php echo _("all others won't work"); ?>)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 col-lg-6 col-xl-7">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XPOST "<?php echo get_base_url(); ?>api/v1/system/user?apikey=<?php echo $apikey; ?>&amp;pretty=1" -d "username=jmcdouglas&amp;password=test&amp;name=Jordan%20McDouglas&amp;email=jmcdouglas@localhost"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "<?php echo _('User account jmcdouglas was added successfully!'); ?>",
    "userid": "16"
}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="delete-user"></a>
                    <h4>DELETE system/user/&lt;user_id&gt;</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('Deletes a user from the Nagios XI system.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>user_id</td>
                                            <td>#</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XDELETE "<?php echo get_base_url(); ?>api/v1/system/user/2?apikey=<?php echo $apikey; ?>&amp;pretty=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Removed user from the system."
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

             </div>
             <div class="col-sm-4 col-md-3 col-lg-3 nav-box">
                <div class="well help-right-nav">
                    <h5><?php echo _('Backend API - System Reference'); ?></h5>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('Basic System'); ?></p>
                    <ul>
                        <li><a href="#system-applyconfig"><?php echo _('POST system/applyconfig'); ?></a></li>
                        <li><a href="#system-importconfig"><?php echo _('GET system/importconfig'); ?></a></li>
                        <li><a href="#system-status"><?php echo _('GET system/status'); ?></a></li>
                    </ul>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('System Management'); ?></p>
                    <ul>
                        <li><a href="#user"><?php echo 'GET system/user'; ?></a></li>
                        <li><a href="#add-user"><?php echo 'POST system/user'; ?></a></li>
                        <li><a href="#delete-user"><?php echo 'DELETE system/user/&lt;user_id&gt;'; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php
    do_page_end(true);
}
?>