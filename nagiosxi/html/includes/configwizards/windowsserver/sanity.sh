#!/bin/bash

#windowsserver config wizard  sanity check

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

do_these_files_exist $WIZARDS/windowsserver/windowsserver.inc.php

is_wizard $WIZARDS/windowsserver/windowsserver.inc.php

templates_exist xiwizard_windowsserver_host wizard_windowsserver_nsclient_service xiwizard_windowsserver_ping_service

commands_exist check_nt check_nrpe

does_user_own nagios $WIZARDS/windowsserver/windowsserver.inc.php $LIBEXEC/check_nrpe

does_user_own root $LIBEXEC/check_nt 

does_group_groupown nagios $WIZARDS/windowsserver/windowsserver.inc.php $LIBEXEC/check_nrpe

does_group_groupown root $LIBEXEC/check_nt 

do_these_files_exist $LIBEXEC/check_nt $LIBEXEC/check_nrpe

print_results
