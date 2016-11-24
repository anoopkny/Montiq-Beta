#!/bin/bash
 
#tracerouteaction component sanity check

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

do_these_files_exist $COMPONENTS/tracerouteaction/tracerouteaction.inc.php \
	$COMPONENTS/tracerouteaction/traceroute.php \
	$COMPONENTS/tracerouteaction/images/traceroute.png \
	$(which traceroute)

are_these_packages_installed traceroute

can_nagios_execute $(which traceroute)

can_apache_execute $(which traceroute)

is_component $COMPONENTS/tracerouteaction/tracerouteaction.inc.php

print_results
