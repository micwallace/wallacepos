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

<table id="categoriestable" class="table table-striped table-bordered table-hover">
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
    <th># Items</th>
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
        datatable = $('#categoriestable').dataTable(
            { "bProcessing": true,
            "aaData": suparray,
            "aaSorting": [[ 2, "asc" ]],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false, sClass:"hidden-480 hidden-320 hidden-xs noexport" },
                { "mData":"id" },
                { "mData":"name" },
                { "mData": "numitems"},
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="openeditcatdialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="removeSupplier($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false, sClass: "noexport" }
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
    function openeditcatdialog(id){
        var item = categories[id];
        $("#categoryid").val(item.id);
        $("#categoryname").val(item.name);
        $("#editcatdialog").dialog("open");
    }
    function saveSupplier(isnewitem){
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
    function removeSupplier(id){

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
        datatable.fnClearTable();
        datatable.fnAddData(suparray);
    }
</script>
<style type="text/css">
    #categoriestable_processing {
        display: none;
    }
</style>