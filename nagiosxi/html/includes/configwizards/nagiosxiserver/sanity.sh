#!/bin/bash

#nagiosxiserver configwizard sanity check

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

do_these_files_exist $WIZARDS/nagiosxiserver/nagiosxiserver.inc.php $LIBEXEC/check_nagiosxiserver.php $LIBEXEC/check_http $LIBEXEC/check_icmp

is_wizard $WIZARDS/nagiostats/nagiostats.inc.php

can_nagios_execute  $LIBEXEC/check_nagiosxiserver.php $LIBEXEC/check_http $LIBEXEC/check_icmp

can_apache_execute  $LIBEXEC/check_nagiosxiserver.php $LIBEXEC/check_http $LIBEXEC/check_icmp

is_the_sticky_bit_set $LIBEXEC/check_icmp

are_these_packages_installed php   

templates_exist xiwizard_nagiosxiserver_http_service xiwizard_nagiosxiserver_service xiwizard_nagiosxiserver_ping_service xiwizard_nagiosxiserver_host xiwizard_generic_host xiwizard_website_http_service xiwizard_website_ping_service xiwizard_generic_service

commands_exist check_xi_nagiosxiserver check_xi_host_ping check_xi_service_http check_xi_service_ping

print_results
