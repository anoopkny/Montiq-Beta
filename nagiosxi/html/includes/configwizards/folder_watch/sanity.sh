#!/bin/bash

#folder_watch configwizard sanity check

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

do_these_files_exist $WIZARDS/ftpserver/folder_watch.inc.php $LIBEXEC/folder_watch.pl

is_wizard $WIZARDS/folder_watch/folder_watch.inc.php

can_nagios_execute $LIBEXEC/folder_watch.pl
 
can_apache_execute $LIBEXEC/folder_watch.pl

commands_exist check_file_service check_file_size_age

print_results