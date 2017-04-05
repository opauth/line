<?php
/**
 * LINE strategy for Opauth
 * based on https://developers.line.me/web-api/integrating-web-login-v2
 *
 * More information on Opauth: http://opauth.org
 *
 * @copyright    Copyright © 2017 Yuzuru Suzuki (https://github.com/YuzuruS/)
 * @link         http://opauth.org
 * @package      Opauth.LINEStrategy
 * @license      MIT License
 */

/**
 * LINE strategy for Opauth
 * based on https://developers.line.me/web-api/integrating-web-login-v2
 *
 * @package			Opauth.LINE
 */
class LINEStrategy extends OpauthStrategy{

	/**
	 * Compulsory config keys, listed as unassociative arrays
	 */
	public $expects = array('channel_id', 'channel_secret');

	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'redirect_uri' => '{complete_url_to_strategy}oauth2callback',
	);

	/**
	 * Provider Name
	 */
	private $_provider = 'LINE';

	/**
	 * Auth request
	 */
	public function request(){
		$url = 'https://access.line.me/dialog/oauth/weblogin';

		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$_SESSION['_opauth_line_state'] = sha1(time());

		$params = array(
			'client_id' => $this->strategy['channel_id'],
			'redirect_uri' => $this->strategy['redirect_uri'],
			'response_type' => 'code',
			'state' => $_SESSION['_opauth_line_state']
		);

		$this->clientGet($url, $params);
	}

	/**
	 * Internal callback, after LINE's OAuth
	 */
	public function oauth2callback(){
		if (empty($_GET['code']) || empty($_GET['state'])) {
			$error = array(
				'provider' => $this->_provider,
				'code' => 'oauth2callback_error',
				'message' => $_GET['errorMessage'],
				'raw' => $_GET
			);
			$this->errorCallback($error);
			return;
		}

		$state = $_GET['state'];

		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		$session = $_SESSION['_opauth_line_state'];
		unset($_SESSION['_opauth_line_state']);
		if ($session !== $state) {
			$error = array(
				'provider' => $this->_provider,
				'code' => 'csrf_token_error',
				'message' => 'Can\'t verify CSRF token authenticity',
				'raw' => $_GET
			);

			$this->errorCallback($error);
			return;
		}

		$code = $_GET['code'];
		$url = 'https://api.line.me/v2/oauth/accessToken';
		$params = array(
			'code' => $code,
			'client_id' => $this->strategy['channel_id'],
			'client_secret' => $this->strategy['channel_secret'],
			'redirect_uri' => $this->strategy['redirect_uri'],
			'grant_type' => 'authorization_code'
		);
		$response = $this->serverPost($url, $params, null, $headers);

		$results = json_decode($response);

		if (empty($results) || empty($results->access_token)) {
			$error = array(
				'provider' => $this->_provider,
				'code' => 'access_token_error',
				'message' => 'Failed when attempting to obtain access token',
				'raw' => array(
					'response' => $response,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
			return;
		}

		$userinfo = $this->_userinfo($results->access_token);

		$this->auth = array(
			'uid' => $userinfo['userId'],
			'info' => array(),
			'credentials' => array(
				'token' => $results->access_token,
				'expires' => date('c', time() + $results->expires_in)
			),
			'raw' => $userinfo
		);

		if (!empty($results->refresh_token))
		{
			$this->auth['credentials']['refresh_token'] = $results->refresh_token;
		}

		$this->mapProfile($userinfo, 'displayName', 'info.name');
		$this->mapProfile($userinfo, 'pictureUrl', 'info.image');
		$this->mapProfile($userinfo, 'statusMessage', 'info.message');

		$this->callback();
	}

	/**
	 * Queries LINE API for user info
	 *
	 * @param string $access_token
	 * @return array Parsed JSON results
	 */
	private function _userinfo($access_token){
		$option = array('http' => array('header' => 'Authorization: Bearer '.$access_token));
		$userinfo = $this->serverGet('https://api.line.me/v2/profile', array(), $option, $headers);
		if (empty($userinfo)) {
			$error = array(
				'provider' => $this->_provider,
				'code' => 'userinfo_error',
				'message' => "Failed when attempting to query {$this->_provider} API for user information",
				'raw' => array(
					'response' => $userinfo,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
			return;
		}
		return $this->recursiveGetObjectVars(json_decode($userinfo));
	}
}