#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

# Login to NagiosQL
/usr/bin/php -q nagiosql_login.php

#error handling
ret=$?
if [ $ret -gt 0 ]; then
	echo "NAGIOSQL LOGIN FAILED!"
	exit $ret
fi

# Import all data
/usr/bin/php -q nagiosql_importall.php

ret=$?
if [ $ret -gt 0 ]; then
	echo "NAGIOSQL IMPORT FAILED!"
	exit $ret
fi