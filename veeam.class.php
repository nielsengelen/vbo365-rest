<?php
require_once('vendor/autoload.php');
set_time_limit(0);

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
            ]
        );

		$result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            $this->refreshtoken = $result['refresh_token'];
            $this->token = $result['access_token'];
        } elseif ($response->getStatusCode() === 400) {
            echo $result['error'] . ': ' . $result['error_description'];
        } else {
            return 'error';
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
    session_destroy();
    header("Refresh:0");
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
                    ]
                );

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
                    ]
                );

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
  public function getJobs($id = NULL) {
    if ($id) {
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
                ]
            );
                
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
  public function getJobSelectedItems($id) {
    try {
        $response = $this->client->request('GET', 'Jobs/'.$id.'/SelectedItems', [
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
                ]
            );

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
                ]
            );

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
  public function getLicensedUsers($id) {
    try {
        $response = $this->client->request('GET', 'LicensedUsers?organizationId='.$id, [
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
                    ]
                );

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
                    ]
                );

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
                    ]
                );
                
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
                ]
            );

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
                    ]
                );
                
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
                    ]
                );

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
                    ]
                );

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
                ]
            );

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
                ]
            );

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
        $response = $this->client->request('GET', 'BackupRepositories/'.$id.'/SiteData', [
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
                    ]
                );
                
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
                    ]
                );

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
                ]
            );

        if ($response->getStatusCode() === 200) {
            echo 'Job has been started.';
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
   * @param $json JSON code for Exchange, OneDrive or SharePoint
   * @id Organization ID
   * @return $result
   */
  public function startRestoreSession($json, $id = NULL) {
    if ($id) { /* Used for admin restores */
        $call = 'Organizations/'.$id.'/Action';
    } else { /* Used for tenant restores */
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
                ]
            );

        $result = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() === 201) {
            return($result);
        } elseif ($response->getStatusCode() === 401) {
            $this->logout();
        } elseif ($response->getStatusCode() === 500) {
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
   * @param $json JSON
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
                    ]
                );

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
  public function getSessions($offset = null) {
	$call = 'RestoreSessions';

	if (isset($offset)) {
		$call .= '?offset=' . $offset;
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
					]
				);
				
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
		$response = $this->client->request('GET', 'JobSessions/'.$id.'/LogItems?limit=1000', [
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
					]
				);
				
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
					]
				);
				
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
  public function getMailbox($rid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/?offset=0&limit=1000', [
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
  public function getMailboxID($rid, $uid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$uid, [
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
  public function getMailboxFolders($mid, $rid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/folders?limit=1000', [
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
  public function getMailboxItems($mid, $rid, $fid = null, $offset = null) {
    if (isset($fid) || (strcmp($fid, 'null') !== 0)) {
        $call = 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items?folderId='.$fid;

        if (isset($offset)) {
            $call .= '&offset=' . $offset . '&limit=30';
        }
    } else {
        $call = 'RestoreSessions/'.$rid.'/Organization/Mailboxes/'.$mid.'/Items';
        
        if (isset($offset)) {
            $call .= '?offset=' . $offset . '&limit=30';
        }
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
    $tmpFile  = sys_get_temp_dir() . '/' . $mid;
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
                ]
            );

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
  public function exportMailItem($iid, $mid, $rid, $json) {
    $tmpFile  = sys_get_temp_dir() . '/' . $iid;
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
                ]
            );

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
   * @param $iid File name for export
   * @param $json JSON format
   * @return $file 
   */
  public function exportMultipleMailItems($iid, $mid, $rid, $json) {
    $tmpFile  = sys_get_temp_dir() . '/' . $iid;
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
                ]
            );

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
                ]
            );
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['createdItemsCount'] >= '1') {
                echo 'Mailbox has been restored.';
            } elseif ($result['mergedItemsCount'] == '1') {
                echo 'Item has been restored and has been merged.';
            } elseif ($result['failedItemsCount'] == '1') {
                echo 'Item restore failed.';
            } elseif ($result['skippedItemsCount'] == '1') {
                echo 'Item has been skipped.';
            } else {
                echo 'Restore failed.';
            }
        } else {
            echo 'Restore failed: ' . $result['message'];
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
                ]
            );
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['createdItemsCount'] == '1') {
                echo 'Item has been restored.';
            } elseif ($result['mergedItemsCount'] == '1') {
                echo 'Item has been restored and has been merged.';
            } elseif ($result['failedItemsCount'] == '1') {
                echo 'Item restore failed.';
            } elseif ($result['skippedItemsCount'] == '1') {
                echo 'Item has been skipped.';
            } else {
                echo 'Restore failed: ' . $result['message'];
            }
        } else {
            echo 'Restore failed: ' . $result['message'];
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
                ]
            );
                
        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['createdItemsCount'] >= '1') {
                echo 'Items have been restored.';
            } elseif ($result['mergedItemsCount'] == '1') {
                echo 'Items have been restored and have been merged.';
            } elseif ($result['failedItemsCount'] == '1') {
                echo 'Items restore failed.';
            } elseif ($result['skippedItemsCount'] == '1') {
                echo 'Items have been skipped.';
            } else {
                echo 'Restore failed: ' . $result['message'];
            }
        } else {
            echo 'Restore failed: ' . $result['message'];
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
  public function getOneDrives($rid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/OneDrives?offset=0&limit=1000', [
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
                ]
            );
            
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
   * @param $parent Request parent folder - true or false
   * @return $result 
   */
  public function getOneDriveParentFolder($rid, $uid, $type = 'Folders', $pid) {
    $call = 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type.'/'.$pid;

    try {
        $response = $this->client->request('GET', $call, [
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
  public function getOneDriveTree($rid, $uid, $type = 'Folders', $pid = null, $offset = null) {
    $call = 'RestoreSessions/'.$rid.'/Organization/OneDrives/'.$uid.'/'.$type;

    if (isset($pid)) {
        $call .= '?parentID=' . $pid;
        
        if (isset($offset)) {
            $call .= '&offset=' . $offset;
        }
    } else {
        $call .= '?parentID=null';

        if (isset($offset)) {
            $call .= '&offset=' . $offset;
        }
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
                ]
            );

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
    $tmpFile  = sys_get_temp_dir() . '/' . $uid;
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
                ]
            );

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
    $tmpFile  = sys_get_temp_dir() . '/' . $iid;
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
                ]
            );

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
    $tmpFile  = sys_get_temp_dir() . '/' . $iid;
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
                ]
            );

        fclose($resource);
        
        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
                ]
            );

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['restoredItemsCount'] >= '1') {
                echo 'Item has been restored.';
            } else {
                echo 'Failed to restore the item.';
            }
        } else {
            echo 'Restore failed: ' . $result['message'];
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
                ]
            );

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['restoredItemsCount'] >= '1') {
                echo 'Item has been restored.';
            } else {
                echo 'Failed to restore the item.';
            }
        } else {
            echo $response->getStatusCode() . ' Restore failed: ' . $result['message'];
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
                ]
            );

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['restoredItemsCount'] >= '1') {
                echo 'Items have been restored.';
            } else {
                echo 'Failed to restore the items.';
            }
        } else {
            echo $response->getStatusCode() . ' Restore failed: ' . $result['message'];
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
  public function getSharePointSites($rid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites?offset=0&limit=1000', [
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
  public function getSharePointLists($rid, $sid) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/Lists?offset=0&limit=1000', [
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
  public function getSharePointContent($rid, $sid, $type) {
    try {
        $response = $this->client->request('GET', 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type, [
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
                ]
            );

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
                ]
            );

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
   * @param $type Folders (default), items or documents
   * @param $offset Offset
   * @return $result 
   */
  public function getSharePointTree($rid, $sid, $pid, $type = 'Folders', $offset = null) {
    $call = 'RestoreSessions/'.$rid.'/Organization/Sites/'.$sid.'/'.$type.'?parentId='.$pid;

    if (isset($offset)) {
        $call .= '&offset=' . $offset;
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
                ]
            );

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
   * @param $iid Item ID
   * @param $json JSON format
   * @param $type Folders (default) or documents
   * @return $file
   */
  public function exportSharePoint($sid, $rid, $json) {
    $tmpFile  = sys_get_temp_dir() . '/' . $sid;
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
                ]
            );

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
   * @param $type Folders (default) or documents
   * @return $file
   */
  public function exportSharePointItem($iid, $sid, $rid, $json, $type = 'Folders') {
    $tmpFile  = sys_get_temp_dir() . '/' . $iid;
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
                ]
            );

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
   * @param $type Folders (default) or documents
   * @return $file
   */
  public function exportMultipleSharePointItems($iid, $sid, $rid, $json, $type = 'Documents') {
    $tmpFile  = sys_get_temp_dir() . '/' . $iid;
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
                ]
            );

        fclose($resource);

        if ($response->getStatusCode() === 200) {
            echo $tmpFile;
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
                ]
            );

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if (count($result['restoreIssues']) >= '1') {
                echo 'SharePoint site has been restored with warnings.';
            } elseif ($result['failedWebsCount'] >= '1') {
				echo 'Failed to restore the SharePoint site.';
			} elseif ($result['failedRestrictionsCount'] >= '1') {
				echo 'Failed to restore the SharePoint site due to restrictions errors.';
			} else {
                echo 'SharePoint site has been restored.';
            }
        } else {
            echo 'Restore failed: ' . $result['message'];
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
   * @param $type Folders (default) or documents
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
                ]
            );

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['restoredItemsCount'] >= '1') {
                echo 'Item has been restored.';
            } elseif ($result['skippedItemsByNoChangesCount'] == '1') {
                echo 'Failed to restore the item: items already exists with the same permissions.';
            } elseif ($result['failedRestrictionsCount'] == '1') {
                echo 'Failed to restore the item: permission denied to restore the item with this account.';
            }  else {
                echo 'Failed to restore the item.';
            }
        } else {
            echo 'Restore failed: ' . $result['message'];
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
   * @param $type Documents
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
                ]
            );

        $result = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 200) {
            if ($result['restoredItemsCount'] >= '1') {
                echo 'Items have been restored.';
            } else {
                echo 'Failed to restore the items.';
            }
        } else {
            echo $response->getStatusCode() . ' Restore failed: ' . $result['message'];
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