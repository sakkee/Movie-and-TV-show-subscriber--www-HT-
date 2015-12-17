<?php
    $settings = array();
    /*Steam API Key from https://steamcommunity.com/dev/apikey */
    $settings['SteamAPIKey'] = "";
    /*Database information*/
    $settings['dbhost'] = ''; //database host i.e. "localhost"
    $settings['dbname'] = ''; //database name
    $settings['dbuser'] = ''; //database user
    $settings['dbpass'] = ''; //database password
    /*Password salt*/
    $settings['hash'] = ""; //just type a string randomly here
    /*Your site's domain*/
    $settings['domain'] = ''; //your site's domain
    /*Banner texts*/
    $settings["Title1"] = "Movie and TV show follower"; //header title
    $settings["Title2"] = "Stay tuned to the newest episodes and DVD's!"; //header title 2
    
    /*Database information
    Try to connect to database*/
    try {
        $db = new PDO('mysql:hostname='.$settings["dbhost"].';dbname='.$settings["dbname"], $settings["dbuser"], $settings["dbpass"]);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo '<div class="error">'.$e->getMessage().'</div>';
        exit;
    }
    
?>