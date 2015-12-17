<?php
session_start();
if (isset($_GET["text"])) {
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    $array = Array();
    $title = urlencode($_GET["text"]);
    /*I prefer not to use memcached on imdb search suggestions, as they might change and it's "only" one-time-run per searh*/
    /*Also search inputs might vary alot*/
    $imdbSuggestion = simplexml_load_file('http://www.imdb.com/xml/find?xml=1&nr=1&tt=on&q='.$title);
    /*This is a static count, how many titles we'll get (in order of importance) from the IMDb search. I prefer first 5 results*/
    /*Can be implemented to maybe read like second/third/fourth 5 titles, i.e. like next/previous page*/
    $count=0;
    foreach($imdbSuggestion->ResultSet as $rs) {
        foreach($rs->ImdbEntity as $ie) {
            if ($count>4) {
                break;
            }
            $id = $ie["id"];
            /*I use memcached on omdbapi-searches, because it will run 5 times different JSON, making unnecessary traffic*/
            $result = $mem->get($ie["id"]);
            if (!$result) {
                /*&tomatoes=true is needed to get DVD release dates*/
                /* $ie["id"] is title-specific imdb ID retrieved from IMDb api*/
                $url = 'http://www.omdbapi.com/?i='.$ie["id"].'&tomatoes=true';
                $json = file_get_contents($url);
                $details = json_decode($json);
                $item = array();
                $item["response"] = $details->Response;
                $item["type"] = $details->Type;
                /*Checking if title is found*/
                if ($details->Response==='True') {
                    /*Checking if it's TV series / Movie*/
                    if ($details->Type === 'series' || $details->Type ==='movie') {
                        $item["title"]=$details->Title;
                        
                        /*Copy the file to the server, as the imdb api doesn't support hotlinking*/
                        /*Add "no-poster.jpg" if no poster was found*/
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
                /*Save the title information for the next 18 hours on memcached. Note: maybe wiser would be to save until next day*/
                $mem->set($ie["id"],$item,60*60*18);
            }
            
            $result = $mem->get($ie["id"]);
            /*loggedIn is used to get to know whether the user can click on follow/seen buttons*/
            $loggedIn = isset($_SESSION["id"]);
            if ($result) {
                if ($result["response"]==='True') {
                    if ($result["type"]==='Series' || $result["type"]==='Movie') {
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
                        $count+=1;
                        $array[] = $item;
                    }
                }
                
            }
        }
    }
    echo json_encode($array);
}
else {
    header("Location: index.php");
}
?>