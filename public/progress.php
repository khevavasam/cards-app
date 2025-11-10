<?php
// public/progress.php — свайпы «знаю / не знаю» + счётчики, финальный экран + перемешивание + аудио
require_once __DIR__ . '/../app/db.php';

session_name('CARDSAPP');
ini_set('session.cookie_path', '/cards-app');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: /cards-app/public/login.php'); exit; }
$userId = (int)$_SESSION['user_id'];

$pdo = db();

/* i18n */
require_once __DIR__ . '/../app/i18n.php';
i18n_bootstrap($pdo, $userId);

/** 1) Параметр деки (поддерживаем deck_id и deck) */
$deckId = 0;
if (isset($_GET['deck_id']))      $deckId = (int)$_GET['deck_id'];
elseif (isset($_GET['deck']))     $deckId = (int)$_GET['deck'];
if ($deckId <= 0) { http_response_code(400); echo "Некорректный параметр deck."; exit; }

/** 2) Инфо о деке */
$deckStmt = $pdo->prepare("
  SELECT d.id, d.name AS deck_name, c.id AS cat_id, c.name AS cat_name
  FROM decks d
  JOIN categories c ON c.id = d.category_id
  WHERE d.id = :deck
  LIMIT 1
");
$deckStmt->execute([':deck' => $deckId]);
$deck = $deckStmt->fetch(PDO::FETCH_ASSOC);
if (!$deck) { http_response_code(404); echo "Дек не найден."; exit; }

/** helper: пути к /public */
function pubPath(?string $p): ?string {
  if (!$p) return null;
  $p = ltrim($p, '/');
  return '/cards-app/public/' . $p;
}

/** 3) Все активные карточки деки + пометка, известна ли карта юзеру сейчас */
$cardsStmt = $pdo->prepare("
  SELECT
    c.id, c.deck_id, c.title,
    c.front_image_path, c.back_image_path, c.audio_path,
    c.sort_order, c.is_active,
    (uk.user_id IS NOT NULL) AS is_known
  FROM cards c
  LEFT JOIN user_known_cards uk
    ON uk.card_id = c.id AND uk.user_id = :uid
  WHERE c.deck_id = :deck AND c.is_active = 1
  ORDER BY c.sort_order, c.id
");
$cardsStmt->execute([':deck' => $deckId, ':uid' => $userId]);
$rows = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);

/** 4) Подсчёты */
$total = count($rows);
$knownNow = 0;
foreach ($rows as $r) if ((int)$r['is_known'] === 1) $knownNow++;

/** 5) В JS отдадим карточки в удобном виде */
$cards = array_map(function($r){
  return [
    'id'       => (int)$r['id'],
    'title'    => (string)$r['title'],
    'front'    => pubPath($r['front_image_path']),
    'back'     => pubPath($r['back_image_path']),
    'audio'    => $r['audio_path'] ? pubPath($r['audio_path']) : null,
    'is_known' => (int)$r['is_known'] === 1
  ];
}, $rows);
?>
<!doctype html>
<html lang="ru" data-bs-theme="light">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Прогресс — <?= htmlspecialchars($deck['cat_name'].' / '.$deck['deck_name']) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"/>
  <link rel="icon" type="image/png" href="/cards-app/public/images/golova.png">

  <style>
:root{
  --border:#e5e7eb;
  --accent:#0ea5e9;
  --ok:#22c55e;
  --warn:#f59e0b;
}

/* Chips */
.chip{
  border:1px solid #cfe8ff;
  background:#e0f2fe;
  border-radius:999px;
  padding:.35rem .75rem;
  font-weight:600;
  font-size:.875rem;
  display:inline-flex;
  gap:.5rem;
  align-items:center;
}
.chip .dot{width:9px;height:9px;border-radius:50%}
.dot-learn{background:var(--warn)}
.dot-known{background:var(--ok)}

/* НЕ переопределяем bootstrap .progress/.progress-bar глобально */

/* Карточка + свайпер */
html,body{ overflow-x:hidden; }
.swiper{ width:100%; overflow:hidden; }
.swiper .swiper-slide{
  height:min(60vh,420px);
  display:flex; align-items:center; justify-content:center;
  background:transparent;
}
@media (min-width:768px){
  .swiper .swiper-slide{ height:460px; }
}

/* Flip-карта */
.flip-container{ width:100%; height:100%; position:relative; }
.flipper{
  position:relative; width:100%; height:100%;
  transform-style:preserve-3d; transition:transform .6s; cursor:pointer;
}
.flipper.flip{ transform:rotateY(180deg); }
.front,.back{
  position:absolute; inset:0; backface-visibility:hidden;
  display:flex; align-items:center; justify-content:center; padding:8px;
}
.back{ transform:rotateY(180deg); }
.front img,.back img{ max-width:100%; max-height:100%; object-fit:contain; display:block; }

/* Круглые кнопки */
.btn-circle{
  --size:56px;
  inline-size:var(--size);
  block-size:var(--size);
  padding:0 !important;
  border-radius:50% !important;
  aspect-ratio:1/1;
  display:inline-grid; place-items:center;
  line-height:1;
}
.btn-circle-lg{ --size:68px; }

/* Панель действий */
.actions-row{
  display:grid; grid-template-columns:1fr auto 1fr;
  align-items:center; gap:.5rem;
}
.actions-row .left{ justify-self:start; }
.actions-row .center{ justify-self:center; }
.actions-row .right{ justify-self:end; }

/* Анимация удаления */
.swiper-slide.removing{
  will-change: transform, opacity, filter;
  transition: transform .55s cubic-bezier(.22,.61,.36,1),
              opacity .55s linear, filter .55s ease;
}
.swiper-slide.fly-right{ transform: translateX(140%) rotate(14deg) scale(.96) !important; opacity:0 !important; filter:blur(1px); }
.swiper-slide.fly-left { transform: translateX(-140%) rotate(-14deg) scale(.96) !important; opacity:0 !important; filter:blur(1px); }
@media (prefers-reduced-motion:reduce){
  .swiper-slide.removing{ transition:none; }
  .swiper-slide.fly-right,.swiper-slide.fly-left{ transform:translateX(140%) !important; filter:none; }
}

/* ======================================================================
   ФИНАЛЬНЫЙ ЭКРАН (#finish)
   ====================================================================== */

/* Контейнер финиша стабилен по ширине на десктопе */
#finish{ max-width:980px; margin-inline:auto; }

/* Карточка финиша не обрезает содержимое (кнопки) */
.finish-card{ overflow:visible !important; }  /* переопределяем .overflow-hidden */

/* Сетка внутри карточки: слева кольцо, справа тексты и бары */
#finish .card-body{
  display:grid;
  grid-template-columns:auto 1fr;
  align-items:center;
  gap:24px;
}
@media (max-width:991.98px){
  #finish .card-body{ grid-template-columns:1fr; }
}

