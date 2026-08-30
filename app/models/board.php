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

    // GET /b/{slug} — public board view. PHP loads the full board state and
    // embeds it as JSON so Mithril hydrates on first paint (no data XHR).
    public function loadboard($vars) {
        $board = $this->db->get('boards', ['id', 'title', 'slug', 'owner_id'], ['slug' => $vars['slug']]);
        if(!$board) {
            $this->return_code(404);
        }

        $lists = $this->db->select('lists', ['id', 'title', 'position'], [
            'board_id' => $board['id'],
            'ORDER' => ['position' => 'ASC', 'id' => 'ASC'],
        ]);

        // one query for every task on the board, then group by list
        $tasks_by_list = [];
        $list_ids = array_column($lists, 'id');
        if(count($list_ids) > 0) {
            $tasks = $this->db->select('tasks', ['id', 'list_id', 'text', 'completed', 'position'], [
                'list_id' => $list_ids,
                'ORDER' => ['position' => 'ASC', 'id' => 'ASC'],
            ]);
            foreach($tasks as $t) {
                $tasks_by_list[$t['list_id']][] = [
                    'id' => (int) $t['id'],
                    'text' => $t['text'],
                    'completed' => (int) $t['completed'],
                    'position' => (int) $t['position'],
                ];
            }
        }

        $lists_out = [];
        foreach($lists as $l) {
            $lists_out[] = [
                'id' => (int) $l['id'],
                'title' => $l['title'],
                'position' => (int) $l['position'],
                'tasks' => $tasks_by_list[$l['id']] ?? [],
            ];
        }

        $perms = $this->db->get('board_permissions',
            ['allow_complete', 'allow_clear_completed', 'allow_create_lists', 'allow_delete_lists'],
            ['board_id' => $board['id']]);

        $viewer = Cred::userDetails();
        $is_owner = $viewer && $viewer['user_id'] == $board['owner_id'];

        $state = [
            'board' => ['id' => (int) $board['id'], 'title' => $board['title'], 'slug' => $board['slug']],
            'is_owner' => $is_owner,
            'permissions' => [
                'allow_complete' => (bool) ($perms['allow_complete'] ?? false),
                'allow_clear_completed' => (bool) ($perms['allow_clear_completed'] ?? false),
                'allow_create_lists' => (bool) ($perms['allow_create_lists'] ?? false),
                'allow_delete_lists' => (bool) ($perms['allow_delete_lists'] ?? false),
            ],
            'lists' => $lists_out,
        ];

        // JSON_HEX_* keeps user-supplied task text from breaking out of the <script> tag
        $state_json = json_encode($state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $this->templates->addData(['page_title' => $board['title'] . ' — ' . SITE_NAME], ['basic']);
        $this->templates->addData(['board' => $board, 'is_owner' => $is_owner, 'state_json' => $state_json], ['board']);
        echo $this->templates->render('board');
    }

    // PUT /b/{slug}/permissions — owner-only: set the board's sharing flags.
    public function save_permissions($vars) {
        $board = $this->board_for_write($vars['slug']);
        $input = $this->json_input();

        $update = [];
        foreach(['allow_complete', 'allow_clear_completed', 'allow_create_lists', 'allow_delete_lists'] as $flag) {
            $update[$flag] = empty($input[$flag]) ? 0 : 1;
        }
        $this->db->update('board_permissions', $update, ['board_id' => $board['id']]);
        $this->json($update);
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
