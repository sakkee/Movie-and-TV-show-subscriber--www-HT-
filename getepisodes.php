<?php
/*Returns the episodes of the given season of the title*/
if (isset($_GET["id"]) && isset($_GET["season"])) {
    $id = $_GET["id"];
    $season = $_GET["season"];
    /*Returns it from memcached, as the season list is opened before the episode list and opening the season list the episode list is saved aswell*/
    $mem = new Memcached();
    $mem->addServer("127.0.0.1", 11211) or die("Unable to connect");
    $result = $mem->get($id."season");
    echo json_encode($result[$season-1]);
}
else {
    header("Location: index.php");
}
?>