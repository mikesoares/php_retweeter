<?php

/*
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require('twitteroauth/twitteroauth.php');

/**
 * PHP Retweeter Class
 *
 * Allows you to retweet messages, tweet on your wall, follow people that mention you, etc.
 *
 * @author Michael A. Soares <me@mikesoares.com>
 * @copyright Copyright (c) 2010, Michael A. Soares
 *
 * @version 1.1
 */
class Retweeter {

	/**
	 * OAuth Object
	 *
	 * @var object
	 */
	var $oauth;

	/**
	 * Constructor
	 *
	 * @param string $access_token The access token for this application
	 * @param string $access_token_secret The access token secret for this application
	 * @param string $consumer_key The consumer key for your account
	 * @param string $consumer_secret The consumer key secret for your account
	 */
	public function Retweeter($access_token, $access_token_secret, $consumer_key, $consumer_secret) {
		$this->oauth = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
	}

	/**
	 * Call the API directly using GET
	 *
	 * @param string The API method
	 * @param array An array of parameters to send the method
	 * @return mixed Whatever the API method returns
	 */
	public function getCall($call, $params = array()) {
		return $this->oauth->get($call, $params);
	}

	/**
         * Call the API directly using POST
         *
         * @param string The API method
         * @param array An array of parameters to send the method
	 * @return mixed Whatever the API method returns
         */
	public function postCall($post, $params = array()) {
		return $this->oauth->post($call, $params);
	}

	/**
	 * Looks up user info
	 *
	 * @param string $username
	 * @return mixed False if an error or no results, array of users if true
	 */
	public function lookupUsers($username) {
                $response = $this->oauth->get('users/lookup', array('screen_name' => $username));

		if(count($response) > 0 && !isset($response->errors)) {
			return $response;
		} else {
			return false;
		}
        }

	/**
	 * Delete all direct messages (200 at a time)
	 */ 
	public function deleteAllDirect() {
		$results = $this->oauth->get('direct_messages', array('count' => 200));

		foreach($results as $result) {
			$this->oauth->post('direct_messages/destroy', array('id' => $result->id));
		}
	}

	/**
	 * Verifies authentication - Fails silently if not authenticated
	 *
	 * @return bool True if verified
	 */
	public function verifyAccess() {
		// verify credentials
		if($this->oauth->get('account/verify_credentials')->error)
			exit;
		
		// verified
		return true;
	}

	/**
	 * Follow users that mention you
	 *
	 * @param string $username Your username
	 * @return bool True if we follow someone, false if we don't
	 */
	public function followRetweeters($username) {
		$results = $this->searchTweet('@' . $username, 3);
		
		if(!$results) return false;
		
		foreach($results as $key => $value) {
			// do not attempt to follow self
			if($value['user'] == $username)
				continue;
			
			// check if friendship exists, if it doesn't, create one
			if(!$this->oauth->get('friendships/exists', array('user_a' => $username, 'user_b' => $value['user'])))
				return ($this->oauth->post('friendships/create', array('id' => $value['user']))->error) ? false : true;
			
			// we have already followed our retweeters
			return true;
		}
	}

	/**
	 * Search and retweet a user's tweet
	 *
	 * @param string $query Search query
	 * @param string $prefix Retweet prefix
	 * @return bool False if we can't find anything or if we can't retweet it, true if successful
	 */
	public function searchAndRetweet($query, $prefix = 'RT') {
		sleep(2);	// sleep so we can do a bunch of these consecutively
		
		// search for our result - make sure we get one back
		if(!($search_result = $this->searchTweet($query)))
			return false;
		
		// post it
		return $this->tweet($prefix . ' ' . '@' . $search_result['user'] . ': ' . $search_result['text']);
	}

	/**
	 * Tweet something directly to your page
	 *
	 * @param string $tweet Your tweet
	 * @return bool True if successful, false otherwise
	 */
	public function tweet($tweet) {
		// limit char count, post it and check for error
		return ($this->oauth->post('statuses/update', array('status' => $this->_truncateText(htmlspecialchars_decode($tweet))))->error) ? false : true;	
	}

	/**
	 * Search for tweets containing a search query
	 *
	 * @param string $query Search query
	 * @param int $results Number of results you want back
	 * @return mixed If we don't get back any results, returns false. Otherwise, an array containing search results (either 1 or multiple)
	 */
	public function searchTweet($query, $results = 1) {
		// search and temporarily store result
		$search_result = $this->oauth->get('http://search.twitter.com/search.json', array('q' => $query))->results;

		// store everything in an array
		if(count($search_result) > 0 && $results != 0) {
			// do we want 1 result or multiple results?
			if($results == 1) {
				return array('user' => $search_result[0]->from_user, 'id' => $search_result[0]->id, 'text' => $search_result[0]->text);
			} else {
				$multiple_results = array();
				
				for($i = 0; $i < $results; ++$i) 
					$multiple_results[] = array('user' => $search_result[$i]->from_user, 'id' => $search_result[$i]->id, 'text' => $search_result[$i]->text);
				
				return $multiple_results;
			}
		} else {
			return false;
		}
	}

	/**
	 * Truncates text down to however many characters you want
	 *
	 * @param string $text Your text to be truncated
	 * @param int $nChars Number of characters you want it truncated to (default 140)
	 * @param string $suffix Your text suffix after truncated (included in the $nChars amount)
	 * @return string Truncated text
	 */
	private function _truncateText($text, $nChars = 140, $suffix = "...")
	{
		// if above the limit, truncate it, including the suffix, else just return the original text
		return (strlen($text) > $nChars) ? substr($text, 0, $nChars-strlen($suffix)).$suffix : $text;
	}
}

?>