/* Заголовки/подписи чуть темнее */
#finish .h5{ letter-spacing:.2px; font-weight:800; color:#0b1220; }
#finish .text-muted{ color:#334155 !important; }

/* Кольцо прогресса */
#finish .progress-ring{
  contain: paint;
  isolation:isolate;

  --size: clamp(120px, 12vw, 160px);
  --track:#e3eaf2;
  --fill:var(--accent);
  --start:0deg;          /* можно -90deg, чтобы старт был сверху */
  --thickness:20px;
  --white-border:12px;
  --pct:0;               /* 0..100 — ставит JS */

  width:var(--size);
  height:var(--size);
  border-radius:50%;
  position:relative;

  background: conic-gradient(
    from var(--start),
    var(--fill) calc(var(--pct)*1%), var(--track) 0
  );
  box-shadow:
    0 20px 40px rgba(2,132,199,.28),
    0 0 0 var(--white-border) #fff inset,
    0 0 0 1px rgba(14,165,233,.36) inset,
    0 0 26px 8px rgba(56,189,248,.16);
}
#finish .progress-ring__inner{
  position:absolute;
  inset:calc(var(--white-border) + var(--thickness));
  background:#fff; border-radius:50%;
  box-shadow: inset 0 2px 12px rgba(15,23,42,.06),
              0 0 0 1px rgba(148,163,184,.32);
  will-change:transform;
}
#finish .progress-ring__label{
  position:absolute; inset:0;
  display:flex; align-items:center; justify-content:center;
  font-weight:900; font-size:1.15rem; letter-spacing:.2px;
  color:#0b1220; text-shadow:0 1px 0 rgba(255,255,255,.6);
}

