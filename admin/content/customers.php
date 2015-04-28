<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 style="display: inline-block;">
        Customers
    </h1>
    <button onclick="WPOS.customers.openAddCustomerDialog();" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
    <button class="btn btn-success btn-sm pull-right" style="margin-right: 10px;" onclick="exportCustomers();"><i class="icon-cloud-download align-top bigger-125"></i>Export CSV</button>
</div><!-- /.page-header -->

<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    Manage your customer base
                </div>

                    <table id="customertable" class="table table-striped table-bordered table-hover">
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
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Mobile</th>
                            <th>Suburb</th>
                            <th>Postcode</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
            </div>
        </div>

    </div><!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->

<!-- inline scripts related to this page -->
<script type="text/javascript">
    var datatable;
    $(function() {
        WPOS.customers.loadCustomers();
        var data = WPOS.customers.getCustomers();
        var itemarray = [];
        for (var key in data){
            itemarray.push(data[key]);
        }
        datatable = $('#customertable').dataTable(
            { "bProcessing": true,
                "aaData": itemarray,
                "aoColumns": [
                    { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace" type="checkbox"><span class="lbl"></span></label><div>', "bSortable": false, sClass:"hidden-480 hidden-320 hidden-xs noexport" },
                    { "mData":"id" },
                    { "mData":"name" },
                    { "mData":"email" },
                    { "mData":"phone" },
                    { "mData":"mobile" },
                    { "mData":"suburb" },
                    { "mData":"postcode" },
                    { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="WPOS.customers.openCustomerDialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="WPOS.customers.deleteCustomer($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', "bSortable": false }
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
        // hide loader
        WPOS.util.hideLoader();
    });
    function reloadCustomerData(){
        WPOS.customers.loadCustomers();
        reloadCustomerTable();
    }
    function reloadCustomerTable(){
        var itemarray = [];
        var data = WPOS.customers.getCustomers();
        for (var key in data){
            itemarray.push(data[key]);
        }
        datatable.fnClearTable();
        datatable.fnAddData(itemarray);
    }
    function exportCustomers(){
        var data  = WPOS.table2CSV($("#customertable"));
        var filename = "customers-"+WPOS.util.getDateFromTimestamp(new Date());
        filename = filename.replace(" ", "");
        WPOS.initSave(filename, data);
    }
</script>
<style type="text/css">
    #customertable_processing {
        display: none;
    }
</style>