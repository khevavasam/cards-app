<?php
require_once __DIR__ . '/../app/db.php';

/* Сессия как в login.php */
session_name('CARDSAPP');
ini_set('session.cookie_path', '/cards-app');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

/* Авторизация обязательна */
if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}
$userId = (int)$_SESSION['user_id'];

/* Подключаем i18n и сразу бутстрапим язык (с учётом направления) */
require_once __DIR__ . '/../app/i18n.php';
i18n_bootstrap($pdo, $userId);

/* Email и отображаемое имя */
$userEmail = $_SESSION['user_email'] ?? null;
if (!$userEmail) {
  $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
  $stmt->execute([$userId]);
  $userEmail = $stmt->fetchColumn() ?: 'user@example.com';
}
$displayName = ucfirst(strtok($userEmail, '@')) ?: 'User';

/* Направление пользователя */
$st = $pdo->prepare("SELECT default_direction_id FROM users WHERE id = ? LIMIT 1");
$st->execute([$userId]);
$userDirId = (int)$st->fetchColumn();

/* Инфо по направлению (бейдж в UI) */
$dirInfo = null;
if ($userDirId) {
  $q = $pdo->prepare("SELECT id, slug, title FROM directions WHERE id = ? LIMIT 1");
  $q->execute([$userDirId]);
  $dirInfo = $q->fetch(PDO::FETCH_ASSOC);
}

