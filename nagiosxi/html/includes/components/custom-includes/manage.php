<?php
//
// Custom Includes Component
// Copyright (c) 2016 Nagios Enterprises, LLC. All rights reserved.
//

require_once(dirname(__FILE__).'/../../common.inc.php');
require_once(dirname(__FILE__).'/../componenthelper.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables, check prereqs and authentication
grab_request_vars();
check_prereqs();
check_authentication(false);

page_router();

function page_router()
{
    $cmd = grab_request_var('cmd', '');

    switch ($cmd) {

        case 'save':
            save_includes_changes();
            break;

        case 'upload':
            upload_files();
            break;

        case 'delete':
            delete_uploaded_file();
            break;

        case 'rename':
            rename_file();
            break;

        default:
            display_page();
            break;

    }
}

function rename_file()
{
    $error = false;
    $id = grab_request_var('id', '');
    $name = grab_request_var('name', '');
    $newname = stripcslashes(trim($name));
    if (empty($id) || empty($name)) { $error = true; }

    if (!$error) {
        $images = get_array_option('custom_includes_files_images');
        $i = $images[$id];

        // Rename the file and send back json
        $x = rename($i['dir'].'/'.$i['name'], $i['dir'].'/'.$newname);
        if ($x === false) {
            $error = true;
        } else {
            // Rename the database entry
            $images[$id]['name'] = $newname;
            set_array_option('custom_includes_files_images', $images);
        }  
    }

    if ($error) {
        echo json_encode(array('error' => 1, 'msg' => _('Could not rename file. Check file permissions.')));
    } else {
        echo json_encode(array('error' => 0, 'msg' => _('Renamed successfully')));
    }
}

function upload_files()
{
    $data = array();

    if (!empty($_FILES['uploadedfile']['name'])) {

        $error = false;
        $files = array();
        $type = 'css';

        $uploaddir = dirname(__FILE__).'/';

        // Error uploading file - probably due to file size limit
        if ($_FILES['uploadedfile']['error'] === UPLOAD_ERR_INI_SIZE) {
            $error = true;
            $msg = _('Could not upload file') . ' <b>' . $_FILES['uploadedfile']['name'] . '</b><br>' . _('The file you are uploading is too big. Check your PHP file size settings.') . '<br>(upload_max_filesize = ' . ini_get('upload_max_filesize') . ', post_max_size = ' . ini_get('post_max_size') . ')';
        }

        if (!$error) {
            $type = verify_uploaded_file($_FILES['uploadedfile']);
            if (empty($type)) { 
                $error = true;
                $msg = _('Could not upload file') . ' <b>' . $_FILES['uploadedfile']['name'] . '</b><br>' . _('This file does not match one of the valid file types above.');
            }
        }

        if (!$error) {
            $dir = $uploaddir . $type . '/';
            $filename = basename($_FILES['uploadedfile']['name']);
            if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $dir . $filename)) {
                $files[] = $dir . $_FILES['uploadedfile']['name'];
            } else {
                $error = true;
                $msg = _('Could not upload file') . ' <b>' . $_FILES['uploadedfile']['name'] . '</b><br>' . _('Verify permissions on upload directories.');
            }
        }

        // If error occurs, let us know
        if (!$error) {
            $_SESSION['flash_msg_type'] = 0;
            $_SESSION['flash_msg'] = _('Successfully uploaded file.');

            $stored_files = get_array_option('custom_includes_files_'.$type);

            // Check if file already exists in DB
            $file_exists = false;
            $file_index = 0;
            foreach ($stored_files as $i => $f) {
                if ($f['name'] == $filename) {
                    $file_exists = true;
                    $file_index = $i;
                    break;
                }
            }

            if (!$file_exists) {
                $f = array('id' => uniqid(),
                           'dir' => $dir,
                           'name' => $filename,
                           'size' => filesize($dir.$filename),
                           'inc' => 0);
                $stored_files[$f['id']] = $f;
            } else {
                $f[$file_index]['size'] = filesize($dir.$filename);
            }

            // Save stored files
            set_array_option('custom_includes_files_'.$type, $stored_files);

        } else {

            // Die and send error to session message for page load
            $_SESSION['flash_msg_type'] = 1;
            $_SESSION['flash_msg'] = $msg;

        }
    }

    header("Location: manage.php#".$type);
}

