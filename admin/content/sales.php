<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="display: inline-block;">
        POS Sales
    </h1>
    <button style="display: inline-block; vertical-align: top; float: right;" class="btn btn-success btn-sm" onclick="exportCurrentSales();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button>
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
                    <table id="salestable" class="table table-striped table-bordered table-hover table-responsive">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ref</th>
                            <th>User</th>
                            <th>Device / Location</th>
                            <th># Items</th>
                            <th>Sale Time</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th></th>
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
    var etime = new Date().getTime();
    var stime = (etime - 604800000); // a week ago
    // functions for opening info dialogs and populating data
    function reloadSalesData(){
        // show loader
        WPOS.util.showLoader();
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
            tempitem.devlocname = WPOS.devices[tempitem.devid].name+" / "+WPOS.locations[tempitem.locid].name;
            itemarray.push(tempitem);
        }
        datatable.fnClearTable();
        datatable.fnAddData(itemarray);
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
            tempitem.devlocname = WPOS.devices[tempitem.devid].name+" / "+WPOS.locations[tempitem.locid].name;
            itemarray.push(tempitem);
        }
        datatable = $('#salestable').dataTable(
            { "bProcessing": true,
                "aaData": itemarray,
                "aaSorting": [[ 1, "desc" ]],
                "aoColumns": [
                    { "sType": "numeric", "mData":"id" },
                    { "sType": "numeric", "mData":function(data, type, val){ return '<a class="reflabel" title="'+data.ref+'" href="">'+data.ref.split("-")[2]+'</a>'; } },
                    { "sType": "string", "mData":function(data, type, val){ return WPOS.getConfigTable().users[data.userid].username;} },
                    { "sType": "string", "mData":"devlocname" },
                    { "sType": "numeric", "mData":"numitems" },
                    { "sType": "timestamp", "mData":function(data, type, val){return WPOS.util.getDateFromTimestamp(data.processdt);} },
                    { "sType": "currency", "mData":function(data,type,val){return WPOS.util.currencyFormat(data["total"]);} },
                    { "sType": "html", "mData":function(data,type,val){return getStatusHtml(getTransactionStatus(data));} },
                    { "sType": "html", mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="WPOS.transactions.openTransactionDialog($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'));"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="WPOS.transactions.deleteTransaction($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'))"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false }
                ] } );
        // insert table wrapper
        $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");
        // add controls
        $("#salestable_length").append('&nbsp;&nbsp;<div style="display: inline-block;"><label>Range: <input type="text" id="transstime" onclick="$(this).blur();" /></label> <label>to <input type="text" id="transetime" onclick="$(this).blur();" /></label></div>');

        var sselect = $("#transstime"), eselect =$("#transetime");
        sselect.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(etime),
            onSelect: function(text, inst){
                var date = $("#transstime").datepicker("getDate");
                date.setHours(0); date.setMinutes(0); date.setSeconds(0);
                stime = date.getTime();
                reloadSalesData();
            }
        });
        eselect.datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(etime),
            onSelect: function(text, inst){
                var date = $("#transetime").datepicker("getDate");
                date.setHours(23); date.setMinutes(59); date.setSeconds(59);
                etime = date.getTime();
                reloadSalesData();
            }
        });
        sselect.datepicker('setDate', new Date(stime));
        eselect.datepicker('setDate', new Date(etime));

        // hide loader
        WPOS.util.hideLoader();
    });

</script>
<style type="text/css">
    #salestable_processing {
        display: none;
    }
</style>