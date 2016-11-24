#!/bin/bash

#dnsquery configwizard sanity check

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

do_these_files_exist $WIZARDS/dnsquery/dnsquery.inc.php
is_wizard $WIZARDS/dnsquery/dnsquery.inc.php

commands_exist check_xi_service_dnsquery check_xi_service_dns
templates_exist xiwizard_dnsquery_host xiwizard_dnsquery_service xiwizard_generic_host

print_results
