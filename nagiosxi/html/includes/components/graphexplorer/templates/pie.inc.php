<?php //pie.inc.php    template file for pie chart 

/**
 * @param $args
 *
 * @return string
 */
function fetch_piegraph($args)
{

    $graph = '';
    if ($args['pieType'] == 'hosthealth') $colors = "['#b2ff5f', '#FF795F', '#FFC45F']"; //green,red,orange
    elseif ($args['pieType'] == 'servicehealth') $colors = "['#b2ff5f','#FEFF5F','#FF795F', '#FFC45F']"; //green,yellow,red,orange
    else $colors = "['#4572A7', '#AA4643', '#89A54E', '#80699B', '#3D96AE', '#DB843D', '#92A8CD', '#A47D7C', '#B5CA92']"; //default

    $height = grab_array_var($args, 'height', 500);
    $args['title'] = encode_form_val($args['title']);
    $args['subtitle'] = encode_form_val($args['subtitle']);
    $args['container'] = htmlentities($args['container'], ENT_QUOTES);
    $filename = str_replace(" ", "_", strtolower($args['title']));

    // Special export settings for local exporting
    $exporting = "";
    if (get_option('highcharts_local_export_server', 1)) {
        $exporting = "exporting: {
            url: '".get_base_url()."/includes/components/highcharts/exporting-server/index.php',
            sourceHeight: $('#{$args['container']}').height(),
            sourceWidth: $('#{$args['container']}').height(),
            filename: '{$filename}',
            chartOptions: { chart: { spacing: [20, 20, 25, 20] } }
         },";
    }

    //begin heredoc string
    $graph .= <<<GRAPH
	
	
	Highcharts.setOptions({
    colors: {$colors} 
	});
	
	
	var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					{$exporting}
					chart: {
						renderTo: '{$args['container']}',
						plotBackgroundColor: null,
						plotBorderWidth: null,
						plotShadow: false,
						height: {$height},
						animation: false
					},
					credits: {
						enabled: false
					},
					title: {
						text: '{$args['title']}'
					},
					tooltip: {
						formatter: function() {
							return '<b>'+ this.point.name +'</b>: '+ this.y +' %';
						}
					},
					plotOptions: {
						pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
								enabled: true,
								color: '#000',
								connectorColor: '#000000',
								formatter: function() {
									switch(this.point.name)
									{
										case 'OK':
										case 'UP':
											var middle = '<a href="{$args['url']}2" target="_blank" title="View All By State">'+ this.point.name +'</a>';
										break;
										
										case 'WARNING':
										case 'DOWN':
											var middle = '<a href="{$args['url']}4" target="_blank" title="View All By State">'+ this.point.name +'</a>';
										break;
										
										case 'UNKNOWN':
										case 'UNREACHABLE':
											var middle = '<a href="{$args['url']}8" target="_blank" title="View All By State">'+ this.point.name +'</a>';
										break; 
										
										case 'CRITICAL':
											var middle = '<a href="{$args['url']}16" target="_blank" title="View All By State">'+ this.point.name +'</a>';
										break; 
										
										default: 
											var middle = this.point.name;
										break;
									}
									var string = '<strong>'+middle+'</strong>: '+ this.y +' %';
									return string;
									//return '<b><a href="index.php">'+ this.point.name +'</a></b>: '+ this.y +' %';
								}
							}
						}
					},
				    series: [{
						type: 'pie',
						name: '{$args['subtitle']}',
						data: [
							{$args['datastring']}
						],
						animation: false
					}]
				});
			});
GRAPH;

    return $graph;

} //end fetch_pie()

