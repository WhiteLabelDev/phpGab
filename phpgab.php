<?php

/**
 * phpGab
 *
 * Gab.ai does not currently have a public API or documentation, so here
 * are some basic, quick and dirty PHP functions to get you started talking
 * to the Gab.ai API. Presently only posting a plain text Gab is supported,
 * but this should be easy to extend to start doing other things.
 *
 * This is wholly unofficial, likely to break, and hopefully a temporary
 * solution until Gab release an official public API. Use at your own risk
 * and be sure to follow the Gab terms of service: https://gab.ai/about/tos
 * Please be respectful and do not spam Gab with this script!
 *
 * You can follow us at https://gab.ai/white_label_dev
 *
 * @author White Label Dev Ltd
 * @copyright 2017 White Label Dev Ltd
 * @license See LICENSE file
 * @version 1.0
 */


/**
 * Sends a plain text Gab
 * Returns FALSE on error or an array of details about
 * the posted gab on success.
 *
 * @param string $_GabUsername The Gab account username
 * @param string $_GabPassword The Gab account password
 * @param string $_Gab The gab to post (max 300 chars)
 * @return mixed
 * @since PHP 5
 **/
function Gab_Send ($_GabUsername, $_GabPassword, $_GabGab) {
	// Get the JWT token
	$_GabToken = Gab_TokenGet ($_GabUsername, $_GabPassword);
	if (FALSE === $_GabToken)
		return FALSE;

	// Construct the gab post
	$_GabPost = json_encode (array (
		'body' => $_GabGab,
		'reply_to' => '',
		'is_quote' => '0',
		'_method' => 'post',
		'gif' => '',
		'category' => NULL,
		'topic' => NULL,
		'file' => NULL,
		'share_facebook' => NULL,
		'share_twitter' => NULL,
		'is_replies_disabled' => FALSE
	));

	// Send the gab to the Gab API
	list ($_GabHead, $_GabBody) = Gab_cURL ('https://gab.ai/posts', 'POST', $_GabPost, array (
		'Content-Type: application/json',
		'Content-Length: '.strlen ($_GabPost),
		'Authorization: Bearer '.$_GabToken,
	));

	// Return the API response to caller
	return (FALSE === $_GabBody ? FALSE : json_decode ($_GabBody, TRUE));
}

/**
 * Extracts a Gab token (both login tokens and JWT
 * bearer tokens) from a line of text
 * Returns FALSE on error or the extracted token
 * string on success.
 *
 * @param string $_GabTokenLine Line of text with a token
 * @return mixed
 * @since PHP 5
 **/
function Gab_TokenExtract ($_GabTokenLine) {
	// Attempt to extract the token in the cheapest way possible
	$_GabTokenLine = explode ('"', $_GabTokenLine);
	$_GabTokenCount = count ($_GabTokenLine);
	if ($_GabTokenCount < 2 || strlen ($_GabTokenLine[$_GabTokenCount - 2]) < 40)
		return FALSE;

	return trim ($_GabTokenLine[$_GabTokenCount - 2]);
}

/**
 * Gets a Gab JWT bearer token for future API requests
 * Returns FALSE on error or a token string on success.
 * Attempts to first retrieve a locally cached token
 * for the user, or does a Gab login to retrieve one
 * otherwise. When a token is retrieved it is cached
 * locally for quicker future API requests.
 *
 * @param string $_GabUsername The Gab account username
 * @param string $_GabPassword The Gab account password
 * @param string $_Gab The gab to post (max 300 chars)
 * @return mixed
 * @since PHP 5
 **/
