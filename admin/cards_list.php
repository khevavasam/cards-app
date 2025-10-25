<?php require_once __DIR__ . '/_init.php'; ?>

<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Cards';

$catId  = (int)($_GET['category_id'] ?? 0);
$deckId = (int)($_GET['deck_id'] ?? 0);
$onlyActive = (int)($_GET['active'] ?? 0);

// было:
// $cats = db()->query("SELECT id,name FROM categories ORDER BY sort_order, id")->fetchAll();

// стало: тянем ещё direction_title
$cats = db()->query("
  SELECT c.id, c.name, c.sort_order, c.direction_id,
         d.title AS direction_title
  FROM categories c
  LEFT JOIN directions d ON d.id = c.direction_id
  ORDER BY d.title, c.sort_order, c.id
")->fetchAll(PDO::FETCH_ASSOC);

$decksSql = "SELECT id,name FROM decks";
$paramsDecks = [];
if ($catId) { $decksSql .= " WHERE category_id=:cid"; $paramsDecks[':cid']=$catId; }
$decksSql .= " ORDER BY sort_order, id";
$stD = db()->prepare($decksSql); $stD->execute($paramsDecks);
$decks = $stD->fetchAll();

// delete
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = db()->prepare("DELETE FROM cards WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    header("Location: cards_list.php");
    exit;
}

// data
$sql = "SELECT ca.id, ca.title, ca.front_image_path, ca.back_image_path, ca.audio_path,
               ca.sort_order, ca.is_active, d.name AS deck_name, c.name AS category_name
        FROM cards ca
        JOIN decks d ON d.id = ca.deck_id
        LEFT JOIN categories c ON c.id = d.category_id
        WHERE 1=1";
$params = [];
if ($catId)  { $sql.=" AND d.category_id=:cid"; $params[':cid']=$catId; }
if ($deckId) { $sql.=" AND ca.deck_id=:did";   $params[':did']=$deckId; }
if ($onlyActive) { $sql.=" AND ca.is_active=1"; }
$sql .= " ORDER BY c.sort_order, d.sort_order, ca.sort_order, ca.id";
$st = db()->prepare($sql); $st->execute($params); $rows = $st->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Cards</h3>
  <a class="btn btn-primary" href="card_add.php">+ Add Card</a>
</div>

<form class="row gy-2 gx-2 mb-3" method="get">
  <div class="col-auto">
    <select name="category_id" class="form-select" onchange="this.form.submit()">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $catId==(int)$c['id']?'selected':'' ?>>
          <?= htmlspecialchars($c['name']) ?> — <?= htmlspecialchars($c['direction_title'] ?? '—') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="deck_id" class="form-select" onchange="this.form.submit()">
      <option value="0">All decks</option>
      <?php foreach ($decks as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deckId==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="active" value="1" id="chkActive" <?= $onlyActive?'checked':'' ?> onchange="this.form.submit()">
      <label class="form-check-label" for="chkActive">Only active</label>
    </div>
  </div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th><th>Title</th><th>Deck</th><th>Front</th><th>Back</th><th>Audio</th>
      <th>Sort</th><th>Active</th><th class="text-end">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['title']) ?></td>
      <td><?= htmlspecialchars($r['deck_name']) ?></td>
      <td><?php if ($r['front_image_path']): ?><img class="img-thumb" src="../public/<?= htmlspecialchars($r['front_image_path']) ?>"><?php endif; ?></td>
      <td><?php if ($r['back_image_path']): ?><img class="img-thumb" src="../public/<?= htmlspecialchars($r['back_image_path']) ?>"><?php endif; ?></td>
      <td><?php if ($r['audio_path']): ?><audio controls preload="none" style="width:140px"><source src="../public/<?= htmlspecialchars($r['audio_path']) ?>"></audio><?php endif; ?></td>
      <td><?= (int)$r['sort_order'] ?></td>
      <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-primary" href="card_edit.php?id=<?= $r['id'] ?>">Edit</a>
        <form class="d-inline" method="post" onsubmit="return confirm('Delete card?')">
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
