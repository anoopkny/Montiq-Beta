#!/bin/bash
 
#latestalerts component sanity check

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

do_these_files_exist $COMPONENTS/latestalerts/latestalerts.inc.php \
	$COMPONENTS/latestalerts/dashlet.inc.php \
	$COMPONENTS/latestalerts/images \
	$COMPONENTS/latestalerts/index.php \
	$COMPONENTS/latestalerts/latestalerts.css \
	$COMPONENTS/latestalerts/latestalerts.js \
	$COMPONENTS/latestalerts/images/critical_down_small.png \
	$COMPONENTS/latestalerts/images/preview.png \
	$COMPONENTS/latestalerts/images/unknown_down_small.png \
	$COMPONENTS/latestalerts/images/warning_down_small.png

is_component $COMPONENTS/latestalerts/latestalerts.inc.php 

print_results

