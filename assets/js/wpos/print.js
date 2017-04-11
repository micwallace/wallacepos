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
    var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
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
            cutter:"gs_full",
            feed:4
        },
        global: {
            recask:"email",
            cashdraw: false,
            serviceip: "127.0.0.1",
            serviceport: 8080,
            escpreceiptmode: 'text',
            alt_charset: 'pc864',
            alt_codepage: 22,
            rec_language: 'primary',
            rec_orientation: 'ltr',
            currency_override: false,
            currency_codepage: 0,
            currency_codes: '',
            rectemplate: '',
            invtemplate: '',
            printinv: false,
            printers: {}
        }
    };

    this.getGlobalPrintSetting = function(setting){
        return getGlobalPrintSetting(setting);
    };

    function getGlobalPrintSetting(setting){
        if (curset && curset.hasOwnProperty(setting))
            return curset[setting];
        return defaultsettings.global[setting];
    }

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

            //if (!curset.printers.hasOwnProperty('bar'))
               // curset.printers['bar'] = defaultsettings.printer;
        }
        WPOS.util.reloadPrintCurrencySymbol();
    }

    function loadPrintSettings(loaddefaults) {
        if (loaddefaults)
            loadDefaultSettings();
        //deploy qz if not deployed and print method is qz.
        if (doesAnyPrinterHave('method', 'qz')) {
            alert("QZ-Print integration is no longer available, switch to the new webprint applet");
        } else if (doesAnyPrinterHave('method', 'wp') || doesAnyPrinterHave('method', 'ht')) {
            var receiptmethod = curset.printers['receipts'].method;
            if ((receiptmethod=="wp" || receiptmethod=="ht") || WPOS.isOrderTerminal()) {
                if (!wpdeployed) {
                    if (receiptmethod=="ht")
                        WPOS.print.setPrintSetting('receipts', 'method', 'wp');
                    deployRelayApps();
                }
                $("#printstat").show();
            } else {
                $("#printstat").hide();
            }
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
        if (WPOS.util.mobile && !WPOS.util.isandroid) {
            $(".wp-option").prop("disabled", true);
        }
        if (WPOS.util.isandroid){
            $('.psetting_type option[value="serial"]').prop("disabled", true);
        }
    }

    function deployRelayApps() {
        webprint = new WebPrint(true, {
            relayHost: getGlobalPrintSetting('serviceip'),
            relayPort: getGlobalPrintSetting('serviceport'),
            listPortsCallback: WPOS.print.populatePortsList,
            listPrinterCallback: WPOS.print.populatePrintersList,
            readyCallback: WPOS.print.printAppletReady
        });
        wpdeployed = true;
    }

    function setHtmlValues() {
        disableUnsupportedMethods();
        for (var i in curset.printers){
            var printer = curset.printers[i];
            var id = "#printsettings_"+i;
            //console.log(printer);
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
                $(id+" .escpoptions").show();
                $(id+" .psetting_cutter").val(getPrintSetting(i, 'cutter'));
                $(id+" .psetting_feed").val(getPrintSetting(i, 'feed'));
            } else {
                // browser printing, hide all options
                $(id+" .advprintoptions").hide();
                $(id+" .printoptions").hide();
                $(id+" .printserviceoptions").hide();
                $(id+" .escpoptions").hide();
            }
            $(id+" .psetting_method").val(printer.method);
        }
        if (curset.printers['receipts'].method == "br"){
            $(".broptions").show();

        } else {
            $(".broptions").hide();
        }
        $("#cashdraw").prop("checked", curset.cashdraw);
        $("#recask").val(curset.recask);
        $("#printinv").prop("checked", getGlobalPrintSetting('printinv'));
        var escpmode = getGlobalPrintSetting('escpreceiptmode');
        $("#escpreceiptmode").val(escpmode);
        $("#alt_charset").val(getGlobalPrintSetting('alt_charset'));
        $("#alt_codepage").val(getGlobalPrintSetting('alt_codepage'));
        $("#rec_language").val(getGlobalPrintSetting('rec_language'));
        $("#rec_orientation").val(getGlobalPrintSetting('rec_orientation'));
        if (getPrintSetting('receipts', 'method')!="br" ) {
            if (escpmode == "text") {
                $('.escptextmodeoptions').show();
                $('#rectemplatefield').hide();
            } else {
                $('#rectemplatefield').show();
                $('.escptextmodeoptions').hide();
            }
        } else {
            $('#rectemplatefield').show();
            $('.escptextmodeoptions').hide();
        }
        // populate template fields
        var rectemplates = $("#rectemplate");
        var invtemplates = $("#invtemplate");
        rectemplates.html('');
        invtemplates.html('');
        rectemplates.append('<option value="">Use Global Setting</option>');
        invtemplates.append('<option value="">Use Global Setting</option>');
        var templates = WPOS.getConfigTable().templates;
        var html;
        for (var t in templates){
            html = '<option value="'+t+'">'+templates[t].name+'</option>';
            if (templates[t].type=="receipt"){
                rectemplates.append(html);
            } else {
                invtemplates.append(html);
            }
        }
        rectemplates.val(curset.rectemplate);
        invtemplates.val(curset.invtemplate);
        // show service options if needed
        if (doesAnyPrinterHave('method', 'wp') || doesAnyPrinterHave('method', 'ht')) {
            $(".printserviceoptions").show();
            $(".serviceip").val(curset.serviceip);
            $(".serviceport").val(curset.serviceport);
        } else {
            $(".printserviceoptions").hide();
        }
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
        // update escp mode fields
        if (key=="escpreceiptmode"){
            if (value=="text"){
                $('.escptextmodeoptions').show();
                $('#rectemplatefield').hide();
            } else {
                $('#rectemplatefield').show();
                $('.escptextmodeoptions').hide();
            }
        }
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
                browserPrintHtml($("#reportcontain").html(), 'WallacePOS Report', 600, 800);
                break;
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                break;
            case "wp":
                html = '<html><head><title>Wpos Report</title><link media="all" href="/assets/css/bootstrap.min.css" rel="stylesheet"/><link media="all" rel="stylesheet" href="/assets/css/font-awesome.min.css"/><link media="all" rel="stylesheet" href="/assets/css/ace-fonts.css"/><link media="all" rel="stylesheet" href="/assets/css/ace.min.css"/></head><body style="background-color: #FFFFFF;">' + $("#reportcontain").html() + '</body></html>';
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
                browserPrintHtml("<pre style='text-align: center; background-color: white;'>" + text + "</pre>", 'WallacePOS Receipt', 310, 600);
                return true;
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "ht":
            case "wp":
                sendESCPPrintData(printer, esc_init + esc_a_c + text + getFeedAndCutCommands(printer));
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
                if (curset.printinv) {
                    browserPrintHtml(getHtmlReceipt(record, false, true), 'WallacePOS Invoice', 600, 800);
                } else {
                    browserPrintHtml(getHtmlReceipt(record, false), 'WallacePOS Receipt', 310, 600);
                }
                return true;
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "ht":
            case "wp":
                if (getGlobalPrintSetting('escpreceiptmode')=='text') {
                    var data = getEscReceipt(record);
                    printESCPReceipt(data);
                } else {
                    // bitmap mode printing
                    var html = getHtmlReceipt(record, true);
                    getESCPHtmlString(html, printESCPReceipt);
                }
                return true;

            default :
                return false;
        }
    }

    function printESCPReceipt(data){
        if (WPOS.getConfigTable().pos.recprintlogo == true) {
            getESCPImageString(window.location.protocol + "//" + document.location.hostname + WPOS.getConfigTable().pos.reclogo, function (imgdata) {
                appendQrcode("receipts", imgdata + data);
            });
        } else {
            appendQrcode("receipts", data);
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
                sendESCPPrintData(printer, data + getFeedAndCutCommands(printer));
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
        var data = getEscReceiptHeader() + getFeedAndCutCommands(printer);
        getESCPImageString(window.location.protocol + "//" + document.location.hostname + WPOS.getConfigTable().pos.reclogo, function (imgdata) {
            sendESCPPrintData(printer, imgdata + data);
        });
    }

    this.printQrCode = function () {
        appendQrcode("receipts", esc_init);
    };

    function appendQrcode(printer, data) {
        if (WPOS.getConfigTable().pos.recqrcode != "") {
            getESCPImageString(window.location.protocol + "//" + document.location.hostname + "/docs/qrcode.png", function (imgdata) {
                sendESCPPrintData(printer, data + imgdata + getFeedAndCutCommands(printer));
            });
        } else {
            sendESCPPrintData(printer, data + "\n" + getFeedAndCutCommands(printer));
        }
    }

    function getFeedAndCutCommands(printer){
        var cmd = new Array(getPrintSetting(printer, "feed")+1);
        cmd = cmd.join("\n");
        switch(getPrintSetting(printer, "cutter")){
            case "gs_full":
                cmd += gs_full_cut;
                break;
            case "gs_partial":
                cmd += gs_part_cut;
                break;
            case "esc_full":
                cmd += esc_full_cut;
                break;
            case "esc_partial":
                cmd += esc_part_cut;
                break;
        }
        return cmd + "\r";
    }

    function sendESCPPrintData(printer, data) {
        var method = getPrintSetting(printer, 'method');
        switch (method) {
            case "qz":
                alert("QZ-Print integration is no longer available, switch to the new webprint applet");
                return false;
            case "wp":
            case "ht":
                switch(getPrintSetting(printer, 'type')){
                    case "serial":
                        webprint.printSerial(data, getPrintSetting(printer, 'port'));
                        return true;
                    case "raw":
                        webprint.printRaw(data, getPrintSetting(printer, 'printer'));
                        return true;
                    case "tcp":
                        webprint.printTcp(data, getPrintSetting(printer, 'printerip')+":"+getPrintSetting(printer, 'printerport'));
                        return true;
                }
                return false;
        }
        return false;
    }

    // ESC/P receipt generation
    var esc_init = "\x1B" + "\x40"; // initialize printer
    var esc_p = "\x1B" + "\x70" + "\x30"; // open drawer
    var esc_full_cut = "\x1B" + "\x69"; // esc obsolute full (one point left) cut
    var esc_part_cut = "\x1B" + "\x6D"; // esc obsolute partial (three points left) cut
    var gs_full_cut = "\x1D" + "\x56" + "\x30"; // cut paper
    var gs_part_cut = "\x1D" + "\x56" + "\x31"; // partial cut paper
    var esc_a_l = "\x1B" + "\x61" + "\x30"; // align left
    var esc_a_c = "\x1B" + "\x61" + "\x31"; // align center
    var esc_a_r = "\x1B" + "\x61" + "\x32"; // align right
    var esc_double = "\x1B" + "\x21" + "\x31"; // heading
    var font_reset = "\x1B" + "\x21" + "\x02"; // styles off
    var esc_ul_on = "\x1B" + "\x2D" + "\x31"; // underline on
    var esc_ul_off = "\x1B" + "\x2D" + "\x30"; // underline off
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

    var ltr = true;
    var lang = "primary";
    var altlabels;
    function getEscReceipt(record) {
        ltr = getGlobalPrintSetting('rec_orientation')=="ltr";
        lang = getGlobalPrintSetting('rec_language');
        altlabels = WPOS.getConfigTable()['general'].altlabels;
        // header
        var cmd = getEscReceiptHeader();
        // transdetails
        cmd += (ltr ? esc_a_l : esc_a_r);
        cmd += getEscTableRow(formatLabel(translateLabel("Transaction Ref"), true, 1), record.ref, false, false, false);
        if (record.hasOwnProperty('id') && WPOS.getConfigTable().pos.recprintid)
            cmd += getEscTableRow(formatLabel(translateLabel("Transaction ID"), true, 2), record.id, false, false, false);
        cmd += getEscTableRow(formatLabel(translateLabel("Sale Time"), true, 7), WPOS.util.getDateFromTimestamp(record.processdt), false, false, false) + "\n";
        // items
        var item;
        for (var i in record.items) {
            item = record.items[i];
            var itemlabel;
            var itemname = (lang == "alternate" ? convertUnicodeCharacters(item.alt_name, getGlobalPrintSetting('alt_charset'), getGlobalPrintSetting('alt_codepage')) : item.name);
            if (ltr){
                itemlabel = item.qty + " x " + itemname + " (" + WPOS.util.currencyFormat(item.unit, false, true) + ")";
            } else {
                itemlabel = "(" + WPOS.util.currencyFormat(item.unit, false, true) + ")" + itemname + " x " + item.qty;
            }

            cmd += getEscTableRow(itemlabel, WPOS.util.currencyFormat(item.price, false, true), false, false, true);
            if (lang=="mixed" && item.alt_name!=""){
                cmd += (ltr?'    ':'') + convertUnicodeCharacters(item.alt_name, getGlobalPrintSetting('alt_charset'), getGlobalPrintSetting('alt_codepage')) + (!ltr?'    ':'') + "\n";
            }
            if (item.desc!="" && WPOS.getConfigTable().pos.hasOwnProperty('recprintdesc') && WPOS.getConfigTable().pos.recprintdesc){
                cmd += (ltr?'    ':'') + convertUnicodeCharacters(item.desc, getGlobalPrintSetting('alt_charset'), getGlobalPrintSetting('alt_codepage')) + (!ltr?'    ':'') + "\n";
            }
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
            cmd += getEscTableRow(formatLabel(translateLabel('Subtotal'), true, 1), WPOS.util.currencyFormat(record.subtotal, false, true), true, false, true);
        }
        // taxes
        var taxstr;
        for (i in record.taxdata) {
            taxstr = WPOS.getTaxTable().items[i];
            taxstr = taxstr.name + ' (' + taxstr.value + '%)';
            cmd += getEscTableRow(formatLabel(taxstr, true, 1), WPOS.util.currencyFormat(record.taxdata[i], false, true), false, false, true);
        }
        // discount
        cmd += (record.discount > 0 ? getEscTableRow(formatLabel(record.discount + '% ' + translateLabel('Discount'), true, 1), WPOS.util.currencyFormat(Math.abs(parseFloat(record.total) - (parseFloat(record.subtotal) + parseFloat(record.tax))).toFixed(2), false, true), false, false, true) : '');
        // grand total
        cmd += getEscTableRow(formatLabel(translateLabel('Total') + ' (' + record.numitems + ' ' + translateLabel('item' + (record.numitems > 1 ? 's' : '')) + ')', true, 1), WPOS.util.currencyFormat(record.total, false, true), true, true, true);
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
            cmd += getEscTableRow(formatLabel(translateLabel(WPOS.util.capFirstLetter(method)), true, 1), WPOS.util.currencyFormat(amount, false, true), false, false, true);
            if (method == 'cash') { // If cash print tender & change
                cmd += getEscTableRow(formatLabel(translateLabel('Tendered'), true, 1), WPOS.util.currencyFormat(item.tender, false, true), false, false, true);
                cmd += getEscTableRow(formatLabel(translateLabel('Change'), true, 1), WPOS.util.currencyFormat(item.change, false, true), false, false, true);
            }
        }
        cmd += '\n';
        // refunds
        if (record.hasOwnProperty("refunddata")) {
            cmd += esc_a_c + esc_bold_on + translateLabel('Refund') + font_reset + '\n';
            var lastrefindex = 0, lastreftime = 0;
            for (i in record.refunddata) {
                // find last refund for integrated eftpos receipt
                if (record.refunddata[i].processdt > lastreftime) {
                    lastrefindex = i;
                }
                cmd += getEscTableRow(
                        formatLabel((WPOS.util.getDateFromTimestamp(record.refunddata[i].processdt) + ' (' + record.refunddata[i].items.length + ' ' + translateLabel('items') + ')'), true, 1),
                        translateLabel(WPOS.util.capFirstLetter(record.refunddata[i].method) + '     ' + WPOS.util.currencyFormat(record.refunddata[i].amount, false, true)), false, false, true);
            }
            cmd += '\n';
            // check for integrated receipt and replace if found
            if (record.refunddata[lastrefindex].hasOwnProperty('paydata') && record.refunddata[lastrefindex].paydata.hasOwnProperty('customerReceipt')) {
                paymentreceipts = record.refunddata[lastrefindex].paydata.customerReceipt + '\n';
            }
        }
        // void sale
        if (record.hasOwnProperty("voiddata")) {
            cmd += esc_a_c + esc_double + esc_bold_on + translateLabel('VOID TRANSACTION') + font_reset + '\n';
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

    function getEscTableRow(leftstr, rightstr, bold, underline, stretch) {
        var pad = "";
        // adjust for bytes of escp commands that set the character set
        var llength = (leftstr.indexOf("\x1B\x74")!==-1) ? leftstr.length - (3*(leftstr.match(/\x1B\x74/g) || []).length) : leftstr.length;
        var rlength = (rightstr.indexOf("\x1B\x74")!==-1) ? rightstr.length - (3*(rightstr.match(/\x1B\x74/g) || []).length) : rightstr.length;
        if (llength + rlength > 48) {
            var clip = (llength + rlength) - 48; // get amount to clip
            leftstr = leftstr.substring(0, (llength - (clip + 3)));
            pad = "...";
        } else {
            var num = 48 - (llength + rlength);
            pad = new Array(num+1).join(" ");
        }
        var row;
        if (ltr){
            row = leftstr + (stretch?pad:'') + (underline ? esc_ul_on : '') + rightstr + (underline ? esc_ul_off : '') + (!stretch?pad:'') + "\n";
        } else {
            row = (!stretch?pad:'') + (underline ? esc_ul_on : '') + rightstr + (underline ? esc_ul_off : '') + (stretch?pad:'') + leftstr + "\n";
        }
        if (bold) { // format row
            row = esc_bold_on + row + esc_bold_off;
        }
        return row;
    }

    function translateLabel(label){
        if (lang!="alternate")
            return label;

        var key = label.replace(' ', '-').toLowerCase();
        if (altlabels.hasOwnProperty(key))
            return convertUnicodeCharacters(altlabels[key], getGlobalPrintSetting('alt_charset'), getGlobalPrintSetting('alt_codepage'));

        return label;
    }

    function formatLabel(label, addcolon, pad){
        // orientate and pad label
        pad = new Array(pad+1).join(" ");
        if (ltr){
            return label + (addcolon?':':'') + pad;
        } else {
            return pad + (addcolon?':':'') + label;
        }
    }

    function getESCPImageString(url, callback) {
        img = new Image();
        img.onload = function () {
            // Create an empty canvas element
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

    this.testBitmapReceipt = function(ref){
        var record = WPOS.trans.getTransactionRecord(ref!=null?ref:"1449222132735-1-5125");
        var html = getHtmlReceipt(record, true);
        getESCPHtmlString(html, function(data){
            sendESCPPrintData('receipts', data + getFeedAndCutCommands(printer));
        });
    };

    function getHtmlHeight(html){
        var tempId = 'tmp-'+Math.floor(Math.random()*99999);//generating unique id just in case
        var frame = $('<iframe id="frame-'+tempId+'"/>')
            .appendTo(document.body)
            .css('left','-10000em');

        var frameDoc = frame[0].contentDocument || frame[0].contentWindow.document;
        frameDoc.write(html);
        frameDoc.close();

        var body = frame.contents().find('body div');
        var h = body.height();
        frame.remove();
        return h;
    }

    function getESCPHtmlString(html, callback){
        var canvas = document.getElementById('receipt_canvas');
        var ctx = canvas.getContext('2d');
        var height = getHtmlHeight(html);
        console.log(height);
        height = (Math.round(height/24)*24)+128; // height apparently has to be a multiple of 64
        console.log(height);
        canvas.height = height;
        var data = '<svg xmlns="http://www.w3.org/2000/svg" width="576" height="'+height+'">' +
                        '<foreignObject width="100%" height="100%">' +
                            html +
                        '</foreignObject>' +
                    '</svg>';

        var img = new Image();
        var url;
        if (is_chrome){
            url = "data:image/svg+xml;utf8," + data;
        } else {
            var DOMURL = window.URL || window.webkitURL || window;
            var svg = new Blob([data], {type: 'image/svg+xml;charset=utf-8'});
            url = DOMURL.createObjectURL(svg);
        }

        // fill background
        ctx.rect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle="white";
        ctx.fill();

        // draw image when loaded
        img.onload = function () {
            ctx.drawImage(img, 0, 0);
            if (!is_chrome)
                DOMURL.revokeObjectURL(url);
            var bytedata = esc_init + getESCPImageSlices(ctx, canvas) + font_reset;
            callback(bytedata);
        };
        img.src = url;
    }

    // used to print bitmaps using "ESC *" command
    function getESCPImageSlices(context, canvas) {
        var width = canvas.width;
        var height = canvas.height;
        var nL = Math.round(width % 256);
        var nH = Math.round(width / 256);
        var dotDensity = 33;
        var densityRows = dotDensity==33 ? 3 : 1;
        var threshhold = 127;
        // read each pixel and put into a boolean array
        var imageData = context.getImageData(0, 0, width, height);
        imageData = imageData.data;
        // create a boolean array of pixels
        var pixArr = [];
        for (var pix = 0; pix < imageData.length; pix += 4) {
            pixArr.push(((imageData[pix] < threshhold) || (imageData[pix+1] < threshhold) || (imageData[pix+2] < threshhold)));
        }
        // create the byte array
        var final = [];
        // this function adds bytes to the array
        function appendBytes() {
            for (var i = 0; i < arguments.length; i++) {
                final.push(arguments[i]);
            }
        }
        // Starting from x = 0, read 24 bits down. The offset variable keeps track of our global 'y'position in the image.
        // keep making these 24-dot stripes until we've executed past the height of the bitmap.
        var offset = 0;
        while (offset < height) {
            // append the ESCP bit image command
            appendBytes(0x1B, 0x2A, dotDensity, nL, nH);
            for (var x = 0; x < width; ++x) {
                // Remember, 24 dots = 24 bits = 3 bytes. The 'k' variable keeps track of which of those three bytes that we're currently scribbling into.
                for (var k = 0; k < densityRows; ++k) {
                    var slice = 0;
                    // The 'b' variable keeps track of which bit in the byte we're recording.
                    for (var b = 0; b < 8; ++b) {
                        // Calculate the y position that we're currently trying to draw.
                        var y = densityRows==3 ? (((offset / 8) + k) * 8) + b : offset + b;
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
            appendBytes(0x1B, 0x4A, densityRows*8);
        }
        // convert the array into a bytestring and return
        final = WPOS.util.ArrayToByteStr(final);

        return final;
    }

    // used to print bitmaps using "GS v 0" command
    // This is the best ESC/P manual I have found explaining the difference: https://www.spansion.com/downloads/MB9B310_AN706-00093.pdf
    // It's a nicer implementation than ESC * but for some reason there's an issue printing graphics longer than ~576
    function getESCPImageSlices2(context, canvas){
        var width = canvas.width;
        var widthBytes = Math.round((width)/8);
        var widthBits = widthBytes*8;
        var height = canvas.height;
        var bytes = Math.round(((widthBits) * height) / 8);
        var xL = Math.round(widthBytes % 256); // width in bytes
        var xH = Math.max(0, Math.min(255, Math.round(widthBytes / 256)));
        var yL = Math.round(height % 256); // height in bits (dots)
        var yH = Math.max(0, Math.min(8, Math.round(height / 256)));
        var modeDensity = 0x00;
        var threshold = 127;
        console.log(xL+" "+xH+" "+yL+" "+yH);
        // read each pixel and put into a boolean array
        var imageData = context.getImageData(0, 0, width, height);
        imageData = imageData.data;
        // create a boolean array of pixels
        var pixArr = [];
        for (var pix = 0; pix < imageData.length; pix += 4) {
            pixArr.push((imageData[pix] < threshold));
        }
        // create the byte array
        var final = [];
        // this function adds bytes to the array
        function appendBytes() {
            for (var i = 0; i < arguments.length; i++) {
                final.push(arguments[i]);
            }
        }
        // page mode
        //appendBytes(0x1B, 0x4C);
        // Init bitmap mode: GS v 0 modeDensity xL xH yL yH
        appendBytes(0x1D, 0x76, 0x30, modeDensity, xL, xH, yL, yH);
        // variable height bitmap command
        //appendBytes(0x1D, 0x51, 0x30, modeDensity, xL, xH, yL, yH);

        //appendBytes(0x1D, 0x28, 0x4C, 0x0A, 0x00, 0x30, 0x70, 0x30, 0x01, 0x01, 0x31, xL, xH, yL, yH);
        var byteCount = 0;
        var offset = 0;
        while (offset < height) {
            for (var x = 0; x < widthBits; x+=8) {
                var slice = 0;
                // The 'b' variable keeps track of which bit in the byte we're recording.
                for (var b = 0; b < 8; ++b) {
                    var bit;
                    if (x+b>width){
                        bit = 0x00;
                    } else {
                        // Calculate the location of the pixel we want in the bit array. It'll be at (y * width) + x + b.
                        var i = (offset * width) + x + b;
                        // If the image (or this stripe of the image)
                        // is shorter than 24 dots, pad with zero.

                        if (pixArr.hasOwnProperty(i)) bit = pixArr[i] ? 0x01 : 0x00; else bit = 0x00;
                    }
                    // Finally, store our bit in the byte that we're currently scribbling to. Our current 'b' is actually the exact
                    // opposite of where we want it to be in the byte, so subtract it from 7, shift our bit into place in a temp
                    // byte, and OR it with the target byte to get it into the final byte.
                    slice |= bit << (7 - b);    // shift bit and record byte
                }
                // Phew! Write the damn byte to the buffer
                appendBytes(slice);
                byteCount++;
            }
            // Move down to the next row of pixels
            offset++;
        }
        appendBytes(0x1B, 0x4A, 2);
        /*var remainder = bytes-byteCount;
        console.log("Remainder bytes: " + remainder);
        if (remainder>0) {
            var padding = new Array(remainder).join("\x00");
            return WPOS.util.ArrayToByteStr(final)+padding;
        } else if (remainder<0){
            var finalstr = WPOS.util.ArrayToByteStr(final);
            return finalstr.substring(0, finalstr.length+remainder);
        } else {*/
            return WPOS.util.ArrayToByteStr(final);
        //}
        // print the data in page mode
        //appendBytes(0x0C);
        //return WPOS.util.ArrayToByteStr(final);
        //appendBytes(0x1D, 0x28, 0x4C, 0x02, 0x00, 0x30, 0x32);
        // convert the array into a bytestring and return

    }

    this.rasterImageTest = function(){
        var width = 480;
        var widthBytes = Math.round((width)/8);
        var widthBits = widthBytes*8;
        var height = 256;
        var bytes = Math.round(((widthBits) * height) / 8);
        var xL = Math.round(widthBytes % 256); // width in bytes
        var xH = Math.round(widthBytes / 256);
        var yL = Math.round(height % 256); // height in bits (dots)
        var yH = Math.round(height / 256);
        var modeDensity = 0x00;

        console.log(xL+" "+xH+" "+yL+" "+yH);
        // create the byte array
        var final = [];
        // this function adds bytes to the array
        function appendBytes() {
            for (var i = 0; i < arguments.length; i++) {
                final.push(arguments[i]);
            }
        }
        // page mode
        //appendBytes(0x1B, 0x4C);
        // Init bitmap mode: GS v 0 modeDensity xL xH yL yH
        appendBytes(0x1D, 0x76, 0x30, modeDensity, xL, xH, yL, yH);
        // variable height bitmap command
        //appendBytes(0x1D, 0x51, 0x30, modeDensity, xL, xH, yL, yH);

        //appendBytes(0x1D, 0x28, 0x4C, 0x0A, 0x00, 0x30, 0x70, 0x30, 0x01, 0x01, 0x31, xL, xH, yL, yH);
        var byteCount = 0;
        var offset = 0;
        while (offset < height) {
            for (var x = 0; x < widthBits; x+=8) {
                var slice = 0;
                // The 'b' variable keeps track of which bit in the byte we're recording.
                for (var b = 0; b < 8; ++b) {
                    var bit;
                    if (x+b>width){
                        bit = 0x00;
                    } else {
                        // Calculate the location of the pixel we want in the bit array. It'll be at (y * width) + x + b.
                        //var i = (offset * width) + x + b;
                        // If the image (or this stripe of the image)
                        // is shorter than 24 dots, pad with zero.
                        bit = (offset%2>0) ? 0x01 : 0x00;
                        //if (pixArr.hasOwnProperty(i)) bit = pixArr[i] ? 0x01 : 0x00; else bit = 0x00;
                    }
                    // Finally, store our bit in the byte that we're currently scribbling to. Our current 'b' is actually the exact
                    // opposite of where we want it to be in the byte, so subtract it from 7, shift our bit into place in a temp
                    // byte, and OR it with the target byte to get it into the final byte.
                    slice |= bit << (7 - b);    // shift bit and record byte
                }
                // Phew! Write the damn byte to the buffer
                appendBytes(slice);
                byteCount++;
            }
            // Move down to the next row of pixels
            offset++;
        }
        appendBytes(0x1B, 0x4A, 2);
        var remainder = bytes-byteCount;
         console.log("Remainder bytes: " + remainder);
         /*if (remainder>0) {
         var padding = new Array(remainder).join("\x00");
         return WPOS.util.ArrayToByteStr(final)+padding;
         } else if (remainder<0){
         var finalstr = WPOS.util.ArrayToByteStr(final);
         return finalstr.substring(0, finalstr.length+remainder);
         } else {*/
        final =  WPOS.util.ArrayToByteStr(final);
        sendESCPPrintData('receipts', esc_init + esc_a_c + final + getFeedAndCutCommands('receipts'));
        //}
        // print the data in page mode
        //appendBytes(0x0C);
        //return WPOS.util.ArrayToByteStr(final);
        //appendBytes(0x1D, 0x28, 0x4C, 0x02, 0x00, 0x30, 0x32);
        // convert the array into a bytestring and return
    };

    function getHtmlReceipt(record, escpprint, invoice){
        var config = WPOS.getConfigTable();
        // get the chosen template
        var tempid;
        if (invoice){
            if (curset.hasOwnProperty('invtemplate') && curset.invtemplate!="") {
                tempid = curset.invtemplate;
            } else {
                tempid = config.invoice.defaulttemplate;
            }
        } else {
            if (curset.hasOwnProperty('rectemplate') && curset.rectemplate!="") {
                tempid = curset.rectemplate;
            } else {
                tempid = config.pos.rectemplate;
            }
        }
        var template = WPOS.getConfigTable()['templates'][tempid];
        if (!template) {
            alert("Could not load template");
            return;
        }
        var temp_data = {
            sale_id: record.id,
            sale_ref: record.ref,
            sale_dt: WPOS.util.getDateFromTimestamp(record.processdt),
            sale_items: record.items,
            sale_numitems: record.numitems,
            sale_discount: parseFloat(record.discount),
            sale_discountamt: WPOS.util.currencyFormat(Math.abs(parseFloat(record.total) - (parseFloat(record.subtotal) + parseFloat(record.tax))).toFixed(2)),
            sale_subtotal: record.subtotal,
            sale_total: record.total,
            sale_void: record.hasOwnProperty('voiddata'),
            sale_hasrefunds: record.hasOwnProperty('refunddata'),
            show_subtotal: (Object.keys(record.taxdata).length > 0 || record.discount > 0),
            header_line1: config.general.bizname,
            header_line2: config.pos.recline2,
            header_line3: config.pos.recline3,
            logo_url: document.location.protocol+"//"+document.location.host+config.pos.recemaillogo,
            footer: config.pos.recfooter,
            thermalprint: escpprint,
            print_id: config.pos.recprintid,
            print_desc: config.pos.recprintdesc,
            qrcode_url: config.pos.recqrcode!=""?document.location.protocol+"//"+document.location.host+"/docs/qrcode.png":null,
            currency: function() {
                return function (text, render) {
                    return WPOS.util.currencyFormat(render(text));
                }
            }
        };
        // format tax data
        var tax;
        temp_data.sale_tax = [];
        for (var i in record.taxdata) {
            tax = WPOS.getTaxTable().items[i];
            var label = tax.name + ' (' + tax.value + '%)';
            var alttaxlabel = (tax.altname!=""?tax.altname:tax.name) + ' (' + tax.value + '%)';
            temp_data.sale_tax.push({label: label, altlabel: alttaxlabel, value: WPOS.util.currencyFormat(record.taxdata[i])});
        }
        // format payments and collect eftpos receipts
        temp_data.sale_payments = [];
        temp_data.eftpos_receipts = '';
        var item, method, amount;
        var altlabels = config.general.altlabels;
        for (i in record.payments) {
            item = record.payments[i];
            method = item.method;
            amount = item.amount;
            // check for special payment values
            if (item.hasOwnProperty('paydata')) {
                // check for integrated eftpos receipts
                if (item.paydata.hasOwnProperty('customerReceipt')) {
                    temp_data.eftpos_receipts += item.paydata.customerReceipt;
                }
                // catch cash-outs
                if (item.paydata.hasOwnProperty('cashOut')) {
                    method = "cashout";
                    amount = (-amount).toFixed(2);
                }
            }
            var altlabel = altlabels.hasOwnProperty(method)?altlabels[method]:WPOS.util.capFirstLetter(method);
            temp_data.sale_payments.push({label: WPOS.util.capFirstLetter(method), altlabel: altlabel, amount: amount});
            if (method == 'cash') {
                // If cash print tender & change.
                temp_data.sale_payments.push({label: "Tendered", altlabel: altlabels.tendered, amount: item.tender});
                temp_data.sale_payments.push({label: "Change", altlabel: altlabels.change, amount: item.change});
            }
        }
        // customer
        if (record.custid>0) {
            var customer = WPOS.getCustTable()[record.custid];
            if (customer) {
                temp_data.customer_name = customer.name;
                temp_data.customer_address = customer.address;
                temp_data.customer_suburb = customer.suburb;
                temp_data.customer_state = customer.state;
                temp_data.customer_postcode = customer.postcode;
                temp_data.customer_country = customer.country;
            }
        }
		// tablenum
		if (typeof record.orderdata !== 'undefined') {			
			for (var i in record.orderdata) {
				temp_data.tablenum_txt = (record.orderdata[i].tablenum>0?"Table #: " + record.orderdata[i].tablenum:"Take Away");
				break;
			}
		}
        // invoice specific data
        if (invoice){
            // business
            temp_data.payment_instructions = config.invoice.payinst;
            temp_data.logo_url = document.location.protocol+"//"+document.location.host+config.pos.recemaillogo;
            temp_data.business_name = config.general.bizname;
            temp_data.business_address = config.general.bizaddress;
            temp_data.business_suburb = config.general.bizsuburb;
            temp_data.business_state = config.general.bizstate;
            temp_data.business_postcode = config.general.bizpostcode;
            temp_data.business_country = config.general.bizcountry;
            temp_data.business_number = config.general.biznumber;
            for (var a in temp_data.sale_items){
                var saleitem = temp_data.sale_items[a];
                saleitem.tax.items =  [];
                var taxitems = WPOS.getTaxTable().items;
                for (var b in saleitem.tax.values) {
                    taxstr = taxitems[b].name + ' (' + taxitems[b].value + '%)';
                    saleitem.tax.items.push({label: taxstr, value: WPOS.util.currencyFormat(record.taxdata[b])});
                }
                temp_data.sale_items[a] = saleitem;
            }
        } else {
            // format refunds
            if (record.hasOwnProperty("refunddata")) {
                temp_data.sale_refunds = [];
                var lastrefindex = 0, lastreftime = 0;
                for (i in record.refunddata) {
                    // find last refund for integrated eftpos receipt
                    if (record.refunddata[i].processdt > lastreftime) {
                        lastrefindex = i;
                    }
                    var altmethod = altlabels.hasOwnProperty(record.refunddata[i].method)?altlabels[record.refunddata[i].method]:WPOS.util.capFirstLetter(method);
                    temp_data.sale_refunds.push({
                        datetime: WPOS.util.getDateFromTimestamp(record.refunddata[i].processdt),
                        numitems: record.refunddata[i].items.length,
                        method: WPOS.util.capFirstLetter(record.refunddata[i].method),
                        altmethod: altmethod,
                        amount: WPOS.util.currencyFormat(record.refunddata[i].amount)
                    });
                }
                // check for integrated receipt and replace if found
                if (record.refunddata[lastrefindex].hasOwnProperty('paydata') && record.refunddata[lastrefindex].paydata.hasOwnProperty('customerReceipt')) {
                    temp_data.eftpos_receipts = record.refunddata[lastrefindex].paydata.customerReceipt;
                }
            }
            if (!WPOS.getLocalConfig().eftpos.receipts)
                temp_data.eftpos_receipts = '';
        }

        return Mustache.render(template.template, temp_data);
    }

    // Browser printing methods
    function browserPrintHtml(html, name, width, height) {

        var printw = window.open('', name, 'height='+height+',width='+width+',scrollbars=yes');

        printw.document.write(html);
        printw.document.close();

        // close only after printed, This is only implemented properly in firefox but can be used for others soon (part of html5 spec)
        //if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1)
        //printw.addEventListener('afterprint', function(e){ printw.close(); });

        // some browsers including chrome fire the print function before the page is rendered.
        // Print page in the onload event so we know the content is rendered.
        var printed = false;
        function windowReady(){
            if (!printed){
                printed = true;
                printw.focus();
                printw.print();
            }
        }
        printw.onload = windowReady;
        //setTimeout(windowReady, 1200); // possible fallback for browsers that don't support the onload event in child window
    }

    // character conversion
    this.convertUnicodeCharacters = function(unicode, charset, codepage){
        return convertUnicodeCharacters(unicode, charset, codepage);
    };

    function convertUnicodeCharacters(unicode, charset, codepage){
        if(!/[^\u0000-\u00ff]/.test(unicode)) {
            return unicode; // no unicode detected
        }
        var result = "";
        if (!charmap.hasOwnProperty(charset))
            return result;
        //console.log(unicode);
        unicode = getRealCharCodes(unicode, charset);
        //console.log(unicode);
        for (var i = 0; i<unicode.length; i++){
            var char = unicode.charAt(i);
            if (charmap[charset].hasOwnProperty(char)) {
                char = charmap[charset][char];
                result = char + result;
            } else {
                console.log("Could not decode: ("+char.charCodeAt(0)+") "+char);
            }
        }

        return setCharacterSet(codepage)+result+setCharacterSet(null);
    }

    function setCharacterSet(codepage){
        return "\x1B" + "\x74" + (codepage!=null ? String.fromCharCode(parseInt(codepage)) : "\x00");
    }

    this.wrapWithCharacterSet = function(text, codepage){
        return setCharacterSet(codepage) + text + setCharacterSet(null);
    };

    var charmap = {
        "iso-8859-6" : {" ":" ","¤":"¤","،":"¬","­":"­","؛":"»","؟":"¿","ء":"Á","آ":"Â","أ":"Ã","ؤ":"Ä","إ":"Å","ئ":"Æ","ا":"Ç","ب":"È","ة":"É","ت":"Ê","ث":"Ë","ج":"Ì","ح":"Í","خ":"Î","د":"Ï","ذ":"Ð","ر":"Ñ","ز":"Ò","س":"Ó","ش":"Ô","ص":"Õ","ض":"Ö","ط":"×","ظ":"Ø","ع":"Ù","غ":"Ú","ـ":"à","ف":"á","ق":"â","ك":"ã","ل":"ä","م":"å","ن":"æ","ه":"ç","و":"è","ى":"é","ي":"ê","ً":"ë","ٌ":"ì","ٍ":"í","َ":"î","ُ":"ï","ِ":"ð","ّ":"ñ","ْ":"ò"},
        "pc1256"     : {"0":"0","1":"1","2":"2","3":"3","4":"4","5":"5","6":"6","7":"7","8":"8","9":"9","\u0000":"\u0000","\u0001":"\u0001","\u0002":"\u0002","\u0003":"\u0003","\u0004":"\u0004","\u0005":"\u0005","\u0006":"\u0006","\u0007":"\u0007","\b":"\b","\t":"\t","\n":"\n","\u000b":"\u000b","\f":"\f","\r":"\r","\u000e":"\u000e","\u000f":"\u000f","\u0010":"\u0010","\u0011":"\u0011","\u0012":"\u0012","\u0013":"\u0013","\u0014":"\u0014","\u0015":"\u0015","\u0016":"\u0016","\u0017":"\u0017","\u0018":"\u0018","\u0019":"\u0019","\u001a":"\u001a","\u001b":"\u001b","\u001c":"\u001c","\u001d":"\u001d","\u001e":"\u001e","\u001f":"\u001f"," ":" ","!":"!","\"":"\"","#":"#","$":"$","%":"%","&":"&","'":"'","(":"(",")":")","*":"*","+":"+",",":",","-":"-",".":".","/":"/",":":":",";":";","<":"<","=":"=",">":">","?":"?","@":"@","A":"A","B":"B","C":"C","D":"D","E":"E","F":"F","G":"G","H":"H","I":"I","J":"J","K":"K","L":"L","M":"M","N":"N","O":"O","P":"P","Q":"Q","R":"R","S":"S","T":"T","U":"U","V":"V","W":"W","X":"X","Y":"Y","Z":"Z","[":"[","\\":"\\","]":"]","^":"^","_":"_","`":"`","a":"a","b":"b","c":"c","d":"d","e":"e","f":"f","g":"g","h":"h","i":"i","j":"j","k":"k","l":"l","m":"m","n":"n","o":"o","p":"p","q":"q","r":"r","s":"s","t":"t","u":"u","v":"v","w":"w","x":"x","y":"y","z":"z","{":"{","|":"|","}":"}","~":"~","":"","€":"","پ":"","‚":"","ƒ":"","„":"","…":"","†":"","‡":"","ˆ":"","‰":"","ٹ":"","‹":"","Œ":"","چ":"","ژ":"","ڈ":"","گ":"","‘":"","’":"","“":"","”":"","•":"","–":"","—":"","ک":"","™":"","ڑ":"","›":"","œ":"","‌":"","‍":"","ں":""," ":" ","،":"¡","¢":"¢","£":"£","¤":"¤","¥":"¥","¦":"¦","§":"§","¨":"¨","©":"©","ھ":"ª","«":"«","¬":"¬","­":"­","®":"®","¯":"¯","°":"°","±":"±","²":"²","³":"³","´":"´","µ":"µ","¶":"¶","·":"·","¸":"¸","¹":"¹","؛":"º","»":"»","¼":"¼","½":"½","¾":"¾","؟":"¿","ہ":"À","ء":"Á","آ":"Â","أ":"Ã","ؤ":"Ä","إ":"Å","ئ":"Æ","ا":"Ç","ب":"È","ة":"É","ت":"Ê","ث":"Ë","ج":"Ì","ح":"Í","خ":"Î","د":"Ï","ذ":"Ð","ر":"Ñ","ز":"Ò","س":"Ó","ش":"Ô","ص":"Õ","ض":"Ö","×":"×","ط":"Ø","ظ":"Ù","ع":"Ú","غ":"Û","ـ":"Ü","ف":"Ý","ق":"Þ","ك":"ß","à":"à","ل":"á","â":"â","م":"ã","ن":"ä","ه":"å","و":"æ","ç":"ç","è":"è","é":"é","ê":"ê","ë":"ë","ى":"ì","ي":"í","î":"î","ï":"ï","ً":"ð","ٌ":"ñ","ٍ":"ò","َ":"ó","ô":"ô","ُ":"õ","ِ":"ö","÷":"÷","ّ":"ø","ù":"ù","ْ":"ú","û":"û","ü":"ü","‎":"ý","‏":"þ","ے":"ÿ"},
        "pc864"      : {"0":"0","1":"1","2":"2","3":"3","4":"4","5":"5","6":"6","7":"7","8":"8","9":"9","\u0000":"\u0000","\u0001":"\u0001","\u0002":"\u0002","\u0003":"\u0003","\u0004":"\u0004","\u0005":"\u0005","\u0006":"\u0006","\u0007":"\u0007","\b":"\b","\t":"\t","\n":"\n","\u000b":"\u000b","\f":"\f","\r":"\r","\u000e":"\u000e","\u000f":"\u000f","\u0010":"\u0010","\u0011":"\u0011","\u0012":"\u0012","\u0013":"\u0013","\u0014":"\u0014","\u0015":"\u0015","\u0016":"\u0016","\u0017":"\u0017","\u0018":"\u0018","\u0019":"\u0019","\u001a":"\u001a","\u001b":"\u001b","\u001c":"\u001c","\u001d":"\u001d","\u001e":"\u001e","\u001f":"\u001f"," ":" ","!":"!","\"":"\"","#":"#","$":"$","٪":"%","&":"&","'":"'","(":"(",")":")","*":"*","+":"+",",":",","-":"-",".":".","/":"/",":":":",";":";","<":"<","=":"=",">":">","?":"?","@":"@","A":"A","B":"B","C":"C","D":"D","E":"E","F":"F","G":"G","H":"H","I":"I","J":"J","K":"K","L":"L","M":"M","N":"N","O":"O","P":"P","Q":"Q","R":"R","S":"S","T":"T","U":"U","V":"V","W":"W","X":"X","Y":"Y","Z":"Z","[":"[","\\":"\\","]":"]","^":"^","_":"_","`":"`","a":"a","b":"b","c":"c","d":"d","e":"e","f":"f","g":"g","h":"h","i":"i","j":"j","k":"k","l":"l","m":"m","n":"n","o":"o","p":"p","q":"q","r":"r","s":"s","t":"t","u":"u","v":"v","w":"w","x":"x","y":"y","z":"z","{":"{","|":"|","}":"}","~":"~","":"","°":"","·":"","∙":"","√":"","▒":"","─":"","│":"","┼":"","┤":"","┬":"","├":"","┴":"","┐":"","┌":"","└":"","┘":"","β":"","∞":"","φ":"","±":"","½":"","¼":"","≈":"","«":"","»":"","ﻷ":"","ﻸ":"","ﻻ":"","ﻼ":""," ":" ","­":"¡","ﺂ":"¢","£":"£","¤":"¤","ﺄ":"¥","ﺎ":"¨","ﺏ":"©","ﺕ":"ª","ﺙ":"«","،":"¬","ﺝ":"­","ﺡ":"®","ﺥ":"¯","٠":"°","١":"±","٢":"²","٣":"³","٤":"´","٥":"µ","٦":"¶","٧":"·","٨":"¸","٩":"¹","ﻑ":"º","؛":"»","ﺱ":"¼","ﺵ":"½","ﺹ":"¾","؟":"¿","¢":"À","ﺀ":"Á","ﺁ":"Â","ﺃ":"Ã","ﺅ":"Ä","ﻊ":"Å","ﺋ":"Æ","ﺍ":"Ç","ﺑ":"È","ﺓ":"É","ﺗ":"Ê","ﺛ":"Ë","ﺟ":"Ì","ﺣ":"Í","ﺧ":"Î","ﺩ":"Ï","ﺫ":"Ð","ﺭ":"Ñ","ﺯ":"Ò","ﺳ":"Ó","ﺷ":"Ô","ﺻ":"Õ","ﺿ":"Ö","ﻁ":"×","ﻅ":"Ø","ﻋ":"Ù","ﻏ":"Ú","¦":"Û","¬":"Ü","÷":"Ý","×":"Þ","ﻉ":"ß","ـ":"à","ﻓ":"á","ﻗ":"â","ﻛ":"ã","ﻟ":"ä","ﻣ":"å","ﻧ":"æ","ﻫ":"ç","ﻭ":"è","ﻯ":"é","ﻳ":"ê","ﺽ":"ë","ﻌ":"ì","ﻎ":"í","ﻍ":"î","ﻡ":"ï","ﹽ":"ð","ّ":"ñ","ﻥ":"ò","ﻩ":"ó","ﻬ":"ô","ﻰ":"õ","ﻲ":"ö","ﻐ":"÷","ﻕ":"ø","ﻵ":"ù","ﻶ":"ú","ﻝ":"û","ﻙ":"ü","ﻱ":"ý","■":"þ"}
    };

    // Arabic form conversion
    var NICE_FORM = 0;
    var INITIAL_FORM = 1;
    var MEDIAL_FORM = 2;
    var FINAL_FORM = 3;
    var ISOLATED_FORM = 4;

    var arabicTranslations = {};
//ALEF WITH MADDA ABOVE
    arabicTranslations[1570] = [1570, 65153, 65153, 65154, 65153];
//ALEF WITH HAMZA ABOVE
    arabicTranslations[1571] = [1571, 65155, 65155, 65156, 65155];
//WAW WITH HAMZA ABOVE
    arabicTranslations[1572] = [1572, 65157, 65157, 65158, 65157];
//ALEF WITH HAMZA BELOW (fallback to alif)
    //arabicTranslations[1573] = [1573, 65159, 65159, 65160, 65159];
    arabicTranslations[1573] = [1575, 65165, 65166, 65166, 65165];
//YEH WITH HAMZA ABOVE
    arabicTranslations[1574] = [1574, 65163, 65164, 65162, 65161];
//Alif
    arabicTranslations[1575] = [1575, 65165, 65166, 65166, 65165];
//Ba
    arabicTranslations[1576] = [1576, 65169, 65170, 65168, 65167];
//Teh Marbuta
    arabicTranslations[1577] = [1577, 65171, 65171, 65172, 65171];
//Ta
    arabicTranslations[1578] = [1578, 65175, 65176, 65174, 65173];
//Tha
    arabicTranslations[1579] = [1579, 65179, 65180, 65178, 65177];
//Jim
    arabicTranslations[1580] = [1580, 65183, 65184, 65182, 65181];
//Ha
    arabicTranslations[1581] = [1581, 65187, 65188, 65186, 65185];
//Kha
    arabicTranslations[1582] = [1582, 65191, 65192, 65190, 65189];
//Dal
    arabicTranslations[1583] = [1583, 65193, 65194, 65194, 65193];
//Thal
    arabicTranslations[1584] = [1584, 65195, 65196, 65196, 65195];
//Ra
    arabicTranslations[1585] = [1585, 65197, 65198, 65198, 65195];
//Zain
    arabicTranslations[1586] = [1586, 65199, 65200, 65200, 65199];
//Seen
    arabicTranslations[1587] = [1587, 65203, 65204, 65202, 65201];
//Sheen
    arabicTranslations[1588] = [1588, 65207, 65208, 65206, 65205];
//Sod
    arabicTranslations[1589] = [1589, 65211, 65212, 65210, 65209];
//Dod
    arabicTranslations[1590] = [1590, 65215, 65216, 65214, 65213];
//Tah
    arabicTranslations[1591] = [1591, 65219, 65220, 65218, 65217];
//Thah
    arabicTranslations[1592] = [1592, 65223, 65224, 65222, 65221];
//Ayn
    arabicTranslations[1593] = [1593, 65227, 65228, 65224, 65225];
//Ghayn
    arabicTranslations[1594] = [1594, 65231, 65232, 65230, 65229];
//Fah
    arabicTranslations[1601] = [1601, 65235, 65236, 65234, 65233];
//Qaf
    arabicTranslations[1602] = [1602, 65239, 65240, 65238, 65237];
//Kaf
    arabicTranslations[1603] = [1603, 65243, 65244, 65242, 65241];
//Lam
    arabicTranslations[1604] = [1604, 65247, 65248, 65246, 65245];
//Mim
    arabicTranslations[1605] = [1605, 65251, 65252, 65250, 65249];
//Nun
    arabicTranslations[1606] = [1606, 65255, 65256, 65254, 65253];
//Heh
    arabicTranslations[1607] = [1607, 65259, 65260, 65258, 65257];
//Waw
    arabicTranslations[1608] = [1608, 65261, 65262, 65262, 65261];
//Ya
    arabicTranslations[1610] = [1610, 65267, 65268, 65266, 65265];

    var nonConnectors = [1575, 1583, 1584, 1585, 1586, 1608];

    // ligatures
    var ligatures = {
        // lam
        1604 : {
            1575 : 65275, // lam alef
            1570 : 65269, // lam alef with madda
            1571 : 65271  // lam alef with hazma
        }
    };
    // lam alef
    arabicTranslations[65275] = [65275, 65275, 65276, 65276, 65275];
    // lam alef with madda above
    arabicTranslations[65269] = [65269, 65269, 65270, 65270, 65269];
    // lam alef with hamza above
    arabicTranslations[65271] = [65271, 65271, 65272, 65272, 65271];

    var getRealCharCodes = function(str, charset) {
        //Can't change an empty or one-char string
        if(str.length === 0 || str.length == 1) { console.log("Empty string"); return str; }

        //No arabic in here to change, let's be quick about it
        if(!/[\u0600-\u06FF]/.test(str)) { console.log("No arabic here"); return str; }

        var useFallback = charmap.hasOwnProperty(charset);
        //console.log("Changing " + str);
        var toReturn = "";
        var initial = true;
        var final = false;
        for(var x = 0; x < str.length; x++) {
            var tmpCharCode = str.charCodeAt(x);
            var tmpChar = str.charAt(x);

            // this is a space, skip and set to initial
            if (tmpCharCode==32){
                toReturn += tmpChar;
                initial = true;
                final = false;
                continue;
            }

            // unknown character, we have no way to translate
            if(arabicTranslations[tmpCharCode] === undefined) {
                toReturn += tmpChar; console.log("Skipping unknown character: " + tmpChar + "-" + tmpCharCode);
                continue;
            }

            // ignore second letter of ligature
            var prevCode = str.charCodeAt(x-1);
            var ligature = ligatures.hasOwnProperty(prevCode)?ligatures[prevCode]:false;
            if (ligature && ligature.hasOwnProperty(tmpCharCode)){
                continue;
            }

            // convert ligatures
            ligature = ligatures.hasOwnProperty(tmpCharCode)?ligatures[tmpCharCode]:false;
            if (ligature && ligature.hasOwnProperty(str.charCodeAt(x+1))){
                tmpCharCode = ligature[str.charCodeAt(x+1)];
                // check if the ligature should be final form
                if (x == str.length - 2 || arabicTranslations[str.charCodeAt(x + 2)] === undefined){
                    final = true;
                }
            }

            //If we're the last letter, we must be final
            if(x == str.length - 1) {
                final = true;
                //Or if the next letter after us is not an Arabic letter we know how to deal with
            } else if(arabicTranslations[str.charCodeAt(x + 1)] === undefined) {
                final = true;
            }

            //Add this character
            var char = "";
            var form = 0;
            if(initial && final) {
                //console.log("Isolated char");
                char = String.fromCharCode(arabicTranslations[tmpCharCode][ISOLATED_FORM]);
                form = ISOLATED_FORM;
                initial = true;
                final = false;
            } else if(initial) {
                //console.log("Initial char");
                char = String.fromCharCode(arabicTranslations[tmpCharCode][INITIAL_FORM]);
                form = INITIAL_FORM;
                initial = false;
            } else if(final) {
                //console.log("Final char");
                char = String.fromCharCode(arabicTranslations[tmpCharCode][FINAL_FORM]);
                form = FINAL_FORM;
                initial = true;
                final = false;
            } else {
                //console.log("Median char");
                char = String.fromCharCode(arabicTranslations[tmpCharCode][MEDIAL_FORM]);
                form = MEDIAL_FORM;
            }

            // convert to fallback form
            if (useFallback && !charmap[charset].hasOwnProperty(char)){
                switch (form){
                    case FINAL_FORM:
                        char = String.fromCharCode(arabicTranslations[tmpCharCode][ISOLATED_FORM]);
                        break;
                    case MEDIAL_FORM:
                        char = String.fromCharCode(arabicTranslations[tmpCharCode][INITIAL_FORM]);
                        if (charmap[charset].hasOwnProperty(char))
                            break;
                    case INITIAL_FORM:
                        char = String.fromCharCode(arabicTranslations[tmpCharCode][ISOLATED_FORM]);
                }
            }

            toReturn+= char;

            //If this is a non-connector, the next character must be initial
            if(nonConnectors.indexOf(tmpCharCode) > -1) {
                initial = true;
            }
        }
        return toReturn;
    };
}
