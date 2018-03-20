<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="margin-right: 20px; display: inline-block;">
        Categories
    </h1>
    <button onclick="$('#addcatdialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
</div><!-- /.page-header -->

<div class="row">
<div class="col-xs-12">
<!-- PAGE CONTENT BEGINS -->

<div class="row">
<div class="col-xs-12">

<div class="table-header">
    Manage your item categories
</div>

<table id="categoriestable" class="table table-striped table-bordered table-hover dt-responsive" style="width: 100%;">
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
<div id="editcatdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="categoryname" type="text"/>
                <input id="categoryid" type="hidden"/></td>
        </tr>
    </table>
</div>
<div id="addcatdialog" class="hide">
    <table>
       <tr>
           <td style="text-align: right;"><label>Name:&nbsp;</label></td>
           <td><input id="newcategoryname" type="text"/><br/></td>
       </tr>
    </table>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var categories = null;
    var datatable;
    $(function() {
        categories = WPOS.getJsonData("categories/get");
        var suparray = [];
        var supitem;
        for (var key in categories){
            supitem = categories[key];
            suparray.push(supitem);
        }
        datatable = $('#categoriestable').dataTable({
            "bProcessing": true,
            "aaData": suparray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace dt-select-cb" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"noexport" },
                { "mData":"id" },
                { "mData":"name" },
                { "mData": "numitems"},
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="openeditcatdialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeCategory($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false, sClass: "noexport" }
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
                return sPre+(selected>0 ? '<br/>'+selected+' row(s) selected <span class="action-buttons"><a class="red" onclick="removeSelectedCategories();"><i class="icon-trash bigger-130"></i></a></span>':'');
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
        $( "#addcatdialog" ).removeClass('hide').dialog({
                resizable: false,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Add Category",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                        "class" : "btn btn-success btn-xs",
                        click: function() {
                            saveCategory(true);
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
        $( "#editcatdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Category",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveCategory(false);
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
    function openeditcatdialog(id){
        var item = categories[id];
        $("#categoryid").val(item.id);
        $("#categoryname").val(item.name);
        $("#editcatdialog").dialog("open");
    }
    function saveCategory(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var item = {}, result;
        if (isnewitem){
            // adding a new category
            var name_field = $("#newcategoryname");
            item.name = name_field.val();
            result = WPOS.sendJsonData("categories/add", JSON.stringify(item));
            if (result!==false){
                categories[result.id] = result;
                reloadTable();
                name_field.val('');
                $("#addcatdialog").dialog("close");
            }
        } else {
            // updating an item
            item.id = $("#categoryid").val();
            item.name = $("#categoryname").val();
            result = WPOS.sendJsonData("categories/edit", JSON.stringify(item));
            if (result!==false){
                categories[result.id] = result;
                reloadTable();
                $("#editcatdialog").dialog("close");
            }
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function removeCategory(id){

        var answer = confirm("Are you sure you want to delete this category?");
        if (answer){
            // show loader
            WPOS.util.hideLoader();
            if (WPOS.sendJsonData("categories/delete", '{"id":'+id+'}')){
                delete categories[id];
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function removeSelectedCategories(){
        var ids = datatable.api().rows('.selected').data().map(function(row){ return row.id });

        var answer = confirm("Are you sure you want to delete "+ids.length+" selected items?");
        if (answer){
            // show loader
            WPOS.util.hideLoader();
            if (WPOS.sendJsonData("categories/delete", '{"id":"'+ids.join(",")+'"}')){
                for (var i=0; i<ids.length; i++){
                    delete categories[ids[i]];
                }
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function reloadData(){
        categories = WPOS.getJsonData("categories/get");
        reloadTable();
    }
    function reloadTable(){
        var suparray = [];
        var tempsup;
        for (var key in categories){
            tempsup = categories[key];
            suparray.push(tempsup);
        }
        datatable.fnClearTable(false);
        datatable.fnAddData(suparray, false);
        datatable.api().draw(false);
    }
</script>
<style type="text/css">
    #categoriestable_processing {
        display: none;
    }
</style>