#!/bin/bash

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg
php $BASEDIR/import_xiconfig.php > $BASEDIR/config.dat
. $BASEDIR/config.dat
rm -rf $BASEDIR/config.dat

## UNINSTALL NOTICE #########################################

fmt -s -w $(tput cols) <<-EOF
        ==================================
        !! DESTRUCTIVE UNINSTALL NOTICE !!
        ==================================
        WARNING: This script will uninstall 
        
        Nagios 
        MySql
        Postgresql
        
        from this system as well as all data associated with these services.
        This action is irreversible and will result in the removal of
        all Nagios databases, configuration files, log files, and services.

EOF

read -p "Are you sure you want to continue? [y/N] " res

if [ "$res" = "y" -o "$res" = "Y" ]; then
        echo "Proceeding with uninstall..."
else
        echo "Uninstall cancelled"
        exit 1
fi

# Stop services
echo "Stopping services..."
$BASEDIR/manage_services.sh stop nagios
$BASEDIR/manage_services.sh stop ndo2db
$BASEDIR/manage_services.sh stop npcd

# Remove init.d files
echo "removing init files..."
rm -rf /etc/init.d/nagios
rm -rf /etc/init.d/npcd
rm -rf /etc/init.d/ndo2db

# Remove users and sudoers
echo "Removing users and suduoers..."
userdel -r nagios
groupdel nagcmd
rm -f /etc/sudoers.d/nagiosxi

# Remove crontabs
echo "Removing crontabs..."
rm -f /etc/cron.d/nagiosxi

# Remove various files
echo "Removing files..."
rm -rf /usr/local/nagios
rm -rf /usr/local/nagiosxi
# Remove NagiosQL files
echo "Removing NagiosQL files..."
rm -rf /etc/nagiosql
rm -rf /var/www/html/nagiosql
rm -rf /var/lib/mysql
rm -rf /var/lib/pgsql
# Not going to do this as it may contain your only backup
#rm -rf /store/backups
# Remove Apache configs
echo "Removing Apache configs..."
rm -f $httpdconfdir/nagios.conf
rm -f $httpdconfdir/nagiosxi.conf
rm -f $httpdconfdir/nagiosql.conf
rm -f $httpdconfdir/nrdp.conf
rm -f /usr/local/nrdp/nrdp.conf
if [ ! `command -v systemctl` ]; then
    service $httpd restart
else
    systemctl restart $httpd
fi
# Remove xinetd configs
echo "Removing xinetd configs..."
rm -f /etc/xinetd.d/nrpe
rm -f /etc/xinetd.d/nsca
rm -f /etc/xinetd.d/nrdp
service xinetd restart
# Remove Postgres databases
echo "Removing Postgres and mysql databases..."
yum remove mysql postgresql -y
# Remove DB backup scripts
echo "Removing database backup scripts..."
rm -f /root/scripts/automysqlbackup
rm -f /root/scripts/autopostgresqlbackup
(
cd /tmp
rm -rf nagiosxi xi*.tar.gz
)


fmt -s -w $(tput cols) <<-EOF
        ====================
        UNINSTALL COMPLETED!
        ====================

EOF


