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
                            <th data-priority="0" class="center">
                                <label>
                                    <input type="checkbox" class="ace" />
                                    <span class="lbl"></span>
                                </label>
                            </th>
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
        var customers = WPOS.customers.getCustomers();

        var data = {};
        var refs = datatable.api().rows('.selected').data().map(function(row){ return row.ref }).join(',').split(',');

        if (refs && refs.length > 0 && refs[0]!='') {
            for (var i = 0; i < refs.length; i++) {
                var ref = refs[i];
                if (invoices.hasOwnProperty(ref))
                    data[ref] = invoices[ref];
            }
        } else {
            data = invoices;
        }

        var csv = WPOS.data2CSV(
            ['ID', 'Reference', 'User', 'Device', 'Location', 'Customer ID', 'Customer Email', 'Items', '# Items', 'Payments', 'Subtotal', 'Discount', 'Total', 'Balance', 'Invoice DT', 'Due DT', 'Created DT', 'Status', 'JSON Data'],
            [
                'id', 'ref',
                {key:'userid', func: function(value){
                    return WPOS.users.hasOwnProperty(value) ? WPOS.users[value].username : 'Unknown';
                }},
                {key:'devid', func: function(value){
                    return WPOS.devices.hasOwnProperty(value) ? WPOS.devices[value].name : 'Unknown';
                }},
                {key:'locid', func: function(value){
                    return WPOS.locations.hasOwnProperty(value) ? WPOS.locations[value].name : 'Unknown';
                }},
                'custid',
                {key:'custid', func: function(value){
                    return customers.hasOwnProperty(value) ? customers[value].email : '';
                }},
                {key:'items', func: function(value){
                    var itemstr = '';
                    for (var i in value){
                        itemstr += value[i].qty+"x "+value[i].name+"-"+value[i].desc+" @ "+WPOS.util.currencyFormat(value[i].unit)+(value[i].tax.inclusive?" tax incl. ":" tax excl. ")+WPOS.util.currencyFormat(value[i].tax.total)+" = "+WPOS.util.currencyFormat(value[i].price)+" \n";
                    }
                    return itemstr;
                }},
                'numitems',
                {key:'payments', func: function(value){
                    var paystr = '';
                    for (var i in value){
                        paystr += value[i].method+" "+WPOS.util.currencyFormat(value[i].amount)+" ";
                    }
                    return paystr;
                }},
                'subtotal', 'discount', 'total', 'balance',
                {key:'processdt', func: function(value){
                    return WPOS.util.getDateFromTimestamp(value, 'Y-m-d');
                }},
                {key:'duedt', func: function(value){
                    return WPOS.util.getDateFromTimestamp(value, 'Y-m-d');
                }},
                'dt',
                {key:'status', func: function(value){
                    var status;
                    switch (value){
                        case -2: status = "Overdue"; break;
                        case -1: status = "Open"; break;
                        case 1: status = "Closed"; break;
                        case 2: status = "Void"; break;
                        case 3: status = "Refunded"; break;
                    }
                    return status;
                }},
                {key:'id', func: function(value, record){
                    return record;
                }}
            ],
            data
        );

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
            "aaSorting": [[8, "desc"],[ 1, "desc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace dt-select-cb" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false },
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
                {},
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
            ],
            "fnInfoCallback": function( oSettings, iStart, iEnd, iMax, iTotal, sPre ) {
                // Add selected row count to footer
                var selected = this.api().rows('.selected').count();
                return sPre+(selected>0 ? '<br/>'+selected+' row(s) selected':'');
            }
        });

        // row selection checkboxes
        datatable.find("tbody").on('click', '.dt-select-cb', function(e){
            var row = $(this).parents().eq(3);
            if (row.hasClass('selected')) {
                row.removeClass('selected');
            } else {
                row.addClass('selected');
            }
            datatable.api().draw(false);
            e.stopPropagation();
        });

        $('table.dataTable th input:checkbox').on('change' , function(){
            var that = this;
            $(this).closest('table.dataTable').find('tr > td:first-child input:checkbox')
                .each(function(){
                    var row = $(this).parents().eq(3);
                    if ($(that).is(":checked")) {
                        row.addClass('selected');
                        $(this).prop('checked', true);
                    } else {
                        row.removeClass('selected');
                        $(this).prop('checked', false);
                    }
                });
            datatable.api().draw(false);
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
