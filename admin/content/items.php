<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="margin-right: 20px; display: inline-block;">
        Items
    </h1>
    <button onclick="$('#adddialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
    <button class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" onclick="exportItems();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button>
</div><!-- /.page-header -->

<div class="row">
<div class="col-xs-12">
<!-- PAGE CONTENT BEGINS -->

<div class="row">
<div class="col-xs-12">

<div class="table-header">
    Manage your business products
</div>

<table id="itemstable" class="table table-striped table-bordered table-hover">
<thead>
<tr>
    <th class="center hidden-480 hidden-320 hidden-xs noexport">
        <label>
            <input type="checkbox" class="ace" />
            <span class="lbl"></span>
        </label>
    </th>
    <th>ID</th>
    <th>Name</th>
    <th>Description</th>
    <th>Tax</th>
    <th>Default Qty</th>
    <th>Price</th>
    <th>Stockcode</th>
    <th>Supplier</th>
    <th class="noexport"></th>
</tr>
</thead>
<tbody>

</tbody>
</table>

</div>
</div>

</div><!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
<div id="editdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="itemname" type="text"/>
                <input id="itemid" type="hidden"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Description:&nbsp;</label></td>
            <td><input id="itemdesc" type="text"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Unit Price:&nbsp;</label></td>
            <td><input id="itemprice" type="text" value="0"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Tax:&nbsp;</label></td>
            <td><select id="itemtax" class="taxselect">
                </select></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Default Qty:&nbsp;</label></td>
            <td><input id="itemqty" type="text" value="1"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Stockcode:&nbsp;</label></td>
            <td><input id="itemcode" type="text"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Supplier:&nbsp;</label></td>
            <td><select id="itemsupplier" class="supselect">
                </select></td>
        </tr>
    </table>
