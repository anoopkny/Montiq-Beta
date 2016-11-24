#!/bin/bash

#oracle_tablespace configwizard sanity check

function zipit() {
    :
}

if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

do_these_files_exist            /usr/lib/oracle $LIBEXEC/check_oracle_health $WIZARDS/oracle_tablespace/oracle_tablespace.inc.php

is_wizard $WIZARDS/oracle_tablespace/oracle_tablespace.inc.php

can_apache_execute              $LIBEXEC/check_oracle_health

can_nagios_execute              $LIBEXEC/check_oracle_health

are_these_packages_installed    oracle-instantclient[0-9.]+-devel  \
                                oracle-instantclient[0-9.]+-basic  \
                                oracle-instantclient[0-9.]+-sqlplus

templates_exist                 xiwizard_oracletablespace_host     \
                                xiwizard_oracletablespace_service

commands_exist                  check_xi_oracletablespace
 
print_results
