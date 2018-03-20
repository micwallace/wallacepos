<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Invoice Settings
        <small>
            <i class="icon-double-angle-right"></i>
            Manage invoice & accounts settings
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-md-12">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">General</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                <div class="form-group">
                    <div class="col-sm-2"><label>Default Due Date:</label></div>
                    <div class="col-sm-6">
                    <select id="defaultduedtnum">
                        <option value="+1 ">1</option>
                        <option value="+2 ">2</option>
                        <option value="+3 ">3</option>
                        <option value="+4 ">4</option>
                        <option value="+5 ">5</option>
                        <option value="+5 ">6</option>
                        <option value="+5 ">7</option>
                        <option value="+5 ">8</option>
                        <option value="+5 ">9</option>
                        <option value="+5 ">10</option>
                        <option value="+5 ">11</option>
                        <option value="+5 ">12</option>
                    </select>
                        <select id="defaultduedtunit">
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                        </select>
                    </div>
                </div>
                <div class="space-4"></div>
                <div class="form-group">
                    <div class="col-sm-2"><label>Default Template:</label></div>
                    <div class="col-sm-6">
                        <select id="defaulttemplate"></select><br/>
                    </div>
                </div>
                <div class="space-4"></div>
                <div class="form-group">
                    <div class="col-sm-2"><label>Payment Instructions:</label></div>
                    <div class="col-sm-6" style="max-width: 650px;">
                        <div id="payinst" style="height: 175px; border: 1px solid #E5E5E5;" class="wysiwyg-editor">

                        </div>
                    </div>
                </div>
                    <div class="form-group">
                        <div class="col-sm-2"><label>Email Message:</label></div>
                        <div class="col-sm-6" style="max-width: 650px;">
                            <div id="emailmsg" style="height: 175px; border: 1px solid #E5E5E5;" class="wysiwyg-editor">

                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-12 align-center form-actions">
        <button class="btn btn-success" type="button" onclick="saveSettings();"><i class="icon-save align-top bigger-125"></i>Save</button>
    </div>
</div>
<script type="text/javascript">
    var options;

    function saveSettings(){
        // show loader
        WPOS.util.showLoader();
        var data = {};
        $("form :input").each(function(){
            if ($(this).prop('id')=="defaultduedtnum" || $(this).prop('id')=="defaultduedtunit") {
                if ($(this).prop('id')=="defaultduedtnum")
                    data['defaultduedt'] = $(this).val()+$("#defaultduedtunit").val();
            } else {
                if ($(this).prop('id')!=="")
                    data[$(this).prop('id')] = $(this).val();
            }
        });
        data['payinst'] = $("#payinst").html();
        data['emailmsg'] = $("#emailmsg").html();
        var result = WPOS.sendJsonData("settings/invoice/set", JSON.stringify(data));
        if (result !== false){
            WPOS.setConfigSet('invoice', result);
        }
        // hide loader
        WPOS.util.hideLoader();
    }

    function loadSettings(){
        options = WPOS.getJsonData("settings/invoice/get");
        // load option values into the form
        for (var i in options){
            if (i=="defaultduedt"){
                var parts = options[i].split(" ");
                $("#defaultduedtnum").val(parts[0]+" ");
                $("#defaultduedtunit").val(parts[1]);
            } else {
                $("#"+i).val(options[i]);
            }
        }
        refreshTemplateList(options.defaulttemplate);
        $("#payinst").html(options.payinst);
        $("#emailmsg").html(options.emailmsg);
        $("#bizlogoprev").attr("src", options.bizlogo);
    }

    function refreshTemplateList(selectedid){
        var templates = WPOS.getConfigTable()['templates'];
        var list = $("#defaulttemplate");
        list.html('');
        for (var i in templates){
            if (templates[i].type=="invoice")
                list.append('<option value="'+i+'" '+(i==selectedid?'selected="selected"':'')+'>'+templates[i].name+'</option>');
        }
    }

    $('#bizlogofile').on('change',uploadLogo);
    $('#bizlogo').on('change',function(e){
        $("#bizlogoprev").prop("src", $(e.target).val());
    });

    function uploadLogo(event){
        WPOS.uploadFile(event, function(data){
            $("#bizlogo").val(data.path);
            $("#bizlogoprev").prop("src", data.path);
            saveSettings();
        }); // Start file upload, passing a callback to fire if it completes successfully
    }

    $(function(){
        // email wysiwyg
        $('.wysiwyg-toolbar').remove();
        $('.wysiwyg-editor').ace_wysiwyg();
        $(".wysiwyg-toolbar").addClass('wysiwyg-style2');

        loadSettings();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>