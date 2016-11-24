#!/bin/bash

. /usr/local/nagiosxi/var/xi-sys.cfg

componentdir=/usr/local/nagiosxi/html/includes/components/snmptrapsender

# Install required rpms
echo "Installing required components..."

if [ "$distro" != "Ubuntu" ] && [ "$distro" != "Debian" ]; then
	yum install net-snmp net-snmp-utils net-snmp-devel -y
fi

pushd $componentdir

# Install MIBS
echo "Installing MIBs..."
cp mibs/*.txt /usr/share/snmp/mibs/

# Write installed file/flat
touch installed
chown nagios:nagios installed

# Set permissions on net-snmp
if [ -d "/var/lib/net-snmp" ]; then
	chown nagios:nagios /var/lib/net-snmp
fi

popd

echo "==============="
echo "SETUP COMPLETED"
echo "==============="
