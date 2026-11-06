<?php

class Pages {
    public function pagesController($page) {
        if (file_exists('views/' . $page . '.php')) {
            include 'views/' . $page . '.php';
        } else {
            if($page == '') {
                include 'views/login.php';
            } else {
            echo "Page not found.";
            }
        }
    }
}

?>