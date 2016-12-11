<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        Information
        <small>
            <i class="icon-double-angle-right"></i>
            System Information
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Information</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <div class="row">
                    <div class="col-xs-2">Version: </div>
                    <div id="app_version" class="col-xs-10"></div>
                    <div class="space-30"></div>
                </div>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Support & Development</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <p>Please refer here for information on support & development:<br/>
                <a href="https://wallacepos.com/get" target="_blank">https://wallacepos.com/get</a></p>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">License</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <div class="row">
                    <iframe frameborder="0" style="width: 100%; height: 100%; min-height: 600px; max-width: 600px; margin: 0 auto; position: relative;" height="100%" width="100%" src="https://www.gnu.org/licenses/lgpl-3.0.txt"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function(){
        $("#app_version").text(WPOS.getConfigTable().general.version);
        WPOS.util.hideLoader();
    });
</script>