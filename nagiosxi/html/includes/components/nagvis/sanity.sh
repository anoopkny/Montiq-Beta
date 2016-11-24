#!/bin/bash
 
#nagvis component sanity check

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

do_these_files_exist $COMPONENTS/nagvis/nagvis.inc.php \
	$COMPONENTS/nagvis/add_map_links.inc.php \
	$COMPONENTS/nagvis/install.sh \
	/etc/httpd/conf.d/nagvis.conf

is_component $COMPONENTS/nagvis/nagvis.inc.php

print_results
