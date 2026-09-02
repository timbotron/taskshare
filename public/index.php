<?php

use Initium\Admin\Routes as AdminRoutes;
use Initium\Auth\Routes as AuthRoutes;
use Initium\Config;
use Initium\Kernel;
use Initium\View;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/_env.php';

// Fail fast with one clear message if any required constant is missing.
Config::validate();

// Resolve the app's templates ahead of core defaults (override-first).
View::override(__DIR__ . '/../templates');

// Sessions live above the web root, owned by the app.
(new Kernel(__DIR__ . '/../storage/sessions'))
    ->routes(require __DIR__ . '/../routes/web.php')   // app routes (CODE-116)
    ->routes([AuthRoutes::class, 'register'])          // core auth routes
    ->routes([AdminRoutes::class, 'register'])          // core admin area (/admin)
    ->run();
