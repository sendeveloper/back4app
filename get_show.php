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

if(isset($_REQUEST["search_cancel"])) {
    $_SESSION['search_key'] = "";
}
if(isset($_REQUEST["search_venue"])) {
    $_SESSION["search_venue"] = isset($_REQUEST["search_venue"])?$_REQUEST["search_venue"]:"";
}
if(isset($_REQUEST["search_orchestra"])) {
    $_SESSION['search_orchestra'] = isset($_REQUEST["search_orchestra"])?$_REQUEST["search_orchestra"]:"";
}
if(isset($_REQUEST["search_user"])) {
    $_SESSION["search_user"] = isset($_REQUEST["search_user"])?$_REQUEST["search_user"]:"";
}

$currentUser = ParseUser::getCurrentUser();
$sort = isset($_SESSION['sort'])?$_SESSION['sort']:"date";
$sort_type = isset($_SESSION['sort_type'])?$_SESSION["sort_type"]:-1;
$date_sort_type = isset($_SESSION['date_sort_type'])?$_SESSION["date_sort_type"]:-1;
$search_venue = isset($_SESSION['search_venue'])?$_SESSION["search_venue"]:(isset($_REQUEST["search_venue"])?$_REQUEST["search_venue"]:"");
$search_orchestra = isset($_SESSION['search_orchestra'])?$_SESSION["search_orchestra"]:(isset($_REQUEST["search_orchestra"])?$_REQUEST["search_orchestra"]:"");
$search_user = isset($_SESSION['search_user'])?$_SESSION["search_user"]:(isset($_REQUEST["search_user"])?$_REQUEST["search_user"]:"");
$start_date = isset($_REQUEST['start_date'])?$_REQUEST["start_date"]:"";
$end_date = isset($_REQUEST['end_date'])?$_REQUEST["end_date"]:"";

if($search_venue!="") {
    $pieces = explode(",", $search_venue);
    $search_venue = $pieces[0];
}
if($search_orchestra!="") {
    $pieces = explode(",", $search_orchestra);
    $search_orchestra = $pieces[0];
}
if($search_user!="") {
    $pieces = explode(",", $search_user);
    $search_user = $pieces[0];
}
$current_page = isset($_REQUEST["current_page"])?$_REQUEST["current_page"]:1;
if($current_page<0) $current_page=1;

$_SESSION['start_date'] = $start_date;
$_SESSION['end_date'] = $end_date;
$_SESSION['current_page'] = $current_page;


$page_limit = 50;
$paging_info = 0;
$max_limit = 5000;
if (isset($_SESSION['data_concert']) && $_SESSION['page']!="signoff")
{
    $results = $_SESSION['data_concert'];

    if ($sort == "date" && $date_sort_type>0)
        $results = array_reverse($results);
    if($sort=="show") {
        if($sort_type<0) {
            usort($results, "a_name");
        } else {
            usort($results, "d_name");
        }
    }

    // if($search_key=="") {
    //     $paging_info = get_paging_info($count,$page_limit,$current_page);
    //     $min = $page_limit*($current_page-1);
    //     $max = $page_limit*$current_page<$count?$page_limit*$current_page:$count;
    // } else {
    //     $min = 0;
    //     $max = $count;
    // }
}
else
{
    $query = new ParseQuery("Show");

    if ($_SESSION['is_admin_login']==0) {
        $query->equalTo("createdByUser", $currentUser);
    } else if ($_SESSION['is_admin_login']==1) {
//        $query->equalTo("createdByUser", $currentUser);
    } else {
        if ($_SESSION['page'] == "signoff") {
            $query->equalTo("review_state", 1);
        } else {
            $query->equalTo("review_state", 0);
        }
    }
    if ($search_user!="") {
        $query1 = ParseUser::query();
        $query1->equalTo("username", $search_user);
        $object_user = $query1->first();
        $query->equalTo("createdByUser", $object_user);
    }
    if ($search_orchestra!= "")
    {
        $innerQuery2 = new ParseQuery("Orchestra");
        $innerQuery2->matches('name', $search_orchestra, 'i');
        $query->matchesQuery("orchestra", $innerQuery2);
    }
    if ($search_venue!="")
    {
        $innerQuery3 = new ParseQuery("Address");
        $innerQuery3->matches('building', $search_venue, 'i');
        $query->matchesQuery("address", $innerQuery3);
    }
    if ($start_date != "")
    {
        $sdate = DateTime::createFromFormat('Y.m.d', $start_date);
        if ($sdate)
            $query->greaterThanOrEqualTo('show_timestamp', $sdate);
    }
    if ($end_date != "")
    {
        $edate = DateTime::createFromFormat('Y.m.d', $end_date);
        if ($edate)
            $query->lessThanOrEqualTo('show_timestamp', $edate);
    }
    $query->includeKey("createdByUser");
    $query->includeKey("orchestra");
    $query->includeKey("address");
    $query->includeKey("genre");

    $query->limit($max_limit);
    $count = $query->count();
    if($count>$max_limit) $count = $max_limit;

    if ($search_venue == "" && $search_orchestra == "" && $search_user == "")
    {
        if ($sort == "show" || $sort == "date"){
            if($date_sort_type<0) {
                $query->ascending("show_timestamp");
            } else {
                $query->descending("show_timestamp");
            }
            if($sort=="show") {
                if($sort_type<0) {
                    $query->ascending("name");
                } else {
                    $query->descending("name");
                }
            }
            $query->skip($page_limit * ($current_page-1));
            $query->limit($page_limit);
            $results = $query->find();
            $min = 0;
            $max = count($results);
        }
        else
        {
            $query->limit($max_limit);
            $query->ascending("show_timestamp");
            $results = $query->find();
            if($sort=="show") {
                if($sort_type<0) {
                    usort($results, "a_name");
                } else {
                    usort($results, "d_name");
                }
            }
            if ($sort == "date" && $date_sort_type>0)
                $results = array_reverse($results);

            $min = $page_limit*($current_page-1);
            $max = $page_limit*$current_page<$count?$page_limit*$current_page:$count;
        }
        $paging_info = get_paging_info($count,$page_limit,$current_page);
    }
    else
    {
        $query->limit($max_limit);
        $results = $query->find();
        $min = 0;
        $max = $count;
    }
}

