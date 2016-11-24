#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$
#
# Restores a specific snapshot
# Requires a timestamp of the snapshot that should be restored

cfgdir="/usr/local/nagios/etc"
checkpointdir="/usr/local/nagiosxi/nom/checkpoints/nagioscore"

ts=$1
archives=$2

ss=$checkpointdir/$archives$ts.tar.gz

if [ ! -f $ss ]; then
    echo "NOM SNAPSHOT $ss NOT FOUND!"
    exit 1
fi

# Delete the current Nagios core config files
#find /usr/local/nagios/etc/ -name "*.cfg" -exec ls -al {} \;
find /usr/local/nagios/etc/ -name "*.cfg" -exec rm -f {} \;

# Restore config files from checkpoint file
pushd / 
echo "RESTORING NOM SNAPSHOT : $ss"
tar -pxzf "$ss"
popd

# Fix permissions on config files
sudo ./reset_config_perms.sh



