<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Template Manager
        <small>
            <i class="icon-double-angle-right"></i>
            Manage and edit mustache templates
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-12">
        <form class="form-horizontal">
            <div class="form-group">
                <div class="col-sm-5"><label>Template:</label></div>
                <div class="col-sm-5">
                    <select id="template_list" onchange="loadTemplate($(this).val());"></select>
                </div>
            </div>
        </form>
        <hr/>
    </div>
    <div class="col-sm-12">
        <div class="widget-box transparent">
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-2"><label>Type:</label></div>
                        <div class="col-sm-10">
                            <input type="text" id="template_type" readonly />
                            <small>Receipt templates are not used for text-mode ESCP receipt printing</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-2"><label>Name:</label></div>
                        <div class="col-sm-10">
                            <input type="text" id="template_name" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <div id="template" style="height: 440px; width: 100%;">

                            </div>
                        </div>
                    </div>
                    <div class="space-4"></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-warning" type="button" onclick="restoreTemplate();"><i class="icon-undo align-top bigger-125"></i>Restore</button>
        <button class="btn btn-success" type="button" onclick="saveTemplate();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<script language="javascript" src="/admin/assets/js/ace/ace.js"></script>
<script type="text/javascript">
    var templates;
    var templateEditor;
    var curId = null;

    function loadTemplates(){
        templates = WPOS.getJsonData("templates/get");
        var id;
        if (curId!=null && templates.hasOwnProperty(curId)){
            id = curId;
        } else {
            id = Object.keys(templates)[0];
        }
        // load the first template into the form
        loadTemplate(id);
        refreshTemplateList(id);
    }

    function refreshTemplateList(selectedid){
        var list = $("#template_list");
        list.html('');
        for (var i in templates){
            list.append('<option value="'+i+'" '+(i==selectedid?'selected="selected"':'')+'>'+templates[i].name+'</option>');
        }
    }

    function loadTemplate(id){
        var template = templates[id];
        curId = id;
        $("#template_type").val(template.type);
        $("#template_name").val(template.name);
        templateEditor = ace.edit("template");
        templateEditor.setOption("showPrintMargin", false);
        templateEditor.setTheme("ace/theme/chrome");
        templateEditor.getSession().setMode("ace/mode/html");
        templateEditor.setValue(template.template, 1);
        templateEditor.resize(true);
        templateEditor.gotoLine(0, 0, true);
    }

    function saveTemplate(){
        var data = {id: $("#template_list").val(), name: $("#template_name").val(), template: templateEditor.getValue()};
        var result = WPOS.sendJsonData("templates/edit", JSON.stringify(data));
        if (result!==false){
            templates[data.id].name = data.name;
            templates[data.id].template = data.template;
            refreshTemplateList(data.id);
            // update global config
            var template = templates[data.id];
            WPOS.updateConfig('templates~'+data.id, template);
        }
        // hide loader
        WPOS.util.hideLoader();
    }

    function restoreTemplate(){
        var answer = confirm("Are you sure you want to restore the current template?\nThis will destroy all changes you have made.");
        if (answer) {
            WPOS.sendJsonData('templates/restore', '{"filename":"'+templates[curId].filename+'"}');
            loadTemplates();
        }
    }

    $(function(){
        loadTemplates();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>