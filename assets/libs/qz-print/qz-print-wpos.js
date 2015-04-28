/**
 * Optionally used to deploy multiple versions of the applet for mixed
 * environments.  Oracle uses document.write(), which puts the applet at the
 * top of the page, bumping all HTML content down.
 */
//deployQZ();

/**
 * Deploys different versions of the applet depending on Java version.
 * Useful for removing warning dialogs for Java 6.  This function is optional
 * however, if used, should replace the <applet> method.  Needed to address
 * MANIFEST.MF TrustedLibrary=true discrepency between JRE6 and JRE7.
 */
function deployQZ() {
    var attributes = {id: "qz", code:'qz.PrintApplet.class',
        archive:'/assets/libs/qz-print/qz-print.jar', width:1, height:1};
    var parameters = {jnlp_href: '/assets/libs/qz-print/qz-print_jnlp.jnlp',
        cache_option:'plugin', disable_logging:'false',
        initial_focus:'false', separate_jvm: 'true'};
    if (deployJava.versionCheck("1.7+") == true) {}
    else if (deployJava.versionCheck("1.6+") == true) {
        attributes['archive'] = '/assets/libs/qz-print/jre6/qz-print.jar';
        parameters['jnlp_href'] = '/assets/libs/qz-print/jre6/qz-print_jnlp.jnlp';
    }
    deployJava.runApplet(attributes, parameters, '1.5');
}

/**
 * Automatically gets called when applet has loaded.
 */
function qzReady() {
    // Setup our global qz object
    window["qz"] = document.getElementById('qz');
    if (qz) {
        try {
            $("#printstattxt").text("Print-App Connected");
            populatePrinters();
            populateSerialPorts();

        } catch(err) { // LiveConnect error, display a detailed meesage
            $("#printstattxt").text("Print-App Error!");
            alert("ERROR:  \nThe applet did not load correctly.  Communication to the " +
                "applet has failed, likely caused by Java Security Settings.  \n\n" +
                "CAUSE:  \nJava 7 update 25 and higher block LiveConnect calls " +
                "once Oracle has marked that version as outdated, which " +
                "is likely the cause.  \n\nSOLUTION:  \n  1. Update Java to the latest " +
                "Java version \n          (or)\n  2. Lower the security " +
                "settings from the Java Control Panel.");
        }
    }
}

/**
 * Returns whether or not the applet is not ready to print.
 * Displays an alert if not ready.
 */
function notReady() {
    // If applet is not loaded, display an error
    if (!isLoaded()) {
        return true;
    }
    // If a printer hasn't been selected, display a message.
    else if (!qz.getPrinter()) {
        alert('Please select a printer first by using the "Detect Printer" button.');
        return true;
    }
    return false;
}

/**
 * Returns is the applet is not loaded properly
 */
function isLoaded() {
    if (!qz) {
        alert('Error:\n\n\tPrint plugin is NOT loaded!');
        return false;
    } else {
        try {
            if (!qz.isActive()) {
                alert('Error:\n\n\tPrint plugin is loaded but NOT active!');
                return false;
            }
        } catch (err) {
            alert('Error:\n\n\tPrint plugin is NOT loaded properly!');
            return false;
        }
    }
    return true;
}

/**
 * Automatically gets called when "qz.print()" is finished.
 */
function qzDonePrinting() {
    // Alert error, if any
    if (qz.getException()) {
        alert('Error printing:\n\n\t' + qz.getException().getLocalizedMessage());
        qz.clearException();
        return;
    }

    // Alert success message
    //alert('Successfully sent print data to "' + qz.getPrinter() + '" queue.');
}

/***************************************************************************
 * Prototype function for finding the "default printer" on the system
 * Usage:
 *    qz.findPrinter();
 *    window['qzDoneFinding'] = function() { alert(qz.getPrinter()); };
 ***************************************************************************/
function useDefaultPrinter() {
    if (isLoaded()) {
        // Searches for default printer
        qz.findPrinter();

        // Automatically gets called when "qz.findPrinter()" is finished.
        window['qzDoneFinding'] = function() {
            // Alert the printer name to user
            var printer = qz.getPrinter();
            alert(printer !== null ? 'Default printer found: "' + printer + '"':
                'Default printer ' + 'not found');

            // Remove reference to this function
            window['qzDoneFinding'] = null;
        };
    }
}

/***************************************************************************
 * Prototype function for printing raw commands directly to the filesystem
 * Usage:
 *    qz.append("\n\nHello world!\n\n");
 *    qz.printToFile("C:\\Users\\Jdoe\\Desktop\\test.txt");
 ***************************************************************************/
function printToFile() {
    if (isLoaded()) {
        // Any printer is ok since we are writing to the filesystem instead
        qz.findPrinter();

        // Automatically gets called when "qz.findPrinter()" is finished.
        window['qzDoneFinding'] = function() {
            // Send characters/raw commands to qz using "append"
            // Hint:  Carriage Return = \r, New Line = \n, Escape Double Quotes= \"
            qz.append("A590,1600,2,3,1,1,N,\"QZ Print Plugin " + qz.getVersion() + " sample.html\"\n");
            qz.append("A590,1570,2,3,1,1,N,\"Testing qz.printToFile() function\"\n");
            qz.append("P1\n");

            // Send characters/raw commands to file
            // i.e.  qz.printToFile("\\\\server\\printer");
            //       qz.printToFile("/home/user/test.txt");
            qz.printToFile("C:\\qz-print_test-print.txt");

            // Remove reference to this function
            window['qzDoneFinding'] = null;
        };
    }
}

