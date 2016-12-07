/**
 * clientaccess.js is part of Wallace Point of Sale system (WPOS)
 *
 * clientaccess.js Provides base functionality for the customer login area.
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      Class created 15/1/13 12:01 PM
 */

function changehash(hash){
    document.location.hash = hash;
}

function setActiveMenuItem(secname){
    // remove active from previous
    $(".nav-list li").removeClass('active');
    $(".submenu li").removeClass('active');
    // add active to clicked
    var li = $('a[href="#!'+secname+'"]').parent('li');
    $(li).addClass('active');
    // set the parent item if its a submenu
    if ($(li).parent('ul').hasClass('submenu')){
        $(li).parent('ul').parent('li').addClass('active');
    }
}
var WPOS;
//On load page, init the timer which check if the there are anchor changes
$(function(){
    // initiate WPOS object
    WPOS = new WPOSClientDash();
    // init
    WPOS.isLogged();
});
function WPOSClientDash(){
    // AJAX PAGE LOADER FUNCTIONS
    var currentAnchor = '0';
    var currentsec = '';
    var lastAnchor = null;
    // Are there anchor changes, if there are, calculate request and send
    this.checkAnchor = function(){
        //Check if it has changes
        if((currentAnchor != document.location.hash)){
            lastAnchor = currentAnchor;
            currentAnchor = document.location.hash;
            if(currentAnchor){
                var splits = currentAnchor.substring(2).split('&');
                //Get the section
                sec = splits[0];
                // has the section changed
                if (sec==currentsec &&  currentAnchor.indexOf('&query')!=-1){
                    // load some subcontent
                } else {
                    // set new current section
                    currentsec=sec;
                    // set menu items active
                    setActiveMenuItem(sec);
                    // close mobile menu
                    if ($("#menu-toggler").is(":visible")){
                        $("#sidebar").removeClass("display");
                    }
                    // start the loader
                    WPOS.util.showLoader();
                    //Creates the  string callback. This converts the url URL/#! &amp;amp;id=2 in URL/?section=main&amp;amp;id=2
                    delete splits[0];
                    //Create the params string
                    var params = splits.join('&');
                    var query = params;
                    //Send the ajax request
                    WPOS.loadPageContent(query);
                }
            } else {
                WPOS.goToHome();
            }
        }
    };
    var timerId;
    this.startPageLoader = function(){
        timerId = setInterval("WPOS.checkAnchor();", 300);
    };
    this.stopPageLoader = function(){
        currentAnchor = '0';
        clearInterval(timerId);
    };
    this.loadPageContent = function(query){
        $.get("content/"+sec+".php", query, function(data){
            if (data=="AUTH"){
                WPOS.sessionExpired();
            } else {
                $("#maincontent").html(data);
            }
        }, "html");
    };
    this.goToHome = function(){
        changehash("!dashboard");
    };
    var curuser = false;
    // authentication
    this.isLogged = function(){
        WPOS.util.showLoader();
        var data = WPOS.getJsonData("hello");
        curuser = data.user;
        if (curuser!==false){
            WPOS.initCustomers();
        }
        $("#loginbizlogo").attr("src", data.bizlogo);
        $("title").text("My "+data.bizname+" Account");
        $("#headerbizname").text(data.bizname);
        $('#loadingdiv').hide();
        $('#logindiv').show();
        $("#loginbutton").removeAttr('disabled', 'disabled');
        WPOS.util.hideLoader();
    };
    this.getUser = function(){
        return curuser;
    };
    this.login = function () {
        WPOS.util.showLoader();
        performLogin();
    };
    function performLogin(){
        WPOS.util.showLoader();
        var loginbtn = $('#loginbutton');
        // disable login button
        $(loginbtn).attr('disabled', 'disabled');
        $(loginbtn).val('Proccessing');
        // auth is currently disabled on the php side for ease of testing. This function, however will still run and is currently used to test session handling.
        // get form values
        var userfield = $("#loguser");
        var passfield = $("#logpass");
        var username = userfield.val();
        var password = passfield.val();
        // hash password
        password = WPOS.util.SHA256(password);
        // authenticate
        curuser = WPOS.sendJsonData("auth", JSON.stringify({username: username, password: password}));
        if (curuser!==false){
            WPOS.initCustomers();
        }
        passfield.val('');
        WPOS.util.hideLoader();
        $(loginbtn).val('Login');
        $(loginbtn).removeAttr('disabled', 'disabled');
    }
    this.logout = function () {
        var answer = confirm("Are you sure you want to logout?");
        if (answer) {
            WPOS.util.showLoader();
            performLogout();
        }
    };
    function performLogout(){
        WPOS.util.showLoader();
        WPOS.stopPageLoader();
        WPOS.getJsonData("logout");
        $("#modaldiv").show();
        WPOS.util.hideLoader();
    }
    this.sessionExpired = function(){
        WPOS.stopPageLoader();
        $("#modaldiv").show();
        alert("Your session has expired, please login again.");
        WPOS.util.hideLoader();
    };
    this.initCustomers = function(){
        fetchConfigTable();
        WPOS.startPageLoader();
        $("#modaldiv").hide();
    };
    // data handling functions
    this.getJsonData = function(action){
        return getJsonData(action)
    };
    function getJsonData(action) {
        // send request to server
        var response = $.ajax({
            url     : "/customerapi/"+action,
            type    : "GET",
            dataType: "text",
            timeout : 10000,
            cache   : false,
            async   : false
        });
        if (response.status == "200") {
            var json = JSON.parse(response.responseText);
            var errCode = json.errorCode;
            var err = json.error;
            if (err == "OK") {
                // echo warning if set
                if (json.hasOwnProperty('warning')){
                    alert(json.warning);
                }
                return json.data;
            } else {
                if (errCode == "auth") {
                    WPOS.sessionExpired();
                    return false;
                } else {
                    alert(err);
                    return false;
                }
            }
        }

        alert("There was an error connecting to the server: \n"+response.statusText);
        return false;
    }

    this.sendJsonData = function  (action, data, returnfull) {
        // send request to server
        var response = $.ajax({
            url     : "/customerapi/"+action,
            type    : "POST",
            data    : {data: data},
            dataType: "text",
            timeout : 10000,
            cache   : false,
            async   : false
        });
        if (response.status == "200") {
            var json = JSON.parse(response.responseText);
            if (json == null) {
                alert("Error: The response that was returned from the server could not be parsed!");
                return false;
            }
            if (returnfull==true)
                return json;
            var errCode = json.errorCode;
            var err = json.error;
            if (err == "OK") {
                // echo warning if set
                if (json.hasOwnProperty('warning')){
                    alert(json.warning);
                }
                return json.data;
            } else {
                if (errCode == "auth") {
                    WPOS.sessionExpired();
                    return false;
                } else {
                    alert(err);
                    return false;
                }
            }
        }
        alert("There was an error connecting to the server: \n"+response.statusText);
        return false;
    };
    // data & config
    var configtable;

    this.currency = function(){
        return configtable.general.curformat;
    };

    this.getTaxTable = function () {
        if (configtable == null) {
            return false;
        }
        return configtable.tax;
    };

    this.getConfigTable = function () {
        if (configtable == null) {
            return false;
        }
        return configtable;
    };

    function fetchConfigTable() {
        configtable = getJsonData("config");
    }

    // Load globally accessable objects
    this.util = new WPOSUtil();
    this.transactions = new WPOSCustomerTransactions();
}