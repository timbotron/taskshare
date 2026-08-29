<?php

namespace Initium;

class User extends Base {

	protected $templates;

	public function __construct() {
		parent::__construct();
		$this->templates = new \League\Plates\Engine(__DIR__ . '/../templates');
		$this->templates->addData(['is_logged_in' => Cred::userDetails() ? true : false], ['basic']);



	}

	protected function generate_uuid() {
	    $uuid = random_bytes(16);

	    $uuid[6] = chr(ord($uuid[6]) & 0x0f | 0x40);
	    $uuid[8] = chr(ord($uuid[8]) & 0x3f | 0x80);

	    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($uuid), 4));
	}

	// create user
	public function create_user($email, $uuid) {

		$this->db->insert("users", [
	    	"email" => $email,
	    	"created_at" => date("Y-m-d"),
	    	"password_reset" => $uuid,
	    	"is_active" => 0,
	    ]);

	    if($this->db->error) {
	    	// log server-side; never surface the raw SQL error to the client
	    	error_log('create_user DB error: ' . $this->db->error);
	    	return false;
	    }

	    return true;
	}

	// render + send the set/reset-password email for a given uuid
	protected function send_set_password_email($email, $uuid, $reset_type, $subject) {
		$validate_url = SITE_URL . 'password-reset/' . $uuid;

		$this->templates->addData(['reset_type' => $reset_type, 'page_title' => SITE_NAME, 'reset_link' => $validate_url], ['reset_password_email']);
		$email_html = $this->templates->render('reset_password_email');

		$mailer = new Email();
		$mailer->send_mailgun($email, $subject, 'Set/Reset Password here: ' . $validate_url . "\n\n-The " . SITE_NAME . ' team', $email_html);
	}

	public function login_page() {
		// just draw page
		$this->templates->addData(['page_title' => SITE_NAME . ' Login'], ['basic']);

		echo $this->templates->render('login', );

	}

	public function login() {
		$v = new \Valitron\Validator($_POST);
		$v->rule('required', ['email', 'password']);
		$v->rule('email', 'email');
		$v->rule('lengthMin', 'password', 8);
		$v->rule('lengthMax', 'password', 199);
		if(!$v->validate()) {
		    // Errors
		    foreach($v->errors() as $err_section) {
		    	foreach($err_section as $e) {
		    		$this->add_message('error', $e);
		    	}
		    }

		    $this->templates->addData(['messages' => $this->get_messages()], ['basic']);
		    $this->templates->addData(['post_content' => $_POST], ['login']);
		    $this->login_page();
		    return true;
		}

		$cred = new Cred();

		if(!$cred->login($_POST['email'], $_POST['password'])) {
			$this->add_message('error', 'Email or password incorrect.');
			$this->templates->addData(['messages' => $this->get_messages()], ['basic']);
		    $this->templates->addData(['post_content' => $_POST], ['login']);
		    $this->login_page();
		    return true;
		}

		header('Location: ' . SITE_URL . 'logged-in-page');
        exit;



	}

	public function logged_in_page() {
		$user = Cred::userDetails();

		if(!$user) {
			header('Location: ' . SITE_URL);
        exit; 
		}

		// just draw page
		$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
		$this->templates->addData(['user' => $user], ['logged_in_page']);
		echo $this->templates->render('logged_in_page', );
	}


	public function logout_page() {
		
		Cred::logout();

		$this->templates->addData(['is_logged_in' =>false], ['basic']);
		$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
		$this->templates->addData(['is_error' => 0, 'top_title' => "Logout Successful.", "page_message" =>"<p>You have been logged out.</p>"], ['general_message_page']);
		echo $this->templates->render('general_message_page', );

	}

	public function home_page() {
		// just draw page
		$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
		echo $this->templates->render('home', );

	}

	public function create_account_page() {
		if(!ALLOW_SIGNUPS) {
			$this->return_code(404);
		}

		// just draw page
		$this->templates->addData(['page_title' => SITE_NAME . ' Account Creation'], ['basic']);
		echo $this->templates->render('create_account', );

	}

	public function create_account() {
		if(!ALLOW_SIGNUPS) {
			$this->return_code(404);
		}

		// validate

		$v = new \Valitron\Validator($_POST);
		$v->rule('required', ['email', 'email2']);
		$v->rule('email', 'email');
		$v->rule('equals', 'email', 'email2');
		if(!$v->validate()) {
		    // Errors
		    foreach($v->errors() as $err_section) {
		    	foreach($err_section as $e) {
		    		$this->add_message('error', $e);
		    	}
		    }

		    $this->templates->addData(['messages' => $this->get_messages()], ['basic']);
		    $this->templates->addData(['post_content' => $_POST], ['create_account']);
		    $this->create_account_page();
		    return true;
		}

		// validated. Non-enumerable flow: always render the same success page
		// regardless of whether the email already exists.
		$email = $_POST['email'];
		$existing = $this->db->get('users', ['id', 'is_active'], ['email' => $email]);

		if(!$existing) {
			// brand-new email: create the inactive user and send the set-password link
			$uuid = $this->generate_uuid();
			if($this->create_user($email, $uuid)) {
				$this->send_set_password_email($email, $uuid, 'new', 'Welcome to ' . SITE_NAME);
			}
		}
		elseif(!$existing['is_active']) {
			// abandoned signup: refresh the uuid and re-send, so they aren't dead-ended
			$uuid = $this->generate_uuid();
			$this->db->update('users', ['password_reset' => $uuid], ['id' => $existing['id']]);
			$this->send_set_password_email($email, $uuid, 'new', 'Welcome to ' . SITE_NAME);
		}
		// existing and active: send nothing, but still show the same page below

		$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
		$this->templates->addData(['is_error' => 0, 'top_title' => "Created account", "page_message" =>"<p>Your account was successfully created. Please check your email for your confirmation and link to set your password.</p>"], ['general_message_page']);
		echo $this->templates->render('general_message_page', );
	}

	public function forgot_password_page() {
		// just draw page
		$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
		echo $this->templates->render('forgot_password_page', );

	}

	public function forgot_password() {
		// validate

		$v = new \Valitron\Validator($_POST);
		$v->rule('required', ['email']);
		$v->rule('email', 'email');
		if(!$v->validate()) {
		    // Errors
		    foreach($v->errors() as $err_section) {
		    	foreach($err_section as $e) {
		    		$this->add_message('error', $e);
		    	}
		    }

		    $this->templates->addData(['messages' => $this->get_messages()], ['basic']);
		    $this->templates->addData(['post_content' => $_POST], ['forgot_password_page']);
		    $this->forgot_password_page();
		    return true;
		}

		$user_id = $this->db->get('users','id', ['email'=>$_POST['email'], 'is_active' => 1]);

		if($user_id) {
			// actually found user, lets set uuid and trigger email
			$uuid = $this->generate_uuid();

			// update user record to have new uuid
			$this->db->update("users", ['password_reset' => $uuid], ['id' => $user_id]);

			$this->send_set_password_email($_POST['email'], $uuid, 'same', 'Reset Password for ' . SITE_NAME);
		}


		// either way, show success-y page

		$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
		$this->templates->addData(['is_error' => 0, 'top_title' => "New Password requested", "page_message" =>"<p>If your email exists in our system, you should receive an email with a password reset link soon.</p>"], ['general_message_page']);
		echo $this->templates->render('general_message_page', );

				//if good, create user
		//
	}

	public function reset_password_page($vars) {
		if(!$this->isUUID($vars['pass_uuid'])) {
			// is not a UUID
			$this->return_code(400);
		}

		// look up and see if uuid exists
		if(!$this->db->has('users',['password_reset'=>$vars['pass_uuid']])) {
			// UUID not found, 400 it
			$this->return_code(400);
		}

		// just draw page
		$this->templates->addData(['page_title' => SITE_NAME . ' Change Password'], ['basic']);
		$this->templates->addData(['uuid' => $vars['pass_uuid']], ['reset_password_page']);
		echo $this->templates->render('reset_password_page', );
	}

	public function reset_password($vars) {
		if(!$this->isUUID($vars['pass_uuid'])) {
			// is not a UUID
			$this->return_code(400);
		}

		$v = new \Valitron\Validator($_POST);
		$v->rule('required', ['password', 'password2']);
		$v->rule('lengthMin', 'password', 8);
		$v->rule('lengthMax', 'password', 199);
		$v->rule('equals', 'password', 'password2');
		if(!$v->validate()) {
		    // Errors
		    foreach($v->errors() as $err_section) {
		    	foreach($err_section as $e) {
		    		$this->add_message('error', $e);
		    	}
		    }

		    $this->templates->addData(['messages' => $this->get_messages()], ['basic']);
		    $this->reset_password_page($vars);
		    return true;
		}

		// look up and see if uuid exists
		$user_id = $this->db->get('users','id', ['password_reset'=>$vars['pass_uuid']]);
		if(!$user_id) {
			// user not found, 400 it
			$this->return_code(400);
		}
		else {
			// found user so lets set password, wipte password hash and move on in life
			$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ['cost' => 12]);
			
			if(!$this->db->update("users",["is_active" => 1, "password" => $password, "password_reset" => ''], ["id" => $user_id])) {
				// create user failed
				$this->templates->addData(['messages' => $this->get_messages()], ['basic']);
			    $this->reset_password_page($vars);
			    return true;
			}
			// just draw gen message
			$this->templates->addData(['page_title' => SITE_NAME], ['basic']);
			$this->templates->addData(['is_error' => 0, 'top_title' => "Password Changed Successfully", "page_message" =>"<p>Your password was changed successfully, please proceed to login.</p>\n"], ['general_message_page']);
			echo $this->templates->render('general_message_page', );

		}
	}

	// set password

	// reset password email

	// login page

	// reset password page

	//

    public static function go_here() {
        echo "you made it son\n";
    }

    public function test_instance($vars) {
        echo "instance!\n";
        var_dump($this->db, $vars);
    }
}
