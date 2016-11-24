#!/bin/bash
 
#escalationwizard component sanity check

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

do_these_files_exist $COMPONENTS/escalationwizard/escalationwizard.inc.php \
	$COMPONENTS/escalationwizard/escalationwizard.php

is_component $COMPONENTS/escalationwizard/escalationwizard.inc.php

print_results
