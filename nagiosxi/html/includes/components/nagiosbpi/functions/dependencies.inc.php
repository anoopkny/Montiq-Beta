<?php


function bpi_view_dependencies()
{
    $map = get_host_parent_child_array_map();
    return "<pre>" . print_r($map, true) . "</pre>";
}
