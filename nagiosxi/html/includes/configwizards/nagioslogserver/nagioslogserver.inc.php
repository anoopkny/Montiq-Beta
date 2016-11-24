<?php
//
// Nagios Log Server Config Wizard
// Copyright (c) 2014-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../configwizardhelper.inc.php');

define("WIZARD_NAME", 'nagioslogserver');
define("WIZARD_ICON", WIZARD_NAME.'.png');

nagioslogserver_configwizard_init();

function nagioslogserver_configwizard_init()
{
    register_configwizard(WIZARD_NAME, array(
        CONFIGWIZARD_NAME => WIZARD_NAME,
        CONFIGWIZARD_VERSION => '1.0.5',
        CONFIGWIZARD_TYPE => CONFIGWIZARD_TYPE_MONITORING,
        CONFIGWIZARD_DESCRIPTION => _('Monitor a Nagios Log Server query.'),
        CONFIGWIZARD_DISPLAYTITLE => 'Nagios Log Server',
        CONFIGWIZARD_FUNCTION => WIZARD_NAME.'_configwizard_func',
        CONFIGWIZARD_PREVIEWIMAGE => WIZARD_ICON,
        CONFIGWIZARD_FILTER_GROUPS => array('nagios'),
        CONFIGWIZARD_REQUIRES_VERSION => 500
    ));
}


/**
 * Build a Log Server query URL.
 *
 * @param string $base Base URL to the Log Server Web UI (e.g., 'http://host.domain/nagioslogserver/').
 * @param string $path API controller/action path (e.g., 'check/get_queries' or 'system/cpu_status').
 * @param string $token Log Server API access token.
 *
 * @return string The assembled API URL.
 */
function build_api_url($base, $path, $token)
{
    if (substr($base, -1) != '/') $base .= '/'; // Make base url end in a slash.
    return "{$base}index.php/api/$path?token=$token";
}


/**
 * @param string $mode
 * @param null   $inargs
 * @param        $outargs
 * @param        $result
 *
 * @return string
 */
