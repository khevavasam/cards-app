<?php
// cart.php
session_start();
require_once __DIR__ . '/i18n.php';
require __DIR__ . '/data/products.php';

/* Локализация title/desc для списка «додати ще товари» */
if (!function_exists('i18n_products')) {
  function i18n_products(array $products): array {
    foreach ($products as &$p) {
      $id = $p['id'] ?? null;
      if ($id) {
        $t = __t("products.$id.title");
        $d = __t("products.$id.desc");
        if ($t && $t !== "products.$id.title") $p['title'] = $t;
        if ($d && $d !== "products.$id.desc")  $p['desc']  = $d;
      }
    }
    return $products;
  }
}
$PRODUCTS_L10N = i18n_products($PRODUCTS);

/* map id -> item */
$byId = [];
foreach ($PRODUCTS as $p) { $byId[$p['id']] = $p; }

/* add / remove (GET) */
if (isset($_GET['add']) && isset($byId[$_GET['add']])) {
  $_SESSION['cart'][$_GET['add']] = ($_SESSION['cart'][$_GET['add']] ?? 0) + 1;
  header('Location: ' . i18n_link('cart.php?added=1')); exit;
}
if (isset($_GET['remove'])) {
  unset($_SESSION['cart'][$_GET['remove']]);
  header('Location: ' . i18n_link('cart.php')); exit;
}

