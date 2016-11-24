#!/bin/bash

#oracle_query configwizard sanity check

function zipit() {
    :
}

if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

do_these_files_exist            /usr/lib/oracle $WIZARDS/oracle_query/oracle_query.inc.php $LIBEXEC/check_oracle_health

is_wizard 			$WIZARDS/oracle_query/oracle_query.inc.php

can_nagios_execute              $LIBEXEC/check_oracle_health

can_apache_execute              $LIBEXEC/check_oracle_health

are_these_packages_installed    oracle-instantclient[0-9.]+-devel  \
                                oracle-instantclient[0-9.]+-basic  \
                                oracle-instantclient[0-9.]+-sqlplus

templates_exist                 xiwizard_oraclequery_host xiwizard_oraclequery_service

commands_exist                  check_xi_oraclequery
 
print_results
