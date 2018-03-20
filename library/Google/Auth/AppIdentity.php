<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * WARNING - this class depends on the Google App Engine PHP library
 * which is 5.3 and above only, so if you include this in a PHP 5.2
 * setup or one without 5.3 things will blow up.
 */
use google\appengine\api\app_identity\AppIdentityService;

require_once "Google/Auth/Abstract.php";
require_once "Google/Http/Request.php";

/**
 * Authentication via the Google App Engine App Identity service.
 */
class Google_Auth_AppIdentity extends Google_Auth_Abstract
{
  const CACHE_PREFIX = "Google_Auth_AppIdentity::";
  const CACHE_LIFETIME = 1500;
  private $key = null;
  private $client;
  private $token = false;
  private $tokenScopes = false;

  public function __construct(Google_Client $client, $config = null)
  {
    $this->client = $client;
  }

  /**
   * Retrieve an access token for the scopes supplied.
   */
  public function authenticateForScope($scopes)
  {
    if ($this->token && $this->tokenScopes == $scopes) {
      return $this->token;
    }
    $memcache = new Memcached();
    $this->token = $memcache->get(self::CACHE_PREFIX . $scopes);
    if (!$this->token) {
      $this->token = AppIdentityService::getAccessToken($scopes);
      if ($this->token) {
        $memcache_key = self::CACHE_PREFIX;
        if (is_string($scopes)) {
          $memcache_key .= $scopes;
        } else if (is_array($scopes)) {
          $memcache_key .= implode(":", $scopes);
        }
        $memcache->set($memcache_key, $this->token, self::CACHE_LIFETIME);
      }
    }
    $this->tokenScopes = $scopes;
    return $this->token;
  }

  /**
   * Perform an authenticated / signed apiHttpRequest.
   * This function takes the apiHttpRequest, calls apiAuth->sign on it
   * (which can modify the request in what ever way fits the auth mechanism)
   * and then calls apiCurlIO::makeRequest on the signed request
   *
   * @param Google_Http_Request $request
   * @return Google_Http_Request The resulting HTTP response including the
   * responseHttpCode, responseHeaders and responseBody.
   */
  public function authenticatedRequest(Google_Http_Request $request)
  {
    $request = $this->sign($request);
    return $this->io->makeRequest($request);
  }

  public function sign(Google_Http_Request $request)
  {
    if (!$this->token) {
      // No token, so nothing to do.
      return $request;
    }
    // Add the OAuth2 header to the request
    $request->setRequestHeaders(
        array('Authorization' => 'Bearer ' . $this->token['access_token'])
    );

    return $request;
  }
}
