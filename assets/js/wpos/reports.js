/**
 * reports.js is part of Wallace Point of Sale system (WPOS)
 *
 * reports.js Provides functions for calculating till reports and reconciliation items.
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      Class created 15/1/14 12:01 PM
 */

function WPOSReports() {
    // Overview
    this.populateOverview = function () {
        var stats = getOverviewStats();
        // Fill UI
        $("#rsalesnum").text(stats.salesnum);
        $("#rsalestotal").text(WPOS.util.currencyFormat(stats.salestotal.toFixed(2)));
        $("#rrefundsnum").text(stats.refundnum);
        $("#rrefundstotal").text(WPOS.util.currencyFormat(stats.refundtotal.toFixed(2)));
        $("#rvoidsnum").text(stats.voidnum);
        $("#rvoidstotal").text(WPOS.util.currencyFormat(stats.voidtotal.toFixed(2)));
        $("#rtotaltakings").text(WPOS.util.currencyFormat(stats.totaltakings.toFixed(2)));

        showAdditionalReports();
        // generate takings report
        this.generateTakingsReport();
    };

    function showAdditionalReports(){
        // show eftpos reports if available
        if (WPOS.hasOwnProperty('eftpos') && WPOS.eftpos.isEnabledAndReady() && WPOS.eftpos.getType()=="tyro"){
            $("#tyroreports").removeClass('hide');
        } else {
            $("#tyroreports").addClass('hide');
        }
    }

    this.calcReconcil = function () {
        var calcedtakings;
        var balance;
        var recdom100 = parseFloat($("#recdenom100").val()) * 100;
        var recdom50 = parseFloat($("#recdenom50").val()) * 50;
        var recdom20 = parseFloat($("#recdenom20").val()) * 20;
        var recdom10 = parseFloat($("#recdenom10").val()) * 10;
        var recdom5 = parseFloat($("#recdenom5").val()) * 5;
        var recdom2 = parseFloat($("#recdenom2").val()) * 2;
        var recdom1 = parseFloat($("#recdenom1").val());
        var recdom50c = $("#recdenom50c").val() * 0.5;
        var recdom20c = $("#recdenom20c").val() * 0.2;
        var recdom10c = $("#recdenom10c").val() * 0.1;
        var recdom5c = $("#recdenom5c").val() * 0.05;
        var recfloat = parseFloat($("#recfloat").val());
        var rectakings = $("#rectakings");
        var recbalance = $("#recbalance");

        calcedtakings = (recdom100 + recdom50 + recdom20 + recdom10 + recdom5 + recdom2 + recdom1 + recdom50c + recdom20c + recdom10c + recdom5c) - recfloat;
        calcedtakings = calcedtakings.toFixed(2);
        balance = (calcedtakings - curcashtakings).toFixed(2);
        $(rectakings).text(WPOS.util.currencyFormat(calcedtakings));
        $(recbalance).text(WPOS.util.currencyFormat(balance));
        if (recbalance === -0.00) {
            recbalance += 0.00;
        }
        // set status
        if (balance < 0.00) {
            $(recbalance).attr("class", "red");
            $(rectakings).attr("class", "red");
        } else {
            $(recbalance).attr("class", "text-success");
            $(rectakings).attr("class", "text-success");
        }
    };

    var curstats;
    var curcashtakings;

    function getTodaysRecords(includerefunds) {
        var sales = WPOS.getSalesTable();
        var todaysales = {};
        var stime = new Date();
        var etime = new Date();
        stime.setHours(0);
        stime.setMinutes(0);
        stime.setSeconds(0);
        stime = stime.getTime();
        etime.setHours(23);
        etime.setMinutes(59);
        etime.setSeconds(59);
        etime = etime.getTime();
        for (var key in sales) {
            // ignore if an order
            if (sales[key].hasOwnProperty('isorder') == false) {
                // ignore if the sale was not made today or refunded today
                if (sales[key].processdt > stime && sales[key].processdt < etime) {
                    // ignore if not made by this device
                    if (sales[key].devid == WPOS.getConfigTable().deviceid) {
                        todaysales[key] = sales[key];
                    }
                } else {
                    if (includerefunds)
                    // check for refund made today
                        if (sales[key].hasOwnProperty('refunddata')) {
                            var refdata = sales[key].refunddata;
                            for (var record in refdata) {
                                if (refdata[record].processdt > stime && refdata[record].processdt < etime) {
                                    // ignore if not made by this device
                                    if (refdata[record].deviceid == WPOS.getConfigTable().deviceid) {
                                        todaysales[key] = sales[key];
                                    }
                                }
                            }
                        }
                }
            }
        }
        return todaysales;
    }

    function getOverviewStats() {
        var sales = getTodaysRecords(true);
        var sale;
        var emptfloat = parseFloat("0.00");
        var stime = new Date();
        var etime = new Date();
        stime.setHours(0);
        stime.setMinutes(0);
        stime.setSeconds(0);
        stime = stime.getTime();
        etime.setHours(23);
        etime.setMinutes(59);
        etime.setSeconds(59);
        etime = etime.getTime();
        var data = {'salesnum': 0, 'salestotal': emptfloat, 'voidnum': 0, 'voidtotal': emptfloat, 'refundnum': 0, 'refundtotal': emptfloat, 'totaltakings': emptfloat, 'methodtotals': {}};
        var salestat;
        for (var key in sales) {
            sale = sales[key];
            salestat = getTransactionStatus(sale);
            var amount;
            var method;
            switch (salestat) {
                case 2:
                    data.voidnum++;
                    data.voidtotal += parseFloat(sale.total);
                    break;
                case 3:
                    // cycle though all refunds and add to total
                    for (var i in sale.refunddata) {
                        amount = parseFloat(sale.refunddata[i].amount);
                        method = sale.refunddata[i].method;
                        data.refundnum++;
                        data.refundtotal += amount;
                        // add payment type totals
                        if (data.methodtotals.hasOwnProperty(method)) { // check if payment method field is alredy set
                            data.methodtotals[method].refamount += amount;
                            data.methodtotals[method].refqty++;
                        } else {
                            data.methodtotals[method] = {};
                            data.methodtotals[method].refamount = amount;
                            data.methodtotals[method].refqty = 1;
                            data.methodtotals[method].amount = parseFloat(0);
                            data.methodtotals[method].qty = 0;
                        }
                    }
                    // count refund as a sale, but only if it was sold today
                    if (sale.processdt < stime || sale.processdt > etime) {
                        break; // the sale was not made today
                    }
                case 1:
                    data.salesnum++;
                    data.salestotal += parseFloat(sale.total);
                    // calc payment methods
                    for (var p in sale.payments) {
                        amount = parseFloat(sale.payments[p].amount);
                        method = sale.payments[p].method;
                        if (data.methodtotals.hasOwnProperty(method)) { // check if payment method field is alredy set
                            data.methodtotals[method].amount += amount;
                            data.methodtotals[method].qty++;
                        } else {
                            data.methodtotals[method] = {};
                            data.methodtotals[method].amount = amount;
                            data.methodtotals[method].qty = 1;
                            data.methodtotals[method].refamount = parseFloat(0);
                            data.methodtotals[method].refqty = 0;
                        }
                    }
            }
        }
        for (var x in data.methodtotals){
            data.methodtotals[x].amount = parseFloat(data.methodtotals[x].amount).toFixed(2);
            data.methodtotals[x].refamount = parseFloat(data.methodtotals[x].refamount).toFixed(2);
        }
        // calculate takings
        data.totaltakings = data.salestotal.toFixed(2) - data.refundtotal.toFixed(2);
        if (data.methodtotals.hasOwnProperty('cash')) {
            curcashtakings = (data.methodtotals.cash.hasOwnProperty('amount') ? data.methodtotals['cash'].amount : 0) - (data.methodtotals.cash.hasOwnProperty('refamount') ? data.methodtotals['cash'].refamount : 0);
        } else {
            curcashtakings = parseFloat(0).toFixed(2);
        }
        curstats = data;
        //alert(JSON.stringify(data));
        return data;
    }

    function getTransactionStatus(saleobj) {
        if (saleobj.hasOwnProperty('voiddata')) {
            return 2;
        } else if (saleobj.hasOwnProperty('refunddata')) {
            return 3;
        }
        return 1;
    }

    var config;
    var reportheader = function (name) {
        if (config == null) {
            config = WPOS.getConfigTable();
        }
        return '<div style="text-align: center; margin-bottom: 5px;"><h3>' + name + '</h3><h5>' + WPOS.util.getShortDate(null) + ' - ' + config.devicename + ' - ' + config.locationname + '</h5></div>';
    };

    function getSellerStats(){
        var sales = getTodaysRecords(true);
        var sale;
        var emptfloat = parseFloat("0.00");
        var stime = new Date();
        var etime = new Date();
        stime.setHours(0);
        stime.setMinutes(0);
        stime.setSeconds(0);
        stime = stime.getTime();
        etime.setHours(23);
        etime.setMinutes(59);
        etime.setSeconds(59);
        etime = etime.getTime();

        var data = {};
        var salestat;
        for (var key in sales) {
            sale = sales[key];
            salestat = getTransactionStatus(sale);
            var userid;
            switch (salestat) {
                case 2:
                    userid = sale.voiddata.userid;
                    if (data.hasOwnProperty(userid)) {
                        data[userid].voidrefs.push(sale.ref);
                        data[userid].voidnum++;
                        data[userid].voidtotal += parseFloat(sale.total);
                    } else {
                        data[userid] = {};
                        data[userid].salerefs = [];
                        data[userid].salenum = 0;
                        data[userid].saleamount = 0;
                        data[userid].refrefs = [];
                        data[userid].refnum = 0;
                        data[userid].refamount = 0;
                        data[userid].voidrefs = [sale.ref];
                        data[userid].voidnum = 1;
                        data[userid].voidamount = parseFloat(sale.total);
                    }
                    break;
                case 3:
                    // cycle though all refunds and add to total
                    for (var i in sale.refunddata) {
                        var amount = parseFloat(sale.refunddata[i].amount);
                        userid = sale.refunddata[i].userid;
                        if (data.hasOwnProperty(userid)) {
                            data[userid].refrefs.push(sale.ref);
                            data[userid].refnum++;
                            data[userid].reftotal += parseFloat(amount);
                        } else {
                            data[userid] = {};
                            data[userid].salerefs = [];
                            data[userid].salenum = 0;
                            data[userid].saletotal = 0;
                            data[userid].refrefs = [sale.ref];
                            data[userid].refnum = 1;
                            data[userid].reftotal = parseFloat(amount);
                            data[userid].voidrefs = [];
                            data[userid].voidnum = 0;
                            data[userid].voidtotal = 0;
                        }
                    }
                    // count refund as a sale, but only if it was sold today
                    if (sale.processdt < stime || sale.processdt > etime) {
                        break; // the sale was not made today
                    }
                case 1:
                    if (data.hasOwnProperty(sale.userid)) {
                        data[sale.userid].salerefs.push(sale.ref);
                        data[sale.userid].salenum++;
                        data[sale.userid].saletotal += parseFloat(sale.total);
                    } else {
                        data[sale.userid] = {};
                        data[sale.userid].salerefs = [sale.ref];
                        data[sale.userid].salenum = 1;
                        data[sale.userid].saletotal = parseFloat(sale.total);
                        data[sale.userid].refrefs = [];
                        data[sale.userid].refnum = 0;
                        data[sale.userid].reftotal = 0;
                        data[sale.userid].voidrefs = [];
                        data[sale.userid].voidnum = 0;
                        data[sale.userid].voidtotal = 0;
                    }
            }
        }
        for (var x in data){
            data[x].balance = (data[x].saletotal - data[x].reftotal).toFixed(2);
            data[x].saletotal = data[x].saletotal.toFixed(2);
            data[x].reftotal = data[x].reftotal.toFixed(2);
        }

        return data;
    }

    function getWhatsSellingStats() {
        var itemstats = {items: [], totalsold: 0};
        var records = getTodaysRecords(false);
        var items = [];
        var item = {};
        var discount = 0;
        var discprice = 0;
        for (var ref in records) {
            if (!records[ref].hasOwnProperty('voiddata')) { // do not count voided sales
                discount = parseFloat(records[ref].discount);
                items = records[ref].items;
                for (var index in items) {
                    item = items[index];
                    discprice = (parseFloat(item.price)-(item.price*(discount/100)));
                    // check if record exists
                    if (itemstats.items.hasOwnProperty(item.sitemid)) {
                        // sum values
                        itemstats.items[item.sitemid].qty += parseInt(item.qty);
                        itemstats.items[item.sitemid].total += discprice;
                    } else {
                        // create new record
                        var itemname = (item.sitemid == "0" ? "Miscellaneous" : item.name);
                        itemstats.items[item.sitemid] = {"qty": parseInt(item.qty), "total": discprice, "name": itemname};
                    }
                    itemstats.totalsold += item.qty;
                }
            }
        }

        return itemstats;
    }

    this.generateTakingsReport = function () {
        var html = reportheader("Takings Count Report") + '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Method</th><th># Payments</th><th>Takings</th><th># Refunds</th><th>Refunds</th><th>Balance</th></tr></thead><tbody>';
        var methdenoms = curstats.methodtotals;
        for (var method in methdenoms) {
            html += '<tr><td>' + WPOS.util.capFirstLetter(method) + '</td><td>' + methdenoms[method].qty + '</td><td>' + WPOS.util.currencyFormat(methdenoms[method].amount) + '</td><td>' + methdenoms[method].refqty + '</td><td>' + WPOS.util.currencyFormat(methdenoms[method].refamount) + '</td><td>' + WPOS.util.currencyFormat((parseFloat(methdenoms[method].amount) - parseFloat(methdenoms[method].refamount)).toFixed(2)) + '</td></tr>';
        }
        html += '</tbody></table>';
        // put into report window
        $("#reportcontain").html(html);
    };

    this.generateWhatsSellingReport = function () {
        var html = reportheader("What's Selling Report") + '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Item</th><th># Sold</th><th>Total</th></tr></thead><tbody>';
        var stats = getWhatsSellingStats();
        var item;
        for (var id in stats.items) {
            item = stats.items[id];
            html += '<tr><td>' + item.name + '</td><td>' + item.qty + '</td><td>' + WPOS.util.currencyFormat(item.total) + '</td></tr>';
        }

        html += '</tbody></table>';
        // put into report window
        $("#reportcontain").html(html);
    };

    this.generateSellerReport = function () {
        var html = reportheader("Seller Takings") + '<table style="width:100%;" class="table table-stripped"><thead><tr><th>User</th><th>Sales</th><th>Voids</th><th>Refunds</th><th>Balance</th></tr></thead><tbody>';
        var stats = getSellerStats();
        var item;
        var users = WPOS.getConfigTable().users;
        for (var id in stats) {
            item = stats[id];
            var user = users.hasOwnProperty(id) ? users[id].username : 'Unknown';
            html += '<tr><td>' + user + '</td><td>' + WPOS.util.currencyFormat(item.saletotal) + ' ('+item.salenum+')' + '</td><td>' + WPOS.util.currencyFormat(item.voidtotal) + ' ('+item.voidnum+')' + '</td><td>' + WPOS.util.currencyFormat(item.reftotal) + ' ('+item.refnum+')' + '</td><td>' + WPOS.util.currencyFormat(item.balance) + '</td></tr>';
        }

        html += '</tbody></table>';
        // put into report window
        $("#reportcontain").html(html);
    };

    this.generateTyroReport = function(){
        var type = $("#tyroreptype").val();
        WPOS.eftpos.getTyroReport(type, (type=="detail"?WPOS.reports.populateTyroDetailed:WPOS.reports.populateTyroSummary));
    };

    this.populateTyroSummary = function(xml){
        xml = parseXML(xml);
        var html = reportheader("Tyro Eftpos Summary Report") + '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Card Type</th><th style="text-align: right;">Purchase</th><th style="text-align: right;">Cash-Out</th><th style="text-align: right;">Refunds</th><th style="text-align: right;">Total</th></tr></thead><tbody>';
        var line = xml.find("card");
        //console.log(xml);
        //console.log(recon);
        $.each(line, function(i){
            //console.log($(this));
            html += '<tr><td style="text-align: left;">' + WPOS.util.capFirstLetter($(this).attr('type')) + '</td><td style="text-align: right;">' + WPOS.util.currencyFormat($(this).attr('purchases')) + '</td><td style="text-align: right;">' + WPOS.util.currencyFormat($(this).attr('cash-out')?$(this).attr('cash-out'):'0.00') + '</td><td style="text-align: right;">' + WPOS.util.currencyFormat($(this).attr('refunds')) + '</td><td style="text-align: right;">' + WPOS.util.currencyFormat($(this).attr('total')) + '</td></tr>';
        });
        html += '<tr><td colspan="4" style="text-align: left;"><strong>Total:</strong></td><td style="text-align: right;">' +WPOS.util.currencyFormat(xml.find("reconciliation-summary").attr('total')) + '</td></tr>';
        html += '</tbody></table>';
        // put into report window
        $("#reportcontain").html(html);
    };

    this.populateTyroDetailed = function(xml){
        xml = parseXML(xml);
        var html = reportheader("Tyro Eftpos Detail Report") + '<table style="width:100%;" class="table table-stripped"><thead><tr><th>Time</th><th>Type</th><th>Card Type</th><th style="text-align: right;">Cash Out</th><th style="text-align: right;">Total</th></tr></thead><tbody>';
        var line = xml.find("transaction");
        $.each(line, function(i){
            html += '<tr><td  style="text-align: left;">' + $(this).attr('transaction-local-date-time') + '</td><td style="text-align: left;">' + WPOS.util.capFirstLetter($(this).attr('type')) + '</td><td style="text-align: left;">' + WPOS.util.capFirstLetter($(this).attr('card-type')) + '</td><td style="text-align: right;">' + WPOS.util.currencyFormat($(this).attr('cash-out')?$(this).attr('cash-out'):'0.00') + '</td><td style="text-align: right;">' + WPOS.util.currencyFormat($(this).attr('amount')) + '</td></tr>';
        });
        html += '<tr><td colspan="3" style="text-align: left;"><strong>Total:</strong></td><td style="text-align: right;">' + WPOS.util.currencyFormat(xml.find("reconciliation-detail").attr('total')) + '</td></tr>';
        html += '</tbody></table>';
        // put into report window
        $("#reportcontain").html(html);
    };

    function parseXML(xml){
        var xmlobj = $.parseXML(xml);
        return $(xmlobj);
    }
}