<?php
require 'parse_config.php';

use Parse\ParseSession;
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

$result_array = array();

$currentUser = ParseUser::getCurrentUser();

    $show_id = isset($_REQUEST['id'])?$_REQUEST['id']:"";
    $query1 = new ParseQuery("Show");
    $query1->get($show_id);
    $show = $query1->first();

    $query = new ParseQuery("ShowEditHistory");
    $query->includeKey("user");
    $query->equalTo("show", $show);
    $query->ascending("createdAt");
//    $query->descending("createdAt");
    $query->limit(50);
    $results = $query->find();
    $count = $query->count();

    for($i=0;$i<$count;$i++) {
        $obj=$results[$i];
        $object_id = $obj->getObjectId();
        $username = "";
        $timestamp = "";
        $user_obj = $obj->get("user");
        if($user_obj) {
            $username = $user_obj->get("username");
        }
//        $time = $obj->getCreatedAt();
        $time = $obj->get("date");
        if($time) {
            $timestamp = $time->format('Y M d H:i');
        }
        array_push($result_array, array(
            "id" => $object_id,
            "username" => $username,
            "timestamp" => $timestamp
            ));
    }

    $result = array(
        "result" => $result_array);

    echo json_encode($result);
