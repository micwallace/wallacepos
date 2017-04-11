<!-- WallacePOS: Copyright (c) 2014 WallaceIT <micwallace@gmx.com> <https://www.gnu.org/licenses/lgpl.html> -->
<div class="page-header">
    <h1>
        General Settings
        <small>
            <i class="icon-double-angle-right"></i>
            Manage general application settings
        </small>
    </h1>
</div><!-- /.page-header -->
<div class="row">
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Formats</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>Timezone:</label></div>
                        <div class="col-sm-5">
                            <select id="timezone">
                        <?php
                            $timezones = DateTimeZone::listIdentifiers();
                            foreach ($timezones as $timezone){
                                echo('<option value="'.$timezone.'">'.$timezone.'</option>');
                            }
                        ?>
                            </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Date Format:</label></div>
                        <div class="col-sm-5">
                        <select id="dateformat">
                            <option value="d/m/y">dd/mm/yy</option>
                            <option value="m/d/y">mm/dd/yy</option>
                            <option value="Y-m-d">yyyy-mm-dd</option>
                        </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Currency Symbol:</label></div>
                        <div class="col-sm-5">
                        <select id="currency_symbol">
                            <option value="$">$ Dollar</option>
                            <option value="€">€ Euro</option>
                            <option value="£">£ Pound</option>
                            <option value="¥">¥ Yen/Yuan</option>
                            <option value="₣">₣ Franc</option>
                            <option value="₤">₤ Lira</option>
                            <option value="﷼">﷼ Saudi Riyal</option>
                            <option value="₧">₧ Peseta</option>
                            <option value="₹">₹ Indian Rupee</option>
                            <option value="₨">₨ Rupee</option>
                            <option value="₩">₩ Won</option>
                            <option value="₴">₴ Hryvnia</option>
                            <option value="₯">₯ Drachma</option>
                            <option value="₮">₮ Tugrik</option>
                            <option value="₲">₲ Guarani</option>
                            <option value="₱">₱ Peso</option>
                            <option value="₳">₳ Austral</option>
                            <option value="₵">₵ Cedi</option>
                            <option value="₭">₭ Kip</option>
                            <option value="₪">₪ New Sheqel</option>
                            <option value="₫">₫ Dong</option>
                            <option value="៛">៛ Riel</option>
                            <option value="Rp">Rp Rupiah</option>
                            <option value="kr">kr Krone/Kroon/Krona</option>
                            <option value="Kč">Kč Koruna</option>
                        </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Currency Decimals:</label></div>
                        <div class="col-sm-5">
                            <select id="currency_decimals">
                                <option value="0">0</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Decimal Separator:</label></div>
                        <div class="col-sm-5">
                            <select id="currency_decimalsep">
                                <option value=".">.</option>
                                <option value=",">,</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Thousand Separator:</label></div>
                        <div class="col-sm-5">
                            <select id="currency_thousandsep">
                                <option value=",">,</option>
                                <option value=".">.</option>
                                <option value=" "> </option>
                            </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Symbol Position:</label></div>
                        <div class="col-sm-5">
                            <select id="currency_symbolpos">
                                <option value="0">Before Amount</option>
                                <option value="1">After Amount</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group" style="display: none;">
                        <div class="col-sm-5"><label>Accounting Type:</label></div>
                        <div class="col-sm-5">
                            <select id="accntype">
                                <option value="cash">Cash</option>
                                <option value="accrual">Accrual</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Alternate Labels</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <p>Alternate Labels are used when printing receipts in an alternate language.</p>
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>Cash:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_cash" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Credit:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_credit" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Eftpos:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_eftpos" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Cheque:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_cheque" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Deposit:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_deposit" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Tendered:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_tendered" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Change:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_change" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Transaction Reference:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_transaction-ref" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Transaction ID:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_transaction-id" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Sale Time:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_sale-time" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Subtotal:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_subtotal" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Total:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_total" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Item:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_item" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Items:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_items" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Refund:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_refund" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Void Transaction:</label></div>
                        <div class="col-sm-5"><input type="text" id="altlabel_void-transaction" /></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Business Details</h4>
            </div>

            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-5"><label>Business Name:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizname" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Business #:</label></div>
                        <div class="col-sm-5"><input type="text" id="biznumber" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Admin/Info Email:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizemail" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Address:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizaddress" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Suburb:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizsuburb" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>State:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizstate" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Postcode:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizpostcode" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Country:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizcountry" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Business Logo:</label></div>
                        <div class="col-sm-5">
                            <input type="text" id="bizlogo" /><br/>
                            <img id="bizlogoprev" width="128" height="64" src="" />
                            <input type="file" id="bizlogofile" name="file" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>App Icon:</label></div>
                        <div class="col-sm-5"><input type="text" id="bizicon" /></div>
                    </div>
                </form>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Email</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>SMTP Host:</label></div>
                        <div class="col-sm-5"><input type="text" id="email_host" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>SMTP Port:</label></div>
                        <div class="col-sm-5"><input type="text" id="email_port" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>SMTP TLS (recommended):</label></div>
                        <div class="col-sm-5"><input type="checkbox" id="email_tls" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>SMTP Username:</label></div>
                        <div class="col-sm-5"><input type="text" id="email_user" /></div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>SMTP Password:</label></div>
                        <div class="col-sm-5"><input type="text" id="email_pass" /></div>
                    </div>
                    <small>The host and user specified must be allowed to send mail as the email address specified above.</small>
                </form>
            </div>
        </div>
        <div class="widget-box transparent">
            <div class="widget-header widget-header-flat">
                <h4 class="lighter">Google Contacts integration</h4>
            </div>
            <div class="widget-body" style="padding-top: 10px;">
                <form class="form-horizontal">
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Enable:</label></div>
                        <div class="col-sm-5">
                            <input type="checkbox" id="gcontact" value="1" />
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"><label>Account:</label></div>
                        <div class="col-sm-5">
                            <a class="congaccn" style="display: none;" href="javascript:initGoogleAuth();">Connect Google Account</a>
                            <a class="disgaccn" style="display: none;" href="javascript:removeGoogleAuth();">Disconnect Google Account</a>
                        </div>
                    </div>
                    <div class="space-4"></div>
                    <div class="form-group">
                        <div class="col-sm-5"></div>
                        <div class="col-sm-5">
                            <input class="congaccn" style="display: none;" placeholder="Paste Google Auth Code" type="text" id="gcontactcode" />
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
        var altlabels = {};
        var currencyformat = [];
        $("form :input").each(function(){
            if ($(this).prop('id').indexOf("currency")===0){
                switch ($(this).prop('id')){
                    case "currency_symbol":
                        currencyformat[0] = $(this).val();
                        break;
                    case "currency_decimals":
                        currencyformat[1] = $(this).val();
                        break;
                    case "currency_decimalsep":
                        currencyformat[2] = $(this).val();
                        break;
                    case "currency_thousandsep":
                        currencyformat[3] = $(this).val();
                        break;
                    case "currency_symbolpos":
                        currencyformat[4] = $(this).val();
                        break;
                }
            } else if ($(this).prop('id').indexOf("altlabel")===0){
                var name = $(this).prop('id').split("_")[1];
                altlabels[name] = $(this).val();
            } else {
                data[$(this).prop('id')] = $(this).val();
            }
        });
        data['currencyformat'] = currencyformat.join("~");
        data['altlabels'] = altlabels;
        data['gcontact'] = $("#gcontact").is(":checked")?1:0;
        data['email_tls'] = $("#email_tls").is(":checked");
        var result = WPOS.sendJsonData("settings/general/set", JSON.stringify(data));
        if (result !== false){
            WPOS.setConfigSet('general', result);
        }
        // hide loader
        WPOS.util.hideLoader();
    }

    function loadSettings(){
        options = WPOS.getJsonData("settings/general/get");
        // load option values into the form
        for (var i in options){
            if (i == "currencyformat"){
                var format = options[i].split("~");
                $("#currency_symbol").val(format[0]);
                $("#currency_decimals").val(format[1]);
                $("#currency_decimalsep").val(format[2]);
                $("#currency_thousandsep").val(format[3]);
                $("#currency_symbolpos").val(format[4]);
            } else if (i == "altlabels"){
                for (var x in options.altlabels){
                    $("#altlabel_"+x).val(options.altlabels[x]);
                }
            } else {
                $("#" + i).val(options[i]);
            }
        }
        $("#email_tls").prop('checked', options.email_tls);
        setGoogleUI();
        $("#bizlogoprev").attr("src", options.bizlogo);
    }
    function setGoogleUI(){
        var gcontact_enabled = $("#gcontact");
        gcontact_enabled.prop("checked", options.gcontact==1);
        gcontact_enabled.prop("disabled", options.gcontactaval!=1);
        if (options.gcontactaval==1){
            $(".congaccn").hide();
            $(".disgaccn").show();
        } else {
            $(".congaccn").show();
            $(".disgaccn").hide();
        }
    }
    function initGoogleAuth(){
        // show
        window.open('/api/settings/google/authinit','Connect with Google','width=500,height=500');
    }
    function removeGoogleAuth(){
        var answer = confirm("Are you sure you want to remove the current google acount & turn off intergration?");
        if (answer){
            // show loader
            WPOS.util.showLoader();
            var result = WPOS.getJsonData("settings/google/authremove");
            if (result!==false){
                alert("Google account successfully disconnected.");
                options.gcontact=0;
                options.gcontactaval=0;
                setGoogleUI();
            } else {
                alert("Google account removal failed.");
            }
            // hide loader
            WPOS.util.hideLoader();
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
        loadSettings();
        // hide loader
        WPOS.util.hideLoader();
    })
</script>