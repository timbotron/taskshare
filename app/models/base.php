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

    // --- JSON API helpers ---

    // Decode a JSON request body into an array (empty array if absent/invalid).
    protected function json_input(): array {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    // Send a JSON response and end the request.
    protected function json($data, int $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        die;
    }

    protected function json_error(string $message, int $code) {
        $this->json(['error' => $message], $code);
    }

    // Next `position` value for an ordered set. Medoo's max() returns '' (not
    // null) for an empty aggregate, so treat both as "no rows yet" -> 0.
    protected function next_position(string $table, array $where): int {
        $max = $this->db->max($table, 'position', $where);
        return ($max === null || $max === '') ? 0 : (int) $max + 1;
    }

    // Load a board by slug and authorize the caller to mutate it, or send a
    // JSON error and die. Owner-only for now; CODE-86 opens specific actions to
    // anonymous link-holders per board_permissions.
    protected function board_for_write(string $slug): array {
        $board = $this->db->get('boards', ['id', 'owner_id', 'slug'], ['slug' => $slug]);
        if(!$board) {
            $this->json_error('Board not found.', 404);
        }
        $viewer = Cred::userDetails();
        if(!$viewer || $viewer['user_id'] != $board['owner_id']) {
            $this->json_error('Not allowed.', 403);
        }
        return $board;
    }

}
