#!/bin/bash

#nagiostats configwizard sanity check

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

do_these_files_exist $WIZARDS/nagiostats/nagiostats.inc.php $LIBEXEC/check_nagios_performance.php

is_wizard $WIZARDS/nagiostats/nagiostats.inc.php

can_nagios_execute  $LIBEXEC/check_nagios_performance.php

can_apache_execute  $LIBEXEC/check_nagios_performance.php

are_these_packages_installed php   

templates_exist xiwizard_nagiostats_service xiwizard_generic_service

commands_exist check_nagiosxi_performance

print_results
