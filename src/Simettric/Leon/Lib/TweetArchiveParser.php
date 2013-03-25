<?php
/**
 * Created by Asier Marqués.
 * User: Asier
 * Date: 22/03/13
 * Time: 17:43
 */

namespace Simettric\Leon\Lib;


class TweetArchiveParser{

    private $_file, $_content;
    public $tweet_ids = array();

    function __construct($file){

        $this->_file = $file;
        $this->_content = file_get_contents($file);

    }

    function parse(){


        $content    = trim(substr($this->_content, strpos($this->_content, "["), strlen($this->_content)));
        if($content = json_decode($content, true)){


            if(is_array($content)){
                foreach($content as $tweet){
                    if(!isset($tweet["id"])) continue;
                    array_push($this->tweet_ids, $tweet["id"]);
                }
            }


            return $this->tweet_ids;


        }else throw new \Exception("Incorrect file format, check the file " . $this->_file . ". Aborting..");

    }

}
