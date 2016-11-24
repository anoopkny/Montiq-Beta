#!/bin/bash

#mysqlquery configwizard sanity check

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

do_these_files_exist $WIZARDS/mysqlquery/mysqlquery.inc.php $LIBEXEC/check_mysql_health

is_wizard $WIZARDS/mysqlquery/mysqlquery.inc.php

can_nagios_execute  $LIBEXEC/check_mysql_health

can_apache_execute  $LIBEXEC/check_mysql_health

are_these_packages_installed perl perl-DBD-MySQL perl-DBI  

templates_exist xiwizard_mysqlquery_service xiwizard_mysqlquery_host

commands_exist check_xi_mysql_health

print_results
