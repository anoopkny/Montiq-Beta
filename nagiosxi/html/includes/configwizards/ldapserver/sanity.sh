#!/bin/bash

#ldapserver configwizard sanity check

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

do_these_files_exist $WIZARDS/ldapserver/ldapserver.inc.php $LIBEXEC/check_ldap

is_wizard $WIZARDS/ldapserver/ldapserver.inc.php

can_nagios_execute $LIBEXEC/check_ldap
 
can_apache_execute $LIBEXEC/check_ldap

commands_exist check_xi_service_ldap
templates_exist xiwizard_ldapserver_ldap_service xiwizard_ldapserver_host

print_results

