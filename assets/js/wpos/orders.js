function WPOSOrders(){
    var ordercontain = $("#ordercontainer");
    var orderhistcontain = $("#orderhistcontainer");

    this.processOrder = function(orderdata, olddata){
        var device = WPOS.getConfigTable().deviceconfig;
        if (device.type=="order_register") {
            if (device.ordertype == "printer") {
                // print to the kitchen printer
                processOrders(orderdata, olddata, 'kitchen', device.orderdisplay);
            } else if (device.ordertype == "terminal") {
                // set timeout only if updates
                if (processOrders(orderdata, olddata, null, device.orderdisplay)) {
                    timeouts[orderdata.ref] = setTimeout(function () {
                        kitchenTerminalFallback(orderdata, olddata);
                    }, 10000);
                }
            }
        }
    };

    function processOrders(data, olddata, print, display){
        var modcount = 0;
        if (typeof data === "object") {
            // check for old data, if none available process as new orders
            if (olddata) {
                if (data.hasOwnProperty('orderdata')){
                    for (var i in data.orderdata){
                        if (olddata.orderdata.hasOwnProperty(i)){
                            // the moddt param exists the order may have been modified, check further
                            if (data.orderdata[i].hasOwnProperty('moddt')){
                                // if the moddt flag doesn't exist on the old order moddt or is smaller than the new value
                                if (!olddata.orderdata[i].hasOwnProperty('moddt') || data.orderdata[i].moddt>olddata.orderdata[i].moddt) {

                                    if (display){
                                        WPOS.orders.removeOrder(data.ref, i);
                                        insertOrder(data, i);
                                    }
                                    if (print)
                                        WPOS.print.printOrderTicket(print, data, i, "ORDER UPDATED");
                                    modcount++;
                                }
                            }
                        } else {

                            if (display)
                                insertOrder(data, i);
                            if (print)
                                WPOS.print.printOrderTicket(print, data, i, null);
                            modcount++;
                        }
                    }
                } else {
                    // no order data exists in the new data, remove all
                    if (olddata.hasOwnProperty('orderdata'))
                        for (var r in olddata.orderdata){
                            if (display)
                                WPOS.orders.removeOrder(olddata.ref, r);
                            if (print)
                                WPOS.print.printOrderTicket(print, olddata, r, "ORDER CANCELLED");
                            modcount++;
                        }
                }
            } else {

                if (data.hasOwnProperty('orderdata'))
                    for (var o in data.orderdata) {
                        if (display)
                            insertOrder(data, o);
                        if (print)
                            WPOS.print.printOrderTicket(print, data,o, null);
                        modcount++;
                    }
            }
        } else {
            // process removed orders if they exists in the system
            if (olddata){
                if (olddata.hasOwnProperty('orderdata'))
                    for (var d in olddata.orderdata){
                        if (display)
                            WPOS.orders.removeOrder(olddata.ref, d);
                        if (print)
                            WPOS.print.printOrderTicket(print, olddata, d, "ORDER CANCELLED");
                        modcount++;
                    }
            }
        }

        return modcount;
    }

    var timeouts = [];
    this.kitchenTerminalAcknowledge = function(ref){
        // the main kitchen terminal has received the order
        if (timeouts.hasOwnProperty(ref)) {
            clearTimeout(timeouts[ref]);
            delete timeouts[ref];
        }
        console.log("Order acknowledgement received from kitchen terminal");
    };

    function kitchenTerminalFallback(orderdata, olddata){
        // TODO: have kitchen printer for a fallback option
       var answer = confirm("The last order has not been received by the kitchen terminal,\nwould you like to print and order tickets here to take to the kitchen?");
       if (answer){
           processOrders(orderdata, olddata, 'receipts', false);
       }
    }

    function insertOrder(saleobj, orderid){
        var order = saleobj.orderdata[orderid];
        var elem = $("#orderbox_template").clone().removeClass('hide').attr('id', 'order_box_'+saleobj.ref+'-'+order.id);
        elem.find('.orderbox_orderid').text(order.id);
        elem.find('.orderbox_saleref').text(saleobj.ref);
        elem.find('.orderbox_orderdt').text(WPOS.util.getDateFromTimestamp(order.processdt));
        var itemtbl = elem.find('.orderbox_items');
        for (var i in order.items){
            var item = saleobj.items[order.items[i]]; // the items object links the item id with it's index in the data
            var modStr = "";
            if (item.hasOwnProperty('mod')){
                for (var x=0; x<item.mod.items.length; x++){
                    var mod = item.mod.items[x];
                    modStr+= '<br/>'+(mod.hasOwnProperty('qty')?((mod.qty>0?'+ ':'')+mod.qty+' '):'')+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+WPOS.util.currencyFormat(mod.price)+')';
                }
            }
            itemtbl.append('<tr><td style="width:10%;"><strong>'+item.qty+'</strong></td><td><strong>'+item.name+'</strong>'+modStr+'<br/></td></tr>');
        }
        ordercontain.prepend(elem);
    }
    this.removeOrder = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).remove();
    };
    this.moveOrderToHistory = function(ref, orderid){
        $("#order_box_" + ref + '-' + orderid).detach().prependTo(orderhistcontain);
    };
}