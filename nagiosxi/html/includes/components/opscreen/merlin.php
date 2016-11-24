<?php

$opscreen_component_xi = true; // should opscreen use Nagios XI code?


// Nagios XI includes/prereqs
if ($opscreen_component_xi == true) {

    require_once(dirname(__FILE__) . '/../../common.inc.php');

    // Initialization stuff
    pre_init();
    init_session();

    // Grab GET or POST variables and check pre-reqs
    grab_request_vars();
    check_prereqs();
    check_authentication(false);

    //use variables from config.inc.php
    $host = grab_array_var($cfg['db_info']['ndoutils'], 'dbserver', 'localhost');
    $user = grab_array_var($cfg['db_info']['ndoutils'], 'user', 'ndoutils');
    $db = grab_array_var($cfg['db_info']['ndoutils'], 'db', 'nagios');
    $pw = grab_array_var($cfg['db_info']['ndoutils'], 'pwd', 'n@gweb');

    //echo "HOST $host USER $user DB $db PW $pw<br />";

    $con = mysql_connect($host, $user, $pw) or die("<h3><font color=red>Could not connect to the database!</font></h3>");
    $db = mysql_select_db($db, $con);
    
    $hide_ack_down = get_user_meta(0, 'opscreen_hide_ack_down', 0);
    $hide_soft_states = get_user_meta(0, 'opscreen_hide_soft_states', 0);

}

?>
<div class="dash_unhandled hosts dash">
    <h2><?php 
        if ($hide_ack_down) 
            echo _("Unhandled host problems ");
        else
            echo _("All host problems ");

        if ($hide_soft_states) 
            echo _("| Hiding soft states");
        else
            echo _("| Showing all states");
        ?>
    </h2>

    <div class="dash_wrapper">
        <table class="dash_table">
            <?php
            #ALL down-hosts
            //$query = "select host_name, alias, count(host_name) from host where last_hard_state = 1 and problem_has_been_acknowledged = 0 group by host_name";

            $query = "SELECT obj1.name1 AS host_name, nagios_hosts.alias, current_state, problem_has_been_acknowledged FROM nagios_hoststatus LEFT JOIN nagios_objects AS obj1 ON nagios_hoststatus.host_object_id=obj1.object_id LEFT JOIN nagios_hosts ON nagios_hoststatus.host_object_id=nagios_hosts.host_object_id WHERE current_state!='0' ";

            if ($hide_soft_states) {
                $query .= "AND nagios_hoststatus.current_check_attempt = nagios_hoststatus.max_check_attempts ";
            }

            if ($hide_ack_down) {
                $query .= "AND problem_has_been_acknowledged='0' AND scheduled_downtime_depth='0' ";
            }

            // limit what the user can see
            if ($opscreen_component_xi == true) {
                $args = array(
                    "sql" => $query,
                    "objectauthfields" => array(
                        "nagios_hoststatus.host_object_id",
                    ),
                    "objectauthperms" => P_READ,
                );
                $query = limit_sql_by_authorized_object_ids($args);
            }

            $result = mysql_query($query);
            $save = "";
            $output = "";
            while ($row = mysql_fetch_array($result)) {
                $output .= "<tr class=\"critical\" ><td><a style='color:white' href='../xicore/status.php?show=hostdetail&host=" . urlencode($row[0]) . "'>" . $row[0] . "</a></td><td>" . $row[1] . "</td></tr>";
                $save .= $row[0];
            }
            if ($save) {
                ?>
                <tr class="dash_table_head">
                    <th><?php echo _("Hostname"); ?></th>
                    <th><?php echo _("Alias"); ?></th>
                </tr>
                <?php print $output; ?>
            <?php
            } else {
                print "<tr class=\"ok\"><td>"._("All problem hosts have been acknowledged.")."</td></tr>";
            }
            ?>
        </table>
    </div>
