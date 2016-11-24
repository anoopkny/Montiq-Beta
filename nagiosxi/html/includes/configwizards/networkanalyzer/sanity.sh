#!/bin/bash

# NNA configwizard sanity check

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

do_these_files_exist $WIZARDS/networkanalyzer/networkanalyzer.inc.php $LIBEXEC/check_nna.py $LIBEXEC/check_icmp

is_wizard $WIZARDS/networkanalyzer/networkanalyzer.inc.php

can_nagios_execute $LIBEXEC/check_nna.py $LIBEXEC/check_icmp

can_apache_execute $LIBEXEC/check_nna.py $LIBEXEC/check_icmp 

are_these_packages_installed python

is_the_sticky_bit_set $LIBEXEC/check_icmp

templates_exist xiwizard_nna_service xiwizard_nna_host xiwizard_generic_service xiwizard_generic_host

commands_exist check_xi_nna check_xi_host_ping

print_results