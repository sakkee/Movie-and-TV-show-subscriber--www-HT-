<?php
$title = 'Installing...';
include("inc/settings.php");
include("inc/header.php");
$success=1;
$stmt=$db->prepare("CREATE TABLE HT_accounts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username text(20),
    password text(256),
    steamid text(40)
    )");
if ($stmt->execute()) {
    echo "Created table HT_accounts...<br>";
}
else {
    $success=0;
    echo "Failed to create table HT_accounts!<br>";
}
$stmt=$db->prepare("CREATE TABLE HT_followList (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    userid INT(11) NOT NULL,
    showid text(40) NOT NULL
    )");
if ($stmt->execute()) {
    echo "Created table HT_followList...<br>";
}
else {
    $success=0;
    echo "Failed to create table HT_followList!<br>";
}
$stmt=$db->prepare("CREATE TABLE HT_seenList (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    userid INT(11) NOT NULL,
    showid text(40) NOT NULL,
    season INT(5),
    episode INT(6)
    )");
if ($stmt->execute()) {
    echo "Created table HT_seenList...<br>";
}
else {
    $success=0;
    echo "Failed to create table HT_seenList!<br>";
}
if ($success) {
    echo "Tables succesfully added to the database! Please remove this install.php file now.";
}
else {
    echo "Couldn't insert all tables. Insert manually to your database:
        <ul>HT_accounts<li>id, int, primary key, auto increment</li><li>username, text, allow nulls</li><li>password, text, allow nulls</li><li>steamid, text, allow nulls</li></ul>
        <ul>HT_followList<li>id, int, primary key, auto increment</li><li>userid, int, no nulls</li><li>showid, text, no nulls</li></ul>
        <ul>HT_seenList<li>id, int, primary key, auto increment</li><li>userid, int, no nulls</li><li>showid, text, no nulls</li><li>season, int, allow nulls</li><li>episode, int, allow nulls</li></ul>
    ";
}
include("inc/footer.php");
?>