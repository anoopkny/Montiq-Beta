#!/bin/bash
#
# Manage Services (start/stop/restart)
# =====================
# Built to allow start/stop/restart of services using the proper method based on
# the actual version of operating system.
#
# Examples:
# ./manage_services.sh start httpd
# ./manage_services.sh restart mysqld
# ./manage_services.sh checkconfig nagios
#

BASEDIR=$(dirname $(readlink -f $0))

# IMPORT ALL XI CFG VARS
. $BASEDIR/../var/xi-sys.cfg

# Things you can and can't do
first=("start" "stop" "restart" "status" "reload" "checkconfig")
second=("postgresql" "httpd" "mysqld" "nagios" "ndo2db" "npcd" "snmptt" "ntpd" "crond")

# Helper functions
# -----------------------

contains () {
    local array="$1[@]"
    local seeking=$2
    local in=1
    for element in "${!array}"; do
        if [[ $element == $seeking ]]; then
            in=0
            break
        fi
    done
    return $in
}

# Verify to avoid abuse
# -----------------------

# Check to verify the proper usage format
# ($1 = action, $2 = service name)

if ! contains first "$1"; then
    echo "First parameter must be one of: ${first[*]}"
    exit 1
fi

if ! contains second "$2"; then
    echo "Second parameter must be one of: ${second[*]}"
    exit 1
fi

action=$1

# if service name is defined in xi-sys.cfg use that name
# else use name passed
if [ ! -z "${!2}" ];then
    service=${!2}
else
    service=$2
fi


# Run the command
# -----------------------

# CentOS / Red Hat

if [ "$distro" == "CentOS" ] || [ "$distro" == "RedHatEnterpriseServer" ] || [ "$distro" == "EnterpriseEnterpriseServer" ] || [ "$distro" == "OracleServer" ]; then
    if [ "$dist" == "el7" ]; then
        `which systemctl` $action $service
        return_code=$?
        if [ "$service" == "mysqld" ] && [ $return_code -ne 0 ]; then
            service="mariadb"
            `which systemctl` $action $service
            return_code=$?
        fi
    elif [ ! `command -v service` ]; then
        /etc/init.d/$service $action
        return_code=$?
    else
        `which service` $service $action
        return_code=$?
    fi
fi

# OpenSUSE / SUSE Enterprise

if [ "$distro" == "SUSE LINUX" ]; then
    if [ "$dist" == "suse11" ]; then
        `which service` $service $action
        return_code=$?
    fi
fi

# Ubuntu / Debian

# Others?

exit $return_code