function nagioslogserver_configwizard_func($mode='', $inargs=null, &$outargs, &$result)
{
    $result = 0; // Stage result status code: 0 okay; 1 ERROR!!! BAAAAHHHHHHHHHHD!!!!!!!!!!...
    $output = ''; // HTML output string.

    // Initialize output args - pass back the same data we got.
    $outargs[CONFIGWIZARD_PASSBACK_DATA] = $inargs;

    switch ($mode) {
        case CONFIGWIZARD_MODE_GETSTAGE1HTML:
            $address = grab_array_var($inargs, 'address');
            $url = grab_array_var($inargs, 'url');
            $key = grab_array_var($inargs, 'key');

            $address = nagiosccm_replace_user_macros($address);
            $url = nagiosccm_replace_user_macros($url);

            $output = '
            <h5 class="ul">' . _('Host Information') . '</h5>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt"><label>' . _('Host Address') . ':</label></td>
                    <td style="padding-bottom: 8px;">
                        <input type="text" size="20" name="address" id="address" value="' . encode_form_val($address) . '" class="textfield form-control">
                        <div class="subtext">' . _('The IP address or FQDN of the host associated with the Log Server query you\'d like to monitor.') . '</div>
                    </td>
                </tr>
            </table>
            <h5 class="ul">' . _('Log Server Information') . '</h5>
            <p>' . _('Specify the settings to access your Log Server.') . '</p>
            <table class="table table-condensed table-no-border table-auto-width table-padded">
                <tr>
                    <td class="vt"><label>' . _('URL') . ':</label></td>
                    <td>
                        <input type="text" size="40" name="url" id="url" value="' . encode_form_val($url) . '" class="textfield form-control">
                        <div class="subtext">' . _('The URL used to access the Nagios Log Server web interface e.g. (http://logserver.example.com/nagioslogserver/).') . '</div>
                    </td>
                </tr>
                <tr>
                    <td class="vt"><label>' . _('API Key') . ':</label></td>
                    <td>
                        <input type="text" size="20" name="key" id="key" value="' . encode_form_val($key) . '" class="textfield form-control">
                        <div class="subtext">' . _('Authentication token used to access the Log Server API.') . '</div>
                    </td>
                </tr>
            </table>';
            break;


        case CONFIGWIZARD_MODE_VALIDATESTAGE1DATA:
            // Collect and validate submitted values.
            $address = grab_array_var($inargs, 'address');
            $url = grab_array_var($inargs, 'url');
            $key = grab_array_var($inargs, 'key');

            $errors = 0;
            $errmsg = array();

            if (empty($address)) {
                $errmsg[$errors++] = _('No host address specified.');
            }
            if (empty($url)) {
                $errmsg[$errors++] = _('No Log Server URL specified.');
            }
            if (empty($key)) {
                $errmsg[$errors++] = _('No API key specified.');
            }

            if (!$errors) {
                // CPU status as a test URL is shorter and simpler than subsystem status.
                $api_url = build_api_url($url, 'system/cpu_status', $key);

                // We should be able to get a response with the creds we have.
                $json = @file_get_contents($api_url, 0, null, null);
                if (empty($json)) {
                    $errmsg[$errors++] = _('Unable to contact server at ') . $url . '.';
                } else {
                    $data = json_decode($json, true);
                    if (!$data || !is_array($data)) {
                        $errmsg[$errors++] = _('Server returned invalid output. (Is this a Nagios Log Server?)');
                    } else if (
                        grab_array_var($data, 'error') &&
                        grab_array_var($data, 'type') == 'authentication'
                    ) {
                        $errmsg[$errors++] = _('Bad API key. Message from server is') . ': "' . grab_array_var($data, 'message') . '"';
                    } else if (!array_key_exists('cpu_usage', $data)) {
                        $errmsg[$errors++] = _('Server didn\'t return expected output. (Is this a Nagios Log Server?)');
                    }
                }
            }

            if ($errors) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;


        case CONFIGWIZARD_MODE_GETSTAGE2HTML:

            // Collect our submitted values.
            $address = grab_array_var($inargs, 'address');
            $ha = @gethostbyaddr($address);
            $hostname = grab_array_var($inargs, 'hostname', $ha ? $ha : $address);
            $hostname = nagiosccm_replace_user_macros($hostname);
            $url = grab_array_var($inargs, 'url');
            $key = grab_array_var($inargs, 'key');

            $output = '';
            $errors = 0;
            $errmsg = array();

            // Query the Log Server for saved queries.
            $api_url = build_api_url($url, 'check/get_queries', $key);
            $data = array();
            $json = @file_get_contents($api_url, 0, null, null);
            if (empty($json)) {
                $errmsg[$errors++] = _('Unable to contact server at ') . $url . '.';
            } else {
                $data = json_decode($json, true);
                if (!$data || !is_array($data)) {
                    $errmsg[$errors++] = _('Server returned invalid output. (Is this a Nagios Log Server?)');
                    $data = null;
                } else if (
                    grab_array_var($data, 'error') &&
                    grab_array_var($data, 'type') == 'authentication'
                ) {
                    $errmsg[$errors++] = _('Bad API key. Message from server is') . ': "' . grab_array_var($data, 'message') . '"';
                }
            }

            $services = '';
            $serviceargs = '';

            // Use encoded data if user came back from future screen.
            $services_serial = grab_array_var($inargs, 'services_serial');
            if ($services_serial) {
                $services = unserialize(base64_decode($services_serial));
            }
            $serviceargs_serial = grab_array_var($inargs, 'serviceargs_serial');
            if ($serviceargs_serial) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            // If no serialized data, use current request data if available.
            if (!$services)
                $services = grab_array_var($inargs, 'services', array());
            if (!$serviceargs)
                $serviceargs = grab_array_var($inargs, 'serviceargs', array());

            // Initialize array variables if missing.
            if (!array_key_exists('id', $services)) $services['id'] = array();
            if (!array_key_exists('id', $serviceargs)) $serviceargs['id'] = array();
            for ($x = 0; $x < 3; $x++) {
                if (!array_key_exists($x, $services['id']))
                    $services['id'][$x] = '';

                if (!array_key_exists($x, $serviceargs['id'])) {
                    $serviceargs['id'][$x] = array(
                        'name' => '',
                        'id' => '',
                        'minutes' => '',
                        'warning' => '',
                        'critical' => '',
                    );
                }
            }

            if (!array_key_exists('query', $services)) $services['query'] = array();
            if (!array_key_exists('query', $serviceargs)) $serviceargs['query'] = array();
            for ($x = 0; $x < 3; $x++) {
                if (!array_key_exists($x, $services['query']))
                    $services['query'][$x] = '';

                if (!array_key_exists($x, $serviceargs['query'])) {
                    $serviceargs['query'][$x] = array(
                        'name' => '',
                        'type' => '',
                        'query' => '',
                        'minutes' => '',
                        'warning' => '',
                        'critical' => '',
                    );
                }
            }

            $output .= '
<input type="hidden" name="address" value="' . encode_form_val($address) . '">
<input type="hidden" name="url" value="' . encode_form_val($url) . '">
<input type="hidden" name="key" value="' . encode_form_val($key) . '">

<h5 class="ul">' . _('Host Information') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('Host Address') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="address" id="address" value="' . encode_form_val($address) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td class="vt">
            <label>' . _('Host Name') . ':</label>
        </td>
        <td>
            <input type="text" size="20" name="hostname" id="hostname" value="' . encode_form_val($hostname) . '" class="textfield form-control">
            <div class="subtext">' . _('The name you\'d like to have associated with this host.') . '</div>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Log Server Settings') . '</h5>
