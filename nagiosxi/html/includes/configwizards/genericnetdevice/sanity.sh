#!/bin/bash

#genericdevice configwizard sanity check

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

do_these_files_exist $WIZARDS/genericnetdevice/genericnetdevice.inc.php $LIBEXEC/check_icmp

is_wizard $WIZARDS/genericnetdevice/genericnetdevice.inc.php

can_nagios_execute $LIBEXEC/check_icmp
 
can_apache_execute $LIBEXEC/check_icmp

is_the_sticky_bit_set $LIBEXEC/check_icmp

commands_exist check_xi_service_ping
templates_exist xiwizard_genericnetdevice_ping_service xiwizard_generic_service

print_results

