<?php
//
// Copyright (c) 2008-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__) . '/../includes/common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables, check prereqs, and authorization
grab_request_vars();
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You are not authorized to access this feature.  Contact your Nagios XI administrator for more information, or to obtain access to this feature.");
    exit();
}

// Route incoming request
route_request();

function route_request()
{
    global $request;

    if (isset($request['update'])) {
        do_update_user();
    } else if (isset($request['delete']) || (isset($request['multiButton']) && $request['multiButton'] == 'delete')) {
        do_delete_user();
    } else if (isset($request['unlock']) || (isset($request['multiButton']) && $request['multiButton'] == 'unlock')) {
        do_unlock_user();
    } else if (isset($request['toggle_active'])) {
        do_toggle_active_user();
    } else if (isset($request['edit'])) {
        show_edit_user();
    } else if (isset($request['clone'])) {
        show_clone_user();
    } else if (isset($request['doclone'])) {
        do_clone_user();
    } else if (isset($request['masquerade'])) {
        do_masquerade();
    } else if (isset($request['send_emails'])) {
        do_email_users();
    } else {
        show_users();
    }
    exit;
}

// Email all users
function do_email_users()
{
    $email_subject = grab_request_var("email_subject", "");
    $email_message = grab_request_var("email_message", "");
    $user_emails = grab_request_var("email_user_emails", "");

    // Check if emailing all users or not
    if ($user_emails == "all") {

        // Do a quick query to grab the users's email addresses
        $sql = "SELECT * FROM xi_users WHERE TRUE ORDER BY xi_users.email ASC";
        $rs = exec_sql_query(DB_NAGIOSXI, $sql);

        $user_emails = array();
        foreach ($rs as $user) {
            $user_emails[] = $user['email'];
        }

    } else {
        $user_emails = explode(",", $user_emails);
    }

    // Verify that we have stuff to send...
    $error = false;
    if (empty($email_subject) || empty($email_message)) {
        $error = true;
        $msg = _("Failed to send email. No subject or message was given.");
    }

    // Verify that we have some user emails
    if (empty($user_emails[0]) && count($user_emails) == 1) {
        $error = true;
        $msg = _("Failed to send email. No users selected to send to.");
    }

    // Use this for debug output in PHPmailer log
    $debugmsg = "";

    // Set where email is coming from for PHPmailer log
    $send_mail_referer = "admin/users.php > Email All Users";

    // Send to each user individually
    foreach ($user_emails as $email) {

        // Send email to user...
        $opts = array("to" => $email,
            "from" => "",
            "subject" => $email_subject,
            "message" => $email_message);
        send_email($opts, $debugmsg, $send_mail_referer);

    }

    if (!$error)
        $msg = "Email(s) have been sent.";
    show_users($error, $msg);
}

/**
 * Shows the table list view of all the users for the XI system.
 *
 * @param bool   $error
 * @param string $msg
 */
