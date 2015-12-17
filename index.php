<?php
require("inc/settings.php");
$title = "Main";
include("inc/header.php");
session_start();
print "<br><div class='loginBox' id='loginBox'>";
if (!isset($_SESSION["username"])) {
    print "<p class='infotextBox'>Welcome to ".$settings['domain'].", <b>Guest</b>! </p>";
    print '<input type="button" class="toLogin" value="Login"><br><p class="infotextBox">
Not yet a member? </p><input type="button" class="toRegister" value="Register!"><br><p class="infotextBox">Or sign in using Steam </p><a href="registration.php?steamlogin"><div id="steamButton"></div></a>';
    print '</div><div id="settingsBox" class="settingsBox"><p class="infotextBox">Go to my followed titles</p><input type="button" class="toLogin" value="Please login"></div>';
}
else {
    print '<p class="infotextBox">Welcome to '.$settings["domain"].', <b>'.$_SESSION["username"].'</b>! </p>';
    print '<input type="button" class="toLogout" value="Logout"></a>';
    print '</div><div id="settingsBox" class="settingsBox"><p class="infotextBox">Go to my followed titles</p><input type="button" class="toProfile" value="My list"></div>';
}

print "<div id='actionBox'><input type='text' id='searchField' class='searchField' name='searchField' placeholder='Movie name, TV series name, etc...'><input type='button' class='button' id='searchButton' value='Search!'><script>document.getElementById('searchField').focus();</script></div>";


include("inc/footer.php");
?>