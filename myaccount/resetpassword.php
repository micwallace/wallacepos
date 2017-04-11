<html>
<head>
    <title>Password Reset</title>
    <script src='/admin/assets/js/jquery-2.0.3.min.js'></script>
    <link rel="stylesheet" href="https://admin.wallaceit.com.au/assets/ace.form.css"/>
    <script src="/myaccount/assets/accountplugin.js"></script>
    <script>
        $(function(){
            WOMS = new WOMSPluginBase();
            WOMS.init();
        });
        function redirect(){
            document.location.href = "<?php echo(isset($_REQUEST['redirect'])?htmlspecialchars($_REQUEST['redirect'], ENT_QUOTES, 'UTF-8'):'/'); ?>";
        }
    </script>
</head>
<body style="text-align: center;">
    <img style="width: 200px; height: 80px;" src="/assets/images/receipt-logo.png"/>
    <h3 class="smaller">Reset Your Account Password</h3>
    <input type="hidden" id="token" value="<?php echo(htmlspecialchars($_REQUEST['token'], ENT_QUOTES, 'UTF-8')); ?>"/>
    <input type="password" id="reset_pass" placeholder="New Password" /><br/>
    <input type="password" id="reset_cpass" onkeypress="if(event.keyCode == 13){WOMS.resetPassword();}" placeholder="Confirm Password" /><br/><br/>
    <button class="btn btn-primary" onclick="WOMS.resetPassword();">Submit</button>
</body>
</html>