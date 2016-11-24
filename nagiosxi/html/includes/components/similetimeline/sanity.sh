#!/bin/bash
 
#similetimeline component sanity check

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

do_these_files_exist $COMPONENTS/similetimeline/similetimeline.inc.php \
	$COMPONENTS/similetimeline/getdata.php \
	$COMPONENTS/similetimeline/index.php \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/simile-ajax-api.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/simile-ajax-bundle.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/content/history.html \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-arrow-point-down.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-arrow-point-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-arrow-point-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-arrow-point-up.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-bottom-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-bottom.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-bottom-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-top-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-top.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/bubble-top-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/close-button.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/copy.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/message-bottom-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/message-bottom-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/message-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/message-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/message-top-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/images/message-top-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/scripts/signal.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/styles/graphics.css \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_ajax/styles/graphics-ie6.css \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/timeline-api.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/timeline-bundle.css \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/timeline-bundle.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/blue-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-bottom-arrow.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-bottom-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-bottom.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-bottom-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-left-arrow.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-right-arrow.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-top-arrow.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-top-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-top.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/bubble-top-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/close-button.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/copyright.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/copyright-vertical.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/dark-blue-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/dark-green-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/dark-red-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/dull-blue-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/dull-green-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/dull-red-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/gray-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/green-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/message-bottom-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/message-bottom-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/message-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/message-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/message-top-left.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/message-top-right.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/progress-running.gif \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/red-circle.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/images/top-bubble.png \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/cs \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/de \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/en \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/es \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/fr \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/it \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/nl \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/ru \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/se \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/tr \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/vi \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/zh \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/cs/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/cs/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/de/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/de/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/en/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/en/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/es/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/es/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/fr/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/fr/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/it/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/it/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/nl/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/nl/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/ru/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/ru/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/se/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/se/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/tr/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/tr/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/vi/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/vi/timeline.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/zh/labellers.js \
	$COMPONENTS/similetimeline/timeline_2.3.0/timeline_js/scripts/l10n/zh/timeline.js

is_component $COMPONENTS/similetimeline/similetimeline.inc.php

print_results