function Gab_TokenGet ($_GabUsername, $_GabPassword) {
	global $debug;

	// Choose a unique enough filename outside of document root which will be invalidated as soon as any credentials change
	// TODO Windows user check this path separator and hidden 8.3 name is accepted
	$_GabTokenFile = sys_get_temp_dir ().'/.'.substr (md5 ($_GabUsername.$_GabPassword), 0, 7).'.gab';

	// Attempt to retrieve a cached token to save thrashing the Gab API with logins
	if (file_exists ($_GabTokenFile) && $_GabToken = file_get_contents ($_GabTokenFile)) {
		// Validate the file data is sane and the token has not expired
		$_GabTokenParts = explode ('.', $_GabToken);
		if (count ($_GabTokenParts) == 3 && $_GabTokenParts = base64_decode ($_GabTokenParts[1])) {
			$_GabTokenParts = json_decode ($_GabTokenParts, TRUE);
			if (is_array ($_GabTokenParts) && isset ($_GabTokenParts['exp']) && $_GabTokenParts['exp'] > time ())
				return $_GabToken;
		}
	}

	// We make 2 requests, firstly to get the login token and secondly to perform the login to get the JWT token
	for ($i = 0; $i < 2; ++$i) {
		if (!$i) {
			list ($_GabHead, $_GabBody) = Gab_cURL ('https://gab.ai/auth/login');
			//$_GabBody = file_get_contents ('/tmp/login.html'); // FIXME Remove HACK
		} else {
			list ($_GabHead, $_GabBody) = Gab_cURL ('https://gab.ai/auth/login', 'POST', array (
				'_token' => $_GabToken,
				'username' => $_GabUsername,
				'password' => $_GabPassword,
				'remember' => 'on'
			), NULL /*array (
				//'Content-Type: application/x-www-form-urlencoded'
			)*/);
			//$_GabBody = file_get_contents ('/tmp/okay.html'); // FIXME Remove HACK
		}

		if (FALSE === $_GabBody)
			return FALSE;

		// Loop through the returned body lines looking for a token
		$_GabBody = explode ("\n", $_GabBody);
		for ($j = 0, $k = count ($_GabBody); $j < $k; ++$j) {
			if (strpos ($_GabBody[$j], '_token') !== FALSE)
				break;
		}

		// No candidate line found
		if ($j == $k)
			return FALSE;

		// Try to extract a token from the candidate line
		$_GabToken = Gab_TokenExtract ($_GabBody[$j]);
		if (FALSE === $_GabToken)
			return FALSE;

		if (isset ($debug) && $debug)
			echo (!$i ? 'Login' : 'JWT').' token: '.$_GabToken."\n";
	}

	// Store the token in a cache for quick retrieval later
	file_put_contents ($_GabTokenFile, $_GabToken);

	return $_GabToken;
}

/**
 * cURL header callback
 * This function gets called for every header returned
 * in the HTTP response. They are stored in an array
 * to be returned to the caller at the end of the cURL
 * request. Returns the number of bytes processed.
 *
 * @param resource $_GabCH The cURL resource handle
 * @param string $_GabHeaderLine The header passed in
 * @return int
 * @since PHP 5
 **/
function Gab_HeaderGet ($_GabCH, $_GabHeaderLine) {
	global $_GabHead;

	if (isset ($debug) && $debug)
		echo 'Processing cURL header: '.$_GabHeaderLine;

	// Strip newlines and split on header delimiter
	$_GabHeaderParts = explode (': ', str_replace ("\n", '', str_replace ("\r", '', $_GabHeaderLine)), 2);

	// Catch rows like "HTTP/1.1 OK"
	if (!isset ($_GabHeaderParts[1]))
		$_GabHeaderParts = explode (' ', $_GabHeaderParts[0], 2);

	// Add only if header sane, avoids last \r\n line
	if (count ($_GabHeaderParts) == 2)
		$_GabHead[$_GabHeaderParts[0]] = $_GabHeaderParts[1];

	return strlen ($_GabHeaderLine);
}

/**
 * Makes a cURL request
 * Returns an indexed array containing the headers and
 * body of the request. If the request failed then the
 * body component will be FALSE. If the request was
 * successful then the body component will contain the
 * returned remote data and the head component will
 * contain an array of response headers. Call like
 * list ($head, $body) = Gab_cURL (... for a shorthand
 * way of retrieving both components in to variables.
 *
 * @param string $_GabURL The full URL to connect to
 * @param string $_GabMethod One of GET or POST
 * @param mixed $_GabData Optional data to POST
 * @param mixed $_GabHeader Optional headers to send
 * @return array
 * @since PHP 5
 **/
