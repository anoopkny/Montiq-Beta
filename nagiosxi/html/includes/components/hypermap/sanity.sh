#!/bin/bash
 
# Hypermap sanity check

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

do_these_files_exist $COMPONENTS/hypermap/hypermap.inc.php \
	$COMPONENTS/hypermap/ajax.inc.php \
	$COMPONENTS/hypermap/dashlet.inc.php \
	$COMPONENTS/hypermap/hypermap_preview.png \
	$COMPONENTS/hypermap/index.php \
	$COMPONENTS/hypermap/map.php \
	$COMPONENTS/hypermap/css/base.css \
	$COMPONENTS/hypermap/css/hypermap.css \
	$COMPONENTS/hypermap/js/hypermap.js \
	$COMPONENTS/hypermap/js/jit.js \
	$COMPONENTS/hypermap/js/jit-yc.js \
	$COMPONENTS/hypermap/js/Extras/excanvas.js

is_component $COMPONENTS/hypermap/hypermap.inc.php 

print_results
