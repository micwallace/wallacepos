/**
 * accountplugin.js is part of Wallace Point of Sale system (WPOS)
 *
 * accountplugin.js Provides an external library for WallacePOS customer registration, login and password reset.
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
var WOMS;
function WOMSPluginBase(){
    this.init = function(){
        // load utils
        //if (typeof WPOSUtil !=='undefined')
        $.getScript('/assets/js/wpos/utilities.js', function(){
            WOMS.util = new WPOSUtil();
        });
        if ($.fancybox)
            $.fancybox.init();
    };
    // Account registration
    this.showRegisterDialog = function(){
        $.fancybox({
            'autoScale': true,
            'type': 'ajax',
            'href': '/myaccount/register.php'
        });
    };
    var dialog_header = function(title){ return '<img style="width: 200px; height: 80px;" src="/assets/images/receipt-logo.png"/><h3 class="smaller" style="margin-top: 5px;">'+title+'</h3>'; };
    this.showResetDialog = function(){
        $.fancybox({
            'autoScale': true,
            'content': '<div style="text-align: center; background-color: #fff; padding: 5px; width: 260px;">'+dialog_header('Reset your password')+'<img src="/assets/secureimage/securimage_show.php?sid='+Math.random()+'" alt="CAPTCHA Image" id="reset_siimage" /><br />' +
                        '<a tabindex="-1" style="border-style: none" href="#" title="Refresh Image" onclick="WOMS.reloadCaptcha();"><span class="style1"><img src="https://admin.wallaceit.com.au/assets/secureimage/images/refresh.png" alt="Reload Image" border="0" onclick="this.blur()" />Reload Captcha</span></a>' +
                '<br /><br/><input id="reset_captcha" type="text" placeholder="Security Code"/><br/><input id="reset_email" placeholder="Email" type="text" onkeypress="if(event.keyCode == 13){WOMS.resetPasswordEmail();}" /><br/><button class="btn btn-primary" onclick="WOMS.resetPasswordEmail();">Reset Password</button></div>'
        });
    };
    this.reloadCaptcha = function(){
        $('#reset_siimage').attr('src', '/assets/secureimage/securimage_show.php?sid=' + Math.random()); return false;
    };
    this.showAccountFrame = function(url){
        $.fancybox({
            'autoScale': false,
            'autoDimensions': false,
            'hideOnOverlayClick': false,
            'width': 500,
            'height': 500,
            'content': '<iframe style="width:100%; height: 99%; border:0;" src="/'+url+'"></iframe>'
        });
    };
    function handleFrameMessage(e){
        switch (e.data){
            case 'showRegister':
                WOMS.showRegisterDialog();
                break;
            case 'showReset':
                WOMS.showResetDialog();
                break;
            default:
                var data = JSON.parse(e.data);
                if (data.hasOwnProperty('location')){
                    document.location.href = data.location;
                }
        }
    }
    addEventListener("message", handleFrameMessage, true);
    function showSuccessDialog(text){
        $.fancybox({
            'autoScale': true,
            'content': '<div style="text-align: center; background-color: #fff; padding: 5px;">'+dialog_header('Success')+'<p>'+text+'</p></div>'
        });
    }
    this.login = function(){
        WOMS.util.showLoader();
        var loginbtn = $('#login_button');
        // disable login button
        $(loginbtn).prop('disabled', true);
        // auth is currently disabled on the php side for ease of testing. This function, however will still run and is currently used to test session handling.
        // get form values
        var userfield = $("#login_user");
        var passfield = $("#login_pass");
        var username = userfield.val();
        var password = passfield.val();
        // hash password
        password = WOMS.util.SHA256(password);
        // authenticate
        var user = WOMS.sendJsonData("auth", JSON.stringify({username: username, password: password}));
        $(loginbtn).prop('disabled', false);
        WOMS.util.hideLoader();

        return user !== false;
    };
    this.register = function(){
        var data = {
            name:$("#register_name").val(),
            email:$("#register_email").val(),
            phone:$("#register_phone").val(),
            mobile:$("#register_mobile").val(),
            address:$("#register_address").val(),
            suburb:$("#register_suburb").val(),
            postcode:$("#register_postcode").val(),
            state:$("#register_state").val(),
            country:$("#register_country").val(),
            pass:$("#register_pass").val(),
            captcha:$("#register_captcha").val()
        };
        // check passwords & hash
        var passres = validatePassword(data.pass, $("#register_cpass").val());
        if (passres!==true){
            alert(passres);
            return;
        }
        data.pass = WOMS.util.SHA256(data.pass);
        // send data
        WOMS.util.showLoader();
        var result = WOMS.sendJsonData('register', JSON.stringify(data));
        // show success
        if (result!==false){
            showSuccessDialog("Registration Successful!<br/>An activation email has been send to your email address.");
        }
        WOMS.util.hideLoader();
    };
    this.resetPasswordEmail = function(){
        var data = {
           email:$("#reset_email").val(),
           captcha:$("#reset_captcha").val()
        };
        // send data
        WOMS.util.showLoader();
        var result = WOMS.sendJsonData('resetpasswordemail', JSON.stringify(data));
        // show success
        if (result!==false){
            showSuccessDialog("An password reset email has been send to your email address.");
        }
        WOMS.util.hideLoader();
    };
    this.resetPassword = function(){
        var data = {
            token:$("#token").val(),
            pass:$("#reset_pass").val()
        };
        if (data.token==''){
            alert("No valid auth token received!");
            return;
        }
        // check passwords & hash
        var passres = validatePassword(data.pass, $("#reset_cpass").val());
        if (passres!==true){
            alert(passres);
            return;
        }
        data.pass = WOMS.util.SHA256(data.pass);
        // send data
        WOMS.util.showLoader();
        var result = WOMS.sendJsonData('resetpassword', JSON.stringify(data));
        // show success
        if (result!==false){
            alert("Password Successfully Reset!");
            redirect();
        }
        WOMS.util.hideLoader();
    };
    // returns md5 hash if successful, false if not
    function validatePassword(pass, cpass){
        if (pass!==cpass){
            return 'Passwords do not match';
        }
        if (pass.length<8){
            return 'Passwords must be at least 8 characters.';
        }
        return true;
    }
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
            async   : false,
            beforeSend: function(xhr){
                xhr.withCredentials = true;
            }
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
                alert(err);
                return false;
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
            async   : false,
            beforeSend: function(xhr){
                xhr.withCredentials = true;
            }
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
                alert(err);
                return false;
            }
        }
        alert("There was an error connecting to the server: \n"+response.statusText);
        return false;
    };

    return this;
}