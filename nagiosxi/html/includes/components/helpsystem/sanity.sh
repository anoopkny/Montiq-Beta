#!/bin/bash
 
#helpsystem sanity check

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

do_these_files_exist $COMPONENTS/helpsystem/helpsystem.inc.php \
	$COMPONENTS/helpsystem/images/help_and_support.png \
	$COMPONENTS/helpsystem/get_help.php \
	$COMPONENTS/helpsystem/helpsystem.css \
	$COMPONENTS/helpsystem/helpsysteminclude.js \
	$COMPONENTS/helpsystem/helpsystem.inc.php \
	$COMPONENTS/helpsystem/help_system.xml \
	$COMPONENTS/helpsystem/sanity.sh \
	$COMPONENTS/helpsystem/useropts.php

is_protected $COMPONENTS/helpsystem/helpsystem.inc.php \
	$COMPONENTS/helpsystem/get_help.php

print_results