/***************************************************************************
 * Prototype function for printing raw commands directly to a hostname or IP
 * Usage:
 *    qz.append("\n\nHello world!\n\n");
 *    qz.printToHost("192.168.1.254", 9100);
 ***************************************************************************/
function printToHost() {
    if (isLoaded()) {
        // Any printer is ok since we are writing to a host address instead
        qz.findPrinter();

        // Automatically gets called when "qz.findPrinter()" is finished.
        window['qzDoneFinding'] = function() {
            // Send characters/raw commands to qz using "append"
            // Hint:  Carriage Return = \r, New Line = \n, Escape Double Quotes= \"
            qz.append("A590,1600,2,3,1,1,N,\"QZ Print Plugin " + qz.getVersion() + " sample.html\"\n");
            qz.append("A590,1570,2,3,1,1,N,\"Testing qz.printToHost() function\"\n");
            qz.append("P1\n");

            // qz.printToHost(String hostName, int portNumber);
            // qz.printToHost("192.168.254.254");   // Defaults to 9100
            qz.printToHost("192.168.1.254", 9100);

            // Remove reference to this function
            window['qzDoneFinding'] = null;
        };
    }
}


/***************************************************************************
 * Prototype function for finding the closest match to a printer name.
 * Usage:
 *    qz.findPrinter('zebra');
 *    window['qzDoneFinding'] = function() { alert(qz.getPrinter()); };
 ***************************************************************************/
function findPrinter(name) {

    if (isLoaded()) {
        // Searches for locally installed printer with specified name
        qz.findPrinter(name);

        // Automatically gets called when "qz.findPrinter()" is finished.
        window['qzDoneFinding'] = function() {
            var printer = qz.getPrinter();

            // Alert the printer name to user
            if (printer==null){
                alert('Printer "' + printer + '" not found.');
            }

            // Remove reference to this function
            window['qzDoneFinding'] = null;
        };
    }
}

/***************************************************************************
 * Prototype function for listing all printers attached to the system
 * Usage:
 *    qz.findPrinter('\\{dummy_text\\}');
 *    window['qzDoneFinding'] = function() { alert(qz.getPrinters()); };
 ***************************************************************************/
function populatePrinters() {
    if (isLoaded()) {
        // Searches for a locally installed printer with a bogus name
        qz.findPrinter('\\{bogus_printer\\}');

        // Automatically gets called when "qz.findPrinter()" is finished.
        window['qzDoneFinding'] = function() {
            // Get the CSV listing of attached printers
            var printers = qz.getPrinters().split(',');
            WPOS.print.populatePrintersList(printers);
            // Remove reference to this function
            window['qzDoneFinding'] = null;
            return printers;
        };
    }
}

/***************************************************************************
 * Prototype function for printing raw EPL commands
 * Usage:
 *    qz.append('\nN\nA50,50,0,5,1,1,N,"Hello World!"\n');
 *    qz.print();
 ***************************************************************************/
function printEPL() {
    if (notReady()) { return; }

    // Send characters/raw commands to qz using "append"
    // This example is for EPL.  Please adapt to your printer language
    // Hint:  Carriage Return = \r, New Line = \n, Escape Double Quotes= \"
    qz.append('\nN\n');
    qz.append('q609\n');
    qz.append('Q203,26\n');
    qz.append('B5,26,0,1A,3,7,152,B,"1234"\n');
    qz.append('A310,26,0,3,1,1,N,"SKU 00000 MFG 0000"\n');
    qz.append('A310,56,0,3,1,1,N,"QZ PRINT APPLET"\n');
    qz.append('A310,86,0,3,1,1,N,"TEST PRINT SUCCESSFUL"\n');
    qz.append('A310,116,0,3,1,1,N,"FROM SAMPLE.HTML"\n');
    qz.append('A310,146,0,3,1,1,N,"QZINDUSTRIES.COM"\n');
    qz.appendImage(getPath() + 'img/image_sample_bw.png', 'EPL', 150, 300);

    // Automatically gets called when "qz.appendImage()" is finished.
    window['qzDoneAppending'] = function() {
        // Append the rest of our commands
        qz.append('\nP1,1\n');

        // Tell the applet to print.
        qz.print();

        // Remove reference to this function
        window['qzDoneAppending'] = null;
    };
}

/***************************************************************************
 * Prototype function for printing raw ESC/POS commands
 * Usage:
 *    qz.append('\n\n\nHello world!\n');
 *    qz.print();
 ***************************************************************************/
