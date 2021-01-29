<?php
set_time_limit(0);

require_once('config.php');
require_once('vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;

class VBO {
  private $client;
  private $refreshtoken;
  private $token;
  
  public function __construct($host, $port, $version) {
    try {
        $this->client = new Client([
            'base_uri' => 'https://'.$host.':'.$port.'/'.$version.'/',
            'connect_timeout' => 30,
            'http_errors' => false
        ]);
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function login($user, $pass) {
    try {
        $response = $this->client->request('POST', 'token', [
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
					]);

		$result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            $this->refreshtoken = $result['refresh_token'];
            $this->token = $result['access_token'];
        } elseif ($response->getStatusCode() === 400) {
            return 0;
        } else {
            return '<strong>' . $result['error'] . ':</strong> ' . $result['error_description'];
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['error'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function MFALogin($tenantid, $assertion) {
	try {
		$response = $this->client->request('POST', 'token', [
						'form_params' => [
							'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
							'client_id' => $tenantid,
							'assertion' => $assertion
						],
						'headers' => [
						   'Accept' => 'application/json',
						   'Content-Type' => 'application/x-www-form-urlencoded',
						],
						'http_errors' => false,
						'verify' => false
					]);
					
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
            $this->refreshtoken = $result['refresh_token'];
            $this->token = $result['access_token'];
        } elseif ($response->getStatusCode() === 400) {
            return 0;
        } else {
            return '<strong>' . $result['error'] . ':</strong> ' . $result['error_description'];
        }
	} catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['error'];
        } else {
            echo $e->getMessage();
        }
    }	
  }

  public function logout() {
    if (session_status() != PHP_SESSION_NONE) {
		unset($_SESSION);
		session_regenerate_id();
		session_destroy();
		header('Refresh: 1; URL=index.php');
	}
  }

  function alphanum($string, $x = '') {
	$h = strlen($string);
	
	for ($a = 0; $a < $h; $a++) {
		$i = ord($string[$a]);
		
		if(($i==45) || ($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123)) { 
			$x .= $string[$a]; 
		}
	}
	
	return $x;
  }

  public function getToken() {
    return($this->token);
  }

  public function setToken($token) {
    $this->token = $token;
  }
  
  public function getRefreshToken() {
    return($this->refreshtoken);
  }
  
  public function refreshToken($refreshtoken) {
    try {
        $response = $this->client->request('POST', 'token', [
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

		$result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            $this->refreshtoken = $result['refresh_token'];
            $this->token = $result['access_token'];
        } elseif ($response->getStatusCode() === 400) {
            return '400';
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['error'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function getBackupRepositories() {
    try {
        $response = $this->client->request('GET', 'BackupRepositories', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function getObjectStorageRepositories() {
    try {
        $response = $this->client->request('GET', 'ObjectStorageRepositories', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getJobs($id = null) {
    if (isset($id)) {
        $call = 'Organizations/'.$id.'/Jobs';
    } else {
        $call = 'Jobs/';
    }

    try {
        $response = $this->client->request('GET', $call, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getJobSession($id) {
    try {
        $response = $this->client->request('GET', 'Jobs/'.$id.'/JobSessions', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getLicenseInfo($id) {
    try {
        $response = $this->client->request('GET', 'Organizations/'.$id.'/LicensingInformation', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getLicensedUsers($id, $limit = null) {
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'LicensedUsers?organizationId='.$id.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function getOrganization() {
    try {
        $response = $this->client->request('GET', 'Organization', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOrganizationByID($id) {
    try {
        $response = $this->client->request('GET', 'Organizations/'.$id, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function getOrganizationID($rid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('sessionStatus' => 'Restore session has expired');
        } else {
			echo $response->getStatusCode() . ' - ' . $result['message'];
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOrganizationJobs($id) {
    try {
        $response = $this->client->request('GET', 'Organizations/'.$id.'/Jobs', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOrganizationRepository($id) {
    try {
        $response = $this->client->request('GET', 'Organizations/'.$id.'/usedRepositories', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOrganizations() {
    try {
        $response = $this->client->request('GET', 'Organizations', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOrganizationUsers($id) {
    try {
        $response = $this->client->request('GET', 'Organizations/'.$id.'/Users', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getProxies() {
    try {
        $response = $this->client->request('GET', 'Proxies', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getProxy($id) {
    try {
        $response = $this->client->request('GET', 'Proxies/'.$id, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOrganizationData($id) {
    try {
        $response = $this->client->request('GET', 'BackupRepositories/'.$id.'/OrganizationData', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSiteData($id) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'BackupRepositories/'.$id.'/SiteData?limit='.$limit, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getTeamData($id) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'BackupRepositories/'.$id.'/TeamData?limit='.$limit, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getUserData($id, $uid) {
    try {
        $response = $this->client->request('GET', 'BackupRepositories/'.$id.'/UserData/'.$uid, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function changeJobState($id, $json) {
    try {
        $response = $this->client->request('POST', 'Jobs/'.$id.'/Action', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json'
                        ],
                        'verify' => false,
                        'body' => $json,
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function startJob($id) {
    try {
        $response = $this->client->request('POST', 'Jobs/'.$id.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => '{ "start": null }'
					]);
					
		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);
            
            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getRestoreSession($rid) {
	  try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json'
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 400) {
			$this->logout();
		}  elseif ($response->getStatusCode() === 401) {
			$this->logout();
		} else {
			$error = array('error' => $result['message']);
		
            return($error);
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }


  public function startRestoreSession($json, $id = null) {
    if (isset($id)) {
        $call = 'Organizations/'.$id.'/Action';
    } else {
        $call = 'Organization/Action';
    }

    try {
        $response = $this->client->request('POST', $call, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => true,
						'verify' => false,
						'body' => $json
					]);

        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 201) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function stopRestoreSession($rid) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Action', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json'
                        ],
                        'http_errors' => false,
                        'verify' => false,
                        'body' => '{ "stop": null }'
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 400) {
			$this->logout();
		}  elseif ($response->getStatusCode() === 401) {
			$this->logout();
		} else {
			$error = array('error' => $result['message']);
		
            return($error);
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getRestoreDeviceCode($rid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/organization/restoreDeviceCode', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                            'Content-Type'  => 'application/json'
                        ],
                        'http_errors' => false,
                        'verify' => false,
						'body' => $json
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
			$this->logout();
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }  
    
  public function getSessionLog($id) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$id.'/Events', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);
            
            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSessions($offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
	try {
        $response = $this->client->request('GET', 'RestoreSessions?offset='.$offset.'&limit='.$limit, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->token,
                            'Accept'        => 'application/json',
                        ],
                        'http_errors' => false,
                        'verify' => false
                    ]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function getBackupSessions() {
	try {
		$response = $this->client->request('GET', 'JobSessions', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		} elseif ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo 'Error: ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  public function getBackupSessionLog($id) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
	try {
		$response = $this->client->request('GET', 'JobSessions/'.$id.'/LogItems?limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		} elseif ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo 'Error: ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  public function getRestoreSessions() {
	try {
		$response = $this->client->request('GET', 'RestoreSessions', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		} elseif ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo 'Error: ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  public function getRestoreSessionEvents($id) {
	try {
		$response = $this->client->request('GET', 'RestoreSessions/'.$id.'/Events', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);
				
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
		} elseif ($response->getStatusCode() === 401) {
			$this->logout();
		}
	} catch (RequestException $e) {
		if ($e->hasResponse()) {
			$exception = (string) $e->getResponse()->getBody();
			$exception = json_decode($exception, true);
			
			echo 'Error: ' . $exception['message'];
		} else {
			echo $e->getMessage();
		}
    }
  }

  /**
   * Start Exchange functions
   */

  public function getMailboxes($rid, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/?offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('sessionStatus' => 'Restore session has expired');
        } else {
			echo $response->getStatusCode() . ' - ' . $result['message'];
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getMailboxID($rid, $mid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);
            
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }  
  
  public function getMailboxFolders($rid, $mid, $fid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/folders?limit='.$limit.'&parentId='.$fid.'&offset='.$offset, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);
            
            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getMailboxItems($rid, $mid, $fid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items?folderId='.$fid.'&offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
						],
						'http_errors' => false,
						'verify' => false
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);
            
            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportMailbox($rid, $mid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($mid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);
					
		fclose($resource);
	
        if ($response->getStatusCode() === 200) {
			$file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('exportFailed' => '64-bit version of Microsoft Outlook 2010 or later is not installed');
			
			return($error);
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportMailFolder($rid, $mid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Folders/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
			$file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportMailItem($rid, $mid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
			$file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportMultipleMailItems($rid, $mid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
			$file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreMailbox($rid, $mid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
   
  public function restoreMailItem($rid, $mid, $iid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json
					]);
        
		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreMailFolder($rid, $mid, $iid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Folders/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreMultipleMailItems($rid, $mid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  /**
   * Start OneDrive for Business functions
   */

  public function getOneDrives($rid, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/OneDrives?offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('sessionStatus' => 'Restore session has expired');
        } else {
			echo $response->getStatusCode() . ' - ' . $result['message'];
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOneDriveFolders($rid, $uid, $fid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/Folders?limit='.$limit.'&parentID='.$fid.'&offset='.$offset, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function getOneDriveID($rid, $uid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);
            
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getOneDriveTree($rid, $uid, $type = 'Folders', $pid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type.'?limit='.$limit.'&parentID='.$pid.'&offset='.$offset, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportOneDrive($rid, $uid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($uid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportOneDriveItem($rid, $uid, $iid, $json, $type = 'Folders') {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type.'/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportMultipleOneDriveItems($rid, $uid, $iid, $json, $type = 'Documents') {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreOneDrive($rid, $uid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreOneDriveItem($rid, $uid, $iid, $json, $type = 'Folders') {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type.'/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);
					
		$result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreMultipleOneDriveItems($rid, $uid, $json, $type = 'Documents') {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  /**
   * Start SharePoint functions
   */

  public function getSharePointSites($rid, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites?offset='.$offset.'&limit='.$limit.'&parentId=null', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('sessionStatus' => 'Restore session has expired');
        } else {
			echo $response->getStatusCode() . ' - ' . $result['message'];
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSharePointContent($rid, $sid, $type, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'?limit='.$limit.'&offset='.$offset, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('sessionStatus' => 'Restore session has expired');
        } else {
			echo $response->getStatusCode() . ' - ' . $result['message'];
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSharePointFolders($rid, $sid, $fid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/Folders?parentId='.$fid.'&offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {		
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSharePointListName($rid, $sid, $cid, $type = 'Folders') {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'/'.$cid, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSharePointSiteName($rid, $sid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getSharePointItems($rid, $sid, $pid = 'null', $type = 'Folders', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'?parentId='.$pid.'&offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {		
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportSharePoint($rid, $sid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($sid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportSharePointItem($rid, $sid, $iid, $json, $type = 'Folders') {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportMultipleSharePointItems($rid, $sid, $iid, $json, $type = 'Documents') {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreSharePoint($rid, $sid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);
					
		$result = json_decode($response->getBody(), true);
	
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  } 
  
  public function restoreSharePointItem($rid, $sid, $iid, $json, $type = 'Folders') {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);

		if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }	
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreMultipleSharePointItems($rid, $sid, $json, $type = 'Documents') {
   try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  /**
   * Start Teams functions
   */

  public function getTeams($rid, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Teams?offset='.$offset.'&limit='.$limit.'&parentId=null', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
            $error = array('sessionStatus' => 'Restore session has expired');
        } else {
			echo $response->getStatusCode() . ' - ' . $result['message'];
		}
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getTeamsChannels($rid, $tid, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/teams/'.$tid.'/channels?offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {		
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getTeamsFiles($rid, $tid, $cid = 'null', $pid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
	$call = 'RestoreSessions/'.$rid.'/Organization/teams/'.$tid.'/files?channelId='.$cid.'&offset='.$offset.'&limit='.$limit;
	
	if ($pid !== 'null') {
		$call .= $call . '&parentId='.$pid;
	}
	
    try {
        $response = $this->client->request('GET', $call, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {		
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getTeamsPosts($rid, $tid, $cid = 'null', $pid = 'null', $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/teams/'.$tid.'/posts?channelId='.$cid.'&parentId='.$pid.'&offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {		
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function getTeamsTabs($rid, $tid, $cid, $offset = 0) {
	global $limit;
	
	if (!isset($limit)) $limit = 250;
	
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/teams/'.$tid.'/channels/'.$cid.'/tabs?offset='.$offset.'&limit='.$limit, [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
					]);

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {		
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportTeamsFile($rid, $tid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/files/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportTeamsMultipleFiles($rid, $tid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/files/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportTeamsChannelFiles($rid, $tid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/files/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportTeamsPost($rid, $tid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/posts/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function exportTeamsMultiplePosts($rid, $tid, $iid, $json) {
    $tmpFile  = tempnam(sys_get_temp_dir(), $this->alphanum($iid));
    $resource = fopen($tmpFile, 'w');

    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/posts/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/octet-stream',
							'Content-Type'  => 'application/json'
						],
						'verify' => false,
						'body' => $json,
						'sink' => $resource,
					]);

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            $file = array('exportFile' => basename($tmpFile));
			
			return($file);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			$error = array('exportFailed' => $result['message']);
			
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreTeam($rid, $tid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = array('restoreFailed' => $result['message']);
		
            return($result);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function restoreTeamsChannel($rid, $tid, $cid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/Channels/'.$cid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
  
  public function restoreTeamsFile($rid, $tid, $iid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/files/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);
		
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }	
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreTeamsMultipleFiles($rid, $tid, $json) {
   try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/files/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreTeamsMultiplePosts($rid, $tid, $json) {
   try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/posts/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);

		$result = json_decode($response->getBody(), true);
		
        if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
			return($error);
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreTeamsTab($rid, $tid, $cid, $iid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/channels/'.$cid.'/tabs/'.$iid.'/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);
		
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }	
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }

  public function restoreTeamsMultipleTabs($rid, $tid, $cid, $json) {
    try {
        $response = $this->client->request('POST', 'RestoreSessions/'.$rid.'/Organization/Teams/'.$tid.'/channels/'.$cid.'/tabs/Action', [
						'headers' => [
							'Authorization' => 'Bearer ' . $this->token,
							'Accept'        => 'application/json-stream',
							'Content-Type'  => 'application/json'
						],
						'http_errors' => false,
						'verify' => false,
						'body' => $json,
					]);
		
		$result = json_decode($response->getBody(), true);
		
		if ($response->getStatusCode() === 200) {
			return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$error = array('restoreFailed' => $result['message']);
		
            return($error);
        }	
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $exception = (string) $e->getResponse()->getBody();
            $exception = json_decode($exception, true);

            echo 'Error: ' . $exception['message'];
        } else {
            echo $e->getMessage();
        }
    }
  }
}
?>