<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        FAQ
        <small>
            <i class="icon-double-angle-right"></i>
            frequently asked questions & troubleshooting
        </small>
    </h1>
</div><!-- /.page-header -->

<div class="row">
<div class="col-xs-12">
<!-- PAGE CONTENT BEGINS -->

<div class="tabbable">
<ul class="nav nav-tabs padding-18 tab-size-bigger" id="myTab">
    <li class="active">
        <a data-toggle="tab" href="#faq-tab-1">
            <i class="blue icon-question-sign bigger-120"></i>
            General
        </a>
    </li>

    <li>
        <a data-toggle="tab" href="#faq-tab-2">
            <i class="green icon-print bigger-120"></i>
            Hardware
        </a>
    </li>

    <li>
        <a data-toggle="tab" href="#faq-tab-3">
            <i class="orange icon-credit-card bigger-120"></i>
            Sales
        </a>
    </li>

    <li>
        <a data-toggle="tab" href="#faq-tab-4">
            <i class="purple icon-magic bigger-120"></i>
            Misc
        </a>
    </li><!-- /.dropdown -->
</ul>

<div class="tab-content no-border padding-24">
<div id="faq-tab-1" class="tab-pane fade in active">
    <h4 class="blue">
        <i class="icon-ok bigger-110"></i>
        General Questions
    </h4>

    <div class="space-8"></div>

    <div id="faq-list-1" class="panel-group accordion-style1 accordion-style2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-1-1" data-parent="#faq-list-1" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>

                    <i class="icon-cloud-upload bigger-130"></i>
                    &nbsp; How are offline sales, void & refunds processed?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-1-1">
                <div class="panel-body">
                    <p>WallacePOS uses some pretty new & clever technology to enable offline processing of sales, refunds & voids.<br/>
                    Offline mode is available to users once they have signed into a device for the first time.<br/>
                    When in offline mode, new transactions and updates get saved into hmtl5 local storage, which is retained when closing the web browser. When the system detects a connection is available, it will attempt to synchronise these records with the server & other POS terminals.<br/>
                    You can take sales & orders in offline mode, as well as processing refunds, voids & updating sales notes for records that are locally available.<br/>
                    WallacePOS also allows you to perform all these actions for sales & orders that are still offline.</p>
                    <p>
                        Note: For security purposes, admin users must login in online mode before offline functionality becomes available. Locally available records can be adjusted via the admin dashboard, POS settings section.
                    </p>
                </div>
            </div>
        </div>



        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-1-3" data-parent="#faq-list-1" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>

                    <i class="icon-fast-forward bigger-130"></i>
                    &nbsp; How is WPOS so fast to load some things?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-1-3">
                <div class="panel-body">
                    <p>
                    By storing data offline to be readily available, and by receiving real time updates of that data, WallacePOS is able keep a its most used operations lightning fast.<br/>
                    On the terminal side, this means that staff can look up & add items very quickly. In fact, the terminal doesn't use the internet connection at all until the sale is processed.<br/>
                    The transfer of sale data is asynchronous, meaning that you can continue with another sale while the last one is transfering.</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-1-4" data-parent="#faq-list-1" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>

                    <i class="icon-cogs bigger-130"></i>
                    &nbsp; How can I make changes to how the system works?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-1-4">
                <div class="panel-body">
                    <p>
                    Whether your business is interested seeing a new feature implemented or would like to modify WallacePOS to your own needs, we're here to guide you through the process.
                    <br/>Please let us know how we can help by using the "contact us" button in the menu. <br/>
                    Because WallacePOS is based on open-source technologies, there are millions of developers with the skills to extend your WallacePOS system, including our skilled in-house staff.</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-1-5" data-parent="#faq-list-1" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>

                    <i class="icon-lightbulb bigger-130"></i>
                    &nbsp; Another question, guide or cheatsheet?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-1-5">
                <div class="panel-body">
                     <p>Suggest good question to us and we'll include it in the next release.</p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-1-2" data-parent="#faq-list-1" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-left pull-right" data-icon-hide="icon-chevron-down" data-icon-show="icon-chevron-left"></i>

                    <i class="icon-sort-by-attributes-alt"></i>
                    &nbsp; Devices & Users
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-1-2">
                <div class="panel-body">
                    <div id="faq-list-nested-1" class="panel-group accordion-style1 accordion-style2">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <a href="#faq-list-1-sub-1" data-parent="#faq-list-nested-1" data-toggle="collapse" class="accordion-toggle collapsed">
                                    <i class="icon-plus smaller-80 middle" data-icon-hide="icon-minus" data-icon-show="icon-plus"></i>
                                    &nbsp;
                                    User permissions
                                </a>
                            </div>

                            <div class="panel-collapse collapse" id="faq-list-1-sub-1">
                                <div class="panel-body">
                                    <p>
                                    You can allow different staff & managers fine-tuned access to admin dashboard areas:</p>
                                    <ol>
                                        <li>Click on Settings -> Staff & Admins & click on the <i class="icon-pencil bigger-130"></i> edit icon of the user you want to modify.</li>
                                        <li>Give the user access to the dashboard by selecting yes in the Access select box. (If you would like to give the user all permissions, select admin instead and click update to finish).</li>
                                        <li>Tick the checkboxes corresponding to the areas and operations you would like to give the user access to.
                                    You may also select the dashboards that the user has access to. <br/>If you select none, the landing page that the user receives when logging is the first allowed by permissions.</li>
                                    </ol>
                                    <p>Notes: The admin user permissions cannot be modified. Only admins can see and modify settings, including users, devices & locations.</p>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <a href="#faq-list-1-sub-2" data-parent="#faq-list-nested-1" data-toggle="collapse" class="accordion-toggle collapsed">
                                    <i class="icon-plus smaller-80 middle" data-icon-hide="icon-minus" data-icon-show="icon-plus"></i>
                                    &nbsp;
                                    Device registration
                                </a>
                            </div>

                            <div class="panel-collapse collapse" id="faq-list-1-sub-2">
                                <div class="panel-body">
                                    <p>
                                    Device registration is simple, once off process for each device. Once registered your device is remembered with a special ID.
                                    Sometimes clearing extended browser cache can delete this ID, requiring the device to be re-registered.
                                    You can register multiple web browsers under the same device name, allowing staff members to use their preferred browser.</p>

                                    <strong>To register a device:</strong>
                                    <ol>
                                        <li>Browse to <a id="reglink" href="" target="_blank"></a> and login with your admin credentials. If your not an admin, get one to do this for you.</li>
                                        <li>
                                            You will be presented with a registration dialog.
                                            You may select an exiting device/location using the select boxes or create a new one using the provided text field.
                                            Once the form is filled, click register to complete the process.
                                        </li>
                                        <li>Users can now login and take sales using this device.</li>
                                    </ol>

                                    <p>Note: If you would like to Change a devices location after it has been registered, you may do this via the admin dashboard (admins only).</p>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <a href="#faq-list-1-sub-3" data-parent="#faq-list-nested-1" data-toggle="collapse" class="accordion-toggle collapsed">
                                    <i class="icon-plus smaller-80 middle" data-icon-hide="icon-minus" data-icon-show="icon-plus"></i>
                                    &nbsp;
                                    Device, Location & User management
                                </a>
                            </div>

                            <div class="panel-collapse collapse" id="faq-list-1-sub-3">
                                <div class="panel-body">
                                    <p>To keep track of past users, devices & location, WallacePOS provides functionality for disabling these items when they are no longer current.
                                    <br/>Once disabled, you may delete these items, but it is highly recommended to leave them in the system as past sales records may rely on these values.
                                    <br/>WallacePOS will include an archive function in the next release so you can safely archive old data and restore it if necessary.</p>
                                    <p>Note: Managing these items requires you to be an admin user.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="faq-tab-2" class="tab-pane fade">
    <h4 class="blue">
        <i class="green icon-print bigger-110"></i>
        Hardware Questions
    </h4>

    <div class="space-8"></div>

    <div id="faq-list-2" class="panel-group accordion-style1 accordion-style2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-2-1" data-parent="#faq-list-2" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-right smaller-80" data-icon-hide="icon-chevron-down align-top" data-icon-show="icon-chevron-right"></i>
                    &nbsp;
                    Tyro Eftpos Setup (Hosted versions only)
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-2-1">
                <div class="panel-body">
                    <p>Tyro integrated eftpos allows seemless integration between WPOS and your eftpos terminal to save time and avoid operator mistakes.</p>
                    <strong>To set up integrated Eftpos:</strong>
                    <ol>
                        <li>Login to the <a href="/">WPOS terminal</a> and click on the settings tab.</li>
                        <li>Under Integrated Eftpos, click on the enabled checkbox and select Tyro from providers.<br/>
                            <img style="padding-top: 5px;" src="/admin/assets/images/faq/faq-eftpos-settings.png">
                        </li>
                        <li>If you need to, change Eftpos receipt options based on your preferences.</li>
                        <li>Enter your merchant and terminal IDs and click on "Start Tyro Pairing". You will be prompted to perform the "Authorise POS" function on your terminal described in the next step.</li>
                        <li>From your Tyro device select Menu -> Settings -> Integrated Eftpos -> Authorise POS. Enter your Admin Password and press Ok.</li>
                        <li>After successfully Authorising on your tyro terminal, you will receive a "Pairing successful" message in the WPOS terminal. You are now ready to take Eftpos payments.</li>
                    </ol>
                    <p>Note: Due to license limitations, Tyro integration is only available in our hosted or enterprise versions.</p>

                    <strong>To make an integrated Eftpos transaction:</strong>
                    <ol>
                        <li>Create a sale as normal and click proceed.</li>
                        <li>If the customer is using multiple payment methods, add the additional payments (Not the tyro payment) to the sale before proceeding. The remaining balance will be calculated for you.</li>
                        <li>Click on the green "Tyro Eftpos" button. This will initialize the card payment on the Eftpos machine and show you payment progress through the wpos terminal. Once the customer makes a successful payment, the sale will be automatically processed.</li>
                        <li>Depending on your preferences, you will be asked if you would like a merchant receipt printed. If you decline, you can always view and reprint it from the Transaction details dialog.<br/>
                            <img style="padding-top: 5px;" src="/admin/assets/images/faq/faq-eftpos-receipts.png">
                        </li>
                    </ol>
                    <p>Note: At this time, WPOS only supports one integrated Eftpos transaction per sale.</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-2-1" data-parent="#faq-list-2" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-right smaller-80" data-icon-hide="icon-chevron-down align-top" data-icon-show="icon-chevron-right"></i>
                    &nbsp;
                    Printer setup guide
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-2-1">
                <div class="panel-body">
                    Coming soon
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-2-4" data-parent="#faq-list-2" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-right smaller-80" data-icon-hide="icon-chevron-down align-top" data-icon-show="icon-chevron-right"></i>
                    &nbsp;
                    Receipt customization guide
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-2-4">
                <div class="panel-body">
                    Coming soon
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-2-2" data-parent="#faq-list-2" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-right smaller-80" data-icon-hide="icon-chevron-down align-top" data-icon-show="icon-chevron-right"></i>
                    &nbsp;
                    My printer isn't working, what can I try?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-2-2">
                <div class="panel-body">
                    Firstly make sure all connections are secure between the printer and the computer/network. If using a network printer double check that the device is connected to the network.<br/>
                    Secondly, close your web browser (along with WallacePOS), re-open it and log back in. Make sure the "Print-App Connected" status is showing in the bottom right hand corner of WallaecPOS.<br/>
                    Try printing by using the test button in the settings window.
                    If the printer still fails, power off the printer, computer and if using a network printer any network equipment such as routers and switches. Power the devices back on and wait a few minutes before testing the printer.
                    If it's still not working, please <a href="#!contact">contact us</a> and we'll work through it together
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-2-3" data-parent="#faq-list-2" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-chevron-right middle smaller-80" data-icon-hide="icon-chevron-down align-top" data-icon-show="icon-chevron-right"></i>
                    &nbsp;
                    What's required for product barcode scanning & receipt printing?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-2-3">
                <div class="panel-body">
                    <p>
                    WallacePOS supports standard POS hardware.<br/>
                    Barcode scanning requires an android mobile phone with a camera (Android) or a USB barcode scanner (Windows,Mac,Linux).<br/>
                    Receipt printing requires an 80mm thermal receipt printer with ESC/P or ESC/POS (Epson standard code for printers). If you need 40mm support, contact us.<p/>
                    <p>Cash draw just needs to be a standard POS/electronic cash draw. This plugs into the printer using a phone-type plug.<br/>
                    Most of these printer are ESC/P but you should check before purchasing. Android devices require a network-connected model (Ethernet or wifi).<br/>
                    These two devices, being standard models, can be purchased cheaply off ebay. Printers can be bought for a little as ~$200, whilst cash-draws and scanners go from ~$60 and $~35 accordingly.</p>
                    <p>Scanner installation usually just requires you to plug the scanner into one of the USB ports on your computer. If you received instructions with the scanner, follow the manufactures directions.<br/>
                    No settings need to be modified in the terminal, you can login and scan items right away.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="faq-tab-3" class="tab-pane fade">
    <h4 class="blue">
        <i class="orange icon-credit-card bigger-110"></i>
        Sales Questions
    </h4>

    <div class="space-8"></div>

    <div id="faq-list-3" class="panel-group accordion-style1 accordion-style2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-3-1" data-parent="#faq-list-3" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-plus smaller-80" data-icon-hide="icon-minus" data-icon-show="icon-plus"></i>
                    &nbsp;
                    How does cash rounding occur?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-3-1">
                <div class="panel-body">
                    <p>Cash rounding occurs automatically when all payments in the sale are cash and reverted if all cash payments are removed.
                    <br/>The rounding is set to the closest 5 cents (Aus standard) but we are planning to make this a setting in the future to cater for more countries.
                    <br/>If you need this set differently, let us know and we'll modify it to your needs.</p>
                    <p>Since taxes are calculated on a per item level, cash rounding occurs after tax is calculated and the total tax is not altered by the rounding.
                    <br/>The total rounding for a period is displayed in the tax report, allowing you to alter the tax according to your countries regulations.</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-3-2" data-parent="#faq-list-3" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-plus smaller-80" data-icon-hide="icon-minus" data-icon-show="icon-plus"></i>
                    &nbsp;
                    How is tax applied to sales?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-3-2">
                <div class="panel-body">
                    <p>Tax is applied on a per item level. This allows items to have different tax applied to them and support for more complex tax schemes.
                    <br/>Tax rates are not modifyable in the admin dashboard, if yours is missing you can contact us to get your countries tax rates included.
                    <br/>At the present time only Australian GST tax rates are included as default.</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-3-3" data-parent="#faq-list-3" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-plus smaller-80" data-icon-hide="icon-minus" data-icon-show="icon-plus"></i>
                    &nbsp;
                    How do I use WPOS for a restaurant or cafe order?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-3-3">
                <div class="panel-body">
                    <p>WallacePOS order feature is perfect for most cafes & restaurants.</p>
                    <strong>To use the order feature:</strong>
                    <ol>
                        <li>Simply fill out a sale in the normal way by adding requested items and clicking the process button.</li>
                        <li>Instead of clicking "Complete", click "Add Order". The order is then uploaded to the server and distributed to other POS terminals.<br/>(This is based on you POS record setting)</li>
                        <li>(Optional) To print an order ticket/receipt, click on the "Recall" button and then "Print".</li>
                        <li>To return to the order, click on "Transactions" and find the order in the list. Click on the "Details" button, followed by "Complete".<br/>
                            This will load the order and you will be able to add further items or complete the order with a payment.</li>
                        <li>To complete the order with a payment, fill out the payment details and click "Complete". Alternatively you can commit new order changes by clicking the "Add Order" button again.</li>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="faq-tab-4" class="tab-pane fade">
    <h4 class="blue">
        <i class="purple icon-magic bigger-110"></i>
        Miscellaneous Questions
    </h4>

    <div class="space-8"></div>

    <div id="faq-list-4" class="panel-group accordion-style1 accordion-style2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-4-1" data-parent="#faq-list-4" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-tag" data-icon-hide="icon-hand-down" data-icon-show="icon-hand-right"></i>
                    &nbsp;
                    How does wallacePOS keep track of stock?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-4-1">
                <div class="panel-body">
                    <p>Stock tracking is an automatic process that requires little setup and management.</p>
                    <strong>To start tracking stock:</strong>
                    <ol>
                        <li>Add the item that you would like to track if it not already present. This is done from Items -> Stored Items on the admin dashboard.</li>
                        <li>Go to Items -> Stock in the menu and click the add button in the top right corner of the stock page.</li>
                        <li>Select the item you want to track and the location of the first stock, any stock qty and click save.</li>
                        <li>The items stock is now tracked at that location and the stock level is automatically deducted as sales take place. <br/>
                            To track more items & locations, repeat the process above.</li>
                    </ol>
                    <p>Once stock is being tracked you may transfer it to different locations, add more stock or set stock levels (stocktake).
                       The transfer feature will also start the tracking process for that receiving location.</p>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <a href="#faq-4-2" data-parent="#faq-list-4" data-toggle="collapse" class="accordion-toggle collapsed">
                    <i class="icon-zoom-in bigger-120" data-icon-hide="icon-smile" data-icon-show="icon-frown"></i>
                    &nbsp;
                    How can I export my data for external analysis?
                </a>
            </div>

            <div class="panel-collapse collapse" id="faq-4-2">
                <div class="panel-body">
                    WallacePOS provides a few ways to export your data, making it easy to use it the way you want.

                    CSV format is by far the easiest to work with. It's a human-readable text file that most spreadsheet applications open it.
                    You'll mostly find that you can open CSV with you current software but if not you can download a spreadsheet application by
                    heading to <a target="_blank" href="http://www.libreoffice.org/">LibreOffice.com</a> (Windows,Mac,Linux) or <a target="_blank" href="https://play.google.com/store/apps/details?id=com.google.android.apps.docs.editors.sheets&hl=en">Google Sheets</a> (Android).
                    Spreadsheet applications are very powerful and easy to use with a little experience. They allow fine-control your data and most have some nice graphing functionality.

                    WallacePOS also allows you to export your data in SQL database format, using the database backup function in the utilities section.
                    SQL is a widely used format that can we converted and adapted for other systems.

                    Additionally you may like to use the WallacePOS API to access JSON data. An API guide will be coming soon.
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- PAGE CONTENT ENDS -->
</div><!-- /.col -->
</div><!-- /.row -->
<!-- inline scripts related to this page -->
<script type="text/javascript">
    $(function() {
        var accord = $('.accordion');
        accord.on('hide', function (e) {
            $(e.target).prev().children(0).addClass('collapsed');
        });
        accord.on('show', function (e) {
            $(e.target).prev().children(0).removeClass('collapsed');
        });
        var reglink = $("#reglink");
        reglink.attr("href", "https://"+window.location.hostname+"/");
        reglink.text("https://"+window.location.hostname+"/");
        // hide loader
        WPOS.util.hideLoader();
    });
</script>