function printESCP() {
    if (notReady()) { return; }

    // Append a png in ESCP format with single pixel density
    qz.appendImage(getPath() + "img/image_sample_bw.png", "ESCP", "single");

    // Automatically gets called when "qz.appendImage()" is finished.
    window["qzDoneAppending"] = function() {
        // Append the rest of our commands
        qz.append('\nPrinted using qz-print plugin.\n\n\n\n\n\n');

        // Tell the apple to print.
        qz.print();

        // Remove any reference to this function
        window['qzDoneAppending'] = null;
    };
}


/***************************************************************************
 * Prototype function for printing raw ZPL commands
 * Usage:
 *    qz.append('^XA\n^FO50,50^ADN,36,20^FDHello World!\n^FS\n^XZ\n');
 *    qz.print();
 ***************************************************************************/
function printZPL() {
    if (notReady()) { return; }

    // Send characters/raw commands to qz using "append"
    // This example is for ZPL.  Please adapt to your printer language
    // Hint:  Carriage Return = \r, New Line = \n, Escape Double Quotes= \"
    qz.append('^XA\n');
    qz.append('^FO50,50^ADN,36,20^FDPRINTED USING QZ PRINT PLUGIN ' + qz.getVersion() + '\n');
    qz.appendImage(getPath() + 'img/image_sample_bw.png', 'ZPLII');

    // Automatically gets called when "qz.appendImage()" is finished.
    window['qzDoneAppending'] = function() {
        // Append the rest of our commands
        qz.append('^FS\n');
        qz.append('^XZ\n');

        // Tell the apple to print.
        qz.print();

        // Remove any reference to this function
        window['qzDoneAppending'] = null;
    };
}


/***************************************************************************
 * Prototype function for printing syntatically proper raw commands directly
 * to a EPCL capable card printer, such as the Zebra P330i.  Uses helper
 * appendEPCL() to add the proper NUL, data length, escape character and
 * newline per spec:  https://km.zebra.com/kb/index?page=content&id=SO8390
 * Usage:
 *    appendEPCL('A1');
 *    qz.print();
 ***************************************************************************/
function printEPCL()  {
    if (notReady()) { return; }

    appendEPCL('+RIB 4');      // Monochrome ribbon
    appendEPCL('F');           // Clear monochrome print buffer
    appendEPCL('+C 8');        // Adjust monichrome intensity
    appendEPCL('&R');          // Reset magnetic encoder
    appendEPCL('&CDEW 0 0');   // Set R/W encoder to ISO default
    appendEPCL('&CDER 0 0');   // Set R/W encoder to ISO default
    appendEPCL('&SVM 0');      // Disable magnetic encoding verifications
    appendEPCL('T 80 600 0 1 0 45 1 QZ INDUSTRIES');	// Write text buffer
    appendEPCL('&B 1 123456^INDUSTRIES/QZ^789012');	// Write mag strip buffer
    appendEPCL('&E*');         // Encode magnetic data
    appendEPCL('I 10');        // Print card (10 returns to print ready pos.)
    appendEPCL('MO');          // Move card to output hopper

    qz.printToFile("C:\\Users\\Tres\\Desktop\\EPCL_Proper.txt");
    //qz.print();
}

/**
 * EPCL helper function that appends a single line of EPCL data, taking into
 * account special EPCL NUL characters, data length, escape character and
 * carraige return
 */
function appendEPCL(data) {
    if (data == null || data.length == 0) {
        return alert('Empty EPCL data, skipping!');
    }

    // Data length for this command, in 2 character Hex (base 16) format
    var len = (data.length + 2).toString(16);
    len = len.length < 2 ? '0' + len : len;

    // Append three NULs
    qz.appendHex('x00x00x00');
    // Append our command length, in base16 (hex)
    qz.appendHex('x' + len);
    // Append our command
    qz.append(data);
    // Append carraige return
    qz.append('\r');
}

/***************************************************************************
 * Prototype function for printing raw base64 encoded commands
 * Usage:
 *    qz.append64('SGVsbG8gV29ybGQh');
 *    qz.print();
 ***************************************************************************/
