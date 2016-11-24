#!/bin/bash

#windowssnmp configwizard sanity check

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

do_these_files_exist $WIZARDS/windowssnmp/windowssnmp.inc.php $LIBEXEC/check_snmp_load.pl $LIBEXEC/check_snmp_win.pl $LIBEXEC/check_snmp_process.pl $LIBEXEC/check_snmp_storage.pl 

is_wizard $WIZARDS/windowssnmp/windowssnmp.inc.php

can_nagios_execute  $LIBEXEC/check_snmp_load.pl $LIBEXEC/check_snmp_win.pl $LIBEXEC/check_snmp_process.pl $LIBEXEC/check_snmp_storage.pl

can_apache_execute  $LIBEXEC/check_snmp_load.pl $LIBEXEC/check_snmp_win.pl $LIBEXEC/check_snmp_process.pl $LIBEXEC/check_snmp_storage.pl

are_these_packages_installed perl net-snmp-perl net-snmp 

templates_exist xiwizard_generic_service xiwizard_generic_host xiwizard_windowssnmp_host xiwizard_windowssnmp_load xiwizard_windowssnmp_service xiwizard_windowssnmp_process xiwizard_windowssnmp_storage  

commands_exist check_xi_service_snmp_win_load  check_xi_service_snmp_win_service check_xi_service_snmp_win_process check_xi_service_snmp_win_storage check_xi_host_ping

print_results
