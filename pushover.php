#!/usr/bin/php
<?php

    define('APP_TOKEN', 'XXXXXXXXXXXXXXXXXXXXXXXXXXX');
    define('API_URL',   'https://api.pushover.net/1/messages.json');
 
    // CLI Colours
    define('RED',     chr(27).'[1;31m');
    define('GREEN',   chr(27).'[1;32m');
    define('RESET',   chr(27).'[0m');
    define('CRLF',    "\r\n");
 
    // Make sure CURL is available for this PHP install
    if (!function_exists('curl_init')) {
        print RED.'CURL is not available in this version of PHP'.RESET.CRLF;
        exit(1);
    }
     
    // Command Line options
    $shortopts  = 'hm::u::a:t:s:p:';
    $options    = getopt($shortopts);

    //file_put_contents('/tmp/pushover.response',date("c")."\n\noptions".print_r($options,1),FILE_APPEND);
     
    // Show Help
    if (isset($options['h'])) {
         // Display Usage
        print 'Sends a Pushover Notification'."\r\n";
        print 'USAGE: '.basename($_SERVER['SCRIPT_FILENAME']).' [-h] [-a=message_title] [-t=timestamp] -u=user_token -m=message'.CRLF;
        print '  -a    Title of the Application to show. If not set, will show as \'CLI\''.CRLF;
        print '  -t    Timestamp to show in the message. If not set, will use now'.CRLF;
        print '  -u    The User Token key'.CRLF;
        print '  -m    The Message to send'.CRLF;
	      print '  -s    Sound https://pushover.net/api#sounds'.CRLF;
        print '  -h    Display this help'.CRLF.CRLF;
	      print '  -p    Prio';
        exit();
    }
     
    // Setup Post Array
    $post_array = array('token' => APP_TOKEN);
     
    // Check Token
    if (!isset($options['u']) || !$options['u']) {
        print RED.'Invalid User Token'.RESET.CRLF;
        exit(2);
    } else {
        $post_array['user'] = $options['u'];
    }
     
    // Check Message
    if (!isset($options['m']) || !$options['m'] || !strlen(trim($options['m']))) {
        print RED.'Message was not supplied'.RESET.CRLF;
        exit(3);
    } else {
        $post_array['message'] = $options['m'];
        $post_array['message'] = str_replace('\n',"\n",$post_array['message']);
    }

    // Check Sound
    if (!isset($options['s']) || !$options['s'] || !strlen(trim($options['s']))) {
	    //$post_array['sound'] = "none";
    } else {
        $post_array['sound'] = $options['s'];
    }
     
    // Check Timestamp
    if (isset($options['t']) && !is_numeric($options['t'])) {
        print RED.'Timestamp must be a unix timestamp integer'.RESET.CRLF;
        exit(4);
    } else if (isset($options['t'])) {
        $post_array['timestamp'] = $options['t'];
    }
     
    // Check Title
    if (isset($options['a']) && strlen(trim($options['a']))) {
        $post_array['title'] = $options['a'];
    }

    // Check Priority
    if (!isset($options['p']) || !$options['p'] || !strlen(trim($options['p']))) {
        $post_array['priority'] = "0";
    } else {
        $post_array['priority'] = $options['p'];
    }
     
    // Send the notification
    $resource = curl_init();
     
    $curl_opt_array = array(
        // Set Url
        CURLOPT_URL            => API_URL,
        // Return a String
        CURLOPT_RETURNTRANSFER => true,
        // Timeouts
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 30,
        // Fail for 400+
        CURLOPT_FAILONERROR    => true,
        // Follow "Location" headers
        CURLOPT_FOLLOWLOCATION => true,
        // This is POST
        CURLOPT_POST           => true,
        // Post Fields
        CURLOPT_POSTFIELDS     => $post_array,
    );
    curl_setopt_array($resource, $curl_opt_array);
     
    // Send!
    $response = curl_exec($resource);
     
    // Stats
    $stats = curl_getinfo($resource);
 

    //file_put_contents('/tmp/pushover.response',date("c")."\n\n".print_r($post_array,1)."\n\n".print_r($response,1),FILE_APPEND);

    // Errors and redirect failures
    if (!$response) {
        print RED.curl_errno($resource).' - '.curl_error($resource).RESET.CRLF;
    } else {
        $result = json_decode($response, true);
        if ($result && is_array($result) && $result['status']) {
            // Success
            print GREEN.'Message Sent'.RESET.CRLF;
        } else {
            print RED.'Could not send message. Please try again.'.RESET.CRLF;
        }
    }
     
    curl_close($resource);
