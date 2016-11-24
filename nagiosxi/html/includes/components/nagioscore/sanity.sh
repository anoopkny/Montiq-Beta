#!/bin/bash
 
#nagioscore component sanity check

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

do_these_files_exist $COMPONENTS/nagioscore/nagioscore.inc.php \
	$COMPONENTS/nagioscore/coreuiproxy.inc.php

is_component $COMPONENTS/nagioscore/nagioscore.inc.php

print_results
