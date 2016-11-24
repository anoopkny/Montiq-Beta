#!/bin/bash
# nagioslogserver configwizard sanity check

function zipit() {
	:
}

# Include general library (should go in all sanity scripts.)
if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
	echo "Sanity Checks Component not installed"
	exit 1
else 
	. /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

do_these_files_exist $LIBEXEC/check_logserver.php $WIZARDS/nagioslogserver/nagioslogserver.inc.php

is_wizard $WIZARDS/nagioslogserver/nagioslogserver.inc.php

can_nagios_execute $LIBEXEC/check_logserver.php

can_apache_execute $LIBEXEC/check_logserver.php

templates_exist \
	xiwizard_generic_host \
	xiwizard_nagioslogserver_host \
	xiwizard_generic_service \
	xiwizard_nagioslogserver_service

commands_exist \
	check_xi_host_ping \
	check_xi_service_nagioslogserver

print_results
