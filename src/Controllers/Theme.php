<?php

namespace App\Controllers;

use Initium\Auth\Cred;

// POST /theme — persist a logged-in user's light/dark preference (JSON).
// Theme is read back from users.theme by the base's layout wiring (CODE-88),
// so there's nothing to stash in the session here.
class Theme extends Base {

    public function save_theme() {
        $viewer = Cred::userDetails();
        if(!$viewer) {
            $this->json_error('Not allowed.', 403);
        }

        $input = $this->json_input();
        $theme = ($input['theme'] ?? '') === 'dark' ? 'dark' : 'light';
        $this->db->update('users', ['theme' => $theme], ['id' => $viewer['user_id']]);
        $this->json(['theme' => $theme]);
    }
}
