<div style="text-align: center;">
    <ul class="breadcrumb">
        <li>Check Requirements</li>
        <li>Configure Database</li>
        <li><strong>Initial Setup</strong></li>
        <li>Install System</li>
    </ul>
</div>
<div>
    <h4>Choose a password for the admin user</h4>
    <form role="form" class="form-horizontal" method="post" onsubmit="return validatePassword();">
        <input name="doinstall" type="hidden" value="1">
        <div class="space-8"></div>
        <div class="form-group">
            <label for="form-field-2" class="col-sm-3 control-label no-padding-right"> Password </label>
            <div class="col-sm-9">
                <input name="password" id="password" type="password" class="col-xs-10 col-sm-5" placeholder="Password">
            </div>
        </div>
        <div class="space-4"></div>
        <div class="form-group">
            <label for="form-field-2" class="col-sm-3 control-label no-padding-right"> Confirm Password </label>
            <div class="col-sm-9">
                <input name="cpassword" id="cpassword" type="password" class="col-xs-10 col-sm-5" placeholder="Password">
            </div>
        </div>
        <hr/>
        <div style="height: 40px;">
            <button type="button" class="pull-left btn btn-primary" onclick="document.location.href='/installer?screen=2';">Back</button>
            <button type="submit" class="pull-right btn btn-primary">Install</button>
        </div>
    </form>
</div>
<script>
    function validatePassword(){
        var password = $("#password").val();
        if (password.length<8){
            alert("The password must be at least 8 characters");
            return false;
        }
        if ($("#cpassword").val()!=password){
            alert("The provided passwords do not match");
            return false;
        }
        return true;
    }
</script>