function print64() {
    if (notReady()) { return; }

    // Send base64 encoded characters/raw commands to qz using "append64"
    // This will automatically convert provided base64 encoded text into
    // text/ascii/bytes, etc.  This example is for EPL and contains an
    // embedded image.  Please adapt to your printer language
    qz.append64('Ck4KcTYwOQpRMjAzLDI2CkI1LDI2LDAsMUEsMyw3LDE1MixCLCIxMjM0IgpBMzEwLDI2LDAsMywx' +
        'LDEsTiwiU0tVIDAwMDAwIE1GRyAwMDAwIgpBMzEwLDU2LDAsMywxLDEsTiwiUVogUFJJTlQgQVBQ' +
        'TEVUIgpBMzEwLDg2LDAsMywxLDEsTiwiVEVTVCBQUklOVCBTVUNDRVNTRlVMIgpBMzEwLDExNiww' +
        'LDMsMSwxLE4sIkZST00gU0FNUExFLkhUTUwiCkEzMTAsMTQ2LDAsMywxLDEsTiwiUVpJTkRVU1RS' +
        'SUVTLkNPTSIKR1cxNTAsMzAwLDMyLDEyOCz/////////6SSSX///////////////////////////' +
        '//////////6UlUqX////////////////////////////////////8kqkpKP/////////////////' +
        '//////////////////6JUpJSVf//////////////////////////////////9KpKVVU+////////' +
        '//////////////////////////8KSSlJJf5/////////////////////////////////9KUqpVU/' +
        '/7////////////////////////////////9KqUkokf//P///////////////////////////////' +
        '+VKUqpZP//+P///////////////////////////////ElKUlSf///9f/////////////////////' +
        '////////+ipSkqin////y/////////////////////////////+lVUpUlX/////r////////////' +
        '/////////////////qlJKUql/////+n////////////////////////////BFKVKUl//////8v//' +
        '/////////////////////////zVSlKUp///////0f//////////////////////////wiSlSUpf/' +
        '//////q///////////////////////////KqlJUpV///////+R//////////////////////////' +
        '4UlKSpSX///////9T/////////6L///////////////BKlKpSqP///////1X////////0qg/23/V' +
        'VVVVVVf//8CSlJKklf///////kv///////+pS0/JP8AAAAAAB///wFSlSSpV///////+pf//////' +
        '/pUoq+qfwAAAAAAH//+AClSqpUT///////9S///////8pJUlkr+AAAAAAA///4AFJSSSUv//////' +
        '/yl///////KVUpTUv8AAAAAAH///gBKSqlVU////////lX//////6UkqoiU/wAAAAAA///+ABKpJ' +
        'Uko////////JH//////UpIiqlJ/AAAAAAD///wACkSUpJX///////6q//////6pVVSqiv4AAAAAA' +
        'f///AAJVVIqpP///////pI//////pSVtSSq/wAAAAAD///8AAJSlVJVf///////Sp/////8Sq//U' +
        'qL/ttttoAP///wAAUpVSpJ///////+pT/////qkn//UlH/////AB////AABKUSpSX///////5Sn/' +
        '///+lJ//+pS/////4AP///8AABKUkpVP///////ylP////1Kv//+qr/////AA////4AAKVVJUl//' +
        '/////+lKf////KS///8kv////8AH////gAAKSSpJR///////9Kq////9Kv///5Kf////gAf///+A' +
        'AAUlUqov///////1JT////lS////qn////8AD////4AABKpKSqf///////Skj///+kr////JH///' +
        '/wAf////wAACkqUlK///////8pKv///ypf///9V////+AD/////AAAFKUVSj///////wqlP///JT' +
        '////yR////wAP////8AAAFKqkpv///////JSlf//9Sv////U/////AB/////4AAAVIpKRf//////' +
        '+ElV///pS////8of///4AP/////gAAASZVKr///////4qkj///Sn////0v////AA//////AAABUS' +
        'VJH///////glJn//8pP////KH///8AH/////+AAACtUlVf//////+ClRP//qV////9K////gA///' +
        '///4AAACEpJK///////8BSqf/+lX////yr///8AD//////wAAAVUqVH///////gUlU//5Rf////R' +
        'P///gAf//////gAAApKqTP//////8AVSV//pU////6qf//+AD//////+AAAAqkki//////8AEpVL' +
        '/+qP////1L///wAP//////4AAACSVVB/////+AFUpKX/9KP////Sv//+AB///////AAAAEqSgH//' +
        '//+ACkpSUv/lV////6k///4AP//////+AAAAUlSgf////gAJKRUpf/ST////1J///AA///////4A' +
        'AAAVJVB////gAtVFUpV/8lX///+Vf//4AH///////gAAABKSSD///wASSVVJSR/1Vf///8kf//gA' +
        '///////+AAAABVUof//4AElUpKqqv/SL////1L//8AD///////4AAAABJJQ//8AFVJKVKSSP+qj/' +
        '///Kv//gAf///////gAAAAKSpT/+ACkqSlKUkqf5Rf///6S//+AD///////+AAAAAKqpP/ABJKVS' +
        'klKqU/xUf///qp//wAP///////4AAAAAkko+gASVKUlVKlKX/VK///9Sf/+AB////////gAAAACp' +
        'UrgAKqVKVJKSlKf+Sl///0kf/4AP///////+AAAAABSVIAFJUlKqSUpKV/0pX//8qr//AA//////' +
        '//8AAAAACklACSopKSVUqVKX/qpH//okv/4AH////////gAAAAAVVKBUpUqUkkpKSk//SSv/xVK/' +
        '/AAAAAAD////AAAAAAJKWSUpVKVVUqVSp/+qqH9SlR/8AAAAAAH///4AAAAABSUklJSSlJJKUkpf' +
        '/8klQFSo//gAAAAAA////wAAAAABVKqlUkqlSqkqqU//6pUqkkof8AAAAAAB/r//AAAAAAElEpSK' +
        'qSlSSpJKL//pUqpVKr/wAAAAAAP8v/8AAAAAAJLKUqkkpSqkqSVf//yUkpKSv+AAAAAAAfqf/wAA' +
        'AAAAVClKVVUoklUqqp///UpKVVS/wAAAAAAD+S//AAAAAAAlpSkkkpVKkpKSX///JVKTpR+AAAAA' +
        'AAH9X/8AAAAAABRUpVJUqqSpSUlf///SSk/Sv4AAAAAAA/y//wAAAAAAFSVUlSUkUkpUqr////VS' +
        'v9S/AAAAAAAB/3//AAAAAAAFUkpSlJMqqUpJP////13/pT////////////8AAAAAAAEpJSlSqUkk' +
        'pVS////////Un////////////wAAAAAABJVSlSpUqpUpJX///////8q/////////////gAAAAAAC' +
        'pSqkkpKSUpSSP///////5L////////////+AAAAAAACSkVVKSklKpVV///////+SX///////////' +
        '/4AAAAAAAFSqJKlSqqiVSX///////9U/////////////gAAAAAAASpVSlSkklVJU////////yr//' +
        '//////////+AAAAAAAAkpJSklKpKSUp////////kn////////////4AAAAAAABJSqlKqkqUqVf//' +
        '/////5K/////////////gAAAAAAACpUlKpJKUqlI////////1L////////////+AAAAAAAAFSVKS' +
        'SqkpFKX////////SX////////////4AAAAAAAAiklKlSSpTKKv///////9U/////////////wAAA' +
        'AAAABSpSlSqlSiVJ////////pV/////////////AAAAAAAAVUpSkklSlUqX////////Uv///////' +
        '/////8AAAAAAAAkqUpVJJSqpVf///////8pf////////////4AAAAAAAFJKUpKqUpJUT////////' +
        '4r/////////////wAAAAAAAKqVKVKUqSSVX///////+Uv/////////////gAAAAAAASUlKSkpKql' +
        'S////////+qf/////////////AAAAAAAEkpKUlUpJJCn////////iH///////////wAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAH/4B+A8AH/AAAAA' +
        'AAAAAAAAAAAAAA//AAfwD4H4HwAAf/4H4DwB//gAAAAAAAAAAAAAAAAAD/+AB/APgfgfAAB//wfw' +
        'PAf/+AAAAAAAAAAAAAgAAAAP/8AH8AfB+D4AAH//B/g8D//4AAAAAAAAAAAADwAAAA//4A/4B8H4' +
        'PgAAfB+H+DwP4HgAAAAAAAAAAAAPwAAAD4fgD/gHw/w+AAB8D4f8PB+AGAAAAAAAAAAAAA/wAAAP' +
        'g+Af/AfD/D4AAHwPh/48HwAAAAAAAAAAAAAAB/4AAA+D4B98A+P8PAAAfA+Hvjw+AAAAAAAAAAAA' +
        'AAAB/4AAD4PgH3wD4/x8AAB8H4e/PD4AAAAAAAAAAAAAAAB/8AAPh8A+PgPn/nwAAH//B5+8Pg/4' +
        'AH/j/x/4/8f+AA/8AA//wD4+A+eefAAAf/4Hj7w+D/gAf+P/H/j/x/4AA/wAD/+APj4B5554AAB/' +
        '/AeP/D4P+AB/4/8f+P/H/gAD/AAP/wB8HwH3nvgAAH/wB4f8Pw/4AH/j/x/4/8f+AA/8AA//AH//' +
        'Af+f+AAAfAAHg/wfAPgAAAAAAAAAAAAAf/AAD5+A//+B/w/4AAB8AAeD/B+A+AAAAAAAAAAAAAH/' +
        'gAAPj8D//4D/D/AAAHwAB4H8H+D4AAAAAAAAAAAAB/4AAA+H4P//gP8P8AAAfAAHgPwP//gAAAAA' +
        'AAAAAAAP8AAAD4fh+A/A/w/wAAB8AAeA/Af/+AAAAAAAAAAAAA/AAAAPg/HwB8B+B+AAAHwAB4B8' +
        'Af/4AAAAAAAAAAAADwAAAA+B+fAHwH4H4AAAfAAHgHwAf4AAAAAAAAAAAAAIAAAAD4H/8Afgfgfg' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' +
        'AAAAAAAAAAAAAAAAAAAAAAAAClAxLDEK');

    // Tell the apple to print.
    qz.print();
}

