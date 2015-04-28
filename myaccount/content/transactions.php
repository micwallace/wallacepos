<div class="page-header">
    <h1 style="display: inline-block;">
        Transactions
    </h1>
</div><!-- /.page-header -->

<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    View & Search your Purchases
                </div>

                <div class="wpostable">
                    <table id="transtable" class="table table-striped table-bordered table-hover table-responsive">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ref</th>
                            <th>Sale Type</th>
                            <th>Processed Date</th>
                            <th>Due Date</th>
                            <th>Total</th>
                            <th>Balance</th>
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
var datatable;

$(function() {
    // get default data
    var data = WPOS.getJsonData("transactions/get");
    if (data===false) return;
    WPOS.transactions.setTransactions(data);
    var itemarray = [];
    for (var key in data){
        itemarray.push(data[key]);
    }
    datatable = $('#transtable').dataTable(
        { "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[8, "desc"],[ 0, "desc" ]],
            "aoColumns": [
                { "mData":"id" },
                { "mData":function(data, type, val){ return '<a class="reflabel" title="'+data.ref+'" href="">'+data.ref.split("-")[2]+'</a>'; } },
                { "mData":"type" },
                { "mData":function(data, type, val){return WPOS.util.getShortDate(data.processdt);} },
                { "mData":function(data, type, val){return WPOS.util.getShortDate(data.duedt);} },
                { "mData":function(data,type,val){return WPOS.currency()+data["total"];} },
                { "mData":function(data,type,val){return WPOS.currency()+data["balance"];} },
                { "mData":function(data,type,val){return WPOS.transactions.getTransactionStatus(data.ref, true);} },
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="WPOS.transactions.openTransactionDialog($(this).closest(\'tr\').find(\'.reflabel\').attr(\'title\'));"><i class="icon-pencil bigger-130"></i></a></div>', "bSortable": false }
            ] } );
    // insert table wrapper
    $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");

    // hide loader
    WPOS.util.hideLoader();
});

</script>
<style type="text/css">
    #transtable_processing {
        display: none;
    }
</style>