<?php
//
// XI Status Functions
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__).'/../componenthelper.inc.php');
include_once(dirname(__FILE__).'/../nagioscore/coreuiproxy.inc.php');

// Initialization stuff
pre_init();
init_session(true);

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication();

route_request();

function route_request()
{
    $view = grab_request_var("show","");

    switch ($view)
    {
        case "process":
            show_monitoring_process();
            break;
        case "performance":
            show_monitoring_performance();
            break;
        case "comments":
            show_comments();
            break;
        case "services":
            show_services();
            break;
        case "hosts":
            show_hosts();
            break;
        case "hostgroups":
            show_hostgroups();
            break;
        case "servicegroups":
            show_servicegroups();
            break;
        case "servicedetail":
            show_service_detail();
            break;
        case "hostdetail":
            show_host_detail();
            break;
        case "tac":
            show_tac();
            break;
        case "outages":
            show_network_outages();
            break;
        case "map":
            show_status_map();
            break;
        default:
            show_services();
            break;
    }
}


function show_comments()
{
    global $request;
    
    $search = trim(grab_request_var("search", ""));
    $args = array();
    
    if ($search) {
        $args["comment_data"] = "lk:" . $search . ";host_name=lk:" . $search .";service_description=lk:". $search .";author_name=lk:". $search;
    }
    
    do_page_start(array("page_title" => _('Acknowledgements and Comments')), true);
?>

<h1><?php echo _('Acknowledgements and Comments'); ?></h1>

<form method="get" action="<?php echo htmlentities($_SERVER["REQUEST_URI"]); ?>">
    <div style="position: absolute; right: 60px; top: 20px;">
        <input type="hidden" name="show" value="comments">
        <input type="text" size="15" name="search" id="searchBox" value="<?php echo encode_form_val($search); ?>" placeholder="<?php echo _("Search..."); ?>" class="form-control">
    </div>
</form>

<div style="margin-top: 20px;">
<?php
    $dargs = array(
        DASHLET_ARGS => $args
    );
    display_dashlet("xicore_comments", "", $dargs, DASHLET_MODE_OUTBOARD);
?>
</div>

<?php
    do_page_end(true);
}
    
