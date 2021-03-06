#!/bin/bash

#switch configwizard sanity check

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

do_these_files_exist $WIZARDS/switch/switch.inc.php $LIBEXEC/check_ifoperstatus $LIBEXEC/check_rrdtraf /usr/bin/mrtg /usr/bin/rrdtool

is_wizard $WIZARDS/switch/switch.inc.php

can_nagios_execute  $LIBEXEC/check_ifoperstatus $LIBEXEC/check_rrdtraf /usr/bin/mrtg /usr/bin/rrdtool

can_apache_execute  $LIBEXEC/check_ifoperstatus $LIBEXEC/check_rrdtraf /usr/bin/mrtg /usr/bin/rrdtool

are_these_packages_installed net-snmp

templates_exist xiwizard_switch_port_bandwidth_service xiwizard_switch_ping_service xiwizard_switch_host xiwizard_switch_port_status_service xiwizard_generic_host xiwizard_generic_service

commands_exist check_xi_service_ifoperstatus check_xi_service_mrtgtraf check_xi_host_ping

print_results
