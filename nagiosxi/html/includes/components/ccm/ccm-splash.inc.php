<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  Nagios XI-only splash homepage
//

require_once(dirname(__FILE__) . '/../../common.inc.php');

global $ccmDB;

// Get object xml
$html = "";
$hargs = array('brevity' => 1, 'orderby' => 'host_name:a', 'totals' => 1);
$sargs = array('brevity' => 1, 'orderby' => 'service_description:a', 'totals' => 1);
$hgargs = array('orderby' => 'hostgroup_name:a', 'totals' => 1);
$sgargs = array('orderby' => 'servicegroup_name:a', 'totals' => 1);
$caargs = array('orderby' => 'contact_name:a', 'contact_name' => 'ne:xi_default_contact', 'totals' => 1);
$cggargs = array('orderby' => 'contactgroup_name:a', 'totals' => 1);
$hdargs = array('orderby' => 'servicegroup_name:a', 'totals' => 1);
$sdargs = array('orderby' => 'servicegroup_name:a', 'totals' => 1);

// Get CCM database object counts
$host_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_host`");
if (isset($host_count)) {
    $host_count = get_formatted_number(intval($host_count[0]['count']));
}

$service_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_service`");
if (isset($service_count)) {
    $service_count = get_formatted_number(intval($service_count[0]['count']));
}

$hostgroup_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_hostgroup`");
if (isset($hostgroup_count)) {
    $hostgroup_count = get_formatted_number(intval($hostgroup_count[0]['count']));
}

$servicegroup_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_servicegroup`");
if (isset($servicegroup_count)) {
    $servicegroup_count = get_formatted_number(intval($servicegroup_count[0]['count']));
}

$contact_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_contact`");
if (isset($contact_count)) {
    $contact_count = get_formatted_number(intval($contact_count[0]['count']));
}

$contactgroup_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_contactgroup`");
if (isset($contactgroup_count)) {
    $contactgroup_count = get_formatted_number(intval($contactgroup_count[0]['count']));
}

$command_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_command`");
if (isset($command_count)) {
    $command_count = get_formatted_number(intval($command_count[0]['count']));
}

$hostdependency_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_hostdependency`");
if (isset($hostdependency_count)) {
    $hostdependency_count = get_formatted_number(intval($hostdependency_count[0]['count']));
}

$servicedependency_count = $ccmDB->query("SELECT COUNT(*) as count FROM `tbl_servicedependency`");
if (isset($servicedependency_count)) {
    $servicedependency_count = get_formatted_number(intval($servicedependency_count[0]['count']));
}

// Retrieve data for recent changes table
$host_change_sql = $ccmDB->query("SELECT * FROM `tbl_host` WHERE `config_id`=1 ORDER BY `last_modified` DESC LIMIT 5");
$service_change_sql = $ccmDB->query("SELECT * FROM `tbl_service` WHERE `config_id`=1 ORDER BY `last_modified` DESC LIMIT 5");
$host_change_sql_na = $ccmDB->query("SELECT * FROM `tbl_host` WHERE `active`=0 ORDER BY `last_modified` DESC LIMIT 5");
$service_change_sql_na = $ccmDB->query("SELECT * FROM `tbl_service` WHERE `active`=0 ORDER BY `last_modified` DESC LIMIT 5");

// snapshots function for recent table
$snapshots = get_nagioscore_config_snapshots();


// Show config status icon
$config_status = "";
$ac_needed = get_option("ccm_apply_config_needed", 0);
if ($ac_needed != 0) {
    $config_status = "
        <div class='alert alert-top alert-danger'>
            <span style='display: inline-block; padding-top:3px;'>
                <i class='fa fa-asterisk l fa-14'></i> <b>"._('Configuration not applied.')."</b>
                "._('There are changes to the database configuration, apply configuration needed.')."
            </span>
            <a class='btn btn-xs btn-danger' style='margin-left: 15px; vertical-align: top;' href='/nagiosxi/includes/components/nagioscorecfg/applyconfig.php?cmd=confirm'>"._("Apply Configuration Now")." <i class='fa r fa-chevron-right'></i></a>
        </div>";
}

