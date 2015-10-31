jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "currency-pre": function ( a ) {
        a = (a==="-") ? 0 : a.replace( /[^\d\-\.]/g, "" );
        if (a=="") a=0;
        return parseFloat( a );
    },

    "currency-asc": function ( a, b ) {
        return a - b;
    },

    "currency-desc": function ( a, b ) {
        return b - a;
    },

    "timestamp-pre": function ( a ) {
        a = $(a).filter(function() {
            return $(this).is('.timestamp')
        }).text();
        return parseInt( a );
    },

    "timestamp-asc": function ( a, b ) {
        return a - b;
    },

    "timestamp-desc": function ( a, b ) {
        return b - a;
    }
} );