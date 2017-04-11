<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Utilities
        <small>
            <i class="icon-double-angle-right"></i>
            Manage Application Data
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="col-sm-12" style="padding-bottom: 10px;">
    <div class="col-sm-5">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-bullhorn blue" onclick="$('#nodebootbtn').show(); $('#noderestartbtn').removeClass('hidden');"></i>
                    Feed Server
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main no-padding" style="text-align: center;">
                        <div style="padding: 10px;">
                            <h3 style="display: inline-block">Status:</h3>&nbsp;&nbsp;
                            <i id="nodestaticon" class="icon-lightbulb icon-2x"></i>
                            <h4 style="display: inline-block" id="nodestattxt">Loading...</h4>
                        </div>
                        <button id="nodebootbtn" style="display: none;" class="btn btn-success" onclick="startNode();">Start</button>&nbsp;
                        <button id="noderestartbtn" class="btn btn-warning hidden" onclick="restartNode();">Restart</button>
                </div>
                <br/>
                <?php
                    if ($_SERVER['SERVER_NAME']!='demo.wallacepos.com'){
                ?>
                <form class="form-horizontal">
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-3"><label for="feedserver_port">Feed Server port:</label></div>
                        <div class="col-sm-7">
                            <input type="number" id="feedserver_port" /><br/>
                            <small>This is the port that the node.js server operates on.<br/>You may need to change to another port if the default 8080 is already in use by another application.</small>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-3"><label for="feedserver_proxy">Proxied connection:</label></div>
                        <div class="col-sm-7">
                            <input type="checkbox" id="feedserver_proxy" value="true" /><br/>
                            <small>By default, feed server connections are proxied through apache wsproxy.<br/>Uncheck this for a direct connect to the port above.</small>
                            <br/><strong>Un-proxied connections do not work when using HTTPS</strong>
                        </div>
                    </div>
                    <div class="space-4"></div>
                </form>
                <div class="text-center">
                    <button class="btn btn-success" onclick="saveFeedSettings();"><i class="icon-save"></i> Save</button>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-sm-7">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-edit blue"></i>
                    Logs
                </h4>
            </div>

            <div class="widget-body">
                <div class="widget-main no-padding" style="text-align: center;">
                    <select id="loglist" size="10" style="width: 300px;">
                        <option>Loading...</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-sm-12">
    <div class="col-sm-5">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-briefcase blue"></i>
                    Database
                </h4>
            </div>

            <div class="widget-body">
                <h3 style="display: inline-block;">Backup Database:</h3>
                <button class="btn btn-primary" onclick="exportDB();">Backup</button>
                <iframe id="dlframe" style="display: none; width: 0; height: 0;" src=""></iframe>
            </div>
        </div>
    </div>
    <div class="col-sm-7">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-edit blue"></i>
                    Templates
                </h4>
            </div>
            <div class="widget-body" style="height: 200px;">
                <div class="space-8"></div>
                <button class="btn btn-primary" onclick="changehash('!templates');">Template Editor</button>
                <button class="btn btn-primary" onclick="restoreTemplates();">Restore Default Templates</button>
            </div>
        </div>
    </div>
</div>
<div id="logdialog" style="display:none; padding:10px; background-color: white;" title="Log Contents">
    <div id="logcontents" style="font-family: monospace; white-space: pre;"></div>
</div>
<script type="text/javascript">
    function restartNode(){
        var answer = confirm("Are you sure you want to restart the feed server?");
        if (answer){
            doFeedServerRestart();
        }
    }
    function doFeedServerRestart(){
        // show loader
        WPOS.util.showLoader();
        var stat = WPOS.getJsonData("node/restart");
        if (stat==true){
            setUIStatus(true);
            alert("Feed server successfully restarted!");
        } else {
            setUIStatus(false);
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function stopNode(){
        var answer = confirm("Are you sure you want to stop the feed server?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            if (WPOS.getJsonData("node/stop")!==false){
                setUIStatus(false);
            } else {
                setUIStatus(true);
            }
            // hide loader
            WPOS.util.hideLoader();
        }
    }
    function startNode(){
        // show loader
        WPOS.util.showLoader();
        if (WPOS.getJsonData("node/start")!==false){
            setUIStatus(true);
        } else {
            setUIStatus(false);
        }
        // hide loader
        WPOS.util.hideLoader();
    }
    function getNodeStatus(){
        var result = WPOS.getJsonData("node/status");
        if (result!==false){
            setUIStatus(result.status);
            return true;
        }
        return false;
    }
    function setUIStatus(online){
        var nodebtn = $("#nodebootbtn");
        var nodestattxt = $("#nodestattxt");
        var nodestaticon = $("#nodestaticon");
        if (online){
            // set button
            nodebtn.text("Stop");
            nodebtn.removeClass("btn-success");
            nodebtn.addClass("btn-danger");
            nodebtn.attr("onclick", "stopNode();");
            nodebtn.hide(); // hide for production we don't ever want to stop it
            // set status
            nodestattxt.text("Online");
            nodestaticon.removeClass("red");
            nodestaticon.addClass("green");
        } else {
            nodebtn.text("Start");
            nodebtn.removeClass("btn-danger");
            nodebtn.addClass("btn-success");
            nodebtn.attr("onclick", "startNode();");
            nodebtn.show();

            nodestattxt.text("Offline");
            nodestaticon.removeClass("green");
            nodestaticon.addClass("red");
        }
    }

    function populateLogs(){
        var logs = WPOS.getJsonData("logs/list");
        if (logs!==false){
            $("#loglist").html('');
            for (var i in logs){
                $("#loglist").append('<option onclick="viewLog($(this).val())" value="'+logs[i]+'">'+logs[i].split('.')[0]+'</option>');
            }
        }
    }

    function viewLog(filename){
        var log = WPOS.sendJsonData("logs/read", JSON.stringify({filename: filename}));
        if (log!=false){
            log = log.replace(/\n/g, "<br/>");
            $("#logcontents").html(log);
            $("#logdialog").dialog('open');
        }
    }

    function exportDB(){
        $("#dlframe").attr('src', 'https://'+document.location.host+'/api/wpos.php?a=db%2Fbackup');
    }

    function restoreTemplates(){
        var answer = confirm("Are you sure you want to restore the default template files?\nThis will DESTROY all changes you have made to the default templates.");
        if (answer)
            WPOS.getJsonData('templates/restore');
    }

    function loadFeedSettings(){
        var settings = WPOS.getConfigTable().general;
        $("#feedserver_port").val((settings.hasOwnProperty('feedserver_port') ? settings.feedserver_port : 8080));
        $("#feedserver_proxy").prop("checked", (settings.hasOwnProperty('feedserver_proxy') && settings.feedserver_proxy));
    }

    function saveFeedSettings(){
        var answer = confirm("Are you sure you want to save the feed server settings?\nYou may need to restart devices for the settings to take effect.");
        if (answer) {
            WPOS.util.showLoader();

            var port = parseInt($("#feedserver_port").val());
            var proxy = $("#feedserver_proxy").prop("checked") == true;

            var result = WPOS.sendJsonData("settings/general/set", JSON.stringify({
                feedserver_port: port,
                feedserver_proxy: proxy
            }));
            if (result !== false) {
                WPOS.updateConfig('general~feedserver_port', port);
                WPOS.updateConfig('general~feedserver_proxy', proxy);
                doFeedServerRestart();
            }

            WPOS.util.hideLoader();
        }
    }

    $(function(){
        $("#logdialog").dialog({
            height       : 420,
            width        : 'auto',
            maxWidth: 650,
            modal        : true,
            closeOnEscape: false,
            autoOpen     : false,
            open         : function (event, ui) {
            },
            close        : function (event, ui) {
            },
            create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "650px");
            }
        });

        loadFeedSettings();
        if (getNodeStatus()){
            populateLogs();
        }
        // hide loader
        WPOS.util.hideLoader();
    });

</script>