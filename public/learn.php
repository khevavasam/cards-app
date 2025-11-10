<?php
// learn.php — просмотр карт в колоде (с учётом доступа)
// Показываем все карты, если есть доступ; иначе только первые N (preview_count).

require_once __DIR__ . '/../app/db.php';

session_name('CARDSAPP');
ini_set('session.cookie_path', '/cards-app');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

/* 1) Авторизация и deck_id */
if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}
$userId = (int)$_SESSION['user_id'];

$deckId = isset($_GET['deck']) ? (int)$_GET['deck'] : 0;
if ($deckId <= 0) { http_response_code(400); echo "Invalid deck parameter."; exit; }

/* Подключаем i18n и сразу бутстрапим язык (с учётом направления) */
require_once __DIR__ . '/../app/i18n.php';
i18n_bootstrap($pdo, $userId);

/* 2) Инфо о деке (+ preview_count) */
$deckStmt = $pdo->prepare("
  SELECT d.id, d.name AS deck_name, d.preview_count,
         c.id AS cat_id, c.name AS cat_name
  FROM decks d
  JOIN categories c ON c.id = d.category_id
  WHERE d.id = :deck
  LIMIT 1
");
$deckStmt->execute([':deck' => $deckId]);
$deck = $deckStmt->fetch(PDO::FETCH_ASSOC);
if (!$deck) { http_response_code(404); echo "Deck not found."; exit; }

$previewCount = (int)($deck['preview_count'] ?? 5);

/* 3) Проверка доступа пользователя к деке */
$accStmt = $pdo->prepare("
  SELECT 1
  FROM user_deck_access
  WHERE user_id = :u AND deck_id = :d
    AND (expires_at IS NULL OR expires_at > NOW())
  LIMIT 1
");
$accStmt->execute([':u'=>$userId, ':d'=>$deckId]);
$hasAccess = (bool)$accStmt->fetchColumn();

/* 4) Карточки: все или только первые N по sort_order */
$sql = "
  SELECT id, deck_id, title, front_image_path, back_image_path, audio_path, sort_order, is_active
  FROM cards
  WHERE deck_id = :deck AND is_active = 1
  ORDER BY sort_order, id
";
if (!$hasAccess) {
  $limit = max(1, $previewCount);
  $sql .= " LIMIT {$limit}";
}
$cardsStmt = $pdo->prepare($sql);
$cardsStmt->execute([':deck' => $deckId]);
$cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);

/* 5) Хелпер путей */
function publicPath(?string $p): ?string {
  if (!$p) return null;
  $p = ltrim($p, '/');
  return '/cards-app/public/' . $p;
}

/* 6) Данные для JS */
$cardsForJs = array_map(function($row){
  return [
    'id'    => (int)$row['id'],
    'title' => $row['title'],
    'front' => publicPath($row['front_image_path']),
    'back'  => publicPath($row['back_image_path']),
    'audio' => $row['audio_path'] ? publicPath($row['audio_path']) : null,
  ];
}, $cards);

$total     = count($cardsForJs);
$isPreview = !$hasAccess;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <title>
    <?= $isPreview ? 'Preview — ' : '' ?>
    <?= htmlspecialchars($deck['cat_name'] . ' / ' . $deck['deck_name']) ?>
  </title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="icon" type="image/png" href="/cards-app/public/images/golova.png">

<style>
  .flipper{ position:absolute; inset:0; width:100%; height:100%; transform-style:preserve-3d; transition:transform .6s; cursor:pointer; }
  .flipper.flip{ transform: rotateY(180deg); }
  .front,.back{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; backface-visibility:hidden; width:100%; height:100%; }
  .back{ transform: rotateY(180deg); }

  .round-btn{ touch-action:manipulation; -webkit-tap-highlight-color:transparent; user-select:none; cursor:pointer; }
  .round-btn:focus{ outline:0; box-shadow:none; }
  .round-btn.is-pressed{ transform:scale(0.96); }
  .round-btn[disabled]{ pointer-events:none; opacity:.65; }

  #learnFooter{ box-shadow: 0 -6px 18px rgba(2,132,199,.08); }
  /* ЛАПТОПЫ: уменьшаем нижний блок управления в learn */
  @media (min-width: 992px) and (max-width: 1440px){
    /* меньше внутренние отступы и зазор между кнопками */
    #learnFooter .d-flex{ padding-top:.35rem !important; padding-bottom:.35rem !important; gap:.75rem !important; }

    /* меньше круглые кнопки (перебиваем inline-стили) */
    #learnFooter #btnPrev,
    #learnFooter #btnNext{ width:50px !important; height:50px !important; }

    #learnFooter #btnAudio{ width:58px !important; height:58px !important; }

    /* компактнее счётчик под кнопками */
    #learnFooter .text-secondary.small{ font-size:.85rem; }
    /* чуть свободнее снизу у алерта, чтобы он не прилипал к футеру */
    .preview-alert { scroll-margin-bottom: 16px; }

  }

</style>
</head>
<body class="bg-white">

  <nav class="navbar bg-transparent">
    <div class="container">
      <a class="navbar-brand text-secondary" href="/cards-app/public/index.php">
        ← <?= t('common.decks') ?>
      </a>

      <span class="navbar-text text-secondary">
        <?= htmlspecialchars($deck['cat_name']) ?> /
        <strong><?= htmlspecialchars($deck['deck_name']) ?></strong>
        <?php if ($isPreview): ?>
          <span class="badge bg-warning text-dark ms-2">
            <i class="bi bi-lock-fill me-1"></i>
            <?= t('learn.badge.preview') ?>
          </span>
        <?php endif; ?>
      </span>
    </div>
  </nav>

  <!-- main без нижнего padding, футер липкий и не перекрывает контент -->
  <main class="container py-2">

    <?php if ($total === 0): ?>
      <div class="alert alert-warning mt-4">
        <?= t('common.no_cards') ?>
      </div>
    <?php else: ?>

      <!-- скрываем горизонтальный скролл, но даём «карман» под эффект карт -->
      <div class="px-2 px-lg-3 overflow-x-hidden">
        <!-- компенсируем паддинги отрицательными маргинами, чтобы сетка не «поехала» -->
        <div class="row justify-content-center mx-n2 mx-lg-n3">
          <div class="col-10 col-sm-12 col-md-10 col-lg-8 col-xl-7">


            <!-- Прогресс -->
            <div class="progress mb-3" role="progressbar" aria-label="Progress" aria-valuemin="0" aria-valuemax="100">
              <div
                id="overallProgressBar"
                class="progress-bar progress-bar-striped progress-bar-animated"
                style="width:0%;background-color:#0ea5e9;transition:width .6s cubic-bezier(.22,.61,.36,1)"
                aria-valuenow="0">
              </div>
            </div>

            <!-- Центр: слайдер -->
            <div class="flex-grow-1 d-flex min-h-0">
              <div class="swiper w-100" id="swiperCards">
                <div class="swiper-wrapper"></div>
              </div>
            </div>

            <!-- Заголовок карточки -->
            <div class="text-center text-muted small mb-3 px-3" id="cardTitle"></div>

            <!-- Сообщение о превью под карточками -->
            <?php if ($isPreview): ?>
              <div class="preview-alert px-2 mb-3">
                <div class="alert alert-warning py-2 mb-0">
                  <i class="bi bi-eye me-1"></i>
                  <?= t('learn.preview.msg', ['n' => (int)$previewCount]) ?>
                </div>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>

    <?php endif; ?>
  </main>

  <!-- full-width sticky footer (во всю ширину экрана, контент по центру) -->
  <div
    id="learnFooter"
    class="position-sticky bottom-0 w-100 bg-body border-top shadow-none"
    style="z-index:1030; padding-bottom: env(safe-area-inset-bottom);">
    

    <div class="container bg-transparent border-0 shadow-none p-0">
      <div class="d-flex justify-content-center align-items-center gap-3 py-2">
        <button
          id="btnPrev"
          class="round-btn btn btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center"
          aria-label="Previous"
          style="width:64px;height:64px">
          <i class="bi bi-chevron-left fs-3"></i>
        </button>

        <button
          id="btnAudio"
          class="btn rounded-circle d-flex align-items-center justify-content-center"
          style="width:72px;height:72px;background-color:#0ea5e9;border-color:#0ea5e9;color:#fff"
          aria-label="Audio">
          <i class="bi bi-volume-up fs-3"></i>
        </button>

        <button
          id="btnNext"
          class="round-btn btn btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center"
          aria-label="Next"
          style="width:64px;height:64px">
          <i class="bi bi-chevron-right fs-3"></i>
        </button>
      </div>

      <div class="text-center text-secondary small pb-1">
        <span id="counter"></span>
      </div>

      <audio id="audioPlayer"></audio>
    </div>
  </div>

</body>




<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($total > 0): ?>
<script>
const CARDS = <?= json_encode($cardsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

let idx = 0;
let isFront = true;
let navLock = false;

const titleEl   = document.getElementById('cardTitle');
const counterEl = document.getElementById('counter');
const audioEl   = document.getElementById('audioPlayer');

const btnPrev  = document.getElementById('btnPrev');
const btnNext  = document.getElementById('btnNext');
const btnAudio = document.getElementById('btnAudio');

const wrapper = document.querySelector('#swiperCards .swiper-wrapper');

CARDS.forEach((card, i) => {
  const slide = document.createElement('div');
  slide.className = 'swiper-slide';
  slide.innerHTML = `
    <div class="ratio w-100 position-relative d-block d-lg-none" style="--bs-aspect-ratio:145%;">
      <div class="flipper" data-index="${i}">
        <div class="front">
          ${card.front ? `<img src="${card.front}" alt="Front" class="img-fluid object-fit-contain w-100 h-100">` : '<span class="text-muted">No image</span>'}
        </div>
        <div class="back">
          ${card.back ? `<img src="${card.back}" alt="Back" class="img-fluid object-fit-contain w-100 h-100">` : '<span class="text-muted">No image</span>'}
        </div>
      </div>
    </div>

    <div class="ratio ratio-4x3 w-100 position-relative d-none d-lg-block d-xl-none">
      <div class="flipper" data-index="${i}">
        <div class="front">
          ${card.front ? `<img src="${card.front}" alt="Front" class="img-fluid object-fit-contain w-100 h-100">` : '<span class="text-muted">No image</span>'}
        </div>
        <div class="back">
          ${card.back ? `<img src="${card.back}" alt="Back" class="img-fluid object-fit-contain w-100 h-100">` : '<span class="text-muted">No image</span>'}
        </div>
      </div>
    </div>

    <div class="ratio ratio-16x9 w-100 position-relative d-none d-xl-block">
      <div class="flipper" data-index="${i}">
        <div class="front">
          ${card.front ? `<img src="${card.front}" alt="Front" class="img-fluid object-fit-contain w-100 h-100">` : '<span class="text-muted">No image</span>'}
        </div>
        <div class="back">
          ${card.back ? `<img src="${card.back}" alt="Back" class="img-fluid object-fit-contain w-100 h-100">` : '<span class="text-muted">No image</span>'}
        </div>
      </div>
    </div>
  `;
  wrapper.appendChild(slide);
});

const swiper = new Swiper('#swiperCards', {
  effect: 'cards',
  grabCursor: true,
  cardsEffect: { perSlideOffset: 4, perSlideRotate: 4, rotate: true, slideShadows: false },
  on: {
    init() { updateUIForIndex(0); },
    slideChange() {
      idx = swiper.activeIndex;
      resetAllFlips();
      isFront = true;
      updateUIForIndex(idx);
    },
    slideChangeTransitionEnd() {
      navLock = false;
      btnPrev.removeAttribute('disabled');
      btnNext.removeAttribute('disabled');
      updateButtons();
    },
    transitionEnd() {
      navLock = false;
      btnPrev.removeAttribute('disabled');
      btnNext.removeAttribute('disabled');
      updateButtons();
    }
  }
});

function updateButtons() {
  btnPrev.disabled  = (idx === 0);
  btnNext.disabled  = (idx === CARDS.length - 1);
  btnAudio.disabled = !CARDS[idx].audio;
}
function updateUIForIndex(i){
  const card = CARDS[i];
  titleEl.textContent   = card.title || '';
  counterEl.textContent = `${i + 1} / ${CARDS.length}`;
  const percent = Math.round(((i + 1) / CARDS.length) * 100);
  const progressBar = document.querySelector('.progress-bar');
  progressBar.style.width = percent + '%';
  progressBar.textContent = percent + '%';
  progressBar.setAttribute('aria-valuenow', percent);

  audioEl.pause(); audioEl.currentTime = 0; audioEl.src = card.audio || '';
  updateButtons();
}
function getActiveFlipper(){
  const activeSlide = swiper.slides[swiper.activeIndex];
  if (!activeSlide) return null;
  const flippers = activeSlide.querySelectorAll('.ratio .flipper');
  for (const f of flippers) {
    const ratio = f.closest('.ratio');
    if (ratio && ratio.offsetParent !== null) return f;
  }
  return activeSlide.querySelector('.flipper');
}
function resetAllFlips(){
  swiper.slides.forEach(sl => { const f = sl.querySelector('.flipper'); if (f) f.classList.remove('flip'); });
}
function flip(){
  const flipper = getActiveFlipper();
  if (!flipper) return;
  isFront = !isFront;
  flipper.classList.toggle('flip', !isFront);
}
function next(){ if (navLock || idx === CARDS.length - 1) return; navLock = true; swiper.slideNext(); }
function prev(){ if (navLock || idx === 0) return; navLock = true; swiper.slidePrev(); }
function playAudio(){ if (!CARDS[idx].audio) return; audioEl.currentTime = 0; audioEl.play().catch(()=>{}); }

function addPressed(btn){ btn.classList.add('is-pressed'); }
function rmPressed(btn){ btn.classList.remove('is-pressed'); }
function bindButton(btn, onUp, {lockNavButton = false, tempDisableMs = 0} = {}){
  btn.addEventListener('pointerdown', () => addPressed(btn));
  btn.addEventListener('pointerup', () => {
    if (lockNavButton && navLock) { rmPressed(btn); return; }
    if (tempDisableMs > 0) btn.setAttribute('disabled','');
    onUp();
    requestAnimationFrame(() => {
      btn.blur(); rmPressed(btn);
      if (tempDisableMs > 0) setTimeout(() => btn.removeAttribute('disabled'), tempDisableMs);
    });
  });
  ['pointerleave','pointercancel'].forEach(ev => btn.addEventListener(ev, () => rmPressed(btn)));
}
bindButton(btnNext, next, { lockNavButton: true });
bindButton(btnPrev, prev, { lockNavButton: true });
bindButton(btnAudio, playAudio, { tempDisableMs: 150 });

document.getElementById('swiperCards').addEventListener('click', (e) => {
  if (!swiper.allowClick) return;
  if (e.target.closest('button')) return;
  const slideEl = e.target.closest('.swiper-slide');
  if (!slideEl) return;
  if (slideEl !== swiper.slides[swiper.activeIndex]) return;
  flip();
});

document.addEventListener('keydown', (e) => {
  const tag = e.target.tagName;
  if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) return;
  if (e.code === 'ArrowRight' || e.key === 'ArrowRight') { e.preventDefault(); next(); }
  else if (e.code === 'ArrowLeft' || e.key === 'ArrowLeft') { e.preventDefault(); prev(); }
  else if (e.code === 'Space' || e.key === ' ' || e.key === 'Spacebar' || e.keyCode === 32) { e.preventDefault(); flip(); }
});
</script>
<?php endif; ?>

</body>
</html>
