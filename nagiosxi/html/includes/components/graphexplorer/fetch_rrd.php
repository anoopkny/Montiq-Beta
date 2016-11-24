<?php  //fetch_rrd.inc.php  

/*function: fetch_rrd($args)
*
*	@param array $args -> *host, service, start, end  (only hostname is required);  
*	@returns: array $data['sets'] -> array of rrd data in a csv STRING formatted for highcharts,
*					$data['start'] -> starting time of the data
*					$data['increment'] -> timespan between data points 
*					$data['count']     ->total number of data points for graph 
*/
/**
 * @param $args
 *
 * @return array
 */function fetch_rrd($args)
{
	$host = isset($args['host']) ? $args['host'] : die("No host specified"); // Need a hostname at minimum
	$service = $args['service'];  //set to _HOST_ as default
	//rrd fetches 1 day worth of data by default 
	$start = (isset($args['start']) && $args['start'] != '') ? $args['start'] : '0'; //defaults to 1yr
	$end = (isset($args['end']) && $args['end'] != '') ? $args['end'] : time(); //defaults to now 
	$resolution = (isset($args['resolution']) && $args['resolution'] != '') ? $args['resolution'] : ''; 
	
	$fetch = perfdata_rrdtool_path().' fetch ';

	// Check to make sure an RRD file actually exists
	$location = pnp_get_perfdata_file($host, $service);
	if (!file_exists($location)) {
		return false;
	}

	// Fetch data and return into $rrddata array
	$cmd = $fetch.$location.' AVERAGE';
	if (!empty($resolution)) {
		$cmd .= ' -r '.escapeshellarg($resolution);
	}
	if (!empty($start)) {
		$cmd .= ' -s '.escapeshellarg($start);
	}
	if (!empty($end)) {
		$cmd .= ' -e '.escapeshellarg($end);
	}
	
	putenv("LC_ALL=en_US");
	putenv("LANG=en_US"); 
	
	exec($cmd, $rrddata);
	 
	$times = array(); //array of all of the timestamps
	$data =array(); // make room for multiple columns 
	$data['sets'] = array(); 	

	foreach($rrddata as $line)
	{
		//check line syntax, ignore bad data 
		if(strlen(trim($line))<10 || trim($line)=='' ) continue;			
		//echo "should be grabbing a line with data: $line<br />";
		$values = explode(' ', trim($line));
		$time = substr(trim($values[0]), 0,10);
		if(strlen($time<9)) continue; //skip if there's no timestamp, data is bad 
		$times[] = $time; //assign valid time to array   //currently unused 
		for($i=1;$i<count($values);$i++)  
		{
			//create comma delineated list for JSON object			 
			if(!isset($data['sets'][$i-1])) $data['sets'][$i-1] = array(); //create new string index if none exists 
			//chop down string a raw float 
			//convert nan's to 'null'
			if(strpos($values[$i], 'nan')!==false)
			{
				$data['sets'][$i-1][]= 'null';  //replace nan's with 0	
				continue; 
			}	
			//handle rrd exponent multiplier 
			$parts = explode('e',$values[$i]); 
			$flt = $parts[0]; //the float
			$power = $parts[1];  //the multiplier ie -02 or +02 
			$mult = pow(10,$power); //get the actual multiplier 
			$str = $flt * $mult; //get the real number 
			//append to datastring		
			$data['sets'][$i-1][]=$str; 
					 
		}
							
	} //end of while
	
	

	$data['start'] = $times[0]; 	//save the start time from the data grab
	$data['increment'] = (intval($times[1]) - intval($times[0])); //calculate rrd resolution 
	$data['count'] = count($times); 
	//returns an array data strings for JSON object 
	return $data;  
}	

