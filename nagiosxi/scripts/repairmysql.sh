#!/bin/bash

# This script repairs one (or all) tables in a specific Nagios XI mysql database
# Usage:
# repairmysql.sh [database] [table]

BASEDIR=$(dirname $(readlink -f $0))

if [ $# -lt 1 ]; then
    echo "Usage: $0 [table]"
    echo ""
    echo "This script repairs one or more tables in a specific Nagios XI MySQL database."
    echo "Valid database names include:"
    echo " nagios";
    echo " nagiosql";
    echo " nagiosxi";
    echo ""
    echo "If the [table] option is omitted, all tables in the database will be repaired."
    echo ""
    echo "Example Usage:"
    echo " $0 nagios nagios_logentries"
    echo ""
    exit 1
fi

$BASEDIR/manage_services.sh status mysqld
mysqlstatus=$?
if [ ! $mysqlstatus -eq 0 ]; then
    rm -f /var/lib/mysql/mysql.sock
fi

db=$1
table="";
if [ $# -eq 2 ]; then
    table=$2
fi

echo "DATABASE: $db"
echo "TABLE: $table"

cmd="/usr/bin/myisamchk -r -f"

if [ "x$table" == "x" ]; then
    t="*.MYI"
else
    t=$table;
fi

exit_code=0
dest="/var/lib/mysql/$db"
pushd $dest
ret=$?
if [ $ret -eq 0 ]; then
    if [ "$t" == "*.MYI" ] && ! ls $t >/dev/null 2>&1; then
        echo "No *.MYI files found, skipping $db..."
        exit 6
    fi
    $BASEDIR/manage_services.sh stop mysqld
    $cmd $t --sort_buffer_size=256M
    exit_code=$?
    $BASEDIR/manage_services.sh start mysqld
    popd
else
    echo "ERROR: Could not change to dir: $dest"
    exit 1
fi

echo " "
echo "==============="
echo "REPAIR COMPLETE"
echo "==============="

exit $exit_code