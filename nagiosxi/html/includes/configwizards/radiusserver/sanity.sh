#!/bin/bash

#radiusserver configwizard sanity check

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

do_these_files_exist $WIZARDS/radiusserver/radiusserver.inc.php $LIBEXEC/check_radius_adv $LIBEXEC/check_icmp

is_wizard $WIZARDS/radiusserver/radiusserver.inc.php

can_nagios_execute $LIBEXEC/check_radius_adv $LIBEXEC/check_icmp

can_apache_execute $LIBEXEC/check_radius_adv $LIBEXEC/check_icmp 

is_the_sticky_bit_set $LIBEXEC/check_icmp

templates_exist xiwizard_generic_service xiwizard_radiusserver_host xiwizard_generic_host xiwizard_radiusserver_radius_service

commands_exist check_radius_server_adv check_xi_host_ping

print_results
