#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

# Get rid of backups older than 24 hours
find /etc/nagiosql/backup -mmin +1440 -type f -exec rm -f {} \;


