<?php require_once __DIR__ . '/_init.php'; ?>
<?php
$pageTitle = 'Categories';

// 1) словарь направлений: id => title
$dirMap = array_column(
  db()->query("SELECT id, title FROM directions")->fetchAll(PDO::FETCH_ASSOC),
  'title', 'id'
);

// delete
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = db()->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    header("Location: categories_list.php");
    exit;
}

// 2) получаем категории + ОБЯЗАТЕЛЬНО direction_id
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT id, name, sort_order, created_at, direction_id FROM categories";
if ($q !== '') {
    $sql .= " WHERE name LIKE :q";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY sort_order, id";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_layout_top.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Categories</h3>
  <a class="btn btn-primary" href="category_add.php">+ Add Category</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto"><input class="form-control" name="q" placeholder="Search..." value="<?= htmlspecialchars($q) ?>"></div>
  <div class="col-auto"><button class="btn btn-outline-secondary">Find</button></div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <tr><th>ID</th><th>Name</th><th>Sort</th><th>Created</th><th>Direction</th><th class="text-end">Actions</th></tr>
  </thead>
  <tbody>
    

  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= (int)$r['sort_order'] ?></td>
      <td><?= htmlspecialchars($r['created_at']) ?></td>
      <td><?= htmlspecialchars($dirMap[(int)$r['direction_id']] ?? '—') ?></td>

      <td class="text-end">
        <a class="btn btn-sm btn-outline-primary" href="category_edit.php?id=<?= $r['id'] ?>">Edit</a>
        <form class="d-inline" method="post" onsubmit="return confirm('Delete category?')">
          <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
