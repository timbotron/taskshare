<?php

namespace Initium;

// Task CRUD API. Board-scoped; every action authorizes via board_for_write().
// Add + edit-text only here; the complete toggle is CODE-83 and Clear
// Completed (the only delete path) is CODE-84.
class Task extends Base {

    // POST /b/{slug}/lists/{id}/tasks
    public function create($vars) {
        $board = $this->board_for_write($vars['slug']);
        if(!$this->db->has('lists', ['id' => $vars['id'], 'board_id' => $board['id']])) {
            $this->json_error('List not found.', 404);
        }

        $text = $this->valid_text();

        $position = $this->next_position('tasks', ['list_id' => $vars['id']]);

        $this->db->insert('tasks', ['list_id' => $vars['id'], 'text' => $text, 'completed' => 0, 'position' => $position]);
        $this->json(['id' => (int) $this->db->id(), 'text' => $text, 'completed' => 0, 'position' => $position]);
    }

    // PUT /b/{slug}/tasks/{id}
    public function edit($vars) {
        $board = $this->board_for_write($vars['slug']);
        $this->require_task_in_board($vars['id'], $board['id']);

        $text = $this->valid_text();
        $this->db->update('tasks', ['text' => $text], ['id' => $vars['id']]);
        $this->json(['id' => (int) $vars['id'], 'text' => $text]);
    }

    // PUT /b/{slug}/tasks/{id}/complete — set (not toggle) the completed flag.
    // The client sends the desired state, so it's idempotent and race-free.
    public function complete($vars) {
        $board = $this->board_for_write($vars['slug']);
        $this->require_task_in_board($vars['id'], $board['id']);

        $input = $this->json_input();
        $completed = empty($input['completed']) ? 0 : 1;
        $this->db->update('tasks', ['completed' => $completed], ['id' => $vars['id']]);
        $this->json(['id' => (int) $vars['id'], 'completed' => $completed]);
    }

    protected function require_task_in_board($task_id, $board_id) {
        $task = $this->db->get('tasks', ['id', 'list_id'], ['id' => $task_id]);
        if(!$task || !$this->db->has('lists', ['id' => $task['list_id'], 'board_id' => $board_id])) {
            $this->json_error('Task not found.', 404);
        }
    }

    // Validate the required `text` field from the JSON body, or send a 422.
    protected function valid_text(): string {
        $input = $this->json_input();
        $v = new \Valitron\Validator($input);
        $v->rule('required', 'text');
        $v->rule('lengthMax', 'text', 500);
        if(!$v->validate()) {
            $this->json_error('Invalid task text.', 422);
        }
        return $input['text'];
    }
}
