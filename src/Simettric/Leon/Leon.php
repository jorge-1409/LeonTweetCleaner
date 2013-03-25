<?php
/**
 * Created by Asier Marqués.
 * User: Asier
 * Date: 22/03/13
 * Time: 17:40
 */

namespace Simettric\Leon;


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Leon extends Console\Command\Command{

    private $_archive_dir,
            $_silent_mode=false,
            $_twitter_client,
            $_log,
            $_ids=array(),
            $_input,
            $_output;

    function __construct($config_file, $archive_dir, $log_file){

        $this->_archive_dir = $archive_dir;
        $this->_config_file = $config_file;

        $this->_log = new Logger('Leon');
        $this->_log->pushHandler(new StreamHandler($log_file, Logger::INFO));

        parent::__construct();

    }

    protected function configure() {
        $this
            ->setName('simettric:leon')
            ->setDescription('Delete all your tweet archive.')
            ->setDefinition(array(
            new InputOption('config_file', null, InputOption::VALUE_OPTIONAL, 'Config File', $this->_config_file),
            new InputOption('archive_dir', null, InputOption::VALUE_OPTIONAL, 'Archive folder where your tweet js files are located', $this->_archive_dir),
            new InputOption('silent_mode', null, InputOption::VALUE_OPTIONAL, 'Silent mode', false)
        ));
    }

    function execute(InputInterface $input, OutputInterface $output){

        $this->_input  = $input;
        $this->_output = $output;

        $this->_config_file = $input->getOption("config_file");
        $this->_archive_dir = $input->getOption("archive_dir");
        $this->_silent_mode = $input->getOption("silent_mode");

        if(is_file($this->_config_file)){


            if((!$values = parse_ini_file( $this->_config_file, true)) || !isset($values["twitter_oauth"])){
                $this->log( $this->_config_file . " is not a valid config file", "error", true);
            }
        } else $this->log("You need to provide a valid config file", "error", true);


        $this->_twitter_client = new \Simettric\Leon\Lib\TwitterClient($values["twitter_oauth"]);
        $this->_parseArchive($this->_archive_dir);

        $dialog = $this->getHelperSet()->get('dialog');

        $dialog = $this->getHelperSet()->get('dialog');

        if(!$this->tweetIdsCount()){
            $this->log("Zero tweets found in {$this->_archive_dir}. Aborting.", "info", true);
        }

        if ($this->_silent_mode || !$dialog->askConfirmation($output, '<question>'.(sprintf("I´m going to delete %s tweets, abort?", $this->tweetIdsCount())).'</question>', false)) {


            $this->showLN();
            $this->showLN("--");
            $this->destroyTweets();
            $this->showLN("--");
            $this->log("All tweets were deleted.", "info", true);

        }else{

            $this->showLN();
            $this->log("Aborting, any tweet will be deleted", "info", true);
        }



    }

    function tweetIdsCount(){
        return count($this->_ids);
    }

    function showLN($string=""){
        if(!$this->_silent_mode) $this->_output->writeln($string);
    }

    function log($string, $type="info", $die=false){

        $pattern = '%s';
        switch($type){
            case "info":
                $this->_log->addInfo($string);
                $pattern = '<info>%s</info>';
            break;
            case "error":
                $this->_log->addError($string);
                $pattern = '<error>%s</error>';
            break;
            case "warning":
                $this->_log->addWarning($string);
                $pattern = '<comment>%s</comment>';
            break;
        }

        if(!$this->_silent_mode)
            $this->_output->writeln(sprintf($pattern, $string));

        if($die) die("\n");
    }

    function destroyTweets(){
        $wait_seconds = 2;
        $wait_bullets = 10;

        if(is_array($this->_ids)){
            foreach($this->_ids as $id){

                $wait_bullets--;

                $response = $this->_twitter_client->destroy($id);

                if($response instanceof \Guzzle\Http\Message\Response){

                    $limit_reset_seconds_count = 60*15;
                    $limit_request_remaining   = 0;


                    switch($response->getStatusCode()){

                        case "400":
                            $this->log("Bad authentication data. Check your twitter api configuration", "error", true);
                        break;

                        case "401":
                            $this->log("401 Unauthorized", "error");
                        break;

                        case "404":
                            $this->log("Tweet ID:" .$id ." Not found", "warning");
                        break;

                        case "200":
                            $this->log("Tweet ID:" .$id . " deleted!");

                            if(!$wait_bullets){
                                sleep($wait_seconds);
                                $wait_bullets=10;
                            }

                            break;
                    }

                }

            }
        }


    }

    private function _parseArchive($data_dir){
        if(is_dir($data_dir)){


            if(strrpos($data_dir, "/")!==0) $data_dir .= "/";

            $files = scandir($data_dir);



            foreach($files as $filename){

                $file = $data_dir . $filename;

                if($filename != "." && $filename != ".." && is_file($data_dir . $filename) && strrpos($data_dir, ".js")===0){

                    $ids    = array();
                    $parser = new \Simettric\Leon\Lib\TweetArchiveParser($file);

                    try{


                        $ids = $parser->parse();


                    }catch(\Exception $e){
                        $this->log($e->getMessage(), "error", true);
                    }

                    if(is_array($ids)){
                        foreach($ids as $id){ $this->_ids[$id] = $id; };
                    }


                }

            }


        }else{
            $this->log($data_dir . " is not a valid folder", "error", true);
        }
    }


}
