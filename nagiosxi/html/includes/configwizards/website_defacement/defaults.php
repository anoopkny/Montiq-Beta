<?php
$dirpath = dirname(__FILE__) . "/def_defaults";

if (is_dir($dirpath)) {
    if ($dir = opendir($dirpath)) {
        while (($filename = readdir($dir)) !== false) {
            if ($filename != "." && $filename != ".." && $filename != ".svn") {
                $contents = file_get_contents($dirpath . "/" . $filename);
                $safe_filename = substr($filename, 0, strrpos($filename, '.'));
                $files[$safe_filename] = $contents;
            }
        }
        closedir($dir);
        print json_encode($files);
    }
}
?>