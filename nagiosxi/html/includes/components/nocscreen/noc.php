<?php //noc.php
//
// Nagios XI Operations Center Component
//
// Copyright (c) 2011-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// 

require_once(dirname(__FILE__) . '/../../common.inc.php');

// initialization stuff
pre_init();
// start session
init_session();
// grab GET or POST variables 
grab_request_vars();
// check prereqs
check_prereqs();
// check authentication
check_authentication(false);

    
$host = grab_request_var("host", "");
$service = grab_request_var("service", "");
$hostgroup = grab_request_var("hostgroup", "");
$servicegroup = grab_request_var("servicegroup", "");
$state = grab_request_var("state", "");

$hide_soft = grab_request_var("hide_soft", "");
if ($hide_soft !== "")
     set_user_meta(0, 'nocscreen_hide_soft_states', $hide_soft, false);
else
    $hide_soft = get_user_meta(0, 'nocscreen_hide_soft_states');


$title = "Nagios XI - Operations Center";
?>
<?php
do_page_start(array("page_title" => $title), true);
?>

    <style type='text/css'>

        #content {
            width: 95%;
        }

        #topbar {
            width: 95%;
            height: 65px;
        }

        #leftside {
            margin-left: 10px;
            margin-bottom: o;
            width: 450px;
            float: left;
        }

        #rightside {
            width: auto;
            float: right;
        }

        table {
            margin: 10px;
        }

        .strong {
            font-weight: bold;
        }

        #theheader {
            margin: 3px 10px 3px 10px;
        }

        #lastUpdate {
            color: grey;
            font-size 7pt;
        }

        #servicetable {
            width: 100%;
        }

        #hosttable {
            width: 100%;
        }

        div.duration {
            width: 125px;
        }
        #noc_filter{
            margin: 0;
        }

    </style>
    <script type="text/javascript">
        //dashboard refresh time
        MULTIPLIER = 30; //allow for user defined option in future versions
        $(document).ready(function () {
            //ajax refresh
            noc_load_content();
        });
        //content reloader
        function noc_load_content() {
            var datastring = $("#noc_filter").serialize();
            
            $.ajax({
                    url: 'nocscreenapi.php',
                    data: datastring
                    }).done(function (html) {
                        var d = new Date();
                        $('#lastUpdate').empty();
                        $('#lastUpdate').append(('Last Update: ' + d.toString() ));
                        $('#content').html(html);
            });
            //summary bar
            $.ajax({
                    url: 'nocscreenapi.php?summary=true',
                    data: datastring
                    }).done(function (html) {
                        $('#rightside').html(html);
            });
            //alert(contents);
            setTimeout(noc_load_content, (MULTIPLIER * 1000));  //have this multiplier as a config option
        }

    </script>
<div id='topbar'>
    <div id='leftside'>
        <h4 id='theheader'><img src='/nagiosxi/images/nagiosxi-logo-small.png' height='42' width='100'
                                alt='Nagios XI'/><?php echo _("Operations Center"); ?></h4>

        <div id='lastUpdate'></div>
        
    </div>
    <!-- end leftside -->
    <div id='rightside'></div>
</div>
   <div style="clear: both;">    
    <form id="noc_filter" method="get" action="<?php echo htmlentities($_SERVER["REQUEST_URI"]); ?>">
 
            <!-- options go here... -->
            <?php echo _("Limit To"); ?>&nbsp;

            <select name="host" id="hostList" class="form-control" style="width: 150px;">
                <option value=""><?php echo _("Host"); ?>:</option>
                <?php
                $args = array('brevity' => 1, 'orderby' => 'host_name:a');
                $oxml = get_xml_host_objects($args);
                if ($oxml) {
                    foreach ($oxml->host as $hostobject) {
                        $name = strval($hostobject->host_name);
                        echo "<option value='" . $name . "' " . is_selected($host, $name) . ">$name</option>\n";
                    }
                }
                ?>
            </select>

            <select name="hostgroup" id="hostgroupList" class="form-control" style="width: 150px;">
                <option value=""><?php echo _("Hostgroup"); ?>:</option>
                <?php
                $args = array('orderby' => 'hostgroup_name:a');
                $oxml = get_xml_hostgroup_objects($args);
                if ($oxml) {
                    foreach ($oxml->hostgroup as $hg) {
                        $name = strval($hg->hostgroup_name);
                        echo "<option value='" . $name . "' " . is_selected($hostgroup, $name) . ">$name</option>\n";
                    }
                }
                ?>
            </select>

            <select name="servicegroup" id="servicegroupList" class="form-control" style="width: 150px;">
                <option value=""><?php echo _("Servicegroup"); ?>:</option>
                <?php
                $args = array('orderby' => 'servicegroup_name:a');
                $oxml = get_xml_servicegroup_objects($args);
                if ($oxml) {
                    foreach ($oxml->servicegroup as $sg) {
                        $name = strval($sg->servicegroup_name);
                        echo "<option value='" . $name . "' " . is_selected($servicegroup, $name) . ">$name</option>\n";
                    }
                }
                ?>
            </select>

            <select name="state" id="state" class="form-control">
                <option value=""><?php echo _("Service State"); ?>:</option>
                <?php
                        echo "<option value='1' " . is_selected($state, 1) . ">"._("Warning")."</option>\n";
                        echo "<option value='2' " . is_selected($state, 2) . ">"._("Critical")."</option>\n";
                        echo "<option value='3' " . is_selected($state, 3) . ">"._("Unknown")."</option>\n";
                ?>
            </select>

            <select name="hide_soft" id="hide_soft" class="form-control">
                <option value="0"><?php echo _("State"); ?>:</option>
                <?php
                        echo "<option value='0' " . is_selected($hide_soft, 0) . ">"._("Show All States")."</option>
                              <option value='1' " . is_selected($hide_soft, 1) . ">"._("Hide Soft States")."</option>\n";
                ?>
            </select>

            <input type='submit' class='reporttimesubmitbutton btn btn-xs btn-primary' name='reporttimesubmitbutton'
                   value='<?php echo _("Update"); ?>'>

            <br clear='all'>

            <script type="text/javascript">
                $(document).ready(function () {
                    $('#hostList').searchable({maxMultiMatch: 9999});
                    $('#hostgroupList').searchable({maxMultiMatch: 9999});
                    $('#servicegroupList').searchable({maxMultiMatch: 9999});

                    $('#hostList').change(function () {
                        $('#hostgroupList').val('');
                        $('#servicegroupList').val('');
                    });

                    $('#servicegroupList').change(function () {
                        $('#hostList').val('');
                        $('#hostgroupList').val('');
                    });

                    $('#hostgroupList').change(function () {
                        $('#servicegroupList').val('');
                        $('#hostList').val('');
                    });

                });
            </script>
            </form>
    </div>
    <div clear='all'></div>

<div id='content'></div>


</body>
</html>