/* Бары прогресса */
#finish .bar{
  height:14px; background:#e9eef5;
  border-radius:999px; overflow:hidden;
  box-shadow: inset 0 1px 2px rgba(15,23,42,.06);
}
#finish .bar .in{
  height:100%; width:0; /* ширину ставит JS */
  transition: width .95s cubic-bezier(.22,.61,.36,1);
  background: linear-gradient(90deg, #10b981, #059669);
}
#finish .bar .in.orange{ background: linear-gradient(90deg, #f59e0b, #fbbf24); }
#barRemain.in{ background: linear-gradient(90deg, #94a3b8, #cbd5e1); }

/* Кнопки под барами: даём перенос и авто-отступ последней */
#finish .mt-4 > .d-grid{
  /* На sm+ Bootstrap уже делает display:flex — добавим переносы */
  flex-wrap: wrap;
  gap: .5rem 1rem !important; /* одинаковый gap для всех режимов */
}
#finish .mt-4 > .d-grid .btn{
  flex: 0 1 auto;
  white-space: nowrap;         /* чтобы текст на кнопках не ломался */
}
#finish .mt-4 > .d-grid .btn:last-child{
  margin-left: auto;           /* «Домой/Колоди» уезжает вправо, если влазит */
}

/* Конфетти-слой */
.confetti{ position:absolute; inset:0; pointer-events:none; }

/* ======================================================================
   Адаптив по высоте экрана (новые vh-юниты)
   ====================================================================== */
@supports (height:1svh){
  .swiper .swiper-slide{ height: clamp(300px, 52svh, 420px); }
}
@supports (height:1dvh){
  .swiper .swiper-slide{ height: clamp(300px, 52dvh, 420px); }
}

/* Невысокие экраны */
@media (max-height:740px){
  .swiper .swiper-slide{ height:min(48vh,360px); }
  .btn-circle{ --size:48px; }
  .btn-circle-lg{ --size:60px; }
}
@media (max-height:660px){
  .swiper .swiper-slide{ height:min(42vh,320px); }
  .btn-circle{ --size:44px; }
  .btn-circle-lg{ --size:54px; }
  #cardTitle{ font-size:.95rem; }
}

/* Accessibility */
@media (prefers-reduced-motion:reduce){
  #finish .progress-ring{ transition:none; }
  #finish .bar .in{ transition:none; }
}

/* Switch color */
.form-check-input:checked{
  background-color:var(--accent);
  border-color:var(--accent);
}

/* Только для верхнего бара */
#overallProgress{
  height:12px;
  background:#e9eef5;
  border-radius:999px;
  overflow:hidden;
}
#overallProgressBar{
  background-color:#0ea5e9 !important; /* твой акцент */
}
@media (prefers-reduced-motion: no-preference){
  #overallProgressBar{
    transition: width .6s cubic-bezier(.22,.61,.36,1);
  }
}


  </style>
</head>
<body>
<nav class="navbar bg-transparent py-2">
  <div class="container">
    <a class="navbar-brand text-secondary me-2" href="/cards-app/public/index.php">← <?= t('common.decks') ?></a>
    <span class="navbar-text text-secondary small">
      <?= htmlspecialchars($deck['cat_name']) ?> / <strong><?= htmlspecialchars($deck['deck_name']) ?></strong>
    </span>
  </div>
</nav>

