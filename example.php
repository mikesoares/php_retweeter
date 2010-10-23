<?php

	require('php_retweeter.php');

	// optional
	$username = 'username';
	$search_query = 'search query -@'.$username;
	$retweet_prefix = 'RT';
	
	// required
	// get these values from http://twitter.com/apps after setting up an application
	$access_token = '';
	$access_token_secret = '';

	// get these values from http://dev.twitter.com/apps after setting up an application (select your app and click on My Access Token)
	$consumer_key = '';
	$consumer_secret = '';

	// create our object	
	$retweeter = new Retweeter($access_token, $access_token_secret, $consumer_key, $consumer_secret);

	// now we just call our retweeter functions
	
	// just want to make sure we've been authenticated properly, else fail silently
	$retweeter->verifyAccess();
	
	// search for a term and retweet the original tweet
	$retweeter->searchAndRetweet($search_query, $retweet_prefix);

	// follow anyone that mentions your username (or any username you specify, for that matter)
	$retweeter->followRetweeters($username);

?>
