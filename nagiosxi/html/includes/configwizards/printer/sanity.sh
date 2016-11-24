#!/bin/bash

#printer configwizard sanity check

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

do_these_files_exist $WIZARDS/printer/printer.inc.php $LIBEXEC/check_hpjd $LIBEXEC/check_icmp

is_wizard $WIZARDS/printer/printer.inc.php

can_nagios_execute $LIBEXEC/check_hpjd $LIBEXEC/check_icmp

can_apache_execute $LIBEXEC/check_hpjd $LIBEXEC/check_icmp 

is_the_sticky_bit_set $LIBEXEC/check_icmp

templates_exist xiwizard_printer_ping_service xiwizard_printer_hpjd_service xiwizard_generic_service xiwizard_printer_host xiwizard_generic_host

commands_exist check_xi_service_hpjd check_xi_host_ping

print_results
