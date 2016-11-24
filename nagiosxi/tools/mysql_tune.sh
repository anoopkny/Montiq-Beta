#!/bin/bash

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg
successful=0

echo "This script will attempt to perform some basic MySQL Tuning for increased performance"
echo ""
echo "Enter the MySQL configuration file to continue..."
read -p "MySQL configuration file [/etc/my.cnf]: " file
file=${file:-/etc/my.cnf}


if [ -f $file ]; then
	if grep -q "query_cache\|table_size\|buffer_size\|open_cache" $file; then
		echo "Looks like $file has already been tuned, exiting!"
		exit 1
	else

		# cent/rhel 5 need a different tune
		if [ "$dist" == "el5" ]; then
			if ! sed -i 's/\[mysqld\]/\[mysqld\]\nquery_cache_size=16M\nquery_cache_limit=4M\ntmp_table_size=64M\nmax_heap_table_size=64M\nkey_buffer_size=32M\n/' $file; then
				successful=0
			else
				successful=1
			fi
		else
			if ! sed -i 's/\[mysqld\]/\[mysqld\]\nquery_cache_size=16M\nquery_cache_limit=4M\ntmp_table_size=64M\nmax_heap_table_size=64M\nkey_buffer_size=32M\ntable_open_cache=32\n/' $file; then
				successful=0
			else
				successful=1
			fi
		fi

		if [ $successful -eq 0 ]; then
			echo "Could not tune $file"
			exit 1
		else
			echo "Tuned $file, please restart MySQL"
			exit 0
		fi
	fi
else
	echo "Could not locate $file, please try again!"
	exit 1
fi