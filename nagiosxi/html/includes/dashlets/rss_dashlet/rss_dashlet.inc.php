<?php
//
// Custom RSS Dashlet
// Copyright (c) 2008-2015 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__).'/../dashlethelper.inc.php');

rss_dashlet_init();

function rss_dashlet_init()
{
	$name = "rss_dashlet";
	
	$args = array(
		DASHLET_NAME => $name,
		
		// informative information
		DASHLET_VERSION => "1.0.0",
		DASHLET_DATE => "10-21-2015",
		DASHLET_AUTHOR => "Nagios Enterprises, LLC",
		DASHLET_DESCRIPTION => _("Scrollilng RSS Feed. Define your own RSS feed, with up to 5 URLs"),
		DASHLET_COPYRIGHT => "Copyright (c) 2010-2015 Nagios Enterprises",
		DASHLET_LICENSE => "BSD",
		DASHLET_HOMEPAGE => "www.nagios.com",
		
		DASHLET_FUNCTION => "rss_dashlet_func",
		
		DASHLET_TITLE => "RSS Dashlet",
		
		DASHLET_OUTBOARD_CLASS=> "rss_dashlet_outboardclass",
		DASHLET_INBOARD_CLASS => "rss_dashlet_inboardclass",
		DASHLET_PREVIEW_CLASS => "rss_dashlet_previewclass",
		
		DASHLET_CSS_FILE => "rss_dashlet.css",
		//DASHLET_JS_FILE => "rss-dashlet.js",

		DASHLET_WIDTH => "250",
		DASHLET_HEIGHT => "360",
		DASHLET_OPACITY => ".8"
	);

	register_dashlet($name, $args);
}
	

