#!/bin/sh
if [ -f $INSTALL_PATH/offline ]; then
	echo Nothing to do here, offline install.
else
	yum install jwhois -y
fi
exit 0