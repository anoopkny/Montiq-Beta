#!/bin/bash

#macosx configwizard sanity check

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

do_these_files_exist $WIZARDS/macosx/macosx.inc.php $LIBEXEC/check_nrpe $LIBEXEC/check_icmp

is_wizard $WIZARDS/macosx/macosx.inc.php

can_nagios_execute $LIBEXEC/check_nrpe $LIBEXEC/check_icmp
 
can_apache_execute $LIBEXEC/check_nrpe $LIBEXEC/check_icmp

is_the_sticky_bit_set $LIBEXEC/check_icmp

commands_exist check_nrpe check_xi_service_ping
templates_exist xiwizard_nrpe_service xiwizard_linuxserver_ping_service xiwizard_generic_service xiwizard_linuxserver_host

print_results

