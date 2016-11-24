#!/bin/bash
 
#minemap component sanity check

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

do_these_files_exist $COMPONENTS/minemap/minemap.inc.php \
	$COMPONENTS/minemap/dashlet.inc.php \
	$COMPONENTS/minemap/index.php \
	$COMPONENTS/minemap/minemap.css \
	$COMPONENTS/minemap/minemap.inc.php \
	$COMPONENTS/minemap/minemap.js \
	$COMPONENTS/minemap/images/critical.png \
	$COMPONENTS/minemap/images/down.png \
	$COMPONENTS/minemap/images/handled.png \
	$COMPONENTS/minemap/images/ok.png \
	$COMPONENTS/minemap/images/pending.png \
	$COMPONENTS/minemap/images/preview.png \
	$COMPONENTS/minemap/images/unknown.png \
	$COMPONENTS/minemap/images/unreachable.png \
	$COMPONENTS/minemap/images/up.png \
	$COMPONENTS/minemap/images/warning.png

is_component $COMPONENTS/minemap/minemap.inc.php 

print_results
