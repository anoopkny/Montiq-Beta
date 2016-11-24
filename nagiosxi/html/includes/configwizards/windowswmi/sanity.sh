#!/bin/bash

#windowswmi config wizard  sanity check

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

do_these_files_exist $WIZARDS/windowswmi/windowswmi.inc.php

is_wizard $WIZARDS/windowswmi/windowswmi.inc.php

templates_exist xiwizard_windowswmi_host xiwizard_windowswmi_service

commands_exist check_xi_service_wmiplus

does_user_own nagios $WIZARDS/windowswmi/config.xml 

does_user_own root $LIBEXEC/check_wmi*

does_group_groupown nagios $WIZARDS/windowswmi/config.xml 

does_group_groupwn root $LIBEXEC/check_wmi*

do_these_files_exist $LIBEXEC/check_wmi_plus.conf $LIBEXEC/check_wmi_plus.pl $LIBEXEC/check_wmi_plus.ini

print_results
