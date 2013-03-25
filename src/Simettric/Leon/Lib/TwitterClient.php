<?php
/**
 * Created by Asier Marqués.
 * User: Asier
 * Date: 22/03/13
 * Time: 17:51
 */


namespace Simettric\Leon\Lib;
use Guzzle\Http\Client;

class TwitterClient{

    const API_URL     = 'https://api.twitter.com/{version}',
          API_VERSION = "1.1";

    private $_client;


    function __construct($oauth_config=array()){


        $client = new Client(static::API_URL, array('version' => static::API_VERSION));
        $client->addSubscriber(new \Guzzle\Plugin\Oauth\OauthPlugin($oauth_config));

        $this->_client = $client;
    }

    function destroy($tweet_id){
        $request = $this->_client->post('statuses/destroy/'.$tweet_id.'.json');

        try{
            $response = $request->send();
        }catch (\Guzzle\Http\Exception\ClientErrorResponseException $e){
            $response = $e->getResponse();
        }

        return $response;
    }

    function updateLimits(\Guzzle\Http\Message\Response $response, &$limit_reset_seconds_count, &$limit_request_remaining){

        if($response->hasHeader("X-Rate-Limit-Remaining")){
            foreach($response->getHeader("X-Rate-Limit-Remaining") as $value){
                $limit_request_remaining     =  (int)$value;
                break;
            }

            foreach($response->getHeader("X-Rate-Limit-Reset") as $value){
                $limit_reset_seconds_count   =  (int)$value - time();
                break;
            }
        }

    }


}