if($sort=="user") {
    if($sort_type<0) {
        usort($results, "a_user");
    } else {
        usort($results, "d_user");
    }
}
if($sort=="building") {
    if($sort_type<0) {
        usort($results, "a_building");
    } else {
        usort($results, "d_building");
    }
}
if($sort=="genre") {
    if($sort_type<0) {
        usort($results, "a_genre");
    } else {
        usort($results, "d_genre");
    }
}
if($sort=="orchestra") {
    if($sort_type<0) {
        usort($results, "a_orchestra");
    } else {
        usort($results, "d_orchestra");
    }
}
$showgenre_query = new ParseQuery("ShowGenres");
$showgenre_query->includeKey("genre");

$total_count = 0;
for($i=$min;$i<$max;$i++) {
    $obj=$results[$i];
    $object_id = $obj->getObjectId();
    $username = "";
    $useremail = "";
    $name = "";
    $orchestra = "";
    $building = "";
    $genre = "";
    $timestamp = "";
    $mine = 0;
    $cancelled = $obj->get("eventCancelled");
    $review_state = $obj->get("review_state");
    if($review_state==1) {
        $review_state = "PENDING";
    } else if($review_state==2) {
        $review_state = "REJECTED";
    } else {
        $review_state = "APPROVED";
    }
    $user_obj = $obj->get("createdByUser");
    if($user_obj) {
        $username = $user_obj->get("name");
        $useremail = $user_obj->get("username");
        if($useremail==null) {
            $useremail = "";
        }
    }
    if(strcmp($currentUser->get("username"), $useremail)==0) {
        $mine = 1;
    }
    $name = $obj->get("name");
    $orchestra_obj = $obj->get("orchestra");
    if($orchestra_obj) {
        $orchestra = $orchestra_obj->get("name");
    }
    $address_obj = $obj->get("address");
    if($address_obj) {
        $building = $address_obj->get("building");
    }

    // $showgenre_query->equalTo("show", $obj);
    // if ($showgenre_query->count() > 0)
    // {
    //     $showgenre_obj = $showgenre_query->first();
    //     if ($showgenre_obj){
    //         $genre_obj = $showgenre_obj->get("genre");
    //         if ($genre_obj)
    //             $genre = $genre_obj->get("name");
    //     }
    // }
    $genre_obj = $obj->get("genre");
    if($genre_obj) {
        $genre = $genre_obj->get("name");
    }
    $time = $obj->get("show_timestamp");
    if($time) {
        $timestamp = $time->format('d/m/y g.ia');
    }
    // if($search_user!="")
    // {
    //     if(stripos($username, $search_user)===false) continue;
    // }
    // if($search_orchestra!="") {
    //     if(stripos($orchestra, $search_orchestra)===false) continue;
    // }
    // if($search_venue!="") {
    //     if(stripos($building, $search_venue)===false) continue;
    // }
    // else if($search_venue!="conductor") {
    //     $query = new ParseQuery("ShowConductors");
    //     $query->includeKey("conductor");
    //     $query->equalTo("show", $obj);
    //     $sr = $query->find();
    //     $f = 0;
    //     foreach($sr as $s) {
    //         $conductor = $s->get("conductor");
    //         if($conductor) {
    //             $conductor_name = $conductor->get("name");
    //             if(stripos($conductor_name, $search_key)!==false) {
    //                 $f = 1;
    //             }
    //         }
    //     }
    //     if($f==0) {
    //         continue;
    //     }
    // }
    // else if($search_type=="show") {
    //     if(stripos($name, $search_key)===false) continue;
    // } else if($search_type=="programme") {
    //     $query = new ParseQuery("Programme");
    //     $query->equalTo("name", $search_key);
    //     $query->equalTo("show", $obj);
    //     $count = $query->count();
    //     if($count==0) {
    //         continue;
    //     }
    // } else if($search_type=="performer") {
    //     $query = new ParseQuery("ShowPerformers");
    //     $query->includeKey("performer");
    //     $query->equalTo("show", $obj);
    //     $sr = $query->find();
    //     $f = 0;
    //     foreach($sr as $s) {
    //         $performer = $s->get("performer");
    //         if($performer) {
    //             $performer_name = $performer->get("name");
    //             if(stripos($performer_name, $search_key)!==false) {
    //                 $f = 1;
    //             }
    //         }
    //     }
    //     if($f==0) {
    //         continue;
    //     }
    // }
    array_push($result_array, array(
        "id" => $object_id,
        "username" => $username,
        "useremail" => $useremail,
        "cancelled" => $cancelled,
        "state" => $review_state,
        "name" => $name,
        "orchestra" => $orchestra,
        "building" => $building,
        "genre" => $genre,
        "timestamp" => $timestamp,
        "mine" => $mine
        ));
    $total_count++;
}
if ($search_venue == "" && $search_orchestra == "" && $search_user == "")
    $total_count = $count;