function Gab_cURL ($_GabURL, $_GabMethod = 'GET', $_GabData = NULL, $_GabHeader = NULL) {
	global $debug, $_GabHead;

	// Reset the headers and body for a new request
	$_GabHead = array ();
	$_GabBody = FALSE;

	// Check cURL is enabled
	$_GabCH = curl_init ();
	if (FALSE !== $_GabCH) {
		// Construct cookie jar file path
		$_GabCookieFile = sys_get_temp_dir ().'/.cookies.gab';

		// Setup connection options
		curl_setopt ($_GabCH, CURLOPT_URL, $_GabURL);			// The URL to open
		curl_setopt ($_GabCH, CURLOPT_RETURNTRANSFER, TRUE);		// To return the content from curl_exec
		curl_setopt ($_GabCH, CURLOPT_FORBID_REUSE, TRUE);		// Force close the connection when complete
		curl_setopt ($_GabCH, CURLOPT_FRESH_CONNECT, TRUE);		// Force a new connection to avoid stale data
		curl_setopt ($_GabCH, CURLOPT_AUTOREFERER, TRUE);		// Behave more like a browser if endpoint moves
		curl_setopt ($_GabCH, CURLOPT_FOLLOWLOCATION, TRUE);		// Behave more like a browser if endpoint moves
		curl_setopt ($_GabCH, CURLOPT_BINARYTRANSFER, TRUE);		// We want the raw response data to process
		curl_setopt ($_GabCH, CURLOPT_TIMEOUT, 10000);			// How long we will wait for anything to happen
		curl_setopt ($_GabCH, CURLOPT_CONNECTTIMEOUT, 5000);		// How long we will wait to connect
		curl_setopt ($_GabCH, CURLOPT_ENCODING, 'gzip, deflate');	// Reduce Gabs bandwidth costs for our requests
		curl_setopt ($_GabCH, CURLOPT_COOKIEJAR, $_GabCookieFile);	// Location to store inter-request cookies
		curl_setopt ($_GabCH, CURLOPT_COOKIEFILE, $_GabCookieFile);	// Location to load inter-request cookies
		curl_setopt ($_GabCH, CURLOPT_USERAGENT, 'phpGab 1.0');		// Be transparent about what we are
		curl_setopt ($_GabCH, CURLOPT_HEADERFUNCTION, 'Gab_HeaderGet');	// Where header lines get sent for processing

		// If this is a POST request, add relevant settings and data
		if ('POST' == strtoupper ($_GabMethod)) {
			curl_setopt ($_GabCH, CURLOPT_POST, TRUE);		// Use the POST method (any data is optional)
			if (NULL != $_GabData) {				// Add POST data (dependent on encoding)
				if (!is_array ($_GabData))
					curl_setopt ($_GabCH, CURLOPT_CUSTOMREQUEST, 'POST');

				if (isset ($debug) && $debug)
					echo 'Posting: '."\n".print_r (is_array ($_GabData) ? http_build_query ($_GabData) : $_GabData, TRUE)."\n";

				curl_setopt ($_GabCH, CURLOPT_POSTFIELDS, (is_array ($_GabData) ? http_build_query ($_GabData) : $_GabData));
			}
		}

		if (NULL != $_GabHeader)
			curl_setopt ($_GabCH, CURLOPT_HTTPHEADER, $_GabHeader);	// Request headers (arrays are auto encoded)

		// Make request and immediately close connection
		$_GabBody = curl_exec ($_GabCH);
		curl_close ($_GabCH);
	}

	if (isset ($debug) && $debug)
		echo 'cURL request to '.$_GabURL.':'.(FALSE === $_GabBody ? ' FAILED' : "\n".$_GabBody."\n");

	return array ($_GabHead, $_GabBody);
}

?>