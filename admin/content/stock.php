<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 class="inline">
        Item Inventory
    </h1>
    <button onclick="openAddStockDialog();" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
    <button class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" onclick="exportStock();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button>
</div><!-- /.page-header -->

<div class="row">
<div class="col-xs-12">
<!-- PAGE CONTENT BEGINS -->

<div class="row">
<div class="col-xs-12">

<div class="table-header">
    Manage your product inventory
</div>

<table id="stocktable" class="table table-striped table-bordered table-hover dt-responsive" style="width:100%;">
    <thead>
        <tr>
            <th data-priority="0" class="center">
                <label>
                    <input type="checkbox" class="ace" />
                    <span class="lbl"></span>
                </label>
            </th>
            <th data-priority="2">Name</th>
            <th data-priority="5">Supplier</th>
            <th data-priority="3">Location</th>
            <th data-priority="4">Qty</th>
            <th data-priority="1" class="noexport"></th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
</div>
</div>

</div><!-- PAGE CONTENT ENDS -->
</div><!-- /.row -->
<div id="editstockdialog" class="hide">
    <table>
        <tr>
            <input type="hidden" id="setstockitemid" />
            <input type="hidden" id="setstocklocid" />
            <td style="text-align: right;"><label>Qty:&nbsp;</label></td>
            <td><input id="setstockqty" type="text" value="1"/></td>
        </tr>
    </table>
</div>
<div id="transferstockdialog" class="hide">
    <table>
        <tr>
            <input type="hidden" id="tstockitemid" />
            <input type="hidden" id="tstocklocid" />
            <td style="text-align: right;"><label>Transfer to:&nbsp;</label></td>
            <td><select id="tstocknewlocid" class="locselect">
                </select></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Qty:&nbsp;</label></td>
            <td><input id="tstockqty" type="text" value="1"/></td>
        </tr>
    </table>
</div>
<div id="addstockdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Item:</label></td>
            <td><select id="addstockitemid" class="itemselect">
                </select></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Location:</label></td>
            <td><select id="addstocklocid" class="locselect">
            </select></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Qty:&nbsp;</label></td>
            <td><input id="addstockqty" type="text" value="1"/></td>
        </tr>
    </table>
