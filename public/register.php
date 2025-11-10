<?php
// public/register.php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/i18n.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();
$errors = [];

/** Load directions */
$dirsStmt = $pdo->query("SELECT id, slug, title FROM directions ORDER BY id");
$allDirections = $dirsStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Показываем только 2 направления в фиксированном порядке:
 * 1) fi-ua  → учишь Finnish, UI: Ukrainian
 * 2) ua-fi  → учишь Ukrainian, UI: Finnish
 */
$wanted = ['fi-ua', 'ua-fi'];
$directions = [];
foreach ($wanted as $slugWant) {
    foreach ($allDirections as $d) {
        if (dir_slug_normalize($d['slug']) === $slugWant) {
            $directions[] = $d;
            break;
        }
    }
}

/** Лейбл языка по коду */
function lang_label(string $code): string {
    return match (strtolower($code)) {
        'fi','fin','finnish'   => 'Finnish',
        'ua','uk','ukrainian'  => 'Ukrainian',
        'en','eng','english'   => 'English',
        default                => strtoupper($code),
    };
}

/** Короткие подписи для карточек: title = что учишь, hint = UI: второй язык */
function pretty_dir_label(string $slug): array {
    $s = dir_slug_normalize($slug);
    [$learn, $ui] = array_pad(preg_split('/-/', $s, 2), 2, '');
    return [
        'title' => lang_label($learn),
        'hint'  => 'UI: ' . lang_label($ui),
    ];
}

/** UI язык берём из второй части слага; map ua→uk */
function ui_lang_from_dir_slug(string $slug): string {
    $s = dir_slug_normalize($slug);
    [, $ui] = array_pad(preg_split('/-/', $s, 2), 2, '');
    $ui = strtolower($ui);
    $map = ['ua' => 'uk', 'uk' => 'uk', 'fi' => 'fi', 'en' => 'en'];
    return i18n_sanitize_lang($map[$ui] ?? I18N_DEFAULT);
}

/** Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass1 = (string)($_POST['password']  ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    $role  = 'user';
    $dirId = (int)($_POST['direction_id'] ?? 0);

    if ($email === '' || $pass1 === '' || $pass2 === '' || $dirId <= 0) {
        $errors[] = 'Please fill in all fields and choose a language.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($pass1) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($pass1 !== $pass2) {
        $errors[] = 'Passwords do not match.';
    } else {
        $st = $pdo->prepare("SELECT id, slug FROM directions WHERE id = ? LIMIT 1");
        $st->execute([$dirId]);
        $dirRow = $st->fetch(PDO::FETCH_ASSOC);

        $slugOk = $dirRow ? dir_slug_normalize((string)$dirRow['slug']) : '';
        if (!$dirRow || !in_array($slugOk, ['fi-ua','ua-fi'], true)) {
            $errors[] = 'Please choose a valid option.';
        } else {
            if (!function_exists('register_user')) {
                $errors[] = 'Function register_user was not found in app/auth.php.';
            } else {
                [$success, $err] = register_user($email, $pass1, $role);
                if ($success) {
                    $lang = ui_lang_from_dir_slug($dirRow['slug'] ?? '');
                    $upd = $pdo->prepare("
                        UPDATE users
                        SET default_direction_id = :dir, lang = :lang
                        WHERE email = :email
                        LIMIT 1
                    ");
                    $upd->execute([
                        ':dir'   => (int)$dirRow['id'],
                        ':lang'  => $lang,
                        ':email' => $email,
                    ]);

                    if (function_exists('login_user')) {
                        [$okLogin, $errLogin] = login_user($email, $pass1);
                    }
                    i18n_remember($lang);

                    $to = $_SESSION['intended_url'] ?? '/';
                    unset($_SESSION['intended_url']);
                    redirect($to);
                } else {
                    $errors[] = $err ?: 'Could not create the account.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Sign up</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/cards-app/public/images/golova.png">
  <style>
    :root{
      --bg-1:#ffffff; --bg-2:#f7fbff;
      --ink:#0f172a; --muted:#6b7280;
      --primary:#0ea5e9; --primary-600:#0284c7;
      --border:#e5e7eb; --ring:rgba(14,165,233,.18);
      --shadow:0 10px 24px rgba(2,132,199,.10);
    }
    body{
      min-height:100vh; margin:0; display:grid; place-items:center;
      background:
        radial-gradient(1000px 700px at 10% -10%, #eef6ff 0%, transparent 60%),
        radial-gradient(800px 600px at 90% 10%, #eaf5ff 0%, transparent 55%),
        linear-gradient(180deg, var(--bg-1), var(--bg-2));
      color:var(--ink);
    }
    .card-auth{
      width:100%; max-width:520px; background:#fff; border:1px solid var(--border);
      border-radius:16px; box-shadow:var(--shadow);
    }
    .form-control:focus{ border-color:#93c5fd; box-shadow:0 0 0 .2rem var(--ring); }
    .btn-primary{
      --bs-btn-bg:var(--primary); --bs-btn-border-color:var(--primary);
      --bs-btn-hover-bg:var(--primary-600); --bs-btn-hover-border-color:var(--primary-600);
    }
    .errors p{ margin:0; }

    .dir-wrap{ display:grid; gap:.6rem; grid-template-columns:repeat(2, 1fr); }
    .dir-tile{
      border:1px solid var(--border); border-radius:12px; padding:.7rem .8rem; background:#fff;
      display:flex; flex-direction:column; align-items:flex-start; justify-content:center; min-height:72px;
      transition:border-color .15s, box-shadow .15s, background .15s; cursor:pointer; user-select:none;
    }
    .dir-tile:hover{ border-color:#cfe7ff; box-shadow:0 0 0 .14rem var(--ring); }
    .dir-title{ font-weight:700; font-size:1rem; line-height:1.1; }
    .dir-hint{ font-size:.8rem; color:var(--muted); line-height:1.1; }
    input.btn-check:checked + .dir-tile{ border-color:var(--primary); box-shadow:0 0 0 .2rem var(--ring); background:#f7fcff; }
    .section-eyebrow{ font-size:.9rem; color:var(--muted); margin-bottom:.4rem; }
  </style>
</head>
<body>

<div class="card-auth">
  <div class="p-4 p-md-5">
    <h3 class="text-center mb-4">Create account</h3>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger errors" role="alert">
        <?php foreach ($errors as $e): ?>
          <p><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
      <div class="mb-3 text-center">
        <div class="section-eyebrow">Sign up with email</div>
      </div>

      <div class="form-floating mb-3">
        <input type="email" id="regEmail" name="email" class="form-control" placeholder="name@example.com" required>
        <label for="regEmail">Email</label>
      </div>

      <div class="form-floating mb-3">
        <input type="password" id="regPass" name="password" class="form-control" placeholder="••••••••" minlength="8" required>
        <label for="regPass">Password (min. 8 characters)</label>
      </div>

      <div class="form-floating mb-3">
        <input type="password" id="regPass2" name="password2" class="form-control" placeholder="••••••••" minlength="8" required>
        <label for="regPass2">Confirm password</label>
      </div>

      <div class="section-eyebrow">Which language are you learning?</div>
      <div class="dir-wrap mb-3">
        <?php foreach ($directions as $d):
          $meta = pretty_dir_label($d['slug']);
          $id   = (int)$d['id'];
          $checked = (isset($_POST['direction_id']) && (int)$_POST['direction_id'] === $id);
        ?>
          <input class="btn-check" type="radio" name="direction_id" id="dir<?= $id ?>" value="<?= $id ?>" <?= $checked ? 'checked' : '' ?> required>
          <label class="dir-tile" for="dir<?= $id ?>">
            <span class="dir-title"><?= htmlspecialchars($meta['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span class="dir-hint"><?= htmlspecialchars($meta['hint'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <button type="submit" class="btn btn-primary w-100 mb-2">Create account</button>

      <div class="text-center">
        <span class="text-muted">Already have an account? </span><a href="login.php">Sign in</a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
