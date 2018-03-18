<?php
/* OAuthSimple
  * A simpler version of OAuth
  *
  * author:     jr conlin
  * mail:       src@jrconlin.com
  * copyright:  unitedHeroes.net
  * version:    1.2
  * url:        http://unitedHeroes.net/OAuthSimple
  *
  * Copyright (c) 2010, unitedHeroes.net
  * All rights reserved.
  *
  * Redistribution and use in source and binary forms, with or without
  * modification, are permitted provided that the following conditions are met:
  *     * Redistributions of source code must retain the above copyright
  *       notice, this list of conditions and the following disclaimer.
  *     * Redistributions in binary form must reproduce the above copyright
  *       notice, this list of conditions and the following disclaimer in the
  *       documentation and/or other materials provided with the distribution.
  *     * Neither the name of the unitedHeroes.net nor the
  *       names of its contributors may be used to endorse or promote products
  *       derived from this software without specific prior written permission.
  *
  * THIS SOFTWARE IS PROVIDED BY UNITEDHEROES.NET ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL UNITEDHEROES.NET BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/** Define a custom Exception for easy trap and detection
*/
class OAuthSimpleException extends Exception {}


class OAuthSimple {
    var $_secrets;
    var $_default_signature_method;
    var $_action;
    var $_nonce_chars;

    /* Simple OAuth
     *
     * This class only builds the OAuth elements, it does not do the actual
     * transmission or reception of the tokens. It does not validate elements
     * of the token. It is for client use only.
     *
     * api_key is the API key, also known as the OAuth consumer key
     * shared_secret is the shared secret (duh).
     *
     * Both the api_key and shared_secret are generally provided by the site
     * offering OAuth services. You need to specify them at object creation
     * because nobody <explative>ing uses OAuth without that minimal set of
     * signatures.
     *
     * If you want to use the higher order security that comes from the
     * OAuth token (sorry, I don't provide the functions to fetch that because
     * sites aren't horribly consistent about how they offer that), you need to
     * pass those in either with .signatures() or as an argument to the
     * .sign() or .getHeaderString() functions.
     *
     * Example:
       <code>
       <?php
        $oauthObject = new OAuthSimple();
        $result = $oauthObject->sign(Array('path'=>'http://example.com/rest/',
                                           'parameters'=> 'foo=bar&gorp=banana',
                                           'signatures'=> Array(
                                                'api_key'=>'12345abcd',
                                                'shared_secret'=>'xyz-5309'
                                             )));
        ?>
        <a href="<?php print $result['signed_url']; ?>">Some Link</a>;
       </code>
     *
     * that will sign as a "GET" using "SHA1-MAC" the url. If you need more than
     * that, read on, McDuff.
     */

    /** OAuthSimple creator
     *
     * Create an instance of OAuthSimple
     *
     * @param api_key {string}       The API Key (sometimes referred to as the consumer key) This value is usually supplied by the site you wish to use.
     * @param shared_secret (string) The shared secret. This value is also usually provided by the site you wish to use.
     */
    function OAuthSimple ($APIKey = "",$sharedSecret=""){
        if (!empty($APIKey))
            $this->_secrets{'consumer_key'}=$APIKey;
        if (!empty($sharedSecret))
            $this->_secrets{'shared_secret'}=$sharedSecret;
        $this->_default_signature_method="HMAC-SHA1";
        $this->_action="GET";
        $this->_nonce_chars="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        return $this;
    }

    /** reset the parameters and url
    *
    */
    function reset() {
        $this->_parameters=null;
        $this->path=null;
        $this->sbs=null;
        return $this;
    }

