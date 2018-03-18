<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Realtime
        <small>
            <i class="icon-double-angle-right"></i>
            View Sales as they happen
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
<div class="col-xs-12">
<div class="row">
    <div class="col-sm-5">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-shopping-cart blue"></i>
                    Latest Transactions
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main no-padding">
                    <table class="table table-bordered table-striped">
                        <thead class="thin-border-bottom">
                        <tr>
                            <th>
                                <i class="icon-caret-right blue"></i>
                                Time
                            </th>

                            <th>
                                <i class="icon-caret-right blue"></i>
                                Status
                            </th>

                            <th>
                                <i class="icon-caret-right blue"></i>
                                Device / Location
                            </th>

                            <th class="hidden-480 hidden-320">
                                <i class="icon-caret-right blue"></i>
                                # Items
                            </th>

                            <th class="hidden-320">
                                <i class="icon-caret-right blue"></i>
                                Total
                            </th>
                        </tr>
                        </thead>

                        <tbody id="recentsalestable">
                        <tr id="nosalesrow">
                            <td colspan="5" style="text-align: center;"><strong>No sales data for today</strong></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /widget-main -->
            </div>
            <!-- /widget-body -->
        </div>
        <!-- /widget-box -->
    </div>

    <div class="vspace-sm"></div>

    <div class="col-sm-7">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-desktop green"></i>
                    Online Devices & Messaging
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main no-padding">
                    <div class="col-sm-6" style="margin: 0;">

                        <table style="width: 100%" class="table">
                            <thead class="thin-border-bottom">
                            <tr>
                                <th>
                                    <i class="icon-caret-right blue"></i>
                                    <h4 class="lighter blue" style="display: inline-block;">Message Devices</h4>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <select id="msgdevice">
                                        <option value="all">All</option>
                                    </select><br/><br/>
                                    <textarea rows="6" style="width: 100%;" id="msgtext"></textarea><br/><br/>

                                    <div style="text-align: center;">
                                        <button class="btn btn-primary btn-sm" onclick="sendMessage();">Send</button>
                                        <button class="btn btn-danger btn-sm" onclick="sendReset();">Reset Terminal</button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-sm-6" style=" vertical-align: top; margin: 0;">
                        <table style="width: 100%" class="table table-striped">
                            <thead class="thin-border-bottom">
                            <tr>
                                <th>
                                    <i class="icon-caret-right blue"></i>
                                    <h4 class="lighter blue" style="display: inline-block;">Online Devices</h4>
                                </th>
                            </tr>
                            </thead>
                            <tbody id="onlinedevices">
                            <tr><td style="text-align: center;">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /widget-main -->
            </div>
            <!-- /widget-body -->
        </div>
        <!-- /widget-box -->
    </div>

</div>

<div class="vspace-sm"></div>
<div class="hr hr32 hr-dotted hidden-480 hidden-320 hidden-xs"></div>

<div class="row">
    <div class="space-6"></div>
    <div class="col-sm-5">
    <div class="widget-box transparent">
        <div class="widget-header widget-header-flat">
            <h4 class="lighter">
                <i class="icon-dollar"></i>
                Today's Takings
            </h4>
        </div>
        <div class="widget-body" style="padding-top: 10px; text-align: center;">
            <div class="infobox infobox-green infobox-sales">
                <div class="infobox-icon">
                    <i class="icon-shopping-cart"></i>
                </div>

                <div class="infobox-data">
                    <span id="rtsalenum" class="infobox-data-number">-</span>
                    <div class="infobox-content">Sales</div>
                </div>
                <div id="rtsaletotal" class="stat stat-success">-</div>
            </div>

            <div class="infobox infobox-orange infobox-refunds">
                <div class="infobox-icon">
                    <i class="icon-backward"></i>
                </div>

                <div class="infobox-data">
                    <span id="rtrefundnum" class="infobox-data-number">-</span>
                    <div class="infobox-content">Refunds</div>
                </div>

                <div id="rtrefundtotal" class="stat stat-important">-</div>
            </div><br/>

            <div class="infobox infobox-red infobox-voids">
                <div class="infobox-icon">
                    <i class="icon-ban-circle"></i>
                </div>

                <div class="infobox-data">
                    <span id="rtvoidnum" class="infobox-data-number">-</span>
                    <div class="infobox-content">Voids</div>
                </div>
                <div id="rtvoidtotal" class="stat stat-important">-</div>
            </div>

            <div class="infobox infobox-blue2 infobox-takings">
                <div class="infobox-icon">
                    <i class="icon-dollar"></i>
                </div>

                <div class="infobox-data">
                    <span id="rttakings" class="infobox-data-number">-</span>
                    <div class="infobox-content">Revenue</div>
                </div>
            </div><br/>

            <div class="infobox infobox-orange infobox-cost">
                <div class="infobox-icon">
                    <i class="icon-dollar"></i>
                </div>

                <div class="infobox-data">
                    <span id="rtcost" class="infobox-data-number">-</span>
                    <div class="infobox-content">Cost</div>
                </div>
            </div>

            <div class="infobox infobox-green infobox-profit">
                <div class="infobox-icon">
                    <i class="icon-dollar"></i>
                </div>

                <div class="infobox-data">
                    <span id="rtprofit" class="infobox-data-number">-</span>
                    <div class="infobox-content">Profit</div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="vspace-sm"></div>

    <div class="col-sm-7">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-signal"></i>
                    Sale Stats
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main padding-4">
                    <div id="realtime-chart"></div>
                </div>
                <!-- /widget-main -->
            </div>
            <!-- /widget-body -->
        </div>
        <!-- /widget-box -->
    </div>
    <!-- /span -->


</div><!-- /row -->
</div>
</div>
<div class="hr hr32 hr-dotted"></div>

<script type="text/javascript">
var onlinedev = {};

function sendMessage() {
    if (Object.keys(onlinedev).length <= 1) {
        alert("There are no devices online to message");
        return;
    }
    // show loader
    WPOS.util.showLoader();

    var devid = $("#msgdevice option:selected").val();
    var msg = $("#msgtext").val();
    if (msg.length == 0) {
        alert("Please enter a message to send.");
        return;
    }
    var result;
    if (devid == "all") {
        result = WPOS.sendJsonData("message/send", JSON.stringify({message: msg, device: null}));
    } else {
        result = WPOS.sendJsonData("message/send", JSON.stringify({message: msg, device: devid}));
    }
    if (result!==false){
        $("#msgtext").val('');
    }
    // hide loader
    WPOS.util.hideLoader();
}

function sendReset() {
    if (Object.keys(onlinedev).length <= 1) {
        alert("There are no devices online to reset");
        return;
    }
    var answer = confirm("Are you sure you want to reset the selected devices?");
    if (!answer){
        return;
    }
    // show loader
    WPOS.util.showLoader();

    var devid = $("#msgdevice option:selected").val();
    var result;
    if (devid == "all") {
        result = WPOS.sendJsonData("device/reset", JSON.stringify({device: null}));
    } else {
        result = WPOS.sendJsonData("device/reset", JSON.stringify({device: devid}));
    }
    if (result!==false){
        alert("The reset request has been sent to the selected devices.");
    }
    // hide loader
    WPOS.util.hideLoader();
}

function populateOnlineDevices(devices) {
    // get list of active devices from the node feed server
    var devtable = $("#onlinedevices");
    var devselect = $("#msgdevice");
    devtable.html('');
    devselect.html('');

    devselect.append("<option value='all' selected>All</option>");

    if (Object.keys(devices).length > 1) { // devices will always have the admin dash
        for (var i in devices) {
            if (i != 0) { // do not include admin dash
                var devname, locname;
                if (WPOS.devices.hasOwnProperty(i)){
                    devname = WPOS.devices[i].name;
                    locname = WPOS.devices[i].locationname;
                } else {
                    devname = "Unknown";
                    locname = "Unknown";
                }
                devtable.append("<tr><td><i class='icon-lightbulb green icon-large'></i>&nbsp;&nbsp;" + devices[i].username + " / " + devname + " / " + locname + "</td></tr>");
                devselect.append("<option value='" + i + "'>" + devices[i].username + " / " + devname + " / " + locname + "</option>");
            }
        }
    } else {
        devtable.append("<tr><td>There are no online devices.</td></tr>");
    }
}

function processIncomingSale(saleobj) {
    updateSalesTable(saleobj);
    // if sale is an old offline sale, continue no further
    if (isSaleToday(saleobj)) {
        if (!saleobj.hasOwnProperty("voiddata") && !saleobj.hasOwnProperty("refunddata")) {
            // sale does not have any void/refund so no need to check anything else
            totals.salenum = parseInt(totals.salenum) + 1;
            totals.saletotal = parseFloat(totals.saletotal) + parseFloat(saleobj.total);
            totals.cost = parseFloat(totals.cost) + parseFloat(saleobj.cost);
        } else {
            if (saleobj.hasOwnProperty("voiddata")) { // If sale had void data, add it the totals
                totals.voidnum = parseInt(totals.voidnum) + 1;
                totals.voidtotal = parseFloat(totals.voidtotal) + parseFloat(saleobj.total);
                if (sales.hasOwnProperty(saleobj.ref)){ // If the sale was processed today, remove from the sales total
                    totals.salenum -= 1;
                    totals.saletotal = parseFloat(totals.saletotal) - parseFloat(saleobj.total);
                    totals.cost = parseFloat(totals.cost) - parseFloat(saleobj.cost);
                }
            } else {
                if (!sales.hasOwnProperty(saleobj.ref)){ // If the sale has not been processed (ie, refund only)
                    totals.salenum = parseInt(totals.salenum) + 1;
                    totals.saletotal = parseFloat(totals.saletotal) +  parseFloat(saleobj.total);
                }
            }

            if (saleobj.hasOwnProperty("refunddata")) {
                if (sales.hasOwnProperty(saleobj.ref)){ // if the sale was processed today, remove old refund totals before adding the new amount
                    var oldref = sales[saleobj.ref].refunddata;
                    for (var r in oldref){
                        totals.refundnum -= 1;
                        totals.refundtotal -= parseFloat(oldref[r].amount);
                    }
                }
                var newref = saleobj.refunddata;
                for (r in newref){
                    totals.refundnum = parseInt(totals.refundnum) + 1;
                    totals.refundtotal = parseFloat(totals.refundtotal) + parseFloat(newref[r].amount);
                }
            }
        }
        // update total takings and populate the stats widget
        totals.totaltakings = parseFloat(totals.saletotal) - parseFloat(totals.refundtotal);
        totals.profit = totals.totaltakings - parseFloat(totals.cost);
        populateTodayStats();
        // Update sales chart
        reloadGraph();
        // update/add object into sale table
        sales[saleobj.ref] = saleobj;
    }
}

function isSaleToday(saleobj){
    if (saleobj.processdt>stoday){
        return true
    }
    if (saleobj.hasOwnProperty("voiddata")){
        if (saleobj.voiddata.processdt>stoday){
            return true;
        }
    }
    if (saleobj.hasOwnProperty("refunddata")){
        for (var i in saleobj.refunddata){
            if (saleobj.refunddata[i].processdt>stoday){
                return true;
            }
        }
    }
    return false;
}

function getSaleStatus(saleobj){
    if (saleobj.hasOwnProperty("voiddata") || saleobj.hasOwnProperty("refunddata")){
        if (saleobj.hasOwnProperty("voiddata")){
            return 2;
        }
        return 3;
    }
    return 1;
}

function getStatusLabel(status){
    var stathtml;
    switch(status){
        case 1:
            stathtml='<span class="label label-sm label-success arrowed">Complete</span>';
            break;
        case 2:
            stathtml='<span class="label label-sm label-danger arrowed">Void</span>';
            break;
        case 3:
            stathtml='<span class="label label-sm label-warning arrowed">Refunded</span>';
            break;
    }
    return stathtml;
}

function getTotalItems(saleobj){
    var totalitems = 0;
    for (var i in saleobj.items) {
        totalitems += parseInt(saleobj.items[i].qty);
    }
    return totalitems;
}

function updateSalesTable(saleobj) {
    // preprend insert the new row with zero height
    $("#nosalesrow").remove();
    // TODO: If refund get the amount refunded
    $("#recentsalestable").prepend('<tr id="sr-' + saleobj.ref + '"><td>' + WPOS.util.getDateFromTimestamp(saleobj.processdt) + '</td><td>' + getStatusLabel(getSaleStatus(saleobj)) + '</td><td>' + WPOS.devices[saleobj.devid].name + '/' + WPOS.locations[saleobj.locid].name + '</td><td>' + getTotalItems(saleobj) + '</td><td>' + WPOS.util.currencyFormat(saleobj.total) + '</td></tr>');
    // animate does not work for table rows so as a workaround we temporarily wrap in a div
    $("#sr" + saleobj.ref).find('td').wrapInner('<div style="display: block;" />').parent().find('td > div')
        .slideUp(1000, function(){
            $(this).parent().parent().remove();
        });
    if ($("#recentsalestable").children('tr').length>8){ // only take the last sale away if table has more than 8
        $("#recentsalestable tr:last").find('td').wrapInner('<div style="display: block;" />').parent().find('td > div')
        .slideDown(1000, function(){
            $(this).parent().parent().remove();
        });
    }
}

function insertIntoSaleTable(saleobj) {
    $("#recentsalestable").append('<tr><td>' + WPOS.util.getDateFromTimestamp(saleobj.processdt) + '</td><td>' + getStatusLabel(getSaleStatus(saleobj)) + '</td><td>' + WPOS.devices[saleobj.devid].name + ' / ' + WPOS.locations[saleobj.locid].name + '</td><td class="hidden-480 hidden-320">' + getTotalItems(saleobj) + '</td><td class="hidden-320">' + WPOS.util.currencyFormat(saleobj.total) + '</td></tr>');
}

var stime;
var etime;
var stoday;
var totals;
var sales;
var graph;

function loadTodaysSales() {
    if (!sales)
        return false;
    if (Object.keys(sales).length > 0) {
        // sort by time
        var sort = [];
        for (var i in sales) {
            sort.push([i, sales[i].processdt]);
        }
        sort.sort(function (a, b) {
            return b[1] - a[1];
        });
        // remove no data row
        $("#recentsalestable").html('');
        // put last 6 sales into the table
        for (i = 0; (i < 6 && i < sort.length); i++) {
            insertIntoSaleTable(sales[sort[i][0]]);
        }
    }
    return true;
}

function populateTodayStats() {
    if (!totals)
        return false;
    // populate the fields
    $("#rtsalenum").text(totals.salenum);
    $("#rtsaletotal").text(WPOS.util.currencyFormat(totals.saletotal));
    $("#rtrefundnum").text(totals.refundnum);
    $("#rtrefundtotal").text(WPOS.util.currencyFormat(totals.refundtotal));
    $("#rtvoidnum").text(totals.voidnum);
    $("#rtvoidtotal").text(WPOS.util.currencyFormat(totals.voidtotal));
    $("#rttakings").text(WPOS.util.currencyFormat(totals.totaltakings, true));
    $("#rtcost").text(WPOS.util.currencyFormat(totals.cost, true));
    $("#rtprofit").text(WPOS.util.currencyFormat(totals.profit, true));
    // Set onclicks
    $(".infobox-sales").on('click', function(){ WPOS.transactions.openTransactionList(totals.salerefs); });
    $(".infobox-refunds").on('click', function(){ WPOS.transactions.openTransactionList(totals.refundrefs); });
    $(".infobox-voids").on('click', function(){ WPOS.transactions.openTransactionList(totals.voidrefs); });
    $(".infobox-takings").on('click', function(){ WPOS.transactions.openTransactionList(totals.refs); });
}

// Graph functions
function reloadGraph(){
    etime = new Date();
    etime = etime.getTime(); // Update the time
    stime = etime - 36000000;
    // fetch the data
    var data = WPOS.sendJsonData("graph/general", JSON.stringify({"stime": stime, "etime": etime, "interval": 1800000})); // interval half an hour
    loadGraph(data); // reload the graph
}

function loadGraph(data) {
    var sales = [], refunds = [], takings = [], salerefs = [], refundrefs = [], takingrefs = [];
    if (data==false)
    return false;
    // generate graph data
    var temptime;
    for (var i in data) {
        temptime = i;
        salerefs.push(data[i].salerefs);
        sales.push([ temptime, data[i].saletotal]);
        refundrefs.push(data[i].refundrefs);
        refunds.push([ temptime, data[i].refundtotal]);
        takingrefs.push(data[i].refs);
        takings.push([ temptime, data[i].totaltakings]);
    }
    var gdata = [
        { label: "Sales", refs:salerefs, data: sales, color: "#9ABC32" },
        { label: "Refunds", refs:refundrefs, data: refunds, color: "#EDC240" },
        { label: "Takings", refs:takingrefs, data: takings, color: "#3983C2" }
    ];
    drawChart(gdata);
    return true;
}

function drawChart(data) {
    $.plot("#realtime-chart", data, {
        hoverable: true,
        shadowSize: 0,
        series: {
            lines: { show: true },
            points: { show: true }
        },
        xaxis: {
            mode: "time",
            minTickSize: [30, "minute"],
            timeformat: "%h:%M%p",
            timezone: "browser"
        },
        yaxis: {
            ticks: 10
        },
        grid: {
            backgroundColor: { colors: [ "#fff", "#fff" ] },
            borderWidth: 1,
            borderColor: '#555',
            hoverable: true,
            clickable: true
        }
    });
}

$(function () {
    // set time
    etime = new Date();
    stoday = new Date();
    stoday.setHours(0);
    stoday.setMinutes(0);
    stoday.setSeconds(0);
    stoday = stoday.getTime();
    etime = etime.getTime();
    stime = etime - 36000000;
    // init graph
    var $tooltip = $("<div class='tooltip top in'><div class='tooltip-inner'></div></div>").hide().appendTo('body');
    var previousPoint = null;
    var tooltip = function (event, pos, item) {
        if (item) {
            if (previousPoint != item.seriesIndex) {
                previousPoint = item.seriesIndex;
                if (item.series['percent'] != null) {
                    var tip = item.series['label'] + " : " + item.series['percent'].toFixed(2) + '% ($' + item.series['data'][0][1] + ')';
                } else {
                    var tip = item.series['label'] + " : "+ WPOS.util.currencyFormat(item.datapoint[1]);
                }
                $tooltip.show().children(0).text(tip);
            }
            var left, right;
            if ((pos.pageX + 10 + $tooltip.width())>window.innerWidth){
                left = ""; right = 0;
            } else {
                right = ""; left = pos.pageX + 10;
            }
            $tooltip.css({top:pos.pageY + 10, left: left, right: right});
        } else {
            $tooltip.hide();
            previousPoint = null;
        }
    };
    var clickgraph = function(event, pos, item){
        if (item==null) return;
        WPOS.transactions.openTransactionList(item.series['refs'][item.dataIndex]);
    };
    var chart = $('#realtime-chart');
    chart.on('plothover', tooltip);
    chart.on('plotclick', clickgraph);
    chart.css({'width': '100%', 'height': '220px'});
    // load data
    WPOS.startSocket();
    var data = WPOS.sendJsonData("multi", JSON.stringify({"sales/get":{stime: stoday, etime: etime}, "stats/general":{"stime":stoday, "etime":etime}, "graph/general":{"stime": stime, "etime": etime, "interval": 1800000}}));
    if (data!==false) {
        sales = data['sales/get'];
        totals = data['stats/general'];
        loadTodaysSales();
        populateTodayStats();
        loadGraph(data['graph/general']);
    }
    // hide loader
    WPOS.util.hideLoader();
})

</script>