#!/bin/bash
# Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
# $Id$

# exit codes:
#	1	config verification failed
#	2	nagiosql login failed
#	3	nagiosql import failed
#	4	reset_config_perms.sh failed
#	5 	nagiosql_exportall.php failed (write configs failed) 
#	6	/etc/init.d/nagios restart failed 
#	7 	db_connect failed
#


# Import data to NagiosQL
./import_nagiosql.sh
ret=$?
if [ $ret -gt 0 ]; then
	exit $ret
fi

# Restart Nagios
./restart_nagios_with_export.sh

ret=$?
if [ $ret -gt 0 ]; then
	exit $ret
fi

exit 0