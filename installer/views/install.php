<div style="text-align: center;">
<?php
    if ($_REQUEST['doupgrade']) {
        ?>
        <ul class="breadcrumb">
            <li>Check Requirements</li>
            <li><strong>Upgrade</strong></li>
        </ul>
<?php
    } else {
?>
        <ul class="breadcrumb">
            <li>Check Requirements</li>
            <li>Configure Database</li>
            <li>Initial Setup</li>
            <li><strong>Install System</strong></li>
        </ul>
<?php
    }
?>
</div>
<div style="text-align: center;">
    <div id="install_view">
        <h4>Installing System</h4>
        <h4>Do not leave the page until the process is complete</h4>
    </div>
    <div id="complete_view" class="hide">
        <h4>Installation Complete</h4>
        <h4>Check the below frame for errors</h4>
    </div>
    <iframe id="installframe" style="width: 100%; height: 200px; overflow: auto;"></iframe>
</div>
<script>
    $(function(){
        var frame = $("#installframe");
        frame.load(function(){
            $("#install_view").addClass('hide');
            $("#complete_view").removeClass('hide');
        });
        frame.attr('src', "<?php echo(isset($_REQUEST['doupgrade'])?"/installer?upgrade&version=1.2":"/installer?install"); ?>");
    });
</script>