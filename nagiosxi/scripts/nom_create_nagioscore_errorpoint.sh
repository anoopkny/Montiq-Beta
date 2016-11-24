#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg
php $BASEDIR/import_xiconfig.php > $BASEDIR/config.dat
. $BASEDIR/config.dat
rm -rf $BASEDIR/config.dat

cfgdir="/usr/local/nagios/etc"
checkpointdir="/usr/local/nagiosxi/nom/checkpoints/nagioscore/errors"

pushd $checkpointdir

# What timestamp should we use for this files?
stamp=`date +%s`

# Get Nagios verification output
output=`/usr/local/nagios/bin/nagios -v /usr/local/nagios/etc/nagios.cfg > $stamp.txt`

# Create the tarball
tar czf $stamp.tar.gz $cfgdir

# Fix perms (if script run by root)
chown $nagiosuser:$nagiosgroup $stamp.txt
chown $nagiosuser:$nagiosgroup $stamp.tar.gz

popd
