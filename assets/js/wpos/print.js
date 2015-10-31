/**
 * print.js is part of Wallace Point of Sale system (WPOS)
 *
 * print.js Controls rendering of print jobs and outputs them according to current settings.
 * Provides functionality for ESCP and HTML receipt output.
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

function WPOSPrint(kitchenMode) {
    var wpdeployed = false;
    var webprint;
    var curset;
    var curprinter = null;

    this.loadPrintSettings = function () {
        loadPrintSettings(true);
    };

    var defaultsettings = {
        printer: {
            printer:"",
            port:"",
            method: "br",
            type: "raw",
            baud: "9600",
            databits: "8",
            stopbits: "1",
            parity: "even",
            flow: "none",
            printip: "",
            printport: "",
            cutter:true
        },
        global: {
            recask:"print",
            cashdraw: false,
            serviceip: "127.0.0.1",
            serviceport: 8080,
            usekitchen: false,
            usebar: false,
            printers: {}
        }
    };

    function getPrintSetting(printer, setting){
        if (curset.printers[printer].hasOwnProperty(setting))
            return curset.printers[printer][setting];
        return defaultsettings.printer[setting];
    }

    function doesAnyPrinterHave(setting, value){
        for (var i in curset.printers){
            if (curset.printers[i][setting]==value)
                return true;
        }
        return false;
    }

    function loadDefaultSettings(){
        // load variables from local config; a
        curset = WPOS.getLocalConfig().printing;
        if (!curset){
            curset = defaultsettings.global;
        }
        if (kitchenMode){
            if (!curset.printers.hasOwnProperty('orders'))
                curset.printers['orders'] = defaultsettings.printer;
        } else {
            if (!curset.printers.hasOwnProperty('receipts'))
                curset.printers['receipts'] = defaultsettings.printer;

            if (!curset.printers.hasOwnProperty('reports'))
                curset.printers['reports'] = defaultsettings.printer;

            if (!curset.printers.hasOwnProperty('kitchen'))
                curset.printers['kitchen'] = defaultsettings.printer;

            if (!curset.printers.hasOwnProperty('bar'))
                curset.printers['bar'] = defaultsettings.printer;
        }
    }

    function loadPrintSettings(loaddefaults) {
        if (loaddefaults)
            loadDefaultSettings();
        //deploy qz if not deployed and print method is qz.
        if (doesAnyPrinterHave('method', 'qz')) {
            alert("QZ-Print integration is no longer available, switch to the new webprint applet");
        } else if (doesAnyPrinterHave('method', 'wp') || doesAnyPrinterHave('method', 'ht')) {
            if (!wpdeployed)
                deployRelayApps();
            $("#printstat").show();
        } else {
            // disable print status
            $("#printstat").hide();
        }
        // set html option form values? This is only needed for the first load, further interaction handled by inline JS.
        setHtmlValues();
    }

    this.printAppletReady = function(){
        $("#printstattxt").text("Print-App Connected");
        if (doesAnyPrinterHave('method', 'wp')){
            webprint.requestPorts();
            webprint.requestPrinters();
        }
        // connect printer/s specified in config
        if (doesAnyPrinterHave('method', 'wp') && doesAnyPrinterHave('type', 'serial')) {
            setTimeout(function(){ openSerialPorts(); },1000);
        }
        console.log("Print Applet ready");
    };

    function disableUnsupportedMethods() {
        if (WPOS.util.mobile) {
            $(".wp-option").prop("disabled", true);
        }
        // disable http printing if not android
        if (!WPOS.util.isandroid) {
            $(".ht-option").prop("disabled", true);
        }
    }

    function deployRelayApps() {
        if (WPOS.util.isandroid) {
            webprint = new AndroidWebPrint(true, WPOS.print.printAppletReady);
        } else {
            webprint = new WebPrint(true, WPOS.print.populatePortsList, WPOS.print.populatePrintersList, WPOS.print.printAppletReady);
        }
        wpdeployed = true;
    }

    function setHtmlValues() {
        disableUnsupportedMethods();
        for (var i in curset.printers){
            var printer = curset.printers[i];
            var id = "#printsettings_"+i;
            if (printer.method == "wp" || printer.method == "ht") {
                $(id+" .printoptions").show();
                // show advanced printer selection options if needed
                if (printer.method == "wp") {
                    $(id+" .advprintoptions").show();
                    if (printer.type == "serial") {
                        $(id+" .serialoptions").show();
                        $(id+" .rawoptions").hide();
                        $(id+" .tcpoptions").hide();
                        // set serial settings
                        $(id+" .psetting_port").val(printer.port);
                        $(id+" .psetting_baud").val(printer.baud);
                        $(id+" .psetting_databits").val(printer.databits);
                        $(id+" .psetting_stopbits").val(printer.stopbits);
                        $(id+" .psetting_parity").val(printer.parity);
                    } else if (printer.type=="raw") {
                        $(id+" .rawoptions").show();
                        $(id+" .serialoptions").hide();
                        $(id+" .tcpoptions").hide();
                        // set raw settings
                        $(id+" .psetting_printer").val(printer.printer);
                    } else if (printer.type=="tcp") {
                        $(id+" .tcpoptions").show();
                        $(id+" .rawoptions").hide();
                        $(id+" .serialoptions").hide();
                        // set tcp settings
                        $(id+" .psetting_printerip").val(printer.printerip);
                        $(id+" .psetting_printerport").val(printer.printerport);
                    }
                    $(id+" .psetting_type").val(printer.type);
                } else {
                    $(id+" .advprintoptions").hide();
                }
                // set cash draw
                $(id+" .cashdrawoptions").show();
            } else {
                // browser printing, hide all options
                $(id+" .advprintoptions").hide();
                $(id+" .printoptions").hide();
                $(id+" .printserviceoptions").hide();
                $(id+" .cashdrawoptions").hide();
            }
            $(id+" .psetting_method").val(printer.method);
        }
        $("#cashdraw").prop("checked", curset.cashdraw);
        $("#recask").val(curset.recask);
        // show service options if needed
        if (doesAnyPrinterHave('method', 'wp') || doesAnyPrinterHave('method', 'ht')) {
            $(".printserviceoptions").show();
            $(".serviceip").val(curset.serviceip);
            $(".serviceport").val(curset.serviceport);
        } else {
            $(".printserviceoptions").hide();
        }
        // hide kitchen / bar settings
    }

    this.populatePortsList = function (ports) {
        var reclist = $('.psetting_port');
        reclist.html('');
        for (var p in ports) {
            reclist.append('<option value="' + ports[p] + '">' + ports[p] + '</option>');
        }
        for (var i in curset.printers){
            var printer = curset.printers[i];
            if (printer.type=='serial'){
                var id = "#printsettings_"+i;
                var elem = $("#printsettings_"+i+" .psetting_port");
                if ($(id+" .psetting_port option[value='"+printer.port+"']").length == 0){
                    elem.append('<option value="'+printer.port+'">'+printer.port+'</option>');
                }
                elem.val(printer.port);
            }
        }
    };

    this.populatePrintersList = function (printers) {
        var reclist = $('.psetting_printer');
        reclist.html('');
        for (var p in printers) {
            reclist.append('<option value="' + printers[p] + '">' + printers[p] + '</option>');
        }
        for (var i in curset.printers){
            var printer = curset.printers[i];
            if (printer.type=='raw'){
                var id = "#printsettings_"+i;
                var elem = $("#printsettings_"+i+" .psetting_printer");
                if ($(id+" .psetting_printer option[value='"+printer.printer+"']").length == 0){
                    elem.append('<option value="'+printer.printer+'">'+printer.printer+'</option>');
                }
                elem.val(printer.printer);
            }
        }
    };

    this.populatePrinters = function () {
        if (doesAnyPrinterHave('method', 'wp')) {
            webprint.requestPrinters();
        }
    };

    this.populatePorts = function () {
        if (doesAnyPrinterHave('method', 'wp')) {
            webprint.requestPorts();
        }
    };

    this.setPrintSetting= function(printer, key, value) {
        curset.printers[printer][key] = value;
        WPOS.setLocalConfigValue('printing', curset);
        loadPrintSettings(false); // reload print settings, default are already loaded
        if (key == "port" || key == "baud" || key == "databits" || key == "stopbits" || key == "parity" || key == "flow" || (key == "type" && value == "serial")) {
            openSerialPorts();
        }
    };

    this.setGlobalPrintSetting = function(key, value){
        curset[key] = value;
        WPOS.setLocalConfigValue('printing', curset);
    };

    function openSerialPorts(){
        for (var i in curset.printers){
            if (getPrintSetting(i, 'method') == "wp"){
                if (getPrintSetting(i, 'type')=="serial" && getPrintSetting(i, 'port')!="")
                    webprint.openPort(curset.printers[i].port, curset.printers[i]);
            }
        }
        console.log("Setting up serial connections");
    }

    // REPORT PRINTING
    this.printCurrentReport = function () {
        printCurrentReport();
    };

    function printCurrentReport() {
        var html;
        var printer = getPrintSetting('reports', 'printer');
        switch (getPrintSetting('reports', 'method')) {
            case "br":
                browserPrintHtml($("#reportcontain").html(), true);
                break;
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                break;
            case "wp":
                html = '<html><head><title>Wpos Report</title><link media="all" href="/admin/assets/css/bootstrap.min.css" rel="stylesheet"/><link media="all" rel="stylesheet" href="/admin/assets/css/font-awesome.min.css"/><link media="all" rel="stylesheet" href="/admin/assets/css/ace-fonts.css"/><link media="all" rel="stylesheet" href="admin/assets/css/ace.min.css"/></head><body style="background-color: #FFFFFF;">' + $("#reportcontain").html() + '</body></html>';
                webprint.printHtml(html, printer);
        }
    }

    // CASH DRAW
    this.openCashDraw = function (silentfail) {
        var result = openCashDraw();
        if (!silentfail)
            if (!result) {
                alert("Cash draw not connected or configured!!");
            }
    };

    function openCashDraw() {
        var method = getPrintSetting('receipts', 'method');
        if (curset.cashdraw && (method == "ht" || method == "wp")) {
            if (method == "qz"){
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            }
            if (method == "wp") {
                switch(getPrintSetting('receipts', 'type')){
                    case "raw":
                        if (getPrintSetting('receipts', 'printer')=="")
                            return false;
                        break;
                    case "serial":
                        if (getPrintSetting('receipts', 'port')=="")
                            return false;
                        break;
                    case "tcp":
                        if (getPrintSetting('receipts', 'printerip')=="" || getPrintSetting('receipts', 'printerport')=="")
                            return false;
                }
            }
            return sendESCPPrintData('receipts', esc_init + esc_p + "\x32" + "\x32");
        } else {
            return false;
        }
    }

    // RECEIPT PRINTING
    this.printArbReceipt = function (printer, text) {
        var method = getPrintSetting(printer, 'method');
        switch (method) {
            case "br":
                browserPrintHtml("<pre style='text-align: center; background-color: white;'>" + text + "</pre>", false);
                return true;
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "ht":
            case "wp":
                sendESCPPrintData(printer, esc_a_c + text + "\n\n\n\n" + gs_cut + "\r");
                return true;
            default :
                return false;
        }
    };

    this.printReceipt = function (ref) {
        printReceipt(ref);
    };

    function printReceipt(ref) {
        var record = WPOS.trans.getTransactionRecord(ref);
        var method = getPrintSetting('receipts', 'method');
        switch (method) {
            case "br":
                browserPrintHtml(getHtmlReceipt(record), false);
                return true;
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "ht":
            case "wp":
                var data = getEscReceipt(record);
                if (WPOS.getConfigTable().pos.recprintlogo == true) {
                    getESCPImageString("https://" + document.location.hostname + WPOS.getConfigTable().pos.reclogo, function (imgdata) {
                        appendQrcode("receipts", imgdata + data);
                    });
                } else {
                    appendQrcode("receipts", data);
                }
                return true;

            default :
                return false;
        }
    }

    this.printOrderTicket = function(printer, record, orderid, flagtext){
        var method = getPrintSetting(printer, 'method');
        switch (method) {
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "ht":
            case "wp":
                var data = getEscOrderTicket(record, orderid, flagtext);
                sendESCPPrintData(printer, data + "\n\n\n\n" + gs_cut + "\r");
                return true;
            default :
                return false;
        }
    };

    this.testReceiptPrinter = function(printer) {
        var method = getPrintSetting(printer, 'method');
        if (method == "ht" || method == "wp") {
            testReceipt(printer);
        } else {
            if (method == "qz"){
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return;
            }
            alert("Receipt printer not configured!");
        }
    };

    function testReceipt(printer) {
        var data = getEscReceiptHeader() + "\n\n\n\n" + gs_cut + "\r";
        getESCPImageString("https://" + document.location.hostname + WPOS.getConfigTable().pos.reclogo, function (imgdata) {
            sendESCPPrintData(printer, imgdata + data);
        });
    }

    this.printQrCode = function () {
        appendQrcode("receipts", "");
    };

    function appendQrcode(printer, data) {
        if (WPOS.getConfigTable().pos.recqrcode != "") {
            getESCPImageString("https://" + document.location.hostname + "/docs/qrcode.png", function (imgdata) {
                sendESCPPrintData(printer, data + imgdata + "\n\n\n\n" + gs_cut + "\r");
            });
        } else {
            sendESCPPrintData(printer, data + "\n\n\n\n" + gs_cut + "\r");
        }
    }

    function sendESCPPrintData(printer, data) {
        var method = getPrintSetting(printer, 'method');
        switch (method) {
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "wp":
                switch(getPrintSetting(printer, 'type')){
                    case "serial":
                        webprint.printSerial(btoa(data), getPrintSetting(printer, 'port'));
                        return true;
                    case "raw":
                        webprint.printRaw(btoa(data), getPrintSetting(printer, 'printer'));
                        return true;
                    case "tcp":
                        webprint.printTcp(btoa(data), getPrintSetting(printer, 'printerip')+":"+getPrintSetting(printer, 'printerport'));
                        return true;
                }
                return false;
            case "ht":
                webprint.print(data);
                return true;
        }
        return false;
    }

    // android print app methods
    var AndroidWebPrint = function (init, readyCb) {

        this.print = function (data) {
            if (!pwindow || pwindow.closed) {
                openPrintWindow();
                setTimeout(function () {
                    sendData(data);
                }, 220);
            }
            sendData(data);
        };

        function sendData(data) {
            pwindow.postMessage(encodeURIComponent(data), "*");
            console.log(data);
        }

        var pwindow;

        function openPrintWindow() {
            pwindow = window.open("http://" + curset.serviceip + ":" + curset.serviceport + "/printwindow", 'AndroidPrintService');
            if (pwindow)
                pwindow.blur();
            window.focus();
        }

        var timeOut;
        this.checkRelay = function () {
            if (pwindow && pwindow.open) {
                pwindow.close();
            }
            window.addEventListener("message", message, false);
            openPrintWindow();
            timeOut = setTimeout(dispatchAndroid, 2000);
        };

        function message(event) {
            if (event.origin != "http://" + curset.serviceip + ":" + curset.serviceport)
                return;
            if (event.data == "init") {
                clearTimeout(timeOut);
                readyCb();
                alert("The Android print service has been loaded in a new tab, keep it open for faster printing.");
            }
        }

        function dispatchAndroid() {
            var answer = confirm("Would you like to open/install the printing app?");
            if (answer) {
                document.location.href = "https://wallaceit.com.au/playstore/httpsocketadaptor/index.php";
            }
        }

        if (init) this.checkRelay();
        return this;
    };

    // web print methods
    var WebPrint = function (init, defPortCb, defPrinterCb, defReadyCb) {

        this.printRaw = function (data, printer) {
            var request = {a: "printraw", printer: printer, data: data};
            sendAppletRequest(request);
        };

        this.printSerial = function (data, port) {
            var request = {a: "printraw", port: port, data: data};
            sendAppletRequest(request);
        };

        this.printTcp = function (data, socket) {
            var request = {a: "printraw", socket: socket, data: data};
            sendAppletRequest(request);
        };

        this.printHtml = function (data, printer) {
            var request = {a: "printhtml", printer: printer, data: data};
            sendAppletRequest(request);
        };

        this.openPort = function (port, settings) {
            var request = {a: "openport", port: port, settings: {baud: settings.baud, databits: settings.databits, stopbits: settings.stopbits, parity: settings.parity, flow: settings.flow}};
            sendAppletRequest(request);
        };

        this.requestPrinters = function () {
            sendAppletRequest({a: "listprinters"});
        };

        this.requestPorts = function () {
            sendAppletRequest({a: "listports"});
        };

        function sendAppletRequest(data) {
            data.cookie = cookie;
            if (!wpwindow || wpwindow.closed || !wpready) {
                if (wpready){
                    console.log("Print window not detected as open...reopening window");
                    openPrintWindow();
                } else {
                    console.log("Print applet connection not established...trying to reconnect");
                    webprint.checkRelay();
                }
                setTimeout(function () {
                    wpwindow.postMessage(JSON.stringify(data), "*");
                }, 250);
            }
            wpwindow.postMessage(JSON.stringify(data), "*");
        }

        var wpwindow;
        var wpready = false;
        function openPrintWindow() {
            wpready = false;
            wpwindow = window.open("http://" + curset.serviceip + ":" + curset.serviceport + "/printwindow", 'WebPrintService');
            if (wpwindow)
                wpwindow.blur();
            window.focus();
        }

        var wptimeOut;
        this.checkRelay = function () {
            $("#printstattxt").text("Initializing...");
            if (wpwindow && !wpwindow.closed) {
                wpwindow.close();
            }
            window.addEventListener("message", handleWebPrintMessage, false);
            openPrintWindow();
            wptimeOut = setTimeout(dispatchWebPrint, 2500);
        };

        function handleWebPrintMessage(event) {
            if (event.origin != "http://" + curset.serviceip + ":" + curset.serviceport)
                return;
            switch (event.data.a) {
                case "init":
                    clearTimeout(wptimeOut);
                    wpready = true;
                    sendAppletRequest({a:"init"});
                    break;
                case "response":
                    var response = JSON.parse(event.data.json);
                    if (response.hasOwnProperty('ports')) {
                        if (defPortCb instanceof Function) defPortCb(response.ports);
                    } else if (response.hasOwnProperty('printers')) {
                        if (defPrinterCb instanceof Function)  defPrinterCb(response.printers);
                    } else if (response.hasOwnProperty('error')) {
                        alert(response.error);
                    }
                    if (response.hasOwnProperty("cookie")){
                        cookie = response.cookie;
                        localStorage.setItem("webprint_auth", response.cookie);
                    }
                    if (response.hasOwnProperty("ready")){
                        if (defReadyCb instanceof Function){
                            defReadyCb();
                            defReadyCb = null;
                        }
                    }
                    break;
                case "error": // cannot contact print applet from relay window
                    webprint.checkRelay();

            }
            //alert("The Web Printing service has been loaded in a new tab, keep it open for faster printing.");
        }

        function dispatchWebPrint() {
            $("#printstattxt").text("Print-App Error");
            var answer = confirm("Cannot communicate with the printing app.\nWould you like to open/install the printing app?");
            var dlframe = $("#dlframe");
            dlframe.attr("src", "");
            if (answer) {
                var installFile="WebPrint.jar";
                if (navigator.appVersion.indexOf("Win")!=-1) installFile="WebPrint_windows_1_1.exe";
                if (navigator.appVersion.indexOf("Mac")!=-1) installFile="WebPrint_macos_1_1.dmg";
                if (navigator.appVersion.indexOf("X11")!=-1) installFile="WebPrint_unix_1_1.sh";
                if (navigator.appVersion.indexOf("Linux")!=-1) installFile="WebPrint_unix_1_1.sh";
                //window.open("/assets/libs/WebPrint.jar", '_blank');
                dlframe.attr("src", "https://content.wallaceit.com.au/webprint/"+installFile);
            }
        }

        var cookie = localStorage.getItem("webprint_auth");
        if (cookie==null){
            cookie = "";
        }
        if (init) this.checkRelay();

        return this;
    };

    // ESC/P receipt generation
    var esc_init = "\x1B" + "\x40"; // initialize printer
    var esc_p = "\x1B" + "\x70" + "\x30"; // open drawer
    var gs_cut = "\x1D" + "\x56" + "\x4E"; // cut paper
    var esc_a_l = "\x1B" + "\x61" + "\x30"; // align left
    var esc_a_c = "\x1B" + "\x61" + "\x31"; // align center
    var esc_a_r = "\x1B" + "\x61" + "\x32"; // align right
    var esc_double = "\x1B" + "\x21" + "\x31"; // heading
    var font_reset = "\x1B" + "\x21" + "\x02"; // styles off
    var esc_ul_on = "\x1B" + "\x2D" + "\x31"; // underline on
    var esc_bold_on = "\x1B" + "\x45" + "\x31"; // emphasis on
    var esc_bold_off = "\x1B" + "\x45" + "\x30"; // emphasis off

    function getEscReceiptHeader() {
        var bizname = WPOS.getConfigTable().general.bizname;
        var recval = WPOS.getConfigTable().pos;
        // header
        var header = esc_init + esc_a_c + esc_double + bizname + "\n" + font_reset +
            esc_bold_on + recval.recline2 + "\n";
        if (recval.recline3 != "") {
            header += recval.recline3 + "\n";
        }
        header += "\n" + esc_bold_off;
        return header;
    }

    function getEscReceipt(record) {
        // send cash draw command to the printer
        // header
        var cmd = getEscReceiptHeader();
        // transdetails
        cmd += esc_a_l + "Transaction Ref: " + record.ref + "\n";
        cmd += "Sale Time:       " + WPOS.util.getDateFromTimestamp(record.processdt) + "\n\n";
        // items
        var item;
        for (var i in record.items) {
            item = record.items[i];
            cmd += getEscTableRow(item.qty + " x " + item.name + " (" + WPOS.util.currencyFormat(item.unit, false, true) + ")", WPOS.util.currencyFormat(item.price, false, true), false, false);
            if (item.hasOwnProperty('mod')){
                for (var x=0; x<item.mod.items.length; x++){
                    var mod = item.mod.items[x];
                    cmd+= '    '+(mod.hasOwnProperty('qty')?((mod.qty>0?'+':'')+mod.qty+' '):'')+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+WPOS.util.currencyFormat(mod.price, false, true)+')\n';
                }
            }
        }
        cmd += '\n';
        // totals
        // subtotal
        if (Object.keys(record.taxdata).length > 0 || record.discount > 0) { // only add if discount or taxes
            cmd += getEscTableRow('Subtotal:', WPOS.util.currencyFormat(record.subtotal, false, true), true, false);
        }
        // taxes
        var taxstr;
        for (i in record.taxdata) {
            taxstr = WPOS.getTaxTable().items[i];
            taxstr = taxstr.name + ' (' + taxstr.value + '%)';
            cmd += getEscTableRow(taxstr, WPOS.util.currencyFormat(record.taxdata[i], false, true), false, false);
        }
        // discount
        cmd += (record.discount > 0 ? getEscTableRow(record.discount + '% Discount', WPOS.util.currencyFormat(Math.abs(parseFloat(record.total) - (parseFloat(record.subtotal) + parseFloat(record.tax))).toFixed(2), false, true), false, false) : '');
        // grand total
        cmd += getEscTableRow('Total (' + record.numitems + ' item' + (record.numitems > 1 ? 's)' : ')') + ':', WPOS.util.currencyFormat(record.total, false, true), true, true);
        // payments
        var paymentreceipts = '';
        var method, amount;
        for (i in record.payments) {
            item = record.payments[i];
            method = item.method;
            amount = item.amount;
            // check for extra payment data
            if (item.hasOwnProperty('paydata')) {
                // check for integrated eftpos receipts
                if (item.paydata.hasOwnProperty('customerReceipt')) {
                    paymentreceipts += item.paydata.customerReceipt + '\n';
                }
                // catch cash-outs
                if (item.paydata.hasOwnProperty('cashOut')) {
                    method = "cashout";
                    amount = (-amount).toFixed(2);
                }
            }
            cmd += getEscTableRow(WPOS.util.capFirstLetter(method) + ':', WPOS.util.currencyFormat(amount, false, true), false, false);
            if (method == 'cash') { // If cash print tender & change
                cmd += getEscTableRow('Tendered:', WPOS.util.currencyFormat(item.tender, false, true), false, false);
                cmd += getEscTableRow('Change:', WPOS.util.currencyFormat(item.change, false, true), false, false);
            }
        }
        cmd += '\n';
        // refunds
        if (record.hasOwnProperty("refunddata")) {
            cmd += esc_a_c + esc_bold_on + 'Refund' + font_reset + '\n';
            var lastrefindex = 0, lastreftime = 0;
            for (i in record.refunddata) {
                // find last refund for integrated eftpos receipt
                if (record.refunddata[i].processdt > lastreftime) {
                    lastrefindex = i;
                }
                cmd += getEscTableRow((WPOS.util.getDateFromTimestamp(record.refunddata[i].processdt) + ' (' + record.refunddata[i].items.length + ' items)'), (WPOS.util.capFirstLetter(record.refunddata[i].method) + '     ' + WPOS.util.currencyFormat(record.refunddata[i].amount, false, true)), false, false);
            }
            cmd += '\n';
            // check for integrated receipt and replace if found
            if (record.refunddata[lastrefindex].hasOwnProperty('paydata') && record.refunddata[lastrefindex].paydata.hasOwnProperty('customerReceipt')) {
                paymentreceipts = record.refunddata[lastrefindex].paydata.customerReceipt + '\n';
            }
        }
        // void sale
        if (record.hasOwnProperty("voiddata")) {
            cmd += esc_a_c + esc_double + esc_bold_on + 'VOID TRANSACTION' + font_reset + '\n';
            cmd += '\n';
        }
        // add integrated eftpos receipts
        if (paymentreceipts != '' && WPOS.getLocalConfig().eftpos.receipts) cmd += esc_a_c + paymentreceipts;
        // footer
        cmd += esc_bold_on + esc_a_c + WPOS.getConfigTable().pos.recfooter + font_reset + "\r";

        return cmd;
    }

    function getEscOrderTicket(record, orderid, flagtext) {
        console.log(record);
        console.log(orderid);
        // send cash draw command to the printer
        // header
        var bizname = WPOS.getConfigTable().general.bizname;
        // header
        var cmd = esc_init + esc_a_c + esc_double + bizname + "\n" + font_reset + '\n';
        // transdetails
        var order = record.orderdata[orderid];
        cmd += esc_a_l +"Transaction Ref: " + record.ref + "\n";
        cmd +=          "Order Time:      " + WPOS.util.getDateFromTimestamp(order.processdt) + "\n";
        if (order.hasOwnProperty('moddt'))
            cmd +=      "Modified Time:   " + WPOS.util.getDateFromTimestamp(order.moddt) + "\n\n";
        cmd +=       esc_a_c + esc_double+ 'Order #' +order.id + (flagtext?'\n'+flagtext:'') + font_reset + "\n";
        cmd +=       esc_a_c + esc_bold_on + (order.tablenum>0?"Table #: " + order.tablenum:"Take Away") +  font_reset + "\n\n";
        // items
        var item;
        for (var i in record.orderdata[orderid].items) {
            item = record.items[record.orderdata[orderid].items[i]];
            cmd += getEscTableRow(item.qty + " x " + item.name + " (" + WPOS.util.currencyFormat(item.unit, false, true) + ")", WPOS.util.currencyFormat(item.price, false, true), false, false);
            if (item.hasOwnProperty('mod')) {
                for (var x = 0; x < item.mod.items.length; x++) {
                    var mod = item.mod.items[x];
                    cmd += '    ' + (mod.hasOwnProperty('qty') ? ((mod.qty > 0 ? '+' : '') + mod.qty + ' ') : '') + mod.name + (mod.hasOwnProperty('value') ? ': ' + mod.value : '') + ' (' + WPOS.util.currencyFormat(mod.price, false, true) + ')\n';
                }
            }
        }
        cmd += '\n';

        return cmd;
    }

    function getEscTableRow(leftstr, rightstr, bold, underline) {
        var pad = "";
        if (leftstr.length + rightstr.length > 48) {
            var clip = (leftstr.length + rightstr) - 48; // get amount to clip
            leftstr = leftstr.substring(0, (leftstr.length - (clip + 3)));
            pad = ".. ";
        } else {
            var num = 48 - (leftstr.length + rightstr.length);
            for (num; num > 0; num--) {
                pad += " ";
            }
        }
        var row = leftstr + pad + (underline ? esc_ul_on : '') + rightstr + (underline ? font_reset : '') + "\n";
        if (bold) { // format row
            row = esc_bold_on + row + esc_bold_off;
        }
        return row;
    }

    function getESCPImageString(url, callback) {
        img = new Image();
        img.onload = function () {
            // Create an empty canvas element
            //var canvas = document.createElement("canvas");
            var canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            // Copy the image contents to the canvas
            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0);
            // get image slices and append commands
            var bytedata = esc_init + esc_a_c + getESCPImageSlices(ctx, canvas) + font_reset;
            //alert(bytedata);
            callback(bytedata);
        };
        img.src = url;
    }

    function getESCPImageSlices(context, canvas) {
        var width = canvas.width;
        var height = canvas.height;
        var nL = Math.round(width % 256);
        var nH = Math.round(height / 256);
        var dotDensity = 33;
        // read each pixel and put into a boolean array
        var imageData = context.getImageData(0, 0, width, height);
        imageData = imageData.data;
        // create a boolean array of pixels
        var pixArr = [];
        for (var pix = 0; pix < imageData.length; pix += 4) {
            pixArr.push((imageData[pix] == 0));
        }
        // create the byte array
        var final = [];
        // this function adds bytes to the array
        function appendBytes() {
            for (var i = 0; i < arguments.length; i++) {
                final.push(arguments[i]);
            }
        }
        // Set the line spacing to 24 dots, the height of each "stripe" of the image that we're drawing.
        appendBytes(0x1B, 0x33, 24);
        // Starting from x = 0, read 24 bits down. The offset variable keeps track of our global 'y'position in the image.
        // keep making these 24-dot stripes until we've executed past the height of the bitmap.
        var offset = 0;
        while (offset < height) {
            // append the ESCP bit image command
            appendBytes(0x1B, 0x2A, dotDensity, nL, nH);
            for (var x = 0; x < width; ++x) {
                // Remember, 24 dots = 24 bits = 3 bytes. The 'k' variable keeps track of which of those three bytes that we're currently scribbling into.
                for (var k = 0; k < 3; ++k) {
                    var slice = 0;
                    // The 'b' variable keeps track of which bit in the byte we're recording.
                    for (var b = 0; b < 8; ++b) {
                        // Calculate the y position that we're currently trying to draw.
                        var y = (((offset / 8) + k) * 8) + b;
                        // Calculate the location of the pixel we want in the bit array. It'll be at (y * width) + x.
                        var i = (y * width) + x;
                        // If the image (or this stripe of the image)
                        // is shorter than 24 dots, pad with zero.
                        var bit;
                        if (pixArr.hasOwnProperty(i)) bit = pixArr[i] ? 0x01 : 0x00; else bit = 0x00;
                        // Finally, store our bit in the byte that we're currently scribbling to. Our current 'b' is actually the exact
                        // opposite of where we want it to be in the byte, so subtract it from 7, shift our bit into place in a temp
                        // byte, and OR it with the target byte to get it into the final byte.
                        slice |= bit << (7 - b);    // shift bit and record byte
                    }
                    // Phew! Write the damn byte to the buffer
                    appendBytes(slice);
                }
            }
            // We're done with this 24-dot high pass. Render a newline to bump the print head down to the next line and keep on trucking.
            offset += 24;
            appendBytes(10);
        }
        // Restore the line spacing to the default of 30 dots.
        appendBytes(0x1B, 0x33, 30);
        // convert the array into a bytestring and return
        final = WPOS.util.ArrayToByteStr(final);

        return final;
    }

    function getHtmlReceipt(record) {
        var bizname = WPOS.getConfigTable().general.bizname;
        var recval = WPOS.getConfigTable().pos;
        // logo and header
        var html = '<div style="padding-left: 5px; padding-right: 5px; text-align: center;"><img style="width: 260px;" src="' + recval.recemaillogo + '"/><br/>';
        html += '<h3 style="text-align: center; margin: 5px;">' + bizname + '</h3>';
        html += '<p style="text-align: center"><strong>' + recval.recline2 + '</strong>';
        if (recval.recline3 != "") {
            html += '<br/><strong style="text-align: center">' + recval.recline3 + '</strong>';
        }
        html += '</p>';
        // body
        html += '<p style="padding-top: 5px;">Transaction Ref:&nbsp;&nbsp;' + record.ref + '<br/>';
        html += 'Sale Time:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' + WPOS.util.getDateFromTimestamp(record.processdt) + '</p>';
        // items
        html += '<table style="width: 100%; margin-bottom: 4px; font-size: 13px;">';
        var item;
        for (var i in record.items) {
            item = record.items[i];
            // item mod details
            var modStr = "";
            if (item.hasOwnProperty('mod')){
                for (var x=0; x<item.mod.items.length; x++){
                    var mod = item.mod.items[x];
                    modStr+= '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+(mod.hasOwnProperty('qty')?((mod.qty>0?'+ ':'')+mod.qty+' '):'')+mod.name+(mod.hasOwnProperty('value')?': '+mod.value:'')+' ('+WPOS.util.currencyFormat(mod.price)+')';
                }
            }
            html += '<tr><td>' + item.qty + " x " + item.name + " (" + WPOS.util.currencyFormat(item.unit) + ")" + modStr + '</td><td style="text-align: right; vertical-align: top;">' + WPOS.util.currencyFormat(item.price) + '</td></tr>';
        }
        html += '<tr style="height: 5px;"><td></td><td></td></tr>';
        // totals
        // subtotal
        if (Object.keys(record.taxdata).length > 0 || record.discount > 0) { // only add if discount or taxes
            html += '<tr><td><b>Subtotal: </b></td><td style="text-align: right;"><b style="text-decoration: overline;">' + WPOS.util.currencyFormat(record.subtotal) + '</b></td></tr>';
        }
        // taxes
        var taxstr;
        for (i in record.taxdata) {
            taxstr = WPOS.getTaxTable().items[i];
            taxstr = taxstr.name + ' (' + taxstr.value + '%)';
            html += '<tr><td>' + taxstr + ':</td><td style="text-align: right;">' + WPOS.util.currencyFormat(record.taxdata[i]) + '</td></tr>';
        }
        // discount
        html += (record.discount > 0 ? '<tr><td>' + record.discount + '% Discount</td><td style="text-align: right;">' + WPOS.util.currencyFormat(Math.abs(parseFloat(record.total) - (parseFloat(record.subtotal) + parseFloat(record.tax))).toFixed(2)) + '</td></tr>' : '');
        // grand total
        html += '<tr><td><b>Total (' + record.numitems + ' items): </b></td><td style="text-align: right;"><b style="text-decoration: overline;">' + WPOS.util.currencyFormat(record.total) + '</b></td></tr>';
        html += '<tr style="height: 2px;"><td></td><td></td></tr>';
        // payments
        var paymentreceipts = '';
        var method, amount;
        for (i in record.payments) {
            item = record.payments[i];
            method = item.method;
            amount = item.amount;
            // check for special payment values
            if (item.hasOwnProperty('paydata')) {
                // check for integrated eftpos receipts
                if (item.paydata.hasOwnProperty('customerReceipt')) {
                    paymentreceipts += item.paydata.customerReceipt;
                }
                // catch cash-outs
                if (item.paydata.hasOwnProperty('cashOut')) {
                    method = "cashout";
                    amount = (-amount).toFixed(2);
                }
            }
            html += '<tr><td>' + WPOS.util.capFirstLetter(method) + ':</td><td style="text-align: right;">' + WPOS.util.currencyFormat(amount) + '</td></tr>';
            if (method == 'cash') {
                // If cash print tender & change.
                html += '<tr><td>Tendered:</td><td style="text-align: right;">' + WPOS.util.currencyFormat(item.tender) + '</td></tr>';
                html += '<tr><td>Change:</td><td style="text-align: right;">' + WPOS.util.currencyFormat(item.change) + '</td></tr>';
            }

        }
        html += '</table>';
        // refunds
        if (record.hasOwnProperty("refunddata")) {
            html += '<p style="margin-top: 0px; margin-bottom: 5px; font-size: 13px;"><strong>Refund</strong></p><table style="width: 100%; font-size: 13px;">';
            var lastrefindex = 0, lastreftime = 0;
            for (i in record.refunddata) {
                // find last refund for integrated eftpos receipt
                if (record.refunddata[i].processdt > lastreftime) {
                    lastrefindex = i;
                }
                html += '<tr><td>' + WPOS.util.getDateFromTimestamp(record.refunddata[i].processdt) + ' (' + record.refunddata[i].items.length + ' items)</p></td><td><p style="font-size: 13px; display: inline-block;">' + WPOS.util.capFirstLetter(record.refunddata[i].method) + '</p><p style="font-size: 13px; display: inline-block; float: right;">' + WPOS.util.currencyFormat(record.refunddata[i].amount) + '</td></tr>';
            }
            html += '</table>';
            // check for integrated receipt and replace if found
            if (record.refunddata[lastrefindex].hasOwnProperty('paydata') && record.refunddata[lastrefindex].paydata.hasOwnProperty('customerReceipt')) {
                paymentreceipts = record.refunddata[lastrefindex].paydata.customerReceipt;
            }
        }
        // void sale
        if (record.hasOwnProperty("voiddata")) {
            html += '<h2 style="text-align: center; color: #dc322f; margin-top: 5px;">VOID TRANSACTION</h2>';
        }
        // add integrated eftpos receipts
        if (paymentreceipts != '' && WPOS.getLocalConfig().eftpos.receipts) html += '<pre style="text-align: center; background-color: white;">' + paymentreceipts + '</pre>';
        // footer
        html += '<p style="text-align: center;"><strong>' + recval.recfooter + '</strong><br/>';
        if (recval.recqrcode != "") {
            html += '<img style="text-align: center;" height="99" src="/docs/qrcode.png"/>';
        }
        html += '</p></div>';
        return html;
    }

    // Browser printing methods
    function browserPrintHtml(html, isreport) {
        var printw;
        if (isreport == true) {
            printw = window.open('', 'Wpos Report', 'height=800,width=600,scrollbars=yes');
            printw.document.write('<html><head><title>Wpos Report</title>');
        } else {
            printw = window.open('', 'Wpos Receipt', 'height=600,width=300,scrollbars=yes');
            printw.document.write('<html><head><title>Wpos Receipt</title>');
        }
        printw.document.write('<link media="all" href="/admin/assets/css/bootstrap.min.css" rel="stylesheet"/><link media="all" rel="stylesheet" href="/admin/assets/css/font-awesome.min.css"/><link media="all" rel="stylesheet" href="admin/assets/css/ace-fonts.css"/><link media="all" rel="stylesheet" href="admin/assets/css/ace.min.css"/>');
        printw.document.write('</head><body style="background-color: #FFFFFF;">');
        printw.document.write(html);
        printw.document.write('</body></html>');
        printw.document.close();

        // close only after printed, This is only implemented properly in firefox but can be used for others soon (part of html5 spec)
        //if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1)
        //printw.addEventListener('afterprint', function(e){ printw.close(); });

        printw.focus();
        printw.print();
    }
}
