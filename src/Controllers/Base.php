<?php

namespace App\Controllers;

use Initium\Auth\Cred;
use Initium\View;

/**
 * Base for TaskShare's own controllers. Extends core's Initium\Base (which owns
 * $this->db, the flash queue, return_code, isUUID, generate_uuid) and adds the
 * app-only toolkit that is not a framework concern: the JSON API helpers, the
 * ordered-position helper, the login gate, and board_for_action (the board
 * authorization gate). Also wires the shared Plates engine (override-first via
 * core's View) and the data the layout expects.
 */
class Base extends \Initium\Base {

    protected $templates;

    public function __construct() {
        parent::__construct(); // $this->db + flash queue

        // Shared Plates engine (app:: resolves app-first, core-fallback) + layout state.
        $this->templates = View::engine();
        $viewer = Cred::userDetails();
        $this->templates->addData([
            'is_logged_in' => $viewer ? true : false,
            'is_admin' => Cred::isAdmin(),
            // Core's Cred session omits theme (CODE-88), so fetch it for the layout.
            'user_theme' => $viewer ? $this->db->get('users', 'theme', ['id' => $viewer['user_id']]) : null,
        ], ['app::basic']);
    }

    protected function require_login() {
        // Gate for owner-only pages (dashboard, board settings). Bounces
        // anonymous visitors to the login page.
        if(!Cred::userDetails()) {
            header('Location: ' . SITE_URL . 'login');
            exit;
        }
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

    // Load a board by slug and authorize the caller for a mutating action, or
    // send a JSON error and die. The owner may do anything. An anonymous /
    // non-owner caller is allowed only when $permission (a board_permissions
    // column) is given and enabled for this board; owner-only actions pass none.
    protected function board_for_action(string $slug, ?string $permission = null): array {
        $board = $this->db->get('boards', ['id', 'owner_id', 'slug'], ['slug' => $slug]);
        if(!$board) {
            $this->json_error('Board not found.', 404);
        }

        $viewer = Cred::userDetails();
        if($viewer && $viewer['user_id'] == $board['owner_id']) {
            return $board;
        }

        if($permission !== null && (int) $this->db->get('board_permissions', $permission, ['board_id' => $board['id']]) === 1) {
            return $board;
        }

        $this->json_error('Not allowed.', 403);
    }

}
