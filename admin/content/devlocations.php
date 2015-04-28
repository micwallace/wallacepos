<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 class="inline">
        Devices
    </h1>
    <button onclick="$('#adddevdialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    Manage POS Devices
                </div>

                    <table id="devtable" class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
            </div>
        </div>
        <div class="space-6"></div>
        <div class="page-header">
            <h1 class="inline">
                Locations
            </h1>
            <button onclick="$('#addlocdialog').dialog('open');" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
        </div><!-- /.page-header -->
        <div class="row" style="margin-top: 10px;">
            <div class="col-xs-12">

                <div class="table-header">
                    Manage POS Locations
                </div>

                    <table id="loctable" class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
            </div>
        </div>
    </div><!-- PAGE CONTENT ENDS -->
</div><!-- /.row -->
<div id="editlocdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="locname" type="text"/>
                <input id="locid" type="hidden"/></td>
        </tr>
    </table>
</div>
<div id="addlocdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="newlocname" type="text"/><br/></td>
        </tr>
    </table>
</div>
<div id="editdevdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="devname" type="text"/>
                <input id="devid" type="hidden"/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Location:&nbsp;</label></td>
            <td><select id="devlocid" class="locselect">
                </select></td>
        </tr>
    </table>
</div>
<div id="adddevdialog" class="hide">
    <table>
        <tr>
            <td style="text-align: right;"><label>Name:&nbsp;</label></td>
            <td><input id="newdevname" type="text"/><br/></td>
        </tr>
        <tr>
            <td style="text-align: right;"><label>Location:&nbsp;</label></td>
            <td><select id="newdevlocid" class="locselect">
                </select></td>
        </tr>
    </table>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">

    var devtable, loctable, devices, locations;

    $(function() {
        var data = WPOS.sendJsonData("multi", JSON.stringify({"devices/get":"", "locations/get":""}));
        devices = data['devices/get'];
        locations = data['locations/get'];

        var devarray = [];
        var tempitem = {};
        for (var key in devices){
            tempitem = devices[key];
            if (tempitem['locationid']!=undefined){
                tempitem.locationname = locations[tempitem['locationid']].name;
            } else {
                tempitem.locationname = "None"
            }
            devarray.push(tempitem);
        }
        devtable = $('#devtable').dataTable(
            { "bProcessing": true,
                "aaData": devarray,
                "aoColumns": [
                    { "mData":"id" }, { "mData":"name" }, { "mData":"locationname" },
                    { "mData":function(data, type, val){ return '<i class="'+(data.disabled==1?'red icon-arrow-down':'green icon-arrow-up')+'"></i>'; } },
                    { "mData":function(data, type, val){ return data.id==0?'':'<div class="action-buttons"><a class="green" onclick="openEditDevDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                        (data.disabled==1?'<a class="green" onclick="setItemDisabled(0, $(this).closest(\'tr\').find(\'td\').eq(0).text(), false)"><i class="icon-arrow-up bigger-130"></i></a><a class="red" onclick="removeDevItem($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a>':'<a class="red" onclick="setItemDisabled(0, $(this).closest(\'tr\').find(\'td\').eq(0).text(), true)"><i class="icon-arrow-down bigger-130"></i></a>')+'</div>'; }, "bSortable": false }
                ] } );
        // insert table wrapper
        $(".dataTables_wrapper table").wrap("<div class='table_wrapper'></div>");

        var locarray = [];
        for (key in locations){
            locarray.push(locations[key]);
        }
        loctable = $('#loctable').dataTable(
            { "bProcessing": true,
                "aaData": locarray,
                "aoColumns": [
                    { "mData":"id" }, { "mData":"name" },
                    { "mData":function(data, type, val){ return '<i class="'+(data.disabled==1?'red icon-arrow-down':'green icon-arrow-up')+'"></i>'; } },
                    { "mData":function(data, type, val){ return data.id==0?'':'<div class="action-buttons"><a class="green" onclick="openEditLocDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                        (data.disabled==1?'<a class="green" onclick="setItemDisabled(1, $(this).closest(\'tr\').find(\'td\').eq(0).text(), false)"><i class="icon-arrow-up bigger-130"></i></a><a class="red" onclick="removeLocItem($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a>':'<a class="red" onclick="setItemDisabled(1, $(this).closest(\'tr\').find(\'td\').eq(0).text(), true)"><i class="icon-arrow-down bigger-130"></i></a>')+'</div>'; }, "bSortable": false }
                ] } );

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
        $( "#adddevdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Add Device",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Save",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveDevItem(true);
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
        $( "#editdevdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Device",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-save bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveDevItem(false);
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
        $( "#addlocdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Add Location",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-trash bigger-110'></i>&nbsp; Save",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveLocItem(true);
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
        $( "#editlocdialog" ).removeClass('hide').dialog({
            resizable: false,
            width: 'auto',
            modal: true,
            autoOpen: false,
            title: "Edit Location",
            title_html: true,
            buttons: [
                {
                    html: "<i class='icon-trash bigger-110'></i>&nbsp; Update",
                    "class" : "btn btn-success btn-xs",
                    click: function() {
                        saveLocItem(false);
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
        // populate select box records
        populateLocationSelect();

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function openEditLocDialog(id){
        var loc = locations[id];
        $("#locid").val(loc.id);
        $("#locname").val(loc.name);
        $("#editlocdialog").dialog("open");
    }
    function saveLocItem(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var location = {};
        if (isnewitem){
            // adding a new item
            location.locname = $("#newlocname").val();
            if (WPOS.sendJsonData("locations/add", JSON.stringify(location))){
                reloadLocTable();
                $("#addlocdialog").dialog("close");
            }
        } else {
            // updating an item
            location.locid = $("#locid").val();
            location.locname = $("#locname").val();
            if (WPOS.sendJsonData("locations/edit", JSON.stringify(location))){
                reloadLocTable();
                $("#editlocdialog").dialog("close");
            }
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function removeLocItem(id){
        var answer = confirm("Are you sure you want to delete this location?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("locations/delete", '{"locid":'+id+'}')){
                reloadLocTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function openEditDevDialog(id){
        var dev = devices[id];
        $("#devid").val(dev.id);
        $("#devname").val(dev.name);
        $("#devlocid").val(dev.locationid);
        $("#editdevdialog").dialog("open");
    }
    function saveDevItem(isnewitem){
        // show loader
        WPOS.util.showLoader();
        var device = {};
        if (isnewitem){
            // adding a new item
            device.devname = $("#newdevname").val();
            device.locid = $("#newdevlocid").val();
            if (WPOS.sendJsonData("devices/add", JSON.stringify(device))){
                reloadDevTable();
                $("#adddevdialog").dialog("close");
            }
        } else {
            // updating an item
            device.devid = $("#devid").val();
            device.devname = $("#devname").val();
            device.locid = $("#devlocid").val();
            if (WPOS.sendJsonData("devices/edit", JSON.stringify(device))){
                reloadDevTable();
                $("#editdevdialog").dialog("close");
            }
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function removeDevItem(id){
        var answer = confirm("Are you sure you want to delete this device?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("devices/delete", '{"devid":'+id+'}')){
                reloadDevTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function setItemDisabled(type, id, disable){
        var answer = confirm("Are you sure you want to "+(disable?"disable":"enable")+" this item.");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            var result;
            if (type===0){ // device
                result = WPOS.sendJsonData("devices/disable", JSON.stringify({id: id, disable: disable}));
                if (result!==false){
                    reloadDevTable();
                }
            } else { // location
                result = WPOS.sendJsonData("locations/disable", JSON.stringify({id: id, disable: disable}));
                if (result!==false){
                    reloadLocTable();
                }
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function reloadDevTable(){
        devices = WPOS.getJsonData("devices/get");
        reDrawDevTable();
    }

    function reDrawDevTable (){
        var itemarray = [];
        var tempitem;
        for (var key in devices){
            tempitem = devices[key];
            if (tempitem['locationid']!=undefined){
                tempitem.locationname = locations[tempitem['locationid']].name;
            } else {
                tempitem.locationname = "None"
            }
            itemarray.push(devices[key]);
        }
        devtable.fnClearTable();
        devtable.fnAddData(itemarray);
    }

    function reloadLocTable(){
        locations = WPOS.getJsonData("locations/get");
        var itemarray = [];
        for (var key in locations){
            itemarray.push(locations[key]);
        }
        loctable.fnClearTable();
        loctable.fnAddData(itemarray);
        // redraw the dev table, location names have changed
        reDrawDevTable();
        // repopulate the select boxes
        populateLocationSelect();
    }

    function populateLocationSelect(){
        var locselect = $(".locselect");
        $(locselect).html('');
        // populate tax records
        for (var key in locations){
            $(locselect).append('<option class="locid-'+locations[key].id+'" value="'+locations[key].id+'">'+locations[key].name+'</option>');
        }
    }
</script>
<style type="text/css">
    #devtable_processing, #loctable_processing {
        display: none;
    }
</style>