<!-- Даем контенту отступ снизу под фикс-панель + safe-area iOS -->
<main class="container py-2"
      style="padding-bottom: calc(104px + env(safe-area-inset-bottom));"> 
  <div class="row justify-content-center">
    <div class="col-12 col-sm-11 col-md-10 col-lg-8 col-xl-7">

      <?php if ($total === 0): ?>
        <div class="alert alert-warning my-2"><?= t('common.no_cards') ?></div>
      <?php else: ?>

        <!-- Верх -->
        <div class="d-flex flex-column gap-1 gap-md-2 flex-md-row justify-content-between align-items-start align-items-md-center mb-2 mb-md-3">
          <div class="d-flex flex-wrap gap-1 gap-md-2">
            <span class="chip"><span class="dot dot-learn"></span> <?= t('progress.chip.learning') ?>: <span id="cntLearning" class="ms-1"><?= $total - $knownNow ?></span></span>
            <span class="chip"><span class="dot dot-known"></span> <?= t('progress.chip.known') ?>: <span id="cntKnown" class="ms-1"><?= $knownNow ?></span></span>
          </div>
          <div class="text-muted small mt-1 mt-md-0"><?= t('common.total') ?>: <?= $total ?></div>
        </div>

        <!-- Общий прогресс по колоде -->
        <div id="overallProgress" class="progress my-2 my-md-3" role="progressbar"
            aria-label="Progress" aria-valuemin="0" aria-valuemax="100">
          <div id="overallProgressBar"
              class="progress-bar progress-bar-striped"
              style="width:0%"></div>
        </div>


        <!-- Карточки -->
        <div class="swiper mt-2" id="swiperCards">
          <div class="swiper-wrapper"></div>
        </div>

        <!-- Заголовок карточки -->
        <div class="text-center small my-1" id="cardTitle"></div>

        <!-- Финал (красивый) -->
        <section id="finish" class="d-none mt-3 mt-md-4">
          <div class="finish-card card border-0 shadow-sm position-relative overflow-hidden">
            <canvas id="confetti" class="confetti d-none"></canvas>

            <div class="card-body p-3 p-md-4">
              <div class="d-flex flex-column flex-md-row align-items-center gap-3 gap-md-4">

                <!-- Кольцо прогресса -->
                <div class="progress-ring-wrapper">
                  <div class="progress-ring" id="finishRing" style="--pct:0;">
                    <div class="progress-ring__inner"></div>
                    <div class="progress-ring__label">
                      <span id="finishPct">0%</span>
                    </div>
                  </div>
                </div>

                <!-- Текст и бары -->
                <div class="flex-grow-1 w-100">
                  <h2 class="h5 mb-2 mb-md-3 text-center text-md-start"><?= t('progress.final.title') ?></h2>
                  <div class="text-muted mb-3 text-center text-md-start"><?= t('progress.final.subtitle') ?></div>

                  <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="min-width:110px"><?= t('progress.bar.known') ?> <span id="finKnown">0</span></div>
                    <div class="bar flex-grow-1"><div id="barKnown" class="in" style="width:0%"></div></div>
                  </div>
                  <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="min-width:110px"><?= t('progress.bar.learning') ?> <span id="finLearn">0</span></div>
                    <div class="bar flex-grow-1"><div id="barLearn" class="in orange" style="width:0%"></div></div>
                  </div>

                  <!-- Кнопки действий -->
                  <div class="mt-4">
                    <div class="d-grid gap-2 gap-sm-3 d-sm-flex align-items-center">
                      <a
                        class="btn btn-primary btn-lg px-4 d-flex align-items-center gap-2"
                        style="background-color:#0ea5e9;border-color:#0ea5e9;
                              transition:background-color .18s ease, border-color .18s ease,
                                          box-shadow .18s ease, transform .18s ease;"
                        onmouseenter="this.style.backgroundColor='#10b4f0'; this.style.borderColor='#10b4f0'; this.style.boxShadow='0 6px 16px rgba(14,165,233,.35)'; this.style.transform='translateY(-1px)';"
                        onmouseleave="this.style.backgroundColor='#0ea5e9'; this.style.borderColor='#0ea5e9'; this.style.boxShadow='none'; this.style.transform='none';"
                        href="/cards-app/public/progress.php?deck=<?= (int)$deckId ?>"
                        aria-label="<?= t('progress.btn.restart') ?>">
                        <i class="bi bi-arrow-repeat fs-5"></i>
                        <span><?= t('progress.btn.restart') ?></span>
                      </a>



                      <a class="btn btn-outline-secondary btn-lg px-4 d-flex align-items-center gap-2"
                        href="/cards-app/public/learn.php?deck=<?= (int)$deckId ?>"
                        aria-label="<?= t('progress.btn.go_to_learn') ?>">
                        <i class="bi bi-book fs-5"></i>
                        <span><?= t('progress.btn.go_to_learn') ?></span>
                      </a>

                      <!-- Доп.кнопка: вернуться к колодам (иконка домика). Если не нужна — удали. -->
                      <a class="btn btn-light btn-lg px-4 d-flex align-items-center gap-2 ms-sm-auto"
                        href="/cards-app/public/index.php"
                        aria-label="<?= t('common.decks') ?>">
                        <i class="bi bi-house-door fs-5"></i>
                        <span><?= t('common.decks') ?></span>
                      </a>
                    </div>

                    <!-- Маленькая подсказка под кнопками (без изменения основного текста) -->
                    <div class="text-muted small mt-2">
                      <!-- можно оставить пустым или добавить любой нейтральный вспомогательный текст в будущем -->
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </section>



      <?php endif; ?>

    </div>
  </div>
