<?php
/*Save a seen click on the database and memcached*/
session_start();
if (!isset($_SESSION["id"]) || !isset($_POST["showid"]) || !isset($_POST["season"]) || !isset($_POST["episode"])) {
    header("Location: index.php");
}
else {
    require("inc/settings.php");
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    /*First delete from the table. Note: use of "update or insert" / delete might be a wiser and less-consumpting command*/
    $stmt=$db->prepare("DELETE FROM HT_seenList WHERE userid=:userid AND showid=:showid");
    $stmt->execute(array(":userid"=>$_SESSION["id"],"showid"=>$_POST["showid"]));
    /*If episode and season are 0, then user is deleting the seen key completely, if not, the user is updating the old command*/
    $ep="0";
    if ($_POST["season"]!=="0") {
        $ep=$_POST["episode"];
    }
    if ($ep!=="0" && $_POST["season"]!=="0") {
        $stmt=$db->prepare("INSERT INTO HT_seenList (userid, showid, season, episode) VALUES(:userid, :showid, :season, :episode)");
        $stmt->execute(array(":userid"=>$_SESSION["id"], ":showid"=>$_POST["showid"], ":season"=>$_POST["season"],":episode"=>$ep));
    }
    $item=Array();
    $item["season"] = $_POST["season"];
    $item["episode"] = $ep;
    /*Save it for the next 24 hours. It can be made longer aswell*/
    $mem->set($_SESSION["id"].$_POST["showid"],$item,24*60*60);
}
?>