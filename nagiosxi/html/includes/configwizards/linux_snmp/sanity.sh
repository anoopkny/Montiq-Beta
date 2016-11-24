#!/bin/bash

#linux_snmp configwizard sanity check

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

do_these_files_exist $WIZARDS/linux_snmp/linux_snmp.inc.php $LIBEXEC/check_snmp_load_wizard.pl $LIBEXEC/check_snmp_process_wizard.pl $LIBEXEC/check_snmp_storage_wizard.pl 

is_wizard $WIZARDS/linux_snmp/linux_snmp.inc.php

can_nagios_execute $LIBEXEC/check_snmp_load_wizard.pl $LIBEXEC/check_snmp_process_wizard.pl $LIBEXEC/check_snmp_storage_wizard.pl 

can_apache_execute $LIBEXEC/check_snmp_load_wizard.pl $LIBEXEC/check_snmp_process_wizard.pl $LIBEXEC/check_snmp_storage_wizard.pl 

are_these_packages_installed net-snmp net-snmp-utils perl net-snmp-perl 

templates_exist xiwizard_generic_host xiwizard_generic_service xiwizard_linuxsnmp_host xiwizard_linuxsnmp_load xiwizard_linuxsnmp_process xiwizard_linuxsnmp_storage

commands_exist check_xi_service_snmp_linux_load check_xi_service_snmp_linux_process check_xi_service_snmp_linux_storage check_xi_host_ping 

print_results
