<?php require_once __DIR__ . '/_init.php'; ?>

<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Add Category';
$errors = [];

/* Загружаем направления */
function getDirections() {
    return db()->query("SELECT id, slug, title FROM directions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

/* Текущий dir из GET (если пришел) */
$dirSlug = isset($_GET['dir']) ? trim($_GET['dir']) : '';
$currentDir = null;
if ($dirSlug !== '') {
    $stmt = db()->prepare("SELECT id, slug, title FROM directions WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $dirSlug]);
    $currentDir = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* Обработка формы */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $direction_id = (int)($_POST['direction_id'] ?? 0);

    if ($name === '') $errors[] = 'Name is required';
    if ($direction_id <= 0) $errors[] = 'Direction is required';

    // доп. проверка, что direction существует
    if ($direction_id > 0) {
        $check = db()->prepare("SELECT COUNT(*) FROM directions WHERE id = :id");
        $check->execute([':id' => $direction_id]);
        if ((int)$check->fetchColumn() === 0) {
            $errors[] = 'Selected direction does not exist';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare("
            INSERT INTO categories (name, sort_order, direction_id, created_at)
            VALUES (:name, :sort, :direction_id, NOW())
        ");
        $stmt->execute([
            ':name' => $name,
            ':sort' => $sort,
            ':direction_id' => $direction_id
        ]);

        // возвращаемся в список с тем же dir, если он был
        $back = 'categories_list.php' . ($currentDir ? ('?dir=' . urlencode($currentDir['slug'])) : '');
        header("Location: $back");
        exit;
    }
}

$dirs = getDirections();

include __DIR__ . '/_layout_top.php';
?>
<h3>Add Category</h3>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars',$errors)) ?></div>
<?php endif; ?>

<form method="post" class="mt-3" autocomplete="off" novalidate>
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Sort order</label>
    <input class="form-control" name="sort_order" type="number" value="<?= htmlspecialchars($_POST['sort_order'] ?? '0') ?>">
  </div>

  <?php if ($currentDir): ?>
    <!-- Направление зафиксировано фильтром -->
    <input type="hidden" name="direction_id" value="<?= (int)$currentDir['id'] ?>">
    <div class="mb-3">
      <label class="form-label">Direction</label>
      <input class="form-control" value="<?= htmlspecialchars($currentDir['title']) ?>" readonly>
    </div>
  <?php else: ?>
    <!-- Нет фильтра: обязуем выбрать направление -->
    <div class="mb-3">
      <label class="form-label">Direction <span class="text-danger">*</span></label>
      <select name="direction_id" class="form-select" required>
        <option value="">— Select direction —</option>
        <?php foreach ($dirs as $d): ?>
          <option value="<?= (int)$d['id'] ?>"
            <?= (isset($_POST['direction_id']) && (int)$_POST['direction_id'] === (int)$d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>

  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline-secondary" href="categories_list.php<?= $currentDir ? ('?dir=' . urlencode($currentDir['slug'])) : '' ?>">Cancel</a>
</form>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
