<?php

/**
 * Securimage with AJAX
 *
 * Author: Drew Phillips (www.phpcaptcha.org)
 *
 * This code is released to the public domain.
 *
 */

$your_email = 'micwallace@gmx.com';  // Email to send message to

if ($_SERVER['REQUEST_METHOD'] != 'POST') exit; // Quit if it is not a form post

// quick way clean up incoming fields
foreach($_POST as $key => $value) $_POST[$key] = urldecode(trim($value));

// get form data into shorter variables
// each $_POST variable is named based on the form field's id value
$name    = $_POST['sender_name'];
$email   = $_POST['sender_email'];
$message = $_POST['sender_message'];
$code    = $_POST['code'];

$errors  = []; // array of errors

// validation
if ($name == '') {
  $errors[] = "Please enter your name";
}

if ($email == '') {
  $errors[] = "Please enter your email address";
} else if (strpos($email, '@') === false) {
  $errors[] = "Please enter a valid email address";
}

if ($message == '') {
  $errors[] = "Please enter a message to send";
}

if (sizeof($errors) == 0) {
  // only check the code if there are no other errors
  require 'captcha/securimage.php';
  $img = new Securimage;
  if ($img->check($code) == false) {
    $errors[] = "Incorrect security code entered";
  } // if the code checked is correct, it is destroyed to prevent re-use
}

if (sizeof($errors) > 0) {
  // if errors, send the error message
  $str = implode("\n", $errors);
  die("There was an error with your submission!  Please correct the following:\n\n" . $str);
}

$time = date('r');
$body = <<<EOD
Hi!

An enquiry has been made by $name on $time.
Their email is: $email

Here is their message:

$message
EOD;

// send email
mail($your_email, "Wallacepos Client enquiry", $body, "From: admin@wallacepos.com\nContent-Type: text/plain; charset=ISO-8859-1\nMIME-Version: 1.0\nReply-To: ".$email);

die('OK'); // send success indicator

?>