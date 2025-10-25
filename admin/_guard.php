<?php
// проверяем именно админ-сессию
if (empty($_SESSION['user_id']) || (($_SESSION['user_type'] ?? '') !== 'admin')) {
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/cards-app/admin/admin_dashboard.php';
    header('Location: ../public/login.php');
    exit;
}
