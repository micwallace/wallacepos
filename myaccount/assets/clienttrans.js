/**
 * clienttrans.js is part of Wallace Point of Sale system (WPOS)
 *
 * clienttrans.js Provides functionality for loading and viewing data/UI for customer transactions, across all customer dashboard pages.
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

function WPOSCustomerTransactions() {
    var transactions = {};
    var items = null;
    // functions for opening info dialogs and populating data
    var curref;
    var curid;
    // TRANSACTION DETAILS DIALOG FUNCTIONS
    this.openTransactionList = function(refs){
        // check input
        if (!refs) return;
        // Initiate UI
        if (!uiinit) initUI();
        var translist = $("#translist"), transdialog = $("#translistdialog");
        translist.html('');
        refs = refs.split(',');
        for (var i in refs){
            translist.append('<tr><td>'+refs[i]+'</td><td><button class="btn btn-sm btn-primary" onclick="WPOS.transactions.openTransactionDialog(\''+refs[i]+'\');">Details</button></td></tr>');
        }
        transdialog.dialog('open');
    };
    this.openTransactionDialog = function(ref){
        // Initiate UI
        if (!uiinit) initUI();
        // Check for transaction record
        if (transactions.hasOwnProperty(ref)==false){
            // try to load the customer record
            WPOS.util.showLoader();
            if (loadTransaction(ref)==false){
                WPOS.util.hideLoader();
                return;
            }
        }
        var record = transactions[ref];
        curref = ref;
        curid = record.id;
        // Populate general transaction info
        $("#transref").text(record.ref);
        $("#transid").text(record.id);
        $("#transtime").text(WPOS.util.getDateFromTimestamp(record.processdt));
        $("#transptime").text(record.dt);
        $("#transsubtotal").text(WPOS.currency() + record.subtotal);
        if (record.discount > 0) {
            $("#transdiscount").text(record.discount + "% (" + WPOS.currency() + record.discountval + ')');
            $("#transdisdiv").show();
        } else {
            $("#transdisdiv").hide();
        }
        $("#transtotal").text(WPOS.currency() + record.total);

        populateItemsTable(record.items);
        populatePaymentsTable(record.payments);
        populateTaxinfo(record);
        populateVoidInfo(record);

        WPOS.util.hideLoader();
        $("#edittransdialog").dialog("open");
    };

    this.setTransactions = function(transdata){
        transactions = transdata;
    };

    this.setTransaction = function(trans){
        if (trans.hasOwnProperty('ref'))
            transactions[trans.ref] = trans;
    };

    function loadTransaction(ref){
        var trans = WPOS.sendJsonData("transactions/get", JSON.stringify({ref: ref}));
        if (!trans.hasOwnProperty(ref)){
            alert("Could not load the selected transaction.");
            return false;
        }
        transactions[ref] = trans[ref];
        return true;
    }

    this.getTransactions = function(){
        return transactions;
    };

    function populateTaxinfo(record) {
        var transtax = $('#transtax');
        transtax.html('');
        if (record.hasOwnProperty('taxdata')) {
            for (var i in record.taxdata) {
                if (i != 1)
                    transtax.append('<label class="fixedlabel">' + WPOS.getTaxTable()[i].name + ' (' + WPOS.getTaxTable()[i].value + '%):</label><span>' + WPOS.currency() + record.taxdata[i] + '</span><br/>');
            }
        }
    }

    function populateVoidInfo(record) {
        var status = getTransactionStatus(record);
        $("#transstat").html(getStatusHtml(status, record.type));
        if (status > 1) {
            $("#voidinfo").show();
            if (status == 2) {
                // hide buttons if void
                $(".voidbuttons").hide();
            } else {
                $(".voidbuttons").show();
            }
            // populate void/refund list
            populateRefundTable(record);
        } else {
            $(".voidbuttons").show();
            $("#voidinfo").hide();
        }
    }

    function populateItemsTable(items) {
        var itemtable = $("#transitemtable");
        $(itemtable).html('');
        var taxval;
        for (var i = 0; i < items.length; i++) {
            if (WPOS.getTaxTable().hasOwnProperty(items[i].taxid)) {
                taxval = WPOS.currency() + items[i].tax + " (" + WPOS.getTaxTable()[items[i].taxid].name + ")";
            } else {
                taxval = "N/A";
            }
            $(itemtable).append('<tr><td>' + items[i].qty + '</td><td>' + items[i].name + '</td><td>' + WPOS.currency() + items[i].unit + '</td><td>' + (taxval != null ? taxval : "") + '</td><td>' + WPOS.currency() + items[i].price + '</td>');
        }
    }

    function populatePaymentsTable(payments) {
        var paytable = $("#transpaymenttable");
        $(paytable).html('');
        for (var i = 0; i < payments.length; i++) {
            $(paytable).append('<tr><td>' + payments[i].method + (payments[i].hasOwnProperty('extid')?'  (ID:'+payments[i].extid+')':'') + '</td><td>' + WPOS.currency() + payments[i].amount + '</td><td>' + WPOS.util.getShortDate(payments[i].processdt) + '</td><td>');
        }
    }

    function populateRefundTable(record) {
        var refundtable = $("#transvoidtable");
        $(refundtable).html("");
        if (record.refunddata !== undefined) {
            var tempdata;
            for (var i = 0; i < record.refunddata.length; i++) {
                tempdata = record.refunddata[i];
                $(refundtable).append('<tr><td><span class="label label-warning arrowed">Refund</span></td><td>' + WPOS.util.getDateFromTimestamp(tempdata.processdt) + '</td><td><button class="btn btn-sm btn-primary" onclick="showRefundDialog(' + i + ');">View</button></td><td><button onclick="removeVoid(' + curid + ', ' + tempdata.processdt + ');" class="btn btn-sm btn-danger">X</button></td></tr>');
            }
        }
        if (record.voiddata !== undefined && record.voiddata !== null) {
            $(refundtable).append('<tr>><td><span class="label label-danger arrowed">Void</span></td><td>' + WPOS.util.getDateFromTimestamp(record.voiddata.processdt) + '</td><td><button class="btn btn-sm btn-primary" onclick="showVoidDialog();">View</button></td><td><button onclick="removeVoid(' + curid + ', ' + record.voiddata.processdt + ');" class="btn btn-sm btn-danger">X</button></td></tr>');
        }
    }

    function populateSharedVoidData(record) {
        $("#reftime").text(WPOS.util.getDateFromTimestamp(record.processdt));
        $("#refuser").text(WPOS.users[record.userid].username);
        $("#refdev").text(WPOS.devices[record.deviceid].name);
        $("#refloc").text(WPOS.locations[record.locationid].name);
        $("#refreason").text(record.reason);
    }

    this.showVoidDialog = function() {
        var record = getVoidData(curref, false);
        populateSharedVoidData(record);
        $("#refunddetails").hide(); // the dialog section is used for refunds too, hide that view
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#voiddetails").show();
        mdialog.dialog('option', 'title', "Void Details");
        mdialog.dialog('open');
    };

    this.showRefundDialog = function(refundindex){
        var record = getVoidData(curref, true);
        record = record[refundindex]; // get the right refund record from the array
        populateSharedVoidData(record);
        $("#refmethod").text(record.method);
        $("#refamount").text(WPOS.currency() + record.amount);
        // populate refunded items
        var refitemtbl = $("#refitemtable");
        refitemtbl.html("");
        for (var i = 0; i < record.items.length; i++) {
            refitemtbl.append("<tr><td>" + getItemData(curref, record.items[i].id).name + "</td><td>" + record.items[i].numreturned + "</td></tr>");
        }
        $("#refunddetails").show(); // show the refund only view.
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#voiddetails").show();
        mdialog.dialog('option', 'title', "Refund Details");
        mdialog.dialog('open');
    };

    // EDIT DIALOG FUNCTIONS
    this.showGenerateDialog = function(){
        var mdialog = $('#miscdialog');
        mdialog.children("div").hide();
        mdialog.children("#geninvoiceform").show();
        mdialog.dialog('option', 'title', "Generate Invoice");
        mdialog.dialog('open');
    };

    // DATA FUNCTIONS
    this.generateInvoice = function(type, download) {
        var link = "/customerapi/invoice/generate?id=" + curid;
        if (type == "html") {
            link += "&type=html";
        } else {
            link += "&type=pdf";
        }
        if (download == 1) {
            link += "&download=1";
        } else {
            link += "&download=0";
        }

        window.open(link, '_blank');
    };

    // functions for determining status
    function getStatusHtml(status, type) {
        var stathtml;
        switch (status) {
            case -2:
                stathtml = '<span class="label label-danger arrowed">'+(type=='esale'?'Abandoned':'Overdue')+'</span>';
                break;
            case -1:
                stathtml = '<span class="label label-primary arrowed">Open</span>';
                break;
            case 0:
                stathtml = '<span class="label label-primary arrowed">'+(type=='esale'?'Pending Payment':'Order')+'</span>';
                break;
            case 1:
                stathtml = '<span class="label label-success arrowed">Complete</span>';
                break;
            case 2:
                stathtml = '<span class="label arrowed">Void</span>';
                break;
            case 3:
                stathtml = '<span class="label label-warning arrowed">Refunded</span>';
                break;
            default:
                stathtml = '<span class="label arrowed">Unknown</span>';
                break;
        }
        return stathtml;
    }

    this.getTransactionStatus = function(ref, html){
        if (!transactions.hasOwnProperty(ref)){
            return false;
        }
        var status = getTransactionStatus(transactions[ref]);
        if (html==true){
            return getStatusHtml(status, transactions[ref].type);
        }
        return status;
    };

    function getTransactionStatus(record) {
        if (record.type=='esale'){
            if (record.balance!=0){
                if (record.payments.length==0 && (record.processdt+43200000)<(new Date()).getTime()){
                    return -2;
                }
                return 0;
            }
        }
        if (record.type=='invoice'){
            if (record.balance == 0 && record.total != 0) {
                // closed/complete
                return 1;
            } else if ((record.duedt < (new Date).getTime()) && record.balance != 0) {
                // overdue
                return -2
            }
        }
        return record.status;
    }

    function getVoidData(ref, isrefund) {
        var record;
        record = transactions[ref];
        if (isrefund) {
            return record.refunddata;
        } else {
            return record.voiddata;
        }
    }

    function getItemData(ref, itemid) {
        var items = transactions[ref].items;
        for (var key in items) {
            if (items[key].id == itemid) {
                return items[key];
            }
        }
        return false;
    }

    var uiinit = false;
    function initUI(){
        uiinit = true;
        $(function(){
            // dialogs
            $("#edittransdialog").removeClass('hide').dialog({
                resizable: false,
                maxWidth: 800,
                width: 'auto',
                modal: true,
                autoOpen: false,
                title: "Transaction Details",
                title_html: true,

                buttons: [
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ],
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "800px");
                }
            });
            $("#miscdialog").removeClass('hide').dialog({
                width: 'auto',
                maxWidth: 500,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                title: "",
                title_html: true,
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "500px");
                }
            });
            // transaction listing dialog
            $("#translistdialog").removeClass('hide').dialog({
                width: 'auto',
                maxWidth: 400,
                maxHeight: 400,
                modal: true,
                closeOnEscape: false,
                autoOpen: false,
                title: "Report Transactions",
                title_html: true,
                buttons: [
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Close",
                        "class": "btn btn-xs",
                        click: function () {
                            $(this).dialog("close");
                        }
                    }
                ],
                create: function (event, ui) {
                    // Set maxWidth
                    $(this).css("maxWidth", "400px");
                    $(this).css("maxHeight", "400px");
                }
            });

        });
    }

    return this;
}