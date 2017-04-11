<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Accounting Setting
        <small>
            <i class="icon-double-angle-right"></i>
            Manage tax preferences & accounting integration
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Tax Rules</h4>
            </div>
            <div style="padding-top: 10px;">
                <div class="table-header">
                    Tax rules are applied to sale items
                </div>
                <div class="table-responsive">
                    <table id="tax-rule-table" class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price Inclusive</th>
                            <th>Mode</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                    <br/><button id="addtaxrulebtn" class="btn btn-primary btn-sm pull-right" onclick="openTaxRuleDialog(0)"><i class="icon-pencil align-top bigger-125"></i>Add</button>
                </div>
            </div>
        </div>
        <div class="space-26"></div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Tax Items</h4>
            </div>
            <div style="padding-top: 10px;">
                <div class="table-responsive">
                    <div class="table-header">
                        Tax items/components are included in tax rules
                    </div>
                    <table id="tax-item-table" class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th class="hidden-480">Value</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>


                        </tbody>
                    </table>
                    <br/><button id="addtaxitembtn" class="btn btn-primary btn-sm pull-right" onclick="openTaxItemDialog(0)"><i class="icon-pencil align-top bigger-125"></i>Add</button>
                </div>
            </div>
        </div>
    </div>
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
                    <i>Xero export does not support multi mode tax rules at this time.</i>
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
        <div class="col-sm-12 align-center form-actions">
            <button class="btn btn-success" type="button" onclick="saveSettings();"><i class="icon-save align-top bigger-125"></i>Save</button>
        </div>
    </div>
</div>
<div id="edittaxitemdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="taxitemname" type="text"/>
                <input id="taxitemid" type="hidden"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Alt Name:&nbsp;</label></td>
            <td><input id="taxitemaltname" type="text"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Type:&nbsp;</label></td>
            <td>
                <select style="width: 180px;" id="taxitemtype">
                    <option value="standard">Standard</option>
                    <option value="vat">VAT</option>
                </select><br/>
                <small>Item unit cost must be set for VAT calculation.</small>
            </td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Value:&nbsp;</label></td>
            <td><input style="width: 165px;" id="taxitemvalue" type="text"/>%</td>
        </tr>
    </table>
