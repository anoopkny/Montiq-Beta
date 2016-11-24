#!/bin/bash
 
#profile component sanity check

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

do_these_files_exist $COMPONENTS/profile/profile.inc.php \
	$COMPONENTS/profile/profile.php \
	$COMPONENTS/profile/getprofile.sh

does_string_exist_in_files "NAGIOSXIWEB ALL = NOPASSWD:/usr/bin/tail -100 /var/log/messages" /etc/sudoers
does_string_exist_in_files "NAGIOSXIWEB ALL = NOPASSWD:/usr/bin/tail -100 /var/log/httpd/error_log" /etc/sudoers
does_string_exist_in_files "NAGIOSXIWEB ALL = NOPASSWD:/usr/bin/tail -100 /var/log/mysqld.log" /etc/sudoers

is_component $COMPONENTS/profile/profile.inc.php

print_results
