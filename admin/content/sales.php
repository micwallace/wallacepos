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
        var csv = "ID, Reference, User, Device, Location, Customer Email, Items, #Items, Payments, Subtotal, Discount, Total, Sale Time, Process Time, Status, Void Data, Refund Data\n"; // Set header
        var sale;
        for (var i in sales){
            sale = sales[i];
            // join items
            var itemstr = "";
            var itemqty = 0;
            for (var i2 in sale.items){
                itemqty += parseInt(sale.items[i2].qty);
                itemstr += "("+sale.items[i2].qty+"x "+sale.items[i2].name+"-"+sale.items[i2].desc+" @ "+WPOS.util.currencyFormat(sale.items[i2].unit)+(sale.items[i2].tax.inclusive?" tax incl. ":" tax excl. ")+WPOS.util.currencyFormat(sale.items[i2].tax.total)+" = "+WPOS.util.currencyFormat(sale.items[i2].price)+") ";
            }
            // join payments
            var paystr = "";
            for (i2 in sale.payments){
                paystr += "("+sale.payments[i2].method+"-"+WPOS.util.currencyFormat(sale.payments[i2].amount)+") ";
            }
            var status = getTransactionStatus(sales[i]);
            var voidstr = "";
            var refstr = "";
            if (status !== 1){
                // join void
                if (sale.hasOwnProperty("voiddata")){
                    voidstr += WPOS.util.getDateFromTimestamp(sale.voiddata.reason)+" - "+sale.voiddata.reason;
                }
                // join refunds
                if (sale.hasOwnProperty("refunddata")){
                    for (i2 in sale.refunddata){
                        var ritems = JSON.stringify(sale.refunddata[i2].items);
                        // TODO: get returned item string
                        refstr += "(" + WPOS.util.getDateFromTimestamp(sale.refunddata[i2].processdt) + " - "+sale.refunddata[i2].reason+" - "+sale.refunddata[i2].method+" - "+WPOS.util.currencyFormat(sale.refunddata[i2].amount)+" - items: "+ritems+") ";
                    }
                }
            }
            switch (status){
                case 1: status = "Complete"; break;
                case 2: status = "Void"; break;
                case 3: status = "Refunded"; break;
            }
            csv+=sale.id+","+sale.ref+","+WPOS.getConfigTable().users[sale.userid].username+","+WPOS.getConfigTable().devices[sale.devid].name+","+WPOS.getConfigTable().locations[sale.locid].name+","
                +sale.custemail+","+itemstr+","+itemqty+","+paystr+","+WPOS.util.currencyFormat(sale.subtotal)+","+sale.discount+"%,"+WPOS.util.currencyFormat(sale.total)+","+WPOS.util.getDateFromTimestamp(sale.processdt)+","+sale.dt+","+status+","+voidstr+","+refstr+"\n";
        }

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
            "aaSorting": [[ 0, "desc" ]],
            "aoColumns": [
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
                {type: "numeric"},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {type: "numeric"},
                {type: "timestamp"},
                {type: "currency"},
                {type: "html"},
                {}
            ]
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