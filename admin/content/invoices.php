<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="display: inline-block;">
        Invoices
    </h1>
    <button class="btn btn-primary btn-sm pull-right" onclick="showInvoiceForm();"><i class="icon-plus-sign align-top bigger-125"></i>Add</button>
    <button class="btn btn-success btn-sm pull-right" style="margin-right: 8px;" onclick="exportCurrentInvoices();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button>
    <div class="pull-right refsearchbox">
        <label for="refsearch">Ref:</label>&nbsp;<input id="refsearch" type="text" style="height: 35px;" onkeypress="if(event.keyCode == 13){doSearch();}"/>
        <button class="btn btn-primary btn-sm" style="vertical-align: top;" onclick="doSearch();"><i class="icon-search align-top bigger-125"></i>Search</button>
        <button id="refsearch_clearbtn" class="btn btn-warning btn-sm" style="display: none; vertical-align: top;" onclick="reloadInvoiceData();"><i class="icon-remove align-top bigger-125"></i></button>
    </div>
</div><!-- /.page-header -->

<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    View & search invoices
                </div>

                <div class="wpostable">
                    <table id="invoicestable" class="table table-striped table-bordered table-hover table-responsive">
                        <thead>
                        <tr>
                            <th data-priority="1">ID</th>
                            <th data-priority="8">Ref</th>
                            <th data-priority="3">Customer</th>
                            <th data-priority="9">User</th>
                            <th data-priority="4">Invoice Date</th>
                            <th data-priority="10">Due Date</th>
                            <th data-priority="7">Total</th>
                            <th data-priority="6">Balance</th>
                            <th data-priority="5">Status</th>
                            <th data-priority="2"></th>
                        </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- PAGE CONTENT ENDS -->
</div><!-- /.row -->
<div id="addinvoicedialog" class="hide" style="padding-left: 20px; padding-right: 20px;">
    <form id="addinvoiceform">
        <label class="fixedlabel">Customer: </label><select style="width: 180px;" id="ninvcustid"></select>
        <div class="space-8"></div>
        <label class="fixedlabel">Invoice Date: </label><input type="text" id="ninvprocessdt" onclick="$(this).blur();"/>
        <div class="space-8"></div>
        <label class="fixedlabel">Due Date: </label><input type="text" id="ninvduedt" onclick="$(this).blur();"/>
        <div class="space-8"></div>
        <label class="fixedlabel">Notes: </label><textarea id="ninvnotes"></textarea>
    </form>
