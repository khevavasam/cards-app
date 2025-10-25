<?php
// public/login.php (PHP)

require_once __DIR__ . '/../app/db.php';

// ==== FRONT SESSION ====
session_name('CARDSAPP');
ini_set('session.cookie_path', '/cards-app');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errors = [];

// Already logged in? Redirect by role
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['user_type'] ?? '') === 'admin') {
        header('Location: ../admin/admin_dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $errors[] = 'Please enter your email and password.';
    } else {
        $pdo = db();

        // Find user by email
        $stmt = $pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Support hashed and (temporarily) plaintext passwords
            $ok = password_verify($pass, (string)$user['password_hash']) || ((string)$user['password_hash'] === $pass);

            if ($ok) {
                // --- 1) Front session ---
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['user_type'] = $user['role'] ?: 'user';

                // --- 2) Admin → separate admin session ---
                if ($_SESSION['user_type'] === 'admin') {
                    session_write_close();

                    session_name('CARDSAPP_ADMIN');
                    ini_set('session.cookie_path', '/cards-app/admin');
                    session_start();
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = (int)$user['id'];
                    $_SESSION['user_type'] = 'admin';
                    session_write_close();

                    header('Location: ../admin/admin_dashboard.php');
                    exit;
                }

                // --- 3) Regular user → home ---
                header('Location: index.php');
                exit;
            }
        }

        // If we got here — login failed
        $errors[] = 'Invalid email or password.';
    }
}
?>




<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign in</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap + FA -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"/>

  <style>
    :root{
      --bg-1:#ffffff; --bg-2:#f5f9ff;
      --ink:#0f172a; --ink-muted:#64748b;
      --primary:#0ea5e9; --primary-600:#0284c7; --primary-100:#e0f2fe;
      --card-bg:#ffffff; --card-border:#e5e7eb; --ring:rgba(14,165,233,.25);
      --shadow-1:0 6px 18px rgba(2,132,199,.10);
      --shadow-2:0 14px 40px rgba(2,132,199,.18);
    }

    body{
      min-height:100vh; margin:0;
      display:grid; place-items:center;
      background:
        radial-gradient(1200px 800px at 10% -10%, #eef6ff 0%, transparent 60%),
        radial-gradient(900px 700px at 90% 10%, #eaf5ff 0%, transparent 55%),
        linear-gradient(180deg, var(--bg-1), var(--bg-2));
      color:var(--ink);
    }

    .auth-card{
      width:100%; max-width:520px;
      background:var(--card-bg);
      border:1px solid var(--card-border);
      border-radius:1rem;
      box-shadow:var(--shadow-1);
    }

    .form-control:focus{
      border-color:#93c5fd;
      box-shadow:0 0 0 .2rem var(--ring);
    }

    .btn-primary{
      --bs-btn-bg:var(--primary);
      --bs-btn-border-color:var(--primary);
      --bs-btn-hover-bg:var(--primary-600);
      --bs-btn-hover-border-color:var(--primary-600);
      --bs-btn-color:#fff;
      box-shadow:0 6px 18px rgba(14,165,233,.25);
    }

    .btn-outline-secondary{
      border-color:#c7d2fe; color:#0f172a; background:#ffffff;
    }
    .btn-outline-secondary:hover{
      background:#eff6ff; border-color:#93c5fd; color:#0f172a;
    }

    .errors p{ margin:0; }
    .divider{
      display:flex; align-items:center; gap:.75rem; color:var(--ink-muted); font-size:.9rem;
    }
    .divider::before,.divider::after{
      content:""; flex:1; height:1px; background:var(--card-border);
    }
  </style>
</head>
<body>

<div class="auth-card">
  <div class="p-4 p-md-5">
    <h3 class="text-center mb-4">Sign in</h3>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger errors" role="alert">
        <?php foreach ($errors as $e): ?>
          <p><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
      <div class="text-center mb-3">
        <div class="divider mb-3"><span>Continue with email</span></div>



      <div class="form-floating mb-3">
        <input type="email" id="loginEmail" name="email" class="form-control" placeholder="name@example.com" required>
        <label for="loginEmail">Email</label>
      </div>

      <div class="form-floating mb-3">
        <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
        <label for="loginPassword">Password</label>
      </div>

      <div class="row mb-4">
        <div class="col-md-6 d-flex align-items-center">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" checked>
            <label class="form-check-label" for="remember">Remember me</label>
          </div>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end justify-content-start mt-2 mt-md-0">
          <a href="/forgot.php" class="link-primary">Forgot password?</a>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 mb-3">Sign in</button>

      <div class="text-center">
        <p class="mb-0">Don’t have an account? <a href="register.php" class="link-primary">Sign up</a></p>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
