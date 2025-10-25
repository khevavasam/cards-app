<?php require_once __DIR__ . '/_init.php'; ?>

<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Decks';

$catId = (int)($_GET['category_id'] ?? 0);

// категории для фильтра (+ название направления)
$cats = db()->query("
  SELECT c.id, c.name, c.direction_id,
         d.title AS direction_title
  FROM categories c
  LEFT JOIN directions d ON d.id = c.direction_id
  ORDER BY d.title, c.sort_order, c.id
")->fetchAll(PDO::FETCH_ASSOC);



$params = [];
$sql = "SELECT d.id, d.name, d.description, d.sort_order, d.created_at, d.cover, c.name AS category_name
        FROM decks d
        LEFT JOIN categories c ON c.id = d.category_id";
if ($catId) { $sql .= " WHERE d.category_id=:cid"; $params[':cid']=$catId; }
$sql .= " ORDER BY c.sort_order, d.sort_order, d.id";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Decks</h3>
  <a class="btn btn-primary" href="deck_add.php">+ Add Deck</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto">
    <select name="category_id" class="form-select" onchange="this.form.submit()">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $catId==(int)$c['id']?'selected':'' ?>>
          <?= htmlspecialchars($c['name']) ?>
          — <?= htmlspecialchars($c['direction_title'] ?? '—') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>


<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Cover</th>
      <th>Name</th>
      <th>Category</th>
      <th>Sort</th>
      <th>Created</th>
      <th class="text-end">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td>
        <?php if (!empty($r['cover'])): ?>
          <img src="<?= htmlspecialchars($r['cover']) ?>"
               alt="<?= htmlspecialchars($r['name']) ?>"
               width="40" height="40" loading="lazy" decoding="async">
        <?php else: ?>—<?php endif; ?>
      </td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['category_name'] ?? '—') ?></td>
      <td><?= (int)$r['sort_order'] ?></td>
      <td><?= htmlspecialchars($r['created_at']) ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-primary" href="deck_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
        <form class="d-inline" method="post" onsubmit="return confirm('Delete deck?')">
          <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
