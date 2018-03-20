<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 class="inline">
        Staff & Admins
    </h1>
    <button onclick="$('#adduserdialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
</div><!-- /.page-header -->

<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    Manage POS users and Administrators
                </div>

                    <table id="usertable" class="table table-striped table-bordered table-hover dt-responsive" style="width:100%;">
                        <thead>
                        <tr>
                            <th data-priority="1">ID</th>
                            <th data-priority="3">Username</th>
                            <th data-priority="4">Status</th>
                            <th data-priority="2"></th>
                        </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
            </div>
        </div>

    </div><!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
<div id="edituserdialog" class="hide">
    <div style="display: inline-block; min-width: 325px; vertical-align: top; padding-bottom: 20px; margin: 0;">
    <h5 style="text-align: center;">User Details</h5>
    <table>
        <tr>
            <td style="text-align: right;"><label>Username:&nbsp;</label></td>
            <td><input id="usersname" type="text"/>
                <input id="userid" type="hidden"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Password:&nbsp;</label></td>
            <td><input id="userpass" type="password" value="" placeholder="Leave blank to ignore"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Confirm Password:&nbsp;</label></td>
            <td><input id="usercpass" type="password" value=""/></td>
        </tr>
    </table>
    </div>
    <div id="permisionsedit" style="display: inline-block; min-width: 325px; vertical-align: top; margin: 0;">
    <h5 style="text-align: center;">Dashboard Permissions</h5>
    <table class="table" style="min-width: 325px;">
        <thead class="table-header">
        <tr>
            <td>Section</td>
            <td>Permissions</td>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Access</td>
            <td>
                    <select id="permaccess">
                        <option value="no" onclick="setPermState(0);">No</option>
                        <option value="yes" onclick="setPermState(1);">Yes</option>
                        <option value="admin" onclick="setPermState(2);">Administrator</option>
                    </select>
            </td>
        </tr>
        <tr>
            <td>Dashboard</td>
            <td>
                    <select id="permdash" class="permsel">
                        <option value="both">Both</option>
                        <option value="standard">Standard</option>
                        <option value="realtime">Realtime</option>
                        <option value="none">None</option>
                    </select>
            </td>
        </tr>
        <tr>
            <td>Reports</td>
            <td><label>View: <input id="permreport" type="checkbox" class="permcb"/></label></td>
        </tr>
        <tr>
            <td>Graph</td>
            <td><label>View: <input id="permgraph" type="checkbox" class="permcb"/></label></td>
        </tr>
        <tr>
            <td>Sales</td>
            <td>
                <label>View: <input id="permsale" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="permsaleedit" type="checkbox" class="permcb"/> </label><br/>(notes update is allowed by all)
            </td>
        </tr>
        <tr>
            <td>Invoices</td>
            <td>
                <label>View: <input id="perminvoice" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="perminvoiceedit" type="checkbox" class="permcb"/> </label>
            </td>
        </tr>
        <tr>
            <td>Stored Items</td>
            <td>
                <label>View: <input id="permitem" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="permitemedit" type="checkbox" class="permcb"/></label>
            </td>
        </tr>
        <tr>
            <td>Stock</td>
            <td>
                <label>View: <input id="permstock" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="permstockedit" type="checkbox" class="permcb"/></label>
            </td>
        </tr>
        <tr>
            <td>Categories</td>
            <td>
                <label>View: <input id="permcat" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="permcatedit" type="checkbox" class="permcb"/></label>
            </td>
        </tr>
        <tr>
            <td>Suppliers</td>
            <td>
                <label>View: <input id="permsupp" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="permsuppedit" type="checkbox" class="permcb"/></label>
            </td>
        </tr>
        <tr>
            <td>Customers</td>
            <td>
                <label>View: <input id="permcust" type="checkbox" class="permcb"/></label>&nbsp;&nbsp;
                <label>Edit: <input id="permcustedit" type="checkbox" class="permcb"/></label>
            </td>
        </tr>
        </tbody>
    </table>
    </div>
