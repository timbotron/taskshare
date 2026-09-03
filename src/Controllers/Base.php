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

    protected $templates; // lazily built by view()
    protected $viewer;            // memoized userDetails() for this request
    private $viewer_loaded = false;

    // The logged-in user array (user_id, email, is_admin) or false, read once per
    // request from core's Cred and cached — handlers hit it several times (CODE-157).
    protected function viewer() {
        if(!$this->viewer_loaded) {
            $this->viewer = Cred::userDetails();
            $this->viewer_loaded = true;
        }
        return $this->viewer;
    }

    // Lazily build the shared Plates engine (app:: resolves app-first, core-fallback)
    // with the layout data. Only the HTML handlers call this; the JSON API handlers
    // never do, so they skip the engine build and the users.theme lookup entirely —
    // core's own Base constructor stays as lean as it renders (CODE-152).
    protected function view() {
        if($this->templates !== null) {
            return $this->templates;
        }
        $this->templates = View::engine();
        $viewer = $this->viewer();
        $this->templates->addData([
            'is_logged_in' => $viewer ? true : false,
            'is_admin' => Cred::isAdmin(),
            // Core's Cred session omits theme (CODE-88), so fetch it for the layout.
            'user_theme' => $viewer ? $this->db->get('users', 'theme', ['id' => $viewer['user_id']]) : null,
        ], ['app::basic']);
        return $this->templates;
    }

    protected function require_login() {
        // Gate for owner-only pages (dashboard, board settings). Bounces
        // anonymous visitors to the login page.
        if(!$this->viewer()) {
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

    // Validate a required string field from the JSON body, or send a 422 (CODE-157).
    protected function valid_text(string $field, int $max): string {
        $input = $this->json_input();
        $v = new \Valitron\Validator($input);
        $v->rule('required', $field);
        $v->rule('lengthMax', $field, $max);
        if(!$v->validate()) {
            $this->json_error('Invalid ' . $field . '.', 422);
        }
        return $input[$field];
    }

    // Next `position` value for an ordered set. Medoo's max() returns '' (not
    // null) for an empty aggregate, so treat both as "no rows yet" -> 0.
    //
    // MAX+1 is deliberately not atomic (CODE-159): two concurrent adds on a shared
    // board can land on the same position, but every ordered read tiebreaks with
    // `ORDER BY position, id`, so ordering stays deterministic — accepted over the
    // cost of DB-side sequencing for a low-write todo app.
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

        $viewer = $this->viewer();
        if($viewer && $viewer['user_id'] == $board['owner_id']) {
            return $board;
        }

        if($permission !== null && (int) $this->db->get('board_permissions', $permission, ['board_id' => $board['id']]) === 1) {
            return $board;
        }

        $this->json_error('Not allowed.', 403);
    }

}
