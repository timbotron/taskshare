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
	    $response = curl_exec($curl);
	    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	    // Log the outcome so mail sends are visible in `docker compose logs php`.
	    // curl_exec returns false on transport failure; the key is never logged.
	    if($response === false) {
	        error_log('Mailgun send to ' . $email . ' failed (curl): ' . curl_error($curl));
	    }
	    else {
	        error_log('Mailgun send to ' . $email . ' -> HTTP ' . $status . ' ' . $response);
	    }
	    curl_close($curl);

	    return $response === false ? '' : $response;
	}
}
