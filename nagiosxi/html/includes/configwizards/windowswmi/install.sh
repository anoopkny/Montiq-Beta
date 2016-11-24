#!/bin/sh
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
if [ "el$ver" == "el7" ]; then
    if test ! -f "/lib64/libsasl2.so.2"; then
      echo "No 'libsasl2.so.2', we will link libsasl3.so to libsasl2.so.2"
      ( 
          cd /lib64
          ln -s /lib64/libsasl2.so.3.0.0 libsasl2.so.2
      )
    fi
fi

(
	cd /tmp
	
	if [ ! -f $INSTALL_PATH/offline ]; then
		wget https://assets.nagios.com/downloads/nagiosxi/scripts/wmicinstall.py
	else
		cp $INSTALL_PATH/packages/offline_install/Downloads/wmicinstall.py ./
	fi
	
	chmod +x wmicinstall.py
	./wmicinstall.py
    
)

exit 0
