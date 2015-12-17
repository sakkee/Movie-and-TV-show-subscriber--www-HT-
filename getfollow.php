<?php
/*Returns 1 or 0 whether the user follows the title*/
/*Similar to the getseen.php*/
session_start();
if (!isset($_GET["showid"])) {
    header("Location: index.php");
}
$following = 0;
if (isset($_SESSION["id"])) {
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    $result = $mem->get("follow".$_SESSION["id"].$_GET["showid"]);
    /*First reads it from the memcached*/
    if (!$result) {
        /*If no hits, then reads it from the sql*/
        require("inc/settings.php");
        $stmt=$db->prepare("SELECT * FROM HT_followList WHERE userid=:userid AND showid=:showid");
        $stmt->execute(array(":userid"=>$_SESSION["id"], ":showid"=>$_GET["showid"]));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $following = 1;
        }
        /*This could be a longer time aswell*/
        $mem->set("follow".$_SESSION["id"].$_GET["showid"],$following,24*60*60);
    }
    else {
        $following=1;
    }
}
echo json_encode($following);
?>