function show_users($error = false, $msg = "")
{
    global $request;
    global $db_tables;
    global $sqlquery;
    global $cfg;

    // Generate messages...
    if ($msg == "") {
        if (isset($request["useradded"])) {
            $msg = _("User Added.");
        }
        if (isset($request["userupdated"])) {
            $msg = _("User Updated.");
        }
        if (isset($request["usercloned"])) {
            $msg = _("User cloned.");
        }
    }

    // Defaults
    $sortby = "username";
    $sortorder = "asc";
    $page = 1;
    $records = 5;

    // Default to use saved options
    $s = get_user_meta(0, 'user_management_options');
    $saved_options = unserialize($s);
    if (is_array($saved_options)) {
        if (isset($saved_options["sortby"])) {
            $sortby = $saved_options["sortby"];
        }
        if (isset($saved_options["sortorder"])) {
            $sortorder = $saved_options["sortorder"];
        }
        if (isset($saved_options["records"])) {
            $records = $saved_options["records"];
        }
        if (array_key_exists("search", $saved_options)) {
            $search = $saved_options["search"];
        }
    }

    // Get options
    $sortby = grab_request_var("sortby", $sortby);
    $sortorder = grab_request_var("sortorder", $sortorder);
    $page = grab_request_var("page", $page);
    $records = grab_request_var("records", $records);
    $user_id = grab_request_var("user_id", array());
    $search = grab_request_var("search", $search);
    if ($search == _("Search...")) {
        $search = "";
    }

    // Save options for later
    $saved_options = array(
        "sortby" => $sortby,
        "sortorder" => $sortorder,
        "records" => $records,
        "search" => $search
    );
    $s = serialize($saved_options);
    set_user_meta(0, 'user_management_options', $s, false);

    // Generate query
    $fieldmap = array(
        "username" => $db_tables[DB_NAGIOSXI]["users"] . ".username",
        "name" => $db_tables[DB_NAGIOSXI]["users"] . ".name",
        "email" => $db_tables[DB_NAGIOSXI]["users"] . ".email"
    );
    $query_args = array();
    if (isset($sortby)) {
        $query_args["orderby"] = $sortby;
        if (isset($sortorder) && $sortorder == "desc") {
            $query_args["orderby"] .= ":d";
        } else {
            $query_args["orderby"] .= ":a";
        }
    }
    if (isset($search) && have_value($search)) {
        $query_args["username"] = "lk:" . $search . ";name=lk:" . $search . ";email=lk:" . $search;
    }

    // First get record count
    $sql_args = array(
        "sql" => $sqlquery['GetUsers'],
        "fieldmap" => $fieldmap,
        "default_order" => "username",
        "useropts" => $query_args,
        "limitrecords" => false
    );
    $sql = generate_sql_query(DB_NAGIOSXI, $sql_args);
    $rs = exec_sql_query(DB_NAGIOSXI, $sql);
    if (!$rs->EOF) {
        $total_records = $rs->RecordCount();
    } else {
        $total_records = 0;
    }

    // get any locked account info
    $locked_accounts = locked_account_list();

    // Get table paging info - reset page number if necessary
    $pager_args = array(
        "sortby" => $sortby,
        "sortorder" => $sortorder,
        "search" => $search
    );
    $pager_results = get_table_pager_info("", $total_records, $page, $records, $pager_args);

    do_page_start(array("page_title" => _("Manage Users")), true);

    ?>
    <h1><?php echo _("Manage Users"); ?></h1>

    <?php
    display_message($error, false, $msg);
    ?>

    <form action="users.php" method="post" id="userList">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="sortby" value="<?php echo encode_form_val($sortby); ?>">
        <input type="hidden" name="sortorder" value="<?php echo encode_form_val($sortorder); ?>">

        <div id="usersTableContainer" class="tableContainer">

            <div class="tableHeader">

                <div class="tableTopButtons new-buttons">
                    <a href="?users&amp;edit=1" class="btn btn-sm btn-primary">
                        <img class="tableTopButton" src="<?php echo theme_image("user_add.png"); ?>" border="0" alt="<?php echo _("Add New User"); ?>" title="<?php echo _("Add New User"); ?>">
                        <span><?php echo _("Add New User"); ?></span>
                    </a>

                    <?php if (is_component_installed("ldap_ad_integration")) { ?>
                    <a href="<?php echo get_component_url_base("ldap_ad_integration"); ?>/index.php" class="btn btn-sm btn-primary">
                        <img class="tableTopButton" src="<?php echo theme_image("import_user.png"); ?>" border="0" alt="<?php echo _("Add users from LDAP/AD"); ?>" title="<?php echo _("Add users from LDAP/AD"); ?>">
                        <span><?php echo _("Add users from LDAP/AD"); ?></span>
                    </a>
                    <?php } ?>

                    <a href="#" onclick="users_display_email_selected(true)" class="btn btn-sm btn-primary">
                        <img class="tableTopButton" src="<?php echo theme_image("email_go.png"); ?>" border="0" alt="" title="<?php echo _("Send Email to All Users"); ?>">
                        <span><?php echo _("Email All Users"); ?></span>
                    </a>

                    <div class="tableListSearch">
                        <?php
                        $searchclass = "textfield";
                        $searchstring = '';
                        if (have_value($search)) {
                            $searchstring = $search;
                            $searchclass .= " newdata";
                        }
                        ?>
                        <input type="text" size="15" name="search" id="searchBox" value="<?php echo encode_form_val($searchstring); ?>" placeholder="Search..." class="<?php echo $searchclass; ?> form-control va-m">
                        <button type="submit" class="btn btn-sm btn-primary" name="searchButton" id="searchButton"><i class="fa fa-search"></i></button>
                    </div>
                    <!--table list search -->
                </div>
                <!-- table top buttons -->

                <div class="tableTopText">
                    <?php
                    $clear_args = array(
                        "sortby" => $sortby,
                        "search" => ""
                    );
                    echo table_record_count_text($pager_results, $search, true, $clear_args);
                    ?>
                </div>

                <br/>

            </div>
            <!-- tableHeader -->

            <table id="usersTable" class="tablesorter table table-striped table-hover table-condensed table-no-margin">
                <thead>
                    <tr>
                        <th style="width: 30px; text-align: center; padding: 0;">
                            <input type='checkbox' name='userList_checkAll' id='checkall' value='0'>
                        </th>
                        <th style="width: 16px; text-align: center; padding: 0;"><!-- disabled icons --></th>
                        <?php
                        $extra_args = array();
                        $extra_args["search"] = $search;
                        $extra_args["records"] = $records;
                        $extra_args["page"] = $page;
                        echo sorted_table_header($sortby, $sortorder, "username", _('Username'), $extra_args);
                        echo sorted_table_header($sortby, $sortorder, "name", _('Name'), $extra_args);
                        echo sorted_table_header($sortby, $sortorder, "email", _('Email'), $extra_args);
                        ?>
                        <th><?php echo _('Phone Number'); ?></th>
                        <th style="width: 10%;"><?php echo _('Auth Level'); ?></th>
                        <th style="width: 10%;"><?php echo _('Auth Type'); ?></th>
                        <th style="width: 150px;"><?php echo _('Last Login'); ?></th>
                        <th style="width: 140px;"><?php echo _('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Run record-limiting query
                $query_args["records"] = $records . ":" . (($pager_results["current_page"] - 1) * $records);
                $sql_args["sql"] = $sql;
                $sql_args["useropts"] = $query_args;
                $sql = limit_sql_query_records($sql_args, $cfg['db_info'][DB_NAGIOSXI]['dbtype']);
                $rs = exec_sql_query(DB_NAGIOSXI, $sql);
                $authlevels = get_authlevels();
                $authtypes = array('ad' => _('Active Directory'), 'ldap' => _('LDAP'), 'local' => _('Local'));

                $x = 0;

                if (!$rs || $rs->EOF) {
                    echo "<tr><td colspan='9'>" . _('No records found') . ".</td></tr>";
                } else {
                    while (!$rs->EOF) {
                        $x++;

                        $checked = "";
                        $classes = "";

                        if (($x % 2) == 0)
                            $classes .= " even";
                        else
                            $classes .= " odd";

                        $oid = $rs->fields["user_id"];
                        $user_enabled = $rs->fields["enabled"];
                        $user_disabled_icon = $user_enabled > 0 ? "" :
                            "<img class='tableItemButton tt-bind' src='" . theme_image("exclamation.png") . "' border='0' alt='" . _("Account is disabled!") . "' title='" . _("Account is disabled!") . "'>";

                        if (is_array($user_id)) {
                            if (in_array($oid, $user_id)) {
                                $checked = "CHECKED";
                                $classes .= " selected";
                            }
                        } else if ($oid == $user_id) {
                            $checked = "CHECKED";
                            $classes .= " selected";
                        }

                        $last_login = '-';
                        if (!empty($rs->fields['last_login'])) {
                            $last_login = get_datetime_string($rs->fields['last_login']);
                        }

                        echo "<tr";
                        if (have_value($classes))
                            echo " class='" . $classes . "'";
                        echo ">";
                        echo "<td style='text-align: center;'><input type='checkbox' class='uidcheckbox' name='user_id[]' data-email='" . $rs->fields["email"] . "' value='" . $oid . "' id='checkbox_" . $oid . "' " . $checked . " style='display: inline-block; padding: 0; margin: 0; vertical-align: middle;'></td>";
                        echo "<td style='text-align: center; padding: 0;'>$user_disabled_icon</td>";
                        echo "<td class='clickable'>" . encode_form_val($rs->fields["username"]) . "</td>";
                        echo "<td class='clickable'>" . encode_form_val($rs->fields["name"]) . "</td>";
                        echo "<td class='clickable'><a href='mailto:" . $rs->fields["email"] . "'>" . $rs->fields["email"] . "</a></td>";
                        echo "<td>".get_user_meta($oid, 'mobile_number', '-')."</td>";
                        echo "<td class='clickable'>".$authlevels[get_user_meta($oid, "userlevel")]."</td>";
                        echo "<td class='clickable'>".$authtypes[get_user_meta($oid, "auth_type", 'local')]."</td>";
                        echo '<td>'.$last_login.'</td>';
                        echo "<td>";
                        echo "<a style='padding: 0 1px;' href='?edit=1&amp;user_id[]=" . $oid . "'><img class='tableItemButton tt-bind' src='" . theme_image("pencil.png") . "' border='0' alt='" . _("Edit") . "' title='" . _("Edit") . "'></a> ";
                        echo "<a style='padding: 0 1px;' href='?clone=1&amp;user_id[]=" . $oid . "'><img class='tableItemButton tt-bind' src='" . theme_image("user_go.png") . "' border='0' alt='" . _("Clone") . "' title='" . _("Clone") . "'></a>";
                        if ($user_enabled > 0) {
                            echo "<a style='padding: 0 1px;' href='?masquerade=1&user_id=" . $oid . "&nsp=" . get_nagios_session_protector_id() . "' class='masquerade_link'><img class='tableItemButton tt-bind' src='" . theme_image("eye.png") . "' border='0' alt='" . _("Masquerade As") . "' title='" . _("Masquerade As") . "'></a> ";
                            echo "<a style='padding: 0 1px;' href='?toggle_active=0&amp;user_id=" . $oid . "&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton tt-bind' src='" . theme_image("user_disable.png") . "' border='0' alt='" . _("Disable") . "' title='" . _("Disable") . "'></a>";
                        } else {
                            echo "<a style='padding: 0 1px;' href='?toggle_active=1&amp;user_id=" . $oid . "&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton tt-bind' src='" . theme_image("user_add.png") . "' border='0' alt='" . _("Enable") . "' title='" . _("Enable") . "'></a>";                            
                        }
                        if (is_array($locked_accounts) && in_array($oid, $locked_accounts)) {
                            echo "<a style='padding: 0 1px;' href='?unlock=1&user_id[]=" . $oid . "&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton tt-bind' src='" . theme_image("lock_open.png") . "' border='0' alt='" . _("Unlock Account") . "' title='" . _("Unlock Account") . "'></a>";
                        }
                        echo "<a style='padding: 0 1px;' href='?delete=1&amp;user_id[]=" . $oid . "&nsp=" . get_nagios_session_protector_id() . "'><img class='tableItemButton tt-bind' src='" . theme_image("cross.png") . "' border='0' alt='" . _("Delete") . "' title='" . _("Delete") . "'></a>";
                        echo "</td>";
                        echo "</tr>\n";

                        $rs->MoveNext();
                    }
                }
                ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="10" class="tablePagerLinks">
                            <?php table_record_pager($pager_results); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <div class="tableFooter">

                <div class="tableListMultiOptions">
                    <?php echo _("With Selected:"); ?>
                    <button class="tableMultiItemButton tt-bind" title="<?php echo _('Delete'); ?>" value="delete" name="multiButton" type="submit">
                        <img class="tableMultiButton" src="<?php echo theme_image("cross.png"); ?>" border="0" alt="<?php echo _("Delete"); ?>" title="<?php echo _("Delete"); ?>">
                    </button>
                    <button class="tableMultiItemButton tt-bind" title="<?php echo _('Send Email'); ?>" value="email" name="multiEmailButton" type="button" onclick="users_display_email_selected(false)">
                        <img class="tableMultiButton" src="<?php echo theme_image("email_go.png"); ?>" border="0" alt="<?php echo _("Send Email"); ?>" title="<?php echo _("Send Email"); ?>">
                    </button>
                    <?php if ($locked_accounts !== false) { ?>

                    <button class="tableMultiItemButton tt-bind" title="<?php echo _('Unlock'); ?>" value="unlock" name="multiButton" type="submit">
                        <img class="tableMultieButton" src="<?php echo theme_image("lock_open.png"); ?>" border="0" alt="<?php echo _("Unlock"); ?>" title="<?php echo _("Unlock"); ?>">
                    </button>

                    <?php } ?>
                </div>

            </div>
            <!-- tableFooter -->

        </div>
        <!-- tableContainer -->

    </form>

    <!-- Send email overlay -->
    <script type="text/javascript">

    $(document).ready(function() {
        $('#checkall').click(function() {
            if ($(this).is(':checked')) {
                $('.uidcheckbox').prop('checked', true);
            } else {
                $('.uidcheckbox').prop('checked', false);
            }
        });
    });

    function users_display_email_selected(send_to_all) {
        // Grab the user emails and put them into a variable that will be hidden
        if (!send_to_all) {
            var user_emails = [];
            $('.uidcheckbox:checked').each(function () {
                user_emails.push($(this).data('email'));
            });
            pu_title = "<?php echo _('Send Email to Selected Users'); ?>";
        } else {
            user_emails = "all";
            pu_title = "<?php echo _('Send Email to All Users'); ?>";
        }

        // prepare container for graph
        var content = "<div style='clear:both;'>\
                        <h2 style='padding: 0; margin: 0 0 20px 0;'>" + pu_title + "</h2>\
                        <form method='post' id='send_emails_form'>\
                        <table>\
                            <tr>\
                                <td style='padding-right: 10px;'><?php echo _('Email Subject'); ?>:</td>\
                                <td><input type='text' value='' name='email_subject' id='email_subject' style='width: 400px; padding: 2px 6px; line-height: 16px;'></td>\
                            </tr>\
                            <tr>\
                                <td style='padding-right: 10px;'><?php echo _('Email Body'); ?>:</td>\
                                <td>\
                                    <textarea style='width: 534px; height: 150px; padding: 6px; margin: 6px 0; line-height: 16px;' name='email_message' id='email_message'></textarea>\
                                </td>\
                            </tr>\
                            <tr>\
                                <td></td>\
                                <td>\
                                    <button type='submit' name='send_emails' value='1'><?php echo _('Send Email'); ?></button>\
                                    <span style='margin-left: 10px; color: red;' id='email_error'></span>\
                                </td>\
                            </tr>\
                        </table>\
                        <input type='hidden' value='" + user_emails + "' name='email_user_emails' id='email_user_emails'>\
                        </form>\
                    </div>";

        $("#child_popup_container").height(300);
        $("#child_popup_container").width(650);
        $("#child_popup_layer").height(320);
        $("#child_popup_layer").width(680);
        $("#child_popup_layer").css('position', 'fixed');
        center_child_popup();
        display_child_popup();
        $("#child_popup_layer").css('top', '100px');

        set_child_popup_content(content);

        // Display errors if something is wrong
        $('#send_emails_form').submit(function (e) {

            // Check subject and message
            var subject = $('#email_subject').val();
            var message = $('#email_message').val();
            if (subject == "" || message == "") {
                e.preventDefault();
                $('#email_error').html("<?php echo _('Must have a subject and message to send email.'); ?>");
                return;
            }

            // Check if there are any checked users
            var users = $('#email_user_emails').val();
            if (users == "") {
                e.preventDefault();
                $('#email_error').html("<?php echo _('You need to select users to send this email to.'); ?>");
                return;
            }

        });

        $('#close_child_popup_link').click(function () {
            set_child_popup_content('');
            $("#child_popup_layer").css('position', 'absolute');
            $("#child_popup_layer").width(300);
            $("#child_popup_container").width(300);
            center_child_popup();
        });
    }
    </script>

    <?php

    do_page_end(true);
    exit();
}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_edit_user($error = false, $msg = "")
{
    global $request;

    // Dy default we add a new user
    $add = true;

    // Get languages and themes
    $languages = get_languages();
    $authlevels = get_authlevels();
    $number_formats = get_number_formats();
    $date_formats = get_date_formats();

    // Defaults
    $date_format = DF_ISO8601;
    $number_format = NF_2;
    $language = get_option("default_language");
    //$theme = get_option("default_theme");
    $add_contact = 0;


    // Get options
    $user_id = grab_request_var("user_id");
    if (is_array($user_id)) {
        $user_id = current($user_id);
        if ($user_id != 0) {
            $add = false;
        }
    }

    if ($error == false) {
        if (isset($request["updated"])) {
            $msg = _("User Updated.");
        } else if (isset($request["added"])) {
            $msg = _("User Added.");
        }

        // Check if this users api key needs updated
        if ($msg === "") {
            $backend_ticket = get_user_attr($user_id, 'backend_ticket');
            $api_key = get_user_attr($user_id, 'api_key');
            $username = get_user_attr($user_id, "username");
            if ($backend_ticket == $api_key) {
                $msg = sprintf(_("%s API Key hasn't been updated in a while! You should generate a new key for %s."), $username . "'s", $username);
            }
        }
    }

    // Load current user info
    if ($add == false) {

        // Make sure user exists first
        if (!is_valid_user_id($user_id)) {
            show_users(true, _("User account was not found.") . " (ID=" . $user_id . ")");
        }

        $username = grab_request_var("username", get_user_attr($user_id, "username"));
        $email = grab_request_var("email", get_user_attr($user_id, "email"));
        $level = grab_request_var("level", get_user_meta($user_id, "userlevel"));
        $name = grab_request_var("name", get_user_attr($user_id, "name"));
        $enabled = grab_request_var("enabled", get_user_attr($user_id, "enabled"));
        $language = grab_request_var("language", get_user_meta($user_id, "language"));
        $date_format = grab_request_var("defaultDateFormat", intval(get_user_meta($user_id, 'date_format')));
        $number_format = grab_request_var("defaultNumberFormat", intval(get_user_meta($user_id, 'number_format')));

        $arr = get_user_nagioscore_contact_info($username);
        $is_nagioscore_contact = grab_array_var($arr, "is_nagioscore_contact", 1);
        if ($is_nagioscore_contact) {
            $enable_notifications = grab_request_var('enable_notifications', intval(get_user_meta($user_id, 'enable_notifications')));
        }

        $api_key = grab_request_var("api_key", get_user_attr($user_id, "api_key"));
        $api_enabled = checkbox_binary(grab_request_var("api_enabled", get_user_attr($user_id, "api_enabled")));

        $authorized_for_all_objects = checkbox_binary(grab_request_var("authorized_for_all_objects", get_user_meta($user_id, "authorized_for_all_objects")));
        $authorized_to_configure_objects = checkbox_binary(grab_request_var("authorized_to_configure_objects", get_user_meta($user_id, "authorized_to_configure_objects")));
        $authorized_for_all_object_commands = checkbox_binary(grab_request_var("authorized_for_all_object_commands", get_user_meta($user_id, "authorized_for_all_object_commands")));
        $authorized_for_monitoring_system = checkbox_binary(grab_request_var("authorized_for_monitoring_system", get_user_meta($user_id, "authorized_for_monitoring_system")));
        $advanced_user = checkbox_binary(grab_request_var("advanced_user", get_user_meta($user_id, "advanced_user")));
        $readonly_user = checkbox_binary(grab_request_var("readonly_user", get_user_meta($user_id, "readonly_user")));

        $auth_type = grab_request_var("auth_type", get_user_meta($user_id, "auth_type"));
        $auth_server_id = grab_request_var("auth_server_id", get_user_meta($user_id, "auth_server_id"));
        $ldap_ad_username = grab_request_var("ldap_ad_username", get_user_meta($user_id, "ldap_ad_username"));
        $ldap_ad_dn = grab_request_var("ldap_ad_dn", get_user_meta($user_id, "ldap_ad_dn"));
        $allow_local = grab_request_var("allow_local", get_user_meta($user_id, "allow_local", 0));

        // Force nagiosadmin user to use local password no matter what - in case AD/LDAP is unreachable
        if ($user_id == 1) {
            $allow_local = 1;
        }

        $password1 = "";
        $password2 = "";
        $forcepasswordchange = get_user_meta($user_id, "forcepasswordchange");

        $passwordbox1title = _("New Password");
        $passwordbox2title = _("Repeat New Password");

        $sendemail = "0";
        $sendemailboxtitle = _("Email User New Password");

        $page_title = _("Edit User");
        $page_header = _("Edit User") . ": " . encode_form_val($username);
        $button_title = _("Update User");
    } else {
        // Get defaults to use for new user (or use submitted data)
        $username = grab_request_var("username", "");
        $email = grab_request_var("email", "");
        $level = grab_request_var("level", "user");
        $name = grab_request_var("name", "");
        $enabled = grab_request_var("enabled", 1);
        $language = grab_request_var("language", $language);
        $enable_notifications = grab_request_var('enable_notifications', 1);
        $is_nagioscore_contact = grab_request_var('is_nagioscore_contact', 1);

        $auth_type = grab_request_var('auth_type', 'local');
        $ldap_ad_username = grab_request_var('ldap_ad_username', '');
        $ldap_ad_dn = grab_request_var('ldap_ad_dn', '');
        $allow_local = grab_request_var('allow_local', 0);

        $add_contact = 1;

        $api_enabled = checkbox_binary(grab_request_var("api_enabled", ""));

        $authorized_for_all_objects = checkbox_binary(grab_request_var("authorized_for_all_objects", ""));
        $authorized_to_configure_objects = checkbox_binary(grab_request_var("authorized_to_configure_objects", ""));
        $authorized_for_all_object_commands = checkbox_binary(grab_request_var("authorized_for_all_object_commands", ""));
        $authorized_for_monitoring_system = checkbox_binary(grab_request_var("authorized_for_monitoring_system", ""));
        $advanced_user = checkbox_binary(grab_request_var("advanced_user", ""));
        $readonly_user = checkbox_binary(grab_request_var("readonly_user", ""));

        $password1 = random_string(6);
        $password2 = $password1;
        $forcepasswordchange = "1";
        $passwordbox1title = _("Password");
        $passwordbox2title = _("Repeat Password");

        $sendemail = "1";
        $sendemailboxtitle = _("Email User Account Information");

        $page_title = _("Add New User");
        $page_header = _("Add New User");
        $button_title = _("Add User");
    }

    if ($forcepasswordchange == "1") {
        $forcechangechecked = "CHECKED";
    } else {
        $forcechangechecked = "";
    }

    if ($sendemail == "1") {
        $sendemailchecked = "CHECKED";
    } else {
        $sendemailchecked = "";
    }

    do_page_start(array("page_title" => $page_title), true);
    ?>

    <h1><?php echo $page_header; ?></h1>

    <?php
    display_message($error, false, $msg);
    ?>

    <script type="text/javascript">
    $(document).ready(function() {
        var updateButtonClicked = false;
        $('#addContactBox').change(function() {
            if ($(this).is(":checked")) {
                $('#notificationsBox').attr('disabled', false);
            } else {
                $('#notificationsBox').attr('disabled', true);
            }
        });
        $('#updateButton').click(function() {
            updateButtonClicked = true;
        })
        <?php if ($add == false) { ?>
        $('#updateForm').submit(function(e) {
            if (updateButtonClicked && $('#usernameBox').val() != "<?php echo $username; ?>") {
                var go_ahead_and_change = confirm("<?php echo _('Changing your username is not recommended. But if you wish to proceed, you should be warned that it may take a while to take effect depending on your configuration. Do you wish to proceed?'); ?>");
                if (!go_ahead_and_change) {
                    e.preventDefault();
                }
            }
        });
        <?php } ?>
    });
    </script>

    <form id="updateForm" method="post" action="">

        <input type="hidden" name="update" value="1">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="users" value="1">
        <input type="hidden" name="user_id[]" value="<?php echo encode_form_val($user_id); ?>">

        <div style="float: left; margin-right: 30px;">

            <h5 class="ul"><?php echo _("General Settings"); ?></h5>

            <table class="editDataSourceTable table table-condensed table-no-border" cellpadding="2">
                <tr>
                    <td>
                        <label for="usernameBox"><?php echo _("Username"); ?>:</label>
                    </td>
                    <td>
                        <input type="text" size="15" name="username" id="usernameBox" value="<?php echo encode_form_val($username); ?>" class="textfield form-control">
                    </td>
                </tr>
                <tr class="pw">
                    <td>
                        <label for="passwordBox1"><?php echo $passwordbox1title; ?>:</label>
                    </td>
                    <td>
                        <input type="password" size="10" name="password1" id="passwordBox1" value="<?php echo encode_form_val($password1); ?>" class="textfield form-control" <?php echo sensitive_field_autocomplete(); ?>>
                    </td>
                </tr>
                <tr class="pw">
                    <td>
                        <label for="passwordBox2"><?php echo $passwordbox2title; ?>:</label>
                    </td>
                    <td>
                        <input type="password" size="10" name="password2" id="passwordBox2" value="<?php echo encode_form_val($password2); ?>" class="textfield form-control" <?php echo sensitive_field_autocomplete(); ?>>
                    </td>
                </tr>
                <tr class="lo">
                    <td>
                        <label for="forcePasswordChangeBox"><?php echo _("Force Password Change at Next Login"); ?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" class="checkbox" id="forcePasswordChangeBox" name="forcepasswordchange" <?php echo $forcechangechecked; ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="sendEmailBox"><?php echo $sendemailboxtitle; ?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" class="checkbox" id="sendEmailBox" name="sendemail" <?php echo $sendemailchecked; ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="nameBox"><?php echo _("Name"); ?>:</label>
                    </td>
                    <td>
                        <input type="text" size="30" name="name" id="nameBox" value="<?php echo encode_form_val($name); ?>" class="textfield form-control">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="emailAddressBox"><?php echo _("Email Address"); ?>:</label>
                    </td>
                    <td>
                        <input type="text" size="30" name="email" id="emailAddressBox" value="<?php echo encode_form_val($email); ?>" class="textfield form-control">
                    </td>
                </tr>
                <?php
                if ($add == true) {
                ?>
                <tr>
                    <td>
                        <label for="addContactBox"><?php echo _("Create as Monitoring Contact"); ?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" class="checkbox" id="addContactBox" name="add_contact" <?php echo is_checked($add_contact, 1); ?>>
                    </td>
                </tr>
                <?php
                }

                if ($is_nagioscore_contact || $add == true) {
                ?>
                <tr>
                    <td>
                        <label for="notificationsBox"><?php echo _('Enable Notifications'); ?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" class="checkbox" id="notificationsBox" name="enable_notifications" <?php echo is_checked($enable_notifications, 1); ?>>
                    </td>
                </tr>
            <?php } ?>
                <tr>
                    <td>
                        <label for="addContactBox"><?php echo _("Account Enabled"); ?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" class="checkbox" id="enableUserBox" name="enabled" <?php echo is_checked($enabled, 1); ?>>
                    </td>
                </tr>
        </table>

        <h5 class="ul"><?php echo _("Preferences"); ?></h5>

        <table class="editDataSourceTable table table-condensed table-no-border" cellpadding="2">

            <tr>
                <td>
                    <label for="languageListForm"><?php echo _("Language"); ?>:</label>
                </td>
                <td>
                    <select name="language" id="languageListForm" class="languageListForm dropdown form-control">
                    <?php foreach ($languages as $lang => $title) { ?>
                        <option value="<?php echo $lang; ?>" <?php echo is_selected($language, $lang); ?>><?php echo get_language_nicename($title); ?></option>
                    <?php } ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="defaultDateFormat"><?php echo _("Date Format"); ?>:</label>
                </td>
                <td>
                    <select name="defaultDateFormat" class="dateformatList dropdown form-control">
                        <?php
                        foreach ($date_formats as $id => $txt) {
                            ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo is_selected($id, $date_format); ?>><?php echo $txt; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="defaultNumberFormat"><?php echo _("Number Format"); ?>:</label>
                </td>
                <td>
                    <select name="defaultNumberFormat" class="numberformatList dropdown form-control">
                        <?php
                        foreach ($number_formats as $id => $txt) {
                            ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo is_selected($id, $number_format); ?>><?php echo $txt; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>

        </table>

        <h5 class="ul"><?php echo _("Authentication Settings"); ?> <i class="fa fa-question-circle pop" title="<?php echo _('Authentication Settings'); ?>" data-content="<?php echo _('User accounts can be authenticated in many different ways either from your local database or external programs such as Active Directory or LDAP. You can set up external authentication servers in the'); ?> <a href='<?php echo get_component_url_base('ldap_ad_integration').'/manage.php'; ?>'><?php echo _('LDAP/AD Integration'); ?></a> <?php echo _('settings'); ?>."></i></h5>

            <?php
            // Grab LDAP/AD servers
            $ad = array();
            $ldap = array();
            $servers_raw = get_option("ldap_ad_integration_component_servers");
            if ($servers_raw == "") { $servers = array(); } else {
                $servers = unserialize(base64_decode($servers_raw));
            }
            foreach ($servers as $server) {
                if ($server['conn_method'] == 'ldap') {
                    $ldap[] = $server;
                } else if ($server['conn_method'] == 'ad') {
                    $ad[] = $server;
                }
            }
            ?>

            <table class="table table-condensed table-no-border">
                <tbody>
                    <tr>
                        <td style="width: 110px;"><label><?php echo _("Auth Type"); ?>:</label></td>
                        <td>
                            <select name="auth_type" id="auth_type" class="form-control">
                                <option value="local" <?php echo is_selected($auth_type, "local"); ?>><?php echo _("Local (Default)"); ?></option>
                                <option value="ad" <?php echo is_selected($auth_type, "ad"); ?>><?php echo _("Active Directory"); ?></option>
                                <option value="ldap" <?php echo is_selected($auth_type, "ldap"); ?>><?php echo _("LDAP"); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr class="hide auth-ad">
                        <td><label><?php echo _("AD Server"); ?>:</label></td>
                        <td>
                            <select name="ad_server" class="form-control">
                                <?php foreach ($ad as $s) { ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo is_selected($auth_server_id, $s['id']); ?>><?php echo $s['ad_domain_controllers']; if (!$s['enabled']) { echo _('(Disabled)'); } ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="hide auth-ad">
                        <td><label><?php echo _("AD Username"); ?>:</label></td>
                        <td>
                            <input type="text" name="ad_username" style="width: 240px;" class="form-control" value="<?php echo $ldap_ad_username; ?>">
                        </td>
                    </tr>
                    <tr class="hide auth-ldap">
                        <td><label><?php echo _("LDAP Server"); ?>:</label></td>
                        <td>
                            <select name="ldap_server" class="form-control">
                                <?php foreach ($ldap as $s) { ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo is_selected($auth_server_id, $s['id']); ?>><?php echo $s['ldap_host']; if (!$s['enabled']) { echo _('(Disabled)'); } ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr class="hide auth-ldap">
                        <td><label><?php echo _("User's Full DN"); ?>:</label></td>
                        <td>
                            <input type="text" style="width: 400px;" class="form-control" name="dn" value="<?php echo $ldap_ad_dn; ?>" placeholder="cn=John Smith,dn=nagios,dc=com">
                        </td>
                    </tr>
                    <tr class="hide auth-ldap auth-ad">
                        <td></td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input class="checkbox" name="allow_local" id="allow_local" value="1" type="checkbox" <?php if ($user_id == 1) { echo 'disabled'; } ?> <?php echo is_checked($allow_local, 1); ?>> <?php echo _("Allow local login if auth server login fails"); ?>
                                </label>
                                <i class="fa fa-question-circle pop" style="font-size: 13px; line-height: 20px; vertical-align: middle;" title="<?php echo _('Fallback to Local Password'); ?>" data-placement="top" data-content="<?php echo _('By checking this box you will allow the user to use the local password created for this user (if the password is not blank) when the auth server cannot be connected to, times out, or the password provided is incorrect. This allows a secondary means of authentication in case the auth server is unreachable.'); ?>"></i>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

    </div>

    <div style="float: left;">

        <h5 class="ul"><?php echo _('Security Settings'); ?></h5>

        <table class="editDataSourceTable ss-table table table-condensed table-no-border" cellpadding="2">

            <tr>
                <td>
                    <label for="authLevelListForm"><?php echo _('Authorization Level'); ?>:</label> <i class="fa fa-question-circle pop" title="<?php echo _('Authorization Level'); ?>" data-content="<?php echo _('Users can either be a user or admin. Admins have access to all hosts/services by default and can control/access the entire Nagios XI system including access to admin panel. Users default to only seeing what they are contacts of, unless specified to view all hosts/services with the permissions below.'); ?>"></i>
                </td>
                <td>
                    <select name="level" id="authLevelListForm" class="authLevelList dropdown form-control">
                        <?php foreach ($authlevels as $al => $at) { ?>
                        <option value="<?php echo $al; ?>" <?php echo is_selected($level, $al); ?>><?php echo $at . "</option>"; ?>
                        <?php } ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="aoo"><?php echo _('Can see all objects'); ?>:</label> <i class="fa fa-question-circle pop" title="<?php echo _('User Permissions'); ?>" data-content="<?php echo _('Allows a user to view all objects that are configured no matter what what contact group they are in or what objects they are contacts of.'); ?>"></i>
                </td>
                <td>
                    <input type="checkbox" class="checkbox authcheckbox" id="aoo" name="authorized_for_all_objects" <?php echo is_checked_admin($authorized_for_all_objects, 1, $level); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="aco"><?php echo _('Can (re)configure hosts and services'); ?>:</label> <i class="fa fa-question-circle pop" title="<?php echo _('User Permissions'); ?>" data-content="<?php echo _('Allows a user to be able to re-configure a host/service from the status screen under by using the re-configure option to set contacts, update check intervals, amount of checks for hard state, etc.'); ?>"></i>
                </td>
                <td>
                    <input type="checkbox" class="checkbox authcheckbox" id="aco" name="authorized_to_configure_objects" <?php echo is_checked_admin($authorized_to_configure_objects, 1, $level); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="aaoc"><?php echo _('Can control all objects'); ?>:</label> <i class="fa fa-question-circle pop" title="<?php echo _('User Permissions'); ?>" data-content="<?php echo _('Allows a user to configure - acknowledge problems, schedule downtime, toggle notifications and force checks on all objects.'); ?>"></i>
                </td>
                <td>
                    <input type="checkbox" class="checkbox authcheckbox" id="aaoc" name="authorized_for_all_object_commands" <?php echo is_checked_admin($authorized_for_all_object_commands, 1, $level); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="ams"><?php echo _('Can see/control monitoring engine'); ?>:</label>
                </td>
                <td>
                    <input type="checkbox" class="checkbox authcheckbox" id="ams" name="authorized_for_monitoring_system" <?php echo is_checked_admin($authorized_for_monitoring_system, 1, $level); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="au"><?php echo _('Can access advanced features'); ?>:</label> <i class="fa fa-question-circle pop" title="<?php echo _('User Permissions'); ?>" data-content="<?php echo _('Allows the user to see the CCM links. Shows the check_command in the re-configure host/service page. Shows advanced tab with advanced commands in the host/service detail pages. Allows setting parents during wizards and re-configuration.'); ?>"></i>
                </td>
                <td>
                    <input type="checkbox" class="checkbox authcheckbox" id="au" name="advanced_user" <?php echo is_checked_admin($advanced_user, 1, $level); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="rou"><?php echo _('Has read-only access'); ?>:</label>
                </td>
                <td>
                    <input type="checkbox" class="checkbox" id="rou" name="readonly_user" <?php if ($level == L_GLOBALADMIN) { echo 'disabled'; } ?> <?php echo is_checked_admin($readonly_user, 1, 0); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="api_enabled"><?php echo _("Has API access"); ?>:</label>
                </td>
                <td>
                    <input type="checkbox" class="checkbox" id="api_enabled" name="api_enabled" <?php echo is_checked($api_enabled, 1); ?>>
                </td>
            </tr>
        </table>

        <?php if (!$add) { ?>
        <h5 class="ul"><?php echo _('API Settings'); ?></h5>
        <table class="editDataSourceTable table table-condensed table-no-border" cellpadding="2">
            <tr>
                <td>
                    <label for="apikey"><?php echo _('API Key'); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" onClick="this.select();" value="<?php echo $api_key; ?>" class="textfield form-control" readonly name="apikey" id="apikey">
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="button" class="btn btn-xs btn-info" id="resetapikey" name="resetapikey" onclick="generate_new_api_key(<?php echo $user_id; ?>);"><?php echo _("Generate new API key"); ?></button>
                </td>
            </tr>
        </table>
        <?php } ?>
    </div>

    <div class="clear"></div>

    <div id="formButtons" style="margin-top: 10px;">
        <input type="submit" class="submitbutton btn btn-sm btn-primary" name="updateButton" value="<?php echo $button_title; ?>" id="updateButton">
        <input type="submit" class="submitbutton btn btn-sm btn-default" name="cancelButton" value="<?php echo _("Cancel"); ?>" id="cancelButton">
    </div>

    </form>

    <script type="text/javascript" language="JavaScript">
    document.forms['updateForm'].elements['usernameBox'].focus();

    // Disables authorization checkboxes when Admin is selected as Admins can do everything -SW
    $(document).ready(function() {
        var arrVal = [ "255" ];

        $(".ss-table select").change(function () {
            var valToCheck = String($(this).val());

            if ($.inArray(valToCheck, arrVal) != -1) {
                $(".authcheckbox").attr("disabled", "true");
                $("#rou").attr("disabled", "true");
                $(".authcheckbox").attr("checked", "checked");
            } else {
                $(".authcheckbox").removeAttr("disabled");
                $("#rou").removeAttr("disabled");
                $(".authcheckbox").attr("checked", false);
            }
        });

        $('#auth_type').change(function() {
            var type = $(this).val();
            if (type == 'ldap') {
                $('.auth-ad').hide();
                $('.auth-ldap').show();
                $('.lo').hide();
            } else if (type == 'ad') {
                $('.auth-ldap').hide();
                $('.auth-ad').show();
                $('.lo').hide();
            } else {
                $('.auth-ad').hide();
                $('.auth-ldap').hide();
                $('.lo').show();
            }
            verify_allow_local();
        });

        $('#allow_local').click(function() {
            verify_allow_local();
        })

        $('#auth_type').trigger('change');
        verify_allow_local();

    });

    function verify_allow_local() {
        if ($('#allow_local').is(':checked') || $('#allow_local').is(':disabled') || $('#auth_type').val() == 'local') {
            $('.lo').show();
            $('.pw').show();
        } else {
            $('.lo').hide();
            $('.pw').hide();
        }
    }
    </script>

    <?php

    do_page_end(true);
    exit();
}


function do_update_user()
{
    global $request;

    // User pressed the cancel button
    if (isset($request["cancelButton"])) {
        show_users(false, "");
        exit();
    }

    // Check session
    check_nagios_session_protector();

    // Defaults
    $errmsg = array();
    $errors = 0;
    $changepass = false;
    $add = true;

    // Get values
    $username = grab_request_var("username", "");
    $email = grab_request_var("email", "");
    $name = grab_request_var("name", "");
    $level = grab_request_var("level", "user");
    $language = grab_request_var("language", "");
    $date_format = grab_request_var("defaultDateFormat", DF_ISO8601);
    $number_format = grab_request_var("defaultNumberFormat", NF_2);
    $password1 = grab_request_var("password1", "");
    $password2 = grab_request_var("password2", "");

    $add_contact = checkbox_binary(grab_request_var("add_contact", ""));
    if ($add_contact == 1) {
        $add_contact = true;
    } else {
        $add_contact = false;
    }

    $enabled = checkbox_binary(grab_request_var("enabled", ""));
    $enable_notifications = checkbox_binary(grab_request_var('enable_notifications', 0));
    $api_enabled = checkbox_binary(grab_request_var("api_enabled", 0));

    $authorized_for_all_objects = checkbox_binary(grab_request_var("authorized_for_all_objects", ""));
    $authorized_to_configure_objects = checkbox_binary(grab_request_var("authorized_to_configure_objects", ""));
    $authorized_for_all_object_commands = checkbox_binary(grab_request_var("authorized_for_all_object_commands", ""));
    $authorized_for_monitoring_system = checkbox_binary(grab_request_var("authorized_for_monitoring_system", ""));
    $advanced_user = checkbox_binary(grab_request_var("advanced_user", ""));
    $readonly_user = checkbox_binary(grab_request_var("readonly_user", ""));

    // Grab authentication settings
    $auth_type = grab_request_var('auth_type', 'local');
    $ad_server = grab_request_var('ad_server', '');
    $ldap_server = grab_request_var('ldap_server', '');
    $ad_username = grab_request_var('ad_username', '');
    $dn = grab_request_var('dn', '');
    $allow_local = checkbox_binary(grab_request_var('allow_local', 0));

    if ($level == L_GLOBALADMIN) {
        $readonly_user = 0;
    }

    // Check for errors
    if (in_demo_mode() == true) {
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    }
    if (have_value($password1) == true && have_value($password2) == true) {
        // User has entered a password
        if (have_value($password1) == true || have_value($password2) == true) {
            if (strcmp($password1, $password2)) {
                $errmsg[$errors++] = _("Passwords do not match.");
            } else {
                $changepass = true;
            }
        }
    }
    if (have_value($username) == false) {
        $errmsg[$errors++] = _("Username is blank.");
    }
    if (have_value($email) == false) {
        $errmsg[$errors++] = _("Email address is blank.");
    } else if (!valid_email($email)) {
        $errmsg[$errors++] = _("Email address is invalid.");
    }
    if (have_value($name) == false) {
        $errmsg[$errors++] = _("Name is blank.");
    }
    if (have_value($level) == false) {
        $errmsg[$errors++] = _("Blank authorization level.");
    } else if (!is_valid_authlevel($level)) {
        $errmsg[$errors++] = _("Invalid authorization level.");
    }
    $user_id = grab_request_var("user_id");
    if (is_array($user_id)) {
        $user_id = current($user_id);
        if ($user_id != 0) {
            $add = false;
            // Make sure user exists
            if (!is_valid_user_id($user_id)) {
                $errmsg[$errors++] = _("User account was not found.") . " (ID=" . $user_id . ")";
            }
        }
    }
    if ($level != L_GLOBALADMIN && $user_id == $_SESSION["user_id"]) {
        $errmsg[$errors++] = _("Authorization level demotion error.");
    }

    if (isset($request["forcepasswordchange"]) && $auth_type == 'local') {
        $forcechangepass = true;
    } else {
        $forcechangepass = false;
    }

    // Handle errors
    if ($errors > 0) {
        show_edit_user(true, $errmsg);
    }

    // Add a new user
    if ($add == true) {
        if (!($user_id = add_user_account($username, $password1, $name, $email, $level, $forcechangepass, $add_contact, $api_enabled, $errmsg))) {
            show_edit_user(true, $errmsg);
            exit();
        }

        change_user_attr($user_id, 'created_time', time());
        change_user_attr($user_id, 'created_by', $_SESSION['user_id']);

        set_user_meta($user_id, 'name', $name);
        set_user_meta($user_id, 'language', $language);
        set_user_meta($user_id, "date_format", $date_format);
        set_user_meta($user_id, "number_format", $number_format);
        set_user_meta($user_id, "authorized_for_all_objects", $authorized_for_all_objects);
        set_user_meta($user_id, "authorized_to_configure_objects", $authorized_to_configure_objects);
        set_user_meta($user_id, "authorized_for_all_object_commands", $authorized_for_all_object_commands);
        set_user_meta($user_id, "authorized_for_monitoring_system", $authorized_for_monitoring_system);
        set_user_meta($user_id, "advanced_user", $advanced_user);
        set_user_meta($user_id, "readonly_user", $readonly_user);

        if ($add_contact) {
            set_user_meta($user_id, "enable_notifications", $enable_notifications);
        }

        // Set authentication settings
        set_user_meta($user_id, "auth_type", $auth_type);
        set_user_meta($user_id, "allow_local", $allow_local);
        if ($auth_type == 'ad') {
            set_user_meta($user_id, "auth_server_id", $ad_server);
            set_user_meta($user_id, "ldap_ad_username", $ad_username);
        } else if ($auth_type == 'ldap') {
            set_user_meta($user_id, "auth_server_id", $ldap_server);
            set_user_meta($user_id, "ldap_ad_dn", $dn);
        } else {
            submit_command(COMMAND_NAGIOSXI_SET_HTACCESS, serialize(array('username' => $username, 'password' => $password1)));
        }

        // Update nagios cgi config file
        update_nagioscore_cgi_config();

        // Send email
        if (isset($request["sendemail"])) {

            $password = $password1;
            $adminname = get_option("admin_name");
            $adminemail = get_option("admin_email");
            $url = get_option("url");

            $message = sprintf(_("An account has been created for you to access Nagios XI.  You can login using the following information:

Username: %s
Password: %s
URL: %s

"), $username, $password, $url);

            // Use this for debug output in PHPmailer log
            $debugmsg = "";

            // Set where email is coming from for PHPmailer log
            $send_mail_referer = "admin/users.php > Account Creation";

            $opts = array(
                "from" => $adminname . " <" . $adminemail . ">\r\n",
                "to" => $email,
                "subject" => _("Nagios XI Account Created"),
                "message" => $message,
            );
            send_email($opts, $debugmsg, $send_mail_referer);
        }

        // Log it
        if ($level == L_GLOBALADMIN) {
            send_to_audit_log("User account '" . $username . "' was created with GLOBAL ADMIN privileges", AUDITLOGTYPE_SECURITY);
        }

        change_user_attr($user_id, "enabled", $enabled);

        // Success!
        header("Location: ?useradded");
    } else {

        // Edit user...

        $oldlevel = get_user_meta($user_id, 'userlevel');
        $oldname = get_user_attr($user_id, 'username');

        if ($username != $oldname) {
            change_user_attr($user_id, 'username', $username);
            rename_nagioscore_contact($oldname, $username);
            delete_nagioscore_host_and_service_configs();
        }
        if ($changepass == true) {
            if (password_meets_complexity_requirements($password1)) {
                change_user_attr($user_id, 'password', md5($password1));
                change_user_attr($user_id, 'last_password_change', time());
                submit_command(COMMAND_NAGIOSXI_SET_HTACCESS, serialize(array('username' => $username, 'password' => $password1)));
                do_user_password_change_callback($user_id, $password1);
            } else {
                show_edit_user(true, _("Specified password does not meet the complexity requirements.") . get_password_requirements_message());
                exit();                
            }
        }
        if ($forcechangepass == true) {
            set_user_meta($user_id, 'forcepasswordchange', "1");
        } else {
            delete_user_meta($user_id, 'forcepasswordchange');
        }

        change_user_attr($user_id, 'email', $email);
        change_user_attr($user_id, 'name', $name);

        change_user_attr($user_id, 'last_edited', time());
        change_user_attr($user_id, 'last_edited_by', $_SESSION['user_id']);

        set_user_meta($user_id, 'language', $language);
        //set_user_meta($user_id, 'theme', $theme);
        set_user_meta($user_id, "date_format", $date_format);
        set_user_meta($user_id, "number_format", $number_format);
        set_user_meta($user_id, 'userlevel', $level);
        set_user_meta($user_id, "authorized_for_all_objects", $authorized_for_all_objects);
        set_user_meta($user_id, "authorized_to_configure_objects", $authorized_to_configure_objects);
        set_user_meta($user_id, "authorized_for_all_object_commands", $authorized_for_all_object_commands);
        set_user_meta($user_id, "authorized_for_monitoring_system", $authorized_for_monitoring_system);
        set_user_meta($user_id, "advanced_user", $advanced_user);
        set_user_meta($user_id, "readonly_user", $readonly_user);
        change_user_attr($user_id, 'api_enabled', $api_enabled);

        $arr = get_user_nagioscore_contact_info($username);
        if ($arr["is_nagioscore_contact"]) {
            set_user_meta($user_id, "enable_notifications", $enable_notifications);
        }

        // Set authentication settings
        set_user_meta($user_id, "auth_type", $auth_type);
        set_user_meta($user_id, "allow_local", $allow_local);
        if ($auth_type == 'ad') {
            set_user_meta($user_id, "auth_server_id", $ad_server);
            set_user_meta($user_id, "ldap_ad_username", $ad_username);
        } else if ($auth_type == 'ldap') {
            set_user_meta($user_id, "auth_server_id", $ldap_server);
            set_user_meta($user_id, "ldap_ad_dn", $dn);
        }

        // Set session vars if this is the current user
        if ($user_id == $_SESSION["user_id"]) {
            $_SESSION["language"] = $language;
            $_SESSION["date_format"] = $date_format;
            $_SESSION["number_format"] = $number_format;
        }

        // Update nagios cgi config file
        update_nagioscore_cgi_config();

        // Send email
        if (isset($request["sendemail"]) && $changepass == true) {

            $password = $password1;
            $adminname = get_option("admin_name");
            $adminemail = get_option("admin_email");
            $url = get_option("url");

            $message = sprintf(_("Your Nagios XI account password has been changed by an administrator.  You can login using the following information:

Username: %s
Password: %s
URL: %s

"), $username, $password, $url);

            // Use this for debug output in PHPmailer log
            $debugmsg = "";

            // Set where email is coming from for PHPmailer log
            $send_mail_referer = "admin/users.php > Administrator Password Reset";

            $opts = array(
                "from" => $adminname . " <" . $adminemail . ">\r\n",
                "to" => $email,
                "subject" => _("Nagios XI Password Changed"),
                "message" => $message,
            );
            send_email($opts, $debugmsg, $send_mail_referer);
        }

        // Log it (for privilege changes)
        if ($level == L_GLOBALADMIN && $oldlevel != L_GLOBALADMIN) {
            send_to_audit_log("User account '" . $username . "' was granted GLOBAL ADMIN privileges", AUDITLOGTYPE_SECURITY);
        }
        if ($level != L_GLOBALADMIN && $oldlevel == L_GLOBALADMIN) {
            send_to_audit_log("User account '" . $username . "' had GLOBAL ADMIN privileges revoked", AUDITLOGTYPE_SECURITY);
        }

        if (($user_id != $_SESSION["user_id"]) || ($enabled == 1)) {
            change_user_attr($user_id, "enabled", $enabled);
        }
        
        // Success!

        flash_message(_('User') . " <b>{$username}</b> " . _('updated.'), FLASH_MSG_SUCCESS);
        header("Location: users.php");
    }
}


function do_toggle_active_user()
{
    global $request;
    global $db_tables;

    check_nagios_session_protector();

    $toggle = grab_request_var("toggle_active") == "1" ? "1" : "0";

    $errmsg = array();
    $errors = 0;
    $user_id = grab_request_var("user_id", "");
    if (empty($user_id) || !is_numeric($user_id)) {
        $errmsg[$errors++] = _("Invalid user id.");
    }

    // Check for errors
    if (in_demo_mode() == true)
        $errors++;
        $errmsg = _("Changes are disabled while in demo mode.");
    if (!isset($request["user_id"])) {
        $errors++;
        $errmsg = _("No account selected.");
    } else {

        // Make sure user exists
        if (!is_valid_user_id($user_id)) {
            $errors++;
            $errmsg = _("User account was not found.") . " (ID=" . $user_id . ")";
        }

        // User can't disable their own account, but they can enable their own accounts
        if (($user_id == $_SESSION["user_id"]) && ($toggle == "0")) {
            $errors++;
            $errmsg = _("You cannot disable your own account.");
        }
    }

    // Disable the account
    if ($errors == 0) {
        if (change_user_attr($user_id, "enabled", $toggle)) {
            if ($toggle == "0") {
                flash_message(_("User account disabled."), FLASH_MSG_SUCCESS);
                show_users();
            } else {
                flash_message(_("User account enabled."), FLASH_MSG_SUCCESS);
                show_users();
            }
        } else {
            if ($toggle == "0") {
                $errors++;
                $errmsg = _("Unable to disable account.");
            } else {
                $errors++;
                $errmsg = _("Unable to enable account.");            
            }
        }
    }

    if ($errors > 0) {
        flash_message($errmsg, FLASH_MSG_ERROR);
        show_users();
    }
}


function do_delete_user()
{
    global $request;

    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // Check for errors
    if (in_demo_mode() == true) {
        $errors++;
        $errmsg = _("Changes are disabled while in demo mode.");
    }
    if (!isset($request["user_id"])) {
        $errors++;
        $errmsg = _("No account selected.");
    } else {
        $user_id_arr = grab_request_var("user_id");
        foreach ($user_id_arr as $user_id) {

            // Make sure user exists
            if (!is_valid_user_id($user_id)) {
                $errors++;
                $errmsg = _("User account was not found.") . " (ID=" . $user_id . ")";
            }

            // User can't delete their own account
            if ($user_id == $_SESSION["user_id"]) {
                $errors++;
                $errmsg = _("You cannot delete your own account.");
            }
        }
    }

    if ($errors > 0) {
        flash_message($errmsg, FLASH_MSG_ERROR);
        show_users();
        return;
    }

    // Delete the accounts
    $user_id_arr = grab_request_var("user_id");
    foreach ($user_id_arr as $user_id) {
        update_nagioscore_cgi_config();
        $args = array(
            "username" => get_user_attr($user_id, 'username'),
        );
        submit_command(COMMAND_NAGIOSXI_DEL_HTACCESS, serialize($args));
        delete_user_id($user_id);

        // callback for user deletion
        $args['user_id'] = $user_id;
        do_callbacks(CALLBACK_USER_DELETED, $args);
    }

    $users = count($request["user_id"]);
    if ($users > 1) {
        $msg = $users . " " . _('users deleted.');
        flash_message($msg, FLASH_MSG_SUCCESS);
        show_users();
    }

    flash_message(_('User') . " {$username} " . _('deleted.'), FLASH_MSG_SUCCESS);
    show_users();
}


function do_unlock_user() {

    global $request;

    check_nagios_session_protector();

    $errmsg = array();
    $msg = "";
    $errors = 0;

    // Check for errors
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");

    if (!isset($request["user_id"]))
        $errmsg[$errors++] = _("No account selected.");

    $user_id_arr = grab_request_var("user_id");
    foreach ($user_id_arr as $user_id) {

        // Make sure user exists
        if (!is_valid_user_id($user_id)) {
            $errmsg[$errors++] = _("User account was not found.") . " (ID=" . $user_id . ")";
        }

        if (!change_user_attr($user_id, "login_attempts", 0) || !change_user_attr($user_id, "last_attempt", 0)) {
            $errmsg[$errors++] = _("Unable to unlock account.") . " (ID=" . $user_id . ")";
        }
    }

    if ($errors > 0) {
        show_users(true, $errmsg);
    } else {
        show_users(false, count($user_id_arr) . " " . _("User Accounts Unlocked."));
    }


}


/**
 * @param bool   $error
 * @param string $msg
 */
function show_clone_user($error = false, $msg = "")
{
    global $request;


    // defaults
    $add_contact = 1;

    // get options
    $user_id = grab_request_var("user_id", 0);
    if (is_array($user_id)) {
        $user_id = current($user_id);
    }
    //echo "USERID";
    //print_r($user_id);
    //exit();

    if ($error == false) {
        if (isset($request["updated"]))
            $msg = _("User Updated.");
        else if (isset($request["added"]))
            $msg = _("User Added.");
    }


    // make sure user exists first
    if (!is_valid_user_id($user_id)) {
        show_users(true, _("User account was not found.") . " (ID=" . $user_id . ")");
    }

    $username = grab_request_var("username", "");
    $email = grab_request_var("email", "");
    $name = grab_request_var("name", "");

    $password1 = "";
    $password2 = "";
    $forcepasswordchange = get_user_meta($user_id, "forcepasswordchange");

    $passwordbox1title = _("New Password");
    $passwordbox2title = _("Repeat New Password");

    $sendemail = "0";
    $sendemailboxtitle = _("Email User New Password");

    $page_title = _("Clone User");
    $page_header = _("Clone User") . ": " . encode_form_val(get_user_attr($user_id, "username"));
    $button_title = _("Clone User");

    if ($forcepasswordchange == "1")
        $forcechangechecked = "CHECKED";
    else
        $forcechangechecked = "";
    if ($sendemail == "1")
        $sendemailchecked = "CHECKED";
    else
        $sendemailchecked = "";

    do_page_start(array("page_title" => $page_title), true);
?>

    <h1><?php echo $page_header; ?></h1>

    <?php display_message($error, false, $msg); ?>

    <script type="text/javascript">
        $(document).ready(function () {
            $("#passwordBox1").change(function () {
                $("#updateForm").checkCheckboxes("#forcePasswordChangeBox", true);
                $("#updateForm").checkCheckboxes("#sendEmailBox", true);
            });
        });
    </script>

    <p>
        <?php echo _('Use this functionality to create a new user account that is an exact clone of another account on the system. The cloned account will inherit all preferences, views, and dashboards of the original user.'); ?>
    </p>

    <form id="updateForm" method="post" action="?">
        <input type="hidden" name="doclone" value="1">
        <?php echo get_nagios_session_protector(); ?>
        <input type="hidden" name="user_id[]" value="<?php echo encode_form_val($user_id); ?>">

        <h5 class="ul"><?php echo _("General Settings"); ?></h5>

        <table class="editDataSourceTable table table-condensed table-no-border table-auto-width">
            <tr>
                <td>
                    <label for="usernameBox"><?php echo _("Username"); ?>:</label>
                </td>
                <td>
                    <input type="text" size="15" name="username" id="usernameBox" value="<?php echo encode_form_val($username); ?>" class="form-control">
                </td>
            </tr>

            <tr>
                <td>
                    <label for="passwordBox1"><?php echo $passwordbox1title; ?>:</label>
                </td>
                <td>
                    <input type="password" size="10" name="password1" id="passwordBox1" value="<?php echo encode_form_val($password1); ?>" class="form-control" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="passwordBox2"><?php echo $passwordbox2title; ?>:</label>
                </td>
                <td>
                    <input type="password" size="10" name="password2" id="passwordBox2" value="<?php echo encode_form_val($password2); ?>" class="form-control" <?php echo sensitive_field_autocomplete(); ?>>
                </td>
            </tr>

            <tr>
                <td></td>
                <td>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" class="checkbox" id="forcePasswordChangeBox" name="forcepasswordchange" <?php echo $forcechangechecked; ?>>
                            <?php echo _('Force password change at next login'); ?>
                        </label>
                    </div>
                </td>
            </tr>

            <tr>
                <td></td>
                <td>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" class="checkbox" id="sendEmailBox" name="sendemail" <?php echo $sendemailchecked; ?>>
                            <?php echo _('Email user new password'); ?>
                        </label>
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="nameBox"><?php echo _("Name"); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" name="name" id="nameBox" value="<?php echo encode_form_val($name); ?>" class="form-control">
                </td>
            </tr>

            <tr>
                <td>
                    <label for="emailAddressBox"><?php echo _("Email Address"); ?>:</label>
                </td>
                <td>
                    <input type="text" size="30" name="email" id="emailAddressBox" value="<?php echo encode_form_val($email); ?>" class="form-control">
                </td>
            </tr>

            <tr>
                <td></td>
                <td>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" class="checkbox" id="addContactBox" name="add_contact" <?php echo is_checked($add_contact, 1); ?>>
                            <?php echo _('Create as monitoring contact'); ?>
                        </label>
                    </div>
                </td>
            </tr>

        </table>

        <div id="formButtons">
            <button type="submit" class="btn btn-sm btn-primary" name="updateButton" id="updateButton"><?php echo $button_title; ?></button>
            <input type="submit" class="btn btn-sm btn-default" name="cancelButton" value="<?php echo _('Cancel'); ?>" id="cancelButton">
        </div>

    </form>

    <script type="text/javascript" language="JavaScript">
        document.forms['updateForm'].elements['usernameBox'].focus();
    </script>


    <?php

    do_page_end(true);
    exit();
}


function do_clone_user()
{
    global $request;

    // user pressed the cancel button
    if (isset($request["cancelButton"])) {
        show_users(false, "");
        exit();
    }

    // check session
    check_nagios_session_protector();

    $errmsg = array();
    $errors = 0;

    // get values
    $username = grab_request_var("username", "");
    $email = grab_request_var("email", "");
    $name = grab_request_var("name", "");
    $password1 = grab_request_var("password1", "");
    $password2 = grab_request_var("password2", "");

    $add_contact = checkbox_binary(grab_request_var("add_contact", ""));
    if ($add_contact == 1)
        $add_contact = true;
    else
        $add_contact = false;

    // check for errors
    if (in_demo_mode() == true)
        $errmsg[$errors++] = _("Changes are disabled while in demo mode.");
    if (have_value($password1) == true && have_value($password2) == true) {
        // user has entered a password
        if (have_value($password1) == true || have_value($password2) == true) {
            if (strcmp($password1, $password2))
                $errmsg[$errors++] = _("Passwords do not match.");
        }
    }
    if (have_value($username) == false)
        $errmsg[$errors++] = _("Username is blank.");
    if (have_value($email) == false)
        $errmsg[$errors++] = _("Email address is blank.");
    else if (!valid_email($email))
        $errmsg[$errors++] = _("Email address is invalid.");
    if (have_value($name) == false)
        $errmsg[$errors++] = _("Name is blank.");

    $user_id = grab_request_var("user_id", 0);
    if (is_array($user_id)) {
        $user_id = current($user_id);
        if ($user_id != 0) {
            // make sure user exists
            if (!is_valid_user_id($user_id)) {
                $errmsg[$errors++] = _("User account was not found.") . " (ID=" . $user_id . ")";
            }
        }
    }

    if (isset($request["forcepasswordchange"]))
        $forcechangepass = true;
    else
        $forcechangepass = false;

    // handle errors
    if ($errors > 0)
        show_clone_user(true, $errmsg);

    // log it
    $original_user = get_user_attr($user_id, "username");
    send_to_audit_log("User cloned account '" . $original_user . "'", AUDITLOGTYPE_SECURITY);

    // add the new user
    $level = get_user_meta($user_id, "userlevel");
    $api_enabled = get_user_attr($user_id, "api_enabled");
    if (!($new_user_id = add_user_account($username, $password1, $name, $email, $level, $forcechangepass, $add_contact, $api_enabled, $errmsg))) {
        show_clone_user(true, $errmsg);
    }

    // copy over all meta data from original user
    $meta = get_all_user_meta($user_id);
    foreach ($meta as $var => $val) {

        // skip a few types of meta data
        if ($var == "userlevel")
            continue;
        if ($var == "forcepasswordchange")
            continue;
        if ($var == "lastlogintime")
            continue;
        if ($var == "timesloggedin")
            continue;

        set_user_meta($new_user_id, $var, $val);
    }

    // send email
    if (isset($request["sendemail"])) {

        $password = $password1;
        $adminname = get_option("admin_name");
        $adminemail = get_option("admin_email");
        $url = get_option("url");

        $message = sprintf(_("An account has been created for you to access Nagios XI.  You can login using the following information:

Username: %s
Password: %s
URL: %s

"), $username, $password, $url);

        // Use this for debug output in PHPmailer log
        $debugmsg = "";

        // Set where email is coming from for PHPmailer log
        $send_mail_referer = "admin/users.php > Clone User - Account Creation";

        $opts = array(
            "from" => $adminname . " <" . $adminemail . ">\r\n",
            "to" => $email,
            "subject" => _("Nagios XI Account Created"),
            "message" => $message,
        );
        send_email($opts, $debugmsg, $send_mail_referer);
    }

    // success!
    header("Location: ?usercloned");
}


function do_masquerade()
{

    // check session
    check_nagios_session_protector();

    $user_id = grab_request_var("user_id", -1);

    if (!is_valid_user_id($user_id)) {
        show_users(false, _("Invalid account."));
        exit();
    }

    if (get_user_attr($user_id, "enabled") < 1) {
        show_users(false, _("Account is disabled."));
        exit();
    }

    // do the magic masquerade stuff...
    masquerade_as_user_id($user_id);

    // redirect to home page
    header("Location: " . get_base_url());
}


function is_checked_admin($var1, $var2, $level)
{
    if ($level == 255) {
        return "checked disabled";
    } else if ($var1 == $var2) {
        return "checked";
    }

    return "";
}