/*Passes registration inputs, shows output in case of an error*/
function register(username, password1, password2) {
    var request = $.ajax({
        url: "registration.php",
        type: "POST",
        data: {
            "action": "toRegister",
            "username": username,
            "password1": password1,
            "password2": password2
        },
        dataType: "html",
        success: function(data) {
            var json = JSON.parse(data);
            
            if (json.error) {
                $("#error").innerHTML = json.msg;
                document.getElementById("error").innerHTML = json.msg;
            }
            else {
                window.location.href = "index.php";
            }
        }
    });
}
/*Passes login inputs, shows output in case of an error*/
function login(username,password) {
    var request = $.ajax({
        url:"registration.php",
        type: "POST",
        data: {
            "action": "toLogin",
            "username": username,
            "password": password
        },
        dataType: "html",
        success: function(data) {
            var json = JSON.parse(data);
            if (json.error) {
                document.getElementById("error").innerHTML = json.msg;
            }
            else {
                window.location.href = "index.php";
            }
        }
        
    })
}
/*Saves to the seenlist (or deletes from there)*/
function saveSeen(id,season,episode) {
    if (!loggedIn) return;
    var request = $.ajax({
        url:"saveseen.php",
        type: "POST",
        data: {
            "action": "save",
            "showid": id,
            "season": season,
            "episode": episode
        },
        dataType:"html"
    });
}
/*Saves / deletes from the follow list, similar to the seen function*/
function followFunction(id,action) {
    if (!loggedIn) return;
    var request = $.ajax({
        url:"savefollow.php",
        type: "POST",
        data: {
            "action": action,
            "showid": id
        },
        dataType:"html"
    });
}
/*Graphic output of the episode list*/
/*Object is the season element, and the episode list is inserted after it*/
function episodeList(id,season, object) {
    $.getJSON("getseen.php?showid="+id,function(readData) {
        /*Checks the last episode the user has seen of the title*/
        $.getJSON("getepisodes.php?id="+id+"&season="+season,function(data) {
            var htmltext="";
            var list = document.createElement('div');
            list.id='episodeList';
            list.classList.add('episodeList');
            var epCount=1;
            data.forEach(function(entry) {
                /*For each episode we add an own element and check the date in case the episode hasn't been released yet*/
                var date = new Date(entry.released);
                var day = entry.released.split("-");
                htmltext+="<div id='"+entry.id+"' class='episode' name='"+entry.episode+"'><span class='epName'>"+entry.title+"</span>";
                if (entry.released=='N/A') {
                    htmltext+="<span class='notReleased'>N/A</span>";
                }
                else if (date>dateNow) {
                    htmltext+="<span class='notReleased'>"+day[2]+"."+day[1]+"."+day[0].slice(-2)+"</span>";
                }
                else {
                    htmltext+="<span class='Released'>"+day[2]+"."+day[1]+"."+day[0].slice(-2)+"</span>";
                    /*checks if the user has logged in, so we can redirect to the login page when a non logged user tries to add to the watchlist or seenlist*/
                    if (loggedIn) {
                        /*If season of the episode is newer than the last season of an episode the user has seen, or couldn't read the data for some reason*/
                        if (parseInt(readData.season) < parseInt(season) || isNaN(parseInt(readData.season)) || isNaN(parseInt(readData.episode))) {
                            htmltext+="<div title='Mark as seen' class='see'></div>";
                        }
                        else if(parseInt(readData.season) == parseInt(season)) {
                            /*If season is the same, check which one's episode is smaller*/
                            if (readData.episode<epCount) {
                                htmltext+="<div title='Mark as seen' class='see'></div>";
                            }
                            else {
                                htmltext+="<div title='Mark as unseen' class='seen'></div>";
                            }
                        }
                        else {
                            htmltext+="<div title='Mark as unseen' class='seen'></div>";
                        }
                    }
                    else {
                        /*User hasn't logged in*/
                        htmltext+="<div onclick='goToLoginForm()' id='notLoggedIn' title='Mark as seen' class='see'></div>";
                    }
                    /*Add some information on the episode element, such as season number and show title imdb id*/
                    htmltext+="<div class='seasonNumber' id="+season+"></div>";
                    htmltext+="<div class='showId' id="+id+"></div>";
                }
                htmltext+="</div>";
                epCount++;
            });
            /*Adds the episode to the list, the list is first removed from the body and then the list is added to the body*/
            list.innerHTML=htmltext;
            $('.episodeList').remove();
            $(list).insertAfter(object);
            $(".mainBody").css("height","100%");
        });
    });
}
/*Adds seasons on the body (graphical output)*/
/*Similar to the episodeList function*/
/*Object is the main title of the tv show, and the season list is inserted after it*/
function seasonList(id,object) {
    /*Gets the last episode/season the user has seen*/
    $.getJSON("getseen.php?showid="+id,function(readData) {
        $.getJSON("getseries.php?id="+id,function(data) {
            var htmltext="";
            var list = document.createElement('div');
            var released = "Released!";
            list.id='seasonList';
            list.classList.add('seasonList');
            var count=1;
            data.forEach(function(entry) {
                var count2=1;
                for (var i=0;i<entry.length;i++) {
                    if (released!="Not released") {
                        var date = new Date(entry[i]);
                        if (date>dateNow || date=='Invalid Date') {
                            if (count2==1) {
                                released ="Not released";
                            }
                            else {
                                released ="In progress";
                            }
                        }
                        else {
                            released="Released!";
                        }
                        count2++;
                    }
                    
                }
                if (released=="Released!") {
                    htmltext+="<div id='"+count+"' class='season' name='"+id+"'>Season "+count+"<span class='Released'>"+released+"</span>";
                    if (loggedIn) {
                        if (parseInt(readData.season) < count || isNaN(parseInt(readData.season)) || isNaN(parseInt(readData.episode))) {
                            htmltext+="<div title='Mark as seen' class='see'></div>";
                        }
                        else if(parseInt(readData.season) == count) {
                            if (parseInt(readData.episode)<entry.length) {
                                htmltext+="<div title='Mark as seen' class='see'></div>";
                            }
                            else {
                                htmltext+="<div title='Mark as unseen' class='seen'></div>";
                            }
                        }
                        else {
                            htmltext+="<div title='Mark as unseen' class='seen'></div>";
                        }
                    }
                    else {
                        htmltext+="<div onclick='goToLoginForm()' id='notLoggedIn' title='Mark as seen' class='see'></div>";
                    }
                    htmltext+="<div class='episodeCount' id="+entry.length+"></div></div>";
                }
                else if (released=="Not released") {
                    htmltext+="<div id='"+count+"' class='season' name='"+id+"'>Season "+count+"<span class='notReleased'>"+released+"</span></div>";
                }
                else {
                    htmltext+="<div id='"+count+"' class='season' name='"+id+"'>Season "+count+"<span class='inProgress'>"+released+"</span></div>";
                }
                count++;
            });
            list.innerHTML = htmltext;
            $('.seasonList').remove();
            $(list).insertAfter(object);
            $(".mainBody").css("height","100%");
        });
    });
}
/*Constructs the title list (graphical output), given an argument of a json array of titles*/
/*Sort of similar to episodeList() and seasonList()*/
function titleConstructor(data,text) {
    ready=0;
    $('input[name=searchField]').val("");
    htmltext = "";
    /*Removes previous titleList*/
    $(document).find(".titleList").remove();
    var list = document.createElement('div');
    list.id = 'titleList';
    list.classList.add('titleList');
    count = 0;
    /*Checks for the length of data*/
    if(data.length) {
        data.forEach(function(entry) {
            $.getJSON("getfollow.php?showid="+entry.name,function(readData) {
                /*Gets whether the user follows the title or not*/
                if (count==0) {
                    /*Adds the logged in to the loggedIn var, stupid but working*/
                    loggedIn = entry.loggedIn;
                    htmltext = htmltext + "<div id='title" + entry.type + "' name='"+entry.name+"' class='titleFirst'>";
                    htmltext = htmltext  + "<img class='posterImg' src='"+entry.poster+"'>";
                    htmltext = htmltext + "<div class='firstTitle'><p class='firstTitle'>" + entry.title + "</p>";
                    htmltext = htmltext + "<p class='firstTitle1'>";
                }
                else {
                    htmltext = htmltext + "<div id='title" + entry.type + "' name='"+entry.name+"' class='title'>";
                    htmltext = htmltext  + "<img style='width:150px;height:173px;vertical-align:middle' src='"+entry.poster+"'>";
                    htmltext = htmltext + "<div class='textBox'><p class='titleName'>" + entry.title + "</p>";
                    htmltext = htmltext + "<p class='titleInfo'>";
                }
                /*Movie has a different kind of infotext than TV show*/
                if (entry.type=="Movie") {
                    htmltext = htmltext + "Released: " + entry.released + "<br>Type: " + entry.type + "<br>Genre: " + entry.genre + "<br>IMDb Score: " + entry.imdbRating + "<br>DVD: "+entry.dvd+"</p></div>";
                }
                else {
                    htmltext = htmltext + "Year: " + entry.year + "<br>Type: " + entry.type + "<br>Genre: " + entry.genre + "<br>IMDb Score: " + entry.imdbRating + "</p></div>";
                }
                if (loggedIn==0) htmltext = htmltext + "<div onclick='goToLoginForm()' id='notLoggedIn' title='Add to watchlist' class='follow'></div>";
                else {
                    if (!readData) htmltext = htmltext + "<div title='Add to watchlist' class='follow'></div>";
                    else htmltext = htmltext + "<div title='Remove from watchlist' class='followed'></div>";
                }
               htmltext = htmltext + "</div>";
               count++;
               list.innerHTML = htmltext;
               $(".mainBody").append(list);
               $(".mainBody").css("height","100%");
               if (count==data.length) ready=1;
            });
        })
        
    }
    else {
        ready=1;
        htmltext =  "<div class='infoTitle'>Couldn't find anything for " + text + " :(</div>";
        list.innerHTML = htmltext;
        $(".mainBody").append(list);
        $(".mainBody").css("height","100%");
    }
}
/*Search function to use the titleConstructor(), based whether we use the imdb search, or get all followed titles or only titles that are the same time unseen, released and followed*/
/*Given as arguments a text (search input) and an action (default, followlist, unseen)*/
function searchFunction(text,action) {
    ready=0;
    if (action=="default") {
        $.getJSON("search.php?text="+text,function(data) {
            titleConstructor(data,text);
        });
    }
    else if (action=="followlist") {
        $.getJSON("getfollowlist.php?action="+action,function(data) {
            titleConstructor(data,"");
        });
    }
    else if (action=="unseen") {
        $.getJSON("getfollowlist.php?action="+action,function(data) {
            titleConstructor(data, "");
        });
    }
}
/*"Front page"*/
function indexForm() {
    var htmltext = "<input type='text' id='searchField' class='searchField' name='searchField' placeholder='Movie name, TV series name, etc...'><input type='button' class='button' id='searchButton' value='Search!'>";
    document.getElementById("actionBox").innerHTML = htmltext;
    var htmltext2 = '<p class="infotextBox">Go to my followed titles</p><input type="button" class="toProfile" value="My list">';
    document.getElementById("settingsBox").innerHTML = htmltext2;
    if (document.getElementById("titleList")) {
        $(document.getElementById("titleList")).remove();
    }
    
}
/*"Profile page"*/
function profileForm() {
    var htmltext = "<input type='button' id='getFollowList' class='button' value='All followed titles'><input type='button' id='getUnseenList' class='button' value='Unseen episodes & DVDs'>";
    document.getElementById("actionBox").innerHTML = htmltext;
    var htmltext2 = '<p class="infotextBox">Go to main page </p><input type="button" class="toIndex" value="Main page">';
    document.getElementById("settingsBox").innerHTML = htmltext2;
    if (ready==1) searchFunction("","followlist");
    
}
/*Register window*/
function registerForm() {
    var htmltext = '<h4>Register</h4><div id="error"></div><input type="text" name="username" placeholder="Username"><br>';
    htmltext+= '<input type="password" name="password" placeholder="Password"/><br>';
    htmltext+= '<input type="password" name="password2" placeholder="Confirm password"/><br>';
    htmltext+= '<input type="button" id="registerButton" value="Register"><br><br>';
    htmltext+= 'Already have an account?<br><input type="button" class="toLogin" value="Login"><br>Or sign in using Steam<br>';
    htmltext+= '<a href="registration.php?steamlogin"><div id="steamButton"></div></a>';
    document.getElementById("loginBox").innerHTML = htmltext;
    $("#registerButton").click(function() {
        register($('input[name=username]').val(),$('input[name=password]').val(),$('input[name=password2]').val());
    })
}
/*User tries to use a member-only-function, such as mark as seen / follow, without being logged in*/
function goToLoginForm() {
    loginForm();
    $('html, body').animate({ scrollTop: 0 }, 'fast');
    $('#loginBox').effect("shake", {times:4}, 600);
}
/*Login window*/
function loginForm() {
    var htmltext = '<h4>Login</h4><div id="error"></div><input type="text" id="username" name="username" placeholder="Username">';
    htmltext+= '<br><input type="password" name="password" placeholder="Password"><br>';
    htmltext+= '<input type="button" id="loginButton" value="Login"><br><br>';
    htmltext+= 'Not yet a member? <br><input type="button" class="toRegister" value="Register!"><br>Or sign in using Steam<br>';
    htmltext+= '<a href="registration.php?steamlogin"><div id="steamButton"></div></a>';
    
    document.getElementById("loginBox").innerHTML = htmltext;
    $("#loginButton").click(function() {
        login($('input[name=username]').val(),$('input[name=password]').val());
    })
}
/*Vars. Date is used to compare to title/season/episode release dates, and used here so it will be constructed only on page loads*/
var dateObj = new Date();
var month = dateObj.getUTCMonth()+1;
var day = dateObj.getUTCDate();
var year = dateObj.getUTCFullYear();
var newdate = year + "-" + month + "-" + day;
var dateNow = new Date(newdate);
var loggedIn = 0;
var ready=1;
$(document).ready(function() {
    /*dblClick is to prevent when user clicks follow/see button so it won't be registered as a click for the title beneath it as well, simple hack*/
    var dblClick=0;
    
    /*When user clicks "Mark as seen"-button*/
    $(document).on('mousedown','.see',function() {
        if($(this).attr('id')=="notLoggedIn") return;
        /*Looks dirty*/
        $(this).attr("class", "seen");
        $(this).attr("title", "Mark as unseen");
        /*Try to find previous/next episodes and mark them seen / unseen*/
        $(this).parent().prevAll().find('.see').attr("class","seen");
        $(this).parent().prevAll().find('.seen').attr("title","Mark as unseen");
        $(this).parent().nextAll().find('.seen').attr("class","see");
        $(this).parent().nextAll().find('.see').attr("title","Mark as seen");
        
        /*Episode and season elements have different layouts, thus they need different functions*/
        if($(this).parent().attr('class')=='season') {
            /*saveSeen() arguments: imdbid, season, episode*/
            saveSeen($(this).parent().attr('name'),$(this).parent().attr('id'),$(this).parent().find('.episodeCount').attr('id'));
            /*Try to find next seasons and mark them seen*/
            $(this).parent().nextAll().find('#'+$(this).parent().attr('id').toString()).attr("class","seen");
            $(this).parent().nextAll().find('#'+$(this).parent().attr('id').toString()).attr("title","Mark as unseen");
        }
        else {
            saveSeen($(this).parent().find('.showId').attr('id'),$(this).parent().find('.seasonNumber').attr('id'),$(this).parent().attr('name'));
        }
        /*Waits 100 ms until dblClick is available again*/
        dblClick=1;
        setTimeout(function() {
            dblClick=0;
        },100);
    })
    /*User clicks "Mark as unseen"-button*/
    /*Similar to the previous listener*/
    $(document).on('mousedown','.seen',function() {
        $(this).attr("class", "see");
        $(this).attr("title", "Mark as seen");
        $(this).parent().nextAll().find('.seen').attr("class","see");
        $(this).parent().nextAll().find('.see').attr("title","Mark as seen");
        $(this).parent().prevAll().find('.see').attr("class","seen");
        $(this).parent().prevAll().find('.seen').attr("title","Mark as unseen");
        if($(this).parent().attr('class')=='season') {
            /*saveSeen() arguments: imdbid, season, episode*/
            saveSeen($(this).parent().attr('name'),$(this).parent().attr('id')-1,$(this).parent().find('.episodeCount').attr('id'));
        }
        else {
            saveSeen($(this).parent().find('.showId').attr('id'),$(this).parent().find('.seasonNumber').attr('id'),$(this).parent().attr('name')-1);
        }
        dblClick=1;
        setTimeout(function() {
            dblClick=0;
        },100);
    })
    /*When user clicks "Add to watchlist"-button*/
    $(document).on('mousedown','.follow',function() {
        if($(this).attr('id')=="notLoggedIn") return;
        $(this).attr("class", "followed");
        $(this).attr("title", "Remove from watchlist");
        /*Passes the imdbid and "save" arguments to the followfunction*/
        followFunction(($(this).parent().attr('name')),"save");
        dblClick=1;
        setTimeout(function() {
            dblClick=0;
        },100);
    })
    /*When user clicks "Remove from watchlist"-button*/
    $(document).on('mousedown','.followed',function() {
        $(this).attr("class", "follow");
        $(this).attr("title", "Add to watchlist");
        /*Passes the imdbid and "delete" arguments to the followfunction*/
        followFunction(($(this).parent().attr('name')),"delete");
        dblClick=1;
        setTimeout(function() {
            dblClick=0;
        },100);
    })
    /*When user clicks a tv show element*/
    $(document).on('mousedown','#titleSeries',function() {
        if (dblClick == 0) {
            /*Checks if the season element is opened or not*/
            if (!$(this).find('.opened').length) {
                $('.opened').remove();
                $(this).append("<div class='opened'></div>");
                /*seasonList is given arguments imdbid and this object*/
                seasonList($(this).attr('name'),$(this));
            }
            else {
                /*Hides the season list*/
                $('.seasonList').remove();
                $('.opened').remove();
            }
            
        }
    })
    /*When user clicks a season element*/
    /*Similar to the previous listener*/
    $(document).on('mousedown','.season',function() {
        if(dblClick==0) {
            if (!$(this).find('.opened').length) {
                $('.opened').remove();
                $(this).append("<div class='opened'></div>");
                /*episodeList arguments (imdbid, season number, this object)*/
                episodeList($(this).attr('name'),$(this).attr('id'),$(this));
            }
            else {
                $('.episodeList').remove();
                $('.opened').remove();
            }
        }
    })
    
    $(document).on('mousedown','.toLogin',function() {
        loginForm();
    })
    $(document).on('mousedown','.toRegister',function() {
        registerForm();
    })
    
    $(document).on('mousedown','.toLogout',function() {
        window.location.href = "registration.php?logout";
    })
    $(document).on('mousedown','.toProfile',function() {
        profileForm();
    })
    $(document).on('mousedown','.toIndex',function() {
        indexForm();
        
    })
    /*Search button, pass the input value to the search function*/
    $(document).on('mousedown','#searchButton',function()  {
        if(ready==1) searchFunction($('input[name=searchField]').val(),"default");
    })
    $(document).on('mousedown','#getUnseenList',function() {
        if(ready==1) searchFunction('','unseen');
    })
    $(document).on('mousedown','#getFollowList',function() {
        if(ready==1) searchFunction('','followlist');
    })
})