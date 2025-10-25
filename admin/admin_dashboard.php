<?php require_once __DIR__ . '/_init.php'; ?>
<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Dashboard';

function count_simple($sql, $params = []) {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/* 1) Получаем направления */
$dirs = db()->query("SELECT id, slug, title FROM directions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

/* Если направлений нет — показываем предупреждение и выходим */
if (!$dirs) {
    include __DIR__ . '/_layout_top.php';
    echo '<div class="alert alert-warning">No directions found. Create at least one in the DB.</div>';
    include __DIR__ . '/_layout_bottom.php';
    exit;
}

/* 2) Определяем текущее направление
      - если не передано, редиректим на первое */
$dirSlug = isset($_GET['dir']) ? trim($_GET['dir']) : '';
if ($dirSlug === '') {
    header('Location: '.$_SERVER['PHP_SELF'].'?dir='.rawurlencode($dirs[0]['slug']));
    exit;
}

$stmt = db()->prepare("SELECT id, slug, title FROM directions WHERE slug = :slug LIMIT 1");
$stmt->execute([':slug' => $dirSlug]);
$dir = $stmt->fetch(PDO::FETCH_ASSOC);

/* Если slug кривой — тоже уводим на первое */
if (!$dir) {
    header('Location: '.$_SERVER['PHP_SELF'].'?dir='.rawurlencode($dirs[0]['slug']));
    exit;
}

/* 3) Счётчики ТОЛЬКО по выбранному направлению */
$params = [':dir_id' => $dir['id']];

$categories = count_simple(
    "SELECT COUNT(*) FROM categories WHERE direction_id = :dir_id",
    $params
);
$decks = count_simple(
    "SELECT COUNT(*) FROM decks WHERE direction_id = :dir_id",
    $params
);
$cards = count_simple(
    "SELECT COUNT(*) 
     FROM cards c 
     JOIN decks d ON d.id = c.deck_id 
     WHERE d.direction_id = :dir_id",
    $params
);
$active = count_simple(
    "SELECT COUNT(*) 
     FROM cards c 
     JOIN decks d ON d.id = c.deck_id 
     WHERE c.is_active = 1 AND d.direction_id = :dir_id",
    $params
);

include __DIR__ . '/_layout_top.php';
?>

<h2 class="mb-3">Welcome, Admin 👋</h2>

<!-- Фиксированный селект без "All directions" -->
<form class="row gy-2 gx-2 align-items-center mb-3" method="get">
  <div class="col-auto">
    <label for="dir" class="form-label mb-0">Direction</label>
  </div>
  <div class="col-auto">
    <select name="dir" id="dir" class="form-select" required>
      <?php foreach ($dirs as $d): ?>
        <option value="<?= htmlspecialchars($d['slug']) ?>"
          <?= $dir['slug'] === $d['slug'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($d['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-primary">Apply</button>
  </div>
</form>

<div class="alert alert-info py-2">
  Showing stats for: <strong><?= htmlspecialchars($dir['title']) ?></strong>
</div>

<div class="row g-3">
  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fs-1 fw-bold"><?= $categories ?></div>
        <div class="text-muted">Categories</div>
        <a class="btn btn-sm btn-outline-secondary mt-2" href="categories_list.php?dir=<?= htmlspecialchars($dir['slug']) ?>">Manage</a>
        <a class="btn btn-sm btn-primary mt-2" href="category_add.php?dir=<?= htmlspecialchars($dir['slug']) ?>">Add</a>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fs-1 fw-bold"><?= $decks ?></div>
        <div class="text-muted">Decks</div>
        <a class="btn btn-sm btn-outline-secondary mt-2" href="decks_list.php?dir=<?= htmlspecialchars($dir['slug']) ?>">Manage</a>
        <a class="btn btn-sm btn-primary mt-2" href="deck_add.php?dir=<?= htmlspecialchars($dir['slug']) ?>">Add</a>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fs-1 fw-bold"><?= $cards ?></div>
        <div class="text-muted">Cards</div>
        <a class="btn btn-sm btn-outline-secondary mt-2" href="cards_list.php?dir=<?= htmlspecialchars($dir['slug']) ?>">Manage</a>
        <a class="btn btn-sm btn-primary mt-2" href="card_add.php?dir=<?= htmlspecialchars($dir['slug']) ?>">Add</a>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="fs-1 fw-bold"><?= $active ?></div>
        <div class="text-muted">Active cards</div>
      </div>
    </div>
  </div>
</div>

<h5 class="mt-4 mb-2">By direction</h5>
<div class="row g-3">
  <?php foreach ($dirs as $d):
    $p = [':dir_id' => $d['id']];
    $cntCats   = count_simple("SELECT COUNT(*) FROM categories WHERE direction_id = :dir_id", $p);
    $cntDecks  = count_simple("SELECT COUNT(*) FROM decks WHERE direction_id = :dir_id", $p);
    $cntCards  = count_simple("SELECT COUNT(*) FROM cards c JOIN decks dk ON dk.id = c.deck_id WHERE dk.direction_id = :dir_id", $p);
    $cntActive = count_simple("SELECT COUNT(*) FROM cards c JOIN decks dk ON dk.id = c.deck_id WHERE c.is_active = 1 AND dk.direction_id = :dir_id", $p);
  ?>
    <div class="col-md-6 col-lg-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <strong><?= htmlspecialchars($d['title']) ?></strong>
            <a class="btn btn-sm btn-outline-primary" href="?dir=<?= htmlspecialchars($d['slug']) ?>">Open</a>
          </div>
          <div class="small text-muted">Categories: <strong><?= $cntCats ?></strong></div>
          <div class="small text-muted">Decks: <strong><?= $cntDecks ?></strong></div>
          <div class="small text-muted">Cards: <strong><?= $cntCards ?></strong></div>
          <div class="small text-muted">Active: <strong><?= $cntActive ?></strong></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
