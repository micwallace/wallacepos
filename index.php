<!DOCTYPE html>
<html manifest="/wpos.appcache">
<head>
    <meta name="copyright" content="Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html>" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WallacePOS</title>

    <link rel="shortcut icon" href="/assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png">
    <!-- UI FRAMEWORK STYLES -->
    <link type="text/css" rel="stylesheet" href="assets/css/wpos.css"/>
    <link rel="stylesheet" href="/assets/css/jquery-ui-1.10.3.full.min.css"/>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/assets/css/font-awesome.min.css"/>
    <!--[if IE 7]>
    <link rel="stylesheet" href="/assets/css/font-awesome-ie7.min.css"/>
    <![endif]-->
    <!-- fonts -->
    <link rel="stylesheet" href="/assets/css/ace-fonts.css"/>
    <!-- ace styles -->
    <link rel="stylesheet" href="/assets/css/ace.min.css"/>
    <link rel="stylesheet" href="/assets/css/ace-rtl.min.css"/>
    <!--[if lte IE 8]>
    <link rel="stylesheet" href="/assets/css/ace-ie.min.css"/>
    <![endif]-->
    <link rel="stylesheet" href="/assets/libs/datatables/datatables.min.css"/>

    <!-- UI FRAMEWORK SCRIPTS -->
    <!--[if !IE]> -->
        <script type="text/javascript" src="/assets/js/jquery-2.2.0.min.js"></script>
    <!-- <![endif]-->
    <!--[if IE]>
        <script type="text/javascript" src="assets/js/jquery-1.10.2.min.js"></script>
    <![endif]-->
    <script type="text/javascript">
        if ("ontouchend" in document) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
    </script>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="/assets/js/typeahead-bs2.min.js"></script>
    <script src="/assets/js/jquery-ui-1.10.3.full.min.js"></script>
    <script src="/assets/js/jquery.mustache.js"></script>

    <!-- Wpos Libraries -->
    <script type="text/javascript" src="/assets/js/wpos/core.js"></script>
    <script type="text/javascript" src="/assets/js/wpos/sales.js"></script>
    <script type="text/javascript" src="/assets/js/wpos/transactions.js"></script>
    <script type="text/javascript" src="/assets/js/wpos/reports.js"></script>
    <script type="text/javascript" src="/assets/js/wpos/print.js"></script>
    <script type="text/javascript" src="/assets/js/wpos/orders.js"></script>
    <script type="text/javascript" src="/assets/js/wpos/utilities.js"></script>
    <script type="text/javascript" src="/assets/libs/webprint.js"></script>
    <!-- Websocket library -->
    <script type="text/javascript" src="/assets/libs/socketio/socket.io-1.4.5.js"></script>
    <!-- POS Keypad -->
    <link type="text/css" href="/assets/libs/jquery-keypad/jquery.keypad.css" rel="stylesheet">
    <script type="text/javascript" src="/assets/libs/jquery-keypad/jquery.plugin.min.js"></script>
    <script type="text/javascript" src="/assets/libs/jquery-keypad/jquery.keypad.min.js"></script>
    <!-- Datatables -->
    <script src="/assets/libs/datatables/datatables.min.js"></script>
    <script src="/assets/libs/datatables/datatableSorting.js"></script>

</head>
<?php
  $default_locale=locale_get_default(); // as per php.ini
  if (!isset($default_locale)) {
    $default_locale=$_GET['locale'];
  }
  if (isset($default_locale)) {
    $language = $default_locale;
    $put_env_result=putenv("LANGUAGE=$language");
    if ($put_env_result) {
      setlocale(LC_ALL, $language.'.utf8');

      $domain = 'messages';
      bindtextdomain($domain, "./Locale");
      bind_textdomain_codeset($domain, "UTF-8"); 
      textdomain($domain);
    }
  }
?>
<body style="overflow: hidden;">
<div id="wrapper">
<ul class="navbar overflow-hidden">
    <li onclick="setItemListPadding();"><a href="#tabs-1"><?php echo _("Till") ?></a></li>
    <li onclick="WPOS.trans.setupTransactionView();"><a href="#tabs-2" ><?php echo _("Sales") ?></a></li>
    <li onclick="WPOS.reports.populateOverview();"><a href="#tabs-3"><?php echo _("Reports") ?></a></li>
    <li><a href="#tabs-4"><?php echo _("Settings") ?></a></li>
    <button onClick="WPOS.logout();" style="float:right; height: 45px; padding: 6px 8px;" class="btn logout_btn"><i class="glyphicon glyphicon-log-out"></i><?php echo _("Logout") ?></button>
    <div style="float:right; vertical-align: middle; margin-top: 10px; text-align: right;">
        <button title="Open Administration Dashboard" onClick="window.open('/admin');" style="margin-right: 4px; margin-left: 4px;" class="btn btn-xs btn-primary"><i class="icon icon-cog">&nbsp;<?php echo _("Admin") ?></i></button>
        <button title="Lock terminal or switch user" id="switch_user_btn" onClick="WPOS.lockSession();" style="margin-right: 4px; margin-left: 4px;" class="btn btn-xs btn-primary"><i class="icon icon-lock"></i>&nbsp;<?php echo _("Lock") ?></button>
        <button title="Expand window" id="window_width_toggle" onClick="expandWindow();" style="margin-right: 4px; margin-left: 6px;" class="btn btn-xs btn-primary"><i class="icon icon-expand"></i></button>
    </div>
