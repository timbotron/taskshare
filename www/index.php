<?php

namespace Initium;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
\AaronHolbrook\Autoload\autoload( __DIR__ . '/../app/config' );
require  __DIR__ . '/../vendor/verot/class.upload.php/src/class.upload.php';

$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
   
    $r->get('/',['\Initium\User','home_page']);
    $r->get('/logout',['\Initium\User','logout_page']);
    $r->get('/login',['\Initium\User','login_page']);
    $r->post('/login',['\Initium\User','login']);
    $r->get('/logged-in-page',['\Initium\User','logged_in_page']);
    $r->get('/create-account',['\Initium\User','create_account_page']);
    $r->post('/create-account',['\Initium\User','create_account']);
    $r->get('/password-forgot',['\Initium\User','forgot_password_page']);
    $r->post('/password-forgot',['\Initium\User','forgot_password']);
    $r->get('/password-reset/{pass_uuid}',['\Initium\User','reset_password_page']);
    $r->post('/password-reset/{pass_uuid}',['\Initium\User','reset_password']);

    // Boards dashboard (owner-only)
    $r->get('/dashboard',['\Initium\Board','dashboard']);
    $r->post('/boards',['\Initium\Board','create']);
    $r->post('/boards/{id:\d+}/rename',['\Initium\Board','rename']);
    $r->post('/boards/{id:\d+}/delete',['\Initium\Board','delete']);

    // Public board view — anyone with the link can view (no login required)
    $r->get('/b/{slug:[A-Za-z0-9]+}',['\Initium\Board','loadboard']);

    // Board JSON API (list + task CRUD). Mutations authorized in the handlers.
    $r->post('/b/{slug:[A-Za-z0-9]+}/lists',['\Initium\TaskList','create']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}',['\Initium\TaskList','rename']);
    $r->delete('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}',['\Initium\TaskList','delete']);
    $r->delete('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}/completed',['\Initium\TaskList','clear_completed']);
    $r->post('/b/{slug:[A-Za-z0-9]+}/lists/{id:\d+}/tasks',['\Initium\Task','create']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/tasks/{id:\d+}',['\Initium\Task','edit']);
    $r->put('/b/{slug:[A-Za-z0-9]+}/tasks/{id:\d+}/complete',['\Initium\Task','complete']);

});


// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        //sleep(5);
        header("HTTP/1.0 404 Not Found");
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        //sleep(5);
        header("HTTP/1.0 405 Method Not Allowed");
        break;
    case \FastRoute\Dispatcher::FOUND:
        // session timeouts + persistent-by-default login
        $session_lifetime = 3600 * LOGIN_TIMEOUT;

        // Keep sessions in a private store above the web root so the OS
        // sessionclean cron cannot purge our long-lived session files.
        $session_path = __DIR__ . '/../app/storage/sessions';
        if (!is_dir($session_path)) {
            mkdir($session_path, 0700, true);
        }
        session_save_path($session_path);
        ini_set('session.gc_maxlifetime', $session_lifetime);

        $cookie_base = [
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ];
        session_set_cookie_params(['lifetime' => $session_lifetime] + $cookie_base);
        session_start();

        // Re-send the cookie each request so the expiry slides forward with activity.
        setcookie(session_name(), session_id(), ['expires' => time() + $session_lifetime] + $cookie_base);

        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $class = new $handler[0];
        $class->{$handler[1]}($vars);    
        break;
}
