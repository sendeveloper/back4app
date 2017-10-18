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

$currentUser = ParseUser::getCurrentUser();

    $show_id = isset($_REQUEST['show_id'])?$_REQUEST['show_id']:"";
    $copy = isset($_REQUEST['copy'])?$_REQUEST['copy']:0;
    $state = intval(isset($_REQUEST['state'])?$_REQUEST['state']:0);
    $event_cancelled = isset($_REQUEST['event_cancelled'])?$_REQUEST['event_cancelled']:0;
	$programme_composer = isset($_REQUEST['programme_composer'])?$_REQUEST['programme_composer']:array();
	$programme_title = isset($_REQUEST['programme_title'])?$_REQUEST['programme_title']:array();

    $conductors = isset($_REQUEST['conductors'])?$_REQUEST['conductors']:array();
	$performers = isset($_REQUEST['performers'])?$_REQUEST['performers']:array();

    $no_orchestra = isset($_REQUEST['no_orchestra'])?$_REQUEST['no_orchestra']:0;
    $orchestra_id = "";
    if(!$no_orchestra) {
        if ($_SESSION['is_admin_login']!=0) {
            $orchestra_id = $_REQUEST['orchestra_id'];
        }
        $orchestra_name = $_REQUEST['orchestra_name'];
        $orchestra_acronym = $_REQUEST['orchestra_acronym'];
    }

    $show_title = $_REQUEST['show_title'];
    $date_time = $_REQUEST['date_time'];
    $hour_time = $_REQUEST['hour_time'];
    $minute_time = $_REQUEST['minute_time'];
    $pm_time = $_REQUEST['pm_time'];

//    $date_time = $date_time." ".$hour_time.":".$minute_time;
//    $date_time = $date_time." ".$hour_time;
    $date_time = $date_time." ".$hour_time." : ".$minute_time." ".$pm_time;

    $cost = $_REQUEST['cost'];

    $address_id = "";
    if ($_SESSION['is_admin_login']!=0) {
        $address_id = $_REQUEST['address_id'];
    }
    $building_name = $_REQUEST['building_name'];
    $county_area = isset($_REQUEST['county_area'])?$_REQUEST['county_area']:"";
    $road = isset($_REQUEST['road'])?$_REQUEST['road']:"";
    $area = isset($_REQUEST['area'])?$_REQUEST['area']:"";
    $postcode = isset($_REQUEST['postcode'])?$_REQUEST['postcode']:"";
    $address_acronym = isset($_REQUEST['address_acronym'])?$_REQUEST['address_acronym']:"";
    $town = isset($_REQUEST['town'])?$_REQUEST['town']:"";
    $phone = isset($_REQUEST['phone'])?$_REQUEST['phone']:"";

    $description = $_REQUEST['description'];
    $genres = isset($_REQUEST['genres']) ? $_REQUEST['genres'] : array();
    $booking_link = isset($_REQUEST['booking_link'])?$_REQUEST['booking_link']:"";
    $booking_telephone = isset($_REQUEST['booking_telephone'])?$_REQUEST['booking_telephone']:"";

    $isNewAddress=1;
    if($address_id!="") {
        $query = new ParseQuery("Address");
        $query->get($address_id);
        $address = $query->first();
    } else {
        $query = new ParseQuery("Address");
        $query->equalTo("building",$building_name);
        $query->equalTo("postcode",$postcode);
        $address = $query->first();
    }
    if($address) {
        $isNewAddress=0;
    } else {
        $address = new ParseObject("Address");
    }
