#!/bin/bash
 
#xicore component sanity check

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

do_these_files_exist $COMPONENTS/xicore/xicore.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-comments.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-hoststatus.inc.php \
	$COMPONENTS/xicore/ajaxhelpers.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-misc.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-monitoringengine.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-perfdata.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-servicestatus.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-status.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-sysstat.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-tac.inc.php \
	$COMPONENTS/xicore/ajaxhelpers-tasks.inc.php \
	$COMPONENTS/xicore/dashlets-comments.inc.php \
	$COMPONENTS/xicore/dashlets.inc.php \
	$COMPONENTS/xicore/dashlets-misc.inc.php \
	$COMPONENTS/xicore/dashlets-monitoringengine.inc.php \
	$COMPONENTS/xicore/dashlets-perfdata.inc.php \
	$COMPONENTS/xicore/dashlets-status.inc.php \
	$COMPONENTS/xicore/dashlets-sysstat.inc.php \
	$COMPONENTS/xicore/dashlets-tac.inc.php \
	$COMPONENTS/xicore/dashlets-tasks.inc.php \
	$COMPONENTS/xicore/downtime.php \
	$COMPONENTS/xicore/recurringdowntime.php \
	$COMPONENTS/xicore/status-object-detail.inc.php \
	$COMPONENTS/xicore/status.php \
	$COMPONENTS/xicore/status-utils.inc.php \
	$COMPONENTS/xicore/tac.php \
	$COMPONENTS/xicore/images/dashlets \
	$COMPONENTS/xicore/images/legacyreports \
	$COMPONENTS/xicore/images/subcomponents \
	$COMPONENTS/xicore/images/dashlets/admin_tasks_preview.png \
	$COMPONENTS/xicore/images/dashlets/available_updates_preview.png \
	$COMPONENTS/xicore/images/dashlets/component_status_preview.png \
	$COMPONENTS/xicore/images/dashlets/eventqueue_chart_preview.png \
	$COMPONENTS/xicore/images/dashlets/getting_started_preview.png \
	$COMPONENTS/xicore/images/dashlets/hostgroup_status_summary.png \
	$COMPONENTS/xicore/images/dashlets/host_status_summary.png \
	$COMPONENTS/xicore/images/dashlets/monitoring_perf_preview.png \
	$COMPONENTS/xicore/images/dashlets/monitoring_proc_preview.png \
	$COMPONENTS/xicore/images/dashlets/monitoring_stats_preview.png \
	$COMPONENTS/xicore/images/dashlets/network_outages_preview.png \
	$COMPONENTS/xicore/images/dashlets/perfdata_chart_preview.png \
	$COMPONENTS/xicore/images/dashlets/pspbrwse.jbf \
	$COMPONENTS/xicore/images/dashlets/server_stats_preview.png \
	$COMPONENTS/xicore/images/dashlets/servicegroup_status_summary.png \
	$COMPONENTS/xicore/images/dashlets/service_status_summary.png \
	$COMPONENTS/xicore/images/dashlets/xi_news_feed_preview.png \
	$COMPONENTS/xicore/images/legacyreports/avail.png \
	$COMPONENTS/xicore/images/legacyreports/histogram.png \
	$COMPONENTS/xicore/images/legacyreports/history.png \
	$COMPONENTS/xicore/images/legacyreports/notifications.png \
	$COMPONENTS/xicore/images/legacyreports/summary.png \
	$COMPONENTS/xicore/images/legacyreports/trends.png \
	$COMPONENTS/xicore/images/subcomponents/nagioscorecfg.png \
	$COMPONENTS/xicore/images/subcomponents/nagioscore.png \
	$COMPONENTS/xicore/images/subcomponents/nagiosql.png

is_component $COMPONENTS/xicore/xicore.inc.php

print_results
