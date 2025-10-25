<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Add Deck';

/* Загружаем категории (с направлением) */
$cats = db()->query("
    SELECT c.id, c.name, c.direction_id, d.title AS dir_title
    FROM categories c
    JOIN directions d ON d.id = c.direction_id
    ORDER BY c.sort_order, c.id
")->fetchAll(PDO::FETCH_ASSOC);

/* Загружаем направления (на случай если нет категории) */
$dirs = db()->query("SELECT id, slug, title FROM directions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$coverCandidate = null;   // сюда положим инфу о загруженном файле (если валидный)
$coverUrl = null;         // итоговый веб-путь к обложке (сохраняем в БД)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid  = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $direction_id = (int)($_POST['direction_id'] ?? 0);

    if ($name === '') $errors[] = 'Name is required';

    if ($cid > 0) {
        // проверяем, что категория существует и берём её направление
        $stmt = db()->prepare("SELECT direction_id FROM categories WHERE id = :id");
        $stmt->execute([':id' => $cid]);
        $catDir = $stmt->fetchColumn();
        if (!$catDir) {
            $errors[] = 'Invalid category selected';
        } else {
            $direction_id = (int)$catDir;
        }
    } else {
        if ($direction_id <= 0) {
            $errors[] = 'Direction is required when no category selected';
        }
    }

    // ====== ВАЛИДАЦИЯ и ПОДГОТОВКА КАРТИНКИ (если её прислали) ======
    if (!empty($_FILES['cover']['tmp_name'])) {
        $maxBytes = 5 * 1024 * 1024; // 5 МБ
        if ((int)($_FILES['cover']['size'] ?? 0) > $maxBytes) {
            $errors[] = 'Cover image is too large (max 5 MB)';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['cover']['tmp_name']);
            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];
            if (!isset($extMap[$mime])) {
                $errors[] = 'Unsupported image format (use JPG/PNG/WEBP/GIF)';
            } else {
                $coverCandidate = [
                    'tmp' => $_FILES['cover']['tmp_name'],
                    'ext' => $extMap[$mime],
                ];
            }
        }
    }
    // =================================================================

    if (!$errors) {
        // Если есть валидный файл — переносим его в /uploads/decks
        if ($coverCandidate) {
            $uploadsWeb = '/uploads/decks'; // веб-путь (должен быть доступен из браузера)
            $uploadsFs  = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $uploadsWeb; // физ. путь

            if (!is_dir($uploadsFs)) {
                @mkdir($uploadsFs, 0775, true);
            }

            $fname = bin2hex(random_bytes(8)) . '.' . $coverCandidate['ext'];
            $destFs = $uploadsFs . '/' . $fname;

            if (@move_uploaded_file($coverCandidate['tmp'], $destFs)) {
                $coverUrl = $uploadsWeb . '/' . $fname;
            } else {
                // не фатальная — просто создадим деку без обложки
                $coverUrl = null;
            }
        }

        $stmt = db()->prepare("
            INSERT INTO decks (category_id, name, description, sort_order, direction_id, cover, created_at) 
            VALUES (:cid, :n, :d, :s, :dir, :cover, NOW())
        ");
        $stmt->execute([
            ':cid'   => $cid ?: null,
            ':n'     => $name,
            ':d'     => $desc,
            ':s'     => $sort,
            ':dir'   => $direction_id,
            ':cover' => $coverUrl
        ]);

        $slugById = array_column($dirs, 'slug', 'id');
        $redir = isset($slugById[$direction_id]) ? ("?dir=" . urlencode($slugById[$direction_id])) : '';
        header("Location: decks_list.php" . $redir);
        exit;
    }
}

include __DIR__ . '/_layout_top.php';
?>
<h3>Add Deck</h3>
<?php if ($errors): ?>
  <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<!-- ОБЯЗАТЕЛЬНО multipart/form-data для загрузки файла -->
<form method="post" class="mt-3" autocomplete="off" enctype="multipart/form-data" novalidate>
  <div class="mb-3">
    <label class="form-label">Category</label>
    <select class="form-select" name="category_id">
      <option value="0" <?= isset($_POST['category_id']) && $_POST['category_id']==0 ? 'selected' : '' ?>>— no category —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id']==$c['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['dir_title']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">If you select a category, direction will be inherited automatically.</div>
  </div>

  <?php if (empty($cats)): ?>
    <!-- fallback: выбор направления если категорий нет -->
    <div class="mb-3">
      <label class="form-label">Direction <span class="text-danger">*</span></label>
      <select class="form-select" name="direction_id" required>
        <option value="">— Select direction —</option>
        <?php foreach ($dirs as $d): ?>
          <option value="<?= $d['id'] ?>" <?= (isset($_POST['direction_id']) && $_POST['direction_id']==$d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php else: ?>
    <!-- если категорий много, direction скрыто — проставится через выбранную категорию -->
    <input type="hidden" name="direction_id" value="<?= htmlspecialchars($_POST['direction_id'] ?? '') ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Sort order</label>
    <input class="form-control" name="sort_order" type="number" value="<?= htmlspecialchars($_POST['sort_order'] ?? '0') ?>">
  </div>

  <!-- Новое поле: обложка -->
  <div class="mb-3">
    <label class="form-label">Cover image</label>
    <input class="form-control" type="file" name="cover" id="coverInput" accept="image/*">
    <div class="form-text">JPG/PNG/WEBP/GIF, до 5 МБ.</div>
    <img id="coverPreview" alt="preview" style="display:none;max-height:120px;margin-top:8px;border-radius:8px;">
  </div>

  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline-secondary" href="decks_list.php">Cancel</a>
</form>

<script>
  // Простое превью выбранного файла
  (function () {
    const input = document.getElementById('coverInput');
    const img   = document.getElementById('coverPreview');
    if (!input || !img) return;
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (file) {
        img.src = URL.createObjectURL(file);
        img.style.display = 'block';
      } else {
        img.removeAttribute('src');
        img.style.display = 'none';
      }
    });
  })();
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
