<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Edit Deck';
$id = (int)($_GET['id'] ?? 0);

/* Категории с направлением */
$cats = db()->query("
  SELECT c.id, c.name, c.direction_id, d.title AS dir_title
  FROM categories c
  JOIN directions d ON d.id = c.direction_id
  ORDER BY c.sort_order, c.id
")->fetchAll(PDO::FETCH_ASSOC);

/* Направления (для редиректа/валидации и fallback) */
$dirs = db()->query("SELECT id, slug, title FROM directions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$dirById = [];
foreach ($dirs as $d) $dirById[(int)$d['id']] = $d;

/* Текущая колода */
$stmt = db()->prepare("SELECT * FROM decks WHERE id=:id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { header("Location: decks_list.php"); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid  = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0);
    $direction_id = (int)($_POST['direction_id'] ?? 0); // может прийти только когда нет категорий

    if ($name === '') $errors[] = 'Name is required';

    if ($cid > 0) {
        // Наследуем направление от выбранной категории
        $st = db()->prepare("SELECT direction_id FROM categories WHERE id = :id");
        $st->execute([':id' => $cid]);
        $catDir = $st->fetchColumn();
        if (!$catDir) {
            $errors[] = 'Invalid category selected';
        } else {
            $direction_id = (int)$catDir;
        }
    } else {
        // Без категории — направление обязательно
        if ($direction_id <= 0) $errors[] = 'Direction is required when no category is selected';
        // Проверим, что такое направление существует
        if ($direction_id > 0 && !isset($dirById[$direction_id])) {
            $errors[] = 'Selected direction does not exist';
        }
    }

    // ================== РАБОТА С ОБЛОЖКОЙ ==================
    $newCoverPath = null;                 // если загрузили новую — сюда положим веб-путь
    $removeCover  = isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1';
    $maxBytes = 5 * 1024 * 1024;          // 5 МБ

    // Валидация файла, если выбран
    if (!empty($_FILES['cover']['tmp_name'])) {
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
            }
        }
    }
    // =======================================================

    if (!$errors) {
        // Если загружен новый файл — переносим в /uploads/decks
        if (!empty($_FILES['cover']['tmp_name'])) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['cover']['tmp_name']);
            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];
            $ext = $extMap[$mime] ?? null;

            if ($ext) {
                $uploadsWeb = '/uploads/decks'; // веб-путь
                $uploadsFs  = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $uploadsWeb; // физический путь
                if (!is_dir($uploadsFs)) { @mkdir($uploadsFs, 0775, true); }

                $fname  = bin2hex(random_bytes(8)) . '.' . $ext;
                $destFs = $uploadsFs . '/' . $fname;

                if (@move_uploaded_file($_FILES['cover']['tmp_name'], $destFs)) {
                    $newCoverPath = $uploadsWeb . '/' . $fname;

                    // Удалим старую, если была и лежит у нас в uploads
                    if (!empty($row['cover']) && str_starts_with($row['cover'], $uploadsWeb)) {
                        $oldFs = $uploadsFs . '/' . basename($row['cover']);
                        @unlink($oldFs);
                    }
                }
            }
        } elseif ($removeCover) {
            // Отметили галку удалить текущую обложку
            $uploadsWeb = '/uploads/decks';
            $uploadsFs  = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $uploadsWeb;
            if (!empty($row['cover']) && str_starts_with($row['cover'], $uploadsWeb)) {
                @unlink($uploadsFs . '/' . basename($row['cover']));
            }
            $newCoverPath = null; // в БД поставим NULL
        }

        // Итоговое значение cover для апдейта:
        //  - если загрузили новую — пишем новую
        //  - если отметили удаление — пишем NULL
        //  - иначе оставляем старую
        $coverToSave = $newCoverPath !== null ? $newCoverPath : ($removeCover ? null : ($row['cover'] ?? null));

        $upd = db()->prepare("
            UPDATE decks 
            SET category_id = :cid, name = :n, description = :d, sort_order = :s, direction_id = :dir, cover = :cover
            WHERE id = :id
        ");
        $upd->execute([
            ':cid'   => $cid ?: null,
            ':n'     => $name,
            ':d'     => $desc,
            ':s'     => $sort,
            ':dir'   => $direction_id,
            ':cover' => $coverToSave,
            ':id'    => $id
        ]);

        $back = 'decks_list.php' . (isset($dirById[$direction_id]) ? ('?dir=' . urlencode($dirById[$direction_id]['slug'])) : '');
        header("Location: $back");
        exit;
    }

    // если ошибка — подменим $row значениями из формы для sticky
    $row['category_id']  = $cid ?: null;
    $row['name']         = $name;
    $row['description']  = $desc;
    $row['sort_order']   = $sort;
    $row['direction_id'] = $direction_id ?: ($row['direction_id'] ?? null);
    if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
        $row['cover'] = null;
    }
}

