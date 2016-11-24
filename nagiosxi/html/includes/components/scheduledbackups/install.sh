#!/bin/sh

BASEDIR=$(dirname $(readlink -f $0))

. $BASEDIR/../../../../var/xi-sys.cfg

if [ -f $INSTALL_PATH/offline ]; then
	echo Nothing to do here, offline install.
else
	yum install php-pecl-ssh2 -y
fi

$BASEDIR/../../../../manage_services.sh restart httpd
chown $nagiosuser.$nagiosgroup /store/backups/nagiosxi
exit 0