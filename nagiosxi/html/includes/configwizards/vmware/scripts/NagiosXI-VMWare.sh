#!/bin/bash
# Prereq for VMWare.
#
# Usage:
#   cd /tmp
#   wget -r http://assets.nagios.com/downloads/nagiosxi/scripts/NagiosXI-VMWare.sh
#   ./NagiosXI-VMWare.sh
#
# Copyright (c) 2010 Nagios Enterprises, LLC.  All rights reserved.
#
# $Id: NagiosXI-SNMPTrap.sh 175 2010-04-20 22:06:20Z mmestnik $

export MEID='$Id: NagiosXI-SNMPTrap.sh 175 2010-04-20 22:06:20Z mmestnik $'

# Check whether we have sufficient privileges
if [ $(( $(id -u) )) -ne 0 ]; then
	echo "This script needs to be run as root/superuser."
	echo "$MEID"
	exit 1
fi

# Install prerequisite packages
rpm -Uvh http://download.fedora.redhat.com/pub/epel/5/i386/epel-release-5-3.noarch.rpm
yum -yq check-update > /dev/null
yum -yq install rpmdevtools

# Install prerequisite Perl modules
function yumperlget () {
	echo "Some packages will fail to install, this is normal."
	yum -yq provides perl"($1)" |
		awk '/^Repo/ { print pkg; next; };
				/^[^ ].* : / { pkg = $1; next; };' | {
		# Here we write our guidelines for pkg selection.
			read verlist
			while read i
				do for topver in $verlist; do rpmdev-vercmp "$topver" "$i"
					ret=$(( $? ))
					[ $(( $ret )) -eq 11 -o $(( $ret )) -eq 0 ] && {
						verlist="$(sed "s/$topver/$i $topver/" \
								<<< "$verlist")"
						break
					}
					[ $(( $ret )) -eq 1 ] && {
						# Bail.
						echo "Failed to sort a version: $i"
						verlist="$verlist $i"
						break
					}
					done
				grep -qF "$i" <<< "$verlist" || verlist="$verlist $i"
			done
			for ech in $verlist; do yum -qy install "$ect"; done
		}
}

yumperlget "Class::Accessor"
yumperlget "Class::Accessor::Fast"
yumperlget "Config::Tiny"
yumperlget "Math::Calc::Units"
yumperlget "Params::Validate"

rpm -Uvh http://assets.nagios.com/downloads/nagiosxi/packages/perl-Nagios-Plugin-0.33-2.noarch.rpm

echo "Script may have completed successfully ignore the next message and
continue with the VMWare Instalation."
echo "================================="
echo "VMWare Installation NOT Complete!"
echo "================================="

exit 0
