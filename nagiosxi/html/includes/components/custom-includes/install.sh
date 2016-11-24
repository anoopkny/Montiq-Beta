#!/bin/bash

BASEDIR=$(dirname $(readlink -f $0))

# Set permissions for the custom-includes directory
chmod g+w $BASEDIR/css $BASEDIR/images $BASEDIR/javascript