</ul>
<div id="tabs-1">
    <div id="sales">
        <div style="width:100%;">
            <div id="watermark"></div>
            <div id="items" style="padding:0;" class="ui-widget-content">
                <div style="margin-bottom: 10px;">
                    <div class="inline">
                        <div class="inline">
                            <label><div class="hlabel inline" ><?php echo _("Stock Code") ?>:&nbsp;</div><input style="width: 100px;" id="codeinput" type="text" onkeypress="if(event.keyCode=='13' || event.keyCode=='7'){ WPOS.items.addItemFromStockCode($('#codeinput').val()); }"/></label>
                            <button class="btn btn-xs field-button field-button-attach" style="vertical-align: top;" onclick="WPOS.items.addItemFromStockCode($('#codeinput').val());"><?php echo _("Add") ?></button>
                        </div>
                        &nbsp;&nbsp;
                        <div class="inline">
                            <label><div class="hlabel inline"><?php echo _("Stock Search") ?>:&nbsp;</div><input id="itemsearch" type="text"/></label>
                        </div>
                    </div>
                    <div class="wscan-btn inline" style="display: none !important;">
                        <button class="btn btn-sm inline field-button" onclick="$.wscan.scanOnce();"><i class="icon-barcode" style="vertical-align: top;">&nbsp;x1</i></button>
                        <button class="btn btn-sm inline field-button" onclick="$.wscan.startScanning();"><i class="icon-barcode" style="vertical-align: top;"></i></button>
                    </div>
                </div>
                <div id="items_contain" style="overflow: auto; max-height: 458px;">
                <table style="width: 100%; margin-bottom: 0;" class="table table-striped table-bordered">
                    <thead class="table-header">
                    <tr style="text-align: left">
                        <th><?php echo _("Qty") ?></th>
                        <th><?php echo _("Name") ?></th>
                        <th><?php echo _("Unit") ?></th>
                        <th><?php echo _("Options") ?></th>
                        <th><?php echo _("Tax") ?></th>
                        <th><?php echo _("Price") ?></th>
                        <th style="text-align: center;">X</th>
                    </tr>
                    </thead>
                    <tbody id="itemtable" style="overflow:auto;">
                        <tr class="order_row">
                            <td style="background-color:#438EB9; color:#FFF;" colspan="7"><h4 style="text-align: center; margin: 0;"><?php echo _("New Order") ?></h4></td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <span id="invaliditemnotice" style="float: left; display: none;" class="text-warning bigger-110 red">
                    <i class="icon-warning-sign"></i>&nbsp;<?php echo _("Invalid items marked in red will not be added to the sale") ?>
                </span>
                <button style="float:right; font-size: 14px !important; margin-top:10px;" class="btn btn-sm btn-primary" onClick="WPOS.items.addManualItemRow();"><?php echo _("Add Item") ?></button>
            </div>
            <div id="totals">
                <div id="totals-container">
                    <div class="totalsbox">
                        <div>
                            <div class="totalslabel"><?php echo _("Subtotal") ?>:</div>
                            <div class="totalsval"><span id="subtotal">$0.00</span></div>
                        </div>
                        <div>
                            <div class="totalslabel"><?php echo _("Discount") ?>:</div>
                            <div class="totalsval"><input onChange="WPOS.sales.updateDiscount();" size="2" id="salediscount" type="text" class="numpad" value="0" autocomplete="off"/><strong>%</strong>&nbsp;<span id="discounttxt">($0.00)</span></div>
                        </div>
                    </div>
                    <div class="totalsbox">
                        <div>
                            <div class="totalslabel"><?php echo _("Tax") ?>:</div>
                            <div class="totalsval"><span id="totaltax">$0.00</span></div>
                        </div>
                        <div>
                            <div class="totalslabel"><?php echo _("Total") ?>:</div>
                            <div class="totalsval"><span id="total">$0.00</span></div>
                        </div>
                    </div>
                    <div class="order_terminal_options" style="margin-top: 10px;">
                        <label class="fixedlabel" style="margin-right: 5px;"><input type="radio" id="radio_takeaway" name="eatin" value="0" onclick="$('#tablenumber').val(0).prop('readonly', true);" checked="checked"/><b>&nbsp;<?php echo _("Take Away") ?></b></label>
                        <label class="fixedlabel" style="margin-right: 5px;"><input type="radio" id="radio_eatin" name="eatin" value="1" onclick="$('#tablenumber').prop('readonly', false);"/><b>&nbsp;<?php echo _("Eat In") ?></b></label>

                        <label class="fixedlabel"><b><?php echo _("Table") ?> #:</b><input class="numpad" type="text" id="tablenumber" value="0" size="3" onclick="$('#radio_eatin').prop('checked', true); $(this).prop('readonly', false);" readonly="readonly"/></label>
                    </div>
                    <table style="margin-top: 10px; margin-bottom: 10px; width: 100%; text-align: left;" class="ui-widget-content">
                        <tr>
                            <th><?php echo _("Customer Email") ?>:</th>
                            <td>
                                <input id="custemail" type="email" onchange="WPOS.items.processNewEmail();" autocomplete="off"/>
                                <input id="custid" type="hidden" value="0" />
                                <button class="btn btn-xs btn-primary field-button field-button-attach" onclick="WPOS.items.openCustomerDialog($('#custid').val());" ><?php echo _("Details") ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo _("Email Receipt") ?>:</th>
                            <td>
                                <input type="checkbox" id="emailreceipt" disabled="disabled" autocomplete="off" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo _("Notes") ?>:</th>
                            <td><textarea id="salenotes" name="salenotes" autocomplete="off"></textarea></td>
                        </tr>
                    </table>
                </div>
                <div id="regkey-container" class="pull-right">
                        <div id="regkey-inner">
                        <div id="registerkeydiv">
                            <button class="regbtn btn btn-success" onClick="WPOS.sales.showPaymentDialog();"><?php echo _("Process") ?></button>
                            <button class="regbtn btn btn-danger" onClick="WPOS.sales.userAbortSale();"><?php echo _("Abort") ?></button>
                            <button class="regbtn btn" onclick="WPOS.print.openCashDraw();"><?php echo _("Till") ?></button>
                            <button class="regbtn btn" onclick="WPOS.trans.recallLastTransaction();"><?php echo _("Recall") ?></button>
                        </div>
                        <div>
                            <button onclick="WPOS.trans.showTransactionView();" class="regbtn btn btn-primary"
                                    style="width:153px; height:50px; display:inline-block;"><?php echo _("Transactions") ?></button>
                        </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
    <div id="ibox" class="widget-body">
        <div id="iboxhandle" class="header-color-blue"><i class="icon-chevron-left white"></i></div>
        <div id="iboxitem_contain">
            <div class="row">
            <div id="iboxitems">
            </div>
            </div>
        </div>
    </div>
</div>
<div id="tabs-2">
    <div id="remtransearch">
        <label><?php echo _("Global Ref Lookup") ?>:</label><br/>
        <label><?php echo _("Reference") ?>: <input id="remsearchref" type="text"/></label>
        <button onclick="WPOS.trans.searchRemote();" class="btn btn-sm btn-primary" style="vertical-align:top;"><?php echo _("Go") ?></button>
        <button onclick="WPOS.trans.clearSearch();" class="btn btn-sm btn-warning" style="vertical-align:top;">X</button>
    </div>
    <table id="transactiontable" style="text-align: left; width:100%;" class="table table-striped table-bordered table-hover dt-responsive">
        <thead class="table-header">
        <tr>
            <th data-priority="1">GID</th>
            <th data-priority="7">Ref</th>
            <th data-priority="6"><?php echo _("Device / Location") ?></th>
            <th data-priority="8">#<?php echo _("Items") ?></th>
            <th data-priority="5"><?php echo _("Total") ?></th>
            <th data-priority="3"><?php echo _("Sale Time") ?></th>
            <th data-priority="4"><?php echo _("Status") ?></th>
            <th data-priority="2"></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<div id="tabs-3">
    <div class="row">
    <div class="col-md-8">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter" >
                    <i class="icon-list"></i>
                    <?php echo _("Reports") ?>
                </h4>
            </div>
            <div class="widget-body" style="padding-top: 10px; text-align: center;">
                <div style="text-align: left;">
                    <div id="reportbuttons" class="inline hide">
                        <button class="btn btn-primary btn-sm" onclick="WPOS.reports.generateTakingsReport();"><?php echo _("Takings Count") ?></button>
                        <button class="btn btn-primary btn-sm" onclick="WPOS.reports.generateWhatsSellingReport();"><?php echo _("What's Selling") ?></button>
                        <button class="btn btn-primary btn-sm" onclick="WPOS.reports.generateSellerReport();"><?php echo _("Seller Report") ?></button>
                    </div>
                    <div id="tyroreports" class="hide" style="display: inline-block;">
                        <select id="tyroreptype" style="height: 34px; vertical-align: bottom; margin-left: 5px;">
                            <option value="summary" selected><?php echo _("Summary") ?></option>
                            <option value="detail"><?php echo _("Detailed") ?></option>
                        </select>
                        <button class="btn btn-success btn-sm" onclick="WPOS.reports.generateTyroReport();"><?php echo _("Tyro Report") ?></button>
                    </div>
                </div>
                <hr>
                <div style="text-align: left; margin-bottom: 10px; margin-left: 10px;">
                    <button class="btn btn-primary btn-xs" onclick="WPOS.print.printCurrentReport();"><i class="icon-print"></i>&nbsp;<?php echo _("Print") ?></button>
                </div>
                <div style="overflow-x: auto; padding: 10px; padding-top: 0;">
                    <div id="reportcontain">

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter" >
                    <i class="icon-dollar"></i>
                    <?php echo _("Today's Takings") ?>
                </h4>
            </div>
            <div class="widget-body" style="padding-top: 10px; text-align: center;">
                <div class="infobox infobox-green  ">
                    <div class="infobox-icon">
                        <i class="icon-shopping-cart"></i>
                    </div>

                    <div class="infobox-data">
                        <span id="rsalesnum" class="infobox-data-number">-</span>
                        <div class="infobox-content" ><?php echo _("Sales") ?></div>
                    </div>
                    <div id="rsalestotal" class="stat stat-success">-</div>
                </div>

                <div class="infobox infobox-orange">
                    <div class="infobox-icon">
                        <i class="icon-backward"></i>
                    </div>

                    <div class="infobox-data">
                        <span id="rrefundsnum" class="infobox-data-number">-</span>
                        <div class="infobox-content"><?php echo _("Refunds") ?></div>
                    </div>

                    <div id="rrefundstotal" class="stat stat-important">-</div>
                </div><br/>

                <div class="infobox infobox-red">
                    <div class="infobox-icon">
                        <i class="icon-ban-circle"></i>
                    </div>

                    <div class="infobox-data">
                        <span id="rvoidsnum" class="infobox-data-number">-</span>
                        <div class="infobox-content"><?php echo _("Voids") ?></div>
                    </div>
                    <div id="rvoidstotal" class="stat stat-important">-</div>
                </div>

                <div class="infobox infobox-blue2">
                    <div class="infobox-icon">
                        <i class="icon-dollar"></i>
                    </div>

                    <div class="infobox-data">
                        <span id="rtotaltakings" class="infobox-data-number">-</span>
                        <div class="infobox-content"><?php echo _("Takings") ?></div>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">
                    <i class="icon-check"></i>
                    <?php echo _("Cash Reconciliation") ?>
                </h4>
            </div>
            <div class="widget-body" style="padding-top: 10px; text-align: center;">
                <table class="table">
                    <tbody>
                    <tr>
                        <td style="text-align: right;">$100:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom100" value="0"/></td>
                        <td style="text-align: right;">$50:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom50" value="0"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">$20:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom20" value="0"/></td>
                        <td style="text-align: right;">$10:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom10" value="0"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">$5:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom5" value="0"/></td>
                        <td style="text-align: right;">$2:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom2" value="0"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">$1:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom1" value="0"/></td>
                        <td style="text-align: right;">50c:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom50c" value="0"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">20c:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom20c" value="0"/></td>
                        <td style="text-align: right;">10c:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom10c" value="0"/></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;">5c:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recdenom5c" value="0"/></td>
                        <td style="text-align: right;">Float:</td>
                        <td><input onchange="WPOS.reports.calcReconcil();" type="text" size="4" id="recfloat" value="0"/></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right;"><?php echo _("Takings - Float") ?>:</td>
                        <td colspan="2"><span id="rectakings"></span></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right;"><?php echo _("Balance") ?>:</td>
                        <td colspan="2"><span id="recbalance"></span></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>
