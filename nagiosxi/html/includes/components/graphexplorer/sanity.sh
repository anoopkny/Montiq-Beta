#!/bin/bash
 
#graphexplorer component sanity check

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

do_these_files_exist $COMPONENTS/graphexplorer/graphexplorer.inc.php \
	$COMPONENTS/graphexplorer/dashifygraph.php \
	$COMPONENTS/graphexplorer/dashlet.inc.php \
	$COMPONENTS/graphexplorer/fetch_rrd.php \
	$COMPONENTS/graphexplorer/graphexplorerinclude.js \
	$COMPONENTS/graphexplorer/graphexplorer.inc.php \
	$COMPONENTS/graphexplorer/graphexplorer.js \
	$COMPONENTS/graphexplorer/index.php \
	$COMPONENTS/graphexplorer/lists.php \
	$COMPONENTS/graphexplorer/sanity.sh \
	$COMPONENTS/graphexplorer/visApi.php \
	$COMPONENTS/graphexplorer/visFunctions.inc.php \
	$COMPONENTS/graphexplorer/ajax/datatypes.php \
	$COMPONENTS/graphexplorer/ajax/hosts.php \
	$COMPONENTS/graphexplorer/ajax/lists.php \
	$COMPONENTS/graphexplorer/ajax/services.php \
	$COMPONENTS/graphexplorer/images/collapse.png \
	$COMPONENTS/graphexplorer/images/cross.png \
	$COMPONENTS/graphexplorer/images/expand1.png \
	$COMPONENTS/graphexplorer/images/expand.png \
	$COMPONENTS/graphexplorer/images/timeline_icon.png \
	$COMPONENTS/graphexplorer/images/timeline.png \
	$COMPONENTS/graphexplorer/includes/graphexplorerinclude.js \
	$COMPONENTS/graphexplorer/templates/bar.inc.php \
	$COMPONENTS/graphexplorer/templates/column.inc.php \
	$COMPONENTS/graphexplorer/templates/default.inc.php \
	$COMPONENTS/graphexplorer/templates/multistack.inc.php \
	$COMPONENTS/graphexplorer/templates/pie.inc.php \
	$COMPONENTS/graphexplorer/templates/timeline.inc.php

is_component $COMPONENTS/graphexplorer/graphexplorer.inc.php

print_results
