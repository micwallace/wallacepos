/**
 * sales.js is part of Wallace Point of Sale system (WPOS)
 *
 * sales.js Provides functions for processing sales, including the storage and upload of offline sales,
 * It also includes WPOSItems object for adding items to the sale.
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

function WPOSItems() {
    // public members for modifying the current sales items
    /**
     * Adds a blank item row for the user to fill in
     */
    this.addManualItemRow = function () {
        // add the row
        addItemRow(1, "", "", "0.00", 1, 0);
        // focus on qty
        $("#itemtable")
            .children('tr :last')
            .find(".itemqty")
            .focus();
        // mark invalid records
        WPOS.sales.updateSalesTotal();
    };
    /**
     * Removes an item from the sale
     * @param {string} sitem
     */
    this.removeItem = function (sitem) {
        $(sitem).parent('td').parent('tr').remove();
        WPOS.sales.updateSalesTotal();
    };

    /**
     * Adds an item from a stock code
     * @param {string} code
     */
    this.addItemFromStockCode = function (code) {
        // find the item id from the stock code index and use it to retrieve the record.
        var item = WPOS.getItemsTable()[WPOS.getStockIndex()[code.toUpperCase()]];
        if (item === null || item === undefined || item === "") {//ADAM: Should use triple equals
            alert("Item not found");
            $("#codeinput").val('');
        } else {
            // add the item
            addItem(item);
        }
    };

    /**
     *
     * @param {Number} id
     */
    this.addItemFromId = function (id) {
        var item = WPOS.getItemsTable()[id];
        if (item === null) {
            alert("Item not found");
        } else {
            // add the item
            addItem(item);
        }
    };

    /**
     *
     * @param {String} query
     * @returns {Array}
     */
    this.searchItems = function (query) {
        var results = [];
        query.trim();
        if (query !== '') {
            var upquery = query.toUpperCase();
            // search items for the text.
            var itemtable = WPOS.getItemsTable();
            for (var key in itemtable) {
                if (!itemtable.hasOwnProperty(key)) {
                    continue;
                }
                if (itemtable[key].name.toUpperCase().indexOf(upquery) != -1) {
                    results.push(itemtable[key]);
                } else if (itemtable[key].code.toUpperCase().indexOf(upquery) != -1) {
                    results.push(itemtable[key]);
                }
            }
        }
        //alert(JSON.stringify(results));
        return results;
    };

    this.addItemRow = function(qty, name, desc, unit, taxid, sitemid){
        addItemRow(qty, name, desc, unit, taxid, sitemid)
    };

    this.generateItemGrid = function(){
        var iboxitems = $("#iboxitems");
        var items = WPOS.getItemsTable();
        iboxitems.html('');
        var price;
        for (var i in items){
            price = (items[i].price==""?"??.??":parseFloat(items[i].price).toFixed(2));
            iboxitems.append('<div class="iboxitem" onclick="WPOS.items.addItemFromId('+items[i].id+'); toggleItemBox(false);">' +
                                '<h6>'+items[i].name+'</h6>'+
                                '<h5>'+WPOS.currency()+price+'</h5>'+
                            '</div>');
        }
    };

    /**
     * Adds a html row into the sales table, if sitem id is greater than 0, all fields that are filled are disabled to prevent modification.
     * @param {Number} qty
     * @param {String} name
     * @param {String} desc
     * @param {String} unit
     * @param {Number} taxid
     * @param {Number} sitemid ; the stored item id to keep track of inventory sales
     */
    function addItemRow(qty, name, desc, unit, taxid, sitemid) {
        sitemid = (sitemid>0?sitemid:0);
        var disable = (sitemid>0); // disable fields that are filled by the stored item
        var disableprice = (sitemid>0 && WPOS.getConfigTable().pos.priceedit!="always");
        $("#itemtable")
            .append(
                '<tr>' +
                '<td>' +
                '<input class="itemid" type="hidden" value="' + sitemid + '" />' +
                '<input onChange="WPOS.sales.updateSalesTotal();" style="width:50px;" type="text" class="itemqty numpad" value="' + qty + '" />' +
                '</td>' +
                '<td>' +
                '<input '+((disable==true && name!="")?"disabled":"")+' style="width: 100%; min-width: 100px;" type="text" class="itemname" value="' + name + '" onChange="WPOS.sales.updateSalesTotal();" />' +
                '</td>' +
                '<td>' +
                '<input  style="width: 100%; min-width: 100px;" type="text" class="itemdesc" value="' +
                desc +
                '" />' +
                '</td>' +
                '<td>' +
                '<input '+((disableprice==true && unit!="")?"disabled":"")+' onChange="WPOS.sales.updateSalesTotal();" style="max-width:50px;" type="text" class="itemunit numpad" value="' + unit + '" />' +
                '</td>' +
                '<td>' +
                '<select '+((disable==true && taxid!=null)?"disabled":"")+' onChange="WPOS.sales.updateSalesTotal();" style="max-width:110px;" class="itemtax" value="'+taxid+'">' +
                getTaxSelectHTML(taxid) +
                '</select>' +
                '<input class="itemtaxval" type="hidden" value="0.00" />' +
                '</td>' +
                '<td>' +
                '<input style="max-width:50px;" type="text" class="itemprice" value="0.00" disabled />' +
                '</td>' +
                '<td style="text-align: center;">' +
                '<button class="btn btn-sm btn-danger" onclick="WPOS.items.removeItem($(this));">' +
                'X' +
                '</button>' +
                '</td>' +
                '</tr>');
        // reinitialize keypad & field listeners
        WPOS.initKeypad();
    }

    /**
     * Gets or generates the taxid select HTML depending on input
     * @param {Number} taxid
     * @returns {String}
     */
    function getTaxSelectHTML(taxid) {
        var taxselecthtml = "";
        var taxtable = WPOS.getTaxTable();
            for (var key in taxtable) {
                if (taxtable.hasOwnProperty(key)) {
                    taxselecthtml += "<option id='taxid-" + key + "' value='" + taxtable[key].value + "' "+(taxid==key?"selected='selected'":"")+">" + taxtable[key].name + " (" + taxtable[key].value + "%)</option>";
                }
            }
        return taxselecthtml;
    }

    /**
     *
     * @param {Object} item
     */
    function addItem(item) {
        // remove last row from table if its invalid.
        // check if a priced item is already present in the sale and if so increment it's qty
        if (item.price==""){
            // insert item into table
            addItemRow(1, item.name, item.description, item.price, item.taxid, item.id);
        } else {
            if (!isItemAdded(item.id, true)){
                // insert item into table
                addItemRow(1, item.name, item.description, item.price, item.taxid, item.id);
            }
        }
        $("#codeinput").val('');
        WPOS.sales.updateSalesTotal();
    }

    function isItemAdded(itemid, addqty){
        var found = false;
        $("#itemtable").children(".valid").each(function(index, element) {
            if ($(element).find(".itemid").val()==itemid){
                if (addqty) $(element).find(".itemqty").val(parseInt($(element).find(".itemqty").val())+1);
                found = true;
                return false;
            }
            return true;
        });
        return found;
    }

    this.searchCustomers = function(query){
        var results = [];
        var customers = WPOS.getCustTable();
        query.trim();
        query = query.toLowerCase();
        for (var key in customers){
            if (customers[key].email.toLowerCase().indexOf(query) != -1){
                results.push(customers[key]);
            }
        }
        //this.processNewEmail();
        return results;
    };

    this.loadCustomerDetails = function(custid, email){ // triggered on autocomplete click
        validateEmail(email);
        loadCustomerIfExists(custid);
    };

    function loadCustomerIfExists(custid){
        var custdata = WPOS.getCustTable()[custid];
        if (custdata!=null){
            $("#custid").val(custdata.id);
            $("#custname").val(custdata.name);
            //$("#custemail").val(custdata.email);
            $("#custphone").val(custdata.phone);
            $("#custmobile").val(custdata.mobile);
            $("#custaddress").val(custdata.address);
            $("#custsuburb").val(custdata.suburb);
            $("#custpostcode").val(custdata.postcode);
            $("#custcountry").val(custdata.country);
            return true;
        } else {
            return false;
        }
    }

    this.openCustomerDialog = function(){
        $("#custdiv").dialog("open");
    };

    this.processNewEmail = function(){ // triggered while the user is typing
        var email = $('#custemail').val();
        if (validateEmail(email)==true){
            if (loadCustomerIfExists(WPOS.getCustId(email))){
                return;
            }
        }
        // clear old forms/flags if no account loaded
        $('#custid').val(0);
        WPOS.sales.clearCustUpdate();
        $('#custform').trigger('reset');
    };

    function validateEmail(email){
        var emailreccb = $("#emailreceipt");
        if (email!=="" && email.indexOf("@")!==-1){
            emailreccb.prop('checked', true); // make this a local setting
            emailreccb.prop('disabled', false);
            return true;
        } else {
            emailreccb.prop('checked', false);
            emailreccb.prop('disabled', true);
            return false;
        }
    }
}
// Item UI stuff
$(function () {
    $.ui.autocomplete.prototype._renderItem = function (ul, item) {
        return $("<li>").data("ui-autocomplete-item", item).append("<a>" + (item.email!=undefined?item.email:item.name) + "</a>").appendTo(ul);
    };

    $("#itemsearch").autocomplete({
        source: function (request, response) {
            response(WPOS.items.searchItems(request.term));
        },
        search: function () {
            // custom minLength
            var term = this.value;
            return term.length >= 2;
        },
        focus : function () {
            // prevent value inserted on focus
            return false;
        },
        select: function (event, ui) {
            WPOS.items.addItemFromId(ui.item.id);
            this.value = "";
            return false;
        }
    });

    $("#custemail").autocomplete({
        source: function (request, response) {
            response(WPOS.items.searchCustomers(request.term));
        },
        search: function () {
            // custom minLength
            var term = this.value;
            return term.length >= 2;

        },
        select: function (event, ui) {
            this.value = ui.item.email;
            WPOS.items.loadCustomerDetails(ui.item.id, ui.item.email);
            return false;
        }
    });
});

