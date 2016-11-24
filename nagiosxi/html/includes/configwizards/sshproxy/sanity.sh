#!/bin/bash

#sshproxy configwizard sanity check

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

do_these_files_exist $WIZARDS/sshproxy/sshproxy.inc.php $LIBEXEC/check_ssh

is_wizard $WIZARDS/sshproxy/sshproxy.inc.php

can_nagios_execute  $LIBEXEC/check_ssh

can_apache_execute  $LIBEXEC/check_ssh

are_these_packages_installed openssh 

templates_exist generic_service xiwizard_generic_service xiwizard_linuxserver_ping_service xiwizard_linuxserver_host

commands_exist check_xi_by_ssh check_xi_service_ping

print_results
