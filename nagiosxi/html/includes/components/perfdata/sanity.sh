#!/bin/bash
 
#perfdata component sanity check

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

do_these_files_exist $COMPONENTS/perfdata/perfdata.inc.php \
	$COMPONENTS/perfdata/perfdata.php \
	$COMPONENTS/perfdata/graphApi.php \
	$COMPONENTS/perfdata/noperfdata.png

is_component $COMPONENTS/perfdata/perfdata.inc.php

print_results
