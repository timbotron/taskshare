<?php

$f3=require('lib/base.php');

$f3->config('app/config/globals.ini');
$f3->config('app/config/routes.ini');
$f3->config('app/config/maps.ini');

$f3->set('db',new \DB\SQL('mysql:host='.$f3->get('DBHOST').';port='.$f3->get('DBPORT').';dbname='.$f3->get('DBNAME'),$f3->get('DBUSER'),$f3->get('DBPASS')));

$f3->route('GET /',
    function() {
        echo View::instance()->render('home.html');
    }
);

$f3->route('GET /test',
    function() {
        echo View::instance()->render('test.html');
    }
);

$f3->route('GET /new','Board->post');

$f3->route('GET /b/@boardcode','Board->loadboard');

$f3->route('GET /minify/@type',
    function($f3,$args) {
        $f3->set('UI',$args['type'].'/');
        echo Web::instance()->minify($_GET['files']);
    },
    3600
);
$f3->run();
