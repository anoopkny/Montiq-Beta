#!/bin/bash

#actions components sanity check

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

do_these_files_exist $COMPONENTS/actions/actions.inc.php $COMPONENTS/actions/runcmd.php

is_component $COMPONENTS/actions/actions.inc.php

can_nagios_execute $COMPONENTS/actions/runcmd.php

can_apache_execute $COMPONENTS/actions/runcmd.php

print_results