</main>

<?php if ($total !== 0): ?>
<!-- Фиксированная панель действий поверх контента (всегда видна) -->
<div class="fixed-bottom bg-white border-top">
  <div class="container"
       style="padding-bottom: env(safe-area-inset-bottom);"> <!-- чтобы не упиралось в home-indicator -->
    <div class="row justify-content-center">
      <div class="col-12 col-sm-11 col-md-10 col-lg-8 col-xl-7">

        <div id="actionsRow" class="actions-row my-2 my-md-3">
          <div class="left">
          <div class="form-check form-switch m-0 fs-4" id="shuffleSwitchWrap" title="<?= t('progress.btn.shuffle') ?>">
            <input class="form-check-input" type="checkbox" role="switch" id="shuffleSwitch">
            <label class="form-check-label small ms-2 d-none d-sm-inline" for="shuffleSwitch">
              <?= t('progress.btn.shuffle') ?>
            </label>
          </div>

          </div>

          <div class="center d-flex align-items-center gap-3">
            <button type="button" class="btn btn-outline-danger border-2 btn-circle" id="btnUnlearn">
              <i class="bi bi-x-lg fs-5"></i>
            </button>
            
            <button id="btnAudio"
              class="btn rounded-circle d-flex align-items-center justify-content-center"
              style="width:72px;height:72px;background-color:var(--accent);border-color:var(--accent);color:#fff"
              aria-label="Audio">
              <i class="bi bi-volume-up fs-3"></i>
            </button>

            <button type="button" class="btn btn-outline-success border-2 btn-circle" id="btnKnow">
              <i class="bi bi-check-lg fs-5"></i>
            </button>
          </div>

          <div class="right text-muted small text-nowrap">
            <span id="counter"></span>
          </div>
        </div>

        <!-- Аудио-плеер -->
        <audio id="audioPlayer"></audio>

      </div>
    </div>
  </div>
</div>  
<?php endif; ?>

</body>



<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($total > 0): ?>
<script>
/* ===== Исходные данные ===== */
const CARDS = <?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const TOTAL = <?= (int)$total ?>;
const initKnown = <?= (int)$knownNow ?>;
const ORIGINAL_ORDER = CARDS.map(c => c.id);

let isShuffled = false;
let known = initKnown;
let learning = TOTAL - initKnown;

/* ===== Ссылки на элементы ===== */
const cntKnownEl  = document.getElementById('cntKnown');
const cntLearnEl  = document.getElementById('cntLearning');
const pb          = document.getElementById('overallProgressBar');
const titleEl     = document.getElementById('cardTitle');
const counterEl   = document.getElementById('counter');
const audioEl     = document.getElementById('audioPlayer');

const btnKnow     = document.getElementById('btnKnow');
const btnUnl      = document.getElementById('btnUnlearn');
const btnShuffle  = document.getElementById('btnShuffle');
const btnAudio    = document.getElementById('btnAudio');

const wrap        = document.querySelector('#swiperCards .swiper-wrapper');

/* ===== Рендер слайдов ===== */
function slideHTML(card, i){
  return `
    <div class="swiper-slide">
      <div class="flip-container">
        <div class="flipper" data-index="${i}">
          <div class="front">
            ${card.front ? `<img class="img-fluid" src="${card.front}" alt="">` : '<span class="text-muted">Нет изображения</span>'}
          </div>
          <div class="back">
            ${card.back ? `<img class="img-fluid" src="${card.back}" alt="">` : '<span class="text-muted">Нет изображения</span>'}
          </div>
        </div>
      </div>
    </div>`;
}
CARDS.forEach((c,i)=>{ const d=document.createElement('div'); d.innerHTML=slideHTML(c,i); wrap.appendChild(d.firstElementChild); });

/* ===== Swiper ===== */
let idx = 0, isFront = true;
const swiper = new Swiper('#swiperCards', {
  effect:'cards',
  allowTouchMove:false,
  grabCursor:false,
  cardsEffect:{ perSlideOffset:8, perSlideRotate:2, rotate:true, slideShadows:false },
  on:{
    init(){ updateUI(0); },
    slideChange(){
      idx = swiper.activeIndex;
      resetFlips(); isFront = true;
      updateUI(idx);
    }
  }
});

