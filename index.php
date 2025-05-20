<?php
define("IN_SITE", true);
require_once(__DIR__.'/core/DB.php');
require_once(__DIR__.'/core/helpers.php');
$module = !empty($_GET['module']) ? $_GET['module'] : 'home';
$action = !empty($_GET['action']) ? $_GET['action'] : 'home';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$path = "resources/views/$module/$action.php";
if (file_exists($path)) {
    require_once(__DIR__.'/'.$path);
    exit();
} else {
    require_once(__DIR__.'/resources/views/errors/404.php');
    exit();
}
?>
