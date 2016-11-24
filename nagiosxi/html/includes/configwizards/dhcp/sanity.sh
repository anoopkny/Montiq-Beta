#!/bin/bash

#dhcp configwizard sanity check

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

do_these_files_exist $WIZARDS/dhcp/dhcp.inc.php $LIBEXEC/check_dhcp
is_wizard $WIZARDS/dhcp/dhcp.inc.php
is_the_sticky_bit_set $LIBEXEC/check_dhcp

print_results
