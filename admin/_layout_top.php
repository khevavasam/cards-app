<?php /* подключай в начале страницы */ ?>
<?php require_once __DIR__ . '/_init.php'; ?>

<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style_admin.css">
  <link rel="icon" type="image/png" href="/cards-app/public/images/golova.png">
  <style>
    .sidebar{min-width:230px;max-width:230px}
    .sidebar a{display:block;padding:.5rem .75rem;color:#333;text-decoration:none;border-radius:.5rem}
    .sidebar a.active,.sidebar a:hover{background:#f1f3f5}
    .img-thumb{width:60px;height:60px;object-fit:cover;border-radius:.5rem;border:1px solid #dee2e6}
  </style>
</head>
<body class="bg-light">
<div class="container-fluid">
  <div class="row">
    <aside class="col-12 col-md-3 col-xl-2 p-3 bg-white border-end sidebar">
      <h5 class="mb-3">Admin Panel</h5>
      <a href="admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])==='admin_dashboard.php'?'active':'' ?>">🏠 Dashboard</a>
      <hr>
      <div class="small text-uppercase text-muted mb-2">Taxonomy</div>
      <a href="categories_list.php" class="<?= str_starts_with(basename($_SERVER['PHP_SELF']),'category')||basename($_SERVER['PHP_SELF'])==='categories_list.php'?'active':'' ?>">🗂 Categories</a>
      <a href="decks_list.php" class="<?= str_starts_with(basename($_SERVER['PHP_SELF']),'deck')||basename($_SERVER['PHP_SELF'])==='decks_list.php'?'active':'' ?>">📚 Decks</a>
      <hr>
      <div class="small text-uppercase text-muted mb-2">Cards</div>
      <a href="cards_list.php" class="<?= str_starts_with(basename($_SERVER['PHP_SELF']),'card')||basename($_SERVER['PHP_SELF'])==='cards_list.php'?'active':'' ?>">🃏 Cards</a>
      <hr>
      <div class="small text-uppercase text-muted mb-2">Access</div>
      <a href="deck_access.php" class="<?= basename($_SERVER['PHP_SELF'])==='deck_access.php'?'active':'' ?>">🔓 Deck access</a>
      <hr>
      <a href="logout.php" class="text-danger">🚪 Logout</a>
    </aside>
    <main class="col p-4">