function rss_dashlet_func($mode=DASHLET_MODE_PREVIEW,$id="",$args=null){
	
	$output="";

	switch($mode){
		case DASHLET_MODE_GETCONFIGHTML:
			//input form for dashlet vars 
			$output='
			<br/>	
			
			<label for="url1">RSS 1 </label>
			<br class="nobr" />
			<input type="text" name="url1" id="url1" value="" size="40">
			<br class="nobr" />
			
			<label for="url22">RSS 2 </label>
			<br class="nobr" />
			<input type="text" name="url2" id="url2" value="" size="40">
			<br class="nobr" />
			
			<label for="title3">RSS 3 </label>
			<br class="nobr" />
			<input type="text" name="url3" id="url3" value="" size="40">
			<br class="nobr" />
			
			<label for="title4">RSS 4 </label>
			<br class="nobr" />
			<input type="text" name="url4" id="url4" value="" size="40">
			<br class="nobr" />
			
			<label for="url5">RSS 5</label>
			<br class="nobr" />
			<input type="text" name="url5" id="url4" value="" size="40">
			<br class="nobr" />
			
			<label for="opacity">Opacity</label>
			<br class="nobr" />
			<select name="opacity" id="opacity">
			<option value="1.0">100%</option>
			<option value=".75" selected>75%</option>
			<option value=".50">50%</option>
			<option value=".25">25%</option>
			</select>
			<br class="nobr" />
			';  
			break;
		case DASHLET_MODE_OUTBOARD:
		case DASHLET_MODE_INBOARD:
			//vars from input form 
			//$height=$args["height"];
			$height = 310; 
			$width = 250; 
			//$title=$args['feedTitle']; 
			//$width=$args["width"];
			$opacity=$args["opacity"];
			
			//grab RSS data 
			require('magpierss/rss_fetch.inc'); 
			$rss = array(); 
			$count=1; 
			foreach(array($args['url1'], $args['url2'],$args['url3'],$args['url4'],$args['url5']) as $arg)
			{
				$title = isset($args['title'.$count]) ? $args['title'.$count] : "Feed $count";
				if(!isset($arg) || $arg == '') break; 
				else $rssObject = @fetch_rss(urldecode($arg)); 
				$newarray = array_slice($rssObject->items,0,10); 
				foreach($newarray as $a) $a['source'] = $title;
				$rss = array_merge($rss,$newarray); 	//grab only 10 results 	
				$count++; 	
			}
			
			$rss2 = array();
			foreach($rss as $item)
			{
				$date = 0; 
				if(isset($item['dc']['date'])) $date =  strtotime($item['dc']['date']); 
				elseif(isset($item['published'])) $date =  strtotime($item['published']);
				elseif(isset($item['pubdate'])) $date = strtotime($item['pubdate']); 
				elseif(isset($item['pubDate'])) $date = strtotime($item['pubDate']);
				elseif(isset($item['updated'])) $date = strtotime($item['updated']);
				elseif(isset($item['timestamp'])) $date = $item['timestamp'];
				$rss2[$date] = $item; 
			}
			asort($rss2); //sort array by date 
			//random dashlet id 
			$rand = rand(); 
			$inWidth = $width-10; //dimensions for iframe 
			$inHeight = $height-50;
						
			//html output    			
			$output.="
			<div class='rss_dashlet_inboardclass' id='rssdashlet{$rand}'>"; 
			$output.="<div id='news-feed{$rand}'>"; 
			$output.= "<div class='rss_inboardclass_rssList' id='theList'>";
			$r = 178;
			$g = 255;
			$b = 95; 
			foreach($rss2 as $item) 
			{
				$source = isset($item['source']) ? $item['source'] : 'No Source'; 
				$rgb="$r,$g,$b"; //gradient each RSS feed by date (darker with age) 
				$output.= '<div style="background-color: rgb('.$rgb.')" class="rss_dashlet_inboardclass_rssListItem">								
									<a class="rss_dashlet_inboardclass_rssLink" title="'.htmlentities($item['description']).'" target="_blank" href="'.$item['link'].'">'.$item['title'].'</a>
											</div>';
				if($r<253) $r+=3; else $r=255;  
				if($g<3) $g = 0; else $g-=3;
				if($b<3) $b = 0; else $b-=3; 
				
			}
			$output .= "</div>\n"; 					
			$output.= "</div></div>\n"; 
			$output.= "<script type='text/javascript'>\n\n"; 
			
			//DO NOT MODIFY
			//html output (heredoc string syntax) 
			$output.=<<<OUTPUT
			
			var dashcontainer = $('#rssdashlet{$rand}').parent().parent(); 
			var innercontainer = $('#rssdashlet{$rand}').parent(); 
			
			//set specified size on page load.  Overrides hardcoded settings.  
			$(document).ready( function() {
			   //do stuff on load 
			   innercontainer.css('opacity', '{$opacity}')
							.css('height', '{$inHeight}px')
							.css('width', '{$width}px');
							
										
			   dashcontainer.css('height', '{$height}px')
							.css('width', '{$width}px'); 
							
				$('#rssdashlet{$rand}').width({$width}).height({$inHeight});
				
			  
			}); 
			
			//bind resize handlers to div and iframe  

			
			dashcontainer.resize(function() {
				newHeight = $(this).height();				
				newWidth = $(this).width(); 

				//innercontainer.width(newWidth).height(newHeight);
				$('#rssdashlet{$rand}').width(newWidth).height(newHeight-50);

			}); 	
			
			//jquery for headline rotation
			var interval='';

			$(document).ready(function() {
				rotate(); 
				//bind RSS rotation to hover event 
				$('#news-feed{$rand}').hover(function() { //define 2 functions for hover on, and hover off 
					clearTimeout(interval, 0);
					},  
					function() {
						 interval = setTimeout('rotate()',250);	
				}); //end hover args
			});

			//sends top item to the bottom of the list 
			function rotate()  {  
				var thisItem = $('.rss_dashlet_inboardclass_rssListItem:eq(0)');
				thisItem.appendTo($('#theList'));
				interval = setTimeout('rotate()',4000);	//tells browser to run again in 4 seconds  
			}
			
			</script>
OUTPUT;


			
			
			break;
		case DASHLET_MODE_PREVIEW:
			$output="<p><img src='/nagiosxi/includes/dashlets/rss_dashlet/rss.jpg' height='40' width='40' alt='RSS' /></p>";
			break;			
		}
		
	return $output;
	}


?>