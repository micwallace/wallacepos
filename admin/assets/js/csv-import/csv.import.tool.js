$(function() {

    $.widget( "custom.csvImport", {
        // default options
        options: {
            jsonFields: {},
            csvHasHeader: true,
            // callbacks
            onImport: null
        },

        // the constructor
        _create: function() {
            var widget = this;

            var html = '<input id="file_input" type="file" name="file" accept="text/csv" />';
            html += '<div style="display: inline-flex; width:100%;"><ol id="dest_table">';
            for (var i in this.options.jsonFields){
                if (this.options.jsonFields.hasOwnProperty(i))
                    html += '<li id="'+i+'">'+this.options.jsonFields[i]+'</li>';
            }
            html += '</ol><ul id="source_table"></ul></div>';

            this.dialog = $( "<div>", {
                html: html,
                "class": "csv-import-dialog"
            }).appendTo( this.element )
                .dialog({
                    title: 'Import CSV',
                    width: "auto",
                    open: function(){
                        $(this).css("min-width", "425px");
                    }
                });

            this.mappingTable = $("#source_table");

            $("#file_input").ezdz({
                validators: {
                    maxWidth: 600,
                    maxHeight: 400,
                    maxSize: 1000000
                },
                accept: function(file) {
                    widget.processCsvFile(file);
                },
                reject: function() {
                    alert("Only CSV files less than 10mb can be imported.");
                }
            });

            this.dialog.dialog('open');

            // bind click events on the changer button to the random method
            /*this._on( this.changer, {
             // _on won't call random when widget is disabled
             click: "random"
             });*/
            this._refresh();
        },

        csvData: null,

        processCsvFile: function(file){
            // read file
            var reader = new FileReader();
            var widget = this;
            reader.onload = function(){
                widget.csvData = $.csv.toArrays(reader.result);
                if (widget.csvData!=null){
                    widget.populateSourceFields();
                } else {
                    alert("Could not parse the CSV file");
                }
            };
            reader.onerror = function(){
                alert("Could not read the CSV file");
            };
            reader.readAsText(file);
        },

        populateSourceFields: function(){
            var headers = this.csvData[0];
            this.mappingTable.html('');
            for (var i=0; i<headers.length; i++){
                this.mappingTable.append('<li id="sourceindex_'+i+'">'+
                    headers[i]+(this.options.csvHasHeader?': '+this.csvData[1][i]:'')+'</li>');
            }
            this.mappingTable.sortable();
        },

        // called when created, and later when changing options
        _refresh: function() {
            // trigger a callback/event
            this._trigger( "change" );
        },

        // events bound via _on are removed automatically
        // revert other modifications here
        _destroy: function() {
            // remove generated elements
            this.dialog.remove();
        },

        // _setOptions is called with a hash of all options that are changing
        // always refresh when changing options
        _setOptions: function() {
            // _super and _superApply handle keeping the right this-context
            this._superApply( arguments );
            this._refresh();
        },

        // _setOption is called for each individual option that is changing
        _setOption: function( key, value ) {
            // TODO: prevent invalid
            this._super( key, value );
        }
    });

    // initialize with two customized options
    /*$( "body" ).csvImport({
        jsonFields: {
            'code':'Stock Code',
            'name':'Name',
            'qty':'Default Quantity',
            'unit': 'Unit Price',
            'taxid': 'Tax Rule ID',
            'supplierid': 'Supplier ID',
            'categoryid': 'Category ID'
        },
        csvHasHeader: true,
        // callbacks
        onImport: function(jsondata){
            console.log(JSON.stringify(jsondata));
        }
    });*/

});