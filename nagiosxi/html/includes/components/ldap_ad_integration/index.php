<?php
//
// LDAP / Active Directory Integration
// Copyright (c) 2011-2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__).'/../../common.inc.php');
require_once(dirname(__FILE__).'/../componenthelper.inc.php');

require_once(dirname(__FILE__).'/adLDAP/src/adLDAP.php');
include_once(dirname(__FILE__).'/ldap_ad_integration.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables, check prereqs and authentication
grab_request_vars();
check_prereqs();
check_authentication(false);

// Only admins can access this page
if (is_admin() == false) {
    echo _("You do not have access to this section.");
    exit();
}

route_request();

function route_request()
{
    global $request;

    $cmd = grab_request_var('cmd', 'default');
    $users = grab_request_var('users', '');
    $action = grab_request_var('action', '');
    $update = grab_request_var('update', '');
    $back = grab_request_var("back", '');

    switch($cmd)
    {        
        case "landing_page":
            do_page_start(array("page_title" => _("LDAP / Active Directory Users")), true);
            $_SESSION['ldap_ad_username'] = grab_request_var("username", "");
            $_SESSION['ldap_ad_password'] = grab_request_var("password", "");
            
            $server_id = grab_request_var("server_id", "");

            if (empty($server_id)) {
                echo '<h1>'._("LDAP / Active Directory Import Users").'</h1>';
                collect_credentials();
                exit();
            }

            // Verify server type
            $servers_raw = get_option("ldap_ad_integration_component_servers");
            if (empty($servers_raw)) { $servers = array(); } else {
                $servers = unserialize(base64_decode($servers_raw));
            }

            $_SESSION['ldap_ad_server_id'] = $server_id;
            foreach ($servers as $server) {
                if ($server['id'] == $server_id) {
                    $_SESSION['ldap_ad_server'] = $server;
                }
            }

            landing_page();
            break;

        case "cancel":
            do_page_start(array("page_title" => _("LDAP / Active Directory Users")), true);
            landing_page();
            break;

        case "return_items":
            return_items();
            break;

        case "navigate":
            $select = grab_request_var('ad_object', "");
            $direction = grab_request_var("direction", "");
            $obj_array = parse_object($select);
            $folder = grab_array_var($obj_array, "1", array());
            $_SESSION["current_object_type"] = grab_array_var($obj_array, "0", "con");
            navigate($direction,$folder);
            break;

        case "import":
            $objs = grab_request_var("objs", "");
            $users = explode('|', $objs);
            show_user_options($users, false);
            break;

        case "finish":
            $users = grab_request_var("users", array());
            $preferences = grab_request_var("preferences", array());
            $security = grab_request_var("security", array());
            do_page_start(array("page_title" => _("LDAP / Active Directory Users")), true);
            create_nagios_users($users, $preferences, $security);
            break;

        case "get_location":
            get_location();
            break;

        case "parse_object":
            parse_object();
            break;

        case "grab_user_list":
            grab_user_list();
            break;

        case "display_select_list":
            display_select_list();
            break;

        case "display_nav_window":
            $target_path = grab_request_var("target_path", "");
            $type = grab_request_var("object_type", "");
            $new_list = grab_request_var("new_list", "0");

            $folder = json_decode($target_path);

            $array_to_enum = grab_ad_folders($folder, $type);
            display_nav_window($array_to_enum, $new_list);
            break;
        
        case "display_users":
            $target_path = grab_request_var("target_path", "");
            $type = grab_request_var("object_type", "");
            $selected = grab_request_var("selected", "");

            $folder = json_decode($target_path);
            $selected = json_decode($selected);

            $array_to_enum = grab_ad_folders($folder, $type);
            display_users($array_to_enum, $folder, $type, $selected);
            break;

        default:
            do_page_start(array("page_title" => _("LDAP / Active Directory Import Users")), true);
            echo '<h1>'._("LDAP / Active Directory Import Users").'</h1>';
            collect_credentials();
    }
}

/**
 * Display the current Active Directory settings when there is an error so user can see what might
 * be wrong and change it.
 */
function error_page()
{
    $servers_raw = get_option("ldap_ad_integration_component_servers");
    if (empty($servers_raw)) { $servers = array(); } else {
        $servers = unserialize(base64_decode($servers_raw));
    }
}

/**
 * The main page that we go to when we open the Active Directory Users page
 */
function landing_page()
{
    global $ad_error;

    echo '<h1>'._("LDAP / Active Directory Import Users").'</h1>';
    
    $adldap = create_obj();
    
    if ($adldap == false) {
        $msg = '<strong>'._("Unable to authenticate:").'</strong> '.$ad_error;
        display_message(true, false, $msg, "");
    }

    if ($adldap == false) {
        collect_credentials();
        error_page();
        exit();
    }
    ?>

    <style type="text/css">
    .table-icon { vertical-align: middle; }
    input[type="checkbox"].ad-checkbox { vertical-align: middle; margin-right: 5px; }
    .ad-list { list-style-type: none; margin: 0; padding: 0; }
    .folder-list { background-color: #F9F9F9; }
    .user-list { margin: 15px 30px; }
    .user-list li { }
    .sub-list li span { padding-left: 20px; }
    .ad-folder { padding: 1px 8px 1px 7px; height: 22px; display: block; margin: 2px 0; }
    .ad-folder:hover { cursor: pointer; background-color: #E9E9E9; }
    .ad-folder.active { background-color: #E9E9E9; }
    .import-button { margin-top: 20px; }
    #selected-users { margin-bottom: 15px; }
    #selected-users .num-users, #selected-users .users { font-weight: bold; }
    .user-dn { padding-left: 40px; font-family: 'Consolas', "Courier New", Courier, monospace; margin-bottom: 6px; }
    .user-toggle-show-dn { font-size: 11px; vertical-align: middle; cursor: pointer; margin-left: 2px; }
    </style>
    
    <p><?php echo _("Select the users you would like to give access to Nagios XI via LDAP/AD authentication. You will be able to set user-specific permissions on the next page."); ?></p>
    <h3><?php echo _("Select Users to Import from LDAP/AD"); ?></h3>

    <div id="selected-users">
        <span class="num-users">0</span> <?php echo _("users selected for import"); ?><span class="users"></span>
    </div>

    <div style="display: table; min-height: 100px;">
        <div id="root" style="min-width: 200px; display: table-cell; background-color: #F9F9F9; border-right: 1px solid #CCC; vertical-align: top;"></div>
        <div id="view" style="min-width: 400px; max-width: 600px; display: table-cell; vertical-align: top;">
            <ul class="ad-list user-list">
                <li>&nbsp;</li>
            </ul>
        </div>
    </div>

    <div class="import-button">
        <form action="?cmd=import" method="post">
            <input type="hidden" value="" name="objs" id="objs">
            <button class="btn btn-sm btn-primary" type="submit" id="select-users"><?php echo _("Add Selected Users"); ?></button>
        </form>
    </div>
    
    <script language="javascript" type="text/javascript">
    // Store the selected users for multiple requests
    var SELECTED_USERS = [];
    var SELECTED_USERNAMES = [];

    $(document).ready(function() {

        // Get the default root folders
        ad_generate_root();

        // When clicking on a folder we actually show the users/folders
        $('#root').on('click', '.ad-folder', function(e) {
            if (!$(this).parents('ul').hasClass('sub-list') && !$(this).hasClass('active') && $(this).parent().has('ul').length == 0) {
                $('.sub-list').remove();
            }

            $('.ad-folder').removeClass('active');
            $(this).addClass('active');

            var path = $(this).data('path');
            var type = $(this).data('type');
            grab_ad_obj('view', type, path, this);
        });

        // Select a user
        $('#view').on('change', '.ad-checkbox', function(e) {
            if ($(this).is(":checked")) {
                if (SELECTED_USERS.indexOf($(this).val()) == -1) {
                    SELECTED_USERS.push($(this).val());
                }
                if (SELECTED_USERNAMES.indexOf($(this).data('username')) == -1) {
                    SELECTED_USERNAMES.push($(this).data('username'));
                }
            } else {
                // Remove user from the list if we are un-checking it
                var i = SELECTED_USERS.indexOf($(this).val());
                if (i > -1) { SELECTED_USERS.splice(i, 1); }
                var i = SELECTED_USERNAMES.indexOf($(this).data('username'));
                if (i > -1) { SELECTED_USERNAMES.splice(i, 1); }
            }

            // Update user count at bottom of page
            var num = SELECTED_USERNAMES.length;
            $('#selected-users .num-users').html(num);
            var html = "";
            if (num > 0) {
                html = ": "+SELECTED_USERNAMES.join(', ');
            }
            $('#selected-users .users').html(html);

            $('#objs').val(SELECTED_USERS.join('|'));
        });

        $("#view").on('click', '.user-toggle-show-dn', function(e) {
            var userdn = $(this).parents('li').find('.user-dn');
            if (userdn.css("display") == "none") {
                userdn.show();
                $(this).removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
            } else {
                userdn.hide();
                $(this).removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
            }
        });

        $("#view").on('click', '.toggle-users', function(e) {
            var text = $(this).parents('label').find('span');
            if ($(this).attr('checked')) {
                $('.ad-checkbox:not(:disabled)').attr('checked', true).trigger('change');
                text.html('<?php echo _("Select None"); ?>');
            } else {
                $('.ad-checkbox:not(:disabled)').attr('checked', false).trigger('change');
                text.html('<?php echo _("Select All"); ?>');
            }
        });

        $('#select-users').click(function() {
            if ($('#objs').val() == '') {
                alert("<?php echo _('You must select at least one users to import.'); ?>");
                return false;
            }
        });

    });
            
    function grab_ad_obj(target_form, type, path, folder)
    {
        already_loaded = $(folder).parent().has('ul').length;
        var json_path = JSON.stringify(path);
        var target_form = "#" + target_form;
        
        if (already_loaded == 0) {
            $.ajax({
                type: "POST",
                url: "index.php",
                data: { cmd: "display_nav_window", object_type: type, target_path: json_path },
                success: function(response) {
                    if (response != "") {
                        // Check the level of the folder and add padding if necessary
                        var sub = $(folder).parents('ul').length;
                        var x = sub * 20;
                        $(folder).parent().append(response);
                        $(folder).parent().find('ul').find('span.ad-folder').css('padding-left', x+'px');
                    }
                    grab_ad_users(target_form, type, json_path);
                },
                error: function(response) { console.log("Error: Unable to connect to LDAP server."); }
            });
        } else {
            grab_ad_users(target_form, type, json_path);
        }
    }

    function ad_generate_root() {
        var target = "#root";
        var type = "container";
        $.ajax({
            type: "POST",
            url: "index.php",
            data: { cmd: "display_nav_window", object_type: type, target_path: "", new_list: "1" },
            success: function(response) {
                $(target).html(response);
                var path = '/';
                var type = 'organizationalUnit';
                grab_ad_obj('view', type, path, this);
            },
            error: function(response) { console.log("Error: Unable to connect to LDAP server."); }
        });
    }

    function grab_ad_users(target_form, type, json_path) {
        $.ajax({
            type: "POST",
            url: "index.php",
            data: { cmd: "display_users", object_type: type, target_path: json_path, selected: JSON.stringify(SELECTED_USERS) },
            success: function(response) {
                $(target_form).html(response);

                // Go through and verify that all users are checked that need to be

            },
            error: function(response) { console.log("Error: Unable to connect to LDAP server."); }
        });
    }
    
    function toggle_boxes(element) {
        $(element.parentNode).children().attr("checked", "true");
        toggle_user_add();
    }
    </script>
<?php
}

function collect_credentials()
{
    // Grab the posted cerdentials to show again if there was an error
    $username = grab_request_var('username', '');
    $password = grab_request_var('password', '');

    // Get top level container
    $servers_raw = get_option("ldap_ad_integration_component_servers");
    if ($servers_raw == "") { $servers = array(); } else {
        $servers = unserialize(base64_decode($servers_raw));
    }

    $output = '
    <p>'._("Log into your LDAP / Active Directory administrator or privileged account to be able to import users.").'</p>
    <form method="post" style="margin: 30px 0;">
        <div style="margin-bottom: 10px;">
            <input type="text" size="25" placeholder="Username" value="'.$username.'" name="username" id="ad_username" class="textfield form-control">
        </div>
        <div style="margin-bottom: 10px;">
            <input type="password" placeholder="Password" size="25" value="'.$password.'" name="password" id="ad_password" class="textfield form-control">
        </div>
        <div style="margin-bottom: 10px;">
            <select name="server_id" class="form-control">';

            foreach ($servers as $server) {
                if ($server['conn_method'] == "ad") {
                    $display_servers = $server['ad_domain_controllers'];
                } else {
                    $display_servers = $server['ldap_host'];
                }
                $output .= '<option value="'.$server['id'].'">'.ldap_ad_display_type($server['conn_method']).' - '.$display_servers.'</option>';
            }

    $output .= '</select>
        </div>
        <input type="hidden" name="cmd" value="landing_page">
        <button type="submit" class="btn btn-sm btn-primary" name="next">'._("Next").' <i class="fa fa-chevron-right r"></i></button>
    </form>
    <a href="'.get_component_url_base("ldap_ad_integration").'/manage.php">'._("Manage Authentication Servers").'</a>';

    echo $output;
}

function is_computer($ad_array) {
    $comp = grab_array_var($ad_array["objectclass"], "4", "");
    if ($comp == "computer") {
        return true;
    }
    return false;
}

function is_user($ad_obj) {
    if ($_SESSION['ldap_ad_server']['conn_method'] == "ad") {
        $comp = grab_array_var($ad_obj["objectclass"], "4", "");
        $person = grab_array_var($ad_obj["objectclass"], "1", "");
        if (empty($comp) && $person == "person") {
            return true;
        }
    } else if ($_SESSION['ldap_ad_server']['conn_method'] == "ldap") {
        $person = strtolower(grab_array_var($ad_obj["objectclass"], "0", ""));
        $types = array('inetorgperson', 'person', 'organizationalperson');
        if (in_array($person, $types)) {
            return true;
        }
    }
    return false;
}

function parse_object($select) {
    $prefix_length = 3;
    $prefix = substr($select, 0, $prefix_length);
    $postfix = substr($select, 3);
    return array ($prefix, $postfix);
}

function grab_ad_folders($folder="", $type="container")
{
    $ldap_obj = create_obj();
    
    if ($type == "organizationalUnit") {
        if ($ldap_obj->type == "ad") {
            $list_array = $ldap_obj->folder()->listing($folder, adLDAP::ADLDAP_FOLDER, false);
        } else {
            $list_array = $ldap_obj->folder_listing($folder, basicLDAP::LDAP_FOLDER);
        }
        return check_validity($list_array);
    } else if ($type == "container" || $type == "nsContainer") {
        if ($ldap_obj->type == "ad") {
            $list_array = $ldap_obj->folder()->listing($folder, adLDAP::ADLDAP_CONTAINER, false);
        } else {
            $list_array = $ldap_obj->folder_listing($folder, basicLDAP::LDAP_CONTAINER);
        }
        return check_validity($list_array);
    } else if ($type == "group") {
        $folder = grab_array_var($folder, "0");
        $ad_array = $ldap_obj->group()->members($folder);
        return $ad_array;
    }

    return false;
}

function check_validity($ad_array) {
    $count = grab_array_var($ad_array, "count", 0);
    if (!($count == 0)) {
        return $ad_array;
    } else {
        return false;
    }
}

function grab_type($obj) {
    $item = grab_array_var($obj, "objectclass", "");
    if (!empty($item)) {
        $type = grab_array_var($item, "1", "");
        if (empty($type)) {
            $type = grab_array_var($item, "0", "");
        }
        return $type;
    }
}

function grab_user_name($type, $obj) {
    if ($type == "person") {
        $item = grab_array_var($obj, "samaccountname");
        if (!empty($item)) {
            return grab_array_var($item, "0", "");
        }
    } else if ($type == "inetOrgPerson") {
        $item = grab_array_var($obj, "uid");
        if (!empty($item)) {
            return grab_array_var($item, "0", "");
        }
    }
}

function grab_dn($obj) {
    $item = grab_array_var($obj, "dn");
    $item = str_replace(array('\,', '\2C'), '&#44;', $item);
    if (!($item == "")) {
        $dn = explode(",", $item);
        $value = explode("=", grab_array_var($dn, "0"));
        return grab_array_var($value, "1");
    }
}

function grab_full_dn($obj) {
    return grab_array_var($obj, "dn", grab_dn($obj));
}

function grab_path($obj) {
    $item = grab_array_var($obj, "dn", "");
    $item = str_replace(array('\,', '\2C'), '&#44;', $item);
    $path = array();
    
    if (!($item == "")) {
        $fully_qualified = explode(",", $item);
        foreach ($fully_qualified as $branch) {
            $value = explode("=", $branch);
            $id = grab_array_var($value, "0");
            if (strtoupper($id) == "OU" || strtoupper($id) == "CN") {
                $ou_location = grab_array_var($value, "1");
                if (!($ou_location == "")){
                    array_push($path, $ou_location);
                }
            }
        }
    }
    return $path;
}


function strip_dc($val_arr) {
    $return_arr = array();
    foreach ($val_arr as $val) {
        $pair = explode("=", $val);
        $type = grab_array_var($pair, "0", "");
        if (!(strtoupper($type) == "DC" || $type == "")) {
            $key = grab_array_var($pair, "1", "");
            array_push($return_arr, $key);
        }
    }
    return $return_arr;
}

function grab_root($obj) {
    $dn = grab_array_var($obj, "dn", "");
    $dn = str_replace(array('\,', '\2C'), '&#44;', $dn);
    $location = "";
    if (!($dn == "")) {
        $val = explode(",", $dn);
        $v = array_shift($val);
        $ad_obj = strip_dc($val);
        $container_name = grab_array_var($ad_obj, "0", "");
        return $container_name;
    }
    return $container_name;
}

function grab_sam($obj) {
    $sam = grab_array_var($obj, "samaccountname", "");
    $sam_account_name = "Unknown";
    if (!empty($sam)) {
        $sam_account_name = grab_array_var($sam, "0", "Unknown");
    }
    return $sam_account_name;
}

function display_nav_window($array_to_enum, $new_list=0)
{
    // Hide some folders that shouldn't be shown because they are very VERY unlikely to have users in them
    // unless someone likes putting their users in strange places...
    $dont_show = array("System", "Program Data", "ForeignSecurityPrincipals", "Managed Service Accounts");

    if ($new_list) {
        echo '<ul class="ad-list folder-list">';
    } else {
        echo '<ul class="ad-list sub-list">';
    }

    if (!($array_to_enum == false)) {
        foreach ($array_to_enum as $obj) {
            if (is_array($obj)) {
                $path = json_encode(grab_path($obj));
                $dn = grab_dn($obj);
                $type = grab_type($obj);
                $stype = strtolower($type);

                # Types of navigational structures (all lowercase)
                $containers = array('organizationalunit', 'container', 'nscontainer', 'group');

                if (in_array($stype, $containers)) {
                    
                    // Skip if the object is something we don't need to display
                    if (in_array($dn, $dont_show)) {
                        continue;
                    }

                    if ($stype == "group") { $image = "group.png"; }
                    if ($stype == "container" || $type == "nsContainer") { $image = "folder.png"; }
                    if ($stype == "organizationalunit") { $image = "folder_page.png"; }
                    ?>
                    <li>
                        <span class="ad-folder" data-path='<?php echo $path; ?>' data-type="<?php echo $type; ?>">
                            <img class="table-icon" src="<?php echo theme_image($image); ?>">
                            <?php echo $dn; ?>
                        </span>
                    </li>
                    <?php
                }
            }
        }
    }
    echo '</ul>';
}

function return_image($obj)
{
    if (is_user($obj)) {
        $image = "user.png";
    }
    if (is_computer($obj)) {
        $image = "monitor.png";
    }
    return $image;
}

function display_users($array_to_enum, $location, $type, $selected)
{
    // List of usernames not to show...
    $dont_show = array("krbtgt");

    $location = grab_array_var($location, "0");
    $ldapad_obj = create_obj();
    $person_exists = false;
    $all_checked = true;
    $printed = false;

    if (!empty($array_to_enum)) {
        echo '<ul class="ad-list user-list">';
        if ($type == "group" || $type == "Group") {
            foreach ($array_to_enum as $username)
            {
                $person_exists = true;

                if ($ldapad_obj->type == "ad") {

                    $userinfo = $ldapad_obj->user()->info($username, array("displayname"));
                    $displayname = $userinfo[0]["displayname"][0];
                    $dn = $userinfo[0]['dn'];
                    $obj = $username;

                } else if ($ldapad_obj->type == "ldap") {
                    // Add LDAP groups someday...
                }
                ?>
                <li>
                    <label>
                        <input type="checkbox" class="ad-checkbox" data-username="<?php echo $username; ?>" value="<?php echo $obj; ?>" <?php if (in_array($obj, $selected)) { echo "checked"; } else { $all_checked = false; } ?>>
                        <img class="table-icon" src="<?php echo theme_image("user.png");?>" border="0" alt="<?php echo _("Add New User");?>" title="<?php echo _("Add New User");?>" style="">
                        <?php echo $displayname; ?> (<?php echo $username; ?>)
                    </label>
                    <i class="fa fa-plus-square-o user-toggle-show-dn" title="<?php echo _("Show full DN (destinguished name)"); ?>"></i>
                    <div class="user-dn hide">DN: <?php echo $dn; ?></div>
                </li>
                <?php
            }
        } else {
            foreach ($array_to_enum as $obj)
            {

                if (is_array($obj)) {

                    $type = grab_type($obj);
                    $stype = strtolower($type);

                    # List of types of users/person units (all lowercase)
                    $units = array('person', 'inetorgperson', 'organizationalperson');

                    if (in_array($stype, $units)) {
                        $username = grab_user_name($type, $obj);
                        $dn = grab_full_dn($obj);

                        if (in_array($username, $dont_show)) {
                            continue;
                        }

                        if ($ldapad_obj->type == "ad") {
                            $o = $username;
                        } else if ($ldapad_obj->type == "ldap") {
                            $o = $dn;
                        }

                        $image = return_image($obj);
                        $person_exists = true;
                        ?>
                        <li>
                            <label style="font-weight: normal; line-height: 20px;">
                                <input type="checkbox" class="ad-checkbox" style="margin: 0 5px 0 0; vertical-align: middle;" data-username="<?php echo $username; ?>" value="<?php echo $o; ?>" <?php if (is_computer($obj)) { echo "disabled"; } ?> <?php if (in_array($o, $selected)) { echo "checked"; } else { $all_checked = false; } ?>>
                                <?php if (!empty($image)) { ?><img class="table-icon" src="<?php echo theme_image($image);?>" border="0" alt="<?php echo _("User"); ?>" title="<?php echo _("User"); ?>" style=""><?php } ?>
                                <?php echo grab_dn($obj); ?> <?php if (!empty($username)) { echo '(' . $username . ')'; } ?>
                            </label>
                            <i class="fa fa-plus-square-o user-toggle-show-dn" title="<?php echo _("Show full DN (destinguished name)"); ?>"></i>
                            <div class="user-dn hide">DN: <?php echo $dn; ?></div>
                        </li>
                        <?php
                    }
                }
            }
        }
        if ($person_exists) {
        ?>
            <li style="margin-top: 10px;">
                <label style="line-height: 20px; font-weight: normal;">
                    <input type="checkbox" class="toggle-users" style="margin: 0 5px 0 0; vertical-align: middle;" <?php if ($all_checked) { echo "checked"; } ?>>
                    <span><?php if ($all_checked) { echo _("Select None"); } else { echo _("Select All"); } ?></span>
                </label>
            </li>
        <?php
        } else {
            echo '<li>'._("No users or computers found in this object.").'</li>';
            $printed = true;
        }
        echo '<ul>';
    }

    if (!$person_exists && !$printed) {
        echo '<ul class="ad-list user-list">';
        echo '<li>'._("No users or computers found in this object.").'</li>';
        echo "</ul>";
    }
}

                    
function submit_forms() {
    ?>
    <script language="javascript" type="text/javascript">
    function count(){
        var users_to_add = new Array();
        $( "input:checked" ).each(function () {
            users_to_add.push( $(this).val() );
            })
        var rapture = JSON.stringify(users_to_add);
        window.location.replace("index.php?cmd=user_add&user_additions=" + rapture);
        };
    </script>
    <?php
}


function return_item_type($item) {
    $objectclass = grab_array_var($item, "objectclass");
    $type = $objectclass[1];
    return $type;
}

function return_dn($item) {
    $dn = grab_array_var($item, "distinguishedname");
    $count = grab_array_var($dn, "count");

    if ($count == 1) {
        $this_dn = $dn[0];
        $record_arr = explode(',', $this_dn);
        $cn = $record_arr[0];
        list($name, $val) = split('=', $cn);
    }
    return $val;
}

function create_profile_array($objs)
{
    $ldapad_obj = create_obj();
    $store = array();
    
    foreach ($objs as $obj) {
        if ($ldapad_obj->type == "ad") {
            $userinfo = $ldapad_obj->user()->info($obj, array("mail", "displayname"));
            $username = $obj;
            $email = grab_email($userinfo);
            $display_name = grab_display($userinfo);
            $dn = $userinfo[0]['dn'];
        } else if ($ldapad_obj->type == "ldap") {
            $userinfo = $ldapad_obj->user_info($obj);
            $username = $userinfo[0]['uid'][0];
            $email = grab_email($userinfo);
            $display_name = $userinfo[0]['cn'][0];
            $dn = $userinfo[0]['dn'];
        }

        $u = array(
            "username" => $username,
            "email" => $email,
            "displayname" => $display_name,
            "dn" => $dn
        );
        array_push($store, $u);
    }
    
    return $store;
}

function grab_email($the_user) {
    $arr = grab_array_var($the_user, "0", "");
    $mail = grab_array_var($arr, "mail", "");
    $email = grab_array_var($mail, "0", "");
    return $email;
}

function grab_display($the_user) {
    $display = grab_array_var($the_user[0]["displayname"], "0", "unknown");
    return $display;
}

function show_user_options($users, $revise)
{
    
    if ($revise == false) {
        $users = create_profile_array($users);
    }
    
    // By default we add a new user
    $add = true;
    $user_id = 0;

    // Get languages
    $languages = get_languages();
    $authlevels = get_authlevels();
    $number_formats = get_number_formats();
    $date_formats = get_date_formats();

    // Defaults
    $date_format = DF_ISO8601;
    $number_format = NF_2;
    $email = "";
    $username = "";
    $name = "";
    $level = "user";
    $language = get_option("default_language");
    $theme = get_option("default_theme");
    $create_contact = 1;
    $authorized_for_all_objects = 0;
    $authorized_to_configure_objects = 0;
    $authorized_for_all_object_commands = 0;
    $authorized_for_monitoring_system = 0;
    $advanced_user = 0;
    $readonly_user = 0;

    do_page_start(array("page_title" => _("LDAP / Active Directory Import Users")), true);
    echo '<h1>'._("LDAP / Active Directory Import Users").'</h1>';
    ?>

    <p><?php echo _('Set the preferences and security settings for all users that will be imported. You can also edit multiple user\'s preferences/security settings at once by checking the users you want to edit and selecting the action from the dropdown.'); ?></p>

    <script type="text/javascript">

    $(document).ready(function() {

        function generate_popup(eid, etype) {
            show_throbber();
               
            if (etype == "preferences") {
                edit_title = '<?php echo _("Preferences"); ?>';
                specific_content = '<div id="popup_data" style="width: 400px;"><p>'+$('.preferences-form').clone().html()+'</p>';
            } else if (etype == "security") {
                edit_title = '<?php echo _("Security Settings"); ?>';
                specific_content = '<div id="popup_data" data-eid="'+eid+'" style="width: 400px;"><p>'+$('.security-form').clone().html()+'</p>';
            }

            var content = '<div id="popup_header"><b>'+edit_title+'</b></div>';
            content += specific_content;
            content += '<input type="hidden" value="'+eid+'" id="eid"><input type="hidden" value="'+etype+'" id="etype"><button type="button" class="e-save btn btn-sm btn-primary"><?php echo _("Save"); ?></button> <button type="button" class="e-cancel btn btn-sm btn-default"><?php echo _("Cancel"); ?></button></div>';

            hide_throbber();
            set_child_popup_content(content);
        }

        // Edit user(s) preferences
        $('.edit').click(function () {
            var etype = $(this).data('type');
            var eid = $(this).parents('tr').attr('id');

            generate_popup(eid, etype);

            // Set the popup content
            if ($(this).parents('td').find('i').hasClass('fa-check-circle')) {
                if (etype == "preferences") {

                    var create_contact = true;
                    if ($('#'+eid+' .create_contact').val() == 0) { create_contact = false; }

                    $('#popup_data .create_contact').attr('checked', create_contact);
                    $('#popup_data .language').val($('#'+eid+' .language').val());
                    $('#popup_data .theme').val($('#'+eid+' .theme').val());
                    $('#popup_data .number_format').val($('#'+eid+' .number_format').val());
                    $('#popup_data .date_format').val($('#'+eid+' .date_format').val());

                } else if (etype == "security") {

                    $('#popup_data .auth_level').val($('#'+eid+' .auth_level').val()).trigger('change');
                    update_popup_security_checkboxes(eid);

                }
            }

            display_child_popup();
            whiteout();
        });

        $('body').on('change', '.auth_level', function() {
            if ($(this).val() == "255") {
                $('#popup_data input').each(function(k, v) {
                    if ($(v).hasClass('read_only')) {
                        $(v).attr('disabled', true);
                    } else {
                        $(v).attr('checked', true).attr('disabled', true);
                    }
                });
            } else {
                $('#popup_data input').each(function(k, v) {
                    $(v).attr('disabled', false);
                });
                // Set all the values...
                update_popup_security_checkboxes($('#popup_data').data('eid'));
            }
        });

        $('body').on('click', '.e-cancel', function() {
            close_child_popup();
            clear_whiteout();
            $('.edit-action').val('');
        });

        $('body').on('click', '.e-save', function() {
            var eid = $('#eid').val();
            var etype = $('#etype').val();

            // Update the preferences icon
            if (eid == -1) {
                $('.user-select:checked').each(function(k, v) {
                    var x = $(v).parents('tr').attr('id');
                    $('#'+x+' .'+etype+'-icon').removeClass('fa-circle-o').addClass('fa-check-circle');
                });
            } else {
                $('#'+eid+' .'+etype+'-icon').removeClass('fa-circle-o').addClass('fa-check-circle');
            }

            // Update items in the input fields
            if (etype == "preferences") {
                var create_contact = 0;
                if ($('#popup_data input.create_contact').is(":checked")) { create_contact = 1; }

                if (eid == -1) {
                    $('.user-select:checked').each(function(k, v) {
                        var x = $(v).parents('tr').attr('id');
                        $('#'+x+' .create_contact').val(create_contact);
                        $('#'+x+' .language').val($('#popup_data select.language').val());
                        $('#'+x+' .theme').val($('#popup_data select.theme').val());
                        $('#'+x+' .date_format').val($('#popup_data select.date_format').val());
                        $('#'+x+' .number_format').val($('#popup_data select.number_format').val());
                    });
                } else {
                    $('#'+eid+' .create_contact').val(create_contact);
                    $('#'+eid+' .language').val($('#popup_data select.language').val());
                    $('#'+eid+' .theme').val($('#popup_data select.theme').val());
                    $('#'+eid+' .date_format').val($('#popup_data select.date_format').val());
                    $('#'+eid+' .number_format').val($('#popup_data select.number_format').val());
                }
            } else if (etype == "security") {
                var can_see = 0;
                var can_reconfigure = 0;
                var can_control = 0;
                var can_control_engine = 0;
                var advanced_user = 0;
                var read_only = 0;
                if ($('#popup_data input.can_see').is(":checked")) { can_see = 1; }
                if ($('#popup_data input.can_reconfigure').is(":checked")) { can_reconfigure = 1; }
                if ($('#popup_data input.can_control').is(":checked")) { can_control = 1; }
                if ($('#popup_data input.can_control_engine').is(":checked")) { can_control_engine = 1; }
                if ($('#popup_data input.advanced_user').is(":checked")) { advanced_user = 1; }
                if ($('#popup_data input.read_only').is(":checked")) { read_only = 1; }

                 if (eid == -1) {
                    $('.user-select:checked').each(function(k, v) {
                        var x = $(v).parents('tr').attr('id');
                        $('#'+x+' .auth_level').val($('#popup_data select.auth_level').val());
                        $('#'+x+' .can_see').val(can_see);
                        $('#'+x+' .can_reconfigure').val(can_reconfigure);
                        $('#'+x+' .can_control').val(can_control);
                        $('#'+x+' .can_control_engine').val(can_control_engine);
                        $('#'+x+' .advanced_user').val(advanced_user);
                        $('#'+x+' .read_only').val(read_only);
                    });
                } else {
                    $('#'+eid+' .auth_level').val($('#popup_data select.auth_level').val());
                    $('#'+eid+' .can_see').val(can_see);
                    $('#'+eid+' .can_reconfigure').val(can_reconfigure);
                    $('#'+eid+' .can_control').val(can_control);
                    $('#'+eid+' .can_control_engine').val(can_control_engine);
                    $('#'+eid+' .advanced_user').val(advanced_user);
                    $('#'+eid+' .read_only').val(read_only);
                }
            }

            close_child_popup();
            clear_whiteout();
            verify_users();
            if (eid == -1) {
                $('.edit-action').val('');
            }
        });

        function update_popup_security_checkboxes(eid)
        {
            var can_see = false;
            var can_reconfigure = false;
            var can_control = false;
            var can_control_engine = false;
            var advanced_user = false;
            var read_only = false;
            if ($('#'+eid+' .can_see').val() == 1) { can_see = true; }
            if ($('#'+eid+' .can_reconfigure').val() == 1) { can_reconfigure = true; }
            if ($('#'+eid+' .can_control').val() == 1) { can_control = true; }
            if ($('#'+eid+' .can_control_engine').val() == 1) { can_control_engine = true; }
            if ($('#'+eid+' .advanced_user').val() == 1) { advanced_user = true; }
            if ($('#'+eid+' .read_only').val() == 1) { read_only = true; }

            $('#popup_data .can_see').prop('checked', can_see);
            $('#popup_data .can_reconfigure').prop('checked', can_reconfigure);
            $('#popup_data .can_control').prop('checked', can_control);
            $('#popup_data .can_control_engine').prop('checked', can_control_engine);
            $('#popup_data .advanced_user').prop('checked', advanced_user);
            $('#popup_data .read_only').prop('checked', read_only);
        }

        // Verifys that every user has their preferences/security settings set
        function verify_users()
        {
            var valid = true;
            $('.import-users tbody tr').each(function(i, row) {
                // Loop through each row and make sure everything we need is there...
                if (!$(row).find('.preferences-icon').hasClass('fa-check-circle') || !$(row).find('.security-icon').hasClass('fa-check-circle')) {
                    valid = false;
                    return;
                }
            });
            if (valid) {
                $('.import').prop('disabled', false);
                $('#import-message').hide();
            }
        }

        $('.user-select').click(function() {
            var disable_edit = true;
            $('.user-select').each(function(k, o) {
                if ($(o).is(':checked')) {
                    disable_edit = false;
                    return;
                }
            });

            $('.edit-action').prop('disabled', disable_edit).val('');
        });

        // Stop enter submitting form
        $(window).keydown(function(event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });

        // Check/uncheck all checkboxes
        $('#selectall').click(function() {
            if ($(this).is(":checked")) {
                $('input.user-select').prop('checked', true);
            } else {
                $('input.user-select').prop('checked', false);
                $('.edit-action').prop('disabled', true).val('');
            }
        });

        // Edit multiple user's settings at once
        $('.edit-action').change(function() {
            var etype = $(this).val();

            generate_popup(-1, etype);

            display_child_popup();
            whiteout();
            $("#child_popup_layer").center();
        });

        // Verify import before we send it out
        $('.import').click(function() {

            $('.import').prop('disabled', true).html('<i class="fa fa-spinner fa-pulse"></i> <?php echo _("Verifying"); ?>...');

            // Verify user's info...
            $.post("<?php echo get_component_url_base('ldap_ad_integration'); ?>/ajax.php", { cmd: 'getxiusers' }, function(users) {

                // Check usernames, names, and emails
                var errortext = '';
                var errors = false;
                var uerror = false;
                $('.import-users tbody tr').each(function(i, row) {
                    var username = $(row).find('.username').val();
                    if (username == "") {
                        $(row).find('.username').addClass('form-error');
                        errors = true;
                    } else if ($.inArray(username, users) >= 0) {
                        // Username is already in use
                        $(row).find('.username').addClass('form-error');
                        if (!uerror) {
                            errortext += '<strong><?php echo _("Username(s) already exist"); ?>.</strong> <?php echo _("Usernames must be unique"); ?>. ';
                        }
                        errors = true;
                        uerror = true;
                    }
                    if ($(row).find('.displayname').val() == "") {
                        $(row).find('.displayname').addClass('form-error');
                        errors = true;
                    }
                    if ($(row).find('.email').val() == "") {
                        $(row).find('.email').addClass('form-error');
                        errors = true;
                    }
                });

                if (!errors) {
                    $('form').submit();
                } else {
                    errortext += '<?php echo _("Must enter valid username, display name, and email for each user"); ?>. ';
                    $('.errors').html('<div class="alert alert-danger" style="margin-top: -20px;" role="alert">'+errortext+'</div>');
                    $('.import').prop('disabled', false).html('<?php echo _("Import"); ?> <i class="fa fa-chevron-right r"></i>');
                }

            }, 'json');

        });

        $('body').on('blur', '.form-error', function() {
            if ($(this).val() != "") {
                $(this).removeClass('form-error');
            }
        });

    });
    </script>

    <div id="import-message" class="message" style="max-width: 800px;">
        <ul class="actionMessage">
            <li><?php echo _('In order to finish importing you'); ?> <em><b><?php echo _('must select the preferences and security settings for all users'); ?></b></em>. <?php echo _('For quicker creation, select users with checkboxes and use the dropdown to set the preferences and security settings for multiple users at once.'); ?></li>
        </ul>
    </div>

    <form action="index.php?cmd=finish" method="post" style="margin-top: 25px;">

        <input type="hidden" name="cmd" value="finish">

        <?php echo get_nagios_session_protector(); ?>

        <div class="errors"></div>

        <table class="table table-striped table-bordered import-users" style="width: auto; margin-bottom: 0.5em;">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectall" class="checkbox user-select tt-bind" title="<?php echo _('Toggle checkboxes'); ?>"></th>
                    <th><?php echo _('Username'); ?></th>
                    <th><?php echo _('Display Name'); ?></th>
                    <th><?php echo _('Email'); ?></th>
                    <th style="text-align: center;"><?php echo _('Preferences'); ?></th>
                    <th style="text-align: center;"><?php echo _('Security Settings'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($users as $i => $user) {
                    if (!empty($user)) {
                    ?>
                    <tr id="user-<?php echo $i; ?>">
                        <td style="text-align: center;"><input type="checkbox" class="checkbox user-select"></td>
                        <td>
                            <input class="username form-control" name="users[<?php echo $i; ?>][username]" type="text" value="<?php echo $user['username']; ?>">
                            <input type="hidden" name="users[<?php echo $i; ?>][ldap_ad_username]" value="<?php echo $user['username']; ?>">
                            <input type="hidden" name="users[<?php echo $i; ?>][ldap_ad_dn]" value="<?php echo $user['dn']; ?>">
                        </td>
                        <td><input class="displayname form-control" name="users[<?php echo $i; ?>][displayname]" type="text" value="<?php echo $user['displayname']; ?>"></td>
                        <td><input class="email form-control" name="users[<?php echo $i; ?>][email]" type="text" value="<?php echo $user['email']; ?>" style="width: 200px;"></td>
                        <td style="line-height: 26px; text-align: center; width: 120px;">
                            <i class="fa fa-circle-o preferences-icon" style="margin-right: 4px; font-size: 12px;"></i>
                            <a class="edit" data-type="preferences"><?php echo _('Edit'); ?></a>
                            <input type="hidden" class="create_contact" name="preferences[<?php echo $i; ?>][create_contact]" value="">
                            <input type="hidden" class="language" name="preferences[<?php echo $i; ?>][language]" value="">
                            <input type="hidden" class="theme" name="preferences[<?php echo $i; ?>][theme]" value="">
                            <input type="hidden" class="date_format" name="preferences[<?php echo $i; ?>][date_format]" value="">
                            <input type="hidden" class="number_format" name="preferences[<?php echo $i; ?>][number_format]" value="">
                        </td>
                        <td style="line-height: 26px; text-align: center; width: 140px;">
                            <i class="fa fa-circle-o security-icon" style="margin-right: 4px; font-size: 12px;"></i> 
                            <a class="edit" data-type="security"><?php echo _('Edit'); ?></a>
                            <input type="hidden" class="auth_level" name="security[<?php echo $i; ?>][auth_level]" value="">
                            <input type="hidden" class="can_see" name="security[<?php echo $i; ?>][can_see]" value="">
                            <input type="hidden" class="can_reconfigure" name="security[<?php echo $i; ?>][can_reconfigure]" value="">
                            <input type="hidden" class="can_control" name="security[<?php echo $i; ?>][can_control]" value="">
                            <input type="hidden" class="can_control_engine" name="security[<?php echo $i; ?>][can_control_engine]" value="">
                            <input type="hidden" class="advanced_user" name="security[<?php echo $i; ?>][advanced_user]" value="">
                            <input type="hidden" class="read_only" name="security[<?php echo $i; ?>][read_only]" value="">
                        </td>
                    </tr>
                    <?php
                    }
                }
                ?>
            </tbody>
        </table>

        <select class="edit-action form-control" disabled>
            <option value="" disabled selected><?php echo _('Edit multiple'); ?> ...</option>
            <option value="preferences"><?php echo _('Preferences'); ?></option>
            <option value="security"><?php echo _('Security Settings'); ?></option>
        </select>

    <div class="preferences-form hide">
        <table>
            <tbody>
                <tr>
                    <td><label for="acb"><?php echo _('Create as Monitoring Contact'); ?>:</label></td>
                    <td>
                        <input type="checkbox" class="checkbox create_contact" id="acb" name="create_contact" <?php echo is_checked($create_contact, 1); ?>>
                    </td>
                </tr>
                <tr>
                    <td><label><?php echo _('Language'); ?>:</label></td>
                    <td>
                        <select name="defaultLanguage" class="language languageList dropdown form-control">
                            <?php foreach ($languages as $lang => $title) { ?>
                            <option value="<?php echo $lang; ?>" <?php echo is_selected($language, $lang); ?>><?php echo get_language_nicename($title)."</option>"; ?>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label><?php echo _("XI User Interface Theme"); ?>:</label></td>
                    <td>
                        <select name="theme" class="theme form-control">
                            <option value=""<?php if ($theme == '') { echo " selected"; } ?>><?php echo _("Default"); ?></option>
                            <option value="xi5"<?php if ($theme == 'xi5') { echo " selected"; } ?>><?php echo _("XI 5 - Modern"); ?></option>
                            <option value="xi2014"<?php if ($theme == 'xi2014') { echo " selected"; } ?>><?php echo _("XI 2014"); ?></option>
                            <option value="classic"<?php if ($theme == 'classic') { echo " selected"; } ?>><?php echo _("Classic XI"); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label><?php echo _('Date Format'); ?>:</label></td>
                    <td>
                        <select name="defaultDateFormat" class="date_format dateformatList dropdown form-control">
                            <?php foreach ($date_formats as $id => $txt) { ?>
                            <option value="<?php echo $id; ?>" <?php echo is_selected($id, $date_format); ?>><?php echo $txt; ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label><?php echo _('Number Format'); ?>:</label></td>
                    <td>
                        <select name="defaultNumberFormat" class="number_format numberformatList dropdown form-control">
                            <?php foreach ($number_formats as $id => $txt) { ?>
                            <option value="<?php echo $id; ?>" <?php echo is_selected($id, $number_format); ?>><?php echo $txt; ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="security-form hide">
        <table>
            <tbody>
                <tr>
                    <td>
                        <label for="al"><?php echo _("Authorization Level");?>:</label>
                    </td>
                    <td>
                        <select name="level" id="al" class="auth_level authLevelList dropdown form-control">
                            <?php foreach ($authlevels as $al => $at) { ?>
                            <option value="<?php echo $al; ?>" <?php echo is_selected($level, $al); ?>><?php echo $at."</option>"; ?>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="aao"><?php echo _("Can see all hosts and services");?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" value="1" class="checkbox can_see" id="aao" name="authorized_for_all_objects" <?php echo is_checked($authorized_for_all_objects, 1); ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="atco"><?php echo _("Can (re)configure hosts and services");?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" value="1" class="checkbox can_reconfigure" id="atco" name="authorized_to_configure_objects" <?php echo is_checked($authorized_to_configure_objects, 1); ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="aaco"><?php echo _("Can control all hosts and services");?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" value="1" class="checkbox can_control" id="aaco" name="authorized_for_all_object_commands" <?php echo is_checked($authorized_for_all_object_commands, 1); ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="ams"><?php echo _("Can see/control monitoring engine");?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" value="1" class="checkbox can_control_engine" id="ams" name="authorized_for_monitoring_system" <?php echo is_checked($authorized_for_monitoring_system, 1); ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="au"><?php echo _("Can access advanced features");?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" value="1" class="checkbox advanced_user" id="au" name="advanced_user" <?php echo is_checked($advanced_user, 1); ?>>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="rou"><?php echo _("Has read-only access");?>:</label>
                    </td>
                    <td>
                        <input type="checkbox" value="1" class="checkbox read_only" id="rou" name="readonly_user" <?php echo is_checked($readonly_user, 1); ?>>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="formButtons" style="margin-top: 3em;">
        <a href="index.php?cmd=cancel" class="btn btn-sm btn-default" name="cancelButton"><?php echo _('Cancel'); ?></a>
        <button type="button" class="btn btn-sm btn-primary import" name="updateButton" disabled><?php echo _('Import'); ?> <i class="fa fa-chevron-right r"></i></button>
    </div>

    </form>
<?php
}

/**
 * Function to create Nagios XI users based on the selected AD or LDAP users.
 */
function create_nagios_users($users, $preferences, $security)
{
    global $request;
    $server = grab_array_var($_SESSION, 'ldap_ad_server');

    $count = count($users);
    foreach ($users as $k => $user) {

        $username = $user["username"];
        $email = $user["email"];
        $name = $user["displayname"];
        $ldap_ad_username = $user["ldap_ad_username"];
        $ldap_ad_dn = $user["ldap_ad_dn"];
        $password = random_string(12);
        $api_enabled = 0; //default to off

        /**
        * Adds XI user account, handles all DB interactions for XI user settings and options
        * this function and ALL USER RELATED functions are located in /usr/local/nagiosxi/html/includes/utils-users.inc.php
        *
        * @param string $username  - the username to login with: "jdoe"
        * @param string $password  - the password
        * @param string $name  - the full name of the user: "John Doe"
        * @param int $level  - access level of user account (1 | 255) 255 = admin, 1 = user
        * @param boolean $forcepasschange -  force password change at next login?  Use 0 for mass import
        * @param boolean $addcontact - Should we also add this user as a contact in the Core Config manager?
        * @param string $errmg - REFERENCE variable to external $errmsg that gets returned to browser
        * @return int $user_id  (user_id | null) returns null on failure
        */

        $user_id = add_user_account($username, $password ,$name, $email, $security[$k]['auth_level'], 0, $preferences[$k]['create_contact'], $api_enabled, $errmsg);

        // Don't continue if the user_id doesn't actually exist!
        if (empty($user_id)) {
            continue;
        }

        set_user_meta($user_id, 'language', $preferences[$k]['language']);
        set_user_meta($user_id, 'theme', $preferences[$k]['theme']);
        set_user_meta($user_id, "date_format", $preferences[$k]['date_format']);
        set_user_meta($user_id, "number_format", $preferences[$k]['number_format']);
        set_user_meta($user_id, "authorized_for_all_objects", $security[$k]['can_see']);
        set_user_meta($user_id, "authorized_to_configure_objects", $security[$k]['can_reconfigure']);
        set_user_meta($user_id, "authorized_for_all_object_commands", $security[$k]['can_control']);
        set_user_meta($user_id, "authorized_for_monitoring_system", $security[$k]['can_control_engine']);
        set_user_meta($user_id, "advanced_user", $security[$k]['advanced_user']);
        set_user_meta($user_id, "readonly_user", $security[$k]['read_only']);
        
        set_user_meta($user_id, "auth_type", $server['conn_method']);
        set_user_meta($user_id, "auth_server_id", $server['id']);
        set_user_meta($user_id, "ldap_ad_username", $ldap_ad_username);
        set_user_meta($user_id, "ldap_ad_dn", $ldap_ad_dn);
                
        // Update nagios cgi config file
        update_nagioscore_cgi_config();
        
        // Add to audit log
        if ($security[$k]['auth_level'] == L_GLOBALADMIN) {
            send_to_audit_log("User account '".$original_user."' was created with GLOBAL ADMIN privileges from LDAP/AD User Import function.", AUDITLOGTYPE_SECURITY);   
        }
    }

    finish_page($count);
}

function finish_page($count) {
    do_page_start(array("page_title" => _("LDAP / Active Directory Import Users")), true);
    echo '<h1>'._("LDAP / Active Directory Import Users").'</h1>';
    echo '<div class="message"><ul class="infoMessage"><li>'._("Successfully added").' '.$count.' '._("users").'.</li></ul></div>';
    collect_credentials();
}