#!/bin/bash
PATH=$PATH:/sbin:/usr/sbin

BASEDIR=$(dirname $(readlink -f $0))

repair() {
    $BASEDIR/repairmysql.sh $1
    exit_code=$?
    if [ $exit_code -eq 0 ]; then
        exit_message+=$1$' database repair succeeded\n'
    elif [ $exit_code -eq 6 ]; then
        exit_message+=$1$' database repair skipped, no *.MYI files found\n'
    else
        exit_message+=$1$' database repair FAILED, please check output above!\n'
    fi
}

if [ ! -f "$BASEDIR/repair_databases.lock" ]; then
    touch "$BASEDIR/repair_databases.lock"
    $BASEDIR/manage_services.sh status mysqld
    mysqlstatus=$?
    if [ ! $mysqlstatus -eq 0 ]; then
        rm -f /var/lib/mysql/mysql.sock
        $BASEDIR/manage_services.sh start mysqld
    fi
    repair nagios
    repair nagiosql
    XIDBTYPE=$(/usr/bin/php -q $BASEDIR/nagiosxi_dbtype.php)
    if [ "$XIDBTYPE" == "mysql" ]; then
        repair nagiosxi
    fi
    $BASEDIR/manage_services.sh restart ndo2db
    $BASEDIR/manage_services.sh restart nagios
    rm -f "$BASEDIR/repair_databases.lock"
    echo ""
    echo "======================="
    echo "$exit_message"
fi