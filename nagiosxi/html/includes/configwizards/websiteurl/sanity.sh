#!/bin/bash

#websiteurl config wizard  sanity check

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

do_these_files_exist $WIZARDS/websiteurl/websiteurl.inc.php $LIBEXEC/check_http

is_wizard $WIZARDS/websiteurl/websiteurl.inc.php

templates_exist xiwizard_website_host xiwizard_website_http_service xiwizard_website_ping_service xiwizard_website_dnsip_service xiwizard_website_dns_service xiwizard_website_http_cert_service xiwizard_website_http_content_service

commands_exist check_xi_service_http

does_user_own nagios $WIZARDS/websiteurl/config.xml 

does_user_own root $LIBEXEC/check_http

does_group_groupown nagios $WIZARDS/websiteurl/config.xml 

does_group_groupwn root $LIBEXEC/check_http

print_results
