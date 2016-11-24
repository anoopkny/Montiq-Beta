#!/bin/bash

# Make sure we have the backup file
if [ $# != 1 ]; then
	echo "Usage: $0 <backupfile>"
	echo "This script restores your XI system using a previously made Nagios XI backup file."
	exit 1
fi
backupfile=$1

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg
php $BASEDIR/import_xiconfig.php > $BASEDIR/config.dat
. $BASEDIR/config.dat
rm -rf $BASEDIR/config.dat

# MySQL root password
mysqlpass="nagiosxi"

# Must be root
me=`whoami`
if [ $me != "root" ]; then
	echo "You must be root to run this script."
	exit 1
fi

rootdir=/store/backups/nagiosxi

##############################
# MAKE SURE BACKUP FILE EXIST
##############################
if [ ! -f $backupfile ]; then
	echo "Unable to find backup file $backupfile!"
	exit 1
fi

# Look inside the (nested) tarball to see what architecture the nagios
# executable is
if [ $backupfile == "/store/backups/nagiosxi-demo.tar.gz" ];then
backuparch="i686"
else
backuparch=$(eval $(echo $(tar -xzOf $backupfile $(basename $backupfile .tar.gz)/nagiosxi.tar.gz | tar -xzOf - usr/local/nagiosxi/var/xi-sys.cfg |cat|grep ^arch\=));echo $arch)

fi
arch=$(uname -m)
case $arch in
	i*86 )   arch="i686" ;;
	x86_64 ) arch="x86_64" ;;
	* )      echo "Error detecting architecture."; exit 1
esac

if [ "$arch" != "$backuparch" ]; then
	echo "WARNING: you are trying to restore a $backuparch backup on a $arch system"
	echo "         Compiled plugins and other binaries will NOT be restored."
	echo
	read -r -p "Are you sure you want to continue? [y/N] " ok

	case "$ok" in
		Y | y ) : ;;
		* )     exit 1
	esac
fi

backupver=$(eval $(echo $(tar -xzOf $backupfile $(basename $backupfile .tar.gz)/nagiosxi.tar.gz | tar -xzOf - usr/local/nagiosxi/var/xi-sys.cfg |cat|grep ^ver\=));echo $ver)

if [ "$ver" != "$backupver" ]; then
	echo "WARNING: you are trying to restore a OS $backupver backup on a OS $ver system"
	echo "         Compiled plugins and other binaries as well as httpd configurations"
    echo "         will NOT be restored."
	echo
	read -r -p "Are you sure you want to continue? [y/N] " ok

	case "$ok" in
		Y | y ) : ;;
		* )     exit 1
	esac
fi

##############################
# MAKE TEMP RESTORE DIRECTORY
##############################
#ts=`echo $backupfile | cut -d . -f 1`
ts=`date +%s`
echo "TS=$ts"
mydir=${rootdir}/${ts}-restore
mkdir -p $mydir
if [ ! -d $mydir ]; then
	echo "Unable to create restore directory $mydir!"
	exit 1
fi


##############################
# UNZIP BACKUP
##############################
echo "Extracting backup to $mydir..."
cd $mydir
tar xzfps $backupfile

# Change to subdirectory
cd `ls`

# Make sure we have some directories here...
backupdir=`pwd`
echo "In $backupdir..."
if [ ! -f nagiosxi.tar.gz ]; then
	echo "Unable to find files to restore in $backupdir"
	exit 1
fi

echo "Backup files look okay.  Preparing to restore..."


##############################
# SHUTDOWN SERVICES
##############################
echo "Shutting down services..."
$BASEDIR/manage_services.sh stop nagios
$BASEDIR/manage_services.sh stop ndo2db
$BASEDIR/manage_services.sh stop npcd


##############################
# RESTORE DIRS
##############################
rootdir=/
echo "Restoring directories to ${rootdir}..."

# Nagios Core
echo "Restoring Nagios Core..."
if [ "$arch" == "$backuparch" ] && [ "$ver" == "$backupver" ]; then
    rm -rf /usr/local/nagios
	cd $rootdir && tar xzf $backupdir/nagios.tar.gz 
else
    rm -rf /usr/local/nagios/etc /usr/local/nagios/share /usr/local/nagios/var
    cd $rootdir && tar --exclude="usr/local/nagios/bin" --exclude="usr/local/nagios/sbin" --exclude="usr/local/nagios/libexec" -xzf $backupdir/nagios.tar.gz
    cd $rootdir && tar --wildcards 'usr/local/nagios/libexec/*.*' -xzf $backupdir/nagios.tar.gz
fi

# Nagios XI
echo "Restoring Nagios XI..."
if [ "$arch" == "$backuparch" ] && [ "$ver" == "$backupver" ]; then
    rm -rf /usr/local/nagiosxi
    cd $rootdir && tar xzfps $backupdir/nagiosxi.tar.gz 
else
    mv $BASEDIR/../var/xi-sys.cfg /tmp/xi-sys.cfg
    rm -rf /usr/local/nagiosxi
    cd $rootdir && tar xzfps $backupdir/nagiosxi.tar.gz 
    cp -r /tmp/xi-sys.cfg $BASEDIR/../var/xi-sys.cfg
    rm -f /tmp/xi-sys.cfg
fi

# NagiosQL
echo "Restoring NagiosQL..."
rm -rf /var/www/html/nagiosql
cd $rootdir && tar xzfps $backupdir/nagiosql.tar.gz 

# NagiosQL etc
echo "Restoring NagiosQL backups..."
rm -rf /etc/nagiosql
cd $rootdir && tar xzfps $backupdir/nagiosql-etc.tar.gz 

