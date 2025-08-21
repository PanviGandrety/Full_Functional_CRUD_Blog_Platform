<?php
session_start();
$_SESSION = [];
session_unset();
session_destroy();
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
header('Location: login.php');
exit;
