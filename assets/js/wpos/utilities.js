/**
 * utilities.js is part of Wallace Point of Sale system (WPOS)
 *
 * utilities.js Provides a global set of general functions.
 * These functions are used accross the WPOS system including the admin and client login areas.
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

function WPOSUtil() {
    // DATE
    this.getDateFromTimestamp = function (timestamp, format) {
        // get the config if available
        if (!format)
            format = WPOS.getConfigTable().general.dateformat;
        var date = new Date(timestamp);
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        var day = date.getDate();
        var hour = date.getHours();
        var min = date.getMinutes();
        var sec = date.getSeconds();
        if (hour < 10)
            hour = '0' + hour;

        if (min < 10)
            min = '0' + min;

        if (sec < 10)
            sec = '0' + sec;

        var datestr;
        if (format == "d/m/y" || format == "m/d/y") {
            datestr = (format == "d/m/y" ? day + "/" + month : month + "/" + day   ) + "/" + year.toString().substring(2, 4) + " " + hour + ":" + min + ":" + sec;
        } else {
            datestr = year + "-" + month + "-" + day + " " + hour + ":" + min + ":" + sec;
        }
        return datestr;
    };

    this.getShortDate = function (timestamp) {
        var date;
        // get the config if available
        var format = WPOS.getConfigTable().general.dateformat;
        if (timestamp == null) {
            date = new Date();
        } else {
            date = new Date(timestamp);
        }
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        var day = date.getDate();
        var hour = date.getHours();
        var min = date.getMinutes();
        var sec = date.getSeconds();
        if (hour < 10)
            hour = '0' + hour;

        if (min < 10)
            min = '0' + min;

        if (sec < 10)
            sec = '0' + sec;

        var datestr;
        if (format == "d/m/y" || format == "m/d/y") {
            datestr = (format == "d/m/y" ? day + "/" + month : month + "/" + day   ) + "/" + year.toString().substring(2, 4);
        } else {
            datestr = year + "-" + month + "-" + day;
        }

        return datestr;
    };
    // returns number of milliseconds specified by syntax "+1 days/weeks/month" as an equivalent of php dates function that does the same thing
    this.parseDateString = function (string) {
        var parts = string.split(" ");
        var umillis;
        switch (parts[1]) {
            case "second":
                umillis = 1000;
                break;
            case "minute":
                umillis = 60000;
                break;
            case "hours":
                umillis = 3600000;
                break;
            case "days":
                umillis = 86400000;
                break;
            case "weeks":
                umillis = 604800000;
                break;
            case "months":
                umillis = 2.62974e9;
                break;
            case "years":
                umillis = 3.15569e10;
                break;
        }
        return (umillis * parts[0].slice(1));
    };
    // string functions
    this.capFirstLetter = function (string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    };
    // random ids
    this.getRandomId = function(){
        return 'xxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        });
    };
    // get order number
    var ordercount = null;
    this.getSequencialOrderNumber = function(){
        if (!ordercount){
            ordercount = localStorage.getItem("wpos_ordercount");
            if (ordercount<0)
                ordercount = 0;
        }
        // increment or reset count
        if (ordercount<99){
            ordercount++;
        }
        localStorage.setItem("wpos_ordercount", ordercount);
        // pad order number, include deviceid
        var orderStr = WPOS.getConfigTable().deviceid.toString();
        if (ordercount<10)
            orderStr+="0";
        return (orderStr+ordercount);
    };
    // check if an object is equvalent
    this.areObjectsEquivalent = function(a, b) {
        // Create arrays of property names
        var aProps, bProps;
        if (typeof a === "object") {
            aProps = Object.getOwnPropertyNames(a);
        } else if (typeof a === "array"){
            aProps = a;
        }
        if (typeof b === "object") {
            bProps = Object.getOwnPropertyNames(b);
        } else if (typeof b === "array"){
            bProps = b;
        }
        // If number of properties is different,
        // objects are not equivalent
        if (aProps.length != bProps.length) {
            return false;
        }

        for (var i = 0; i < aProps.length; i++) {
            var propName = aProps[i];
            // If values of same property are not equal,
            // objects are not equivalent
            if (typeof a[propName] === "object" && typeof b[propName]=== "object"){
                if (!this.areObjectsEquivalent(a[propName], b[propName])){
                    return false;
                }
            } else {
                if (a[propName] !== b[propName]) {
                    return false;
                }
            }
        }
        // If we made it this far, objects
        // are considered equivalent
        return true;
    };
    // TAX CALCULATIONS
    /*this.isTaxInclusive = function(taxruleid){
        var taxrules = WPOS.getTaxTable().rules;
        if (!taxrules.hasOwnProperty(taxruleid)) return true;
        return taxrules[taxruleid].inclusive;
    };*/
    this.calcTax = function(taxruleid, itemtotal, itemcost){
        var tax = {total:0, values:{}, inclusive:true};
        if (!WPOS.getTaxTable().rules.hasOwnProperty(taxruleid))
            return tax;
        // get the tax rule; taxable total is needed to calculate inclusive tax
        var rule = WPOS.getTaxTable().rules[taxruleid];
        tax.inclusive = rule.inclusive;
        var taxitems = WPOS.getTaxTable().items;
        var locationid = WPOS.getConfigTable().hasOwnProperty("locationid")?WPOS.getConfigTable().locationid:0;
        var taxablemulti = rule.inclusive?getTaxableTotal(rule, locationid):0;
        var tempitem;
        var tempval;
        var taxableamt;
        // check in locations, if location rule present get tax totals
        if (rule.locations.hasOwnProperty(locationid)){
            for (i=0; i<rule.locations[locationid].length; i++){
                if (taxitems.hasOwnProperty(rule.locations[locationid][i])){
                    tempitem = taxitems[rule.locations[locationid][i]];
                    if (!tax.values.hasOwnProperty(rule.locations[locationid][i])) tax.values[tempitem.id]= 0;
                    taxableamt = (tempitem.type=="vat" ? itemcost : itemtotal);
                    tempval = rule.inclusive ? getIncludedTax(tempitem.multiplier, taxablemulti, taxableamt) : getExcludedTax(tempitem.multiplier, taxableamt);
                    tax.values[tempitem.id] += tempval;
                    tax.total += tempval;
                    if (rule.mode=="single")
                        return tax; // return if in single mode
                }
            }
        }
        // apply base tax totals, if rule is single mode, only apply if no matched locations
        for (var i=0; i<rule.base.length; i++){
            if (taxitems.hasOwnProperty(rule.base[i])){
                tempitem = taxitems[rule.base[i]];
                if (!tax.values.hasOwnProperty(rule.base[i])) tax.values[tempitem.id]= 0;
                taxableamt = (tempitem.type=="vat" ? itemcost : itemtotal);
                tempval = rule.inclusive ? getIncludedTax(tempitem.multiplier, taxablemulti, taxableamt) : getExcludedTax(tempitem.multiplier, taxableamt);
                tax.values[tempitem.id] += tempval;
                tax.total += tempval;
                if (rule.mode=="single")
                    break; // only apply one base tax in single mode
            }
        }

        return tax;
    };
    // this is used for inclusive tax to workout the total, when multiple tax items are applied, we need this to work out each tax rate total;
    function getTaxableTotal(rule, locationid){
        var taxitems = WPOS.getTaxTable().items;
        var taxable = 0;
        if (rule.locations.hasOwnProperty(locationid)){
            for (i=0; i<rule.locations[locationid].length; i++){
                if (taxitems.hasOwnProperty(rule.locations[locationid][i]))
                    taxable += parseFloat(taxitems[rule.locations[locationid][i]].multiplier);
                if (rule.mode=="single")
                    return Number(taxable.toFixed(2));
            }
        }
        for (var i=0; i<rule.base.length; i++){
            if (taxitems.hasOwnProperty(rule.base[i]))
                taxable += parseFloat(taxitems[rule.base[i]].multiplier);
            if (rule.mode=="single")
                break;
        }
        return Number(taxable.toFixed(2));
    }
    function getIncludedTax(multiplier, taxablemulti, value){
        value = parseFloat(value);
        var taxable = (value-(value/(parseFloat(taxablemulti)+1)));
        return Number( ((taxable/taxablemulti)*multiplier).toFixed(2) );
    }
    function getExcludedTax(multiplier, value){
        return Number( (parseFloat(multiplier)*parseFloat(value)).toFixed(2) );
    }

    this.roundToNearestCents = function(cents, value){
        var x = 100 / parseInt(cents);
        return (Math.round(value * x) / x).toFixed(2);
    };

    var curformat = null;
    var printcursymbol = "";
    function loadCurrencyValues(){
        if (curformat==null){
            if (!WPOS.getConfigTable().hasOwnProperty('general'))
                return;

            curformat = WPOS.getConfigTable().general.currencyformat.split('~');
            //if (WPOS.getConfigTable().pos.hasOwnProperty('reccurrency') && WPOS.getConfigTable().pos.reccurrency!="")
                //printcursymbol = String.fromCharCode(parseInt(WPOS.getConfigTable().pos.reccurrency));
            if (WPOS.hasOwnProperty('print')) {
                printcursymbol = getPrintCurrencySymbol();
                if (printcursymbol == "" && (curformat[0] == "£" || containsNonLatinCodepoints(curformat[0]))) {
                    // check for unicode characters and set default alt character if so
                    printcursymbol = curformat[0] == "£" ? String.fromCharCode(156) : "$";
                }
            }

            setTimeout(function(){ curformat = null; }, 30000);
        }
    }

    function getPrintCurrencySymbol(){
        // check local setting first
        var codepage, codes;
        if (WPOS.print.getGlobalPrintSetting('currency_override') && WPOS.print.getGlobalPrintSetting('currency_codes')!=""){
            codepage = WPOS.print.getGlobalPrintSetting('currency_codepage');
            codes = WPOS.print.getGlobalPrintSetting('currency_codes').split(',');
        } else if (WPOS.getConfigTable().pos.hasOwnProperty('reccurrency') && WPOS.getConfigTable().pos.reccurrency!="") {
            codepage = WPOS.getConfigTable().pos.reccurrency_codepage;
            codes = WPOS.getConfigTable().pos.reccurrency.split(',');
        } else {
            return "";
        }
        var result = "";
        for (var i=0; i<codes.length; i++){
            result += String.fromCharCode(parseInt(codes[i]));
        }
        if (codepage>0)
            return WPOS.print.wrapWithCharacterSet(result, codepage);

        return result;
    }

    this.reloadPrintCurrencySymbol = function(){
        printcursymbol = getPrintCurrencySymbol();
        if ((printcursymbol=="" && curformat!=null) && (curformat[0]=="£" || containsNonLatinCodepoints(curformat[0]))){
            // check for unicode characters and set default alt character if so
            printcursymbol = curformat[0]=="£"?String.fromCharCode(156):"$";
        }
    };

    function containsNonLatinCodepoints(s) {
        return /[^\u0000-\u00ff]/.test(s);
    }

    this.getCurrencySymbol = function(){
        loadCurrencyValues();
        return curformat[0];
    };

    this.getCurrencyPlacedAfter = function(){
        loadCurrencyValues();
        return curformat[4]!=0;
    };

    this.currencyFormat = function(value, nosymbol, usesymboloverride){
        loadCurrencyValues();
        var result = number_format(value, curformat[1], curformat[2], curformat[3]);
        if (!nosymbol){
            var cursymbol = ((printcursymbol!="" && usesymboloverride)?printcursymbol:curformat[0]);
            result = curformat[4]==0 ? (cursymbol + result) : (result + cursymbol);
        }
        return result;
    };
    // javascript equiv of php's number_format
    function number_format(number, decimals, dec_point, thousands_sep) {
        //  discuss at: http://phpjs.org/functions/number_format/
        // original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: davook
        // improved by: Brett Zamir (http://brett-zamir.me)
        // improved by: Brett Zamir (http://brett-zamir.me)
        // improved by: Theriault
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // bugfixed by: Michael White (http://getsprink.com)
        // bugfixed by: Benjamin Lupton
        // bugfixed by: Allan Jensen (http://www.winternet.no)
        // bugfixed by: Howard Yeend
        // bugfixed by: Diogo Resende
        // bugfixed by: Rival
        // bugfixed by: Brett Zamir (http://brett-zamir.me)
        //  revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
        //  revised by: Luke Smith (http://lucassmith.name)
        //    input by: Kheang Hok Chin (http://www.distantia.ca/)
        //    input by: Jay Klehr
        //    input by: Amir Habibi (http://www.residence-mixte.com/)
        //    input by: Amirouche

        number = (number + '')
            .replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + (Math.round(n * k) / k)
                    .toFixed(prec);
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
            .split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '')
            .length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1)
                .join('0');
        }
        return s.join(dec);
    }

    // KEYPAD
    this.initKeypad = function () {
        function moveCursor(input, pos) {
            if (input[0].setSelectionRange) { // Mozilla
                input[0].setSelectionRange(pos, pos);
            }
            else if (input[0].createTextRange) { // IE
                var range = input[0].createTextRange();
                range.move('character', pos);
                range.select();
            }
            input.focus();
        }

        $.keypad.addKeyDef('START', 'start',
            function (inst) {
                moveCursor(this, 0);
            });
        $.keypad.addKeyDef('END', 'end',
            function (inst) {
                moveCursor(this, this.val().length);
            });

        $('.numpad').keypad({prompt: '', keypadOnly: false,
            startText: '|<', startStatus: 'Move to start',
            endText: '>|', endStatus: 'Move to end',
            showAnim: 'show',
            duration: 'fast',
            layout: ['789' + $.keypad.CLOSE,
                '456' + $.keypad.CLEAR,
                '123' + $.keypad.BACK,
                '.0' + $.keypad.SPACE + $.keypad.START + $.keypad.END]
        });
    };

    this.disableKeypad = function () {
        $('.numpad').keypad('destroy');
    };

    // loader
    this.showLoader = function () {
        if (this.mobile) {
            $("#loader").show({duration: 0, queue: false});
        } else {
            $("body").css("cursor", "wait");
        }
    };
    this.hideLoader = function () {
        if (this.mobile) {
            $("#loader").fadeOut();
        } else {
            $("body").css("cursor", "default");
        }
    };

    this.mobile = false;
    this.isandroid = false;
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        // append the loader html
        $("body").append('<div id="loader" style="display: none; position: fixed; left: 0; top:0; width: 100%; height:100%; z-index: 3000; background-color: #000000; opacity:0.5; filter:alpha(opacity=50); text-align: center;"><img style="margin-top: 200px;" src="/assets/images/ajax-loader.gif"/></div>');
        this.mobile = true;
        if (/Android/i.test(navigator.userAgent)) {
            this.isandroid = true;
        }
    }

    this.ArrayToByteStr = function (array) {
        var s = '';
        for (var i = 0; i < array.length; i++) {
            s += String.fromCharCode(array[i]);
        }
        return s;
    };

    /*
     CryptoJS v3.1.2
     code.google.com/p/crypto-js
     (c) 2009-2013 by Jeff Mott. All rights reserved.
     code.google.com/p/crypto-js/wiki/License
     */
    this.SHA256 = function(value){
        return CryptoJS.SHA256(value).toString();
    };

    var CryptoJS = CryptoJS || function (h, s) {
        var f = {}, t = f.lib = {}, g = function () {
            }, j = t.Base = {extend: function (a) {
                g.prototype = this;
                var c = new g;
                a && c.mixIn(a);
                c.hasOwnProperty("init") || (c.init = function () {
                    c.$super.init.apply(this, arguments)
                });
                c.init.prototype = c;
                c.$super = this;
                return c
            }, create: function () {
                var a = this.extend();
                a.init.apply(a, arguments);
                return a
            }, init: function () {
            }, mixIn: function (a) {
                for (var c in a)a.hasOwnProperty(c) && (this[c] = a[c]);
                a.hasOwnProperty("toString") && (this.toString = a.toString)
            }, clone: function () {
                return this.init.prototype.extend(this)
            }},
            q = t.WordArray = j.extend({init: function (a, c) {
                a = this.words = a || [];
                this.sigBytes = c != s ? c : 4 * a.length
            }, toString: function (a) {
                return(a || u).stringify(this)
            }, concat: function (a) {
                var c = this.words, d = a.words, b = this.sigBytes;
                a = a.sigBytes;
                this.clamp();
                if (b % 4)for (var e = 0; e < a; e++)c[b + e >>> 2] |= (d[e >>> 2] >>> 24 - 8 * (e % 4) & 255) << 24 - 8 * ((b + e) % 4); else if (65535 < d.length)for (e = 0; e < a; e += 4)c[b + e >>> 2] = d[e >>> 2]; else c.push.apply(c, d);
                this.sigBytes += a;
                return this
            }, clamp: function () {
                var a = this.words, c = this.sigBytes;
                a[c >>> 2] &= 4294967295 <<
                    32 - 8 * (c % 4);
                a.length = h.ceil(c / 4)
            }, clone: function () {
                var a = j.clone.call(this);
                a.words = this.words.slice(0);
                return a
            }, random: function (a) {
                for (var c = [], d = 0; d < a; d += 4)c.push(4294967296 * h.random() | 0);
                return new q.init(c, a)
            }}), v = f.enc = {}, u = v.Hex = {stringify: function (a) {
                var c = a.words;
                a = a.sigBytes;
                for (var d = [], b = 0; b < a; b++) {
                    var e = c[b >>> 2] >>> 24 - 8 * (b % 4) & 255;
                    d.push((e >>> 4).toString(16));
                    d.push((e & 15).toString(16))
                }
                return d.join("")
            }, parse: function (a) {
                for (var c = a.length, d = [], b = 0; b < c; b += 2)d[b >>> 3] |= parseInt(a.substr(b,
                    2), 16) << 24 - 4 * (b % 8);
                return new q.init(d, c / 2)
            }}, k = v.Latin1 = {stringify: function (a) {
                var c = a.words;
                a = a.sigBytes;
                for (var d = [], b = 0; b < a; b++)d.push(String.fromCharCode(c[b >>> 2] >>> 24 - 8 * (b % 4) & 255));
                return d.join("")
            }, parse: function (a) {
                for (var c = a.length, d = [], b = 0; b < c; b++)d[b >>> 2] |= (a.charCodeAt(b) & 255) << 24 - 8 * (b % 4);
                return new q.init(d, c)
            }}, l = v.Utf8 = {stringify: function (a) {
                try {
                    return decodeURIComponent(escape(k.stringify(a)))
                } catch (c) {
                    throw Error("Malformed UTF-8 data");
                }
            }, parse: function (a) {
                return k.parse(unescape(encodeURIComponent(a)))
            }},
            x = t.BufferedBlockAlgorithm = j.extend({reset: function () {
                this._data = new q.init;
                this._nDataBytes = 0
            }, _append: function (a) {
                "string" == typeof a && (a = l.parse(a));
                this._data.concat(a);
                this._nDataBytes += a.sigBytes
            }, _process: function (a) {
                var c = this._data, d = c.words, b = c.sigBytes, e = this.blockSize, f = b / (4 * e), f = a ? h.ceil(f) : h.max((f | 0) - this._minBufferSize, 0);
                a = f * e;
                b = h.min(4 * a, b);
                if (a) {
                    for (var m = 0; m < a; m += e)this._doProcessBlock(d, m);
                    m = d.splice(0, a);
                    c.sigBytes -= b
                }
                return new q.init(m, b)
            }, clone: function () {
                var a = j.clone.call(this);
                a._data = this._data.clone();
                return a
            }, _minBufferSize: 0});
        t.Hasher = x.extend({cfg: j.extend(), init: function (a) {
            this.cfg = this.cfg.extend(a);
            this.reset()
        }, reset: function () {
            x.reset.call(this);
            this._doReset()
        }, update: function (a) {
            this._append(a);
            this._process();
            return this
        }, finalize: function (a) {
            a && this._append(a);
            return this._doFinalize()
        }, blockSize: 16, _createHelper: function (a) {
            return function (c, d) {
                return(new a.init(d)).finalize(c)
            }
        }, _createHmacHelper: function (a) {
            return function (c, d) {
                return(new w.HMAC.init(a,
                    d)).finalize(c)
            }
        }});
        var w = f.algo = {};
        return f
    }(Math);
    (function (h) {
        for (var s = CryptoJS, f = s.lib, t = f.WordArray, g = f.Hasher, f = s.algo, j = [], q = [], v = function (a) {
            return 4294967296 * (a - (a | 0)) | 0
        }, u = 2, k = 0; 64 > k;) {
            var l;
            a:{
                l = u;
                for (var x = h.sqrt(l), w = 2; w <= x; w++)if (!(l % w)) {
                    l = !1;
                    break a
                }
                l = !0
            }
            l && (8 > k && (j[k] = v(h.pow(u, 0.5))), q[k] = v(h.pow(u, 1 / 3)), k++);
            u++
        }
        var a = [], f = f.SHA256 = g.extend({_doReset: function () {
            this._hash = new t.init(j.slice(0))
        }, _doProcessBlock: function (c, d) {
            for (var b = this._hash.words, e = b[0], f = b[1], m = b[2], h = b[3], p = b[4], j = b[5], k = b[6], l = b[7], n = 0; 64 > n; n++) {
                if (16 > n)a[n] =
                    c[d + n] | 0; else {
                    var r = a[n - 15], g = a[n - 2];
                    a[n] = ((r << 25 | r >>> 7) ^ (r << 14 | r >>> 18) ^ r >>> 3) + a[n - 7] + ((g << 15 | g >>> 17) ^ (g << 13 | g >>> 19) ^ g >>> 10) + a[n - 16]
                }
                r = l + ((p << 26 | p >>> 6) ^ (p << 21 | p >>> 11) ^ (p << 7 | p >>> 25)) + (p & j ^ ~p & k) + q[n] + a[n];
                g = ((e << 30 | e >>> 2) ^ (e << 19 | e >>> 13) ^ (e << 10 | e >>> 22)) + (e & f ^ e & m ^ f & m);
                l = k;
                k = j;
                j = p;
                p = h + r | 0;
                h = m;
                m = f;
                f = e;
                e = r + g | 0
            }
            b[0] = b[0] + e | 0;
            b[1] = b[1] + f | 0;
            b[2] = b[2] + m | 0;
            b[3] = b[3] + h | 0;
            b[4] = b[4] + p | 0;
            b[5] = b[5] + j | 0;
            b[6] = b[6] + k | 0;
            b[7] = b[7] + l | 0
        }, _doFinalize: function () {
            var a = this._data, d = a.words, b = 8 * this._nDataBytes, e = 8 * a.sigBytes;
            d[e >>> 5] |= 128 << 24 - e % 32;
            d[(e + 64 >>> 9 << 4) + 14] = h.floor(b / 4294967296);
            d[(e + 64 >>> 9 << 4) + 15] = b;
            a.sigBytes = 4 * d.length;
            this._process();
            return this._hash
        }, clone: function () {
            var a = g.clone.call(this);
            a._hash = this._hash.clone();
            return a
        }});
        s.SHA256 = g._createHelper(f);
        s.HmacSHA256 = g._createHmacHelper(f)
    })(Math);
}
