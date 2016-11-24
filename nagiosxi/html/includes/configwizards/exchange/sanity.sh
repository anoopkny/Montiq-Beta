#!/bin/bash

#exchange configwizard sanity check

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

do_these_files_exist $WIZARDS/exchange/exchange.inc.php $LIBEXEC/check_bl $LIBEXEC/check_nt $LIBEXEC/check_imap $LIBEXEC/check_http $LIBEXEC/check_pop $LIBEXEC/check_smtp

is_wizard $WIZARDS/exchange/exchange.inc.php

can_nagios_execute $LIBEXEC/check_bl $LIBEXEC/check_nt $LIBEXEC/check_imap $LIBEXEC/check_http $LIBEXEC/check_pop $LIBEXEC/check_smtp
 
can_apache_execute $LIBEXEC/check_bl $LIBEXEC/check_nt $LIBEXEC/check_imap $LIBEXEC/check_http $LIBEXEC/check_pop $LIBEXEC/check_smtp

commands_exist check_exchange_rbl check_xi_service_nsclient check_xi_service_imap check_xi_service_http check_xi_service_pop check_xi_service_smtp
templates_exist xiwizard_exchange_service xiwizard_imap_service xiwizard_windowsserver_nsclient_service xiwizard_pop_service xiwizard_smtp_service 

print_results

