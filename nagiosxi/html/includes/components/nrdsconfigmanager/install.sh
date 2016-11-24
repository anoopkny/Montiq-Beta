#!/bin/bash

# Install NRDS Server Plugin
# --------------------------
BASEDIR=$(dirname $0)
cd $BASEDIR

# Check whether we have sufficient privileges
if [ $(id -u) -ne 0 ]; then
    rm -f installed.nrds
    echo "This script needs to be run as root/superuser." >&2
    exit 0
fi

if [ ! -f nrds_version.txt ]; then
    echo "NRDS Version information is missing"
    exit 0
else
    nrds_version=$(<nrds_version.txt)
fi

if [ ! -f nsis_version.txt ]; then
    echo "NSIS Version information is missing"
    exit 0
else
    nsis_version=$(<nsis_version.txt)
fi

# In NRDS already installed?
if [ -f installed.nrds ]; then
    echo "NRDS already installed"
    exit 0
fi

(
    cd /tmp
    
    if [ -f $nrds_version ]; then
        rm -rf $nrds_version
    fi

    if [ -f $nsis_version ]; then
        rm -rf $nsis_version
    fi

    if [ ! -f $INSTALL_PATH/offline ]; then
        wget https://assets.nagios.com/downloads/nrdp/$nrds_version
        wget https://assets.nagios.com/downloads/nsis/$nsis_version
    else
        cp $INSTALL_PATH/packages/offlineinstall/Downloads/$nrds_version ./$nrds_version
        cp $INSTALL_PATH/packages/offlineinstall/Downloads/$nsis_version ./$nsis_version
    fi

    tar xzf $nrds_version
    cd nrds
    
    if [ -f "installnrdsserver" ];then
        ./installnrdsserver
        chmod -R ug+rwx "/usr/local/nrdp/configs" "/usr/local/nrdp/plugins"
    else
        echo "Install script not found aborting"
        exit 0
    fi
    
    cd /tmp
    tar xzf $nsis_version
    cd nsis
    
    if [ -f "install.sh" ];then
        ./install.sh
    else
        echo "unable to install nsis for building NRDS_Win"
        exit
    fi
)

if [ -f "/usr/local/nrdp/clients/nrds/nrds.pl" ]; then
    touch installed.nrds
    echo "NRDS Server component installed sucessfully"
    echo ""
else
    echo "INSTALATION FAILED: Expected /usr/local/nrdp/clients/nrds/nrds.pl to exist"
fi
