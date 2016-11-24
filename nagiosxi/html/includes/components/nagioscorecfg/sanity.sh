#!/bin/bash
 
#nagioscorecfg component sanity check

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

do_these_files_exist $COMPONENTS/nagioscorecfg/nagioscorecfg.inc.php \
	$COMPONENTS/nagioscorecfg/applyconfig.php \
	$COMPONENTS/nagioscorecfg/nagioscorecfg.inc.php \
	$COMPONENTS/nagioscorecfg/nagioscorecfg.php

can_apache_execute $COMPONENTS/nagioscorecfg/applyconfig.php

is_component $COMPONENTS/nagioscorecfg/nagioscorecfg.inc.php

print_results
