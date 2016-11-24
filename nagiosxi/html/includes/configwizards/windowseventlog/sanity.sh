#!/bin/bash

#windowseventlog config wizard  sanity check

function zipit() {
	:
}

#~ Include general library (should go in all sanity scripts.)
if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

do_these_files_exist $WIZARDS/windowseventlog/windowseventlog.inc.php

is_wizard $WIZARDS/windowseventlog/windowseventlog.inc.php

templates_exist xiwizard_windowsserver_host xiwizard_windowseventlog_service

does_user_own nagios $WIZARDS/windowseventlog/config.xml 

does_group_groupown nagios $WIZARDS/windowseventlog/config.xml 

print_results
