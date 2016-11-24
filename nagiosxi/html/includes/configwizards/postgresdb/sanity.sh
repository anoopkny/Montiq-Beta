#!/bin/bash

#postgresdb configwizard sanity check

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

do_these_files_exist $WIZARDS/postgresdb/postgresdb.inc.php $LIBEXEC/check_postgres.pl

is_wizard $WIZARDS/postgresdb/postgresdb.inc.php

can_nagios_execute  $LIBEXEC/check_postgres.pl

can_apache_execute  $LIBEXEC/check_postgres.pl

are_these_packages_installed perl

templates_exist xiwizard_generic_service xiwizard_postgresdb_host xiwizard_postgresdb_service xiwizard_generic_service

commands_exist check_xi_host_ping check_xi_postgres_db

print_results
