<?php require_once __DIR__ . '/_init.php'; ?>

<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Add Card';

// decks for select (show with category)
$decks = db()->query("SELECT d.id, d.name, c.name AS category_name
                      FROM decks d LEFT JOIN categories c ON c.id=d.category_id
                      ORDER BY c.sort_order, d.sort_order, d.id")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $deck_id = (int)($_POST['deck_id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $sort    = (int)($_POST['sort_order'] ?? 0);
    $active  = isset($_POST['is_active']) ? 1 : 0;

    if ($deck_id<=0) $errors[]='Deck is required';
    if ($title==='') $errors[]='Title is required';

    // uploads
    $front = upload_file('front_image','image'); // returns relative path like uploads/images/...
    $back  = upload_file('back_image','image');
    $audio = upload_file('audio','audio');

    if (!$errors) {
        $stmt = db()->prepare("INSERT INTO cards (deck_id,title,front_image_path,back_image_path,audio_path,sort_order,is_active,created_at)
                               VALUES (:did,:t,:f,:b,:a,:s,:act,NOW())");
        $stmt->execute([
           ':did'=>$deck_id, ':t'=>$title, ':f'=>$front, ':b'=>$back, ':a'=>$audio,
           ':s'=>$sort, ':act'=>$active
        ]);
        header("Location: cards_list.php");
        exit;
    }
}

include __DIR__ . '/_layout_top.php';
?>
<h3>Add Card</h3>
<?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars',$errors)) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="mt-3">
  <div class="mb-3">
    <label class="form-label">Deck</label>
    <select class="form-select" name="deck_id" required>
      <option value="">— select —</option>
      <?php foreach ($decks as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars(($d['category_name']? $d['category_name'].' / ' : '').$d['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Title</label>
    <input class="form-control" name="title" required>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Front image (png/jpg/webp)</label>
      <input class="form-control" type="file" name="front_image" accept=".png,.jpg,.jpeg,.webp">
    </div>
    <div class="col-md-4">
      <label class="form-label">Back image (png/jpg/webp)</label>
      <input class="form-control" type="file" name="back_image" accept=".png,.jpg,.jpeg,.webp">
    </div>
    <div class="col-md-4">
      <label class="form-label">Audio (mp3/wav)</label>
      <input class="form-control" type="file" name="audio" accept=".mp3,.wav">
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Sort order</label>
      <input class="form-control" type="number" name="sort_order" value="0">
    </div>
    <div class="col-md-4">
      <label class="form-label">Active</label><br>
      <input class="form-check-input" type="checkbox" name="is_active" value="1" id="chkActive">
      <label class="form-check-label ms-2" for="chkActive">Yes</label>
    </div>
  </div>

  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline-secondary" href="cards_list.php">Cancel</a>
</form>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