/* update qty (POST, авто) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
  foreach ($_POST['qty'] as $id => $q) {
    if (!isset($byId[$id])) continue;
    $q = max(1, (int)$q);
    $_SESSION['cart'][$id] = $q;
  }
  header('Location: ' . i18n_link('cart.php')); exit;
}

/* calc */
$items = []; $subtotal = 0;
foreach (($_SESSION['cart'] ?? []) as $id => $qty) {
  if (!isset($byId[$id])) continue;
  $p = $byId[$id];
  $qty = max(1, (int)$qty);
  $price = (float)($p['price'] ?? 0);
  $total = $price * $qty;
  $subtotal += $total;
  $items[] = ['p' => $p, 'qty' => $qty, 'total' => $total];
}
$promo    = strtoupper($_SESSION['promo'] ?? '');
$discount = ($promo === 'SAVE10') ? round($subtotal * 0.10) : 0;
/* доставка: 6€ если итог <100 и >0, иначе 0€ */
$shipping = ($subtotal - $discount) >= 100 || ($subtotal - $discount) <= 0 ? 0 : 6;
$total    = max($subtotal - $discount, 0) + $shipping;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?= htmlspecialchars(i18n_lang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php _e('cart.meta_title'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
  /* фирменный цвет */
  :root{ --bs-primary:#0ea5e9; }

  /* только брендовые кнопки */
  .btn.btn-primary{
    background:#0ea5e9!important; border-color:#0ea5e9!important;
  }
  .btn.btn-primary:hover,
  .btn.btn-primary:focus{
    background:#0a8dc4!important; border-color:#0a8dc4!important;
  }

  .btn.btn-outline-primary{
    color:#0ea5e9!important; border-color:#0ea5e9!important; background:transparent!important;
  }
  .btn.btn-outline-primary:hover,
  .btn.btn-outline-primary:focus{
    color:#fff!important; background:#0ea5e9!important; border-color:#0ea5e9!important;
  }

  /* чтобы +/− и корзина оставались контурными и иконки не терялись */
  .btn.btn-outline-secondary:hover,
  .btn.btn-outline-secondary:focus{
    color:var(--bs-secondary)!important;
    background:transparent!important;
    border-color:var(--bs-secondary)!important;
  }
  .btn.btn-outline-danger:hover,
  .btn.btn-outline-danger:focus{
    color:var(--bs-danger)!important;
    background:transparent!important;
    border-color:var(--bs-danger)!important;
  }
</style>

</head>
<body>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?php _e('cart.title'); ?></h1>
    <a href="<?= i18n_link('index.php#sets') ?>" class="btn btn-outline-primary">
      <i class="bi bi-arrow-left me-2"></i><?php _e('cart.back_to_sets'); ?>
    </a>
  </div>

  <div class="row g-4">
    <!-- LEFT: items -->
    <div class="col-lg-8">
      <form id="cartForm" method="post" action="<?= i18n_link('cart.php') ?>" class="card shadow-sm">
        <div class="card-body">
          <?php if(!$items): ?>
            <div class="text-center py-5">
              <i class="bi bi-bag fs-1 text-secondary d-block mb-3"></i>
              <p class="mb-3"><?php _e('cart.empty'); ?></p>
              <a class="btn btn-primary rounded-pill px-4" href="<?= i18n_link('index.php#sets') ?>"><?php _e('cart.to_shop'); ?></a>
            </div>
          <?php else: ?>
            <?php foreach($items as $i): $p=$i['p']; ?>
              <div class="row g-3 align-items-center py-2 cart-item">
                <div class="col-4 col-md-3">
                  <div class="ratio ratio-4x3">
                    <img src="<?= h($p['img']) ?>" alt="<?= h($p['title']) ?>" class="img-fluid rounded object-fit-cover">
                  </div>
                </div>
                <div class="col-8 col-md-5">
                  <h5 class="mb-1"><?= h($p['title']) ?></h5>
                  <div class="text-secondary small">
                    <?= $p['cards'] ? __t('cart.item.cards_count', ['n'=>(int)$p['cards']]) : '' ?>
                  </div>
                </div>
                <div class="col-7 col-md-2">
                  <div class="input-group input-group-sm">
                    <button class="btn btn-outline-secondary btn-qty" type="button" data-target="<?= h($p['id']) ?>" data-step="-1"><i class="bi bi-dash"></i></button>
                    <input class="form-control text-center qty-input" name="qty[<?= h($p['id']) ?>]" id="qty-<?= h($p['id']) ?>" value="<?= (int)$i['qty'] ?>" inputmode="numeric" pattern="[0-9]*" aria-label="<?php _e('cart.item.qty_aria'); ?>">
                    <button class="btn btn-outline-secondary btn-qty" type="button" data-target="<?= h($p['id']) ?>" data-step="1"><i class="bi bi-plus"></i></button>
                  </div>
                </div>
                <div class="col-5 col-md-2 text-end">
                  <div class="fw-bold"><?= (int)$i['total'] ?>€</div>
                  <a class="btn btn-sm btn-outline-danger mt-2" href="<?= i18n_link('cart.php?remove='.h($p['id'])) ?>" title="<?php _e('cart.item.remove'); ?>">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </div>
              <hr class="my-3">
            <?php endforeach; ?>

            <!-- Промокод -->
            <div class="input-group" style="max-width:360px;">
              <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
              <input type="text" class="form-control" name="promo" value="<?= h($_SESSION['promo'] ?? '') ?>" placeholder="<?php _e('cart.promo.placeholder'); ?>">
              <button class="btn btn-outline-secondary" formaction="promo.php" formmethod="post" disabled><?php _e('cart.promo.apply'); ?></button>
            </div>
          <?php endif; ?>
        </div>
      </form>

      <!-- СЕКЦІЯ: додати ще товари -->
      <div class="card shadow-sm mt-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><?php _e('cart.more.heading'); ?></h5>
            <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#moreItems"><?php _e('cart.more.toggle'); ?></a>
          </div>
          <div class="collapse show" id="moreItems">
            <div class="row g-3">
              <?php foreach($PRODUCTS_L10N as $pp): ?>
                <div class="col-12 col-md-6">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="d-flex gap-3">
                        <div class="ratio ratio-4x3" style="width:110px;">
                          <img src="<?= h($pp['img']) ?>" alt="<?= h($pp['title']) ?>" class="img-fluid rounded object-fit-cover">
                        </div>
                        <div class="flex-grow-1">
                          <div class="fw-semibold"><?= h($pp['title']) ?></div>
                          <div class="text-secondary small mb-2">
                            <?= $pp['cards'] ? __t('cart.item.cards_count', ['n'=>(int)$pp['cards']]) : '' ?>
                          </div>
                          <?php if(isset($pp['price'])): ?>
                            <div class="mb-2"><span class="fw-bold"><?= (int)$pp['price'] ?>€</span></div>
                          <?php endif; ?>
                          <a href="<?= i18n_link('cart.php?add='.h($pp['id'])) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-bag-plus me-1"></i> <?php _e('cart.more.add_btn'); ?>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- RIGHT: summary -->
    <div class="col-lg-4">
      <div class="position-sticky" style="top:24px;">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-3"><?php _e('cart.summary.title'); ?></h5>
            <div class="d-flex justify-content-between mb-2"><span><?php _e('cart.summary.subtotal'); ?></span><span><?= (int)$subtotal ?>€</span></div>
            <div class="d-flex justify-content-between mb-2"><span><?php _e('cart.summary.discount'); ?><?= $promo ? ' ('.h($promo).')' : '' ?></span><span class="text-success">−<?= (int)$discount ?>€</span></div>
            <div class="d-flex justify-content-between mb-2"><span><?php _e('cart.summary.shipping'); ?></span><span><?= (int)$shipping ?>€</span></div>
            <hr>
            <div class="d-flex justify-content-between fs-5 mb-3"><strong><?php _e('cart.summary.total'); ?></strong><strong><?= (int)$total ?>€</strong></div>
            <?php if($items): ?>
              <a href="<?= i18n_link('index.php#order') ?>" class="btn btn-primary w-100">
                <i class="bi bi-lock-fill me-2"></i><?php _e('cart.summary.checkout_btn'); ?>
              </a>
            <?php else: ?>
              <a href="<?= i18n_link('index.php#sets') ?>" class="btn btn-outline-primary w-100">
                <?php _e('cart.to_shop'); ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Toast "додано в кошик" -->
<?php if(isset($_GET['added'])): ?>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
  <div class="toast align-items-center text-bg-success border-0 show">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-check2-circle me-2"></i><?php _e('cart.toast.added'); ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Авто-оновлення кількості: +/- та ручний ввід → миттєвий POST
  const form = document.getElementById('cartForm');

  // кнопки +/- 
  document.addEventListener('click', (e)=> {
    const btn = e.target.closest('.btn-qty');
    if (!btn) return;
    const id = btn.dataset.target;
    const step = parseInt(btn.dataset.step || 0, 10);
    const input = document.getElementById('qty-' + id);
    let val = parseInt(input.value || '1', 10) + step;
    if (isNaN(val) || val < 1) val = 1;
    input.value = val;
    form.submit(); // POST і перезавантаження
  });

  // ручний ввід
  document.addEventListener('input', (e)=> {
    if (!e.target.classList.contains('qty-input')) return;
    e.target.value = e.target.value.replace(/[^\d]/g, '');
  });
  document.addEventListener('change', (e)=> {
    if (!e.target.classList.contains('qty-input')) return;
    if (e.target.value === '' || parseInt(e.target.value, 10) < 1) e.target.value = 1;
    form.submit();
  });
</script>
</body>
</html>
