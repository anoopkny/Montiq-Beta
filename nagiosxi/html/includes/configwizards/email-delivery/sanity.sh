#!/bin/bash

#email delivery configwizard sanity check

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

is_wizard $WIZARDS/email-delivery/email-delivery.inc.php

do_these_files_exist $LIBEXEC/check_email_delivery $LIBEXEC/check_email_delivery_epn  $LIBEXEC/check_imap_receive  $LIBEXEC/check_imap_receive_epn  $LIBEXEC/check_smtp_send  $LIBEXEC/check_smtp_send_epn

can_nagios_execute $LIBEXEC/check_email_delivery $LIBEXEC/check_email_delivery_epn  $LIBEXEC/check_imap_receive  $LIBEXEC/check_imap_receive_epn  $LIBEXEC/check_smtp_send  $LIBEXEC/check_smtp_send_epn
 
can_apache_execute $LIBEXEC/check_email_delivery $LIBEXEC/check_email_delivery_epn  $LIBEXEC/check_imap_receive  $LIBEXEC/check_imap_receive_epn  $LIBEXEC/check_smtp_send  $LIBEXEC/check_smtp_send_epn

commands_exist check_smtp_delivery
templates_exist xiwizard_generic_service

print_results
