<?php
require 'vendor/autoload.php';
$filePath = __DIR__."/file";
$files  = scandir($filePath);
foreach($files as $filename)
{
    if($filename == '.'|| $filename == ".."){
        continue;
    }

    $mp3_file = $filePath.'/'.$filename;
    echo $mp3_file;
    $id3 = new Zend_Media_Id3v2($mp3_file);
//    setIdentifier
//    $id3->setOptions();
    $frame = $id3->getFramesByIdentifier('TIT2');
//    $pa = array('Title' => "789" ,
//        'Artist' => '852',
//        'Copyright' => 'YaYue.fm',
//        'Description' => '',
//        'Track' => '',
//        'Genre' => '',
//        'AlbumTitle' => '');
    var_dump($frame);exit;
  $frame[0]->setIdentifier('aaabbbb');
    $id3->write($mp3_file);

}