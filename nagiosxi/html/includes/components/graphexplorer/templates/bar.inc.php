<?php //bargraph template

//returns bargraph JSON/Jquery
//Expects associative array -> $args(array()) :
//string $args['container']
//string $args['title']
//string $args['yTitle']
//string $args['categories']
//string  $args['names']
//string $args['data']  - actual field data, json_encoded array

/**
 * @param $args
 *
 * @return string
 */
function fetch_bargraph($args)
{
    $height = grab_array_var($args, 'height', 500);
    $filename = str_replace(" ", "_", strtolower($args['title']));

    $args['title'] = encode_form_val($args['title']);
    $args['names'] = encode_form_val($args['names']);
    $args['container'] = htmlentities($args['container'], ENT_QUOTES);

    // Special export settings for local exporting
    $exporting = "";
    if (get_option('highcharts_local_export_server', 1)) {
        $exporting = "exporting: {
            url: '".get_base_url()."/includes/components/highcharts/exporting-server/index.php',
            sourceHeight: $('#{$args['container']}').height(),
            sourceWidth: $('#{$args['container']}').width(),
            filename: '{$filename}',
            chartOptions: { chart: { spacing: [30, 50, 30, 30] } }
         },";
    }

    //begin heredoc string
    $graph = <<<GRAPH
	
		var chart1; // globally available
$(document).ready(function() {

    //reset default colors
    Highcharts.setOptions({
			    colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4']
	});

    chart1 = new Highcharts.Chart({
         {$exporting}
         chart: {
            renderTo: '{$args['container']}',      
            defaultSeriesType: 'bar',
			height: {$height},
			animation: false
         },
         credits: {
         	enabled: false
         },
         title: {
            text: '{$args['title']}'      
         },
		 legend: {
				enabled: false
		 },
         xAxis: {
         		categories: {$args['categories']},  //use if there are multiple perf outputs like "rta" and "pl"
              //categories: ['Apples', 'Bananas', 'Oranges']
         },
         yAxis: {
            title: {
               text: '{$args['yTitle']}'         
            }
         },
         series: 
		 [{
				name: '{$args['names']}',           
				data: {$args['data']},
				animation: false		 			 
		  }]  //end series           
	});  //close chart 
});
GRAPH;

    return $graph;
}		
		
