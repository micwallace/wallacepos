/**
 * transactions.js is part of Wallace Point of Sale system (WPOS)
 *
 * transactions.js Provides functions to view and manage past transactions, as well as UI functionality for refunds/voids.
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
 * @since      Class created 15/1/13 12:01 PM
 */

function WPOSTransactions() {

    this.showTransactionDialog = function () {
        showTransactionView(1);
        clearTransTable();
        loadLocalTransactions();
        $("#transactiondiv").dialog('open');
    };

    //
    this.showTransactionTable = function(){
        clearTransTable();
        loadLocalTransactions();
        showTransactionView(1);
    };

    this.showTransactionInfo = function(ref){
        populateTransactionInfo(ref);
        showTransactionView(2);
    };

    this.recallLastTransaction = function(){
        var lastref = WPOS.sales.getLastRef();
        if (lastref == null){
            alert("No transactions yet for this session.");
            return;
        }
        WPOS.trans.showTransactionInfo(lastref);
        $("#transactiondiv").dialog('open');
    };

    var rows;

    function insertIntoTransTable(gid, syncstat, ref, devloc, numitems, total, sdt, status, cacherow) {
        var ostathtml, rowhtml;
        switch (syncstat){
            case 1:
                ostathtml = '<span class="label label-sm label-warning arrowed">offline</span>';
                break;
            case 2:
                ostathtml = '<span class="label label-sm label-warning arrowed">partial</span>';
                break;
            case 3:
                ostathtml = '<span class="label label-sm label-primary arrowed">synced</span>';
                break;
        }
        // put into array if cache is true
        rowhtml = "<tr><td>" + gid + "<br/>" + ostathtml +"</td><td><a href='#' title='" + ref + "'>"+ ref.split('-')[2]+"</a></td><td>" + devloc + "</td><td>" + numitems + "</td><td>" + WPOS.currency() + total + "</td><td>" + sdt + "</td><td>" + getStatusHtml(status) + "</td><td><button class='btn btn-sm btn-primary' onclick='WPOS.trans.showTransactionInfo("+'"'+ref+'"'+")'>View</button></td></tr>";
        if (cacherow) rows[ref] = rowhtml;
        // add to table
        $("#transtable").prepend(rowhtml);
    }

    function getStatusHtml(status){
        var stathtml;
        switch(status){
            case 0:
                stathtml='<span class="label label-primary arrowed">Order</span>';
                break;
            case 1:
                stathtml='<span class="label label-success arrowed">Complete</span>';
                break;
            case 2:
                stathtml='<span class="label label-danger arrowed">Void</span>';
                break;
            case 3:
                stathtml='<span class="label label-warning arrowed">Refunded</span>';
                break;
            default:
                stathtml='<span class="label arrowed">Unknown</span>';
                break
        }
        return stathtml;
    }

    /**
     * Clear all transactions in the UI table
     */
    function clearTransTable() {
        $("#transtable").html("");
    }
    /** Shows the specified view in the transactions window.
     *  @param {int} typeid
     *  @return void
     */
    function showTransactionView(typeid){
        var transtable = $("#transactiontable");
        var transinfo = $("#transactioninfo");
        switch (typeid){
            case 1:
                transtable.css("display", "block");
                transinfo.css("display", "none");
                break;
            case 2:
                transinfo.css("display", "block");
                transtable.css("display", "none");
                break;
        }
    }

    this.getTransactionRecord = function(ref){
        return getTransactionRecord(ref);
    };

    function getTransactionRecord(ref){
        if (WPOS.getSalesTable().hasOwnProperty(ref)){
            return WPOS.getSalesTable()[ref];
        } else if (WPOS.sales.getOfflineSales().hasOwnProperty(ref)){ // check offline sales
            return WPOS.sales.getOfflineSales()[ref];
        } else if (remtrans.hasOwnProperty(ref)) { // check in remote transaction table
            return remtrans[ref];
        } else {
            return false;
        }
    }

    this.populateTransactionInfo = function(ref){
        populateTransactionInfo(ref);
    };

    function populateTransactionInfo(ref){
        var record = getTransactionRecord(ref);
        var status = getTransactionStatus(ref);
        if (record===false){
            alert("Could not find the transaction record!");
        }
        // set values in info div
        $("#transstat").html(getStatusHtml(status));
        $("#transref").text(ref);
        $("#transid").text(record.id);
        $("#transtime").text(WPOS.util.getDateFromTimestamp(record.processdt));
        $("#transptime").text(record.dt);
        $("#transuser").text(WPOS.getConfigTable().users[record.userid].username);
        $("#transdev").text(WPOS.getConfigTable().devices[record.devid].name);
        $("#transloc").text(WPOS.getConfigTable().locations[record.locid].name);
        $("#transnotes").val(record.salenotes);

        $("#transsubtotal").text(WPOS.currency()+record.subtotal);
        populateTaxinfo(record);
        if (record.discount>0){
            $("#transdiscount").text(record.discount+"% ("+WPOS.currency()+(parseFloat(record.total)-Math.abs(parseFloat(record.subtotal)+parseFloat(record.tax))).toFixed(2)+')');
            $("#transdisdiv").show();
        } else {
            $("#transdisdiv").hide();
        }
        $("#transtotal").text(WPOS.currency()+record.total);

        populateItemsTable(record.items);
        populatePaymentsTable(record.payments);
        if (status>1){
            $("#voidinfo").show();
            $("#orderbuttons").hide();
            if (status==2){
                // hide buttons if void
                $("#voidbuttons").hide();
            } else {
                $("#voidbuttons").show();
            }
            // populate void/refund list
            populateRefundTable(record);
        } else {
            if (status ==0){
                $("#voidbuttons").hide();
                $("#orderbuttons").show();
            } else {
                $("#orderbuttons").hide();
                $("#voidbuttons").show();
            }
            $("#voidinfo").hide();
        }
    }

    function populateTaxinfo(record){
        var transtax = $('#transtax');
        transtax.html('');
        if (record.hasOwnProperty('taxdata')){
            for (var i in record.taxdata){
                transtax.append('<label class="fixedlabel">'+WPOS.getConfigTable().tax[i].name+' ('+WPOS.getConfigTable().tax[i].value+'%):</label><span>'+WPOS.currency()+record.taxdata[i]+'</span><br/>');
            }
        }
    }

    function populateItemsTable(items){
        var itemtable = $("#transitemtable");
        $(itemtable).html('');
        var taxtable = WPOS.getTaxTable();
        var taxval;
        for (var i = 0; i<items.length; i++){
            if (taxtable.hasOwnProperty(items[i].taxid)){
                taxval = taxtable[items[i].taxid].name;
            } else {
                taxval = "";
            }
            $(itemtable).append('<tr><td>'+items[i].qty+'</td><td>'+items[i].name+'</td><td>'+WPOS.currency()+items[i].unit+'</td><td>'+(taxval!=null?taxval:"")+'</td><td>'+WPOS.currency()+items[i].price+'</td></tr>');
        }
    }

    function populatePaymentsTable(payments){
        var paytable =$("#transpaymenttable");
        $(paytable).html('');
        var method, amount;
        for (var i = 0; i<payments.length; i++){
            // catch extras
            method = payments[i].method;
            amount = payments[i].amount;
            var paydetailsbtn = '';
            if (payments[i].hasOwnProperty('paydata')){
                // check for integrated payment details
                if (payments[i].paydata.hasOwnProperty('transRef')){
                    console.log(payments[i].paydata);
                    paydetailsbtn = "<button onclick='WPOS.trans.showPaymentInfo(this);' class='btn btn-xs btn-primary' data-paydata='"+JSON.stringify(payments[i].paydata)+"'>Details</button>";
                }
                // catch cash-outs
                if (payments[i].paydata.hasOwnProperty('cashOut')){
                    method = "cashout ("+WPOS.currency()+(-amount).toFixed(2)+")";
                }
            }
            $(paytable).append('<tr><td>'+WPOS.util.capFirstLetter(method)+'</td><td>'+WPOS.currency()+amount+'</td><td style="text-align: right;">'+paydetailsbtn+'</td></tr>');
        }
    }

    var curtransref;

    function populateRefundTable(record){
        curtransref = record.ref;
        var refundtable = $("#transvoidtable");
        $(refundtable).html("");
        if (record.refunddata !== undefined){
            var tempdata;
            for (var i = 0; i<record.refunddata.length; i++){
                tempdata = record.refunddata[i];
                $(refundtable).append('<tr><td><span class="label label-warning arrowed">Refund</span></td><td>'+WPOS.util.getDateFromTimestamp(tempdata.processdt)+'</td><td><button class="btn btn-sm btn-primary" onclick="WPOS.trans.showRefundDialog('+i+');">View</button></td></tr>');
            }
        }
        if (record.voiddata !== undefined && record.voiddata !== null){
            $(refundtable).append('<tr>><td><span class="label label-danger arrowed">Void</span></td><td>'+WPOS.util.getDateFromTimestamp(record.voiddata.processdt)+'</td><td><button class="btn btn-sm btn-primary" onclick="WPOS.trans.showVoidDialog();">View</button></td></tr>');
        }
    }

    function getVoidData(ref, isrefund){
        var record;
        record = getTransactionRecord(ref);
        if (isrefund){
            return record.refunddata;
        } else {
            return record.voiddata;
        }
    }

    function populateSharedVoidData(record){
        $("#transreftime").text(WPOS.util.getDateFromTimestamp(record.processdt));
        $("#transrefuser").text(WPOS.getConfigTable().users[record.userid].username);
        $("#transrefdev").text(WPOS.getConfigTable().devices[record.deviceid].name);
        $("#transrefloc").text(WPOS.getConfigTable().locations[record.locationid].name);
        $("#transrefreason").text(record.reason);
    }

    this.showVoidDialog = function(){
        populateVoidData(curtransref);
        $("#refunddetails").hide(); // the dialog is used for refunds too, hide that view
        var voiddiv = $("#voiddiv");
        voiddiv.dialog("option", "title", "Void Details");
        voiddiv.dialog('open');
    };

    function populateVoidData(ref){
        var record;
        record = getVoidData(ref, false);
        populateSharedVoidData(record);
    }

    this.showRefundDialog = function(refundindex){
        populateRefundData(curtransref, refundindex);
        $("#refunddetails").show(); // show the refund only view.
        var voiddiv = $("#voiddiv");
        voiddiv.dialog("option", "title", "Refund Details");
        voiddiv.dialog('open');
    };

    function populateRefundData(ref, refundindex){
        var record;
        record = getVoidData(ref, true);
        record = record[refundindex]; // get the right refund record from the array
        populateSharedVoidData(record);
        $("#transrefmethod").text(record.method);
        $("#transrefamount").text(WPOS.currency()+record.amount);
        // show payment details button if available
        var dtlbtn = $("#refpaydtlbtn");
        if (record.hasOwnProperty('paydata')){
            dtlbtn.removeClass('hide');
            console.log(record.paydata);
            dtlbtn.data('paydata', record.paydata);
        } else {
            dtlbtn.addClass('hide');
        }
        // populate refunded items
        var treftable = $("#transrefitemtable");
        treftable.html("");
        for (var i = 0; i<record.items.length; i++){
            treftable.append("<tr><td>"+getSaleItemData(ref, record.items[i].ref).name+"</td><td>"+record.items[i].numreturned+"</td></tr>");
        }
    }

    this.showPaymentInfo = function(btn){
        // the data is already stored in a HTML5 data element
        console.log($(btn).data('paydata'));
        showEftPaymentDialog($(btn).data('paydata'));
    };

    var paydialoginit = false;
    function showEftPaymentDialog(object){
        var paydialog = $("#eftdetailsdialog");
        if (!paydialoginit){
            paydialoginit = true;
            paydialog.removeClass('hide').dialog({
                maxWidth : 200,
                width : 'auto',
                modal   : true,
                autoOpen: false,
                buttons: [
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                        "class": "btn btn-xs",
                        click: function () {
                            $(".keypad-popup").hide();
                            paydialog.dialog('close');
                        }
                    }
                ],
                create: function( event, ui ) {
                }
            });
        }
        $("#efttransref").text(object.transRef);
        $("#efttranscard").text(object.cardType);
        $("#eftcustrec").text(object.customerReceipt);
        $("#eftmerchrec").text(object.merchantReceipt);
        paydialog.dialog('open');
    }

    function getSaleItemData(ref, itemref){
        var items = getTransactionRecord(ref).items;
        for (var key in items){
            if (items[key].ref == itemref){
                return items[key];
            }
        }
        return false;
    }

    function clearTransactions(){
        clearTransTable();
        transtable = {};
        rows = {};
    }

    var transtable = {};

    function loadLocalTransactions() {
        clearTransactions();
        var salestable = WPOS.getSalesTable();
        // Populate synced records
        for (var ref in salestable) {
                transtable[ref] = salestable[ref];
                var devloc = WPOS.getConfigTable().devices[parseInt(salestable[ref].devid)].name+" / "+WPOS.getConfigTable().locations[parseInt(salestable[ref].locid)].name;
                insertIntoTransTable(salestable[ref].id, 3, ref, devloc, salestable[ref].numitems, salestable[ref].total, WPOS.util.getDateFromTimestamp(salestable[ref].processdt), getTransactionStatus(ref), true);
        }
        // Populate offline records
        if (WPOS.sales.getOfflineSalesNum() > 0) {
            var olsales = WPOS.sales.getOfflineSales();
            var syncstat, gid;
            for (ref in olsales) {
                    // add to the transaction info table
                    delete transtable[ref];
                    transtable[ref] = olsales[ref];
                    // calculate extra info and insert into UI table
                    devloc = WPOS.getConfigTable().devices[transtable[ref].devid].name+" / "+WPOS.getConfigTable().locations[transtable[ref].locid].name;
                    // is record an update?
                    if (transtable[ref].hasOwnProperty('id')){
                        syncstat = 2;
                        gid=transtable[ref].id;
                    } else {
                        syncstat = 1;
                        gid="";
                    }
                    insertIntoTransTable(gid, syncstat, ref, devloc, transtable[ref].numitems, transtable[ref].total, WPOS.util.getDateFromTimestamp(transtable[ref].processdt), getTransactionStatus(ref), true);
            }

        }

    }

    function getTransactionStatus(ref){
        var record = getTransactionRecord(ref);
        if (record.hasOwnProperty('voiddata')){
            return 2;
        } else if (record.hasOwnProperty("refunddata")){
            // refund
            return 3;
        } else if (record.hasOwnProperty("isorder")){
            return 0;
        }
        return 1;
    }

    this.searchTransactions = function(){
        var query = $("#transearchquery").val();
        if (query==null){
            alert("Please enter a search query");
            return false;
        }
        searchLocalTransactions(query);
        return true;
    };

    function searchLocalTransactions(query){
        // clear the html table
        clearTransTable();
        // search in cached rows
        for (var ref in rows){
            // check row html first
            if (rows[ref].indexOf(query)!==-1){
                $("#transtable").prepend(rows[ref]);
                // check json data
            } else if (JSON.stringify(transtable[ref]).indexOf(query)!==-1){
                $("#transtable").prepend(rows[ref]);
            }
        }
    }

    this.openRemoteSearchBox = function(){
        $("#transearch").hide();
        $("#remtransearch").show();
    };

    this.searchRemote = function(){
        var searchdata =  {};
        var refinput = $("#remsearchref");
        if (refinput.val()!=""){
            searchdata.ref = refinput.val();
        }
        if (Object.keys(searchdata).length>0){
            searchRemoteTransactions(searchdata);
        } else {
            alert("Please select at least one search option.");
        }
    };

    var remtrans = {};

    function searchRemoteTransactions(searchdata) {
        remtrans = WPOS.sendJsonData("sales/search", JSON.stringify(searchdata));
        clearTransTable();
        if (remtrans !== false){
            for (var ref in remtrans){
                var devloc = WPOS.getConfigTable().devices[remtrans[ref].devid].name+" / "+WPOS.getConfigTable().locations[remtrans[ref].locid].name;
                insertIntoTransTable(remtrans[ref].id, 3, ref, devloc, remtrans[ref].items.length, remtrans[ref].total, WPOS.util.getDateFromTimestamp(remtrans[ref].processdt), ((remtrans[ref].hasOwnProperty('void')) ? 2 : 1), false);
            }
        }
    }

    this.clearSearch = function(closeremote){
        clearTransTable();
        // load cached rows
        for (var ref in rows){
            $("#transtable").prepend(rows[ref]);
        }
        if (closeremote){
            $("#remtransearch").hide();
            $("#transearch").show();
        }
    };

    this.updateSaleNotes = function(){
        if (WPOS.isOnline()){
            updateSaleNotes();
        } else {
            // TODO: update notes and misc info offline
            alert("Updating notes offline is not supported at this time\nsorry for the inconvenience");
        }
    };

    function updateSaleNotes(){
        var answer = confirm("Save sale notes?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            var ref = $('#transref').text();
            var notes = $('#transnotes').val();
            if (WPOS.sendJsonData("sales/updatenotes", JSON.stringify({"ref":ref, "notes":notes}))!==false){
                // update local copy
                var sale = WPOS.trans.getTransactionRecord(ref);
                if (sale!=false){
                    // set new notes
                    sale.salenotes = notes;
                    if (WPOS.sales.isSaleOffline(ref)===true){
                        WPOS.sales.updateOfflineSale(sale);
                    } else {
                        WPOS.updateSalesTable(ref, sale)
                    }
                }
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }

}