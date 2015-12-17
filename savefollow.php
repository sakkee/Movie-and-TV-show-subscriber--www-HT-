<?php
/*Save or delete follow click*/
session_start();
if (!isset($_SESSION["id"]) || !isset($_POST["showid"]) || !isset($_POST["action"])) {
    header("Location: index.php");
}
else {
    require("inc/settings.php");
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    if ($_POST["action"]==="save") {
        $stmt=$db->prepare("INSERT INTO HT_followList (userid, showid) VALUES (:userid, :showid)");
        $stmt->execute(array(":userid"=>$_SESSION["id"], ":showid"=>$_POST["showid"]));
        /*Save it for 18 hours on memcached. It can be made longer aswell*/
        $mem->set("follow".$_SESSION["id"].$_POST["showid"],1,18*60*60);
    }
    else if ($_POST["action"]==="delete") {
        $stmt=$db->prepare("DELETE FROM HT_followList WHERE userid=:userid AND showid=:showid");
        $stmt->execute(array(":userid"=>$_SESSION["id"],":showid"=>$_POST["showid"]));
        $mem->delete("follow".$_SESSION["id"].$_POST["showid"]);
    }
}
?>