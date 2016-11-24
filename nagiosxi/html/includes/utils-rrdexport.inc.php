<?php
//
// Copyright (c) 2016-2016 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 01/20/2016
// $Id$

/**
* function to get exported rrd data
* @param string $host hostname to pull from
* @param string $service service of hostname to pull from, will default to _HOST_
* @param string $return_type optional DEFAULT=EXPORT_RRD_XML, otherwise EXPORT_RRD_JSON | EXPORT_RRD_CSV | EXPORT_RRD_ARRAY
* @param int $start optional DEFAULT=-24hrs from now, otherwise the start time, in seconds since epoch
* @param int $end optional DEFAULT=now, otherwise the end time, in seconds since epoch
* @param int $step optional DEFAULT=300sec step of data to return from rrd
* @param array $columns_to_display optional DEFAULT=ALL, otherwise the array of strings that correspond to datasources in the rrd file
*
* @return mixed returns either a string containing final csv,xml or json, or an array
*/
function get_rrd_data($host, $service = "", $return_type = EXPORT_RRD_XML, $start = "", $end = "", $step = "", $columns_to_display = "") {

	$rrd_file = get_rrd_file_name_from_host_and_service($host, $service);

	// first check if file and corresponding xml metadata file exists
	$xml_file_info = pathinfo($rrd_file);
	$xml_file = $xml_file_info['dirname'] . '/' . $xml_file_info['filename'] . '.xml';
	if (!file_exists($rrd_file) || !file_exists($xml_file))
		return FALSE;

	// get all valid columns from metadata and get their ds/label information
	$possible_columns_to_display = get_rrd_metadeta_datasource_as_array($xml_file);

	// then check if columns specified in function arguments are valid
	$valid_columns_to_display = array();
	if (!empty($columns_to_display)) {

		// convert to array if not already
		if (!is_array($columns_to_display))
			$columns_to_display = array($columns_to_display);

		// cycle through the array of column information and try and find a match
		// if the current column is found in the known possibles, add this to the valid column array
		foreach ($columns_to_display as $current_column) {
			if ($key = @array_search($current_column, $possible_columns_to_display)) {
				$valid_columns_to_display[$key] = $possible_columns_to_display[$key];
			}
		}

	} else {
		$valid_columns_to_display = $possible_columns_to_display;
	}

	// build the command to export the data
	$cmd = "rrdtool xport ";

	// error checking for input based on start/end needs to happen somewhere else, this function can *actually* accept anything that rrdtool xport can... (read the manpage)
	if (!empty($start))
		$cmd .= "--start $start ";
	if (!empty($end))
		$cmd .= "--end $end ";
	if (!empty($step))
		$cmd .= "--step $step ";

	$vname = 0;
	foreach ($valid_columns_to_display as $ds => $label) {
		$cmd .= "DEF:$vname=$rrd_file:$ds:AVERAGE ";
		$cmd .= "XPORT:$vname:\"$label\" ";
		$vname++;
	}

	// run the rrdtool xport command, get the output and return
	exec($cmd, $lined_output, $return_output);
	if ($return_output !== 0)
		return FALSE;

	$xml_output = implode("", $lined_output);

	// check return type and return accordingly
	switch ($return_type) {
		case EXPORT_RRD_ARRAY:
			return json_decode(convert_xml_string_to_json($xml_output), true);

		case EXPORT_RRD_CSV:
			return convert_xml_string_to_csv($xml_output);

		case EXPORT_RRD_JSON:
			return convert_xml_string_to_json($xml_output);

		case EXPORT_RRD_XML:
		default:
			return $xml_output;
	}
}


/**
* convert_xml_string_to_json
* 
* @param string $xml_string the xml string to perform the conversion on
*
* @return string json encoded data
*/
function convert_xml_string_to_json($xml_string) {

	$xml = simplexml_load_string($xml_string, "SimpleXMLElement", LIBXML_NOCDATA);
	return json_encode($xml);
}


/**
* get_rrd_metadata_datasource_as_array
* function to load xml (rrd metadata) and return specific key/value pairs
*
* @param string xml_file
*
* @return array array of datasource in the form of array[DS] = LABEL (where DS and LABEL are pulled from <DATASOURCE> tags in the specified xml file)
*/
function get_rrd_metadeta_datasource_as_array($xml_file) {

	$rrd_metadata_xml = simplexml_load_file($xml_file);
	$output_array = array();

	foreach($rrd_metadata_xml->DATASOURCE as $datasource)
		$output_array[(string) $datasource->DS] = (string) $datasource->LABEL;

	if (empty($output_array))
		return FALSE;
	else
		return $output_array;
}


/**
* convert_xml_string_to_csv
* function to take an xml string, and parse that data into a string full of csv data
*
* @param string $xml_string the xml data in string format
* @param string $string_escape_character optional the string/character used to delimit strings in the csv, defaults to single quote (')
* @param bool $csv_header optional to generate a header row or not? default is true
*
* @return string string containing csv data
*/
function convert_xml_string_to_csv($xml_string, $string_escape_character = "'", $csv_header = true) {

	$csv_output = "";
	$xml = simplexml_load_string($xml_string);
	$ec = $string_escape_character; // this just looks better inside of the strings

	if ($csv_header) {
		$csv_output .= "{$ec}timestamp{$ec}";
		foreach($xml->meta->legend->entry as $key => $value) {
			$csv_output .= ",{$ec}$value{$ec}";
		}
		$csv_output .= "\n";
	}

	foreach($xml->data->row as $row) {
		$csv_output .= $ec . $row->t . $ec;
		foreach($row->v as $key => $value) {
			$csv_output .= ",{$ec}$value{$ec}";
		}
		$csv_output .= "\n";
	}

	return $csv_output;
}


/**
* get file rrd file name from host service
*
* @param string $host hostname
* @param string $service optional service name, DEFAULT = "_HOST_"
*
* @return string string containing full path to rrd file
*/
function get_rrd_file_name_from_host_and_service($host, $service = "") {

	global $cfg;
	$perfdata = $cfg['component_info']['pnp']['perfdata_dir'];

	$host = pnp_convert_object_name($host);
	$service = pnp_convert_object_name($service == "" ? "_HOST_" : $service);

	if (pnp_chart_exists($host, $service)) {
		return str_replace(" ", "_", "$perfdata/$host/$service.rrd");
	} else {
		return false;
	}
}