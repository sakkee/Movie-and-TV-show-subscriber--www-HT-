<?php
/*Returns and saves seasons (and episode count of each one) of given titles*/
/*Also saves json data to data/lastepisodes.json to keep a count on the last available episode on each tv show*/
session_start();
if (isset($_GET["id"])) {
    $id = $_GET["id"];
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    $array=Array();
    /*Date is needed to know whether the episode release/dvd release date has passed or not*/
    $date = date('Y-m-d');
    $day = explode("-",$date);
    $over=true;
    $result = $mem->get($id."season");
    
    if (!$result) {
        /*Reads season datas of the given title*/
        $json = file_get_contents('http://www.omdbapi.com/?i='.$id.'&Season=1');
        $details = json_decode($json);
        /*$season2 and $epCount2 are last available episodes. $season1 and $epCount1 are total seasons/episodes*/
        $season = 1;
        $season2=0;
        $epCount=0;
        $epCount2=0;
        while ($details->Response === "True") {
            $seasonArr = array();
            $epCount=0;
            if ($over) {
                $season2+=1;
            }
            foreach($details->Episodes as $ep) {
                $item=array();
                $item["id"] = $ep->imdbID;
                $item["episode"] = $ep->Episode;
                $item["released"] = $ep->Released;
                $item["title"] = $ep->Title;
                $seasonArr[] = $item;
                
                /*Checks if date has passed or not*/
                /*Default format: Y-m-d, thus [0] is year, [1] month, [2] day*/
                $epDate = explode("-",$ep->Released);
                if ($day[0]===$epDate[0] && $over) {
                    if ($day[1]===$epDate[1]) {
                        if ($day[2]<$epDate[2]) {
                            $over=false;
                            if ($epCount==0) $season2-=1;
                        }
                    }
                    else if ($day[1]<$epDate[1]) {
                        $over=false;
                        if ($epCount==0) $season2-=1;
                    }
                }
                elseif ($day[0]<$epDate[0] && $over) {
                    $over=false;
                    if ($epCount==0) $season2-=1;
                }
                if ($over) {
                    $epCount+=1;
                    $epCount2=$epCount;
                }
            }
            $array[] = $seasonArr;
            $season+=1;
            $json = file_get_contents('http://www.omdbapi.com/?i='.$id.'&Season='.$season);
            $details = json_decode($json);
        }
        if ($over) $epCount2 = $epCount;
        /*Appends or updates data on the lastepisodes.json*/
        
        /*
        This might be the most important fix needed in the project: the lastepisodes.json gets updated ONLY when the title is opened/clicked (i.e. this php file)
        If no one opens a title for a long time, and the user instead only presses F5 in his profile page waiting for new titles, the last episode isn't updated
        And then the user might not get to know about new episode releases.
        */
        
        $jsonString = file_get_contents('data/lastepisodes.json');
        $data = json_decode($jsonString);
        unset($jsonString);
        if (!isset($data->$id)) {
            $data->$id=(object) array("season"=>$season2,"episode"=>$epCount2);
            file_put_contents('data/lastepisodes.json',json_encode($data));
        }
        else if ($data->$id->season != $season2 || $data->$id->episode != $epCount2) {
            unset($data->$id);
            $data->$id=(object) array("season"=>$season2,"episode"=>$epCount2);
            file_put_contents('data/lastepisodes.json',json_encode($data));
        }
        $mem->set($id.'lastEp',array("season"=>$season2,"episode"=>$epCount2),18*60*60);
        unset($data);
        /*Saves it in memory for 18 hours, would be wiser to save until next day*/
        $mem->set($id."season",$array,18*60*60);
    }
    $result = $mem->get($id."season");
    $arr=Array();
    if ($result) {
        for ($i=0;$i<count($result);++$i) {
            $ssn = Array();
            for ($j=0;$j<count($result[$i]);++$j) {
                $ssn[] = $result[$i][$j]["released"];
            }
            $arr[] = $ssn;
        }
    }
    echo json_encode($arr);
}
else {
    header("Location: index.php");
}
?>