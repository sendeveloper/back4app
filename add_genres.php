<?php
require 'parse_config.php';

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseACL;
use Parse\ParsePush;
use Parse\ParseUser;
use Parse\ParseInstallation;
use Parse\ParseException;
use Parse\ParseAnalytics;
use Parse\ParseFile;
use Parse\ParseCloud;
use Parse\ParseClient;

// $genre_arr = array("Country music", "Folk music", "Musicals", "Sceance", "Theatre"); 
// $genre_arr = array("Theatre", "Theatre", "Theatre", "Theatre", "Theatre", "Theatre", "Theatre", "Theatre", "Theatre");
// $order = 37;
// foreach($genre_arr as $each)
// {
// 	$genre = new ParseObject("Genre");
// 	$genre->set("createdAt", new DateTime());
// 	$genre->set("updatedAt", new DateTime());
// 	$genre->set("name", $each);
// 	$genre->set("order", $order);
// 	$genre->set("classical", TRUE);
// 	$genre->save();
// 	$order ++;
// }


?>