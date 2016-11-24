#!/bin/bash

#webtransactions config wizard  sanity check

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

do_these_files_exist $WIZARDS/webtransaction/webtransaction.inc.php

is_wizard $WIZARDS/webtransaction/webtransaction.inc.php

templates_exist xiwizard_webtransaction_host xiwizard_webtransaction_webinject_service

commands_exist check_xi_service_webinject

does_user_own nagios $WIZARDS/webtransaction/webtransaction.inc.php

does_group_groupown nagios $WIZARDS/webtransaction/webtransaction.inc.php

does_user_own root $LIBEXEC/check_webinject.sh

does_group_groupown root $LIBEXEC/check_webinject.sh

print_results
