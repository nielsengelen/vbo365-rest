<?php
require_once('vendor/autoload.php');
set_time_limit(0);

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;

$scope = 'Directory.AccessAsUser.All User.ReadWrite.All offline_access';

function getDeviceCode($clientid, $tenantid, $scope) {
	try {
		$client = new \GuzzleHttp\Client();
		
		$url = 'https://login.microsoftonline.com/'.$tenantid.'/oauth2/v2.0/devicecode';
		$response = $client->request('POST', $url, [
					'form_params' => [
								'client_id' => $clientid,
								'scope' => $scope,
							],
					'headers' => [
								'Accept' => 'application/json',
								'Content-Type' => 'application/x-www-form-urlencoded',
								],
					'http_errors' => false,
					'verify' => false
				]);


		if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
		} else {
			$result = $response['error'];
		}
		
		return $result;
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);

			echo 'Error: ' . $exception['error'] . '<br>' . $exception['error_description'];
		} else {
			echo $e->getMessage();
		}
	}
}

function getToken($clientid, $tenantid, $devicecode) {
	try {
		$client = new \GuzzleHttp\Client();
		
		$url = 'https://login.microsoftonline.com/'.$tenantid.'/oauth2/v2.0/token';
		$response = $client->request('POST', $url, [
					'form_params' => [
								'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
								'client_id' => $clientid,
								'device_code' => $devicecode,
							],
					'headers' => [
								'Accept' => 'application/json',
								'Content-Type' => 'application/x-www-form-urlencoded',
								],
					'verify' => false
				]);

		if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
		} else {
			$result = $response->getStatusCode();
		}
		
		return $result;
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);

			return $exception;
		} else {
			echo $e->getMessage();
		}
	}
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	if (isset($_POST['action'])) { $action = $_POST['action']; }
	if (isset($_POST['assertion'])) { $assertion = $_POST['assertion']; }
	if (isset($_POST['clientid'])) { $clientid = $_POST['clientid']; }
	if (isset($_POST['tenantid'])) { $tenantid = $_POST['tenantid']; }
	if (isset($_POST['devicecode'])) { $devicecode = $_POST['devicecode']; }

	/* Microsoft Calls */
	if ($action == 'getdevicecode') {
		$code = getDeviceCode($clientid, $tenantid, $scope);
		
		echo json_encode($code);
	}
	if ($action == 'gettoken') {
		$token = getToken($clientid, $tenantid, $devicecode);
		
		echo json_encode($token);
	}
} else {
	die();
}
?>