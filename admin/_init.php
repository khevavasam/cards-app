<?php
// сессия админки — отдельное имя и path
session_name('CARDSAPP_ADMIN');
ini_set('session.cookie_path', '/cards-app/admin');
if (session_status() === PHP_SESSION_NONE) session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/_guard.php';

require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/upload.php';
