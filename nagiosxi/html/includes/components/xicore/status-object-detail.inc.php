<?php
//
// XI Status Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../componenthelper.inc.php');


////////////////////////////////////////////////////////////////////////
// SERVICE DETAIL
////////////////////////////////////////////////////////////////////////

function show_service_detail()
{

    $host = grab_request_var("host", "");
    $service = grab_request_var("service", "");

    $service_id = get_service_id($host, $service);

    if (is_authorized_for_service(0, $host, $service) == false) {
        show_not_authorized_for_object_page();
    }

    // get additional tabs
    $cbdata = array(
        "host" => $host,
        "service" => $service,
        "tabs" => array(),
    );
    do_callbacks(CALLBACK_SERVICE_TABS_INIT, $cbdata);
    $customtabs = grab_array_var($cbdata, "tabs", array());
    //echo "CUSTOMTABS:<BR>";
    //print_r($customtabs);

    // save this for later
    $auth_command = is_authorized_for_service_command(0, $host, $service);

    // should configure tab be shown?
    //if(is_authorized_to_configure_service(0,$host,$service)==true && is_service_configurable($host,$service)==true)
    if (is_authorized_to_configure_service(0, $host, $service) == true)
        $show_configure = true;
    else
        $show_configure = false;


    // get service status
    $args = array(
        "cmd" => "getservicestatus",
        "service_id" => $service_id,
    );
    $xml = get_backend_xml_data($args);
    
    // get servicegroups
    $args = array(
        "cmd" => "getservicegroupmembers",
        "service_id" => $service_id,
    );
    $servicegroupsxml = get_backend_xml_data($args);

    // Get timezone datepicker format
    if (isset($_SESSION['date_format']))
        $dformat = $_SESSION['date_format'];
    else {
        if (is_null($dformat = get_user_meta(0, 'date_format')))
            $dformat = get_option('default_date_format');
    }
    $dfs = get_date_formats();

    $js_date = 'mm/dd/yy';
    if ($dformat == DF_ISO8601) {
        $js_date = 'yy-mm-dd';
    } else if ($dformat == DF_US) {
        $js_date = 'mm/dd/yy';
    } else if ($dformat == DF_EURO) {
        $js_date = 'dd/mm/yy';
    }

    do_page_start(array("page_title" => _("Service Status Detail")), true);
?>

    <h1><?php echo _("Service Status Detail"); ?></h1>

    <div class="servicestatusdetailheader">
        <div class="serviceimage">
            <!--image-->
            <?php show_object_icon($host, $service, true); ?>
        </div>
        <div class="servicetitle">
            <div class="servicename"><?php echo encode_form_val($service); ?></div>
            <div class="hostname"><a
                    href="<?php echo get_host_status_detail_link($host); ?>"><?php echo encode_form_val($host); ?></a>
            </div>
            <div class="servicegroups">
            <?php if (!empty($servicegroupsxml) && $servicegroupsxml->recordcount > 0) { ?>
                <?php echo _("Servicegroups:"); ?> 
                    <?php 
                            $sg_cnt = 1;
                            foreach($servicegroupsxml->servicegroup as $sg){
                                echo encode_form_val($sg->servicegroup_name);
                                if ($sg_cnt++ != $servicegroupsxml->recordcount)
                                    echo ", ";
                            }
                        ?>
            <?php } ?>
            </div>
        </div>
    </div>

    <?php draw_service_detail_links($host, $service); ?>
    <br clear="all">

    <script type="text/javascript">
        $(document).ready(function () {
            $("#tabs").tabs().show();
        });
    </script>

    <div id="tabs" class="hide">
    <ul class="tabnavigation">
        <li><a href="#tab-overview" title="<?php echo _("Overview"); ?>"><i class="fa fa-home fa-14"></i> <span><?php echo _("Overview"); ?></span></a></li>
        <li><a href="#tab-perfgraphs" title="<?php echo _("Performance Graphs"); ?>"><i class="fa fa-area-chart fa-14"></i> <span><?php echo _("Performance Graphs"); ?></span></a></li>
        <?php
        if (is_advanced_user()) {
            ?>
            <li><a href="#tab-advanced" title="<?php echo _("Advanced"); ?>"><i class="fa fa-plus-square fa-14"></i> <span><?php echo _("Advanced"); ?></span></a></li>
        <?php
        }
        if ($show_configure == true) {
            ?>
            <li><a href="#tab-configure" title="<?php echo _("Configure"); ?>"><i class="fa fa-cog fa-14"></i> <span><?php echo _("Configure"); ?></span></a></li>
        <?php
        }
        ?>
        <?php
        // custom tabs
        foreach ($customtabs as $ct) {
            $id = grab_array_var($ct, "id");
            $title = grab_array_var($ct, "title");
            $icon = grab_array_var($ct, "icon");
            if (empty($icon))
                    $icon = '<i class="fa fa-file-o fa-14"></i>';
            echo "<li><a href='#tab-custom-" . $id . "' title='". encode_form_val($title)."'>".$icon." <span>" . encode_form_val($title) . "</span></a></li>";
        }
        ?>
    </ul>

    <!-- overview tab -->
    <div id="tab-overview" class="ui-tabs-hide">

    <div class="statusdetail_panelspacer"></div>

    <div>
        <?php

        $args = array(
            "hostname" => $host,
            "servicename" => urlencode($service),
            "service_id" => $service_id,
            "display" => "simple",
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"$var\" : \"$val\"";
            $n++;
        }
        $jargs .= "}";

        $id = "service_state_summary_" . random_string(6);
        $output = '
    <div class="service_state_summary" id="' . $id . '">
    ' . xicore_ajax_get_service_status_state_summary_html($args) . '
    </div><!--service_state_summary-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(7*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_service_status_state_summary_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
        ?>
        <?php echo $output; ?>
    </div>

    <div style="float: left;">
        <?php

        $args = array(
            "hostname" => $host,
            "servicename" => urlencode($service),
            "service_id" => $service_id,
            "display" => "simple",
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"$var\" : \"$val\"";
            $n++;
        }
        $jargs .= "}";

        $id = "service_state_info_" . random_string(6);
        $output = '
    <div class="service_state_info" id="' . $id . '">
    ' . xicore_ajax_get_service_status_detailed_info_html($args) . '
    </div><!--service_state_info-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(7*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_service_status_detailed_info_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
        function fill_' . $id . '(data){
            $("#' . $id . '").innerHTML=data;
            }
        
    });
    </script>
            ';
        ?>
        <?php echo $output; ?>
    </div>

    <input type="hidden" id="host" value="<?php echo $host; ?>">
    <input type="hidden" id="service" value="<?php echo $service; ?>">
    <input type="hidden" id="com_author" value="<?php echo get_user_attr(0, 'name'); ?>">

    <script type="text/javascript">
    $(document).ready(function() {

        $('.childpage').on('click', '.cmdlink', function() {
            var modal = $(this).data('modal');
            var cmdtype = $(this).data('cmd-type');
            $('#'+modal+' .cmd-type').val(cmdtype);

            whiteout();
            $('#'+modal).show();
            $('#'+modal).position({ my: "center", at: "center", of: window });
        });

        $('.submit-add-ack').click(function() {
            var error = 0;
            $('#add-ack .req').each(function(k, i) {
                if ($(i).val() == '') {
                    error++;
                }
            });

            if (error) {
                alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                return;
            }

            var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                         cmd_mod: 2,
                         nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                         host: $('#host').val(),
                         service: $('#service').val(),
                         com_author: $('#com_author').val(),
                         com_data: $('#add-ack .com_data').val() }

            if ($('#sticky_ack').is(':checked')) {
                args.sticky_ack = 'on';
            }

            if ($('#send_notification').is(':checked')) {
                args.send_notification = 'on';
            }

            if ($('#add-ack .persistent').is(':checked')) {
                args.persistent = 'on';
            }

            // Send the cmd & data to Core
            $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                $('#add-ack').hide();
                clear_whiteout();
            });
        });

        $('.submit-remove-ack').click(function() {
            var error = 0;
            $('#remove-ack .req').each(function(k, i) {
                if ($(i).val() == '') {
                    error++;
                }
            });

            if (error) {
                alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                return;
            }

            var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                         cmd_mod: 2,
                         nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                         host: $('#host').val(),
                         service: $('#service').val() }

            // Send the cmd & data to Core
            $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                $('#remove-ack').hide();
                clear_whiteout();
            });
        });

        $('.cancel').click(function() {
            $(this).parent('div').hide();
            clear_whiteout();
        });

        $('#fixed').change(function() {
            if ($(this).val() == 0) {
                $('#flexible-box').show();
                $(this).parents('.xi-modal').position({ my: "center", at: "center", of: window });
            } else {
                $('#flexible-box').hide();
                $(this).parents('.xi-modal').position({ my: "center", at: "center", of: window });
            }
        });

        $('.datetimepicker').datetimepicker({
            showOn: 'button',
            buttonImage: '../../../images/datetimepicker.png',
            buttonImageOnly: true,
            dateFormat: '<?php echo $js_date; ?>',
            timeFormat: 'HH:mm:ss',
            showHour: true,
            showMinute: true,
            showSecond: true
        });

        $(window).resize(function() {
            $('.xi-modal').position({ my: "center", at: "center", of: window });
        });

    });
    </script>

    <div class="xi-modal hide" id="add-ack">

        <?php
        // Get acknowledgement defaults
        $adefault_sticky_acknowledgment = get_option('adefault_sticky_acknowledgment', 1);
        $adefault_send_notification = get_option('adefault_send_notification', 1);
        $adefault_persistent_comment = get_option('adefault_persistent_comment', 0);
        ?>

        <h2><?php echo _('Acknowledge Problem'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Acknowledge Problem'); ?>" data-content="<?php echo _('This command is used to acknowledge a service problem. When a service problem is acknowledged, future notifications about problems are temporarily disabled until the service changes from its current state. If you want acknowledgement to disable notifications until the service recovers, check the Sticky Acknowledgement checkbox. Contacts for this service will receive a notification about the acknowledgement, so they are aware that someone is working on the problem. Additionally, a comment will also be added to the service. Make sure to enter your name and fill in a brief description of what you are doing in the comment field. If you would like the service comment to remain once the acknowledgement is removed, check the Persistent Comment checkbox. If you do not want an acknowledgement notification sent out to the appropriate contacts, uncheck the Send Notification checkbox.'); ?>"></i></h2>
        <input type="hidden" class="cmd-type" value="">
        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
            </tr>
            <tr>
                <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" id="sticky_ack" value="1" <?php echo is_checked($adefault_sticky_acknowledgment, 1); ?>> <?php echo _('Sticky Acknowledgement'); ?>
                    </label>
                    <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Sticky Acknowledgement'); ?>" data-content="<?php echo _('If you want acknowledgement to disable notifications until the service recovers, check the Sticky Acknowledgement checkbox.'); ?>"></i>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" id="send_notification" value="1" <?php echo is_checked($adefault_send_notification, 1); ?>> <?php echo _('Send Notification'); ?>
                    </label>
                    <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Send Notification'); ?>" data-content="<?php echo _('If you do not want an acknowledgement notification sent out to the appropriate contacts, uncheck the Send Notification checkbox.'); ?>"></i>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" class="persistent" value="1" <?php echo is_checked($adefault_persistent_comment, 1); ?>> <?php echo _('Persistent Comment'); ?>
                    </label>
                    <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Persistent Comment'); ?>" data-content="<?php echo _('Make sure to enter your name and fill in a brief description of what you are doing in the comment field. If you would like the service comment to remain once the acknowledgement is removed, check the Persistent Comment checkbox.'); ?>"></i>
                </td>
            </tr>
            <tr>
                <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
            </tr>
            <tr>
                <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control com_data req" style="width: 360px;" value="<?php echo _('Problem has been acknowledged'); ?>"></td>
            </tr>
        </table>
        <button type="button" class="btn btn-sm btn-primary submit-add-ack"><?php echo _('Submit'); ?></button>
        <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
    </div>

    <div style="float: left;">
        <div class="infotable_title"><?php echo _("Quick Actions"); ?></div>
        <table class="table table-condensed table-striped table-bordered">
            <thead>
            </thead>
            <tbody>
            <tr>
                <td>
                    <!-- dynamic entries-->
                    <ul class="quickactions dynamic">
                        <?php

                        $args = array(
                            "hostname" => $host,
                            "servicename" => urlencode($service),
                            "service_id" => $service_id,
                            "display" => "simple",
                        );

                        // build args for javascript
                        $n = 0;
                        $jargs = "{";
                        foreach ($args as $var => $val) {
                            if ($n > 0)
                                $jargs .= ", ";
                            $jargs .= "\"$var\" : \"$val\"";
                            $n++;
                        }
                        $jargs .= "}";

                        $id = "service_state_quick_actions_" . random_string(6);
                        $output = '
    <div class="service_state_quick_actions" id="' . $id . '">
    ' . xicore_ajax_get_service_status_quick_actions_html($args) . '
    </div><!--service_state_quick_actions-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_service_status_quick_actions_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
                        ?>
                        <?php echo $output; ?>
                    </ul>

                    <!-- other entries-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
        function show_ack() {
            $("#servicequickactionform").each(function (i) {
                $(this).show();
                this.innerHTML = "<br><div class='infotable_title'><?php echo _("Acknowledge Problem"); ?></div><form action='' method='get'><input type='hidden' name='show' value='servicedetail'><input type='hidden' name='host' value='<?php echo htmlentities($host);?>'><input type='hidden' name='service' value='<?php echo htmlentities($service);?>'><input type='hidden' name='submitcommand' value='1'><input type='hidden' name='cmd' value='ackservice'><label for='comment'><?php echo _("Your comment");?></label><br><input type='text' class='textfield' size='40' name='comment' id='comment'><input type='submit' name='btnSubmit' value='<?php echo _("Submit");?>'></form>";
            });
            $("#servicequickactionformcontainer").each(function (i) {
                $(this).hide();
            });
        }
    </script>

    <div class="clear"></div>

    <div id="servicequickactionformcontainer" class="hide">
        <div id="servicequickactionform">
            <!--LIVE ACTION FORM-->
        </div>
    </div>

    <div style="float: left; margin-top: 20px;">
        <?php

        $args = array(
            "hostname" => $host,
            "servicename" => urlencode($service),
            "service_id" => $service_id,
            "display" => "simple",
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"$var\" : \"$val\"";
            $n++;
        }
        $jargs .= "}";

        $id = "service_comments_" . random_string(6);
        $output = '
    <div class="service_comments" id="' . $id . '">
    ' . xicore_ajax_get_service_comments_html($args) . '
    </div><!--service_comments-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_service_comments_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
        ?>
        <?php echo $output; ?>
    </div>

    <div class="clear"></div>
    </div>

    <!-- performance graphs tab -->
    <div id="tab-perfgraphs" class="ui-tabs-hide">

        <?php //draw_service_detail_links($host,$service);?>
        <div class="statusdetail_panelspacer"></div>

        <div class="stausdetail_chart_timeframe_selector"<?php if (!use_2014_features()) {
            echo ' style="display:none;"';
        } ?>>
            <?php echo _('Graph Timeframe'); ?>:
            <select id="perfdata-timeframe-select" class="form-control condensed">
                <option value="0"><?php echo _('Last 4 Hours'); ?></option>
                <option value="1" selected><?php echo _('Last 24 Hours'); ?></option>
                <option value="2"><?php echo _('Last 7 Days'); ?></option>
                <option value="3"><?php echo _('Last 30 Days'); ?></option>
                <option value="4"><?php echo _('Last 365 Days'); ?></option>
            </select>
        </div>

        <?php
        $args = array(
            "hostname" => $host,
            "servicename" => urlencode($service),
            "service_id" => $service_id,
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"" . htmlentities($var) . "\" : \"" . htmlentities($val) . "\"";
            $n++;
        }
        $jargs .= "}";
        ?>


        <script type="text/javascript">
            var service_perfgraphs_panel_displayed = false;
            var servoce_perfgraphs_panel_throbber = $("#servicedetails-perfgraphs-panel-content").html();

            $(document).ready(function() {

                var locationObj = window.location;
                if (locationObj.hash == "#tab-perfgraphs") {
                    //alert('tab-perfgraphs');
                    load_perfgraphs_panel();
                }

                var tabContainers = $('#tabs > div');
                $('#tabs ul.tabnavigation a').click(function () {
                    //alert(this.hash + " selected");
                    if (this.hash == "#tab-perfgraphs")
                        load_perfgraphs_panel();
                    return false;
                });

                // Timeframe selection
                $("#perfdata-timeframe-select").change(function () {
                    service_perfgraphs_panel_displayed = false;
                    $("#servicedetails-perfgraphs-panel-content").html(servoce_perfgraphs_panel_throbber);
                    load_perfgraphs_panel();
                });

            });

            // Load the actual perfgraphs in the panel
            function load_perfgraphs_panel() {

                if (service_perfgraphs_panel_displayed == true) {
                    return;
                }
                service_perfgraphs_panel_displayed = true;

                // Load default time settings
                <?php if (use_2014_features()) { ?>
                var view = $("#perfdata-timeframe-select option:selected").val();
                <?php } else { ?>
                view = 1;
                <?php } ?>

                var optsarr = {
                    "func": "get_service_detail_perfgraphs_panel",
                    "args": <?php echo $jargs;?>
                }

                // Add timeframe
                optsarr.args.view = view;
                var opts = array2json(optsarr);
                var panel = $('#servicedetails-perfgraphs-panel-content');
                var thepanel = panel[0];
                get_ajax_data_innerHTML("getxicoreajax", opts, true, thepanel);
            }

        </script>

        <div id="servicedetails-perfgraphs-panel-content">
            <img src="<?php echo theme_image("throbber.gif"); ?>"> <?php echo _("Loading performance graphs..."); ?>
        </div>

    </div>
    <!-- performance graphs tab -->

    <!-- advanced tab -->
    <?php
    if (is_advanced_user()) {
        ?>
        <div id="tab-advanced" class="ui-tabs-hide">

            <div class="statusdetail_panelspacer"></div>

            <div style="float: left;">
                <?php

                $args = array(
                    "hostname" => $host,
                    "servicename" => urlencode($service),
                    "service_id" => $service_id,
                    "display" => "advanced",
                );

                // build args for javascript
                $n = 0;
                $jargs = "{";
                foreach ($args as $var => $val) {
                    if ($n > 0)
                        $jargs .= ", ";
                    $jargs .= "\"$var\" : \"$val\"";
                    $n++;
                }
                $jargs .= "}";

                $id = "service_state_info_" . random_string(6);
                $statusdetail_id = $id;
                $statusdetail_jargs = $jargs;
                $output = '
    <div class="service_state_info" id="' . $id . '">
    ' . xicore_ajax_get_service_status_detailed_info_html($args) . '
    </div><!--service_state_info-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
            var optsarr = {
                "func": "get_service_status_detailed_info_html",
                "args": ' . $jargs . '
                }
            var opts=array2json(optsarr);
            get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
                ?>
                <?php echo $output; ?>
            </div>
            <!--state info-->

            <div style="float: left;">
                <?php

                $args = array(
                    "hostname" => $host,
                    "servicename" => urlencode($service),
                    "service_id" => $service_id,
                    "display" => "all",
                );

                // build args for javascript
                $n = 0;
                $jargs = "{";
                foreach ($args as $var => $val) {
                    if ($n > 0)
                        $jargs .= ", ";
                    $jargs .= "\"$var\" : \"$val\"";
                    $n++;
                }
                $jargs .= "}";

                $id = "advanced_servicestatus_attributes_" . random_string(6);
                $output = '

    <div class="advanced_servicestatus_attributes" id="' . $id . '">
    ' . xicore_ajax_get_service_status_attributes_html($args) . '
    </div>

    <script type="text/javascript">
    $(document).ready(function() {
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_service_status_attributes_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
                ?>
                <?php echo $output; ?>
            </div>

            <script type="text/javascript">
            $(document).ready(function() {

                $('.submit-schedule-downtime').click(function() {
                    var error = 0;
                    $('#schedule-downtime .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 service: $('#service').val(),
                                 com_author: $('#com_author').val(),
                                 com_data: $('#schedule-downtime .com_data').val(),
                                 trigger: $('#trigger').val(),
                                 start_time: $('#startdateBox').val(),
                                 end_time: $('#enddateBox').val(),
                                 fixed: $('#fixed').val(),
                                 hours: parseInt($('#flexible-hours').val()),
                                 minutes: parseInt($('#flexible-minutes').val()) }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#schedule-downtime').hide();
                        clear_whiteout();

                        $('#schedule-downtime .com_data').val('');
                    });
                });

                $('.submit-comment').click(function() {
                    var error = 0;
                    $('#comment .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 service: $('#service').val(),
                                 com_author: $('#com_author').val(),
                                 com_data: $('#comment .com_data').val() }

                    if ($('#comment .persistent').is(':checked')) {
                        args.persistent = 'on';
                    }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#comment').hide();
                        clear_whiteout();

                        $('#comment .persistent').prop('checked', true);
                        $('#comment .com_data').val('');
                    });
                });

                $('.submit-delay-notification').click(function() {
                    var error = 0;
                    $('#delay-notification .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 service: $('#service').val(),
                                 not_dly: $('#not_dly').val() }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#delay-notification').hide();
                        clear_whiteout();
                        $('#not_dly').val('0');
                    });
                });

                $('.submit-custom-notification').click(function() {
                    var error = 0;
                    $('#custom-notification .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 service: $('#service').val(),
                                 com_author: $('#com_author').val(),
                                 com_data: $('#custom-notification .com_data').val() }

                    if ($('#forced').is(':checked')) {
                        args.force_notification = 'on';
                    }

                    if ($('#broadcast').is(':checked')) {
                        args.broadcast_notification = 'on';
                    }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#custom-notification').hide();
                        clear_whiteout();
                        $('#custom-notification .com_data').val('');
                        $('#forced').prop('checked', false);
                        $('#broadcast').prop('checked', false);
                    });
                });
            
                $('.submit-passive-check').click(function() {
                    var error = 0;
                    $('#passive-check .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 service: $('#service').val(),
                                 plugin_state: $('#plugin_state').val(),
                                 plugin_output: $('#plugin_output').val(),
                                 performance_data: $('#perfdata').val() }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#passive-check').hide();
                        clear_whiteout();

                        $('#plugin_state').val(0);
                        $('#plugin_output').val('');

                        // Force reload of status in the Advanced tab
                        setTimeout(function() {
                            var optsarr = {
                                "func": "get_service_status_detailed_info_html",
                                "args": <?php echo $statusdetail_jargs; ?>
                            }
                            var opts = array2json(optsarr);
                            get_ajax_data_innerHTML("getxicoreajax", opts, true, '#<?php echo $statusdetail_id; ?>');
                        }, 500);
                    });
                });

                // Check Date range accuracy
                $('#startdateBox').change(function() {
                    var start_input = $('#startdateBox');
                    var end_input = $('#enddateBox');
                    var startdate_tp = start_input.datetimepicker('getDate');
                    var enddate_tp = end_input.datetimepicker('getDate');

                    dstartdate = Date.parse(startdate_tp)/1000;
                    denddate = Date.parse(enddate_tp)/1000;

                    if (dstartdate > denddate) {
                        var new_ntp = startdate_tp;
                        new_ntp.setHours(startdate_tp.getHours() + 2);
                        end_input.datetimepicker('setDate', new_ntp);
                    }
                });

            });
            </script>

            <div style="float: left; margin: 0 25px;">

                <?php if ($auth_command) { ?>
                    
                    <div class="infotable_title"><?php echo _("Commands"); ?></div>

                    <table class="table table-condensed table-striped table-bordered table-auto-width">
                        <tbody>

                        <?php
                        $urlbase = get_base_url() . "includes/components/nagioscore/ui/cmd.php?cmd_typ=";
                        $urlmod = "&host=" . urlencode($host) . "&service=" . urlencode($service);

                        if ($xml && intval($xml->servicestatus->problem_acknowledged) == 1) {
                            ?>
                            <tr>
                                <td>
                                    <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_REMOVE_SVC_ACKNOWLEDGEMENT . $urlmod, "noack.gif", _("Remove problem acknowledgement")); ?>
                                    <a class="cmdlink" data-modal="remove-ack" data-cmd-type="<?php echo NAGIOSCORE_CMD_REMOVE_SVC_ACKNOWLEDGEMENT; ?>"><img src="<?php echo theme_image('ack_remove.png'); ?>"><?php echo _('Remove acknowledgement'); ?></a>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_ADD_SVC_COMMENT . $urlmod, "comment.png", _("Add Comment")); ?>
                                <a class="cmdlink" data-modal="comment" data-cmd-type="<?php echo NAGIOSCORE_CMD_ADD_SVC_COMMENT; ?>"><img src="<?php echo theme_image('comment_add.png'); ?>"><?php echo _('Add comment'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_SCHEDULE_SVC_DOWNTIME . $urlmod, "downtime.gif", _("Schedule downtime")); ?>
                                <a class="cmdlink" data-modal="schedule-downtime" data-cmd-type="<?php echo NAGIOSCORE_CMD_SCHEDULE_SVC_DOWNTIME; ?>"><img src="<?php echo theme_image('time_add.png'); ?>"><?php echo _('Schedule downtime'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_PROCESS_SERVICE_CHECK_RESULT . $urlmod, "passiveonly.gif", _("Submit passive check result")); ?>
                                <a class="cmdlink" data-modal="passive-check" data-cmd-type="<?php echo NAGIOSCORE_CMD_PROCESS_SERVICE_CHECK_RESULT; ?>"><img src="<?php echo theme_image('passiveonly.png'); ?>"><?php echo _('Submit passive check result'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_SEND_CUSTOM_SVC_NOTIFICATION . $urlmod, "notify.gif", _("Send custom notification")); ?>
                                <a class="cmdlink" data-modal="custom-notification" data-cmd-type="<?php echo NAGIOSCORE_CMD_SEND_CUSTOM_SVC_NOTIFICATION; ?>"><img src="<?php echo theme_image('transmit_go.png'); ?>"><?php echo _('Send custom notification'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_DELAY_SVC_NOTIFICATION . $urlmod, "delay.gif", _("Delay next notification")); ?>
                                <a class="cmdlink" data-modal="delay-notification" data-cmd-type="<?php echo NAGIOSCORE_CMD_DELAY_SVC_NOTIFICATION; ?>"><img src="<?php echo theme_image('transmit_blue.png'); ?>"><?php echo _('Delay next notification'); ?></a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                <?php
                }
                ?>

                <div class="xi-modal hide" id="remove-ack">
                    <h2><?php echo _('Remove Acknowledgement'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Remove Acknowledgement'); ?>" data-content="<?php echo _('This command is used to remove an acknowledgement for a particular service problem. Once the acknowledgement is removed, notifications may start being sent out about the service problem.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-remove-ack"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="passive-check">
                    <h2><?php echo _('Submit Passive Check Result'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Submit Passive Check Result'); ?>" data-content="<?php echo _('This command is used to submit a passive check result for a particular service. It can be useful for resetting security-related services to OK states once they have been dealt with.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Check Result'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td>
                                <select class="form-control state-select-service req" id="plugin_state">
                                    <option value="0" class="ok"><?php echo _('OK'); ?></option>
                                    <option value="1" class="warning"><?php echo _('WARNING'); ?></option>
                                    <option value="3" class="unknown"><?php echo _('UNKNOWN'); ?></option>
                                    <option value="2" class="critical"><?php echo _('CRITICAL'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Check Output'); ?> <i class="fa fa-asterisk tt-bind" title="<?php echo _('Required'); ?>" style="color: red;"></i></td>
                            <td><input type="text" class="form-control req" id="plugin_output" style="width: 360px;"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Performance Data'); ?></td>
                            <td><input type="text" class="form-control" id="perfdata" style="width: 360px;"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-passive-check"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="comment">
                    <h2><?php echo _('Add Comment'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Add Comment'); ?>" data-content="<?php echo _('This command is used to add a comment for the specified service. If you work with other administrators, you may find it useful to share information about a host or service that is having problems if more than one of you may be working on it. If you do not check the <strong>persistent</strong> option, the comment will automatically be deleted the next time Nagios is restarted.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" class="persistent" value="on" checked>
                                    <?php echo _('Persistent'); ?>
                                </label>
                                <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Persistent'); ?>" data-content="<?php echo _('If you do not check the <strong>persistent</strong> option, the comment will automatically be deleted the next time Nagios is restarted.'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_data req" style="width: 360px;"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-comment"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="schedule-downtime">
                    <h2><?php echo _('Schedule Downtime'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Schedule Downtime'); ?>" data-content="<?php echo _('This command is used to schedule downtime for a particular service. During the specified downtime, Nagios will not send notifications out about the service. When the scheduled downtime expires, Nagios will send out notifications for this service as it normally would. Scheduled downtimes are preserved across program shutdowns and restarts. Both the start and end times should be specified in the following format:'). ' ' . $dfs[$dformat] . '. ' ._('If you select the fixed option, the downtime will be in effect between the start and end times you specify. If you do not select the fixed option, Nagios will treat this as "flexible" downtime. Flexible downtime starts when the service enters a non-OK state (sometime between the start and end times you specified) and lasts as long as the duration of time you enter. The duration fields do not apply for fixed downtime.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_data req" style="width: 360px;"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Triggered By'); ?></td>
                            <td>
                                <?php

                                $get = '&username=' . $_SESSION['username'] . '&ticket=' . get_user_attr(0, 'backend_ticket');
                                $url = get_base_url()."includes/components/xicore/downtime.php?cmd=getdowntimes" . $get;

                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                $output = curl_exec($ch);
                                curl_close($ch);

                                $objs = json_decode($output);
                                ?>
                                <select class="form-control" id="trigger">
                                    <option value="0" selected><?php echo _('None'); ?></option>
                                    <?php
                                    $options = '';
                                    $s = '';
                                    $h = '';

                                    foreach ($objs as $obj) { 
                                        if (!empty($obj->service_description)) {
                                            $s .= '<option value="' . $obj->downtime_id . '">' . $obj->host_name . ' - '. $obj->service_description .' @ ' . get_datetime_string($obj->start_time/1000) . ' (ID ' . $obj->downtime_id . ')</option>';
                                        } else {
                                            $h .= '<option value="' . $obj->downtime_id . '">' . $obj->host_name . ' @ ' . get_datetime_string($obj->start_time/1000) . ' (ID ' . $obj->downtime_id . ')</option>';
                                        }
                                    }

                                    if (!empty($h)) {
                                        $options .= '<optgroup label="'._('Host Downtimes').'">' . $h . '</optgroup>';
                                    }

                                    if (!empty($s)) {
                                        $options .= '<optgroup label="'._('Service Downtimes').'">' . $s . '</optgroup>';
                                    }

                                    echo $options;
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Type'); ?></td>
                            <td>
                                <select id="fixed" class="form-control">
                                    <option value="1"><?php echo _('Fixed'); ?></option>
                                    <option value="0"><?php echo _('Flexible'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="flexible-box" class="hide">
                            <td><?php echo _('Duration'); ?></td>
                            <td>
                                <input type="text" class="form-control" style="width: 40px;" id="flexible-hours" value="2"> Hours
                                <input type="text" class="form-control" style="width: 40px; margin-left: 5px;" id="flexible-minutes" value="0"> Minutes
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _("Start Time"); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td>
                                <input class="form-control datetimepicker req" type="text" id='startdateBox' name="startdate" value="<?php echo get_datetime_string(time()); ?>" size="18">
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _("End Time"); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td>
                                <input class="form-control datetimepicker req" type="text" id='enddateBox' name="enddate" value="<?php echo get_datetime_string(strtotime('now + 2 hours')); ?>" size="18">
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-schedule-downtime"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="custom-notification">
                    <h2><?php echo _('Send Custom Notification'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Send Custom Notification'); ?>" data-content="<?php echo _('This command is used to send a custom notification about the specified service. Useful in emergencies when you need to notify admins of an issue regarding a monitored system or service. Custom notifications normally follow the regular notification logic in Nagios. Selecting the Forced option will force the notification to be sent out, regardless of the time restrictions, whether or not notifications are enabled, etc. Selecting the Broadcast option causes the notification to be sent out to all normal (non-escalated) and escalated contacts. These options allow you to override the normal notification logic if you need to get an important message out.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" id="forced" value="1"> <?php echo _('Forced'); ?>
                                </label>
                                <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Forced'); ?>" data-content="<?php echo _('Selecting the Forced option will force the notification to be sent out, regardless of the time restrictions, whether or not notifications are enabled, etc.'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" id="broadcast" value="1"> <?php echo _('Broadcast'); ?>
                                </label>
                                <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Broadcast'); ?>" data-content="<?php echo _('Selecting the Broadcast option causes the notification to be sent out to all normal (non-escalated) and escalated contacts.'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_data req" style="width: 360px;"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-custom-notification"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="delay-notification">
                    <h2><?php echo _('Delay Next Notification'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Delay Next Notification'); ?>" data-content="<?php echo _('This command is used to delay the next problem notification that is sent out for the specified service. The notification delay will be disregarded if the service changes state before the next notification is scheduled to be sent out. This command has no effect if the service is currently in an OK state.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Service'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $service; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Notification Delay'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" id="not_dly" class="form-control req" value="0" style="width: 40px;"> <?php echo _('minutes from now'); ?></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-delay-notification"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

            <div class="infotable_title" style="margin-top: 20px;"><?php echo _("More Options"); ?></div>
                <ul>
                    <li>
                        <a href="<?php echo get_service_status_detail_link($host, $service, "core"); ?>"><?php echo _("View in Nagios Core"); ?></a>
                    </li>
                </ul>
            </div>


        </div>
        <!-- advanced tab -->
    <?php
    }
    ?>

    <!-- configure tab -->
    <?php
    if ($show_configure == true) {
        ?>
        <div id="tab-configure" class="ui-tabs-hide">
            <?php

            echo "<p>";
            echo "<img src='" . theme_image("editsettings.png") . "' style='float: left; margin-right: 10px;'>";

            $url = get_base_url() . "config/configobject.php?host=" . urlencode($host) . "&service=" . urlencode($service) . "&return=servicedetail";
            echo "<a href='" . $url . "'>" . _('Re-configure this service') . "</a>";

            /*
            if(is_service_configurable($host,$service)==true){
                $url=get_base_url()."config/modifyobject.php?host=".$host."&service=".$service."&return=servicedetail";
                echo "<a href='".$url."'>Modify the settings for this service</a>";
                }
            else{
                //echo"This services makes use of an advanced configuration.  ";
                if(is_advanced_user()==true){
                    $url=get_base_url()."includes/components/ccm/xi-index.php";
                    echo "<a href='".$url."' target='_top'>Enter the advanced configuration manager</a> to modify the settings for this service.";
                    }
                else
                    echo "Contact your Nagios administrator to modify the settings for this service.";
                }
            */

            echo "<br>";
            echo "<p>";
            echo "<img src='" . theme_image("cross.png") . "' style='float: left; margin-right: 10px;'>";

            /*
            if(can_service_be_deleted($host,$service)==true){
                $url=get_base_url()."config/deleteobject.php?host=".$host."&service=".$service."&return=servicedetail";
                echo "<a href='".$url."'>Delete this service</a>";
                }
            else{
                if(is_advanced_user()==true){
                    $url=get_base_url()."includes/components/ccm/xi-index.php";
                    echo "<a href='".$url."' target='_top'>Enter the advanced configuration manager</a> to delete this service.";
                    }
                else
                    echo "Contact your Nagios administrator to delete this service.";
                }
            */
            $url = get_base_url() . "config/deleteobject.php?host=" . urlencode($host) . "&service=" . urlencode($service) . "&return=servicedetail";
            echo "<a href='" . $url . "'>" . _('Delete this service') . "</a>";

            ?>

        </div>
    <?php
    }
    ?>
    <!-- configure tab -->

    <?php
    // custom tabs
    foreach ($customtabs as $ct) {
        $id = grab_array_var($ct, "id");
        $content = grab_array_var($ct, "content");
        echo "<div id='tab-custom-" . $id . "'>" . $content . "</div>";
    }
    ?>

    </div>



    <?php
    do_page_end(true);
}


////////////////////////////////////////////////////////////////////////
// HOST DETAIL
////////////////////////////////////////////////////////////////////////

function show_host_detail()
{

    $host = grab_request_var("host", "");

    $host_id = get_host_id($host);

    if (is_authorized_for_host(0, $host) == false) {
        /*
        echo "HOST: $host<BR>";
        print_r($request);
        exit();
        */
        show_not_authorized_for_object_page();
    }

    // save this for later
    $auth_command = is_authorized_for_host_command(0, $host);

    // should configure tab be shown?
    //if(is_authorized_to_configure_host(0,$host)==true && is_host_configurable($host)==true)
    if (is_authorized_to_configure_host(0, $host) == true)
        $show_configure = true;
    else
        $show_configure = false;

    // get additional tabs
    $cbdata = array(
        "host" => $host,
        "service" => "",
        "tabs" => array(),
    );
    do_callbacks(CALLBACK_HOST_TABS_INIT, $cbdata);
    $customtabs = grab_array_var($cbdata, "tabs", array());
    //print_r($customtabs);

    // get host status
    $args = array(
        "cmd" => "gethoststatus",
        "host_id" => $host_id,
    );
    $xml = get_backend_xml_data($args);

    $hostalias = $xml->hoststatus->alias;

    // get hostgroups
    $args = array(
        "cmd" => "gethostgroupmembers",
        "host_id" => $host_id,
    );
    $hostgroupsxml = get_backend_xml_data($args);

    // Get timezone datepicker format
    if (isset($_SESSION['date_format']))
        $dformat = $_SESSION['date_format'];
    else {
        if (is_null($dformat = get_user_meta(0, 'date_format')))
            $dformat = get_option('default_date_format');
    }
    $dfs = get_date_formats();

    $js_date = 'mm/dd/yy';
    if ($dformat == DF_ISO8601) {
        $js_date = 'yy-mm-dd';
    } else if ($dformat == DF_US) {
        $js_date = 'mm/dd/yy';
    } else if ($dformat == DF_EURO) {
        $js_date = 'dd/mm/yy';
    }

    do_page_start(array("page_title" => _("Host Status Detail")), true);
?>

    <h1><?php echo _("Host Status Detail"); ?></h1>

    <div class="hoststatusdetailheader">
        <div class="hostimage">
            <!--image-->
            <?php show_object_icon($host, "", true); ?>
        </div>
        <div class="hosttitle">
            <div class="hostname"><?php echo encode_form_val($host); ?></div>
            <div class="hostalias"><?php echo _("Alias:"); ?> <?php echo encode_form_val($hostalias); ?></div>
            <div class="hostgroups">
            <?php if (!empty($hostgroupsxml) && $hostgroupsxml->recordcount > 0) { ?>
                <?php echo _("Hostgroups:"); ?> 
                    <?php 
                            $hg_cnt = 1;
                            foreach($hostgroupsxml->hostgroup as $hg){
                                echo encode_form_val($hg->hostgroup_name);
                                if ($hg_cnt++ != $hostgroupsxml->recordcount)
                                    echo ", ";
                            }
                        ?>
            <?php } ?>
            </div>
        </div>
    </div>

    <?php draw_host_detail_links($host); ?>
    <br clear="all">

    <script type="text/javascript">
        $(document).ready(function () {
            $("#tabs").tabs().show();
        });
    </script>

    <div id="tabs" class="hide">
    <ul class="tabnavigation">
        <li><a href="#tab-overview" title="<?php echo _("Overview"); ?>"><i class="fa fa-home fa-14"></i> <span><?php echo _("Overview"); ?></span></a></li>
        <li><a href="#tab-perfgraphs" title="<?php echo _("Performance Graphs"); ?>"><i class="fa fa-area-chart fa-14"></i> <span><?php echo _("Performance Graphs"); ?></span></a></li>
        <?php
        if (is_advanced_user()) {
            ?>
            <li><a href="#tab-advanced" title="<?php echo _("Advanced"); ?>"><i class="fa fa-plus-square fa-14"></i> <span><?php echo _("Advanced"); ?></span></a></li>
        <?php
        }
        if ($show_configure == true) {
            ?>
            <li><a href="#tab-configure" title="<?php echo _("Configure"); ?>"><i class="fa fa-cog fa-14"></i> <span><?php echo _("Configure"); ?></span></a></li>
        <?php
        }
        ?>
        <?php
        // custom tabs
        foreach ($customtabs as $ct) {
            $id = grab_array_var($ct, "id");
            $title = grab_array_var($ct, "title");
            $icon = grab_array_var($ct, "icon");
            if (empty($icon))
                    $icon = '<i class="fa fa-file-o fa-14"></i>';
            echo "<li><a href='#tab-custom-" . $id . "' title='". encode_form_val($title)."'>".$icon." <span>" . encode_form_val($title) . "</span></a></li>";
        }
        ?>
    </ul>

    <!-- overview tab -->
    <div id="tab-overview" class="ui-tabs-hide">

    <div class="statusdetail_panelspacer"></div>

    <div>
        <?php

        $args = array(
            "hostname" => $host,
            "host_id" => $host_id,
            "display" => "simple",
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"$var\" : \"$val\"";
            $n++;
        }
        $jargs .= "}";

        $id = "host_state_summary_" . random_string(6);
        $output = '
    <div class="host_state_summary" id="' . $id . '">
    ' . xicore_ajax_get_host_status_state_summary_html($args) . '
    </div><!--host_state_summary-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(7*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_host_status_state_summary_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
        ?>
        <?php echo $output; ?>
    </div>
   
    <div class="clear"></div>

    <div style="float: left; margin-bottom: 15px; clear: left;">
        <?php
        // get host info
        $args = array(
            "host_id" => $host_id,
        );
        $configxml = get_xml_host_objects($args);

        // host address
        $address = "";
        if ($configxml && intval($configxml->recordcount) > 0) {
            foreach ($configxml->host as $h) {
                $address = strval($h->address);
            }
        }
        ?>
        <b><?php echo _("Address"); ?>:</b> <?php echo $address; ?>
    </div>

    <br clear="all">


    <div style="float: left;"><!--state info-->
        <?php

        $args = array(
            "hostname" => $host,
            "host_id" => $host_id,
            "display" => "simple",
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"$var\" : \"$val\"";
            $n++;
        }
        $jargs .= "}";

        $id = "host_state_info_" . random_string(6);

        $output = '
    <div class="host_state_info" id="' . $id . '">
    ' . xicore_ajax_get_host_status_detailed_info_html($args) . '
    </div><!--host_state_info-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(7*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_host_status_detailed_info_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
        function fill_' . $id . '(data){
            $("#' . $id . '").innerHTML=data;
            }
        
    });
    </script>
            ';
        ?>
        <?php echo $output; ?>
    </div>

    <input type="hidden" id="host" value="<?php echo $host; ?>">
    <input type="hidden" id="com_author" value="<?php echo get_user_attr(0, 'name'); ?>">
    
    <script type="text/javascript">
    $(document).ready(function() {

        $('.childpage').on('click', '.cmdlink', function() {
            var modal = $(this).data('modal');
            var cmdtype = $(this).data('cmd-type');
            $('#'+modal+' .cmd-type').val(cmdtype);
            whiteout();
            $('#'+modal).show();
            $('#'+modal).position({ my: "center", at: "center", of: window });

            // Special for downtimes
            if (cmdtype == 86) {
                $('#childoptions-box').hide();
                $('#sd-all-info').show();
                $('#sd-info').hide();
                $('#sd-title').html('<?php echo _("Schedule Downtime for All Services on Host"); ?>');
            } else if (cmdtype == 55) {
                $('#childoptions-box').show();
                $('#sd-info').show();
                $('#sd-all-info').hide();
                $('#sd-title').html('<?php echo _("Schedule Downtime"); ?>');
            }
        });

        $('.submit-add-ack').click(function() {
            var error = 0;
            $('#add-ack .req').each(function(k, i) {
                if ($(i).val() == '') {
                    error++;
                }
            });

            if (error) {
                alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                return;
            }

            var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                         cmd_mod: 2,
                         nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                         host: $('#host').val(),
                         com_author: $('#com_author').val(),
                         com_data: $('#add-ack .com_data').val() }

            if ($('#sticky_ack').is(':checked')) {
                args.sticky_ack = 'on';
            }

            if ($('#send_notification').is(':checked')) {
                args.send_notification = 'on';
            }

            if ($('#add-ack .persistent').is(':checked')) {
                args.persistent = 'on';
            }

            // Send the cmd & data to Core
            $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                $('#add-ack').hide();
                clear_whiteout();
            });
        });

        $('.submit-remove-ack').click(function() {
            var error = 0;
            $('#remove-ack .req').each(function(k, i) {
                if ($(i).val() == '') {
                    error++;
                }
            });

            if (error) {
                alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                return;
            }

            var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                         cmd_mod: 2,
                         nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                         host: $('#host').val(),
                         service: $('#service').val() }

            // Send the cmd & data to Core
            $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                $('#remove-ack').hide();
                clear_whiteout();
            });
        });

        $('.cancel').click(function() {
            $(this).parent('div').hide();
            clear_whiteout();
        });

        $('#fixed').change(function() {
            if ($(this).val() == 0) {
                $('#flexible-box').show();
                $(this).parents('.xi-modal').position({ my: "center", at: "center", of: window });
            } else {
                $('#flexible-box').hide();
                $(this).parents('.xi-modal').position({ my: "center", at: "center", of: window });
            }
        });

        $('.datetimepicker').datetimepicker({
            showOn: 'button',
            buttonImage: '../../../images/datetimepicker.png',
            buttonImageOnly: true,
            dateFormat: '<?php echo $js_date; ?>',
            timeFormat: 'HH:mm:ss',
            showHour: true,
            showMinute: true,
            showSecond: true
        });

        $(window).resize(function() {
            $('.xi-modal').position({ my: "center", at: "center", of: window });
        });

    });
    </script>

    <div class="xi-modal hide" id="add-ack">

        <?php
        // Get acknowledgement defaults
        $adefault_sticky_acknowledgment = get_option('adefault_sticky_acknowledgment', 1);
        $adefault_send_notification = get_option('adefault_send_notification', 1);
        $adefault_persistent_comment = get_option('adefault_persistent_comment', 0);
        ?>

        <h2><?php echo _('Acknowledge Problem'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Acknowledge Problem'); ?>" data-content="<?php echo _('This command is used to acknowledge a host problem. When a host problem is acknowledged, future notifications about problems are temporarily disabled until the host changes from its current state. If you want acknowledgement to disable notifications until the host recovers, check the Sticky Acknowledgement checkbox. Contacts for this host will receive a notification about the acknowledgement, so they are aware that someone is working on the problem. Additionally, a comment will also be added to the host. Make sure to enter your name and fill in a brief description of what you are doing in the comment field. If you would like the host comment to remain once the acknowledgement is removed, check the Persistent Comment checkbox. If you do not want an acknowledgement notification sent out to the appropriate contacts, uncheck the Send Notification checkbox.'); ?>"></i></h2>
        <input type="hidden" class="cmd-type" value="">
        <table class="table table-condensed table-no-border table-auto-width">
            <tr>
                <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" id="sticky_ack" value="1" <?php echo is_checked($adefault_sticky_acknowledgment, 1); ?>> <?php echo _('Sticky Acknowledgement'); ?>
                    </label>
                    <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Sticky Acknowledgement'); ?>" data-content="<?php echo _('If you want acknowledgement to disable notifications until the host recovers, check the Sticky Acknowledgement checkbox.'); ?>"></i>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" id="send_notification" value="1" <?php echo is_checked($adefault_send_notification, 1); ?>> <?php echo _('Send Notification'); ?>
                    </label>
                    <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Send Notification'); ?>" data-content="<?php echo _('If you do not want an acknowledgement notification sent out to the appropriate contacts, uncheck the Send Notification checkbox.'); ?>"></i>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="checkbox">
                    <label>
                        <input type="checkbox" class="persistent" value="1" <?php echo is_checked($adefault_persistent_comment, 1); ?>> <?php echo _('Persistent Comment'); ?>
                    </label>
                    <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Persistent Comment'); ?>" data-content="<?php echo _('Make sure to enter your name and fill in a brief description of what you are doing in the comment field. If you would like the host comment to remain once the acknowledgement is removed, check the Persistent Comment checkbox.'); ?>"></i>
                </td>
            </tr>
            <tr>
                <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
            </tr>
            <tr>
                <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                <td><input type="text" class="form-control com_data req" style="width: 360px;" value="<?php echo _('Problem has been acknowledged'); ?>"></td>
            </tr>
        </table>
        <button type="button" class="btn btn-sm btn-primary submit-add-ack"><?php echo _('Submit'); ?></button>
        <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
    </div>

    <div style="float: left;">
        <div class="infotable_title"><?php echo _("Quick Actions"); ?></div>
        <table class="table table-condensed table-striped table-bordered table-no-margin">
            <thead>
            </thead>
            <tbody>
            <tr>
                <td>
                    <!-- dynamic entries-->
                    <ul class="quickactions dynamic">
                        <?php

                        $args = array(
                            "hostname" => $host,
                            "host_id" => $host_id,
                            "display" => "simple",
                        );

                        // build args for javascript
                        $n = 0;
                        $jargs = "{";
                        foreach ($args as $var => $val) {
                            if ($n > 0)
                                $jargs .= ", ";
                            $jargs .= "\"$var\" : \"$val\"";
                            $n++;
                        }
                        $jargs .= "}";

                        $id = "host_state_quick_actions_" . random_string(6);
                        $output = '
    <div class="host_state_quick_actions" id="' . $id . '">
    ' . xicore_ajax_get_host_status_quick_actions_html($args) . '
    </div><!--host_state_quick_actions-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_host_status_quick_actions_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
                        ?>
                        <?php echo $output; ?>
                    </ul>

                    <!-- other entries-->
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="clear"></div>

    <div style="float: left; margin-top: 15px;"><!--comments-->
        <?php

        $args = array(
            "hostname" => $host,
            "host_id" => $host_id,
            "display" => "simple",
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"$var\" : \"$val\"";
            $n++;
        }
        $jargs .= "}";

        $id = "host_comments_" . random_string(6);
        $output = '
    <div class="host_comments" id="' . $id . '">
    ' . xicore_ajax_get_host_comments_html($args) . '
    </div><!--service_host-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_host_comments_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
        ?>
        <?php echo $output; ?>
    </div>
    <!--comments-->


    </div>
    <!-- overview tab -->

    <!-- performance graphs tab -->
    <div id="tab-perfgraphs" class="ui-tabs-hide">

        <?php //draw_service_detail_links($host,$service);?>
        <div class="statusdetail_panelspacer"></div>

        <div class="stausdetail_chart_timeframe_selector"<?php if (!use_2014_features()) {
            echo ' style="display:none;"';
        } ?>>
            <?php echo _('Graph Timeframe'); ?>:
            <select id="perfdata-timeframe-select" class="form-control condensed">
                <option value="0"><?php echo _('Last 4 Hours'); ?></option>
                <option value="1" selected><?php echo _('Last 24 Hours'); ?></option>
                <option value="2"><?php echo _('Last 7 Days'); ?></option>
                <option value="3"><?php echo _('Last 30 Days'); ?></option>
                <option value="4"><?php echo _('Last 365 Days'); ?></option>
            </select>
        </div>

        <?php
        $args = array(
            "hostname" => $host,
            "host_id" => $host_id,
        );

        // build args for javascript
        $n = 0;
        $jargs = "{";
        foreach ($args as $var => $val) {
            if ($n > 0)
                $jargs .= ", ";
            $jargs .= "\"" . htmlentities($var) . "\" : \"" . htmlentities($val) . "\"";
            $n++;
        }
        $jargs .= "}";
        ?>


        <script type="text/javascript">
            var host_perfgraphs_panel_displayed = false;
            var host_perfgraphs_panel_throbber = $("#servicedetails-perfgraphs-panel-content").html();

            $(document).ready(function () {
                
                var locationObj = window.location;
                if (locationObj.hash == "#tab-perfgraphs") {
                    //alert('tab-perfgraphs');
                    load_perfgraphs_panel();
                }

                var tabContainers = $('#tabs > div');
                $('#tabs ul.tabnavigation a').click(function () {
                    //alert(this.hash + " selected");
                    if (this.hash == "#tab-perfgraphs")
                        load_perfgraphs_panel();
                    return false;
                });

                // Timeframe selection
                $("#perfdata-timeframe-select").change(function () {
                    host_perfgraphs_panel_displayed = false;
                    $("#hostdetails-perfgraphs-panel-content").html(host_perfgraphs_panel_throbber);
                    load_perfgraphs_panel();
                });

            });

            function load_perfgraphs_panel() {

                if (host_perfgraphs_panel_displayed == true) {
                    return;
                }
                host_perfgraphs_panel_displayed = true;

                // Load default time settings
                <?php if (use_2014_features()) { ?>
                var view = $("#perfdata-timeframe-select option:selected").val();
                <?php } else { ?>
                view = 1;
                <?php } ?>

                var optsarr = {
                    "func": "get_host_detail_perfgraphs_panel",
                    "args": <?php echo $jargs;?>
                }

                optsarr.args.view = view;
                var opts = array2json(optsarr);
                var panel = $('#hostdetails-perfgraphs-panel-content');
                var thepanel = panel[0];
                get_ajax_data_innerHTML("getxicoreajax", opts, true, thepanel);
            }

        </script>

        <div id="hostdetails-perfgraphs-panel-content">
            <i class="fa fa-spinner fa-spin"></i> <?php echo _("Loading performance graphs..."); ?>
        </div>

        <?php
        ?>
    </div>
    <!-- performance graphs tab -->

    <!-- advanced tab -->
    <?php
    if (is_advanced_user()) {
        ?>
        <div id="tab-advanced" class="ui-tabs-hide">

            <div class="statusdetail_panelspacer"></div>

            <div style="float: left; margin-bottom: 25px;"><!--state info-->
                <?php

                $args = array(
                    "hostname" => $host,
                    "host_id" => $host_id,
                    "display" => "advanced",
                );

                // build args for javascript
                $n = 0;
                $jargs = "{";
                foreach ($args as $var => $val) {
                    if ($n > 0)
                        $jargs .= ", ";
                    $jargs .= "\"$var\" : \"$val\"";
                    $n++;
                }
                $jargs .= "}";

                $id = "host_state_info_" . random_string(6);

                $statusdetail_id = $id;
                $statusdetail_jargs = $jargs;

                $output = '
    <div class="host_state_info" id="' . $id . '">
    ' . xicore_ajax_get_host_status_detailed_info_html($args) . '
    </div><!--host_state_info-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_host_status_detailed_info_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
                ?>
                <?php echo $output; ?>
            </div>
            <!--state info-->

            <div style="float: left;">
                <?php

                $args = array(
                    "hostname" => $host,
                    "host_id" => $host_id,
                    "display" => "all",
                );

                // build args for javascript
                $n = 0;
                $jargs = "{";
                foreach ($args as $var => $val) {
                    if ($n > 0)
                        $jargs .= ", ";
                    $jargs .= "\"$var\" : \"$val\"";
                    $n++;
                }
                $jargs .= "}";

                $id = "advanced_hoststatus_attributes_" . random_string(6);
                $output = '
    <div class="advanced_hoststatus_attributes" id="' . $id . '">
    ' . xicore_ajax_get_host_status_attributes_html($args) . '
    </div><!--advanced_hoststatus_attributes-->
    <script type="text/javascript">
    $(document).ready(function(){
            
        $("#' . $id . '").everyTime(10*1000, "timer-' . $id . '", function(i) {
        var optsarr = {
            "func": "get_host_status_attributes_html",
            "args": ' . $jargs . '
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
            ';
                ?>
                <?php echo $output; ?>
            </div>

            <script type="text/javascript">
            $(document).ready(function() {

                $('.submit-comment').click(function() {
                    var error = 0;
                    $('#comment .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 com_author: $('#com_author').val(),
                                 com_data: $('#comment .com_data').val() }

                    if ($('#comment .persistent').is(':checked')) {
                        args.persistent = 'on';
                    }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#comment').hide();
                        clear_whiteout();
                        $('#comment .persistent').prop('checked', true);
                        $('#comment .com_data').val('');
                    });

                });

                $('.submit-schedule-downtime').click(function() {
                    var error = 0;
                    $('#schedule-downtime .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 com_author: $('#com_author').val(),
                                 com_data: $('#schedule-downtime .com_data').val(),
                                 trigger: $('#trigger').val(),
                                 start_time: $('#startdateBox').val(),
                                 end_time: $('#enddateBox').val(),
                                 fixed: $('#fixed').val(),
                                 hours: parseInt($('#flexible-hours').val()),
                                 minutes: parseInt($('#flexible-minutes').val()),
                                 childoptions: $('#childoptions').val() }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#schedule-downtime').hide();
                        clear_whiteout();
                        $('#schedule-downtime .com_data').val('');
                    });

                });

                $('.submit-passive-check').click(function() {
                    var error = 0;
                    $('#passive-check .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 plugin_state: $('#plugin_state').val(),
                                 plugin_output: $('#plugin_output').val(),
                                 performance_data: $('#perfdata').val() }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#passive-check').hide();
                        clear_whiteout();

                        $('#plugin_state').val(0);
                        $('#plugin_output').val('');

                        // Force reload of status in the Advanced tab
                        setTimeout(function() {
                            var optsarr = {
                                "func": "get_host_status_detailed_info_html",
                                "args": <?php echo $statusdetail_jargs; ?>
                            }
                            var opts = array2json(optsarr);
                            get_ajax_data_innerHTML("getxicoreajax", opts, true, '#<?php echo $statusdetail_id; ?>');
                        }, 500);
                    });

                });

                $('.submit-delay-notification').click(function() {
                    var error = 0;
                    $('#delay-notification .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 not_dly: $('#not_dly').val() }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#delay-notification').hide();
                        clear_whiteout();
                        $('#not_dly').val('0');
                    });

                });

                $('.submit-custom-notification').click(function() {
                    var error = 0;
                    $('#custom-notification .req').each(function(k, i) {
                        if ($(i).val() == '') {
                            error++;
                        }
                    });

                    if (error) {
                        alert("<?php echo encode_form_val(_('Please fill out all required fields.')); ?>"); 
                        return;
                    }

                    var args = { cmd_typ: $(this).parent('div').find('.cmd-type').val(),
                                 cmd_mod: 2,
                                 nsp: '<?php echo get_nagios_session_protector_id(); ?>',
                                 host: $('#host').val(),
                                 com_author: $('#com_author').val(),
                                 com_data: $('#custom-notification .com_data').val() }

                    if ($('#forced').is(':checked')) {
                        args.force_notification = 'on';
                    }

                    if ($('#broadcast').is(':checked')) {
                        args.broadcast_notification = 'on';
                    }

                    // Send the cmd & data to Core
                    $.post('<?php echo get_base_url(); ?>includes/components/nagioscore/ui/cmd.php', args, function(d) {
                        $('#custom-notification').hide();
                        clear_whiteout();
                        $('#custom-notification .com_data').val('');
                        $('#forced').prop('checked', false);
                        $('#broadcast').prop('checked', false);
                    });

                });

                // Check Date range accuracy
                $('#startdateBox').change(function() {
                    var start_input = $('#startdateBox');
                    var end_input = $('#enddateBox');
                    var startdate_tp = start_input.datetimepicker('getDate');
                    var enddate_tp = end_input.datetimepicker('getDate');

                    dstartdate = Date.parse(startdate_tp)/1000;
                    denddate = Date.parse(enddate_tp)/1000;

                    if (dstartdate > denddate) {
                        var new_ntp = startdate_tp;
                        new_ntp.setHours(startdate_tp.getHours() + 2);
                        end_input.datetimepicker('setDate', new_ntp);
                    }
                });

            });
            </script>

            <div style="float: left; margin-left: 25px;">

                <?php
                if ($auth_command) {
                    ?>
                    <div class="infotable_title"><?php echo _("Commands"); ?></div>

                    <table class="table table-condensed table-striped table-bordered">
                        <tbody>

                        <?php
                        $urlbase = get_base_url() . "includes/components/nagioscore/ui/cmd.php?cmd_typ=";
                        $urlmod = "&host=" . urlencode($host);
                        
                        // initialze some stuff we'll use a few times...
                        $multicmd = array(
                            "command" => COMMAND_NAGIOSCORE_SUBMITCOMMAND,
                            "multi_cmd" => array(
                                
                                )
                        );
                        $multicmd['multi_cmd'][]["command_args"] = array(
                            "cmd" => NAGIOSCORE_CMD_SCHEDULE_FORCED_HOST_CHECK,
                            "host_name" => $host,
                            "start_time" => time()
                        );
                        $multicmd['multi_cmd'][]["command_args"] = array(
                            "cmd" => NAGIOSCORE_CMD_SCHEDULE_FORCED_HOST_SVC_CHECKS,
                            "host_name" => $host,
                            "start_time" => time()
                        );
                        
                        ?>
                        <?php
                        if ($xml && intval($xml->hoststatus->problem_acknowledged) == 1) {
                            ?>
                            <tr>
                                <td>
                                    <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_REMOVE_HOST_ACKNOWLEDGEMENT . $urlmod, "noack.gif", "Remove problem acknowledgement"); ?>
                                    <a class="cmdlink" data-modal="remove-ack" data-cmd-type="<?php echo NAGIOSCORE_CMD_REMOVE_HOST_ACKNOWLEDGEMENT; ?>"><img src="<?php echo theme_image('ack_remove.png'); ?>"><?php echo _('Remove acknowledgement'); ?></a>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>

                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_ADD_HOST_COMMENT . $urlmod, "comment.gif", _("Add Comment")); ?>
                                <a class="cmdlink" data-modal="comment" data-cmd-type="<?php echo NAGIOSCORE_CMD_ADD_HOST_COMMENT; ?>"><img src="<?php echo theme_image('comment_add.png'); ?>"><?php echo _('Add comment'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_SCHEDULE_HOST_DOWNTIME . $urlmod, "downtime.gif", _("Schedule downtime")); ?>
                                <a class="cmdlink" data-modal="schedule-downtime" data-cmd-type="<?php echo NAGIOSCORE_CMD_SCHEDULE_HOST_DOWNTIME; ?>"><img src="<?php echo theme_image('time_add.png'); ?>"><?php echo _('Schedule downtime'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_SCHEDULE_HOST_SVC_DOWNTIME . $urlmod, "downtime.gif", _("Schedule downtime for all services on this host")); ?>
                                <a class="cmdlink" data-modal="schedule-downtime" data-cmd-type="<?php echo NAGIOSCORE_CMD_SCHEDULE_HOST_SVC_DOWNTIME; ?>"><img src="<?php echo theme_image('time_add.png'); ?>"><?php echo _('Schedule downtime for all services on this host'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php echo get_host_detail_command_link($multicmd, "arrow_refresh.png", _("Forced immediate check for host and all services")); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_PROCESS_HOST_CHECK_RESULT . $urlmod, "passiveonly.gif", _("Submit passive check result")); ?>
                                <a class="cmdlink" data-modal="passive-check" data-cmd-type="<?php echo NAGIOSCORE_CMD_PROCESS_HOST_CHECK_RESULT; ?>"><img src="<?php echo theme_image('passiveonly.png'); ?>"><?php echo _('Submit passive check result'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_SEND_CUSTOM_HOST_NOTIFICATION . $urlmod, "notify.gif", _("Send custom notification")); ?>
                                <a class="cmdlink" data-modal="custom-notification" data-cmd-type="<?php echo NAGIOSCORE_CMD_SEND_CUSTOM_HOST_NOTIFICATION; ?>"><img src="<?php echo theme_image('transmit_go.png'); ?>"><?php echo _('Send custom notification'); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php //show_object_command_link($urlbase . NAGIOSCORE_CMD_DELAY_HOST_NOTIFICATION . $urlmod, "delay.gif", _("Delay next notification")); ?>
                                <a class="cmdlink" data-modal="delay-notification" data-cmd-type="<?php echo NAGIOSCORE_CMD_DELAY_HOST_NOTIFICATION; ?>"><img src="<?php echo theme_image('transmit_blue.png'); ?>"><?php echo _('Delay next notification'); ?></a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                <?php
                }
                ?>

                <div class="xi-modal hide" id="remove-ack">
                    <h2><?php echo _('Remove Acknowledgement'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Remove Acknowledgement'); ?>" data-content="<?php echo _('This command is used to remove an acknowledgement for a particular host problem. Once the acknowledgement is removed, notifications may start being sent out about the host problem.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-remove-ack"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="comment">
                    <h2><?php echo _('Add Comment'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Add Comment'); ?>" data-content="<?php echo _('This command is used to add a comment for the specified service. If you work with other administrators, you may find it useful to share information about a host or service that is having problems if more than one of you may be working on it. If you do not check the <strong>persistent</strong> option, the comment will automatically be deleted the next time Nagios is restarted.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" class="persistent" value="on" checked>
                                    <?php echo _('Persistent'); ?>
                                </label>
                                <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Persistent'); ?>" data-content="<?php echo _('If you do not check the <strong>persistent</strong> option, the comment will automatically be deleted the next time Nagios is restarted.'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_data req" style="width: 360px;"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-comment"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="schedule-downtime">
                    <h2>
                        <span id="sd-title"><?php echo _('Schedule Downtime'); ?></span>
                        <i class="fa fa-question-circle fa-14 pop hide" id="sd-info" style="margin-left: 5px;" data-title="<?php echo _('Schedule Downtime'); ?>" data-content="<?php echo _('This command is used to schedule downtime for a particular host. During the specified downtime, Nagios will not send notifications out about the host. When the scheduled downtime expires, Nagios will send out notifications for this host as it normally would. Scheduled downtimes are preserved across program shutdowns and restarts. Both the start and end times should be specified in the following format:'). ' ' . $dfs[$dformat] . '. ' ._('If you select the fixed option, the downtime will be in effect between the start and end times you specify. If you do not select the fixed option, Nagios will treat this as flexible downtime. Flexible downtime starts when the host goes down or becomes unreachable (sometime between the start and end times you specified) and lasts as long as the duration of time you enter. The duration fields do not apply for fixed downtime.'); ?>"></i>
                        <i class="fa fa-question-circle fa-14 pop hide" id="sd-all-info" style="margin-left: 5px;" data-title="<?php echo _('Schedule Downtime'); ?>" data-content="<?php echo _('This command is used to schedule downtime for all services on a particular host. During the specified downtime, Nagios will not send notifications out about the host. Normally, a host in downtime will not send alerts about any services in a failed state. This option will explicitly set downtime for all services for this host. When the scheduled downtime expires, Nagios will send out notifications for this host as it normally would. Scheduled downtimes are preserved across program shutdowns and restarts. Both the start and end times should be specified in the following format:'). ' ' . $dfs[$dformat] . '. ' ._('If you select the fixed option, the downtime will be in effect between the start and end times you specify. If you do not select the fixed option, Nagios will treat this as flexible downtime. Flexible downtime starts when the host goes down or becomes unreachable (sometime between the start and end times you specified) and lasts as long as the duration of time you enter. The duration fields do not apply for fixed downtime.'); ?>"></i>
                    </h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_data req" style="width: 360px;"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Triggered By'); ?></td>
                            <td>
                                <?php

                                $get = '&username=' . $_SESSION['username'] . '&ticket=' . get_user_attr(0, 'backend_ticket');
                                $url = get_base_url()."includes/components/xicore/downtime.php?cmd=getdowntimes" . $get;

                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $url);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                $output = curl_exec($ch);
                                curl_close($ch);

                                $objs = json_decode($output);
                                ?>
                                <select class="form-control" id="trigger">
                                    <option value="0" selected><?php echo _('None'); ?></option>
                                    <?php
                                    $options = '';
                                    $s = '';
                                    $h = '';

                                    foreach ($objs as $obj) { 
                                        if (!empty($obj->service_description)) {
                                            $s .= '<option value="' . $obj->downtime_id . '">' . $obj->host_name . ' - '. $obj->service_description .' @ ' . get_datetime_string($obj->start_time/1000) . ' (ID ' . $obj->downtime_id . ')</option>';
                                        } else {
                                            $h .= '<option value="' . $obj->downtime_id . '">' . $obj->host_name . ' @ ' . get_datetime_string($obj->start_time/1000) . ' (ID ' . $obj->downtime_id . ')</option>';
                                        }
                                    }

                                    if (!empty($h)) {
                                        $options .= '<optgroup label="'._('Host Downtimes').'">' . $h . '</optgroup>';
                                    }

                                    if (!empty($s)) {
                                        $options .= '<optgroup label="'._('Service Downtimes').'">' . $s . '</optgroup>';
                                    }

                                    echo $options;
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Type'); ?></td>
                            <td>
                                <select id="fixed" class="form-control">
                                    <option value="1"><?php echo _('Fixed'); ?></option>
                                    <option value="0"><?php echo _('Flexible'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="flexible-box" class="hide">
                            <td><?php echo _('Duration'); ?></td>
                            <td>
                                <input type="text" class="form-control" style="width: 40px;" id="flexible-hours" value="2"> Hours
                                <input type="text" class="form-control" style="width: 40px; margin-left: 5px;" id="flexible-minutes" value="0"> Minutes
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _("Start Time"); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td>
                                <input class="form-control datetimepicker req" type="text" id='startdateBox' name="startdate" value="<?php echo get_datetime_string(time()); ?>" size="18">
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _("End Time"); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td>
                                <input class="form-control datetimepicker req" type="text" id='enddateBox' name="enddate" value="<?php echo get_datetime_string(strtotime('now + 2 hours')); ?>" size="18">
                            </td>
                        </tr>
                        <tr id="childoptions-box">
                            <td><?php echo _('Child Hosts'); ?></td>
                            <td>
                                <select id="childoptions" class="form-control req">
                                    <option value="0"><?php echo _('Do nothing with child hosts'); ?></option>
                                    <option value="1"><?php echo _('Schedule triggered downtime for all child hosts'); ?></option>
                                    <option value="2"><?php echo _('Schedule non-triggered downtime for all child hosts'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-schedule-downtime"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="passive-check">
                    <h2><?php echo _('Submit Passive Check Result'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" data-title="<?php echo _('Submit Passive Check Result'); ?>" data-content="<?php echo _('This command is used to submit a passive check result for a particular host.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Check Result'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td>
                                <select class="form-control state-select-service req" id="plugin_state">
                                    <option value="0" class="ok"><?php echo _('UP'); ?></option>
                                    <option value="1" class="critical"><?php echo _('DOWN'); ?></option>
                                    <option value="2" class="unknown"><?php echo _('UNREACHABLE'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Check Output'); ?> <i class="fa fa-asterisk tt-bind" title="<?php echo _('Required'); ?>" style="color: red;"></i></td>
                            <td><input type="text" class="form-control req" id="plugin_output" style="width: 360px;"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Performance Data'); ?></td>
                            <td><input type="text" class="form-control" id="perfdata" style="width: 360px;"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-passive-check"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="custom-notification">
                    <h2><?php echo _('Send Custom Notification'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Send Custom Notification'); ?>" data-content="<?php echo _('This command is used to send a custom notification about the specified host. Useful in emergencies when you need to notify admins of an issue regarding a monitored system or service. Custom notifications normally follow the regular notification logic in Nagios. Selecting the Forced option will force the notification to be sent out, regardless of the time restrictions, whether or not notifications are enabled, etc. Selecting the Broadcast option causes the notification to be sent out to all normal (non-escalated) and escalated contacts. These options allow you to override the normal notification logic if you need to get an important message out.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" id="forced" value="1"> <?php echo _('Forced'); ?>
                                </label>
                                <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Forced'); ?>" data-content="<?php echo _('Selecting the Forced option will force the notification to be sent out, regardless of the time restrictions, whether or not notifications are enabled, etc.'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="checkbox">
                                <label>
                                    <input type="checkbox" id="broadcast" value="1"> <?php echo _('Broadcast'); ?>
                                </label>
                                <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Broadcast'); ?>" data-content="<?php echo _('Selecting the Broadcast option causes the notification to be sent out to all normal (non-escalated) and escalated contacts.'); ?>"></i>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo _('Author'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_author req" readonly value="<?php echo get_user_attr(0, 'name'); ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Comment'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control com_data req" style="width: 360px;"></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-custom-notification"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="xi-modal hide" id="delay-notification">
                    <h2><?php echo _('Delay Next Notification'); ?> <i class="fa fa-question-circle fa-14 pop" style="margin-left: 5px;" title="<?php echo _('Delay Next Notification'); ?>" data-content="<?php echo _('This command is used to delay the next problem notification that is sent out for the specified host. The notification delay will be disregarded if the host changes state before the next notification is scheduled to be sent out. This command has no effect if the host is currently UP.'); ?>"></i></h2>
                    <input type="hidden" class="cmd-type" value="">
                    <table class="table table-condensed table-no-border table-auto-width">
                        <tr>
                            <td><?php echo _('Host Name'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" class="form-control req" readonly value="<?php echo $host; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo _('Notification Delay'); ?> <i class="fa fa-asterisk fa-req tt-bind" title="<?php echo _('Required'); ?>"></i></td>
                            <td><input type="text" id="not_dly" class="form-control req" value="0" style="width: 40px;"> <?php echo _('minutes from now'); ?></td>
                        </tr>
                    </table>
                    <button type="button" class="btn btn-sm btn-primary submit-delay-notification"><?php echo _('Submit'); ?></button>
                    <button type="button" class="btn btn-sm btn-default cancel"><?php echo _('Cancel'); ?></button>
                </div>

                <div class="infotable_title"><?php echo _("More Options"); ?></div>
                <ul>
                    <li>
                        <a href="<?php echo get_host_status_detail_link($host, "core"); ?>"><?php echo _("View in Nagios Core"); ?></a>
                    </li>
                </ul>

            </div>

        </div>
        <!-- advanced tab -->
    <?php
    }
    ?>

    <!-- configure tab -->
    <?php
    if ($show_configure == true) {
        ?>
        <div id="tab-configure" class="ui-tabs-hide">
            <?php

            echo "<p>";
            echo "<img src='" . theme_image("editsettings.png") . "' style='float: left; margin-right: 10px;'>";

            $url = get_base_url() . "config/configobject.php?host=" . urlencode($host) . "&return=hostdetail";
            echo "<a href='" . $url . "'>" . _('Re-configure this host') . "</a>";

            /*
            if(is_host_configurable($host)==true){
                $url=get_base_url()."config/modifyobject.php?host=".$host."&return=hostdetail";
                echo "<a href='".$url."'>Modify the settings for this host</a>";
                }
            else{
                if(is_advanced_user()==true){
                    $url=get_base_url()."includes/components/ccm/xi-index.php";
                    echo "<a href='".$url."' target='_top'>Enter the advanced configuration manager</a> to modify the settings for this host.";
                    }
                else
                    echo "Contact your Nagios administrator to modify the settings for this host.";
                }
            */

            echo "<br>";
            echo "<p>";
            echo "<img src='" . theme_image("cross.png") . "' style='float: left; margin-right: 10px;'>";

            /*
            if(can_host_be_deleted($host)==true){
                $url=get_base_url()."config/deleteobject.php?host=".$host."&return=hostdetail";
                echo "<a href='".$url."'>Delete this host</a>";
                }
            else{
                if(is_advanced_user()==true){
                    $url=get_base_url()."includes/components/ccm/xi-index.php";
                    echo "<a href='".$url."' target='_top'>Enter the advanced configuration manager</a> to delete this host.";
                    }
                else
                    echo "Contact your Nagios administrator to delete this host.";
                }
            */
            $url = get_base_url() . "config/deleteobject.php?host=" . urlencode($host) . "&return=hostdetail";
            echo "<a href='" . $url . "'>" . _('Delete this host') . "</a>";

            ?>

        </div>
    <?php
    }
    ?>
    <!-- configure tab -->


    <?php
    // custom tabs
    foreach ($customtabs as $ct) {
        $id = grab_array_var($ct, "id");
        $content = grab_array_var($ct, "content");
        echo "<div id='tab-custom-" . $id . "' class='ui-tabs-hide'>" . $content . "</div>";
    }
    ?>

    </div>

    <?php
    do_page_end(true);
}


/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_host_detail_perfgraphs_panel($args = null)
{

    if ($args == null)
        $args = array();

    $output = "";
    $p_output = "";
    $g_output = "";

    $host = grab_array_var($args, "hostname");
    $host_id = grab_array_var($args, "host_id");

    // Time settings
    $view = grab_array_var($args, "view");

    $have_chart = false;
    $current_graph = 0;

    if (perfdata_chart_exists($host, "") == true) {

        $current_graph++;
        $have_chart = true;

        // primary host performance graph
        $p_output .= "<div class='serviceperfgraphcontainer pd-container'>";
        $dargs = array(
            DASHLET_ADDTODASHBOARDTITLE => "Add This Performance Graph To A Dashboard",
            DASHLET_ARGS => array(
                "host_id" => $host_id,
                "hostname" => $host,
                "servicename" => "_HOST_",
                "source" => 1,
                "view" => $view,
                //"start" => $start,
                //"end" => $end,
                //"width" => "",
                //"height" => "",
                "mode" => PERFGRAPH_MODE_HOSTOVERVIEW,
            ),
            DASHLET_TITLE => $host . " Host Performance Graph",
        );

        ob_start();
        display_dashlet("xicore_perfdata_chart", "", $dargs, DASHLET_MODE_OUTBOARD);
        $p_output .= ob_get_clean();

        $p_output .= "</div>";
    }

    // get services
    $args = array("cmd" => "getservicestatus",
        "host_name" => $host,
        "brevity" => 2);

    $xml = get_backend_xml_data($args);

    // loop over all services
    foreach ($xml->servicestatus as $svc) {

        $hostname = strval($svc->host_name);
        $servicename = strval($svc->name);

        // skip this if the service doesn't have any perfdata
        if (perfdata_chart_exists($hostname, $servicename) == false)
            continue;

        $current_graph++;

        // limit to 5 graphs
        if ($current_graph > 5)
            break;

        $have_chart = true;

        $sources = perfdata_get_service_sources($hostname, $servicename);
        foreach ($sources as $s) {

            $p_output .= "<div class='serviceperfgraphcontainer pd-container'>";

            $dargs = array(
                DASHLET_ADDTODASHBOARDTITLE => _("Add This Performance Graph To A Dashboard"),
                DASHLET_ARGS => array(
                    "hostname" => $hostname,
                    "servicename" => $servicename,
                    "service_id" => strval($svc->service_id),
                    //"source" => 1,
                    "source" => $s["id"], // fix by Antal Ferenc 01/27/2010
                    "sourcename" => $s["name"],
                    "sourcetemplate" => $s["template"],
                    "view" => $view,
                    //"start" => $start,
                    //"end" => $end,
                    "width" => "250",
                    //"height" => "50",
                    "mode" => PERFGRAPH_MODE_GOTOSERVICEDETAIL,
                ),
                DASHLET_TITLE => htmlentities($host) . " " . htmlentities($service) . " " . _('Performance Graph') . "",
            );

            ob_start();
            display_dashlet("xicore_perfdata_chart", "", $dargs, DASHLET_MODE_OUTBOARD);
            $ob = ob_get_clean();

            $p_output .= $ob . "</div>";

            // Break if we are using the HC theme so we don't display multiples of the same graphs on service detail page -SW
            if (get_option("perfdata_theme", 1) != 0)
                break;
        }
    }

    // get sources for host
    $sources = perfdata_get_service_sources($host, "_HOST_", false);

    foreach ($sources as $s) {
        if (!gauges_dashlet_gauge_exists($host, "_HOST_", $s["name"]))
            continue;

        $g_output .= "<div class='hostgaugecontainer'>\n";

        $dargs = array(
            DASHLET_ADDTODASHBOARDTITLE => _("Add to Dashboard"),
            DASHLET_ARGS => array(
                "host" => $host,
                "service" => "_HOST_",
                "ds" => $s["name"],
            ),
            DASHLET_TITLE => htmlentities($host) . " " . htmlentities($s["name"]) . " " . _('Gauge') . "",
        );

        ob_start();
        display_dashlet("gauges", "", $dargs, DASHLET_MODE_OUTBOARD);
        $ob = ob_get_clean();

        $g_output .= $ob . "</div>";
    }

    // Add graphs and guages to output
    $output .= '<div><div></div>';
    $output .= $p_output;
    $output .= '</div><div>';
    $output .= $g_output;
    $output .= '</div><div class="clear"></div>';

    if ($have_chart == true) {
        $output .= '
        <script type="text/javascript">
        $(document).ready(function(){
            // initialize javascript for dashifying performance graphs
            init_dashlet_js();  
            });
        </script>
        ';
    }

    if ($have_chart == false) {
        $output .= _("No performance graphs were found for this host.");
    } else if ($current_graph > 5) {
        $output .= "<br style='clear:both'/><a href='" . get_base_url() . "perfgraphs/?host=" . urlencode($hostname) . "&mode=1'>" . _('More Performance Graphs') . "</a>";
    }

    return $output;
}

/**
 * @param null $args
 *
 * @return string
 */
function xicore_ajax_get_service_detail_perfgraphs_panel($args = null)
{

    if ($args == null)
        $args = array();

    $output = "";
    $p_output = "";
    $g_output = "";

    $host = grab_array_var($args, "hostname");
    $service = urldecode(grab_array_var($args, "servicename"));
    $service_id = grab_array_var($args, "service_id");
    $view = grab_array_var($args, "view", 1);

    if (perfdata_chart_exists($host, $service) == true) {

        $sources = perfdata_get_service_sources($host, $service);
        foreach ($sources as $s) {

            $p_output .= "<div class='serviceperfgraphcontainer pd-container'>";

            $dargs = array(
                DASHLET_ADDTODASHBOARDTITLE => _("Add to Dashboard"),
                DASHLET_ARGS => array(
                    "hostname" => $host,
                    "servicename" => $service,
                    "service_id" => $service_id,
                    //"source" => 1,
                    "source" => $s["id"], // fix by Antal Ferenc 01/27/2010
                    "sourcename" => $s["name"],
                    "sourcetemplate" => $s["template"],
                    "view" => $view,
                    //"start" => $start,
                    //"end" => $end,
                    "width" => "250",
                    //"height" => "50",
                    "mode" => PERFGRAPH_MODE_GOTOSERVICEDETAIL,
                ),
                DASHLET_TITLE => htmlentities($host) . " " . htmlentities($service) . " " . _('Performance Graph') . "",
            );

            ob_start();
            display_dashlet("xicore_perfdata_chart", "", $dargs, DASHLET_MODE_OUTBOARD);
            $ob = ob_get_clean();

            $p_output .= $ob . "</div>";

            // Break if we are using the HC theme so we don't display multiples of the same graphs on service detail page -SW
            if (get_option("perfdata_theme", 1) != 0)
                break;
        }

        // Start gauges if using 2014 features
        if (use_2014_features()) {
            $sources = perfdata_get_service_sources($host, $service, false);
            foreach ($sources as $s) {
                if (!gauges_dashlet_gauge_exists($host, $service, $s["name"]))
                    continue;

                $g_output .= "<div class='servicegaugecontainer'>\n";

                $dargs = array(
                    DASHLET_ADDTODASHBOARDTITLE => _("Add This Gauge To A Dashboard"),
                    DASHLET_ARGS => array(
                        "host" => $host,
                        "service" => $service,
                        "ds" => $s["name"],
                    ),
                    DASHLET_TITLE => htmlentities($host) . " " . htmlentities($service) . " " . htmlentities($s["name"]) . " " . _('Gauge') . "",
                );

                ob_start();
                display_dashlet("gauges", "", $dargs, DASHLET_MODE_OUTBOARD);
                $ob = ob_get_clean();

                $g_output .= $ob . "</div>";
            }
        }

        // Add graphs and guages to output
        $output .= '<div>';
        $output .= $p_output;
        $output .= '</div><div>';
        $output .= $g_output;
        $output .= '<div class="clear"></div></div>';

        $output .= '
        <script type="text/javascript">
        $(document).ready(function(){
            // initialize javascript for dashifying performance graphs
            init_dashlet_js();  
            });
        </script>
        ';

    } else {
        $output .= _("No performance graphs were found for this service. If you have just started monitoring this object then it may take up to 15 minutes for the performance graphs to appear.");
    }

    return $output;
}

?>