#!/bin/sh
# Script to automatically install and configure NagVis
#
# Copyright (c) 2010-2015 Nagios Enterprises, LLC Nagios Enterprises, LLC.  All rights reserved.
#
# $Id: NagiosXI-Nagvis.sh 607 2011-06-29 15:39:49Z agriffin $

if [ -f /usr/local/nagvis/share/index.php ]; then
    exit 0
fi

set -e

NAGVIS_VER="1.5.9"

# Print usage information
usage() {
	echo "Script to automatically install and configure NagVis"
	echo "Copyright:  2011, Nagios Enterprises LLC."
	echo "Author:     Tony Yarusso <tyarusso@nagios.com>"
	echo "            Alex Griffin <agriffin@nagios.com>"
	echo "License:    All rights reserved"
	echo ""
	echo "Options:"
	echo "    -h | --help"
	echo "           Display this help text"
	echo "    -v | --version"
	echo "           Display the version of NagVis this script will install"
	echo "    -m | --mode [core,xi]"
	echo "           Set whether to run in Nagios Core or Nagios XI mode (defaults to xi)"
	echo ""
}

# Parse parameters
MODE="xi"
while [ $# -gt 0 ]; do
	case "$1" in
		-h | --help)
			usage
			exit 0
			;;
		-v | --version)
			echo "Will install NagVis version $NAGVIS_VER"
			echo ""
			exit 0
			;;
		-s | --short-version)
			echo "$NAGVIS_VER"
			exit 0
			;;
		-m | --mode)
			shift
			case "$1" in
				core | Core | CORE)
					MODE="core"
					;;
				xi | XI | ix | IX)
					MODE="xi"
					;;
				*)
					echo "Unknown mode.  Use either core or xi." >&2
					exit 0
					;;
			esac
			;;
		*)
			echo "Unknown argument: $1" >&2
			usage >&2
			exit 0
			;;
	esac
shift
done

# Check whether we have sufficient privileges
if [ $(whoami) != "root" ]; then
	echo "This script needs to be run as root/superuser." >&2
	exit 0 # We're going to exit cleanly either way 
fi

# Removed this for aoto install -SW
#
# We need to check whether Nagios and NDO are installed and running
#if ! ( service nagios status | grep -c "running" > /dev/null && service ndo2db status | grep -c "running" > /dev/null ); then
#	echo "Both nagios and ndo2db need to be installed and running." >&2
#	exit 1
#fi

# The minimum version requirement of PHP is 5.0.0, with web server support.
if [ $(php -v | head -n1 | cut -d\  -f2 | cut -d\. -f1) -lt 5 ]; then
	echo "NagVis requires PHP version 5.0.0 or greater, with web server support." >&2
	exit 0
fi

dldir=$(pwd)

# Install prerequisite packages
if rpm -q php || rpm -q yum-plugin-replace; then
	PHP_VER=""
elif rpm -q php52; then
	PHP_VER=52
elif rpm -q php53; then
	PHP_VER=53
elif rpm -q php53u || yum info php53u; then
	PHP_VER=53u
elif yum info php53; then
	PHP_VER=53
elif yum info php52; then
	PHP_VER=52
else
	PHP_VER=""
fi >/dev/null

GV_VER=$(yum info graphviz-gd | sed -n '/^Version *: / s///p')
rpm -e --nodeps graphviz 2>/dev/null

#already in place if offline install
if [ ! -f $INSTALL_PATH/offline ]; then
	yum -qy install \
		graphviz-$GV_VER \
		graphviz-gd \
		php$PHP_VER-gd \
		php$PHP_VER-mbstring \
		php$PHP_VER-mysql \
		php$PHP_VER-pdo \
		php$PHP_VER-xml \
		php$PHP_VER-common \
		php-pecl-json
fi
# graphviz is only needed for the automap feature; can be optional

# Download and install NagVis from upstream source
cd /tmp

if [ ! -f $INSTALL_PATH/offline ]; then
	wget -qc "http://assets.nagios.com/downloads/nagiosxi/packages/nagvis-1.5.9.tar.gz";
else
	cp $INSTALL_PATH/packages/offlineinstall/nagvis-$NAGVIS_VER.tar.gz ./nagvis-$NAGVIS_VER.tar.gz
