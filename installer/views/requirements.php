<div style="text-align: center;">
<?php
  if ($curversion>0) {
?>
      <ul class="breadcrumb">
          <li><strong>Check Requirements</strong></li>
          <li>Upgrade</li>
      </ul>
<?php
  } else {
?>
      <ul class="breadcrumb">
          <li><strong>Check Requirements</strong></li>
          <li>Configure Database</li>
          <li>Initial Setup</li>
          <li>Install System</li>
      </ul>
<?php
  }
?>
</div>
<div>
    <h4>Requirements</h4>
    <div class="space-4"></div>
    <div>
        <ul class="list-unstyled spaced">
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['app_root']?"green":"red"); ?>"></i>
                <?php echo("Correct Application Root".($deps['app_root']?"":"<br/><small>WallacePOS must be installed in the root directory of it's own virtual host</small>")); ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['apache']?"green":"red"); ?>"></i>
                <?php echo("Apache ".($deps['apache']?"":"2.4.7 required, ").$deps['apache_version']." installed"); ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['apache_rewrite']?"green":"red"); ?>"></i>
                Apache rewrite module
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['apache_wstunnel']?"green":"red"); ?>"></i>
                Apache proxy_wstunnel module
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['php']?"green":"red"); ?>"></i>
                <?php echo("PHP ".($deps['php']?"":"5.4 required, ").$deps['php_version']." installed"); ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['php_pdomysql']?"green":"red"); ?>"></i>
                PHP pdo_mysql extension
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['php_gd']?"green":"red"); ?>"></i>
                PHP gd extension
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['php_curl']?"green":"red"); ?>"></i>
                PHP cURL extension
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['node']?"green":"red"); ?>"></i>
                Node.js installed
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['node_socketio']?"green":"red"); ?>"></i>
                Node.js Socket.IO library installed
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['node_redirect']?"green":"red"); ?>"></i>
                Apache Configuration: Node.js (Proxy Web Socket Tunnel)
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($https=(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!="off")?"green":"red"); ?>"></i>
                Apache Configuration: HTTPS <?php echo($https?"Active":"is recommended") ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['permissions_root']?"green":"red"); ?>"></i>
                Folder Permissions: App Root / <?php echo($deps['permissions_root']?"is writable":"must be writable") ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['permissions_files']?"green":"red"); ?>"></i>
                File Permissions: Application files <?php echo($deps['permissions_files']?"are not writable":"must not be writable") ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['permissions_docs']?"green":"red"); ?>"></i>
                File Permissions: Documents (/docs/) <?php echo($deps['permissions_docs']?" are writable":"files must be writable") ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['permissions_config']?"green":"red"); ?>"></i>
                File Permissions: Config files <?php echo($deps['permissions_config']?" are writable":" .config.json && .dbconfig.json (/library/wpos/) must be writable") ?>
            </li>
            <li>
                <i class="icon icon-large icon-check <?php echo($deps['all']?"green":"red"); ?>"></i>
                <strong><?php echo($deps['all']?"All Requirements Met":"Not all requirements met, correct the above to proceed"); ?></strong>
            </li>
            <?php
                if(!$deps['all']) {
            ?>
            <li>
                <label><input type="checkbox" data-reqs-met="<?php echo($deps['all']); ?>" onchange="$('#next-button').prop('disabled', ($(this).is(':checked')?false:true))" />
                &nbsp;Ignore requirements check</label>
            </li>
            <?php
                }
            ?>
        </ul>
        <hr/>
        <div style="height: 40px;">
            <button class="pull-left btn btn-primary" onclick="document.location.reload();">Refresh</button>
            <form method="post">
                <input type="hidden" name="<?php echo($curversion>0?"doupgrade":"screen"); ?>" value="2">
                <button id="next-button" type="submit" class="pull-right btn btn-primary" <?php echo($deps['all']?"":"disabled='disabled'"); ?> ><?php echo($curversion>0?"Upgrade":"Next"); ?></button>
            </form>
        </div>
    </div>
</div>