/* ===== Обновление UI ===== */
function updateCounters(){
  cntKnownEl.textContent = known;
  cntLearnEl.textContent = learning;
}

function updateProgress(){
  // уже решено (слайдов, удалённых из CARDS)
  const solved = TOTAL - (Array.isArray(CARDS) ? CARDS.length : 0);

  // процент строго 0..100
  const p = TOTAL > 0
    ? Math.min(100, Math.max(0, Math.round((solved / TOTAL) * 100)))
    : 0;

  if (!pb) return;

  pb.style.width = p + '%';
  pb.setAttribute('aria-valuenow', p);
  pb.classList.toggle('progress-bar-animated', p > 0 && p < 100);

  // текст внутри бара: показываем только в процессе, на 0% и 100% — пусто
  pb.textContent = (p > 0 && p < 100) ? (p + '%') : '';
  pb.setAttribute('aria-valuetext', p + '%');
}


function updateUI(i){
  if (!CARDS[i]) return;
  titleEl.textContent = CARDS[i].title || '';
  if (counterEl) counterEl.textContent = `${TOTAL - CARDS.length + 1} / ${TOTAL}`;

  if (audioEl){
    audioEl.pause(); audioEl.currentTime = 0;
    audioEl.src = CARDS[i].audio || '';
  }
  if (btnAudio){ btnAudio.disabled = !CARDS[i].audio; }

  updateCounters();
  updateProgress();
  defocus();
}

/* ===== Вспомогательные ===== */
function getActiveSlide(){ return swiper.slides[swiper.activeIndex] || null; }
function getActiveFlipper(){ const sl = getActiveSlide(); return sl ? sl.querySelector('.flipper') : null; }
function resetFlips(){ swiper.slides.forEach(sl => sl.querySelector('.flipper')?.classList.remove('flip')); }
function flip(){
  const f = getActiveFlipper(); if (!f) return;
  isFront = !isFront;
  f.classList.toggle('flip', !isFront);
}
function defocus(){ const ae = document.activeElement; if (ae && typeof ae.blur === 'function') ae.blur(); }

/* ===== API ===== */
async function mark(cardId, action){
  try{
    const res = await fetch('/cards-app/public/api/known_toggle.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ card_id: cardId, action })
    });
    const data = await res.json();
    if (!data.ok) console.warn('API error', data);
  }catch(e){ console.warn('Network error', e); }
}

/* ===== Решение карточки (моментальный UI + надёжный фолбэк) ===== */
function decide(direction){
  return new Promise((resolve) => {
    if (!CARDS[idx]) return resolve();

    const card = CARDS[idx];
    const wasKnown = !!card.is_known;
    const slideEl = getActiveSlide();
    if (!slideEl) return resolve();

    // 1) Визуальная анимация «вылета»
    slideEl.classList.add('removing');
    void slideEl.offsetWidth; // reflow
    slideEl.classList.add(direction === 'right' ? 'fly-right' : 'fly-left');

    // 2) Мгновенно обновляем локальное состояние (UI не блокируем сетью)
    if (direction === 'right'){
      if (!wasKnown){ known++; learning--; card.is_known = true; }
      // fire-and-forget
      mark(card.id, 'know').catch(()=>{ /* можно лог */ });
    } else {
      if (wasKnown){ known--; learning++; card.is_known = false; }
      mark(card.id, 'unlearn').catch(()=>{ /* можно лог */ });
    }

    // 3) Последняя карточка — финал сразу, без ожиданий
    if (CARDS.length === 1) {
      swiper.removeSlide(idx);
      CARDS.splice(idx, 1);
      updateProgress();
      showFinish();
      return resolve();
    }

    // 4) Остальные — ждём transitionend + даём фолбэк на случай, если ивент не придёт
    let finished = false;
    const done = () => {
      if (finished) return; finished = true;

      swiper.removeSlide(idx);
      CARDS.splice(idx, 1);

      if (CARDS.length === 0) {
        showFinish();
        return resolve();
      }

      if (idx >= CARDS.length) idx = CARDS.length - 1;
      swiper.update();
      updateUI(idx);
      resolve();
    };

    slideEl.addEventListener('transitionend', done, { once: true });
    setTimeout(done, 800); // фолбэк
  });
}



