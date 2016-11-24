#!/bin/bash
 
#bbmap sanity check

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

do_these_files_exist $COMPONENTS/bbmap/bbmap.inc.php \
	$COMPONENTS/bbmap/bbmap.css \
	$COMPONENTS/bbmap/bbmap.js \
	$COMPONENTS/bbmap/dashlet.inc.php \
	$COMPONENTS/bbmap/index.php \
	$COMPONENTS/bbmap/images/critical.png \
	$COMPONENTS/bbmap/images/down.png \
	$COMPONENTS/bbmap/images/handled.png \
	$COMPONENTS/bbmap/images/ok.png \
	$COMPONENTS/bbmap/images/pending.png \
	$COMPONENTS/bbmap/images/preview.png \
	$COMPONENTS/bbmap/images/unknown.png \
	$COMPONENTS/bbmap/images/unreachable.png \
	$COMPONENTS/bbmap/images/up.png \
	$COMPONENTS/bbmap/images/warning.png 

is_component $COMPONENTS/bbmap/bbmap.inc.php

print_results