/***************************************************************************
 * Prototype function for controlling print spooling between pages
 * Usage:
 *    qz.setEndOfDocument('P1,1\r\n');
 *    qz.setDocumentsPerSpool('5');
 *    qz.appendFile('/path/to/file.txt');
 *    window['qzDoneAppending'] = function() { qz.print(); };
 ***************************************************************************/
function printPages() {
    if (notReady()) { return; }

    // Mark the end of a label, in this case  P1 plus a newline character
    // qz-print knows to look for this and treat this as the end of a "page"
    // for better control of larger spooled jobs (i.e. 50+ labels)
    qz.setEndOfDocument('P1,1\r\n');

    // The amount of labels to spool to the printer at a time. When
    // qz-print counts this many `EndOfDocument`'s, a new print job will
    // automatically be spooled to the printer and counting will start
    // over.
    qz.setDocumentsPerSpool("2");

    qz.appendFile(getPath() + "misc/epl_multiples.txt");

    // Automatically gets called when "qz.appendFile()" is finished.
    window['qzDoneAppending'] = function() {
        // Tell the applet to print.
        qz.print();

        // Remove reference to this function
        window['qzDoneAppending'] = null;
    };
}

/***************************************************************************
 * Prototype function for printing a single XML node containing base64
 * encoded data.
 * Usage:
 *    qz.appendXML('/path/to/file.xml');
 *    window['qzDoneAppending'] = function() { qz.print(); };
 ***************************************************************************/