</div>
<div class="dash_tactical_overview tactical_overview hosts dash">
    <h2><?php echo _("Tactical overview"); ?></h2>

    <div class="dash_wrapper">
        <table class="dash_table">
            <tr class="dash_table_head">
                <th><?php echo _("Type"); ?></th>
                <th><?php echo _("Totals"); ?></th>
                <th><?php echo _("Percentage"); ?> %</th>
            </tr>
            <?php
            # number of hosts down
            //$query = "select count(1) as count from host where last_hard_state = 1";
            $query = "SELECT count(*) as total from nagios_hoststatus WHERE current_state!=0";

            if ($hide_soft_states) {
                $query .= " AND nagios_hoststatus.current_check_attempt = nagios_hoststatus.max_check_attempts ";
            }

            // limit what the user can see
            if ($opscreen_component_xi == true) {
                $args = array(
                    "sql" => $query,
                    "objectauthfields" => array(
                        "nagios_hoststatus.host_object_id",
                    ),
                    "objectauthperms" => P_READ,
                );
                $query = limit_sql_by_authorized_object_ids($args);
            }

            //echo "HOSTSDOWNQ: $query<BR>";
            $result = mysql_query($query);
            $row = mysql_fetch_array($result);
            $hosts_down = $row[0];
            //echo "HOSTSDOWN: $hosts_down<BR>";

            # total number of hosts
            //$query = "select count(1) as count from host";
            $query = "SELECT count(*) as total from nagios_hoststatus WHERE 1";
            // limit what the user can see
            if ($opscreen_component_xi == true) {
                $args = array(
                    "sql" => $query,
                    "objectauthfields" => array(
                        "nagios_hoststatus.host_object_id",
                    ),
                    "objectauthperms" => P_READ,
                );
                $query = limit_sql_by_authorized_object_ids($args);
            }
            //echo "HOSTSTOTALQ: $query<BR>";
            $result = mysql_query($query);
            $row = mysql_fetch_array($result);
            $total_hosts = $row[0];
            //echo "HOSTSTOTAL: $total_hosts<BR>";

            $hosts_down_pct = round($hosts_down / $total_hosts * 100, 2);
            $hosts_up = $total_hosts - $hosts_down;
            $hosts_up_pct = round($hosts_up / $total_hosts * 100, 2);

            #### SERVICES
            #
            //$query = "select count(1) as count from service where last_hard_state = 1";
            $query = "SELECT count(*) as total from nagios_servicestatus WHERE current_state!=0";

            if ($hide_soft_states) {
                $query .= " AND nagios_servicestatus.current_check_attempt = nagios_servicestatus.max_check_attempts ";
            }

            // limit what the user can see
            if ($opscreen_component_xi == true) {
                $args = array(
                    "sql" => $query,
                    "objectauthfields" => array(
                        "nagios_servicestatus.service_object_id",
                    ),
                    "objectauthperms" => P_READ,
                );
                $query = limit_sql_by_authorized_object_ids($args);
            }
            //echo "SERVICEDOWNQ: $query<BR>";
            $result = mysql_query($query);
            $row = mysql_fetch_array($result);
            $services_down = $row[0];
            //echo "SERVICESDOWN: $services_down<BR>";

            # total number of hosts
            //$query = "select count(1) as count from service";
            $query = "SELECT count(*) as total from nagios_servicestatus WHERE 1";
            // limit what the user can see
            if ($opscreen_component_xi == true) {
                $args = array(
                    "sql" => $query,
                    "objectauthfields" => array(
                        "nagios_servicestatus.service_object_id",
                    ),
                    "objectauthperms" => P_READ,
                );
                $query = limit_sql_by_authorized_object_ids($args);
            }
            $result = mysql_query($query);
            $row = mysql_fetch_array($result);
            $total_services = $row[0];

            $services_down_pct = round($services_down / $total_services * 100, 2);
            $services_up = $total_services - $services_down;
            $services_up_pct = round($services_up / $total_services * 100, 2);

            ?>
            <tr class="ok total_hosts_up">
                <td><?php echo _("Hosts up"); ?></td>
                <td><?php print $hosts_up ?>/<?php print $total_hosts ?></td>
                <td><?php print $hosts_up_pct ?></td>
            </tr>
            <tr class="critical total_hosts_down">
                <td><?php echo _("Hosts down"); ?></td>
                <td><?php print $hosts_down ?>/<?php print $total_hosts ?></td>
                <td><?php print $hosts_down_pct ?></td>
            </tr>
            <tr class="ok total_services_up">
                <td><?php echo _("Services up"); ?></td>
                <td><?php print $services_up ?>/<?php print $total_services ?></td>
                <td><?php print $services_up_pct ?></td>
            </tr>
            <tr class="critical total_services_down">
                <td><?php echo _("Services down"); ?></td>
                <td><?php print $services_down ?>/<?php print $total_services ?></td>
                <td><?php print $services_down_pct ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="logo">
    <a href="http://www.nagios.com" target="_blank"><img src="images/nagios.png" border="0" alt="Nagios" title="Nagios"></a>
    <br clear="right">

    <div class="logotext"><?php echo _("Operations Screen"); ?></div>