<div id="tabs-4">
    <div class="tabbable">
        <ul class="nav nav-tabs tab-size-bigger" id="myTab">
            <li class="active">
                <a data-toggle="tab" href="#settings-tab-1">
                    <i class="blue icon-cogs bigger-120"></i>
                    <?php echo _("General") ?>
                </a>
            </li>

            <li>
                <a data-toggle="tab" href="#settings-tab-2">
                    <i class="green icon-print bigger-120"></i>
                    <?php echo _("Printing") ?>
                </a>
            </li>

            <li class="eftpos_settings hide">
                <a data-toggle="tab" href="#settings-tab-3">
                    <i class="orange icon-credit-card bigger-120"></i>
                    Eftpos
                </a>
            </li>
        </ul>

        <div class="tab-content no-border">
            <div id="settings-tab-1" class="tab-pane fade in active">
                <div class="widget-box transparent">
                    <div class="widget-header widget-header-flat">
                        <h4 class="lighter">
                            <i class="icon-cogs"></i>
                            <?php echo _("General") ?>
                        </h4>
                    </div>
                    <div class="widget-body" style="padding-top: 10px; text-align: left;">
                        <label><?php echo _("Use On-screen keypad") ?>: <input id="keypadset" type="checkbox" style="vertical-align: top;" onclick="WPOS.setLocalConfigValue('keypad', $('#keypadset').is(':checked'))"></label>
                    </div>
                    <div class="widget-header widget-header-flat">
                        <h4 class="lighter" >
                            <i class="icon-link"></i>
                            <?php echo _("Registration") ?>
                        </h4>
                    </div>
                    <div class="widget-body" style="padding-top: 10px; text-align: left;">
                        <table class="table tab-pane">
                            <tr>
                                <td><?php echo _("Device ID") ?>: </td>
                                <td class="device_id"></td>
                            </tr>
                            <tr>
                                <td><?php echo _("Device Name") ?>: </td>
                                <td class="device_name"></td>
                            </tr>
                            <tr>
                                <td><?php echo _("Location Name") ?>: </td>
                                <td class="location_name"></td>
                            </tr>
                            <tr>
                                <td><?php echo _("Registration ID") ?>: </td>
                                <td class="devicereg_id"></td>
                            </tr>
                            <tr>
                                <td><?php echo _("Registration UUID") ?>: </td>
                                <td class="devicereg_uuid"></td>
                            </tr>
                            <tr>
                                <td><?php echo _("Registration DT") ?>: </td>
                                <td class="devicereg_dt"></td>
                            </tr>
                        </table>
                        <button id="backup_btn" class="btn-xs btn-success" onclick="WPOS.backupOfflineSales();"><?php echo _("Backup Offline Sales") ?></button>
                        <button class="btn-xs btn-warning" onclick="WPOS.resetLocalConfig();"><?php echo _("Reset Local Config") ?></button>
                        <button class="btn-xs btn-danger" onclick="WPOS.clearLocalData();"><?php echo _("Clear Local Data") ?></button>
                        <button class="btn-xs btn-primary" onclick="WPOS.refreshRemoteData();"><?php echo _("Refresh Remote Data") ?></button>
                        <button class="btn-xs btn-danger" onclick="WPOS.removeDeviceRegistration();"><?php echo _("Remove Device Registration") ?></button>
                    </div>
                </div>
            </div>
            <div id="settings-tab-2" class="tab-pane fade">
                <div class="widget-box transparent">
                    <div class="widget-header widget-header-flat">
                        <h4 class="lighter">
                            <i class="icon-print"></i>
                            <?php echo _("Receipt Printing") ?>
                        </h4>
                    </div>
                    <div class="widget-body" style="padding-top: 10px; text-align: left;">
                        <div id="printsettings_receipts">
                            <div id="settings-list-1" class="panel-group accordion-style1 accordion-style2">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <a href="#settings-1-1" data-parent="#settings-list-1" data-toggle="collapse" class="accordion-toggle collapsed">
                                            <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>
                                            <i class="icon-cogs bigger-130"></i>
                                            &nbsp; <?php echo _("General") ?>
                                        </a>
                                    </div>
                                    <div class="panel-collapse collapse" id="settings-1-1">
                                        <div class="panel-body">
                                            <div class="settings-row">
                                                <label><?php echo _("Ask to print") ?>: <select id="recask" onchange="WPOS.print.setGlobalPrintSetting('recask', $('#recask').val());" style="margin-right: 20px;">
                                                    <option value="ask"><?php echo _("Always Ask") ?></option>
                                                    <option value="email"><?php echo _("Ask if not emailed") ?></option>
                                                    <option value="print"><?php echo _("Always Print") ?></option>
                                                </select></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <a href="#settings-1-2" data-parent="#settings-list-1" data-toggle="collapse" class="accordion-toggle">
                                            <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>
                                            <i class="icon-adjust bigger-130"></i>
                                            &nbsp; <?php echo _("Connection") ?>
                                        </a>
                                    </div>
                                    <div class="panel-collapse in" id="settings-1-2">
                                        <div class="panel-body">
                                            <div class="settings-row" style="display: inline-block;">
                                                <label style="margin-right: 20px;"><?php echo _("Method") ?>:
                                                    <select class="psetting_method" onchange="WPOS.print.setPrintSetting('receipts', 'method', $(this).val())">
                                                        <option value="br">
                                                            <?php echo _("browser printing") ?>
                                                        </option>
                                                        <option value="wp" class="wp-option">
                                                            <?php echo _("Web Print ESCP") ?>
                                                        </option>
                                                    </select>
                                                </label>
                                                <label class="advprintoptions">Type:
                                                    <select class="psetting_type"  onchange="WPOS.print.setPrintSetting('receipts', 'type', $(this).val())">
                                                        <option value="serial">
                                                            <?php echo _("Serial") ?>
                                                        </option>
                                                        <option value="raw">
                                                            <?php echo _("Raw") ?>
                                                        </option>
                                                        <option value="tcp">
                                                            <?php echo _("Raw TCP") ?>
                                                        </option>
                                                    </select>
                                                </label>
                                            </div>
                                            <div class="advprintoptions settings-row" style="display: inline-block;">
                                                <div class="tcpoptions" style="display: inline-block;">
                                                    <label style="margin-right: 20px;"><?php echo _("Printer IP") ?>:
                                                        <input class="psetting_printerip" size="16" onchange="WPOS.print.setPrintSetting('receipts', 'printerip', $(this).val());" placeholder="192.168.1.100" type="text">
                                                    </label>
                                                    <label><?php echo _("Printer Port") ?>:
                                                        <input class="psetting_printerport" size="6" onchange="WPOS.print.setPrintSetting('receipts', 'printerport', $(this).val());" placeholder="9100" type="text">
                                                    </label>
                                                </div>
                                                <div class="rawoptions" style="display: inline-block;">
                                                    <label><?php echo _("Printer") ?>:
                                                        <select class="psetting_printer" onchange="WPOS.print.setPrintSetting('receipts', 'printer', $(this).val());">

                                                        </select>
                                                        <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.populatePrinters();"><i class="icon-refresh"></i></button>
                                                    </label>
                                                </div>
                                                <div class="serialoptions" style="display: inline-block;">
                                                    <label><?php echo _("Port") ?>:
                                                        <select class="psetting_port" onchange="WPOS.print.setPrintSetting('receipts', 'port', $(this).val());"></select>
                                                        <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.populatePorts();"><i class="icon-refresh"></i></button>
                                                    </label>
                                                    <label>Baud:
                                                        <select class="psetting_baud" onchange="WPOS.print.setPrintSetting('receipts', 'baud', $(this).val());">
                                                            <option value="4800">4800</option>
                                                            <option value="9600">9600</option>
                                                            <option value="19200">19200</option>
                                                            <option value="38400">38400</option>
                                                            <option value="57600">57600</option>
                                                            <option value="115200">115200</option>
                                                        </select>
                                                    </label>
                                                    <label>Data Bits:
                                                        <select class="psetting_databits" onchange="WPOS.print.setPrintSetting('receipts', 'databits', $(this).val());">
                                                            <option value="7">7</option>
                                                            <option value="8">8</option>
                                                        </select>
                                                    </label>
                                                    <label>Stop Bits:
                                                        <select class="psetting_stopbits" onchange="WPOS.print.setPrintSetting('receipts', 'stopbits', $(this).val());">
                                                            <option value="1">1</option>
                                                            <option value="0">0</option>
                                                        </select>
                                                    </label>
                                                    <label><?php echo _("Parity") ?>:
                                                        <select class="psetting_parity" onchange="WPOS.print.setPrintSetting('receipts', 'parity', $(this).val());">
                                                            <option value="none"><?php echo _("None") ?></option>
                                                            <option value="even"><?php echo _("Even") ?></option>
                                                            <option value="odd"><?php echo _("Odd") ?></option>
                                                        </select>
                                                    </label>
                                                    <label><?php echo _("Flow Control") ?>:
                                                        <select class="psetting_flow" onchange="WPOS.print.setPrintSetting('receipts', 'flow', $(this).val());">
                                                            <option value="none"><?php echo _("None") ?></option>
                                                            <option value="xonxoff"><?php echo _("XON/XOFF") ?></option>
                                                            <option value="rtscts"><?php echo _("RTS/CTS") ?></option>
                                                        </select>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="settings-row printoptions" style="display: inline-block;">
                                                <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.testReceiptPrinter('receipts');"><?php echo _("Test") ?></button>
                                                <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.printQrCode();"><?php echo _("Print QR") ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default escpoptions">
                                    <div class="panel-heading">
                                        <a href="#settings-1-3" data-parent="#settings-list-1" data-toggle="collapse" class="accordion-toggle collapsed" >
                                            <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>
                                            <i class="icon-dollar bigger-130"></i>
                                            &nbsp; <?php echo _("Cashdraw, Cutter & Page Feed") ?>
                                        </a>
                                    </div>
                                    <div class="panel-collapse collapse" id="settings-1-3">
                                        <div class="panel-body">
                                            <div class="settings-row">
                                                <label><?php echo _("Cash Draw Connected") ?>: <input onchange="WPOS.print.setGlobalPrintSetting('cashdraw', $('#cashdraw').is(':checked'));" id="cashdraw"  type="checkbox" style="margin-right:20px; vertical-align:top;" /></label>
                                                <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.openCashDraw();"><?php echo _("Test") ?></button>
                                            </div>
                                            <div class="settings-row">
                                                <label style="margin-right: 20px;"><?php echo _("Cutter Command") ?>:
                                                    <select class="psetting_cutter" onchange="WPOS.print.setPrintSetting('receipts', 'cutter', $(this).val());">
                                                        <option value="none"><?php echo _("None") ?></option>
                                                        <option value="gs_full"><?php echo _("GS full cut") ?></option>
                                                        <option value="gs_partial"><?php echo _("GS partial cut") ?></option>
                                                        <option value="esc_full"><?php echo _("ESC full cut") ?></option>
                                                        <option value="esc_partial"><?php echo _("ESC partial cut") ?></option>
                                                    </select>
                                                </label>
                                                <label><?php echo _("Page Feed") ?>:
                                                    <input class="psetting_feed" type="number" onchange="WPOS.print.setPrintSetting('receipts', 'feed', parseInt($(this).val()));">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <a href="#settings-1-4" data-parent="#settings-list-1" data-toggle="collapse" class="accordion-toggle collapsed" >
                                            <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>
                                            <i class="icon-list bigger-130"></i>
                                            &nbsp; <?php echo _("Format & Layout") ?>
                                        </a>
                                    </div>
                                    <div class="panel-collapse collapse" id="settings-1-4">
                                        <div class="panel-body">
                                            <div class="settings-row escpoptions">
                                                <label style="margin-right:20px"><?php echo _("ESCP Receipt Mode") ?>: <select id="escpreceiptmode" onchange="WPOS.print.setGlobalPrintSetting('escpreceiptmode', $('#escpreceiptmode').val());">
                                                    <option value="text"><?php echo _("Text") ?></option>
                                                    <option value="bitmap"><?php echo _("Bitmap (Unicode support)") ?></option>
                                                </select></label>

                                            </div>
                                            <div class="settings-row">
                                                <label id="rectemplatefield" style="margin-right:20px"><?php echo _("Receipt Template") ?>: <select id="rectemplate" onchange="WPOS.print.setGlobalPrintSetting('rectemplate', $('#rectemplate').val());">
                                                </select></label>
                                                <label class="broptions" style="margin-right:20px"><?php echo _("Invoice Template") ?>: <select id="invtemplate" onchange="WPOS.print.setGlobalPrintSetting('invtemplate', $('#invtemplate').val());" style="margin-right: 20px;">
                                                </select></label>
                                                <label class="broptions" ><input type="checkbox" id="printinv" onchange="WPOS.print.setGlobalPrintSetting('printinv', $('#printinv').is(':checked'));"/> <?php echo _("Print Invoices By Default") ?></label>
                                            </div>
                                            <div class="settings-row escptextmodeoptions">
                                                <label style="margin-right: 20px;"><?php echo _("Language") ?>:
                                                <select id="rec_language" onchange="WPOS.print.setGlobalPrintSetting('rec_language', $('#rec_language').val());">
                                                    <option value="primary"><?php echo _("Primary") ?></option>
                                                    <option value="mixed"><?php echo _("Mixed") ?></option>
                                                    <option value="alternate"><?php echo _("Alternate") ?></option>
                                                </select>
                                                </label>
                                                <label><?php echo _("Orientation") ?>
                                                    <select id="rec_orientation" onchange="WPOS.print.setGlobalPrintSetting('rec_orientation', $('#rec_orientation').val());">
                                                        <option value="ltr"><?php echo _("Left to Right") ?></option>
                                                        <option value="rtl"><?php echo _("Right to Left") ?></option>
                                                    </select>
                                                </label>
                                            </div>
                                            <div class="settings-row escptextmodeoptions">
                                                <label><?php echo _("Alternate Charset & codepage") ?>:
                                                    <select id="alt_charset" onchange="WPOS.print.setGlobalPrintSetting('alt_charset', $('#alt_charset').val());">
                                                        <option value="pc864">PC864</option>
                                                        <option value="pc1256">PC1256</option>
                                                    </select>
                                                    <input id="alt_codepage" onchange="WPOS.print.setGlobalPrintSetting('alt_codepage', $('#alt_codepage').val());" type="number" />
                                                </label>
                                            </div>
                                            <div class="settings-row escptextmodeoptions">
                                                <label><?php echo _("Override currency character") ?>
                                                    <input type="checkbox" id="currency_override" onchange="WPOS.print.setGlobalPrintSetting('currency_override', $('#currency_override').is(':checked'));"/>
                                                </label><br/>
                                                <label style="margin-right:20px">
                                                    <?php echo _("Currency Codepage") ?>:
                                                    <input id="currency_codepage" onchange="WPOS.print.setGlobalPrintSetting('currency_codepage', $('#currency_codepage').val());" type="number" />
                                                </label>
                                                <label>
                                                    <?php echo _("Currency Codes") ?>:
                                                    <input id="currency_codes" onchange="WPOS.print.setGlobalPrintSetting('currency_codes', $('#currency_codes').val());" type="text" />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="printsettings_kitchen" class="order_terminal_options widget-box transparent">
                            <div class="widget-header widget-header-flat">
                                <h4 class="lighter">
                                    <i class="icon-print"></i>
                                    <?php echo _("Order Ticket Printing") ?>
                                </h4>
                            </div>
                            <div class="settings-row" style="display: inline-block;">
                                <label><?php echo _("Method") ?>:
                                    <select class="psetting_method" onchange="WPOS.print.setPrintSetting('kitchen', 'method', $(this).val())">
                                        <option value="wp" class="wp-option">
                                            <?php echo _("Web Print ESCP") ?>
                                        </option>
                                    </select>
                                </label>
                            </div>
                            <div class="advprintoptions settings-row" style="display: inline-block;">
                                <label style="margin-right: 20px;"><?php echo _("Type") ?>:
                                    <select class="psetting_type"  onchange="WPOS.print.setPrintSetting('kitchen', 'type', $(this).val())">
                                        <option value="serial">
                                            <?php echo _("Serial") ?>
                                        </option>
                                        <option value="raw">
                                            <?php echo _("Raw") ?>
                                        </option>
                                        <option value="tcp">
                                            <?php echo _("Raw TCP") ?>
                                        </option>
                                    </select>
                                </label>
                                <div class="tcpoptions" style="display: inline-block;">
                                    <label style="margin-right: 20px"><?php echo _("Printer IP") ?>:
                                        <input class="psetting_printerip" size="16" onchange="WPOS.print.setPrintSetting('kitchen', 'printerip', $(this).val());" placeholder="192.168.1.100" type="text">
                                    </label>
                                    <label><?php echo _("Printer Port") ?>:
                                        <input class="psetting_printerport" size="6" onchange="WPOS.print.setPrintSetting('kitchen', 'printerport', $(this).val());" placeholder="9100" type="text">
                                    </label>
                                </div>
                                <div class="rawoptions" style="display: inline-block;">
                                    <label><?php echo _("Printer") ?>:
                                        <select class="psetting_printer" onchange="WPOS.print.setPrintSetting('kitchen', 'printer', $(this).val());">

                                        </select>
                                        <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.populatePrinters();"><i class="icon-refresh"></i></button>
                                    </label>
                                </div>
                                <div class="serialoptions" style="display: inline-block;">
                                    <label><?php echo _("Port") ?>:
                                        <select class="psetting_port" onchange="WPOS.print.setPrintSetting('kitchen', 'port', $(this).val());">

                                        </select>
                                        <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.populatePorts();"><i class="icon-refresh"></i></button>
                                    </label><br/>
                                    <label><?php echo _("Baud") ?>:
                                        <select class="psetting_baud" onchange="WPOS.print.setPrintSetting('kitchen', 'baud', $(this).val());">
                                            <option value="4800">4800</option>
                                            <option value="9600">9600</option>
                                            <option value="19200">19200</option>
                                            <option value="38400">38400</option>
                                            <option value="57600">57600</option>
                                            <option value="115200">115200</option>
                                        </select>
                                    </label>
                                    <label><?php echo _("Data Bits") ?>:
                                        <select class="psetting_databits" onchange="WPOS.print.setPrintSetting('kitchen', 'databits', $(this).val());">
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                        </select>
                                    </label>
                                    <label><?php echo _("Stop Bits") ?>:
                                        <select class="psetting_stopbits" onchange="WPOS.print.setPrintSetting('kitchen', 'stopbits', $(this).val());">
                                            <option value="1">1</option>
                                            <option value="0">0</option>
                                        </select>
                                    </label>
                                    <label><?php echo _("Parity") ?>:
                                        <select class="psetting_parity" onchange="WPOS.print.setPrintSetting('kitchen', 'parity', $(this).val());">
                                            <option value="none"><?php echo _("none") ?></option>
                                            <option value="even"><?php echo _("even") ?></option>
                                            <option value="odd"><?php echo _("odd") ?></option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                            <div class="settings-row printoptions" style="display: inline-block;">
                                <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.testReceiptPrinter('kitchen');"><?php echo _("Test") ?></button>
                            </div>
                        </div>
                        <div id="printsettings_reports" class="widget-box transparent">
                            <div class="widget-header widget-header-flat">
                                <h4 class="lighter">
                                    <i class="icon-print"></i>
                                    <?php echo _("Report Printing") ?>
                                </h4>
                            </div>
                            <div class="settings-row" style="display: inline-block;">
                                <label><?php echo _("Method") ?>:
                                    <select class="psetting_method" onchange="WPOS.print.setPrintSetting('reports', 'method', $(this).val())">
                                        <option value="br">
                                            <?php echo _("browser printing") ?>
                                        </option>
                                        <option value="wp" class="wp-option">
                                            <?php echo _("Web Print") ?>
                                        </option>
                                    </select></label>
                            </div>
                            <div class="advprintoptions settings-row" style="display: inline-block;">
                                <label><?php echo _("Printer") ?>:
                                    <select class="psetting_printer" onchange="WPOS.print.setPrintSetting('reports', 'printer', $(this).val());">

                                    </select>
                                    <button class="btn btn-primary btn-xs field-button" onclick="WPOS.print.populatePrinters();"><i class="icon-refresh"></i></button>
                                </label>
                            </div>
                        </div>
                        <div class="printserviceoptions widget-box transparent" style="padding-top: 10px; text-align: left;">
                            <div class="widget-header widget-header-flat">
                                <h4 class="lighter">
                                    <i class="icon-cogs"></i>
                                    <?php echo _("Print Service options (WebPrint/Android)") ?>
                                </h4>
                            </div>
                            <div class="settings-row" style="display: inline-block;">
                                <label><?php echo _("IP Address") ?>: <input id="serviceip" type="text" onchange="WPOS.print.setGlobalPrintSetting('serviceip', $(this).val());" value="127.0.0.1"></label>
                                <label><?php echo _("Port") ?>: <input id="serviceport" type="text" onchange="WPOS.print.setGlobalPrintSetting('serviceport', $(this).val());" value="8080"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="settings-tab-3" class="tab-pane fade eftpos_settings hide">
                <div class="widget-box transparent">
                    <div class="widget-header widget-header-flat">
                        <h4 class="lighter">
                            <i class="icon-credit-card"></i>
                            <?php echo _("Integrated Eftpos") ?>
                        </h4>
                    </div>
                    <div class="widget-body eftpos_settings hide" style="padding-top: 10px; text-align: left;">
                        <div class="settings-row" style="display: inline-block;">
                            <label><?php echo _("Provider") ?>:
                                <select id="eftposprovider" style="vertical-align: middle; margin-right: 20px;" onchange="WPOS.eftpos.setEftposSetting('provider', $('#eftposenabled').val());">
                                    <option value="tyro">Tyro</option>
                                </select></label>
                            <label><?php echo _("Enabled") ?>: <input id="eftposenabled" type="checkbox" onclick="WPOS.eftpos.setEftposSetting('enabled', $('#eftposenabled').is(':checked'));" style="vertical-align: top; margin-right: 20px;"></label>
                            <label><?php echo _("Integrated Receipts") ?>: <input id="eftposreceipts" type="checkbox" onclick="WPOS.eftpos.setEftposSetting('receipts', $('#eftposreceipts').is(':checked'));" style="vertical-align: top;"></label>
                        </div>
                        <div id="eftrecsettings" class="settings-row" style="display: inline-block;">
                            <label><?php echo _("Print Merchant Receipts") ?>: <select id="eftposmerchrec" style="margin-right: 20px;" onchange="WPOS.eftpos.setEftposSetting('merchrec', $('#eftposmerchrec option:selected').val());">
                                <option value="ask"><?php echo _("Always Ask") ?></option>
                                <option value="print"><?php echo _("Always Print") ?></option>
                                <option value="never"><?php echo _("Never") ?></option>
                            </select></label>
                            <label><?php echo _("Print Declined Customer Receipts") ?>: <select id="eftposcustrec" onchange="WPOS.eftpos.setEftposSetting('custrec', $('#eftposcustrec option:selected').val());">
                                <option value="ask"><?php echo _("Always Ask") ?></option>
                                <option value="print"><?php echo _("Always Print") ?></option>
                                <option value="never"><?php echo _("Never") ?></option>
                            </select></label>
                        </div>
                        <div id="tyroprovopt" class="settings-row eftposprovopt hide" style="display: inline-block;">
                            <label><?php echo _("Merchant ID") ?>: <input type="text" id="tyromid" style="margin-right: 20px; width: 60px;"/></label>
                            <label><?php echo _("Terminal ID") ?>: <input type="text" id="tyrotid" style="margin-right: 20px; width: 60px;"/></label>
                            <button class="btn-primary btn-xs btn" onclick="WPOS.eftpos.doTyroPairing();"><?php echo _("Start Tyro Pairing") ?></button>
                            <button class="btn-primary btn-xs btn" onclick="WPOS.eftpos.openTyroConfiguration();"><?php echo _("Open Terminal Config") ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="statusbar" style="width:100%; position:absolute; bottom:0; min-height:40px; background-color:#666666; padding-top: 10px;">
    <div class="col-sm-3 col-xs-6" style="vertical-align: middle;">
        <div id="printstat">
            <i class="icon-print icon-large white" style="margin-right: 5px;"></i>&nbsp;<span id="printstattxt" class="white"><?php echo _("Initializing") ?>...</span>
        </div>
    </div>
    <div id="wposstat" class="col-sm-3 col-sm-push-6 col-xs-6 text-right">
        <span id="wposstattxt" class="white"><?php echo _("WPOS is Online") ?></span>
        <span id="wposstaticon" class="badge badge-success" style="margin-left: 5px;"><i class="icon-ok"></i></span>
    </div>
    <div class="col-sm-6 col-sm-pull-3 text-center white" style="padding-bottom: 10px;">
        <span class="biz_name"></span> - <span class="device_name"></span> - <span class="location_name"></span>
    </div>
