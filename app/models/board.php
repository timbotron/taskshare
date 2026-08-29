<?php

namespace Initium;

class Board extends Base {

    const MAX_BOARDS = 5;

    // GET /dashboard — the logged-in owner's boards
    public function dashboard() {
        $this->require_login();
        $owner_id = Cred::userDetails()['user_id'];

        $boards = $this->db->select('boards', ['id', 'title', 'slug'], [
            'owner_id' => $owner_id,
            'ORDER' => ['created_at' => 'ASC'],
        ]);

        $this->templates->addData(['page_title' => SITE_NAME . ' — Your Boards'], ['basic']);
        $this->templates->addData(['messages' => $this->get_messages()], ['basic']);
        $this->templates->addData([
            'boards' => $boards,
            'at_cap' => count($boards) >= self::MAX_BOARDS,
            'max_boards' => self::MAX_BOARDS,
        ], ['dashboard']);
        echo $this->templates->render('dashboard');
    }

    // POST /boards — create a board (hard cap enforced server-side)
    public function create() {
        $this->require_login();
        $owner_id = Cred::userDetails()['user_id'];

        if($this->db->count('boards', ['owner_id' => $owner_id]) >= self::MAX_BOARDS) {
            $this->add_message('error', 'You have reached the maximum of ' . self::MAX_BOARDS . ' boards. Delete one to create another.');
            $this->dashboard();
            return;
        }

        if(!$this->valid_title()) {
            $this->dashboard();
            return;
        }

        $this->db->insert('boards', [
            'owner_id' => $owner_id,
            'title' => $_POST['title'],
            'slug' => $this->generate_slug(),
        ]);
        // one permissions row per board, all flags default off
        $this->db->insert('board_permissions', ['board_id' => $this->db->id()]);

        header('Location: ' . SITE_URL . 'dashboard');
        exit;
    }

    // POST /boards/{id}/rename
    public function rename($vars) {
        $this->require_login();
        $board = $this->owned_board_or_404($vars['id']);

        if(!$this->valid_title()) {
            $this->dashboard();
            return;
        }

        $this->db->update('boards', ['title' => $_POST['title']], ['id' => $board['id']]);
        header('Location: ' . SITE_URL . 'dashboard');
        exit;
    }

    // POST /boards/{id}/delete — cascades lists/tasks/permissions via FKs
    public function delete($vars) {
        $this->require_login();
        $board = $this->owned_board_or_404($vars['id']);

        $this->db->delete('boards', ['id' => $board['id']]);
        header('Location: ' . SITE_URL . 'dashboard');
        exit;
    }

    // Fetch a board only if it belongs to the current user; 404 otherwise
    // (never reveal or mutate someone else's board).
    protected function owned_board_or_404($id): array {
        $board = $this->db->get('boards', ['id', 'owner_id'], ['id' => $id]);
        if(!$board || $board['owner_id'] != Cred::userDetails()['user_id']) {
            $this->return_code(404);
        }
        return $board;
    }

    // Validate the posted board title, queuing an error message on failure.
    protected function valid_title(): bool {
        $v = new \Valitron\Validator($_POST);
        $v->rule('required', 'title');
        $v->rule('lengthMax', 'title', 200);
        if($v->validate()) {
            return true;
        }
        $this->add_message('error', 'Please enter a board title (up to 200 characters).');
        return false;
    }

    protected function generate_slug(): string {
        do {
            $slug = bin2hex(random_bytes(10)); // 20 hex chars, unguessable share slug
        } while($this->db->has('boards', ['slug' => $slug]));
        return $slug;
    }
}
