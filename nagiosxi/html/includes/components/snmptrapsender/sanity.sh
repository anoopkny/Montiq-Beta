#!/bin/bash
 
#snmptrapsender component sanity check

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

do_these_files_exist $COMPONENTS/snmptrapsender/snmptrapsender.inc.php \
	$COMPONENTS/snmptrapsender/installprereqs.sh \
	$COMPONENTS/snmptrapsender/mibs/NAGIOS-NOTIFY-MIB.txt \
	$COMPONENTS/snmptrapsender/mibs/NAGIOS-ROOT-MIB.txt \
	/usr/share/snmp/mibs/NAGIOS-NOTIFY-MIB.txt \
	/usr/share/snmp/mibs/NAGIOS-ROOT-MIB.txt \
	$COMPONENTS/snmptrapsender/installed.ok
	
are_these_packages_installed net-snmp net-snmp-utils net-snmp-devel

is_component $COMPONENTS/snmptrapsender/snmptrapsender.inc.php

print_results
