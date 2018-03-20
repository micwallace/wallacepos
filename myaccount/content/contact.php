<div class="page-header">
    <h1>
        Contact Us
        <small>
            <i class="icon-double-angle-right"></i>
            to ask a question or get support.
        </small>
    </h1>
</div><!-- /.page-header -->

<div class="row">
    <div class="col-xs-12">
        <!-- PAGE CONTENT BEGINS -->
        <p style="text-align: center;">To contact WallacePOS staff, use the contact form below. <br/>
            Alternatively, you can call Michael on 0410 844 700<br /><br/>
            <i>Please note the security code below is case sensitive.</i></p>

        <div style="text-align: center; margin: 0;">
            <form id="contact_form" onsubmit="return sendMail()">
                <div style="margin: 0 auto; text-align: left; position: relative; max-width: 610px;">
                    <div style="text-align: left; width: 300px; display: inline-block;">
                        <label>Your name:<input type="text" name="sender_name" id="sender_name" size="30" /></label><br/>
                        <label>Email:<br/><input type="text" name="sender_email" id="sender_email" size="30" /></label><br/>
                        <label>Message:</label><textarea name="sender_message" id="sender_message" rows="5" cols="30"></textarea><br/>
                    </div>
                    <div style="text-align: left; width: 300px; display: inline-block; vertical-align: top;">
                        <div style="display: inline-block;"><img src="/assets/secureimage/securimage_show.php" alt="CAPTCHA Image" name="siimage" id="siimage" /><a tabindex="-1" style="border-style: none" href="#" title="Refresh Image" onclick="document.getElementById('siimage').src = '/assets/secureimage/securimage_show.php?sid=' + Math.random(); return false"><br/><img src="/assets/secureimage/images/refresh.png" alt="Reload Image" border="0" onclick="this.blur()" align="top" /><span class="style1">Reload Captcha</span></a></div><br/>
                        <label>Security Code:</label><input type="text" id="code" name="code" size="8" /><br/>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <input style="color: white !important;" class="btn btn-sm btn-primary" id="submit_contact" type="submit" value="Send Message" />
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function(){
        WPOS.util.hideLoader();
    });
    function sendMail(){
        var submitbtn = $("#submit_contact");
        submitbtn.prop('disabled', true);
        submitbtn.val('Sending..');
        var name = $("#sender_name").val();
        var email = $("#sender_email").val();
        var message = $("#sender_message").val();
        var code = $("#code").val();
        var data = $.ajax({
            url: "/assets/process_contact.php",
            type: "POST",
            data: {sender_name: name, sender_email: email, sender_message: message, code: code},
            dataType: "html",
            async: false
        }).responseText;
        if (data=="OK"){
            $("#submit_contact").val('Your message has been sent!');
            $("#sender_name").val('');
            $("#sender_email").val('');
            $("#sender_message").val('');
            $("#code").val('');
        } else {
            alert(data);
            submitbtn.val('Send Message');
            submitbtn.prop('disabled', false);
        }
        return false;
    }
</script>