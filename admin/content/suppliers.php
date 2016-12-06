<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="margin-right: 20px; display: inline-block;">
        Suppliers
    </h1>
    <button onclick="$('#addsupdialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
</div><!-- /.page-header -->

<div class="row">
<div class="col-xs-12">
<!-- PAGE CONTENT BEGINS -->

<div class="row">
<div class="col-xs-12">

<div class="table-header">
    Manage your suppliers
</div>

<table id="supplierstable" class="table table-striped table-bordered table-hover dt-responsive" style="width: 100%;">
<thead>
<tr>
    <th data-priority="0" class="center noexport">
        <label>
            <input type="checkbox" class="ace" />
            <span class="lbl"></span>
        </label>
    </th>
    <th data-priority="4">ID</th>
    <th data-priority="2">Name</th>
    <th data-priority="3"># Items</th>
    <th data-priority="1" class="noexport"></th>
</tr>
</thead>

<tbody>

</tbody>
</table>

</div>
</div>

</div><!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
<div id="editsupdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="suppliername" type="text"/>
                <input id="supplierid" type="hidden"/></td>
        </tr>
    </table>
</div>
<div id="addsupdialog" class="hide">
    <table>
       <tr>
           <td style="text-align: right;"><label>Name:&nbsp;</label></td>
           <td><input id="newsuppliername" type="text"/><br/></td>
       </tr>
    </table>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var suppliers = null;
    var datatable;
    $(function() {
        suppliers = WPOS.getJsonData("suppliers/get");
        var suparray = [];
        var supitem;
        for (var key in suppliers){
            supitem = suppliers[key];
            suparray.push(supitem);
        }
        datatable = $('#supplierstable').dataTable({
            "bProcessing": true,
            "aaData": suparray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace dt-select-cb" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"noexport" },
                { "mData":"id" },
                { "mData":"name" },
                { "mData": "numitems"},
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="openeditsupdialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeSupplier($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false, sClass: "noexport" }
            ],
            "columns": [
                {},
                {type: "numeric"},
                {type: "string"},
                {type: "numeric"},
                {}
            ],
            "fnInfoCallback": function( oSettings, iStart, iEnd, iMax, iTotal, sPre ) {
                // Add selected row count to footer
                var selected = this.api().rows('.selected').count();
                return sPre+(selected>0 ? '<br/>'+selected+' row(s) selected <span class="action-buttons"><a class="red" onclick="removeSelectedSuppliers();"><i class="icon-trash bigger-130"></i></a></span>':'');
            }
        });

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
        $( "#addsupdialog" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Supplier",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                        "class" : "btn btn-success btn-xs",
                        click: function() {
                            saveSupplier(true);
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
        $( "#editsupdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Supplier",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveSupplier(false);
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
        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function openeditsupdialog(id){
        var item = suppliers[id];
        $("#supplierid").val(item.id);
        $("#suppliername").val(item.name);
        $("#editsupdialog").dialog("open");
    }
    function saveSupplier(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var item = {}, result;
        if (isnewitem){
            // adding a new supplier
            var name_field = $("#newsuppliername");
            item.name = name_field.val();
            result = WPOS.sendJsonData("suppliers/add", JSON.stringify(item));
            if (result!==false){
                suppliers[result.id] = result;
                reloadTable();
                name_field.val('');
                $("#addsupdialog").dialog("close");
            }
        } else {
            // updating an item
            item.id = $("#supplierid").val();
            item.name = $("#suppliername").val();
            result = WPOS.sendJsonData("suppliers/edit", JSON.stringify(item));
            if (result!==false){
                suppliers[result.id] = result;
                reloadTable();
                $("#editsupdialog").dialog("close");
            }
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function removeSupplier(id){

        var answer = confirm("Are you sure you want to delete this supplier?");
        if (answer){
            // show loader
            WPOS.util.hideLoader();
            if (WPOS.sendJsonData("suppliers/delete", '{"id":'+id+'}')){
                delete suppliers[id];
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function removeSelectedSuppliers(){
        var ids = datatable.api().rows('.selected').data().map(function(row){ return row.id });

        var answer = confirm("Are you sure you want to delete "+ids.length+" selected items?");
        if (answer){
            // show loader
            WPOS.util.hideLoader();
            if (WPOS.sendJsonData("suppliers/delete", '{"id":"'+ids.join(",")+'"}')){
                for (var i=0; i<ids.length; i++){
                    delete suppliers[ids[i]];
                }
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function reloadData(){
        suppliers = WPOS.getJsonData("suppliers/get");
        reloadTable();
    }
    function reloadTable(){
        var suparray = [];
        var tempsup;
        for (var key in suppliers){
            tempsup = suppliers[key];
            suparray.push(tempsup);
        }
        datatable.fnClearTable(false);
        datatable.fnAddData(suparray, false);
        datatable.api().draw(false);
    }
</script>
<style type="text/css">
    #supplierstable_processing {
        display: none;
    }
</style>