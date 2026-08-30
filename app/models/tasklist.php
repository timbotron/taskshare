<?php

namespace Initium;

// List CRUD API. Board-scoped; every action authorizes via board_for_action().
// (Named TaskList because `List` is a reserved word in PHP.)
class TaskList extends Base {

    // POST /b/{slug}/lists — owner or allow_create_lists
    public function create($vars) {
        $board = $this->board_for_action($vars['slug'], 'allow_create_lists');

        $position = $this->next_position('lists', ['board_id' => $board['id']]);
        $this->db->insert('lists', ['board_id' => $board['id'], 'title' => 'New List', 'position' => $position]);
        $this->json(['id' => (int) $this->db->id(), 'title' => 'New List', 'position' => $position, 'tasks' => []]);
    }

    // PUT /b/{slug}/lists/{id} — owner only (renaming isn't a shareable permission)
    public function rename($vars) {
        $board = $this->board_for_action($vars['slug']);
        $this->require_list_in_board($vars['id'], $board['id']);

        $title = $this->valid_text('title', 200);
        $this->db->update('lists', ['title' => $title], ['id' => $vars['id']]);
        $this->json(['id' => (int) $vars['id'], 'title' => $title]);
    }

    // DELETE /b/{slug}/lists/{id} — owner or allow_delete_lists; tasks cascade via FK
    public function delete($vars) {
        $board = $this->board_for_action($vars['slug'], 'allow_delete_lists');
        $this->require_list_in_board($vars['id'], $board['id']);

        $this->db->delete('lists', ['id' => $vars['id']]);
        $this->json(['ok' => true]);
    }

    // DELETE /b/{slug}/lists/{id}/completed — owner or allow_clear_completed.
    // The only place the completion flow actually deletes tasks.
    public function clear_completed($vars) {
        $board = $this->board_for_action($vars['slug'], 'allow_clear_completed');
        $this->require_list_in_board($vars['id'], $board['id']);

        $stmt = $this->db->delete('tasks', ['list_id' => $vars['id'], 'completed' => 1]);
        $this->json(['ok' => true, 'deleted' => $stmt ? $stmt->rowCount() : 0]);
    }

    protected function require_list_in_board($list_id, $board_id) {
        if(!$this->db->has('lists', ['id' => $list_id, 'board_id' => $board_id])) {
            $this->json_error('List not found.', 404);
        }
    }

    // Validate a required string field from the JSON body, or send a 422.
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
}
