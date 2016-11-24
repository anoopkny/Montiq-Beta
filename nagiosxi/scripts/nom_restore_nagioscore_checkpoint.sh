#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

cfgdir="/usr/local/nagios/etc"
checkpointdir="/usr/local/nagiosxi/nom/checkpoints/nagioscore"


# Find latest snapshot
latest=`ls -1r $checkpointdir/*.gz | head --lines=1`

if [ "x$latest" = "x" ]; then
    echo "NO NOM SNAPSHOT FOUND!"
    exit 1
fi

echo "LATEST NOM SNAPSHOT: $latest"

# Delete the current Nagios core config files
#find /usr/local/nagios/etc/ -name "*.cfg" -exec ls -al {} \;

# Restore config files from checkpoint file
pushd / 
echo "RESTORING NOM SNAPSHOT : $latest"
#tar -p -s -xzf "$checkpointdir/$latest"
tar -p -s -xzf "$latest"
popd

# Fix permissions on config files
sudo ./reset_config_perms.sh