function printXML() {
    if (notReady()) { return; }

    // Appends the contents of an XML file from a SOAP response, etc.
    // First parameter:  A valid complete URL is required for the XML file.
    // Second parameter:  A valid XML tag/node name containing
    //    base64 encoded data, i.e. <node_1>aGVsbG8gd29ybGQ=</node_1>
    // Example:
    //    qz.appendXML("http://yoursite.com/zpl.xml", "node_1");
    qz.appendXML(getPath() + "misc/zpl_sample.xml", "v7:Image");

    // Automatically gets called when "qz.appendXML()" is finished.
    window['qzDoneAppending'] = function() {
        // Tell the applet to print.
        qz.print();

        // Remove reference to this function
        window['qzDoneAppending'] = null;
    };
}

/***************************************************************************
 * Prototype function for printing hexadecimal formatted raw data
 *
 * Usage:
 *    qz.appendHex('00AABBCCDDEEFF');
 *    qz.appendHex('x00xAAxBBxCCxDDxEExFF');
 *    qz.print();
 ***************************************************************************/
function printHex() {
    if (notReady()) { return; }
    // Since 1.5.4, No backslashes needed (fixes \x00 NUL JavaScript bug)
    // Can be in format "1B00" or "x1Bx00"
    // EPL Sample Provided
    qz.appendHex("4e0d0a713630390d0a513230332c32360d0a42352c32362c");
    qz.appendHex("302c31412c332c372c3135322c422c2231323334220d0a41");
    qz.appendHex("3331302c32362c302c332c312c312c4e2c22534b55203030");
    qz.appendHex("303030204d46472030303030220d0a413331302c35362c30");
    qz.appendHex("2c332c312c312c4e2c22515a205072696e7420506c756769");
    qz.appendHex("6e220d0a413331302c38362c302c332c312c312c4e2c2254");
    qz.appendHex("657374207072696e74207375636365737366756c220d0a41");
    qz.appendHex("3331302c3131362c302c332c312c312c4e2c2266726f6d20");
    qz.appendHex("73616d706c652e68746d6c220d0a413331302c3134362c30");
    qz.appendHex("2c332c312c312c4e2c227072696e7448657828292066756e");
    qz.appendHex("6374696f6e2e220d0a50312c310d0a");

    // Send characters/raw commands to printer
    qz.print();
}

/***************************************************************************
 * Prototype function for printing a text or binary file containing raw
 * print commands.
 * Usage:
 *    qz.appendFile('/path/to/file.txt');
 *    window['qzDoneAppending'] = function() { qz.print(); };
 ***************************************************************************/
function printFile(file) {
    if (notReady()) { return; }

    // Append raw or binary text file containing raw print commands
    qz.appendFile(getPath() + "misc/" + file);

    // Automatically gets called when "qz.appendFile()" is finished.
    window['qzDoneAppending'] = function() {
        // Tell the applet to print.
        qz.print();

        // Remove reference to this function
        window['qzDoneAppending'] = null;
    };
}

/***************************************************************************
 * Prototype function for printing a graphic to a PostScript capable printer.
 * Not to be used in combination with raw printers.
 * Usage:
 *    qz.appendImage('/path/to/image.png');
 *    window['qzDoneAppending'] = function() { qz.printPS(); };
 ***************************************************************************/
function printImage(scaleImage) {
    if (notReady()) { return; }

    // Optional, set up custom page size.  These only work for PostScript printing.
    // setPaperSize() must be called before setAutoSize(), setOrientation(), etc.
    if (scaleImage) {
        qz.setPaperSize("8.5in", "11.0in");  // US Letter
        //qz.setPaperSize("210mm", "297mm");  // A4
        qz.setAutoSize(true);
        //qz.setOrientation("landscape");
        //qz.setOrientation("reverse-landscape");
        //qz.setCopies(3); //Does not seem to do anything
    }

    // Append our image (only one image can be appended per print)
    qz.appendImage(getPath() + "img/image_sample.png");

    // Automatically gets called when "qz.appendImage()" is finished.
    window['qzDoneAppending'] = function() {
        // Tell the applet to print PostScript.
        qz.printPS();

        // Remove reference to this function
        window['qzDoneAppending'] = null;
    };
}

/***************************************************************************
 * Prototype function for printing a PDF to a PostScript capable printer.
 * Not to be used in combination with raw printers.
 * Usage:
 *    qz.appendPDF('/path/to/sample.pdf');
 *    window['qzDoneAppending'] = function() { qz.printPS(); };
 ***************************************************************************/
function printPDF() {
    if (notReady()) { return; }
    // Append our pdf (only one pdf can be appended per print)
    qz.appendPDF(getPath() + "misc/pdf_sample.pdf");

    // Automatically gets called when "qz.appendPDF()" is finished.
    window['qzDoneAppending'] = function() {
        // Tell the applet to print PostScript.
        qz.printPS();

        // Remove reference to this function
        window['qzDoneAppending'] = null;
    };
}

