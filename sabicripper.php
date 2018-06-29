<?php 
/**
 * Script By Ervin Sabic
 * Published under GPL
 * Feel free to do whatever you please with this script.
 * Website: www.gearsite.net 
 * Desc: PHP CLI Script that is used to pull data from a website. Useful for when you need to move hundreds of images and want to organize them. 
 */
    class Ripper {

        public $galleryLinks = [];

        public $sourceLinks = [];

        public $dom;

        public $debug;

        public $data;

        public $dir;

        public function __construct($args, $count, $debug = false){
            $this->dom = new DomDocument();
            $this->source = $args[1];
            $this->dir = $args[2];
            $this->count = $count;
            $this->timeout = 30;
        }

        public function init(){
            echo 
            "
            _____       _     _       ______ _                       \n
            /  ___|     | |   (_)      | ___ (_)                      \n
            \ `--.  __ _| |__  _  ___  | |_/ /_ _ __  _ __   ___ _ __ \n
             `--. \/ _` | '_ \| |/ __| |    /| | '_ \| '_ \ / _ \ '__|\n
            /\__/ / (_| | |_) | | (__  | |\ \| | |_) | |_) |  __/ |   \n
            \____/ \__,_|_.__/|_|\___| \_| \_|_| .__/| .__/ \___|_|   \n
                                               | |   | |              \n
                                               |_|   |_|           \n
            ";

            if($this->count > 3 || $this->count < 3){
                if($this->count > 3) $this->throwError("Too Many Arguments", false);
                if($this->count < 3) $this->throwError("Too Few Arguments", false);
                if($this->debug){
                    echo "Displaying Passed Parameters: \n";
                    var_dump($argv);
                }
                echo "CLI Usage: \nphp ".$argv[0]." http://www.example.com /MyDirectoryThatIWantImagesIn \n";
                die();
            }else {
                echo "Source: ".$this->source ."\n";
                echo "Directory: ".$this->dir ."\n";
            }
        }


        /**
         * --------------------
         * 
         * SCRAPING METHODS
         * 
         * --------------------
         */


        /**
         * @param string $source 
         * Returns HTML using PhantomJS
         */
        public function scrapeHtml($source) {
            echo "Attempting to connect to ".$source."...\n";
            file_put_contents("includes/page.json", json_encode($source));
            $raw = shell_exec("phantomjs includes/scrape.js 2>&1");
            if($raw == "failed" || $raw == ""){
                $this->throwError("Error Getting Page ".$source."! \n Check to see if it is a valid URL. Include the http:// or https:// if you didn't.");
            }else {
                echo "Page found!\n";
            }
            return $raw;
        }

        /**
         * @param string $html
         * Returns an array of src links pulled from IMG tags using the DOM
         */
        public function scrapeImageSources($html){
            $ret = [];
            $this->dom->loadHtml($html);
            echo "Attempting to rip image source data from given html. Standby... \n";
            foreach($this->dom->getElementsByTagName("img") as $found){
                $src = $found->getAttribute('src');
                echo "Found: ".$src."\n";
                array_push($ret, $src);
            }
            return $ret;
        }

        /**
         * @param array $locations
         * Downloads images at the specified locations into the directory specified in the constructor. 
         */
        public function scrapeImages($locations){
            echo "Attempting to download images from given source data. Standby... \n";
            foreach($locations as $location){
                echo "Scraping: ".$location;
                $fname = basename($location);
                //$this->downloadFile($location, $this->formatDirectory($this->dir).$fname);
                file_put_contents($this->formatDirectory($this->dir).$fname,$this->downloadFile($location));
            }
        }


        /**
         * @param string $path 
         * Checks to see if a file exists and is readable then if it is, downloads it. 
         */
        public function downloadFile($path){
            if(!file_exists($path)){
                echo "File does not exist! \n";
            }
            if(!is_readable($path)){
                echo "File is not readable! \n";
            };
            return file_get_contents(trim($path));
        }
        
        /**
         * @param array $data 
         * Dumps current data into a .json file
         */
        public function dumpJSON($data){
            echo "Dumping JSON... Standby... \n"; 
            $data = json_encode($data);
            file_put_contents("data.json", $data);
            echo "JSON Successfully Saved!";
        }

        /**
         * --------------------
         * 
         * INPUT RELATED METHODS
         * 
         * --------------------
         */


        /**
         * @param string $prompt
         * Asks for user input via CLI
         */
        public function requestInput(string $prompt, string $inputType, $options){
            $input = readline($prompt);
            if($input == "Exit"){
                die();
            }
            elseif($input == "Help"){
                echo "Nobody can help you here! Muahahahahahah";
            }
            else {
                return $this->checkInput($input, $inputType, $options);
            }

        }

        /**
         * @param string $input
         * @param string $type 
         * Checks the users input based on type.
         */
        public function checkInput(string $input, string $type, array $options = [])
        {
            if($type == "Y/N"){
                if($input == "Y"){
                    return True;
                }elseif($input == "N"){
                    return False;
                }
                else {
                    return $this->throwError("Invalid Input! Must be Y or N.");
                }
            }
            elseif($type == "Open"){
                if($options == []) $this->throwError("Bad input options exception! Terminating...");
                if (in_array($input, $options)){
                    return true;
                }
                else {
                    $this->throwError($input." is not a valid option!", false);
                    echo "Valid Options are: \n";
                    foreach($options as $item){
                        echo $item."\n";
                    }
                    die();
                }
            }
            else {
                return $this->throwError("Bad input type exception. Terminating");
            }
        }
        /**
         * --------------------
         * 
         * FILTERING RELATED METHODS
         * 
         * --------------------
         */

        /**
        * @param string $input 
        * @param array $subject
        * Applies the necessary filter depending on the input.
        */
        public function selectFilter($input, $subject){
            $acceptedFilters = [
                "Unique"=>$this->uniqueFilter($subject),
                "Contain"=>$this->containFilter($subject),
                "Exclude"=>$this->excludeFilter($subject),
                "None"=>$this->returnSubject($subject),
            ];
            if(array_key_exists($input,$acceptedFilters)){
                $this->$acceptedFilters[$input]();
            }else{
                $input($subject);
            }
        }

        /**
         * @param array $subject 
         * Filters data depending on if value contains a string. Removes those that don't.
         */
        public function containFilter($subject){
            $condition = readline("What is the data supposed to contain?");
            foreach($subject as $key=>$value){
                if(strpos($value, $condition) !== false){
                    continue;
                }else {
                    echo $value. " has been filtered out. Does not contain the string ".$condition."\n";
                    unset($subject[$key]);
                }
            }
            return $subject;
        }

        /**
         * @param array $subject 
         * Filters data depending on if value contains a string. Removes those that do. 
         */
        public function excludeFilter($subject){
            $condition = readline("What string should be excluded from the data?");
            foreach($subject as $key=>$value){
                if(strpos($subject, $condition) === false){
                    continue; 
                }else {
                    unset($subject[$key]);
                }
            }
            return $subject;
        }

        /**
         * @param array $subject 
         * Filters data to make each entry unique.
         */
        public function uniqueFilter($subject){
            $subject = array_unique($subject);
            return $subject;
        }


        /**
         * @param array $subject 
         * @param string $url 
         * Filters data that does not start with $url
         */
        public function externalLinkFilter($subject, $url){
            foreach($subject as $key=>$value){
                if(strpos($value, $url) === 0){
                    continue;
                }else {
                    echo "Removing: ".$value." does not start with ".$url."\n";
                    unset($subject[$key]);
                }
            }
            return $subject;
        }
        /**
         * --------------------
         * 
         * UTILITY METHODS
         * 
         * --------------------
         */

        /**
        * View current data
        * @param boolean $request
        */
        public function viewData($request = false){
            if($request == false){
                return var_dump($this->data);                
            }else {
                $option = $this->requestInput("Would you like to view the current data?", "Y/N");
                if($option){
                    return var_dump($this->data);
                }
            }
        }
        /**
         * --------------------
         * 
         * CORE METHODS
         * 
         * --------------------
         */

        /**
         * @param string $error 
         * @param boolean $kill 
         * Function used to return exceptions and kill the program by default.
         */
        public function throwError(string $error, $kill = true){
            echo "ERROR!: \n";
            echo "\033[0;31m".$error." \033[0m \n";
            if($kill){
                die();
            }
        }

        /**
         * @param string $dir 
         * Used to format directories to be consistent with varying distros. 
         */
        public function formatDirectory(string $dir){
            return rtrim($dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        }

        /**
         * Returns scrape source
         */
        public function getSource(){
            return $this->source;
        }

        /**
         * Returns directory for images
         */
        public function getDirectory(){
            return $this->dir;
        }
    }
?>