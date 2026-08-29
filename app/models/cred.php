<?php

namespace Initium;

class Cred extends Base {

	public function __construct() {
		parent::__construct();

	}

	public static function userDetails(): array | bool {
		// returns array of user detail from session if logged in, else false
		return $_SESSION['user_data'] ?? false;
	}

	public function login(string $email, string $password): bool {

		if(empty($password) || strpos($password, "\0") !== false
			|| strlen($password) > 200)
		{
			return false;
		}

		// password at least is sensical, lets look up user
		$user = $this->db->get("users", ['id','email','password'], ['is_active' => 1, 'email'=> $email]);

		if($user && password_verify($password, $user['password'])) {
			// login good. Record the login time on the row
			$this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);

			// Regenerate the id at the privilege boundary to
			// prevent session fixation, then set session stuff
			session_regenerate_id(true);
			$_SESSION['user_data'] = [
				'user_id' => $user['id'],
				'email' => $user['email'],
			];

			return true;
		}
		else {
			// username or password incorrect
			return false;
		}

	}

	public static function logout(): bool {
		session_unset(); 
		session_destroy(); 
		return true;
	}

}
