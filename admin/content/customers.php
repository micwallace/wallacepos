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

                    <table id="customertable" class="table table-striped table-bordered table-hover dt-responsive" style="width: 100%;">
                        <thead>
                        <tr>
                            <th data-priority="0" class="center">
                                <label>
                                    <input type="checkbox" class="ace" />
                                    <span class="lbl"></span>
                                </label>
                            </th>
                            <th data-priority="2" >ID</th>
                            <th data-priority="3" >Name</th>
                            <th data-priority="4" >Email</th>
                            <th data-priority="5" >Phone</th>
                            <th data-priority="6" >Mobile</th>
                            <th data-priority="7" >Suburb</th>
                            <th data-priority="8" >Postcode</th>
                            <th data-priority="1" ></th>
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
        datatable = $('#customertable').dataTable({
            "bProcessing": true,
            "aaData": itemarray,
            "aaSorting": [[ 1, "asc" ]],
            "aLengthMenu": [ 10, 25, 50, 100, 200],
            "aoColumns": [
                { mData:null, sDefaultContent:'<div style="text-align: center"><label><input class="ace dt-select-cb" type="checkbox"><span class="lbl"></span></label><div>', bSortable: false },
                { "mData":"id" },
                { "mData":"name" },
                { "mData":"email" },
                { "mData":"phone" },
                { "mData":"mobile" },
                { "mData":"suburb" },
                { "mData":"postcode" },
                { mData:null, sDefaultContent:'<div class="action-buttons"><a class="green" onclick="WPOS.customers.openCustomerDialog($(this).closest(\'tr\').find(\'td\').eq(1).text());"><i class="icon-pencil bigger-130"></i></a><a class="red" onclick="WPOS.customers.deleteCustomer($(this).closest(\'tr\').find(\'td\').eq(1).text())"><i class="icon-trash bigger-130"></i></a></div>', bSortable: false }
            ],
            "columns": [
                {},
                {type: "numeric"},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {type: "numeric"},
                {type: "currency"},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {}
            ],
            "fnInfoCallback": function( oSettings, iStart, iEnd, iMax, iTotal, sPre ) {
                // Add selected row count to footer
                var selected = this.api().rows('.selected').count();
                return sPre+(selected>0 ? '<br/>'+selected+' row(s) selected':'');
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
        datatable.fnClearTable(false);
        datatable.fnAddData(itemarray, false);
        datatable.api().draw(false);
    }
    function exportCustomers(){

        var filename = "customers-"+WPOS.util.getDateFromTimestamp(new Date());
        filename = filename.replace(" ", "");
        var customers = WPOS.customers.getCustomers();

        var data = {};
        var ids = datatable.api().rows('.selected').data().map(function(row){ return row.id }).join(',').split(',');

        if (ids && ids.length > 0 && ids[0]!='') {
            for (var i = 0; i < ids.length; i++) {
                var id = ids[i];
                if (customers.hasOwnProperty(id))
                    data[id] = customers[id];
            }
        } else {
            data = customers;
        }

        var csv = WPOS.data2CSV(
            ['ID', 'Name', 'Email', 'Phone', 'Mobile', 'Address', 'Suburb', 'Postcode', 'State', 'Country', 'Notes', 'Contacts'],
            ['id', 'name', 'email', 'phone', 'mobile', 'address', 'suburb', 'postcode', 'state', 'country', 'notes', 'contacts'],
            data
        );

        WPOS.initSave(filename, csv);
    }
</script>
<style type="text/css">
    #customertable_processing {
        display: none;
    }
</style>