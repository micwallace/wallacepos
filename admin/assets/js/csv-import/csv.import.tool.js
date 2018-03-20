$(function() {

    $.widget( "custom.csvImport", {
        // default options
        options: {
            jsonFields: {},
            csvHasHeader: true,
            importOptions: [],
            // callbacks
            onImport: null
        },

        dest_headers: [],

        // the constructor
        _create: function() {
            var widget = this;
            var html = '<input id="file_input" type="file" name="file" accept=".csv, text/csv, text/x-csv, text/plain" />';
            html += '<label for="column_headers">CSV includes column header</label><br/>';
            var cb = $('<br/><input id="column_headers" type="checkbox" '+(this.options.csvHasHeader ? 'checked="checked"' : '')+'/>');
            cb.on('click', function(){
               widget.onHeadersChanged(cb);
            });
            html += '<small>Match source columns on the right with destination columns on the left.<br/>Click the exclude icon to use the default value for that column.</small><br/>';
            html += '<div style="display: inline-flex; width:100%; margin-top: 10px;"><ol id="dest_table" class="list-unstyled list-group">';
            for (var i in this.options.jsonFields){
                if (this.options.jsonFields.hasOwnProperty(i)) {
                    html += '<li id="' + i + '" class="list-group-item">' +
                        (!this.options.jsonFields[i].required ? '<i class="excluded_icon icon icon-ban-circle red" title="Exclude column from import & use default values."></i>' : '') +
                        this.options.jsonFields[i].title + '</li>';
                    widget.dest_headers.push(this.options.jsonFields[i].title);
                }
            }
            html += '</ol><ul id="source_table" class="list-unstyled list-group"></ul></div>';

            for (i=0; i<widget.options.importOptions.length; i++){
                var option = widget.options.importOptions[i];
                html += '<br/><input id="'+option.id+'" type="checkbox" '+(option.checked ? 'checked="checked"' : '')+'/><label for="'+option.id+'">'+option.label+'</label>';
            }

            this.dialog = $( "<div>", {
                html: html,
                "class": "csv-import-dialog"
            }).appendTo( this.element )
                .dialog({
                    title: 'Import CSV',
                    width: "auto",
                    modal: true,
                    open: function(){
                        $(this).css("min-width", "400px");
                    },
                    buttons: [
                        {
                            html: "<i class='icon-save bigger-110'></i>&nbsp; Import",
                            "class" : "btn btn-success btn-xs",
                            click: function(){
                                widget.generateJson();
                            }
                        }
                        ,
                        {
                            html: "<i class='icon-remove bigger-110'></i>&nbsp; Cancel",
                            "class" : "btn btn-xs",
                            click: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    ]
                });

            this.mappingTable = $("#source_table");

            var file_input = $("#file_input");

            cb.insertAfter(file_input);

            $("#dest_table").on('click', '.excluded_icon', this.toggleIgnoreColumn);

            file_input.ezdz({
                validators: {
                    maxSize: 10000000
                },
                accept: function(file) {
                    widget.processCsvFile(file);
                    var remove_btn = $('<i class="icon-remove-circle" style="position: absolute; right:2px; z-index:20; cursor: pointer;"></i>');
                    remove_btn.on('click', function(){
                        $('#file_input').ezdz('preview', null);
                        $(this).remove();
                        widget.csvData = null;
                        $("#source_table").html('');
                    });
                    $(".ezdz-accept div").append(remove_btn);
                },
                reject: function(filname, errors) {
                    if (errors.maxSize) {
                        alert("Only CSV files less than 10mb can be imported.");
                        return;
                    }
                    alert("There was an error loading the file: "+JSON.stringify(errors));
                }
            });

            $('.ezdz-dropzone').css('max-width', '100%');

            this.dialog.dialog('open');

            // bind click events on the changer button to the random method
            /*this._on( this.changer, {
             // _on won't call random when widget is disabled
             click: "random"
             });*/
            this._refresh();
        },

        onHeadersChanged: function(cb){
            this.options.csvHasHeader = cb.is(":checked");
            if (this.csvData!=null)
                this.populateSourceFields();
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
            var headerInfo = [];
            for (i=0; i<headers.length; i++) {
                headerInfo.push({name: headers[i], idx: i});
            }
            if (this.options.csvHasHeader) {
                // try to match headers with destination names
                var dest_headers = this.dest_headers.join(",").replace(/\s/g, "").toLowerCase();
                headerInfo.sort(function (a, b) {
                    var aindex = dest_headers.indexOf(a.name.replace(/\s/g, "").toLowerCase());
                    var bindex = dest_headers.indexOf(b.name.replace(/\s/g, "").toLowerCase());
                    if (aindex == -1)
                        return 1;
                    if (bindex == -1)
                        return -1;
                    return (aindex < bindex ? -1 : (aindex > bindex ? 1 : 0))
                });
            }

            this.mappingTable.html('');
            for (var i=0; i<headerInfo.length; i++){
                this.mappingTable.append('<li id="sourceindex_'+headerInfo[i].idx+'" class="list-group-item">'+
                    headerInfo[i].name+(this.options.csvHasHeader?': '+this.csvData[1][headerInfo[i].idx]:'')+'</li>');
            }
            var widget = this;
            var adjustment;
            this.mappingTable.sortable({
                // set $item relative to cursor position
                onDragStart: function ($item, container, _super) {
                    var offset = $item.offset(),
                        pointer = container.rootGroup.pointer;

                    adjustment = {
                        left: pointer.left - offset.left,
                        top: pointer.top - offset.top
                    };

                    _super($item, container);
                },
                onDrag: function ($item, position) {
                    $item.css({
                        left: position.left - adjustment.left,
                        top: position.top - adjustment.top
                    });
                },
                onDrop: function (item, container, _super) {
                    _super(item, container);
                    widget.setSourceColumnsDisabled();
                }
            });
            widget.setSourceColumnsDisabled();
        },

        setSourceColumnsDisabled: function(){
            var source_table = $("#source_table").find("li");
            source_table.removeClass('excluded');
            var dest_table = $("#dest_table").find("li");
            for (var i=0; i<dest_table.length; i++){
                if (dest_table.eq(i).hasClass('excluded'))
                    source_table.eq(i).addClass('excluded');
            }
        },

        toggleIgnoreColumn: function(event){
            var elem = $(event.target);
            var parent = elem.parent();
            var index = parent.index();
            var source_item = $("#source_table").find("li").eq(index);
            if (parent.hasClass("excluded")){
                parent.removeClass('excluded');
                if (source_item) source_item.removeClass('excluded');
                elem.addClass('red');
                elem.addClass('icon-ban-circle');
                elem.removeClass('green');
                elem.removeClass('icon-ok-circle');
            } else {
                parent.addClass('excluded');
                if (source_item) source_item.addClass('excluded');
                elem.addClass('green');
                elem.addClass('icon-ok-circle');
                elem.removeClass('red');
                elem.removeClass('icon-ban-circle');
            }
        },

        generateJson: function(){
            if (this.csvData==null){
                alert("Please add a valid CSV file before proceeding.");
                return;
            }

            var output = [];
            var fields = this.options.jsonFields;
            var source_table = $("#source_table").find("li");
            // map fields to their source data index
            var i = 0;
            for (var x in fields){
                if (fields.hasOwnProperty(x)){
                    var excluded = source_table.eq(i).hasClass('excluded');
                    var data_idx = -1;
                    // don't add index for fields that are excluded
                    if (!excluded) {
                        data_idx = parseInt(source_table.eq(i).attr('id').split('_')[1]);
                        if (data_idx<0 && fields[x].required===true && !fields[x].hasOwnProperty('value')) {
                            console.log(data_idx);
                            console.log(source_table.eq(i).attr('id'));
                            console.log(fields[x].required);
                            console.log(fields[x].value);
                            alert("The " + x + " column is required and does not have a default value.");
                            return;
                        }
                    }
                    fields[x].idx = data_idx<0 ? -1 : data_idx;
                }
                i++;
            }
            // generate data
            for (i=(this.options.csvHasHeader?1:0); i<this.csvData.length; i++){
                var item = {};
                for (x in fields) {
                    if (fields.hasOwnProperty(x)) {
                        var value = null;
                        if (fields[x].idx<0){
                            if (fields[x].hasOwnProperty('value'))
                                value = fields[x].value;
                        } else {
                            if (this.csvData[i].length>fields[x].idx)
                                value = this.csvData[i][fields[x].idx];
                        }
                        if (value==null && fields[x].required) {
                            alert("The " + x + " column is required and does not have a default value on line "+i+" of the CSV file.");
                            return;
                        }

                        item[x] = value;
                    }
                }
                output.push(item);
            }

            var options = {};
            for (i=0; i<this.options.importOptions.length; i++){
                var option = this.options.importOptions[i];
                options[option.id] = $('#'+option.id).is(':checked');
            }

            if (typeof this.options.onImport == "function")
                this.options.onImport(output, options);

            this.dialog.dialog('close');
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
            delete this;
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