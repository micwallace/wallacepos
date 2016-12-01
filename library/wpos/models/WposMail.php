<?php
/**
 * WposMail is part of Wallace Point of Sale system (WPOS) API
 *
 * WposMail is used to send all outgoing emails and can optionally include attachments and predefined message text
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)
 * @link       https://wallacepos.com
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      File available since 12/04/14 3:44 PM
 */
class WposMail {
    private $mail;
    private $configMdl;
    private $genconfig;

    function __construct($generalconfig = null){
        // Get config if it does not exist
        if ($generalconfig!==null){
           $this->genconfig = $generalconfig;
        } else {
            $this->configMdl = new WposAdminSettings();
            $this->genconfig = $this->configMdl->getSettingsObject("general");
        }
        // Initialize mail object
        include $_SERVER['DOCUMENT_ROOT'].$_SERVER['APP_ROOT']."library/PHPMailerAutoload.php";
        $this->mail = new PHPMailer();
    }

    /**
     * Init PHPMailer instance
     * @return PHPMailer
     */
    private function getMailer(){
        $config = WposAdminSettings::getConfigFileValues(true);
        $mail = new PHPMailer();
        $mail->isSMTP(); // Set mailer to use SMTP

        $mail->Host = ($config->email_host==""?"127.0.0.1":$config->email_host);  // Specify main and backup SMTP servers
        if (is_numeric($config->email_port))
            $mail->Port = intval($config->email_port);

        if ($config->email_tls==true)
            $mail->SMTPSecure = 'tls'; // Enable encryption, 'ssl' also accepted

        if ($config->email_user!="") {
            $mail->Username = $config->email_user;
            $mail->Password = $config->email_pass;
        }

        $mail->From = $this->genconfig->bizemail;
        $mail->FromName = $this->genconfig->bizname;
        $mail->isHTML(true);
        return $mail;
    }

    /**
     * @param $to
     * @param $msgid
     * @param array $values
     * @return bool|string
     */
    public function sendPredefinedMessage($to, $msgid, $values=[]){
        $mail = $this->getMailer();
        // get message
        if ($this->configMdl == null)
            $this->configMdl = new WposAdminSettings();
        $emails = $this->configMdl->getSettingsObject('email');
        $message = $emails->messages->{$msgid};
        if ($message==null){
            return "Could not load predefined message.";
        }
        $mail->addAddress($to);
        $mail->Subject = $message->subject;
        foreach ($values as $key=>$value){
            $message->body = str_replace("%".$key."%", $value, $message->body);
        }
        $mail->Body = $message->body;
        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return true;
        }
    }

    /**
     * Send specified email as HTML format
     * @param $to
     * @param $subject
     * @param $html
     * @param null $cc
     * @param null $bcc
     * @param null $attachment
     * @return bool|string
     */
    public function sendHtmlEmail($to, $subject, $html, $cc=null, $bcc=null, $attachment=null){
        $mail = $this->getMailer();
        $mail->Subject = $subject;
        $mail->Body    = $html;
        // Add addresses
        $to = explode(", ", $to);
        foreach($to as $toa){
            $mail->addAddress($toa);   // Name is optional
        }
        if ($cc!==null){
            $cc = explode(", ", $cc);
            foreach($cc as $cca){
                $mail->addCC($cca);   // Name is optional
            }
        }
        if ($bcc!==null){
            $bcc = explode(", ", $bcc);
            foreach($bcc as $bcca){
                $mail->addBCC($bcca);   // Name is optional
            }
        }
        // add attachment
        if ($attachment!==null)
            if (is_array($attachment)){
                $mail->addStringAttachment($attachment[0], $attachment[1]);
            } else {
                $mail->addAttachment($attachment);
            }

        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return true;
        }
    }
}