<?php
// гасим ТОЛЬКО админ-сессию
session_name('CARDSAPP_ADMIN');
ini_set('session.cookie_path', '/cards-app/admin');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ../public/login.php');
exit;
