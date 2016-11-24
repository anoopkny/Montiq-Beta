#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

SLEEPTIME=0
BASEDIR=$(dirname $(readlink -f $0))
LOCKFILE="$BASEDIR/reconfigure_nagios.lock"

# Sleep for up to 5 minutes if another Apply Configuration is running before continuting
while [ "$SLEEPTIME" -lt 300 ]
do
    if [ -f "$LOCKFILE" ]; then
        SLEEPTIME=$((SLEEPTIME+1))
        echo "Another reconfigure process is still running, sleeping...";
        sleep 1
        continue
    fi
    break
done

# Create LOCKFILE
touch "$LOCKFILE"

# Export configuration from NagiosQL
./export_nagiosql.sh
ret=$?
if [ $ret -gt 0 ]; then
    exit $ret
fi

# Verify Nagios configuration
output=`/usr/local/nagios/bin/nagios -v /usr/local/nagios/etc/nagios.cfg`

ret=$?
echo "OUTPUT: $output"
echo "RET: $ret"

#exit 1

# Config was okay, so restart
if [ $ret -eq 0 ]; then

    # Restart Nagios
    sudo $BASEDIR/manage_services.sh restart nagios
    ret=$?
    if [ $ret -gt 0 ]; then
        # Remove LOCKFILE
        rm -f "$LOCKFILE"
        exit 6
    fi

    # Make a new NOM checkpoint
    ./nom_create_nagioscore_checkpoint.sh > /dev/null 2>&1 &
  
    # Remove LOCKFILE
    rm -f "$LOCKFILE"
    exit 0

# There was a problem with the config, so restore older config from last NOM checkpoint
else
    # Make a new NOM error checkpoint
    ./nom_create_nagioscore_errorpoint.sh

    # Restore the last known good checkpoint
    ./nom_restore_nagioscore_checkpoint.sh
  
    # Remove LOCKFILE
    rm -f "$LOCKFILE"
  
    exit 1
fi

# Remove LOCKFILE
rm -f "$LOCKFILE"
