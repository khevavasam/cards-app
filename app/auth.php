<?php
// app/auth.php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Нормализация email (трим + нижний регистр)
 */
function norm_email(string $email): string {
    return strtolower(trim($email));
}

/**
 * Текущий пользователь (кэшируем в сессии и локально)
 */
function current_user(): ?array {
    static $cached = null;
    if ($cached !== null) return $cached;

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return null;

    $stmt = db()->prepare("SELECT id, email, role, created_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    $cached = $user ?: null;
    return $cached;
}

/**
 * Проверка ролей
 */
function user_has_role(string|array $roles): bool {
    $u = current_user();
    if (!$u) return false;
    $roles = (array)$roles;
    return in_array($u['role'], $roles, true);
}

/**
 * Редирект-хэлпер
 */
function redirect(string $to): never {
    header("Location: /cards-app/public{$to}");
    exit;
}


/**
 * Требовать логин
 */
function require_login(): void {
    if (!current_user()) {
        // Можно сохранить intended URL
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
        redirect('/login.php');
    }
}

/**
 * Требовать определённую роль
 */
function require_role(string|array $roles): void {
    require_login();
    if (!user_has_role($roles)) {
        http_response_code(403);
        die('Forbidden');
    }
}

/**
 * Регистрация
 * Возвращает [true, null] при успехе или [false, 'сообщение об ошибке']
 */
function register_user(string $email, string $password, string $role = 'user'): array {
    $email = norm_email($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Некорректный email'];
    }
    if (strlen($password) < 8) {
        return [false, 'Пароль должен быть не короче 8 символов'];
    }
    if (!in_array($role, ['admin','editor','user'], true)) {
        $role = 'user';
    }

    // Проверка существования
    $stmt = db()->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return [false, 'Пользователь с таким email уже существует'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare("
        INSERT INTO users (email, password_hash, role, created_at)
        VALUES (:email, :hash, :role, NOW())
    ");
    try {
        $stmt->execute([
            ':email' => $email,
            ':hash'  => $hash,
            ':role'  => $role,
        ]);
    } catch (PDOException $e) {
        return [false, 'Ошибка регистрации'];
    }

    return [true, null];
}

/**
 * Логин
 */
function login_user(string $email, string $password): array {
    $email = norm_email($email);

    $stmt = db()->prepare("SELECT id, email, password_hash, role FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return [false, 'Неверный email или пароль'];
    }

    // защита от устаревших хэшей — при желании можно перехэшировать
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = db()->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $upd->execute([':h' => $newHash, ':id' => $user['id']]);
    }

    // Регистрация сессии
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    return [true, null];
}

/**
 * Логаут
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