$count = 0;
if($isNewAddress==0) {
    if($show_id=="" || $copy==1) {
        $query = new ParseQuery("Show");
    //    $date = date_create_from_format('d/m/Y H:i', $date_time);
        $date = date_create_from_format('d/m/Y h : i A', $date_time);
        $query->equalTo("show_timestamp", $date);
        $query->equalTo("address", $address);

        $count = $query->count();
    } else if($show_id!="") {
        $query = new ParseQuery("Show");
        $query->notEqualTo("objectId",$show_id);
    //    $date = date_create_from_format('d/m/Y H:i', $date_time);
        $date = date_create_from_format('d/m/Y h : i A', $date_time);
        $query->equalTo("show_timestamp", $date);
        $query->equalTo("address", $address);

        $count = $query->count();
    }
}
if($count==0) {

    if($show_id!="" && $copy==0) {
        $query = new ParseQuery("Show");
        $query->get($show_id);
        $query->includeKey("createdByUser");
        $query->includeKey("orchestra");
        $query->includeKey("address");
        $show = $query->first();

    } else {
        $show = new ParseObject("Show");
        $show->set("createdByUser", $currentUser);
    }

    if(isset($_FILES['file1'])) {
        $imageFileType = pathinfo(basename($_FILES['file1']['name']),PATHINFO_EXTENSION);
        $filename =  uniqid(). '.' . $imageFileType;
        $file = ParseFile::createFromFile($_FILES['file1']['tmp_name'], $filename);
        $show->set("show_image",$file);
    } else if($show_id!="" && $copy==1) {
        $query = new ParseQuery("Show");
        $query->get($show_id);
        $show1 = $query->first();
        $image = $show1->get("show_image");
        $show->set("show_image",$image);
        $show->save();
    }

    // $query = new ParseQuery("Genre");
    try {
        $query = new ParseQuery("ShowGenres");
        $query->equalTo("show", $show);
        $genre = $query->find();
        foreach($genre as $each) {
            $each->destroy();
        }

        foreach($genres as $each)
        {
            $query = new ParseQuery("Genre");
            $genre = $query->get($each);
            // $query->equalTo("objectId", $each, "i");
            // if($query->count()>0) {
            //     $genre = $query->first();
            // } else {
            //     $genre = new ParseObject("Genre");
            //     $genre->set("name", trim(ucwords($each)));
            //     $genre->save();
            // }

            $showgenre = new ParseObject("ShowGenres");
            $showgenre->set("genre", $genre);
            $showgenre->set("show", $show);
            $showgenre->save();
        }

        if (count($genres) > 0)
        {
            $query = new ParseQuery("Genre");
            $genre_object = $query->get($genres[0]);
            $show->set("genre", $genre_object);
        }
    } catch (ParseException $ex) {
    }

    $show->set("name", $show_title);
    if(!$event_cancelled) {
        $show->set("eventCancelled", false);
    } else {
        $show->set("eventCancelled", true);
    }

//    $date = date_create_from_format('d/m/Y H:i', $date_time);
    $date = date_create_from_format('d/m/Y h : i A', $date_time);
    $show->set("show_timestamp", $date);
    $show->set("cost_description", $cost);
    $show->set("show_description", $description);
//    $show->setArray("conductors", $conductors);
//    $show->setArray("principal_players", $performers);
    $show->set("booking_link", $booking_link);
    $show->set("booking_telephone", $booking_telephone);

    if ($_SESSION['is_admin_login']==0) {
        $show->set("review_state", 1);
    } else {
        $show->set("review_state", $state);
    }

    $isNewOrchestra=1;
    if(!$no_orchestra) {
        if($orchestra_id!="") {
            $query = new ParseQuery("Orchestra");
            $query->get($orchestra_id);
            $orchestra = $query->first();
        } else {
            $query = new ParseQuery("Orchestra");
            $query->equalTo("name", $orchestra_name);
            $orchestra = $query->first();
            if($orchestra) {
                $show->set("orchestra", $orchestra);
                $isNewOrchestra=0;
            } else {
                $orchestra = new ParseObject("Orchestra");
            }
        }
        if($isNewOrchestra) {
            $orchestra->set("name", $orchestra_name);
            $orchestra->set("acronym", $orchestra_acronym);
            if(isset($_FILES['file2'])) {
                $imageFileType = pathinfo(basename($_FILES['file2']['name']),PATHINFO_EXTENSION);
                $filename =  uniqid(). '.' . $imageFileType;
                $file = ParseFile::createFromFile($_FILES['file2']['tmp_name'], $filename);
                $orchestra->set("logo_image", $file);
            }
        }
    }

//    if($isNewAddress) {
        $country = "UK";
        $address->set("building", $building_name);
        $address->set("street", $road);
        $address->set("area", $area);
        $address->set("county", $county_area);
        $address->set("city", $town);
        $address->set("postcode", $postcode);
        $address->set("acronym", $address_acronym);
        $address->set("country_iso", $country);
        $address->set("phone", $phone);

        $str = $building_name.", ".$road.", ".$town.", ".$county_area.", ".$postcode." ,".$country;
        $latLong = getLatLong($str);
        if(!$latLong) {
            $str = $postcode;
            $latLong = getLatLong($str);
        }
        $latitude = $latLong['latitude']?$latLong['latitude']:'Not found';
        $longitude = $latLong['longitude']?$latLong['longitude']:'Not found';
        $latitude = strval($latitude);
        $longitude = strval($longitude);
        $address->set("latitude", $latitude);
        $address->set("longitude", $longitude);
//    }
    try {
//        if($isNewAddress==1) {
            $address->save();
//        }
        $show->set("address",$address);

        if(!$no_orchestra) {
            if($isNewOrchestra==1) {
                $orchestra->save();
                $show->set("orchestra", $orchestra);
            }
        } else {
            $show->set("orchestra", null);
        }

        $show->save();
//        $show->getObjectId();

        $showEditHistory = new ParseObject("ShowEditHistory");
        $showEditHistory->set("user", $currentUser);
        $showEditHistory->set("show", $show);
        $showEditHistory->set("date", new DateTime());
        $showEditHistory->save();


        $sub_query = new ParseQuery("Programme");
        $sub_query->equalTo("show", $show);
        $programme = $sub_query->find();
        foreach($programme as $pro) {
            $pro->destroy();
        }
        for($i=0;$i<sizeof($programme_composer);$i++) {
            $programme = new ParseObject("Programme");
            $programme->set("show",$show);
            $programme->set("composer", trim(ucwords($programme_composer[$i])));
            $programme->set("name", trim(ucwords($programme_title[$i])));
            $programme->set("order",$i+1);
            $programme->save();
        }

    //Add Conductors

        $sub_query = new ParseQuery("ShowConductors");
        $sub_query->equalTo("show", $show);
        $programme = $sub_query->find();
        foreach($programme as $pro) {
            $pro->destroy();
        }
        foreach($conductors as $obj) {
            $showConductors = new ParseObject("ShowConductors");

            $con_arr = explode(",", $obj);
//            if(sizeof($con_arr)<2) {
//                $con_arr[1] = "";
//            }

            $query = new ParseQuery("Conductor");
            $query->equalTo("name", $con_arr[0]);
            if($query->count()>0) {
                $conductor = $query->first();
            } else {
                $conductor = new ParseObject("Conductor");
                $conductor->set("name", trim(ucwords($con_arr[0])));
                $conductor->save();
            }
            $showConductors->set("show", $show);
            $showConductors->set("conductor", $conductor);
            $showConductors->save();
        }
    //End Conductors
    //Add Performers
        $sub_query = new ParseQuery("ShowPerformers");
        $sub_query->equalTo("show", $show);
        $programme = $sub_query->find();
        foreach($programme as $pro) {
            $pro->destroy();
        }
        foreach($performers as $obj) {
            $showPerformers = new ParseObject("ShowPerformers");

            $con_arr = explode(",", $obj);
            if(sizeof($con_arr)<2) {
                $con_arr[1] = "";
            }

            $query = new ParseQuery("Performer");
            $query->equalTo("name", $con_arr[0]);
            $query->equalTo("role", $con_arr[1]);
            if($query->count()>0) {
                $performer = $query->first();
            } else {
                $performer = new ParseObject("Performer");
                $performer->set("name", trim(ucwords($con_arr[0])));
                $performer->set("role", trim(ucwords($con_arr[1])));
                $performer->save();
            }
            $showPerformers->set("show", $show);
            $showPerformers->set("performer", $performer);
            $showPerformers->save();
        }
    //End Performers
    } catch (ParseException $ex) {
        // Execute any logic that should take place if the save fails.
        // error is a ParseException object with an error code and message.
        echo 'Failed to create new object, with error message: ' . $ex->getMessage();
    }

    if ($_SESSION['is_admin_login']==0) {
        $subject = "Added Event at Jubal";
        $email = $currentUser->get("email");
//        $location = $building_name.", ".$road.", ".$town.", ".$county_area.", ".$postcode;

        $location = $building_name;
        if($road!="") {
            $location.= ", ".$road;
        }
        if($county_area!="") {
            $location.= ", ".$county_area;
        }
        if($town!="") {
            $location.= ", ".$town;
        }
        if($postcode!="") {
            $location.= ", ".$postcode;
        }

        $content = "
        <p>Email: $email</p>
        <p>Event title: $show_title</p>
        <p>Date: $date_time</p>
        <p>Location: $location</p>";
        Send_Mail("contact@jubal.co.uk", $subject, $content, $email);
        Send_Mail("maarten.mali814@gmail.com", $subject, $content);
    } else {
        if ($_SESSION['page'] == "signoff") {
            if($state==0) {
                $state = "approved";
            } else {
                $state = "rejected";
            }
            $user_obj = $show->get("createdByUser");
            if($user_obj) {
                $email = $user_obj->get("username");
                $name = $user_obj->get("name");
            }
            $subject = "Your event is ".$state.".";

            $content = "<p>Hi $name,</p>
            <p>Your event is ".$state.".</p>
            <p>Kind regards,</p>
            <p>The Jubal Team</p>";
            Send_Mail($email, $subject, $content);
            Send_Mail("maarten.mali814@gmail.com", $subject, $content);
        }
    }
    echo "0";
} else {
    echo "1";
}

function getLatLong($address){
    if(!empty($address)){
        //Formatted address
        $formattedAddr = str_replace(' ','+',$address);
        //Send request and receive json data by address
        $geocodeFromAddr = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false');
//        $geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false&key=AIzaSyBcc2DfTxJlEbOFyMLTSKSa3Sx4F7KOVMk');
        $output = json_decode($geocodeFromAddr);
        //Get latitude and longitute from json data
        if($output->status!="OK") {
            return false;
        }
        $data['latitude']  = $output->results[0]->geometry->location->lat;
        $data['longitude'] = $output->results[0]->geometry->location->lng;
        //Return latitude and longitude of the given address
        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
    }else{
        return false;
    }
}
?>