    /** set the parameters either from a hash or a string
    *
    * @param {string,object} List of parameters for the call, this can either be a URI string (e.g. "foo=bar&gorp=banana" or an object/hash)
    */
    function setParameters ($parameters=Array()) {

        if (is_string($parameters))
            $parameters = $this->_parseParameterString($parameters);
        if (empty($this->_parameters))
            $this->_parameters = $parameters;
        elseif (!empty($parameters))
            $this->_parameters = array_merge($this->_parameters,$parameters);
        if (empty($this->_parameters['oauth_nonce']))
            $this->_getNonce();
        if (empty($this->_parameters['oauth_timestamp']))
            $this->_getTimeStamp();
        if (empty($this->_parameters['oauth_consumer_key']))
            $this->_getApiKey();
        if (empty($this->_parameters['oauth_token']))
            $this->_getAccessToken();
        if (empty($this->_parameters['oauth_signature_method']))
            $this->setSignatureMethod();
        if (empty($this->_parameters['oauth_version']))
            $this->_parameters['oauth_version']="1.0";
        return $this;
    }

    // convienence method for setParameters
    function setQueryString ($parameters) {
        return $this->setParameters($parameters);
    }

    /** Set the target URL (does not include the parameters)
    *
    * @param path {string} the fully qualified URI (excluding query arguments) (e.g "http://example.org/foo")
    */
    function setURL ($path) {
        if (empty($path))
            throw new OAuthSimpleException('No path specified for OAuthSimple.setURL');
        $this->_path=$path;
        return $this;
    }

    /** convienence method for setURL
    *
    * @param path {string} see .setURL
    */
    function setPath ($path) {
        return $this->_path=$path;
    }

    /** set the "action" for the url, (e.g. GET,POST, DELETE, etc.)
    *
    * @param action {string} HTTP Action word.
    */
    function setAction ($action) {
        if (empty($action))
            $action = 'GET';
        $action = strtoupper($action);
        if (preg_match('/[^A-Z]/',$action))
            throw new OAuthSimpleException('Invalid action specified for OAuthSimple.setAction');
        $this->_action = $action;
        return $this;
    }

    /** set the signatures (as well as validate the ones you have)
    *
    * @param signatures {object} object/hash of the token/signature pairs {api_key:, shared_secret:, oauth_token: oauth_secret:}
    */
    function signatures ($signatures) {
        if (!empty($signatures) && !is_array($signatures))
            throw new OAuthSimpleException('Must pass dictionary array to OAuthSimple.signatures');
        if (!empty($signatures)){
            if (empty($this->_secrets)) {
                $this->_secrets=Array();
            }
            $this->_secrets=array_merge($this->_secrets,$signatures);
        }
        // Aliases
        if (isset($this->_secrets['api_key']))
            $this->_secrets['consumer_key'] = $this->_secrets['api_key'];
        if (isset($this->_secrets['access_token']))
            $this->_secrets['oauth_token'] = $this->_secrets['access_token'];
        if (isset($this->_secrets['access_secret']))
            $this->_secrets['oauth_secret'] = $this->_secrets['access_secret'];
        if (isset($this->_secrets['access_token_secret']))
            $this->_secrets['oauth_secret'] = $this->_secrets['access_token_secret'];
        if (isset($this->_secrets['rsa_private_key']))
            $this->_secrets['private_key'] = $this->_secrets['rsa_private_key'];
         if (isset($this->_secrets['rsa_public_key']))
            $this->_secrets['public_key'] = $this->_secrets['rsa_public_key'];
        // Gauntlet
        if (empty($this->_secrets['consumer_key']))
            throw new OAuthSimpleException('Missing required consumer_key in OAuthSimple.signatures');
        if (empty($this->_secrets['shared_secret']))
            throw new OAuthSimpleException('Missing requires shared_secret in OAuthSimple.signatures');
        if (!empty($this->_secrets['oauth_token']) && empty($this->_secrets['oauth_secret']))
            throw new OAuthSimpleException('Missing oauth_secret for supplied oauth_token in OAuthSimple.signatures');
        return $this;
    }

    function setTokensAndSecrets($signatures) {
        return $this->signatures($signatures);
    }

