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

$currentUser = ParseUser::getCurrentUser();
$maxtime = 60 * 60;
if (isset($_GET['param']))
{
    $result = array();
    if($_GET['param'] == 'orcehstra')
    {
        if (isset($_SESSION['data_orchestra'])){
            if (time() > $_SESSION['data_orchestra']['time'] + $maxtime){
                unset($_SESSION['data_orchestra']);
            }
            else
                $result['orchestra'] = $_SESSION['data_orchestra']['data'];
        }
        if (!isset($result['orchestra']))
        {
            $query = new ParseQuery("Orchestra");
            $query->ascending("name");
            $query->limit(1000);
            $object_orchestras = $query->find();
            $orchestras_results = array();
            foreach($object_orchestras as $obj) {
                $orchestra = array("name" => $obj->get("name"));
                if(!in_array($orchestra, $orchestras_results, true))
                    array_push($orchestras_results, $orchestra);
            }
            $result['orchestra'] = $orchestras_results;
            $_SESSION['data_orchestra'] = array('time'=>time(), 'data'=>$orchestras_results);
        }
    }
    else if ($_GET['param'] == 'address')
    {
        if (isset($_SESSION['data_address'])){
            if (time() > $_SESSION['data_address']['time'] + $maxtime){
                unset($_SESSION['data_address']);
            }
            else
                $result['address'] = $_SESSION['data_address']['data'];
        }
        if (!isset($result['address']))
        {
            $query = new ParseQuery("Address");
            $query->ascending("building");
            $query->limit(1000);
            $object_addresses = $query->find();
            $addresses_results = array();
            foreach($object_addresses as $obj) {
                $full = $obj->get("building");
                if($obj->get("street")!="") {
                    $full.= ", ".$obj->get("street");
                }
                if($obj->get("area")!="") {
                    $full.= ", ".$obj->get("area");
                }
                if($obj->get("city")!="") {
                    $full.= ", ".$obj->get("city");
                }
                if($obj->get("county")!="") {
                    $full.= ", ".$obj->get("county");
                }
                if($obj->get("postcode")!="") {
                    $full.= ", ".$obj->get("postcode");
                }

                $address = array("name" => $full);
                if(!in_array($address, $addresses_results, true))
                    array_push($addresses_results, $address);
            }
            $result['address'] = $addresses_results;
            $_SESSION['data_address'] = array('time'=>time(), 'data'=>$addresses_results);
        }
    }
    else if ($_GET['param'] == 'users')
    {
        if (isset($_SESSION['data_user'])){
            if (time() > $_SESSION['data_user']['time'] + $maxtime){
                unset($_SESSION['data_user']);
            }
            else
                $result['user'] = $_SESSION['data_user']['data'];
        }
        if (!isset($result['user']))
        {
            $query = ParseUser::query();
            $query->equalTo("web_active", 1);
            $object_users = $query->find();
            $users_results = array();
            foreach($object_users as $obj) {
                $user = array("name" => $obj->get("username"));
                array_push($users_results, $user);
            }
            $result['user'] = $users_results;
            $_SESSION['data_user'] = array('time'=>time(), 'data'=>$users_results);
        }
    }
    else if ($_GET['param'] == 'conductors' && isset($_GET['id']))
    {
        $id = $_GET['id'];
        $query = new ParseQuery("ShowConductors");
        $query->includeKey("conductor");

        $innerQuery = new ParseQuery("Show");
        $innerQuery->equalTo('objectId', $id, 'i');
        $query->matchesQuery("show", $innerQuery);

        $conductors = $query->find();
        $conductor_result = array();
        foreach($conductors as $each)
        {
            $conduct_obj = $each->get("conductor");
            if($conduct_obj) {
                $obj = array("name" => $conduct_obj->get("name"));
                $conductor_result[] = $obj;
            }
        }
        $result['conductors'] = $conductor_result;
    }
    else if ($_GET['param'] == 'performers' && isset($_GET['id']))
    {
        $id = $_GET['id'];
        $query = new ParseQuery("ShowPerformers");
        $query->includeKey("performer");

        $innerQuery = new ParseQuery("Show");
        $innerQuery->equalTo('objectId', $id, 'i');
        $query->matchesQuery("show", $innerQuery);

        $performers = $query->find();
        $performer_result = array();
        foreach($performers as $each)
        {
            $performer_obj = $each->get("performer");
            if($performer_obj) {
                $obj = array("name" => $performer_obj->get("name"),"role" => $performer_obj->get("role"));
                $performer_result[] = $obj;
            }
        }
        $result['performers'] = $performer_result;
    }
    else if ($_GET['param'] == 'programme' && isset($_GET['id']))
    {
        $id = $_GET['id'];
        $query = new ParseQuery("Programme");
        $query->ascending("order");

        $innerQuery = new ParseQuery("Show");
        $innerQuery->equalTo('objectId', $id, 'i');
        $query->matchesQuery("show", $innerQuery);

        $programme = $query->find();
        $programme_result = array();
        foreach($programme as $each)
        {
            $obj = array("composer" => $each->get("composer"),"title" => $each->get("name"));
            if (!in_array($obj, $programme_result))
            {
                $programme_result[] = $obj;
            }
        }
        $result['programme'] = $programme_result;
    }
    else if ($_GET['param'] == 'genre' && isset($_GET['id']))
    {
        $id = $_GET['id'];
        $query = new ParseQuery("Genre");
        $query->ascending("name");
        $results = $query->find();
        $genre_result = array();
        foreach($results as $each) {
            $obj = array("id" => $each->getObjectId(),"name" => $each->get("name"), "checked" => false);
            $genre_result[] = $obj;
        }
        $innerQuery = new ParseQuery("Show");
        $innerQuery->equalTo('objectId', $id, 'i');
        if ($innerQuery->count() > 0)
        {
            $show = $innerQuery->first();

            $query1 = new ParseQuery("ShowGenres");
            $query1->includeKey("genre");
            $query1->includeKey("show");
            $query1->equalTo('show', $show, 'i');
            $results1 = $query1->find();
            foreach($results1 as $obj1)
            {
                $genre = $obj1->get("genre");
                if ($genre)
                {
                    $objid = $genre->getObjectId();
                    foreach($genre_result as &$each)
                    {
                        if ($each['id'] == $objid)
                        {
                            $each['checked'] = true;
                            break;
                        }
                    }
                }
            }
        }
        $result['genre'] = $genre_result;
    }
    else if ($_GET['param'] == 'auto_conductor')
    {
        $query = new ParseQuery("Conductor");
        $query->ascending("name");
        $query->limit(5000);
        $object_conductors = $query->find();

        $got_result = array();
        foreach($object_conductors as $each)
        {
            $obj = array("objectId" => $each->getObjectId(),"name" => $each->get("name"));
            $got_result[] = $obj;
        }
        $result['auto_conductor'] = $got_result;
    }
    else if ($_GET['param'] == 'auto_performer')
    {
        $query = new ParseQuery("Performer");
        $query->ascending("name");
        $query->limit(5000);
        $object_performers = $query->find();

        $got_result = array();
        foreach($object_performers as $each)
        {
            $obj = array("name" => $each->get("name"),"role" => $each->get("role"));
            $got_result[] = $obj;
        }
        $result['auto_performer'] = $got_result;
    }
    else if ($_GET['param'] == 'auto_orchestra')
    {
        $query = new ParseQuery("Orchestra");
        $query->ascending("name");
        $query->limit(5000);
        $object_orchestras = $query->find();

        $got_result = array();
        foreach($object_orchestras as $each)
        {
            $obj = array("objectId" => $each->getObjectId(), "name" => $each->get("name"),"acronym" => $each->get("acronym"), "logo" => "");
            $o_logo = $each->get("logo_image");
            if ($o_logo)
                $obj['logo'] = $o_logo->getURL();
            $got_result[] = $obj;
        }
        $result['auto_orchestra'] = $got_result;
    }
    else if ($_GET['param'] == 'auto_programme')
    {
        $query = new ParseQuery("Programme");
        $query->ascending("composer");
        $query->limit(5000);
        $object_composers = $query->find();

        $composer_result = array();
        $name_result = array();
        foreach($object_composers as $each)
        {
            $composer = $each->get("composer");
            $name = $each->get("name");
            if(!in_array($composer, $composer_result, true))
                $composer_result[] = $composer;
            if(!in_array($name, $name_result, true))
                $name_result[] = $name;
        }
        $result['auto_composer'] = $composer_result;
        $result['auto_music'] = $name_result;
    }
    else if ($_GET['param'] == 'auto_address')
    {
        $query = new ParseQuery("Address");
        $query->ascending("building");
        $query->limit(5000);
        $object_addresses = $query->find();

        $got_result = array();
        foreach($object_addresses as $address)
        {
            $o_id = $address->getObjectId();
            $o_building = $address->get("building");
            $o_street = $address->get("street");
            $o_area = $address->get("area");
            $o_city = $address->get("city");
            $o_county = $address->get("county");
            $o_postcode = $address->get("postcode");
            $o_phone = $address->get("phone");
            $o_acronym = $address->get("acronym");
            $o_label = $o_building;
            if($o_street!="") $o_label.= ", ".$o_street;
            if($o_area!="") $o_label.= ", ".$o_area;
            if($o_city!="") $o_label.= ", ".$o_city;
            if($o_county!="") $o_label.= ", ".$o_county;
            if($o_postcode!="") $o_label.= ", ".$o_postcode;

            $obj = array("id" => $o_id, "building" => $o_building,"street" => $o_street, 
                        "area" => $o_area, "city" => $o_city, "county" => $o_county,
                        "postcode" => $o_postcode, "phone" => $o_postcode, 
                        "acronym" => $o_acronym, "label" => $o_label);
            $got_result[] = $obj;
        }
        $result['auto_address'] = $got_result;
    }
    echo json_encode($result);
}