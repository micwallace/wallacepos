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
    this.getDateFromTimestamp = function (timestamp) {
        // get the config if available
        var format = WPOS.getConfigTable().general.dateformat;
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
            datestr = date + "/" + month + "/" + day + " " + hour + ":" + min + ":" + sec;
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
            datestr = date + "-" + month + "-" + day;
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
    // TAX CALC
    this.calcTax = function (itemtotal, taxid) {
        var tax = parseFloat("0.00").toFixed(2);
        if (taxid != 1) { // don't calc for no tax
            // calculate the tax
            tax = WPOS.getTaxTable()[taxid].calcfunc(itemtotal);
        }
        return tax;
    };

    this.roundToFiveCents = function (v) {
        v = (Math.round(v * 20) / 20).toFixed(2);
        return v;
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

    this.utf8ArrayToStr = function (array) {
        var s = '';
        for (var i = 0; i < array.length; i++) {
            s += String.fromCharCode(array[i]);
            //s += String.fromCharCode(Math.max(0, Math.min(255, array[i])));
        }
        return s;
    };

    this.md5 = function (string) {
        function rotateLeft(lValue, iShiftBits) {
            return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
        }

        function addUnsigned(lX, lY) {
            var lX4, lY4, lX8, lY8, lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        }

        function f(x, y, z) {
            return (x & y) | ((~x) & z);
        }

        function g(x, y, z) {
            return (x & z) | (y & (~z));
        }

        function h(x, y, z) {
            return (x ^ y ^ z);
        }

        function i(x, y, z) {
            return (y ^ (x | (~z)));
        }

        function ff(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(f(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }

        function gg(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(g(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }

        function hh(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(h(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }

        function ii(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(i(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        }

        function convertToWordArray(string) {
            var lWordCount;
            var lMessageLength = string.length;
            var lNumberOfWords_temp1 = lMessageLength + 8;
            var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
            var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
            var lWordArray = [lNumberOfWords - 1];
            var lBytePosition = 0;
            var lByteCount = 0;
            while (lByteCount < lMessageLength) {
                lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                lBytePosition = (lByteCount % 4) * 8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
            lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
            lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
            return lWordArray;
        }

        function wordToHex(lValue) {
            var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
            for (lCount = 0; lCount <= 3; lCount++) {
                lByte = (lValue >>> (lCount * 8)) & 255;
                WordToHexValue_temp = "0" + lByte.toString(16);
                WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
            }
            return WordToHexValue;
        }

        function wtf8Encode(string) {
            string = string.replace(/\r\n/g, "\n");
            var utftext = "";

            for (var n = 0; n < string.length; n++) {

                var c = string.charCodeAt(n);

                if (c < 128) {
                    utftext += String.fromCharCode(c);
                }
                else if ((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
                else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }

            }

            return utftext;
        }

        var x,
            k, AA, BB, CC, DD, a, b, c, d,
            S11 = 7, S12 = 12, S13 = 17, S14 = 22,
            S21 = 5, S22 = 9, S23 = 14, S24 = 20,
            S31 = 4, S32 = 11, S33 = 16, S34 = 23,
            S41 = 6, S42 = 10, S43 = 15, S44 = 21;

        string = wtf8Encode(string);

        x = convertToWordArray(string);

        a = 0x67452301;
        b = 0xEFCDAB89;
        c = 0x98BADCFE;
        d = 0x10325476;

        for (k = 0; k < x.length; k += 16) {
            AA = a;
            BB = b;
            CC = c;
            DD = d;
            a = ff(a, b, c, d, x[k + 0], S11, 0xD76AA478);
            d = ff(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
            c = ff(c, d, a, b, x[k + 2], S13, 0x242070DB);
            b = ff(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
            a = ff(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
            d = ff(d, a, b, c, x[k + 5], S12, 0x4787C62A);
            c = ff(c, d, a, b, x[k + 6], S13, 0xA8304613);
            b = ff(b, c, d, a, x[k + 7], S14, 0xFD469501);
            a = ff(a, b, c, d, x[k + 8], S11, 0x698098D8);
            d = ff(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
            c = ff(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
            b = ff(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
            a = ff(a, b, c, d, x[k + 12], S11, 0x6B901122);
            d = ff(d, a, b, c, x[k + 13], S12, 0xFD987193);
            c = ff(c, d, a, b, x[k + 14], S13, 0xA679438E);
            b = ff(b, c, d, a, x[k + 15], S14, 0x49B40821);
            a = gg(a, b, c, d, x[k + 1], S21, 0xF61E2562);
            d = gg(d, a, b, c, x[k + 6], S22, 0xC040B340);
            c = gg(c, d, a, b, x[k + 11], S23, 0x265E5A51);
            b = gg(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
            a = gg(a, b, c, d, x[k + 5], S21, 0xD62F105D);
            d = gg(d, a, b, c, x[k + 10], S22, 0x2441453);
            c = gg(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
            b = gg(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
            a = gg(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
            d = gg(d, a, b, c, x[k + 14], S22, 0xC33707D6);
            c = gg(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
            b = gg(b, c, d, a, x[k + 8], S24, 0x455A14ED);
            a = gg(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
            d = gg(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
            c = gg(c, d, a, b, x[k + 7], S23, 0x676F02D9);
            b = gg(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
            a = hh(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
            d = hh(d, a, b, c, x[k + 8], S32, 0x8771F681);
            c = hh(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
            b = hh(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
            a = hh(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
            d = hh(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
            c = hh(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
            b = hh(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
            a = hh(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
            d = hh(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
            c = hh(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
            b = hh(b, c, d, a, x[k + 6], S34, 0x4881D05);
            a = hh(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
            d = hh(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
            c = hh(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
            b = hh(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
            a = ii(a, b, c, d, x[k + 0], S41, 0xF4292244);
            d = ii(d, a, b, c, x[k + 7], S42, 0x432AFF97);
            c = ii(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
            b = ii(b, c, d, a, x[k + 5], S44, 0xFC93A039);
            a = ii(a, b, c, d, x[k + 12], S41, 0x655B59C3);
            d = ii(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
            c = ii(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
            b = ii(b, c, d, a, x[k + 1], S44, 0x85845DD1);
            a = ii(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
            d = ii(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
            c = ii(c, d, a, b, x[k + 6], S43, 0xA3014314);
            b = ii(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
            a = ii(a, b, c, d, x[k + 4], S41, 0xF7537E82);
            d = ii(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
            c = ii(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
            b = ii(b, c, d, a, x[k + 9], S44, 0xEB86D391);
            a = addUnsigned(a, AA);
            b = addUnsigned(b, BB);
            c = addUnsigned(c, CC);
            d = addUnsigned(d, DD);
        }

        var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);

        return temp.toLowerCase();
    };

    /*
     CryptoJS v3.1.2
     code.google.com/p/crypto-js
     (c) 2009-2013 by Jeff Mott. All rights reserved.
     code.google.com/p/crypto-js/wiki/License
     */
    this.SHA256 = function(value){
        return CryptoJS.SHA256(value);
    }

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