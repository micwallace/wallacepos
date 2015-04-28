<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Accounting Setting
        <small>
            <i class="icon-double-angle-right"></i>
            Manage accounting integration and preferences
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Xero Accounting Export</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form id="accnsettings" class="form-horizontal">
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-3"><label>Enable:</label></div>
                        <div class="col-sm-7">
                            <input type="checkbox" id="xeroenabled" value="1" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-3"><label>Account:</label></div>
                        <div class="col-sm-7">
                            <a class="conxaccn" style="display: none;" href="javascript:initXeroAuth();">Connect Xero Account</a>
                            <a class="disxaccn" style="display: none;" href="javascript:removeXeroAuth();">Disconnect Xero Account</a>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-3"><label>Account Mappings:</label></div>
                        <div class="col-sm-7 disxaccn" style="display: none;">
                            <table class="table table-responsive">
                                <tbody>
                                    <tr>
                                        <td><label>Sales<select id="xeromap-sales" class="width-100"></select></label></td>
                                        <td><label>Refunds<select id="xeromap-refunds" class="width-100"></select></label></td>
                                    </tr>
                                    <tr>
                                        <td><label>Cash Payments<select id="xeromap-pay-cash" class="xeropayselect width-100"></select></label></td>
                                        <td><label>Card Payments<select id="xeromap-pay-card" class="xeropayselect width-100"></select></label></td>
                                    </tr>
                                    <tr>
                                        <td><label>Deposit Payments<select id="xeromap-pay-deposit" class="xeropayselect width-100"></select></label></td>
                                        <td><label>Cheque Payments<select id="xeromap-pay-cheque" class="xeropayselect width-100"></select></label></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-3"><label>Tax Mappings:</label></div>
                        <div class="col-sm-7 disxaccn" style="display: none;">
                            <table class="table table-responsive">
                                <tbody id="taxmaptable">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"></div>
                        <div class="col-sm-5 disxaccn">
                            <button type="button" class="btn btn-primary btn-sm" onclick="showSalesExportDialog();">Export Sales to Xero</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-success" type="button" onclick="saveSettings();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<div id="salesexportdialog" class="hide">
    <div id="exportform">
        <input id="exportsdate" type="text"/>
        <input id="exportedate" type="text"/>
    </div>
    <div id="exportprogress" style="display: none; text-align: center;">
        <i class="blue icon-cloud-upload icon-4x"></i>
        <div id="exprogtext" style="text-align: center">
            <h3>Exporting Sales</h3>
            <div id="loadingprogdiv" class="progress progress-striped active">
                <div class="progress-bar" id="loadingprog" style="width: 100%;"></div>
            </div>
            <h3>Exported <span id="exprogcount">0</span> of <span id="exprogtotal">0</span> days</h3>
        </div>
        <div id="exprogresult" style="display: none;">
            <h3 id="progrestext">Successfully Exported Sales</h3>
        </div>
    </div>
