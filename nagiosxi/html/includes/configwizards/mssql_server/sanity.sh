#!/bin/bash

function zipit() {
    echo /tmp/mssql-*.tmp
}

if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

can_nagios_execute  $LIBEXEC/check_mssql_server.py

are_these_packages_installed freetds freetds-devel python pymssql

are_these_python_modules_installed pymssql time sys optparse

templates_exist xiwizard_mssqlserver_host xiwizard_mssqlserver_service

commands_exist check_xi_mssql_server

print_results
