<?php
require_once __DIR__ . '/../app/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<p style='color:green'>✅ Подключение успешно!</p>";

    // Проверим, есть ли таблица users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:blue'>✔ Таблица <b>users</b> найдена!</p>";
    } else {
        echo "<p style='color:red'>❌ Таблица users не найдена в базе.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Ошибка подключения: " . $e->getMessage() . "</p>";
}
?>
