#!/bin/sh 

if [ ! -f $INSTALL_PATH/offline ]; then
	yum install perl-Net-DNS -y
fi

exit 0