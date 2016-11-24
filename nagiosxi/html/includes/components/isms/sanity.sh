#!/bin/bash
 
#isms component sanity check

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

do_these_files_exist $COMPONENTS/isms/isms.inc.php \
	$COMPONENTS/isms/receiver.php \
	$COMPONENTS/isms/images/multitech.png 

is_component $COMPONENTS/isms/isms.inc.php 

print_results
