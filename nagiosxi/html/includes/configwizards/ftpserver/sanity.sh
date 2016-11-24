#!/bin/bash

#ftpserver configwizard sanity check

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

do_these_files_exist $WIZARDS/ftpserver/ftpserver.inc.php $LIBEXEC/check_ftp_fully $LIBEXEC/check_ftp

is_wizard $WIZARDS/ftpserver/ftpserver.inc.php

can_nagios_execute $LIBEXEC/check_ftp_fully $LIBEXEC/check_ftp
 
can_apache_execute $LIBEXEC/check_ftp_fully $LIBEXEC/check_ftp

commands_exist check_xi_service_ftp check_ftp_fully
templates_exist xiwizard_ftpserver_server_service xiwizard_ftpserver_transfer_service xiwizard_ftpserver_host

print_results

