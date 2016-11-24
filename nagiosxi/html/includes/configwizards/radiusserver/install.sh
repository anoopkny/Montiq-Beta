#!/bin/sh
if [ "`uname -m`" == x86_64 ]; then
BASEDIR=$(dirname $(readlink -f $0)) 
	cp -r $BASEDIR/check_radius_adv_64 /usr/local/nagios/libexec/check_radius_adv
fi
exit 0