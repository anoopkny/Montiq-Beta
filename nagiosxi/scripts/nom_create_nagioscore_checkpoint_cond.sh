#!/bin/bash
# Create a conditional NOM checkpoint
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

scriptsdir=/usr/local/nagiosxi/scripts

/etc/init.d/nagios checkconfig
ret=$?


if [ $ret -eq 0 ]; then
    pushd $scriptsdir
    ./nom_create_nagioscore_checkpoint.sh
    popd
    echo "Config test passed.  Checkpoint created."
    exit 0
else
    echo "Config test failed.  Checkpoint aborted."
    exit 1
fi