function show_services()
{

    licensed_feature_check(true, true);
    
    $show = grab_request_var("show", "services");
    $host = grab_request_var("host", "");
    $hostgroup = grab_request_var("hostgroup", "");
    $servicegroup = grab_request_var("servicegroup", "");
    $hostattr = grab_request_var("hostattr", 0);
    $serviceattr = grab_request_var("serviceattr", 0);
    $hoststatustypes = grab_request_var("hoststatustypes", 0);
    $servicestatustypes = grab_request_var("servicestatustypes", 0);
    
    $search = trim(grab_request_var("search", ""));

    // Fix for "all" options
    if ($hostgroup == "all")
        $hostgroup = "";
    if ($servicegroup == "all")
        $servicegroup = "";
    if ($host == "all")
        $host = "";

    // tps#7852 fix for search -bh
    if (empty($search) && !empty($host)) {
        $search = $host;
    }

    // If user was searching for a host, and no matching services are found, redirect them to the host status screen
    if (!empty($search)) {
        $backendargs = array();
        $backendargs["cmd"] = "getservicestatus";
        $backendargs["host_name"] = "lk:".$search.";name=lk:".$search.";host_address=lk:".$search.";host_display_name=lk:".$search.";display_name=lk:".$search;
        $backendargs["combinedhost"] = true;  // Need combined view for host search fields
        $backendargs["limitrecords"] = false;  // Don't limit records
        $backendargs["totals"] = 1; // Only get recordcount       

        // Get result from backend
        $xml = get_xml_service_status($backendargs);

        // How many total services do we have?
        $total_records = 0;
        if ($xml) {
            $total_records = intval($xml->recordcount);
        }

        // Redirect to host status screen
        if ($total_records == 0) {
            header("Location: status.php?show=hosts&search=".urlencode($search)."&noservices=1");
        }
    }

    $target_text = _("All services");
    if ($hostgroup != "") {
        $trans_hostgroup = _("Hostgroup");
        $target_text = "$trans_hostgroup: <b>".encode_form_val($hostgroup)."</b>";
    }
    if (!empty($servicegroup)) {
        $trans_servicegroup = _("Servicegroup");
        $target_text = "$trans_servicegroup: <b>".encode_form_val($servicegroup)."</b>";
    }
    if (!empty($host)) {
        $trans_host = _("Host");
        $target_text = "$trans_host: <b>".encode_form_val($host)."</b>";
    }
    
    do_page_start(array("page_title" => _("Service Status")), true);
?>

<div style="float: left;">
    <h1><?php echo _("Service Status");?></h1>
    <div class="servicestatustar_"><?php echo $target_text;?></div>
</div>

<?php
    // $t1=get_timer();
?>
    
<div style="float: right; margin-top: 10px;">
<div style="float: left; margin-right: 25px;">
<?php
    $dargs = array(
        DASHLET_ARGS => array(
            "host" => $host,
            "hostgroup" => $hostgroup,
            "servicegroup" => $servicegroup,
            "hostattr" => $hostattr,
            "serviceattr" => $serviceattr,
            "hoststatustypes" => $hoststatustypes,
            "servicestatustypes" => $servicestatustypes,
            "show" => $show
        )
    );
    display_dashlet("xicore_host_status_summary", "", $dargs, DASHLET_MODE_OUTBOARD);
?>
</div>
<?php
    //$t2=get_timer();
?>
<div style="float: left;">
<?php
    display_dashlet("xicore_service_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
<?php
    //$t3=get_timer();
?>
</div>  

<br clear="all">

<?php 
    draw_servicestatus_table(); 
?>
<?php
    //$t4=get_timer();
?>

<?php
/*
    echo "T1-T2: ".get_timer_diff($t1,$t2)."<BR>";
    echo "T2-T3: ".get_timer_diff($t2,$t3)."<BR>";
    echo "T3-T4: ".get_timer_diff($t3,$t4)."<BR>";
*/
?>

<?php
    do_page_end(true);
    }


/**
 * @param bool   $error
 * @param string $msg
 */
function show_hosts($error=false,$msg=""){

    // check licensing
    licensed_feature_check(true,true);
    
    $show=grab_request_var("show","hosts");
    $host=grab_request_var("host","");
    $hostgroup=grab_request_var("hostgroup","");
    $servicegroup=grab_request_var("servicegroup","");
    $hostattr=grab_request_var("hostattr",0);
    $serviceattr=grab_request_var("serviceattr",0);
    $hoststatustypes=grab_request_var("hoststatustypes",0);
    $servicestatustypes=grab_request_var("servicestatustypes",0);
    
    $noservices=grab_request_var("noservices",0);
    
    // no services found during search - user was redirected
    if($noservices==1){
        $error=false;
        $msg=_("No matching services found - showing matching hosts instead.");
        }

    // fix for "all" options
    if($hostgroup=="all")
        $hostgroup="";
    if($servicegroup=="all")
        $servicegroup="";
    if($host=="all")
        $host="";

    $target_text=_("All hosts");
    if($hostgroup!="")
        $target_text=""._('Hostgroup').": <b>".encode_form_val($hostgroup)."</b>";
    if($servicegroup!="")
        $target_text=""._('Servicegroup').": <b>".encode_form_val($servicegroup)."</b>";
    if($host!="")
        $target_text=""._('Host').": <b>".encode_form_val($host)."</b>";
    
    do_page_start(array("page_title"=>_("Host Status")),true);

?>
<div style="float: left;">
    <h1><?php echo _("Host Status");?></h1>
    <div class="hoststatustar_"><?php echo $target_text;?></div>
</div>

<div style="float: right; margin-top: 10px;">
<div style="float: left; margin-right: 25px;">
<?php
    $dargs=array(
        DASHLET_ARGS => array(
            "host" => $host,
            "hostgroup" => $hostgroup,
            "servicegroup" => $servicegroup,
            "hostattr" => $hostattr,
            "serviceattr" => $serviceattr,
            "hoststatustypes" => $hoststatustypes,
            "servicestatustypes" => $servicestatustypes,
            "show" => $show,
            ),
        );
    display_dashlet("xicore_host_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
<div style="float: left;">
<?php
    display_dashlet("xicore_service_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
</div>  

<?php
    if(is_array($msg) || $msg!=""){
        echo "<br clear='all'>";
        display_message($error,false,$msg);
    }
?>

<br clear="all">

<div style="clear: both;">
<?php 
    draw_hoststatus_table(); 
?>
</div>

<?php
    do_page_end(true);
    }
    
    
function show_hostgroups(){

    // check licensing
    licensed_feature_check(true,true);
    
    // grab request vars
    $hostgroup=grab_request_var("hostgroup","all");
    $style=grab_request_var("style","overview");

    // performance optimization
    $opt=get_option("use_unified_hostgroup_screens");
    if($opt==1)
        header("Location: ".get_base_url()."includes/components/nagioscore/ui/status.php?hostgroup=".urlencode($hostgroup)."&style=".$style);


    do_page_start(array("page_title"=>_("Host Group Status")),true);
    
    $target_text="";
    switch($style){
        case "summary":
            $target_text=_("Summary View");
            break;
        case "overview":
            $target_text=_("Overview");
            break;
        case "grid":
            $target_text=_("Grid View");
            break;
        default:
            break;
        }

?>
<div style="float: left;">
    <h1><?php echo _("Host Group Status");?></h1>
    <div class="servicestatustar_"><?php echo $target_text;?></div>
    
    <?php draw_hostgroup_viewstyle_links($hostgroup);?>
</div>

<div style="float: right; margin-top: 10px;">
<div style="float: left; margin-right: 25px;">
<?php
    $dargs=array(
        DASHLET_ARGS => array(
            "hostgroup" => $hostgroup,
            "show" => "services",
            ),
        );
    display_dashlet("xicore_host_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
<div style="float: left;">
<?php
    display_dashlet("xicore_service_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
</div>  

<div style="clear: both; margin-bottom: 35px;"></div>

<div class="fl">
<?php
    if($style=="summary"){
        $dargs=array(
            DASHLET_ARGS => array(
                "style" => $style,
                ),
            );
        display_dashlet("xicore_hostgroup_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
        }
    
    // overview or grid styles
    else{
        $args=array(
            "orderby" => "hostgroup_name:a",
            );
        if($hostgroup!="" && $hostgroup!="all")
            $args["hostgroup_name"]=$hostgroup;
        $xml=get_xml_hostgroup_objects($args);
        
        if($xml){
            foreach($xml->hostgroup as $hg){
                $hgname=strval($hg->hostgroup_name);
                $hgalias=strval($hg->alias);
                //echo "HG: $hgname<BR>";
                
                echo "<div class=\"hostgroup" . htmlentities($style, ENT_COMPAT, 'UTF-8') . "-hostgroup\">";
                $dargs=array(
                    DASHLET_ARGS => array(
                        "hostgroup" => $hgname,
                        "hostgroup_alias" => $hgalias,
                        "style" => $style,
                        ),
                    );
                display_dashlet("xicore_hostgroup_status_".$style,"",$dargs,DASHLET_MODE_OUTBOARD);
                echo "</div>";
                }
            }
        }
?>
</div>
<div class="clear"></div>

<?php
    do_page_end(true);
}


function show_servicegroups(){
    
    // check licensing
    licensed_feature_check(true,true);
    
    // grab request vars
    $servicegroup=grab_request_var("servicegroup","all");
    $style=grab_request_var("style","overview");
    
    // performance optimization
    $opt=get_option("use_unified_servicegroup_screens");
    if($opt==1)
        header("Location: ".get_base_url()."includes/components/nagioscore/ui/status.php?servicegroup=".urlencode($servicegroup)."&style=".$style);


    do_page_start(array("page_title"=>_("Service Group Status")),true);
    
    $target_text="";
    switch($style){
        case "summary":
            $target_text=_("Summary View");
            break;
        case "overview":
            $target_text=_("Overview");
            break;
        case "grid":
            $target_text=_("Grid View");
            break;
        default:
            break;
        }

?>
<div style="float: left;">
    <h1><?php echo _("Service Group Status");?></h1>
    <div class="servicestatustar_"><?php echo $target_text;?></div>
    
    <?php draw_servicegroup_viewstyle_links($servicegroup);?>
</div>

<div style="float: right; margin-top: 10px;">
<div style="float: left; margin-right: 25px;">
<?php
    $dargs=array(
        DASHLET_ARGS => array(
            "servicegroup" => $servicegroup,
            "show" => "services",
            ),
        );
    display_dashlet("xicore_host_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
<div style="float: left;">
<?php
    display_dashlet("xicore_service_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);
?>
</div>
</div>  

<div style="clear: both; margin-bottom: 35px;"></div>
<?php
    if($style=="summary"){
        $dargs=array(
            DASHLET_ARGS => array(
                "style" => $style,
                ),
            );
        display_dashlet("xicore_servicegroup_status_summary","",$dargs,DASHLET_MODE_OUTBOARD);

        }
    
    // overview or grid styles
    else{
        $args=array(
            "orderby" => "servicegroup_name:a",
            );
        if($servicegroup!="" && $servicegroup!="all")
            $args["servicegroup_name"]=$servicegroup;
        $xml=get_xml_servicegroup_objects($args);
        
        if($xml){
            foreach($xml->servicegroup as $sg){
                $sgname=strval($sg->servicegroup_name);
                $sgalias=strval($sg->alias);
                
                echo "<div class=servicegroup".htmlentities($style)."-servicegroup>";
                $dargs=array(
                    DASHLET_ARGS => array(
                        "servicegroup" => $sgname,
                        "servicegroup_alias" => $sgalias,
                        "style" => $style,
                        ),
                    );
                display_dashlet("xicore_servicegroup_status_".$style,"",$dargs,DASHLET_MODE_OUTBOARD);
                echo "</div>";
                }
            }
        }
?>
<br clear="all">
<?php
    do_page_end(true);
    }
    

    
function show_tac(){

    do_page_start(array("page_title"=>_("Tactical Overview")),true);

?>
    <h1><?php echo _("Tactical Overview");?></h1>

<?php
    do_page_end(true);
    }
    
    
function show_open_problems(){

    do_page_start(array("page_title"=>_("Open Problems")),true);

?>
    <h1><?php echo _("Open Problems");?></h1>

<?php
    do_page_end(true);
    }
    
    
function show_host_problems(){

    do_page_start(array("page_title"=>_("Host Problems")),true);

?>
    <h1><?php echo _("Host Problems");?></h1>

<?php
    do_page_end(true);
    }
    
    
function show_service_problems(){

    do_page_start(array("page_title"=>_("Service Problems")),true);

?>
    <h1><?php echo _("Service Problems");?></h1>

<?php
    do_page_end(true);
    }
    
    
function show_network_outages(){

    do_page_start(array("page_title"=>_("Network Outages")),true);

?>
    <h1><?php echo _("Network Outages");?></h1>
    
<?php
/*
    $url="outages.php?noheader";

    $args=array(
        "url" => $url,
        );

    // build args for javascript
    $n=0;
    $jargs="{";
    foreach($args as $var => $val){
        if($n>0)
            $jargs.=", ";
        $jargs.="\"$var\" : \"$val\"";
        $n++;
        }
    $jargs.="}";

    $id="nagioscore_cgi_output_".random_string(6);
    $output='
    <div class="nagioscore_cgi_output" id="'.$id.'">
    '.xicore_ajax_get_nagioscore_cgi_html($args).'
    </div><!--nagioscore_cgi_output-->
    <script type="text/javascript">
    $(document).ready(function(){
        $("#'.$id.'").everyTime(15*1000, "timer-'.$id.'", function(i) {
        var optsarr = {
            "func": "get_nagioscore_cgi_html",
            "args": '.$jargs.'
            }
        var opts=array2json(optsarr);
        get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
        
    });
    </script>
    ';
    */
?>
    <!--
    <div class="networkoutages">
    <?php //echo $output;?>
    </div>
    //-->
    
    <?php //echo xicore_ajax_get_network_outages_html();?>
    
    <div style="float: left;">
<?php
    $dargs=array(
        DASHLET_ARGS => array(
            ),
        );
    display_dashlet("xicore_network_outages","",$dargs,DASHLET_MODE_OUTBOARD);
?>
    </div>

<?php
    do_page_end(true);
}


function show_status_map()
{
    global $request;
    do_page_start(array( "page_title" => _("Legacy Network Status Map")), true);
?>

    <style type="text/css">
    #mappage { padding: 0; margin: 0; }
    #mappage h1 { font-family: verdana, serif; }
    </style>

    <h1><?php echo _("Legacy Network Status Map"); ?></h1>

    <?php draw_statusmap_viewstyle_links(); ?>
    
<?php
    // Add request arguments to the url
    $url = "statusmap.php?noheader";
    foreach ($request as $var => $val) {
        if ($var == "show" || empty($var)) {
            continue;
        }
        $url .= "&$var=$val";
    }
    $args = array("url" => encode_form_val($url));
    
    // Build args for JS
    $jargs = json_encode($args);

    $id = "nagioscore_cgi_output_" . random_string(6);
    $output = '
    <div class="nagioscore_cgi_output" id="' . $id . '">
    ' . xicore_ajax_get_nagioscore_cgi_html($args) . '
    </div><!--nagioscore_cgi_output-->
    <script type="text/javascript">
    $(document).ready(function() {
        $("#' . $id . '").everyTime(30*1000, "timer-' . $id . '", function(i) {
            var optsarr = {
                "func": "get_nagioscore_cgi_html",
                "args": ' . $jargs . '
            }
            var opts=array2json(optsarr);
            get_ajax_data_innerHTML("getxicoreajax",opts,true,this);
        });
    });
    </script>';
?>

    <div class="statusmap">
        <?php echo $output;?>
    </div>

<?php
    do_page_end(true);
}


function show_not_authorized_for_object_page()
{
    do_page_start(array("page_title"=>_("Not Authorized")), true);
?>
    <h1><?php echo _("Not Authorized");?></h1>

    <?php echo _("You are not authorized to view the requested object, or the object does not exist.");?>
    
<?php
    do_page_end(true);
    exit();
}


function show_monitoring_process()
{
    do_page_start(array("page_title"=>_("Monitoring Process")), true);
?>

    <h1><?php echo _("Monitoring Process");?></h1>

    <div style="float: left; margin: 0 30px 30px 0;">
<?php
    display_dashlet("xicore_monitoring_process","",null,DASHLET_MODE_OUTBOARD);
?>
    </div>
    
    <div style="float: left; margin: 0 30px 30px 0;">
<?php
    display_dashlet("xicore_eventqueue_chart","",null,DASHLET_MODE_OUTBOARD);
?>
    </div>
    
<?php
    do_page_end(true);
}

    
function show_monitoring_performance()
{
    do_page_start(array("page_title"=>_("Monitoring Performance")), true);
?>

    <h1><?php echo _("Monitoring Performance");?></h1>

    <div style="float: left; margin: 0 30px 30px 0;">
<?php
    display_dashlet("xicore_monitoring_stats","",null,DASHLET_MODE_OUTBOARD);
?>
    </div>
    
    <div style="float: left; margin: 0 30px 30px 0;">
<?php
    display_dashlet("xicore_monitoring_perf","",null,DASHLET_MODE_OUTBOARD);
?>
    </div>
    

    
<?php
    do_page_end(true);
    }
    


?>