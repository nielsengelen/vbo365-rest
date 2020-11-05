<?php
require_once('vendor/autoload.php');
set_time_limit(0);

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;

/** Class VBO **/
class VBO {
  private $client;
  private $refreshtoken;
  private $token;

  /**
   * @param $host
   * @param $port
   * @param $version
   */
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

  /**
   * @param $user Username
   * @param $pass Password
   * @return SESSION
   */
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
    unset($_SESSION);
	session_regenerate_id();
    session_destroy();
    header("Refresh:0");
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

  /**
   * @return SESSION
   */
  public function getToken() {
    return($this->token);
  }

  /**
   * @param $token Token
   */
  public function setToken($token) {
    $this->token = $token;
  }
  
  /**
   * @return SESSION 
   */
  public function getRefreshToken() {
    return($this->refreshtoken);
  }
  
  /**
   * @param $refreshtoken Refresh Token
   * @return SESSION 
   */
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
  

  /**
   * @return $result 
   */
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
  
  /**
   * @return $result 
   */
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

  /**
   * @param $id Organization ID
   * @return $result 
   */
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

  /**
   * @param $id Job ID
   * @return $result 
   */
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

  /**
   * @param $id Organization ID
   * @return $result 
   */
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

  /**
   * @param $id Organization ID
   * @return $result 
   */
  public function getLicensedUsers($id, $limit = 50) {
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
  
  /**
   * @return $result 
   */
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

  /**
   * @param $id Organization ID  
   * @return $result 
   */
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
  
  /**
   * @param $rid Restore session ID
   * @return $result 
   */
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
            return '500';
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

  /**
   * @param $id Organization ID
   * @return $result 
   */
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

  /**
   * @param $rid Restore session ID
   * @return $result 
   */
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

  /**
   * @return $result 
   */
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

  /**
   * @param $id Organization ID 
   * @return $result 
   */
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

  /**
   * @return $result 
   */
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

  /**
   * @param $id Repository ID
   * @return $result 
   */
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

  /**
   * @param $id Repository ID
   * @param $oid Organization ID
   * @return $result 
   */
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

  /**
   * @param $id Backup Repository ID
   * @return $result 
   */
  public function getSiteData($id) {
    try {
        $response = $this->client->request('GET', 'BackupRepositories/'.$id.'/SiteData?limit=50', [
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
   * @param $id Backup Repository ID
   * @return $result 
   */
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

  /**
   * @param $id Job ID
   * @param $json JSON format
   * @return $result 
   */
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
  
  /**
   * @param $id Job ID
   * @return string
   */
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

  /**
   * @param $json JSON code
   * @id Organization ID
   * @return $result
   */
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

  /**
   * @param $id Organization ID
   * @return string
   */
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
        } elseif ($response->getStatusCode() === 401) {
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

  /**
   * @param $rid Restore Session ID
   * @return string
   */
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
  
  /**
   * Start Session Log functions
   */
   
  /**
   * @param $id Restore Session ID
   * @return $result 
   */
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

  /**
   * @param $offset Session offset
   * @return $result 
   */
  public function getSessions($offset = 0) {
	try {
        $response = $this->client->request('GET', 'RestoreSessions?offset='.$offset, [
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
   * @return $result 
   */
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

  /**
   * @param $id Job Session ID
   * @return $result 
   */
  public function getBackupSessionLog($id) {
	try {
		$response = $this->client->request('GET', 'JobSessions/'.$id.'/LogItems?limit=250', [
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
   * @return $result 
   */
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

  /**
   * @return $result 
   */
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

  /**
   * @param $rid Restore Session ID
   * @return $result 
   */
  public function getMailboxes($rid, $offset = 0, $limit = 50) {
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
            return '500';
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

  /**
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @return $result 
   */
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
  
  /**
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @return $result 
   */
  public function getMailboxFolders($mid, $rid, $offset = 0, $fid = 'null', $limit = 50) {
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

  /**
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $offset Offset to start from
   * @return $result 
   */
  public function getMailboxItems($mid, $rid, $fid = 'null', $offset = 0, $limit = 50) {
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

  /**
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $json JSON format
   * @return $file 
   */
  public function exportMailbox($mid, $rid, $json) {
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
						'sink' => $resource
					]);

        if ($response->getStatusCode() === 200) {
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
        }
		
		$response->getBody()->detach();
		
		fclose($resource);
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
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $iid Item ID
   * @param $json JSON format
   * @return $file 
   */
  public function exportMailItem($iid, $mid, $rid, $json) {
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
			echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $iid Item ID
   * @param $json JSON format
   * @return $file 
   */
  public function exportMultipleMailItems($iid, $mid, $rid, $json) {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $json JSON
   * @return STRING 
   */
  public function restoreMailbox($mid, $rid, $json) {
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
                
        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $iid Item ID
   * @param $json JSON
   * @return STRING 
   */
  public function restoreMailItem($iid, $mid, $rid, $json) {
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
                
        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
   * @param $rid Restore Session ID
   * @param $mid Mailbox ID
   * @param $json JSON
   * @return STRING 
   */
  public function restoreMultipleMailItems($mid, $rid, $json) {
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

        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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

  /**
   * @param $rid Restore Session ID
   * @return $result 
   */
  public function getOneDrives($rid, $offset = 0, $limit = 50) {
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
            return '500';
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

  /**
   * @param $rid Restore Session ID
   * @param $uid OneDrive User ID
   * @param $offset Offset
   * @param $fid Folder ID
   * @param $limit Limit (default: 50)
   * @return $result 
   */
  public function getOneDriveFolders($rid, $uid, $fid = 'null', $offset = 0, $limit = 50) {
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
  
  /**
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @return $result 
   */
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

  /**
   * @param $rid Restore Session ID
   * @param $uid OneDrive User ID
   * @param $pid Parent ID (null or item ID)
   * @param $type Folders (default) or documents
   * @param $offset Offset
   * @return $result 
   */
  public function getOneDriveTree($rid, $uid, $type = 'Folders', $pid = 'null', $offset = 0, $limit = 50) {
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

   /**
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @param $json JSON format
   * @return $file
   */
  public function exportOneDrive($uid, $rid, $json) {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @param $iid Item ID
   * @param $json JSON format
   * @param $type Folders (default) or documents
   * @return $file
   */
  public function exportOneDriveItem($iid, $uid, $rid, $json, $type = 'Folders') {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @param $iid Item ID
   * @param $json JSON format
   * @param $type Documents
   * @return $file
   */
  public function exportMultipleOneDriveItems($iid, $uid, $rid, $json, $type = 'Documents') {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @param $json JSON format
   * @return $result
   */
  public function restoreOneDrive($uid, $rid, $json) {
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

        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
   * @param $iid Item ID
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @param $json JSON format
   * @param $type Folders (default) or documents
   * @return $result
   */
  public function restoreOneDriveItem($iid, $uid, $rid, $json, $type = 'Folders') {
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

        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
   * @param $rid Restore Session ID
   * @param $uid User ID
   * @param $json JSON format
   * @param $type Documents
   * @return $result
   */
  public function restoreMultipleOneDriveItems($uid, $rid, $json, $type = 'Documents') {
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

        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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

  /**
   * @param $rid Restore Session ID
   * @return $result 
   */
  public function getSharePointSites($rid, $offset = 0, $limit = 50) {
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
            return '500';
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

  /**
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @return $result 
   */
  public function getSharePointContent($rid, $sid, $type, $offset = 0, $limit = 50) {
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
            return '500';
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

  /**
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $offset Offset
   * @param $fid Folder ID
   * @param $limit Limit (default: 50)
   * @return $result 
   */
  public function getSharePointFolders($rid, $sid, $fid = 'null', $offset = 0, $limit = 50) {
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

  /**
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $cid Content ID
   * @param $type Folders (default), items or documents
   * @return $result 
   */
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

  /**
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @return $result 
   */
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

  /**
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $pid Parent Content ID
   * @param $type Folders (default), Items or Documents
   * @param $offset Offset
   * @return $result 
   */
  public function getSharePointItems($rid, $sid, $pid = 'null', $type = 'Folders', $offset = 0, $limit = 50) {
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

  /**
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $json JSON format
   * @return $file
   */
  public function exportSharePoint($sid, $rid, $json) {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $iid Item ID
   * @param $json JSON format
   * @param $type Folders (default) or Documents
   * @return $file
   */
  public function exportSharePointItem($iid, $sid, $rid, $json, $type = 'Folders') {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $iid Item ID
   * @param $json JSON format
   * @param $type Folders or Documents (default)
   * @return $file
   */
  public function exportMultipleSharePointItems($iid, $sid, $rid, $json, $type = 'Documents') {
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
            echo basename($tmpFile);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } else {
			$result = json_decode($response->getBody(), true);
			
			echo $result['message'];
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
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $json JSON format
   * @return $result
   */
  public function restoreSharePoint($sid, $rid, $json) {
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

        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
   * @param $iid Item ID
   * @param $rid Restore Session ID
   * @param $sid SharePoint Site ID
   * @param $json JSON format
   * @param $type Folders (default) or Documents
   * @return $result
   */
  public function restoreSharePointItem($iid, $sid, $rid, $json, $type = 'Folders') {
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
		
		if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
   * @param $rid Restore Session ID
   * @param $sid Site ID
   * @param $json JSON format
   * @param $type Documents (default)
   * @return $result
   */
  public function restoreMultipleSharePointItems($sid, $rid, $json, $type = 'Documents') {
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

        if ($response->getStatusCode() === 200) {
			$result = json_decode($response->getBody(), true);
			
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
}
?>