fi

tar zxf nagvis-$NAGVIS_VER.tar.gz
cd nagvis-$NAGVIS_VER
chmod +x install.sh
./install.sh -b /usr/bin -p /usr/local/nagvis -W /nagvis -u apache -g apache -w /etc/httpd/conf.d -i ndo2db -a y -q
cd ..
rm -rf /tmp/nagvis-$NAGVIS_VER*

# Make necessary changes to nagvis configuration file
cd "$dldir"

if [ ! -f $INSTALL_PATH/offline ]; then
	wget -qc "http://assets.nagios.com/downloads/nagiosxi/scripts/NagiosXI-Nagvis-adjust_config.py"
else
	cp $INSTALL_PATH/packages/offlineinstall/NagiosXI-Nagvis-adjust_config.py ./NagiosXI-Nagvis-adjust_config.py
fi	

chmod +x NagiosXI-Nagvis-adjust_config.py
./NagiosXI-Nagvis-adjust_config.py --mode $MODE
rm NagiosXI-Nagvis-adjust_config.py

# Make sure timezone is set in PHP configuration
if [ -f /etc/timezone ]; then
	TIMEZONE=$(cat /etc/timezone)
elif [ $(grep ZONE /etc/sysconfig/clock | sed 's/.*"\([^"].*\)".*/\1/') != "" ]; then
	TIMEZONE=$(grep ZONE /etc/sysconfig/clock | sed 's/.*"\([^"].*\)".*/\1/')
else
	TIMEZONE="UTC"
fi
if [ -f /etc/php5/apache2/php.ini ]; then
	PHPINI="/etc/php5/apache2/php.ini"
elif [ -f /etc/php5/php.ini ]; then
	PHPINI="/etc/php5/php.ini"
elif [ -f /etc/php.ini ]; then
	PHPINI="/etc/php.ini"
elif [ -d /etc/php5/conf.d ]; then
	PHPINI="/etc/php5/conf.d/nagvis.ini"
elif [ -d /etc/php.d ]; then
	PHPINI="/etc/php.d/nagvis.ini"
fi
if [ $(grep -c "date.timezone" $PHPINI) -ge 1 ] && [ $(grep "date.timezone" $PHPINI | grep -c ^\;) -ge 1 ]; then
	sed -i "s%\;date\.timezone.*%date\.timezone\ =\ $TIMEZONE%" $PHPINI
elif [ $(grep -c "date.timezone" $PHPINI) -lt 1 ]; then
	echo "date.timezone = $TIMEZONE" >> $PHPINI
fi

# Adjust link paths from automap
if [ $MODE = "xi" ]; then
	sed -i 's/status.cgi/status.php/' /usr/local/nagvis/etc/automaps/__automap.cfg
fi

# Fix for TPS#8313, Errors in Cent7 in General Configuration -BH
sed -i 's/.*\$arr\[\$propname\]\['\''default'\''\].*/if (\!is_array(\$prop\['\''default'\''\])) { & } /' /usr/local/nagvis/share/server/core/classes/WuiViewEditMainCfg.php

# Set up user for authenticating
sed -i 's/#AuthName/AuthName/' /etc/httpd/conf.d/nagvis.conf
sed -i 's/#AuthType/AuthType/' /etc/httpd/conf.d/nagvis.conf
sed -i 's/#AuthUserFile/AuthUserFile/' /etc/httpd/conf.d/nagvis.conf
if [ $MODE = "xi" ]; then
	sed -i 's%AuthUserFile.*%AuthUserFile\ /usr/local/nagiosxi/etc/htpasswd.users%' /etc/httpd/conf.d/nagvis.conf
else
	sed -i 's%AuthUserFile.*%AuthUserFile\ /usr/local/nagios/etc/htpasswd.users%' /etc/httpd/conf.d/nagvis.conf
	sed -i 's%AuthName.*%AuthName\ "Nagios Access"%' /etc/httpd/conf.d/nagvis.conf
fi
sed -i 's/#Require/Require/' /etc/httpd/conf.d/nagvis.conf

# Reload Apache configuration
service httpd reload

echo ""
echo "============================="
echo "NagVis Installation Complete!"
echo "============================="