/* ===== Перемешивание ===== */
function shuffleArray(arr){
  for (let i = arr.length - 1; i > 0; i--){
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
}
function rebuildSlidesFromCards(){
  const slides = CARDS.map((c, i) => slideHTML(c, i));
  const count = swiper.slides.length;
  if (count > 0) swiper.removeSlide([...Array(count).keys()]);
  swiper.appendSlide(slides);
  idx = 0; isFront = true;
  swiper.slideTo(0, 0);
  resetFlips();
  updateUI(0);
}
function restoreOriginalOrder(){
  CARDS.sort((a,b)=> ORIGINAL_ORDER.indexOf(a.id) - ORIGINAL_ORDER.indexOf(b.id));
  rebuildSlidesFromCards();
}
// Свитч «перемешать»
const shuffleSwitch = document.getElementById('shuffleSwitch');
if (shuffleSwitch){
  // актуальное состояние в UI
  shuffleSwitch.checked = isShuffled;

  shuffleSwitch.addEventListener('change', (e) => {
    if (!CARDS.length) return;
    if (e.target.checked){
      shuffleArray(CARDS);
      rebuildSlidesFromCards();
      isShuffled = true;
    } else {
      restoreOriginalOrder();
      isShuffled = false;
    }
  });
}

/* ===== Визуальные состояния на кнопках ===== */
const addPressed = btn => btn.classList.add('active');
const rmPressed  = btn => btn.classList.remove('active');

const btnKnowEl = document.getElementById('btnKnow');
const btnUnlEl  = document.getElementById('btnUnlearn');

btnKnowEl.addEventListener('pointerdown', () => addPressed(btnKnowEl));
btnUnlEl .addEventListener('pointerdown', () => addPressed(btnUnlEl));

const handlePress = (dir, btn) => () => {
  btn.setAttribute('disabled', '');
  decide(dir).finally(() => {
    requestAnimationFrame(() => { btn.blur(); rmPressed(btn); btn.removeAttribute('disabled'); });
  });
};
btnKnowEl.addEventListener('pointerup', handlePress('right', btnKnowEl));
btnUnlEl .addEventListener('pointerup', handlePress('left',  btnUnlEl ));
['pointercancel','pointerleave'].forEach(ev => {
  btnKnowEl.addEventListener(ev, () => rmPressed(btnKnowEl));
  btnUnlEl .addEventListener(ev, () => rmPressed(btnUnlEl));
});

/* ===== Аудио ===== */
if (btnAudio){
  btnAudio.addEventListener('pointerdown', () => addPressed(btnAudio));
  btnAudio.addEventListener('pointerup', () => {
    btnAudio.setAttribute('disabled','');
    if (audioEl && audioEl.src){ audioEl.currentTime = 0; audioEl.play().catch(()=>{}); }
    requestAnimationFrame(() => {
      btnAudio.blur(); rmPressed(btnAudio);
      setTimeout(() => { if (CARDS[idx]?.audio) btnAudio.removeAttribute('disabled'); }, 150);
    });
  });
  ['pointerleave','pointercancel'].forEach(ev => btnAudio.addEventListener(ev, () => rmPressed(btnAudio)));
}

/* ===== Тапы / клавиши ===== */
document.getElementById('swiperCards').addEventListener('click', (e)=>{
  const fl = e.target.closest('.flipper'); if (fl) flip();
});
document.addEventListener('keydown', (e)=>{
  if (!CARDS.length) return;
  if (e.code === 'ArrowRight'){ e.preventDefault(); decide('right'); }
  if (e.code === 'ArrowLeft') { e.preventDefault(); decide('left'); }
  if (e.code === 'Space'){ e.preventDefault(); flip(); }
});




/* ===== Анимации финала ===== */

// Плавная анимация числа: 0 -> target за duration мс
function animateNumber(el, target, duration = 1800){
  if (!el) return;
  const start = 0;
  const t0 = performance.now();
  const fmt = v => Math.round(v);
  function frame(t){
    const p = Math.min(1, (t - t0) / duration);
    el.textContent = fmt(start + (target - start) * p);
    if (p < 1) requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
}

// Установить ширину прогресс-бара (в процентах)
function setWidth(el, pct){ if (el) el.style.width = pct + '%'; }

/* Кольцо: JS-твин переменной --pct (работает везде, без @property)
   fromValue — старт (обычно 0), toValue — целевой процент [0..100] */
function animateRing(el, toValue, duration = 1800, fromValue = 0){
  if (!el) return;
  const start = performance.now();
  // Сбрасываем к 0 (или fromValue), чтобы анимация всегда шла "с нуля"
  el.style.setProperty('--pct', fromValue);
  function tick(now){
    const p = Math.min(1, (now - start) / duration);
    const val = fromValue + (toValue - fromValue) * p;
    el.style.setProperty('--pct', val);
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

// Лёгкое конфетти (canvas)
function launchConfetti(canvas, seconds = 1.2, count = 120){
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width = canvas.offsetWidth;
  const H = canvas.height = canvas.offsetHeight;
  canvas.classList.remove('d-none');

  const parts = Array.from({ length: count }, () => ({
    x: Math.random() * W,
    y: -10 - Math.random() * H * 0.3,
    s: 2 + Math.random() * 4,
    a: Math.random() * Math.PI,
    v: 40 + Math.random() * 90,
    c: `hsl(${Math.floor(Math.random()*360)},80%,60%)`
  }));

  const t0 = performance.now();
  function tick(t){
    const dt = (t - (tick._t || t)) / 1000; tick._t = t;
    ctx.clearRect(0,0,W,H);
    parts.forEach(p=>{
      p.y += p.v * dt;
      p.x += Math.sin((t/200) + p.a) * 30 * dt;
      ctx.fillStyle = p.c;
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.a + t/500);
      ctx.fillRect(-p.s/2, -p.s/2, p.s, p.s * 0.6);
      ctx.restore();
    });
    if ((t - t0) / 1000 < seconds) requestAnimationFrame(tick);
    else canvas.classList.add('d-none');
  }
  requestAnimationFrame(tick);
}

// Запуск всех анимаций финального экрана
function startFinishAnimation({ known, learn, total }){
  const remaining = Math.max(0, total - known - learn);
  const pct = total > 0 ? Math.round((known / total) * 100) : 0;

  // 1) Цифры
  animateNumber(document.getElementById('finKnown'), known);
  animateNumber(document.getElementById('finLearn'), learn);

  // 2) Полосы прогресса (с небольшими смещениями по времени)
  setTimeout(()=> setWidth(document.getElementById('barKnown'),   total ? (known/total)*100    : 0),  60);
  setTimeout(()=> setWidth(document.getElementById('barLearn'),   total ? (learn/total)*100    : 0), 130);
  setTimeout(()=> setWidth(document.getElementById('barRemain'),  total ? (remaining/total)*100: 0), 200);

  // 3) Кольцо + процент в центре (синхронно, JS-анимация)
  const ringDuration = 1800;
  const ring = document.getElementById('finishRing');
  setTimeout(() => animateRing(ring, pct, ringDuration, 0), 100);
  animateNumber(document.getElementById('finishPct'), pct, ringDuration);

  // 4) Эффекты при высоком результате
  if (pct >= 80) {
    ring?.classList.add('is-high'); // если в CSS есть спец-эффект
    const canvas = document.getElementById('confetti');
    setTimeout(() => launchConfetti(canvas, 1.2, 140), ringDuration + 150);
  }
}

/* ===== Показ финала ===== */
function showFinish(){
  // спрятать рабочие части
  document.getElementById('swiperCards')?.classList.add('d-none');
  document.getElementById('cardTitle')?.classList.add('d-none');

  // спрятать прогресс целиком
  const pbWrap = pb?.closest('.progress');
  if (pbWrap) pbWrap.classList.add('d-none');
  if (pb){
    pb.classList.remove('progress-bar-animated', 'progress-bar-striped');
    pb.textContent = ''; // на финале не показываем «100%»
  }

  // спрятать нижнюю панель (учитывая, что теперь она не fixed-bottom)
  document.getElementById('actionsRow')?.classList.add('d-none');
  document.getElementById('learnFooter')?.classList.add('d-none');

  // показать финал
  const finish = document.getElementById('finish');
  if (finish){
    finish.classList.remove('d-none');

    startFinishAnimation({ known, learn: learning, total: TOTAL });

    const y = Math.max(0, finish.getBoundingClientRect().top + window.scrollY - 24);
    window.scrollTo({ top: y, behavior: 'smooth' });
  } else {
    console.warn('#finish в DOM');
  }
}



</script>


<?php endif; ?>
</body>
</html>
