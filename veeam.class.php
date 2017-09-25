<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;

/** Class VBO **/
class VBO {
  private $client;
  private $refreshtoken;
  private $token;

  /**
   * @param $uri
   * @param $method
   * @param $user
   * @param $pass
   */
  public function __construct($host, $port) {
	$this->client = new GuzzleHttp\Client(['base_uri' => 'https://'.$host.':'.$port]);
  }

  public function changeJobState($id, $json) {
	try {
		$response = $this->client->request('POST', '/v1/Jobs/'.$id.'/action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
					]
				);
				
		if ($response->getStatusCode() === 200) {
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function createJob($id, $json) {
	try {
		$response = $this->client->request('POST', '/v1/Organizations/'.$id.'/Jobs', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json
					]
				);
				
		if ($response->getStatusCode() === 201) {
			echo 'Job has been added.';
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  public function createOrganization($json) {
	try {
		$response = $this->client->request('POST', '/v1/Organizations', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json
					]
				);
				
		if ($response->getStatusCode() === 201) {
			echo 'Organization has been added.';
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
    
  public function createProxy($json) {
	try {
		$response = $this->client->request('POST', '/v1/Proxies', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
					]
				);
				
		if ($response->getStatusCode() === 201) {
			echo 'Proxy has been added.';
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  public function createRepository($json) {
	try {
		$response = $this->client->request('POST', '/v1/BackupRepositories', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json
					]
				);
				
		if ($response->getStatusCode() === 201) {
			echo 'Repository has been added.';
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function endSession($id) {
	try {
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
		
		if ($response->getStatusCode() === 201) {			
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function exportItem($mid, $rid, $iid, $json) {
	$tmpFile  = sys_get_temp_dir() . '/' . $iid;
	$resource = fopen($tmpFile, 'w');
	$stream   = GuzzleHttp\Psr7\stream_for($resource);
	
	try {
		$response = $this->client->request('POST', '/v1/RestoreSessions/'.$rid.'/organization/mailboxes/'.$mid.'/items/'.$iid.'/action', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/octet-stream',
						'Content-Type'  => 'application/json'
					],
					'verify' => false,
					'body' => $json,
					'sink' => $tmpFile,
				]
			);

		$result = json_decode($response->getBody(), true);
		
		$stream->close();
		fclose($resource);
		
		if ($response->getStatusCode() === 200) {
			echo $tmpFile;
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getBackupRepositories() {
	try {
		$response = $this->client->request('GET', '/v1/BackupRepositories/', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]
				);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getBackupRepository($id) {
	try {
		$response = $this->client->request('GET', '/v1/Proxies/'.$id.'/repositories', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
						],
						'verify' => false
					]
				);
			
		$result = json_decode($response->getBody(), true);
	
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getItems($rid, $mid) {
	try {
		$response = $this->client->request('GET', '/v1/RestoreSessions/'.$rid.'/organization/mailboxes/'.$mid.'/items', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'http_errors' => false,
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getJobs($id = NULL) {
	if ($id) {
		$call = '/v1/Organizations/'.$id.'/Jobs';
	} else {
		$call = '/v1/Jobs/';
	}
	
	try {
		$response = $this->client->request('GET', $call, [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'http_errors' => false,
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getFolders($rid, $mid) {
	  try {
		$response = $this->client->request('GET', '/v1/RestoreSessions/'.$rid.'/organization/mailboxes/'.$mid.'/Folders', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'http_errors' => false,
					'verify' => false,
				]
			);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {			
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getMailbox($id) {
	  try {
		$response = $this->client->request('GET', '/v1/RestoreSessions/'.$id.'/organization/mailboxes', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'http_errors' => false,
					'verify' => false,
				]
			);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {			
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getOrganizations() {
	try {
		$response = $this->client->request('GET', '/v1/Organizations', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]
				);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getOrganizationJob($id) {
	try {
		$response = $this->client->request('GET', '/v1/Organizations/'.$id.'/Jobs', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  public function getOrganizationMailboxes($id) {
	  try {
		$response = $this->client->request('GET', '/v1/Organizations/'.$id.'/mailboxes', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'verify' => false,
				]
			);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {			
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getProxies() {
	try {
		$response = $this->client->request('GET', '/v1/Proxies/', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'http_errors' => false,
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
	
		if ($response->getStatusCode() === 200) {
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getRefreshToken() {
	  return($this->refreshtoken);
  }
  
  public function getSelectedMailboxes($id) {
	try {
		$response = $this->client->request('GET', '/v1/Jobs/'.$id.'/SelectedMailboxes', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getSessions() {
	try {
		$response = $this->client->request('GET', '/v1/RestoreSessions', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]
				);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		}
		
		if ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function getToken() {
	  return($this->token);
  }
  
  public function login($user, $pass) {
	try {
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
				'http_errors' => false,
				'verify' => false
			]
		);

		if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);	
			$this->refreshtoken = $result['refresh_token'];
			$this->token = $result['access_token'];
		} elseif ($response->getStatusCode() === 400) {
			// error - error_description
			return '400';
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['error'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function logout() {
	unset($_SESSION);
	session_destroy();
	header('Location: index.php');
  }
  
  public function refreshToken($refreshtoken) {
	try {
		$response = $this->client->request('POST', '/v1/token', [
				'form_params' => [
					'grant_type' => 'refresh_token',
					'refresh_token' => $refreshtoken
				],
				'headers' => [
					   'Accept' => 'application/json',
					   'Content-Type' => 'application/x-www-form-urlencoded',
					],
				'http_errors' => false,
				'verify' => false
			]
		);

		if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);	
			$this->refreshtoken = $result['refresh_token'];
			$this->token = $result['access_token'];
		} elseif ($response->getStatusCode() === 400) {
			// error - error_description
			return '400';
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['error'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function restoreItem($mid, $rid, $iid, $json) {
	try {
		$response = $this->client->request('POST', '/v1/RestoreSessions/'.$rid.'/organization/mailboxes/'.$mid.'/items/'.$iid.'/action', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/octet-stream',
						'Content-Type'  => 'application/json'
					],
					'http_errors' => false,
					'verify' => false,
					'body' => $json
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			if ($result['createdItemsCount'] == '1') {
				echo 'Item has been restored.';
			} elseif ($result['mergedItemsCount'] == '1') {
				echo 'Item has been restored but has been merged.';
			} elseif ($result['failedItemsCount'] == '1') {
				echo 'Item restore failed.';
			} elseif ($result['skippedItemsCount'] == '1') {
				echo 'Item has been skipped.';
			} else {
				echo 'Unknown error.';
			}
		} else {			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function removeJob($id) {
	try {
		$response = $this->client->delete('/v1/Jobs/'.$id, [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'verify' => false
				]
			);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			echo 'Job has been removed.';
		} else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function removeOrganization($id) {
	try {
		$response = $this->client->delete('/v1/Organizations/'.$id, [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'http_errors' => false,
					'verify' => false
				]
			);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			echo 'Organization has been removed.';
		} else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function removeProxy($id) {
	try {
		$response = $this->client->delete('/v1/Proxies/'.$id, [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'http_errors' => false,
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			echo 'Proxy has been removed.';
		} else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function removeRepo($id) {
	try {
		$response = $this->client->delete('/v1/BackupRepositories/'.$id, [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
					],
					'http_errors' => false,
					'verify' => false
				]
			);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			echo 'Repository has been removed.';
		} else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function setToken($token) {
	  $this->token = $token;
  }
  
  public function startJob($id) {
	try {
		$response = $this->client->request('POST', '/v1/Jobs/'.$id.'/Action', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'http_errors' => false,
					'verify' => false,
					'body' => '{ "start": null }'
				]
			);
				
		if ($response->getStatusCode() === 200) {
			echo 'Job has been started.';
		} else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function startSession() {
	try {
		$response = $this->client->request('POST', '/v1/RestoreSessions/action', [
					'headers' => [
						'Authorization' => 'Bearer ' . $this->token,        
						'Accept'        => 'application/json',
						'Content-Type'  => 'application/json'
					],
					'verify' => false,
					'body' => '{ "explore": { "datetime": "' . gmdate("Y-m-d H:i:s") .'" } }'
				]
			);
			
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 201) {			
			return($result);
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }
  
  public function vexSessionHandler($id, $json) {
	try {
		$response = $this->client->request('POST', '/v1/Organizations/'.$id.'/action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,        
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json
					]
				);
		
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 201) {
			return($result);
		} else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo '<strong>Error:</strong> ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }  
}
?>