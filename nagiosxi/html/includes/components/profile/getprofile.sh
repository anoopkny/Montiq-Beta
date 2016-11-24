#!/bin/bash

# Get OS & version
if which lsb_release &>/dev/null; then
	distro=`lsb_release -si`
	version=`lsb_release -sr`
elif [ -r /etc/redhat-release ]; then

	if rpm -q centos-release; then
		distro=CentOS
	elif rpm -q sl-release; then
		distro=Scientific
	elif [ -r /etc/oracle-release ]; then
		distro=OracleServer
    elif rpm -q fedora-release; then
		distro=Fedora
	elif rpm -q redhat-release || rpm -q redhat-release-server; then
		distro=RedHatEnterpriseServer
	fi >/dev/null

	version=`sed 's/.*release \([0-9.]\+\).*/\1/' /etc/redhat-release`
else
	# Release is not RedHat or CentOS, let's start by checking for SuSE
	# or we can just make the last-ditch effort to find out the OS by sourcing os-release if it exists
	if [ -r /etc/os-release ]; then
		source /etc/os-release
		if [ -n "$NAME" ]; then
			distro=$NAME
			version=$VERSION_ID
		fi
	fi
fi

ver="${version%%.*}"

echo "-------------------Fetching Information-------------------"

echo "Please wait......."

echo "Creating nagios.txt...";

nagios_log_file=$(cat /usr/local/nagios/etc/nagios.cfg | sed -n -e 's/^log_file=//p')
tail -100 "$nagios_log_file" &> /usr/local/nagiosxi/var/components/profile/nagios.txt;

echo "Creating perfdata.txt...";

perfdata_log_file=$(cat /usr/local/nagios/etc/pnp/process_perfdata.cfg | sed -n -e 's/^LOG_FILE = //p')
tail -100 "$perfdata_log_file" &> /usr/local/nagiosxi/var/components/profile/perfdata.txt;

echo "Creating npcd.txt...";

npcd_log_file=$(cat /usr/local/nagios/etc/pnp/npcd.cfg | sed -n -e 's/^log_file = //p')
tail -100 "$npcd_log_file" &> /usr/local/nagiosxi/var/components/profile/npcd.txt;

echo "Creating cmdsubsys.txt...";

tail -100 /usr/local/nagiosxi/var/cmdsubsys.log > /usr/local/nagiosxi/var/components/profile/cmdsubsys.txt;

echo "Creating eventman.txt...";

tail -100 /usr/local/nagiosxi/var/eventman.log > /usr/local/nagiosxi/var/components/profile/eventman.txt;

echo "Creating systemlog.txt...";

sudo /usr/bin/tail -100 /var/log/messages > /usr/local/nagiosxi/var/components/profile/systemlog.txt;

echo "Creating apacheerrors.txt...";

sudo /usr/bin/tail -100 /var/log/httpd/error_log > /usr/local/nagiosxi/var/components/profile/apacheerrors.txt;

echo "Creating mysqllog.txt...";

# Determine if MySQL or MariaDB is localhost
db_host=$(cat /usr/local/nagiosxi/html/config.inc.php | sed -rn "/\"ndoutils\" => array\(*/,/\"dbmaint\"/p" | grep -o -P '(?<="dbserver" => ).*(?=,)' | tr -d \')
echo "The database host is $db_host" > /usr/local/nagiosxi/var/components/profile/database_host.txt
if [ "$db_host" == "localhost" ];then
    if [ -f /var/log/mysqld.log ];then
        sudo /usr/bin/tail -100 /var/log/mysqld.log > /usr/local/nagiosxi/var/components/profile/mysqllog.txt;
    elif [ -f /var/log/mariadb/mariadb.log ];then
        sudo /usr/bin/tail -100 /var/log/mariadb/mariadb.log > /usr/local/nagiosxi/var/components/profile/mariadblog.txt
    fi
fi

echo "Creating a sanatized copy of config.inc.php...";
cp /usr/local/nagiosxi/html/config.inc.php /usr/local/nagiosxi/var/components/profile/config.inc.php
sed -i '/pwd/d' /usr/local/nagiosxi/var/components/profile/config.inc.php
sed -i '/password/d' /usr/local/nagiosxi/var/components/profile/config.inc.php

echo "Creating memorybyprocess.txt...";

ps aux --sort -rss > /usr/local/nagiosxi/var/components/profile/memorybyprocess.txt

echo "Creating filesystem.txt...";

df -h > /usr/local/nagiosxi/var/components/profile/filesystem.txt;
echo "" >> /usr/local/nagiosxi/var/components/profile/filesystem.txt;
df -i >> /usr/local/nagiosxi/var/components/profile/filesystem.txt;

echo "Dumping PS - AEF to psaef.txt...";

ps -aef > /usr/local/nagiosxi/var/components/profile/psaef.txt;

echo "Creating top log...";

top -b -n 1 > /usr/local/nagiosxi/var/components/profile/top.txt;

FILE=$(ls /usr/local/nagiosxi/nom/checkpoints/nagioscore/ | sort -n -t _ -k 2 | grep .gz | tail -1) 
cp /usr/local/nagiosxi/nom/checkpoints/nagioscore/$FILE /usr/local/nagiosxi/var/components/profile/

echo "Copying objects.cache"

objects_cache_file=$(cat /usr/local/nagios/etc/nagios.cfg | sed -n -e 's/^object_cache_file=//p')
cp "$objects_cache_file" /usr/local/nagiosxi/var/components/profile/

echo "Copying MRTG Configs"

tar -pczf /usr/local/nagiosxi/var/components/profile/mrtg.tar.gz /etc/mrtg/

echo "Counting Performance Data Files"

spool_perfdata_location=$(cat /usr/local/nagios/etc/pnp/npcd.cfg | sed -n -e 's/^perfdata_spool_dir = //p')
echo "Total files in $spool_perfdata_location" > /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
ls -al $spool_perfdata_location | wc -l >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
echo "" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;

spool_xidpe_location=$(cat /usr/local/nagios/etc/commands.cfg | sed -n -e 's/\$TIMET\$.perfdata.host//p' | sed -n -e 's/\s*command_line\s*\/bin\/mv\s//p' | sed -n -e 's/.*\s//p')
echo "Total files in $spool_xidpe_location" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
ls -al $spool_xidpe_location | wc -l >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
echo "" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;

echo "Counting MRTG Files"

echo "Total files in /etc/mrtg/conf.d/" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
ls -al /etc/mrtg/conf.d/ | wc -l >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
echo "" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;

echo "Total files in /var/lib/mrtg/" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
ls -al /var/lib/mrtg/ | wc -l >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;
echo "" >> /usr/local/nagiosxi/var/components/profile/File_Counts.txt;

echo "Getting Network Information"
ip addr > /usr/local/nagiosxi/var/components/profile/ip_addr.txt

echo "Adding latest snapshot to: `pwd`"

## temporarily change to that directory, zip, then leave
(
	cd /usr/local/nagiosxi/var/components/ && zip -r profile.zip profile
)

echo "Zipping logs directory...";

#Remove directory to avoid duplicate files
rm -rf /usr/local/nagiosxi/var/components/profile/

echo "Backup and Zip complete!";
