<?php 
setcookie('token', null, -1, '/');
session_destroy();
redirect(BASE_URL('home/login'));
?>

