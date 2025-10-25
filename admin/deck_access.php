<?php
// admin/deck_access.php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/_guard.php';
$pageTitle = 'Deck access';

$pdo = db();
$errors = [];
$messages = [];

/* ===================== helpers ===================== */
function allDirections(PDO $pdo): array {
  return $pdo->query("SELECT id, slug, title FROM directions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}
function allDecksByDirection(PDO $pdo, ?int $directionId): array {
  if ($directionId) {
    $st = $pdo->prepare("
      SELECT d.id, d.name, d.direction_id, dir.slug AS dir_slug, dir.title AS dir_title
      FROM decks d
      JOIN directions dir ON dir.id = d.direction_id
      WHERE d.direction_id = :dir
      ORDER BY d.name
    ");
    $st->execute([':dir' => $directionId]);
  } else {
    $st = $pdo->query("
      SELECT d.id, d.name, d.direction_id, dir.slug AS dir_slug, dir.title AS dir_title
      FROM decks d
      JOIN directions dir ON dir.id = d.direction_id
      ORDER BY dir.id, d.name
    ");
  }
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function usersList(PDO $pdo, string $q = ''): array {
  $sql = "
    SELECT u.id, u.email, u.role, u.created_at,
           u.default_direction_id,
           dir.slug AS dir_slug, dir.title AS dir_title
    FROM users u
    LEFT JOIN directions dir ON dir.id = u.default_direction_id
  ";
  $ord = " ORDER BY u.created_at DESC";
  if ($q !== '') {
    $st = $pdo->prepare($sql." WHERE u.email LIKE :q ".$ord." LIMIT 200");
    $st->execute([':q' => "%$q%"]);
  } else {
    $st = $pdo->query($sql.$ord." LIMIT 100");
  }
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function userAccessMap(PDO $pdo, array $userIds): array {
  if (!$userIds) return [];
  $in = implode(',', array_map('intval', $userIds));
  $sql = "
    SELECT uda.id, uda.user_id,
           d.id AS deck_id, d.name AS deck_name,
           dir.slug AS dir_slug, dir.title AS dir_title,
           uda.granted_at
    FROM user_deck_access uda
    JOIN decks d   ON d.id   = uda.deck_id
    JOIN directions dir ON dir.id = d.direction_id
    WHERE uda.user_id IN ($in)
    ORDER BY dir.id, d.name
  ";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $map = [];
  foreach ($rows as $r) $map[(int)$r['user_id']][] = $r;
  return $map;
}

/* ===================== actions ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';

  // ---------- Grant access ----------
  if ($act === 'grant') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $deckId = (int)($_POST['deck_id'] ?? 0);

    if (!$userId || !$deckId) {
      $errors[] = "Select a user and a deck.";
    } else {
      try {
        $pdo->beginTransaction();

        // юзер
        $u = $pdo->prepare("SELECT id, default_direction_id FROM users WHERE id = :id LIMIT 1");
        $u->execute([':id'=>$userId]);
        $user = $u->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new RuntimeException("User not found.");

        // дека
        $d = $pdo->prepare("
          SELECT d.id, d.name, d.direction_id, dir.slug AS dir_slug
          FROM decks d
          JOIN directions dir ON dir.id = d.direction_id
          WHERE d.id = :id LIMIT 1
        ");
        $d->execute([':id'=>$deckId]);
        $deck = $d->fetch(PDO::FETCH_ASSOC);
        if (!$deck) throw new RuntimeException("Deck not found.");

        $userDir = (int)($user['default_direction_id'] ?? 0);
        $deckDir = (int)$deck['direction_id'];

        // если у юзера нет direction — ставим по первой выданной деке
        if (!$userDir) {
          $upd = $pdo->prepare("UPDATE users SET default_direction_id = :dir WHERE id = :id");
          $upd->execute([':dir'=>$deckDir, ':id'=>$userId]);
          $userDir = $deckDir;
        }

        // запрет чужого направления
        if ($userDir !== $deckDir) {
          throw new RuntimeException("Cannot grant a deck from another direction. The user already has a set direction.");
        }

        // upsert доступа
        $q = $pdo->prepare("SELECT id FROM user_deck_access WHERE user_id=:u AND deck_id=:d LIMIT 1");
        $q->execute([':u'=>$userId, ':d'=>$deckId]);
        if (!$q->fetchColumn()) {
          $ins = $pdo->prepare("INSERT INTO user_deck_access (user_id, deck_id, source) VALUES (:u,:d,'admin')");
          $ins->execute([':u'=>$userId, ':d'=>$deckId]);
        }

        $pdo->commit();
        $messages[] = "Access granted: {$deck['name']} ({$deck['dir_slug']}).";
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = $e->getMessage();
      }
    }

  // ---------- Revoke access ----------
  } elseif ($act === 'revoke') {
    $id = (int)($_POST['id'] ?? 0);
    try {
      if ($id) {
        $stmt = $pdo->prepare("DELETE FROM user_deck_access WHERE id = :id");
        $stmt->execute([':id'=>$id]);
        $messages[] = "Access revoked.";
      }
    } catch (Throwable $e) {
      $errors[] = $e->getMessage();
    }

  // ---------- Set / Force direction ----------
  } elseif ($act === 'set_direction') {
    // инпуты
    $userId = (int)($_POST['user_id'] ?? 0);
    $dirId  = isset($_POST['direction_id']) && $_POST['direction_id'] !== '' ? (int)$_POST['direction_id'] : 0;
    $force  = !empty($_POST['force']); // Force = снести чужие доступы

    if (!$userId || !$dirId) {
      $errors[] = 'Select a user and a direction.';
    } else {
      try {
        $pdo->beginTransaction();

        // валидность направления
        $chk = $pdo->prepare("SELECT id FROM directions WHERE id = :id LIMIT 1");
        $chk->execute([':id'=>$dirId]);
        if (!$chk->fetchColumn()) throw new RuntimeException("Direction not found.");

        // конфликтующие доступы (другого направления)
        $q = $pdo->prepare("
          SELECT COUNT(*)
          FROM user_deck_access uda
          JOIN decks d ON d.id = uda.deck_id
          WHERE uda.user_id = :u AND d.direction_id <> :dir
        ");
        $q->execute([':u'=>$userId, ':dir'=>$dirId]);
        $conflicts = (int)$q->fetchColumn();

        if ($conflicts > 0 && !$force) {
          throw new RuntimeException("Cannot change direction: there are accesses from another direction ({$conflicts}). Revoke them first or use Force.");
        }

        // если форс — удаляем чужие доступы
        if ($conflicts > 0 && $force) {
          $del = $pdo->prepare("
            DELETE uda FROM user_deck_access uda
            JOIN decks d ON d.id = uda.deck_id
            WHERE uda.user_id = :u AND d.direction_id <> :dir
          ");
          $del->execute([':u'=>$userId, ':dir'=>$dirId]);
        }

        // маппинг ID -> язык UI
        $langMap = [ 1 => 'uk', 2 => 'fi', 3 => 'en' ];
        $langFromDir = $langMap[$dirId] ?? 'fi';

        // один апдейт (фикс HY093: разные плейсхолдеры)
        $sql = "
          UPDATE users
          SET
            lang = IF(default_direction_id <> :dir_cond, :lang, lang),
            default_direction_id = :dir_set
          WHERE id = :uid
        ";
        $st = $pdo->prepare($sql);
        $st->execute([
          ':dir_cond' => $dirId,
          ':dir_set'  => $dirId,
          ':lang'     => $langFromDir,
          ':uid'      => $userId,
        ]);

        $pdo->commit();
        $messages[] = $conflicts > 0 && $force
          ? "Direction updated. Removed conflicting accesses: {$conflicts}."
          : "Direction updated.";
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = $e->getMessage();
      }
    }
  }
}

/* ===================== data ===================== */
$q = trim($_GET['q'] ?? '');
$directions   = allDirections($pdo);
$users        = usersList($pdo, $q);
$userIds      = array_map(fn($u)=>(int)$u['id'], $users);
$accessByUser = userAccessMap($pdo, $userIds);

/* ===================== layout ===================== */
include __DIR__ . '/_layout_top.php';
?>
<style>
  body { overflow-x: hidden; }
  .card .table-responsive { overflow-x: auto; }
  .text-break-any { word-break: break-word; overflow-wrap: anywhere; }
  .access-badges { display: flex; flex-wrap: wrap; gap: .5rem; }
  .badge-pill { border-radius: 999px; }
  .badge .btn-close { width:.6rem; height:.6rem; margin-left:.35rem; filter:invert(1); opacity:.8; }
  .badge .btn-close:hover { opacity:1; }
  .w-min-160{ min-width:160px; } .w-max-100{ max-width:100%; }
</style>

<h1 class="mb-3">Deck access</h1>

<form class="row g-2 mb-3" method="get">
  <div class="col-sm-6 col-md-4">
    <input class="form-control" name="q" placeholder="Search by email" value="<?= htmlspecialchars($q) ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-outline-secondary">Search</button>
  </div>
  <?php if ($q !== ''): ?>
    <div class="col-auto">
      <a class="btn btn-link" href="deck_access.php">Reset</a>
    </div>
  <?php endif; ?>
</form>

<?php if ($errors): ?>
  <div class="alert alert-danger py-2"><?= implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
<?php endif; ?>
<?php if ($messages): ?>
  <div class="alert alert-success py-2"><?= implode('<br>', array_map('htmlspecialchars', $messages)); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <?php if (!$users): ?>
      <div class="text-muted">No users.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <colgroup>
            <col style="width:64px">
            <col>
            <col style="width:90px">
            <col style="width:160px">
            <col style="width:200px">
            <col style="width:220px">
          </colgroup>
          <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Role</th>
            <th>Direction</th>
            <th>Access</th>
            <th>Grant access</th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($users as $u):
            $uid = (int)$u['id'];
            $userAcc   = $accessByUser[$uid] ?? [];
            $userDirId = $u['default_direction_id'] ? (int)$u['default_direction_id'] : null;
            $userDirLabel = $u['dir_slug'] ? ($u['dir_title'].' ('.$u['dir_slug'].')') : 'not set';
            $decksForSelect = allDecksByDirection($pdo, $userDirId);
          ?>
            <tr>
              <td class="text-muted"><?= $uid ?></td>
              <td class="text-break-any"><strong><?= htmlspecialchars($u['email']) ?></strong></td>
              <td><span class="badge bg-light text-dark"><?= htmlspecialchars($u['role'] ?: 'user') ?></span></td>

              <td>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <?php if ($userDirId): ?>
                    <span class="badge bg-info-subtle text-dark border">
                      <?= htmlspecialchars($userDirLabel) ?>
                    </span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-dark border">not set</span>
                  <?php endif; ?>

                  <form method="post" class="d-flex gap-2 flex-wrap">
                    <input type="hidden" name="action" value="set_direction">
                    <input type="hidden" name="user_id" value="<?= $uid ?>">

                    <select name="direction_id"
                            class="form-select form-select-sm w-min-160 w-max-100"
                            style="max-width:180px" required>
                      <option value="">— choose —</option>
                      <?php foreach ($directions as $dir): ?>
                        <option value="<?= (int)$dir['id'] ?>" <?= $userDirId===(int)$dir['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($dir['slug']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>

                    <button class="btn btn-sm btn-outline-primary">Set</button>

                    <button class="btn btn-sm btn-outline-danger"
                            name="force" value="1"
                            onclick="return confirm('Force switch? All accesses from another direction will be removed. Continue?')">
                      Force
                    </button>
                  </form>
                </div>
              </td>

              <td>
                <?php if (!$userAcc): ?>
                  <span class="text-muted">none</span>
                <?php else: ?>
                  <div class="access-badges">
                    <?php foreach ($userAcc as $acc): ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="id" value="<?= (int)$acc['id'] ?>">
                        <span class="badge bg-primary badge-pill d-inline-flex align-items-center">
                          <?= htmlspecialchars($acc['deck_name']) ?>
                          <small class="opacity-75 ms-1">· <?= htmlspecialchars($acc['dir_slug']) ?></small>
                          <button type="submit" class="btn-close" title="Revoke"
                                  onclick="return confirm('Revoke access?')"></button>
                        </span>
                      </form>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>

              <td>
                <form method="post" class="d-flex gap-2 flex-wrap">
                  <input type="hidden" name="action" value="grant">
                  <input type="hidden" name="user_id" value="<?= $uid ?>">
                  <select name="deck_id"
                          class="form-select form-select-sm w-min-160 w-max-100"
                          style="max-width:180px" required>
                    <option value="">— select —</option>
                    <?php foreach ($decksForSelect as $d): ?>
                      <option value="<?= (int)$d['id'] ?>">
                        <?= htmlspecialchars($d['name']) ?><?= $userDirId ? '' : ' — '.htmlspecialchars($d['dir_slug']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-primary">Grant</button>
                </form>
                <?php if (!$userDirId): ?>
                  <div class="form-text">⚠ Direction = first granted deck.</div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="small text-muted mt-2">Shown: <?= count($users) ?> users.</div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
