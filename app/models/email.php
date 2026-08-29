<?php

namespace Initium;

class Email {

    public function send_mailgun($email, $subject, $text_body, $html_body): string {
	    $config = [];
	    $config['api_key'] = EMAIL_MAILGUN_KEY;
	    $config['api_url'] = "https://api.mailgun.net/v3/" . EMAIL_MAILGUN_DOMAIN . "/messages";
	    $message = [];
	    $message['from'] = SITE_NAME . " <noreply@". EMAIL_MAILGUN_DOMAIN .">";
	    $message['to'] = $email;
	    $message['h:Reply-To'] = "<noreply@". EMAIL_MAILGUN_DOMAIN .">";
	    $message['subject'] = $subject;
	    $message['html'] = $html_body;
	    $message['text'] = $text_body;
	    $curl = curl_init($config['api_url']);
	    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	    curl_setopt($curl, CURLOPT_USERPWD, "api:{$config['api_key']}");
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $message);
	    return curl_exec($curl);
	}
}
