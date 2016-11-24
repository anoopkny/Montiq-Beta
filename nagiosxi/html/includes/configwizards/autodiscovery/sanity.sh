#!/bin/bash

#autodiscovery configwizard sanity check

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

does_string_exist_in_files "NAGIOSXI ALL = NOPASSWD:/usr/bin/nmap *" /etc/sudoers

can_nagios_execute /usr/bin/nmap
can_apache_execute /usr/bin/nmap

are_these_packages_installed nmap
is_wizard $WIZARDS/autodiscovery/autodiscovery.inc.php


print_results
