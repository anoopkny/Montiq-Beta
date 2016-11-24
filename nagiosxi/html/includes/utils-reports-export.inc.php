<?php
//
// Report Exporting Utilities
// Copyright (c) 2015 Nagios Enterprises, LLC. All rights reserved.
//

define('EXPORT_PDF', 'pdf');
define('EXPORT_JPG', 'jpg');
define('EXPORT_PORTRAIT', 0);
define('EXPORT_LANDSCAPE', 1);

/**
 * Export a report as a PDF file or JPG image
 *
 * @param string $reportname    The name of the report file (i.e. 'availability')
 * @param constant $type        Msut be EXPORT_PDF (default) or EXPORT_JPG
 * @param constant $orientation Can be either EXPORT_PORTRAIT (default) or EXPORT_LANDSCAPE
 *
 * @return null
 */
function export_report($reportname, $type = EXPORT_PDF, $orientation = EXPORT_PORTRAIT)
{
    global $cfg;
    $username = $_SESSION['username'];
    $language = $_SESSION['language'];
    $backend_ticket = get_user_attr(0, 'backend_ticket');

    // tps #7480. when not using high charts, the page needs to shrink to fit the page -bh
    $smart_shrinking_action = (get_option('perfdata_theme') == 0 ? "--enable-smart-shrinking" : "--disable-smart-shrinking");

    // Do specifics for each type of report
    switch ($type)
    {
        case EXPORT_PDF:
            $bin = 'wkhtmltopdf';
            $content_type = 'application/pdf';
            $opts = ' --lowquality ' . $smart_shrinking_action . ' --no-outline --footer-spacing 3 --margin-bottom 15mm --footer-font-size 9 --footer-right "Page [page] of [toPage]" --footer-left "' . get_datetime_string(time(), DT_SHORT_DATE_TIME, DF_AUTO, "null") . '"';
            if ($orientation == EXPORT_LANDSCAPE) {
                $opts .= ' -O landscape';
            }
            $ext = '';
            break;
        case EXPORT_JPG:
            $bin = 'wkhtmltoimage';
            $content_type = 'application/jpg';
            $opts = '';
            $ext = 'jpg';
            break;
        default:
            die(_('ERROR: Could not export report as ') . $type . '. ' . _('This type is not defined.'));
            break;
    }

    // Grab the current URL parts
    $query = array();
    $url_parts = parse_url($_SERVER['REQUEST_URI']);
    $url_parts = explode('&', $url_parts['query']);
    foreach ($url_parts as $p) {
        $part = explode('=', $p);
        if (!empty($part[1]) && $part[0] != 'mode') {
            $query[$part[0]] = urldecode($part[1]);
        }
    }

    // Add in some required components to the query
    $query['username'] = $username;
    $query['ticket'] = $backend_ticket;
    $query['locale'] = $language;
    $query['records'] = 100000;
    $query['mode'] = 'getreport';
    $query['export'] = 1;

    $report_location = 'reports';

    // some report specific changes
    if ($reportname == 'auditlog') {
        $report_location = 'admin';
    } else if ($reportname == 'capacityplanning') {
        $report_location = 'includes/components/capacityplanning';
    } else if ($reportname == 'execsummary') {
        $query['records'] = 10;
    }

    // Start creating the internal report url we will be calling and make a tempfile name
    $url = get_internal_url() . $report_location . '/' . urlencode($reportname) . '.php?' . http_build_query($query);

    $tempfile = get_tmp_dir() . "/exportreport-" . $username . "-" . uniqid();
    if (!empty($ext)) { $tempfile .= '.'.$ext; }
    $cmd = '/usr/bin/' . $bin . "{$opts} '{$url}' '{$tempfile}' 2>&1";
    @exec($cmd);
    
    if (!file_exists($tempfile)) {
        echo '<div style="margin: 7% auto; max-width: 80%; text-align: center; font-family: verdana, arial; font-size: 1rem; word-wrap: break-word;">';
        echo '<div><strong>' . _('Failed to create ') . '<span style="text-transform: uppercase;">' . $type . '</span></strong></div>';
        echo '<div style="margin: 10px 0 30px 0;">' . _('Verify that your Nagios XI server can connect to the URL') . ':</div>';
        echo '<div style="font-size: 0.7rem;">' . $url . '</div>';
        echo '</div>';
        die();
    } else {
        header('Content-type: ' . $content_type);
        header('Content-Disposition: inline; filename="' . $reportname . '.' . $type . '"');
        readfile($tempfile);
        unlink($tempfile);
    }
}