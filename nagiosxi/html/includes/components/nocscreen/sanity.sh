#!/bin/bash
 
#nocscreen component sanity check

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

do_these_files_exist $COMPONENTS/nocscreen/nocscreen.inc.php \
	$COMPONENTS/nocscreen/nocscreenapi.php \
	$COMPONENTS/nocscreen/noc.php

is_component $COMPONENTS/nocscreen/nocscreen.inc.php

print_results