    /** set the signature method (currently only Plaintext or SHA-MAC1)
    *
    * @param method {string} Method of signing the transaction (only PLAINTEXT and SHA-MAC1 allowed for now)
    */
    function setSignatureMethod ($method="") {
        if (empty($method))
            $method = $this->_default_signature_method;
        $method = strtoupper($method);
        switch($method)
        {
            case 'RSA-SHA1':
                $this->_parameters['oauth_signature_method']=$method;
                break;
            case 'PLAINTEXT':
            case 'HMAC-SHA1':
                $this->_parameters['oauth_signature_method']=$method;
                break;
            default:
                throw new OAuthSimpleException ("Unknown signing method $method specified for OAuthSimple.setSignatureMethod");
        }
        return $this;
    }

    /** sign the request
    *
    * note: all arguments are optional, provided you've set them using the
    * other helper functions.
    *
    * @param args {object} hash of arguments for the call
    *                   {action, path, parameters (array), method, signatures (array)}
    *                   all arguments are optional.
    */
    function sign($args=array()) {
        if (!empty($args['action']))
            $this->setAction($args['action']);
        if (!empty($args['path']))
            $this->setPath($args['path']);
        if (!empty($args['method']))
            $this->setSignatureMethod($args['method']);
        if (!empty($args['signatures']))
            $this->signatures($args['signatures']);
        if (empty($args['parameters']))
            $args['parameters']=array();        // squelch the warning.
        $this->setParameters($args['parameters']);
        $normParams = $this->_normalizedParameters();
        $this->_parameters['oauth_signature'] = $this->_generateSignature($normParams);
        return Array(
            'parameters' => $this->_parameters,
            'signature' => $this->_oauthEscape($this->_parameters['oauth_signature']),
            'signed_url' => $this->_path . '?' . $this->_normalizedParameters('true'),
            'header' => $this->getHeaderString(),
            'sbs'=> $this->sbs
            );
    }

    /** Return a formatted "header" string
    *
    * NOTE: This doesn't set the "Authorization: " prefix, which is required.
    * I don't set it because various set header functions prefer different
    * ways to do that.
    *
    * @param args {object} see .sign
    */
    function getHeaderString ($args=array()) {
        if (empty($this->_parameters['oauth_signature']))
            $this->sign($args);

        $result = 'OAuth ';

        foreach ($this->_parameters as $pName=>$pValue)
        {
            if (strpos($pName,'oauth_') !== 0)
                continue;
            if (is_array($pValue))
            {
                foreach ($pValue as $val)
                {
                    $result .= $pName .'="' . $this->_oauthEscape($val) . '", ';
                }
            }
            else
            {
                $result .= $pName . '="' . $this->_oauthEscape($pValue) . '", ';
            }
        }
        return preg_replace('/, $/','',$result);
    }

    // Start private methods. Here be Dragons.
    // No promises are kept that any of these functions will continue to exist
    // in future versions.
    function _parseParameterString ($paramString) {
        $elements = explode('&',$paramString);
        $result = array();
        foreach ($elements as $element)
        {
            list ($key,$token) = explode('=',$element);
            if ($token)
                $token = urldecode($token);
            if (!empty($result[$key]))
            {
                if (!is_array($result[$key]))
                    $result[$key] = array($result[$key],$token);
                else
                    array_push($result[$key],$token);
            }
            else
                $result[$key]=$token;
        }
        //error_log('Parse parameters : '.print_r($result,1));
        return $result;
    }

    function _oauthEscape($string) {
        if ($string === 0)
            return 0;
        if (empty($string))
            return '';
        if (is_array($string))
            throw new OAuthSimpleException('Array passed to _oauthEscape');
        $string = rawurlencode($string);
        $string = str_replace('+','%20',$string);
        $string = str_replace('!','%21',$string);
        $string = str_replace('*','%2A',$string);
        $string = str_replace('\'','%27',$string);
        $string = str_replace('(','%28',$string);
        $string = str_replace(')','%29',$string);
        return $string;
    }

