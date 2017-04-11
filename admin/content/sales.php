<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="display: inline-block;">
        POS Sales
    </h1>
    <button class="btn btn-success btn-sm pull-right" onclick="exportCurrentSales();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button>
    <div class="pull-right refsearchbox">
        <label for="refsearch">Ref:</label>&nbsp;<input id="refsearch" type="text" style="height: 35px;" onkeypress="if(event.keyCode == 13){doSearch();}"/>
        <button class="btn btn-primary btn-sm" style="vertical-align: top;" onclick="doSearch();"><i class="icon-search align-top bigger-125"></i>Search</button>
        <button id="refsearch_clearbtn" class="btn btn-warning btn-sm" style="display: none; vertical-align: top;" onclick="reloadSalesData();"><i class="icon-remove align-top bigger-125"></i></button>
    </div>
</div><!-- /.page-header -->

<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    View & search POS transactions
                </div>

                <div class="wpostable">
                    <table id="salestable" class="table table-striped table-bordered table-hover dt-responsive" style="width:100%;">
                        <thead>
                            <tr>
                                <th data-priority="0" class="center">
                                    <label>
                                        <input type="checkbox" class="ace" />
                                        <span class="lbl"></span>
                                    </label>
                                </th>
                                <th data-priority="1">ID</th>
                                <th data-priority="7">Ref</th>
                                <th data-priority="8">User</th>
                                <th data-priority="3">Device / Location</th>
                                <th data-priority="9"># Items</th>
                                <th data-priority="4">Sale Time</th>
                                <th data-priority="6">Total</th>
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
<!-- inline scripts related to this page -->
<script type="text/javascript">
    //var sales = null;
    var datatable;
    var etime = null; // start will no end time, so sales in different timezones show up.
    var stime = (new Date().getTime() - 604800000); // a week ago
    // functions for opening info dialogs and populating data
    function reloadSalesData(){
        // show loader
        WPOS.util.showLoader();
        resetSearchBox();
        var sales = WPOS.sendJsonData("sales/get", JSON.stringify({"stime":stime, "etime":etime}));
        WPOS.transactions.setTransactions(sales);
        reloadSalesTable();
        // hide loader
        WPOS.util.hideLoader();
    }

    function reloadSalesTable(){
        var itemarray = [];
        var tempitem;
        var sales = WPOS.transactions.getTransactions();
        for (var key in sales){
            tempitem = sales[key];
            tempitem.devlocname = (WPOS.devices.hasOwnProperty(tempitem.devid)?WPOS.devices[tempitem.devid].name:'NA')+" / "+(WPOS.locations.hasOwnProperty(tempitem.locid)?WPOS.locations[tempitem.locid].name:'NA');
            itemarray.push(tempitem);
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
        WPOS.sendJsonDataAsync("sales/search", JSON.stringify(data), function(sales){
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
            case 0:
                stathtml='<span class="label label-primary arrowed">Order</span>';
                break;
            case 1:
                stathtml='<span class="label label-success arrowed">Complete</span>';
                break;
            case 2:
                stathtml='<span class="label label-danger arrowed">Void</span>';
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
        } else if (record.hasOwnProperty("isorder")){
            return 0;
        }
        return 1;
    }

    function exportCurrentSales(){
        var sales = WPOS.transactions.getTransactions();
        WPOS.customers.loadCustomers();
        var customers = WPOS.customers.getCustomers();

        var data = {};
        var refs = datatable.api().rows('.selected').data().map(function(row){ return row.ref }).join(',').split(',');

        if (refs && refs.length > 0 && refs[0]!='') {
            for (var i = 0; i < refs.length; i++) {
                var ref = refs[i];
                if (sales.hasOwnProperty(ref))
                    data[ref] = sales[ref];
            }
        } else {
            data = sales;
        }

        var csv = WPOS.data2CSV(
            ['ID', 'Reference', 'User', 'Device', 'Location', 'Customer ID', 'Customer Email', 'Items', '# Items', 'Payments', 'Subtotal', 'Discount', 'Total', 'Sale DT', 'Created DT', 'Status', 'JSON Data'],
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
                'subtotal', 'discount', 'total',
                {key:'processdt', func: function(value){
                    return WPOS.util.getDateFromTimestamp(value, 'Y-m-d');
                }},
                'dt',
                {key:'status', func: function(value){
                    var status;
                    switch (value){
                        case 1: status = "Complete"; break;
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

        WPOS.initSave("sales-"+WPOS.util.getDateFromTimestamp(stime)+"-"+WPOS.util.getDateFromTimestamp(etime), csv);
    }

    $(function() {
        // get default data
        var sales = WPOS.sendJsonData("sales/get", JSON.stringify({"stime":stime, "etime":etime}));
        WPOS.transactions.setTransactions(sales);
        var itemarray = [];
        var tempitem;
        for (var key in sales){
            tempitem = sales[key];
            tempitem.devlocname = (WPOS.devices.hasOwnProperty(tempitem.devid)?WPOS.devices[tempitem.devid].name:'NA')+" / "+(WPOS.locations.hasOwnProperty(tempitem.locid)?WPOS.locations[tempitem.locid].name:'NA');
            itemarray.push(tempitem);
        }
        datatable = $('#salestable').dataTable({
            "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace dt-select-cb" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false },
                { "mData":"id" },
                { "mData":function(data, type, val){ return '<a class="reflabel" title="'+data.ref+'" href="">'+data.ref.split("-")[2]+'</a>'; } },
                { "mData":function(data, type, val){ var users = WPOS.getConfigTable().users; if (users.hasOwnProperty(data.userid)){ return users[data.userid].username; } return 'N/A'; } },
                { "mData":"devlocname" },
                { "mData":"numitems" },
                { "sType": "timestamp", "mData":function(data, type, val){ return datatableTimestampRender(type, data.processdt, WPOS.util.getDateFromTimestamp);} },
                { "sType": "currency", "mData":function(data,type,val){return WPOS.util.currencyFormat(data["total"]);} },
                { "mData":function(data,type,val){return getStatusHtml(getTransactionStatus(data));} },
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="WPOS.transactions.openTransactionDialog($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'));"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="WPOS.transactions.deleteTransaction($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'))"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false }
            ],
            "columns": [
                {},
                {type: "numeric"},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {type: "numeric"},
                {type: "timestamp"},
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
        $("#salestable_length").append('&nbsp;&nbsp;<div style="display: inline-block;"><label>Range: <input type="text" id="transstime" onclick="$(this).blur();" /></label> <label>to <input type="text" id="transetime" onclick="$(this).blur();" /></label></div>');
        var wrapper = $(".dataTables_wrapper");
        wrapper.find('.row:eq(0) > .col-sm-6:eq(0)').removeClass('col-sm-6').addClass('col-sm-8');
        wrapper.find('.row:eq(0) > .col-sm-6:eq(0)').removeClass('col-sm-6').addClass('col-sm-4');

        var maxdate = new Date().getTime();
        var sselect = $("#transstime"), eselect =$("#transetime");
        sselect.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(maxdate),
            onSelect: function(text, inst){
                var date = $("#transstime").datepicker("getDate");
                date.setHours(0); date.setMinutes(0); date.setSeconds(0);
                stime = date.getTime();
                reloadSalesData();
            }
        });
        eselect.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(maxdate),
            onSelect: function(text, inst){
                var date = $("#transetime").datepicker("getDate");
                date.setHours(23); date.setMinutes(59); date.setSeconds(59);
                etime = date.getTime();
                reloadSalesData();
            }
        });
        sselect.datepicker('setDate', new Date(stime));

        // hide loader
        WPOS.util.hideLoader();
    });

</script>
<style type="text/css">
    #salestable_processing {
        display: none;
    }
</style>