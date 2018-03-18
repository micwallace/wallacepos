<div class="page-header">
    <h1>
        My Account
        <small>
            <i class="icon-double-angle-right"></i>
            Manage your account details & settings
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">My Details</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>Name:</label></div>
                        <div class="col-sm-5"><input type="text" id="name" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Email/Username:</label></div>
                        <div class="col-sm-5"><input type="text" id="email" readonly/></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Phone:</label></div>
                        <div class="col-sm-5"><input type="text" id="phone" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Mobile:</label></div>
                        <div class="col-sm-5"><input type="text" id="mobile" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Address:</label></div>
                        <div class="col-sm-5"><input type="text" id="address" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Suburb:</label></div>
                        <div class="col-sm-5"><input type="text" id="suburb" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Postcode:</label></div>
                        <div class="col-sm-5"><input type="text" id="postcode" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>State:</label></div>
                        <div class="col-sm-5"><input type="text" id="state" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Country:</label></div>
                        <div class="col-sm-5"><input type="text" id="country" /></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-success" type="button" onclick="saveDetails();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<script type="text/javascript">
    var options;

    function saveDetails(){
        // show loader
        WPOS.util.showLoader();
        var data = {};
        $("form :input").each(function(){
            data[$(this).prop('id')] = $(this).val();
        });
        WPOS.sendJsonData("mydetails/save", JSON.stringify(data));
        // hide loader
        WPOS.util.hideLoader();
    }

    function loadDetails(){
        options = WPOS.getJsonData("mydetails/get");
        // load option values into the form
        for (var i in options){
            $("#"+i).val(options[i]);
        }
    }

    $(function(){
        loadDetails();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>