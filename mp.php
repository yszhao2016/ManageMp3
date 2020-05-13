<?php
require __DIR__ . '/vendor/autoload.php';

function changefile($filepath,$title)
{
    $eyed3 = new \Stormiix\EyeD3\EyeD3($filepath);
    $tags = $eyed3->readMeta();
// $tags is an array that contains the following keys:
// artist, title, album, comment(s), lyrics ..etc

    $meta = [
        "artist" => $title,
        "title" => "MyTitle",
        "album" => "MyAlbum",
        "comment" => "MyComment",
        "lyrics" => "MyLyrics",
        "album_art" => "cover.png"
    ];
// Update the mp3 file with the new meta tags
    $eyed3->updateMeta($meta);
}
$filePath = __DIR__ . "/file";
$files = scandir($filePath);
foreach ($files as $filename) {
    if ($filename == '.' || $filename == "..") {
        continue;
    }

    $mp3_file = $filePath . '/' . $filename;
    changeFile($mp3_file,$filename);

}