# NRDP
echo "Restoring NRDP backups..."
rm -rf /usr/local/nrdp
cd $rootdir && tar xzfps $backupdir/nrdp.tar.gz

# MRTG
if [ -f $backupdir/mrtg.tar.gz ]; then
    echo "Restoring MRTG..."
    rm -rf /var/lib/mrtg
    cd $rootdir && tar xzfps $backupdir/mrtg.tar.gz 
    cp -rp $backupdir/conf.d /etc/mrtg/
    cp -p $backupdir/mrtg.cfg /etc/mrtg/
    chown $apacheuser:$nagiosgroup /etc/mrtg/conf.d /etc/mrtg/mrtg.cfg
fi
cd $backupdir

# Nagvis 
if [ -f $backupdir/nagvis.tar.gz ]; then 
	echo "Restoring Nagvis backups..." 
	rm -rf /usr/local/nagvis 
	cd $rootdir && tar xzfps $backupdir/nagvis.tar.gz 
	chown -R apache.apache /usr/local/nagvis 
fi 

# RE-IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg
php $BASEDIR/import_xiconfig.php > $BASEDIR/config.dat
. $BASEDIR/config.dat
rm -rf $BASEDIR/config.dat

##############################
# RESTORE DATABASES
##############################
echo "Restoring MySQL databases..."
#mysql -u root --password=$mysqlpass nagios < mysql/nagios.sql
#mysql -u root --password=$mysqlpass nagiosql < mysql/nagiosql.sql
mysql -h $cfg__db_info__ndoutils__dbserver -u root --password=$mysqlpass < $backupdir/mysql/nagios.sql
res=$?
if [ $res != 0 ]; then
	echo "Error restoring MySQL database 'nagios' - check the password in this script!"
	exit;
fi

mysql -h $cfg__db_info__nagiosql__dbserver -u root --password=$mysqlpass < $backupdir/mysql/nagiosql.sql
res=$?
if [ $res != 0 ]; then
	echo "Error restoring MySQL database 'nagiosql' - check the password in this script!"
	exit;
fi

# Only backup PostgresQL if we are still using it 
if [ $cfg__db_info__nagiosxi__dbtype == "pgsql" ]; then
	
    service postgresql initdb &>/dev/null || true
    
    echo "Restoring Nagios XI PostgresQL database..."
    if [ -f /var/lib/pgsql/data/pg_hba.conf ]; then
        cp -pr /var/lib/pgsql/data/pg_hba.conf /var/lib/pgsql/data/pg_hba.conf.old
    fi
    echo "local	 all	     all			       trust
host    all         all         127.0.0.1/32          trust
host    all         all         ::1/128               trust" > /var/lib/pgsql/data/pg_hba.conf
    
    $BASEDIR/manage_services.sh start postgresql
	
    sudo -u postgres psql -c "create user nagiosxi with password 'n@gweb';"
    sudo -u postgres psql -c "create database nagiosxi owner nagiosxi;"
    
    # Sleep a bit (required so Postgres finishes startup before we connect again)
    echo "Sleeping for a few seconds (please wait)..."
    sleep 7
    
	psql -U nagiosxi nagiosxi < $backupdir/pgsql/nagiosxi.sql
	res=$?
	if [ $res != 0 ]; then
		echo "Error restoring PostgresQL database 'nagiosxi' !"
		exit;
	fi
	$BASEDIR/manage_services.sh restart postgresql
	if [ "$dist" == "el7" ]; then
		systemctl enable postgresql.service
	else
		chkconfig postgresql on
	fi
	# Remove nagiosxi db from mysql if postgres is used instead
	mysql -h "$cfg__db_info__nagiosql__dbserver" -u root --password=$mysqlpass < "DROP TABLE IF EXISTS nagiosxi;"
else
	echo "Restoring Nagios XI MySQL database..."
	mysql -h "$cfg__db_info__nagiosql__dbserver" -u root --password=$mysqlpass < $backupdir/mysql/nagiosxi.sql
	res=$?
	if [ $res != 0 ]; then
		echo "Error restoring MySQL database 'nagiosxi' !"
		exit;
	fi
fi

echo "Restarting database servers..."
$BASEDIR/manage_services.sh restart mysqld

##############################
# RESTORE CRONJOB ENTRIES
##############################
echo "Restoring Apache cronjobs..."
cp -rp $backupdir/cron/apache /var/spool/cron/apache

##############################
# RESTORE SUDOERS
##############################
# Not necessary

##############################
# RESTORE LOGROTATE
##############################
echo "Restoring logrotate config files..."
cp -rp $backupdir/logrotate/nagiosxi /etc/logrotate.d

##############################
# RESTORE APACHE CONFIG FILES
##############################
if [ "$ver" == "$backupver" ]; then
    echo "Restoring Apache config files..."
    cp -rp $backupdir/httpd/*.conf /etc/httpd/conf/
else
    echo "Skipping Apache config files restoration"
fi

##############################
# RESTART SERVICES
##############################
$BASEDIR/manage_services.sh restart httpd
$BASEDIR/manage_services.sh start npcd
$BASEDIR/manage_services.sh start ndo2db
$BASEDIR/manage_services.sh start nagios

##############################
# DELETE TEMP RESTORE DIRECTORY
##############################
rm -rf $mydir

##############################
# DELETE forceinstall FILE
##############################
rm -f /tmp/nagiosxi.forceinstall

echo " "
echo "==============="
echo "RESTORE COMPLETE"
echo "==============="

exit 0;
