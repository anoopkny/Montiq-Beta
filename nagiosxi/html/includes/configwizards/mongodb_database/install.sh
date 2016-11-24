#!/bin/sh
if [ -f $INSTALL_PATH/offline ]; then
	echo Nothing to do here, offline install.
else
	yum install pymongo -y
fi
exit 0