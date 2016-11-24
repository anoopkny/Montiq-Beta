<?php
// Nagvis COMPONENT include
//
// Copyright (c) 2010-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

//script will add individual links to the menu for nagvis maps if map config files can be found.  


function nagvis_component_add_maps($address)
{

    $path = '/usr/local/nagvis/etc/maps/';


    if (isset($address)) {
        //add rest of link
        $map_url = 'http://' . $address . '/nagvis/frontend/nagvis-js/index.php?mod=Map&act=view&show=';

        $content = scandir($path);
        if ($content) {
            foreach ($content as $fileinfo) {
                $mi = find_menu_item(MENU_HOME, "menu-home-networkstatusmap", "id");
                if ($mi == null)
                    return;

                $order = grab_array_var($mi, "order", "");
                if ($order == "")
                    return;

                $neworder = $order - 0.1;

                //filter files
                if (($fileinfo[0] != ".") && ($fileinfo != 'autobackup.status') && ($fileinfo[0] != "_")) {
                    //$neworder=$neworder-0.1;
                    $file = basename($fileinfo, '.cfg');
                    $title = ucfirst($file);
                    //add each map as a menu item
                    add_menu_item(MENU_HOME, array(
                        "type" => "link",
                        "title" => $title,
                        "id" => "menu-home-" . $file,
                        "order" => $neworder,
                        "opts" => array(
                            "href" => $map_url . $file,
                        )
                    ));

                }
                //end of IF
            }
            //end of FOREACH
        }
        //end of main IF.

    }
}

?>



	

	
	

		