    function _getNonce($length=5) {
        $result = '';
        $cLength = strlen($this->_nonce_chars);
        for ($i=0; $i < $length; $i++)
        {
            $rnum = rand(0,$cLength);
            $result .= substr($this->_nonce_chars,$rnum,1);
        }
        $this->_parameters['oauth_nonce'] = $result;
        return $result;
    }

    function _getApiKey() {
        if (empty($this->_secrets['consumer_key']))
        {
            throw new OAuthSimpleException('No consumer_key set for OAuthSimple');
        }
        $this->_parameters['oauth_consumer_key']=$this->_secrets['consumer_key'];
        return $this->_parameters['oauth_consumer_key'];
    }

    function _getAccessToken() {
        if (!isset($this->_secrets['oauth_secret']))
            return '';
        if (!isset($this->_secrets['oauth_token']))
            throw new OAuthSimpleException('No access token (oauth_token) set for OAuthSimple.');
        $this->_parameters['oauth_token'] = $this->_secrets['oauth_token'];
        return $this->_parameters['oauth_token'];
    }

    function _getTimeStamp() {
        return $this->_parameters['oauth_timestamp'] = time();
    }

    function _normalizedParameters($filter='false') {
        $elements = array();
        $ra = 0;
        ksort($this->_parameters);
        foreach ( $this->_parameters as $paramName=>$paramValue) {
           if($paramName=='xml'){
               if($filter=="true")
                   continue;
               }
            if (preg_match('/\w+_secret/',$paramName))
                continue;
            if (is_array($paramValue))
            {
                sort($paramValue);
                foreach($paramValue as $element)
                    array_push($elements,$this->_oauthEscape($paramName).'='.$this->_oauthEscape($element));
                continue;
            }
            array_push($elements,$this->_oauthEscape($paramName).'='.$this->_oauthEscape($paramValue));

        }
        return join('&',$elements);
    }

    function _readFile($filePath) {

           $fp = fopen($filePath,"r");

        $file_contents = fread($fp,8192);

        fclose($fp);

        return $file_contents;
    }

    function _generateSignature () {
        $secretKey = '';
    if(isset($this->_secrets['shared_secret']))
        $secretKey = $this->_oauthEscape($this->_secrets['shared_secret']);
    $secretKey .= '&';
    if(isset($this->_secrets['oauth_secret']))
            $secretKey .= $this->_oauthEscape($this->_secrets['oauth_secret']);
        switch($this->_parameters['oauth_signature_method'])
        {
            case 'RSA-SHA1':

                $publickey = "";
                // Fetch the public key
                if($publickey = openssl_get_publickey($this->_readFile($this->_secrets['public_key']))){

                }else{
                    throw new OAuthSimpleException('Cannot access public key for signing');
                }
                
                $privatekeyid = "";
                // Fetch the private key
                if($privatekeyid = openssl_pkey_get_private($this->_readFile($this->_secrets['private_key'])))
                {
                    // Sign using the key
                     $this->sbs = $this->_oauthEscape($this->_action).'&'.$this->_oauthEscape($this->_path).'&'.$this->_oauthEscape($this->_normalizedParameters());

                       $ok = openssl_sign($this->sbs, $signature, $privatekeyid);

                      // Release the key resource
                    openssl_free_key($privatekeyid);

                       return base64_encode($signature);

                }else{
                    throw new OAuthSimpleException('Cannot access private key for signing');
                }


            case 'PLAINTEXT':
                return urlencode($secretKey);

            case 'HMAC-SHA1':
                $this->sbs = $this->_oauthEscape($this->_action).'&'.$this->_oauthEscape($this->_path).'&'.$this->_oauthEscape($this->_normalizedParameters());
                //error_log('SBS: '.$sigString);
                return base64_encode(hash_hmac('sha1',$this->sbs,$secretKey,true));

            default:
                throw new OAuthSimpleException('Unknown signature method for OAuthSimple');
        }
    }
}
?>