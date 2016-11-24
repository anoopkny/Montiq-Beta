#!/bin/bash
 
#hypermap_replay sanity check

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

do_these_files_exist $COMPONENTS/hypermap_replay/hypermap_replay.inc.php \
	$COMPONENTS/hypermap_replay/ajax.inc.php \
	$COMPONENTS/hypermap_replay/dashlet.inc.php \
	$COMPONENTS/hypermap_replay/hypermap_preview.png \
	$COMPONENTS/hypermap_replay/index.php \
	$COMPONENTS/hypermap_replay/map.php \
	$COMPONENTS/hypermap_replay/css/base.css \
	$COMPONENTS/hypermap_replay/css/hypermap.css \
	$COMPONENTS/hypermap_replay/js/example1.js \
	$COMPONENTS/hypermap_replay/js/hypermap.js \
	$COMPONENTS/hypermap_replay/js/jit.js \
	$COMPONENTS/hypermap_replay/js/jit-yc.js \
	$COMPONENTS/hypermap_replay/js/Extras/excanvas.js

is_component $COMPONENTS/hypermap_replay/hypermap_replay.inc.php 

print_results
