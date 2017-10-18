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


    if(empty($_REQUEST['item'])) {
        // No items checked
    }
    else {
        foreach($_REQUEST['item'] as $id) {
            // delete the item with the id $id
            $query = new ParseQuery("Show");
            try {
                $object = $query->get($id);
                $sub_query = new ParseQuery("Programme");
                $sub_query->equalTo("show", $object);
                $programme = $sub_query->find();
                foreach($programme as $pro) {
                    $pro->destroy();
                }

                $object->destroy();

            } catch (ParseException $ex) {
            }
        }
    }