</div>
<div id="adduserdialog" class="hide">
    <form id="newuserform">
        <table>
        <tr>
            <td style="text-align: right;"><label>Username:&nbsp;</label></td>
            <td><input id="newusername" type="text" autocomplete="off"/><br/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Password:&nbsp;</label></td>
            <td><input id="newuserpass" type="password" value="" autocomplete="off" /></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Confirm Password:&nbsp;</label></td>
            <td><input id="newusercpass" type="password" value="" autocomplete="off" /></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Admin:&nbsp;</label></td>
            <td><input id="newuseradmin" type="checkbox" autocomplete="off" /></td>
        </tr>
        </table>
    </form>

</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var users = null;
    var datatable;
    $(function() {
        users = WPOS.getJsonData("users/get");
        var itemarray = [];
        for (var key in users){
            itemarray.push(users[key]);
        }
        datatable = $('#usertable').dataTable({
            "bProcessing": true,
            "aaData": itemarray,
            "aoColumns": [
                { mData:"id" },
                { mData:"username" },
                { mData:function(data, type, val){ return '<i class="'+(data.disabled==1?'red icon-arrow-down':'green icon-arrow-up')+'"></i>'; } },
                { mData:function(data, type, val){ return data.id==0?'':'<div class="action-buttons"><a class="green" onclick="openEditUserDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                    (data.id!=1?(data.disabled==1?'<a class="green" onclick="setUserDisabled($(this).closest(\'tr\').find(\'td\').eq(0).text(), false)"><i class="icon-arrow-up bigger-130"></i></a><a class="red" onclick="removeItem($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a>':'<a class="red" onclick="setUserDisabled($(this).closest(\'tr\').find(\'td\').eq(0).text(), true)"><i class="icon-arrow-down bigger-130"></i></a>'):'')+'</div>'; }, "bSortable": false }
            ],
            "columns": [
                {type: "numeric"},
                {type: "string"},
                {type: "html"},
                {}
            ]
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
        $( "#adduserdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Add User",
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
        $( "#edituserdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit User",
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
                $(this).css("maxWidth", "800px");
            }
        });

        $(".permcb").on('click', function(){
            $("#permaccess").val("yes");
        });

        // hide loader
        WPOS.util.hideLoader();
    });
    function setPermState(state){
        var cb = $('.permcb');
        var sel = $('.permsel');
        if (state==0){
            cb.prop('checked', false);
            cb.prop('disabled', true);
            sel.prop('disabled', true);
            sel.val('none');
        } else {
            if (state==2){
                cb.prop('checked', true);
                cb.prop('disabled', true);
                sel.prop('disabled', true);
            } else {
                cb.prop('checked', false);
                cb.prop('disabled', false);
                sel.prop('disabled', false);
            }
            sel.val('both');
        }
    }
    // updating records
    function openEditUserDialog(id){
        var user = users[id];
        var username = $("#usersname");
        $("#userid").val(user.id);
        username.val(user.username);
        $("#userpass").val("");
        $("#usercpass").val("");
        if (user.id == 1){
            username.prop('disabled', true);
            $("#permisionsedit").hide();
        } else {
            username.prop('disabled', false);
            // populate permisions
            populatePermissions(user);
        }
        $("#edituserdialog").dialog("open");
    }
    function populatePermissions(user){
        // populate access status
        var perm = user.permissions.sections;
        if (user.admin==1){
            $("#permaccess").val('admin');
            setPermState(2)
        } else {
            $("#permaccess").val(perm.access);
            if (perm.access=="no"){
                setPermState(0)
            } else {
                $('.permcb').prop('disabled', false);
                $('.permsel').prop('disabled', false);
            }
        }
        // populate dash access
        $("#permdash").val(perm.dashboard);
        $("#permreport").prop("checked", perm.reports>0);
        $("#permgraph").prop("checked", perm.graph>0);

        $("#permsale").prop("checked", perm.sales>0);
        $("#permsaleedit").prop("checked", perm.sales>1);
        $("#perminvoice").prop("checked", perm.invoices>0);
        $("#perminvoiceedit").prop("checked", perm.invoices>1);
        $("#permitem").prop("checked", perm.items>0);
        $("#permitemedit").prop("checked", perm.items>1);
        $("#permstock").prop("checked", perm.stock>0);
        $("#permstockedit").prop("checked", perm.stock>1);
        $("#permcat").prop("checked", perm.categories>0);
        $("#permcatedit").prop("checked", perm.categories>1);
        $("#permsupp").prop("checked", perm.suppliers>0);
        $("#permsuppedit").prop("checked", perm.suppliers>1);
        $("#permcust").prop("checked", perm.customers>0);
        $("#permcustedit").prop("checked", perm.customers>1);

        $("#permisionsedit").show();
    }

    function getPermissionsObject(){
        var perm = {};
        perm['access'] = $("#permaccess").val();
        perm['dashboard'] = $("#permdash").val();
        perm['reports'] = $("#permreport").is(':checked')?1:0;
        perm['graph'] = $("#permgraph").is(':checked')?1:0;
        perm['sales'] = $("#permsaleedit").is(':checked')?2:($("#permsale").is(':checked')?1:0);
        perm['invoices'] = $("#perminvoiceedit").is(':checked')?2:($("#perminvoice").is(':checked')?1:0);
        perm['items'] = $("#permitemedit").is(':checked')?2:($("#permitem").is(':checked')?1:0);
        perm['stock'] = $("#permstockedit").is(':checked')?2:($("#permstock").is(':checked')?1:0);
        perm['categories'] = $("#permcatedit").is(':checked')?2:($("#permcat").is(':checked')?1:0);
        perm['suppliers'] = $("#permsuppedit").is(':checked')?2:($("#permsupp").is(':checked')?1:0);
        perm['customers'] = $("#permcustedit").is(':checked')?2:($("#permcust").is(':checked')?1:0);
        return perm;
    }
    function saveItem(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var user = {};
        var username, pass, cpass, isadmin, hpass;
        if (isnewitem){
            username =  $("#newusername").val();
            pass =  $("#newuserpass").val();
            cpass =  $("#newusercpass").val();
            isadmin = ($("#newuseradmin").is(":checked")?1:0);
        } else {
            username =  $("#usersname").val();
            pass =  $("#userpass").val();
            cpass =  $("#usercpass").val();
            isadmin = ($("#permaccess :selected").val()=="admin"?1:0);
        }
        if (username==""){
            alert("Please enter a username");
            return false;
        }
        if (isnewitem || pass != ""){
            if (pass == cpass){
                hpass = WPOS.util.SHA256(pass);
            } else {
                alert("Passwords do not match");
                return false;
            }
        }
        if (isnewitem){
            if (pass == ""){
                alert("Please enter a new password");
                return false;
            }
            // adding a new item
            user.username = username;
            user.pass = hpass;
            user.admin = isadmin;
            if (WPOS.sendJsonData("users/add", JSON.stringify(user))){
                reloadTable();
                $("#adduserdialog").dialog("close");
                // clear form
                $("#newuserform").trigger('reset');
            }
        } else {
            // updating an item
            user.id = $("#userid").val();
            user.username = username;
            user.pass = hpass;
            user.admin = isadmin;
            user.permissions = getPermissionsObject();
            if (WPOS.sendJsonData("users/edit", JSON.stringify(user))){
                reloadTable();
                $("#edituserdialog").dialog("close");
            }
        }
       // hide loader
       WPOS.util.hideLoader();
       return true;
    }
    function removeItem(id){

        var answer = confirm("Are you sure you want to delete this item? It's recommended to either back up first or disable the user instead.");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("users/delete", '{"id":'+id+'}')){
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function setUserDisabled(id, disable){
        var answer = confirm("Are you sure you want to disable this user?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("users/disable", '{"id":'+id+', "disable":'+disable+'}')!==false){
                reloadTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function reloadTable(){
        users = WPOS.getJsonData("users/get");
        var itemarray = [];
        for (var key in users){
            itemarray.push(users[key]);
        }
        datatable.fnClearTable(false);
        datatable.fnAddData(itemarray, false);
        datatable.api().draw(false);
    }
</script>
<style type="text/css">
    #usertable_processing {
        display: none;
    }
</style>