/* Категории и деки только этого направления */
if ($userDirId) {
  $catsStmt = $pdo->prepare("
      SELECT c.id, c.name, COUNT(d.id) AS deck_count
      FROM categories c
      LEFT JOIN decks d ON d.category_id = c.id
      WHERE c.direction_id = :dir
      GROUP BY c.id, c.name
      ORDER BY c.sort_order, c.name
  ");
  $catsStmt->execute([':dir'=>$userDirId]);
  $cats = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

  $decksStmt = $pdo->prepare("
      SELECT d.id, d.name, d.description, d.cover, d.category_id, c.name AS cat_name, d.preview_count
      FROM decks d
      JOIN categories c ON c.id = d.category_id
      WHERE d.direction_id = :dir
      ORDER BY c.sort_order, d.sort_order, d.name
  ");
  $decksStmt->execute([':dir'=>$userDirId]);
  $decks = $decksStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $cats = [];
  $decks = [];
}

$totalDecksAll = (int)array_sum($cats ? array_column($cats, 'deck_count') : []);
$totalCats = (int)count($cats);

/* Доступы пользователя */
$st = $pdo->prepare("
    SELECT deck_id 
    FROM user_deck_access 
    WHERE user_id = :u AND (expires_at IS NULL OR expires_at > NOW())
");
$st->execute([':u' => $userId]);
$ownedDeckIds = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'deck_id');
$owned = array_fill_keys(array_map('intval', $ownedDeckIds), true);

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <title><?= t('site.title') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/cards-app/public/images/golova.png">
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
      background:
        radial-gradient(1200px 800px at 10% -10%, #eef6ff 0%, transparent 60%),
        radial-gradient(900px 700px at 90% 10%, #eaf5ff 0%, transparent 55%),
        linear-gradient(180deg, var(--bg-1), var(--bg-2));
      color:var(--ink); min-height:100vh;
    }
    .navbar{ background:rgba(255,255,255,.85); backdrop-filter:saturate(150%) blur(6px); border-bottom:1px solid var(--card-border); }
    .navbar-brand{ color:var(--ink); } .navbar-brand .bi{ color:var(--primary); }
    .sidebar-sticky{ position:sticky; top:1rem; }
    .profile-card{ background:var(--card-bg); border:1px solid var(--card-border); border-radius:1rem; box-shadow:var(--shadow-1); }
    .profile-card .avatar-ring{ outline:3px solid var(--ring); border-radius:999px; }
    .text-muted{ color:var(--ink-muted) !important; }
    .stat{ text-align:center; padding:.35rem .85rem; border:1px solid var(--card-border); border-radius:.75rem; background:#fafcff; min-width:88px; }
    .stat .num{ font-weight:700; font-size:1.1rem; color:var(--ink); }
    .stat .lbl{ color:var(--ink-muted); font-size:.8rem; }
    .deck-card{ background:var(--card-bg); border:1px solid var(--card-border); border-radius:1rem; overflow:hidden; box-shadow:var(--shadow-1); transform:translateY(0); transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease; position:relative; }
    .deck-card:hover{ transform:translateY(-6px); box-shadow:var(--shadow-2); border-color:#cfe8ff; }
    .deck-cover{ aspect-ratio:16/9; width:100%; overflow:hidden; background:#eaf4ff; position:relative; }
    .deck-cover img{ width:100%; height:100%; object-fit:cover; display:block; transform:scale(1); transition:transform .6s ease; }
    .deck-card:hover .deck-cover img{ transform:scale(1.04); }
    .deck-body{ padding:.9rem 1rem .6rem 1rem; }
    .deck-meta{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.35rem; }
    .deck-cat{ border:1px solid #ccefff; background:var(--primary-100); color:#075985; padding:.22rem .55rem; border-radius:.5rem; font-size:.75rem; font-weight:600; }
    .deck-title{ margin:0 0 .25rem 0; font-size:1.05rem; font-weight:700; color:var(--ink); }
    .deck-desc{ margin:0; color:var(--ink-muted); font-size:.92rem; }
    .deck-footer{ display:flex; gap:.5rem; justify-content:space-between; align-items:center; padding:.8rem 1rem 1rem 1rem; }
    .btn-primary{ --bs-btn-bg:var(--primary); --bs-btn-border-color:var(--primary); --bs-btn-hover-bg:var(--primary-600); --bs-btn-hover-border-color:var(--primary-600); --bs-btn-color:#fff; box-shadow:0 6px 18px rgba(14,165,233,.25); }
    .btn-outline-secondary{ border-color:#c7d2fe; color:#0f172a; background:#ffffff; }
    .btn-outline-secondary:hover{ background:#eff6ff; border-color:#93c5fd; color:#0f172a; }
    .section-title{ display:flex; align-items:center; gap:.6rem; margin:0 0 .9rem 0; font-weight:800; color:var(--ink); }
    .section-title .bi{ color:var(--primary); }
    .alert-empty{ background:#f0f9ff; border:1px dashed #bae6fd; color:#075985; }
    .locked-badge{ position:absolute; top:.5rem; right:.5rem; background:rgba(2,8,23,.6); color:#fff; font-size:.75rem; padding:.2rem .5rem; border-radius:.5rem; display:flex; align-items:center; gap:.35rem; }
    .btn-disabled-like{ pointer-events:none; opacity:.6; }

    .lang-switch { display:inline-flex; gap:.4rem; align-items:center; }
    .lang-switch select { width:auto; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand py-3">
  <div class="container"><!-- хочешь уже — замени на container-xxl / container -->
    <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="">
      <img src="images/golova.png"
           alt=""
           class="d-inline-block"
           width="56" height="56"> <!-- крути размер тут: 40–72 -->
      <span class="fs-3"><?= t('site.title') ?></span> <!-- текст тоже подрос -->
    </a>
  </div>
</nav>


<main class="container py-4">
  <div class="row g-4">
    <!-- ЛЕВАЯ КОЛОНКА -->
    <aside class="col-12 col-lg-3">
      <div class="sidebar-sticky">
        <div class="card profile-card text-center p-3">
          <div>
            <img
              src="https://ui-avatars.com/api/?name=<?= urlencode($displayName) ?>&background=0ea5e9&color=fff&size=128"
              alt="Avatar"
              class="rounded-circle border avatar-ring mb-3"
              width="128" height="128">
          </div>
          <h5 class="mb-1"><?= htmlspecialchars($displayName) ?></h5>
          <div class="text-muted small mb-1"><?= htmlspecialchars($userEmail) ?></div>

          <?php if ($dirInfo): ?>
            <div class="small mb-3">
              <span class="badge bg-info-subtle text-dark border" title="<?= t('direction.badge') ?>">
                <?= htmlspecialchars($dirInfo['title']) ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-center gap-2 mb-3">
            <div class="stat"><div class="num"><?= $totalDecksAll ?></div><div class="lbl"><?= t('user.decks') ?></div></div>
            <div class="stat"><div class="num"><?= $totalCats ?></div><div class="lbl"><?= t('user.categories') ?></div></div>
          </div>

          <div class="d-grid gap-2">
            <a href="#" class="btn btn-primary"><i class="bi bi-person-gear me-1"></i><?= t('user.edit_profile') ?></a>
            <a href="logout.php" class="btn btn-outline-secondary"><?= t('auth.logout') ?></a>
          </div>
        </div>
      </div>
    </aside>

    <!-- ПРАВАЯ КОЛОНКА: деки -->
    <section class="col-12 col-lg-9">
      <h2 class="section-title">
        <i class="bi bi-collection-play"></i>
        <?= t('nav.my_decks') ?>
      </h2>

      <?php if (!$userDirId): ?>
        <div class="alert alert-empty" role="alert">
          <?= t('alert.no_direction') ?>
        </div>
      <?php elseif (empty($decks)): ?>
        <div class="alert alert-empty" role="alert">
          <?= t('alert.no_decks') ?>
        </div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4">
          <?php foreach ($decks as $d):
            $deckId    = (int)$d['id'];
            $hasAccess = isset($owned[$deckId]);
            $previewN  = (int)($d['preview_count'] ?? 5);
          ?>
            <div class="col">
              <div class="deck-card h-100">
                <?php if (!$hasAccess): ?>
                  <div class="locked-badge"><i class="bi bi-lock-fill"></i> <?= t('deck.locked') ?></div>
                <?php endif; ?>

                <div class="deck-cover">
                  <?php if (!empty($d['cover'])): ?>
                    <img src="<?= htmlspecialchars($d['cover']) ?>" alt="<?= htmlspecialchars($d['name']) ?>" loading="lazy" decoding="async">
                  <?php else: ?>
                    <img src="https://dummyimage.com/1280x720/eaf4ff/94a3b8&text=No+cover" alt="No cover" loading="lazy" decoding="async">
                  <?php endif; ?>
                </div>

                <div class="deck-body">
                  <div class="deck-meta">
                    <span></span><span></span>
                    <span class="deck-cat"><?= htmlspecialchars($d['cat_name']) ?></span>
                  </div>
                  <h3 class="deck-title"><?= htmlspecialchars($d['name']) ?></h3>
                  <?php if (!empty($d['description'])): ?>
                    <p class="deck-desc"><?= htmlspecialchars($d['description']) ?></p>
                  <?php endif; ?>
                </div>

                <div class="deck-footer">
                  <?php if ($hasAccess): ?>
                    <a href="/cards-app/public/learn.php?deck=<?= $deckId ?>" class="btn btn-primary">
                      <i class="bi bi-play-circle me-1"></i><?= t('deck.learn') ?>
                    </a>
                    <a href="/cards-app/public/progress.php?deck_id=<?= $deckId ?>" class="btn btn-outline-secondary">
                      <i class="bi bi-graph-up-arrow me-1"></i><?= t('deck.progress') ?>
                    </a>
                  <?php else: ?>
                    <a href="/cards-app/public/learn.php?deck=<?= $deckId ?>" class="btn btn-outline-secondary">
                      <i class="bi bi-eye me-1"></i><?= t('deck.preview') ?>
                    </a>
                    <span class="btn btn-outline-secondary btn-disabled-like">
                      <i class="bi bi-graph-up-arrow me-1"></i><?= t('deck.progress') ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
