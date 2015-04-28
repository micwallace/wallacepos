XeroOAuth-PHP
-----------------------

PHP library for working with the Xero OAuth API.

Intro
======
XeroOAuth-PHP is a sample library for use with the Xero API (<http://developer.xero.com>). The Xero API uses OAuth 1.0a, but we would not recommend using this library for other OAuth 1.0a APIs as
the Xero API has one of the more advanced implementations (RSA-SHA1, client ssl certs etc) and thus has many configuration options not typically used in other APIs.

This library is designed to get a developer up and running quickly with the OAuth authentication layer, but there will be some customisation of its implementation required before it can be used in a
production environment.

## Requirements
* PHP 5+
* php\_curl extension - ensure a recent version (7.30+)
* php\_openssl extension


## Setup
To get setup, you will need to modify the values in the \_config.php file to your own requirements and application settings.
_Special options for Partner applications_ should be commented out for non-partner applications.

## Usage

There are a number of functions used when interacting with Xero:

#### Make a request
The request function lies at the core of any communication with the API. There are a number of types of requests you may wish to make, all handled by the request() function.

    request($method, $url, $parameters, $xml, $format)

###### Parameters
* Method: the API method to be used (GET, PUT, POST)
* URL: the URL of the API endpoint. This is handled by a special function (see below)
* Parameters: an associative array of parameters such as where, order by etc (see <http://developer.xero.com/documentation/getting-started/http-requests-and-responses/>)
* XML: request data (for PUT and POST operations)
* Format: response format (currently xml, json & pdf are supported). Note that PDF is not supported for all endpoints

#### Generate a URL
Create a properly formatted request URL.

    url($endpoint, $api)

###### Parameters
* Endpoint: the endpoint you wish to work with. Note there are OAuth endpoints such as 'RequestToken' and 'AccessToken' in addition to various API endpoints such as Invoices, Contacts etc. When specifying a resource, such as Invoices/$GUID, you can construct the request by appending the GUID to the base URL.
* API: there are two APIs: core (core accounting API) and payroll (payroll application API). Default is core.

#### Parse the response
Once you get data back, you can pass it through the parseResponse function to turn it into something usable.

    parseResponse($response, $format)

###### Parameters
* Response: the raw API response to be parsed
* Format: xml pdf and json are supported, but you cannot use this function to parse an XML API response as JSON - must correspond to the requested response format.

#### Authorise
For public and partner API type applications using the 3-legged OAuth process, we need to redirect the user to Xero to authorise the API connection. To do so, redirect the user to a url generated with a call like this:

    url("Authorize", '') . "?oauth_token=".$oauth_token."&scope=" . $scope;

###### Appendages
* oauth\_token: this is a request token generated in a prior RequestToken call
* scope: the Payroll API is a permissioned API and required a comma separated list of endpoints the application is requesting access to e.g. $scope = 'payroll.payrollcalendars,payroll.superfunds,payroll.payruns,payroll.payslip,payroll.employees';


#### Refresh an access token
For partner API applications where the 30 minute access tokens can be programatically refreshed via the API, you can use the refreshToken function:

    refreshToken('the access token', 'the session handle')

###### Parameters
* Access token: the current access token
* Session handle: the session identifier handle

## Debug

###### Setup Diagnostics
As you are getting set up, you may run into a few configuration issues, particularly with some of the more advanced application types such as partner.

To make sure your configuration is correct, you can run a diagnostics function:

    diagnostics();

This returns an array of error messages (if there are any). These are in human readable form so should be enough to put you on the right track. If not, check the Xero developer centre and forum for more detail.

It would probably be a bad idea to run this in your production code as the errors returned ones only a developer can resolve, not the end user.

###### Runtime errors

There are many reasons why an error may be encountered: data validation, token issues, authorisation revocation etc. It is important to inspect not just the HTTP response code, but also the associated error string.

A very basic error output function is included in the sample code, which outputs all available information related to an error. It would need to be substantially tidied up before the results could be surfaced in a production environment.

    outputError($object);


## Response Helpers
Understanding the type of message you are getting from the API could be useful. In each response that is not successful, a helper element is returned:

* **TokenExpired:**  This means that the access token has expired. If you are using a partner API type application, you can renew it automatically, or if using a public application, prompt the user to re-authenticate
* **TokenFatal:** In this scenario, a token is in a state that it cannot be renewed, and the user will need to re-authenticate
* **SetupIssue:** There is an issue within the setup/configuration of the connection - check the diagnostics function

## TODO

- [ ] Reading a value from a report
- [x] Better WHERE and ORDER examples
- [ ] Merge OAuthsimple changes for RSA-SHA1 back to parent repo


## License & Credits

This software is published under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).

###### OAuthSimple
OAuthsimple.php contains minor adaptations from the OAuthSimple PHP class by [United Heroes](http://unitedheroes.net/OAuthSimple/).

###### tmhOAuth
XeroOAuth class is based on code and structure derived from the [tmhOAuth](https://github.com/themattharris/tmhOAuth) library.

## Major change history

#### 0.5 - 16th November 2014

Added examples for CRU of tracking categories and options.
Updated the CA certs to a recent one - warning that if you are using a very old version of curl you may get 'cert invalid' type error.
Removed an unused function and tidied up comments on another to make them more sensible.

#### 0.4 - 29th September 2014

Merged some pull requests, addressed an issue with multiple calls having signature validation issues.

#### 0.3 - 3rd January 2014

Merged a number of pull requests, tidied up formatting and extended sample tests.

#### 0.2 - 13th May 2013

Merged to master, added more tests and improved security handling for partner API apps.


#### 0.1 - 10th May 2013

Initial release candidate prepared and released to 'refactor' branch.
