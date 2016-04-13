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
                            <div id="template" style="height: 175px; border: 1px solid #E5E5E5;" class="wysiwyg-editor">

                            </div>
                        </div>
                    </div>
                    <div class="space-4"></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-success" type="button" onclick="saveTemplate();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<script type="text/javascript">
    var templates;

    function loadTemplates(){
        templates = WPOS.getJsonData("templates/get");
        var first = Object.keys(templates)[0];
        // load the first template into the form
        loadTemplate(first);
        refreshTemplateList(first);
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
        $("#template_type").val(template.type);
        $("#template_name").val(template.name);
        $("#template").html(template.template.replace('<style type="text/css">', '<style type="text/css" scoped>'));
    }

    function saveTemplate(){
        var data = {id: $("#template_list").val(), name: $("#template_name").val(), template: $("#template").html()};
        var result = WPOS.sendJsonData("templates/edit", JSON.stringify(data));
        if (result!==false){
            templates[data.id].name = data.name;
            templates[data.id].template = data.template;
            refreshTemplateList(data.id);
            // update global config
            var template = templates[data.id];
            delete template.template;
            WPOS.updateConfig('templates~'+data.id, template);
        }
        // hide loader
        WPOS.util.hideLoader();
    }

    $(function(){
        // email wysiwyg
        $('.wysiwyg-toolbar').remove();
        $('.wysiwyg-editor').ace_wysiwyg();
        $(".wysiwyg-toolbar").addClass('wysiwyg-style2');
        loadTemplates();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>