include __DIR__ . '/_layout_top.php';
?>
<h3>Edit Deck</h3>
<?php if ($errors): ?>
  <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars',$errors)) ?></div>
<?php endif; ?>

<!-- Важно: enctype для загрузки -->
<form method="post" class="mt-3" autocomplete="off" enctype="multipart/form-data" novalidate>
  <div class="mb-3">
    <label class="form-label">Category</label>
    <select class="form-select" name="category_id">
      <option value="0" <?= empty($row['category_id']) ? 'selected' : '' ?>>— no category —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (int)$row['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['dir_title']) ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">If a category is selected, direction will be inherited automatically.</div>
  </div>

  <?php if (empty($cats)): ?>
    <!-- как и в "предыдущем варианте": показываем выбор направления, только если нет категорий -->
    <div class="mb-3">
      <label class="form-label">Direction <span class="text-danger">*</span></label>
      <select class="form-select" name="direction_id" required>
        <option value="">— Select direction —</option>
        <?php foreach ($dirs as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= ((int)($row['direction_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php else: ?>
    <!-- направление хранится скрыто, меняется автоматически при выборе категории -->
    <input type="hidden" name="direction_id" value="<?= (int)($row['direction_id'] ?? 0) ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Name</label>
    <input class="form-control" name="name" required value="<?= htmlspecialchars($row['name']) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($row['description'] ?? '') ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label">Sort order</label>
    <input class="form-control" name="sort_order" type="number" value="<?= (int)($row['sort_order'] ?? 0) ?>">
  </div>

  <!-- Текущая обложка + удаление -->
  <div class="mb-3">
    <label class="form-label">Current cover</label><br>
    <?php if (!empty($row['cover'])): ?>
      <img src="<?= htmlspecialchars($row['cover']) ?>" alt="cover" style="max-height:120px;border-radius:8px;">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="removeCover">
        <label class="form-check-label" for="removeCover">Remove cover</label>
      </div>
    <?php else: ?>
      <div class="text-muted">No cover set.</div>
    <?php endif; ?>
  </div>

  <!-- Загрузка новой обложки -->
  <div class="mb-3">
    <label class="form-label">Replace / upload new cover</label>
    <input class="form-control" type="file" name="cover" id="coverInput" accept="image/*">
    <div class="form-text">JPG/PNG/WEBP/GIF, up to 5 MB. Uploading a new file will replace the current cover.</div>
    <img id="coverPreview" alt="preview" style="display:none;max-height:120px;margin-top:8px;border-radius:8px;">
  </div>

  <button class="btn btn-primary">Save</button>
  <a class="btn btn-outline-secondary" href="decks_list.php<?= isset($dirById[(int)($row['direction_id'] ?? 0)]) ? ('?dir=' . urlencode($dirById[(int)$row['direction_id']]['slug'])) : '' ?>">Cancel</a>
</form>

<script>
  // Превью новой обложки
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