</div><iframe id="dlframe" style="width: 1px;height: 1px; border: 0; position: absolute; bottom: 0; left: 0; z-index: -1;"></iframe>
</div>
<div id="loginmodal" class="login-layout">
    <div id="loginbox" class="login-box widget-box visible no-border">
        <div class="widget-main">
            <h2 class="header blue lighter bigger">
                <img style="height: 40px; margin-top: -5px;" src="/assets/images/apple-touch-icon-72x72.png">&nbsp;
            </h2>
            <div class="space-2"></div>
            <div id="login-banner" class="alert alert-block alert-success" style="display:none;">
                <i class="icon icon-lock green" style="margin-right: 3px;"></i>
                <span id="login-banner-txt"></span>
            </div>
            <div class="space-10"></div>
            <div id="logindiv" style="display: none;">
                <label class="block clearfix">
                    <span class="block input-icon input-icon-right">
                        <input class="form-control" id="username" name="username" type="text" placeholder="Username"/>
                        <i class="icon-user"></i>
                    </span>
                </label>
                <label class="block clearfix">
                    <span class="block input-icon input-icon-right">
                        <input class="form-control" id="password" name="password" onkeypress="if(event.keyCode == 13){WPOS.userLogin();}" type="password"  placeholder="Password"/>
                        <i class="icon-lock"></i>
                    </span>
                </label>
                <div class="space-6"></div>
                <div class="space-6"></div>
                <button id="loginbutton" onClick="WPOS.userLogin();" class="btn btn-primary width-35" disabled>
                    <i class="icon-key"></i><?php echo _("Login") ?>
                </button>
                <div class="space-6"></div>
            </div>
            <div id="loadingdiv" style="height: 225px;">
                <h3 id="loadingbartxt">Initializing</h3>
                <div id="loadingprogdiv" class="progress progress-striped active">
                    <div class="progress-bar" id="loadingprog" style="width: 100%;"></div>
                </div>
                <span id="loadingstat"></span>
            </div>
        </div>
    </div>
