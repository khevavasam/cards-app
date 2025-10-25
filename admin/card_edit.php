<?php
require_once __DIR__ . '/_init.php';

$pageTitle = 'Edit Card';

// 1) validate id
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: cards_list.php");
    exit;
}

// 2) fetch current card
$stmt = db()->prepare("SELECT * FROM cards WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    header("Location: cards_list.php");
    exit;
}

// 3) decks for select
$decks = db()->query("
    SELECT d.id, d.name, c.name AS category_name
    FROM decks d
    LEFT JOIN categories c ON c.id = d.category_id
    ORDER BY c.sort_order, d.sort_order, d.id
")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deck_id = (int)($_POST['deck_id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $sort    = (int)($_POST['sort_order'] ?? 0);
    $active  = isset($_POST['is_active']) ? 1 : 0;

    if ($deck_id <= 0)   $errors[] = 'Deck is required';
    if ($title === '')    $errors[] = 'Title is required';

    // uploads (если не загружали — оставляем прежние пути)
    $front = upload_file('front_image', 'image');
    $back  = upload_file('back_image',  'image');
    $audio = upload_file('audio',       'audio');

    // если upload_file() вернуло пусто — берём старое
    $front = $front ?: $row['front_image_path'];
    $back  = $back  ?: $row['back_image_path'];
    $audio = $audio ?: $row['audio_path'];

    if (!$errors) {
        $upd = db()->prepare("
            UPDATE cards
               SET deck_id = :did,
                   title = :t,
                   front_image_path = :f,
                   back_image_path  = :b,
                   audio_path = :a,
                   sort_order = :s,
                   is_active = :act
             WHERE id = :id
        ");
        $upd->execute([
            ':did' => $deck_id,
            ':t'   => $title,
            ':f'   => $front,
            ':b'   => $back,
            ':a'   => $audio,
            ':s'   => $sort,
            ':act' => $active,
            ':id'  => $id
        ]);

        header("Location: cards_list.php");
        exit;
    }

    // если есть ошибки — подменяем $row, чтобы форма не теряла ввод
    $row['deck_id']          = $deck_id;
    $row['title']            = $title;
    $row['sort_order']       = $sort;
    $row['is_active']        = $active;
    $row['front_image_path'] = $front;
    $row['back_image_path']  = $back;
    $row['audio_path']       = $audio;
}

include __DIR__ . '/_layout_top.php';
?>
<h3>Edit Card</h3>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mt-3">
  <div class="mb-3">
    <label class="form-label">Deck</label>
    <select class="form-select" name="deck_id" required>
      <?php foreach ($decks as $d): ?>
        <option value="<?= (int)$d['id'] ?>" <?= ((int)$row['deck_id'] === (int)$d['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars(($d['category_name'] ? $d['category_name'].' / ' : '').$d['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Title</label>
    <input class="form-control" name="title" value="<?= htmlspecialchars($row['title'] ?? '') ?>" required>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Front image (png/jpg/webp)</label>
      <input class="form-control" type="file" name="front_image" accept=".png,.jpg,.jpeg,.webp">
      <?php if (!empty($row['front_image_path'])): ?>
        <div class="mt-2">
          <img class="img-fluid border rounded" style="max-height:120px"
               src="../public/<?= htmlspecialchars($row['front_image_path']) ?>" alt="front preview">
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Back image (png/jpg/webp)</label>
      <input class="form-control" type="file" name="back_image" accept=".png,.jpg,.jpeg,.webp">
      <?php if (!empty($row['back_image_path'])): ?>
        <div class="mt-2">
          <img class="img-fluid border rounded" style="max-height:120px"
               src="../public/<?= htmlspecialchars($row['back_image_path']) ?>" alt="back preview">
        </div>
      <?php endif; ?>
    </div>
    <div class="col-md-4">
      <label class="form-label">Audio (mp3/wav)</label>
      <input class="form-control" type="file" name="audio" accept=".mp3,.wav">
      <?php if (!empty($row['audio_path'])): ?>
        <div class="mt-2">
          <audio controls preload="none" style="width: 100%">
            <source src="../public/<?= htmlspecialchars($row['audio_path']) ?>">
          </audio>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Sort order</label>
      <input class="form-control" type="number" name="sort_order" value="<?= (int)($row['sort_order'] ?? 0) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Active</label><br>
      <input class="form-check-input" type="checkbox" name="is_active" value="1" id="chkActive"
             <?= !empty($row['is_active']) ? 'checked' : '' ?>>
      <label class="form-check-label ms-2" for="chkActive">Yes</label>
    </div>
  </div>

  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline-secondary" href="cards_list.php">Cancel</a>
</form>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
