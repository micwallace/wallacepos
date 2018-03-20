<?php
$_SERVER['APP_ROOT'] = "/";
ini_set('date.timezone', 'Australia/Sydney');
require $_SERVER['DOCUMENT_ROOT']."/library/wpos/AutoLoader.php";
// check input
if (!isset($_REQUEST['ref'])){
    $error = "Did not receive a valid order reference";
} else if (!isset($_REQUEST['token'])){
    $error = "Did not receive a valid payment token";
} else {
    // get order details
    $eMdl = new EcomSalesModel();
    $order = $eMdl->getByRef($_REQUEST['ref']);
    if ($order===false || empty($order)){
        $error = "Could not retrieve your order";
    } else {
        $order = json_decode($order[0]['data']);
        if (($order->processdt+3600000<(time()*1000))||$order->balance==0){
            $error = "This order has expired or already been paid.";
        }
    }
}
?>
<html>
<head>
    <title>Confirm Payment -</title>
    <script src='/admin/assets/js/jquery-2.0.3.min.js'></script>
    <link rel="stylesheet" href="/admin/assets/css/ace.min.css"/>
    <link rel="stylesheet" href="/admin/assets/css/bootstrap.min.css"/>
    <script src="/myaccount/assets/accountplugin.js"></script>
    <script>
        $(function(){
            WOMS = new WOMSPluginBase();
        });
    </script>
    <script type="text/javascript">
        function doPayment(){
            $(window).onbeforeunload = function(){
                return "Payment is in progress, if you leave now it might not complete.";
            };
            var body = $('body');
            body.css('cursor', 'wait');
            var load = $("#loading_screen");
            $("#confirm_screen").hide();
            load.show();
            var result = WOMS.sendJsonData("sales/dopaypalpayment", JSON.stringify({ref: "<?php echo($_REQUEST['ref']);?>", token: "<?php echo($_REQUEST['token']);?>", payerID: "<?php echo($_REQUEST['PayerID']);?>"}), true);
            if (result.error=="OK"){
                // show success & reload in two seconds
                $("#loadingbartxt").text('Success, redirecting...');
                setTimeout('redirectToTransactions();', 2000);
            } else {
                $("#errordetails").text(result.error);
                load.hide();
                body.css('cursor', 'pointer');
                $("#error_screen").show();
            }
        }
        function redirectToTransactions(){
            document.location.href = '/myaccount/#!subscriptions';
        }
    </script>
</head>
<body class="login-layout">
<div class="login-box widget-box visible no-border" style="width: 100%; position: relative; max-width: 460px; margin: 0 auto; padding: 10px;">
    <div class="widget-main" style="padding-left: 10px; padding-right: 10px;">
        <div style="width: 100%;">
            <img style="width: 200px; height: 80px;" src="/assets/images/receipt-logo.png"/>
            <h3 style="margin: 0;" class="header blue lighter bigger">WallaceIT Checkout</h3>
        </div>
        <div id="container" style="min-height: 250px;">
            <div id="confirm_screen" style="width: 100%; margin-bottom: 50px; <?php if(isset($error))echo('display: none;');?>">
                <h4 style="margin: 10px; text-align: center;">Click on Process Payment to finalize your order.</h4>
                <table style="width: 100%; margin-bottom: 10px; font-family: Arial, Helvetica, Sans-serif;" class="table table-responsive">
                    <thead>
                    <tr>
                        <th style="text-align: left;">Qty</th>
                        <th style="text-align: left;">Name</th>
                        <th style="text-align: right;">Price</th>
                    </tr>
                    </thead>
                    <tbody id="orderitems">
                    <?php
                        if (!isset($error)&&(isset($order)&&$order!=false)){
                            foreach($order->items as $item){
                                echo('<tr><td style="vertical-align: top;">1</td><td><strong>'.$item->name.'</strong><br/>'.$item->description.'</td><td style="text-align: right; vertical-align: top;">AUD$'.$item->price.'</td></tr>');
                            }
                            echo('<tr><td colspan="2" style="text-align: right"><strong>Total:</strong></td><td style="text-align: right;"><strong>AUD$'.$order->total.'</strong></td></tr>');
                            echo('<tr><td colspan="2" style="text-align: right"><strong>Balance:</strong></td><td style="text-align: right;"><strong>AUD$'.$order->balance.'</strong></td></tr>');
                        }
                    ?>
                    </tbody>
                </table>
                <button onclick="doPayment();" style="float: right;" class="btn btn-primary button">Process Payment</button>
            </div>
            <div id="loading_screen" style="text-align: center; display: none;">
                    <h4 id="loadingbartxt">Processing Payment...</h4>
                    <div id="loadingprogdiv" class="progress progress-striped active pro">
                        <div class="progress-bar" id="loadingprog" style="width: 100%;"></div>
                    </div>
            </div>
            <div id="error_screen" class="error-container" style="text-align: center; <?php if(!isset($error))echo('display: none;');?>">
                <h4 class="header-color-dark red" style="padding: 2px;">There was an error processing your order</h4>
                <h4 id="errordetails" class="no-margin-bottom smaller"><?php if(isset($error))echo($error); ?></h4>
                <h5 style="margin: 5px;">Please <a href="/contact">contact</a> us for help</h5>
            </div>
        </div>
    </div>
</div>
</body>
</html>