</div>
<div id="paymentsdiv" style="display:none; padding:5px;" title="Complete Sale">
    <table width="100%" class="ui-widget-content">
        <tr>
            <td><strong style="width: 60px; display: inline-block;">Total:</strong><span id="salestotal">$0.00</span></td>
            <td><strong style="width: 80px; display: inline-block;">Payments:</strong><span id="paymentstotal">$0.00</span></td>
        </tr>
        <tr>
            <td><strong style="width: 60px; display: inline-block;">Balance:</strong><span id="salesbalance">$0.00</span></td>
            <td><strong style="width: 80px; display: inline-block;">Change:</strong><span id="saleschange">$0.00</span></td>
        </tr>
    </table>
    <div style="height:250px; overflow:auto; padding:0;">
        <table style="width: 100%; text-align: left;" class="table table-striped table-bordered table-hover">
            <thead class="table-header">
            <tr>
                <th><?php echo _("Method") ?></th>
                <th><?php echo _("Amount") ?></th>
                <th>X</th>
            </tr>
            </thead>
            <tbody id="paymentstable">

            </tbody>
        </table>
        <div style="text-align: center;">
            <button id="eftpospaybtn" class="btn btn-sm btn-success" onclick="WPOS.sales.startEftposPayment();"><?php echo _("Start Eftpos") ?></button>
        </div>
        <div style="margin-top: 20px;">
            <button class="btn btn-xs btn-primary" onclick="WPOS.sales.addPayment('cash');"><?php echo _("Cash") ?></button>
            <button class="btn btn-xs btn-primary" onclick="WPOS.sales.addPayment('credit');"><?php echo _("Credit") ?></button>
            <button class="btn btn-xs btn-primary" onclick="WPOS.sales.addPayment('eftpos');"><?php echo _("Eftpos") ?></button>
            <button class="btn btn-xs btn-primary" onclick="WPOS.sales.addPayment('cheque');"><?php echo _("Cheque") ?></button>
            <button style="float: right;" class="btn btn-xs btn-primary" onclick="WPOS.sales.addAdditionalPayment();"><?php echo _("Add Payment") ?></button>
        </div>
    </div>
    <button id="endsalebtn" class="btn btn-success" onclick="WPOS.sales.processSale();"><?php echo _("Complete") ?></button>
    <button id="savesalebtn" class="btn btn-primary" onclick="WPOS.sales.saveOrder();"><?php echo _("Add Order") ?></button>
    <button class="btn btn-danger" onClick="$('#paymentsdiv').dialog('close');"><?php echo _("Cancel") ?></button>
