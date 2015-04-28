<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        General Settings
        <small>
            <i class="icon-double-angle-right"></i>
            Manage general application settings
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Formats</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                <div class="form-group">
                    <div class="col-sm-5"><label>Date Format:</label></div>
                    <div class="col-sm-5">
                    <select id="dateformat">
                        <option value="d/m/y">dd/mm/yy</option>
                        <option value="m/d/y">mm/dd/yy</option>
                        <option value="Y-m-d">yyyy-mm-dd</option>
                    </select>
                    </div>
                </div>
                <div class="space-4"></div>
                <div class="form-group">
                    <div class="col-sm-5"><label>Currency Symbol:</label></div>
                    <div class="col-sm-5">
                    <select id="curformat">
                        <option value="$">$ Dollar</option>
                        <option value="€">€ Euro</option>
                        <option value="£">£ Pound</option>
                    </select>
                    </div>
                </div>
                <div class="space-4"></div>
                <div class="form-group" style="display: none;">
                    <div class="col-sm-5"><label>Accounting Type:</label></div>
                    <div class="col-sm-5">
                        <select id="accntype">
                            <option value="cash">Cash</option>
                            <option value="accrual">Accrual</option>
                        </select>
                    </div>
                </div>
                </form>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Google Contacts integration</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Enable:</label></div>
                        <div class="col-sm-5">
                            <input type="checkbox" id="gcontact" value="1" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Account:</label></div>
                        <div class="col-sm-5">
                            <a class="congaccn" style="display: none;" href="javascript:initGoogleAuth();">Connect Google Account</a>
                            <a class="disgaccn" style="display: none;" href="javascript:removeGoogleAuth();">Disconnect Google Account</a>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"></div>
                        <div class="col-sm-5">
                            <input class="congaccn" style="display: none;" placeholder="Paste Google Auth Code" type="text" id="gcontactcode" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Business Details</h4>
            </div>

            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>Business Name:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizname" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Business #:</label></div>
                        <div class="col-sm-5"><input type="text" id="biznumber" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Admin/Info Email:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizemail" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Address:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizaddress" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Suburb:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizsuburb" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>State:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizstate" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Postcode:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizpostcode" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Country:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizcountry" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Business Logo:</label></div>
                        <div class="col-sm-5">
                            <input type="text" id="bizlogo" /><br/>
                            <img id="bizlogoprev" width="128" height="64" src="" />
                            <input type="file" id="bizlogofile" name="file" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>App Icon:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizicon" /></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-success" type="button" onclick="saveSettings();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<script type="text/javascript">
    var options;

    function saveSettings(){
        // show loader
        WPOS.util.showLoader();
        var data = {};
        $("form :input").each(function(){
            data[$(this).prop('id')] = $(this).val();
        });
        data['gcontact'] = $("#gcontact").is(":checked")?1:0;
        WPOS.sendJsonData("settings/general/set", JSON.stringify(data));
        // hide loader
        WPOS.util.hideLoader();
    }

    function loadSettings(){
        options = WPOS.getJsonData("settings/general/get");
        // load option values into the form
        for (var i in options){
            $("#"+i).val(options[i]);
        }
        setGoogleUI();
        $("#bizlogoprev").attr("src", options.bizlogo);
    }
    function setGoogleUI(){
        $("#gcontact").prop("checked", options.gcontact==1);
        $("#gcontact").prop("disabled", options.gcontactaval!=1);
        if (options.gcontactaval==1){
            $(".congaccn").hide();
            $(".disgaccn").show();
        } else {
            $(".congaccn").show();
            $(".disgaccn").hide();
        }
    }
    function initGoogleAuth(){
        // show
        window.open('/api/settings/google/authinit','Connect with Google','width=500,height=500');
    }
    function removeGoogleAuth(){
        var answer = confirm("Are you sure you want to remove the current google acount & turn off intergration?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            var result = WPOS.getJsonData("settings/google/authremove");
            if (result!==false){
                alert("Google account successfully disconnected.");
                options.gcontact=0;
                options.gcontactaval=0;
                setGoogleUI();
            } else {
                alert("Google account removal failed.");
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    $('#bizlogofile').on('change',uploadLogo);
    $('#bizlogo').on('change',function(e){
        $("#bizlogoprev").prop("src", $(e.target).val());
    });

    function uploadLogo(event){
        WPOS.uploadFile(event, function(data){
            $("#bizlogo").val(data.path);
            $("#bizlogoprev").prop("src", data.path);
            saveSettings();
        }); // Start file upload, passing a callback to fire if it completes successfully
    }

    $(function(){
        loadSettings();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>