</div>
<div id="adddialog" class="hide">
    <table>
       <tr>
           <td style="text-align: right;"><label>Name:&nbsp;</label></td>
           <td><input id="newitemname" type="text"/><br/></td>
       </tr>
        <tr>
            <td style="text-align: right;"><label>Description:&nbsp;</label></td>
            <td><input id="newitemdesc" type="text"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Unit Price:&nbsp;</label></td>
            <td><input id="newitemprice" type="text" value="0"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Tax:&nbsp;</label></td>
            <td><select id="newitemtax" class="taxselect">
            </select></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Default Qty:&nbsp;</label></td>
            <td><input id="newitemqty" type="text" value="1"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Stockcode:&nbsp;</label></td>
            <td><input id="newitemcode" type="text"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Supplier:&nbsp;</label></td>
            <td><select id="newitemsupplier" class="supselect">
            </select></td>
        </tr>
    </table>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var stock = null;
    var suppliers = null;
    var datatable;
    $(function() {
        var data = WPOS.sendJsonData("multi", JSON.stringify({"items/get":"", "suppliers/get":""}));
        stock = data['items/get'];
        suppliers = data['suppliers/get'];
        var itemarray = [];
        var tempitem;
        for (var key in stock){
            tempitem = stock[key];
            tempitem.taxname = WPOS.getTaxTable()[tempitem.taxid].name+" ("+WPOS.getTaxTable()[tempitem.taxid].value+"%)";
            itemarray.push(tempitem);
        }
        datatable = $('#itemstable').dataTable(
            { "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"hidden-480 hidden-320 hidden-xs noexport" },
                { "mData":"id" },
                { "mData":"name" },
                { "mData":"description" },
                { "mData":"taxname" },
                { "mData":"qty" },
                { "mData":function(data,type,val){return (data['price']==""?"":WPOS.currency()+data["price"]);} },
                { "mData":"code" },
                { "mData":function(data,type,val){return (suppliers.hasOwnProperty(data.supplierid)?suppliers[data.supplierid].name:'Misc'); } },
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="openEditDialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeItem($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false, sClass: "noexport" }
            ] } );
        // insert table wrapper
        $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");


        $('table th input:checkbox').on('click' , function(){
            var that = this;
            $(this).closest('table').find('tr > td:first-child input:checkbox')
                .each(function(){
                    this.checked = that.checked;
                    $(this).closest('tr').toggleClass('selected');
                });

        });


        $('[data-rel="tooltip"]').tooltip({placement: tooltip_placement});
        function tooltip_placement(context, source) {
            var $source = $(source);
            var $parent = $source.closest('table');
            var off1 = $parent.offset();
            var w1 = $parent.width();

            var off2 = $source.offset();
            var w2 = $source.width();

            if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) return 'right';
            return 'left';
        }
        // dialogs
        $( "#adddialog" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Item",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                        "class" : "btn btn-success btn-xs",
                        click: function() {
                            saveItem(true);
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
                    $(this).css("maxWidth", "400px");
                }
        });
        $( "#editdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Item",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveItem(false);
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
                $(this).css("maxWidth", "400px");
            }
        });
        // populate tax records in select boxes
        var taxsel = $(".taxselect");
        taxsel.html('');
        for (key in WPOS.getTaxTable()){
            taxsel.append('<option class="taxid-'+WPOS.getTaxTable()[key].id+'" value="'+WPOS.getTaxTable()[key].id+'">'+WPOS.getTaxTable()[key].name+' ('+WPOS.getTaxTable()[key].value+'%)</option>');
        }
        // populate supplier records in select boxes
        var supsel = $(".supselect");
        supsel.html('');
        supsel.append('<option class="supid-0" value="0">None</option>');
        for (key in suppliers){
            supsel.append('<option class="supid-'+suppliers[key].id+'" value="'+suppliers[key].id+'">'+suppliers[key].name+'</option>');
        }

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function openEditDialog(id){
        var item = stock[id];
        $("#itemid").val(item.id);
        $("#itemname").val(item.name);
        $("#itemdesc").val(item.description);
        $("#itemqty").val(item.qty);
        $("#itemtax").val(item.taxid);
        $("#itemcode").val(item.code);
        $("#itemprice").val(item.price);
        $("#itemsupplier").val(item.supplierid);
        $("#editdialog").dialog("open");
    }
    function saveItem(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var item = {};
        var result;
        if (isnewitem){
            // adding a new item
            item.code = $("#newitemcode").val();
            item.qty = $("#newitemqty").val();
            item.name = $("#newitemname").val();
            item.description = $("#newitemdesc").val();
            item.taxid = $("#newitemtax").val();
            item.price = $("#newitemprice").val();
            item.supplierid = $("#newitemsupplier option:selected").val();
            result = WPOS.sendJsonData("items/add", JSON.stringify(item));
            if (result!==false){
                stock[result.id] = result;
                reloadTable();
                $("#adddialog").dialog("close");
            }
        } else {
            // updating an item
            item.id = $("#itemid").val();
            item.code = $("#itemcode").val();
            item.qty = $("#itemqty").val();
            item.name = $("#itemname").val();
            item.description = $("#itemdesc").val();
            item.taxid = $("#itemtax").val();
            item.price = $("#itemprice").val();
            item.supplierid = $("#itemsupplier option:selected").val();
            result = WPOS.sendJsonData("items/edit", JSON.stringify(item));
            if (result!==false){
                stock[result.id] = result;
                reloadTable();
                $("#editdialog").dialog("close");
            }
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function removeItem(id){

        var answer = confirm("Are you sure you want to delete this item?");
        if (answer){
            // show loader
            WPOS.util.hideLoader();
            if (WPOS.sendJsonData("items/delete", '{"id":'+id+'}')){
                delete stock[id];
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function reloadData(){
        stock = WPOS.getJsonData("items/get");
        reloadTable();
    }
    function reloadTable(){
        var itemarray = [];
        var tempitem;
        for (var key in stock){
            tempitem = stock[key];
            tempitem.taxname = WPOS.getTaxTable()[tempitem.taxid].name+" ("+WPOS.getTaxTable()[tempitem.taxid].value+"%)";
            itemarray.push(tempitem);
        }
        datatable.fnClearTable();
        datatable.fnAddData(itemarray);
    }
    function exportItems(){
        var data  = WPOS.table2CSV($("#itemstable"));
        var filename = "items-"+WPOS.util.getDateFromTimestamp(new Date());
        filename = filename.replace(" ", "");
        WPOS.initSave(filename, data);
    }
</script>
<style type="text/css">
    #itemstable_processing {
        display: none;
    }
</style>