</div>
<div id="custdiv" style="display:none;" title="<?php echo _("Customer Details") ?>">
    <form id="custform">
    <label style="width: 80px;"><?php echo _("Name") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custname"/><br/>
    <label style="width: 80px;"><?php echo _("Phone") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custphone"/><br/>
    <label style="width: 80px;"><?php echo _("Mobile") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custmobile"/><br/>
    <label style="width: 80px;"><?php echo _("Address") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custaddress"/><br/>
    <label style="width: 80px;"><?php echo _("Suburb") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custsuburb"/><br/>
    <label style="width: 80px;"><?php echo _("Postcode") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custpostcode"/><br/>
    <label style="width: 80px;"><?php echo _("Country") ?>:</label><input onchange="WPOS.sales.setUpdateCust();" type="text" id="custcountry"/><br/>
    </form>
    <div style="text-align: center; margin-top: 10px;">
        <button class="btn btn-primary" onclick="$('#custdiv').dialog('close');">Close</button>
    </div>
</div>
<div id="transactiondiv" style="display:none; padding: 4px; min-height: 450px;" title="Transaction Details">
    <div id="transactioninfo">
        <div class="row" style="padding-top: 8px; margin: 0;">
            <div class="col-sm-6">
                <label class="fixedlabel"><?php echo _("Status") ?>:</label> <span id="transstat"></span><br/>
                <label class="fixedlabel"><?php echo _("ID") ?>:</label> <span id="transid"></span><br/>
                <label class="fixedlabel"><?php echo _("Ref") ?>:</label> <span id="transref"></span><br/>
                <label class="fixedlabel"><?php echo _("Sale DT") ?>:</label> <span id="transtime"></span><br/>
            </div>
            <div class="col-sm-6">
                <label class="fixedlabel"><?php echo _("Process DT") ?>:</label> <span id="transptime"></span><br/>
                <label class="fixedlabel"><?php echo _("User") ?>:</label> <span id="transuser"></span><br/>
                <label class="fixedlabel"><?php echo _("Device") ?>:</label> <span id="transdev"></span><br/>
                <label class="fixedlabel"><?php echo _("Location") ?>:</label> <span id="transloc"></span><br/>
            </div>
            <div class="col-sm-6">
                <label style="vertical-align: top;" class="fixedlabel"><?php echo _("Notes") ?>:
                    <textarea style="margin-left: 38px;" id="transnotes" tabindex="-1"></textarea><br/>
                    <button style="float: right;" class="btn btn-xs btn-primary" onclick="WPOS.trans.updateSaleNotes($('#transref').text(), $('#transnotes').text());"><?php echo _("Save") ?></button>
                </label>
            </div>
            <div id="transoptions" style="text-align: center; margin-bottom: 10px; margin-top: 10px;" class="col-sm-6">
                <div id="voidbuttons" style="display: inline-block;">
                    <button class="btn btn-danger" onclick="WPOS.sales.openVoidDialog($('#transref').text());"><i class="icon-ban-circle"></i>&nbsp;<?php echo _("Void") ?></button>
                    <button class="btn btn-warning" onclick="WPOS.sales.openRefundDialog($('#transref').text());"><i class="icon-backward"></i>&nbsp;<?php echo _("Refund") ?></button>
                </div>
                <div id="orderbuttons" style="display: inline-block;">
                    <button class="btn btn-success" onclick="WPOS.sales.loadOrder($('#transref').text());"><i class="icon-check"></i>&nbsp;<?php echo _("Complete") ?></button>
                    <button class="btn btn-danger" onclick="WPOS.sales.removeOrder($('#transref').text());"><i class="icon-trash"></i>&nbsp;<?php echo _("Remove") ?></button>
                </div>
                <button class="btn btn-primary" onclick="WPOS.print.printReceipt($('#transref').text());"><i class="icon-print"></i>&nbsp;<?php echo _("Print") ?></button>
            </div>
        </div>

        <div class="tabbable" style="margin-top: 10px;">
            <ul class="nav nav-tabs">
                <li class="active">
                    <a href="#transdetails" data-toggle="tab">
                        <i class="green icon-gift bigger-120"></i>
                        <?php echo _("Details") ?>
                    </a>
                </li>
                <li>
                    <a href="#transitems" data-toggle="tab">
                        <i class="green icon-gift bigger-120"></i>
                        <?php echo _("Items") ?>
                    </a>
                </li>
                <li class="">
                    <a href="#transpayments" data-toggle="tab">
                        <i class="red icon-dollar bigger-120"></i>
                        <?php echo _("Payments") ?>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active in" id="transdetails">
                    <div class="inline" style="vertical-align: top; width: 49%; min-width: 250px;">
                        <h4><?php echo _("Sale Totals") ?>:</h4>
                        <label class="fixedlabel"><?php echo _("Subtotal") ?>:</label><span id="transsubtotal"></span><br/>
                        <div id="transtax">
                        </div>
                        <div id="transdisdiv"><label class="fixedlabel"><?php echo _("Discount") ?>:</label><span id="transdiscount"></span></div>
                        <label class="fixedlabel"><?php echo _("Total") ?>:</label><span id="transtotal"></span><br/>
                    </div>
                    <div class="inline" style="vertical-align: top; width: 50%; min-width: 250px;">
                        <div id="voidinfo" style="display: none;">
                            <h4><?php echo _("Void/Refunds") ?>:</h4>
                            <table style="width: 100%" class="table">
                                <thead class="table-header">
                                <tr>
                                    <th><?php echo _("Type") ?></th>
                                    <th><?php echo _("Time") ?></th>
                                    <th><?php echo _("View") ?></th>
                                </tr>
                                </thead>
                                <tbody id="transvoidtable" class="ui-widget-content">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="transitems">
                    <h4 class="inline"><?php echo _("Items") ?>:</h4>
                    <table style="width: 100%;" class="table">
                        <thead class="table-header">
                        <tr style="text-align: left;">
                            <th><?php echo _("Qty") ?></th>
                            <th><?php echo _("Name") ?></th>
                            <th><?php echo _("Unit") ?></th>
                            <th><?php echo _("Tax") ?></th>
                            <th><?php echo _("Price") ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody id="transitemtable" style="overflow:auto;" class="ui-widget-content">

                        </tbody>
                    </table>
                </div>
                <div class="tab-pane" id="transpayments">
                    <h4 class="inline"><?php echo _("Payments") ?>:</h4>
                    <table style="width: 100%;" class="table">
                        <thead class="table-header">
                        <tr>
                            <th><?php echo _("Method") ?></th>
                            <th><?php echo _("Amount") ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody id="transpaymenttable" class="ui-widget-content">

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="voiddiv" style="display:none; padding:5px;" title="Void Details">
    <label class="fixedlabel"><?php echo _("Time") ?>: </label><span id="transreftime"></span><br/>
    <label class="fixedlabel"><?php echo _("User") ?>: </label><span id="transrefuser"></span><br/>
    <label class="fixedlabel"><?php echo _("Device") ?>: </label><span id="transrefdev"></span><br/>
    <label class="fixedlabel"><?php echo _("Location") ?>: </label><span id="transrefloc"></span><br/>
    <label class="fixedlabel"><?php echo _("Reason") ?>: </label><span id="transrefreason"></span><br/>
    <div id="refunddetails">
        <label class="fixedlabel"><?php echo _("Amount") ?>: </label><span id="transrefamount"></span>
        <div class="space-4"></div>
        <label class="fixedlabel"><?php echo _("Method") ?>: </label><span id="transrefmethod"></span>
        <button onclick="WPOS.trans.showPaymentInfo(this);" id="refpaydtlbtn" class="btn btn-xs btn-primary" style="display: inline-block; float: right;"><?php echo _("Details") ?></button>
        <div class="space-4"></div>
        <table style="width: 100%;" class="table">
            <thead class="table-header">
            <tr>
                <th><?php echo _("Item ID") ?></th>
                <th># <?php echo _("Returned") ?></th>
            </tr>
            </thead>
            <tbody id="transrefitemtable">

            </tbody>
        </table>
    </div>
