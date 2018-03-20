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
        return a;
    },

    "timestamp-asc": function ( a, b ) {
        return a - b;
    },

    "timestamp-desc": function ( a, b ) {
        return b - a;
    }
} );

function datatableTimestampRender(type, val, renderFunction) {
    if (type === 'display' || type === 'filter') {
        return renderFunction(val);
    }
    return val;
}