</div>
<div id="edittaxruledialog" class="hide" style="min-width: 375px;">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="taxrulename" type="text"/>
                <input id="taxruleid" type="hidden"/></td>
        </tr>
        <tr>
            <td style="text-align: right; vertical-align: top;"><label>Inclusive:&nbsp;</label></td>
            <td><input id="taxruleinc" type="checkbox"/><br/><small>Tax is included in item unit price</small></td>
        </tr>
        <tr>
            <td style="text-align: right; vertical-align: top;"><label>Multi-Mode:&nbsp;</label></td>
            <td>
                <select id="taxrulemode">
                    <option value="single">Single</option>
                    <option value="multi">Multiple</option>
                </select>
                <br/><small>Single: Applies first matched location tax, or base tax if there is no match<br/>
                Multiple: Allows multiple taxes to be applied to an item</small>
            </td>
        </tr>
    </table>
    <div style="margin-bottom: 25px;">
        <h4 class="header blue lighter smaller no-margin-bottom">Base Taxes:</h4>
        <table id="taxrulebasetable" class="table" style="margin-bottom: 5px;">

        </table>
        <button onclick="insertTaxBaseRule(null);" class="btn btn-primary btn-xs pull-right">Add Base Tax</button>
    </div>
    <div>
        <h4 class="header blue lighter smaller no-margin-bottom">Apply at location:</h4>
        <table id="taxrulelocalstable" class="table no-margin-bottom" style="margin-bottom: 5px;">

        </table>
        <button onclick="insertTaxLocationRule(null, null);" class="btn btn-primary btn-xs pull-right">Add Rule</button>
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
        var result = WPOS.sendJsonData("settings/set", JSON.stringify(data));
        if (result !== false){
            WPOS.setConfigSet('accounting', result);
        }
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
            var tax = WPOS.getTaxTable().items;
            var taxtable = $("#taxmaptable");
            for (var i in tax){
                taxtable.append('<tr><td>'+tax[i].name+'</td><td><select id="xeromap-tax-'+tax[i].id+'" class="width-100 xerotaxselect"></select></td></tr>');
            }
            // add cash rounding account
            taxtable.append('<tr><td>Cash Rounding</td><td><select id="xeromap-tax-0" class="width-100 xerotaxselect"></select></td></tr>');
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
    // tax stuff
    var taxitemtable, taxruletable, rulearray, itemarray;
    var taxtable = WPOS.getTaxTable();
    function initTaxTables(){
        loadTaxRuleData();
        taxruletable = $('#tax-rule-table').dataTable(
            { "bProcessing": false,
                "sDom": '<"top">t<"clear">',
                "aaData": rulearray,
                "aoColumns": [
                    { "mData":"id" }, { "mData":"name" },
                    { "mData":function(data, type, val){ return data.inclusive?'<i class="icon-cogs green"></i>&nbsp;Inclusive':'<i class="icon-cogs red"></i>&nbsp;Exclusive' } },
                    { "mData":function(data, type, val){ return data.hasOwnProperty('mode')?WPOS.util.capFirstLetter(data.mode):''; } },
                    { "mData":function(data, type, val){ if (data.id==1) return ""; else return '<div class="action-buttons" style="width: 40px;"><a class="green" onclick="openTaxRuleDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                        '<a class="red" onclick="deleteTaxRule($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a></div>'; }, "bSortable": false }
                ] } );
        loadTaxItemData();
        taxitemtable = $('#tax-item-table').dataTable(
            { "bProcessing": false,
                "sDom": '<"top">t<"clear">',
                "aaData": itemarray,
                "aoColumns": [
                    { "mData":"id" }, { "mData":"name" }, { "mData":"type" }, { "mData":function(data, type, val){return data.value+"%";} },
                    { "mdata":null, "sDefaultContent":'<div class="action-buttons" style="width: 40px;"><a class="green" onclick="openTaxItemDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                        '<a class="red" onclick="deleteTaxItem($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false }
                ] } );
        // insert table wrapper
        $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");
        // init dialogs
        $( "#edittaxitemdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Tax Item",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-trash bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveTaxItem();
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ]
        });
        $( "#edittaxruledialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Tax Rule",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-trash bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveTaxRule();
                    }
                }
                ,
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ]
        });
    }
    function insertTaxBaseRule(id){
        $("#taxrulebasetable").append("<tr><td><select class='taxbase-id'>"+getTaxSelectOptions(id)+'</option></td><td><div class="action-buttons text-right"><a class="red" onclick="$(this).closest(\'tr\').remove();"><i class="icon-trash bigger-130"></i></a></div></td></tr>');
    }
    function insertTaxLocationRule(locid, taxid){
        var lochtml="";
        for (var key in WPOS.locations){
            lochtml+='<option value="'+WPOS.locations[key].id+'" '+(locid==WPOS.locations[key].id?'selected=selected':'')+'>'+WPOS.locations[key].name+'</option>';
        }
        $("#taxrulelocalstable").append("<tr><td><select class='taxlocals-locid'>"+lochtml+"</option></td>" +
                "<td><select class='taxlocals-taxid'>"+getTaxSelectOptions(taxid)+'</option></td><td><div class="action-buttons text-right"><a class="red" onclick="$(this).closest(\'tr\').remove();"><i class="icon-trash bigger-130"></i></a></div></td></tr>');
    }
    function getTaxSelectOptions(id){
        var html="";
        for (var key in taxtable.items){
            html+='<option class="taxid-'+taxtable.items[key].id+'" value="'+taxtable.items[key].id+'" '+(id==taxtable.items[key].id?'selected=selected':'')+'>'+taxtable.items[key].name+' ('+taxtable.items[key].value+'%)</option>';
        }
        return html;
    }
    function loadTaxRuleData(){
        var tempitem;
        rulearray = [];
        for (var key in taxtable.rules){
            tempitem = taxtable.rules[key];
            rulearray.push(tempitem);
        }
    }
    function loadTaxItemData(){
        var tempitem;
        itemarray = [];
        for (var key in taxtable.items){
            tempitem = taxtable.items[key];
            itemarray.push(tempitem);
        }
    }
    function reloadTaxRuleTable(){
        loadTaxRuleData();
        taxruletable.fnClearTable();
        taxruletable.fnAddData(rulearray);
    }
    function reloadTaxItemTable(){
        loadTaxItemData();
        taxitemtable.fnClearTable();
        taxitemtable.fnAddData(itemarray);
    }
    function openTaxItemDialog(id){
        $("#taxitemid").val(id);
        if (id==0){
            $("#taxitemname").val("");
            $("#taxitemvalue").val("");
            $("#edittaxitemdialog").dialog("open");
            return;
        }
        var item = taxtable.items[id];
        $("#taxitemname").val(item.name);
        $("#taxitemaltname").val(item.altname);
        $("#taxitemtype").val(item.type);
        $("#taxitemvalue").val(item.value);
        $("#edittaxitemdialog").dialog("open");
    }
    function saveTaxItem(){
        WPOS.util.showLoader();
        var item = {};
        item.name = $("#taxitemname").val();
        item.altname = $("#taxitemaltname").val();
        item.type = $("#taxitemtype").val();
        item.value = $("#taxitemvalue").val();
        var id = $("#taxitemid").val();
        var result;
        if (id==0){
            // adding a new item
            result = WPOS.sendJsonData("tax/items/add", JSON.stringify(item));
            if (result){
                taxtable.items[result.id] = result;
                WPOS.putTaxTable(taxtable);
                reloadTaxItemTable();
                $("#edittaxitemdialog").dialog("close");
            }
        } else {
            // updating an item
            item.id = id;
            result = WPOS.sendJsonData("tax/items/edit", JSON.stringify(item));
            if (result){
                taxtable.items[result.id] = result;
                WPOS.putTaxTable(taxtable);
                reloadTaxItemTable();
                $("#edittaxitemdialog").dialog("close");
            }
        }
        WPOS.util.hideLoader();
    }
    function deleteTaxItem(id){
        // check if it's being used in a rule
        id = parseInt(id);
        for (var i in taxtable.rules){
            if (taxtable.rules[i].base.indexOf(id)!==-1){
                alert("This tax item is being used in a rule, remove it from the rule first");
                return;
            }
            for (var x in taxtable.rules[i].locations){
                if (taxtable.rules[i].locations[x].indexOf(id)!==-1){
                    console.log(taxtable.rules[i].locations[x].indexOf(id));
                    alert("This tax item is being used in a rule, remove it from the rule first");
                    return;
                }
            }
        }
        var answer = confirm("Are you sure you want to delete this tax item?");
        if (answer){
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("tax/items/delete", '{"id":'+id+'}')){
                delete taxtable.items[id];
                WPOS.putTaxTable(taxtable);
                reloadTaxItemTable();
            }
            WPOS.util.hideLoader();
        }
    }
    function openTaxRuleDialog(id){
        $("#taxruleid").val(id);
        $("#taxrulebasetable").html('');
        $("#taxrulelocalstable").html('');
        if (id==0){
            $("#taxrulename").val('');
            $("#taxruleinc").prop("checked", true);
            $("#edittaxruledialog").dialog("open");
            return;
        }
        var rule = taxtable.rules[id];
        $("#taxrulename").val(rule.name);
        $("#taxruleinc").prop("checked", rule.inclusive);
        $("#taxrulemode").val(rule.mode);
        for (var i = 0; i<rule.base.length; i++){
            insertTaxBaseRule(rule.base[i]);
        }
        var x;
        for (i in rule.locations){
            for (x = 0; x<rule.locations[i].length; x++){
                insertTaxLocationRule(i, rule.locations[i][x]);
            }
        }
        $("#edittaxruledialog").dialog("open");
    }
    function saveTaxRule(){
        WPOS.util.showLoader();
        var rule = {};
        rule.name = $("#taxrulename").val();
        rule.inclusive = $("#taxruleinc").is(":checked");
        rule.mode = $("#taxrulemode").val();
        rule.base = [];
        rule.locations = {};
        $("#taxrulebasetable tr").each(function(){
           rule.base.push(parseInt($(this).find(".taxbase-id").val()));
        });
        $("#taxrulelocalstable tr").each(function(){
            var id = $(this).find(".taxlocals-locid").val();
            if (!rule.locations.hasOwnProperty(id))
                rule.locations[id] = [];
            rule.locations[id].push(parseInt($(this).find(".taxlocals-taxid").val()));

        });
        var id = $("#taxruleid").val();
        var result;
        if (id==0){
            // adding a new item
            result = WPOS.sendJsonData("tax/rules/add", JSON.stringify(rule));
            if (result){
                taxtable.rules[result.id] = result;
                WPOS.putTaxTable(taxtable);
                reloadTaxRuleTable();
                $("#edittaxruledialog").dialog("close");
            }
        } else {
            // updating an item
            rule.id = id;
            result = WPOS.sendJsonData("tax/rules/edit", JSON.stringify(rule));
            if (result){
                taxtable.rules[result.id] = result;
                WPOS.putTaxTable(taxtable);
                reloadTaxRuleTable();
                $("#edittaxruledialog").dialog("close");
            }
        }
        WPOS.util.hideLoader();
    }
    function deleteTaxRule(id){
        var answer = confirm("Are you sure you want to delete this tax rule?\nIf the rule is applied to stored items, this tax will no longer apply.");
        if (answer){
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("tax/rules/delete", '{"id":'+id+'}')){
                delete taxtable.rules[id];
                WPOS.putTaxTable(taxtable);
                reloadTaxRuleTable();
            }
            WPOS.util.hideLoader();
        }
    }
    $(function(){
        initTaxTables();
        loadSettings();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>