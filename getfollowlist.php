<?php
/*Returns user's followed list or dvds&episodes from the followed list the user hasn't seen yet*/
/*Highly similar to the search.php function*/
session_start();
if (!isset($_SESSION["id"]) || !isset($_GET["action"])) {
    header("Location: index.php");
}
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
/*Todo: create a better memcached handler for the user's watchlist, so the SQL selecting isn't needed as often*/
require("inc/settings.php");
$stmt = $db->prepare("SELECT showid FROM HT_followList WHERE userid=:userid");
$stmt->execute(array(":userid"=>$_SESSION["id"]));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$array = Array();
if($rows) {
    foreach($rows as $row) {
        $id = $row["showid"];
        $result = $mem->get($row["showid"]);
        if (!$result) {
            $url = 'http://www.omdbapi.com/?i='.$id.'&tomatoes=true';
            $json = file_get_contents($url);
            $details = json_decode($json);
            $item = array();
            $item["response"] = $details->Response;
            $item["type"] = $details->Type;
            if ($details->Response==='True') {
                if ($details->Type === 'series' || $details->Type ==='movie') {
                    $item["title"]=$details->Title;
                    $filePath = "img/no-poster.jpg";
                    if ($details->Poster!=="N/A") {
                        $filePath = "img/posters/".(string)$id.".jpg";
                        if (!file_exists($filePath)) {
                            $filePath2 = $details->Poster;
                            copy($filePath2,$filePath);
                        }
                    }
                    $item["poster"] = $filePath;
                    $item["type"] = ucfirst($details->Type);
                    $item["year"] = $details->Year;
                    $item["released"] = $details->Released;
                    $item["genre"] = $details->Genre;
                    $item["dvd"] = $details->DVD;
                    $item["imdbRating"] = $details->imdbRating;
                }
            }
            $mem->set($id,$item,60*60*18);
        }
        $result = $mem->get($id);
        $loggedIn = 1;
        if ($result) {
            if ($result["response"]==='True') {
                if ($result["type"]==='Series' || $result["type"]==='Movie') {
                    if ($_GET["action"]==='unseen' && $result["type"]==="Series") {
                        /*This is used on the profile page's unseen list, selects only titles that aren't yet seen*/
                        /*Gets the last episode the user has seen*/
                        $rs=$mem->get($_SESSION["id"].$id);
                        $item1=Array();
                        if (!$rs || $rs["season"]===null || $rs["episode"]===null) {
                            $stmt=$db->prepare("SELECT * FROM HT_seenList WHERE userid=:userid AND showid=:showid");
                            $stmt->execute(array(":userid"=>$_SESSION["id"], ":showid"=>$id));
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($row) {
                                $item1["season"] = $row["season"];
                                $item1["episode"] = $row["episode"];
                            }
                            else {
                                /*Again, 0,0 means not yet seen*/
                                $item1["season"]=0;
                                $item1["episode"]=0;
                            }
                            $mem->set($_SESSION["id"].$id,$item1,24*60*60);
                        }
                        else {
                            $item1["season"] = $rs["season"];
                            $item1["episode"] = $rs["episode"];
                        }
                        /*Compares the last episode the user has seen to the last episode released of the series*/
                        /*First reads it from the memcached*/
                        $r = $mem->get($id.'lastEp');
                        $item2=Array();
                        if (!$r) {
                            /*If no hits, then reads it from the lastepisodes.json*/
                            $jsonString = file_get_contents('data/lastepisodes.json');
                            $dt = json_decode($jsonString);
                            unset($jsonString);
                            $item2["season"] = $dt->$id->season;
                            $item2["episode"] = $dt->$id->episode;
                        }
                        else {
                            $item2 = $r;
                        }
                        /*If there are episodes the user hasn't seen, returns the title*/
                        if ($item2["season"]>intval($item1["season"]) || $item2["episode"]>intval($item1["episode"])) {
                            $item = array();
                            $item["name"] = (string)$id;
                            $item["title"]=$result["title"];
                            $item["poster"] = $result["poster"];
                            $item["type"] = $result["type"];
                            $item["year"] = $result["year"];
                            $item["genre"] = $result["genre"];
                            $item["dvd"] = $result["dvd"];
                            $item["released"] = $result["released"];
                            $item["imdbRating"] = $result["imdbRating"];
                            $item["loggedIn"] = $loggedIn;
                            $array[] = $item;
                        }
                        
                    }
                    else if ($_GET["action"]==="unseen" && $result["type"]==="Movie") {
                        /*Same for dvd release dates*/
                        $dvd = $result["dvd"];
                        if ($dvd!== "N/A") {
                            if (strtotime($dvd)<strtotime('today'))  {
                                $item = array();
                                $item["name"] = (string)$id;
                                $item["title"]=$result["title"];
                                $item["poster"] = $result["poster"];
                                $item["type"] = $result["type"];
                                $item["year"] = $result["year"];
                                $item["genre"] = $result["genre"];
                                $item["dvd"] = $result["dvd"];
                                $item["released"] = $result["released"];
                                $item["imdbRating"] = $result["imdbRating"];
                                $item["loggedIn"] = $loggedIn;
                                $array[] = $item;
                            }
                        }
                    }
                    /*Only follow list is called, no need to check watched titles*/
                    else if ($_GET["action"]==="followlist") {
                        $item = array();
                        $item["name"] = (string)$id;
                        $item["title"]=$result["title"];
                        $item["poster"] = $result["poster"];
                        $item["type"] = $result["type"];
                        $item["year"] = $result["year"];
                        $item["genre"] = $result["genre"];
                        $item["dvd"] = $result["dvd"];
                        $item["released"] = $result["released"];
                        $item["imdbRating"] = $result["imdbRating"];
                        $item["loggedIn"] = $loggedIn;
                        $array[] = $item;
                    }
                }
            }
            
        }
    }
}
echo json_encode($array);