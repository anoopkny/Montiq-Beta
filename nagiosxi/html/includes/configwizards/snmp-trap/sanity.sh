#!/bin/bash

#snmp-trap configwizard sanity check

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

do_these_files_exist $WIZARDS/snmp-trap/snmp-trap.inc.php $LIBEXEC/check_dummy

is_wizard $WIZARDS/snmp-trap/snmp-trap.inc.php

can_nagios_execute  $LIBEXEC/check_dummy

can_apache_execute  $LIBEXEC/check_dummy

templates_exist xiwizard_generic_host xiwizard_generic_service xiwizard_snmptrap_host xiwizard_snmptrap_service

commands_exist check_dummy

print_results
