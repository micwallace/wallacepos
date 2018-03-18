<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1 class="inline">
        Devices
    </h1>
    <button onclick="openDevDialog(0);" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->

        <div class="row">
            <div class="col-xs-12">

                <div class="table-header">
                    Manage POS Devices
                </div>

                    <table id="devtable" class="table table-striped table-bordered table-hover dt-responsive" style="width:100%;">
                        <thead>
                        <tr>
                            <th data-priority="0">ID</th>
                            <th data-priority="2">Name</th>
                            <th data-priority="4">Location</th>
                            <th data-priority="5">Type</th>
                            <th data-priority="3">Status</th>
                            <th data-priority="1"></th>
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
            <button onclick="openLocDialog(0);" id="addbtn" class="btn btn-primary btn-sm pull-right"><i class="icon-pencil align-top bigger-125"></i>Add</button>
        </div><!-- /.page-header -->
        <div class="row" style="margin-top: 10px;">
            <div class="col-xs-12">

                <div class="table-header">
                    Manage POS Locations
                </div>

                    <table id="loctable" class="table table-striped table-bordered table-hover dt-responsive" style="width:100%;">
                        <thead>
                        <tr>
                            <th data-priority="0">ID</th>
                            <th data-priority="2">Name</th>
                            <th data-priority="3">Status</th>
                            <th data-priority="1"></th>
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
<div id="editdevdialog" class="hide">
    <div class="tabbable" style="min-width: 360px; min-height: 310px;">
    <ul class="nav nav-tabs">
        <li class="active">
            <a href="#devicedetails" data-toggle="tab">
                Details
            </a>
        </li>
        <li id="devregtab" class="" onclick="loadDeviceRegistrations();">
            <a href="#devicereg" data-toggle="tab">
                Registrations
            </a>
        </li>
    </ul>
    <div class="tab-content" style="min-height: 320px;">
        <div class="tab-pane active in" id="devicedetails">
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
                <tr>
                    <td style="text-align: right;"><label>Device Type:&nbsp;</label></td>
                    <td><select id="devtype" onchange="showDeviceOptions($(this).val());">
                            <option class="reg_device" value="general_register">General Cash Register</option>
                            <option class="reg_device" value="order_register">Order Register (alpha version)</option>
                            <option class="kitchen_device" value="kitchen_terminal">Kitchen/Bar Terminal (alpha version)</option>
                        </select></td>
                </tr>
                <tr class="order_options">
                    <td style="text-align: right;"><label>Kitchen Delivery:&nbsp;</label></td>
                    <td><select id="devordertype" onchange="showKitchenOptions($(this).val());">
                            <option value="terminal">Kitchen/Bar Terminal</option>
                            <option value="printer">Kitchen/Bar Printer</option>
                        </select></td>
                </tr>
                <tr class="order_options">
                    <td style="text-align: right;"><label>Display Orders:&nbsp;</label></td>
                    <td><input id="devorderdisplay" type="checkbox" /></td>
                </tr>
                <tr class="order_term_options">
                    <td style="text-align: right;"><label>Kitchen Terminal:&nbsp;</label></td>
                    <td><select id="devkitchenid" class="kitchenselect">
                        </select></td>
                </tr>
            </table>
        </div>
        <div class="tab-pane" id="devicereg" style="min-height: 280px;">
            <div style="max-height: 300px; overflow-y: auto;">
                <table class="table table-responsive">
                    <tbody id="devreglist">
                        <tr>
                            <td colspan="2" style="text-align: center;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- page specific plugin scripts; migrated to index.php due to heavy use -->

