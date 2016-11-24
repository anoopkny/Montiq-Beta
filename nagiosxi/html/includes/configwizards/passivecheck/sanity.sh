#!/bin/bash

#passivecheck configwizard sanity check

function zipit() {
    :
}

if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

do_these_files_exist            $LIBEXEC/check_dummy $WIZARDS/passivecheck/passivecheck.inc.php

is_wizard 			$WIZARDS/passivecheck/passivecheck.inc.php

can_apache_execute              $LIBEXEC/check_dummy

can_nagios_execute              $LIBEXEC/check_dummy

templates_exist                 xiwizard_generic_host xiwizard_passive_host xiwizard_passive_service xiwizard_generic_service

commands_exist                  check_dummy
 
print_results
