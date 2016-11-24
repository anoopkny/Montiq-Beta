#!/bin/bash

#tcpudpport configwizard sanity check

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

do_these_files_exist $WIZARDS/tcpudpport/tcpudpport.inc.php $LIBEXEC/check_smtp $LIBEXEC/check_pop $LIBEXEC/check_tcp $LIBEXEC/check_udp $LIBEXEC/check_http $LIBEXEC/check_imap $LIBEXEC/check_ssh $LIBEXEC/check_ftp

is_wizard $WIZARDS/tcpudpport/tcpudpport.inc.php

can_nagios_execute $LIBEXEC/check_smtp $LIBEXEC/check_pop $LIBEXEC/check_tcp $LIBEXEC/check_udp $LIBEXEC/check_http $LIBEXEC/check_imap $LIBEXEC/check_ssh $LIBEXEC/check_ftp

can_apache_execute $LIBEXEC/check_smtp $LIBEXEC/check_pop $LIBEXEC/check_tcp $LIBEXEC/check_udp $LIBEXEC/check_http $LIBEXEC/check_imap $LIBEXEC/check_ssh $LIBEXEC/check_ftp

templates_exist xiwizard_ftp_service xiwizard_website_http_service xiwizard_imap_service xiwizard_pop_service xiwizard_smtp_service xiwizard_ssh_service xiwizard_tcp_service xiwizard_udp_service xiwizard_generic_service

commands_exist check_xi_service_http check_xi_service_imap check_xi_service_pop check_xi_service_tcp check_xi_service_udp check_xi_service_ssh check_xi_service_ftp

print_results