<!-- inline scripts related to this page -->
<script type="text/javascript">

    function showDeviceOptions(devicetype){
        if (devicetype=='order_register'){
            $('.order_options').show();
            showKitchenOptions($('#devordertype').val());
        } else {
            $('.order_options').hide();
            $('.order_term_options').hide();
        }
    }

    function showKitchenOptions(deliverytype){
        if (deliverytype=='terminal'){
            $('.order_term_options').show();
        } else {
            $('.order_term_options').hide();
        }
    }

    var devtable, loctable, devices, locations;

    $(function() {
        var data = WPOS.sendJsonData("multi", JSON.stringify({"devices/get":"", "locations/get":""}));
        devices = data['devices/get'];
        locations = data['locations/get'];

        var devarray = [];
        var tempitem;
        for (var key in devices){
            tempitem = devices[key];
            if (tempitem['locationid']!=undefined){
                tempitem.locationname = locations[tempitem['locationid']].name;
            } else {
                tempitem.locationname = "None"
            }
            devarray.push(tempitem);
        }
        devtable = $('#devtable').dataTable({
            "bProcessing": true,
            "aaData": devarray,
            "aoColumns": [
                { "mData":"id" },
                { "mData":"name" },
                { "mData":"locationname" },
                { "mData":function(data, type, val){ switch(data.type){case 'kitchen_terminal': return 'Kitchen/Bar Terminal'; case 'general_register': return 'General Register'; case 'order_register': return 'Order Register';} return ''; } },
                { "mData":function(data, type, val){ return '<i class="'+(data.disabled==1?'red icon-arrow-down':'green icon-arrow-up')+'"></i>'; } },
                { "mData":function(data, type, val){ return data.id==0?'':'<div class="action-buttons"><a class="green" onclick="openDevDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                    (data.disabled==1?'<a class="green" onclick="setItemDisabled(0, $(this).closest(\'tr\').find(\'td\').eq(0).text(), false)"><i class="icon-arrow-up bigger-130"></i></a><a class="red" onclick="removeDevItem($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a>':'<a class="red" onclick="setItemDisabled(0, $(this).closest(\'tr\').find(\'td\').eq(0).text(), true)"><i class="icon-arrow-down bigger-130"></i></a>')+'</div>'; }, "bSortable": false }
            ],
            "columns": [
                {type: "numeric"},
                {type: "string"},
                {type: "string"},
                {type: "string"},
                {type: "html"},
                {}
            ]
        });

        var locarray = [];
        for (key in locations){
            locarray.push(locations[key]);
        }
        loctable = $('#loctable').dataTable({
            "bProcessing": true,
            "aaData": locarray,
            "aoColumns": [
                { "mData":"id" },
                { "mData":"name" },
                { "mData":function(data, type, val){ return '<i class="'+(data.disabled==1?'red icon-arrow-down':'green icon-arrow-up')+'"></i>'; } },
                { "mData":function(data, type, val){ return data.id==0?'':'<div class="action-buttons"><a class="green" onclick="openLocDialog($(this).closest(\'tr\').find(\'td\').eq(0).text());"><i class="icon-pencil bigger-130"></i></a>'+
                    (data.disabled==1?'<a class="green" onclick="setItemDisabled(1, $(this).closest(\'tr\').find(\'td\').eq(0).text(), false)"><i class="icon-arrow-up bigger-130"></i></a><a class="red" onclick="removeLocItem($(this).closest(\'tr\').find(\'td\').eq(0).text())"><i class="icon-trash bigger-130"></i></a>':'<a class="red" onclick="setItemDisabled(1, $(this).closest(\'tr\').find(\'td\').eq(0).text(), true)"><i class="icon-arrow-down bigger-130"></i></a>')+'</div>'; }, "bSortable": false }
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
                        saveDevItem();
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
                        saveLocItem();
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
        populateKitchenTerminalSelect();

        // hide loader
        WPOS.util.hideLoader();
    });
    // updating records
    function openLocDialog(id){
        var idfield = $("#locid");
        var namefield = $("#locname");
        if (id && id>0){
            var loc = locations[id];
            idfield.val(loc.id);
            namefield.val(loc.name);
        } else {
            idfield.val(0);
            namefield.val('');
        }
        $("#editlocdialog").dialog("open");
    }
    function saveLocItem(){
        WPOS.util.showLoader();
        var result;
        var location = {};
        location.name = $("#locname").val();
        var id = $("#locid").val();
        if (id==0){
            result = WPOS.sendJsonData("locations/add", JSON.stringify(location));
        } else {
            // updating an item
            location.id = id;
            result = WPOS.sendJsonData("locations/edit", JSON.stringify(location));
        }
        if (result){
            locations[result.id] = result;
            refreshLocTable();
            $("#editlocdialog").dialog("close");
        }
        WPOS.util.hideLoader();
    }
    function removeLocItem(id){
        var answer = confirm("Are you sure you want to delete this location?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("locations/delete", '{"id":'+id+'}')){
                delete locations[id];
                refreshLocTable();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function loadDeviceRegistrations(){
        var id = $('#devid').val();
        var regtable = $("#devreglist");
        if (id==0){
            regtable.html('');
            return;
        }
        regtable.html('<tr><td colspan="2" style="text-align: center;">Loading...</td></tr>');
        WPOS.sendJsonDataAsync('devices/registrations', '{"id":'+id+'}', function(data){
            if (data.length<1){
                regtable.html('<tr><td colspan="2" style="text-align: center;">No Device registrations</td></tr>');
                return;
            }
            // populate registrations
            regtable.html('');
            for (var i in data){
                regtable.append('<tr id="devreg-'+data[i].id+'"><td>UUID: '+data[i].uuid+'<br/>IP Address: '+data[i].ip+'<br/>User-Agent: '+data[i].useragent+'<br/>Date Added: '+data[i].dt+'</td><td><a class="red" onclick="deleteDeviceRegistration('+data[i].id+');"><i class="icon-trash bigger-130"></i></a></td></tr>');
            }
        }, null);
    }
    function deleteDeviceRegistration(id){
        var answer = confirm("Are you sure you want to delete this registration?\nThe device affected will need to be re-registered.");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("devices/registrations/delete", '{"id":'+id+'}')){
                $('#devreg-'+id).remove();
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function openDevDialog(id){
        var idfield = $("#devid");
        var namefield = $("#devname");
        var locidfield = $("#devlocid");
        var typefield = $("#devtype");
        var otypefield = $("#devordertype");
        var odisplyfield = $("#devorderdisplay");
        var kitchenidfield = $("#devkitchenid");
        if (id && id>0){
            var dev = devices[id];
            idfield.val(dev.id);
            namefield.val(dev.name);
            locidfield.val(dev.locationid);
            typefield.val(dev.type);
            otypefield.val(dev.ordertype);
            odisplyfield.prop('checked', (dev.orderdisplay)?true:false);
            kitchenidfield.val(dev.kitchenid);
            var iskitchen = dev.type=="kitchen_terminal";
            $(".reg_device").prop('disabled', iskitchen);
            $(".kitchen_device").prop('disabled', !iskitchen);
        } else {
            idfield.val(0);
            namefield.val('');
            locidfield.val('');
            typefield.val('general_register');
            otypefield.val('terminal');
            odisplyfield.prop('checked', true);
            kitchenidfield.val(0);
        }
        showDeviceOptions(typefield.val());
        if ($("#devregtab").hasClass('active')){
            loadDeviceRegistrations();
        }
        $("#editdevdialog").dialog("open");
    }
    function saveDevItem(){
        WPOS.util.showLoader();
        var result;
        var device = {};
        device.name = $("#devname").val();
        device.locationid = $("#devlocid").val();
        device.type = $("#devtype").val();
        device.ordertype = $("#devordertype").val();
        device.orderdisplay = $("#devorderdisplay").prop('checked')==true;
        device.kitchenid = $("#devkitchenid").val();
        var id = $("#devid").val();
        if (id==0){
            // adding a new item
            result = WPOS.sendJsonData("devices/add", JSON.stringify(device));
        } else {
            // updating an item
            device.id = id;
            result = WPOS.sendJsonData("devices/edit", JSON.stringify(device));
        }
        if (result){
            devices[result.id] = result;
            refreshDevTable();
            populateKitchenTerminalSelect();
            $("#editdevdialog").dialog("close");
        }
        WPOS.util.hideLoader();
    }
    function removeDevItem(id){
        var answer = confirm("Are you sure you want to delete this device?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("devices/delete", '{"id":'+id+'}')){
                delete devices[id];
                refreshDevTable();
                populateKitchenTerminalSelect();
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
            } else { // location
                result = WPOS.sendJsonData("locations/disable", JSON.stringify({id: id, disable: disable}));
            }
            if (result!==false){
                if (type==0){
                    devices[id].disabled = disable;
                    refreshDevTable();
                } else {
                    locations[id].disabled = disable;
                    refreshLocTable();
                }
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

    function refreshDevTable(){
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
        devtable.fnClearTable(false);
        devtable.fnAddData(itemarray, false);
        devtable.api().draw(false);
    }

    function refreshLocTable(){
        var itemarray = [];
        for (var key in locations){
            itemarray.push(locations[key]);
        }
        loctable.fnClearTable(false);
        loctable.fnAddData(itemarray, false);
        loctable.api().draw(false);
        // redraw the dev table, location names have changed
        refreshDevTable();
        // repopulate the select boxes
        populateLocationSelect();
    }

    function populateLocationSelect(){
        var locselect = $(".locselect");
        $(locselect).html('');
        // populate tax records
        for (var key in locations){
            if (key!=0)
                $(locselect).append('<option class="locid-'+locations[key].id+'" value="'+locations[key].id+'">'+locations[key].name+'</option>');
        }
    }

    function populateKitchenTerminalSelect(){
        var kselect = $(".kitchenselect");
        $(kselect).html('');
        // populate tax records
        for (var i in devices){
            if (devices[i].type=='kitchen_terminal')
                $(kselect).append('<option class="devid-'+devices[i].id+'" value="'+devices[i].id+'">'+devices[i].name+'</option>');
        }
    }
</script>
<style type="text/css">
    #devtable_processing, #loctable_processing {
        display: none;
    }
</style>