$result = array(
    "sort" => $sort,
    "sort_type" => $sort_type,
    "date_sort_type" => $date_sort_type,
    "result" => $result_array,
    "total_count" => $total_count,
    "paging_info" => $paging_info);

echo json_encode($result);

function get_paging_info($tot_rows,$pp,$curr_page)
{
    $pages = ceil($tot_rows / $pp); // calc pages

    $data = array(); // start out array
    $data['si']        = ($curr_page * $pp) - $pp; // what row to start at
    $data['pages']     = $pages;                   // add the pages
    $data['curr_page'] = intval($curr_page);               // Whats the current page
    return $data; //return the paging data

}
function a_name($a, $b)
{
    global $date_sort_type;
    $result = 0;
    $result = strcasecmp($a->get("name"), $b->get("name"));
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function d_name($a, $b)
{
    global $date_sort_type;
    $result = 0;
    $result = -1*strcasecmp($a->get("name"), $b->get("name"));
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}

function a_user($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("createdByUser") && $b->get("createdByUser")) {
        $result = strcasecmp($a->get("createdByUser")->get("username"), $b->get("createdByUser")->get("username"));
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function d_user($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("createdByUser") && $b->get("createdByUser")) {
        $result = -1*strcasecmp($a->get("createdByUser")->get("username"), $b->get("createdByUser")->get("username"));
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function a_building($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("address") && $b->get("address")) {
        $result = strcasecmp($a->get("address")->get("building"), $b->get("address")->get("building"));
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function d_building($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("address") && $b->get("address")) {
        $result = -1*strcasecmp($a->get("address")->get("building"), $b->get("address")->get("building"));
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function a_genre($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("genre") && $b->get("genre")) {
        $result = strcasecmp($a->get("genre")->get("name"), $b->get("genre")->get("name"));
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function d_genre($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("genre") && $b->get("genre")) {
        $result = -1*strcasecmp($a->get("genre")->get("name"), $b->get("genre")->get("name"));
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function a_orchestra($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("orchestra") && $b->get("orchestra")) {
        $result = strcasecmp($a->get("orchestra")->get("name"), $b->get("orchestra")->get("name"));
    } else if($a->get("orchestra")) {
        return -1;
    } else if($b->get("orchestra")) {
        return 1;
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
function d_orchestra($a, $b)
{
    global $date_sort_type;
    $result = 0;
    if($a->get("orchestra") && $b->get("orchestra")) {
        $result = -1*strcasecmp($a->get("orchestra")->get("name"), $b->get("orchestra")->get("name"));
    } else if($a->get("orchestra")) {
        return 1;
    } else if($b->get("orchestra")) {
        return -1;
    }
    if($result == 0) {
        $a_time = $a->get("show_timestamp");
        $b_time = $b->get("show_timestamp");
        if(!$a_time || !$b_time) return 0;
        $a_time = $a_time->format('Y/m/d g.ia');
        $b_time = $b_time->format('Y/m/d g.ia');
        $result = strcasecmp($a_time, $b_time);
        if($date_sort_type>0) $result = -1*$result;
    }
    return $result;
}
