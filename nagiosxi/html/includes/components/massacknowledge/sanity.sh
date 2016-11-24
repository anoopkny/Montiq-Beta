#!/bin/bash
 
#massacknowledge component sanity check

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

do_these_files_exist $COMPONENTS/massacknowledge/massacknowledge.inc.php \
	$COMPONENTS/massacknowledge/mass_ack.php

is_component $COMPONENTS/massacknowledge/massacknowledge.inc.php 

print_results