/***************************************************************************
 * Prototype function for printing plain HTML 1.0 to a PostScript capable
 * printer.  Not to be used in combination with raw printers.
 * Usage:
 *    qz.appendHTML('<h1>Hello world!</h1>');
 *    qz.printPS();
 ***************************************************************************/
function printHTML(html) {
    if (notReady()) { return; }

    // Append our image (only one image can be appended per print)
    qz.appendHTML(html);

    qz.printHTML();
}

function testHtmlPrint(){
    // Preserve formatting for white spaces, etc.
    var colA = fixHTML('<h2>*  QZ Print Plugin HTML Printing  *</h2>');
    colA = colA + '<color=red>Version:</color> ' + qz.getVersion() + '<br />';
    colA = colA + '<color=red>Visit:</color> http://code.google.com/p/jzebra';

    // HTML image
    var colB = '<img src="' + getPath() + 'img/image_sample.png">';

    printHtml('<html><table face="monospace" border="1px"><tr height="6cm">' +
        '<td valign="top">' + colA + '</td>' +
        '<td valign="top">' + colB + '</td>' +
    '</tr></table></html>');
}

/***************************************************************************
 * Prototype function for getting the primary IP or Mac address of a computer
 * Usage:
 *    qz.findNetworkInfo();
 *    window['qzDoneFindingNetwork'] = function() {alert(qz.getMac() + ',' +
	*       qz.getIP()); };
 ***************************************************************************/
function listNetworkInfo() {
    if (isLoaded()) {
        // Makes a quick connection to www.google.com to determine the active interface
        // Note, if you don't wish to use google.com, you can customize the host and port
        // qz.getNetworkUtilities().setHostname("qzindustries.com");
        // qz.getNetworkUtilities().setPort(80);
        qz.findNetworkInfo();

        // Automatically gets called when "qz.findPrinter()" is finished.
        window['qzDoneFindingNetwork'] = function() {
            alert("Primary adapter found: " + qz.getMac() + ", IP: " + qz.getIP());

            // Remove reference to this function
            window['qzDoneFindingNetwork'] = null;
        };
    }
}

/***************************************************************************
 * Prototype function for printing an HTML screenshot of the existing page
 * Usage: (identical to appendImage(), but uses html2canvas for png rendering)
 *    qz.setPaperSize("8.5in", "11.0in");  // US Letter
 *    qz.setAutoSize(true);
 *    qz.appendImage($("canvas")[0].toDataURL('image/png'));
 ***************************************************************************/
function printHTML5Page() {
    $("#content").html2canvas({
        canvas: hidden_screenshot,
        onrendered: function() {
            if (notReady()) { return; }
            // Optional, set up custom page size.  These only work for PostScript printing.
            // setPaperSize() must be called before setAutoSize(), setOrientation(), etc.
            qz.setPaperSize("8.5in", "11.0in");  // US Letter
            qz.setAutoSize(true);
            qz.appendImage($("canvas")[0].toDataURL('image/png'));
            // Automatically gets called when "qz.appendFile()" is finished.
            window['qzDoneAppending'] = function() {
                // Tell the applet to print.
                qz.printPS();

                // Remove reference to this function
                window['qzDoneAppending'] = null;
            };
        }
    });
}

/***************************************************************************
 * Prototype function for logging a PostScript printer's capabilites to the
 * java console to expose potentially  new applet features/enhancements.
 * Warning, this has been known to trigger some PC firewalls
 * when it scans ports for certain printer capabilities.
 * Usage: (identical to appendImage(), but uses html2canvas for png rendering)
 *    qz.setLogPostScriptFeatures(true);
 *    qz.appendHTML("<h1>Hello world!</h1>");
 *    qz.printPS();
 ***************************************************************************/
function logFeatures() {
    if (isLoaded()) {
        var logging = qz.getLogPostScriptFeatures();
        qz.setLogPostScriptFeatures(!logging);
        alert('Logging of PostScript printer capabilities to console set to "' + !logging + '"');
    }
}

/***************************************************************************
 * Prototype function to force Unix to use the terminal/command line for
 * printing rather than the Java-to-CUPS interface.  This will write the
 * raw bytes to a temporary file, then execute a shell command.
 * (i.e. lpr -o raw temp_file).  This was created specifically for OSX but
 * may work on several Linux versions as well.
 *    qz.useAlternatePrinting(true);
 *    qz.append('\n\nHello World!\n\n');
 *    qz.print();
 ***************************************************************************/
function useAlternatePrinting() {
    if (isLoaded()) {
        var alternate = qz.isAlternatePrinting();
        qz.useAlternatePrinting(!alternate);
        alert('Alternate CUPS printing set to "' + !alternate + '"');
    }
}


/***************************************************************************
 * Prototype function to list all available com ports availabe to this PC
 * used for RS232 communication.  Relies on jssc_qz.jar signed and in the
 * /dist/ folder.
 *    qz.findPorts();
 *    window['qzDoneFindingPorts'] = function() { alert(qz.getPorts()); };
 ***************************************************************************/
