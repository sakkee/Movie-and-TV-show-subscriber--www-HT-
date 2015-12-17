<?php
/*One really messy file to handle registration, login and steamlogin*/
session_start();
if(isset($_SESSION['username'])) {
    header('Location: index.php');
}
require("inc/settings.php");
/*Openid is needed for steam login*/
require_once ("inc/openid.php");
$OpenID = new LightOpenID($settings["domain"]);

/*Returns a message that will tell the user what went wrong*/
$results = array(
    'error'=>true,
    'msg'=>"An error occured!"
);
/*This function's credits go to nikey646 user on YouTube*/
/*Retrives steam profile data from Steam*/
function get_contents($URL){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $URL);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
/*Checks if the steam login has begun (OpenID->mode isn't set yet)*/
if (!$OpenID->mode && isset($_GET["steamlogin"])) {
    $OpenID->identity = "http://steamcommunity.com/openid";
    header("Location: {$OpenID->authUrl()}");
    
}
else if ($OpenID->mode == "cancel") {
    $results["msg"] = "You did cancel the authentication, didn't you?";
    echo json_encode($results);
    header("Location: index.php");
}
else if ($OpenID->mode){
    $SteamAuth = $OpenID->validate() ? $OpenID->identity : null;
    if ($SteamAuth !== null) {
        /*Succesful login*/
        $Steam64 = str_replace("http://steamcommunity.com/openid/id/", "", $SteamAuth);
        $profile = get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$settings["SteamAPIKey"]}&steamids={$Steam64}");
        $json_decoded = json_decode($profile);
        
        foreach ($json_decoded->response->players as $player)
        {
            $_SESSION["username"] = $player->personaname;
        }
        /*Checks if account exists*/
        $stmt = $db->prepare("SELECT * FROM HT_accounts WHERE steamid=:steamid");
        $stmt->execute(array(":steamid"=>$Steam64));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            /*Note: steam users don't need a password. They only need unique steam id and user id that is given to them*/
            $stmt = $db->prepare("INSERT INTO HT_accounts (steamid) VALUES(:f1)");
            $stmt->execute(array(":f1"=>$Steam64));
        }
        /*Retrieve the user id*/
        $stmt = $db->prepare("SELECT * FROM HT_accounts WHERE steamid=:steamid");
        $stmt->execute(array(":steamid"=>$Steam64));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION["id"] = $row["id"];
            
    }
    header("Location: index.php");
}
else {
    /*This monster handles the registering / login*/
    if (isset($_POST["action"])) {
        if ($_POST["action"]=="toRegister") {
            if (isset($_POST["username"]) && isset($_POST["password1"]) && isset($_POST["password2"])) {
                $username = $_POST["username"];
                if (strlen($username)>2 || strlen($username)<13) {
                    $pw = $_POST["password1"];
                    if ($pw === $_POST["password2"]) {
                        if (strlen($pw) > 7 && strlen($pw) < 256) {
                            /*Checks if username is alphanumeric. We don't need to do the same to the password, because password goes through hashing
                            before entered to the SQL query*/
                            if (ctype_alnum($username)) {
                                $stmt = $db->prepare("SELECT * FROM HT_accounts WHERE username=:username");
                                $stmt->execute(array(":username"=>$username));
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                if (!$row) {
                                    $hashedPw = sha1($pw.$settings["hash"]);
                                    $stmt = $db->prepare("INSERT INTO HT_accounts (username, password) VALUES(:f1, :f2)");
                                    $stmt->execute(array(":f1"=>$username,":f2"=>$hashedPw));
                                    $stmt = $db->prepare("SELECT * FROM HT_accounts WHERE username=:username AND password=:password");
                                    $stmt->execute(array(":username"=>$username, ":password"=>$hashedPw));
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($row) {
                                        $_SESSION["username"] = $username;
                                        $_SESSION["id"] = $row["id"];
                                        $results["error"] = false;
                                        $results["msg"] = "You in da hood now!";
                                    }
                                    else {
                                        $results["msg"] = "Something very mysterious happened.";
                                    }
                                }
                                else {
                                    $results["msg"] = "The username you consider a special snowflake has been stolen. Pick another one.";
                                }
                            }
                            else {
                                $results["msg"] = "Username must consist of a-z, A-Z and 0-9 characters.";
                            }
                        }
                        else {
                            $results["msg"] = "Password must be between 8 and 255 characters long.";
                        }
                    }
                    else {
                        $results["msg"] = "Passwords aren't the same!";
                    }
                }
                else {
                    $results["msg"] = "Username must be between 3 and 12 characters long!";
                } 
            }
            else {
                $results["msg"] = "Please fill all the forms!";
            }
        }
        else if ($_POST["action"]=="toLogin") {
            if (isset($_POST["username"]) && isset($_POST["password"])) {
                if (ctype_alnum($_POST["username"])) {
                    $hashedPw = sha1($_POST["password"].$settings["hash"]);
                    $stmt = $db->prepare("SELECT * FROM HT_accounts WHERE username=:username AND password=:password");
                    $stmt->execute(array(":username"=>$_POST["username"], ":password"=>$hashedPw));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $_SESSION["id"] = $row["id"];
                        $_SESSION["username"] = $row["username"];
                        $results["error"] = false;
                    }
                    else {
                        $results["msg"] = "Username or password wrong!";
                    }
                }
                else {
                    $results["msg"] = "Something strange with your input.";
                }
            }
            else {
                $results["msg"] = "Please fill all the forms!";
            }
        }
        echo json_encode($results);
    }
    else {
        header("Location: index.php");
    }
    
}
if(isset($_GET["logout"])) {
    session_destroy();
    header("Location: index.php");
    
}
?>