</div>
<div id="setupdiv" style="display:none; padding:5px;" title="Initial Device Setup">
    <div style="display: inline-block; margin-bottom: 10px;">
        <?php echo _("Select a device to merge with or enter a new device name") ?><br/>
        <label><?php echo _("Existing device") ?>:</label><br/>
        <select id="posdevices" onchange="$('#newposdevice').val('');">
            <option value="" selected></option>
        </select><br/><br/>

        <label><?php echo _("New device name") ?>:</label><br/>
        <input id="newposdevice" type="text" onchange="$('#posdevices>option:eq(0)').prop('selected', true);"/>
    </div>
    <div style="display: inline-block;">
        <?php echo _("Select an existing location for the device or enter a new location") ?><br/>
        <label><?php echo _("Existing location") ?>:</label><br/>
        <select id="poslocations" onchange="$('#newposlocation').val('');">
            <option value="" selected></option>
        </select><br/><br/>
        <label><?php echo _("New location name") ?>:</label><br/>
        <input id="newposlocation" type="text" onchange="$('#poslocations>option:eq(0)').prop('selected', true);"/>
    </div>
    <div style="text-align: center; margin-top: 10px;">
        <button class="btn btn-primary btn-sm" onclick="WPOS.deviceSetup();"><?php echo _("Register") ?></button>
    </div>