</div>
<script type="text/javascript">
    var options;
    var xeroaccounts;

    function saveSettings(){
        // show loader
        WPOS.util.showLoader();
        var data = {};
        data.xeroaccnmap = {};
        $("#accnsettings :input").each(function(){
            if ($(this).prop('id').indexOf('xeromap')!==-1){
                var mapvalues = $(this).prop('id').split('-');
                if (mapvalues.length==3){
                    mapvalues[1] = mapvalues[1]+'-'+mapvalues[2];
                }
                data.xeroaccnmap[mapvalues[1]] = $(this).val();
            } else {
                data[$(this).prop('id')] = $(this).val();
            }
        });

        data['xeroenabled'] = $("#xeroenabled").is(":checked")?1:0;
        data['name'] = "accounting";
        WPOS.sendJsonData("settings/set", JSON.stringify(data));
        // hide loader
        WPOS.util.hideLoader();
    }

    function loadSettings(){
        options = WPOS.sendJsonData("settings/get", JSON.stringify({name:"accounting"}));
        // load option values into the form
        for (var i in options){
            if (i!="xeroaccnmap"){
                $("#"+i).val(options[i]);
            }
        }
        setXeroUI();
    }
    function setXeroUI(){
        var xenabled = $("#xeroenabled");
        xenabled.prop("checked", options.xeroenabled==1);
        xenabled.prop("disabled", options.xeroaval!=1);
        if (options.xeroaval==1){
            $(".conxaccn").hide();
            $(".disxaccn").show();
            var tax = WPOS.getTaxTable();
            for (var i in tax){
                $("#taxmaptable").append('<tr><td>'+tax[i].name+'</td><td><select id="xeromap-tax-'+tax[i].id+'" class="width-100 xerotaxselect"></select></td></tr>');
            }
            // populate xero accounts
            getXeroAccountSelects();
        } else {
            $(".conxaccn").show();
            $(".disxaccn").hide();
        }
    }
    function getXeroAccountSelects(){
        WPOS.sendJsonDataAsync("settings/xero/configvalues", '', populateXeroAccounts);
    }
    function populateXeroAccounts(data){
        var sselect = $("#xeromap-sales");
        var rselect = $("#xeromap-refunds");
        var pselect = $(".xeropayselect");
        var tselect = $(".xerotaxselect");
        sselect.html(''); rselect.html('');
        var account;
        for (var i in data.Accounts){
            account = data.Accounts[i];
            if ((account.Type=="SALES" || account.Type=="CURRENT") || (account.Type=="CURRLIAB" || account.Type=="REVENUE")){
                sselect.append('<option value="'+account.Code+'">'+account.Name+' ('+account.Code+')</option>');
                rselect.append('<option value="'+account.Code+'">'+account.Name+' ('+account.Code+')</option>');
            }
            if (account.Type=="BANK" || account.EnablePaymentsToAccount)
                pselect.append('<option value="'+account.Code+'">'+account.Name+' ('+account.Code+')</option>');
        }
        var tax;
        for (var i in data.TaxRates){
            tax = data.TaxRates[i];
            tselect.append('<option value="'+tax.TaxType+'">'+tax.Name+'</option>');
        }
        // populate preferences
        for (var m in options.xeroaccnmap){
            $("#xeromap-"+m).val(options.xeroaccnmap[m]);
        }
    }
    function initXeroAuth(){
        // show oauth window, when the oauth process is complete, we can reload the UI
        var child = window.open('/api/settings/xero/oauthinit','Connect with Xero','width=500,height=500');
        var timer = setInterval(checkChild, 500);
        function checkChild() {
            if (child.closed) {
                // refresh the main panes content
                clearInterval(timer);
                WPOS.loadPageContent("");
            }
        }
    }
    function removeXeroAuth(){
        var answer = confirm("Are you sure you want to remove the current Xero account & turn off integration?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            var result = WPOS.getJsonData("settings/xero/oauthremove");
            if (result!==false){
                alert("Xero account successfully disconnected.");
                options.xeroenabled=0;
                options.xeroaval=0;
                setXeroUI();
            } else {
                alert("Xero account removal failed.");
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function showSalesExportDialog(){
        $("#exportform").show();
        $("#exportprogress").hide();
        $("#exprogtext").show();
        $("#exprogresult").hide();
        var expdialog = $( "#salesexportdialog");
        expdialog.removeClass('hide').dialog({
            resizable: false,
            maxWidth: 400,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Export Sales",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-edit bigger-110'></i>&nbsp; Export",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        exportSalesToXero();
                    }
                },
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "400px");
            }
        });
        var exstime = $("#exportsdate");
        var exetime = $("#exportedate");
        var date = new Date();
        date.setDate(date.getDate()-1);
        date.setHours(0); date.setMinutes(0); date.setSeconds(0);
        var stime = date.getTime();
        date.setHours(23); date.setMinutes(59); date.setSeconds(59);
        var etime = date.getTime();
        exstime.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(stime)});
        exetime.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(etime)});
        // set to the last day
        exstime.datepicker('setDate', new Date(stime));
        exetime.datepicker('setDate', new Date(etime));

        expdialog.dialog('open');
    }

    function exportSalesToXero(){
        var exdialog = $("#salesexportdialog");
        exdialog.dialog('option', 'buttons', {});
        exdialog.dialog('option', 'closeOnEscape', false);
        $("#salesexportdialog .ui-dialog-titlebar-close").hide();
        $("#exportform").hide();
        $("#exportprogress").show();

        var stime = $("#exportsdate").datepicker("getDate");
        stime.setHours(0); stime.setMinutes(0); stime.setSeconds(0);
        stime = stime.getTime();
        var etime = $("#exportedate").datepicker("getDate");
        etime.setHours(23); etime.setMinutes(59); etime.setSeconds(59);
        etime = etime.getTime();
        if (etime<stime){
            alert("The start date cannot come before the end date");
        }
        // find out how many days
        var days = Math.round((etime-stime)/86400000);
        $("#exprogtotal").text(days);
        var exprog = $("#exprogcount");
        exprog.val("0");
        var cetime = etime - ((days-1)*86400000);
        var error = false;
        while (cetime<=etime){
            var result = WPOS.sendJsonData("settings/xero/export", JSON.stringify({"stime":stime,"etime":cetime}));
            if (result==false){
                error = true;
                break;
                //alert(stime+" "+cetime);
            }
            exprog.text(parseInt(exprog.text())+1);

            stime+=86400000;
            cetime+=86400000;
        }
        $("#exprogtext").hide();
        $("#exprogresult").show();
        var restext = $("#progrestext");
        if (error == false){
            restext.text("Successfully Exported Sales");
            restext.addClass("green");
            restext.removeClass("red");
        } else {
            restext.text("Error Exporting Sales");
            restext.addClass("red");
            restext.removeClass("green");
        }
    }

    $(function(){
        loadSettings();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>