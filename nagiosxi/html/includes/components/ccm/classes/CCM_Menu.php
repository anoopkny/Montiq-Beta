<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: CCM_Menu.php
//  Desc: Handles the menu management for the standalone CCM menu.
//

class Main_Menu
{
    var $parentMenus = array();
    private $menuItems = array();

    /**
     * Prints the basic CCM menu when the environment is not Nagios XI
     * $_SESSION['menu'] =='visible'
     */ 
    public function print_menu_html() {
        if ($_SESSION['menu'] == 'visible') {
            print "<div id='mainNavMenu'>"; 
            print "<a href='index.php?cmd=view&type=host'>Hosts</a><br />";
            print "<a href='index.php?cmd=view&type=service'>Services<br />";
            print "<a href='index.php?cmd=view&type=hostgroup'>Host Groups</a><br />";
            print "<a href='index.php?cmd=view&type=servicegroup'>Service Groups</a><br />";
            print "<a href='index.php?cmd=view&type=hosttemplate'>Host Templates</a><br />";
            print "<a href='index.php?cmd=view&type=servicetemplate'>Service Templates</a><br />";
            print "<a href='index.php?cmd=view&type=contact'>Contacts</a><br />";
            print "<a href='index.php?cmd=view&type=contactgroup'>Contact Groups</a><br />";
            print "<a href='index.php?cmd=view&type=contacttemplate'>Contact Templates</a><br />";
            print "<a href='index.php?cmd=view&type=timeperiod'>Timeperiods</a><br />";
            print "<a href='index.php?cmd=view&type=command'>Commands</a><br />";
            print "<a href='index.php?cmd=view&type=hostescalation'>Host Escalations</a><br />";
            print "<a href='index.php?cmd=view&type=serviceescalation'>Service Escalations</a><br />";          
            print "<a href='index.php?cmd=view&type=hostdependency'>Host Dependencies</a><br />";
            print "<a href='index.php?cmd=view&type=servicedependency'>Service Dependencies</a><br />";
            print "<br />";
            print "<a href='index.php?cmd=admin&type=static'>Static Configurations</a><br />";
            print "<a href='index.php?cmd=admin&type=import'>Import Configs</a><br />";
            print "<a href='index.php?cmd=apply'>Write Configs</a><br />";
            print "<a href='index.php?cmd=admin&type=corecfg'>Nagios Main Config</a><br />";
            print "<a href='index.php?cmd=admin&type=cgicfg'>Nagios CGI Config</a><br />";
            print "<br />";
            print "<a href='index.php?cmd=admin&type=user'>Manage CCM Users</a><br />";
            print "<a href='index.php?cmd=admin&type=log'>CCM Log</a><br />";
            print "<a href='index.php?cmd=admin&type=settings'>CCM Settings</a><br />";
            print "</div>"; 
        }
    }   

    /**
     * @param      $href
     * @param      $id
     * @param      $order
     * @param      $title
     * @param bool $target
     * @param bool $class
     */
    public function add_menu_item($href, $id, $order, $title, $target=false, $class=false) {
        $array = array( 'href' =>$href,
                        'id' => $id,
                        'order' =>  $order,
                        'title' => $title,
                        'target' => $target,
                        'class' => $class); 
        $this->menuItems[] = $array;    
    }
}