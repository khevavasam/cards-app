<?php require __DIR__ . '/data/products.php'; ?>
<?php require_once __DIR__ . '/i18n.php'; ?> 

<!doctype html>
<html lang="<?= htmlspecialchars(i18n_lang()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?php _e('meta.description'); ?>">

  <title><?php _e('meta.title'); ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"/>

  <!-- Наш минимальный CSS -->
  <link href="style.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary py-2 sticky-top">
  <div class="container-xxl">
    <a class="navbar-brand d-flex align-items-center" href="#">
      <img src="assets/img/logo.png" alt="" width="50" height="50" class="flex-shrink-0">
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button"
            data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="<?php _e('nav.toggle'); ?>">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-lg-auto gap-lg-4 text-uppercase fw-semibold small mt-2 mt-lg-0">
        <li class="nav-item"><a class="nav-link link-light-quiet" href="#sets"><?php _e('nav.sets'); ?></a></li>
        <li class="nav-item"><a class="nav-link link-light-quiet" href="#demo"><?php _e('nav.demo'); ?></a></li>
        <li class="nav-item"><a class="nav-link link-light-quiet" href="#why"><?php _e('nav.why'); ?></a></li>
        <li class="nav-item"><a class="nav-link link-light-quiet" href="#about"><?php _e('nav.about'); ?></a></li>
        <li class="nav-item"><a class="nav-link link-light-quiet" href="#buy"><?php _e('nav.buy'); ?></a></li>
      </ul>

      <div class="d-flex align-items-center gap-3">
        <?php $cnt = array_sum($_SESSION['cart'] ?? []); ?>
        <a href="cart.php" class="btn btn-outline-light position-relative" aria-label="<?php _e('nav.cart'); ?>">
          <i class="bi bi-bag"></i>
          <?php if ($cnt): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
              <?= (int)$cnt ?>
            </span>
          <?php endif; ?>
        </a>

        <a class="text-white-50" href="#" aria-label="<?php _e('social.instagram'); ?>"><i class="bi bi-instagram fs-5"></i></a>
        <a class="text-white-50" href="#" aria-label="<?php _e('social.facebook'); ?>"><i class="bi bi-facebook fs-5"></i></a>
        <a class="text-white-50" href="#" aria-label="<?php _e('social.tiktok'); ?>"><i class="bi bi-tiktok fs-5"></i></a>

        <div class="dropdown">
          <button class="btn btn-sm btn-outline-light border-0 bg-transparent text-white-75 d-flex align-items-center gap-2"
                  data-bs-toggle="dropdown" aria-label="<?php _e('nav.lang.choose'); ?>">
            <span style="display:inline-block;width:22px;height:14px;background:
              linear-gradient(#003580 0 50%, #ffffff 50% 100%); border-radius:2px;"></span>
            <i class="bi bi-caret-down-fill"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="?lang=fi">Suomi</a></li>
            <li><a class="dropdown-item" href="?lang=en">English</a></li>
            <li><a class="dropdown-item" href="?lang=uk">Українська</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>


<!-- HERO -->
<section class="py-5 py-lg-6 hero-bg">
  <div class="container-fluid px-3 px-md-4 px-lg-5">
    <div class="row">
      <div class="col-12 col-lg-8 col-xl-7">
        <h1 class="display-5 fw-bold lh-1 mb-4">
          <?php _e('hero.title.line1'); ?><br class="d-none d-xl-inline">
          <?php _e('hero.title.line2'); ?>
        </h1>

        <p class="fs-5 fw-semibold text-body-emphasis mb-4">
          <?php _e('hero.subtitle'); ?>
        </p>

        <h2 class="fs-2 fw-bold mb-3"><?php _e('hero.question'); ?></h2>

        <div class="d-flex flex-wrap gap-3 mt-4 mt-lg-5">
          <a href="#fi" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-sm"><?php _e('hero.btn.fi'); ?></a>
          <a href="#ua" class="btn btn-outline-primary btn-lg rounded-pill px-5 py-3 shadow-sm"><?php _e('hero.btn.uk'); ?></a>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ====== ОБЩИЙ WRAPPER ДЛЯ ВСЕХ СЕКЦИЙ ПОСЛЕ ПЕРВОГО БЛОКА ====== -->
<main class="container-fluid px-4 px-sm-2 px-md-3 px-lg-3"
      data-bs-spy="scroll" data-bs-target="#mainNav" data-bs-offset="100" tabindex="0">
  <div class="row justify-content-center">
    <div class="col-12 col-xl-10 col-xxl-9">

      <!-- БЛОК: Впізнаєте себе? -->
      <section class="py-5">
        <div class="row align-items-center gx-2 gx-lg-5 gy-4">

          <!-- Фото слева -->
          <div class="col-12 col-lg-6 pe-lg-5 d-flex">
            <div class="shadow-sm rounded-3 overflow-hidden mx-auto mx-lg-0 me-lg-auto"
                 style="max-width:480px; width:100%;">
              <img src="assets/img/ФОТО2-1.jpg"
                   alt="<?php _e('recognize.img_alt'); ?>"
                   class="w-100 h-100 d-block"
                   style="aspect-ratio:1/1; object-fit:cover; object-position:center;">
            </div>
          </div>

          <!-- Текст справа (чуть крупнее) -->
          <div class="col-12 col-lg-6 ps-0 ps-lg-2">
            <h2 class="display-5 fw-bold mb-3 mb-lg-4"><?php _e('recognize.title'); ?></h2>

            <ul class="list-unstyled d-grid gap-3 gap-lg-3 mb-0">
              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/1-4.png" class="flex-shrink-0 mt-1" alt=""
                     style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('recognize.items.0'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/2-5.png" class="flex-shrink-0 mt-1" alt=""
                     style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('recognize.items.1'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/3-6.png" class="flex-shrink-0 mt-1" alt=""
                     style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('recognize.items.2'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/4-4.png" class="flex-shrink-0 mt-1" alt=""
                     style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('recognize.items.3'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/5-5.png" class="flex-shrink-0 mt-1" alt=""
                     style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('recognize.items.4'); ?></p>
              </li>
            </ul>
          </div>

        </div>
      </section>

      <section class="py-2 py-md-3">
        <h2 class="text-center fw-bold mb-0 fs-3 fs-md-2">
          <?php _e('wish.question'); ?>
        </h2>
      </section>



      <!-- БЛОК: Можна вивчати мову цікаво! -->
      <section class="py-5">
        <div class="row align-items-center gx-2 gx-lg-5 gy-4">

          <!-- Фото слева -->
          <div class="col-12 col-lg-6 pe-lg-5 d-flex">
            <div class="shadow-sm rounded-3 overflow-hidden mx-auto mx-lg-0 me-lg-auto"
                style="max-width:480px; width:100%;">
              <img src="assets/img/3-1.jpg"
                  alt="<?php _e('fun.img_alt'); ?>"
                  class="w-100 h-100 d-block"
                  style="aspect-ratio:1/1; object-fit:cover; object-position:center;">
            </div>
          </div>

          <!-- Текст справа -->
          <div class="col-12 col-lg-6 ps-0 ps-lg-2">
            <h2 class="display-6 fw-bold mb-3 mb-lg-3">
              <?php _e('fun.title.line1'); ?><br><?php _e('fun.title.line2'); ?>
            </h2>

            <ul class="list-unstyled d-grid gap-3 gap-lg-3 mb-0">
              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/6-4.png" class="flex-shrink-0 mt-1" alt="" style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('fun.items.0'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/7-3.png" class="flex-shrink-0 mt-1" alt="" style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('fun.items.1'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/8.png" class="flex-shrink-0 mt-1" alt="" style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('fun.items.2'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/9.png" class="flex-shrink-0 mt-1" alt="" style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('fun.items.3'); ?></p>
              </li>

              <li class="d-flex gap-3 align-items-start">
                <img src="assets/img/10.png" class="flex-shrink-0 mt-1" alt="" style="width:62px;height:62px;">
                <p class="mb-0 fs-5 fs-md-4 fs-lg-3"><?php _e('fun.items.4'); ?></p>
              </li>
            </ul>
          </div>

        </div>
      </section>

      <section class="pt-2 pt-md-3">
        <h2 class="text-center fw-bold mb-1 fs-3 fs-md-2">
          <?php _e('tagline.heading'); ?>
        </h2>
      </section>

      <section class="pt-5 pb-3 pt-md-5 pb-md-5">
        <h2 class="text-center display-6 fw-bold mb-3 mb-lg-3">
          <?php _e('help.heading'); ?>
        </h2>
      </section>




      <!-- БЛОК: 3 карточки-примеры -->
      <section class="pt-2 pb-4">
        <div class="row g-3 g-md-4">

          <!-- Карточка 1 -->
          <div class="col-12 col-md-6 col-lg-4">
            <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm p-3">
              <img src="assets/img/01.png" alt="<?php _e('examples.cards.0.img_alt'); ?>" class="img-fluid rounded-3 mb-3">
              <p class="mb-0 text-secondary">
                <span class="fw-semibold"><?php _e('examples.cards.0.title'); ?></span>
                <?php _e('examples.cards.0.text'); ?>
              </p>
            </div>
          </div>

          <!-- Карточка 2 -->
          <div class="col-12 col-md-6 col-lg-4">
            <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm p-3">
              <img src="assets/img/02.png" alt="<?php _e('examples.cards.1.img_alt'); ?>" class="img-fluid rounded-3 mb-3">
              <p class="mb-0 text-secondary">
                <span class="fw-semibold"><?php _e('examples.cards.1.title'); ?></span>
                <?php _e('examples.cards.1.text'); ?>
              </p>
            </div>
          </div>

          <!-- Карточка 3 -->
          <div class="col-12 col-md-6 col-lg-4">
            <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm p-3">
              <img src="assets/img/03.png" alt="<?php _e('examples.cards.2.img_alt'); ?>" class="img-fluid rounded-3 mb-3">
              <p class="mb-0 text-secondary">
                <span class="fw-semibold"><?php _e('examples.cards.2.title'); ?></span>
                <?php _e('examples.cards.2.text'); ?>
              </p>
            </div>
          </div>

        </div>
      </section>





      <section id="why" class="py-5 scroll-target">

        <!-- Ряд 1: заголовок -->
        <div class="row g-0">
          <div class="col-12 col-lg-6 offset-lg-6">
            <h2 class="fw-bold mb-2 mb-lg-2" style="font-size:clamp(2rem,3vw+1rem,3.2rem);line-height:1.5;">
              <?php _e('why.heading'); ?>
            </h2>
          </div>
        </div>

        <!-- Ряд 2: контент -->
        <div class="row g-0 align-items-start">
          <!-- левая колонка: картинка -->
          <div class="col-12 col-lg-6 pe-lg-5 pt-lg-2 d-flex">
            <div class="shadow-sm rounded-3 overflow-hidden mx-auto mx-lg-0 me-lg-auto" style="max-width:520px; width:100%;">
              <img src="assets/img/фото1.jpg" class="w-100 d-block"
                  style="aspect-ratio:4/3; object-fit:cover; object-position:center;"
                  alt="<?php _e('why.image_alt'); ?>">
            </div>
          </div>

          <!-- правая колонка: аккордеон -->
          <div class="col-12 col-lg-6 ps-0 ps-lg-2">
            <div class="accordion accordion-flush hk-acc" id="hk-features">

              <!-- item 1 -->
              <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="hk-h1">
                  <button class="accordion-button collapsed py-3" type="button"
                          data-bs-toggle="collapse" data-bs-target="#hk-c1"
                          aria-expanded="false" aria-controls="hk-c1">
                    <?php _e('why.items.0.title'); ?>
                  </button>
                </h2>
                <div id="hk-c1" class="accordion-collapse collapse"
                    aria-labelledby="hk-h1" data-bs-parent="#hk-features">
                  <div class="accordion-body">
                    <?php _e('why.items.0.body'); ?>
                  </div>
                </div>
              </div>

              <!-- item 2 -->
              <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="hk-h2">
                  <button class="accordion-button collapsed py-3" type="button"
                          data-bs-toggle="collapse" data-bs-target="#hk-c2"
                          aria-expanded="false" aria-controls="hk-c2">
                    <?php _e('why.items.1.title'); ?>
                  </button>
                </h2>
                <div id="hk-c2" class="accordion-collapse collapse"
                    aria-labelledby="hk-h2" data-bs-parent="#hk-features">
                  <div class="accordion-body">
                    <?php _e('why.items.1.body'); ?>
                  </div>
                </div>
              </div>

              <!-- item 3 -->
              <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="hk-h3">
                  <button class="accordion-button collapsed py-3" type="button"
                          data-bs-toggle="collapse" data-bs-target="#hk-c3"
                          aria-expanded="false" aria-controls="hk-c3">
                    <?php _e('why.items.2.title'); ?>
                  </button>
                </h2>
                <div id="hk-c3" class="accordion-collapse collapse"
                    aria-labelledby="hk-h3" data-bs-parent="#hk-features">
                  <div class="accordion-body">
                    <?php _e('why.items.2.body'); ?>
                  </div>
                </div>
              </div>

              <!-- item 4 -->
              <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="hk-h4">
                  <button class="accordion-button collapsed py-3" type="button"
                          data-bs-toggle="collapse" data-bs-target="#hk-c4"
                          aria-expanded="false" aria-controls="hk-c4">
                    <?php _e('why.items.3.title'); ?>
                  </button>
                </h2>
                <div id="hk-c4" class="accordion-collapse collapse"
                    aria-labelledby="hk-h4" data-bs-parent="#hk-features">
                  <div class="accordion-body">
                    <?php _e('why.items.3.body'); ?>
                  </div>
                </div>
              </div>

              <!-- item 5 -->
              <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="hk-h5">
                  <button class="accordion-button collapsed py-3" type="button"
                          data-bs-toggle="collapse" data-bs-target="#hk-c5"
                          aria-expanded="false" aria-controls="hk-c5">
                    <?php _e('why.items.4.title'); ?>
                  </button>
                </h2>
                <div id="hk-c5" class="accordion-collapse collapse"
                    aria-labelledby="hk-h5" data-bs-parent="#hk-features">
                  <div class="accordion-body">
                    <?php _e('why.items.4.body'); ?>
                  </div>
                </div>
              </div>

              <!-- item 6 -->
              <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="hk-h6">
                  <button class="accordion-button collapsed py-3" type="button"
                          data-bs-toggle="collapse" data-bs-target="#hk-c6"
                          aria-expanded="false" aria-controls="hk-c6">
                    <?php _e('why.items.5.title'); ?>
                  </button>
                </h2>
                <div id="hk-c6" class="accordion-collapse collapse"
                    aria-labelledby="hk-h6" data-bs-parent="#hk-features">
                  <div class="accordion-body">
                    <?php _e('why.items.5.body'); ?>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>
      </section>




            <section id="demo" class="py-5 scroll-target">
              <div class="container text-center mb-4 mb-lg-5">
                <h2 class="fw-bold mb-2" style="font-size:clamp(1.8rem, 1.2rem + 2.5vw, 3rem); line-height:1.1;">
                  <?php _e('demo.heading'); ?>
                </h2>
                <h3 class="fw-semibold mb-0" style="font-size:clamp(1.25rem, 1rem + .9vw, 1.8rem);">
                  <?php _e('demo.subheading'); ?>
                </h3>
              </div>
            </section>


            <!-- ТВОЯ ЛЕНТА-КОНВЕЙЕР -->
            <div class="scroller" style="--gap:24px; --speed:50s;">
                <div class="scroller__inner">
            <!-- ЭЛЕМЕНТЫ ЛЕНТЫ (только картинки, flip) -->
            <div class="flip-card">
                <div class="flip-inner">
                <div class="flip-front"><img src="assets/img/number_2.png"  alt=""></div>
                <div class="flip-back"><img  src="assets/img/number_2-2.png" alt=""></div>
                </div>
            </div>

            <div class="flip-card">
                <div class="flip-inner">
                <div class="flip-front"><img src="assets/img/21 kieli.png"  alt=""></div>
                <div class="flip-back"><img  src="assets/img/21 kieli2.png" alt=""></div>
                </div>
            </div>

            <div class="flip-card">
                <div class="flip-inner">
                <div class="flip-front"><img src="assets/img/9olla.png"  alt=""></div>
                <div class="flip-back"><img  src="assets/img/9olla2.png" alt=""></div>
                </div>
            </div>

            <div class="flip-card">
                <div class="flip-inner">
                <div class="flip-front"><img src="assets/img/38 todella.png"  alt=""></div>
                <div class="flip-back"><img  src="assets/img/38 todella2.png"  alt=""></div>
                </div>
            </div>

            <div class="flip-card">
                <div class="flip-inner">
                <div class="flip-front"><img src="assets/img/adj44.png"  alt=""></div>
                <div class="flip-back"><img  src="assets/img/adj44-2.png"  alt=""></div>
                </div>
            </div>
            <!-- добавляй ещё сколько нужно... -->
            </div>

            <div class="container text-center mt-4 mt-lg-5">
                <h3 class="fw-semibold mb-0" style="font-size:clamp(1.25rem, 1rem + .9vw, 1.8rem);">
                  <strong>Hyvä kielipää</strong> — <?php _e('brand.tagline'); ?>
                </h3>
            </div>
            </section>




            <section id="sets" class="py-5 scroll-target">
              <div class="row g-4">

                <?php
                // чтобы избежать копипаста
                $setsCards = [
                  ['img' => 'ФОТО5.1.jpg', 'i' => 0],
                  ['img' => 'ФОТО5.2.jpg', 'i' => 1],
                  ['img' => 'ФОТО5.3.jpg', 'i' => 2],
                ];
                ?>

                <?php foreach ($setsCards as $c): ?>
                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="h-100 p-3 rounded-4 border border-2 border-light-subtle shadow-sm">
                      <div class="ratio ratio-4x3 rounded-3 overflow-hidden mb-3">
                        <img src="assets/img/<?= htmlspecialchars($c['img']) ?>"
                            alt="<?php _e("sets.cards.{$c['i']}.img_alt"); ?>"
                            class="w-100 h-100 object-fit-cover">
                      </div>

                      <h5 class="text-center fw-semibold mb-3">
                        <?php echo __t("sets.cards.{$c['i']}.title"); // вместо _e() ?>
                      </h5>


                      <a href="#catalog"
                        data-cat="<?php _e("sets.cards.{$c['i']}.slug"); ?>"
                        class="btn btn-primary rounded-pill w-100 py-2 btn-ghost-hover js-open-catalog">
                        <?php _e('sets.view_btn'); ?>
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>

              </div>
            </section>



            <!-- Велика CTA кнопка під картками -->
            <div class="text-center mt-4 mt-lg-5">
              <a href="#"
                class="btn btn-primary btn-lg rounded-pill px-4 py-3 btn-ghost-hover">
                <?php _e('sets.cta'); ?>
              </a>
            </div>





            <!-- БЛОК: Хто ми? -->
            <section id="about" class="py-5 scroll-target">
              <div class="row align-items-center gx-1 gy-1">

                <!-- Левая колонка: текст -->
                <div class="col-12 col-lg-6">
                  <h1 class="display-4 fw-bold mb-3" style="line-height:1.05;">
                    <?php _e('about.heading'); ?>
                  </h1>

                  <div class="fs-5 lh-sm text-secondary">
                    <p class="mb-3"><?php _e('about.p1'); ?></p>
                    <p class="mb-3"><?php _e('about.p2'); ?></p>
                    <p class="mb-3"><?php _e('about.p3'); ?></p>
                    <p class="mb-3"><?php _e('about.p4'); ?></p>
                  </div>

                  <a href="#"
                    class="btn btn-primary btn-lg rounded-pill mt-4 px-4 py-3 btn-ghost-hover">
                    <?php _e('about.cta'); ?>
                  </a>
                </div>

                <!-- Правая колонка: изображение -->
                <div class="col-12 col-lg-6 ps-lg-3">
                  <div class="ratio ratio-4x3 rounded-3 shadow-sm overflow-hidden">
                    <img src="assets/img/10-1.jpg"
                        alt="<?php _e('about.img_alt'); ?>"
                        class="w-100 h-100 object-fit-cover">
                  </div>
                </div>

              </div>
            </section>




            <!-- ЩО ВСЕРЕДИНІ НАБОРУ -->
            <section class="py-5">
              <h2 class="text-center fw-bold mb-4 mb-lg-5" style="font-size:clamp(1.8rem,2.2vw+1rem,3rem);line-height:1.1;">
                <?php _e('inside.heading'); ?>
              </h2>

              <div class="row g-3 g-md-4">
                <!-- Карточка 1 -->
                <div class="col-12 col-md-6 col-lg-4">
                  <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm p-3">
                    <div class="ratio ratio-4x3 rounded-3 overflow-hidden mb-3">
                      <img src="assets/img/20-1.jpg" class="w-100 h-100 object-fit-cover" alt="<?php _e('inside.cards.0.img_alt'); ?>">
                    </div>
                    <p class="mb-0 text-secondary">
                      <span class="fw-semibold"><?php _e('inside.cards.0.title'); ?></span>
                      <?php _e('inside.cards.0.text'); ?>
                    </p>
                  </div>
                </div>

                <!-- Карточка 2 -->
                <div class="col-12 col-md-6 col-lg-4">
                  <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm p-3">
                    <div class="ratio ratio-4x3 rounded-3 overflow-hidden mb-3">
                      <img src="assets/img/20-2.png" class="w-100 h-100 object-fit-cover" alt="<?php _e('inside.cards.1.img_alt'); ?>">
                    </div>
                    <p class="mb-0 text-secondary">
                      <span class="fw-semibold"><?php _e('inside.cards.1.title'); ?></span>
                      <?php _e('inside.cards.1.text'); ?>
                    </p>
                  </div>
                </div>

                <!-- Карточка 3 -->
                <div class="col-12 col-md-6 col-lg-4">
                  <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm p-3">
                    <div class="ratio ratio-4x3 rounded-3 overflow-hidden mb-3">
                      <img src="assets/img/20-3.jpg" class="w-100 h-100 object-fit-cover" alt="<?php _e('inside.cards.2.img_alt'); ?>">
                    </div>
                    <p class="mb-0 text-secondary">
                      <span class="fw-semibold"><?php _e('inside.cards.2.title'); ?></span>
                      <?php _e('inside.cards.2.text'); ?>
                    </p>
                  </div>
                </div>
              </div>

              <!-- Большая кнопка снизу -->
              <div class="text-center mt-4 mt-lg-5">
                <a href="#buy" class="btn btn-primary btn-lg rounded-pill fw-bold px-4 py-3 shadow-sm btn-ghost-hover">
                  <?php _e('inside.shipping_cta'); ?>
                </a>
              </div>
            </section>

            



            <!-- ====== Відгуки ====== -->
            <section class="py-5">
              <h2 class="text-center fw-bold mb-4 mb-lg-5" style="font-size:clamp(2rem,2.4vw+1rem,3.2rem);line-height:1.1;">
                <?php _e('reviews.heading'); ?>
              </h2>

              <div class="swiper hk-reviews">
                <div class="swiper-wrapper">
                  <?php
                  // индексы соответствуют reviews.items.[i]
                  $reviewIndices = range(0, 9); // у тебя 10 слайдов ниже; можно уменьшить/увеличить
                  foreach ($reviewIndices as $i): ?>
                    <div class="swiper-slide">
                      <div class="h-100 rounded-4 border border-2 border-light-subtle shadow-sm<?php echo in_array($i, [3,4,5,6,7,8,9]) ? ' p-3' : ''; ?>">
                        <?php if (in_array($i, [0,1,2])): // первые три со шапкой-бордером ?>
                          <div class="p-3 border-bottom">
                            <div class="fw-semibold">
                              <?php _e("reviews.items.$i.name"); ?><?php _e("reviews.items.$i.location"); ?>
                            </div>
                            <div class="text-warning">
                              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                              <i class="bi bi-star-fill"></i>
                            </div>
                          </div>
                          <div class="p-3 text-secondary">
                            <?php _e("reviews.items.$i.text"); ?>
                          </div>
                        <?php else: // остальные в компактном формате ?>
                          <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">
                              <?php _e("reviews.items.$i.name"); ?><?php _e("reviews.items.$i.location"); ?>
                            </div>
                            <div class="text-warning small">
                              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                              <i class="bi bi-star-fill"></i>
                            </div>
                          </div>
                          <div class="text-secondary small lh-base">
                            <?php _e("reviews.items.$i.text"); ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- стрелки / точки -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-pagination d-lg-none"></div>
              </div>
            </section>




        <!-- ГАРАНТІЯ ЯКОСТІ ТА ПОВЕРНЕННЯ -->
        <section class="py-5">
          <div class="row">
            <h2 class="col-12 fw-bold mb-4"
                style="font-size:clamp(2rem,2.2vw+1rem,3.2rem);line-height:1.1;">
              <?php _e('guarantee.heading'); ?>
            </h2>
          </div>

          <div class="row g-4 g-lg-5">
            <!-- ЛЕВАЯ КОЛОНКА -->
            <div class="col-12 col-lg-6 d-grid gap-4">

              <!-- 15.png — Умови повернення -->
              <div class="d-flex align-items-start gap-3">
                <img src="assets/img/15.png" alt="<?php _e('guarantee.left.0.img_alt'); ?>" class="flex-shrink-0" style="width:70px;height:70px;object-fit:contain;">
                <div>
                  <h5 class="fw-bold mb-2"><?php _e('guarantee.left.0.title'); ?></h5>
                  <p class="mb-0 text-secondary"><?php _e('guarantee.left.0.text'); ?></p>
                </div>
              </div>

              <!-- 16.png — Повернення та обмін -->
              <div class="d-flex align-items-start gap-3">
                <img src="assets/img/16.png" alt="<?php _e('guarantee.left.1.img_alt'); ?>" class="flex-shrink-0" style="width:70px;height:70px;object-fit:contain;">
                <div>
                  <h5 class="fw-bold mb-2"><?php _e('guarantee.left.1.title'); ?></h5>
                  <p class="mb-0 text-secondary"><?php _e('guarantee.left.1.text'); ?></p>
                </div>
              </div>

              <!-- 8.png — Гарантія якості -->
              <div class="d-flex align-items-start gap-3">
                <img src="assets/img/8.png" alt="<?php _e('guarantee.left.2.img_alt'); ?>" class="flex-shrink-0" style="width:70px;height:70px;object-fit:contain;">
                <div>
                  <h5 class="fw-bold mb-2"><?php _e('guarantee.left.2.title'); ?></h5>
                  <p class="mb-0 text-secondary"><?php _e('guarantee.left.2.text'); ?></p>
                </div>
              </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА -->
            <div class="col-12 col-lg-6 d-grid gap-4">
              <!-- 17.png — Підтримка у чаті -->
              <div class="d-flex align-items-start gap-3">
                <img src="assets/img/17.png" alt="<?php _e('guarantee.right.0.img_alt'); ?>" class="flex-shrink-0" style="width:70px;height:70px;object-fit:contain;">
                <div>
                  <h5 class="fw-bold mb-2"><?php _e('guarantee.right.0.title'); ?></h5>
                  <p class="mb-0 text-secondary"><?php _e('guarantee.right.0.text'); ?></p>
                </div>
              </div>

              <!-- 18.png — Наші контакти -->
              <div class="d-flex align-items-start gap-3">
                <img src="assets/img/18.png" alt="<?php _e('guarantee.right.1.img_alt'); ?>" class="flex-shrink-0" style="width:70px;height:70px;object-fit:contain;">
                <div>
                  <h5 class="fw-bold mb-2"><?php _e('guarantee.right.1.title'); ?></h5>
                  <p class="mb-0 text-secondary">
                    <?php _e('guarantee.right.1.text'); ?>
                  </p>
                </div>
              </div>

              <!-- Слоган справа -->
              <div class="pt-2">
                <h3 class="fw-bold mb-0" style="font-size:clamp(1.4rem,1.6vw+1rem,2rem);">
                  <?php _e('guarantee.slogan'); ?>
                </h3>
              </div>
            </div>
          </div>
        </section>

    
                
        <!-- ЧАСТІ ЗАПИТАННЯ (FAQ) -->
        <section class="py-5">
          <div class="row">
            <h2 class="col-12 fw-bold text-center mb-4"
                style="font-size:clamp(2rem,2.2vw+1rem,3.2rem);line-height:1.1;">
              <?php _e('faq.heading'); ?>
            </h2>
          </div>

          <div class="row gx-3 gy-4">
            <!-- Левая колонка -->
            <div class="col-12 col-lg-6">
              <div class="accordion accordion-flush hk-acc" id="hk-faq-left">
                <?php for ($i=0; $i<5; $i++): ?>
                  <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                    <h2 class="accordion-header" id="faq-l<?= $i ?>-h">
                      <button class="accordion-button collapsed py-3" type="button"
                              data-bs-toggle="collapse" data-bs-target="#faq-l<?= $i ?>"
                              aria-expanded="false" aria-controls="faq-l<?= $i ?>">
                        <?php _e("faq.left.$i.q"); ?>
                      </button>
                    </h2>
                    <div id="faq-l<?= $i ?>" class="accordion-collapse collapse"
                        aria-labelledby="faq-l<?= $i ?>-h" data-bs-parent="#hk-faq-left">
                      <div class="accordion-body"><?php _e("faq.left.$i.a"); ?></div>
                    </div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Правая колонка -->
            <div class="col-12 col-lg-6">
              <div class="accordion accordion-flush hk-acc" id="hk-faq-right">
                <?php for ($i=0; $i<5; $i++): ?>
                  <div class="accordion-item mb-3 rounded-4 overflow-hidden shadow-sm">
                    <h2 class="accordion-header" id="faq-r<?= $i ?>-h">
                      <button class="accordion-button collapsed py-3" type="button"
                              data-bs-toggle="collapse" data-bs-target="#faq-r<?= $i ?>"
                              aria-expanded="false" aria-controls="faq-r<?= $i ?>">
                        <?php _e("faq.right.$i.q"); ?>
                      </button>
                    </h2>
                    <div id="faq-r<?= $i ?>" class="accordion-collapse collapse"
                        aria-labelledby="faq-r<?= $i ?>-h" data-bs-parent="#hk-faq-right">
                      <div class="accordion-body"><?php _e("faq.right.$i.a"); ?></div>
                    </div>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <!-- CTA под блоком -->
          <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 mt-4">
            <a href="#buy" class="btn btn-primary btn-lg rounded-pill px-4 py-3">
              <?php _e('faq.cta_order_btn'); ?>
            </a>
            <a href="#contact" class="btn btn-outline-primary btn-lg rounded-pill px-4 py-3">
              <?php _e('faq.cta_contact_btn'); ?>
            </a>
          </div>
        </section>




                <!-- БЛОК: Карта / Де ми знаходимося -->
        <section class="py-5">
        <div class="row">
            <h2 class="col-12 fw-bold text-center mb-4"
                style="font-size:clamp(2rem, 2.2vw + 1rem, 3.2rem);line-height:1.1;">
            Де ми знаходимося
            </h2>
        </div>

        <!-- Респонсивная карта -->
        <div class="ratio rounded-4 shadow-sm overflow-hidden" style="--bs-aspect-ratio: 55%;">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d56298.54524290838!2d23.130036!3d63.835182999999994!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46874892647e505d%3A0x21df575e47864cc0!2sIt%C3%A4inen%20Kirkkokatu%202a%2044%2C%2067100%20Kokkola%2C%20Finland!5e0!3m2!1sen!2sus!4v1760749911468!5m2!1sen!2sus"
            class="w-100 h-100"
            style="border:0;"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            aria-label="<?php _e('map.aria_label'); ?>">
          </iframe>
        </div>

        <!-- Кнопки под картой -->
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 mt-4">
          <a href="https://maps.google.com/?q=It%C3%A4inen+Kirkkokatu+2a+44,+Kokkola"
            target="_blank" class="btn btn-primary rounded-pill px-4 py-3">
            <?php _e('map.open_btn'); ?>
          </a>
          <a href="#contact" class="btn btn-outline-primary rounded-pill px-4 py-3">
            <?php _e('map.contact_btn'); ?>
          </a>
        </div>



      <!-- …дальше любые секции, просто добавляй <section class="py-5"> … </section> … -->


    </div>
  </div>
</main>

<!-- ====== FOOTER / Контакти + копирайт ====== -->
<section class="py-5 text-white" style="
  --bg:#188bd6;
  background:
    repeating-linear-gradient(135deg, rgba(255,255,255,.08) 0 8px, rgba(255,255,255,0) 8px 16px),
    var(--bg);
">
  <div class="container-fluid px-2 px-sm-3 px-md-4 px-lg-5">
    <div class="row g-4 align-items-center">

      <!-- Лого + слоган -->
      <div class="col-12 col-lg-6 d-flex align-items-center gap-3">
        <img src="assets/img/logo.png" alt="<?php _e('footer.logo_alt'); ?>" width="100" height="100" class="flex-shrink-0">
        <div>
          <h3 class="h4 fw-bold mb-1"><?php _e('brand.name'); ?> —</h3>
          <p class="mb-0 fs-5"><?php _e('brand.tagline'); ?></p>
        </div>
      </div>

      <!-- Контактна інформація -->
      <div class="col-12 col-lg-6">
        <h3 class="h4 fw-bold mb-3"><?php _e('footer.contact_heading'); ?></h3>

        <ul class="list-unstyled d-grid gap-2 fs-5 mb-0">
          <li class="d-flex align-items-start gap-3">
            <i class="bi bi-geo-alt-fill fs-4 lh-1"></i>
            <span><?php _e('footer.address'); ?></span>
          </li>
          <li class="d-flex align-items-start gap-3">
            <i class="bi bi-telephone-fill fs-4 lh-1"></i>
            <a class="link-light text-decoration-none" href="tel:+358451722772">+358 45 1722772</a>
          </li>
          <li class="d-flex align-items-start gap-3">
            <i class="bi bi-envelope-fill fs-4 lh-1"></i>
            <a class="link-light text-decoration-none" href="mailto:hyva.kielipaa@gmail.com">hyva.kielipaa@gmail.com</a>
          </li>
        </ul>
      </div>

    </div>

    <!-- Низ футера: копирайт + соцсети -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 pt-3 mt-4"
         style="border-top:1px solid rgba(255,255,255,.35)">
      <div class="small opacity-100">
        <?php _e('footer.copyright', ['year' => date('Y')]); ?>
      </div>

      <div class="d-flex align-items-center gap-3 fs-3">
        <a class="link-light" href="https://instagram.com" target="_blank" aria-label="<?php _e('social.instagram'); ?>"><i class="bi bi-instagram"></i></a>
        <a class="link-light" href="https://facebook.com" target="_blank" aria-label="<?php _e('social.facebook'); ?>"><i class="bi bi-facebook"></i></a>
        <a class="link-light" href="https://tiktok.com" target="_blank" aria-label="<?php _e('social.tiktok'); ?>"><i class="bi bi-tiktok"></i></a>
      </div>
    </div>
  </div>
</section>





<div class="modal fade" id="catalog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header bg-body sticky-top">
        <h2 id="catTitle" class="modal-title fs-4 fw-bold mb-0"></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="catList" class="d-grid gap-3 gap-md-4"></div>
      </div>
    </div>
  </div>
</div>






<!-- ====== /WRAPPER ====== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>


<script>
  const scrollers = document.querySelectorAll(".scroller");

  if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    addAnimation();
  }

  function addAnimation() {
    scrollers.forEach((scroller) => {
      scroller.setAttribute("data-animated", true);

      const scrollerInner = scroller.querySelector(".scroller__inner");
      const scrollerContent = Array.from(scrollerInner.children);

      // Дублируем элементы для бесшовной прокрутки
      scrollerContent.forEach((item) => {
        const duplicatedItem = item.cloneNode(true);
        duplicatedItem.setAttribute("aria-hidden", true);
        scrollerInner.appendChild(duplicatedItem);
      });
    });
  }
</script>



<script>
  // Авто-свайп отзывов
  new Swiper('.hk-reviews', {
    slidesPerView: 1,
    spaceBetween: 16,

    // автопрокрутка
    loop: true,                     // чтобы вертелось по кругу
    speed: 600,                     // скорость анимации (мс)
    autoplay: {
      delay: 3500,                  // пауза между слайдами
      disableOnInteraction: false,  // не останавливать после действий
      pauseOnMouseEnter: true       // при наведении — пауза (удали, если не нужно)
    },

    autoHeight: true,
    navigation: {
      nextEl: '.hk-reviews .swiper-button-next',
      prevEl: '.hk-reviews .swiper-button-prev',
    },
    pagination: {
      el: '.hk-reviews .swiper-pagination',
      clickable: true,
    },
    breakpoints: {
      768:  { slidesPerView: 2, spaceBetween: 20 },
      992:  { slidesPerView: 3, spaceBetween: 22 }
    }
  });
</script>



<script>
  // PHP → JS (локализованные продукты и подписи)
  <?php
    require_once __DIR__ . '/i18n.php';
    require __DIR__ . '/data/products.php';

    // helper: локализуем title/desc по id
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

    $CAT_TITLES = [
      'lexicon' => __t('catalog.cat.lexicon'),
      'grammar' => __t('catalog.cat.grammar'),
      'digital' => __t('catalog.cat.digital'),
    ];

    // короткие UI-строки
    $CAT_UI = [
      'add'           => __t('catalog.add'),
      'cards_fmt'     => __t('catalog.cards_fmt'),      // "({n} карток)" / "({n} korttia)" / "({n} cards)"
      'price_fmt'     => __t('catalog.price_fmt'),      // "{n}€"
      'old_price_fmt' => __t('catalog.old_price_fmt'),  // "{n}€"
      'promo_until'   => __t('catalog.promo_until'),    // "акція діє до {date}" и т.п.
      'cta_order'     => __t('catalog.cta.order'),
      'cta_request'   => __t('catalog.cta.request'),
    ];
  ?>
  window.PRODUCTS   = <?= json_encode($PRODUCTS_L10N, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
  window.CAT_TITLES = <?= json_encode($CAT_TITLES, JSON_UNESCAPED_UNICODE) ?>;
  window.CAT_UI     = <?= json_encode($CAT_UI, JSON_UNESCAPED_UNICODE) ?>;
  window.CART_URL   = "<?= i18n_link('cart.php') ?>";
  window.LOCALE     = "<?= i18n_lang() ?>";
</script>
<script>
  // локаль для toLocaleDateString
  const mapLocale = l => (l==='fi' ? 'fi-FI' : l==='en' ? 'en-US' : 'uk-UA');

  const fmtPrice = v => (v == null ? '' : CAT_UI.price_fmt.replace('{n}', v));
  const fmtDate  = iso => {
    if (!iso) return '';
    const d = new Date(iso + 'T00:00:00');
    return d.toLocaleDateString(mapLocale(window.LOCALE));
  };

function renderItem(p){
  const old   = (p.old!=null)   ? `<del class="me-2 opacity-75">${CAT_UI.old_price_fmt.replace('{n}', p.old)}</del>` : '';
  const price = (p.price!=null) ? `<span class="text-danger fw-bold fs-4">${fmtPrice(p.price)}</span>` : '';
  const until = p.until ? `<div class="small text-secondary">${CAT_UI.promo_until.replace('{date}', fmtDate(p.until))}</div>` : '';
  const cards = p.cards ? CAT_UI.cards_fmt.replace('{n}', p.cards) : '';
  const cta   = (p.price!=null) ? CAT_UI.cta_order : CAT_UI.cta_request;

  // ВАЖНО: корректный разделитель
  const sep = window.CART_URL.includes('?') ? '&' : '?';
  const cartAddUrl = window.CART_URL + sep + 'add=' + encodeURIComponent(p.id);

  return `
    <div class="card shadow-sm border rounded-4">
      <div class="card-body">
        <div class="row g-3 g-lg-4 align-items-center">
          <div class="col-5 col-md-4 col-lg-3">
            <div class="ratio ratio-4x3">
              <img src="${p.img||''}" alt="${p.title||''}"
                   class="img-fluid rounded-3"
                   onerror="this.onerror=null;this.src='data/images/placeholder.jpg'">
            </div>
          </div>

          <div class="col-7 col-md-8 col-lg-9">
            <h3 class="h4 fw-bold mb-1">${p.title||''}</h3>
            <div class="text-secondary mb-2">${cards}</div>

            <div class="d-flex align-items-baseline mb-1">
              ${old}${price}
            </div>
            ${until}

            <p class="mt-3 mb-3 text-secondary mb-lg-4">${p.desc||''}</p>

            <a href="${cartAddUrl}"
               class="btn btn-primary rounded-pill px-4 py-2 btn-ghost-hover">
               ${cta}
            </a>
          </div>
        </div>
      </div>
    </div>`;
}


  function openCatalog(cat){
    const list = (window.PRODUCTS || []).filter(p => p.cat === cat);
    document.getElementById('catTitle').textContent = (window.CAT_TITLES && window.CAT_TITLES[cat]) ? window.CAT_TITLES[cat] : cat;
    document.getElementById('catList').innerHTML = list.map(renderItem).join('');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('catalog')).show();
  }

  // кнопки "Переглянути" (sets)
  document.querySelectorAll('.js-open-catalog').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      openCatalog(btn.dataset.cat);
    });
  });
</script>



<script>
  // ===== Cart logic (vanilla JS, Bootstrap-agnostic) =====
  const EURO = v => Number(v).toFixed(0); // округляем до целых €

  function recalc() {
    let subtotal = 0;

    document.querySelectorAll('.cart-item').forEach(item => {
      const price = Number(item.dataset.price || 0);
      const qtyInput = item.querySelector('.quantity-input');
      let qty = parseInt(qtyInput.value, 10);
      if (isNaN(qty) || qty < 1) qty = 1;
      qtyInput.value = qty;

      const itemTotal = price * qty;
      const totalEl = item.querySelector('.item-total');
      if (totalEl) totalEl.textContent = EURO(itemTotal);
      subtotal += itemTotal;
    });

    const discount = Number(document.body.dataset.discount || 0);
    const shipping = subtotal >= 100 || subtotal === 0 ? 0 : 6;
    const total = Math.max(subtotal - discount, 0) + shipping;

    const set = (id, val, withEuro=false) => {
      const el = document.getElementById(id);
      if (el) el.textContent = withEuro ? `${EURO(val)}€` : EURO(val);
    };

    set('subtotal', subtotal);
    set('discount', discount);
    set('shipping', shipping, true);
    set('total', total);
  }

  // +/- кол-во
  document.addEventListener('click', e => {
    const btn = e.target.closest('.btn-qty');
    if (!btn) return;

    const item = btn.closest('.cart-item');
    const input = item.querySelector('.quantity-input');
    const step = Number(btn.dataset.step || 0);

    let val = parseInt(input.value, 10) || 1;
    val = Math.max(1, val + step);
    input.value = val;
    recalc();
  });

  // ручной ввод кол-ва (только цифры)
  document.addEventListener('input', e => {
    if (!e.target.classList.contains('quantity-input')) return;
    e.target.value = e.target.value.replace(/[^\d]/g, '');
    recalc();
  });

  // удалить товар
  document.addEventListener('click', e => {
    const rm = e.target.closest('.btn-remove');
    if (!rm) return;
    const item = rm.closest('.cart-item');
    item.remove();
    recalc();
  });

  // промокод
  const promoBtn = document.getElementById('applyPromo');
  if (promoBtn) {
    promoBtn.addEventListener('click', () => {
      const input = document.getElementById('promoInput');
      const code = (input?.value || '').trim().toUpperCase();
      const subtotal = Number(document.getElementById('subtotal')?.textContent || 0);
      let discount = 0;

      if (code === 'SAVE10') {
        discount = Math.round(subtotal * 0.10);
      } else if (code) {
        // тут можно добавить другие коды
        discount = 0;
      }
      document.body.dataset.discount = discount;
      recalc();
    });
  }

  // первый пересчёт
  recalc();
</script>