function delete_uploaded_file()
{
    $obj_id = grab_request_var('id', '');
    $type = grab_request_var('type', '');
    if (empty($obj_id) || empty($type) || !in_array($type, array('css', 'javascript', 'images'))) { header("Location: manage.php"); }

    // Grab the actual item
    $files = get_array_option('custom_includes_files_'.$type, array());
    $file = array();
    $index = null;
    foreach ($files as $i => $f) {
        if ($f['id'] == $obj_id) {
            $file = $f;
            $index = $i;
            break;
        }
    }

    // Delete the file
    $x = unlink($file['dir'].$file['name']);
    if ($x) {
        if ($index !== null) {
            unset($files[$index]);
            set_array_option('custom_includes_files_'.$type, $files);
        }
        $_SESSION['flash_msg_type'] = 0;
        $_SESSION['flash_msg'] = _('Successfully removed file.');
    } else {
        $_SESSION['flash_msg_type'] = 1;
        $_SESSION['flash_msg'] = _('Could not remove selected file. Location:') . ' ' . $file['dir'] . $file['name'];
    }

    // Send message and return
    header("Location: manage.php#".$type);
}

function verify_uploaded_file($file)
{
    $dir = '';
    $css = array('text/css');
    $js = array('application/javascript', 'application/x-javascript', 'application/octet-stream', 'text/html');
    $images = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/bmp');

    // Verify file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $ext = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Fix for FF (is bmp && octet)
    if (in_array($ext, $images) || ($file['type'] == 'image/bmp' && $ext == 'application/octet-stream')) {
        $dir = 'images';
    } else if ($ext == 'text/plain' || $ext == 'text/html' || $ext == 'text/x-c' || $ext == 'text/x-c++') {
        if (in_array($file['type'], $css)) {
            $dir = 'css';
        } else if (in_array($file['type'], $js)) {
            $dir = 'javascript';
        }
    }

    return $dir;
}

function save_includes_changes()
{
    // Reorder css
    $css_order = grab_request_var('css_order', array());
    $css = get_array_option('custom_includes_files_css');
    if (count($css) > 0) {
        $inc_css = grab_request_var('css', array());
        $new_css = array();
        foreach ($css_order as $co) {
            if (in_array($co, $inc_css)) { $css[$co]['inc'] = 1; }
            else { $css[$co]['inc'] = 0; }
            $new_css[$co] = $css[$co];
        }
        set_array_option('custom_includes_files_css', $new_css);
    }

    // Reorder javascript
    $javascript_order = grab_request_var('javascript_order', array());
    $js = get_array_option('custom_includes_files_javascript');
    if (count($js) > 0) {
        $inc_js = grab_request_var('javascript', array());
        $new_js = array();
        foreach ($javascript_order as $jo) {
            if (in_array($jo, $inc_js)) { $js[$jo]['inc'] = 1; }
            else { $js[$jo]['inc'] = 0; }
            $new_js[$jo] = $js[$jo];
        }
        set_array_option('custom_includes_files_javascript', $new_js);
    }

    # Send back to the tab they were on
    $tab = '';
    $ontab = grab_request_var('ontab', '');
    if (!empty($ontab)) {
        if ($ontab == 'javascripts') {
            $tab = '#javascript';
        } else if ($ontab == 'css') {
            $tab = '#css';
        } else if ($ontab == 'images') {
            $tab = '#images';
        }
    }
    header("Location: manage.php".$tab);
}

