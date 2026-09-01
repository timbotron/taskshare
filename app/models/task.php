<?php

namespace Initium;

// Task CRUD API. Board-scoped; every action authorizes via board_for_action().
// Add + edit-text are owner-only; complete is gated by allow_complete. Clear
// Completed (the only delete path) lives on TaskList (CODE-84).
class Task extends Base {

    // POST /b/{slug}/lists/{id}/tasks — owner only (adding tasks isn't shareable)
    public function create($vars) {
        $board = $this->board_for_action($vars['slug']);
        if(!$this->db->has('lists', ['id' => $vars['id'], 'board_id' => $board['id']])) {
            $this->json_error('List not found.', 404);
        }

        $text = $this->valid_text();

        $position = $this->next_position('tasks', ['list_id' => $vars['id']]);
        $this->db->insert('tasks', ['list_id' => $vars['id'], 'text' => $text, 'completed' => 0, 'position' => $position]);
        $this->json(['id' => (int) $this->db->id(), 'text' => $text, 'completed' => 0, 'position' => $position]);
    }

    // PUT /b/{slug}/tasks/{id} — owner only (editing text isn't shareable)
    public function edit($vars) {
        $board = $this->board_for_action($vars['slug']);
        $this->require_task_in_board($vars['id'], $board['id']);

        $text = $this->valid_text();
        $this->db->update('tasks', ['text' => $text], ['id' => $vars['id']]);
        $this->json(['id' => (int) $vars['id'], 'text' => $text]);
    }

    // PUT /b/{slug}/tasks/{id}/complete — owner or allow_complete. Sets (not
    // blindly toggles) the flag from the client-sent state; idempotent.
    public function complete($vars) {
        $board = $this->board_for_action($vars['slug'], 'allow_complete');
        $this->require_task_in_board($vars['id'], $board['id']);

        $input = $this->json_input();
        $completed = empty($input['completed']) ? 0 : 1;
        $this->db->update('tasks', ['completed' => $completed], ['id' => $vars['id']]);
        $this->json(['id' => (int) $vars['id'], 'completed' => $completed]);
    }

    // PUT /b/{slug}/lists/{id}/tasks/reorder — owner only (reordering is an edit
    // action, like editing text). Body: { order: [taskId, ...] } in the new order.
    public function reorder($vars) {
        $board = $this->board_for_action($vars['slug']);
        if(!$this->db->has('lists', ['id' => $vars['id'], 'board_id' => $board['id']])) {
            $this->json_error('List not found.', 404);
        }

        $order = $this->json_input()['order'] ?? null;
        if(!is_array($order) || count($order) === 0) {
            $this->json_error('Invalid order.', 422);
        }

        // Position only the tasks that actually belong to this list; the client
        // sends the full ordered set, so foreign/unknown ids are ignored.
        $valid = array_flip(array_map('intval', $this->db->select('tasks', 'id', ['list_id' => $vars['id']])));
        $position = 0;
        foreach($order as $task_id) {
            $task_id = (int) $task_id;
            if(isset($valid[$task_id])) {
                $this->db->update('tasks', ['position' => $position], ['id' => $task_id]);
                $position++;
            }
        }
        $this->json(['ok' => true]);
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
