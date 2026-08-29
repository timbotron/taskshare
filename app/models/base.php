<?php

namespace Initium;

class Base {

    protected $db;
    protected $messages;
    protected $templates;

    public function __construct() {
        $this->db = DB::getInstance()->connect();

        $this->messages = [];

        // Shared Plates engine + login state for every handler that renders.
        $this->templates = new \League\Plates\Engine(__DIR__ . '/../templates');
        $this->templates->addData(['is_logged_in' => Cred::userDetails() ? true : false], ['basic']);
    }

    protected function return_code(int $http_code) {
        switch ($http_code) {
            case 400:
                header('HTTP/1.1 400 Bad Request');
                break;

            case 404:
                header("HTTP/1.0 404 Not Found");
                break;

            case 500:
                header('HTTP/1.1 500 Internal Server Error');
                break;
            
            default:
                // code...
                break;
        }

        // kill the request
        die;
    }

    protected function require_login() {
        // Gate for owner-only pages (dashboard, board settings). Bounces
        // anonymous visitors to the login page.
        if(!Cred::userDetails()) {
            header('Location: ' . SITE_URL . 'login');
            exit;
        }
    }

    protected function isUUID($uuid): bool {
        $regex = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/';
        return preg_match($regex, $uuid) === 1;
    }

    protected function add_message($type, $value) {
        // types are error and info
        $this->messages[] = ['type' => $type, 'value' => $value];

        return true;
    }

    protected function get_messages(): array {
        $ret = $this->messages;
        $this->messages = [];
        return $ret;
    }

}
