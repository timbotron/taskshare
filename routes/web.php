<?php

use FastRoute\RouteCollector;

// App routes. Core auth + admin routes are mounted separately in public/index.php.
// The board/list/task/theme/dashboard handlers land here once the controllers
// move to App\Controllers (CODE-116); empty until then.
return function (RouteCollector $r) {
};
