#!/bin/bash

#bpi configwizard sanity check

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

templates_exist xiwizard_bpi_service 
do_these_files_exist $LIBEXEC/check_bpi.php 
is_wizard $WIZARDS/bpiwizard/bpiwizard.inc.php 

print_results