</div>
<div id="formdiv" style="display:none; padding:5px; z-index: 1100;" title="">
    <div id="voidform">
        <input id="voidref" type="hidden" value=""/>
        <label><?php echo _("Reason") ?>:
        <textarea id="voidreason"></textarea></label>
    </div>
    <div id="refundform" style="margin-bottom: 20px;">
        <input id="refundref" type="hidden" value=""/>
        <label><?php echo _("Reason") ?>:
        <textarea id="refundreason"></textarea></label>
        <table class="table">
            <thead class="table-header">
            <tr>
                <th># <?php echo _("Returned") ?></th>
                <th><?php echo _("Item") ?></th>
            </tr>
            </thead>
            <tbody id="refunditems">

            </tbody>
        </table>
        <label><?php echo _("Refund Amount") ?>:
        <input id="refundamount" class="numpad" type="text" value="0" autocomplete="off" onclick="alert('Please be careful when manually specifying the refund amount!');"/></label><br/>
        <div style="text-align: center;">
            <button style="margin:5px; margin-bottom:10px;" id="eftposrefundbtn" class="btn btn-sm btn-success" onclick="WPOS.eftpos.startEftposRefund($('#refundamount').val());"><?php echo _("Start Eftpos Refund") ?></button>
        </div>
        <label><?php echo _("Refund Payment Type") ?>:
        <select id="refundmethod">
            <option value="cash"><?php echo _("Cash") ?></option>
            <option value="credit"><?php echo _("Credit") ?></option>
            <option value="cheque"><?php echo _("Cheque") ?></option>
        </select></label>
    </div>
    <button id="procvoidbtn" class="btn btn-success" onclick=""><?php echo _("Process") ?></button>
    <button class="btn" onclick="$('#formdiv').dialog('close');"><?php echo _("Cancel") ?></button>
</div>
<div id="itemoptionsdialog" class="hide" title="Item Options">
    <label class="fixedlabel">
        <?php echo _("Description") ?>:<br/>
        <textarea id="itemdesc"></textarea>
    </label><br/>
    <label class="fixedlabel" >
        <?php echo _("Alt Name") ?>:<br/>
        <input type="text" id="itemaltname"/>
    </label><br/>
    <label class="fixedlabel">
        <?php echo _("Cost") ?>:<br/>
        <input type="text" id="itemcost" class="numpad"/>
    </label><br/>
    <label class="fixedlabel"><?php echo _("Modifiers") ?>: </label>
    <table class="table table-stripped table-responsive">
        <thead>
            <tr>
                <th><?php echo _("Name") ?></th>
                <th><?php echo _("Qty/Selection") ?></th>
                <th><?php echo _("Value") ?></th>
            </tr>
        </thead>
        <tbody id="itemmods">

        </tbody>
    </table>
    <label class="fixedlabel"><?php echo _("Unit Mod Value") ?>: </label><span id="itemmodtotal">0.00</span><br/>
    <label class="fixedlabel"><?php echo _("New Unit Value") ?>: </label><span id="itemmodunit">0.00</span><br/>
</div>
<div id="codialog" class="hide" title="Cashout">
    <input type="hidden" autofocus="autofocus" />
    <label><?php echo _("Cashout") ?>: <input id="cashoutamount" type="text" onclick="$(this).focus();" class="numpad"/></label>
</div>
<div id="eftdetailsdialog" class="hide" title="Payment Details">
    <div>
        <div>
            <label><?php echo _("Reference") ?>: <span id="efttransref" style="margin-right: 20px;"></span></label>
            <label><?php echo _("Card Type") ?>: <span id="efttranscard"></span></label>
        </div>
    </div>
    <div>
        <div style="width: 250px; display: inline-block; text-align: center; vertical-align: top;">
            <pre id="eftcustrec"></pre>
            <button class="btn btn-primary btn-xs" onclick="WPOS.print.printArbReceipt($('#eftcustrec').text())" style="float: left;"><i class="icon-print"></i>&nbsp;<?php echo _("Print") ?></button>
        </div>
        <div style="width: 250px; display: inline-block; text-align: center; vertical-align: top;">
            <pre id="eftmerchrec"></pre>
            <button class="btn btn-primary btn-xs" onclick="WPOS.print.printArbReceipt($('#eftmerchrec').text())" style="float: left;"><i class="icon-print"></i>&nbsp;<?php echo _("Print") ?></button>
        </div>
    </div>
</div>
<div id="resetdialog" class="hide" title="Reset Request">
    <?php echo _("The server has requested restart. This is usually to install an update") ?>.<br/>
    <?php echo _("The terminal will restart in") ?> <span id="resettimeval">10</span> <?php echo _("seconds") ?>.<br/><br/>
    <?php echo _("Press Ok to restart now or Cancel to prevent the restart") ?>.
</div>
<canvas id="receipt_canvas" style="background-color: white; display: none;" width="576"></canvas>
</body>
</html>
