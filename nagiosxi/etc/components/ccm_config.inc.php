<?php
        /** DO NOT MANUALLY EDIT THIS FILE
        This file is used internally by Nagios CCM.
        Nagios CCM will override this file automatically with the latest settings. */

        $CFG["plugins_directory"] = "/usr/local/nagios/libexec";
        $CFG["command_file"] = "/usr/local/nagios/var/rw/nagios.cmd"; 
        $CFG["default_language"] = "en";
         
        // MySQL database connection info 
        $CFG["db"] = array(
            "server"   => "localhost",
            "port"     => "3306",
            "database" => "nagiosql",
            "username" => "nagiosql",
            "password" => "n@gweb"
            );