// create sunburst
$html = "

<div id='contentWrapper' class='ccm-splash-wrapper'>
    <div class='ccm-top'>
        " . $config_status . "
    </div>
    <div class='row-fluid' style='margin: 0 -10px;'>
        <div class='col-md-6 col-lg-6 col-xl-4'>
            <div class='ccm-splash-container'>
                <div class='ccm-splash-title'>
                    <i class='fa fa-bar-chart'></i>&nbsp;&nbsp;<span>"._('CCM Object Summary')."</span>
                </div>
                <div class='well'>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=host'>
                            <i class='fa fa-file-o fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $host_count . "</span><span class='ccm-stat-text'>" . _("Hosts") .  "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=hostgroup'>
                            <i class='fa fa-folder-open-o fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $hostgroup_count . "</span><span class='ccm-stat-text'>" . _("Host Groups") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=service'>
                            <i class='fa fa-file-o fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $service_count . "</span><span class='ccm-stat-text'>" . _("Services") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=servicegroup'>
                            <i class='fa fa-folder-open-o fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $servicegroup_count . "</span><span class='ccm-stat-text'>" . _("Service Groups") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=contact'>
                            <i class='fa fa-user fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $contact_count . "</span><span class='ccm-stat-text'>" . _("Contacts") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=contactgroup'>
                            <i class='fa fa-users fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $contactgroup_count . "</span><span class='ccm-stat-text'>" . _("Contact Groups") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=command'>
                            <i class='fa fa-terminal fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $command_count . "</span><span class='ccm-stat-text'>" . _("Commands") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=hostdependency'>
                            <i class='fa fa-sitemap fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $hostdependency_count . "</span><span class='ccm-stat-text'>" . _("Host Dependencies") . "</span>
                        </a>
                    </div>
                    <div class='ccm-stat-box last'>
                        <a class='btn btn-lg btn-primary' href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=servicedependency'>
                            <i class='fa fa-sitemap fa-fw ccm-icon-style'></i><span class='ccm-stat-detail'>" . $servicedependency_count . "</span><span class='ccm-stat-text'>" . _("Service Dependencies") . "</span>
                        </a>
                    </div>
                    <div class='clear'></div>
                </div>
            </div>
        </div>
        <div class='col-md-6 col-lg-6 col-xl-4'>
            <div class='ccm-splash-container'>
                <div class='ccm-splash-title'>
                    <i class='fa fa-database'></i>&nbsp;&nbsp;Recent Snapshots
                </div>
                <div class='well'>
                    <table class='table table-bordered table-striped table-condensed table-no-margin'>
                        <thead>
                        <tr>
                            <th>" . _("Date") . "</th>
                            <th>" . _("Snapshot Result") . "</th>
                            <th>" . _("Actions") . "</th>
                        </tr>
                        </thead>
                        <tbody>";
                        // fill snapshot table
                        $x = 0;
                        $y = 0;
                        $archives = 0;
                        foreach ($snapshots as $snapshot) {

                            if ($snapshot["archive"]) {
                                $archives++;
                                continue;
                            }                                    

                            $resultstring = "Config Ok";
                            $rowclass = "";
                            $qstring = "result=ok&archive=0";
                            if ($snapshot["error"] == true) {
                                $resultstring = "Config Error";
                                $rowclass = "ccm-alert";
                                $qstring = "result=error&archive=0";
                            }

                            if ($resultstring == "Config Ok") {
                                $x++;
                            } else {
                                $y++;
                            }

                            if (($x + $y) > 12)
                                break;

                            $html .= "<tr class=" . $rowclass . ">";
                            $html .= "<td nowrap>" . $snapshot["date"] . "</td>";
                            $html .= "<td>" . $resultstring . "</td>";
                            $html .= "<td nowrap>";
                            $html .= "<a href='" . get_base_url() . "admin/coreconfigsnapshots.php?download=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('package_go.png')."' class='tt-bind' title='"._('Download')."'></a> ";
                            $html .= "<a href='" . get_base_url() . "admin/coreconfigsnapshots.php?view=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('page_white.png')."' class='tt-bind' title='"._('View')."'></a> ";
                            if ($snapshot["error"] == true) {
                                $html .= "<a href='" . get_base_url() . "admin/coreconfigsnapshots.php?delete=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('cross.png')."' class='tt-bind' title='"._('Delete')."'></a> ";
                            } else {
                                if (use_2012_features() == true)
                                    $html .= "<a href='" . get_base_url() . "admin/coreconfigsnapshots.php?restore=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('arrow_undo.png')."' class='tt-bind' title='"._('Restore')."'></a> ";
                                if (use_2014_features() == true)
                                    $html .= "<a href='" . get_base_url() . "admin/coreconfigsnapshots.php?doarchive=" . $snapshot["timestamp"] . "&" . $qstring . "'><img src='".theme_image('folder_go.png')."' class='tt-bind' title='"._('Archive')."'></a>";
                            }
                            $html .= "</td>";
                            $html .= "</tr>";
                        }
                    $html .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='col-md-12 col-lg-12 col-xl-4'>
            <div class='ccm-splash-container'>
                <div class='ccm-splash-title'>
                    <i class='fa fa-gears'></i>&nbsp;&nbsp;<span>Recently Changed Hosts and Services</span>
                </div>
                <div class='well'>
                    <table class='table table-condensed table-bordered table-striped'>
                        <thead>
                        <tr>
                            <th class='ccm-table-adjust-1'>" . _("Host Name") . "</th>
                            <th>" . _("Modified Time") . "</th>
                        </tr>
                        </thead>
                        <tbody>";
                        // applied changes - host
                        // we have data? make table
                        if ($host_change_sql) {
                            $x = 0;

                            foreach ($host_change_sql as $a) {
                                $x++;
                                if ($x > 5)
                                    break;

                                $html .= "<tr>";
                                $html .= "<td class='ccm-table-adjust-1'><a href='" . get_base_url() . "includes/components/ccm/?cmd=modify&type=host&id=" . $a['id'] . "&page=1&returnUrl=index.php'>" . $a['host_name'] . "</a></td>";
                                $html .= "<td nowrap><span class='notificationtime'>" . $a['last_modified'] . "</span></td>";
                                $html .= "</tr>";
                            }
                        } else {
                            $html .= "<tr><td colspan='7'>" . _("No matching results found.") . "</td></tr>\n";
                        } // end changes table
                    $html .="
                        </tbody>
                    </table>
                    
                    <table class='table table-condensed table-bordered table-striped table-no-margin'>
                        <thead>
                            <tr>
                                <th>" . _("Service Name") . "</th>
                                <th>" . _("Config Name") . "</th>
                                <th>" . _("Modified Time") . "</th>
                            </tr>
                        </thead>
                        <tbody>";
                        // applied changes - service
                        // we have data? make table
                        if ($service_change_sql) {
                            $x = 0;

                            foreach ($service_change_sql as $a) {
                                $x++;
                                if ($x > 5)
                                    break;

                                $html .= "<tr>";
                                $html .= "<td><a href='" . get_base_url() . "includes/components/ccm/?cmd=modify&type=service&id=" . $a['id'] . "&page=1&returnUrl=index.php'>" . $a['service_description'] . "</a></td>";
                                $html .= "<td><a href='" . get_base_url() . "includes/components/ccm/?cmd=view&type=service&search=&name_filter=" . $a['config_name'] . "&page=1'>" . $a['config_name'] . "</a></td>";
                                $html .= "<td nowrap><span class='notificationtime'>" . $a['last_modified'] . "</span></td>";
                                $html .= "</tr>";
                            }
                        } else {
                            $html .= "<tr><td colspan='7'>" . _("No matching results found.") . "</td></tr>\n";
                        } // end changes table
                    $html .="
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='clear'></div>
    </div>
</div>";