function display_page()
{
    do_page_start(array("page_title" => _("Custom Includes")), true);

    $image_files = get_array_option('custom_includes_files_images');
    $css_files = get_array_option('custom_includes_files_css');
    $javascript_files = get_array_option('custom_includes_files_javascript');
    
    // Verify that files still exist
    foreach ($image_files as $i => $img) {
        if (!file_exists($img['dir'].$img['name'])) {
            unset($image_files[$i]);
            set_array_option('custom_includes_files_images', $image_files);
        }
    }
    foreach ($css_files as $i => $img) {
        if (!file_exists($img['dir'].$img['name'])) {
            unset($css_files[$i]);
            set_array_option('custom_includes_files_css', $css_files);
        }
    }
    foreach ($javascript_files as $i => $img) {
        if (!file_exists($img['dir'].$img['name'])) {
            unset($javascript_files[$i]);
            set_array_option('custom_includes_files_javascript', $javascript_files);
        }
    }
?>

<script type="text/javascript">
$(document).ready(function() {
    
    $('#tabs').tabs({
        activate: function(e, ui) {
            var href = $(ui.newTab).find('a').prop('href');
            var hash = href.substring(href.indexOf('#')+1);
            if (history.pushState) {
                history.pushState(null, null, '#'+hash);
            } else {
                window.location.hash = hash;
            }
            $('#ontab').val(hash);
        }
    }).show();
    $('.sortable').sortable();

    $('.rename').click(function() {
        var content = '<h2><?php echo _("Rename File"); ?></h2><div><input type="text" class="form-control" data-obj-id="'+$(this).data('obj-id')+'" id="rename-form" value="'+$(this).data('name')+'" style="width: 400px; margin: 5px 0 10px; 0;"></div><button class="btn btn-sm btn-primary btn-rename"><?php echo _("Rename"); ?></button>';
        set_child_popup_content(content);
        display_child_popup();
    });

    $('#child_popup_content').on('click', '.btn-rename', function() {
        $.post('manage.php', { cmd: 'rename', id: $('#rename-form').data('obj-id'), name: $('#rename-form').val() }, function(data) {
            if (!data.error) {
                var id = $('#rename-form').data('obj-id');
                $('.obj-'+id+' td.name').html($('#rename-form').val());
                $('.obj-'+id+' a.rename').data('name', $('#rename-form').val());
                set_child_popup_content('');
                close_child_popup();
            } else {
                alert(data.msg);
            }
        }, 'json');
    });

});
</script>

<h1><?php echo _('Custom Includes'); ?></h1>
<p>
    <?php echo _('Allowed file types for upload are: .css, .js, .png, .jpg, .jpeg, .gif, and .bmp. Uploading the same filename will overwrite the original.'); ?>
    <em><?php echo _('Note: CSS and JS files will be included in the order they appear in the list below. To change include order, drag and drop the rows and then save changes.'); ?></em>
</p>

<?php
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    $type = $_SESSION['flash_msg_type'];
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_msg_type']);

    // Display flash message
    if ($type == 1) { $mt = 'errorMessage'; } else { $mt = 'actionMessage'; }
    echo '<div class="message"><ul class="'.$mt.'"><li>'.$msg.'</li></ul></div>';
}
?>

<div style="margin: 10px 0 20px 0;">
    <form action="manage.php?cmd=upload" method="post" enctype="multipart/form-data" style="margin: 0;">
        <div class="input-group" style="width: 280px; float: left; margin-right: 5px;">
            <span class="input-group-btn">
                <span class="btn btn-sm btn-default btn-file">
                    <?php echo _('Browse'); ?>&hellip; <input type="file" name="uploadedfile">
                </span>
            </span>
            <input type="text" class="form-control" style="width: 240px;" readonly>
        </div>
        <button type="submit" class="btn btn-sm btn-default"><i class="fa fa-upload l"></i> <?php echo _('Upload'); ?></button>
    </form>
    <div class="clear"></div>
</div>

