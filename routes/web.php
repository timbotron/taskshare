<?php

use App\Controllers\Board;
use App\Controllers\Home;
use App\Controllers\Task;
use App\Controllers\TaskList;
use App\Controllers\Theme;
use FastRoute\RouteCollector;

// App routes. Core auth routes (/login, /create-account, /password-*, /logout)
// and the admin area (/admin) are mounted separately in public/index.php.
return function (RouteCollector $r) {
    // App pages
    $r->get('/', [Home::class, 'home_page']);
    $r->get('/logged-in-page', [Home::class, 'logged_in_page']);
    $r->post('/theme', [Theme::class, 'save_theme']);

    // Boards dashboard (owner-only)
    $r->get('/dashboard', [Board::class, 'dashboard']);
    $r->post('/boards', [Board::class, 'create']);
    $r->post('/boards/{id:\d+}/rename', [Board::class, 'rename']);
    $r->post('/boards/{id:\d+}/delete', [Board::class, 'delete']);

    // Public board view — anyone with the link can view (no login required)
    $r->get('/b/{slug:[A-Za-z0-9]+}', [Board::class, 'loadboard']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/permissions', [Board::class, 'save_permissions']);

    // Board JSON API (list + task CRUD). Mutations authorized in the handlers.
    $r->post('/b/{slug:[A-Za-z0-9]+}/lists', [TaskList::class, 'create']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}', [TaskList::class, 'rename']);
    $r->delete('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}', [TaskList::class, 'delete']);
    $r->delete('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}/completed', [TaskList::class, 'clear_completed']);
    $r->post('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}/tasks', [Task::class, 'create']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}/tasks/reorder', [Task::class, 'reorder']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/tasks/{id:\d+}', [Task::class, 'edit']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/tasks/{id:\d+}/complete', [Task::class, 'complete']);
};