function populateSerialPorts() {
    if (isLoaded()) {
        // Search the PC for communication (RS232, COM, tty) ports
        qz.findPorts();

        // Automatically called when "qz.findPorts()" is finished
        window['qzDoneFindingPorts'] = function() {
            var ports = qz.getPorts().split(",");
            WPOS.print.populatePortsList(ports);
            // Remove reference to this function
            window['qzDoneFindingPorts'] = null;

            WPOS.print.qzReady();
        };
    }
}


/***************************************************************************
 * Prototype function to open the specified communication port for 2-way
 * communication.
 *    qz.openPort('COM1');
 *    qz.openPort('/dev/ttyUSB0');
 *    window['qzDoneOpeningPort'] = function(port) { alert(port); };
 ***************************************************************************/
var portopen = false;

function openSerialPort() {
    if (isLoaded()) {
        if (portopen){
            closeSerialPort();
        }
        var con = WPOS.getLocalConfig();
        qz.openPort(con.recport);
        qz.setSerialProperties(con.recbaud, con.recdatabits, con.recstopbits, con.recparity, con.recflow);
        // Automatically called when "qz.openPort()" is finished (even if it fails to open)
        window['qzDoneOpeningPort'] = function(portName) {
            if (qz.getException()) {
                alert("Could not open port [" + portName + "] \n\t" +
                    qz.getException().getLocalizedMessage());
                qz.clearException();
            } else {
                console.log("Port [" + portName +  "] is open!");
                portopen = true;
            }
        };
    }
}

/***************************************************************************
 * Prototype function to close the specified communication port.
 *    qz.closePort('COM1');
 *    qz.closePort('/dev/ttyUSB0');
 *    window['qzDoneClosingPort'] = function(port) { alert(port); };
 ***************************************************************************/
function closeSerialPort() {
    if (isLoaded()) {
        qz.closePort(document.getElementById("recport").value);

        // Automatically called when "qz.closePort() is finished (even if it fails to close)
        window['qzDoneClosingPort'] = function(portName) {
            if (qz.getException()) {
                alert("Could not close port [" + portName + "] \n\t" +
                    qz.getException().getLocalizedMessage());
                qz.clearException();
            } else {
                alert("Port [" + portName +  "] closed!");
                portopen = false;
            }
        };
    }
}


/***************************************************************************
 * Prototype function to send data to the open port
 *    qz.setSerialBegin(chr(2));
 *    qz.setSerialEnd(chr(13));
 *    qz.setSerialProperties("9600", "7", "1", "even", "none");
 *    qz.send("COM1", "\nW\n");
 ***************************************************************************/
function sendSerialData(serdata) {
    if (isLoaded()) {
        /*if (!portopen){
            qz.openPort(document.getElementById("recport").value);
        }*/
        // Beggining and ending patterns that signify port has responded
        qz.send(document.getElementById("recport").value, serdata+"\n");
        // Automatically called when "qz.send()" is finished waiting for
        // a valid message starting with the value supplied for setSerialBegin()
        // and ending with with the value supplied for setSerialEnd()
        window['qzSerialReturned'] = function(portName, data) {
            if (qz.getException()) {
                alert("Could not send data:\n\t" + qz.getException().getLocalizedMessage());
                qz.clearException();
            } else {
                if (data == null || data == "") {       // Test for blank data
                    alert("No data was returned.")
                } else if (data.indexOf("?") !=-1) {    // Test for bad data
                    alert("Device not ready.  Please wait.")
                } else {                                // Display good data
                    alert("Port [" + portName + "] returned data:\n\t" + data);
                }
            }
        };
    }
}

/***************************************************************************
 ****************************************************************************
 * *                          HELPER FUNCTIONS                             **
 ****************************************************************************
 ***************************************************************************/


/***************************************************************************
 * Gets the current url's path, such as http://site.com/example/dist/
 ***************************************************************************/
function getPath() {
    var path = window.location.href;
    return path.substring(0, path.lastIndexOf("/")) + "/";
}

/**
 * Fixes some html formatting for printing. Only use on text, not on tags!
 * Very important!
 *   1.  HTML ignores white spaces, this fixes that
 *   2.  The right quotation mark breaks PostScript print formatting
 *   3.  The hyphen/dash autoflows and breaks formatting
 */
function fixHTML(html) {
    return html.replace(/ /g, "&nbsp;").replace(/’/g, "'").replace(/-/g,"&#8209;");
}

/**
 * Equivelant of VisualBasic CHR() function
 */
function chr(i) {
    return String.fromCharCode(i);
}

/***************************************************************************
 * Prototype function for allowing the applet to run multiple instances.
 * IE and Firefox may benefit from this setting if using heavy AJAX to
 * rewrite the page.  Use with care;
 * Usage:
 *    qz.allowMultipleInstances(true);
 ***************************************************************************/
function allowMultiple() {
    if (isLoaded()) {
        var multiple = qz.getAllowMultipleInstances();
        qz.allowMultipleInstances(!multiple);
        alert('Allowing of multiple applet instances set to "' + !multiple + '"');
    }
}