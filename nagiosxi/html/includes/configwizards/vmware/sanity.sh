#!/bin/bash

#vmware config wizard  sanity check

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

do_these_files_exist $WIZARDS/vmware/vmware.inc.php

is_wizard $WIZARDS/vmware/vmware.inc.php

templates_exist xiwizard_generic_host xiwizard_generic_service

commands_exist check_esx3_host

does_error_exist_in_logs "ESX3 UNKNOWN - Missing perl module" 50 /var/log/httpd/error_log

are_these_packages_installed perl-Nagios-Plugin libuuid* perl-XML-LibXML

does_user_own nagios $WIZARDS/vmware/config.xml

does_user_own apache /usr/local/nagiosxi/etc/components/vmware/*.txt

does_user_own root $LIBEXEC/check_esx*

does_group_groupown nagios $WIZARDS/vmware/config.xml /usr/local/nagiosxi/etc/components/vmware/*.txt

does_group_groupwn root $LIBEXEC/check_esx*

do_these_files_exist $LIBEXEC/check_esx3* $LIBEXEC/check_wmi_plus.pl $LIBEXEC/check_wmi_plus.ini /usr/local/nagiosxi/etc/components/vmware/*.txt

print_results
