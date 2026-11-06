<?php

require_once 'controller/pages.php';

$page = new Pages();
$page->pagesController(isset($_GET['page']) ? $_GET['page'] : '');

?>