</div>
<div class="clear"></div>
<div class="dash_unhandled_service_problems hosts dash">
    <h2><?php 
        if ($hide_ack_down) 
            echo _("Unhandled service problems");
        else
            echo _("All service problems");

        if ($hide_soft_states) 
            echo _(" | Hiding soft states");
        else
            echo _(" | Showing all states");
        ?>
    </h2>

    <div class="dash_wrapper">
        <table class="dash_table">
            <tr class="dash_table_head">
                <th>
                    <?php echo _("Host"); ?>
                </th>
                <th>
                    <?php echo _("Service"); ?>
                </th>
                <th>
                    <?php echo _("State"); ?>
                </th>
                <th>
                    <?php echo _("Output"); ?>
                </th>
                <th>
                    <?php echo _("Last statechange"); ?>
                </th>
                <th>
                    <?php echo _("Last check"); ?>
                </th>
            </tr>
            <?php
            $query = "SELECT obj1.name1 AS host_name, obj1.name2 AS service_name, nagios_servicestatus.current_state, nagios_servicestatus.last_hard_state, nagios_servicestatus.output, nagios_servicestatus.last_hard_state_change, nagios_servicestatus.last_check, nagios_servicestatus.problem_has_been_acknowledged,
nagios_hosts.address as ha, nagios_hoststatus.problem_has_been_acknowledged AS hack, nagios_services.service_id as sid ,
nagios_servicestatus.servicestatus_id as ssid,
nagios_hosts.host_object_id AS hid
FROM nagios_servicestatus 
LEFT JOIN nagios_objects AS obj1 ON nagios_servicestatus.service_object_id=obj1.object_id 
LEFT JOIN nagios_services ON nagios_servicestatus.service_object_id=nagios_services.service_object_id 
LEFT JOIN nagios_hosts ON nagios_services.host_object_id=nagios_hosts.host_object_id
LEFT JOIN nagios_hoststatus ON nagios_hosts.host_object_id=nagios_hoststatus.host_object_id
WHERE 1 AND nagios_servicestatus.current_state!='0' ";

if ($hide_soft_states) {
    $query .= "AND nagios_servicestatus.current_check_attempt = nagios_servicestatus.max_check_attempts ";
}

if ($hide_ack_down) {
    $query .= "AND nagios_servicestatus.scheduled_downtime_depth='0' AND nagios_servicestatus.problem_has_been_acknowledged='0' AND nagios_hoststatus.problem_has_been_acknowledged='0' AND nagios_hoststatus.last_hard_state='0' AND nagios_hoststatus.current_state='0'";
}

        // limit what the user can see
        if ($opscreen_component_xi == true) {
            $args = array(
                "sql" => $query,
                "objectauthfields" => array(
                    "nagios_servicestatus.service_object_id",
                ),
                "objectauthperms" => P_READ,
            );
            $query = limit_sql_by_authorized_object_ids($args);
        }
        $query .= " ORDER BY obj1.name1 ASC, obj1.name2 ASC";
        $result = mysql_query($query);
        ?>
        <?php
        $save = "";
        while ($row = mysql_fetch_array($result)) {
            $class = "";
            if ($row['current_state'] == 2) {
                $class = "critical";
            } elseif ($row['current_state'] == 1) {
                $class = "warning";
            } else {
                $class = "unknown";
            }
            ?>
            <tr class="<?php print $class ?>">
                <td>
                    <a href="../xicore/status.php?show=hostdetail&host=<?php echo urlencode($row['host_name']); ?>&service=<?php echo urlencode($row['service_name']); ?>&dest=auto"><?php print $row['host_name']; ?></a>
                </td>
                <td>
                    <a href="../xicore/status.php?show=servicedetail&host=<?php echo urlencode($row['host_name']); ?>&service=<?php echo urlencode($row['service_name']); ?>&dest=auto"><?php print $row['service_name']; ?></a>
                </td>
                <td><?php echo $row['current_state']; ?></td>
                <td style="word-break: break-all;"><?php echo $row['output']; ?></td>
                <td class="date date_statechange"><?php echo $row['last_hard_state_change']; /*print date("d-m-Y H:i:s", $row[4])*/ ?></td>
                <td class="date date_lastcheck"><?php echo $row['last_check']; /* print date("d-m-Y H:i:s", $row[5])*/ ?></td>
            </tr>
        <?php
            $save .= $row['host_name'];
        }
        if (empty($save)) {
            print "<tr class=\"ok\"><td colspan='5'>"._("All problem services have been acknowledged.")."</td></tr>";
        }
        ?>
        </table>
    </div>
</div>