</div>
<div id="stockhistdialog" class="hide">

    <div style="width: 100%; overflow-x: auto;">
    <table class="table table-responsive table-stripped">
        <thead>
            <tr>
                <th>Item</th>
                <th>Location</th>
                <th>Type</th>
                <th>Amount</th>
                <th>DT</th>
            </tr>
        </thead>
        <tbody id="stockhisttable">

        </tbody>
    </table>
    </div>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var stock = null;
    var items = null;
    var datatable;
    $(function() {
        stock = WPOS.getJsonData("stock/get");
        var stockarray = [];
        var tempstock;
        for (var key in stock){
            tempstock = stock[key];
            stockarray.push(tempstock);
        }
        datatable = $('#stocktable').dataTable({"bProcessing": true,
            "aaData": stockarray,
            "aaSorting": [[ 2, "asc" ]],
            "aLengthMenu": [ 10, 25, 50, 100, 200],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace dt-select-cb" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false },
                { mData:function(data,type,val){return (data.name==null?"Unknown":data.name) } },
                { mData:"supplier" },
                { mData:function(data,type,val){return (data.locationid!=='0'?(WPOS.locations.hasOwnProperty(data.locationid)?WPOS.locations[data.locationid].name:'Unknown'):'Warehouse');} },
                { mData:"stocklevel" },
                { mData:function(data,type,val){return '<div class="action-buttons"><a class="green" onclick="openEditStockDialog('+data.id+');"><i class="icon-pencil bigger-130"></i></a><a class="blue" onclick="openTransferStockDialog('+data.id+')"><i class="icon-arrow-right bigger-130"></i></a><a class="red" onclick="getStockHistory('+data.storeditemid+', '+data.locationid+');"><i class="icon-time bigger-130"></i></a></div>'; }, "bSortable": false }
            ],
            "columns": [
                {},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {type: "numeric"},
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

        // dialogs
        $( "#addstockdialog" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Stock",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                        "class" : "btn btn-success btn-xs",
                        click: function() {
                            saveItem(1);
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
                ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "375px");
            }
        });
        $( "#editstockdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Stock",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveItem(2);
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
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "375px");
            }
        });
        $( "#transferstockdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Transfer Stock",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveItem(3);
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
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "375px");
            }
        });
        $( "#stockhistdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            maxWidth: '700px',
            modal: true,
            autoOpen: false,
            title: "Stock History",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                    "class" : "btn btn-xs",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "700px");
            }
        });
        // fill location selects
        var locselect = $(".locselect");
        locselect.html('');
        for (key in WPOS.locations){
            if (key == 0){
                locselect.append('<option class="locid-0" value="0">Warehouse</option>');
            } else {
                locselect.append('<option class="locid-'+WPOS.locations[key].id+'" value="'+WPOS.locations[key].id+'">'+WPOS.locations[key].name+'</option>');
            }
        }

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function getStockHistory(id, locationid){
        WPOS.util.showLoader();
        var stockhist = WPOS.sendJsonData("stock/history", JSON.stringify({storeditemid: id, locationid: locationid}));
        // populate stock dialog with list
        $("#stockhisttable").html("");
        var hist;
        for (var i in stockhist){
            hist = stockhist[i];
            $("#stockhisttable").append('<tr><td>'+hist.name+'</td><td>'+hist.location+'</td><td>'+hist.type+(hist.auxid!=-1?(hist.auxdir==1?" from ":" to ")+(hist.auxid==0?"Warehouse":WPOS.locations[hist.auxid].name):"")+'</td><td>'+hist.amount+'</td><td>'+hist.dt+'</td></tr>');
        }
        WPOS.util.hideLoader();
        $("#stockhistdialog").dialog('open');
    }
    function openEditStockDialog(id){
        var item = stock[id];
        $("#setstockitemid").val(item.storeditemid);
        $("#setstocklocid").val(item.locationid);
        $("#setstockqty").val(item.stocklevel);
        $("#editstockdialog").dialog("open");
    }
    function openAddStockDialog(){
        populateItems();
        $("#addstockdialog").dialog("open");
    }
    function openTransferStockDialog(id){
        var item = stock[id];
        $("#tstockitemid").val(item.storeditemid);
        $("#tstocklocid").val(item.locationid);
        $("#transferstockdialog").dialog("open");
    }
    function populateItems(){
        if (items == null){
            WPOS.util.showLoader();
            items = WPOS.sendJsonData("items/get");
            var itemselect = $(".itemselect");
            itemselect.html('');
            for (var i in items){
                itemselect.append('<option class="itemid-'+items[i].id+'" value="'+items[i].id+'">'+items[i].name+'</option>');
            }
            WPOS.util.hideLoader();
        }
    }
    function saveItem(type){
        // show loader
        WPOS.util.showLoader();
        var item = {};
        switch (type){
        case 1:
            // adding new stock
            item.storeditemid = $("#addstockitemid option:selected").val();
            item.locationid = $("#addstocklocid option:selected").val();
            item.amount = $("#addstockqty").val();
            if (WPOS.sendJsonData("stock/add", JSON.stringify(item))!==false){
                reloadTable();
                $("#addstockdialog").dialog("close");
            }
            break;
        case 2:
            // set stock level
            item.storeditemid = $("#setstockitemid").val();
            item.locationid = $("#setstocklocid").val();
            item.amount = $("#setstockqty").val();
            if (WPOS.sendJsonData("stock/set", JSON.stringify(item))!==false){
                reloadTable();
                $("#editstockdialog").dialog("close");
            }
            break;
        case 3:
            // transfer stock
            item.storeditemid = $("#tstockitemid").val();
            item.locationid = $("#tstocklocid").val();
            item.newlocationid = $("#tstocknewlocid").val();
            item.amount = $("#tstockqty").val();
            if (WPOS.sendJsonData("stock/transfer", JSON.stringify(item))!==false){
               reloadTable();
               $("#transferstockdialog").dialog("close");
            }
            break;
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function reloadTable(){
        stock = WPOS.getJsonData("stock/get");
        var stockarray = [];
        var tempstock;
        for (var key in stock){
            tempstock = stock[key];
            stockarray.push(tempstock);
        }
        datatable.fnClearTable(false);
        datatable.fnAddData(stockarray, false);
        datatable.api().draw(false);
    }
    function exportStock(){
        //var data  = WPOS.table2CSV($("#stocktable"));
        var filename = "stock-"+WPOS.util.getDateFromTimestamp(new Date());
        filename = filename.replace(" ", "");

        var data = {};
        var ids = datatable.api().rows('.selected').data().map(function(row){ return row.id }).join(',').split(',');

        if (ids && ids.length > 0 && ids[0]!='') {
            for (var i = 0; i < ids.length; i++) {
                var id = ids[i];
                if (stock.hasOwnProperty(id))
                    data[id] = stock[id];
            }
        } else {
            data = stock;
        }

        var csv = WPOS.data2CSV(
            ['ID', 'Name', 'Supplier', 'Location', 'Qty'],
            ['id', 'name', 'supplier', {key:'locationid', func: function(value){ return WPOS.locations.hasOwnProperty(value) ? WPOS.locations[value].name : 'Unknown'; }}, 'stocklevel'],
            data
        );

        WPOS.initSave(filename, csv);
    }
</script>
<style type="text/css">
    #itemstable_processing {
        display: none;
    }
</style>