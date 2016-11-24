#!/bin/bash

# $Id$#

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg

# Fix permissions on config files
echo "RESETTING PERMS"

/bin/chown $nagiosuser.$nagiosgroup /usr/local/nagiosxi/scripts/nagiosql*
/bin/chmod 775 /usr/local/nagiosxi/scripts/nagiosql*
/bin/chown -R $apacheuser:$nagiosgroup /usr/local/nagios/etc/
/bin/chmod -R ug+rw /usr/local/nagios/etc/
/bin/chmod -R 775 /usr/local/nagios/share/perfdata/

/bin/chown -R $nagiosuser.$nagiosgroup /usr/local/nagios/share/perfdata 
/bin/chmod 775 /usr/local/nagios/libexec

/bin/chown $nagiosuser:$nagiosgroup /usr/local/nagiosxi/nom/checkpoints/nagiosxi

if [ -f /usr/local/nagiosxi/var/corelog.newobjects ]; then
    /bin/chown $nagiosuser.$nagiosgroup /usr/local/nagiosxi/var/corelog.newobjects
fi

# Make sure ccm config file is writeable by apache
if [ -f /usr/local/nagiosxi/etc/components/ccm_config.inc.php ]; then
    /bin/chown $apacheuser.$nagiosgroup /usr/local/nagiosxi/etc/components/ccm_config.inc.php
fi
