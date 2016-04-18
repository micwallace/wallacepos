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
    <th>Category</th>
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
    <div class="tabbable" style="min-width: 360px; min-height: 310px;">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#itemdetails" data-toggle="tab">
                    Details
                </a>
            </li>
            <li class="">
                <a href="#itemoptions" data-toggle="tab">
                    Options
                </a>
            </li>
        </ul>
        <div class="tab-content" style="min-height: 320px;">
            <div class="tab-pane active in" id="itemdetails">
                <table>
                    <tr>
                        <td style="text-align: right;"><label>Name:&nbsp;</label></td>
                        <td><input id="itemname" type="text"/>
                            <input id="itemid" type="hidden"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label>Alternate Name:&nbsp;</label></td>
                        <td><input id="itemaltname" type="text"/><br/>
                            <small>Alternate language name</small>
                        </td>
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
                        <td style="text-align: right;"><label>Category:&nbsp;</label></td>
                        <td><select id="itemcategory" class="catselect">
                            </select></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><label>Supplier:&nbsp;</label></td>
                        <td><select id="itemsupplier" class="supselect">
                            </select></td>
                    </tr>
                </table>
            </div>
            <div class="tab-pane" id="itemoptions" style="min-height: 280px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-4"><label>Item Type:</label></div>
                        <div class="col-sm-8">
                            <select id="itemtype">
                                <option value="general">General</option>
                                <option value="food">Food</option>
                                <option value="beverage">Beverage</option>
                            </select>
                            <br/><small>Used for kitchen terminal dispatch</small>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-12"><label>Simple Modifiers:</label></div>
                        <table class="table table-stripped table-responsive" style="margin-bottom: 0; padding-left: 10px; margin-right: 10px;">
                            <thead class="table-header smaller">
                                <tr>
                                    <th><small>Qty</small></th>
                                    <th><small>Min Qty</small></th>
                                    <th><small>Max Qty</small></th>
                                    <th><small>Name</small></th>
                                    <th><small>Price</small></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemmodtable">

                            </tbody>
                        </table>
                        <button style="float: right; margin-right: 8px;" class="btn btn-primary btn-xs" onclick="addItemModifier();">Add</button>
                        <div class="col-sm-12"><label>Select Modifiers:</label></div>
                        <table class="table table-stripped table-responsive" style="margin-bottom: 0; padding-left: 10px; margin-right: 10px;">
                            <tbody id="itemselmodtable">

                            </tbody>
                        </table>
                        <button style="float: right; margin-right: 8px;" class="btn btn-primary btn-xs" onclick="addSelectItemModifier();">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="adddialog" class="hide">
    <table>
        <tr>
           <td style="text-align: right;"><label>Name:&nbsp;</label></td>
           <td><input id="newitemname" type="text"/><br/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Alternate Name:&nbsp;</label></td>
            <td><input id="newitemaltname" type="text"/><br/>
                <small>Alternate language name</small>
            </td>
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
            <td style="text-align: right;"><label>Category:&nbsp;</label></td>
            <td><select id="newitemcategory" class="catselect">
                </select></td>
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
    var categories = null;
    var datatable;
    $(function() {
        var data = WPOS.sendJsonData("multi", JSON.stringify({"items/get":"", "suppliers/get":"", "categories/get":""}));
        stock = data['items/get'];
        suppliers = data['suppliers/get'];
        categories = data['categories/get'];
        var itemarray = [];
        var tempitem;
        var taxrules = WPOS.getTaxTable().rules;
        for (var key in stock){
            tempitem = stock[key];
            if (taxrules.hasOwnProperty(tempitem.taxid)){
                tempitem.taxname = taxrules[tempitem.taxid].name;
            } else {
                tempitem.taxname = "Not Defined";
            }
            itemarray.push(tempitem);
        }
        datatable = $('#itemstable').dataTable(
            { "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"hidden-480 hidden-320 hidden-xs noexport" },
                { "sType": "numeric", "mData":"id" },
                { "sType": "string", "mData":"name" },
                { "sType": "string", "mData":"description" },
                { "sType": "string", "mData":"taxname" },
                { "sType": "numeric", "mData":"qty" },
                { "sType": "currency", "mData":function(data,type,val){return (data['price']==""?"":WPOS.util.currencyFormat(data["price"]));} },
                { "sType": "string", "mData":"code" },
                { "sType": "string", "mData":function(data,type,val){return (categories.hasOwnProperty(data.categoryid)?categories[data.categoryid].name:'Misc'); } },
                { "sType": "string", "mData":function(data,type,val){return (suppliers.hasOwnProperty(data.supplierid)?suppliers[data.supplierid].name:'Misc'); } },
                { "sType": "html", mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="openEditDialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeItem($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false, sClass: "noexport" }
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
                    $(this).css("maxWidth", "460px");
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
                $(this).css("maxWidth", "460px");
            }
        });
        // populate tax records in select boxes
        var taxsel = $(".taxselect");
        taxsel.html('');
        for (key in WPOS.getTaxTable().rules){
            taxsel.append('<option class="taxid-'+WPOS.getTaxTable().rules[key].id+'" value="'+WPOS.getTaxTable().rules[key].id+'">'+WPOS.getTaxTable().rules[key].name+'</option>');
        }
        // populate category & supplier records in select boxes
        var supsel = $(".supselect");
        supsel.html('');
        supsel.append('<option class="supid-0" value="0">None</option>');
        for (key in suppliers){
            supsel.append('<option class="supid-'+suppliers[key].id+'" value="'+suppliers[key].id+'">'+suppliers[key].name+'</option>');
        }

        var catsel = $(".catselect");
        catsel.html('');
        catsel.append('<option class="catid-0" value="0">None</option>');
        for (key in categories){
            catsel.append('<option class="catid-'+categories[key].id+'" value="'+categories[key].id+'">'+categories[key].name+'</option>');
        }

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function openEditDialog(id){
        var item = stock[id];
        $("#itemid").val(item.id);
        $("#itemname").val(item.name);
        $("#itemaltname").val(item.alt_name);
        $("#itemdesc").val(item.description);
        $("#itemqty").val(item.qty);
        $("#itemtax").val(item.taxid);
        $("#itemcode").val(item.code);
        $("#itemprice").val(item.price);
        $("#itemsupplier").val(item.supplierid);
        $("#itemcategory").val(item.categoryid);
        $("#itemtype").val(item.type);
        var modtable = $("#itemmodtable");
        var modselecttable = $("#itemselmodtable");
        modtable.html('');
        modselecttable.html('');
        if (item.hasOwnProperty('modifiers')){
            var mod;
            for (var i=0; i<item.modifiers.length; i++){
                mod = item.modifiers[i];
                if (mod.type=='select'){
                    var modopttable = '';
                    for (var o=0; o<mod.options.length; o++){
                        modopttable += '<tr><td><input onclick="handleSelectCheckbox(this);" type="checkbox" class="modoptdefault" '+(mod.options[o].default==true?'checked="checked"':'')+'/></td><td><input style="width: 130px" type="text" class="modoptname" value="'+mod.options[o].name+'"/></td><td><input type="text" style="width: 60px" class="modoptprice" value="'+mod.options[o].price+'"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parent().parent().remove();">X</button></td></tr>';
                    }
                    modselecttable.append('<tr class="selmoditem"><td colspan="4" style="padding-right: 0; padding-left: 0;"><div style="padding-left: 8px; padding-right: 8px;"><label>Name:</label>&nbsp;<input style="width: 130px" type="text" class="modname" value="'+mod.name+'"/><button class="btn btn-danger btn-xs pull-right" style="margin-left: 5px;" onclick="$(this).parents().eq(2).remove();">X</button></div><table class="table" style="margin-top: 5px;">'+modtableheader+'<tbody class="modoptions">'+modopttable+'</tbody></table></td></tr>');
                } else {
                    modtable.append('<tr><td><input type="text" style="width: 40px" class="modqty" value="'+mod.qty+'"/></td><td><input type="text" style="width: 40px" class="modminqty" value="'+mod.minqty+'"/></td><td><input type="text" style="width: 40px" class="modmaxqty" value="'+mod.maxqty+'"/></td><td><input style="width: 130px" type="text" class="modname" value="'+mod.name+'"/></td><td><input type="text" style="width: 60px" class="modprice" value="'+mod.price+'"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parent().parent().remove();">X</button></td></tr>');
                }
            }
        }
        $("#editdialog").dialog("open");
    }
    function addItemModifier(){
        $("#itemmodtable").append('<tr><td><input onchange="var row = $(this).parent().parent(); if ($(this).val()>row.find(\'.modminqty\').val()) row.find(\'.modminqty\').val($(this).val())" type="text" style="width: 40px" class="modqty" value="0"/></td><td><input type="text" style="width: 40px" class="modminqty" value="0"/></td><td><input type="text" style="width: 40px" class="modmaxqty" value="0"/></td><td><input style="width: 130px" type="text" class="modname" value=""/></td><td><input type="text" style="width: 60px" class="modprice" value="0.00"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parent().parent().remove();">X</button></td></tr>');
    }
    function addSelectItemModifier(){
        var modseltable = $("#itemselmodtable");
        var modelem = $('<tr class="selmoditem"><td colspan="4" style="padding-right: 0; padding-left: 0;"><div style="padding-left: 8px; padding-right: 8px;"><label>Name:</label>&nbsp;<input style="width: 130px" type="text" class="modname" value=""/><button class="btn btn-danger btn-xs pull-right" style="margin-left: 5px;" onclick="$(this).parents().eq(2).remove();">X</button></div><table class="table" style="margin-top: 5px;">'+modtableheader+'<tbody class="modoptions">'+modselectoption+'</tbody></table></td></tr>');
        modelem.find('.modoptdefault').prop('checked', true);
        modseltable.append(modelem);
    }
    var modtableheader = '<thead class="table-header smaller"><tr><th><small>Default</small></th><th><small>Name</small></th><th><small>Price</small></th><th><button class="btn btn-primary btn-xs pull-right" onclick="addSelectModItem($(this).parents().eq(3).find(\'.modoptions\'));">Add Option</button></th></tr></thead>';
    var modselectoption = '<tr><td><input onclick="handleSelectCheckbox($(this));" type="checkbox" class="modoptdefault"/></td><td><input style="width: 130px" type="text" class="modoptname" value=""/></td><td><input type="text" style="width: 60px" class="modoptprice" value="0.00"/></td><td style="text-align: right;"><button class="btn btn-danger btn-xs" onclick="$(this).parents().eq(1).remove();">X</button></td></tr>';
    function addSelectModItem(elem){
        $(elem).append(modselectoption);
        if (elem.find('tr').length==1) $(elem).find('.modoptdefault').prop('checked', true);
    }
    function handleSelectCheckbox(elem){
        var table = $(elem).parent().parent().parent();
        table.find('.modoptdefault').prop('checked', false);
        $(elem).prop('checked', true);
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
            item.alt_name = $("#newitemaltname").val();
            item.description = $("#newitemdesc").val();
            item.taxid = $("#newitemtax").val();
            item.price = $("#newitemprice").val();
            item.supplierid = $("#newitemsupplier").val();
            item.categoryid = $("#newitemcategory").val();
            item.type = "general";
            item.modifiers = [];
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
            item.alt_name = $("#itemaltname").val();
            item.description = $("#itemdesc").val();
            item.taxid = $("#itemtax").val();
            item.price = $("#itemprice").val();
            item.supplierid = $("#itemsupplier").val();
            item.categoryid = $("#itemcategory").val();
            item.type = $("#itemtype").val();
            item.modifiers = [];
            $("#itemselmodtable .selmoditem").each(function(){
                var mod = {type:"select", options:[]};
                mod.name = $(this).find(".modname").val();
                $(this).find('.modoptions tr').each(function(){
                    var modoption = {};
                    modoption.default = $(this).find(".modoptdefault").is(':checked');
                    modoption.name = $(this).find(".modoptname").val();
                    modoption.price = $(this).find(".modoptprice").val();
                    mod.options.push(modoption);
                });
                item.modifiers.push(mod);
            });
            $("#itemmodtable tr").each(function(){
               var mod = {type:"simple"};
               mod.qty = $(this).find(".modqty").val();
               mod.minqty = $(this).find(".modminqty").val();
               mod.maxqty = $(this).find(".modmaxqty").val();
               mod.name = $(this).find(".modname").val();
               mod.price = $(this).find(".modprice").val();
               item.modifiers.push(mod);
            });
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
            tempitem.taxname = WPOS.getTaxTable().rules[tempitem.taxid].name;
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