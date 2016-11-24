#!/bin/bash

#snmpwalk configwizard sanity check

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

do_these_files_exist $WIZARDS/snmpwalk/snmpwalk.inc.php /usr/bin/snmpwalk $LIBEXEC/check_snmp

is_wizard $WIZARDS/snmpwalk/snmpwalk.inc.php

can_nagios_execute  $LIBEXEC/check_snmp /usr/bin/snmpwalk 

can_apache_execute  $LIBEXEC/check_snmp /usr/bin/snmpwalk

are_these_packages_installed net-snmp net-snmp-utils 

templates_exist xiwizard_generic_host xiwizard_generic_service xiwizard_genericnetdevice_host xiwizard_snmp_service

commands_exist check_xi_host_ping check_xi_service_snmp 

print_results
