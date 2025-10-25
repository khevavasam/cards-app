<?php
// public/api/known_toggle.php
require_once __DIR__ . '/../../app/db.php';

session_name('CARDSAPP');
ini_set('session.cookie_path', '/cards-app');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok'=>0,'error'=>'unauthorized']);
    exit;
}

$pdo = db();

$action  = $_POST['action']  ?? '';
$cardId  = isset($_POST['card_id']) ? (int)$_POST['card_id'] : 0;
$userId  = (int)$_SESSION['user_id'];

if (!$cardId || !in_array($action, ['know','unlearn'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>0,'error'=>'bad_request']);
    exit;
}

try {
    if ($action === 'know') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_known_cards (user_id, card_id) VALUES (?, ?)");
        $stmt->execute([$userId, $cardId]);
    } else { // unlearn
        $stmt = $pdo->prepare("DELETE FROM user_known_cards WHERE user_id=? AND card_id=?");
        $stmt->execute([$userId, $cardId]);
    }
    echo json_encode(['ok'=>1]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'server_error']);
}
