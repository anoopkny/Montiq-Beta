#!/bin/bash
 
#opscreen component sanity check

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

do_these_files_exist $COMPONENTS/opscreen/opscreen.inc.php \
	$COMPONENTS/opscreen/opscreen.php \
	$COMPONENTS/opscreen/merlin.php \
	$COMPONENTS/opscreen/vdc_opscreen.inc.php \
	$COMPONENTS/opscreen/images/nagios.png

is_component $COMPONENTS/opscreen/opscreen.inc.php

print_results
