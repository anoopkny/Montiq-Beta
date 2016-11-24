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
    $apikey = get_apikey();

    do_page_start(array("page_title" => _('Backend API - Config Reference')), true);
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

                <h1><?php echo _('Backend API - Config Reference'); ?></h1>
                <p style="padding: 0;"><?php echo _('With the new API in Nagios XI 5 we linked it up directly with the Core Config Manager. This allows creation and deletion of items directly from the API.'); ?> <em><?php echo _('This API section is'); ?> <b><?php echo _('admin only'); ?></b>.</em></p>

                <div class="message">
                    <ul class="actionMessage" style="margin-top: 2em;">
                        <li><i class="fa fa-exclamation l"></i> <b><?php echo _('Note'); ?>:</b> <?php echo _('By default the Nagios Core service <em><b>is not restarted</b></em> when an object is created or deleted via the API.'); ?> <?php echo _('To apply the configuration, use the'); ?> <a href="api-system-reference.php#system-applyconfig">GET system/applyconfig</a> <?php echo _('request after making the changes you want to apply'); ?>. <?php echo _('You may change the default behavior by passing the argument <code>applyconfig=1</code> in your API request which will import the data into your configuration and <em><b>will restart</b></em> the Nagios Core service.'); ?></li>
                    </ul>
                </div>

                <div class="message">
                    <ul class="actionMessage" style="margin-top: 0;">
                        <li>
                            <i class="fa fa-exclamation l"></i> <b><?php echo _('Note'); ?>:</b> <?php echo _('If you need to skip the per-item config verification add <code>force=1</code> to the config API request. This is especially helpful when applying an object that uses a template and may be inheriting one of the required parameters such as <code>check_command</code> which the CCM verification will not know about.'); ?> <em><?php echo _('Warning: This can cause the apply config to fail if not used properly.'); ?></em>
                        </li>
                    </ul>
                </div>

                <div class="help-section obj-reference">
                    <a name="add-host"></a>
                    <h4>POST config/host</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('This command creates a new host object.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>host_name</td>
                                            <td>host name</td>
                                        </tr>
                                        <tr>
                                            <td>address</td>
                                            <td>ip address</td>
                                        </tr>
                                        <tr>
                                            <td>max_check_attempts</td>
                                            <td>#</td>
                                        </tr>
                                        <tr>
                                            <td>check_period</td>
                                            <td>timeperiod_name</td>
                                        </tr>
                                        <tr>
                                            <td>contacts<br><em>or</em><br>contact_groups</td>
                                            <td>contacts<br><em>or</em><br>contact_groups</td>
                                        </tr>
                                        <tr>
                                            <td>notification_interval</td>
                                            <td>#</td>
                                        </tr>
                                        <tr>
                                            <td>notification_period</td>
                                            <td>timeperiod_name</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p><em>** <?php echo _('All normal host directives are able to be used like normal core config definition files.'); ?></em></p>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XPOST "<?php echo get_base_url(); ?>api/v1/config/host?apikey=<?php echo $apikey; ?>&amp;pretty=1" -d "host_name=testapihostapply&amp;address=127.0.0.1&amp;check_command=check_ping\!3000,80%\!5000,100%&amp;max_check_attempts=2&amp;check_period=24x7&amp;contacts=nagiosadmin&amp;notification_interval=5&amp;notification_period=24x7&amp;applyconfig=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Successfully added testapihostapply to the system. Config applied, Nagios Core was restarted."
}
</pre>
                                <h6><?php echo _('Response (Failure)'); ?>:</h6>
                                <pre>{
    "error": "Missing required variables",
    "missing": [
        "host_name",
        "address",
        "max_check_attempts",
        "check_period",
        "notification_interval",
        "notification_period",
        "contacts OR contact_groups"
    ]
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="add-service"></a>
                    <h4>POST config/service</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('This command creates a new service object.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>host_name</td>
                                            <td>host name</td>
                                        </tr>
                                        <tr>
                                            <td>service_description</td>
                                            <td>service name</td>
                                        </tr>
                                        <tr>
                                            <td>check_command</td>
                                            <td>command_name</td>
                                        </tr>
                                        <tr>
                                            <td>max_check_attempts</td>
                                            <td>#</td>
                                        </tr>
                                        <tr>
                                            <td>check_interval</td>
                                            <td>#</td>
                                        </tr>
                                        <tr>
                                            <td>retry_interval</td>
                                            <td>#</td>
                                        </tr>
                                        <tr>
                                            <td>check_period</td>
                                            <td>timeperiod_name</td>
                                        </tr>
                                        <tr>
                                            <td>notification_interval</td>
                                            <td>#</td>
                                        </tr>
                                        <tr>
                                            <td>notification_period</td>
                                            <td>timeperiod_name</td>
                                        </tr>
                                        <tr>
                                            <td>contacts<br><em>or</em><br>contact_groups</td>
                                            <td>contacts<br><em>or</em><br>contact_groups</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p><em>** <?php echo _('All normal host directives are able to be used like normal core config definition files.'); ?></em></p>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XPOST "<?php echo get_base_url(); ?>api/v1/config/service?apikey=<?php echo $apikey; ?>&amp;pretty=1" -d "host_name=testapihostapply&amp;service_description=PING&amp;check_command=check_ping\!3000,80%\!5000,100%&amp;check_interval=5&amp;retry_interval=5&amp;max_check_attempts=2&amp;check_period=24x7&amp;contacts=nagiosadmin&amp;notification_interval=5&amp;notification_period=24x7&amp;applyconfig=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Successfully added testapihostapply :: PING to the system. Config applied, Nagios Core was restarted."
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="add-hostgroup"></a>
                    <h4>POST config/hostgroup</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('This command creates a new hostgroup object.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>hostgroup_name</td>
                                            <td>hostgroup name</td>
                                        </tr>
                                        <tr>
                                            <td>alias</td>
                                            <td>alias</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p><em>** <?php echo _('All normal host directives are able to be used like normal core config definition files.'); ?></em></p>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XPOST "<?php echo get_base_url(); ?>api/v1/config/hostgroup?apikey=<?php echo $apikey; ?>&amp;pretty=1" -d "hostgroup_name=testapihostgroup&amp;alias=127.0.0.1&amp;applyconfig=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Successfully added testapihostgroup to the system. Config applied, Nagios Core was restarted."
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="add-servicegroup"></a>
                    <h4>POST config/servicegroup</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('This command creates a new servicegroup object.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>servicegroup_name</td>
                                            <td>servicegroup name</td>
                                        </tr>
                                        <tr>
                                            <td>alias</td>
                                            <td>alias</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p><em>** <?php echo _('All normal host directives are able to be used like normal core config definition files.'); ?></em></p>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XPOST "<?php echo get_base_url(); ?>api/v1/config/servicegroup?apikey=<?php echo $apikey; ?>&amp;pretty=1" -d "servicegroup_name=testapiservicegroup&amp;alias=127.0.0.1&amp;applyconfig=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Successfully added testapiservicegroup to the system. Config applied, Nagios Core was restarted."
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="delete-host"></a>
                    <h4>DELETE config/host</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('This command removes a host object.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>host_name</td>
                                            <td>host name</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XDELETE "<?php echo get_base_url(); ?>api/v1/config/host?apikey=<?php echo $apikey; ?>&amp;pretty=1&amp;host_name=testapihostapply&amp;applyconfig=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Removed testapihostapply from the system. Config applied, Nagios Core was restarted."
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="help-section obj-reference">
                    <a name="delete-service"></a>
                    <h4>DELETE config/service</h4>
                    <div class="container-fluid" style="padding: 0 5px;">
                        <div class="row">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <p><?php echo _('This command removes a service object.'); ?></p>
                                <table class="table table-condensed table-bordered table-hover" style="margin-bottom: 10px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo _('Required Parameters'); ?></th>
                                            <th><?php echo _('Value Type'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>host_name</td>
                                            <td>host name</td>
                                        </tr>
                                        <tr>
                                            <td>service_description</td>
                                            <td>service name</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <h6><?php echo _('Example cURL Request'); ?>:</h6>
                                <pre>curl -XDELETE "<?php echo get_base_url(); ?>api/v1/config/service?apikey=<?php echo $apikey; ?>&amp;pretty=1&amp;host_name=testapihostapply&amp;service_description=PING&amp;applyconfig=1"</pre>
                                <h6><?php echo _('Response (Success)'); ?>:</h6>
                                <pre>{
    "success": "Removed testapihostapply :: PING from the system. Config applied, Nagios Core was restarted."
}
</pre>
                            </div>
                        </div>
                    </div>
                </div>

             </div>
             <div class="col-sm-4 col-md-3 col-lg-3 nav-box">
                <div class="well help-right-nav">
                    <h5><?php echo _('Backend API - Config Reference'); ?></h5>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('Adding Objects'); ?></p>
                    <ul>
                        <li><a href="#add-host"><?php echo _('POST config/host'); ?></a></li>
                        <li><a href="#add-service"><?php echo _('POST config/service'); ?></a></li>
                        <li><a href="#add-hostgroup"><?php echo _('POST config/hostgroup'); ?></a></li>
                        <li><a href="#add-servicegroup"><?php echo _('POST config/servicegroup'); ?></a></li>
                    </ul>
                    <p style="margin: 10px 0; padding: 0;"><?php echo _('Removing Objects'); ?></p>
                    <ul>
                        <li><a href="#delete-host"><?php echo _('DELETE config/host'); ?></a></li>
                        <li><a href="#delete-service"><?php echo _('DELETE config/service'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php
    do_page_end(true);
}
?>