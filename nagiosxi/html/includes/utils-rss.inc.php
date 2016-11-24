<?php //utils.rss.inc.php 
//
// Copyright (c) 2008-20012 Nagios Enterprises, LLC.  All rights reserved.
//
// Development Started 08/22/2012
// 

/**
 * @param $url
 *
 * @return bool
 */
function xi_fetch_rss($url)
{

    $tmp = get_tmp_dir() . '/';
    $xmlcache = $tmp . str_replace('/', '_', $url) . '.xml';

    //check for cache, or update cache if it's older than 1 day
    if (file_exists($xmlcache) && filemtime($xmlcache) > (time() - (60 * 60 * 24))) {
        //use cache
        $xml = @simplexml_load_file($xmlcache);
        if ($xml)
            return $xml->channel->item;

    } else { //fetch live rss feed and cache it
        $xml = fetch_live_rss_and_cache($url, $xmlcache);
        if ($xml)
            return $xml->channel->item;
    }
    //false on failure
    return false;
}


/**
 * @param $url
 * @param $xmlcache
 *
 * @return SimpleXMLElement
 */
function fetch_live_rss_and_cache($url, $xmlcache)
{
    //use proxy component?
    $proxy = false;
    if (have_value(get_option('use_proxy')))
        $proxy = true;

    $options = array(
        'return_info' => true,
        'method' => 'get',
        'timeout' => 10
    );

    // fetch the url
    $result = load_url($url, $options, $proxy);
    $body = trim($result["body"]);

    //cache contents
    file_put_contents($xmlcache, $body);

    $xml = simplexml_load_string($body);

    return $xml;
}


