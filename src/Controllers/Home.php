<?php

namespace App\Controllers;

use Initium\Auth\Cred;

// The app's own pages, split out of the old monolithic User class (its auth
// methods are core's Initium\Auth\Controller now). Templates use the app::
// folder so a same-named file in this app's templates/ overrides a core default.
class Home extends Base {

    public function home_page() {
        $this->templates->addData(['page_title' => SITE_NAME], ['app::basic']);
        echo $this->templates->render('app::home');
    }

    public function logged_in_page() {
        $this->require_login();
        $user = Cred::userDetails();

        $this->templates->addData(['page_title' => SITE_NAME], ['app::basic']);
        $this->templates->addData(['user' => $user], ['app::logged_in_page']);
        echo $this->templates->render('app::logged_in_page');
    }
}
