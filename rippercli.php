<?php 
/**
 * Script By Ervin Sabic
 * Published under GPL
 * Feel free to do whatever you please with this script.
 * Website: www.gearsite.net 
 * Desc: PHP CLI Script that is used to pull data from a website. Useful for when you need to move hundreds of images and want to organize them. 
 */

require("sabicripper.php");
$ripper = new Ripper($argv, $argc);
$ripper->init();
$html = $ripper->scrapeHtml($ripper->getSource());
$imageSources = $ripper->scrapeImageSources($html);
$acceptedFormats = [
    'jpg', 'png', 'tiff', 'gif', 'bmp', 'svg', 'pdf', 
];
foreach($imageSources as $key=>$source){
    $imageSources[$key] = washUrl($source, $acceptedFormats);
    echo $imageSources[$key];
}
var_dump($imageSources);
$imageSources = $ripper->uniqueFilter($imageSources);
$ripper->scrapeImages($imageSources);



/**
 * Get the first images which is what we're looking for. Custom Filter
 */
function washURL($url, $options){
    $noMatch = true;
    while($noMatch){
    foreach($options as $check){
        $check = ".".$check;
        if(strpos($url, $check) !== false){
            $noMatch = false;
            return substr($url, 0, strpos($url, $check)+strlen($check))."\n";   
        }
    }
    $noMatch = false;
    }
}
?>