<form action="manage.php?cmd=save" method="post" style="margin: 0;">
    <input type="hidden" name="ontab" id="ontab">
    <div id="tabs" class="hide">
        <ul>
            <li><a href="#css"><i class="fa fa-14 fa-file-text-o"></i> <?php echo _('CSS'); ?></a></li>
            <li><a href="#javascript"><i class="fa fa-14 fa-file-code-o"></i> <?php echo _('JavaScripts'); ?></a></li>
            <li><a href="#images"><i class="fa fa-14 fa-file-image-o"></i> <?php echo _('Images'); ?></a></li>
        </ul>
        <div id="css">
            <table class="table table-outside-bordered table-hover table-no-margin">
                <thead>
                    <tr>
                        <th style="width: 36px;"></th>
                        <th><?php echo _('File Name'); ?></th>
                        <th style="width: 140px;"><?php echo _('File Size'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php echo _('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="<?php if (count($css_files) > 0) { echo 'sortable'; } ?>">
                    <?php
                    if (count($css_files) > 0) {
                        foreach ($css_files as $i => $f) {
                            ?>
                            <tr>
                                <td style="text-align: right;">
                                    <input type="checkbox" name="css[]" value="<?php echo $f['id']; ?>" class="tt-bind" title="<?php echo _('Include'); ?>" style="margin: 0; vertical-align: text-bottom;" <?php if ($f['inc']) { echo 'checked'; } ?>>
                                    <input type="hidden" name="css_order[]" value="<?php echo $f['id']; ?>">
                                </td>
                                <td><?php echo $f['name']; ?></td>
                                <td style="width: 140px;"><?php echo human_readable_bytes($f['size']); ?></td>
                                <td style="text-align: center; width: 80px;">
                                    <a href="manage.php?cmd=delete&type=css&id=<?php echo $f['id']; ?>" class="tt-bind" data-placement="left" title="<?php echo _('Delete'); ?>"><img src="<?php echo theme_image('cross.png'); ?>"></a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="4"><em><?php echo _('No CSS files have been added.'); ?></em></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div id="javascript">
            <table class="table table-outside-bordered table-hover table-no-margin">
                <thead>
                    <tr>
                        <th style="width: 36px;"></th>
                        <th><?php echo _('File Name'); ?></th>
                        <th style="width: 140px;"><?php echo _('File Size'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php echo _('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="<?php if (count($javascript_files) > 0) { echo 'sortable'; } ?>">
                    <?php
                    if (count($javascript_files) > 0) {
                        foreach ($javascript_files as $i => $f) {
                            ?>
                            <tr>
                                <td style="text-align: right;">
                                    <input type="checkbox" name="javascript[]" value="<?php echo $f['id']; ?>" class="tt-bind" title="<?php echo _('Include'); ?>" style="margin: 0; vertical-align: text-bottom;" <?php if ($f['inc']) { echo 'checked'; } ?>>
                                    <input type="hidden" name="javascript_order[]" value="<?php echo $f['id']; ?>">
                                </td>
                                <td><?php echo $f['name']; ?></td>
                                <td style="width: 140px;"><?php echo human_readable_bytes($f['size']); ?></td>
                                <td style="text-align: center; width: 80px;">
                                    <a href="manage.php?cmd=delete&type=javascript&id=<?php echo $f['id']; ?>" class="tt-bind" data-placement="left" title="<?php echo _('Delete'); ?>"><img src="<?php echo theme_image('cross.png'); ?>"></a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="4"><em><?php echo _('No JavaScript files have been added.'); ?></em></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div id="images">
            <table class="table table-outside-bordered table-hover table-no-margin">
                <thead>
                    <tr>
                        <th><?php echo _('File Name'); ?></th>
                        <th style="width: 140px;"><?php echo _('File Size'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php echo _('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($image_files) > 0) {
                        foreach ($image_files as $i => $f) {
                            ?>
                            <tr class="obj-<?php echo $i; ?>">
                                <td class="name"><?php echo $f['name']; ?></td>
                                <td style="width: 140px;"><?php echo human_readable_bytes($f['size']); ?></td>
                                <td style="text-align: center; width: 80px;">
                                    <a href="images/<?php echo $f['name']; ?>" target="_new" class="tt-bind" data-placement="left" title="<?php echo _('Preview'); ?>"><img src="<?php echo theme_image('vssummary.png'); ?>"></a>
                                    <a target="_new" class="tt-bind rename" data-name="<?php echo $f['name']; ?>" data-obj-id="<?php echo $f['id']; ?>" data-placement="left" title="<?php echo _('Rename'); ?>"><img src="<?php echo theme_image('pencil.png'); ?>"></a>
                                    <a href="manage.php?cmd=delete&type=images&id=<?php echo $f['id']; ?>" class="tt-bind" data-placement="left" title="<?php echo _('Delete'); ?>"><img src="<?php echo theme_image('cross.png'); ?>"></a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="3"><em><?php echo _('No images have been added.'); ?></em></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 10px;">
        <button type="submit" class="btn btn-sm btn-primary"><?php echo _('Save Changes'); ?></button>
    </div>
</form>

<?php
    do_page_end(true);
}
