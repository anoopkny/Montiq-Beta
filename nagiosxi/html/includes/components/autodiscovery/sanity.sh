#!/bin/bash

#autodiscovery component sanity check

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

do_these_files_exist $COMPONENTS/autodiscovery/autodiscovery.inc.php \
	$COMPONENTS/autodiscovery/index.php \
	$COMPONENTS/autodiscovery/install.sh \
	$COMPONENTS/autodiscovery/scripts/autodiscover_new.php \
	$COMPONENTS/autodiscovery/scripts/run_fping \
	$COMPONENTS/autodiscovery/scripts/run_traceroute.php \
	/usr/sbin/fping \
	/bin/traceroute \
	/usr/bin/nmap

is_component $COMPONENTS/autodiscovery/autodiscovery.inc.php

is_protected $COMPONENTS/autodiscovery

can_nagios_execute $COMPONENTS/autodiscovery/scripts/run_fping \
	$COMPONENTS/autodiscovery/scripts/run_traceroute.php \
        $COMPONENTS/autodiscovery/scripts/autodiscover_new.php \
	/usr/sbin/fping \
	/bin/traceroute \
	/usr/bin/nmap

can_apache_execute $COMPONENTS/autodiscovery/scripts/run_fping \
	$COMPONENTS/autodiscovery/scripts/run_traceroute.php \
	$COMPONENTS/autodiscovery/scripts/autodiscover_new.php \
	/usr/sbin/fping \
	/bin/traceroute \
	/usr/bin/nmap

are_these_packages_installed fping traceroute nmap 

print_results