function WPOSSales() {
    var paymentMethods = ['eftpos', 'credit', 'cash', 'cheque', 'deposit'];
    var cursubtotal = 0.00;
    var curtax = 0.00;
    var curtotal = 0.00;
    var lasttransref = null;
    var curref = null; // reference for the order/sale currently processing, set when a prev order is loaded, cleared when the form is cleared

    this.getLastRef = function(){
        return lasttransref;
    };

    this.getCurrentRef = function(){
        return curref;
    };

    /**
     *
     */
    this.updateSalesTotal = function () {
        var total = 0.00;
        var tempprice = 0.00;
        curtax = 0.00; // clear last tax
        // validate records, marks valid records to be used in sale and informs user of invalid records
        // It also calculates item total and checks that its a correct result
        validateSalesItems();
        // cycle through valid records and add item total to the sales total
        var temptax;
        $("#itemtable").children('.valid').each(function (index, element) {
                // get item total
                tempprice = parseFloat($(element).find(".itemprice").val());
                // add to total
                total = total + tempprice;
                // get tax amount included with each item
                if ($(element).find(".itemtax option:selected").attr("id").split("-")[1]!=1){ // skip for no tax;
                    // get tax
                    temptax = WPOS.util.calcTax(tempprice, $(element).find(".itemtax option:selected").attr("id").split("-")[1]);
                    // add to total
                    curtax+= temptax;
                } else {
                    temptax = parseFloat("0.00");
                }
                // put into UI for checkout calculations
                $(element).find(".itemtaxval").val(temptax);
        });
        // remove cur tax from the total and we have our subtotal
        curtotal = total;
        cursubtotal = (total - curtax);
        $("#subtotal").text(WPOS.currency() + cursubtotal.toFixed(2));
        $("#totaltax").text(WPOS.currency() + curtax.toFixed(2));
        this.updateDiscount();
    };

    /**
     *
     */
    this.updateDiscount = function () {
        if (cursubtotal === null) {
            this.updateSalesTotal();
            return;
        }
        var discountobj = $("#salediscount");
        var distxtobj = $("#discounttxt");
        var discount = discountobj.val();
        curtotal = (parseFloat(cursubtotal) + parseFloat(curtax)); // !important reset total
        if (discount === "" || discount == "0" || discount === null) {
            discountobj.val("0");
            distxtobj.text("("+WPOS.currency()+"0.00)");
        } else {
            var discountsum = ((discount / 100) * curtotal).toFixed(2);
            distxtobj.text("(" + WPOS.currency() + discountsum + ")");
            curtotal = (curtotal - discountsum);
        }
        $("#total").text(WPOS.currency() + curtotal.toFixed(2));
    };

    /**
     *
     */
    var curgrandtotal = 0;
    var curround = 0;
    var curbalance = 0;
    this.updatePaymentSums = function () {
        var totalpaid = parseFloat(0);
        var temppay = parseFloat(0);
        var temptend = parseFloat(0);
        var totalchange = parseFloat(0);
        var allcash = true;
        var paymentstable = $("#paymentstable");
        // loop through payments, if cash apply rounding to payment amount and find change amount
        paymentstable.children('tr').each(function (index, element) {
            var paystr = $(element).find(".payamount").val();
            temppay = parseFloat(paystr);
            if ($(element).find(".paymethod").val()=='cash'){
                // apply cash rounding
                temppay = WPOS.util.roundToFiveCents(temppay);
                // get tender amount
                var change, tendfield = $(element).find(".paytender");
                temptend = parseFloat(tendfield.val()).toFixed(2);
                // apply cash rounding
                temptend = WPOS.util.roundToFiveCents(temptend);
                $(element).find(".paytender").val(temptend);
                //tendfield.val(temptend); causes problems with keypad
                if (temptend>temppay){
                    change = temptend - temppay;
                    totalchange+=change;
                } else {
                    change = 0.00;
                }
                $(element).find(".paychange").val(change.toFixed(2));
            } else {
                allcash = false;
            }

            if (paystr.match(new RegExp("^[0-9]+\.[0-9][0-9]$"))){
                $(element).find(".payamount").val(parseFloat(temppay).toFixed(2));
            }

            if (!isNaN(temppay)) {
                totalpaid += parseFloat(temppay);
            }
        });
        totalpaid = totalpaid.toFixed(2);
        // if all payments are cash, apply cash rounding to total else reverse any currently applied rounding
        if (allcash==false || paymentstable.children('tr').length==0){
            curgrandtotal = curtotal;
            curround = 0;
        } else {
            curgrandtotal = WPOS.util.roundToFiveCents(curtotal);
            curround = curgrandtotal - curtotal;
        }
        // update payment sums
        var balance = -(curgrandtotal - totalpaid);
        $("#salestotal").text(WPOS.currency() + parseFloat(curgrandtotal).toFixed(2));
        $("#paymentstotal").text(WPOS.currency() + parseFloat(totalpaid).toFixed(2));
        $("#salesbalance").text(WPOS.currency() + balance.toFixed(2));
        $("#saleschange").text(WPOS.currency() + (balance>=0?totalchange.toFixed(2):0.00));
        salebalanced = balance == 0;
        curbalance = balance;
    };

    this.updatePaymentChange = function(element){
        var tender = parseFloat($(element).find(".paytender").val());
        var amount = parseFloat($(element).parent().parent().find(".payamount").val());
        var change = 0.00;
        if (tender>amount){
            change = tender - amount;
        } /*else {
            // update payment amount field to match tender
            $(element).parent().parent().find(".payamount").val(tender);
            WPOS.sales.updatePaymentSums();
        }*/
        $(element).find(".paychange").val(change.toFixed(2));
    };

    /**
     *
     */
    this.userAbortSale = function () {
        var answer = confirm("Are you sure you want to abort this order?");
        if (answer) {
            clearSalesForm();
        }
    };

    /**
     *
     */
    function clearSalesForm() {
        // clear sales form
        $("#itemtable").html('');
        $("#paymentstable").html('');
        $(".payamount").val("0.00");
        $("#salenotes").text("").val('');
        $("#salediscount").val("0");
        $("#discounttxt").text("($0.00)");
        $("#totaltax").text("$0.00");
        $("#subtotal").text("$0.00");
        $("#total").text("$0.00");
        $("#custemail").val("");
        $("#custid").val("");
        var emailreccb = $("#emailreceipt");
        emailreccb.prop("checked", false);
        emailreccb.prop("disabled", true);
        $("#custform").trigger('reset');
        // zero current totals
        cursubtotal = 0.00;
        curtotal = 0.00;
        curround = 0.00;
        curgrandtotal = 0.00;
        curref = null;
        // remove error notice
        $("#invaliditemnotice").hide();
    }

    function getNumSalesItems(){
        return $("#itemtable .valid").length;
    }

    function validateSalesItems(){
        var qty,name, unit, tempprice;
        var numinvalid = 0;
        $("#itemtable").children("tr").each(function (index, element) {
            qty = parseInt($(element).find(".itemqty").val());
            name =  $(element).find(".itemname").val();
            unit = parseFloat($(element).find(".itemunit").val());
            tempprice = parseFloat("0.00");
            if ((qty>0 && name!="") && unit>0){
                // calculate item total
                tempprice = qty * unit;
                if (tempprice>0){
                    // valid item; mark as valid, remove ui indicator class
                    $(element).addClass("valid");
                    $(element).removeClass("danger");
                    $(element).find(".itemprice").val(tempprice.toFixed(2));
                } else {
                    $(element).find(".itemprice").val("0.00");
                }
            } else {
                // not a valid record
                $(element).removeClass("valid");
                $(element).addClass("danger");
                // something is null, set price to 0
                $(element).find(".itemprice").val("0.00");
                // increment number invalid
                numinvalid++;
            }
        });
        // show warning if items invalid
        if (numinvalid>0){
            $("#invaliditemnotice").show();
        } else {
            $("#invaliditemnotice").hide();
        }
    }

    this.showPaymentDialog = function () {
        WPOS.sales.updatePaymentSums();
        if (getNumSalesItems() && curgrandtotal>0){
            // Show integrated eftpos button if enabled
            var inteftbtn = $("#eftpospaybtn");
            if (WPOS.eftpos.isEnabledAndReady()){
                inteftbtn.show();
                inteftbtn.text(WPOS.util.capFirstLetter(WPOS.eftpos.getType())+' Eftpos');
            } else {
                inteftbtn.hide();
            }
            $("#paymentsdiv").dialog('open');
            $("#endsalebtn").prop("disabled", false); // make sure the damn button is active, dunno why but when the page reloads it seems to keep its state.
        } else {
            alert("Please add some valid items to the sale before proceeding!");
        }
    };

    this.addAdditionalPayment = function(){
        addPaymentRow(null, 0, 0, 0);
    };

    this.removePayment = function(pitem){
        $(pitem).parent('td').parent('tr').remove();
        WPOS.sales.updatePaymentSums();
    };

    this.addPayment = function(method){
        if ($("#paymentstable").children('tr').length>0){
            addPaymentRow(method, (-curbalance+(method=="cash"?0:-curround)).toFixed(2), null, null); // don't add sale total
        } else {
            addPaymentRow(method, curgrandtotal.toFixed(2), curgrandtotal.toFixed(2), null);
        }
        WPOS.sales.updatePaymentSums();
    };

    this.addPaymentRowWithData = function(method, value, extraData){
        addPaymentRow(method, value, value, 0, extraData);
        WPOS.sales.updatePaymentSums();
    };

    this.startEftposPayment = function(){
        // find out if cashout supported by provider
        if (WPOS.eftpos.isCashoutSupported){
            showCashoutDialog();
        } else {
            initEftposPayment(0);
        }
    };

    var codialoginit = false;
    function showCashoutDialog(){
        var codialog = $("#codialog");
        if (!codialoginit){
            codialoginit = true;
            codialog.removeClass('hide').dialog({
                maxWidth : 200,
                width : 'auto',
                modal   : true,
                autoOpen: false,
                appendTo: "#paymentsdiv",
                buttons: [
                    {
                        html: "<i class='icon-check bigger-110'></i>&nbsp; Ok",
                        "class": "btn btn-success btn-xs",
                        click: function () {
                            $(".keypad-popup").hide();
                            var cashout =  parseFloat($("#cashoutamount").val()).toFixed(2);
                            if (cashout<0){
                                alert("Cashout value must be positive or 0");
                                return;
                            }
                            codialog.dialog('close');
                            initEftposPayment(cashout);
                        }
                    },
                    {
                        html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                        "class": "btn btn-xs",
                        click: function () {
                            $(".keypad-popup").hide();
                            codialog.dialog('close');
                        }
                    }
                ],
                create: function( event, ui ) {
                }
            });
        }
        $("#cashoutamount").val(0);
        codialog.dialog('open');
    }

    function initEftposPayment(cashout){
        if ($("#paymentstable").children('tr').length>0){
            WPOS.eftpos.startEftposPayment((-curbalance+-curround).toFixed(2), cashout); // use current balance, remove rounding
        } else {
            WPOS.eftpos.startEftposPayment(curgrandtotal.toFixed(2), cashout);
        }
    }

    this.onPaymentMethodChange = function(elem){
        if($(elem).find(':selected').val()=='cash'){
            $(elem).parent().find('.cashvals').show();
        }else{
            $(elem).parent().find('.cashvals').hide();
        }
        WPOS.sales.updatePaymentSums();
    };

    function addPaymentRow(method, value, tender, change, extraData){
        var exmethod = '';
        if ($.inArray(method, paymentMethods)==-1)
            exmethod = '<option value="'+method+'" selected>'+method+'</option>';
        var data = '';
        if (extraData)
            data = "data-paydata='"+JSON.stringify(extraData)+"'";

        var payrow =  '<tr '+data+'><td>' +
            '<select class="paymethod" onchange="WPOS.sales.onPaymentMethodChange(this);">' +
            '<option value="eftpos" '+(method=='eftpos'?'selected':'')+'>Eftpos</option>' +
            '<option value="credit" '+(method=='credit'?'selected':'')+'>Credit</option>' +
            '<option value="cash" '+(method=='cash'?'selected':'')+'>Cash</option>' +
            '<option value="check" '+(method=='check'?'selected':'')+'>Cheque</option>' +
            '<option value="deposit" '+(method=='deposit'?'selected':'')+'>Deposit</option>' +
            exmethod+ '</select>' +
            '<div class="cashvals" '+(method!='cash'?'style="display: none"':'')+'>' +
            '<div style="width: 100px; display: inline-block;">Tendered:</div><input onChange="WPOS.sales.updatePaymentChange($(this).parent());" class="paytender numpad" style="width:50px;" type="text" value="'+(method!='cash'?0.00:(tender!=null?tender:value))+'" />' +
            '<div style="width: 100px; display: inline-block;">Change:</div><input class="paychange" style="width:50px;" type="text" value="'+(method!='cash'?0.00:(change!=null?change:0.00))+'" readonly />' +
            '</div></td>' +
            '<td>$<input onChange="WPOS.sales.updatePaymentSums();" class="payamount numpad" style="width:50px;" type="text" value="'+value+'" autocomplete="off"/></td>' +
            '<td><button class="btn btn-xs btn-danger" onclick="WPOS.sales.removePayment($(this));">X</button></td></tr>';

        $("#paymentstable").append(payrow);

        // reinitialize keypad & field listeners
        WPOS.initKeypad();
    }

    // FUNCTIONS BEFORE ARE FOR PROCESSING ORDERS
    this.saveOrder = function(){
        processOrder();
    };

    function processOrder(){
        var salesobj = getSaleObject();
        if (curref!=null){
            salesobj.ref = curref;
            removeSalesRecord(curref);
        }
        lasttransref = salesobj.ref;
        salesobj.isorder = true;
        // add to offline table temporarily
        addOfflineSale(salesobj, "orders/set");
        if (WPOS.isOnline()) {
            if (WPOS.sendJsonDataAsync("orders/set", JSON.stringify(salesobj), WPOS.sales.postSaleUpload, salesobj.ref)){ // if the ajax request is success
                WPOS.setStatusBar(2, "Uploading Record...");
            } else {
                WPOS.sales.postSaleUpload(false, salesobj.ref);
            }
        } else {
            // update status
            WPOS.setStatusBar(3, "WPOS is offline ("+WPOS.sales.getOfflineSalesNum()+" offline records)");
        }
        // close the payment dialog and clear form
        clearSalesForm();
        $("#paymentsdiv").dialog("close");

    }

    this.loadOrder = function(ref){
        loadOrder(ref);
    };

    this.removeOrder = function(ref){
        var answer = confirm("Are you sure you want to delete this order?");
        if (answer){
            WPOS.util.showLoader();
            if (WPOS.sendJsonData("orders/remove", JSON.stringify({ref: ref}))!==false){
                removeSalesRecord(ref);
            } else {
                alert("Could not delete the order!");
            }
            WPOS.util.hideLoader();
            WPOS.trans.showTransactionTable();
        }
    };

    function loadOrder(ref){
        // get the existing sales object.
        var salesobj = WPOS.trans.getTransactionRecord(ref);
        if (salesobj!=false){
            clearSalesForm();
            // load items into the table
            var item;
            for (var i in salesobj.items){
                item = salesobj.items[i];
                WPOS.items.addItemRow(item.qty, item.name, item.desc, item.unit, item.taxid, item.sitemid);
            }
            $("#salediscount").val(salesobj.discount);
            $("#salenotes").val(salesobj.salenotes);
            $("#custemail").val(salesobj.custemail);
            $("#custid").val(salesobj.custid);
            // TODO: Check for custdata property, indicating that the customer data requires an update, load the data
            var payment;
            for (var i in salesobj.payments){
                payment = salesobj.payments[i];
                var tender, change;
                if (payment.method == "cash"){
                    tender = payment.tender;
                    change = payment.change;
                } else {
                    tender = 0;
                    change = 0
                }
                addPaymentRow(payment.method, payment.amount, tender, change);
            }
            // set the current transaction reference and close dialog
            curref = salesobj.ref;
            WPOS.sales.updateSalesTotal();
            WPOS.sales.updatePaymentSums();
            $("#transactiondiv").dialog('close');
        } else {
            alert("Could not find the current record.");
        }
    }

    // FUNCTIONS BELOW ARE FOR PROCESSING THE SALE
    this.processSale = function () {
        var salebtn = $("#endsalebtn");
        salebtn.prop("disabled", true);
        if (!isSaleBalanced()){
            alert("Please balance the sale before continuing");
            return;
        }
        if (!validatePayments()){
            alert("Only cash-out payments may have a negative amount");
            return;
        }
        ProcessSaleTransaction();
        salebtn.prop("disabled", false);
    };

    var salebalanced = false;

    function isSaleBalanced(){
        return salebalanced;
    }

    function validatePayments(){
        var valid = true;
        $("#paymentstable").children("tr").each(function (index, element) {
            // Make sure payments are positive amounts, except cashout
            if (parseFloat($(element).find(".payamount").val())<0){
                if ($(element).find(".payamount").val()=='cash' && !$(element).data('paydata').hasOwnProperty('cashOut'))
                    valid = false;
            }
        });
        return valid;
    }

    function ProcessSaleTransaction(){
        var salesobj = getSaleObject();
        // check for sale reference, indicating an exiting order and set it's reference onto the new data
        if (curref!==null){
            //alert("Processing using orders existing reference: "+curref);
            salesobj.ref = curref;
            delete salesobj.isorder;
            removeSalesRecord(curref);
        }
        // add to offline table temporarily
        addOfflineSale(salesobj, "sales/add");
        lasttransref = salesobj.ref; // set for recall function use
        if (WPOS.isOnline()) {
            if (WPOS.sendJsonDataAsync("sales/add", JSON.stringify(salesobj), WPOS.sales.postSaleUpload, salesobj.ref)){ // if the ajax request fails to execute (try/catch)
                WPOS.setStatusBar(2, "Uploading Record...");
            } else {
                WPOS.sales.postSaleUpload(false, salesobj.ref);
            }
        } else {
            // update status
            WPOS.setStatusBar(3, "WPOS is offline ("+WPOS.sales.getOfflineSalesNum()+" offline records)");
        }
        var recemailed = $("#emailreceipt").is(":checked");
        // close the payment dialog and clear form (clears current ref aswell)
        clearSalesForm();
        $("#paymentsdiv").dialog("close");

        // open the draw if a cash payment
        for (var i in salesobj.payments){
            if (salesobj.payments[i].method == "cash"){
                WPOS.print.openCashDraw(true); // opens cash draw if configured
                break;
            }
        }
        // print receipt or prompt
        if (WPOS.getLocalConfig().recask == "print"){
            WPOS.print.printReceipt(salesobj.ref);
        } else {
            if (WPOS.getLocalConfig().recask == "email" && recemailed){
                return; // receipt has been emailed
            }
            var answer = confirm("Would you like to print a receipt?");
            if (answer){
                WPOS.print.printReceipt(salesobj.ref);
            }

        }
    }

    this.postSaleUpload = function(jsonresponse, callbackref){
        if (jsonresponse !== false) {
            // SUCCESS
            // add json response to sales records
            addSalesRecord(jsonresponse);
            // remove from offline temp
            removeOfflineSale(callbackref);
            // check if customer data available for processing
            if (jsonresponse.custdata != undefined){
                WPOS.updateCustTable(jsonresponse.custid, jsonresponse.custdata);
            }
            // reset status Icon
            WPOS.setStatusBar(1, "WPOS is Online");
        } else {
            // ERROR
            if (WPOS.switchToOffline()) { // do not store record if offline mode is not supported.
                // update status
                WPOS.setStatusBar(3, "WPOS is offline ("+WPOS.sales.getOfflineSalesNum()+" offline records)");
            } else {
                // remove from offline temp
                removeOfflineSale(callbackref);
            }
        }
    };

    function getSaleObject(){
        // get sales items
        var items = [];
        var taxtotals = {};
        var temptax, temptaxid, tempqty, numitems = 0;

        $("#itemtable").children(".valid").each(function (index, element) {
            // add tax information into the tax totals array
            temptax = parseFloat($(element).find(".itemtaxval").val());
            temptaxid = $(element).find(".itemtax option:selected").attr("id").split("-")[1];
            // add items tax to total if it is not "no tax"
            if (temptaxid!=1){
                if (!taxtotals.hasOwnProperty(temptaxid)){
                    taxtotals[temptaxid] = parseFloat('0.00');
                }
                taxtotals[temptaxid]+=temptax;
            }
            // add # items to total
            tempqty = parseInt($(element).find(".itemqty").val());
            numitems+=tempqty;
            // add item to the array
            items.push({"ref": index+1, "sitemid": $(element).find(".itemid").val(), "qty": tempqty, "name": $(element).find(".itemname").val(), "desc": $(element).find(".itemdesc").val(), "unit": parseFloat($(element).find(".itemunit").val()).toFixed(2), "taxid": temptaxid, "tax":temptax, "price": parseFloat($(element).find(".itemprice").val()).toFixed(2) });
        });

        // gather payments
        var payments = [];
        $("#paymentstable").children("tr").each(function (index, element) {
            var payment = {"method": $(element).find(".paymethod option:selected").val(), "amount": parseFloat($(element).find(".payamount").val()).toFixed(2) };
            if (payment.method == 'cash'){
                payment.tender = parseFloat($(element).find(".paytender").val()).toFixed(2);
                payment.change = parseFloat($(element).find(".paychange").val()).toFixed(2);

            }
            if ($(element).data('paydata'))
                payment.paydata = $(element).data('paydata');
            payments.push(payment);
        });

        // create object
        var salesobj = { };
        var date = new Date().getTime();
        var config = WPOS.getConfigTable();
        salesobj.ref = date + "-" + config.deviceid + "-" + Math.floor((Math.random() * 10000) + 1);
        salesobj.userid = WPOS.getCurrentUserId();
        salesobj.devid = config.deviceid;
        salesobj.locid = config.locationid;
        salesobj.custid = $("#custid").val();
        salesobj.custemail = $("#custemail").val();
        salesobj.notes = $("#salenotes").val();
        salesobj.discount = $("#salediscount").val();
        salesobj.rounding = curround.toFixed(2);
        salesobj.subtotal = cursubtotal.toFixed(2);
        salesobj.total = parseFloat(curgrandtotal).toFixed(2);
        salesobj.numitems = numitems;
        salesobj.processdt = date;
        salesobj.items = items;
        salesobj.payments = payments;
        // add tax information
        salesobj.tax = curtax.toFixed(2);
        for (var i in taxtotals){
            taxtotals[i] = taxtotals[i].toFixed(2);
        }
        salesobj.taxdata = taxtotals;

        // is customer data needed
        if (updatecust){
            salesobj.custdata = getCustomerObject();
            updatecust = false; // reset flag
        }

        // if customer wants the receipt send, set the flag
        if ($("#emailreceipt").is(":checked")){
            salesobj.emailrec = true;
        }

        return salesobj;
    }

    function getCustomerObject(){
        var custdata = {};

        custdata.id = $('#custid').val();
        custdata.name = $('#custname').val();
        custdata.email = $('#custemail').val();
        custdata.phone = $('#custphone').val();
        custdata.mobile = $('#custmobile').val();
        custdata.address = $('#custaddress').val();
        custdata.suburb = $('#custsuburb').val();
        custdata.postcode = $('#custpostcode').val();
        custdata.country = $('#custcountry').val();

        return custdata;
    }

    var updatecust = false; // flag indicating customer details need updating/adding
    this.setUpdateCust = function(){ // used to indicate new customer data
        updatecust = true;
    };
    this.clearCustUpdate = function(){ // used when the customer email field is changed
        updatecust = true;
    };

    this.openRefundDialog = function(ref){
        $("#voidform").hide();
        $("#refundform").show();
        $("#refundref").val(ref);
        var refamtinput = $("#refundamount");
        refamtinput.val(0);
        refamtinput.removeData('paydata'); // remove extra payment data
        var formdiv = $("#formdiv");
        formdiv.dialog("option", "title", "Refund transaction");
        var voidbtn = $("#procvoidbtn");
        voidbtn.attr("onclick", 'WPOS.sales.processRefund();');
        voidbtn.prop('disabled', false);
        // populate items
        var sale = WPOS.trans.getTransactionRecord(ref);
        var items = sale.items;
        var refitems = $("#refunditems");
        refitems.html('');
        var itemid;
        for (var i = 0; i<items.length; i++){
            // calc how many current items have already been refunded
            itemid = items[i].id;
            var refnum = 0;
            // loop though each refund
            for (var key in sale.refunddata){
                var ritems = sale.refunddata[key].items;
                // loop through items of the refund and add to total if the id equals
                for (var key1 in ritems){
                    refnum += (ritems[key1].id == itemid?1:0);
                }
            }
            refitems.append('<tr>' +
                '<td><input size="4" class="refundqty" type="number" value="0" onchange="WPOS.sales.validateRefund();" autocomplete="off"/>' +
                '<input class="refunditemid" type="hidden" value="'+(items[i].hasOwnProperty('id')?items[i].id:(i+1))+'"/>' + // temp fix for old db records not containing item id
                '<input class="refundsqty" type="hidden" value="'+(parseInt(items[i].qty)-refnum)+'"/>' +
                '<input class="refundsunit" type="hidden" value="'+items[i].unit+'"/></td>' +
                '<td>'+items[i].qty+' x '+items[i].name+' ($'+items[i].price+')</td>' +
            '</tr>');
        }
        var eftbtn = $("#eftposrefundbtn");
        if (WPOS.eftpos.isEnabledAndReady()){
            eftbtn.show();
        } else {
            eftbtn.hide();
        }
        formdiv.dialog('open');
    };

    this.addRefundPaymentData = function(method, amount, data){
        var refamount = $("#refundamount");
        refamount.data('paydata', data);
        refamount.val(amount);
        var refmethodsel = $("#refundmethod");
        refmethodsel.append('<option value="'+method+'">'+method+'</option>');
        refmethodsel.val(method);
    };

    this.validateRefund = function(){
        var refundamt = 0;
        $("#refunditems").children("tr").each(function(index, item){
            var refundqty = parseInt($(item).find('.refundqty').val());
            var netqty = parseInt($(item).find('.refundsqty').val());
            // check if the amount is larger than bought qty
            if (refundqty>netqty){
                alert("Cannot return more items than sold + returned!");
                $(item).find('.refundqty').val(netqty);
                return false;
            }
            if (refundqty>0){
                var boughtunit = $(item).find('.refundsunit').val();
                // calculate refund amount and add to field
                refundamt += parseFloat(boughtunit) * parseFloat(refundqty);
            }
            return true;
        });
        $("#refundamount").val(refundamt);
        return true;
    };

    this.openVoidDialog = function(ref){
        $("#refundform").hide();
        $("#voidform").show();
        $("#voidref").val(ref);
        var formdiv = $("#formdiv");
        var voidbtn = $("#procvoidbtn");
        formdiv.dialog("option", "title", "Void transaction");
        voidbtn.attr("onclick", 'WPOS.sales.processVoid();');
        voidbtn.prop('disabled', false);
        formdiv.dialog('open');
    };

    this.processVoid = function(){
        // do not allow for sales with integrated eftpos transactions
        var ref = $("#voidref").val();
        var trans = WPOS.trans.getTransactionRecord(ref);
        for (var i in trans.payments){
            if (trans.payments[i].method=="tyro"){
                alert("Sales with Eftpos transactions cannot be voided. Refund this transaction instead.");
                return;
            }
        }

        if ($("#voidreason").val()==""){
            alert("Reason must not be blank.");
            return;
        }
        var answer = confirm("Are you sure you want to void this transaction?");
        if (answer){
            $("#procvoidbtn").prop('disabled', true);
            processVoidTransaction(ref, false);
            $("#formdiv").dialog('close');
            lasttransref = ref;
            // update transaction info
            WPOS.trans.populateTransactionInfo(ref);
        }
    };

    this.processRefund = function(){
        if ($("#refundreason").val()==""){
            alert("Reason must not be blank.");
            return;
        }
        if ($("#refundamount").val()<=0){
            alert("Amount must be larger than 0.");
            return;
        }
        var ref = $("#refundref").val();
        var answer = confirm("Are you sure you want to refund this transaction?");
        if (answer){
            $("#procvoidbtn").prop('disabled', true);
            processVoidTransaction(ref, true);
            $("#formdiv").dialog('close');
            lasttransref = ref;
            // update transaction info
            WPOS.trans.populateTransactionInfo(ref);
        }
    };

    this.eftposRefund = function(){
        var ref = $("#refundref").val();
        $("#formdiv").dialog('close');
        processVoidTransaction(ref, true);
        lasttransref = ref;
        // update transaction info
        WPOS.trans.populateTransactionInfo(ref);
    };

    function processVoidTransaction(ref, isrefund){
        var refundobj;
        // get current sale object
        if (isSaleOffline(ref)){
            refundobj = getVoidObject(ref, isrefund);
            // update the record in the offline table
            addUpdatedOfflineRecord(false, refundobj, "sales/void");
            return true;
        } else {
            refundobj =  getVoidObject(ref, isrefund);
        }
        if (WPOS.isOnline()){
            if (WPOS.sendJsonDataAsync("sales/void", JSON.stringify(refundobj), WPOS.sales.postVoidUpload, refundobj)){ // if the ajax request fails to execute (try/catch)
                WPOS.setStatusBar(2, "Uploading Record...");
            } else {
                WPOS.sales.postVoidUpload(false, refundobj);
            }
        } else {
            addUpdatedOfflineRecord(true, refundobj, "sales/void"); // update records
            // update status
            WPOS.setStatusBar(3, "WPOS is offline ("+WPOS.sales.getOfflineSalesNum()+" offline records)");
        }
        if (isrefund){
            // open the draw if a cash payment
            if (refundobj.refunddata[refundobj.refunddata.length-1].method == "cash"){
                WPOS.print.openCashDraw(true); // opens cash draw if configured
            }
            // print receipt or prompt
            if (WPOS.getLocalConfig().recask == "print"){
                WPOS.print.printReceipt(refundobj.ref);
            } else {
                var answer = confirm("Would you like to print a receipt?");
                if (answer){
                    WPOS.print.printReceipt(refundobj.ref);
                }
            }
        }
        return true;
    }

    this.postVoidUpload = function(jsonresponse, refundobject){
        if (jsonresponse !== false) {
            // SUCCESS
            // add json response to sales records
            addSalesRecord(jsonresponse);
            // reset status Icon
            WPOS.setStatusBar(1, "WPOS is Online");
        } else {
            // ERROR
            if (WPOS.switchToOffline()) { // do not store record if offline mode is not supported.
                addUpdatedOfflineRecord(true, refundobject, "sales/void"); // update records
                // update status
                WPOS.setStatusBar(3, "WPOS is offline ("+WPOS.sales.getOfflineSalesNum()+" offline records)");
            }
        }
    };

    function getVoidObject(ref, refund){
        var refundobj;
        var date = new Date().getTime();
        var config = WPOS.getConfigTable();
        // get data from the trans object, it holds offline + remotely loaded transactions
        refundobj = WPOS.trans.getTransactionRecord(ref);
        // add refund/void shared data
        var shareddata = {"userid": WPOS.getCurrentUserId(), "deviceid": config.deviceid, "locationid": config.locationid, "processdt": date};
        // add specific data
        if (refund){
            // if refund data is not defined, create an array
            if (refundobj.refunddata == null){
                refundobj.refunddata = [];
            }
            // add refund specific values to shared data
            shareddata.reason = $("#refundreason").val();
            var items = [];
            // get returned items
            var numreturned;
            $("#refunditems").children("tr").each(function(index, item){
                numreturned = $(item).find('.refundqty').val();
                if (numreturned>0){
                    items.push({"id": $(item).find('.refunditemid').val(), "numreturned": numreturned});
                }
            });
            var refamtinput = $("#refundamount");
            shareddata.items = items;
            shareddata.method = $("#refundmethod").val();
            shareddata.amount = parseFloat(refamtinput.val()).toFixed(2);
            if (refamtinput.data('paydata'))
                shareddata.paydata = refamtinput.data('paydata');
            // add to refund array
            refundobj.refunddata.push(shareddata);
        } else {
            refundobj.voiddata = shareddata;
            refundobj.voiddata.reason = $("#voidreason").val();
        }

        return refundobj;
    }

    function addUpdatedOfflineRecord(removefromsales, jsondata, action){
        // add record to offline table and remove from the sales table
        if (addOfflineSale(jsondata, action)){
            if (removefromsales) removeSalesRecord(jsondata.ref);
        } else {
            alert("Failed to update the record in offline storage, the sale has not been updated.");
        }
    }

    // THE FOLLOWING FUNCTIONS ARE ALL RELATED TO SALES TABLES IN LOCAL STORAGE.
    /**
     *
     * @param {Object} jsonobj
     * @returns {number|*}
     */
    function addSalesRecord(jsonobj) {
        // add to java object
        WPOS.updateSalesTable(jsonobj.ref, jsonobj);
        // save to local storage
        var jsonsales;
        if (localStorage.getItem("wpos_csales") !== null) {
            jsonsales = $.parseJSON(localStorage.getItem("wpos_csales"));
            jsonsales[jsonobj.ref] = jsonobj;
        } else {
            jsonsales = {};
            jsonsales[jsonobj.ref] = jsonobj;
        }
        localStorage.setItem("wpos_csales", JSON.stringify(jsonsales));
        return true;
    }

    function removeSalesRecord(ref){
        // add to java object
        WPOS.removeFromSalesTable(ref);
        // remove from local storage
        var jsonsales;
        if (localStorage.getItem("wpos_csales") !== null) {
            jsonsales = $.parseJSON(localStorage.getItem("wpos_csales"));
            delete jsonsales[ref];
        }
        localStorage.setItem("wpos_csales", JSON.stringify(jsonsales));
        return true;
    }

    /**
     *
     * @returns {*}
     */
    this.getOfflineSalesNum = function () {
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            return Object.keys(jsonosales).length;
        } else {
            return 0;
        }
    };
    this.getOfflineSales = function (){
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            // strip the action variable so the data is returned in the same format as normal sales table'
            for (var i in jsonosales){
                jsonosales[i] = jsonosales[i].data;
            }
            return jsonosales;
        } else {
            return {};
        }
    };
    /**
     * @param {Object} jsonobj
     * @param {String} action
     * @returns {number|*}
     */
    this.updateOfflineSale = function(jsonobj, action){
        return addOfflineSale(jsonobj, action);
    };
    function addOfflineSale(jsonobj, action) {
        var jsonosales;
        if (localStorage.getItem("wpos_osales") !== null) {
            jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
        } else {
            jsonosales = {};
        }
        // If we wanted to allow multiple actions per record, we can change action to an array.
        jsonosales[jsonobj.ref] = {};
        jsonosales[jsonobj.ref].a = action;
        jsonosales[jsonobj.ref].data = jsonobj;
        localStorage.setItem("wpos_osales", JSON.stringify(jsonosales));
        return true;
    }

    this.isSaleOffline = function(ref){
        return isSaleOffline(ref);
    };

    function isSaleOffline(ref){
        var jsonosales;
        if (localStorage.getItem("wpos_osales") !== null) {
            jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            if (jsonosales.hasOwnProperty(ref)){
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param {String} ref
     * @returns {boolean}
     */
    function removeOfflineSale(ref) {
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            delete jsonosales[ref];
            localStorage.setItem("wpos_osales", JSON.stringify(jsonosales));
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    this.uploadOfflineRecords = function(){
        return uploadOfflineRecords();
    };
    function uploadOfflineRecords() {
        if (localStorage.getItem("wpos_osales") !== null) {
            var jsonosales = $.parseJSON(localStorage.getItem("wpos_osales"));
            var jsonresponse;
            var uploadcount = 1;
            var totalcount = Object.keys(jsonosales).length;
            for (var key in jsonosales){
                // update status
                WPOS.setStatusBar(2, "Uploading record "+uploadcount+" of "+totalcount);
                // what action to perform to the offline record is kept in the a var
                var action = jsonosales[key].a;
                if (action=="sales/void"){
                    action = (jsonosales[key].data.hasOwnProperty("id")?"sales/void":"sales/add"); // if sale is completely offline, use addsale method.
                }
                jsonresponse = WPOS.sendJsonData(action, JSON.stringify(jsonosales[key].data));
                if (jsonresponse !== false && jsonresponse !== null) {
                    // remove from offline temp
                    removeOfflineSale(jsonosales[key].data.ref);
                    // add json response to todays records
                    addSalesRecord(jsonresponse);
                } else {
                    // damn so close, go back into offline mode
                    if (WPOS.switchToOffline()) {
                        // update status
                        WPOS.setStatusBar(3, "WPOS is offline ("+WPOS.sales.getOfflineSalesNum()+" offline records)");
                        return false;
                    }
                }
                uploadcount++;
            }

        }
        return true;
    }
}
