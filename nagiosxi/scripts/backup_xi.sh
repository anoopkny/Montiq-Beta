#!/bin/bash

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg
php $BASEDIR/import_xiconfig.php > $BASEDIR/config.dat
. $BASEDIR/config.dat
rm -rf $BASEDIR/config.dat
SBLOG="/usr/local/nagiosxi/var/components/scheduledbackups.log"
ts=`date +%s`

###############################
# USAGE / HELP
###############################
usage () {
	echo ""
	echo "Use this script to backup Nagios XI."
	echo ""
		echo " -n | --name              Set the name of the backup minus the .tar.gz"
        echo " -p | --prepend           Prepend a string to the .tar.gz name"
        echo " -a | --append            Append a string to the .tar.gz name"
        echo " -d | --directory         Change the directory to store the compressed backup"
	echo ""
}

###############################
# ADDING LOGIC FOR NEW BACKUPS
###############################
while [ -n "$1" ]; do
	case "$1" in
		-h | --help)
			usage
			exit 0
			;;
		-n | --name)
			fullname=$2
			;;
		-p | --prepend)
			prepend=$2"."
			;;
		-a | --append)
			append="."$2
			;;
		-d | --directory)
			rootdir=$2
			;;
	esac
	shift
done

# Restart nagios to forcibly update retention.dat
$BASEDIR/manage_services.sh restart nagios
sleep 10

if [ -z $rootdir ]; then
	rootdir="/store/backups/nagiosxi"
fi

# Move to root dir to store backups
cd $rootdir

#############################
# SET THE NAME & TIME
#############################
name=$fullname

if [ -z $fullname ]; then
	name=$prepend$ts$append
fi

# Get current Unix timestamp as name
if [ -z $name ]; then
	name=$ts
fi

# My working directory
mydir=$rootdir/$name

# Make directory for this specific backup
mkdir -p $mydir

##############################
# BACKUP DIRS
##############################

echo "Backing up Core Config Manager (NagiosQL)..."
#cp -rp /var/www/html/nagiosql $mydir
#cp -rp /etc/nagiosql $mydir/nagiosql-etc
tar czfp $mydir/nagiosql.tar.gz /var/www/html/nagiosql
tar czfp $mydir/nagiosql-etc.tar.gz /etc/nagiosql

echo "Backing up Nagios Core..."
#cp -rp /usr/local/nagios $mydir
tar czfp $mydir/nagios.tar.gz /usr/local/nagios

echo "Backing up Nagios XI..."
#cp -rp /usr/local/nagiosxi $mydir
tar czfp $mydir/nagiosxi.tar.gz /usr/local/nagiosxi

echo "Backing up MRTG..."
#cp -rp /usr/local/nagiosxi $mydir
tar czfp $mydir/mrtg.tar.gz /var/lib/mrtg
cp /etc/mrtg/mrtg.cfg $mydir/
cp -r /etc/mrtg/conf.d $mydir/

echo "Backing up NRDP..."
tar czfp $mydir/nrdp.tar.gz /usr/local/nrdp

echo "Backing up Nagvis..." 
tar czfp $mydir/nagvis.tar.gz /usr/local/nagvis

##############################
# BACKUP DATABASES
##############################
echo "Backing up MySQL databases..."
mkdir -p $mydir/mysql
mysqldump -h $cfg__db_info__ndoutils__dbserver -u $cfg__db_info__ndoutils__user --password="$cfg__db_info__ndoutils__pwd" --add-drop-database -B $cfg__db_info__ndoutils__db > $mydir/mysql/nagios.sql
res=$?
if [ $res != 0 ]; then
	echo "Error backing up MySQL database 'nagios' - check the password in this script!" | tee -a $SBLOG
	rm -r $mydir
	exit $res;
fi
mysqldump -h $cfg__db_info__nagiosql__dbserver -u $cfg__db_info__nagiosql__user --password="$cfg__db_info__nagiosql__pwd" --add-drop-database -B $cfg__db_info__nagiosql__db > $mydir/mysql/nagiosql.sql
res=$?
if [ $res != 0 ]; then
	echo "Error backing up MySQL database 'nagiosql' - check the password in this script!" | tee -a $SBLOG
	rm -r $mydir
	exit $res;
fi

# Only backup PostgresQL if we are still using it 
if [ $cfg__db_info__nagiosxi__dbtype == "pgsql" ]; then
	echo "Backing up PostgresQL databases..."
	mkdir -p $mydir/pgsql
	if [ -z $cfg__db_info__nagiosxi__dbserver ]; then
		cfg__db_info__nagiosxi__dbserver="localhost"
	fi
	pg_dump -h $cfg__db_info__nagiosxi__dbserver -c -U $cfg__db_info__nagiosxi__user $cfg__db_info__nagiosxi__db > $mydir/pgsql/nagiosxi.sql
	res=$?
	if [ $res != 0 ]; then
		echo "Error backing up PostgresQL database 'nagiosxi' !" | tee -a $SBLOG
		rm -r $mydir
		exit $res;
	fi
else
	mysqldump -h "$cfg__db_info__nagiosxi__dbserver" -u $cfg__db_info__nagiosxi__user --password="$cfg__db_info__nagiosxi__pwd" --add-drop-database -B $cfg__db_info__nagiosxi__db > $mydir/mysql/nagiosxi.sql
	res=$?
	if [ $res != 0 ]; then
		echo "Error backing up MySQL database 'nagiosxi' - check the password in this script!" | tee -a $SBLOG
		rm -r $mydir
		exit $res;
	fi
fi

##############################
# BACKUP CRONJOB ENTRIES
##############################
echo "Backing up cronjobs for Apache..."
mkdir -p $mydir/cron
cp /var/spool/cron/apache $mydir/cron/apache

##############################
# BACKUP SUDOERS
##############################
# Not necessary

##############################
# BACKUP LOGROTATE
##############################
echo "Backing up logrotate config files..."
mkdir -p $mydir/logrotate
cp -rp /etc/logrotate.d/nagiosxi $mydir/logrotate

##############################
# BACKUP APACHE CONFIG FILES
##############################
echo "Backing up Apache config files..."
mkdir -p $mydir/httpd
cp -rp $httpdconfdir/nagios.conf $mydir/httpd
cp -rp $httpdconfdir/nagiosxi.conf $mydir/httpd
cp -rp $httpdconfdir/nagiosql.conf $mydir/httpd

##############################
# COMPRESS BACKUP
##############################
echo "Compressing backup..."
tar czfp $name.tar.gz $name
rm -rf $name

#change ownership
chown $nagiosuser:$nagiosgroup $name.tar.gz

if [ -s $name.tar.gz ];then

	echo " "
	echo "==============="
	echo "BACKUP COMPLETE"
	echo "==============="
	echo "Backup stored in $rootdir/$name.tar.gz"

	exit 0;
else
	echo " "
	echo "==============="
	echo "BACKUP FAILED"
	echo "==============="
	echo "File was not created at $rootdir/$name.tar.gz"
	rm -r $mydir
	exit 1;
fi
