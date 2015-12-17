<?php
/*Returns the last seen episode (and season) of a title*/
session_start();
if (!isset($_GET["showid"])) {
    header("Location: index.php");
}
$item=Array();
/*0 and 0 are used when the user hasn't seen any episode*/
$item["season"] = 0;
$item["episode"] = 0;
if (isset($_SESSION["id"])) {
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    $result = $mem->get($_SESSION["id"].$_GET["showid"]);
    if (!$result || $result["season"]===null || $result["episode"]===null) {
        require("inc/settings.php");
        $stmt=$db->prepare("SELECT * FROM HT_seenList WHERE userid=:userid AND showid=:showid");
        $stmt->execute(array(":userid"=>$_SESSION["id"], ":showid"=>$_GET["showid"]));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $item=Array();
        if ($row) {
            $item["season"] = $row["season"];
            $item["episode"] = $row["episode"];
        }
        /*Saves it for 24 hours. Can be made to last a longer time*/
        $mem->set($_SESSION["id"].$_GET["showid"],$item,24*60*60);
    }
    else {
        $item["season"] = $result["season"];
        $item["episode"] = $result["episode"];
    }
}
echo json_encode($item);
?>