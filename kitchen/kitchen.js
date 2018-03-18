/**
 * core.js is part of Wallace Point of Sale system (WPOS)
 *
 * core.js is the main object that provides base functionality to the WallacePOS terminal.
 * It loads other needed modules and provides authentication, storage and data functions.
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
 * @since      Class created 15/1/14 12:01 PM
 */

function WPOSKitchen() {
    var WPOS = this;
    var initialsetup = false;
    this.initApp = function () {
        // set cache default to true
        $.ajaxSetup({
            cache: true
        });
        // check online status to determine start & load procedure.
        if (checkOnlineStatus()) {
            WPOS.checkCacheUpdate(); // check if application cache is updating or already updated
        } else {
            // check approppriate offline records exist
            if (switchToOffline()) {
                WPOS.initLogin();
            }
        }
    };
    var cacheloaded = 1;
    this.checkCacheUpdate = function(){
        // check if cache exists, if the app is loaded for the first time, we don't need to wait for an update
        if (window.applicationCache.status == window.applicationCache.UNCACHED){
            console.log("Application cache not yet loaded.");
            WPOS.initLogin();
            return;
        }
        // For chrome, the UNCACHED status is never seen, instead listen for the cached event, cache has finished loading the first time
        window.applicationCache.addEventListener('cached', function(e) {
            console.log("Cache loaded for the first time, no need for reload.");
            WPOS.initLogin();
        });
        // wait for update to finish: check after applying event listener aswell, we may have missed the event.
        window.applicationCache.addEventListener('updateready', function(e) {
            console.log("Appcache update finished, reloading...");
            setLoadingBar(100, "Loading...");
            location.reload(true);
        });
        window.applicationCache.addEventListener('noupdate', function(e) {
            console.log("No appcache update found");
            WPOS.initLogin();
        });
        window.applicationCache.addEventListener('progress', function(e) {
            var loaded = parseInt((100/ e.total)*e.loaded);
            cacheloaded = isNaN(loaded)?(cacheloaded+1):loaded;
            //console.log(cacheloaded);
            setLoadingBar(cacheloaded, "Updating application...");
        });
        window.applicationCache.addEventListener('downloading', function(e) {
            console.log("Updating appcache");
            setLoadingBar(1, "Updating application...");
        });
        if (window.applicationCache.status == window.applicationCache.UPDATEREADY){
            console.log("Appcache update finished, reloading...");
            setLoadingBar(100, "Loading...");
            location.reload(true);
        }
    };
    // Check for device UUID & present Login, initial setup is triggered if the device UUID is not present
    this.initLogin = function(){
        showLogin();
        if (getDeviceUUID() == null) {
            // The device has not been setup yet; User will have to login as an admin to setup the device.
            alert("The device has not been setup yet, please login as an administrator to setup the device.");
            initialsetup = true;
            online = true;
            return false;
        }
        return true;
    };
    // Plugin initiation functions
    this.initPlugins = function(){
        // load keypad if set
        setKeypad(true);
        // load printer plugin
        WPOS.print.loadPrintSettings();
    };
    this.initKeypad = function(){
        setKeypad(false);
    };
    function setKeypad(setcheckbox){
        if (getLocalConfig().keypad == true ){
            WPOS.util.initKeypad();
            if (setcheckbox)
                $("#keypadset").prop("checked", true);
        } else {
            if (setcheckbox)
                $("#keypadset").prop("checked", false);
        }
        // set keypad focus on click
        $(".numpad").on("click", function () {
            $(this).focus().select();
        });
    }
    // removed due to https mixed content restrictions
    function deployDefaultScanApp(){
        $.getScript('/assets/js/jquery.scannerdetection.js').done(function(){
            // Init plugin
            $(window).scannerDetection({
                onComplete: function(barcode){
                    // switch to sales tab
                    $("#wrapper").tabs( "option", "active", 0 );
                    WPOS.items.addItemFromStockCode(barcode);
                }
            });
        }).error(function(){
            alert("Failed to load the scanning plugin.");
        });
    }

    // AUTH
    function showLogin() {
        $("#modaldiv").show();
        $("#logindiv").show();
        $("#loadingdiv").hide();
        $('#loginbutton').removeAttr('disabled', 'disabled');
        setLoadingBar(0, "");
        $('body').css('overflow', 'auto');
    }

    this.userLogin = function () {
        WPOS.util.showLoader();
        var loginbtn = $('#loginbutton');
        // disable login button
        $(loginbtn).prop('disabled', true);
        $(loginbtn).val('Proccessing');
        // auth is currently disabled on the php side for ease of testing. This function, however will still run and is currently used to test session handling.
        // get form values
        var userfield = $("#username");
        var passfield = $("#password");
        var username = userfield.val();
        var password = passfield.val();
        // hash password
        password = WPOS.util.SHA256(password);
        // authenticate
        if (authenticate(username, password) === true) {
            userfield.val('');
            passfield.val('');
            $("#logindiv").hide();
            $("#loadingdiv").show();
            // initiate data download/check
            if (initialsetup) {
                if (isUserAdmin()) {
                    initSetup();
                } else {
                    alert("You must login as an administrator for first time setup");
                    showLogin();
                }
            } else {
                initData(true);
            }
        }
        passfield.val('');
        $(loginbtn).val('Login');
        $(loginbtn).prop('disabled', false);
        WPOS.util.hideLoader();
    };

    this.logout = function () {
        var answer = confirm("Are you sure you want to logout?");
        if (answer) {
            WPOS.util.showLoader();
            stopSocket();
            WPOS.getJsonData("logout");
            showLogin();
            WPOS.util.hideLoader();
        }
    };

    function authenticate(user, hashpass) {
        // auth against server if online, offline table if not.
        if (online == true) {
            // send request to server
            var response = WPOS.sendJsonData("auth", JSON.stringify({username: user, password: hashpass, getsessiontokens:true}));
            if (response !== false) {
                // set current user will possibly get passed additional data from server in the future but for now just username and pass is enough
                setCurrentUser(response);
                updateAuthTable(response);
                return true;
            } else {
                return false;
            }
        } else {
            return offlineAuth(user, hashpass);
        }
    }

    function sessionRenew(){
        // send request to server
        var response = WPOS.sendJsonData("authrenew", JSON.stringify({username:currentuser.username, auth_hash:currentuser.auth_hash}));
        if (response !== false) {
            // set current user will possibly get passed additional data from server in the future but for now just username and pass is enough
            setCurrentUser(response);
            updateAuthTable(response);
            return true;
        } else {
            return false;
        }
    }

    function offlineAuth(username, hashpass) {
        if (localStorage.getItem("wpos_auth") !== null) {
            var jsonauth = $.parseJSON(localStorage.getItem("wpos_auth"));
            if (jsonauth[username] === null || jsonauth[username] === undefined) {
                alert("Sorry, your credentials are currently not available offline.");
                return false;
            } else {
                var authentry = jsonauth[username];
                if (authentry.auth_hash == WPOS.util.SHA256(hashpass+authentry.token)) {
                    setCurrentUser(authentry);
                    return true;
                } else {
                    alert("Access denied!");
                    return false;
                }
            }
        } else {
            alert("We tried to authenticate you without an internet connection but there are currently no local credentials stored.");
            return false;
        }
    }

    this.getCurrentUserId = function () {
        return currentuser.id
    };

    var currentuser;
    // set current user details
    function setCurrentUser(user) {
        currentuser = user;
    }

    function isUserAdmin() {
        return currentuser.isadmin == 1;
    }

    // initiate the setup process
    this.deviceSetup = function () {
        WPOS.util.showLoader();
        var devid = $("#posdevices option:selected").val();
        var devname = $("#newposdevice").val();
        var locid = $("#poslocations option:selected").val();
        var locname = $("#newposlocation").val();
        // check input
        if ((devid == null && devname == null) || (locid == null && locname == null)) {
            alert("Please select a item from the dropdowns or specify a new name.");
        } else {
            // call the setup function
            if (deviceSetup(devid, devname, locid, locname)) {
                currentuser = null;
                initialsetup = false;
                $("#setupdiv").dialog("close");
                showLogin();
            } else {
                alert("There was a problem setting up the device, please try again.");
            }
        }
        WPOS.util.hideLoader();
    };

    function initSetup() {
        WPOS.util.showLoader();
        // get pos locations and devices and populate select lists
        var devices = WPOS.getJsonData("devices/get");
        var locations = WPOS.getJsonData("locations/get");

        for (var i in devices) {
            if (devices[i].disabled==0 && devices[i].type=="kitchen_terminal"){ // only show kitchen devices which aren't disabled
                $("#posdevices").append('<option value="' + devices[i].id + '">' + devices[i].name + ' (' + devices[i].locationname + ')</option>');
            }
        }
        for (i in locations) {
            if (locations[i].disabled == 0){
                $("#poslocations").append('<option value="' + locations[i].id + '">' + locations[i].name + '</option>');
            }
        }
        WPOS.util.hideLoader();
        // show the setup dialog
        $("#setupdiv").dialog("open");
    }

    // get initial data for pos startup.
    function initData(loginloader) {
        if (loginloader){
            $("#loadingprogdiv").show();
            $("#loadingdiv").show();
        }
        if (online) {
            loadOnlineData(1, loginloader);
        } else {
            initOfflineData(loginloader);
        }
    }

    function loadOnlineData(step, loginloader){
        var statusmsg = "The POS is updating data and switching to online mode.";
        switch (step){
            case 1:
                $("#loadingbartxt").text("Loading online resources");
                // get device info and settings
                setLoadingBar(10, "Getting device settings...");
                setStatusBar(4, "Updating device settings...", statusmsg, 0);
                fetchConfigTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    loadOnlineData(2, loginloader);
                });
                break;

            case 2:
                // get stored items
                setLoadingBar(30, "Getting stored items...");
                setStatusBar(4, "Updating stored items...", statusmsg, 0);
                fetchItemsTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    loadOnlineData(3, loginloader);
                });
                break;

            case 3:
                // get all sales (Will limit to the weeks sales in future)
                setLoadingBar(60, "Getting recent sales...");
                setStatusBar(4, "Updating sales...", statusmsg, 0);
                fetchSalesTable(function(data){
                    if (data===false){
                        showLogin();
                        return;
                    }
                    // start websocket connection
                    startSocket();
                    setStatusBar(1, "WPOS is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
                    initDataSuccess(loginloader);
                    // check for offline sales on login
                    //setTimeout('if (WPOS.sales.getOfflineSalesNum()){ if (WPOS.sales.uploadOfflineRecords()){ WPOS.setStatusBar(1, "WPOS is online"); } }', 2000);
                });
                break;
        }
    }

    function initOfflineData(loginloader){
        // check records and initiate java objects
        setLoadingBar(50, "Loading offline data...");
        loadConfigTable();
        loadItemsTable();
        loadSalesTable();
        alert("Your internet connection is not active and WPOS has started in offline mode.\nSome features are not available in offline mode but you can always make sales and alter transactions that are locally available. \nWhen a connection becomes available WPOS will process your transactions on the server.");
        initDataSuccess(loginloader);
    }

    function initDataSuccess(loginloader){
        if (loginloader){
            setLoadingBar(100, "Initializing the awesome...");
            $("title").text("WallacePOS - Your POS in the cloud");
            WPOS.initPlugins();
            setTimeout('$("#modaldiv").hide();', 500);
        }
        WPOS.kitchen.populateOrders();
    }

    function setLoadingBar(progress, status) {
        var loadingprog = $("#loadingprog");
        var loadingstat = $("#loadingstat");
        $(loadingstat).text(status);
        $(loadingprog).css("width", progress + "%");
    }

    /**
     * Update the pos status text and icon
     * @param statusType (1=Online, 2=Uploading, 3=Offline, 4=Downloading)
     * @param text
     * @param tooltip
     * @param timeout
     */
    this.setStatusBar = function(statusType, text, tooltip, timeout){
        setStatusBar(statusType, text, tooltip, timeout);
    };

    var defaultStatus = {type:1, text:"", tooltip:""};
    var statusTimer = null;

    function setDefaultStatus(statusType, text, tooltip){
        defaultStatus.type = statusType;
        defaultStatus.text = text;
        defaultStatus.tooltip = tooltip;
    }

    function setStatusBar(statusType, text, tooltip, timeout){
        if (timeout===0){
            setDefaultStatus(statusType, text, tooltip);
        } else if (timeout > 0 && statusTimer!=null){
            clearTimeout(statusTimer);
        }

        var staticon = $("#wposstaticon");
        var statimg = $("#wposstaticon i");
        switch (statusType){
            // Online icon
            case 1: $(staticon).attr("class", "badge badge-success");
                $(statimg).attr("class", "icon-ok");
                break;
            // Upload icon
            case 2: $(staticon).attr("class", "badge badge-info");
                $(statimg).attr("class", "icon-cloud-upload");
                break;
            // Offline icon
            case 3: $(staticon).attr("class", "badge badge-warning");
                $(statimg).attr("class", "icon-exclamation");
                break;
            // Download icon
            case 4: $(staticon).attr("class", "badge badge-info");
                $(statimg).attr("class", "icon-cloud-download");
                break;
            // Feed server disconnected
            case 5: $(staticon).attr("class", "badge badge-warning");
                $(statimg).attr("class", "icon-ok");
        }
        $("#wposstattxt").text(text);
        $("#wposstat").attr("title", tooltip);

        if (timeout > 0){
            statusTimer = setTimeout(resetStatusBar, timeout);
        }
    }

    // reset status bar to the current default status
    function resetStatusBar(){
        clearTimeout(statusTimer);
        statusTimer = null;
        setStatusBar(defaultStatus.type, defaultStatus.text, defaultStatus.tooltip);
    }

    var online = false;

    this.isOnline = function () {
        return online;
    };

    function checkOnlineStatus() {
        try {
            var res = $.ajax({
                timeout : 3000,
                url     : "/api/hello",
                type    : "GET",
                cache   : false,
                dataType: "text",
                async   : false
            }).status;
            online = res == "200";
        } catch (ex){
            online = false;
        }
        return online;
    }

    // OFFLINE MODE FUNCTIONS
    function canDoOffline() {
        if (getDeviceUUID()!==null) { // can't go offline if device hasn't been setup
            // check for auth table
            if (localStorage.getItem("wpos_auth") == null) {
                return false;
            }
            // check for machine settings etc.
            if (localStorage.getItem("wpos_config") == null) {
                return false;
            }
            return localStorage.getItem("wpos_items") != null;
        }
        return false;
    }

    var checktimer;

    this.switchToOffline = function(){
        return switchToOffline();
    };

    function switchToOffline() {
        if (canDoOffline()==true) {
            // set js indicator: important
            online = false;
            setStatusBar(3, "WPOS is Offline", "The POS is offine and will store sale data locally until a connection becomes available.", 0);
            // start online check routine
            checktimer = setInterval(doOnlineCheck, 60000);
            return true;
        } else {
            // display error notice
            alert("There was an error connecting to the webserver & files needed to run offline are not present :( \nPlease check your connection and try again.");
            $("#modaldiv").show();
            ('#loginbutton').prop('disabled', true);
            setLoadingBar(100, "Error switching to offine mode");
            return false;
        }
    }

    function doOnlineCheck() {
        if (checkOnlineStatus()==true) {
            clearInterval(checktimer);
            switchToOnline();
        }
    }

    function switchToOnline() {
        // upload offline sales
        //if (WPOS.sales.uploadOfflineRecords()){
            // set js and ui indicators
            online = true;
            // load fresh data
            initData(false);
            // initData();
        setStatusBar(1, "WPOS is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
        //}
    }

    // GLOBAL COM FUNCTIONS
    this.sendJsonData = function (action, data) {
        // send request to server
        try {
            var response = $.ajax({
                url     : "/api/"+action,
                type    : "POST",
                data    : {data: data},
                dataType: "text",
                timeout : 10000,
                cache   : false,
                async   : false
            });
            if (response.status == "200") {
                var json = $.parseJSON(response.responseText);
                if (json == null) {
                    alert("Error: The response that was returned from the server could not be parsed!");
                    return false;
                }
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
                        if (sessionRenew()) {
                            // try again after authenticating
                            return WPOS.sendJsonData(action, data);
                        } else {
                            //alert(err);
                            return false;
                        }
                    } else {
                        alert(err);
                        return false;
                    }
                }
            } else {
                switchToOffline();
                alert("There was an error connecting to the server: \n"+response.statusText+", \n switching to offline mode");
                return false;
            }
        } catch (ex) {
            switchToOffline();
            alert("There was an error sending data, switching to offline mode");
            return false;
        }
    };

    this.sendJsonDataAsync = function (action, data, callback, callbackref) {
        // send request to server
        try {
            var response = $.ajax({
                url     : "/api/"+action,
                type    : "POST",
                data    : {data: data},
                dataType: "json",
                timeout : 10000,
                cache   : false,
                success : function(json){
                    var errCode = json.errorCode;
                    var err = json.error;
                    if (err == "OK") {
                        // echo warning if set
                        if (json.hasOwnProperty('warning')){
                            alert(json.warning);
                        }
                        callback(json.data, callbackref);
                    } else {
                        if (errCode == "auth") {
                            if (sessionRenew()) {
                                // try again after authenticating
                                callback(WPOS.sendJsonData(action, data), callbackref);
                            } else {
                                //alert(err);
                                callback(false, callbackref);
                            }
                        } else {
                            alert(err);
                            callback(false, callbackref);
                        }
                    }
                },
                error   : function(jqXHR, status, error){
                    alert(error);
                    callback(false, callbackref);
                }
            });
            return true;
        } catch (ex) {
            return false;
        }
    };

    this.getJsonData = function (action) {
        // send request to server
        try {
            var response = $.ajax({
                url     : "/api/"+action,
                type    : "GET",
                dataType: "text",
                timeout : 10000,
                cache   : false,
                async   : false
            });
            if (response.status == "200") {
                var json = $.parseJSON(response.responseText);
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
                        if (sessionRenew()) {
                            // try again after authenticating
                            return WPOS.getJsonData(action);
                        } else {
                            //alert(err);
                            return false;
                        }
                    } else {
                        alert(err);
                        return false;
                    }
                }
            } else {
                alert("There was an error connecting to the server: \n"+response.statusText);
                return false;
            }
        } catch (ex){
            return false;
        }
    };

    this.getJsonDataAsync = function (action, callback) {
        // send request to server
        try {
            $.ajax({
                url     : "/api/"+action,
                type    : "GET",
                dataType: "json",
                timeout : 10000,
                cache   : false,
                success : function(json){
                    var errCode = json.errorCode;
                    var err = json.error;
                    if (err == "OK") {
                        // echo warning if set
                        if (json.hasOwnProperty('warning')){
                            alert(json.warning);
                        }
                        if (callback)
                            callback(json.data);
                    } else {
                        if (errCode == "auth") {
                            if (sessionRenew()) {
                                // try again after authenticating
                                var result = WPOS.sendJsonData(action, data);
                                if (result){
                                    if (callback)
                                        callback(result);
                                    return;
                                }
                            }
                        }
                        alert(err);
                        if (callback)
                            callback(false);
                    }
                },
                error   : function(jqXHR, status, error){
                    alert(error);
                    if (callback)
                        callback(false);
                }
            });
        } catch (ex) {
            alert("Exception: "+ex);
            if (callback)
                callback(false);
        }
    };

    // AUTHENTICATION & USER SETTINGS
    /**
     * Update the offline authentication table using the json object provided. This it returned on successful login.
     * @param {object} jsonobj ; user record returned by authentication
     */
    function updateAuthTable(jsonobj) {
        var jsonauth;
        if (localStorage.getItem("wpos_auth") !== null) {
            jsonauth = $.parseJSON(localStorage.getItem("wpos_auth"));
            jsonauth[jsonobj.username.toString()] = jsonobj;
        } else {
            jsonauth = { };
            jsonauth[jsonobj.username.toString()] = jsonobj;
        }
        localStorage.setItem("wpos_auth", JSON.stringify(jsonauth));
    }

    // DEVICE SETTINGS AND INFO
    var configtable;

    this.getConfigTable = function () {
        if (configtable == null) {
            loadConfigTable();
        }
        return configtable;
    };
    /**
     * Fetch device settings from the server using UUID
     * @return boolean
     */
    function fetchConfigTable(callback) {
        var data = {};
        data.uuid = getDeviceUUID();
        return WPOS.sendJsonDataAsync("config/get", JSON.stringify(data), function(data){
            if (data) {
                console.log(data);
                if (data.hasOwnProperty("remdev")){ // return false if dev is disabled
                    initialsetup = true;
                    if (callback){
                        callback(false);
                        return;
                    }
                } else {
                    configtable = data;
                    localStorage.setItem("wpos_kitchen_config", JSON.stringify(data));
                    setAppCustomization();
                }
            }
            if (callback)
                callback(data);
        });
    }

    function loadConfigTable() {
        var data = localStorage.getItem("wpos_kitchen_config");
        if (data != null) {
            configtable = JSON.parse(data);
            return true;
        }
        return false;
    }

    function updateConfig(key, value){
        configtable[key] = value; // write to current data
        localStorage.setItem("wpos_kitchen_config", JSON.stringify(configtable));
        setAppCustomization();
    }

    function setAppCustomization(){
        var url = WPOS.getConfigTable().general.bizlogo;
        console.log(url);
        $("#watermark").css("background-image", "url('"+url+"')");
    }

    this.getTaxTable = function () {
        if (configtable == null) {
            loadConfigTable();
        }
        return configtable.tax;
    };

    // Local Config
    this.setLocalConfigValue = function(key, value){
        setLocalConfigValue(key, value);
    };

    this.getLocalConfig = function(){
        return getLocalConfig();
    };

    function getLocalConfig(){
        var lconfig = localStorage.getItem("wpos_kitchen_lconfig");
        if (lconfig==null || lconfig==undefined){
            // put default config here.
            var defcon = {
                keypad: true
            };
            updateLocalConfig(defcon);
            return defcon;
        }
        return JSON.parse(lconfig);
    }

    function setLocalConfigValue(key, value){
        var data = localStorage.getItem("wpos_kitchen_lconfig");
        if (data==null){
            data = {};
        } else {
            data = JSON.parse(data);
        }
        data[key] = value;
        updateLocalConfig(data);
        if (key == "keypad"){
            setKeypad(false);
        }
    }

    function updateLocalConfig(configobj){
        localStorage.setItem("wpos_kitchen_lconfig", JSON.stringify(configobj));
    }

    /**
     * This function sets up the
     * @param {int} devid ; if not null, the newname var is ignored and the new uuid is merged with the device specified by devid.
     * @param {int} newdevname ; A new device name, if specified the
     * @param {int} locid ; if not null, the newlocname field is ignored and blah blah blah....
     * @param {int} newlocname ; if not null, the newlocname field is ignored and blah blah blah....
     * @returns {boolean}
     */
    function deviceSetup(devid, newdevname, locid, newlocname) {
        var data = {};
        data.uuid = setDeviceUUID(false);
        if (devid === "") {
            data.devicename = newdevname;
        } else {
            data.deviceid = devid;
        }
        if (locid === "") {
            data.locationname = newlocname;
        } else {
            data.locationid = locid;
        }
        var configobj = WPOS.sendJsonData("devices/setup", JSON.stringify(data));
        if (configobj) {
            localStorage.setItem("wpos_config", JSON.stringify(configobj));
            configtable = configobj;
            return true;
        } else {
            setDeviceUUID(true);
            return false;
        }
    }

    /**
     * Returns the current devices UUID
     * @returns {String, Null} String if set, null if not
     */
    function getDeviceUUID() {
        // return the devices uuid; if null, the device has not been setup or local storage was cleared
        return localStorage.getItem("wpos_kitchen_devuuid");
    }

    /**
     * Creates or clears device UUID and updates in local storage
     * @param clear If true, the current UUID is detroyed
     * @returns {String, Null} String uuid if set, null if cleared
     */
    function setDeviceUUID(clear) {
        var uuid = null;
        if (clear) {
            localStorage.removeItem("wpos_kitchen_devuuid");
        } else {
            // generate a md5 UUID using datestamp and rand for entropy and return the result
            var date = new Date().getTime();
            uuid = WPOS.util.SHA256((date * Math.random()).toString());
            localStorage.setItem("wpos_kitchen_devuuid", uuid);
        }
        return uuid;
    }

    // RECENT SALES
    var salestable;

    this.getSalesTable = function () {
        if (salestable == null) {
            loadSalesTable();
        }
        return salestable;
    };

    function fetchSalesTable(callback) {
        return WPOS.sendJsonDataAsync("sales/get", JSON.stringify({deviceid: configtable.deviceid}), function(data){
            if (data) {
                salestable = data;
                localStorage.setItem("wpos_csales", JSON.stringify(data));
            }
            if (callback)
                callback(data);
        });
    }

    // loads from local storage
    function loadSalesTable() {
        var data = localStorage.getItem("wpos_csales");
        if (data !== null) {
            salestable = JSON.parse(data);
            return true;
        }
        return false;
    }

    this.updateSalesTable = function(saleobject){
        updateSalesTable(saleobject);
    };
    function updateSalesTable(saleobject) {
        // delete the sale if ref supplied
        if (typeof saleobject === 'object'){
            salestable[saleobject.ref] = saleobject;
        } else {
            delete salestable[saleobject];
        }
        localStorage.setItem("wpos_csales", JSON.stringify(salestable));
    }
    // STORED ITEMS
    var itemtable;

    this.getItemsTable = function () {
        if (itemtable == null) {
            loadItemsTable();
        }
        return itemtable;
    };

    // fetches from server
    function fetchItemsTable(callback) {
        return WPOS.getJsonDataAsync("items/get", function(data){
            if (data) {
                itemtable = data;
                localStorage.setItem("wpos_items", JSON.stringify(data));
            }
            if (callback)
                callback(data);
        });
    }

    // loads from local storage
    function loadItemsTable() {
        var data = localStorage.getItem("wpos_items");
        if (data != null) {
            itemtable = JSON.parse(data);
            return true;
        }
        return false;
    }

    // adds/edits a record to the current table
    function updateItemsTable(itemobject) {
        // delete the sale if id/ref supplied
        if (typeof itemobject === 'object'){
            itemtable[itemobject.id] = itemobject;
        } else {
            delete itemtable[itemobject];
        }
        localStorage.setItem("wpos_items", JSON.stringify(itemtable));
    }

    // Websocket updates & commands
    var socket = null;
    var socketon = false;
    var authretry = false;
    function startSocket(){
        if (socket==null){
            var proxy = WPOS.getConfigTable().general.feedserver_proxy;
            var port = WPOS.getConfigTable().general.feedserver_port;
            var socketPath = window.location.protocol+'//'+window.location.hostname+(proxy==false ? ':'+port : '');
            socket = io.connect(socketPath);
            socket.on('connection', onSocketConnect);
            socket.on('reconnect', onSocketConnect);
            socket.on('connect_error', socketError);
            socket.on('reconnect_error', socketError);
            socket.on('error', socketError);

            socket.on('updates', function (data) {
                switch (data.a){
                    case "item":
                        updateItemsTable(data.data);
                        break;

                    case "sale":
                        console.log("Sale data received:");
                        console.log(data.data);
                        WPOS.kitchen.processOrder(data.data);
                        break;

                    case "config":
                        updateConfig(data.type, data.data);
                        break;

                    case "regreq":
                        socket.emit('reg', {deviceid: configtable.deviceid, username: currentuser.username});
                        break;

                    case "msg":
                        alert(data.data);
                        break;

                    case "reset":
                        resetTerminalRequest();
                        break;

                    case "error":
                        if (!authretry && data.data.hasOwnProperty('code') && data.data.code=="auth"){
                            authretry = true;
                            stopSocket();
                            var result = WPOS.getJsonData('auth/websocket');
                            if (result===true){
                                startSocket();
                                return;
                            }
                        }

                        alert(data.data);
                        break;
                }
                var statustypes = ['item', 'sale', 'customer', 'config'];
                if (statustypes.indexOf(data.a) > -1) {
                    var statustxt = data.a=="sale" ? "Kitchen order received" : "Receiving "+ data.a + " update";
                    var statusmsg = data.a=="sale" ? "The Kitchen terminal has received an order from a POS register" : "The terminal has received updated "+ data.a + " data from the server";
                    setStatusBar(4, statustxt, statusmsg, 5000);
                }
                //alert(data.a);
            });
        } else {
            socket.connect();
        }
    }

    function onSocketConnect(){
        socketon = true;
        if (WPOS.isOnline() && defaultStatus.type != 1){
            setStatusBar(1, "WPOS is Online", "The POS is running in online mode.\nThe feed server is connected and receiving realtime updates.", 0);
        }
    }

    function socketError(){
        if (WPOS.isOnline())
            setStatusBar(5, "Update Feed Offline", "The POS is running in online mode.\nThe feed server is disconnected and this terminal will not receive realtime updates.", 0);
        socketon = false;
        authretry = false;
    }

    this.sendAcknowledgement = function(deviceid, ref){
        if (socket) {
            var data = {include: null, data: {a: "kitchenack", data: ref}};
            if (deviceid) {
                data.include = {};
                data.include[deviceid] = true;
            }
            socket.emit('send', data);
        }
        console.log("Order acknowledgement sent!");
    };

    function stopSocket(){
        if (socket!=null){
            socketon = false;
            authretry = false;
            socket.disconnect();
            socket = null;
        }
    }

    window.onbeforeunload = function(){
        socketon = false;
    };

    // Reset terminal
    function resetTerminalRequest(){
        // Set timer
        var reset_timer = setTimeout("window.location.reload(true);", 10000);
        var reset_interval = setInterval('var r=$("#resettimeval"); r.text(r.text()-1);', 1000);
        $("#resetdialog").removeClass('hide').dialog({
            width : 'auto',
            maxWidth        : 370,
            modal        : true,
            closeOnEscape: false,
            autoOpen     : true,
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "370px");
            },
            buttons: [
                {
                    html: "<i class='icon-check bigger-110'></i>&nbsp; Ok",
                    "class": "btn btn-success btn-xs",
                    click: function () {
                        window.location.reload(true);
                    }
                },
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class": "btn btn-xs",
                    click: function () {
                        clearTimeout(reset_timer);
                        clearInterval(reset_interval);
                        $("#resetdialog").dialog('close');
                        $("#resettimeval").text(10);
                    }
                }
            ]
        });
    }
    // TODO: On socket error, start a timer to reconnect
    // Contructor code
    // load WPOS Objects
    this.print = new WPOSPrint(true); // kitchen mode
    this.trans = new WPOSTransactions();
    this.util = new WPOSUtil();
    this.kitchen = new WPOSKitchenMod();

    return this;
}
function WPOSKitchenMod(){
    var ordercontain = $("#ordercontainer");
    var orderhistcontain = $("#orderhistcontainer");
    // populate orders in the UI
    this.populateOrders = function(){
        var sales = WPOS.getSalesTable();
        for (var ref in sales){
            var sale = sales[ref];
            if (sale.hasOwnProperty('orderdata'))
                for (var o in sale.orderdata){
                    insertOrder(sale, o);
                }
        }
    };
    // refresh orders in the UI
    this.refreshOrders = function(reload){
        ordercontain.html('');
        orderhistcontain.html('');
        this.populateOrders();
    };
    // insert an order into the UI
    function insertOrder(saleobj, orderid){
        var order = saleobj.orderdata[orderid];
        var elem = $("#orderbox_template").clone().removeClass('hide').attr('id', 'order_box_'+saleobj.ref+'-'+order.id);
        elem.find('.orderbox_orderid').text(order.id);
        elem.find('.orderbox_saleref').text(saleobj.ref);
        elem.find('.orderbox_orderdt').text(WPOS.util.getDateFromTimestamp(order.processdt));
        var itemtbl = elem.find('.orderbox_items');
        for (var i in order.items){
            var item = saleobj.items[order.items[i]]; // the items object links the item id with it's index in the data
            var modStr = "";
            if (item.hasOwnProperty('mod')){
                for (var x=0; x<item.mod.items.length; x++){
                    var mod = item.mod.items[x];
                    modStr+= '<br/>'+(mod.hasOwnProperty('qty')?((mod.qty>0?'+ ':'')+mod.qty+' '):'')+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+WPOS.util.currencyFormat(mod.price)+')';
                }
            }
            itemtbl.append('<tr><td style="width:10%;"><strong>'+item.qty+'</strong></td><td><strong>'+item.name+'</strong>'+modStr+'<br/></td></tr>');
        }
        ordercontain.prepend(elem);
    }
    this.removeOrder = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).remove();
    };
    this.moveOrderToHistory = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).detach().prependTo(orderhistcontain);
    };
    this.moveOrderToCurrent = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).detach().prependTo(ordercontain);
    };
    // process an incoming order from the websocket
    this.processOrder = function(data){
        var olddata;
        var modcount = 0;
        var deviceid = null;
        var ref;
        if (typeof data === "object") {
            ref = data.ref;
            // check for old data, if none available process as new orders
            if (WPOS.getSalesTable().hasOwnProperty(ref)) {
                olddata = WPOS.getSalesTable()[ref];
                if (data.hasOwnProperty('orderdata')){
                    for (var i in data.orderdata){
                        if (olddata.orderdata.hasOwnProperty(i)){
                            // the moddt param exists the order may have been modified, check further
                            if (data.orderdata[i].hasOwnProperty('moddt')){
                                // if the moddt flag doesn't exist on the old order moddt or is smaller than the new value
                                if (!olddata.orderdata[i].hasOwnProperty('moddt') || data.orderdata[i].moddt>olddata.orderdata[i].moddt) {
                                    processUpdatedOrder(data, i);
                                    modcount++;
                                }
                            }
                        } else {
                            processNewOrder(data, i);
                            modcount++;
                        }
                    }
                } else {
                    // no order data exists in the new data, remove all
                    if (olddata.hasOwnProperty('orderdata'))
                        for (var r in olddata.orderdata){
                            processDeletedOrder(olddata, r);
                            modcount++;
                        }
                }
            } else {
                if (data.hasOwnProperty('orderdata'))
                    for (var o in data.orderdata) {
                        processNewOrder(data, o);
                        modcount++;
                    }
            }
            deviceid = data.devid;
        } else {
            ref = data;
            // process removed orders if they exists in the system
            if (WPOS.getSalesTable().hasOwnProperty(ref)){
                olddata = WPOS.getSalesTable()[ref];
                if (olddata.hasOwnProperty('orderdata'))
                    for (var d in olddata.orderdata){
                        processDeletedOrder(olddata, d);
                        modcount++;
                    }
            }
        }
        // save new sales data
        WPOS.updateSalesTable(data);

        if (modcount)
            WPOS.sendAcknowledgement(deviceid, ref);
    };

    this.onPrintButtonClick = function(element){
        var ref = $(element).parent().find('.orderbox_saleref').text();
        var ordernum = $(element).parent().find('.orderbox_orderid').text();
        var data = WPOS.getSalesTable()[ref];
        if (data)
            WPOS.print.printOrderTicket("orders", data, ordernum);

        console.log(data);
    };

    function processNewOrder(saleobj, orderid){
        console.log("Processed new order "+saleobj.ref+" "+orderid);
        var order = saleobj.orderdata[orderid];
        insertOrder(saleobj, orderid);
        playChime();
        switch (WPOS.getLocalConfig().printing.recask) {
            case "ask":
                var answer = confirm("Print order ticket?");
                if (!answer) break;
            case "print":
                WPOS.print.printOrderTicket("orders", saleobj, orderid, null);
        }
    }

    function processUpdatedOrder(saleobj, orderid){
        console.log("Processed updated order "+saleobj.ref+" "+orderid);
        var order = saleobj.orderdata[orderid];
        // remove old record that may be present
        WPOS.kitchen.removeOrder(saleobj.ref, orderid);
        insertOrder(saleobj, order.id);
        playChime();
        switch (WPOS.getLocalConfig().printing.recask) {
            case "ask":
                var answer = confirm("Print order ticket?");
                if (!answer) break;
            case "print":
                WPOS.print.printOrderTicket("orders", saleobj, orderid, "ORDER UPDATED");
        }
    }

    function processDeletedOrder(saleobj, orderid){
        console.log("Processed deleted order "+saleobj.ref+" "+orderid);
        var order = saleobj.orderdata[orderid];
        // remove old record that may be present
        WPOS.kitchen.moveOrderToHistory(saleobj.ref, orderid);
        playChime();
        switch (WPOS.getLocalConfig().printing.recask) {
            case "ask":
                var answer = confirm("Print order ticket?");
                if (!answer) break;
            case "print":
                WPOS.print.printOrderTicket("orders", saleobj, orderid, "ORDER CANCELLED");
        }
    }

    var audio = new Audio('/assets/sounds/bell_modern.mp3');
    function playChime(){
        audio.play();
    }

    return this;
}
var WPOS;
$(function () {
    // initiate core object
    WPOS = new WPOSKitchen();
    // initiate startup routine
    WPOS.initApp();

    $("#wrapper").tabs();

    $("#transactiondiv").dialog({
        width   : 'auto',
        maxWidth: 600,
        modal   : true,
        autoOpen: false,
        title_html: true,
        open    : function (event, ui) {
            var tdiv = $("#transdivcontain");
            tdiv.css("width", tdiv.width()+"px");
        },
        close   : function (event, ui) {
            $("#transdivcontain").css("width", "100%");
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "600px");
        }
    });

    $("#setupdiv").dialog({
        width : 'auto',
        maxWidth        : 370,
        modal        : true,
        closeOnEscape: false,
        autoOpen     : false,
        open         : function (event, ui) {
            $(".ui-dialog-titlebar-close").hide();
        },
        close        : function (event, ui) {
            $(".ui-dialog-titlebar-close").show();
        },
        create: function( event, ui ) {
            // Set maxWidth
            $(this).css("maxWidth", "370px");
        }
    });
    // keyboard navigation
    $(document.documentElement).keydown(function (event) {
        // handle cursor keys
        var e = jQuery.Event("keydown");
        var x;
        if (event.keyCode == 37) {
            $(".keypad-popup").hide();
            x = $('input:not(:disabled), textarea:not(:disabled)');
            x.eq(x.index(document.activeElement) - 1).focus().trigger('click');

        } else if (event.keyCode == 39) {
            $(".keypad-popup").hide();
            x = $('input:not(:disabled), textarea:not(:disabled)');
            x.eq(x.index(document.activeElement) + 1).focus().trigger('click');
        }
    });

    // dev/demo quick login
    if (document.location.host=="demo.wallacepos.com" || document.location.host=="alpha.wallacepos.com"){
        $("#logindiv").append('<button class="btn btn-primary btn-sm" onclick="$(\'#username\').val(\'admin\');$(\'#password\').val(\'admin\'); WPOS.userLogin();">Demo Login</button>');
    }
});
