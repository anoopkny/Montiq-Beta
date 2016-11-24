#!/bin/bash
 
#capacityplanning component sanity check

function zipit() {
	:
}

#~ Include general library (should go in all sanity scripts.)
if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

do_these_files_exist $COMPONENTS/capacityplanning/capacityplanning.inc.php \
	$COMPONENTS/capacityplanning/capacityplanning.inc.php \
	$COMPONENTS/capacityplanning/capacityplanning.php \
	$COMPONENTS/capacityplanning/capacityPlanning.py \
	$COMPONENTS/capacityplanning/Forecast.py \
	$COMPONENTS/capacityplanning/RRDDatastore.py \
	$COMPONENTS/capacityplanning/timeframe.py \
	$COMPONENTS/capacityplanning/timepicker.js \
	$COMPONENTS/capacityplanning/XMLrep.py \
	$COMPONENTS/capacityplanning/includes/capacityplanning.css \
	$COMPONENTS/capacityplanning/includes/capacityplanning.js \
	$COMPONENTS/capacityplanning/includes/timepicker.js

is_protected $COMPONENTS/capacityplanning/capacityplanning.inc.php \
	$COMPONENTS/capacityplanning/capacityplanning.php

print_results


includes:
