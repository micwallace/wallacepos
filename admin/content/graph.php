<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        <i class="icon-signal"></i>
        Graph
        <small>
            <i class="icon-double-angle-right"></i>
            plot your sale stats
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-md-12" style="width: 100%;">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">

                <div class="widget-toolbar no-border">
                    <label for="graphinterval">Interval:</label>
                    <select id="graphinterval" onchange="setGraphInterval();" style="margin-right: 5px;">
                        <option value="86400000" selected="selected">Day</option>
                        <option value="604800000">Week</option>
                        <option value="1209600000">Fortnight</option>
                        <option value="2629743833">Month</option>
                        <option value="7889231500">3 Months</option>
                        <option value="31556926000">Year</option>
                    </select>
                    <label>Range: <input type="text" style="width: 85px;" id="graphstime" onclick="$(this).blur();" /></label>
                    <label>to <input type="text" style="width: 85px;" id="graphetime" onclick="$(this).blur();" /></label>
                </div>
            </div>

            <div class="widget-body">
                <div class="widget-main padding-4">
                    <div id="plot-chart"></div>
                </div>
                <!-- /widget-main -->
            </div>
            <!-- /widget-body -->
        </div>
        <!-- /widget-box -->
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
        <div class="widget-header widget-header-flat">
            <h4 class="lighter">
                <i class="icon-signal"></i>
                Add data sets:
            </h4>
        </div>
        <div class="widget-body" style="text-align: center; padding-top: 10px;">
            <select id="datasetselect">
                <option value="method">Payment Methods</option>
                <option value="device">Device Takings</option>
                <option value="location">Location Takings</option>
            </select>
            <button onclick="userLoadDataSet();" class="btn btn-sm btn-primary">Add</button>
        </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
        <div class="widget-header widget-header-flat">
            <h4 class="lighter">
                <i class="icon-signal"></i>
                Data Sets:
            </h4>
        </div>
        <div class="widget-body">

            <ol id="datasets" class="ddlist" style="margin-left: 0; padding-left: 0;">
                <li class="dd-item">
                    <div class="dd2-content">Sales Total
                        <button style="float: right; margin-top: -3px;" onclick="removeUIData('sales', $(this));"
                                class="btn btn-xs btn-danger">Remove
                        </button>
                    </div>
                </li>
                <li class="dd-item">
                    <div class="dd2-content">Refund Total
                        <button style="float: right; margin-top: -3px;" onclick="removeUIData('refunds', $(this));"
                                class="btn btn-xs btn-danger">Remove
                        </button>
                    </div>
                </li>
                <li class="dd-item">
                    <div class="dd2-content">Revenue
                        <button style="float: right; margin-top: -3px;" onclick="removeUIData('takings', $(this));"
                                class="btn btn-xs btn-danger">Remove
                        </button>
                    </div>
                </li>
                <li class="dd-item">
                    <div class="dd2-content">Cost
                        <button style="float: right; margin-top: -3px;" onclick="removeUIData('cost', $(this));"
                                class="btn btn-xs btn-danger">Remove
                        </button>
                    </div>
                </li>
                <li class="dd-item">
                    <div class="dd2-content">Profit
                        <button style="float: right; margin-top: -3px;" onclick="removeUIData('profit', $(this));"
                                class="btn btn-xs btn-danger">Remove
                        </button>
                    </div>
                </li>
            </ol>
        </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var etime;
    var stime;
    var data;
    var dataobj;
    // Graph functions
    function loadDefaultData() {
        var sales = [], refunds = [], takings = [], cost = [], profit = [], salerefs = [], refundrefs = [], takingrefs = [];
        // get range
        var jdata = getData('graph/general');
        var tempdate;
        // create the data object
        for (var i in jdata) {
            tempdate = new Date();
            tempdate.setTime(i);
            tempdate.setHours(0);
            tempdate.setMinutes(0);
            tempdate.setSeconds(0);
            tempdate = tempdate.getTime();
            salerefs.push(jdata[i].salerefs);
            sales.push([ tempdate, jdata[i].saletotal]);
            refundrefs.push(jdata[i].refundrefs);
            refunds.push([ tempdate, jdata[i].refundtotal]);
            takingrefs.push(jdata[i].refs);
            takings.push([ tempdate, jdata[i].totaltakings]);
            cost.push([ tempdate, jdata[i].cost]);
            profit.push([ tempdate, jdata[i].profit]);
        }
        dataobj = {profit:{ label: "Profit", refs: takingrefs, data: profit, color: "#29AB87" }, cost:{ label: "Cost", refs: takingrefs, data: cost, color: "#EA3C53" }, "sales": { label: "Sales", refs: salerefs, data: sales, color: "#9ABC32" }, "refunds": { label: "Refunds", refs: refundrefs, data: refunds, color: "#EDC240" }, "takings": { label: "Takings", refs: takingrefs, data: takings, color: "#3983C2" }};
        drawChart();
    }

    function getData(action){
        // fetch the data
        return WPOS.sendJsonData(action, JSON.stringify({"stime": stime, "etime": etime, "interval": $("#graphinterval").val()}));
    }

    function userLoadDataSet(){
        var action, label;
        switch ($("#datasetselect").val()){
            case "method":
                action = "graph/takings"; label = " Takings";
                break;
            case "device":
                action = "graph/devices"; label = " Takings";
                break;
            case "location":
                action = "graph/locations"; label = " Takings";
                break;
        }
        // hide loader
        WPOS.util.showLoader();

        loadDataSet(action, label, "balance");
        // hide loader
        WPOS.util.hideLoader();
    }

    function loadDataSet(action, label, property){
        var dataseries = {};

        // get range
        var jdata = getData(action);
        var tempdate, tempx;
        var labels = [], refs = [];
        // loop through each time value
        for (var x in jdata) {
            tempdate = new Date();
            tempdate.setTime(x);
            tempdate.setHours(0);
            tempdate.setMinutes(0);
            tempdate.setSeconds(0); // set the time to 0 so the graph grid aligns
            tempdate = tempdate.getTime();
            // loop through each data series and add the requested property as y value
            tempx = jdata[x];
            for (var series in tempx){
                // check for name and add to the labels index
                if (!labels.hasOwnProperty(series)){
                    if (tempx[series].hasOwnProperty('name')){
                        labels[series] = tempx[series].name;
                    }
                }
                if (!dataseries.hasOwnProperty(series)){
                   dataseries[series] = [];
                   refs[series] = [];
                }
                refs[series].push(tempx[series]['refs']);
                dataseries[series].push([ tempdate, tempx[series][property] ]);
            }
        }
        // loop through dataseries and add labels, null values & add to UI List
        for (var i in dataseries){
            var serlabel = (labels.hasOwnProperty(i)?labels[i]:i);
            addUIRow(serlabel, serlabel+" "+label);
            dataobj[serlabel] = {label:serlabel+" "+label, refs: refs[i], data: dataseries[i]};
        }
        // replot graph
        drawChart();
    }

    function setGraphInterval(){
        var interval = $("#graphinterval").val();
        // we want at least 4 plots on the graph and no more than 10 when changing the graph
        var timediff = etime - stime;
        if ((timediff < (interval * 4)) || (timediff > interval * 10)){
            stime = etime - interval * (interval==86400000 ? 7 : 4)+1;
            var sdate = new Date(stime);
            //sdate.setHours(0); sdate.setMinutes(0); sdate.setSeconds(0);
            $("#graphstime").datepicker('setDate', sdate);
        }
        setGraphRange();
    }

    function setGraphRange() {
        // show loader
        WPOS.util.showLoader();
        loadDefaultData();
        // clear data set list and readd the default for now
        // TODO: Reinitilize/reload the current datasets when changing range
        $("#datasets").html('');
        addUIRow('sales', "Sales Total");
        addUIRow('refunds', "Refunds Total");
        addUIRow('takings', "Revenue");
        addUIRow('cost', "Cost");
        addUIRow('profit', "Profit");
        // hide loader
        WPOS.util.hideLoader();
    }

    function drawChart() {
        // generate new data array from object
        data = [];
        for (var i in dataobj) {
            data.push(dataobj[i]);
        }
        // replot the graph
        $.plot("#plot-chart", data, {
            hoverable: true,
            shadowSize: 0,
            series: {
                lines: { show: true },
                points: { show: true }
            },
            xaxis: {
                mode: "time",
                minTickSize: [1, "day"],
                timeformat: "%d/%m",
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

    function addUIRow(key, label){
        key = key.replace("'", "\\'");
        $("#datasets").append('<li class="dd-item">' +
            '<div class="dd2-content">' + label +
            '<button style="float: right; margin-top: -3px;" onclick="removeUIData(\''+key+'\', $(this));" class="btn btn-xs btn-danger">Remove</button>' +
            '</div></li>');
    }

    function removeUIData(propname, element) {
        // remove the UI data row
        $(element).parent().parent().remove();
        // remove data and redraw the chart
        delete(dataobj[propname]);
        drawChart();
    }

    $(function () {
        // plot tooltip
        var $tooltip = $("<div class='tooltip top in'><div class='tooltip-inner'></div></div>").hide().appendTo('body');
        var previousPoint = null;
        var tooltip = function (event, pos, item) {
            if (item) {
                if (previousPoint != item.seriesIndex) {
                    previousPoint = item.seriesIndex;
                    var tip = item.series['label'] + " : " + WPOS.util.currencyFormat(item.datapoint[1]);
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
            if (item.series['refs'].length==0) return;
            var refs = item.series['refs'][item.dataIndex];
            if (refs == "") return;
            WPOS.transactions.openTransactionList(refs);
        };
        var chart = $('#plot-chart');
        chart.on('plothover', tooltip);
        chart.on('plotclick', clickgraph);
        chart.css({'width': '100%', 'height': '320px'});
        // init date pickers
        etime = new Date();
        etime.setHours(23); etime.setMinutes(59); etime.setSeconds(59);
        etime = etime.getTime();
        stime = new Date();
        stime.setHours(0); stime.setMinutes(0); stime.setSeconds(0);
        stime = (stime.getTime() - 604800000); // a week ago

        $("#graphstime").datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(etime),
            onSelect: function(text, inst){
                var date = $("#graphstime").datepicker("getDate");
                date.setHours(0); date.setMinutes(0); date.setSeconds(0);
                stime = date.getTime();
                setGraphRange();
            }
        });
        $("#graphetime").datepicker({dateFormat:"dd/mm/yy", maxDate: new Date(etime),
            onSelect: function(text, inst){
                var date = $("#graphetime").datepicker("getDate");
                date.setHours(23); date.setMinutes(59); date.setSeconds(59);
                etime = date.getTime();
                setGraphRange();
            }
        });
        $("#graphstime").datepicker('setDate', new Date(stime));
        $("#graphetime").datepicker('setDate', new Date(etime));
        // load graph
        loadDefaultData();

        // hide loader
        WPOS.util.hideLoader();
    });
</script>