<table class="table table-condensed table-no-border table-auto-width">
    <tr>
        <td>
            <label>' . _('URL') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="url" id="url" value="' . encode_form_val($url) . '" class="textfield form-control" disabled>
        </td>
    </tr>
    <tr>
        <td>
            <label>' . _('API Key') . ':</label>
        </td>
        <td>
            <input type="text" size="40" name="key" id="key" value="' . encode_form_val($key) . '" class="textfield form-control" disabled>
        </td>
    </tr>
</table>

<h5 class="ul">' . _('Saved Queries') . '</h5>
<p>' . _('Monitor results of queries saved in Log Server.') . '</p>';

            if ($errors) {
                $errmsg = implode('</li><li>', $errmsg);
                $output .= "<div class='message'><ul class='errorMessage'><li>$errmsg</li></ul></div>";
            } else if (!$data) {
                $errmsg = _('No saved queries were found on this Log Server.');
                $output .= "<div class='message'><ul class='actionMessage'><li>$errmsg</li></ul></div>";
            } else {
                $output .= '
                <div class="message" style="margin: -10px 0 0 0;"><ul class="actionMessage">' . _('Queries will be copied from Log Server and saved in your XI configuration. Later changes to the queries in Log Server will not affect the queries in XI.') . '<li></li></ul></div>
                <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin: 0;">
                <tr>
                    <th></th>' .
                    '<th>' . _('Display Name') . '</th>' .
                    '<th>' . _('Saved Query') . '</th>' .
                    '<th>' . _('Over Last Minutes') . ' <i class="fa fa-question-circle fa-14 pop" title="'._('Over Last Minutes').'" data-content="'._('The duration is how long the check should look back in time for. Standard 5 minute checks may want to just use 5 for 5 minutes.').'" data-placement="top"></i></th>' .
                    '<th><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> '._('Warning').'</th>' .
                    '<th><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> '._('Critical').'</th>' .
                '</tr>';

                for ($x = 0; $x < count($serviceargs['id']); $x++) {
                    $minutes = encode_form_val($serviceargs['id'][$x]['minutes']);
                    $warning = encode_form_val($serviceargs['id'][$x]['warning']);
                    $critical = encode_form_val($serviceargs['id'][$x]['critical']);
                    $output .= '<tr>
                    <td><input type="checkbox" class="checkbox" name="services[id][' . $x . ']" ' . (isset($services['id'][$x]) ? is_checked($services['id'][$x], 'on') : '') . '></td>
                    <td><input type="text" size="20" name="serviceargs[id][' . $x . '][name]" value="' . encode_form_val($serviceargs['id'][$x]['name']) . '" class="textfield form-control"></td>

                    <td><select name="serviceargs[id][' . $x . '][id]" class="form-control">';
                    foreach ($data as $d) {
                        $id = encode_form_val($d['id']);
                        $sel = is_selected($serviceargs['id'][$x]['id'], $id);
                        $name = encode_form_val($d['name']);
                        $output .= "<option value='$id' $sel >$name</option>";
                    }
                    $output .= '</select></td>

                    <td><input type="text" size="5" name="serviceargs[id][' . $x . '][minutes]" value="' . $minutes . '" class="textfield form-control"></td>
                    <td><input type="text" size="5" name="serviceargs[id][' . $x . '][warning]" value="' . $warning . '" class="textfield form-control"></td>
                    <td><input type="text" size="5" name="serviceargs[id][' . $x . '][critical]" value="' . $critical . '" class="textfield form-control"></td>
                    </tr>';
                }
                $output .= '</table><div style="height: 20px;"></div>';
            }

            $output .= '
            <h5 class="ul">' . _('Text Queries') . '</h5>
            <p>' . _('Monitor results of a Lucene or JSON query entered as text.') . '<br><a href="http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#query-string-syntax" target="_blank">' . _('About the Lucene query syntax') . '</a>.</p>
            <table class="adddeleterow table table-condensed table-no-border table-auto-width" style="margin: 0;">
                <tr>
                    <th></th>' .
                    '<th>' . _('Display Name') . '</th>' .
                    '<th>' . _('Type') . '</th>' .
                    '<th>' . _('Query') . '</th>' .
                    '<th>' . _('Over Last Minutes') . ' <i class="fa fa-question-circle fa-14 pop" title="'._('Over Last Minutes').'" data-content="'._('The duration is how long the check should look back in time for. Standard 5 minute checks may want to just use 5 for 5 minutes.').'" data-placement="top"></i></th>' .
                    '<th><img src="'.theme_image('error.png').'" class="tt-bind" title="'._('Warning Threshold').'"> '._('Warning').'</th>' .
                    '<th><img src="'.theme_image('critical_small.png').'" class="tt-bind" title="'._('Critical Threshold').'"> '._('Critical').'</th>' .
                '</tr>';

            for ($x = 0; $x < count($serviceargs['query']); $x++) {
                $output .= '<tr>
                <td><input type="checkbox" class="checkbox" name="services[query][' . $x . ']" ' . (isset($services['query'][$x]) ? is_checked($services['query'][$x], 'on') : '') . '></td>
                <td><input type="text" size="20" name="serviceargs[query][' . $x . '][name]" value="' . encode_form_val($serviceargs['query'][$x]['name']) . '" class="textfield form-control"></td>
                <td><select name="serviceargs[query][' . $x . '][type]" class="form-control">
                    <option value="text" ' . is_selected($serviceargs['query'][$x]['type'], 'text') . '>' . _('Lucene') . '</option>
                    <option value="json" ' . is_selected($serviceargs['query'][$x]['type'], 'json') . '>' . _('JSON') . '</option>
                </select></td>
                <td><input type="text" size="30" name="serviceargs[query][' . $x . '][query]" value="' . encode_form_val($serviceargs['query'][$x]['query']) . '" class="textfield form-control"></td>
                <td><input type="text" size="5" name="serviceargs[query][' . $x . '][minutes]" value="' . encode_form_val($serviceargs['query'][$x]['minutes']) . '" class="textfield form-control"></td>
                <td><input type="text" size="5" name="serviceargs[query][' . $x . '][warning]" value="' . encode_form_val($serviceargs['query'][$x]['warning']) . '" class="textfield form-control"></td>
                <td><input type="text" size="5" name="serviceargs[query][' . $x . '][critical]" value="' . encode_form_val($serviceargs['query'][$x]['critical']) . '" class="textfield form-control"></td>
                </tr>';
            }

            $output .= '</table><div style="height: 20px;"></div>';
            break;


        case CONFIGWIZARD_MODE_VALIDATESTAGE2DATA:
            $address = grab_array_var($inargs, 'address');
            $hostname = grab_array_var($inargs, 'hostname');
            $url = grab_array_var($inargs, 'url');
            $key = grab_array_var($inargs, 'key');

            $services = '';
            $serviceargs = '';

            // Use encoded data if user came back from future screen.
            $services_serial = grab_array_var($inargs, 'services_serial');
            if ($services_serial) {
                $services = unserialize(base64_decode($services_serial));
            }
            $serviceargs_serial = grab_array_var($inargs, 'serviceargs_serial');
            if ($serviceargs_serial) {
                $serviceargs = unserialize(base64_decode($serviceargs_serial));
            }

            // If no serialized data, use current request data if available.
            if (!$services)
                $services = grab_array_var($inargs, 'services', array());
            if (!$serviceargs)
                $serviceargs = grab_array_var($inargs, 'serviceargs', array());

            // Now validate the data.
            $errors = 0;
            $errmsg = array();

            if (!is_valid_host_name($hostname)) {
                $errmsg[$errors++] = _('Invalid host name.');
            }

            $have_id_queries = array_key_exists('id', $services) && count($services['id']);
            $have_text_queries = array_key_exists('query', $services) && count($services['query']);
            if (!$have_id_queries && !$have_text_queries) {
                $errmsg[$errors++] = _('You have not specified any queries to monitor.');
            }

            if ($have_id_queries) foreach ($services['id'] as $i => $v) {
                // We need all data for selected query ID rows.
                if (!$serviceargs['id'][$i]['name'])
                    $errmsg[$errors++] = _('Missing display name.');
                if (!$serviceargs['id'][$i]['id'])
                    $errmsg[$errors++] = _('Missing query ID.');
                $minutes = $serviceargs['id'][$i]['minutes'];
                if (!$minutes)
                    $errmsg[$errors++] = _('Minutes must be greater than zero.');
                else if (!is_numeric($minutes) || $minutes != intval($minutes))
                    $errmsg[$errors++] = _('Minutes must be an integer.');
                if (!$serviceargs['id'][$i]['warning'])
                    $errmsg[$errors++] = _('Missing warning range.');
                if (!$serviceargs['id'][$i]['critical'])
                    $errmsg[$errors++] = _('Missing critical range.');
            }

            if ($have_text_queries) foreach ($services['query'] as $i => $v) {
                // Validate non-empty common data first, then more intelligent
                // type-specific checks later.
                if (!$serviceargs['query'][$i]['name'])
                    $errmsg[$errors++] = _('Missing display name.');
                $query = $serviceargs['query'][$i]['query'];
                if (!$query)
                    $errmsg[$errors++] = _('Missing query.');
                $minutes = $serviceargs['query'][$i]['minutes'];
                if (!$minutes)
                    $errmsg[$errors++] = _('Minutes must be greater than zero.');
                else if (!is_numeric($minutes) || $minutes != intval($minutes))
                    $errmsg[$errors++] = _('Minutes must be an integer.');
                if (!$serviceargs['query'][$i]['warning'])
                    $errmsg[$errors++] = _('Missing warning range.');
                if (!$serviceargs['query'][$i]['critical'])
                    $errmsg[$errors++] = _('Missing critical range.');

                $type = $serviceargs['query'][$i]['type'];
                if (!$type) {
                    $errmsg[$errors++] = _('Missing query type.');
                } else if ($query) switch ($type) {
                    case 'text':
                        // Non-empty text: we'll escape shell characters when
                        // building the plugin command later, and the plugin
                        // URL encodes the query.
                        break;
                    case 'json':
                        // Validate that the JSON parses to an object.
                        if (!is_object(json_decode($query)))
                            $errmsg[$errors++] = _('Query JSON is invalid.');
                        break;
                    default:
                        $errmsg[$errors++] = _('Invalid query type') . ": '$type'";
                        break;
                }
            }

            if ($errors > 0) {
                $outargs[CONFIGWIZARD_ERROR_MESSAGES] = $errmsg;
                $result = 1;
            }
            break;


        case CONFIGWIZARD_MODE_GETSTAGE3HTML:
            $address = encode_form_val(grab_array_var($inargs, 'address'));
            $hostname = encode_form_val(grab_array_var($inargs, 'hostname'));
            $url = encode_form_val(grab_array_var($inargs, 'url'));
            $key = encode_form_val(grab_array_var($inargs, 'key'));

            $services = serialize(grab_array_var($inargs, 'services'));
            $services_serial = grab_array_var($inargs, 'services_serial', base64_encode($services));

            $serviceargs = serialize(grab_array_var($inargs, 'serviceargs'));
            $serviceargs_serial = grab_array_var($inargs, 'serviceargs_serial', base64_encode($serviceargs));

            $output = '
            <input type="hidden" name="address" value="' . $address . '">
            <input type="hidden" name="hostname" value="' . $hostname . '">
            <input type="hidden" name="url" value="' . $url . '">
            <input type="hidden" name="key" value="' . $key . '">
            <input type="hidden" name="services_serial" value="' . $services_serial . '">
            <input type="hidden" name="serviceargs_serial" value="' . $serviceargs_serial . '">
            <!-- SERVICES=' . $services . PHP_EOL . 'SERVICEARGS=' . $serviceargs . '-->';
            break;


        case CONFIGWIZARD_MODE_VALIDATESTAGE3DATA:
            break;


        case CONFIGWIZARD_MODE_GETFINALSTAGEHTML:
            break;


        case CONFIGWIZARD_MODE_GETOBJECTS:
            $address = grab_array_var($inargs, 'address');
            $hostname = grab_array_var($inargs, 'hostname');
            $url = grab_array_var($inargs, 'url');
            $key = grab_array_var($inargs, 'key');

            $services_serial = grab_array_var($inargs, 'services_serial');
            $serviceargs_serial = grab_array_var($inargs, 'serviceargs_serial');

            $services = unserialize(base64_decode($services_serial));
            $serviceargs = unserialize(base64_decode($serviceargs_serial));

            // save data for later use in re-entrance
            save_configwizard_object_meta(WIZARD_NAME, $hostname, '', array(
                'hostname' => $hostname,
                'address' => $address,
                'url' => $url,
                'key' => $key,
                'services' => $services,
                'serivceargs' => $serviceargs,
            ));

            $objs = array();

            if (!host_exists($hostname)) {
                $objs[] = array(
                    'type' => OBJECTTYPE_HOST,
                    'use' => 'xiwizard_nagioslogserver_host',
                    'host_name' => $hostname,
                    'address' => $address,
                    'icon_image' => WIZARD_ICON,
                    'statusmap_image' => WIZARD_ICON,
                    '_xiwizard' => WIZARD_NAME,
                );
            }

            // The base API URL for loading queries.
            $api_url = build_api_url($url, 'check/get_queries', $key) . '&id=';

            // Escape and build the common command arguments.
            $url = escapeshellarg($url);
            $key = escapeshellarg($key);
            $cmdargs_base = "--url=$url --apikey=$key";

            // Figure out the 'services' we should monitor.
            foreach ($services as $service_type => $selected_services) {
                foreach (array_keys($selected_services) as $i) {
                    $name = $serviceargs[$service_type][$i]['name'];
                    $mins = escapeshellarg($serviceargs[$service_type][$i]['minutes']);
                    $warn = escapeshellarg($serviceargs[$service_type][$i]['warning']);
                    $crit = escapeshellarg($serviceargs[$service_type][$i]['critical']);

                    $cmdargs = "$cmdargs_base --minutes=$mins --warn=$warn --crit=$crit";

                    switch ($service_type) {
                        case 'id':
                            $id = $serviceargs['id'][$i]['id'];
                            $result = @load_url($api_url.$id, array("method" => "post"));
                            $result = json_decode($result, true);
                            $query = is_array($result) && isset($result['raw'])
                                ? $result['raw'] : ' "Failed to load query"';
                            $cmdargs .= ' --query=' . escapeshellarg($query);
                            // If querying by ID we would add this instead:
                            //$cmdargs .= ' --id=' . $serviceargs['id'][$i]['id'];
                            break;

                        case 'query':
                            $query_type = $serviceargs['query'][$i]['type'];
                            $query_text = $serviceargs['query'][$i]['query'];
                            setlocale(LC_CTYPE, "en_US.UTF-8");
                            $query_text = escapeshellcmd($query_text);
                            $query_text = addslashes($query_text);

                            switch ($query_type) {
                                case 'text':
                                    $query_type = 'string';
                                    break;
                                case 'json':
                                    $query_type = 'query';
                                    break;
                                default:
                                    // Skipping bad values for now, should show a message somewhere...
                                    continue;
                            }

                            $cmdargs .= " --$query_type='$query_text'";
                            break;

                        default:
                            // Skipping bad values for now, should show a message somewhere...
                            continue;
                    }

                    $objs[] = array(
                        'type' => OBJECTTYPE_SERVICE,
                        'host_name' => $hostname,
                        'service_description' => $name,
                        'use' => 'xiwizard_nagioslogserver_service',
                        'check_command' => 'check_xi_service_nagioslogserver!' . $cmdargs,
                        '_xiwizard' => WIZARD_NAME,
                    );
                }
            }

            // Return the object definitions to the wizard.
            $outargs[CONFIGWIZARD_NAGIOS_OBJECTS] = $objs;
            break;


        default:
            break;
    }

    return $output;
}
