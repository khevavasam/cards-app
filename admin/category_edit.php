<?php require_once __DIR__ . '/_init.php'; ?>

<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Edit Category';
$id = (int)($_GET['id'] ?? 0);

/* Загружаем запись */
$stmt = db()->prepare("SELECT * FROM categories WHERE id=:id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { 
    header("Location: categories_list.php"); 
    exit; 
}

/* Загружаем направления */
$dirs = db()->query("SELECT id, slug, title FROM directions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $direction_id = (int)($_POST['direction_id'] ?? 0);

    if ($name === '') $errors[] = 'Name is required';
    if ($direction_id <= 0) $errors[] = 'Direction is required';

    if (!$errors) {
        $upd = db()->prepare("
            UPDATE categories 
            SET name = :n, sort_order = :s, direction_id = :dir 
            WHERE id = :id
        ");
        $upd->execute([
            ':n'   => $name,
            ':s'   => $sort,
            ':dir' => $direction_id,
            ':id'  => $id
        ]);

        // возвращаемся в список с фильтром по текущему направлению
        $back = 'categories_list.php?dir=' . urlencode(array_column($dirs, 'slug', 'id')[$direction_id]);
        header("Location: $back");
        exit;
    }
}

include __DIR__ . '/_layout_top.php';
?>
<h3>Edit Category</h3>
<?php if ($errors): ?>
  <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" class="mt-3" autocomplete="off" novalidate>
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" 
           value="<?= htmlspecialchars($_POST['name'] ?? $row['name']) ?>" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Sort order</label>
    <input class="form-control" name="sort_order" type="number" 
           value="<?= htmlspecialchars($_POST['sort_order'] ?? $row['sort_order']) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Direction <span class="text-danger">*</span></label>
    <select name="direction_id" class="form-select" required>
      <option value="">— Select direction —</option>
      <?php foreach ($dirs as $d): ?>
        <option value="<?= (int)$d['id'] ?>"
          <?= (int)($_POST['direction_id'] ?? $row['direction_id']) === (int)$d['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($d['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline-secondary" href="categories_list.php?dir=<?= urlencode(array_column($dirs, 'slug', 'id')[$row['direction_id']]) ?>">Cancel</a>
</form>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
