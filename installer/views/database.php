<div style="text-align: center;">
    <ul class="breadcrumb">
        <li>Check Requirements</li>
        <li><strong>Configure Database</strong></li>
        <li>Initial Setup</li>
        <li>Install System</li>
    </ul>
</div>
<div>
    <h4>Database</h4>
    <?php
        if (isset($errormessage)){
    ?>
            <div class="alert alert-danger">
                <button class="close" data-dismiss="alert" type="button"></button>
                <strong>
                    <i class="ace-icon fa fa-times"></i>
                    Oh snap!
                </strong>
                <?php echo($errormessage); ?>
                <br>
            </div>
    <?php
        }
    ?>
    <form role="form" class="form-horizontal" method="post">
        <input name="checkdb" type="hidden" value="1">
        <div class="space-4"></div>
        <div class="form-group">
            <label for="form-field-1" class="col-sm-3 control-label no-padding-right"> Host </label>
            <div class="col-sm-9">
                <input name="host" type="text" class="col-xs-10 col-sm-5" placeholder="Host" value="<?php echo(isset($_REQUEST['host'])?$_REQUEST['host']:"127.0.0.1"); ?>">
            </div>
        </div>
        <div class="space-4"></div>
        <div class="form-group">
            <label for="form-field-1" class="col-sm-3 control-label no-padding-right"> Port </label>
            <div class="col-sm-9">
                <input name="port" type="text" class="col-xs-10 col-sm-5" placeholder="Port" value="<?php echo(isset($_REQUEST['port'])?$_REQUEST['port']:"3306"); ?>">
            </div>
        </div>
        <div class="space-4"></div>
        <div class="form-group">
            <label for="form-field-1" class="col-sm-3 control-label no-padding-right"> Database </label>
            <div class="col-sm-9">
                <input name="database" type="text" class="col-xs-10 col-sm-5" placeholder="Database" value="<?php echo(isset($_REQUEST['database'])?$_REQUEST['database']:""); ?>">
            </div>
        </div>
        <div class="space-4"></div>
        <div class="form-group">
            <label for="form-field-1" class="col-sm-3 control-label no-padding-right"> Username </label>
            <div class="col-sm-9">
                <input name="username" type="text" class="col-xs-10 col-sm-5" placeholder="Username" value="<?php echo(isset($_REQUEST['username'])?$_REQUEST['username']:""); ?>">
            </div>
        </div>
        <div class="space-4"></div>
        <div class="form-group">
            <label for="form-field-2" class="col-sm-3 control-label no-padding-right"> Password </label>

            <div class="col-sm-9">
                <input name="password" type="password" class="col-xs-10 col-sm-5" placeholder="Password">
            </div>
        </div>
        <hr/>
        <div style="height: 40px;">
            <button type="button" class="pull-left btn btn-primary" onclick="document.location.href='/installer?screen=1';">Back</button>
            <button type="submit" class="pull-right btn btn-primary">Next</button>
        </div>
    </form>
</div>