</div>
<!-- inline scripts related to this page -->
<script type="text/javascript">
    var datatable;
    var etime = null; // start will no end time, so sales in different timezones show up.
    var stime = (new Date().getTime() - 2.62974e9); // a week ago
    // ADD/EDIT DIALOG FUNCTIONS
    function showInvoiceForm(){
        $("#ninvprocessdt").datepicker('setDate', new Date());
        var increment = WPOS.util.parseDateString(WPOS.getConfigTable().invoice.defaultduedt);
        $("#ninvduedt").datepicker('setDate', new Date((new Date()).getTime()+increment));
        $('#addinvoicedialog').dialog('open');
    }
    // DATA FUNCTIONS
    function addInvoice(){
        WPOS.util.showLoader();
        var ref = (new Date()).getTime()+"-0-"+Math.floor((Math.random() * 10000) + 1);
        var result = WPOS.sendJsonData("invoices/add", JSON.stringify({ref:ref, channel:"manual", discount:0, custid:$("#ninvcustid").val(), processdt:$("#ninvprocessdt").datepicker("getDate").getTime(), duedt:$("#ninvduedt").datepicker("getDate").getTime(), notes:$('#ninvnotes').val()}));
        if (result!==false){
            // add result to invoice data, reload table
            WPOS.transactions.setTransaction(result);
            reloadInvoicesTable();
            $('#addinvoicedialog').dialog('close');
            $('#addinvoiceform')[0].reset();
            WPOS.transactions.openTransactionDialog(ref);
        }
        WPOS.util.hideLoader();
    }

    function reloadInvoiceData(){
        resetSearchBox();
        var result = WPOS.sendJsonData("invoices/get", JSON.stringify({"stime":stime, "etime":etime}));
        if (result!==false){
            WPOS.transactions.setTransactions(result);
            reloadInvoicesTable();
        }
    }

    function reloadInvoicesTable(){
        var invoices = WPOS.transactions.getTransactions();
        var itemarray = [];
        for (var key in invoices){
            itemarray.push(invoices[key]);
        }
        datatable.fnClearTable(false);
        if (itemarray.length>0)
            datatable.fnAddData(itemarray, false);
        datatable.api().draw(false);
    }


    function doSearch(){
        var ref = $("#refsearch").val();
        if (ref==""){
            alert("Please enter a full or partial transaction reference.");
            return;
        }
        var data = {ref: ref};
        WPOS.sendJsonDataAsync("invoices/search", JSON.stringify(data), function(sales){
            var itemarray = [];
            if (sales !== false){
                WPOS.transactions.setTransactions(sales);
                var tempitem;
                for (var key in sales){
                    tempitem = sales[key];
                    tempitem.devlocname = (WPOS.devices.hasOwnProperty(tempitem.devid)?WPOS.devices[tempitem.devid].name:'NA')+" / "+(WPOS.locations.hasOwnProperty(tempitem.locid)?WPOS.locations[tempitem.locid].name:'NA');
                    itemarray.push(tempitem);
                }
                datatable.fnClearTable(false);
                console.log(itemarray);
                if (itemarray.length>0)
                    datatable.fnAddData(itemarray, false);
                datatable.api().draw(false);
                $("#refsearch_clearbtn").show();
            }
        });
    }

    function resetSearchBox(){
        $("#refsearch_clearbtn").hide();
        $("#refsearch").val('');
    }

    // functions for processing json data
    function getStatusHtml(status){
        var stathtml;
        switch(status){
            case -2:
                stathtml='<span class="label label-danger arrowed">Overdue</span>';
                break;
            case -1:
                stathtml='<span class="label label-primary arrowed">Open</span>';
                break;
            case 1:
                stathtml='<span class="label label-success arrowed">Closed</span>';
                break;
            case 2:
                stathtml='<span class="label arrowed">Void</span>';
                break;
            case 3:
                stathtml='<span class="label label-warning arrowed">Refunded</span>';
                break;
            default:
                stathtml='<span class="label arrowed">Unknown</span>';
                break
        }
        return stathtml;
    }
    function getTransactionStatus(record){
        if (record.hasOwnProperty('voiddata')){
            return 2;
        } else if (record.hasOwnProperty("refunddata")){
            // refund
            return 3;
        } else if (record.balance == 0 && record.total!=0){
            // closed
            return 1;
        } else if ((record.duedt < (new Date).getTime()) && record.balace!=0) {
            // overdue
            return -2
        }
        return -1;
    }

    function exportCurrentInvoices(){
        var invoices = WPOS.transactions.getTransactions();
        var csv = "ID, Reference, User, Device, Location, Customer Email, Items, #Items, Payments, Subtotal, Discount, Total, Invoice Time, Process Time, Status, Void Data, Refund Data\n"; // Set header
        var invoice;
        for (var i in invoices){
            invoice = invoices[i];
            // join items
            var itemstr = "";
            var itemqty = 0;
            for (var i2 in invoice.items){
                itemqty += parseInt(invoice.items[i2].qty);
                itemstr += "("+invoice.items[i2].qty+"x "+invoice.items[i2].name+"-"+invoice.items[i2].desc+" @ "+WPOS.util.currencyFormat(invoice.items[i2].unit)+(invoice.items[i2].tax.inclusive?" tax incl. ":" tax excl. ")+WPOS.util.currencyFormat(invoice.items[i2].tax.total)+" = "+WPOS.util.currencyFormat(invoice.items[i2].price)+") \n";
            }
            // join payments
            var paystr = "";
            for (i2 in invoice.payments){
                paystr += "("+invoice.payments[i2].method+"-"+WPOS.util.currencyFormat(invoice.payments[i2].amount)+") ";
            }
            var status = getTransactionStatus(invoices[i]);
            var voidstr = "";
            var refstr = "";
            if (status !== 1){
                // join void
                if (invoice.hasOwnProperty("voiddata")){
                    voidstr += WPOS.util.getDateFromTimestamp(invoice.voiddata.reason)+" - "+invoice.voiddata.reason;
                }
                // join refunds
                if (invoice.hasOwnProperty("refunddata")){
                    for (i2 in invoice.refunddata){
                        var ritems = JSON.stringify(invoice.refunddata[i2].items);
                        // TODO: get returned item string
                        refstr += "(" + WPOS.util.getDateFromTimestamp(invoice.refunddata[i2].processdt) + " - "+invoice.refunddata[i2].reason+" - "+invoice.refunddata[i2].method+" - "+WPOS.util.currencyFormat(invoice.refunddata[i2].amount)+" - items: "+ritems+") ";
                    }
                }
            }
            switch (status){
                case -2: status = "Overdue"; break;
                case -1: status = "Open"; break;
                case 1: status = "Closed"; break;
                case 2: status = "Void"; break;
                case 3: status = "Refunded"; break;
            }
            csv+=invoice.id+","+invoice.ref+","+WPOS.getConfigTable().users[invoice.userid].username+","+WPOS.getConfigTable().devices[invoice.devid].name+","+WPOS.getConfigTable().locations[invoice.locid].name+","
                +invoice.custemail+","+itemstr+","+itemqty+","+paystr+","+WPOS.util.currencyFormat(invoice.subtotal)+","+invoice.discount+"%,"+WPOS.util.currencyFormat(invoice.total)+","+WPOS.util.getDateFromTimestamp(invoice.processdt)+","+invoice.dt+","+status+","+voidstr+","+refstr+"\n";
        }

        WPOS.initSave("invoices-"+WPOS.util.getDateFromTimestamp(stime)+"-"+WPOS.util.getDateFromTimestamp(etime), csv);
    }

    $(function() {
        // get default data
        var data = WPOS.sendJsonData("multi", JSON.stringify({"customers/get":"", "invoices/get":{"stime":stime, "etime":etime}}));
        if (data===false) return;
        WPOS.customers.setCustomers(data['customers/get']);
        WPOS.transactions.setTransactions(data['invoices/get']);
        var customers = WPOS.customers.getCustomers();
        $('select#ninvcustid.select2-offscreen').find('option').remove().end();
        // below not needed
        //$('select#ninvcustid').find('option').remove().end();
        for (var c in customers){
            // do not use the class select2-offscreen to fix issue - https://github.com/micwallace/wallacepos/issues/41
            //$("select#ninvcustid.select2-offscreen").append('<option data-value="'+c+'" value="'+c+'">'+customers[c].name+'</option>');
            $("select#ninvcustid").append('<option data-value="'+c+'" value="'+c+'">'+customers[c].name+'</option>');
        }
        var invoices = WPOS.transactions.getTransactions();
        var itemarray = [];
        for (var key in invoices){
            itemarray.push(invoices[key]);
        }
        datatable = $('#invoicestable').dataTable({
            "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[8, "desc"],[ 0, "desc" ]],
            "aoColumns": [
                { "sType": "numeric", "mData":"id" },
                { "sType": "string", "mData":function(data, type, val){ return '<a class="reflabel" title="'+data.ref+'" href="">'+data.ref.split("-")[2]+'</a>'; } },
                { "sType": "string", "mData":function(data, type, val){ return (customers.hasOwnProperty(data.custid)?customers[data.custid].name:"N/A");} },
                { "sType": "string", "mData":function(data, type, val){ var users = WPOS.getConfigTable().users; if (users.hasOwnProperty(data.userid)){ return users[data.userid].username; } return 'N/A'; } },
                { "sType": "timestamp", "mData":function(data, type, val){return datatableTimestampRender(type, data.processdt, WPOS.util.getShortDate);} },
                { "sType": "timestamp", "mData":function(data, type, val){return datatableTimestampRender(type, data.duedt, WPOS.util.getShortDate);} },
                { "sType": "currency", "mData":function(data,type,val){return WPOS.util.currencyFormat(data["total"]);} },
                { "sType": "currency", "mData":function(data,type,val){return WPOS.util.currencyFormat(data["balance"]);} },
                { "sType": "html", "mData":function(data,type,val){return getStatusHtml(getTransactionStatus(data));} },
                { "sType": "html", mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="WPOS.transactions.openTransactionDialog($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'));"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="WPOS.transactions.deleteTransaction($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'))"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false }
            ],
            "columns": [
                {type: "numeric"},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {type: "timestamp"},
                {type: "timestamp"},
                {type: "currency"},
                {type: "currency"},
                {type: "html"},
                {}
            ]
        });

        // add controls
        $("#invoicestable_length").append('&nbsp;&nbsp;<div style="display: inline-block;"><label>Range: <input type="text" id="invstime" onclick="$(this).blur();" /></label> <label>to <input type="text" id="invetime" onclick="$(this).blur();" /></label></div>');

        // dialogs
        $( "#addinvoicedialog" ).removeClass('hide').dialog({
            resizable: false,
            maxWidth: 800,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Add Invoice",
            title_html: true,

            buttons: [
                {
                    html: "<i class='icon-edit bigger-110'></i>&nbsp; Save",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        addInvoice();
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
                $(this).css("maxWidth", "800px");
            }
        });
        // Invoice range datepickers
        var invstime = $("#invstime");
        var invetime = $("#invetime");
        var maxdate = new Date().getTime();
        invstime.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(maxdate),
            onSelect: function(text, inst){
                var date = $("#invstime").datepicker("getDate");
                date.setHours(0); date.setMinutes(0); date.setSeconds(0);
                stime = date.getTime();
                reloadInvoiceData();
            }
        });
        invetime.datepicker({dateFormat:"dd/mm/yy",
            onSelect: function(text, inst){
                var date = $("#invetime").datepicker("getDate");
                date.setHours(23); date.setMinutes(59); date.setSeconds(59);
                etime = date.getTime();
                reloadInvoiceData();
            }
        });
        invstime.datepicker('setDate', new Date(stime));

        // Add invoice datepickers
        $("#ninvprocessdt").datepicker({dateFormat:"dd/mm/yy"});
        $("#ninvduedt").datepicker({dateFormat:"dd/mm/yy"});

        // Customer multiselect
        $("#ninvcustid").select2();

        // hide loader
        WPOS.util.hideLoader();
    });

</script>
<style type="text/css">
    #invoicestable_processing {
        display: none;
    }
</style>
