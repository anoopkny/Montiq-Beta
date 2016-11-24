#!/bin/bash

#solaris configwizard sanity check

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

do_these_files_exist $WIZARDS/solaris/solaris.inc.php $LIBEXEC/check_nrpe $LIBEXEC/check_icmp

is_wizard $WIZARDS/solaris/solaris.inc.php

can_nagios_execute $LIBEXEC/check_nrpe $LIBEXEC/check_icmp

can_apache_execute $LIBEXEC/check_nrpe $LIBEXEC/check_icmp 

is_the_sticky_bit_set $LIBEXEC/check_icmp

templates_exist xiwizard_generic_service xiwizard_linuxserver_host xiwizard_generic_host xiwizard_nrpe_service

commands_exist check_nrpe check_xi_host_ping

print_results
