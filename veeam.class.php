<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

/** Class VBO **/
class VBO {
  private $client;
  private $token;
  private $sessionid;

  /**
   * @param $uri
   * @param $method
   * @param $user
   * @param $pass
   */
  public function __construct($host, $port, $user, $pass) {
	$uri = 'https://'.$host.':'.$port;
	$this->client = new GuzzleHttp\Client(['base_uri' => $uri]);
	
	$response = $this->client->request('POST', '/v1/token', [
			'form_params' => [
				'grant_type' => 'password',
				'username' => $user,
				'password' => $pass
			],
			'headers' => [
				   'Accept' => 'application/json',
				   'Content-Type' => 'application/x-www-form-urlencoded',
				],
			'verify' => false
		]
	);

	$result = json_decode($response->getBody(), true);	
	$this->token = $result['access_token'];
  }

  public function getBackupRepositories() {
	$response = $this->client->request('GET', '/v1/BackupRepositories/', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  }
  
  public function getJobs($jobid = NULL) {
	$response = $this->client->request('GET', '/v1/Jobs/', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  }
  
  public function getJobSession($jobid) {
	$response = $this->client->request('GET', '/v1/Jobs/'.$jobid.'/JobSessions', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  }
  
  public function getMailbox($id) {
	$response = $this->client->request('GET', '/v1/Organizations/'.$id.'/Mailboxes', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  }
  
  public function getOrganization($id = NULL) {
	$response = $this->client->request('GET', '/v1/Organizations', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);
  }
  
  public function getOrganizationJob($id) {
	$response = $this->client->request('GET', '/v1/Organizations/'.$id.'/Jobs', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  }

  public function getProxies() {
	$response = $this->client->request('GET', '/v1/Proxies/', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  }
  
  public function endRestoreSession($id) {
	$response = $this->client->request('POST', '/v1/RestoreSessions/'.$id.'/action', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'verify' => false,
					'body' => '{ "stop": null }'
				]
			);
			
	$result = json_decode($response->getBody(), true);
	
	return($result);  
  } 
  
  public function startRestoreSession($id) {
	$response = $this->client->request('POST', '/v1/Organizations/'.$id.'/action', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'verify' => false,
					'body' => '{ "explore": { "datetime": "2017-08-02 22:00:28" } }'
				]
			);
			
	$result = json_decode($response->getBody(), true);
	$this->sessionid = $result['id'];
	
	return($result);  
  }
}
?>